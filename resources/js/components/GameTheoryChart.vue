<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'

const props = defineProps({
  triviaStats:      { type: Object, default: () => ({}) },
  recentGames:      { type: Array,  default: () => [] },
  userHistory:      { type: Object, default: () => ({}) },
  inferredSliders:  { type: Object, default: null },
  realStrategyData: { type: Object, default: () => ({}) },
})

// ── Sliders ────────────────────────────────────────────────────────────────
// round:          game round (affects base score)
// pUserCorrect:   AI's estimate of how likely YOU answer correctly (0-100%)
// pAiCorrect:     AI's estimated accuracy — affects user's steal decision
//                 (replaces the old P(steal) slider which was redundant with the x-axis)

const round        = ref(1)
const pUserCorrect = ref(40)   // P(you correct) — affects AI decoy/gamble thresholds
const pAiCorrect   = ref(65)   // P(AI correct) — NEW: drives whether stealing is worth it for the user
const slidersFromData = ref(false)

watch(() => props.inferredSliders, (val) => {
  if (val && val.observations > 0) {
    if (val.p_user_correct != null) pUserCorrect.value = Math.round(val.p_user_correct * 100)
    slidersFromData.value = true
  }
}, { immediate: true })

const chartRef = ref(null)
let chartInstance = null

const viewMode = ref('user') // 'user' | 'ai'

// User strategies mirror AI strategies but from the user's perspective:
// W = answer correctly, no steal   → safe play, +base if correct
// X = answer correctly + steal      → aggressive steal, +(base+1) if AI answered right
// Y = answer wrong, no steal        → decoy, punishes AI steal
// Z = answer wrong + steal          → gamble, only if P(AI correct) > breakeven
const COLORS = { W: '#378ADD', X: '#1D9E75', Y: '#D85A30', Z: '#7F77DD', opt: '#888780' }
const LABELS  = { W: 'correct, no steal', X: 'correct + steal', Y: 'wrong (decoy)', Z: 'wrong + steal' }
const DATASET_INDEX = { W: 0, X: 1, Y: 2, Z: 3 }
const AI_DATASET_INDEX = { A: 0, B: 1, C: 2, D: 3 }

const AI_COLORS = { A: '#378ADD', B: '#1D9E75', C: '#D85A30', D: '#7F77DD' }
const AI_LABELS = { A: 'correct, no steal', B: 'correct + steal', C: 'wrong (decoy)', D: 'wrong + steal' }

const baseScore = computed(() => 1 + (round.value - 1))

const xAxisRate = computed(() => viewMode.value === 'ai' ? (uh.value?.steal_rate ?? 0) : aiStealRate.value)
const xAxisRateMarker = ref(null)

function updateXAxisRateMarker() {
  if (!chartInstance) return
  const idx  = Math.min(Math.round(xAxisRate.value * 100), 100)
  const meta = chartInstance.getDatasetMeta(0)
  if (!meta?.data?.[idx]) return
  const pt   = meta.data[idx]
  const area = chartInstance.chartArea
  const label = viewMode.value === 'ai'
    ? `user ${Math.round(xAxisRate.value * 100)}%`
    : `AI ${Math.round(xAxisRate.value * 100)}%`
  xAxisRateMarker.value = { x: pt.x, top: area.top, bottom: area.bottom, label }
}

const secondaryRate = computed(() => viewMode.value === 'ai' ? aiStealRate.value : (uh.value?.steal_rate ?? null))
const secondaryRateMarker = ref(null)

function updateSecondaryRateMarker() {
  if (!chartInstance) return
  const rate = secondaryRate.value
  if (rate == null) return
  const idx  = Math.min(Math.round(rate * 100), 100)
  const meta = chartInstance.getDatasetMeta(0)
  if (!meta?.data?.[idx]) return
  const pt   = meta.data[idx]
  const area = chartInstance.chartArea
  const label = viewMode.value === 'ai'
    ? `AI ${Math.round(rate * 100)}%`
    : `you ${Math.round(rate * 100)}%`
  secondaryRateMarker.value = { x: pt.x, top: area.top, bottom: area.bottom, label }
}

// ── Environment laws — now from USER perspective ──────────────────────────
// x-axis = AI steal rate (p)
// y-axis = USER expected points
// pA = P(AI correct) from slider — drives steal value for user
// pU = P(user correct) from slider — drives safe-play value for user

function stealBreakeven(b, pU = 0) {
  return (b * (1 + pU)) / (2 * b + 1)
}
function userExpectedPayoff(strategy, p, b, pU, pA) {
  // p  = AI steal rate (x axis)
  // pU = P(user correct) from slider
  // pA = P(AI correct) from slider
  // b  = base score

  // User's steal EV: if user steals from AI's correct answer → +(b+1)
  //                  if user steals from AI's wrong answer   → -(b)
  const evUserSteal   = pA * (b + 1) + (1 - pA) * (-b)

  // User's safe play EV: correct answer no steal → +b, wrong → 0
  const evUserNoSteal = pU * b + (1 - pU) * 0

  switch (strategy) {
    case 'W':
      // Safe correct play: user scores +b when AI doesn't steal from them,
      // 0 when AI steals (AI takes their answer)
      return (1 - p) * pU * b + p * 0

    case 'X':
      // User answers correctly AND steals
      // User's steal payoff depends on whether AI answered correctly
      return evUserSteal

    case 'Y':
      // User answers wrong, doesn't steal (decoy trap)
      // If AI steals from user's wrong answer → AI loses b pts (good for user, but user earns 0)
      // Near zero for user themselves — purely defensive value
      return p * (-b * 0.1) + (1 - p) * 0

    case 'Z':
      // User answers wrong + steals — only rational above breakeven of P(AI correct)
      if (pA < stealBreakeven(b, pU)) return -b * (1 - pA)
      return evUserSteal * 0.8  // Discounted vs X since wrong answer adds risk
  }
}

function aiExpectedPayoff(strategy, p, b, pU, pA) {
  // p  = USER steal rate (x axis in AI view)
  // pU = P(user correct)
  // pA = P(AI correct)
  const evAiSteal   = pU * (b + 1) + (1 - pU) * (-b)
  const evAiNoSteal = pA * b

  switch (strategy) {
    case 'A': return (1 - p) * pA * b + p * 0
    case 'B': return evAiSteal
    case 'C': return p * (-b * 0.1) + (1 - p) * 0
    case 'D':
      if (pU < stealBreakeven(b, pU)) return -b * (1 - pU)
      return evAiSteal * 0.8
  }
}

function buildChartData() {
  const xs = Array.from({ length: 101 }, (_, i) => i / 100)
  const b  = baseScore.value
  const pU = pUserCorrect.value / 100
  const pA = pAiCorrect.value / 100
  const isAi = viewMode.value === 'ai'
  const strategies = isAi ? ['A','B','C','D'] : ['W','X','Y','Z']
  const payoffFn = isAi ? aiExpectedPayoff : userExpectedPayoff

  const datasets = strategies.map(s => ({
    label: s,
    data: xs.map(p => parseFloat(payoffFn(s, p, b, pU, pA).toFixed(3))),
    borderColor: isAi ? AI_COLORS[s] : COLORS[s], borderWidth: 2,
    pointRadius: 0, pointHoverRadius: 0, tension: 0, type: 'line', order: 1,
  }))

  const opt = xs.map((_, i) => parseFloat(
    Math.max(...strategies.map(s => datasets.find(d => d.label === s).data[i])).toFixed(3)
  ))
  datasets.push({
    label: 'optimal', data: opt,
    borderColor: COLORS.opt, borderWidth: 2, borderDash: [5,3],
    pointRadius: 0, pointHoverRadius: 0, tension: 0, type: 'line', order: 1,
  })

  return { labels: xs.map(x => x.toFixed(2)), datasets }
}

// ── Optimal point ──────────────────────────────────────────────────────────

function getOptimalPoint() {
  const xs = Array.from({ length: 101 }, (_, i) => i / 100)
  const b  = baseScore.value
  const pU = pUserCorrect.value / 100
  const pA = pAiCorrect.value / 100
  const isAi = viewMode.value === 'ai'
  const strategies = isAi ? ['A','B','C','D'] : ['W','X','Y','Z']
  const payoffFn = isAi ? aiExpectedPayoff : userExpectedPayoff
  let maxVal = -Infinity, maxIdx = 0
  xs.forEach((p, i) => {
    const v = Math.max(...strategies.map(s => payoffFn(s, p, b, pU, pA)))
    if (v > maxVal) { maxVal = v; maxIdx = i }
  })
  return { x: xs[maxIdx], y: parseFloat(maxVal.toFixed(2)), idx: maxIdx }
}

const optimalPoint  = computed(() => getOptimalPoint())
const optimalCircle = ref(null)

function updateOptimalCircle() {
  if (!chartInstance) return
  const meta  = chartInstance.getDatasetMeta(4)
  if (!meta?.data) return
  const point = meta.data[optimalPoint.value.idx]
  if (!point) return
  optimalCircle.value = { x: point.x, y: point.y }
}

// ── Intersections ──────────────────────────────────────────────────────────

function findPairIntersection(s1, s2) {
  const xs = Array.from({ length: 1000 }, (_, i) => i / 1000)
  const b  = baseScore.value
  const pU = pUserCorrect.value / 100
  const pA = pAiCorrect.value / 100
  const isAi = viewMode.value === 'ai'
  const payoffFn = isAi ? aiExpectedPayoff : userExpectedPayoff
  for (let i = 1; i < xs.length; i++) {
    const v1p = payoffFn(s1, xs[i-1], b, pU, pA)
    const v2p = payoffFn(s2, xs[i-1], b, pU, pA)
    const v1c = payoffFn(s1, xs[i],   b, pU, pA)
    const v2c = payoffFn(s2, xs[i],   b, pU, pA)
    if ((v1p - v2p) * (v1c - v2c) < 0) {
      const ix = xs[i]
      if (ix < 0 || ix > 1) return null
      return { x: ix, y: parseFloat(((v1c + v2c) / 2).toFixed(2)), idx: Math.min(Math.round(ix * 100), 100), label: `${s1}↔${s2}`, s1, s2 }
    }
  }
  return null
}

function circleFromIntersection(ip) {
  if (!chartInstance || !ip) return null
  const isAi = viewMode.value === 'ai'
  const idx1 = isAi ? AI_DATASET_INDEX[ip.s1] : DATASET_INDEX[ip.s1]
  const idx2 = isAi ? AI_DATASET_INDEX[ip.s2] : DATASET_INDEX[ip.s2]
  const meta1 = chartInstance.getDatasetMeta(idx1)
  const meta2 = chartInstance.getDatasetMeta(idx2)
  if (!meta1?.data || !meta2?.data) return null
  const pt1 = meta1.data[ip.idx], pt2 = meta2.data[ip.idx]
  if (!pt1 || !pt2) return null
  const colors = isAi ? AI_COLORS : COLORS
  return { x: pt1.x, y: (pt1.y + pt2.y) / 2, color: colors[ip.s1] }
}

const xwPoint = computed(() => viewMode.value === 'ai' ? findPairIntersection('B', 'A') : findPairIntersection('X', 'W'))
const xzPoint = computed(() => viewMode.value === 'ai' ? findPairIntersection('B', 'D') : findPairIntersection('X', 'Z'))
const wzPoint = computed(() => viewMode.value === 'ai' ? findPairIntersection('A', 'D') : findPairIntersection('W', 'Z'))
const xwCircle = ref(null), xzCircle = ref(null), wzCircle = ref(null)
function updateXwCircle() { xwCircle.value = circleFromIntersection(xwPoint.value) }
function updateXzCircle() { xzCircle.value = circleFromIntersection(xzPoint.value) }
function updateWzCircle() { wzCircle.value = circleFromIntersection(wzPoint.value) }

const minimaxLabel = computed(() => {
  const candidates = [xwPoint.value, xzPoint.value, wzPoint.value].filter(Boolean)
  if (!candidates.length) return null
  return candidates.reduce((min, p) => p.y < min.y ? p : min).label
})

// ── AI steal rate marker (from userHistory) ────────────────────────────────
// We show the actual observed AI steal rate as a vertical line

const aiStealRate = computed(() => {
  const rsd = props.realStrategyData
  let stealRounds = 0, totalRounds = 0
  for (const s of ['W','X','Y','Z','A','B','C','D']) {
    if (rsd?.[s]) {
      if (s === 'B' || s === 'D' || s === 'X' || s === 'Z') stealRounds += rsd[s].total ?? 0
      totalRounds += rsd[s].total ?? 0
    }
  }
  // Also check raw history steal counts from userHistory prop
  return totalRounds > 0 ? stealRounds / totalRounds : 0.27 // fallback to screenshot value
})

const stealRateMarker = ref(null)
function updateStealRateMarker() {
  if (!chartInstance) return
  const idx  = Math.min(Math.round(aiStealRate.value * 100), 100)
  const meta = chartInstance.getDatasetMeta(0)
  if (!meta?.data?.[idx]) return
  const pt   = meta.data[idx]
  const area = chartInstance.chartArea
  stealRateMarker.value = { x: pt.x, top: area.top, bottom: area.bottom, steal: Math.round(aiStealRate.value * 100) }
}

const userStealRateMarker = ref(null)
function updateUserStealRateMarker() {
  if (!chartInstance) return
  const rate = uh.value?.steal_rate
  if (rate == null) return
  const idx  = Math.min(Math.round(rate * 100), 100)
  const meta = chartInstance.getDatasetMeta(0)
  if (!meta?.data?.[idx]) return
  const pt   = meta.data[idx]
  const area = chartInstance.chartArea
  userStealRateMarker.value = { x: pt.x, top: area.top, bottom: area.bottom, steal: Math.round(rate * 100) }
}

// ── Quadrant fills — user perspective with additional insight zones ─────────
// Extra zones:
//  1. Below-zero loss zone (red tint) — strategies that lose points
//  2. Steal-profitable zone (green vertical band) — AI steal rate > breakeven for user steal
//  3. Dominant strategy regions (existing coloured fills)

const quadrantPlugin = computed(() => {
  return {
    id: 'quadrants',
    afterDraw(chart) {
      const { ctx, chartArea: { left, right, top, bottom } } = chart
      const toX = frac => left + frac * (right - left)
      const yMin = chart.scales.y.min
      const yMax = chart.scales.y.max
      const toY = val => bottom - ((val - yMin) / (yMax - yMin)) * (bottom - top)

      const b  = baseScore.value
      const pU = pUserCorrect.value / 100
      const pA = pAiCorrect.value / 100
      const steps = 200
      const xs = Array.from({ length: steps + 1 }, (_, i) => i / steps)

      // ── Zone 1: below-zero loss region ───────────────────────────────────
      // Shade any area where optimal strategy still yields < 0
      const zeroY = toY(0)
      if (zeroY < bottom) {
        ctx.save()
        ctx.beginPath()
        ctx.rect(left, zeroY, right - left, bottom - zeroY)
        ctx.fillStyle = 'rgba(220, 50, 50, 0.07)'
        ctx.fill()
        // Dashed zero line
        ctx.setLineDash([4, 4])
        ctx.strokeStyle = 'rgba(220, 80, 80, 0.35)'
        ctx.lineWidth = 1
        ctx.beginPath()
        ctx.moveTo(left, zeroY)
        ctx.lineTo(right, zeroY)
        ctx.stroke()
        ctx.setLineDash([])
        ctx.restore()

        ctx.save()
        ctx.fillStyle = 'rgba(220, 80, 80, 0.4)'
        ctx.font = '9px DM Sans, sans-serif'
        ctx.textAlign = 'left'
        ctx.fillText('loss zone', left + 6, zeroY + 12)
        ctx.restore()
      }

      // ── Zone 2: steal-profitable band ────────────────────────────────────
      // When P(AI correct) > breakeven, stealing earns positive EV for user
      // Shade the x-axis region where AI steal rate is high enough to make
      // user stealing consistently worthwhile (p > 0.5 is a useful threshold)
      // const stealThreshX = toX(0.5)
      // ctx.save()
      // ctx.beginPath()
      // ctx.rect(stealThreshX, top, right - stealThreshX, bottom - top)
      // ctx.fillStyle = 'rgba(29, 158, 117, 0.04)'
      // ctx.fill()
      // ctx.restore()

      // Label for steal zone
      // ctx.save()
      // ctx.fillStyle = 'rgba(29, 158, 117, 0.35)'
      // ctx.font = '9px DM Sans, sans-serif'
      // ctx.textAlign = 'left'
      // ctx.fillText('AI steals often → X strategy gains', stealThreshX + 6, top + 12)
      // ctx.restore()

      // ── Zone 3: breakeven threshold vertical line ─────────────────────────
      // The steal breakeven for the user = P(AI correct) > b/(b+1)
      // Mark this as a vertical band on the P(AI correct) dimension
      // Since x-axis is AI steal rate, we instead draw a horizontal line
      // at the steal EV = 0 breakeven value of pA
      const evStealAtCurrentPa = pA * (b + 1) + (1 - pA) * (-b)
      // if (evStealAtCurrentPa !== 0) {
      //   const breakevenY = toY(0)
      //   // Already drawn as zero line above — add annotation
      //   const breakevenPa = Math.round(stealBreakeven(b) * 100)
      //   ctx.save()
      //   ctx.fillStyle = pA >= stealBreakeven(b) ? 'rgba(29,158,117,0.6)' : 'rgba(216,90,48,0.6)'
      //   ctx.font = '9px DM Sans, sans-serif'
      //   ctx.textAlign = 'right'
      //   ctx.fillText(
      //     `steal breakeven P(AI correct) = ${breakevenPa}% · currently ${Math.round(pA*100)}% → ${pA >= stealBreakeven(b) ? 'steal +EV ✓' : 'steal -EV ✗'}`,
      //     right - 6,
      //     breakevenY - 5
      //   )
      //   ctx.restore()
      // }

      // ── Zone 4: dominant strategy regions ────────────────────────────────
      const isAi = viewMode.value === 'ai'
      const strategies = isAi ? ['A','B','C','D'] : ['W','X','Y','Z']
      const payoffFn = isAi ? aiExpectedPayoff : userExpectedPayoff
      const colors = isAi ? AI_COLORS : COLORS

      const dominant = xs.map(p => {
        let best = strategies[0], bestVal = -Infinity
        for (const s of strategies) {
          const v = payoffFn(s, p, b, pU, pA)
          if (v > bestVal) { bestVal = v; best = s }
        }
        return { p, best, val: bestVal }
      })

      const regions = []
      let cur = null
      dominant.forEach(({ p, best, val }) => {
        if (!cur || cur.strategy !== best) {
          if (cur) regions.push(cur)
          cur = { strategy: best, points: [] }
        }
        cur.points.push({ p, val })
      })
      if (cur) regions.push(cur)

      regions.forEach(region => {
        const { strategy, points } = region
        if (points.length < 2) return
        const color = colors[strategy]

        ctx.save()
        ctx.beginPath()
        points.forEach(({ p, val }, i) => {
          const x = toX(p), y = toY(val)
          i === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y)
        });
        [...points].reverse().forEach(({ p }) => {
          const vals = strategies.map(s => ({ s, v: payoffFn(s, p, b, pU, pA) })).sort((a, b) => b.v - a.v)
          ctx.lineTo(toX(p), toY(vals[1]?.v ?? vals[0].v))
        })
        ctx.closePath()
        ctx.fillStyle = color + '22'
        ctx.fill()

        const mid = points[Math.floor(points.length / 2)]
        const midVals = strategies.map(s => ({ s, v: payoffFn(s, mid.p, b, pU, pA) })).sort((a, b) => b.v - a.v)
        const labelY = toY((mid.val + (midVals[1]?.v ?? mid.val)) / 2)
        ctx.fillStyle = color + 'cc'
        ctx.font = '10px DM Sans, sans-serif'
        ctx.textAlign = 'center'
        ctx.fillText(strategy + ' dominates', toX(mid.p), labelY)
        ctx.restore()
      })
    }
  }
})

// ── Chart lifecycle ────────────────────────────────────────────────────────

function updateAllCircles() {
  updateOptimalCircle(); updateXwCircle(); updateXzCircle(); updateWzCircle()
  updateXAxisRateMarker(); updateSecondaryRateMarker()
}

function initChart() {
  if (!chartRef.value || !window.Chart) return
  if (chartInstance) { chartInstance.destroy(); chartInstance = null }
  chartInstance = new window.Chart(chartRef.value, {
    type: 'line',
    data: buildChartData(),
    plugins: [quadrantPlugin.value],
    options: {
      responsive: true, maintainAspectRatio: false,
      animation: { duration: 200 }, layout: { padding: { left: 4, right: 4 } },
      plugins: {
        legend: { display: false },
        tooltip: { callbacks: {
          title: items => 'AI steal rate = ' + items[0].label,
          label: item => item.dataset.label + ': ' + item.raw.toFixed(2) + ' pts (user)',
        }}
      },
      scales: {
        x: { title: { display: true, text: viewMode.value === 'ai' ? 'user steal rate →' : 'AI steal rate →', font: { size: 11 }, color: '#888' }, ticks: { maxTicksLimit: 6, font: { size: 11 }, color: '#888', callback: (v, i) => i % 20 === 0 ? (i/100).toFixed(1) : null }, grid: { color: 'rgba(128,128,128,0.1)' } },
        y: { title: { display: true, text: viewMode.value === 'ai' ? 'AI expected pts' : 'user expected pts', font: { size: 11 }, color: '#888' }, ticks: { font: { size: 11 }, color: '#888' }, grid: { color: 'rgba(128,128,128,0.1)' } }
      }
    }
  })
  updateAllCircles()
}

function updateChart() {
  if (!chartInstance) return
  const data = buildChartData()
  chartInstance.data.labels   = data.labels
  chartInstance.data.datasets = data.datasets
  chartInstance.options.scales.x.title.text = viewMode.value === 'ai' ? 'user steal rate →' : 'AI steal rate →'
  chartInstance.options.scales.y.title.text = viewMode.value === 'ai' ? 'AI expected pts' : 'user expected pts'
  chartInstance.update('none')
  updateAllCircles()
}

watch([round, pUserCorrect, pAiCorrect, viewMode], () => updateChart())

watch(() => props.userHistory, () => {
  updateUserStealRateMarker()
}, { deep: true })

watch(() => props.realStrategyData, () => {
  updateStealRateMarker()
  updateUserStealRateMarker()
}, { deep: true })

onMounted(() => {
  if (window.Chart) { initChart() } else {
    const s = document.createElement('script')
    s.src = 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js'
    s.onload = initChart; document.head.appendChild(s)
  }
})
onUnmounted(() => { if (chartInstance) chartInstance.destroy() })

// ── Derived stats ──────────────────────────────────────────────────────────

const userBestAtAiRate = computed(() => {
  const p  = aiStealRate.value
  const b  = baseScore.value
  const pU = pUserCorrect.value / 100
  const pA = pAiCorrect.value / 100
  const best = ['W','X','Y','Z'].reduce((a, s) => {
    const v = userExpectedPayoff(s, p, b, pU, pA)
    return v > a.val ? { s, val: v } : a
  }, { s: 'W', val: -Infinity })
  return { best: best.s, val: parseFloat(best.val.toFixed(2)) }
})

const stealThreshold = computed(() => xwPoint.value ? Math.round(xwPoint.value.x * 100) : null)
const minimaxThreshold = computed(() => {
  const candidates = [xwPoint.value, xzPoint.value, wzPoint.value].filter(Boolean)
  if (!candidates.length) return null
  const pt = candidates.reduce((min, p) => p.y < min.y ? p : min)
  return { pct: Math.round(pt.x * 100), val: pt.y }
})

const rsd = computed(() => props.realStrategyData)
const uh  = computed(() => props.userHistory)

// ── Strategy pills — user perspective ─────────────────────────────────────

const optimalStrategy = computed(() => {
  const p  = aiStealRate.value
  const b  = baseScore.value
  const pU = pUserCorrect.value / 100
  const pA = pAiCorrect.value / 100
  return ['W','X','Y','Z'].reduce((best, s) => {
    const v = userExpectedPayoff(s, p, b, pU, pA)
    return v > best.val ? { s, val: v } : best
  }, { s: 'W', val: -Infinity }).s
})

const strategyPills = computed(() => {
  const b  = baseScore.value
  const pA = pAiCorrect.value / 100

  return [
    {
      key: 'W',
      color: COLORS.W,
      title: 'A — Answering correctly:',
      stat: `+${b}pt`,
      sub: `This wins you ${b} point${b !== 1 ? 's' : ''}.`,
    },
    {
      key: 'X',
      color: COLORS.X,
      title: 'B — Answering correctly and stealing:',
      stat: `+${b+1}pt`,
      sub: `Stealing a correct answer gives you +${b+1}pts, while stealing an incorrect answer gives you −${b}pts.`,
    },
    {
      key: 'Y',
      color: COLORS.Y,
      title: 'C — Answering wrong, not stealing:',
      stat: `0pt`,
      sub: `This wins you 0pts.`,
    },
    {
      key: 'Z',
      color: COLORS.Z,
      title: 'D — Answering wrong and stealing:',
      stat: `+${b+1}pt`,
      sub: `Stealing a correct answer gives you +${b+1}pts, while stealing an incorrect answer gives you −${b}pts.`,
    },
  ]
})

// ── Stat cards ─────────────────────────────────────────────────────────────

const statCards = computed(() => {
  const b   = baseScore.value
  const pU  = pUserCorrect.value / 100
  const pA  = pAiCorrect.value / 100
  const isAi = viewMode.value === 'ai'
  const strategies = isAi ? ['A','B','C','D'] : ['W','X','Y','Z']
  const payoffFn = isAi ? aiExpectedPayoff : userExpectedPayoff
  const xRate = xAxisRate.value
  const xRatePct = Math.round(xRate * 100)

  const best = strategies.reduce((a, s) => {
    const v = payoffFn(s, xRate, b, pU, pA)
    return v > a.val ? { s, val: v } : a
  }, { s: strategies[0], val: -Infinity })

  const evSteal   = isAi
    ? parseFloat((pU * (b+1) + (1-pU) * (-b)).toFixed(2))
    : parseFloat((pA * (b+1) + (1-pA) * (-b)).toFixed(2))
  const evNoSteal = isAi
    ? parseFloat((pA * b).toFixed(2))
    : parseFloat((pU * b).toFixed(2))
  const bePct = Math.round(stealBreakeven(b, pU) * 100)

  const stealColor = isAi ? AI_COLORS[best.s] : COLORS[best.s]

  return [
    {
      label: isAi ? 'AI best vs user steal rate' : 'your best vs AI steal rate',
      value: `best strategy ${best.s} · ${best.val >= 0 ? '+' : ''}${parseFloat(best.val).toFixed(2)}pt expected`,
      sub: (() => {
        const candidates = [xwPoint.value, xzPoint.value, wzPoint.value].filter(Boolean)
        const mmx = candidates.length ? candidates.reduce((min, p) => p.y < min.y ? p : min) : null
        const thresh = xwPoint.value ? Math.round(xwPoint.value.x * 100) : null
        return thresh
          ? `below ${thresh}% ${isAi ? 'user' : 'AI'} steal rate use ${isAi ? 'A' : 'W'}, above ${mmx?.pct ?? mmx ? Math.round(mmx.x * 100) : '?'}% ${best.s} takes over (${isAi ? 'user' : 'AI'} correct ${isAi ? pUserCorrect.value : pAiCorrect.value}% of the time)`
          : `${best.s} wins at every ${isAi ? 'user' : 'AI'} steal rate with the user's ${pUserCorrect.value}% correct rate`
      })(),
      color: stealColor,
      dynamic: true,
    },
    {
      label: isAi ? 'steal value at current P(user correct)' : 'steal value at current P(AI correct)',
      value: `E[steal] = ${evSteal}pt · E[safe] = +${evNoSteal}pt`,
      sub: evSteal > 0
      ? `stealing averages +${evSteal}pt, safe play +${evNoSteal}pt — ${evSteal > evNoSteal ? 'steal wins' : 'safe play edges it'}. Breakeven: ${Math.round(stealBreakeven(b, pU) * 100)}% AI accuracy`
      : `stealing loses ${Math.abs(evSteal)}pt on average, safe play earns +${evNoSteal}pt. Needs >${Math.round(stealBreakeven(b, pU) * 100)}% accuracy`,
      color: evSteal > 0 ? '#1D9E75' : '#D85A30',
      dynamic: true,
    },
    {
      label: isAi ? 'observed user behaviour' : 'observed AI behaviour',
      value: isAi
        ? `user steal rate ${Math.round((uh.value?.steal_rate ?? 0) * 100)}% · ${(uh.value?.steal_rate ?? 0) > 0.5 ? 'aggressive' : (uh.value?.steal_rate ?? 0) > 0.25 ? 'balanced' : 'conservative'} player`
        : `AI steal rate ${xRatePct}% · ${xRatePct > 50 ? 'aggressive' : xRatePct > 25 ? 'balanced' : 'conservative'} AI`,
      sub: (() => {
        const strategies = isAi ? ['A','B','C','D'] : ['W','X','Y','Z']
        const payoffFn = isAi ? aiExpectedPayoff : userExpectedPayoff
        const sorted = strategies
          .map(s => ({ s, v: payoffFn(s, xRate, b, pU, pA) }))
          .sort((a, b) => b.v - a.v)
        const second = sorted[1]
        const secondColor = isAi ? AI_COLORS[second.s] : COLORS[second.s]
        const bestS = sorted[0].s
        if (isAi) {
          return `user steals ${Math.round((uh.value?.steal_rate ?? 0) * 100)}% of the time — AI should ${(uh.value?.steal_rate ?? 0) > 0.5 ? 'play decoy (C) to bait user steals' : 'answer correctly (A) as safe default'}`
        }
        return `${second.s} is also viable at ${xRatePct}% AI steal rate (+${parseFloat(second.v).toFixed(2)}pt) — profitable above ${Math.round(stealBreakeven(b, pU) * 100)}% AI accuracy`
      })(),
      color: (() => {
        const strategies = isAi ? ['A','B','C','D'] : ['W','X','Y','Z']
        const payoffFn = isAi ? aiExpectedPayoff : userExpectedPayoff
        const sorted = strategies
          .map(s => ({ s, v: payoffFn(s, xRate, b, pU, pA) }))
          .sort((a, b) => b.v - a.v)
        const second = sorted[1]
        return isAi ? AI_COLORS[second.s] : COLORS[second.s]
      })(),
    },
  ]
})

// ── Global stats ───────────────────────────────────────────────────────────

const globalStats = computed(() => {
  const total    = props.recentGames?.length ?? 0
  const userWins = props.recentGames?.filter(g => g.winner === 'user').length ?? 0
  const aiWins   = props.recentGames?.filter(g => g.winner === 'ai').length ?? 0
  const userSR   = uh.value?.steal_rate != null ? Math.round(uh.value.steal_rate * 100) + '%' : '—'
  const aiSR     = Math.round(aiStealRate.value * 100) + '%'
  return [
    { label: 'games played',    value: total },
    { label: 'you won',         value: userWins, color: userWins > aiWins ? '#1D9E75' : null },
    { label: 'AI won',          value: aiWins,   color: aiWins > userWins ? '#D85A30' : null },
    { label: 'your steal rate', value: userSR },
    { label: 'AI steal rate',   value: aiSR },
  ]
})
</script>

<template>
  <section class="gt-section">

    <div class="gt-toggle-row">
      <span class="gt-toggle-label" :class="{ active: viewMode === 'user' }">Your view</span>
      <button class="gt-toggle" :class="{ 'gt-toggle--ai': viewMode === 'ai' }" @click="viewMode = viewMode === 'user' ? 'ai' : 'user'">
        <span class="gt-toggle__knob"></span>
      </button>
      <span class="gt-toggle-label" :class="{ active: viewMode === 'ai' }">AI view</span>
    </div>

    <div class="gt-pills">
      <div v-for="pill in strategyPills" :key="pill.key" class="gt-pill"
        :style="{ borderColor: pill.color + '44', background: pill.color + '0f' }">
        <div class="gt-pill__header">
          <span class="gt-pill__stat" :style="{ color: pill.color }">{{ pill.stat }}</span>
          <span class="gt-pill__title" :style="{ color: pill.color + 'aa' }">{{ pill.title }}</span>
        </div>
        <span class="gt-pill__sub">{{ pill.sub }}</span>
        <span v-if="optimalStrategy === pill.key" class="gt-pill__optimal"
          :style="{ background: pill.color + '22', borderColor: pill.color + '66', color: pill.color }">
          ✦ Optimal
        </span>
      </div>
    </div>

    <div class="gt-sliders">
      <div v-if="slidersFromData" class="gt-data-badge">
        inferred from {{ props.inferredSliders?.observations }} questions
      </div>
      <div v-else class="gt-data-badge gt-data-badge--manual">
        manual — play games to calibrate
      </div>

      <div class="gt-slider-row">
        <label class="gt-label">round</label>
        <input type="range" min="1" max="3" step="1" v-model.number="round" />
        <span class="gt-val">{{ round }}</span>
      </div>

      <div class="gt-slider-row">
        <label class="gt-label">P(you correct)</label>
        <input type="range" min="0" max="100" step="1" v-model.number="pUserCorrect" />
        <span class="gt-val">{{ pUserCorrect }}%</span>
      </div>

      <!-- NEW: replaces P(steal) — P(AI correct) is the key driver of steal EV -->
      <div class="gt-slider-row">
        <label class="gt-label">P(AI correct)</label>
        <input type="range" min="0" max="100" step="1" v-model.number="pAiCorrect" />
        <span class="gt-val">{{ pAiCorrect }}%</span>
      </div>
    </div>

    <div class="gt-stat-cards">
      <div v-for="card in statCards" :key="card.label" class="gt-card"
        :style="{ borderColor: card.color + '33', background: card.color + '0d' }">
        <div class="gt-card__label">
          {{ card.label }}
          <span v-if="card.dynamic" class="gt-card__live">live</span>
        </div>
        <div class="gt-card__value" :style="{ color: card.color }">{{ card.value }}</div>
        <div class="gt-card__sub">{{ card.sub }}</div>
      </div>
    </div>

    <div class="gt-legend">
      <span v-for="(color, key) in (viewMode === 'ai' ? AI_COLORS : COLORS)" :key="key" class="gt-legend-item">
        <span class="gt-legend-swatch" :style="{ background: color }"></span>
        {{ key === 'opt' ? 'optimal' : `${key}: ${(viewMode === 'ai' ? AI_LABELS : LABELS)[key] ?? key}` }}
      </span>
      <span class="gt-legend-item gt-legend-item--zone">
        <span class="gt-legend-swatch" style="background: rgba(220,50,50,0.3)"></span>loss zone
      </span>
    </div>

    <div class="gt-chart-wrap">
      <canvas ref="chartRef"></canvas>

      <div v-if="xAxisRateMarker" class="gt-steal-line"
        :style="{ left: xAxisRateMarker.x+'px', top: xAxisRateMarker.top+'px', height: (xAxisRateMarker.bottom - xAxisRateMarker.top)+'px' }">
        <div class="gt-steal-label" style="color: rgba(128,128,128,0.7)">{{ xAxisRateMarker.label }}</div>
      </div>
      <div v-if="secondaryRateMarker" class="gt-steal-line gt-steal-line--user"
        :style="{ left: secondaryRateMarker.x+'px', top: secondaryRateMarker.top+'px', height: (secondaryRateMarker.bottom - secondaryRateMarker.top)+'px' }">
        <div class="gt-steal-label" style="color: rgba(55,138,221,0.8)">{{ secondaryRateMarker.label }}</div>
      </div>

      <!-- Optimal circle -->
      <div v-if="optimalCircle" class="gt-circle"
        :style="{ left: optimalCircle.x+'px', top: optimalCircle.y+'px', borderColor:'#1D9E75', background:'rgba(29,158,117,0.4)' }">
        <div class="gt-tip">{{ optimalPoint.y.toFixed(2) }}pt @ {{ viewMode === 'ai' ? 'user' : 'AI' }} steal {{ (optimalPoint.x*100).toFixed(0) }}% — {{ viewMode === 'ai' ? 'AI' : 'user' }} optimal</div>      </div>

      <!-- X↔W / B↔A crossover -->
      <div v-if="xwCircle" class="gt-circle" :class="{ 'gt-circle--minimax': minimaxLabel===xwPoint?.label }"
        :style="{ left: xwCircle.x+'px', top: xwCircle.y+'px', borderColor: xwCircle.color, background: xwCircle.color+'55' }">
        <div class="gt-tip">{{ xwPoint?.label }} @ {{ viewMode === 'ai' ? 'user' : 'AI' }} steal {{ xwPoint ? (xwPoint.x*100).toFixed(0) : '' }}% · {{ xwPoint?.y }}pt
          <span v-if="minimaxLabel===xwPoint?.label" class="gt-tip-tag">minimax</span></div>
      </div>

      <!-- X↔Z / B↔D crossover -->
      <div v-if="xzCircle" class="gt-circle" :class="{ 'gt-circle--minimax': minimaxLabel===xzPoint?.label }"
        :style="{ left: xzCircle.x+'px', top: xzCircle.y+'px', borderColor: xzCircle.color, background: xzCircle.color+'55' }">
        <div class="gt-tip">{{ xzPoint?.label }} @ {{ viewMode === 'ai' ? 'user' : 'AI' }} steal {{ xzPoint ? (xzPoint.x*100).toFixed(0) : '' }}% · {{ xzPoint?.y }}pt
          <span v-if="minimaxLabel===xzPoint?.label" class="gt-tip-tag">minimax</span></div>
      </div>

      <!-- W↔Z / A↔D crossover -->
      <div v-if="wzCircle" class="gt-circle" :class="{ 'gt-circle--minimax': minimaxLabel===wzPoint?.label }"
        :style="{ left: wzCircle.x+'px', top: wzCircle.y+'px', borderColor: wzCircle.color, background: wzCircle.color+'55' }">
        <div class="gt-tip">{{ wzPoint?.label }} @ {{ viewMode === 'ai' ? 'user' : 'AI' }} steal {{ wzPoint ? (wzPoint.x*100).toFixed(0) : '' }}% · {{ wzPoint?.y }}pt
          <span v-if="minimaxLabel===wzPoint?.label" class="gt-tip-tag">minimax</span></div>
      </div>
    </div>

    <div class="gt-globals">
      <div v-for="g in globalStats" :key="g.label" class="gt-global">
        <span class="gt-global__label">{{ g.label }}</span>
        <span class="gt-global__value" :style="g.color ? { color: g.color } : {}">{{ g.value }}</span>
      </div>
    </div>

  </section>
</template>

<style scoped>
.gt-section { padding: 2rem 0; font-family: 'DM Sans', sans-serif; }

.gt-pills { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-bottom: 1.25rem; }
@media (max-width: 600px) { .gt-pills { grid-template-columns: 1fr 1fr; } }
.gt-pill {
  border: 0.5px solid;
  border-radius: 999px;
  padding: 20px 16px;
  display: flex;
  flex-direction: column;
  gap: 4px;
  height: 100px;
  overflow: visible;
  position: relative;
}
.gt-pill__header {
  display: flex;
  align-items: baseline;
  gap: 8px;
}
.gt-pill__title { font-size: 13px; color: oklch(var(--bc) / 0.45); line-height: 1.3; }
.gt-pill__stat  { font-size: 18px; font-weight: 500; line-height: 1.2; }
.gt-pill__sub   { font-size: 10px; color: oklch(var(--bc) / 0.35); line-height: 1.3; }
.gt-pill__optimal {
  position: absolute;
  bottom: -10px;
  left: 16px;
  font-size: 10px;
  font-weight: 600;
  padding: 2px 8px;
  border-radius: 999px;
  border: 1px solid;
  letter-spacing: 0.03em;
}

.gt-sliders { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 20px; margin-bottom: 1rem; }
.gt-slider-row { display: flex; align-items: center; gap: 8px; flex: 1; min-width: 180px; }
.gt-data-badge { font-size: 11px; color: #1D9E75; padding: 3px 8px; border: 0.5px solid #1D9E75; border-radius: 4px; display: inline-block; }
.gt-data-badge--manual { color: oklch(var(--bc) / 0.4); border-color: oklch(var(--bc) / 0.15); }
.gt-label { font-size: 11px; color: oklch(var(--bc) / 0.5); width: 100px; flex-shrink: 0; }
.gt-slider-row input[type=range] { flex: 1; }
.gt-val { font-size: 12px; font-weight: 500; color: oklch(var(--bc)); min-width: 34px; text-align: right; }

.gt-stat-cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 1rem; }
@media (max-width: 700px) { .gt-stat-cards { grid-template-columns: 1fr; } }
.gt-card { border: 0.5px solid; border-radius: 10px; padding: 10px 12px; height: 90px; overflow: hidden; }
.gt-card__label { font-size: 10px; color: oklch(var(--bc) / 0.4); margin-bottom: 4px; display: flex; align-items: center; gap: 6px; text-transform: uppercase; letter-spacing: 0.04em; }
.gt-card__live { font-size: 9px; background: rgba(29,158,117,0.2); color: #1D9E75; border-radius: 3px; padding: 1px 4px; }
.gt-card__value { font-size: 12px; font-weight: 500; margin-bottom: 4px; line-height: 1.4; }
.gt-card__sub { font-size: 11px; color: oklch(var(--bc) / 0.5); line-height: 1.4; }

.gt-legend { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 0.75rem; font-size: 11px; color: oklch(var(--bc) / 0.6); }
.gt-legend-item { display: flex; align-items: center; gap: 5px; }
.gt-legend-item--zone { opacity: 0.75; font-style: italic; }
.gt-legend-swatch { width: 10px; height: 3px; display: inline-block; border-radius: 2px; }

.gt-chart-wrap { position: relative; width: 100%; height: 280px; margin-bottom: 1rem; }
.gt-chart-wrap canvas { width: 100% !important; height: 100% !important; }

.gt-steal-line { position: absolute; width: 1.5px; border-left: 1.5px dashed rgba(128,128,128,0.5); pointer-events: none; z-index: 5; }
.gt-steal-line--user { border-left-color: rgba(55, 138, 221, 0.7); }
.gt-steal-label { position: absolute; top: -18px; left: 50%; transform: translateX(-50%); font-size: 10px; color: oklch(var(--bc) / 0.5); white-space: nowrap; }


.gt-circle { position: absolute; width: 14px; height: 14px; border-radius: 50%; border: 2.5px solid; transform: translate(-50%, -50%); cursor: pointer; z-index: 10; transition: transform 0.15s; }
.gt-circle--minimax { width: 20px; height: 20px; border-style: dashed; }
.gt-circle:hover { transform: translate(-50%, -50%) scale(1.3); }
.gt-circle:hover .gt-tip { opacity: 1; pointer-events: auto; }
.gt-tip { position: absolute; bottom: calc(100% + 8px); left: 50%; transform: translateX(-50%); background: #1a1a2e; border: 1px solid rgba(255,255,255,0.15); border-radius: 6px; padding: 5px 10px; font-size: 12px; color: #fff; white-space: nowrap; opacity: 0; pointer-events: none; transition: opacity 0.15s; z-index: 20; display: flex; align-items: center; gap: 6px; }
.gt-tip-tag { background: rgba(255,255,255,0.15); border-radius: 4px; padding: 1px 5px; font-size: 11px; }

.gt-globals { display: flex; flex-wrap: wrap; gap: 8px; }
.gt-global {
  background: oklch(var(--b2, var(--b1)));
  border-radius: 8px;
  padding: 8px 14px;
  display: flex;
  flex-direction: column;
  gap: 2px;
  flex: 1;
  min-width: 80px;
  height: 70px;
  overflow: hidden;
}
.gt-global__label { font-size: 10px; color: oklch(var(--bc) / 0.45); text-transform: uppercase; letter-spacing: 0.04em; }
.gt-global__value { font-size: 20px; font-weight: 500; color: oklch(var(--bc)); }

.gt-toggle-row { display: flex; align-items: center; gap: 8px; margin-bottom: 1.25rem; }
.gt-toggle-label { font-size: 12px; color: oklch(var(--bc) / 0.4); transition: color 0.2s; }
.gt-toggle-label.active { color: oklch(var(--bc)); font-weight: 500; }
.gt-toggle {
  width: 40px; height: 22px; border-radius: 999px;
  background: #c0c0c0;
  border: none;
  cursor: pointer;
  position: relative; transition: background 0.2s; padding: 0;
}
.gt-toggle--ai { background: #7F77DD; }
.gt-toggle__knob {
  position: absolute; top: 3px; left: 3px;
  width: 16px; height: 16px; border-radius: 50%;
  background: #fff; transition: transform 0.2s;
  display: block;
}
.gt-toggle--ai .gt-toggle__knob { transform: translateX(18px); }
</style>
<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'

const props = defineProps({
  triviaStats: { type: Object, default: () => ({}) },
  recentGames: { type: Array, default: () => [] },
  userHistory: { type: Object, default: () => ({}) },
  inferredSliders: { type: Object, default: null },
})

const round = ref(1)
const learningRate = ref(10)
const riskTolerance = ref(50)
const slidersFromData = ref(false)

watch(() => props.inferredSliders, (val) => {
  if (val && val.observations > 0) {
    learningRate.value = val.lr
    riskTolerance.value = val.risk
    slidersFromData.value = true
  }
}, { immediate: true })

const chartRef = ref(null)
let chartInstance = null

const COLORS = { A: '#378ADD', B: '#1D9E75', C: '#D85A30', D: '#7F77DD', opt: '#888780' }
const LABELS = { A: 'correct, no steal', B: 'correct + steal', C: 'wrong (bait)', D: 'wrong + steal' }
const DATASET_INDEX = { A: 0, B: 1, C: 2, D: 3 }

const baseScore = computed(() => 1 + (round.value - 1))

const userStealRate = computed(() => {
  if (props.userHistory?.steal_rate != null) return props.userHistory.steal_rate
  return 0.5
})

function expectedPayoff(strategy, p, b, lr, risk) {
  const riskMod = risk / 100
  const lrMod = lr / 100
  switch (strategy) {
    case 'A': return (1 - p) * b
    case 'B': return p * (b + 1 + riskMod) + (1 - p) * (-b * lrMod)
    case 'C': return p * (-b * lrMod) + (1 - p) * 0
    case 'D': return p * (b + 1 + riskMod * 0.5) + (1 - p) * (-b)
  }
}

function getWeights(p, b, lr, risk) {
  const raw = {}
  for (const s of ['A','B','C','D']) {
    raw[s] = Math.max(0.01, expectedPayoff(s, p, b, lr, risk) + b + 1)
  }
  const total = Object.values(raw).reduce((a, v) => a + v, 0)
  const norm = {}
  for (const s of ['A','B','C','D']) norm[s] = raw[s] / total
  return norm
}

function buildChartData() {
  const xs = Array.from({ length: 101 }, (_, i) => i / 100)
  const b = baseScore.value
  const lr = learningRate.value
  const risk = riskTolerance.value
  const datasets = ['A','B','C','D'].map(s => ({
    label: s,
    data: xs.map(p => parseFloat(expectedPayoff(s, p, b, lr, risk).toFixed(3))),
    borderColor: COLORS[s],
    borderWidth: 2,
    pointRadius: 0,
    tension: 0,
  }))
  const opt = xs.map((_, i) => parseFloat(Math.max(...['A','B','C','D'].map(s => datasets.find(d => d.label === s).data[i])).toFixed(3)))
  datasets.push({ label: 'optimal', data: opt, borderColor: COLORS.opt, borderWidth: 2, borderDash: [5,3], pointRadius: 0, tension: 0 })
  return { labels: xs.map(x => x.toFixed(2)), datasets }
}

// ── Shared helpers ────────────────────────────────────────────────────────

function findPairIntersection(s1, s2) {
  const xs = Array.from({ length: 1000 }, (_, i) => i / 1000)
  const b = baseScore.value
  const lr = learningRate.value
  const risk = riskTolerance.value
  for (let i = 1; i < xs.length; i++) {
    const v1p = expectedPayoff(s1, xs[i-1], b, lr, risk)
    const v2p = expectedPayoff(s2, xs[i-1], b, lr, risk)
    const v1c = expectedPayoff(s1, xs[i], b, lr, risk)
    const v2c = expectedPayoff(s2, xs[i], b, lr, risk)
    if ((v1p - v2p) * (v1c - v2c) < 0) {
      return {
        x: xs[i],
        y: parseFloat(((v1c + v2c) / 2).toFixed(2)),
        idx: Math.min(Math.round(xs[i] * 100), 100),
        label: `${s1}↔${s2}`,
        s1, s2,
      }
    }
  }
  return null
}

function circleFromIntersection(ip) {
  if (!chartInstance || !ip) return null
  const meta1 = chartInstance.getDatasetMeta(DATASET_INDEX[ip.s1])
  const meta2 = chartInstance.getDatasetMeta(DATASET_INDEX[ip.s2])
  if (!meta1?.data || !meta2?.data) return null
  const pt1 = meta1.data[ip.idx]
  const pt2 = meta2.data[ip.idx]
  if (!pt1 || !pt2) return null
  return { x: pt1.x, y: (pt1.y + pt2.y) / 2, color: COLORS[ip.s1] }
}

// ── Optimal point ─────────────────────────────────────────────────────────

function getOptimalPoint() {
  const xs = Array.from({ length: 101 }, (_, i) => i / 100)
  const b = baseScore.value
  const lr = learningRate.value
  const risk = riskTolerance.value
  let maxVal = -Infinity, maxIdx = 0
  xs.forEach((p, i) => {
    const v = Math.max(...['A','B','C','D'].map(s => expectedPayoff(s, p, b, lr, risk)))
    if (v > maxVal) { maxVal = v; maxIdx = i }
  })
  return { x: xs[maxIdx], y: parseFloat(maxVal.toFixed(2)), idx: maxIdx }
}

const optimalPoint = computed(() => getOptimalPoint())
const optimalCircle = ref(null)

function updateOptimalCircle() {
  if (!chartInstance) return
  const meta = chartInstance.getDatasetMeta(4)
  if (!meta?.data) return
  const point = meta.data[optimalPoint.value.idx]
  if (!point) return
  optimalCircle.value = { x: point.x, y: point.y }
}

// ── B↔D intersection ──────────────────────────────────────────────────────

const bdPoint = computed(() => findPairIntersection('B', 'D'))
const bdCircle = ref(null)

function updateBdCircle() {
  bdCircle.value = circleFromIntersection(bdPoint.value)
}

// ── B↔A intersection ──────────────────────────────────────────────────────

const baPoint = computed(() => findPairIntersection('B', 'A'))
const baCircle = ref(null)

function updateBaCircle() {
  baCircle.value = circleFromIntersection(baPoint.value)
}

// ── D↔A intersection ──────────────────────────────────────────────────────

const daPoint = computed(() => findPairIntersection('D', 'A'))
const daCircle = ref(null)

function updateDaCircle() {
  daCircle.value = circleFromIntersection(daPoint.value)
}

// ── Minimax (lowest y intersection) ──────────────────────────────────────

const minimaxLabel = computed(() => {
  const candidates = [bdPoint.value, baPoint.value, daPoint.value].filter(Boolean)
  if (!candidates.length) return null
  return candidates.reduce((min, p) => p.y < min.y ? p : min).label
})

// ── Chart ─────────────────────────────────────────────────────────────────

function updateAllCircles() {
  updateOptimalCircle()
  updateBdCircle()
  updateBaCircle()
  updateDaCircle()
}

function initChart() {
  if (!chartRef.value || !window.Chart) return
  if (chartInstance) { chartInstance.destroy(); chartInstance = null }
  chartInstance = new window.Chart(chartRef.value, {
    type: 'line',
    data: buildChartData(),
    options: {
      responsive: true,
      maintainAspectRatio: false,
      animation: { duration: 200 },
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            title: items => 'steal rate = ' + items[0].label,
            label: item => item.dataset.label + ': ' + item.raw.toFixed(2) + ' pts',
          }
        }
      },
      scales: {
        x: {
          title: { display: true, text: 'opponent steal rate →', font: { size: 11 }, color: '#888' },
          ticks: { maxTicksLimit: 6, font: { size: 11 }, color: '#888', callback: (v, i) => i % 20 === 0 ? (i/100).toFixed(1) : null },
          grid: { color: 'rgba(128,128,128,0.1)' },
        },
        y: {
          title: { display: true, text: 'AI expected pts', font: { size: 11 }, color: '#888' },
          ticks: { font: { size: 11 }, color: '#888' },
          grid: { color: 'rgba(128,128,128,0.1)' },
        }
      }
    }
  })
  updateAllCircles()
}

function updateChart() {
  if (!chartInstance) return
  const data = buildChartData()
  chartInstance.data.labels = data.labels
  data.datasets.forEach((ds, i) => { if (chartInstance.data.datasets[i]) chartInstance.data.datasets[i].data = ds.data })
  chartInstance.update('none')
  updateAllCircles()
}

watch([round, learningRate, riskTolerance], () => updateChart())

onMounted(() => {
  if (window.Chart) {
    initChart()
  } else {
    const s = document.createElement('script')
    s.src = 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js'
    s.onload = initChart
    document.head.appendChild(s)
  }
})

onUnmounted(() => { if (chartInstance) chartInstance.destroy() })

// ── Weights / recommendation ──────────────────────────────────────────────

const weights = computed(() => getWeights(userStealRate.value, baseScore.value, learningRate.value, riskTolerance.value))

const bestStrategy = computed(() => {
  return Object.entries(weights.value).reduce((a, [k,v]) => v > a[1] ? [k, v] : a, ['A', 0])[0]
})

const recommendationText = computed(() => {
  const p = Math.round(userStealRate.value * 100)
  const s = bestStrategy.value
  const map = {
    A: `play safe — your ${p}% steal rate is low enough that correct answers score reliably`,
    B: `steal from you — at ${p}% you steal enough that your correct answers are worth taking`,
    C: `bait trap — at ${p}% steal rate, answering wrong costs nothing and punishes your steals`,
    D: `high-risk steal — only worthwhile in desperate round 3 situations`,
  }
  return map[s]
})

const payoffMatrix = computed(() => {
  const b = baseScore.value
  const lr = learningRate.value
  const risk = riskTolerance.value
  return ['A','B','C','D'].map(s => ({
    strategy: s,
    label: LABELS[s],
    color: COLORS[s],
    steal: parseFloat(expectedPayoff(s, 1, b, lr, risk).toFixed(2)),
    noSteal: parseFloat(expectedPayoff(s, 0, b, lr, risk).toFixed(2)),
  }))
})

// ── Stats ─────────────────────────────────────────────────────────────────

const totalGames = computed(() => props.recentGames?.length ?? 0)
const userWins = computed(() => props.recentGames?.filter(g => g.winner === 'user').length ?? 0)
const aiWins = computed(() => props.recentGames?.filter(g => g.winner === 'ai').length ?? 0)
const userAccuracy = computed(() => {
  if (props.userHistory?.accuracy != null) return Math.round(props.userHistory.accuracy * 100)
  return null
})
</script>

<template>
  <section class="gt-section">

    <div class="gt-metrics">
      <div class="gt-metric">
        <span class="gt-metric__label">total games</span>
        <span class="gt-metric__value">{{ totalGames }}</span>
      </div>
      <div class="gt-metric">
        <span class="gt-metric__label">your wins</span>
        <span class="gt-metric__value" style="color: #1D9E75;">{{ userWins }}</span>
      </div>
      <div class="gt-metric">
        <span class="gt-metric__label">AI wins</span>
        <span class="gt-metric__value" style="color: #D85A30;">{{ aiWins }}</span>
      </div>
      <div class="gt-metric" v-if="userAccuracy !== null">
        <span class="gt-metric__label">your accuracy</span>
        <span class="gt-metric__value">{{ userAccuracy }}%</span>
      </div>
    </div>

    <div class="gt-sliders">
      <div v-if="slidersFromData" class="gt-data-badge">
        inferred from {{ props.inferredSliders?.observations }} rounds of game data
      </div>
      <div v-else class="gt-data-badge gt-data-badge--manual">
        manual — play games to calibrate from real data
      </div>
      <div class="gt-slider-row">
        <label class="gt-label">round number</label>
        <input type="range" min="1" max="3" step="1" v-model.number="round" />
        <span class="gt-val">{{ round }}</span>
      </div>
      <div class="gt-slider-row">
        <label class="gt-label">AI learning rate</label>
        <input type="range" min="1" max="100" step="1" v-model.number="learningRate" />
        <span class="gt-val">{{ learningRate }}%</span>
      </div>
      <div class="gt-slider-row">
        <label class="gt-label">AI risk tolerance</label>
        <input type="range" min="0" max="100" step="1" v-model.number="riskTolerance" />
        <span class="gt-val">{{ riskTolerance }}%</span>
      </div>
      <div v-if="slidersFromData && props.inferredSliders?.raw_weights" class="gt-raw-weights">
        <span>actual weights —</span>
        <span v-for="(w, s) in props.inferredSliders.raw_weights" :key="s" :style="{ color: COLORS[s] }">
          {{ s }}: {{ w }}
        </span>
      </div>
    </div>

    <div class="gt-legend">
      <span v-for="(color, key) in { A: '#378ADD', B: '#1D9E75', C: '#D85A30', D: '#7F77DD' }" :key="key" class="gt-legend-item">
        <span class="gt-legend-swatch" :style="{ background: color }"></span>
        {{ key }}: {{ LABELS[key] }}
      </span>
      <span class="gt-legend-item">
        <span class="gt-legend-swatch" style="background: #888780; border-top: 2px dashed #888780;"></span>
        optimal
      </span>
    </div>

    <div class="gt-chart-wrap">
      <canvas ref="chartRef"></canvas>

      <div v-if="optimalCircle" class="gt-circle"
        :style="{ left: optimalCircle.x + 'px', top: optimalCircle.y + 'px', borderColor: '#1D9E75', background: 'rgba(29,158,117,0.4)' }">
        <div class="gt-tip">{{ optimalPoint.y.toFixed(2) }}pt @ {{ (optimalPoint.x * 100).toFixed(0) }}% — optimal</div>
      </div>

      <div v-if="bdCircle" class="gt-circle"
        :class="{ 'gt-circle--minimax': minimaxLabel === 'B↔D' }"
        :style="{ left: bdCircle.x + 'px', top: bdCircle.y + 'px', borderColor: bdCircle.color, background: bdCircle.color + '55' }">
        <div class="gt-tip">
          B↔D @ {{ bdPoint ? (bdPoint.x * 100).toFixed(0) : '' }}% · {{ bdPoint?.y }}pt
          <span v-if="minimaxLabel === 'B↔D'" class="gt-tip-tag">minimax</span>
        </div>
      </div>

      <div v-if="baCircle" class="gt-circle"
        :class="{ 'gt-circle--minimax': minimaxLabel === 'B↔A' }"
        :style="{ left: baCircle.x + 'px', top: baCircle.y + 'px', borderColor: baCircle.color, background: baCircle.color + '55' }">
        <div class="gt-tip">
          B↔A @ {{ baPoint ? (baPoint.x * 100).toFixed(0) : '' }}% · {{ baPoint?.y }}pt
          <span v-if="minimaxLabel === 'B↔A'" class="gt-tip-tag">minimax</span>
        </div>
      </div>

      <div v-if="daCircle" class="gt-circle"
        :class="{ 'gt-circle--minimax': minimaxLabel === 'D↔A' }"
        :style="{ left: daCircle.x + 'px', top: daCircle.y + 'px', borderColor: daCircle.color, background: daCircle.color + '55' }">
        <div class="gt-tip">
          D↔A @ {{ daPoint ? (daPoint.x * 100).toFixed(0) : '' }}% · {{ daPoint?.y }}pt
          <span v-if="minimaxLabel === 'D↔A'" class="gt-tip-tag">minimax</span>
        </div>
      </div>

    </div>

    <div class="gt-optimal">
      <span class="gt-optimal__label">optimal steal rate</span>
      <span class="gt-optimal__val">{{ (optimalPoint.x * 100).toFixed(0) }}%</span>
      <span class="gt-optimal__pts">{{ optimalPoint.y }} pts expected</span>
      <span v-if="minimaxLabel" class="gt-optimal__minimax">· minimax {{ minimaxLabel }}</span>
    </div>

    <p class="gt-sub">2x2 payoff matrix — base score = {{ baseScore }}pt per correct answer</p>

    <div class="gt-table-wrap">
      <table class="gt-table">
        <thead>
          <tr>
            <th class="gt-th">AI strategy</th>
            <th class="gt-th gt-th--center">you steal</th>
            <th class="gt-th gt-th--center">you don't steal</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in payoffMatrix" :key="row.strategy">
            <td class="gt-td gt-td--strategy" :style="{ color: row.color }">{{ row.strategy }}: {{ row.label }}</td>
            <td class="gt-td gt-td--num" :class="{ 'gt-td--best': row.steal === Math.max(...payoffMatrix.map(r => r.steal)) }">
              {{ row.steal >= 0 ? '+' : '' }}{{ row.steal }}
            </td>
            <td class="gt-td gt-td--num" :class="{ 'gt-td--best': row.noSteal === Math.max(...payoffMatrix.map(r => r.noSteal)) }">
              {{ row.noSteal >= 0 ? '+' : '' }}{{ row.noSteal }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <p class="gt-sub" style="margin-top: 1.25rem;">strategy probability weights</p>
    <div class="gt-weights">
      <div v-for="(w, s) in weights" :key="s" class="gt-weight-row">
        <span class="gt-weight-label" :style="{ color: COLORS[s] }">{{ s }}</span>
        <div class="gt-weight-track">
          <div class="gt-weight-fill" :style="{ width: (w * 100).toFixed(1) + '%', background: COLORS[s] }"></div>
        </div>
        <span class="gt-weight-pct">{{ (w * 100).toFixed(0) }}%</span>
      </div>
    </div>

    <div class="gt-rec">
      <span class="gt-rec__label">recommended strategy</span>
      <span class="gt-rec__val" :style="{ color: COLORS[bestStrategy] }">{{ bestStrategy }}: {{ LABELS[bestStrategy] }}</span>
      <span class="gt-rec__reason">{{ recommendationText }}</span>
    </div>

  </section>
</template>

<style scoped>
.gt-section { padding: 2rem 0; font-family: 'DM Sans', sans-serif; }

.gt-metrics { display: grid; grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); gap: 10px; margin-bottom: 1.5rem; }
.gt-metric { background: oklch(var(--b2, var(--b1))); border-radius: 8px; padding: 0.75rem 1rem; }
.gt-metric__label { display: block; font-size: 11px; color: oklch(var(--bc) / 0.5); margin-bottom: 4px; }
.gt-metric__value { font-size: 22px; font-weight: 500; color: oklch(var(--bc)); }

.gt-sliders { margin-bottom: 1rem; }
.gt-data-badge { font-size: 11px; color: #1D9E75; margin-bottom: 0.5rem; padding: 3px 8px; border: 0.5px solid #1D9E75; border-radius: 4px; display: inline-block; }
.gt-data-badge--manual { color: oklch(var(--bc) / 0.4); border-color: oklch(var(--bc) / 0.15); }
.gt-raw-weights { display: flex; gap: 10px; font-size: 11px; margin-top: 0.4rem; color: oklch(var(--bc) / 0.5); flex-wrap: wrap; }
.gt-slider-row { display: flex; align-items: center; gap: 10px; margin-bottom: 0.5rem; }
.gt-label { font-size: 12px; color: oklch(var(--bc) / 0.6); width: 120px; flex-shrink: 0; }
.gt-slider-row input[type=range] { flex: 1; }
.gt-val { font-size: 13px; font-weight: 500; color: oklch(var(--bc)); min-width: 38px; text-align: right; }

.gt-legend { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 0.75rem; font-size: 11px; color: oklch(var(--bc) / 0.6); }
.gt-legend-item { display: flex; align-items: center; gap: 5px; }
.gt-legend-swatch { width: 10px; height: 3px; display: inline-block; border-radius: 2px; }

.gt-chart-wrap { position: relative; width: 100%; height: 260px; margin-bottom: 0.75rem; }
.gt-chart-wrap canvas { width: 100% !important; height: 100% !important; }

.gt-circle {
  position: absolute;
  width: 14px;
  height: 14px;
  border-radius: 50%;
  border: 2.5px solid;
  transform: translate(-50%, -50%);
  cursor: pointer;
  z-index: 10;
  transition: transform 0.15s;
}

.gt-circle--minimax {
  width: 20px;
  height: 20px;
  border-style: dashed;
  border-width: 2.5px;
}

.gt-circle:hover { transform: translate(-50%, -50%) scale(1.3); }
.gt-circle:hover .gt-tip { opacity: 1; pointer-events: auto; }

.gt-tip {
  position: absolute;
  bottom: calc(100% + 8px);
  left: 50%;
  transform: translateX(-50%);
  background: #1a1a2e;
  border: 1px solid rgba(255,255,255,0.15);
  border-radius: 6px;
  padding: 5px 10px;
  font-size: 12px;
  color: #fff;
  white-space: nowrap;
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.15s;
  z-index: 20;
  display: flex;
  align-items: center;
  gap: 6px;
}

.gt-tip-tag {
  background: rgba(255,255,255,0.15);
  border-radius: 4px;
  padding: 1px 5px;
  font-size: 11px;
}

.gt-optimal { display: flex; align-items: baseline; gap: 8px; flex-wrap: wrap; margin-bottom: 1.25rem; padding: 0.75rem 1rem; background: oklch(var(--b2, var(--b1))); border-radius: 8px; }
.gt-optimal__label { font-size: 12px; color: oklch(var(--bc) / 0.5); }
.gt-optimal__val { font-size: 20px; font-weight: 500; color: #1D9E75; }
.gt-optimal__pts { font-size: 12px; color: oklch(var(--bc) / 0.5); }
.gt-optimal__minimax { font-size: 12px; color: oklch(var(--bc) / 0.35); }

.gt-sub { font-size: 12px; color: oklch(var(--bc) / 0.5); margin-bottom: 0.5rem; }

.gt-table-wrap { overflow-x: auto; }
.gt-table { width: 100%; border-collapse: collapse; font-size: 12px; table-layout: fixed; }
.gt-th { padding: 6px 10px; border: 0.5px solid oklch(var(--bc) / 0.15); font-weight: 400; color: oklch(var(--bc) / 0.5); text-align: left; }
.gt-th--center { text-align: center; }
.gt-td { padding: 7px 10px; border: 0.5px solid oklch(var(--bc) / 0.15); color: oklch(var(--bc)); }
.gt-td--strategy { font-weight: 500; }
.gt-td--num { text-align: center; }
.gt-td--best { background: rgba(29,158,117,0.1); }

.gt-weights { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 1.25rem; }
.gt-weight-row { display: flex; align-items: center; gap: 8px; }
.gt-weight-label { font-size: 12px; font-weight: 500; width: 16px; }
.gt-weight-track { flex: 1; height: 5px; background: oklch(var(--bc) / 0.12); border-radius: 3px; overflow: hidden; }
.gt-weight-fill { height: 100%; border-radius: 3px; transition: width 0.3s ease; }
.gt-weight-pct { font-size: 11px; color: oklch(var(--bc) / 0.5); min-width: 30px; text-align: right; }

.gt-rec { padding: 0.75rem 1rem; background: oklch(var(--b2, var(--b1))); border-radius: 8px; }
.gt-rec__label { display: block; font-size: 11px; color: oklch(var(--bc) / 0.5); margin-bottom: 4px; }
.gt-rec__val { display: block; font-size: 15px; font-weight: 500; margin-bottom: 3px; }
.gt-rec__reason { font-size: 12px; color: oklch(var(--bc) / 0.6); }
</style>
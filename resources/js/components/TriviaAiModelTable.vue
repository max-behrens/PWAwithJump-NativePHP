<script setup>
const props = defineProps({
  weights: { type: Array, default: () => [] }
})

function featureColor(feature) {
  if (feature.includes('strategy_A')) return '#378ADD'
  if (feature.includes('strategy_B')) return '#1D9E75'
  if (feature.includes('strategy_C')) return '#D85A30'
  if (feature.includes('strategy_D')) return '#7F77DD'
  if (feature.includes('user_accuracy')) return '#EF9F27'
  if (feature.includes('steal_rate')) return '#E24B4A'
  if (feature.includes('steal_accuracy')) return '#888780'
  return null
}

function weightBar(w) {
  return Math.round(Math.max(0, Math.min(1, w)) * 100)
}
</script>

<template>
  <div class="dt-wrap">
    <p class="dt-title">trivia_ai_model <span class="dt-count">{{ weights.length }} rows</span></p>
    <div class="dt-scroll">
      <table class="dt-table">
        <thead>
          <tr>
            <th>id</th>
            <th>feature</th>
            <th>weight</th>
            <th style="width:120px">weight bar</th>
            <th>observations</th>
            <th>updated_at</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="w in weights" :key="w.id">
            <td class="dt-id">{{ w.id }}</td>
            <td>
              <span class="dt-feature" :style="featureColor(w.feature) ? { color: featureColor(w.feature) } : {}">
                {{ w.feature }}
              </span>
            </td>
            <td class="dt-mono" :class="w.weight >= 0.5 ? 'dt-pos' : 'dt-neg'">
              {{ parseFloat(w.weight).toFixed(4) }}
            </td>
            <td>
              <div class="dt-bar-track">
                <div class="dt-bar-fill"
                  :style="{
                    width: weightBar(w.weight) + '%',
                    background: featureColor(w.feature) ?? 'oklch(var(--bc) / 0.3)'
                  }">
                </div>
              </div>
            </td>
            <td class="dt-muted">{{ w.observations }}</td>
            <td class="dt-muted dt-mono">{{ w.updated_at?.slice(0, 16).replace('T', ' ') }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
.dt-wrap { margin-bottom: 1.5rem; }
.dt-title { font-size: 12px; font-weight: 500; color: oklch(var(--bc) / 0.6); margin-bottom: 0.4rem; font-family: 'DM Mono', monospace; }
.dt-count { font-weight: 400; color: oklch(var(--bc) / 0.35); margin-left: 6px; }
.dt-scroll { overflow-x: auto; border: 0.5px solid oklch(var(--bc) / 0.12); border-radius: 8px; }
.dt-table { width: 100%; border-collapse: collapse; font-size: 11px; white-space: nowrap; }
.dt-table thead tr { background: oklch(var(--bc) / 0.04); }
.dt-table th { padding: 6px 10px; text-align: left; font-weight: 500; color: oklch(var(--bc) / 0.45); border-bottom: 0.5px solid oklch(var(--bc) / 0.1); }
.dt-table td { padding: 5px 10px; color: oklch(var(--bc) / 0.8); border-bottom: 0.5px solid oklch(var(--bc) / 0.06); }
.dt-table tr:last-child td { border-bottom: none; }
.dt-table tr:hover td { background: oklch(var(--bc) / 0.03); }
.dt-id { color: oklch(var(--bc) / 0.35); font-family: 'DM Mono', monospace; }
.dt-mono { font-family: 'DM Mono', monospace; }
.dt-muted { color: oklch(var(--bc) / 0.5); }
.dt-feature { font-family: 'DM Mono', monospace; font-size: 11px; }
.dt-pos { color: #1D9E75; }
.dt-neg { color: #E24B4A; }
.dt-bar-track { width: 100%; height: 5px; background: oklch(var(--bc) / 0.1); border-radius: 3px; overflow: hidden; }
.dt-bar-fill { height: 100%; border-radius: 3px; transition: width 0.3s; }
</style>

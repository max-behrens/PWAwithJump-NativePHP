<script setup>
const props = defineProps({
  history: { type: Array, default: () => [] }
})

const STRATEGY_COLORS = { A: '#378ADD', B: '#1D9E75', C: '#D85A30', D: '#7F77DD' }

function bool(v) { return v == 1 || v === true }
</script>

<template>
  <div class="dt-wrap">
    <p class="dt-title">trivia_user_history <span class="dt-count">{{ history.length }} rows</span></p>
    <div class="dt-scroll">
      <table class="dt-table">
        <thead>
          <tr>
            <th>id</th>
            <th>question_id</th>
            <th>difficulty</th>
            <th>user_correct</th>
            <th>user_steal</th>
            <th>ai_strategy</th>
            <th>ai_correct</th>
            <th>ai_steal</th>
            <th>ai_points</th>
            <th>user_points</th>
            <th>game_id</th>
            <th>game_round</th>
            <th>question_number</th>
            <th>created_at</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="h in history" :key="h.id">
            <td class="dt-id">{{ h.id }}</td>
            <td class="dt-muted">{{ h.question_id }}</td>
            <td>
              <span class="dt-badge" :class="`dt-badge--${h.difficulty}`">{{ h.difficulty }}</span>
            </td>
            <td><span :class="bool(h.user_correct) ? 'dt-yes' : 'dt-no'">{{ bool(h.user_correct) ? '✓' : '–' }}</span></td>
            <td><span :class="bool(h.user_steal) ? 'dt-yes' : 'dt-no'">{{ bool(h.user_steal) ? '✓' : '–' }}</span></td>
            <td>
              <span v-if="h.ai_strategy" class="dt-strategy" :style="{ color: STRATEGY_COLORS[h.ai_strategy], borderColor: STRATEGY_COLORS[h.ai_strategy] + '44' }">
                {{ h.ai_strategy }}
              </span>
            </td>
            <td><span :class="bool(h.ai_correct) ? 'dt-yes' : 'dt-no'">{{ bool(h.ai_correct) ? '✓' : '–' }}</span></td>
            <td><span :class="bool(h.ai_steal) ? 'dt-yes' : 'dt-no'">{{ bool(h.ai_steal) ? '✓' : '–' }}</span></td>
            <td :class="h.ai_points > 0 ? 'dt-pos' : h.ai_points < 0 ? 'dt-neg' : 'dt-muted'">
              {{ h.ai_points > 0 ? '+' : '' }}{{ h.ai_points }}
            </td>
            <td :class="h.user_points > 0 ? 'dt-pos' : h.user_points < 0 ? 'dt-neg' : 'dt-muted'">
              {{ h.user_points > 0 ? '+' : '' }}{{ h.user_points }}
            </td>
            <td class="dt-muted">{{ h.game_id }}</td>
            <td class="dt-muted">{{ h.game_round }}</td>
            <td class="dt-muted">{{ h.question_number }}</td>
            <td class="dt-muted dt-mono">{{ h.created_at?.slice(0, 16).replace('T', ' ') }}</td>
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
.dt-yes { color: #1D9E75; font-weight: 500; }
.dt-no  { color: oklch(var(--bc) / 0.25); }
.dt-pos { color: #1D9E75; font-weight: 500; font-family: 'DM Mono', monospace; }
.dt-neg { color: #E24B4A; font-weight: 500; font-family: 'DM Mono', monospace; }
.dt-strategy { font-size: 11px; font-weight: 500; padding: 1px 6px; border: 0.5px solid; border-radius: 4px; font-family: 'DM Mono', monospace; }
.dt-badge { font-size: 10px; padding: 1px 6px; border-radius: 4px; display: inline-block; }
.dt-badge--easy   { background: rgba(29,158,117,0.15);  color: #1D9E75; }
.dt-badge--medium { background: rgba(239,159,39,0.15);  color: #EF9F27; }
.dt-badge--hard   { background: rgba(226,75,74,0.15);   color: #E24B4A; }
</style>

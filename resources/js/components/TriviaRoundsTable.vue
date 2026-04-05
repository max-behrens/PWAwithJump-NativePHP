<script setup>
const props = defineProps({
  rounds: { type: Array, default: () => [] }
})

const STRATEGY_COLORS = { A: '#378ADD', B: '#1D9E75', C: '#D85A30', D: '#7F77DD' }

function bool(v) { return v == 1 || v === true }
</script>

<template>
  <div class="dt-wrap">
    <p class="dt-title">trivia_rounds <span class="dt-count">{{ rounds.length }} rows</span></p>
    <div class="dt-scroll">
      <table class="dt-table">
        <thead>
          <tr>
            <th>id</th>
            <th>game_id</th>
            <th>question_id</th>
            <th>game_round</th>
            <th>q_number</th>
            <th>user_ans</th>
            <th>ai_ans</th>
            <th>user_steal</th>
            <th>ai_steal</th>
            <th>user_correct</th>
            <th>ai_correct</th>
            <th>user_pts</th>
            <th>ai_pts</th>
            <th>base</th>
            <th>ai_strategy</th>
            <th>created_at</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="r in rounds" :key="r.id">
            <td class="dt-id">{{ r.id }}</td>
            <td class="dt-muted">{{ r.game_id }}</td>
            <td class="dt-muted">{{ r.question_id }}</td>
            <td class="dt-muted">{{ r.game_round }}</td>
            <td class="dt-muted">{{ r.question_number }}</td>
            <td class="dt-mono dt-answer">{{ r.user_answer?.toUpperCase() }}</td>
            <td class="dt-mono dt-answer">{{ r.ai_answer?.toUpperCase() }}</td>
            <td><span :class="bool(r.user_steal) ? 'dt-yes' : 'dt-no'">{{ bool(r.user_steal) ? '✓' : '–' }}</span></td>
            <td><span :class="bool(r.ai_steal) ? 'dt-yes' : 'dt-no'">{{ bool(r.ai_steal) ? '✓' : '–' }}</span></td>
            <td><span :class="bool(r.user_correct) ? 'dt-yes' : 'dt-no'">{{ bool(r.user_correct) ? '✓' : '–' }}</span></td>
            <td><span :class="bool(r.ai_correct) ? 'dt-yes' : 'dt-no'">{{ bool(r.ai_correct) ? '✓' : '–' }}</span></td>
            <td :class="r.user_points_earned > 0 ? 'dt-pos' : r.user_points_earned < 0 ? 'dt-neg' : 'dt-muted'">
              {{ r.user_points_earned > 0 ? '+' : '' }}{{ r.user_points_earned }}
            </td>
            <td :class="r.ai_points_earned > 0 ? 'dt-pos' : r.ai_points_earned < 0 ? 'dt-neg' : 'dt-muted'">
              {{ r.ai_points_earned > 0 ? '+' : '' }}{{ r.ai_points_earned }}
            </td>
            <td class="dt-muted">{{ r.base_score }}</td>
            <td>
              <span v-if="r.ai_strategy" class="dt-strategy" :style="{ color: STRATEGY_COLORS[r.ai_strategy], borderColor: STRATEGY_COLORS[r.ai_strategy] + '44' }">
                {{ r.ai_strategy }}
              </span>
            </td>
            <td class="dt-muted dt-mono">{{ r.created_at?.slice(0, 16).replace('T', ' ') }}</td>
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
.dt-answer { font-weight: 500; color: oklch(var(--bc)); }
.dt-yes { color: #1D9E75; font-weight: 500; }
.dt-no  { color: oklch(var(--bc) / 0.25); }
.dt-pos { color: #1D9E75; font-weight: 500; font-family: 'DM Mono', monospace; }
.dt-neg { color: #E24B4A; font-weight: 500; font-family: 'DM Mono', monospace; }
.dt-strategy { font-size: 11px; font-weight: 500; padding: 1px 6px; border: 0.5px solid; border-radius: 4px; font-family: 'DM Mono', monospace; }
</style>

<script setup>
const props = defineProps({
  games: { type: Array, default: () => [] }
})

const COLORS = { easy: '#1D9E75', medium: '#EF9F27', hard: '#E24B4A' }

function winner(g) {
  if (g.user_score > g.ai_score) return 'you'
  if (g.ai_score > g.user_score) return 'AI'
  return 'draw'
}
</script>

<template>
  <div class="dt-wrap">
    <p class="dt-title">trivia_games <span class="dt-count">{{ games.length }} rows</span></p>
    <div class="dt-scroll">
      <table class="dt-table">
        <thead>
          <tr>
            <th>id</th>
            <th>difficulty</th>
            <th>user_score</th>
            <th>ai_score</th>
            <th>winner</th>
            <th>status</th>
            <th>current_round</th>
            <th>current_question</th>
            <th>round_1_ids</th>
            <th>round_2_ids</th>
            <th>round_3_ids</th>
            <th>created_at</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="g in games" :key="g.id">
            <td class="dt-id">{{ g.id }}</td>
            <td><span class="dt-badge" :style="{ background: COLORS[g.difficulty] + '22', color: COLORS[g.difficulty] }">{{ g.difficulty }}</span></td>
            <td :class="g.user_score > g.ai_score ? 'dt-win' : ''">{{ g.user_score }}</td>
            <td :class="g.ai_score > g.user_score ? 'dt-win' : ''">{{ g.ai_score }}</td>
            <td class="dt-muted">{{ winner(g) }}</td>
            <td><span class="dt-badge" :class="g.status === 'completed' ? 'dt-badge--green' : 'dt-badge--amber'">{{ g.status }}</span></td>
            <td class="dt-muted">{{ g.current_round }}</td>
            <td class="dt-muted">{{ g.current_question }}</td>
            <td class="dt-mono dt-muted">{{ (g.round_1_question_ids ?? []).join(', ') }}</td>
            <td class="dt-mono dt-muted">{{ (g.round_2_question_ids ?? []).join(', ') }}</td>
            <td class="dt-mono dt-muted">{{ (g.round_3_question_ids ?? []).join(', ') }}</td>
            <td class="dt-muted dt-mono">{{ g.created_at?.slice(0, 16).replace('T', ' ') }}</td>
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
.dt-win { color: #1D9E75; font-weight: 500; }
.dt-badge { font-size: 10px; padding: 1px 6px; border-radius: 4px; display: inline-block; }
.dt-badge--green { background: rgba(29,158,117,0.15); color: #1D9E75; }
.dt-badge--amber { background: rgba(239,159,39,0.15); color: #EF9F27; }
</style>

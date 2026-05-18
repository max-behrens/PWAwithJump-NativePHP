<script setup>
import { ref, onMounted } from 'vue'

const recentGames = ref([])
const loading     = ref(true)

onMounted(async () => {
  try {
    const res  = await fetch('/trivia/stats-json')
    const data = await res.json()
    recentGames.value = data.recent_games ?? []
  } catch (e) {}
  finally { loading.value = false }
})

function startGame(difficulty) {
  const token = document.querySelector('meta[name="csrf-token"]')?.content ?? ''
  const form  = document.createElement('form')
  form.method = 'POST'
  form.action = '/trivia/start'

  const csrf = document.createElement('input')
  csrf.type  = 'hidden'
  csrf.name  = '_token'
  csrf.value = token
  form.appendChild(csrf)

  const diff = document.createElement('input')
  diff.type  = 'hidden'
  diff.name  = 'difficulty'
  diff.value = difficulty
  form.appendChild(diff)

  document.body.appendChild(form)
  form.submit()
}

function winnerClass(game, side) {
  const w = game.winner
  if (side === 'user') return w === 'user' ? 'text-success' : w === 'ai' ? 'text-error' : 'text-warning'
  return w === 'ai' ? 'text-success' : w === 'user' ? 'text-error' : 'text-warning'
}

function winnerLabel(game) {
  return game.winner === 'user' ? 'You won' : game.winner === 'ai' ? 'AI won' : 'Draw'
}

function diffForHumans(dateStr) {
  const diff = Date.now() - new Date(dateStr).getTime()
  const mins = Math.floor(diff / 60000)
  if (mins < 1)  return 'just now'
  if (mins < 60) return `${mins} minute${mins===1?'':'s'} ago`
  const hrs = Math.floor(mins / 60)
  if (hrs < 24)  return `${hrs} hour${hrs===1?'':'s'} ago`
  const days = Math.floor(hrs / 24)
  return `${days} day${days===1?'':'s'} ago`
}
</script>

<template>
  <div class="animate-in mb-6">
    <h1 class="text-2xl font-bold">Trivia Bluff</h1>
    <p class="text-base-content/50 text-sm mt-0.5">You vs AI · 3 rounds · 3 questions each</p>
  </div>

  <div class="animate-in card bg-base-100 border border-base-300/50 shadow-sm p-4 mb-5">
    <h2 class="font-semibold text-sm mb-2">How to play</h2>
    <div class="space-y-1 text-sm text-base-content/70">
      <p>🎯 Both you and the AI answer each question secretly</p>
      <p>🤜 Then both decide whether to <strong>steal</strong> the opponent's points</p>
      <p>✅ Correct answer, no steal = <span class="text-success font-semibold">+base pts</span></p>
      <p>🤜 Correct steal = <span class="text-warning font-semibold">+base+1 pts</span></p>
      <p>❌ Wrong steal = <span class="text-error font-semibold">−base pts</span></p>
      <p>📈 Base points increase each round</p>
      <p>🧠 The AI learns your strategy and adapts — it may deliberately answer wrong to bait your steal</p>
    </div>
    <div class="mt-3 overflow-x-auto">
      <table class="table table-xs w-full text-center">
        <thead>
          <tr><th></th><th>R1</th><th>R2</th><th>R3</th></tr>
        </thead>
        <tbody>
          <tr><td class="text-left text-xs">😊 Easy correct</td><td>+1</td><td>+2</td><td>+3</td></tr>
          <tr><td class="text-left text-xs text-warning">😊 Easy steal ✅</td><td>+2</td><td>+3</td><td>+4</td></tr>
          <tr><td class="text-left text-xs">🤔 Med correct</td><td>+2</td><td>+3</td><td>+4</td></tr>
          <tr><td class="text-left text-xs text-warning">🤔 Med steal ✅</td><td>+3</td><td>+4</td><td>+5</td></tr>
          <tr><td class="text-left text-xs">🔥 Hard correct</td><td>+3</td><td>+4</td><td>+5</td></tr>
          <tr><td class="text-left text-xs text-warning">🔥 Hard steal ✅</td><td>+4</td><td>+5</td><td>+6</td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="animate-in mb-6">
    <h2 class="font-semibold text-sm mb-3">Choose difficulty</h2>
    <div class="grid grid-cols-3 gap-3">
      <button class="btn btn-success w-full flex-col h-auto py-4 gap-1" @click="startGame('easy')">
        <span class="text-xl">😊</span>
        <span class="font-bold">Easy</span>
        <span class="text-xs opacity-70">1–4 pts</span>
      </button>
      <button class="btn btn-warning w-full flex-col h-auto py-4 gap-1" @click="startGame('medium')">
        <span class="text-xl">🤔</span>
        <span class="font-bold">Medium</span>
        <span class="text-xs opacity-70">2–5 pts</span>
      </button>
      <button class="btn btn-error w-full flex-col h-auto py-4 gap-1" @click="startGame('hard')">
        <span class="text-xl">🔥</span>
        <span class="font-bold">Hard</span>
        <span class="text-xs opacity-70">3–6 pts</span>
      </button>
    </div>
  </div>

  <div v-if="!loading && recentGames.length" class="animate-in">
    <h2 class="font-semibold text-sm mb-3">Recent games</h2>
    <div class="flex flex-col gap-2">
      <a
        v-for="game in recentGames"
        :key="game.id"
        :href="`/trivia/result/${game.id}`"
        class="card bg-base-100 border border-base-300/50 shadow-sm p-3 flex flex-row items-center justify-between">
        <div>
          <span class="badge badge-sm badge-outline capitalize">{{ game.difficulty }}</span>
          <p class="text-xs text-base-content/50 mt-1">{{ diffForHumans(game.created_at) }}</p>
        </div>
        <div class="text-right">
          <p class="font-bold text-sm">
            <span :class="winnerClass(game, 'user')">You {{ game.user_score }}</span>
            <span class="text-base-content/30 mx-1">vs</span>
            <span :class="winnerClass(game, 'ai')">AI {{ game.ai_score }}</span>
          </p>
          <p class="text-xs" :class="winnerClass(game, 'user')">{{ winnerLabel(game) }}</p>
        </div>
      </a>
    </div>
  </div>
</template>
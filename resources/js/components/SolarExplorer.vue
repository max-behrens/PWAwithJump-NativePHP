<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import { usePage } from '@inertiajs/vue3'
import GameTheoryChart from './GameTheoryChart.vue'
import TriviaGamesTable from './TriviaGamesTable.vue'
import TriviaRoundsTable from './TriviaRoundsTable.vue'
import TriviaUserHistoryTable from './TriviaUserHistoryTable.vue'
import TriviaAiModelTable from './TriviaAiModelTable.vue'

const page = usePage()

const cards = [
  {
    key: 'easy',
    title: 'Easy',
    desc: 'This is the defaut model that holds no prior knowledge of your games or its games.',
    bg: 'https://img.freepik.com/free-photo/beautiful_1203-2633.jpg',
    thumb: 'https://cdn.pixabay.com/photo/2021/09/25/05/25/robot-6654030_1280.png',
  },
  {
    key: 'medium',
    title: 'Medium',
    desc: 'The medium model preserves and learns from previous current player and model game behaviour.',
    bg: 'https://img.freepik.com/free-vector/pink-grid-neon-patterned-background_53876-114920.jpg',
    thumb: 'https://cdn.pixabay.com/photo/2023/09/01/21/07/ai-generated-8227699_1280.png',
  },
  {
    key: 'hard',
    title: 'Hard',
    desc: 'Hard mode involves the model knowing all its previous games against all players, as well as its own behaviour across all models.',
    bg: 'https://img.freepik.com/free-photo/fireman-fire-fighting-evacuation-fire-drill-simulation-training-safety-condominium-factory_640221-72.jpg',
    thumb: 'https://cdn.pixabay.com/photo/2014/04/09/17/48/man-320276_1280.png',
  },
  {
    key: 'game-filters',
    title: 'Filters',
    desc: 'Game rounds are separated by question dificulty and category: Easy, Medium & Hard; and History, Science Entrertainment & All',
    bg: 'https://cdn.pixabay.com/photo/2016/11/07/00/00/maze-1804499_1280.jpg',
    thumb: 'https://cdn.pixabay.com/photo/2015/12/04/22/20/gear-1077550_1280.png',
  },
  {
    key: 'ai-insights',
    title: 'AI Insights',
    desc: 'View AI Model performances over different games played',
    bg: 'https://img.freepik.com/free-photo/call-center-worker-using-ai-tech-laptop-reply-customers-closeup_482257-125822.jpg',
    thumb: 'https://static.vecteezy.com/system/resources/previews/040/334/702/non_2x/ai-generated-colorful-brain-isolated-on-transparent-background-free-png.png',
  },
]

const current = ref(0)
const trackRef = ref(null)
const wrapRef = ref(null)

const isMobile = () => matchMedia('(max-width:767px)').matches

function center(i) {
  const wrap = wrapRef.value
  const track = trackRef.value
  if (!wrap || !track) return
  const card = track.children[i]
  if (!card) return
  if (isMobile()) {
    wrap.scrollTo({ top: card.offsetTop - (wrap.clientHeight / 2 - card.clientHeight / 2), behavior: 'smooth' })
  } else {
    wrap.scrollTo({ left: card.offsetLeft - (wrap.clientWidth / 2 - card.clientWidth / 2), behavior: 'smooth' })
  }
}

function activate(i, scroll = false) {
  if (i === current.value) return
  current.value = i
  if (scroll) nextTick(() => center(i))
}

function go(step) {
  const next = Math.min(Math.max(current.value + step, 0), cards.length - 1)
  activate(next, true)
}

function onMouseEnter(i) {
  if (!matchMedia('(hover:hover)').matches) return
  activate(i, true)
}

let sx = 0, sy = 0
function onTouchStart(e) { sx = e.touches[0].clientX; sy = e.touches[0].clientY }
function onTouchEnd(e) {
  const dx = e.changedTouches[0].clientX - sx
  const dy = e.changedTouches[0].clientY - sy
  if (isMobile() ? Math.abs(dy) > 60 : Math.abs(dx) > 60)
    go((isMobile() ? dy : dx) > 0 ? -1 : 1)
}

function onKeydown(e) {
  if (['ArrowRight', 'ArrowDown'].includes(e.key)) go(1)
  if (['ArrowLeft', 'ArrowUp'].includes(e.key)) go(-1)
}

function onResize() { center(current.value) }

onMounted(() => {
  window.addEventListener('keydown', onKeydown, { passive: true })
  window.addEventListener('resize', onResize)
  nextTick(() => center(0))
})

onUnmounted(() => {
  window.removeEventListener('keydown', onKeydown)
  window.removeEventListener('resize', onResize)
})

const triviaStats = ref({})
const recentGames = ref([])
const userHistory = ref({})
const inferredSliders   = ref(null)
const realStrategyData  = ref({})
const rawGames          = ref([])
const rawRounds         = ref([])
const rawHistory        = ref([])
const rawWeights        = ref([])

onMounted(async () => {
  try {
    const res = await fetch('/trivia/stats-json')
    const data = await res.json()
    triviaStats.value = data.stats ?? {}
    recentGames.value = data.recent_games ?? []
    userHistory.value = data.user_history ?? {}
    if (data.inferred_sliders?.observations > 0) {
      inferredSliders.value = data.inferred_sliders
    }
    realStrategyData.value = data.real_strategy_data ?? {}
    rawGames.value         = data.raw_games    ?? []
    rawRounds.value        = data.raw_rounds   ?? []
    rawHistory.value       = data.raw_history  ?? []
    rawWeights.value       = data.raw_ai_model ?? []
  } catch (e) {}
})
</script>

<template>
  <section>
    <div class="head">
      <div></div>
      <h2>AI Models & Scores</h2>
      <div class="controls">
        <button class="nav-btn" aria-label="Prev" :disabled="current === 0" @click="go(-1)">‹</button>
        <button class="nav-btn" aria-label="Next" :disabled="current === cards.length - 1" @click="go(1)">›</button>
      </div>
    </div>

    <div class="slider" ref="wrapRef">
      <div
        class="track"
        ref="trackRef"
        @touchstart.passive="onTouchStart"
        @touchend.passive="onTouchEnd"
      >
        <article
          v-for="(card, i) in cards"
          :key="card.key"
          class="project-card"
          :class="{ 'is-active': current === i }"
          @mouseenter="onMouseEnter(i)"
          @click="activate(i, true)"
        >
          <img class="project-card__bg" :src="card.bg" alt="" />
          <div class="project-card__content">
            <img class="project-card__thumb" :src="card.thumb" alt="" />
            <div class="project-card__text">
              <h3 class="project-card__title">{{ card.title }}</h3>
              <p class="project-card__desc">{{ card.desc }}</p>
              <button class="project-card__btn">Details</button>
            </div>
          </div>
        </article>
      </div>
    </div>

    <div class="dots" :class="{ 'dots--hidden': isMobile() }">
      <span
        v-for="(card, i) in cards"
        :key="card.key"
        class="dot"
        :class="{ active: current === i }"
        @click="activate(i, true)"
      />
    </div>

    <div class="gt-wrapper">
      <h2 class="gt-heading">Game theory model</h2>
      <GameTheoryChart
        :trivia-stats="triviaStats"
        :recent-games="recentGames"
        :user-history="userHistory"
        :inferred-sliders="inferredSliders"
        :real-strategy-data="realStrategyData"
      />

      <div class="db-tables">
        <h2 class="db-heading">Database viewer</h2>
        <TriviaGamesTable :games="rawGames" />
        <TriviaRoundsTable :rounds="rawRounds" />
        <TriviaUserHistoryTable :history="rawHistory" />
        <TriviaAiModelTable :weights="rawWeights" />
      </div>
    </div>
  </section>
</template>

<style scoped>
* { margin: 0; padding: 0; box-sizing: border-box; }

section {
  font-family: 'DM Sans', Inter, sans-serif;
  background: transparent;
  color: oklch(var(--bc));
}

.head {
  max-width: 1400px;
  margin: auto;
  padding: 40px 20px 40px;
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  align-items: center;
  gap: 1rem;
}

.head h2 {
  font: 400 1.5rem/1.2 'DM Sans', Inter, sans-serif;
  color: oklch(var(--bc));
  text-align: center;
  white-space: nowrap;
}

@media (min-width: 1024px) { .head h2 { font-size: 2.25rem; } }

.controls {
  display: flex;
  flex-direction: row;
  gap: 0.5rem;
  justify-content: flex-end;
}

.nav-btn {
  width: 2.5rem; height: 2.5rem;
  border: 1px solid rgba(128, 128, 128, 0.3); border-radius: 50%;
  background: rgba(128, 128, 128, 0.25);
  color: oklch(var(--bc));
  font-size: 1.5rem;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: background 0.3s, color 0.3s, border-color 0.3s;
}
.nav-btn:hover { background: #ff6b35; color: #fff; border-color: #ff6b35; }
.nav-btn:disabled { opacity: 0.3; cursor: default; }

.slider { max-width: 1400px; margin: auto; overflow: hidden; }

.track {
  display: flex; gap: 1.25rem;
  align-items: flex-start; justify-content: center;
  padding-bottom: 40px;
}
.track::-webkit-scrollbar { display: none; }

.project-card {
  position: relative;
  flex: 0 0 6rem;
  height: 26rem;
  border-radius: 1rem;
  overflow: hidden;
  cursor: pointer;
  transition: flex-basis 0.55s cubic-bezier(0.25, 0.46, 0.45, 0.94),
              transform 0.55s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.project-card.is-active {
  flex-basis: 40rem;
  transform: translateY(-6px);
  box-shadow: 0 18px 55px rgba(0, 0, 0, 0.45);
}

.project-card__bg {
  position: absolute; inset: 0; width: 100%; height: 100%;
  object-fit: cover;
  filter: brightness(0.7) saturate(75%);
  transition: filter 0.3s, transform 0.55s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}
.project-card:hover .project-card__bg { filter: brightness(0.75) saturate(100%); transform: scale(1.06); }

.project-card__content {
  position: absolute; inset: 0;
  display: flex;
  align-items: flex-end;
  padding: 1.5rem;
  gap: 1.1rem;
  background: linear-gradient(transparent 40%, rgba(0, 0, 0, 0.85) 100%);
  z-index: 2;
  overflow: hidden;
}

.project-card__thumb {
  width: 0;
  min-width: 0;
  height: 269px;
  border-radius: 0.45rem;
  object-fit: cover;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.4);
  opacity: 0;
  transition: width 0.55s cubic-bezier(0.25, 0.46, 0.45, 0.94),
              min-width 0.55s cubic-bezier(0.25, 0.46, 0.45, 0.94),
              opacity 0.3s ease 0.2s;
  flex-shrink: 0;
}

.project-card.is-active .project-card__thumb {
  width: 170px;
  min-width: 170px;
  opacity: 1;
}

.project-card__text {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  overflow: hidden;
}

.project-card__title {
  color: #fff;
  font-weight: 700;
  font-size: 1.35rem;
  writing-mode: vertical-rl;
  transform: rotate(180deg);
  transition: font-size 0.3s ease;
  white-space: nowrap;
}

.project-card.is-active .project-card__title {
  writing-mode: horizontal-tb;
  transform: none;
  font-size: 2.4rem;
}

.project-card__desc {
  color: #ddd;
  font-size: 1rem;
  line-height: 1.4;
  max-width: 16rem;
  max-height: 0;
  opacity: 0;
  overflow: hidden;
  transition: max-height 0.4s ease 0.15s, opacity 0.3s ease 0.2s;
}

.project-card.is-active .project-card__desc {
  max-height: 6rem;
  opacity: 1;
}

.project-card__btn {
  padding: 0.55rem 1.3rem; border: none; border-radius: 9999px;
  background: #ff6b35; color: #fff; font-size: 0.9rem; font-weight: 600;
  cursor: pointer;
  width: fit-content;
  max-height: 0;
  opacity: 0;
  overflow: hidden;
  padding-top: 0;
  padding-bottom: 0;
  transition: max-height 0.4s ease 0.2s, opacity 0.3s ease 0.25s,
              padding 0.3s ease 0.2s;
}

.project-card.is-active .project-card__btn {
  max-height: 3rem;
  opacity: 1;
  padding-top: 0.55rem;
  padding-bottom: 0.55rem;
}

.project-card__btn:hover { background: #ff824f; }

.dots { display: flex; gap: 0.5rem; justify-content: center; padding: 20px 0; }
.dots--hidden { display: none; }
.dot {
  width: 13px; height: 13px; border-radius: 50%;
  background: rgba(128, 128, 128, 0.4);
  cursor: pointer; transition: background 0.3s, transform 0.3s;
}
.dot.active { background: #ff6b35; transform: scale(1.2); }

.gt-wrapper {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 20px 60px;
  border-top: 0.5px solid oklch(var(--bc) / 0.1);
  padding-top: 2rem;
}
.gt-heading {
  font: 400 1.5rem/1.2 'DM Sans', Inter, sans-serif;
  color: oklch(var(--bc));
  margin-bottom: 0.25rem;
}

.db-tables {
  max-width: 1400px;
  margin: 0 auto;
  padding: 2rem 20px 60px;
  border-top: 0.5px solid oklch(var(--bc) / 0.1);
}
.db-heading {
  font: 400 1.25rem/1.2 'DM Sans', Inter, sans-serif;
  color: oklch(var(--bc));
  margin-bottom: 1.5rem;
}

@media (max-width: 767px) {
  .head {
    padding: 20px 15px 20px;
    grid-template-columns: 1fr;
    grid-template-rows: auto auto;
  }
  .head h2 { grid-column: 1; text-align: left; font-size: 1.4rem; }
  .controls { grid-column: 1; justify-content: flex-start; padding-bottom: 10px; }

  .slider { padding: 0 15px; }
  .track {
    flex-direction: column;
    gap: 0.8rem; padding-bottom: 20px;
  }
  .project-card {
    height: auto; min-height: 80px; flex: 0 0 auto;
    width: 100%;
  }
  .project-card.is-active {
    min-height: 300px; flex-basis: auto;
    transform: none; box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
  }
  .project-card__content {
    align-items: flex-start;
    padding: 1rem;
    flex-direction: row;
  }
  .project-card__title {
    writing-mode: horizontal-tb; transform: none;
    font-size: 1.2rem;
  }
  .project-card.is-active .project-card__title { font-size: 1.8rem; }
  .project-card.is-active .project-card__thumb {
    width: 120px; min-width: 120px; height: 180px;
  }
  .project-card.is-active .project-card__desc { max-width: 100%; }
  .project-card.is-active .project-card__btn {
    width: 100%; text-align: center;
  }

  .dots { display: none; }
  .nav-btn { width: 2rem; height: 2rem; font-size: 1.2rem; }

  .gt-wrapper { padding: 1.5rem 15px 40px; }
}
</style>
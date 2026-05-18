<script setup>
/**
 * RpgExplorer.vue
 * Map layout, rendering, collision and input only.
 * All tile definitions live in tileInventory.js.
 */
import { ref, computed, onMounted, onUnmounted } from 'vue'
import {
  TILESET_SRC, TILE_SIZE,
  FLOOR_CLEAN, FLOOR_DIRTY,
  WALL, WALL_TOP_POOL, WALL_BOTTOM_POOL, WALL_LEFT_POOL, WALL_RIGHT_POOL,
  WALL_D_TOP_POOL, WALL_D_BOTTOM_POOL, WALL_D_LEFT_POOL, WALL_D_RIGHT_POOL,
  DECOR_OBJECTS, DECOR_TILE_SET,
  pickTile, isTileWalkable,
} from './tileInventory.js'
import GameTheoryChart   from './GameTheoryChart.vue'
import TriviaGamesTable  from './TriviaGamesTable.vue'
import TriviaIndex       from './TriviaIndex.vue'

const T  = TILE_SIZE

// ── Map: 60 wide × 52 tall ────────────────────────────────────────────────
const MW = 60
const MH = 52

// ─────────────────────────────────────────────────────────────────────────
// LAYOUT (tile coords, walls are 1-tile thick around each floor area)
//
//   Room A (clean)   wall: r1=2  c1=2  r2=16 c2=22   floor inside
//   Room B (dirty)   wall: r1=2  c1=37 r2=16 c2=57   floor inside
//   Room C (clean)   wall: r1=34 c1=2  r2=48 c2=22   floor inside
//   Room D (dirty)   wall: r1=34 c1=37 r2=48 c2=57   floor inside
//
//   Corridor H-top   floor rows 7-11,  cols 22-37   (A → B)
//   Corridor V-left  floor rows 16-34, cols 8-13    (A → C)
//   Corridor V-right floor rows 16-34, cols 46-51   (B → D)
//   Corridor H-bot   floor rows 39-43, cols 22-37   (C → D)
//
//   Path: A─bridge─B
//         │              │
//         C─bridge─D
// All 4 rooms reachable.
// ─────────────────────────────────────────────────────────────────────────

const WA = { r1:2,  c1:2,  r2:16, c2:22 }
const WB = { r1:2,  c1:37, r2:16, c2:57 }
const WC = { r1:34, c1:2,  r2:48, c2:22 }
const WD = { r1:34, c1:37, r2:48, c2:57 }

// Corridors — floor rectangles (walls added as single rows/cols outside)
const CHT = { r1:7,  r2:11, c1:22, c2:37 }   // horizontal top  (A→B)
const CHB = { r1:39, r2:43, c1:22, c2:37 }   // horizontal bot  (C→D)
const CVL = { r1:16, r2:34, c1:8,  c2:13 }   // vertical left   (A→C)
const CVR = { r1:16, r2:34, c1:46, c2:51 }   // vertical right  (B→D)

function buildMap() {
  const m = Array.from({ length: MH }, () => Array(MW).fill(null))

  const set = (r, c, tile) => { if (r>=0&&r<MH&&c>=0&&c<MW) m[r][c]=tile }

  function fillFloor(pool, r1, c1, r2, c2) {
    for (let r=r1;r<=r2;r++) for (let c=c1;c<=c2;c++) set(r,c,pickTile(pool,r,c))
  }

  function fillWalls(r1,c1,r2,c2, tP,bP,lP,rP, tl,tr,bl,br) {
    set(r1,c1,tl); set(r1,c2,tr); set(r2,c1,bl); set(r2,c2,br)
    for (let c=c1+1;c<c2;c++) { set(r1,c,pickTile(tP,r1,c)); set(r2,c,pickTile(bP,r2,c)) }
    for (let r=r1+1;r<r2;r++) { set(r,c1,pickTile(lP,r,c1)); set(r,c2,pickTile(rP,r,c2)) }
  }

  function placeDecor(name, r, c) {
    const obj = DECOR_OBJECTS[name]; if (!obj) return
    let i=0
    for (let dr=0;dr<obj.rows;dr++) for (let dc=0;dc<obj.cols;dc++) set(r+dr,c+dc,obj.tiles[i++])
  }

  // Open a doorway: overwrite wall tiles with floor tiles
  function gapH(row, c1, c2, pool) { for (let c=c1;c<=c2;c++) set(row,c,pickTile(pool,row,c)) }
  function gapV(col, r1, r2, pool) { for (let r=r1;r<=r2;r++) set(r,col,pickTile(pool,r,col)) }

  // ── Room A — top-left, clean ─────────────────────────────────────────────
  fillFloor(FLOOR_CLEAN, WA.r1+1, WA.c1+1, WA.r2-1, WA.c2-1)
  fillWalls(WA.r1,WA.c1,WA.r2,WA.c2, WALL_TOP_POOL,WALL_BOTTOM_POOL,WALL_LEFT_POOL,WALL_RIGHT_POOL, WALL.TOP_LEFT,WALL.TOP_RIGHT,WALL.BOTTOM_LEFT,WALL.BOTTOM_RIGHT)

  // Top wall cluster — shelves + terminal hugging the north wall (row 3 = 1 below top wall)
  placeDecor('SHELF_A',   3,  3)   // against top-left corner
  placeDecor('TERMINAL',  3,  5)   // directly beside shelf (clustered)
  placeDecor('MONITOR',   3,  7)   // continues the cluster
  placeDecor('BOOKCASE',  3, 13)   // separate group, also top wall
  placeDecor('SHELF_B',   3, 15)   // beside bookcase (clustered)

  // Left wall cluster — server + radio hugging west wall (col 3 = 1 right of left wall)
  placeDecor('SERVER',    7,  3)   // against left wall
  placeDecor('RADIO',     9,  3)   // directly below (clustered, vertical)

  // Right wall items — locker + chest hugging east wall (col 20 = 1 left of right wall WA.c2=22)
  placeDecor('LOCKER_A', 4,  20)   // against right wall
  placeDecor('CHEST_A',  6,  20)   // below locker (clustered)

  // Bottom wall — crystals along south wall
  placeDecor('CRYSTAL_A', 13,  7)  // against bottom wall
  placeDecor('CRYSTAL_B', 13,  9)  // beside it (clustered)
  placeDecor('FILER',     13, 16)  // right side, bottom wall

  // ── Room B — top-right, dirty ────────────────────────────────────────────
  fillFloor(FLOOR_DIRTY, WB.r1+1, WB.c1+1, WB.r2-1, WB.c2-1)
  fillWalls(WB.r1,WB.c1,WB.r2,WB.c2, WALL_D_TOP_POOL,WALL_D_BOTTOM_POOL,WALL_D_LEFT_POOL,WALL_D_RIGHT_POOL, WALL.D_TOP_LEFT,WALL.D_TOP_RIGHT,WALL.D_BOTTOM_LEFT,WALL.D_BOTTOM_RIGHT)

  // Top wall cluster — cabinets along north wall
  placeDecor('CABINET',   3, 38)   // top wall
  placeDecor('SHELF_C',   3, 40)   // clustered beside cabinet
  placeDecor('CHEST_B',   3, 46)   // separate group, top wall
  placeDecor('MISC_A',    3, 48)   // beside chest (clustered)

  // Right wall cluster — machine + filer hugging east wall (col 55 = 1 left of WB.c2=57)
  placeDecor('MACHINE',   4, 55)   // against right wall
  placeDecor('FILER',     6, 55)   // below machine (clustered)
  placeDecor('SERVER',    8, 55)   // continues down right wall

  // Left wall — standalone item
  placeDecor('CRYSTAL_B', 8, 38)   // against left wall

  // Bottom wall cluster
  placeDecor('LOCKER_B', 13, 41)   // bottom wall
  placeDecor('STAND',    13, 43)   // beside locker (clustered)
  placeDecor('CRYSTAL_C',13, 52)   // bottom-right corner area

  // ── Room C — bottom-left, clean ──────────────────────────────────────────
  fillFloor(FLOOR_CLEAN, WC.r1+1, WC.c1+1, WC.r2-1, WC.c2-1)
  fillWalls(WC.r1,WC.c1,WC.r2,WC.c2, WALL_TOP_POOL,WALL_BOTTOM_POOL,WALL_LEFT_POOL,WALL_RIGHT_POOL, WALL.TOP_LEFT,WALL.TOP_RIGHT,WALL.BOTTOM_LEFT,WALL.BOTTOM_RIGHT)

  // Top wall cluster
  placeDecor('BOOKCASE',  35,  3)  // top-left, against top wall
  placeDecor('SHELF_A',   35,  5)  // clustered beside bookcase
  placeDecor('SHELF_C',   35, 11)  // separate group, top wall
  placeDecor('TERMINAL',  35, 17)  // top-right area

  // Left wall items
  placeDecor('LOCKER_A',  38,  3)  // left wall
  placeDecor('CHEST_C',   40,  3)  // below locker (clustered)

  // Bottom wall
  placeDecor('CRYSTAL_A', 45,  5)  // bottom wall
  placeDecor('CRYSTAL_B', 45,  7)  // beside it (clustered)
  placeDecor('RADIO',     45, 15)  // further along bottom wall

  // Right wall
  placeDecor('STAND',     37, 19)  // right wall
  placeDecor('MISC_A',    40, 19)  // below stand (clustered)

  // ── Room D — bottom-right, dirty ─────────────────────────────────────────
  fillFloor(FLOOR_DIRTY, WD.r1+1, WD.c1+1, WD.r2-1, WD.c2-1)
  fillWalls(WD.r1,WD.c1,WD.r2,WD.c2, WALL_D_TOP_POOL,WALL_D_BOTTOM_POOL,WALL_D_LEFT_POOL,WALL_D_RIGHT_POOL, WALL.D_TOP_LEFT,WALL.D_TOP_RIGHT,WALL.D_BOTTOM_LEFT,WALL.D_BOTTOM_RIGHT)

  // Top wall cluster — long row of shelves
  placeDecor('SHELF_A',   35, 38)  // top wall
  placeDecor('SHELF_B',   35, 40)  // clustered
  placeDecor('SHELF_C',   35, 42)  // continues cluster (long shelf run)
  placeDecor('CABINET',   35, 49)  // separate group top wall
  placeDecor('FILER',     35, 51)  // beside cabinet (clustered)

  // Right wall
  placeDecor('SERVER',    38, 55)  // right wall
  placeDecor('MACHINE',   40, 55)  // below server (clustered)

  // Left wall
  placeDecor('LOCKER_A',  38, 38)  // left wall
  placeDecor('RADIO',     40, 38)  // below locker (clustered)

  // Bottom wall
  placeDecor('CHEST_A',   45, 41)  // bottom wall
  placeDecor('CHEST_B',   45, 43)  // beside chest (clustered)
  placeDecor('CRYSTAL_C', 45, 52)  // bottom-right corner

  // ── Corridor H-top: A → B (rows 7-11, cols 22-37) ───────────────────────
  fillFloor(FLOOR_CLEAN, CHT.r1, CHT.c1, CHT.r2, CHT.c2)
  for (let c=CHT.c1;c<=CHT.c2;c++) {
    set(CHT.r1-1, c, pickTile(WALL_TOP_POOL,    CHT.r1-1, c))
    set(CHT.r2+1, c, pickTile(WALL_BOTTOM_POOL, CHT.r2+1, c))
  }
  gapV(WA.c2, CHT.r1, CHT.r2, FLOOR_CLEAN)   // open Room A right wall
  gapV(WB.c1, CHT.r1, CHT.r2, FLOOR_DIRTY)   // open Room B left wall
  placeDecor('CRYSTAL_A', CHT.r1, 27)

  // ── Corridor V-left: A → C (rows 16-34, cols 8-13) ──────────────────────
  fillFloor(FLOOR_CLEAN, CVL.r1, CVL.c1, CVL.r2, CVL.c2)
  for (let r=CVL.r1;r<=CVL.r2;r++) {
    set(r, CVL.c1-1, pickTile(WALL_LEFT_POOL,  r, CVL.c1-1))
    set(r, CVL.c2+1, pickTile(WALL_RIGHT_POOL, r, CVL.c2+1))
  }
  gapH(WA.r2, CVL.c1, CVL.c2, FLOOR_CLEAN)   // open Room A bottom wall
  gapH(WC.r1, CVL.c1, CVL.c2, FLOOR_CLEAN)   // open Room C top wall
  placeDecor('LOCKER_A', 22, CVL.c1)
  placeDecor('LOCKER_B', 28, CVL.c1)

  // ── Corridor V-right: B → D (rows 16-34, cols 46-51) ────────────────────
  fillFloor(FLOOR_DIRTY, CVR.r1, CVR.c1, CVR.r2, CVR.c2)
  for (let r=CVR.r1;r<=CVR.r2;r++) {
    set(r, CVR.c1-1, pickTile(WALL_D_LEFT_POOL,  r, CVR.c1-1))
    set(r, CVR.c2+1, pickTile(WALL_D_RIGHT_POOL, r, CVR.c2+1))
  }
  gapH(WB.r2, CVR.c1, CVR.c2, FLOOR_DIRTY)   // open Room B bottom wall
  gapH(WD.r1, CVR.c1, CVR.c2, FLOOR_DIRTY)   // open Room D top wall
  placeDecor('CRYSTAL_B', 22, CVR.c1)
  placeDecor('CRYSTAL_C', 28, CVR.c1)

  // ── Corridor H-bot: C → D (rows 39-43, cols 22-37) ──────────────────────
  fillFloor(FLOOR_CLEAN, CHB.r1, CHB.c1, CHB.r2, CHB.c2)
  for (let c=CHB.c1;c<=CHB.c2;c++) {
    set(CHB.r1-1, c, pickTile(WALL_TOP_POOL,    CHB.r1-1, c))
    set(CHB.r2+1, c, pickTile(WALL_BOTTOM_POOL, CHB.r2+1, c))
  }
  gapV(WC.c2, CHB.r1, CHB.r2, FLOOR_CLEAN)   // open Room C right wall
  gapV(WD.c1, CHB.r1, CHB.r2, FLOOR_DIRTY)   // open Room D left wall
  placeDecor('CHEST_B', CHB.r1, 28)

  return m
}

const MAP  = buildMap()
const WALK = MAP.map(row => row.map(isTileWalkable))

function canWalk(wx, wy) {
  const mg = 4
  for (const [px, py] of [
    [wx + mg,         wy + T*2 - mg],
    [wx + T*2 - mg,   wy + T*2 - mg],
    [wx + T,          wy + T*2 - 3],
  ]) {
    const tc = Math.floor(px/T), tr = Math.floor(py/T)
    if (tr < 0 || tr >= MH || tc < 0 || tc >= MW) return false
    if (!WALK[tr][tc]) return false
  }
  return true
}

// ── Interactive hotspots ──────────────────────────────────────────────────
// tileSet contains "mapCol,mapRow" keys — positions on the MAP grid,
// not tileset source coords.
//   BOOKCASE placed at placeDecor('BOOKCASE', 3, 13) → map cols 13-14, rows 3-4
//   SHELF_B  placed at placeDecor('SHELF_B',  3, 15) → map cols 15-16, rows 3-4
//   CABINET  placed at placeDecor('CABINET',  3, 38) → map cols 38-39, rows 3-4

const HOTSPOTS = [
  {
    id:      'ai-stats',
    label:   'AI Stats',
    tileSet: new Set(['13,3','14,3','13,4','14,4']),
    action:  'modal-ai',
    // World pixel centre of the 2×2 group (for label anchor)
    worldCX: 14 * T,   // col 13 + 1 tile = centre of 2-wide group
    worldCY:  3 * T,   // top of group
  },
  {
    id:      'games-played',
    label:   'Games Played',
    tileSet: new Set(['15,3','16,3','15,4','16,4']),
    action:  'modal-games',
    worldCX: 16 * T,
    worldCY:  3 * T,
  },
  {
    id:      'play',
    label:   'Play',
    tileSet: new Set(['38,3','39,3','38,4','39,4']),
    action:  'modal-play',
    worldCX: 39 * T,
    worldCY:  3 * T,
  },
]

const activeHotspot    = ref(null)
const hoveredHotspot   = ref(null)   // mouse hovering over a hotspot tile
const openModal        = ref(null)
const labelBob         = ref(0)
const hotspotScreenPos = ref({ x: 0, y: 0 })
// All hotspot screen positions (for hover labels)
const allHotspotScreenPos = ref({})

let labelRaf = null
let labelT   = 0

function animateLabel() {
  labelT += 0.03
  labelBob.value = Math.sin(labelT) * 6
  labelRaf = requestAnimationFrame(animateLabel)
}

// Track which hotspot IDs have already auto-opened this visit
// so closing the modal doesn't immediately reopen it
const autoOpenedIds = new Set()

function checkHotspots() {
  const charTileC = Math.floor((charX + T)       / T)
  const charTileR = Math.floor((charY + T * 1.5) / T)
  const key = `${charTileC},${charTileR}`
  const found = HOTSPOTS.find(h => h.tileSet.has(key)) ?? null

  // When character LEAVES a hotspot, clear it from the opened set
  // so re-entering will open it again
  if (!found && activeHotspot.value) {
    autoOpenedIds.delete(activeHotspot.value.id)
  }

  // Auto-open once per entry
  if (found && !openModal.value && !autoOpenedIds.has(found.id)) {
    autoOpenedIds.add(found.id)
    fetchTriviaStats()
    openModalSafe(found.action)
  }

  activeHotspot.value = found
}

const modalMounted = ref(false)

function openModalSafe(action) {
  openModal.value = action
  modalMounted.value = false
  // Give Vue one tick to render the backdrop, then allow the chart to mount
  setTimeout(() => { modalMounted.value = true }, 50)
}

function closeModal() {
  openModal.value = null
  modalMounted.value = false
}

function handleHotspotClick(e) {
  if (e && (Math.abs(e.clientX - mouseDownX) > 5 || Math.abs(e.clientY - mouseDownY) > 5)) return
  if (!activeHotspot.value && !hoveredHotspot.value) return
  fetchTriviaStats()
  openModalSafe((activeHotspot.value ?? hoveredHotspot.value).action)
}

const triviaStats      = ref({})
const recentGames      = ref([])
const userHistory      = ref({})
const inferredSliders  = ref(null)
const realStrategyData = ref({})
const rawGames         = ref([])

async function fetchTriviaStats() {
  try {
    const res  = await fetch('/trivia/stats-json')
    const data = await res.json()
    triviaStats.value      = data.stats            ?? {}
    recentGames.value      = data.recent_games     ?? []
    userHistory.value      = data.user_history     ?? {}
    realStrategyData.value = data.real_strategy_data ?? {}
    rawGames.value         = data.raw_games        ?? []
    if (data.inferred_sliders?.observations > 0) {
      inferredSliders.value = data.inferred_sliders
    }
  } catch (e) { console.warn('stats fetch failed', e) }
}
const canvasRef = ref(null)
const charRef   = ref(null)

// Start in Room A
let charX   = 6 * T
let charY   = 6 * T
let facing  = 'down'
let walking = false
let held    = []
const SPEED = 1.5
let rafId   = null

// ── Tileset ───────────────────────────────────────────────────────────────
let tileImg = null, tileReady = false
function loadTileset() {
  return new Promise(resolve => {
    tileImg = new Image()
    tileImg.onload  = () => { tileReady = true; resolve() }
    tileImg.onerror = () => resolve()
    tileImg.src     = TILESET_SRC
  })
}

// ── Renderer ──────────────────────────────────────────────────────────────
function getPS() {
  // Each source pixel → N screen pixels. Lower = more map visible.
  // At 2×: 16px tile = 32px on screen. Canvas fills the frame so
  // all map content stays within the white rectangle.
  return 2
}

function fallbackColour(tile) {
  if (!tile) return 'transparent'
  const [, row] = tile
  if (row <= 5)  return '#5a6f8f'
  if (row <= 7)  return '#2a3a4f'
  if (row <= 13) return '#3d4e62'
  return '#5a4030'
}

// Build a set of map positions that belong to interactive hotspots (for glow)
const HOTSPOT_MAP_KEYS = new Set(HOTSPOTS.flatMap(h => [...h.tileSet]))

function drawMap(ctx, camX, camY) {
  const ps = getPS(), tps = T*ps
  const vpW = ctx.canvas.width, vpH = ctx.canvas.height
  ctx.clearRect(0, 0, vpW, vpH)

  const sc = Math.max(0, Math.floor(camX/T))
  const sr = Math.max(0, Math.floor(camY/T))
  const ec = Math.min(MW-1, Math.ceil((camX + vpW/ps)/T))
  const er = Math.min(MH-1, Math.ceil((camY + vpH/ps)/T))

  ctx.imageSmoothingEnabled = false
  for (let r = sr; r <= er; r++) {
    for (let c = sc; c <= ec; c++) {
      const tile = MAP[r][c]; if (!tile) continue
      const sx = Math.round((c*T - camX)*ps)
      const sy = Math.round((r*T - camY)*ps)

      if (tileReady) {
        if (DECOR_TILE_SET.has(`${tile[0]},${tile[1]}`)) {
          const floor = pickTile(FLOOR_CLEAN, r, c)
          ctx.drawImage(tileImg, floor[0]*T, floor[1]*T, T, T, sx, sy, tps, tps)
        }
        ctx.drawImage(tileImg, tile[0]*T, tile[1]*T, T, T, sx, sy, tps, tps)
      } else {
        ctx.fillStyle = fallbackColour(tile)
        ctx.fillRect(sx, sy, tps, tps)
      }

      // Faint white glow for interactive hotspot tiles
      if (HOTSPOT_MAP_KEYS.has(`${c},${r}`)) {
        ctx.save()
        ctx.globalCompositeOperation = 'source-over'
        ctx.shadowBlur   = tps * 0.8
        ctx.shadowColor  = 'rgba(200, 220, 255, 0.7)'
        ctx.fillStyle    = 'rgba(200, 220, 255, 0.12)'
        ctx.fillRect(sx, sy, tps, tps)
        ctx.restore()
      }
    }
  }
}

function renderFrame() {
  const canvas = canvasRef.value, char = charRef.value
  if (!canvas || !char) return
  const ps  = getPS(), vpW = canvas.width, vpH = canvas.height
  const camX = Math.max(0, Math.min(charX + T - vpW/(2*ps), MW*T - vpW/ps))
  const camY = Math.max(0, Math.min(charY + T - vpH/(2*ps), MH*T - vpH/ps))
  drawMap(canvas.getContext('2d'), camX, camY)
  char.style.transform = `translate3d(${Math.round((charX-camX)*ps)}px,${Math.round((charY-camY)*ps)}px,0)`
  char.setAttribute('facing',  facing)
  char.setAttribute('walking', walking ? 'true' : 'false')

  // Hotspot detection + label screen positions (for character-proximity AND hover)
  checkHotspots()
  const pos = {}
  for (const h of HOTSPOTS) {
    pos[h.id] = {
      x: Math.round((h.worldCX - camX) * ps),
      y: Math.round((h.worldCY - camY) * ps),
    }
  }
  allHotspotScreenPos.value = pos
  if (activeHotspot.value) {
    hotspotScreenPos.value = pos[activeHotspot.value.id]
  }
}

// ── Dpad highlight (keyboard, WASD, mouse movement) ──────────────────────
function highlightDpad(dir) {
  document.querySelectorAll('.rpg-dpad-btn').forEach(b => b.classList.remove('pressed'))
  if (dir) document.querySelector(`.dpad-${dir}`)?.classList.add('pressed')
}

// ── Mouse click-to-move ───────────────────────────────────────────────────
let mouseHeld    = false
let mouseTargetX = null
let mouseTargetY = null
let mouseDownX   = 0
let mouseDownY   = 0

// Which hotspot the mouse is hovering over (world→tile lookup on mousemove)
function onCameraMouseMove(e) {
  // Also handle click-to-move when held
  if (mouseHeld) updateMouseTarget(e)

  const canvas = canvasRef.value; if (!canvas) return
  const rect = canvas.getBoundingClientRect()
  const ps   = getPS()
  const camX = Math.max(0, Math.min(charX + T - canvas.width/(2*ps),  MW*T - canvas.width/ps))
  const camY = Math.max(0, Math.min(charY + T - canvas.height/(2*ps), MH*T - canvas.height/ps))
  const worldX = (e.clientX - rect.left) / ps + camX
  const worldY = (e.clientY - rect.top)  / ps + camY
  const tc = Math.floor(worldX / T)
  const tr = Math.floor(worldY / T)
  const key = `${tc},${tr}`
  hoveredHotspot.value = HOTSPOTS.find(h => h.tileSet.has(key)) ?? null
}

function onCameraMouseLeave() { hoveredHotspot.value = null }

function onMouseDown(e) {
  mouseHeld  = true
  mouseDownX = e.clientX
  mouseDownY = e.clientY
  updateMouseTarget(e)
}
function onMouseUp(e) {
  mouseHeld = false
  // Treat as click (not drag) if mouse barely moved
  if (Math.abs(e.clientX - mouseDownX) <= 5 && Math.abs(e.clientY - mouseDownY) <= 5) {
    handleHotspotClick(e)
  }
}

function updateMouseTarget(e) {
  const canvas = canvasRef.value; if (!canvas) return
  const rect = canvas.getBoundingClientRect()
  const ps   = getPS()
  const camX = Math.max(0, Math.min(charX + T - canvas.width/(2*ps),  MW*T - canvas.width/ps))
  const camY = Math.max(0, Math.min(charY + T - canvas.height/(2*ps), MH*T - canvas.height/ps))
  mouseTargetX = (e.clientX - rect.left) / ps + camX - T
  mouseTargetY = (e.clientY - rect.top)  / ps + camY - T*2
}

// ── Game loop ─────────────────────────────────────────────────────────────
function step() {
  const dir = held[0]; let nx = charX, ny = charY
  if (dir === 'right') nx += SPEED
  if (dir === 'left')  nx -= SPEED
  if (dir === 'down')  ny += SPEED
  if (dir === 'up')    ny -= SPEED
  if (dir) facing = dir

  let activeDir = null

  if (dir && canWalk(nx, ny)) {
    charX = nx; charY = ny; walking = true; activeDir = dir
  } else if (dir) {
    if      (canWalk(nx, charY)) { charX = nx; walking = true; activeDir = dir }
    else if (canWalk(charX, ny)) { charY = ny; walking = true; activeDir = dir }
    else walking = false
  } else if (mouseHeld && mouseTargetX !== null) {
    const dx = mouseTargetX - charX
    const dy = mouseTargetY - charY
    const dist = Math.sqrt(dx*dx + dy*dy)
    if (dist > SPEED) {
      const mx = charX + (dx/dist)*SPEED
      const my = charY + (dy/dist)*SPEED
      if      (canWalk(mx, my))    { charX = mx; charY = my; walking = true }
      else if (canWalk(mx, charY)) { charX = mx; walking = true }
      else if (canWalk(charX, my)) { charY = my; walking = true }
      else walking = false
      if (Math.abs(dx) > Math.abs(dy)) facing = dx > 0 ? 'right' : 'left'
      else facing = dy > 0 ? 'down' : 'up'
      activeDir = facing
    } else {
      mouseTargetX = null; mouseTargetY = null; walking = false
    }
  } else {
    walking = false
  }

  if (!dpadDown) highlightDpad(walking ? activeDir : null)

  renderFrame()
  rafId = requestAnimationFrame(step)
}

// ── Input ─────────────────────────────────────────────────────────────────
const KEY_MAP = {
  ArrowUp:'up', ArrowLeft:'left', ArrowRight:'right', ArrowDown:'down',
  KeyW:'up', KeyA:'left', KeyD:'right', KeyS:'down',
}
function onKeydown(e) {
  const d = KEY_MAP[e.code]
  if (d) { e.preventDefault(); if (!held.includes(d)) held.unshift(d) }
}
function onKeyup(e) {
  const d = KEY_MAP[e.code]; const i = held.indexOf(d)
  if (i > -1) held.splice(i, 1)
}
let dpadDown = false
function dpadPress(dir, click = false) {
  if (click) dpadDown = true
  held = dpadDown ? [dir] : []
  document.querySelectorAll('.rpg-dpad-btn').forEach(b => b.classList.remove('pressed'))
  if (dpadDown) document.querySelector(`.dpad-${dir}`)?.classList.add('pressed')
}
function dpadRelease() {
  dpadDown = false; held = []
  document.querySelectorAll('.rpg-dpad-btn').forEach(b => b.classList.remove('pressed'))
}
function resizeCanvas() {
  const c = canvasRef.value; if (!c) return
  const cam = c.parentElement; if (!cam) return
  const w = cam.clientWidth, h = cam.clientHeight
  // Guard: if layout hasn't happened yet, try again on next frame
  if (w === 0 || h === 0) { requestAnimationFrame(resizeCanvas); return }
  if (c.width !== w || c.height !== h) { c.width = w; c.height = h }
}

onMounted(async () => {
  // 1. Load tileset first (base64 so this is nearly instant, but still async)
  await loadTileset()

  // 2. Give the browser one frame to finish CSS layout so clientWidth/Height are real
  await new Promise(resolve => requestAnimationFrame(resolve))
  resizeCanvas()

  // 3. Start game loop and listeners
  window.addEventListener('keydown', onKeydown)
  window.addEventListener('keyup',   onKeyup)
  window.addEventListener('mouseup', dpadRelease)
  window.addEventListener('resize',  () => { resizeCanvas(); renderFrame() })
  rafId = requestAnimationFrame(step)
  animateLabel()
})
onUnmounted(() => {
  window.removeEventListener('keydown', onKeydown)
  window.removeEventListener('keyup',   onKeyup)
  window.removeEventListener('mouseup', dpadRelease)
  window.removeEventListener('resize',  resizeCanvas)
  if (rafId) cancelAnimationFrame(rafId)
  if (labelRaf) cancelAnimationFrame(labelRaf)
})
</script>

<template>
  <div class="rpg-page">
    <div class="rpg-frame">
      <div class="c_tl"></div><div class="c_tr"></div>
      <div class="c_bl"></div><div class="c_br"></div>

      <div class="rpg-camera"
        @mousedown="onMouseDown"
        @mousemove="onCameraMouseMove"
        @mouseleave="onCameraMouseLeave"
        @mouseup="onMouseUp">

        <canvas ref="canvasRef" class="rpg-canvas"></canvas>

        <!-- Character-proximity label (bobs up and down) -->
        <Transition name="label-fade">
          <div
            v-if="activeHotspot && !openModal"
            class="hotspot-label"
            :style="{
              left: hotspotScreenPos.x + 'px',
              top:  (hotspotScreenPos.y - 24 + labelBob) + 'px',
            }"
            @click.stop="handleHotspotClick">
            {{ activeHotspot.label }}
          </div>
        </Transition>

        <!-- Hover labels for each hotspot (shown when mouse is over the tile) -->
        <template v-if="!openModal">
          <div
            v-for="h in HOTSPOTS"
            :key="h.id + '-hover'"
            v-show="hoveredHotspot && hoveredHotspot.id === h.id && (!activeHotspot || activeHotspot.id !== h.id)"
            class="hotspot-label hotspot-label--hover"
            :style="{
              left: (allHotspotScreenPos[h.id]?.x ?? 0) + 'px',
              top:  (allHotspotScreenPos[h.id]?.y ?? 0) - 24 + 'px',
            }"
            @click.stop="handleHotspotClick">
            {{ h.label }}
          </div>
        </template>

        <div class="rpg-character" facing="down" walking="false" ref="charRef">
          <div class="rpg-shadow"></div>
          <div class="rpg-sprite"></div>
        </div>

        <div class="rpg-dpad">
          <button class="rpg-dpad-btn dpad-left" @mousedown.prevent="dpadPress('left',true)" @mouseover="dpadPress('left')" @touchstart.prevent="dpadPress('left',true)" @touchend.prevent="dpadRelease">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -0.5 13 13" shape-rendering="crispEdges"><path class="Arrow_outline-top" stroke="#5f5f5f" d="M1 0h11M0 1h1M12 1h1M0 2h1M12 2h1M0 3h1M12 3h1M0 4h1M12 4h1M0 5h1M12 5h1M0 6h1M12 6h1M0 7h1M12 7h1M0 8h1M12 8h1"/><path class="Arrow_surface" stroke="#f5f5f5" d="M1 1h11M1 2h11M1 3h5M7 3h5M1 4h4M7 4h5M1 5h3M7 5h5M1 6h4M7 6h5M1 7h5M7 7h5M1 8h11"/><path class="Arrow_arrow-inset" stroke="#434343" d="M6 3h1M5 4h1M4 5h1"/><path class="Arrow_arrow-body" stroke="#5f5f5f" d="M6 4h1M5 5h2M5 6h2M6 7h1"/><path class="Arrow_outline-bottom" stroke="#434343" d="M0 9h1M12 9h1M0 10h1M12 10h1M0 11h1M12 11h1M1 12h11"/><path class="Arrow_edge" stroke="#ffffff" d="M1 9h11"/><path class="Arrow_front" stroke="#cccccc" d="M1 10h11M1 11h11"/></svg>
          </button>
          <button class="rpg-dpad-btn dpad-up" @mousedown.prevent="dpadPress('up',true)" @mouseover="dpadPress('up')" @touchstart.prevent="dpadPress('up',true)" @touchend.prevent="dpadRelease">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -0.5 13 13" shape-rendering="crispEdges"><path class="Arrow_outline-top" stroke="#5f5f5f" d="M1 0h11M0 1h1M12 1h1M0 2h1M12 2h1M0 3h1M12 3h1M0 4h1M12 4h1M0 5h1M12 5h1M0 6h1M12 6h1M0 7h1M12 7h1M0 8h1M12 8h1"/><path class="Arrow_surface" stroke="#f5f5f5" d="M1 1h11M1 2h11M1 3h11M1 4h5M7 4h5M1 5h4M8 5h4M1 6h3M9 6h3M1 7h11M1 8h11"/><path class="Arrow_arrow-inset" stroke="#434343" d="M6 4h1M5 5h1M7 5h1"/><path class="Arrow_arrow-body" stroke="#5f5f5f" d="M6 5h1M4 6h5"/><path class="Arrow_outline-bottom" stroke="#434343" d="M0 9h1M12 9h1M0 10h1M12 10h1M0 11h1M12 11h1M1 12h11"/><path class="Arrow_edge" stroke="#ffffff" d="M1 9h11"/><path class="Arrow_front" stroke="#cccccc" d="M1 10h11M1 11h11"/></svg>
          </button>
          <button class="rpg-dpad-btn dpad-down" @mousedown.prevent="dpadPress('down',true)" @mouseover="dpadPress('down')" @touchstart.prevent="dpadPress('down',true)" @touchend.prevent="dpadRelease">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -0.5 13 13" shape-rendering="crispEdges"><path class="Arrow_outline-top" stroke="#5f5f5f" d="M1 0h11M0 1h1M12 1h1M0 2h1M12 2h1M0 3h1M12 3h1M0 4h1M12 4h1M0 5h1M12 5h1M0 6h1M12 6h1M0 7h1M12 7h1M0 8h1M12 8h1"/><path class="Arrow_surface" stroke="#f5f5f5" d="M1 1h11M1 2h11M1 3h11M1 4h3M9 4h3M1 5h4M8 5h4M1 6h5M7 6h5M1 7h11M1 8h11"/><path class="Arrow_arrow-inset" stroke="#434343" d="M4 4h5"/><path class="Arrow_arrow-body" stroke="#5f5f5f" d="M5 5h3M6 6h1"/><path class="Arrow_outline-bottom" stroke="#434343" d="M0 9h1M12 9h1M0 10h1M12 10h1M0 11h1M12 11h1M1 12h11"/><path class="Arrow_edge" stroke="#ffffff" d="M1 9h11"/><path class="Arrow_front" stroke="#cccccc" d="M1 10h11M1 11h11"/></svg>
          </button>
          <button class="rpg-dpad-btn dpad-right" @mousedown.prevent="dpadPress('right',true)" @mouseover="dpadPress('right')" @touchstart.prevent="dpadPress('right',true)" @touchend.prevent="dpadRelease">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -0.5 13 13" shape-rendering="crispEdges"><path class="Arrow_outline-top" stroke="#5f5f5f" d="M1 0h11M0 1h1M12 1h1M0 2h1M12 2h1M0 3h1M12 3h1M0 4h1M12 4h1M0 5h1M12 5h1M0 6h1M12 6h1M0 7h1M12 7h1M0 8h1M12 8h1"/><path class="Arrow_surface" stroke="#f5f5f5" d="M1 1h11M1 2h11M1 3h5M7 3h5M1 4h5M8 4h4M1 5h5M9 5h3M1 6h5M8 6h4M1 7h5M7 7h5M1 8h11"/><path class="Arrow_arrow-inset" stroke="#434343" d="M6 3h1M7 4h1M8 5h1"/><path class="Arrow_arrow-body" stroke="#5f5f5f" d="M6 4h1M6 5h2M6 6h2M6 7h1"/><path class="Arrow_outline-bottom" stroke="#434343" d="M0 9h1M12 9h1M0 10h1M12 10h1M0 11h1M12 11h1M1 12h11"/><path class="Arrow_edge" stroke="#ffffff" d="M1 9h11"/><path class="Arrow_front" stroke="#cccccc" d="M1 10h11M1 11h11"/></svg>
          </button>
        </div>
      </div>
    </div>
    <p class="rpg-hint">Arrow keys · WASD · D-Pad &nbsp;|&nbsp; 4 rooms to explore</p>

    <!-- ── Modals ─────────────────────────────────────────────────────────── -->
    <Transition name="modal-fade">
      <div v-if="openModal" class="rpg-modal-backdrop" @click.self="closeModal">
        <div class="rpg-modal">
          <button class="rpg-modal-close" @click="closeModal">✕</button>
          <div class="rpg-modal-body">
            <div v-if="openModal === 'modal-ai' && !modalMounted" style="padding:40px;text-align:center;color:rgba(255,255,255,0.4);font-size:13px">Loading…</div>
            <GameTheoryChart
              v-if="openModal === 'modal-ai' && modalMounted"
              :trivia-stats="triviaStats"
              :recent-games="recentGames"
              :user-history="userHistory"
              :inferred-sliders="inferredSliders"
              :real-strategy-data="realStrategyData"
            />
            <TriviaGamesTable
              v-if="openModal === 'modal-games'"
              :games="rawGames"
            />
            <TriviaIndex v-if="openModal === 'modal-play'" />
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.rpg-page {
  --rpg-gc: calc(4px * 16);
  display: flex; flex-direction: column; align-items: stretch;
  padding: 20px 24px 12px;
  gap: 8px;
  background-image: url('https://images.unsplash.com/photo-1475274047050-1d0c0975c63e?q=80&w=2672&auto=format&fit=crop');
  background-size: cover; background-position: center;
  min-height: calc(100vh - 64px);
}

.rpg-frame {
  flex: 0 0 auto;
  height: clamp(480px, 72vh, 680px);
  width: min(1280px, 92vw);
  margin: auto;
  outline: 2px solid rgba(255,255,255,0.45);
  position: relative; z-index: 1;
  border-radius: 2px;
}

.c_tl,.c_tr,.c_bl,.c_br { display: none; }

/* Camera and canvas fill the frame exactly so no map spills outside */
.rpg-camera {
  position: absolute; inset: 0;
  overflow: hidden; background: transparent;
}
.rpg-canvas {
  position: absolute; top: 0; left: 0;
  width: 100% !important; height: 100% !important;
  image-rendering: pixelated; display: block;
}

.rpg-character {
  width: calc(var(--rpg-gc) * 2); height: calc(var(--rpg-gc) * 2);
  position: absolute; top: 0; left: 0;
  overflow: hidden; pointer-events: none; z-index: 10;
}
.rpg-shadow {
  width: calc(var(--rpg-gc) * 2); height: calc(var(--rpg-gc) * 2);
  position: absolute; left: 0; top: 0;
  background: url("https://assets.codepen.io/21542/DemoRpgCharacterShadow.png") no-repeat;
  background-size: 100%; image-rendering: pixelated;
}
.rpg-sprite {
  position: absolute;
  background: url("https://assets.codepen.io/21542/DemoRpgCharacter.png") no-repeat;
  background-size: 100%;
  width: calc(var(--rpg-gc) * 8); height: calc(var(--rpg-gc) * 8);
  image-rendering: pixelated;
}
.rpg-character[facing="right"] .rpg-sprite { background-position-y: calc(4px * -32); }
.rpg-character[facing="up"]    .rpg-sprite { background-position-y: calc(4px * -64); }
.rpg-character[facing="left"]  .rpg-sprite { background-position-y: calc(4px * -96); }
.rpg-character[walking="true"] .rpg-sprite { animation: walk 0.6s steps(4) infinite; }
@keyframes walk { from { transform: translate3d(0%,0%,0); } to { transform: translate3d(-100%,0%,0); } }

.rpg-dpad {
  position: absolute; right: 12px; bottom: 12px;
  width: 108px; height: 110px; user-select: none;
}
.rpg-dpad-btn { appearance: none; outline: 0; border: 0; background: transparent; padding: 0; cursor: pointer; position: absolute; }
.rpg-dpad-btn svg { display: block; height: 36px; }
.rpg-dpad-btn.pressed .Arrow_arrow-inset { stroke: #07c2cc; }
.rpg-dpad-btn.pressed .Arrow_arrow-body  { stroke: #17dfea; }
.dpad-up    { top: 0;    left: 36px; }
.dpad-down  { bottom: 0; left: 36px; }
.dpad-left  { top: 36px; left: 0; }
.dpad-right { top: 36px; right: 0; }

.rpg-hint { font-family: 'DM Mono', monospace; font-size: 11px; color: rgba(255,255,255,0.55); letter-spacing: .05em; text-shadow: 0 1px 3px rgba(0,0,0,0.8); text-align: center; flex-shrink: 0; }

/* ── Floating hotspot label ─────────────────────────────────────────────── */
.hotspot-label {
  position: absolute;
  transform: translateX(-50%);
  background: rgba(8, 12, 24, 0.9);
  color: #c8e0ff;
  font-family: 'DM Sans', sans-serif;
  font-size: 15px;
  font-weight: 600;
  letter-spacing: 0.04em;
  padding: 6px 16px;
  border-radius: 6px;
  border: 1px solid rgba(120, 180, 255, 0.5);
  pointer-events: auto;
  cursor: pointer;
  white-space: nowrap;
  z-index: 30;
  user-select: none;
  box-shadow: 0 2px 12px rgba(0,0,0,0.6), 0 0 16px rgba(100,160,255,0.15);
}
.hotspot-label:hover { border-color: rgba(140,200,255,0.9); color: #fff; }
.hotspot-label--hover { transition: opacity 0.15s ease; }
.label-fade-enter-active, .label-fade-leave-active { transition: opacity 0.2s ease; }
.label-fade-enter-from,  .label-fade-leave-to      { opacity: 0; }

/* ── Modal ──────────────────────────────────────────────────────────────── */
.rpg-modal-backdrop {
  position: fixed; inset: 0; z-index: 200;
  background: rgba(0, 0, 0, 0.75);
  display: flex; align-items: center; justify-content: center;
  backdrop-filter: blur(4px);
}
.rpg-modal {
  background: oklch(var(--b1, 0.15 0 0));
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 12px;
  width: min(92vw, 1100px);
  max-height: 88vh;
  display: flex; flex-direction: column;
  position: relative; overflow: hidden;
  box-shadow: 0 24px 80px rgba(0,0,0,0.6);
}
.rpg-modal-close {
  position: absolute; top: 14px; right: 18px;
  background: none; border: none; font-size: 20px; line-height: 1;
  cursor: pointer; color: rgba(255,255,255,0.4); z-index: 10;
  transition: color 0.15s;
}
.rpg-modal-close:hover { color: rgba(255,255,255,0.9); }
.rpg-modal-body { overflow-y: auto; padding: 28px 24px; flex: 1; }
.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.2s ease; }
.modal-fade-enter-from,  .modal-fade-leave-to      { opacity: 0; }
</style>
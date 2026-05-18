/**
 * tileInventory.js
 * ─────────────────────────────────────────────────────────────────────────────
 * Tile catalogue for TileSet_v1_0 (320×320px, 16×16px tiles, 20×20 grid).
 * Tile address [col, row] → drawImage source x=col*16, y=row*16.
 *
 * CATEGORIES
 * ──────────
 * FLOOR_WALKABLE  Screenshots 1 & 2 — steel floor tiles the character walks on.
 *                 rows 0-5 cols 0-5 (clean) + rows 8-13 cols 0-5 (dirty/scuffed).
 *
 * WALL_BORDER     The outer ring of the two pit-frame graphics.
 *                 Frame 1 (rows 0-5, cols 6-11) and Frame 2 (rows 8-13, cols 6-11).
 *                 Each frame provides directional wall pieces:
 *                 top, bottom, left, right edges and four corners.
 *                 These are IMPASSABLE and render around room perimeters.
 *
 * DECOR_WALKABLE  Screenshots 3, 4, 5 — props placed on top of floor tiles.
 *                 Character walks over them (they are purely visual).
 *                 rows 0-7 cols 12-19, rows 10-11 cols 12-15, rows 14-15 cols 2-15.
 * ─────────────────────────────────────────────────────────────────────────────
 */

export { TILESET_SRC } from './tilesetData.js'
export const TILE_SIZE   = 16

// ── Helpers ───────────────────────────────────────────────────────────────
function grid(rowStart, rowEnd, colStart, colEnd) {
  const out = []
  for (let r = rowStart; r <= rowEnd; r++)
    for (let c = colStart; c <= colEnd; c++)
      out.push([c, r])
  return out
}

// ─────────────────────────────────────────────────────────────────────────
// FLOOR TILES  (screenshots 1 & 2 — walkable base layer)
// ─────────────────────────────────────────────────────────────────────────
export const FLOOR_CLEAN = grid(0, 5, 0, 5)   // plain steel, rows 0-5 cols 0-5
export const FLOOR_DIRTY = grid(8, 13, 0, 5)  // scuffed steel, rows 8-13 cols 0-5

// ─────────────────────────────────────────────────────────────────────────
// WALL / BORDER TILES  (outer ring of the two pit frames — impassable)
//
// Frame 1 occupies rows 0-5, cols 6-11 in the tileset.
// Its outer ring provides 8 directional wall pieces:
//   Top edge:    row 0, cols 6-11  (has dash marks on top)
//   Bottom edge: row 5, cols 6-11  (has dash marks on bottom)
//   Left edge:   col 6, rows 0-5   (has dash marks on left)
//   Right edge:  col 11, rows 0-5  (has dash marks on right)
//   Corners are the intersections of those.
//
// Frame 2 occupies rows 8-13, cols 6-11 — identical layout, used for
// visual variety on room C (dirty) borders.
// ─────────────────────────────────────────────────────────────────────────
export const WALL = {
  // ── Frame 1 (clean-room border) ──
  TOP_LEFT:     [6,  0],
  TOP:          [7,  0],   // repeated across top
  TOP_MID:      [8,  0],   // mid-top (has centre dash mark)
  TOP_RIGHT:    [11, 0],
  LEFT:         [6,  1],   // repeated down left side
  LEFT_MID:     [6,  2],
  RIGHT:        [11, 1],
  RIGHT_MID:    [11, 2],
  INNER_TL:     [7,  1],   // inner corner top-left
  INNER_TR:     [10, 1],
  INNER_BL:     [7,  4],
  INNER_BR:     [10, 4],
  BOTTOM_LEFT:  [6,  5],
  BOTTOM:       [7,  5],
  BOTTOM_MID:   [9,  5],
  BOTTOM_RIGHT: [11, 5],

  // ── Frame 2 (dirty-room border — same positions +8 rows) ──
  D_TOP_LEFT:     [6,  8],
  D_TOP:          [7,  8],
  D_TOP_MID:      [8,  8],
  D_TOP_RIGHT:    [11, 8],
  D_LEFT:         [6,  9],
  D_LEFT_MID:     [6,  10],
  D_RIGHT:        [11, 9],
  D_RIGHT_MID:    [11, 10],
  D_BOTTOM_LEFT:  [6,  13],
  D_BOTTOM:       [7,  13],
  D_BOTTOM_MID:   [9,  13],
  D_BOTTOM_RIGHT: [11, 13],
}

// Pool for filling generic wall segments (top / left / right / bottom)
export const WALL_TOP_POOL    = [[6,0],[7,0],[8,0],[9,0],[10,0],[11,0]]
export const WALL_BOTTOM_POOL = [[6,5],[7,5],[8,5],[9,5],[10,5],[11,5]]
export const WALL_LEFT_POOL   = [[6,0],[6,1],[6,2],[6,3],[6,4],[6,5]]
export const WALL_RIGHT_POOL  = [[11,0],[11,1],[11,2],[11,3],[11,4],[11,5]]

// Dirty variants (frame 2)
export const WALL_D_TOP_POOL    = [[6,8],[7,8],[8,8],[9,8],[10,8],[11,8]]
export const WALL_D_BOTTOM_POOL = [[6,13],[7,13],[8,13],[9,13],[10,13],[11,13]]
export const WALL_D_LEFT_POOL   = [[6,8],[6,9],[6,10],[6,11],[6,12],[6,13]]
export const WALL_D_RIGHT_POOL  = [[11,8],[11,9],[11,10],[11,11],[11,12],[11,13]]

// Set of ALL border/wall tile coords (impassable)
export const WALL_TILE_SET = new Set([
  ...grid(0, 5, 6, 11),    // frame 1 full area (includes inner tiles)
  ...grid(8, 13, 6, 11),   // frame 2 full area
].map(([c, r]) => `${c},${r}`))

// ─────────────────────────────────────────────────────────────────────────
// DECOR TILES  (screenshots 3, 4, 5 — walkable props on the floor)
// Named 2×2 sprite objects; placeDecor() in the map builder uses these.
// ─────────────────────────────────────────────────────────────────────────
export const DECOR_OBJECTS = {
  // Screenshot 4 — rows 2-7, cols 12-19
  SHELF_A:  { tiles: [[12,2],[13,2],[12,3],[13,3]], cols:2, rows:2 },
  TERMINAL: { tiles: [[14,2],[15,2],[14,3],[15,3]], cols:2, rows:2 },
  MONITOR:  { tiles: [[16,2],[17,2],[16,3],[17,3]], cols:2, rows:2 },
  CABINET:  { tiles: [[18,2],[19,2],[18,3],[19,3]], cols:2, rows:2 },
  BOOKCASE: { tiles: [[12,4],[13,4],[12,5],[13,5]], cols:2, rows:2 },
  SERVER:   { tiles: [[14,4],[15,4],[14,5],[15,5]], cols:2, rows:2 },
  FILER:    { tiles: [[16,4],[17,4],[16,5],[17,5]], cols:2, rows:2 },
  STAND:    { tiles: [[18,4],[19,4],[18,5],[19,5]], cols:2, rows:2 },
  SHELF_B:  { tiles: [[12,6],[13,6],[12,7],[13,7]], cols:2, rows:2 },
  CHEST_A:  { tiles: [[14,6],[15,6],[14,7],[15,7]], cols:2, rows:2 },
  SHELF_C:  { tiles: [[16,6],[17,6],[16,7],[17,7]], cols:2, rows:2 },
  MISC_A:   { tiles: [[18,6],[19,6],[18,7],[19,7]], cols:2, rows:2 },
  // Screenshot 3 — rows 10-11 cols 12-15
  RADIO:    { tiles: [[12,10],[13,10],[12,11],[13,11]], cols:2, rows:2 },
  MACHINE:  { tiles: [[14,10],[15,10],[14,11],[15,11]], cols:2, rows:2 },
  // Screenshot 5 — rows 14-15 (crystals, chests, lockers)
  CRYSTAL_A:{ tiles: [[2,14],[3,14]],                  cols:2, rows:1 },
  CRYSTAL_B:{ tiles: [[4,14],[5,14]],                  cols:2, rows:1 },
  CRYSTAL_C:{ tiles: [[6,14],[7,14]],                  cols:2, rows:1 },
  CHEST_B:  { tiles: [[8,14],[9,14],[8,15],[9,15]],    cols:2, rows:2 },
  CHEST_C:  { tiles: [[10,14],[11,14],[10,15],[11,15]],cols:2, rows:2 },
  LOCKER_A: { tiles: [[12,14],[13,14],[12,15],[13,15]],cols:2, rows:2 },
  LOCKER_B: { tiles: [[14,14],[15,14],[14,15],[15,15]],cols:2, rows:2 },
}

export const DECOR_TILE_SET = new Set(
  Object.values(DECOR_OBJECTS)
    .flatMap(o => o.tiles)
    .map(([c,r]) => `${c},${r}`)
)

// ─────────────────────────────────────────────────────────────────────────
// UTILITIES
// ─────────────────────────────────────────────────────────────────────────

/** Pick a tile from pool deterministically by map position */
export function pickTile(pool, mapRow, mapCol) {
  return pool[(mapRow * 7 + mapCol * 3) % pool.length]
}

/** True if the tile can be walked on */
export function isTileWalkable(tile) {
  if (!tile) return false
  const key = `${tile[0]},${tile[1]}`
  if (WALL_TILE_SET.has(key)) return false   // wall frame = impassable
  return true                                 // floor + decor = walkable
}
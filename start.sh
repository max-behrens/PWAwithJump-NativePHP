#!/bin/bash

# ─────────────────────────────────────────────────────────────────
# TriviaBuff — start script for Replit
# Starts: Laravel (port 8000), Vite dev server, Python AI (port 5001)
# ─────────────────────────────────────────────────────────────────

ROOT="$(cd "$(dirname "$0")" && pwd)"
LOG_DIR="$ROOT/storage/logs"
mkdir -p "$LOG_DIR"

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "  TriviaBuff — starting all services"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# ── 1. Install PHP dependencies if needed ─────────────────────────
if [ ! -d "$ROOT/vendor" ]; then
  echo "▸ Installing PHP dependencies..."
  cd "$ROOT" && composer install --no-interaction --prefer-dist 2>&1 | tail -5
fi

# ── 2. Install Node dependencies if needed ────────────────────────
if [ ! -d "$ROOT/node_modules" ]; then
  echo "▸ Installing Node dependencies..."
  cd "$ROOT" && npm install 2>&1 | tail -5
fi

# ── 3. Copy .env if missing ───────────────────────────────────────
if [ ! -f "$ROOT/.env" ]; then
  echo "▸ Creating .env from .env.example..."
  cp "$ROOT/.env.example" "$ROOT/.env"
  cd "$ROOT" && php artisan key:generate
fi

# ── 4. Run migrations ─────────────────────────────────────────────
echo "▸ Running migrations..."
cd "$ROOT" && php artisan migrate --force 2>&1 | tail -5 || echo "  (migration errors above — continuing anyway)"

# ── 5. Seed if trivia_questions is empty ──────────────────────────
QUESTION_COUNT=$(cd "$ROOT" && php artisan tinker --execute="echo \DB::table('trivia_questions')->count();" 2>/dev/null | tail -1)
if [ "$QUESTION_COUNT" = "0" ] || [ -z "$QUESTION_COUNT" ]; then
  echo "▸ Seeding trivia questions..."
  cd "$ROOT" && php artisan db:seed --class=TriviaQuestionSeeder --force 2>&1 | tail -3 || echo "  (seed errors above — continuing anyway)"
fi

# ── 6. Clear caches ───────────────────────────────────────────────
echo "▸ Clearing caches..."
cd "$ROOT" && php artisan config:clear 2>/dev/null || true
cd "$ROOT" && php artisan route:clear  2>/dev/null || true
cd "$ROOT" && php artisan view:clear   2>/dev/null || true

# ── Kill any leftover processes from previous runs ────────────────
echo "▸ Clearing any processes from previous runs..."
pkill -f "ai/server.py"  2>/dev/null || true
pkill -f "artisan serve" 2>/dev/null || true
pkill -f "vite"          2>/dev/null || true
fuser -k 5001/tcp 2>/dev/null || true
fuser -k 5173/tcp 2>/dev/null || true
fuser -k 8000/tcp 2>/dev/null || true
sleep 2

echo ""
echo "▸ Starting services..."
echo ""

mkdir -p "$ROOT/database"
touch "$ROOT/database/database.sqlite"

export DB_PATH="$ROOT/database/database.sqlite"

# ── 7. Start Laravel FIRST so Replit's preview opens the main app ─
if [ -n "$REPL_ID" ] || [ -n "$REPLIT_DB_URL" ]; then
  LARAVEL_HOST="0.0.0.0"
  LARAVEL_PORT="8000"
else
  LARAVEL_HOST="127.0.0.1"
  LARAVEL_PORT="8001"
fi

echo "  [Laravel] Starting on $LARAVEL_HOST:$LARAVEL_PORT"
cd "$ROOT" && php artisan serve --host="$LARAVEL_HOST" --port="$LARAVEL_PORT" > "$LOG_DIR/laravel.log" 2>&1 &
LARAVEL_PID=$!

# Wait until Laravel is actually listening so it claims the preview slot first
for i in {1..20}; do
  if curl -s -o /dev/null "http://127.0.0.1:$LARAVEL_PORT/"; then
    echo "  [Laravel] Ready"
    break
  fi
  sleep 0.25
done

# ── 8. Start Python AI server ─────────────────────────────────────
echo "  [AI]     Starting Python server on 0.0.0.0:5001"
echo "  [DB]     database.sqlite at $DB_PATH"

python3 -m py_compile "$ROOT/ai/server.py" && echo "Syntax OK" || echo "SYNTAX ERROR"

echo "Python is at: $(which python3)"
python3 --version
python3 "$ROOT/ai/server.py" > "$LOG_DIR/python.log" 2>&1 &
PYTHON_PID=$!
sleep 2
echo "=== Python log ==="
cat "$LOG_DIR/python.log"
echo "=================="

# ── 9. Start Vite ─────────────────────────────────────────────────
echo "  [Vite]   Starting asset bundler on port 5173"
cd "$ROOT" && npm run dev > "$LOG_DIR/vite.log" 2>&1 &
VITE_PID=$!
echo "           PID $VITE_PID → logs/vite.log"

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "  All services running"
echo "  AI:     port 5001"
echo "  Vite:   port 5173"
echo "  App:    port 8000"
echo ""
echo "  Logs:   storage/logs/"
echo "  Stop:   Ctrl+C or kill $PYTHON_PID $VITE_PID $LARAVEL_PID"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# ── Keep alive and handle Ctrl+C ──────────────────────────────────
cleanup() {
  echo ""
  echo "▸ Stopping all services..."
  kill $PYTHON_PID $VITE_PID $LARAVEL_PID 2>/dev/null
  echo "  Done."
  exit 0
}

trap cleanup INT TERM

wait -n 2>/dev/null || true

echo ""
echo "⚠  A service exited unexpectedly. Check storage/logs/ for details."
echo "   Keeping remaining services alive. Press Ctrl+C to stop all."

wait
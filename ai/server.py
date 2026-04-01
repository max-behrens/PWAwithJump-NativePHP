#!/usr/bin/env python3
"""
TaskFlow AI Server
- Task completion predictor (Naive Bayes)
- Trivia game AI (game-theory strategy learner)
Runs on 127.0.0.1:5001

TRIVIA AI STRATEGY MODEL
========================
The AI treats each round as a 2x2 game theory problem.
It chooses ONE of 4 strategies:
  A = answer_correct + no_steal   → safe play, scores base pts if correct
  B = answer_correct + steal      → aggressive, scores base+1 if opponent correct
  C = answer_wrong   + no_steal   → BAIT TRAP, costs nothing, punishes opponent steal
  D = answer_wrong   + steal      → rarely optimal, high risk

Strategy weights per difficulty are updated after every round based on
actual points earned. The AI also reads your recent steal behaviour within
the current game to adapt in real time.
"""

import json
import math
import sqlite3
import os
import re
import random
from http.server import HTTPServer, BaseHTTPRequestHandler
from urllib.parse import urlparse, parse_qs

DB_PATH = os.path.join(
    os.path.dirname(os.path.dirname(os.path.abspath(__file__))),
    'database', 'database.sqlite'
)
PORT = 5001


def get_db():
    conn = sqlite3.connect(DB_PATH)
    conn.row_factory = sqlite3.Row
    return conn


# ─────────────────────────────────────────────
# TASK AI - Naive Bayes (unchanged)
# ─────────────────────────────────────────────

def tokenize(text):
    if not text:
        return []
    text = text.lower()
    text = re.sub(r'[^a-z0-9\s]', ' ', text)
    tokens = text.split()
    stopwords = {'a','an','the','is','it','to','do','and','or','for','of','in','on','at','be','my','i','me','we'}
    return [t for t in tokens if len(t) > 2 and t not in stopwords]


class NaiveBayes:
    def __init__(self):
        self.class_counts = {}
        self.word_counts = {}
        self.vocab = set()
        self.total_docs = 0

    def train(self, tasks):
        self.class_counts = {'completed': 0, 'pending': 0}
        self.word_counts = {'completed': {}, 'pending': {}}
        self.vocab = set()
        self.total_docs = len(tasks)
        for task in tasks:
            label = 'completed' if task['completed'] else 'pending'
            self.class_counts[label] += 1
            text = (task['title'] or '') + ' ' + (task['description'] or '')
            for token in tokenize(text):
                self.vocab.add(token)
                self.word_counts[label][token] = self.word_counts[label].get(token, 0) + 1

    def predict(self, text):
        tokens = tokenize(text)
        scores = {}
        vocab_size = len(self.vocab) or 1
        for label in ['completed', 'pending']:
            count = self.class_counts.get(label, 0)
            if self.total_docs == 0 or count == 0:
                scores[label] = -999
                continue
            score = math.log(count / self.total_docs)
            total_words = sum(self.word_counts[label].values()) + vocab_size
            for token in tokens:
                word_count = self.word_counts[label].get(token, 0) + 1
                score += math.log(word_count / total_words)
            scores[label] = score
        best = max(scores, key=scores.get)
        diff = scores['completed'] - scores['pending']
        confidence = min(100, max(0, int(50 + (diff * 10))))
        return {
            'prediction': best,
            'confidence': confidence,
            'completion_likelihood': confidence if best == 'completed' else 100 - confidence
        }

    def get_stats(self, tasks):
        total = len(tasks)
        completed = sum(1 for t in tasks if t['completed'])
        pending = total - completed
        top_completed = sorted(self.word_counts.get('completed', {}).items(), key=lambda x: x[1], reverse=True)[:5]
        top_pending = sorted(self.word_counts.get('pending', {}).items(), key=lambda x: x[1], reverse=True)[:5]
        return {
            'total_tasks': total,
            'completed': completed,
            'pending': pending,
            'completion_rate': round((completed / total * 100), 1) if total > 0 else 0,
            'top_completed_words': [w for w, _ in top_completed],
            'top_pending_words': [w for w, _ in top_pending],
            'trained': total > 0
        }


task_model = NaiveBayes()


def load_and_train():
    try:
        conn = get_db()
        tasks = conn.execute('SELECT title, description, completed FROM tasks').fetchall()
        conn.close()
        task_list = [dict(t) for t in tasks]
        task_model.train(task_list)
        return task_list
    except Exception as e:
        print(f"Training error: {e}")
        return []


# ─────────────────────────────────────────────
# TRIVIA AI - Game Theory Strategy Learner
# ─────────────────────────────────────────────

STRATEGIES = ['A', 'B', 'C', 'D']
# A = correct + no steal
# B = correct + steal
# C = wrong   + no steal  (BAIT)
# D = wrong   + steal

DIFFICULTY_BASES = {'easy': 1, 'medium': 2, 'hard': 3}


def ensure_trivia_tables():
    conn = get_db()
    conn.execute('''
        CREATE TABLE IF NOT EXISTS trivia_ai_model (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            feature TEXT UNIQUE,
            weight REAL DEFAULT 0.5,
            observations INTEGER DEFAULT 0,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )
    ''')
    conn.execute('''
        CREATE TABLE IF NOT EXISTS trivia_user_history (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            question_id INTEGER,
            difficulty TEXT,
            user_correct INTEGER,
            user_steal INTEGER,
            ai_strategy TEXT,
            ai_correct INTEGER,
            ai_steal INTEGER,
            ai_points INTEGER DEFAULT 0,
            user_points INTEGER DEFAULT 0,
            game_id INTEGER,
            game_round INTEGER DEFAULT 1,
            question_number INTEGER DEFAULT 1,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )
    ''')
    conn.commit()
    conn.close()


class TriviaAI:
    def __init__(self):
        ensure_trivia_tables()

    # ── Weight store ──────────────────────────────────────────────────────────

    def get_weight(self, feature, default=0.5):
        conn = get_db()
        row = conn.execute('SELECT weight FROM trivia_ai_model WHERE feature = ?', (feature,)).fetchone()
        conn.close()
        return row['weight'] if row else default

    def update_weight(self, feature, new_value, lr=0.15):
        """Exponential moving average update."""
        conn = get_db()
        existing = conn.execute(
            'SELECT weight, observations FROM trivia_ai_model WHERE feature = ?', (feature,)
        ).fetchone()
        if existing:
            new_weight = existing['weight'] * (1 - lr) + new_value * lr
            conn.execute(
                'UPDATE trivia_ai_model SET weight=?, observations=?, updated_at=CURRENT_TIMESTAMP WHERE feature=?',
                (new_weight, existing['observations'] + 1, feature)
            )
        else:
            conn.execute(
                'INSERT INTO trivia_ai_model (feature, weight, observations) VALUES (?,?,1)',
                (feature, new_value)
            )
        conn.commit()
        conn.close()

    # ── Session context ───────────────────────────────────────────────────────

    def get_game_history(self, game_id):
        """All rounds played so far in this game."""
        conn = get_db()
        rows = conn.execute(
            'SELECT * FROM trivia_user_history WHERE game_id=? ORDER BY game_round, question_number',
            (game_id,)
        ).fetchall()
        conn.close()
        return [dict(r) for r in rows]

    def user_steal_rate_in_game(self, game_id):
        """How often has the user stolen in this game so far?"""
        history = self.get_game_history(game_id)
        if not history:
            return None
        return sum(1 for h in history if h['user_steal']) / len(history)

    def user_accuracy_in_game(self, game_id):
        """User's answer accuracy so far in this game."""
        history = self.get_game_history(game_id)
        if not history:
            return None
        return sum(1 for h in history if h['user_correct']) / len(history)

    def score_differential(self, game_id):
        """
        ai_points - user_points across this game so far.
        Positive = AI winning, negative = AI losing.
        """
        history = self.get_game_history(game_id)
        if not history:
            return 0
        return sum(h['ai_points'] for h in history) - sum(h['user_points'] for h in history)

    # ── Strategy weights ──────────────────────────────────────────────────────

    def get_strategy_weights(self, difficulty, game_round):
        """
        Get the current learned strategy weights for this difficulty + round.
        Returns dict {A: float, B: float, C: float, D: float}
        All weights are profitability scores (higher = more likely to pick).
        """
        weights = {}
        for s in STRATEGIES:
            # Base weight — start with safe defaults
            default = {'A': 0.5, 'B': 0.3, 'C': 0.15, 'D': 0.05}[s]
            weights[s] = self.get_weight(f'strategy_{s}_{difficulty}', default)

        # Round modifier — in later rounds, bait (C) becomes more valuable
        # because the stakes are higher so punishing a wrong steal hurts more
        round_bait_boost = (game_round - 1) * 0.05
        weights['C'] = min(0.8, weights['C'] + round_bait_boost)

        return weights

    def sample_strategy(self, weights, user_steal_rate, score_diff, game_round):
        """
        Choose a strategy probabilistically based on weights,
        adjusted for current game context.
        """
        w = dict(weights)

        # If user steals a lot → boost C (bait them)
        if user_steal_rate is not None:
            if user_steal_rate > 0.6:
                w['C'] = min(0.9, w['C'] * 1.5)
                w['A'] = w['A'] * 0.7
            elif user_steal_rate < 0.2:
                # User rarely steals → bait is wasted, boost B (steal from them)
                w['B'] = min(0.8, w['B'] * 1.3)
                w['C'] = w['C'] * 0.6

        # If AI is losing badly → take more risks (boost B, accept C)
        if score_diff < -3:
            w['B'] = min(0.9, w['B'] * 1.4)
            w['A'] = w['A'] * 0.7

        # If AI is winning comfortably → play safer
        if score_diff > 3:
            w['A'] = min(0.9, w['A'] * 1.3)
            w['B'] = w['B'] * 0.7

        # Normalise to sum to 1
        total = sum(w.values())
        probs = {s: w[s] / total for s in STRATEGIES}

        # Weighted random choice
        r = random.random()
        cumulative = 0
        for s in STRATEGIES:
            cumulative += probs[s]
            if r <= cumulative:
                return s
        return 'A'

    # ── Answer selection ──────────────────────────────────────────────────────

    def get_correct_answer(self, question_id):
        conn = get_db()
        q = conn.execute('SELECT correct_answer FROM trivia_questions WHERE id=?', (question_id,)).fetchone()
        conn.close()
        return q['correct_answer'] if q else random.choice(['a','b','c','d'])

    def get_wrong_answer(self, question_id):
        correct = self.get_correct_answer(question_id)
        wrong = [x for x in ['a','b','c','d'] if x != correct]
        return random.choice(wrong)

    # ── Main decision ─────────────────────────────────────────────────────────

    def decide(self, question_id, difficulty, game_id, game_round, question_number, base_score):
        """
        Pick a strategy and return ai_answer + ai_steal.
        This is the core decision method called before each round.
        """
        # Get context
        user_steal_rate = self.user_steal_rate_in_game(game_id)
        score_diff = self.score_differential(game_id)

        # Get strategy weights
        weights = self.get_strategy_weights(difficulty, game_round)

        # Sample strategy
        strategy = self.sample_strategy(weights, user_steal_rate, score_diff, game_round)

        # Translate strategy to answer + steal
        if strategy == 'A':
            ai_answer = self.get_correct_answer(question_id)
            ai_steal = False
        elif strategy == 'B':
            ai_answer = self.get_correct_answer(question_id)
            ai_steal = True
        elif strategy == 'C':
            ai_answer = self.get_wrong_answer(question_id)
            ai_steal = False
        else:  # D
            ai_answer = self.get_wrong_answer(question_id)
            ai_steal = True

        return {
            'ai_answer': ai_answer,
            'ai_steal': ai_steal,
            'strategy': strategy,
        }

    # ── Learning ──────────────────────────────────────────────────────────────

    def learn(self, question_id, difficulty, user_correct, user_steal,
              ai_strategy, ai_correct, ai_steal, ai_points, user_points,
              game_id, game_round, question_number):
        """
        Called after every round. Updates:
        1. Strategy profitability weights — was the chosen strategy profitable?
        2. User behaviour patterns — accuracy, steal rate
        3. Question knowledge
        """
        # Save full round to history
        conn = get_db()
        conn.execute('''
            INSERT INTO trivia_user_history
            (question_id, difficulty, user_correct, user_steal,
             ai_strategy, ai_correct, ai_steal, ai_points, user_points,
             game_id, game_round, question_number)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
        ''', (
            question_id, difficulty, int(user_correct), int(user_steal),
            ai_strategy, int(ai_correct), int(ai_steal), ai_points, user_points,
            game_id, game_round, question_number
        ))
        conn.commit()
        conn.close()

        # ── Update strategy profitability ──────────────────────────────────
        # Normalise ai_points to a 0-1 signal for the weight update.
        # We use tanh-like normalisation so big wins/losses have diminishing effect.
        # Max possible points in hard R3 = 6 (steal correct), min = -5 (steal wrong)
        max_pts = 6.0
        normalised = (ai_points / max_pts + 1) / 2  # maps [-6,6] → [0,1]
        normalised = max(0.0, min(1.0, normalised))

        # Update strategy weight with higher learning rate for big outcomes
        lr = 0.2 if abs(ai_points) >= 3 else 0.12
        self.update_weight(f'strategy_{ai_strategy}_{difficulty}', normalised, lr=lr)

        # ── Update user behaviour patterns ────────────────────────────────
        self.update_weight(f'user_accuracy_{difficulty}', 1.0 if user_correct else 0.0)
        self.update_weight(f'user_steal_rate_{difficulty}', 1.0 if user_steal else 0.0)
        self.update_weight(f'question_{question_id}_user_correct', 1.0 if user_correct else 0.0)

        # ── Track if user's steal was profitable ─────────────────────────
        # If user stole: was it correct (ai_correct=True) or wrong?
        if user_steal:
            self.update_weight(
                f'user_steal_accuracy_{difficulty}',
                1.0 if ai_correct else 0.0
            )

    def get_stats(self):
        conn = get_db()
        history = conn.execute('SELECT * FROM trivia_user_history').fetchall()
        weights = conn.execute('SELECT * FROM trivia_ai_model ORDER BY observations DESC LIMIT 20').fetchall()
        conn.close()
        total = len(history)
        correct = sum(1 for h in history if h['user_correct'])
        steals = sum(1 for h in history if h['user_steal'])
        strategy_counts = {}
        for s in STRATEGIES:
            strategy_counts[s] = sum(1 for h in history if h['ai_strategy'] == s)
        return {
            'total_rounds_played': total,
            'user_correct_total': correct,
            'user_accuracy_overall': round(correct / total * 100, 1) if total > 0 else 0,
            'user_steal_total': steals,
            'learned_weights': len(weights),
            'ai_strategy_counts': strategy_counts,
            'top_weights': [{'feature': w['feature'], 'weight': round(w['weight'], 3), 'obs': w['observations']} for w in weights],
        }


trivia_ai = TriviaAI()


# ─────────────────────────────────────────────
# HTTP HANDLER
# ─────────────────────────────────────────────

class AIHandler(BaseHTTPRequestHandler):

    def log_message(self, format, *args):
        pass

    def send_json(self, data, status=200):
        body = json.dumps(data).encode()
        self.send_response(status)
        self.send_header('Content-Type', 'application/json')
        self.send_header('Content-Length', len(body))
        self.send_header('Access-Control-Allow-Origin', '*')
        self.end_headers()
        self.wfile.write(body)

    def do_GET(self):
        parsed = urlparse(self.path)
        path = parsed.path
        params = parse_qs(parsed.query)

        # ── Task AI ──────────────────────────────────────────────────────────
        if path == '/health':
            self.send_json({'status': 'ok', 'port': PORT})

        elif path == '/train':
            tasks = load_and_train()
            self.send_json({'status': 'trained', 'tasks_used': len(tasks)})

        elif path == '/stats':
            tasks = load_and_train()
            self.send_json(task_model.get_stats(tasks))

        elif path == '/predict':
            text = params.get('text', [''])[0]
            if not text:
                self.send_json({'error': 'text parameter required'}, 400)
                return
            tasks = load_and_train()
            if not tasks:
                self.send_json({'error': 'No tasks to train on yet'}, 400)
                return
            result = task_model.predict(text)
            result['text'] = text
            self.send_json(result)

        elif path == '/suggestions':
            tasks = load_and_train()
            conn = get_db()
            pending = conn.execute(
                'SELECT id, title, description FROM tasks WHERE completed=0 ORDER BY created_at ASC'
            ).fetchall()
            conn.close()
            suggestions = []
            for task in pending:
                text = (task['title'] or '') + ' ' + (task['description'] or '')
                pred = task_model.predict(text)
                suggestions.append({
                    'id': task['id'],
                    'title': task['title'],
                    'completion_likelihood': pred['completion_likelihood'],
                    'prediction': pred['prediction']
                })
            suggestions.sort(key=lambda x: x['completion_likelihood'], reverse=True)
            self.send_json({'suggestions': suggestions})

        # ── Trivia AI ─────────────────────────────────────────────────────────
        elif path == '/trivia/decide':
            question_id = int(params.get('question_id', [0])[0])
            difficulty = params.get('difficulty', ['medium'])[0]
            game_id = int(params.get('game_id', [0])[0])
            game_round = int(params.get('game_round', [1])[0])
            question_number = int(params.get('question_number', [1])[0])
            base_score = int(params.get('base_score', [1])[0])

            result = trivia_ai.decide(
                question_id, difficulty, game_id,
                game_round, question_number, base_score
            )
            self.send_json(result)

        elif path == '/trivia/learn':
            question_id = int(params.get('question_id', [0])[0])
            difficulty = params.get('difficulty', ['medium'])[0]
            user_correct = params.get('user_correct', ['0'])[0] == '1'
            user_steal = params.get('user_steal', ['0'])[0] == '1'
            ai_strategy = params.get('ai_strategy', ['A'])[0]
            ai_correct = params.get('ai_correct', ['0'])[0] == '1'
            ai_steal = params.get('ai_steal', ['0'])[0] == '1'
            ai_points = int(params.get('ai_points', [0])[0])
            user_points = int(params.get('user_points', [0])[0])
            game_id = int(params.get('game_id', [0])[0])
            game_round = int(params.get('game_round', [1])[0])
            question_number = int(params.get('question_number', [1])[0])

            trivia_ai.learn(
                question_id, difficulty, user_correct, user_steal,
                ai_strategy, ai_correct, ai_steal, ai_points, user_points,
                game_id, game_round, question_number
            )
            self.send_json({'status': 'learned'})

        elif path == '/trivia/stats':
            self.send_json(trivia_ai.get_stats())

        else:
            self.send_json({'error': 'Not found'}, 404)

    def do_OPTIONS(self):
        self.send_response(200)
        self.send_header('Access-Control-Allow-Origin', '*')
        self.end_headers()


if __name__ == '__main__':
    print(f"TaskFlow AI server starting on port {PORT}...")
    load_and_train()
    print("Task model trained. Trivia AI ready.")
    server = HTTPServer(('127.0.0.1', PORT), AIHandler)
    server.serve_forever()
#!/usr/bin/env python3
"""
TaskFlow AI Server
- Task completion predictor (Naive Bayes)
- Trivia game AI (neural-inspired with online learning)
Runs on 127.0.0.1:5001
"""

import json
import math
import sqlite3
import os
import re
import random
from http.server import HTTPServer, BaseHTTPRequestHandler
from urllib.parse import urlparse, parse_qs

DB_PATH = os.path.expanduser('~/sites/PWAwithJump-NativePHP/database/database.sqlite')
PORT = 5001


def get_db():
    conn = sqlite3.connect(DB_PATH)
    conn.row_factory = sqlite3.Row
    return conn


# ─────────────────────────────────────────────
# TASK AI - Naive Bayes
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
# TRIVIA AI - Neural-inspired online learning
# ─────────────────────────────────────────────

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
            game_id INTEGER,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )
    ''')
    conn.commit()
    conn.close()


class TriviaAI:
    def __init__(self):
        ensure_trivia_tables()

    def get_weight(self, feature, default=0.5):
        conn = get_db()
        row = conn.execute('SELECT weight FROM trivia_ai_model WHERE feature = ?', (feature,)).fetchone()
        conn.close()
        return row['weight'] if row else default

    def update_weight(self, feature, new_observation, learning_rate=0.1):
        conn = get_db()
        existing = conn.execute('SELECT weight, observations FROM trivia_ai_model WHERE feature = ?', (feature,)).fetchone()
        if existing:
            old_weight = existing['weight']
            obs = existing['observations']
            new_weight = old_weight * (1 - learning_rate) + new_observation * learning_rate
            conn.execute(
                'UPDATE trivia_ai_model SET weight = ?, observations = ?, updated_at = CURRENT_TIMESTAMP WHERE feature = ?',
                (new_weight, obs + 1, feature)
            )
        else:
            conn.execute(
                'INSERT INTO trivia_ai_model (feature, weight, observations) VALUES (?, ?, 1)',
                (feature, new_observation)
            )
        conn.commit()
        conn.close()

    def predict_user_correct(self, difficulty, question_id):
        defaults = {'easy': 0.7, 'medium': 0.5, 'hard': 0.3, 'custom': 0.5}
        base = self.get_weight(f'user_accuracy_{difficulty}', defaults.get(difficulty, 0.5))
        q_weight = self.get_weight(f'question_{question_id}_user_correct', base)
        return base * 0.7 + q_weight * 0.3

    def decide_ai_answer(self, question_id, difficulty):
        defaults = {'easy': 0.8, 'medium': 0.6, 'hard': 0.4, 'custom': 0.55}
        ai_accuracy = self.get_weight(f'ai_accuracy_{difficulty}', defaults.get(difficulty, 0.55))
        known = self.get_weight(f'question_{question_id}_correct_answer_known', 0.0)
        conn = get_db()
        q = conn.execute('SELECT correct_answer FROM trivia_questions WHERE id = ?', (question_id,)).fetchone()
        conn.close()
        if not q:
            return random.choice(['a', 'b', 'c', 'd'])
        correct = q['correct_answer']
        if known > 0.9 or random.random() < ai_accuracy:
            return correct
        wrong = [x for x in ['a', 'b', 'c', 'd'] if x != correct]
        return random.choice(wrong)

    def decide_steal(self, difficulty, question_id, predicted_user_correct):
        steal_threshold = self.get_weight(f'ai_steal_threshold_{difficulty}', 0.65)
        steal_profitability = self.get_weight('ai_steal_profitability', 0.5)
        adjusted = steal_threshold * (1.2 - steal_profitability * 0.4)
        adjusted = max(0.4, min(0.85, adjusted))
        return predicted_user_correct > adjusted

    def learn(self, question_id, difficulty, user_correct, user_steal, game_id):
        conn = get_db()
        conn.execute('''
            INSERT INTO trivia_user_history (question_id, difficulty, user_correct, user_steal, game_id)
            VALUES (?, ?, ?, ?, ?)
        ''', (question_id, difficulty, int(user_correct), int(user_steal), game_id))
        conn.commit()
        conn.close()
        self.update_weight(f'user_accuracy_{difficulty}', 1.0 if user_correct else 0.0)
        self.update_weight(f'question_{question_id}_user_correct', 1.0 if user_correct else 0.0)
        self.update_weight(f'user_steal_rate_{difficulty}', 1.0 if user_steal else 0.0)
        self.update_weight(f'question_{question_id}_correct_answer_known', 1.0, learning_rate=0.3)

    def get_stats(self):
        conn = get_db()
        history = conn.execute('SELECT * FROM trivia_user_history').fetchall()
        weights = conn.execute('SELECT * FROM trivia_ai_model').fetchall()
        conn.close()
        total = len(history)
        correct = sum(1 for h in history if h['user_correct'])
        steals = sum(1 for h in history if h['user_steal'])
        return {
            'total_rounds_played': total,
            'user_correct_total': correct,
            'user_accuracy_overall': round(correct / total * 100, 1) if total > 0 else 0,
            'user_steal_total': steals,
            'learned_weights': len(weights),
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
                'SELECT id, title, description FROM tasks WHERE completed = 0 ORDER BY created_at ASC'
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

        elif path == '/trivia/decide':
            question_id = int(params.get('question_id', [0])[0])
            difficulty = params.get('difficulty', ['medium'])[0]
            predicted = trivia_ai.predict_user_correct(difficulty, question_id)
            ai_answer = trivia_ai.decide_ai_answer(question_id, difficulty)
            ai_steal = trivia_ai.decide_steal(difficulty, question_id, predicted)
            self.send_json({
                'ai_answer': ai_answer,
                'ai_steal': ai_steal,
                'predicted_user_correct': round(predicted, 2),
            })

        elif path == '/trivia/learn':
            question_id = int(params.get('question_id', [0])[0])
            difficulty = params.get('difficulty', ['medium'])[0]
            user_correct = params.get('user_correct', ['0'])[0] == '1'
            user_steal = params.get('user_steal', ['0'])[0] == '1'
            game_id = int(params.get('game_id', [0])[0])
            trivia_ai.learn(question_id, difficulty, user_correct, user_steal, game_id)
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

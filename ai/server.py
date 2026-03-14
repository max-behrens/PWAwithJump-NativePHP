#!/usr/bin/env python3
"""
TaskFlow AI Server
Naive Bayes classifier trained on your tasks.
Runs on 127.0.0.1:5001
"""

import json
import math
import sqlite3
import os
import re
from http.server import HTTPServer, BaseHTTPRequestHandler
from urllib.parse import urlparse, parse_qs

DB_PATH = os.path.expanduser('~/sites/PWAwithJump-NativePHP/database/database.sqlite')
PORT = 5001


def get_db():
    conn = sqlite3.connect(DB_PATH)
    conn.row_factory = sqlite3.Row
    return conn


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
        # Convert to probability-like confidence 0-100
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

        # Top words for completed tasks
        top_completed = sorted(
            self.word_counts.get('completed', {}).items(),
            key=lambda x: x[1], reverse=True
        )[:5]

        top_pending = sorted(
            self.word_counts.get('pending', {}).items(),
            key=lambda x: x[1], reverse=True
        )[:5]

        return {
            'total_tasks': total,
            'completed': completed,
            'pending': pending,
            'completion_rate': round((completed / total * 100), 1) if total > 0 else 0,
            'top_completed_words': [w for w, _ in top_completed],
            'top_pending_words': [w for w, _ in top_pending],
            'trained': total > 0
        }


# Global model instance
model = NaiveBayes()


def load_and_train():
    try:
        conn = get_db()
        tasks = conn.execute('SELECT title, description, completed FROM tasks').fetchall()
        conn.close()
        task_list = [dict(t) for t in tasks]
        model.train(task_list)
        return task_list
    except Exception as e:
        print(f"Training error: {e}")
        return []


class AIHandler(BaseHTTPRequestHandler):

    def log_message(self, format, *args):
        pass  # Suppress default logging

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
            self.send_json({
                'status': 'trained',
                'tasks_used': len(tasks)
            })

        elif path == '/stats':
            tasks = load_and_train()
            stats = model.get_stats(tasks)
            self.send_json(stats)

        elif path == '/predict':
            text = params.get('text', [''])[0]
            if not text:
                self.send_json({'error': 'text parameter required'}, 400)
                return
            tasks = load_and_train()
            if not tasks:
                self.send_json({'error': 'No tasks to train on yet'}, 400)
                return
            result = model.predict(text)
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
                pred = model.predict(text)
                suggestions.append({
                    'id': task['id'],
                    'title': task['title'],
                    'completion_likelihood': pred['completion_likelihood'],
                    'prediction': pred['prediction']
                })

            # Sort by completion likelihood descending
            suggestions.sort(key=lambda x: x['completion_likelihood'], reverse=True)
            self.send_json({'suggestions': suggestions})

        else:
            self.send_json({'error': 'Not found'}, 404)

    def do_OPTIONS(self):
        self.send_response(200)
        self.send_header('Access-Control-Allow-Origin', '*')
        self.end_headers()


if __name__ == '__main__':
    print(f"TaskFlow AI server starting on port {PORT}...")
    load_and_train()
    print("Model trained. Ready.")
    server = HTTPServer(('127.0.0.1', PORT), AIHandler)
    server.serve_forever()
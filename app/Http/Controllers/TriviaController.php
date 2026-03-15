<?php

namespace App\Http\Controllers;

use App\Models\TriviaGame;
use App\Models\TriviaQuestion;
use App\Models\TriviaRound;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TriviaController extends Controller
{
    private string $aiBase = 'http://127.0.0.1:5001';

    private function aiRequest(string $path, array $params = []): array
    {
        try {
            $url = $this->aiBase . $path;
            if ($params) {
                $url .= '?' . http_build_query($params);
            }
            $response = Http::timeout(5)->get($url);
            return $response->json() ?? [];
        } catch (\Exception $e) {
            return ['error' => 'AI server offline'];
        }
    }

    // Show lobby - pick difficulty or create custom game
    public function index()
    {
        $recentGames = TriviaGame::where('status', 'completed')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $customQuestions = TriviaQuestion::where('is_custom', true)
            ->orderByDesc('created_at')
            ->get();

        return view('trivia.index', compact('recentGames', 'customQuestions'));
    }

    // Start a new game
    public function start(Request $request)
    {
        $difficulty = $request->input('difficulty', 'easy');

        if ($difficulty === 'custom') {
            $request->validate([
                'question_ids' => 'required|array|size:5',
                'question_ids.*' => 'exists:trivia_questions,id',
            ]);
            $questionIds = $request->input('question_ids');
        } else {
            $questions = TriviaQuestion::where('difficulty', $difficulty)
                ->inRandomOrder()
                ->limit(5)
                ->pluck('id')
                ->toArray();

            if (count($questions) < 5) {
                return back()->with('error', 'Not enough questions for this difficulty.');
            }
            $questionIds = $questions;
        }

        $game = TriviaGame::create([
            'difficulty' => $difficulty,
            'user_score' => 0,
            'ai_score' => 0,
            'status' => 'in_progress',
            'current_question' => 0,
            'question_ids' => $questionIds,
        ]);

        return redirect()->route('trivia.play', $game->id);
    }

    // Show current question
    public function play(TriviaGame $trivia)
    {
        if ($trivia->isComplete()) {
            return redirect()->route('trivia.result', $trivia->id);
        }

        $question = $trivia->currentQuestion();
        if (!$question) {
            return redirect()->route('trivia.result', $trivia->id);
        }

        $roundNumber = $trivia->current_question + 1;

        return view('trivia.play', compact('trivia', 'question', 'roundNumber'));
    }

    // Submit answer + steal decision
    public function answer(Request $request, TriviaGame $trivia)
    {
        if ($trivia->isComplete()) {
            return redirect()->route('trivia.result', $trivia->id);
        }

        $request->validate([
            'user_answer' => 'required|in:a,b,c,d',
            'user_steal' => 'required|in:0,1',
        ]);

        $question = $trivia->currentQuestion();
        $roundNumber = $trivia->current_question + 1;
        $scoreValue = $question->score_value;

        // Get AI decision from Python server
        $aiDecision = $this->aiRequest('/trivia/decide', [
            'question_id' => $question->id,
            'difficulty' => $question->difficulty,
            'game_id' => $trivia->id,
        ]);

        // Fallback if AI server offline
        $aiAnswer = $aiDecision['ai_answer'] ?? $this->fallbackAiAnswer($question);
        $aiSteal = $aiDecision['ai_steal'] ?? (rand(0, 1) === 1);

        // Score the round
        $userAnswer = $request->input('user_answer');
        $userSteal = (bool) $request->input('user_steal');
        $userCorrect = $userAnswer === $question->correct_answer;
        $aiCorrect = $aiAnswer === $question->correct_answer;

        $userPoints = $this->calculatePoints($userCorrect, $userSteal, $aiCorrect, $scoreValue);
        $aiPoints = $this->calculatePoints($aiCorrect, $aiSteal, $userCorrect, $scoreValue);

        // Save round
        TriviaRound::create([
            'game_id' => $trivia->id,
            'question_id' => $question->id,
            'round_number' => $roundNumber,
            'user_answer' => $userAnswer,
            'ai_answer' => $aiAnswer,
            'user_steal' => $userSteal,
            'ai_steal' => $aiSteal,
            'user_correct' => $userCorrect,
            'ai_correct' => $aiCorrect,
            'user_points_earned' => $userPoints,
            'ai_points_earned' => $aiPoints,
        ]);

        // Update game scores
        $trivia->user_score += $userPoints;
        $trivia->ai_score += $aiPoints;
        $trivia->current_question += 1;

        // Check if game over
        if ($trivia->current_question >= 5) {
            $trivia->status = 'completed';
        }

        $trivia->save();

        // Tell AI server to learn from this round
        $this->aiRequest('/trivia/learn', [
            'question_id' => $question->id,
            'user_answer' => $userAnswer,
            'user_correct' => $userCorrect ? 1 : 0,
            'user_steal' => $userSteal ? 1 : 0,
            'difficulty' => $question->difficulty,
            'game_id' => $trivia->id,
        ]);

        // Show round result
        return view('trivia.round_result', compact(
            'trivia', 'question', 'roundNumber',
            'userAnswer', 'aiAnswer', 'userSteal', 'aiSteal',
            'userCorrect', 'aiCorrect', 'userPoints', 'aiPoints', 'scoreValue'
        ));
    }

    // Final result screen
    public function result(TriviaGame $trivia)
    {
        $rounds = $trivia->rounds()->with('question')->get();
        return view('trivia.result', compact('trivia', 'rounds'));
    }

    // Create custom question
    public function createQuestion()
    {
        return view('trivia.create_question');
    }

    public function storeQuestion(Request $request)
    {
        $request->validate([
            'question' => 'required|string|max:500',
            'answer_a' => 'required|string|max:200',
            'answer_b' => 'required|string|max:200',
            'answer_c' => 'required|string|max:200',
            'answer_d' => 'required|string|max:200',
            'correct_answer' => 'required|in:a,b,c,d',
            'score_value' => 'required|integer|min:1|max:10',
        ]);

        TriviaQuestion::create([
            'question' => $request->question,
            'answer_a' => $request->answer_a,
            'answer_b' => $request->answer_b,
            'answer_c' => $request->answer_c,
            'answer_d' => $request->answer_d,
            'correct_answer' => $request->correct_answer,
            'difficulty' => 'custom',
            'score_value' => $request->score_value,
            'is_custom' => true,
        ]);

        return redirect()->route('trivia.index')->with('success', 'Question added!');
    }

    // Scoring logic
    private function calculatePoints(bool $myCorrect, bool $mySteal, bool $opponentCorrect, int $scoreValue): int
    {
        if ($mySteal) {
            // Stealing: bet opponent got it right
            if ($opponentCorrect) {
                return $scoreValue * 2; // correct steal = double points
            } else {
                return -$scoreValue; // wrong steal = lose points
            }
        } else {
            // Not stealing: just score own answer
            return $myCorrect ? $scoreValue : 0;
        }
    }

    private function fallbackAiAnswer(TriviaQuestion $question): string
    {
        // Random answer as fallback when AI server offline
        return ['a', 'b', 'c', 'd'][rand(0, 3)];
    }
}

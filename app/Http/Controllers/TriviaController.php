<?php

namespace App\Http\Controllers;

use App\Models\TriviaGame;
use App\Models\TriviaQuestion;
use App\Models\TriviaRound;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TriviaController extends Controller
{
    private string $aiBase = 'http://127.0.0.1:5001';

    private function aiRequest(string $path, array $params = []): array
    {
        $url = $this->aiBase . $path;
        if ($params) {
            $url .= '?' . http_build_query($params);
        }
        try {
            $context = stream_context_create(['http' => ['timeout' => 5, 'ignore_errors' => true]]);
            $result = @file_get_contents($url, false, $context);
            if ($result === false) return ['error' => 'AI server offline'];
            return json_decode($result, true) ?? ['error' => 'Empty response'];
        } catch (\Exception $e) {
            Log::error('TriviaController::aiRequest', ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }

    public function index()
    {
        $recentGames = TriviaGame::where('status', 'completed')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('trivia.index', compact('recentGames'));
    }

    public function start(Request $request)
    {
        $difficulty = $request->input('difficulty', 'easy');

        // Need exactly 9 questions (3 per round × 3 rounds)
        $allIds = TriviaQuestion::where('difficulty', $difficulty)
            ->inRandomOrder()
            ->limit(9)
            ->pluck('id')
            ->toArray();

        if (count($allIds) < 9) {
            return back()->with('error', 'Not enough questions. Need 9, found ' . count($allIds));
        }

        $game = TriviaGame::create([
            'difficulty' => $difficulty,
            'user_score' => 0,
            'ai_score' => 0,
            'status' => 'in_progress',
            'current_round' => 1,
            'current_question' => 0,
            'round_1_question_ids' => array_slice($allIds, 0, 3),
            'round_2_question_ids' => array_slice($allIds, 3, 3),
            'round_3_question_ids' => array_slice($allIds, 6, 3),
        ]);

        return redirect()->route('trivia.play', $game->id);
    }

    public function play(TriviaGame $trivia)
    {
        if ($trivia->isComplete()) {
            return redirect()->route('trivia.result', $trivia->id);
        }

        $question = $trivia->currentQuestion();
        if (!$question) {
            return redirect()->route('trivia.result', $trivia->id);
        }

        $questionNumber = $trivia->current_question + 1; // 1-3
        $currentRound = $trivia->current_round;           // 1-3
        $baseScore = $trivia->baseScore();

        return view('trivia.play', compact('trivia', 'question', 'questionNumber', 'currentRound', 'baseScore'));
    }

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
        $currentRound = $trivia->current_round;
        $questionNumber = $trivia->current_question + 1;
        $baseScore = $trivia->baseScore();

        // Ask AI for its decision
        $aiDecision = $this->aiRequest('/trivia/decide', [
            'question_id' => $question->id,
            'difficulty' => $trivia->difficulty,
            'game_id' => $trivia->id,
            'game_round' => $currentRound,
            'question_number' => $questionNumber,
            'base_score' => $baseScore,
        ]);

        $aiAnswer = $aiDecision['ai_answer'] ?? $this->fallbackAnswer($question);
        $aiSteal = isset($aiDecision['ai_steal']) ? (bool) $aiDecision['ai_steal'] : (rand(0, 1) === 1);
        $aiStrategy = $aiDecision['strategy'] ?? 'A';

        $userAnswer = $request->input('user_answer');
        $userSteal = (bool) $request->input('user_steal');
        $userCorrect = $userAnswer === $question->correct_answer;
        $aiCorrect = $aiAnswer === $question->correct_answer;

        $userPoints = $this->calculatePoints($userCorrect, $userSteal, $aiCorrect, $baseScore);
        $aiPoints = $this->calculatePoints($aiCorrect, $aiSteal, $userCorrect, $baseScore);

        // Save round
        TriviaRound::create([
            'game_id' => $trivia->id,
            'question_id' => $question->id,
            'game_round' => $currentRound,
            'question_number' => $questionNumber,
            'user_answer' => $userAnswer,
            'ai_answer' => $aiAnswer,
            'user_steal' => $userSteal,
            'ai_steal' => $aiSteal,
            'user_correct' => $userCorrect,
            'ai_correct' => $aiCorrect,
            'user_points_earned' => $userPoints,
            'ai_points_earned' => $aiPoints,
            'base_score' => $baseScore,
            'ai_strategy' => $aiStrategy,
        ]);

        // Update scores
        $trivia->user_score += $userPoints;
        $trivia->ai_score += $aiPoints;
        $trivia->current_question += 1;

        // Check if round complete (3 questions done)
        $justFinishedRound = false;
        if ($trivia->current_question >= 3) {
            if ($trivia->current_round >= 3) {
                $trivia->status = 'completed';
            } else {
                $trivia->current_round += 1;
                $trivia->current_question = 0;
                $justFinishedRound = true;
            }
        }

        $trivia->save();

        // Tell AI to learn
        $this->aiRequest('/trivia/learn', [
            'question_id' => $question->id,
            'difficulty' => $trivia->difficulty,
            'user_correct' => $userCorrect ? 1 : 0,
            'user_steal' => $userSteal ? 1 : 0,
            'ai_strategy' => $aiStrategy,
            'ai_correct' => $aiCorrect ? 1 : 0,
            'ai_steal' => $aiSteal ? 1 : 0,
            'ai_points' => $aiPoints,
            'user_points' => $userPoints,
            'game_id' => $trivia->id,
            'game_round' => $currentRound,
            'question_number' => $questionNumber,
        ]);

        return view('trivia.round_result', compact(
            'trivia', 'question', 'questionNumber', 'currentRound',
            'userAnswer', 'aiAnswer', 'userSteal', 'aiSteal',
            'userCorrect', 'aiCorrect', 'userPoints', 'aiPoints',
            'baseScore', 'aiStrategy', 'justFinishedRound'
        ));
    }

    public function result(TriviaGame $trivia)
    {
        $rounds = $trivia->rounds()->with('question')->get();
        $roundGroups = $rounds->groupBy('game_round');
        return view('trivia.result', compact('trivia', 'rounds', 'roundGroups'));
    }

    /**
     * Scoring:
     * - No steal: correct = +base, wrong = 0
     * - Steal correct (opponent answered right) = +(base + 1)
     * - Steal wrong (opponent answered wrong)   = -(base)
     */
    private function calculatePoints(bool $myCorrect, bool $mySteal, bool $opponentCorrect, int $base): int
    {
        if ($mySteal) {
            return $opponentCorrect ? $base + 1 : -$base;
        }
        return $myCorrect ? $base : 0;
    }

    private function fallbackAnswer(TriviaQuestion $question): string
    {
        return ['a', 'b', 'c', 'd'][rand(0, 3)];
    }

    public function statsJson(): \Illuminate\Http\JsonResponse
    {
        $total = TriviaGame::where('status', 'completed')->count();

        $recentGames = TriviaGame::where('status', 'completed')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn($g) => [
                'id'         => $g->id,
                'difficulty' => $g->difficulty,
                'user_score' => $g->user_score,
                'ai_score'   => $g->ai_score,
                'winner'     => $g->winner(),
            ]);

        // User history stats
        $userHistory = [];
        try {
            $history = \DB::table('trivia_user_history')->get();
            $t       = $history->count();
            if ($t > 0) {
                $steals        = $history->where('user_steal', 1)->count();
                $correct       = $history->where('user_correct', 1)->count();
                $correctSteals = $history->where('user_steal', 1)->where('ai_correct', 1)->count();
                $userHistory   = [
                    'total_rounds'   => $t,
                    'accuracy'       => round($correct / $t, 3),
                    'steal_rate'     => round($steals / $t, 3),
                    'steal_accuracy' => $steals > 0 ? round($correctSteals / $steals, 3) : 0,
                ];
            }
        } catch (\Exception $e) {}

        // Read actual learned strategy weights from trivia_ai_model
        // and reverse-engineer lr + risk slider values that best match them.
        $inferredSliders = $this->inferSlidersFromWeights();

        return response()->json([
            'stats'            => ['total_games' => $total],
            'recent_games'     => $recentGames,
            'user_history'     => $userHistory,
            'inferred_sliders' => $inferredSliders,
        ]);
    }

    /**
     * Read strategy_A/B/C/D weights from trivia_ai_model and find the
     * lr + risk values (both 1-100) whose expectedPayoff distribution
     * at p=0.5, b=1 best matches the observed weight ratios.
     *
     * Strategy weights in trivia_ai_model are profitability scores 0-1.
     * We compare ratios B/A and D/B to infer the two slider values:
     *
     * - lr  controls how much wrong-steal penalty matters → drives A vs B separation
     * - risk controls steal bonus inflation → drives B vs D separation
     */
    private function inferSlidersFromWeights(): array
    {
        $defaults = ['lr' => 10, 'risk' => 50, 'observations' => 0];

        try {
            $rows = \DB::table('trivia_ai_model')
                ->whereIn('feature', [
                    'strategy_A_easy', 'strategy_B_easy',
                    'strategy_C_easy', 'strategy_D_easy',
                ])
                ->pluck('weight', 'feature');

            if ($rows->count() < 4) return $defaults;

            $wA = $rows['strategy_A_easy'] ?? 0.5;
            $wB = $rows['strategy_B_easy'] ?? 0.3;
            $wC = $rows['strategy_C_easy'] ?? 0.15;
            $wD = $rows['strategy_D_easy'] ?? 0.05;

            $obs = \DB::table('trivia_ai_model')
                ->where('feature', 'strategy_B_easy')
                ->value('observations') ?? 0;

            // B/A ratio tells us about risk tolerance:
            // high B/A → AI has found stealing profitable → high risk tolerance
            $baRatio   = $wA > 0 ? $wB / $wA : 1.0;
            $inferRisk = (int) round(min(100, max(1, ($baRatio - 0.5) * 80)));

            // D/B ratio tells us about learning rate:
            // low D/B → AI has learned wrong+steal is costly → high learning rate (cautious)
            // high D/B → AI hasn't penalised wrong steals much → low learning rate
            $dbRatio  = $wB > 0 ? $wD / $wB : 0.5;
            $inferLr  = (int) round(min(100, max(1, (1 - $dbRatio) * 80 + 5)));

            return [
                'lr'           => $inferLr,
                'risk'         => $inferRisk,
                'observations' => (int) $obs,
                'raw_weights'  => [
                    'A' => round($wA, 3),
                    'B' => round($wB, 3),
                    'C' => round($wC, 3),
                    'D' => round($wD, 3),
                ],
            ];
        } catch (\Exception $e) {
            return $defaults;
        }
    }
}
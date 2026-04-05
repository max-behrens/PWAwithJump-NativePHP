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

        // ── User history: aggregate across ALL games/difficulties ──────────
        $userHistory = [];
        try {
            $history = \DB::table('trivia_user_history')->get();
            $t = $history->count();
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

        // ── Real strategy performance: actual avg AI points per strategy ───
        // Bucket rounds by whether user stole (p≈1) or didn't (p≈0)
        // giving us real 2x2 cell values and scatter data for the chart
        $realStrategyData = $this->realStrategyData();

        // ── Inferred sliders from obs-weighted avg weights all difficulties ─
        $inferredSliders = $this->inferSlidersFromWeights();

        // ── Raw table data for DB viewer components ────────────────────
        $rawGames    = TriviaGame::orderByDesc('created_at')->get()->toArray();
        $rawRounds   = \DB::table('trivia_rounds')->orderBy('game_id')->orderBy('game_round')->orderBy('question_number')->get()->map(fn($r) => (array)$r)->toArray();
        $rawHistory  = \DB::table('trivia_user_history')->orderBy('id')->get()->map(fn($r) => (array)$r)->toArray();
        $rawAiModel  = \DB::table('trivia_ai_model')->orderBy('id')->get()->map(fn($r) => (array)$r)->toArray();
        
        return response()->json([
            'stats'               => ['total_games' => $total],
            'recent_games'        => $recentGames,
            'user_history'        => $userHistory,
            'inferred_sliders'    => $inferredSliders,
            'real_strategy_data'  => $realStrategyData,
            'raw_games'           => $rawGames,
            'raw_rounds'          => $rawRounds,
            'raw_history'         => $rawHistory,
            'raw_ai_model'        => $rawAiModel,
        ]);
    }

    /**
     * Aggregate actual AI points earned per strategy, bucketed by
     * user_steal (0 or 1) — gives us the real 2x2 matrix values
     * and scatter point data for the chart overlay.
     */
    private function realStrategyData(): array
    {
        try {
            $rounds = \DB::table('trivia_user_history')
                ->select('ai_strategy', 'user_steal', 'ai_points', 'user_steal')
                ->get();

            if ($rounds->isEmpty()) return [];

            $strategies = ['A', 'B', 'C', 'D'];
            $result = [];

            foreach ($strategies as $s) {
                $sRounds = $rounds->where('ai_strategy', $s);
                $total   = $sRounds->count();
                if ($total === 0) continue;

                // When user steals
                $whenSteal    = $sRounds->where('user_steal', 1);
                $whenNoSteal  = $sRounds->where('user_steal', 0);

                // Scatter: group into steal-rate buckets 0.0-1.0 in 0.1 steps
                // Each bucket = avg ai_points for rounds where user_steal≈bucket
                // Since user_steal is binary we use 0=no steal, 1=steal
                $scatterPoints = [];
                if ($whenNoSteal->count() > 0) {
                    $scatterPoints[] = [
                        'x'    => 0.0,
                        'y'    => round($whenNoSteal->avg('ai_points'), 3),
                        'n'    => $whenNoSteal->count(),
                    ];
                }
                if ($whenSteal->count() > 0) {
                    $scatterPoints[] = [
                        'x'    => 1.0,
                        'y'    => round($whenSteal->avg('ai_points'), 3),
                        'n'    => $whenSteal->count(),
                    ];
                }

                $result[$s] = [
                    'total'           => $total,
                    'avg_pts_overall' => round($sRounds->avg('ai_points'), 3),
                    'avg_pts_steal'   => $whenSteal->count() > 0
                        ? round($whenSteal->avg('ai_points'), 3) : null,
                    'avg_pts_no_steal' => $whenNoSteal->count() > 0
                        ? round($whenNoSteal->avg('ai_points'), 3) : null,
                    'steal_count'     => $whenSteal->count(),
                    'no_steal_count'  => $whenNoSteal->count(),
                    'scatter'         => $scatterPoints,
                ];
            }

            return $result;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Aggregate strategy weights across ALL difficulties,
     * weighted by observation count so more-played difficulties
     * contribute more to the inferred slider values.
     */
    private function inferSlidersFromWeights(): array
    {
        $defaults = ['lr' => 10, 'risk' => 50, 'observations' => 0];

        try {
            $difficulties = ['easy', 'medium', 'hard'];
            $strategies   = ['A', 'B', 'C', 'D'];

            // Collect weights and obs counts for all difficulty×strategy combos
            $features = [];
            foreach ($difficulties as $d) {
                foreach ($strategies as $s) {
                    $features[] = "strategy_{$s}_{$d}";
                }
            }

            $rows = \DB::table('trivia_ai_model')
                ->whereIn('feature', $features)
                ->get()
                ->keyBy('feature');

            // Weighted average weight per strategy across difficulties
            $wAvg = [];
            $totalObs = 0;
            foreach ($strategies as $s) {
                $weightedSum = 0;
                $obsSum      = 0;
                foreach ($difficulties as $d) {
                    $key = "strategy_{$s}_{$d}";
                    if (isset($rows[$key])) {
                        $obs          = $rows[$key]->observations;
                        $weightedSum += $rows[$key]->weight * $obs;
                        $obsSum      += $obs;
                    }
                }
                $wAvg[$s] = $obsSum > 0 ? $weightedSum / $obsSum : null;
            }

            $totalObs = \DB::table('trivia_user_history')->count();

            // Need at least A and B to infer
            if ($wAvg['A'] === null || $wAvg['B'] === null) return $defaults;
            if ($totalObs === 0) return $defaults;

            // B/A → risk tolerance
            $baRatio   = $wAvg['A'] > 0 ? $wAvg['B'] / $wAvg['A'] : 1.0;
            $inferRisk = (int) round(min(100, max(1, ($baRatio - 0.5) * 80)));

            // D/B → learning rate (only if D exists)
            $inferLr = 10;
            if ($wAvg['D'] !== null && $wAvg['B'] > 0) {
                $dbRatio = $wAvg['D'] / $wAvg['B'];
                $inferLr = (int) round(min(100, max(1, (1 - $dbRatio) * 80 + 5)));
            }

            return [
                'lr'           => $inferLr,
                'risk'         => $inferRisk,
                'observations' => $totalObs,
                'raw_weights'  => array_map(fn($w) => $w !== null ? round($w, 3) : null, $wAvg),
            ];
        } catch (\Exception $e) {
            return $defaults;
        }
    }

}
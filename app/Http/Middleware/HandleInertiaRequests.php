<?php

namespace App\Http\Middleware;

use App\Models\TriviaGame;
use App\Models\TriviaRound;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [

            'triviaStats' => fn() => $this->triviaStats(),

            'triviaRecentGames' => fn() => TriviaGame::where('status', 'completed')
                ->orderByDesc('created_at')
                ->limit(20)
                ->get()
                ->map(fn($g) => [
                    'id' => $g->id,
                    'difficulty' => $g->difficulty,
                    'user_score' => $g->user_score,
                    'ai_score' => $g->ai_score,
                    'winner' => $g->winner(),
                    'created_at' => $g->created_at->diffForHumans(),
                ]),

            'triviaUserHistory' => fn() => $this->triviaUserHistory(),
        ]);
    }

    private function triviaStats(): array
    {
        $total = TriviaGame::where('status', 'completed')->count();
        if ($total === 0) return ['total_games' => 0];

        $userWins = TriviaGame::where('status', 'completed')
            ->whereColumn('user_score', '>', 'ai_score')
            ->count();

        $aiWins = TriviaGame::where('status', 'completed')
            ->whereColumn('ai_score', '>', 'user_score')
            ->count();

        // Strategy distribution from trivia_user_history if it exists
        $strategyCounts = [];
        try {
            $strategyCounts = \DB::table('trivia_user_history')
                ->selectRaw('ai_strategy, count(*) as cnt')
                ->groupBy('ai_strategy')
                ->pluck('cnt', 'ai_strategy')
                ->toArray();
        } catch (\Exception $e) {}

        return [
            'total_games' => $total,
            'user_wins' => $userWins,
            'ai_wins' => $aiWins,
            'draws' => $total - $userWins - $aiWins,
            'strategy_counts' => $strategyCounts,
        ];
    }

    private function triviaUserHistory(): array
    {
        try {
            $history = \DB::table('trivia_user_history')->get();
            if ($history->isEmpty()) return [];

            $total = $history->count();
            $correct = $history->where('user_correct', 1)->count();
            $steals = $history->where('user_steal', 1)->count();
            $correctSteals = $history->where('user_steal', 1)->where('ai_correct', 1)->count();

            return [
                'total_rounds' => $total,
                'accuracy' => $total > 0 ? round($correct / $total, 3) : 0,
                'steal_rate' => $total > 0 ? round($steals / $total, 3) : 0,
                'steal_accuracy' => $steals > 0 ? round($correctSteals / $steals, 3) : 0,
            ];
        } catch (\Exception $e) {
            return [];
        }
    }
}
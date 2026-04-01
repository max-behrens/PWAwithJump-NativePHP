@extends('layouts.app')

@section('title', 'Trivia · TaskFlow')

@section('content')

@if(session('error'))
    <div class="alert alert-error mb-4">{{ session('error') }}</div>
@endif

<div class="animate-in mb-6">
    <h1 class="text-2xl font-bold">Trivia Bluff</h1>
    <p class="text-base-content/50 text-sm mt-0.5">You vs AI · 3 rounds · 3 questions each</p>
</div>

{{-- How to play --}}
<div class="animate-in card bg-base-100 border border-base-300/50 shadow-sm p-4 mb-5">
    <h2 class="font-semibold text-sm mb-2">How to play</h2>
    <div class="space-y-1 text-sm text-base-content/70">
        <p>🎯 Both you and the AI answer each question secretly</p>
        <p>🤜 Then both decide whether to <strong>steal</strong> the opponent's points</p>
        <p>✅ Correct answer, no steal = <span class="text-success font-semibold">+base pts</span></p>
        <p>🤜 Correct steal = <span class="text-warning font-semibold">+base+1 pts</span></p>
        <p>❌ Wrong steal = <span class="text-error font-semibold">−base pts</span></p>
        <p>📈 Base points increase each round</p>
        <p>🧠 The AI learns your strategy and adapts — it may deliberately answer wrong to bait your steal</p>
    </div>

    <div class="mt-3 overflow-x-auto">
        <table class="table table-xs w-full text-center">
            <thead>
                <tr><th></th><th>R1</th><th>R2</th><th>R3</th></tr>
            </thead>
            <tbody>
                <tr><td class="text-left text-xs">😊 Easy correct</td><td>+1</td><td>+2</td><td>+3</td></tr>
                <tr><td class="text-left text-xs text-warning">😊 Easy steal ✅</td><td>+2</td><td>+3</td><td>+4</td></tr>
                <tr><td class="text-left text-xs">🤔 Med correct</td><td>+2</td><td>+3</td><td>+4</td></tr>
                <tr><td class="text-left text-xs text-warning">🤔 Med steal ✅</td><td>+3</td><td>+4</td><td>+5</td></tr>
                <tr><td class="text-left text-xs">🔥 Hard correct</td><td>+3</td><td>+4</td><td>+5</td></tr>
                <tr><td class="text-left text-xs text-warning">🔥 Hard steal ✅</td><td>+4</td><td>+5</td><td>+6</td></tr>
            </tbody>
        </table>
    </div>
</div>

{{-- Difficulty picker --}}
<div class="animate-in mb-6">
    <h2 class="font-semibold text-sm mb-3">Choose difficulty</h2>
    <div class="grid grid-cols-3 gap-3">
        <form action="{{ route('trivia.start') }}" method="POST">
            @csrf
            <input type="hidden" name="difficulty" value="easy">
            <button type="submit" class="btn btn-success w-full flex-col h-auto py-4 gap-1">
                <span class="text-xl">😊</span>
                <span class="font-bold">Easy</span>
                <span class="text-xs opacity-70">1–4 pts</span>
            </button>
        </form>
        <form action="{{ route('trivia.start') }}" method="POST">
            @csrf
            <input type="hidden" name="difficulty" value="medium">
            <button type="submit" class="btn btn-warning w-full flex-col h-auto py-4 gap-1">
                <span class="text-xl">🤔</span>
                <span class="font-bold">Medium</span>
                <span class="text-xs opacity-70">2–5 pts</span>
            </button>
        </form>
        <form action="{{ route('trivia.start') }}" method="POST">
            @csrf
            <input type="hidden" name="difficulty" value="hard">
            <button type="submit" class="btn btn-error w-full flex-col h-auto py-4 gap-1">
                <span class="text-xl">🔥</span>
                <span class="font-bold">Hard</span>
                <span class="text-xs opacity-70">3–6 pts</span>
            </button>
        </form>
    </div>
</div>

{{-- Recent games --}}
@if($recentGames->count() > 0)
<div class="animate-in">
    <h2 class="font-semibold text-sm mb-3">Recent games</h2>
    <div class="flex flex-col gap-2">
        @foreach($recentGames as $game)
        <a href="{{ route('trivia.result', $game->id) }}"
            class="card bg-base-100 border border-base-300/50 shadow-sm p-3 flex flex-row items-center justify-between">
            <div>
                <span class="badge badge-sm badge-outline capitalize">{{ $game->difficulty }}</span>
                <p class="text-xs text-base-content/50 mt-1">{{ $game->created_at->diffForHumans() }}</p>
            </div>
            <div class="text-right">
                <p class="font-bold text-sm">
                    <span class="{{ $game->winner() === 'user' ? 'text-success' : ($game->winner() === 'ai' ? 'text-error' : 'text-warning') }}">
                        You {{ $game->user_score }}
                    </span>
                    <span class="text-base-content/30 mx-1">vs</span>
                    <span class="{{ $game->winner() === 'ai' ? 'text-success' : ($game->winner() === 'user' ? 'text-error' : 'text-warning') }}">
                        AI {{ $game->ai_score }}
                    </span>
                </p>
                <p class="text-xs {{ $game->winner() === 'user' ? 'text-success' : ($game->winner() === 'ai' ? 'text-error' : 'text-warning') }}">
                    {{ $game->winner() === 'user' ? 'You won' : ($game->winner() === 'ai' ? 'AI won' : 'Draw') }}
                </p>
            </div>
        </a>
        @endforeach
    </div>
</div>
@endif

@endsection
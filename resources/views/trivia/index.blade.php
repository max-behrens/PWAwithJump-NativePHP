@extends('layouts.app')

@section('title', 'Trivia · TaskFlow')

@section('content')

@if(session('success'))
    <div class="alert alert-success mb-4 shadow-sm" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" x-transition>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
        <span>{{ session('success') }}</span>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-error mb-4">{{ session('error') }}</div>
@endif

{{-- Header --}}
<div class="animate-in mb-6">
    <h1 class="text-2xl font-bold">Trivia Bluff</h1>
    <p class="text-base-content/50 text-sm mt-0.5">You vs AI · 5 questions · steal to score big</p>
</div>

{{-- How to play --}}
<div class="animate-in card bg-base-100 border border-base-300/50 shadow-sm p-4 mb-6">
    <h2 class="font-semibold text-sm mb-2">How to play</h2>
    <div class="space-y-1 text-sm text-base-content/70">
        <p>🎯 Both you and the AI answer each question secretly</p>
        <p>🤔 Then both decide whether to <strong>steal</strong> the other's points</p>
        <p>✅ Correct steal = <span class="text-success font-bold">2x points</span></p>
        <p>❌ Wrong steal = <span class="text-error font-bold">-1x points</span></p>
        <p>🧠 The AI learns your patterns over time and gets harder to beat</p>
    </div>
</div>

{{-- Pick difficulty --}}
<div class="animate-in mb-6">
    <h2 class="font-semibold text-sm mb-3">Choose difficulty</h2>
    <div class="grid grid-cols-3 gap-3">

        <form action="{{ route('trivia.start') }}" method="POST">
            @csrf
            <input type="hidden" name="difficulty" value="easy">
            <button type="submit" class="btn btn-success w-full flex-col h-auto py-4 gap-1">
                <span class="text-xl">😊</span>
                <span class="font-bold">Easy</span>
                <span class="text-xs opacity-70">1pt each</span>
            </button>
        </form>

        <form action="{{ route('trivia.start') }}" method="POST">
            @csrf
            <input type="hidden" name="difficulty" value="medium">
            <button type="submit" class="btn btn-warning w-full flex-col h-auto py-4 gap-1">
                <span class="text-xl">🤔</span>
                <span class="font-bold">Medium</span>
                <span class="text-xs opacity-70">2pts each</span>
            </button>
        </form>

        <form action="{{ route('trivia.start') }}" method="POST">
            @csrf
            <input type="hidden" name="difficulty" value="hard">
            <button type="submit" class="btn btn-error w-full flex-col h-auto py-4 gap-1">
                <span class="text-xl">🔥</span>
                <span class="font-bold">Hard</span>
                <span class="text-xs opacity-70">3pts each</span>
            </button>
        </form>

    </div>
</div>

{{-- Custom game --}}
<div class="animate-in card bg-base-100 border border-base-300/50 shadow-sm p-4 mb-6" x-data="{ open: false }">
    <button class="flex items-center justify-between w-full" x-on:click="open = !open">
        <div>
            <h2 class="font-semibold text-sm">Custom Game</h2>
            <p class="text-xs text-base-content/50">Pick 5 of your own questions</p>
        </div>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
    </button>

    <div x-show="open" x-transition class="mt-4">
        @if($customQuestions->count() < 5)
            <p class="text-sm text-base-content/50 mb-3">You need at least 5 custom questions. You have {{ $customQuestions->count() }}.</p>
        @else
            <form action="{{ route('trivia.start') }}" method="POST" x-data="{ selected: [] }">
                @csrf
                <input type="hidden" name="difficulty" value="custom">
                <p class="text-xs text-base-content/50 mb-2">Select exactly 5 questions:</p>
                @foreach($customQuestions as $q)
                <label class="flex items-start gap-3 py-2 border-b border-base-300/30 last:border-0 cursor-pointer">
                    <input type="checkbox" name="question_ids[]" value="{{ $q->id }}"
                        class="checkbox checkbox-primary checkbox-sm mt-0.5"
                        x-on:change="$event.target.checked ? selected.push({{ $q->id }}) : selected.splice(selected.indexOf({{ $q->id }}), 1)"
                        :disabled="selected.length >= 5 && !selected.includes({{ $q->id }})">
                    <div class="flex-1">
                        <p class="text-sm">{{ $q->question }}</p>
                        <p class="text-xs text-base-content/40">{{ $q->score_value }}pt{{ $q->score_value > 1 ? 's' : '' }} each</p>
                    </div>
                </label>
                @endforeach
                <button type="submit" class="btn btn-primary w-full mt-4"
                    :disabled="selected.length !== 5">
                    Start Custom Game (<span x-text="selected.length"></span>/5)
                </button>
            </form>
        @endif

        <a href="{{ route('trivia.create_question') }}" class="btn btn-ghost btn-sm w-full mt-2">
            + Add a question
        </a>
    </div>
</div>

{{-- Add question shortcut --}}
<a href="{{ route('trivia.create_question') }}" class="btn btn-outline btn-sm w-full mb-6">
    + Add your own question
</a>

{{-- Recent games --}}
@if($recentGames->count() > 0)
<div class="animate-in">
    <h2 class="font-semibold text-sm mb-3">Recent games</h2>
    <div class="flex flex-col gap-2">
        @foreach($recentGames as $game)
        <a href="{{ route('trivia.result', $game->id) }}" class="card bg-base-100 border border-base-300/50 shadow-sm p-3 flex flex-row items-center justify-between">
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

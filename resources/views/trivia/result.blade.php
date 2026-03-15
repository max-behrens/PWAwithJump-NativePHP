@extends('layouts.app')

@section('title', 'Game Over · Trivia')

@section('content')

@php $winner = $trivia->winner(); @endphp

{{-- Winner banner --}}
<div class="animate-in text-center py-8 mb-5">
    <p class="text-5xl mb-3">
        @if($winner === 'user') 🏆
        @elseif($winner === 'ai') 🤖
        @else 🤝
        @endif
    </p>
    <h1 class="text-2xl font-bold mb-1">
        @if($winner === 'user') You won!
        @elseif($winner === 'ai') AI won
        @else It's a draw!
        @endif
    </h1>
    <p class="text-base-content/50 text-sm capitalize">{{ $trivia->difficulty }} game</p>
</div>

{{-- Final scores --}}
<div class="animate-in grid grid-cols-2 gap-3 mb-6">
    <div class="card border-2 p-5 text-center {{ $winner === 'user' ? 'border-success bg-success/5' : 'border-base-300/50 bg-base-100' }}">
        <p class="text-xs text-base-content/50 mb-1">Your score</p>
        <p class="text-4xl font-bold {{ $winner === 'user' ? 'text-success' : 'text-base-content' }}">
            {{ $trivia->user_score }}
        </p>
    </div>
    <div class="card border-2 p-5 text-center {{ $winner === 'ai' ? 'border-success bg-success/5' : 'border-base-300/50 bg-base-100' }}">
        <p class="text-xs text-base-content/50 mb-1">AI score</p>
        <p class="text-4xl font-bold {{ $winner === 'ai' ? 'text-success' : 'text-base-content' }}">
            {{ $trivia->ai_score }}
        </p>
    </div>
</div>

{{-- Round breakdown --}}
<div class="animate-in card bg-base-100 border border-base-300/50 shadow-sm p-4 mb-6">
    <h2 class="font-semibold text-sm mb-3">Round breakdown</h2>
    <div class="space-y-3">
        @foreach($rounds as $round)
        <div class="flex gap-3 items-start pb-3 border-b border-base-300/30 last:border-0">
            <span class="w-6 h-6 rounded-full bg-base-200 flex items-center justify-center text-xs font-bold shrink-0 mt-0.5">
                {{ $round->round_number }}
            </span>
            <div class="flex-1 min-w-0">
                <p class="text-sm truncate">{{ $round->question->question }}</p>
                <p class="text-xs text-base-content/50 mt-0.5">
                    Correct: <strong class="uppercase">{{ $round->question->correct_answer }}</strong>
                    · You: <span class="{{ $round->user_correct ? 'text-success' : 'text-error' }} uppercase font-bold">{{ $round->user_answer }}</span>
                    {{ $round->user_steal ? '🤜' : '' }}
                    · AI: <span class="{{ $round->ai_correct ? 'text-success' : 'text-error' }} uppercase font-bold">{{ $round->ai_answer }}</span>
                    {{ $round->ai_steal ? '🤜' : '' }}
                </p>
            </div>
            <div class="text-right shrink-0">
                <p class="text-xs {{ $round->user_points_earned > 0 ? 'text-success' : ($round->user_points_earned < 0 ? 'text-error' : 'text-base-content/30') }}">
                    You: {{ $round->user_points_earned > 0 ? '+' : '' }}{{ $round->user_points_earned }}
                </p>
                <p class="text-xs {{ $round->ai_points_earned > 0 ? 'text-success' : ($round->ai_points_earned < 0 ? 'text-error' : 'text-base-content/30') }}">
                    AI: {{ $round->ai_points_earned > 0 ? '+' : '' }}{{ $round->ai_points_earned }}
                </p>
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- Actions --}}
<div class="flex gap-3">
    <a href="{{ route('trivia.index') }}" class="btn btn-ghost flex-1">Back to lobby</a>
    <form action="{{ route('trivia.start') }}" method="POST" class="flex-1">
        @csrf
        <input type="hidden" name="difficulty" value="{{ $trivia->difficulty }}">
        <button type="submit" class="btn btn-primary w-full">Play again</button>
    </form>
</div>

@endsection

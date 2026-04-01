@extends('layouts.app')

@section('title', 'Game Over · Trivia')

@section('content')

@php $winner = $trivia->winner(); @endphp

<div class="animate-in text-center py-8 mb-5">
    <p class="text-5xl mb-3">
        @if($winner === 'user') 🏆 @elseif($winner === 'ai') 🤖 @else 🤝 @endif
    </p>
    <h1 class="text-2xl font-bold mb-1">
        @if($winner === 'user') You won! @elseif($winner === 'ai') AI won @else Draw! @endif
    </h1>
    <p class="text-base-content/50 text-sm capitalize">{{ $trivia->difficulty }} · 3 rounds</p>
</div>

<div class="animate-in grid grid-cols-2 gap-3 mb-6">
    <div class="card border-2 p-5 text-center {{ $winner === 'user' ? 'border-success bg-success/5' : 'border-base-300/50 bg-base-100' }}">
        <p class="text-xs text-base-content/50 mb-1">Your score</p>
        <p class="text-4xl font-bold {{ $winner === 'user' ? 'text-success' : '' }}">{{ $trivia->user_score }}</p>
    </div>
    <div class="card border-2 p-5 text-center {{ $winner === 'ai' ? 'border-success bg-success/5' : 'border-base-300/50 bg-base-100' }}">
        <p class="text-xs text-base-content/50 mb-1">AI score</p>
        <p class="text-4xl font-bold {{ $winner === 'ai' ? 'text-success' : '' }}">{{ $trivia->ai_score }}</p>
    </div>
</div>

@foreach([1,2,3] as $roundNum)
@if(isset($roundGroups[$roundNum]))
@php
    $rRounds = $roundGroups[$roundNum];
    $userTotal = $rRounds->sum('user_points_earned');
    $aiTotal = $rRounds->sum('ai_points_earned');
@endphp
<div class="animate-in card bg-base-100 border border-base-300/50 shadow-sm p-4 mb-4">
    <div class="flex justify-between items-center mb-3">
        <h2 class="font-semibold text-sm">Round {{ $roundNum }}</h2>
        <div class="text-xs">
            You <span class="{{ $userTotal >= $aiTotal ? 'text-success' : 'text-error' }} font-bold">{{ $userTotal > 0 ? '+' : '' }}{{ $userTotal }}</span>
            · AI <span class="{{ $aiTotal >= $userTotal ? 'text-success' : 'text-error' }} font-bold">{{ $aiTotal > 0 ? '+' : '' }}{{ $aiTotal }}</span>
        </div>
    </div>
    @foreach($rRounds as $round)
    <div class="flex gap-2 items-start pb-2 border-b border-base-300/20 last:border-0 mb-2">
        <span class="w-5 h-5 rounded-full bg-base-200 flex items-center justify-center text-xs font-bold shrink-0 mt-0.5">
            {{ $round->question_number }}
        </span>
        <div class="flex-1 min-w-0">
            <p class="text-xs truncate">{{ $round->question->question }}</p>
            <p class="text-xs text-base-content/40 mt-0.5">
                ✅<strong class="uppercase ml-0.5">{{ $round->question->correct_answer }}</strong>
                · You:<span class="{{ $round->user_correct ? 'text-success' : 'text-error' }} uppercase font-bold ml-0.5">{{ $round->user_answer }}</span>{{ $round->user_steal ? '🤜' : '' }}
                · AI:<span class="{{ $round->ai_correct ? 'text-success' : 'text-error' }} uppercase font-bold ml-0.5">{{ $round->ai_answer }}</span>{{ $round->ai_steal ? '🤜' : '' }}
                @if($round->ai_strategy === 'C' || $round->ai_strategy === 'D')
                    <span class="text-warning">⚠️bait</span>
                @endif
            </p>
        </div>
        <div class="text-right shrink-0 text-xs">
            <p class="{{ $round->user_points_earned > 0 ? 'text-success' : ($round->user_points_earned < 0 ? 'text-error' : 'text-base-content/30') }}">
                {{ $round->user_points_earned > 0 ? '+' : '' }}{{ $round->user_points_earned }}
            </p>
            <p class="{{ $round->ai_points_earned > 0 ? 'text-success' : ($round->ai_points_earned < 0 ? 'text-error' : 'text-base-content/30') }}">
                {{ $round->ai_points_earned > 0 ? '+' : '' }}{{ $round->ai_points_earned }}
            </p>
        </div>
    </div>
    @endforeach
</div>
@endif
@endforeach

<div class="flex gap-3 mt-2">
    <a href="{{ route('trivia.index') }}" class="btn btn-ghost flex-1">Lobby</a>
    <form action="{{ route('trivia.start') }}" method="POST" class="flex-1">
        @csrf
        <input type="hidden" name="difficulty" value="{{ $trivia->difficulty }}">
        <button type="submit" class="btn btn-primary w-full">Play again</button>
    </form>
</div>

@endsection
@extends('layouts.app')

@section('title', 'Round {{ $roundNumber }} Result · Trivia')

@section('content')

{{-- Score bar --}}
<div class="animate-in card bg-base-100 border border-base-300/50 shadow-sm p-4 mb-5">
    <div class="flex justify-between items-center">
        <div class="text-center flex-1">
            <p class="text-xs text-base-content/50">You</p>
            <p class="text-2xl font-bold text-primary">{{ $trivia->user_score }}</p>
        </div>
        <div class="text-center px-4">
            <p class="text-xs text-base-content/50">Round</p>
            <p class="text-lg font-bold">{{ $roundNumber }}<span class="text-base-content/30">/5</span></p>
        </div>
        <div class="text-center flex-1">
            <p class="text-xs text-base-content/50">AI</p>
            <p class="text-2xl font-bold text-secondary">{{ $trivia->ai_score }}</p>
        </div>
    </div>
    <progress class="progress progress-primary w-full mt-3 h-1.5" value="{{ $roundNumber }}" max="5"></progress>
</div>

{{-- Question recap --}}
<div class="animate-in card bg-base-100 border border-base-300/50 shadow-sm p-4 mb-4">
    <p class="text-xs text-base-content/50 mb-1">The question was:</p>
    <p class="font-semibold text-sm">{{ $question->question }}</p>
    <div class="mt-3 p-2 rounded-lg bg-success/10 border border-success/30">
        <p class="text-xs text-success font-semibold">✅ Correct answer: {{ strtoupper($question->correct_answer) }} — {{ $question->{'answer_' . $question->correct_answer} }}</p>
    </div>
</div>

{{-- Side by side reveal --}}
<div class="grid grid-cols-2 gap-3 mb-5">

    {{-- User result --}}
    <div class="animate-in card border-2 p-4 {{ $userCorrect ? 'border-success bg-success/5' : 'border-error bg-error/5' }}">
        <p class="text-xs text-base-content/50 mb-2">You answered</p>
        <p class="font-bold text-lg uppercase">{{ $userAnswer }}</p>
        <p class="text-xs mt-1">{{ $question->{'answer_' . $userAnswer} }}</p>
        <div class="mt-2">
            @if($userCorrect)
                <span class="badge badge-success badge-sm">✅ Correct</span>
            @else
                <span class="badge badge-error badge-sm">❌ Wrong</span>
            @endif
        </div>
        <div class="mt-2 pt-2 border-t border-base-300/30">
            <p class="text-xs text-base-content/50">Your steal decision:</p>
            <p class="text-sm font-semibold {{ $userSteal ? 'text-warning' : 'text-success' }}">
                {{ $userSteal ? '🤜 Stole' : '🛡️ Held back' }}
            </p>
        </div>
        <div class="mt-2 pt-2 border-t border-base-300/30">
            <p class="text-xs text-base-content/50">Points this round:</p>
            <p class="text-xl font-bold {{ $userPoints > 0 ? 'text-success' : ($userPoints < 0 ? 'text-error' : 'text-base-content/50') }}">
                {{ $userPoints > 0 ? '+' : '' }}{{ $userPoints }}
            </p>
        </div>
    </div>

    {{-- AI result --}}
    <div class="animate-in card border-2 p-4 {{ $aiCorrect ? 'border-success bg-success/5' : 'border-error bg-error/5' }}">
        <p class="text-xs text-base-content/50 mb-2">AI answered</p>
        <p class="font-bold text-lg uppercase">{{ $aiAnswer }}</p>
        <p class="text-xs mt-1">{{ $question->{'answer_' . $aiAnswer} }}</p>
        <div class="mt-2">
            @if($aiCorrect)
                <span class="badge badge-success badge-sm">✅ Correct</span>
            @else
                <span class="badge badge-error badge-sm">❌ Wrong</span>
            @endif
        </div>
        <div class="mt-2 pt-2 border-t border-base-300/30">
            <p class="text-xs text-base-content/50">AI steal decision:</p>
            <p class="text-sm font-semibold {{ $aiSteal ? 'text-warning' : 'text-success' }}">
                {{ $aiSteal ? '🤜 Stole' : '🛡️ Held back' }}
            </p>
        </div>
        <div class="mt-2 pt-2 border-t border-base-300/30">
            <p class="text-xs text-base-content/50">Points this round:</p>
            <p class="text-xl font-bold {{ $aiPoints > 0 ? 'text-success' : ($aiPoints < 0 ? 'text-error' : 'text-base-content/50') }}">
                {{ $aiPoints > 0 ? '+' : '' }}{{ $aiPoints }}
            </p>
        </div>
    </div>
</div>

{{-- What happened explanation --}}
<div class="animate-in card bg-base-100 border border-base-300/50 shadow-sm p-4 mb-6">
    <p class="text-xs text-base-content/50 mb-1">What happened:</p>
    <div class="space-y-1 text-sm">
        @if($userSteal && $aiCorrect)
            <p>✅ You stole correctly — AI got it right, you score <strong>{{ $scoreValue * 2 }}pts</strong></p>
        @elseif($userSteal && !$aiCorrect)
            <p>❌ You stole but AI got it wrong — you lose <strong>{{ $scoreValue }}pt{{ $scoreValue > 1 ? 's' : '' }}</strong></p>
        @elseif(!$userSteal && $userCorrect)
            <p>🛡️ You played safe and got it right — <strong>{{ $scoreValue }}pt{{ $scoreValue > 1 ? 's' : '' }}</strong></p>
        @else
            <p>You got it wrong and didn't steal — <strong>0pts</strong></p>
        @endif

        @if($aiSteal && $userCorrect)
            <p>🤖 AI stole correctly — you got it right, AI scores <strong>{{ $scoreValue * 2 }}pts</strong></p>
        @elseif($aiSteal && !$userCorrect)
            <p>🤖 AI tried to steal but you got it wrong — AI loses <strong>{{ $scoreValue }}pt{{ $scoreValue > 1 ? 's' : '' }}</strong></p>
        @elseif(!$aiSteal && $aiCorrect)
            <p>🤖 AI played safe and got it right — <strong>{{ $scoreValue }}pt{{ $scoreValue > 1 ? 's' : '' }}</strong></p>
        @else
            <p>🤖 AI got it wrong and didn't steal — <strong>0pts</strong></p>
        @endif
    </div>
</div>

{{-- Next / finish --}}
@if($trivia->isComplete())
    <a href="{{ route('trivia.result', $trivia->id) }}" class="btn btn-primary w-full">
        See Final Results 🏆
    </a>
@else
    <a href="{{ route('trivia.play', $trivia->id) }}" class="btn btn-primary w-full">
        Next Question →
    </a>
@endif

@endsection

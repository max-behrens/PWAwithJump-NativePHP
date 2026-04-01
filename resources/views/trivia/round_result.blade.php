@extends('layouts.app')

@section('title', 'Result · Trivia')

@section('content')

{{-- Score --}}
<div class="animate-in card bg-base-100 border border-base-300/50 shadow-sm p-4 mb-5">
    <div class="flex justify-between items-center mb-3">
        <div class="text-center flex-1">
            <p class="text-xs text-base-content/50">You</p>
            <p class="text-2xl font-bold text-primary">{{ $trivia->user_score }}</p>
        </div>
        <div class="text-center px-2">
            <p class="text-xs text-base-content/50 mono">R{{ $currentRound }}/3 · Q{{ $questionNumber }}/3</p>
        </div>
        <div class="text-center flex-1">
            <p class="text-xs text-base-content/50">AI</p>
            <p class="text-2xl font-bold text-secondary">{{ $trivia->ai_score }}</p>
        </div>
    </div>
    <div class="flex gap-2 justify-center">
        @for($r = 1; $r <= 3; $r++)
            <div class="flex gap-1">
                @for($q = 1; $q <= 3; $q++)
                    @php $done = ($r < $currentRound) || ($r === $currentRound && $q <= $questionNumber); @endphp
                    <div class="w-4 h-1.5 rounded-full {{ $done ? 'bg-primary' : 'bg-base-300' }}"></div>
                @endfor
            </div>
            @if($r < 3)<div class="w-1"></div>@endif
        @endfor
    </div>
</div>

{{-- Round complete banner --}}
@if($justFinishedRound)
<div class="animate-in alert alert-info mb-4 shadow-sm">
    <span>Round {{ $currentRound }} complete! Points increase next round 📈</span>
</div>
@endif

{{-- Question recap --}}
<div class="animate-in card bg-base-100 border border-base-300/50 shadow-sm p-4 mb-4">
    <p class="text-xs text-base-content/50 mb-1">The question was:</p>
    <p class="font-semibold text-sm">{{ $question->question }}</p>
    <div class="mt-2 p-2 rounded-lg bg-success/10 border border-success/30">
        <p class="text-xs text-success font-semibold">
            ✅ {{ strtoupper($question->correct_answer) }} — {{ $question->{'answer_' . $question->correct_answer} }}
        </p>
    </div>
</div>

{{-- Side by side --}}
<div class="grid grid-cols-2 gap-3 mb-5">
    <div class="animate-in card border-2 p-4 {{ $userCorrect ? 'border-success bg-success/5' : 'border-error bg-error/5' }}">
        <p class="text-xs text-base-content/50 mb-2">You answered</p>
        <p class="font-bold text-lg uppercase">{{ $userAnswer }}</p>
        <p class="text-xs mt-1 text-base-content/60">{{ $question->{'answer_' . $userAnswer} }}</p>
        <div class="mt-2">
            <span class="badge badge-sm {{ $userCorrect ? 'badge-success' : 'badge-error' }}">
                {{ $userCorrect ? '✅ Correct' : '❌ Wrong' }}
            </span>
        </div>
        <div class="mt-2 pt-2 border-t border-base-300/30">
            <p class="text-xs text-base-content/50">Steal:</p>
            <p class="text-sm font-semibold {{ $userSteal ? 'text-warning' : 'text-base-content/60' }}">
                {{ $userSteal ? '🤜 Stole' : '🛡️ Held' }}
            </p>
        </div>
        <div class="mt-2 pt-2 border-t border-base-300/30">
            <p class="text-xs text-base-content/50">Points:</p>
            <p class="text-xl font-bold {{ $userPoints > 0 ? 'text-success' : ($userPoints < 0 ? 'text-error' : 'text-base-content/40') }}">
                {{ $userPoints > 0 ? '+' : '' }}{{ $userPoints }}
            </p>
        </div>
    </div>

    <div class="animate-in card border-2 p-4 {{ $aiCorrect ? 'border-success bg-success/5' : 'border-error bg-error/5' }}">
        <p class="text-xs text-base-content/50 mb-2">AI answered</p>
        <p class="font-bold text-lg uppercase">{{ $aiAnswer }}</p>
        <p class="text-xs mt-1 text-base-content/60">{{ $question->{'answer_' . $aiAnswer} }}</p>
        <div class="mt-2">
            <span class="badge badge-sm {{ $aiCorrect ? 'badge-success' : 'badge-error' }}">
                {{ $aiCorrect ? '✅ Correct' : '❌ Wrong' }}
            </span>
        </div>
        <div class="mt-2 pt-2 border-t border-base-300/30">
            <p class="text-xs text-base-content/50">Steal:</p>
            <p class="text-sm font-semibold {{ $aiSteal ? 'text-warning' : 'text-base-content/60' }}">
                {{ $aiSteal ? '🤜 Stole' : '🛡️ Held' }}
            </p>
        </div>
        <div class="mt-2 pt-2 border-t border-base-300/30">
            <p class="text-xs text-base-content/50">Points:</p>
            <p class="text-xl font-bold {{ $aiPoints > 0 ? 'text-success' : ($aiPoints < 0 ? 'text-error' : 'text-base-content/40') }}">
                {{ $aiPoints > 0 ? '+' : '' }}{{ $aiPoints }}
            </p>
        </div>
    </div>
</div>

{{-- What happened --}}
<div class="animate-in card bg-base-100 border border-base-300/50 shadow-sm p-4 mb-6 text-sm space-y-1">
    <p class="text-xs text-base-content/50 mb-1">What happened:</p>
    @if($userSteal && $aiCorrect)
        <p>🤜 You stole correctly → <strong>+{{ $baseScore + 1 }}pts</strong></p>
    @elseif($userSteal && !$aiCorrect)
        <p>❌ You stole but AI got it wrong → <strong>−{{ $baseScore }}pts</strong></p>
    @elseif(!$userSteal && $userCorrect)
        <p>🛡️ You played safe, got it right → <strong>+{{ $baseScore }}pts</strong></p>
    @else
        <p>You got it wrong, didn't steal → <strong>0pts</strong></p>
    @endif

    @if($aiSteal && $userCorrect)
        <p>🤖 AI stole correctly (strategy {{ $aiStrategy }}) → <strong>+{{ $baseScore + 1 }}pts to AI</strong></p>
    @elseif($aiSteal && !$userCorrect)
        <p>🤖 AI stole but you got it wrong (strategy {{ $aiStrategy }}) → <strong>−{{ $baseScore }}pts to AI</strong></p>
    @elseif(!$aiSteal && $aiCorrect)
        <p>🤖 AI played safe, got it right (strategy {{ $aiStrategy }}) → <strong>+{{ $baseScore }}pts to AI</strong></p>
    @else
        <p>🤖 AI got it wrong, didn't steal (strategy {{ $aiStrategy }}) → <strong>0pts</strong></p>
    @endif

    @if($aiStrategy === 'C')
        <p class="text-xs text-warning mt-1">⚠️ AI deliberately answered wrong — it was trying to bait your steal!</p>
    @endif
</div>

{{-- Next --}}
@if($trivia->isComplete())
    <a href="{{ route('trivia.result', $trivia->id) }}" class="btn btn-primary w-full">See Final Results 🏆</a>
@else
    <a href="{{ route('trivia.play', $trivia->id) }}" class="btn btn-primary w-full">
        {{ $justFinishedRound ? 'Start Round ' . $trivia->current_round . ' →' : 'Next Question →' }}
    </a>
@endif

@endsection
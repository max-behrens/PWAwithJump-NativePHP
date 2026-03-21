@extends('layouts.app')

@section('title', 'Question {{ $roundNumber }} · Trivia')

@section('back_button')
    <a href="{{ route('trivia.index') }}" class="btn btn-ghost btn-sm btn-circle">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
    </a>
@endsection

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
    <progress class="progress progress-primary w-full mt-3 h-1.5" value="{{ $roundNumber - 1 }}" max="5"></progress>
</div>

{{-- Question --}}
<div class="animate-in mb-5">
    <div class="flex items-center gap-2 mb-2">
        <span class="badge badge-outline badge-sm capitalize">{{ $trivia->difficulty }}</span>
        <span class="badge badge-primary badge-sm">{{ $question->score_value }}pt{{ $question->score_value > 1 ? 's' : '' }}</span>
    </div>
    <h2 class="text-xl font-bold leading-snug">{{ $question->question }}</h2>
</div>

{{-- Answer + steal form --}}
<form action="{{ route('trivia.answer', $trivia->id) }}" method="POST"
    x-data="{ selected: null, steal: null, submitted: false }">
    @csrf

    {{-- Answer options --}}
    <div class="grid grid-cols-1 gap-3 mb-6">
        @foreach(['a' => $question->answer_a, 'b' => $question->answer_b, 'c' => $question->answer_c, 'd' => $question->answer_d] as $key => $answer)
        <label class="cursor-pointer">
            <input type="radio" name="user_answer" value="{{ $key }}" class="hidden"
                x-on:change="selected = '{{ $key }}'">
            <div class="card border-2 p-4 transition-all duration-150"
                :class="selected === '{{ $key }}'
                    ? 'border-primary bg-primary/10'
                    : 'border-base-300/50 bg-base-100'">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-full border-2 flex items-center justify-center text-sm font-bold shrink-0 uppercase"
                        :class="selected === '{{ $key }}' ? 'border-primary text-primary' : 'border-base-300 text-base-content/50'">
                        {{ $key }}
                    </span>
                    <span class="text-sm">{{ $answer }}</span>
                </div>
            </div>
        </label>
        @endforeach
    </div>

    {{-- Steal decision --}}
    <div class="animate-in card bg-base-100 border border-base-300/50 shadow-sm p-4 mb-5">
        <p class="font-semibold text-sm mb-1">Will the AI get this right?</p>
        <p class="text-xs text-base-content/50 mb-3">Steal if you think it will — double your points. Wrong steal loses you points.</p>
        <div class="grid grid-cols-2 gap-3">
            <label class="cursor-pointer">
                <input type="radio" name="user_steal" value="1" class="hidden"
                    x-on:change="steal = 'yes'">
                <div class="card border-2 p-3 text-center transition-all"
                    :class="steal === 'yes' ? 'border-warning bg-warning/10' : 'border-base-300/50 bg-base-200/50'">
                    <p class="text-xl mb-1">🤜</p>
                    <p class="font-bold text-sm">Steal</p>
                    <p class="text-xs text-base-content/50">I think AI will get it</p>
                </div>
            </label>
            <label class="cursor-pointer">
                <input type="radio" name="user_steal" value="0" class="hidden"
                    x-on:change="steal = 'no'">
                <div class="card border-2 p-3 text-center transition-all"
                    :class="steal === 'no' ? 'border-success bg-success/10' : 'border-base-300/50 bg-base-200/50'">
                    <p class="text-xl mb-1">🛡️</p>
                    <p class="font-bold text-sm">Don't steal</p>
                    <p class="text-xs text-base-content/50">Play it safe</p>
                </div>
            </label>
        </div>
    </div>

    <button type="submit"
        class="btn btn-primary w-full"
        :disabled="!selected || steal === null">
        Submit Answer
    </button>
</form>

@endsection

@extends('layouts.app')

@section('title', 'Add Question · Trivia')

@section('back_button')
    <a href="{{ route('trivia.index') }}" class="btn btn-ghost btn-sm btn-circle">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
    </a>
@endsection

@section('content')

<div class="animate-in mb-6">
    <h1 class="text-2xl font-bold">Add Question</h1>
    <p class="text-base-content/50 text-sm mt-0.5">Create a custom trivia question</p>
</div>

@if($errors->any())
    <div class="alert alert-error mb-4 shadow-sm">
        @foreach($errors->all() as $error)
            <p class="text-sm">{{ $error }}</p>
        @endforeach
    </div>
@endif

<form action="{{ route('trivia.store_question') }}" method="POST" class="space-y-4">
    @csrf

    <div class="animate-in card bg-base-100 border border-base-300/50 shadow-sm p-5 space-y-4">

        <div class="form-control">
            <label class="label pb-1"><span class="label-text font-semibold text-sm">Question</span></label>
            <textarea name="question" placeholder="e.g. What is the capital of Japan?"
                class="textarea textarea-bordered w-full h-20 resize-none @error('question') textarea-error @enderror"
            >{{ old('question') }}</textarea>
        </div>

        @foreach(['a','b','c','d'] as $letter)
        <div class="form-control">
            <label class="label pb-1">
                <span class="label-text font-semibold text-sm">Answer {{ strtoupper($letter) }}</span>
            </label>
            <input type="text" name="answer_{{ $letter }}"
                value="{{ old('answer_' . $letter) }}"
                placeholder="Option {{ strtoupper($letter) }}"
                class="input input-bordered w-full @error('answer_' . $letter) input-error @enderror">
        </div>
        @endforeach

        <div class="form-control">
            <label class="label pb-1"><span class="label-text font-semibold text-sm">Correct answer</span></label>
            <select name="correct_answer" class="select select-bordered w-full">
                @foreach(['a','b','c','d'] as $letter)
                    <option value="{{ $letter }}" {{ old('correct_answer') === $letter ? 'selected' : '' }}>
                        {{ strtoupper($letter) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-control">
            <label class="label pb-1">
                <span class="label-text font-semibold text-sm">Points value</span>
                <span class="label-text-alt text-base-content/40">1–10</span>
            </label>
            <input type="number" name="score_value" min="1" max="10"
                value="{{ old('score_value', 2) }}"
                class="input input-bordered w-full @error('score_value') input-error @enderror">
        </div>

    </div>

    <div class="flex gap-3 pt-2">
        <a href="{{ route('trivia.index') }}" class="btn btn-ghost flex-1">Cancel</a>
        <button type="submit" class="btn btn-primary flex-1">Add Question</button>
    </div>

</form>

@endsection

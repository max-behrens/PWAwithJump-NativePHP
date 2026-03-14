@extends('layouts.app')

@section('title', 'Edit Task · TaskFlow')

@section('back_button')
    <a href="{{ route('tasks.index') }}" class="btn btn-ghost btn-sm btn-circle">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
    </a>
@endsection

@section('content')
    <div class="animate-in mb-6">
        <h1 class="text-2xl font-bold">Edit Task</h1>
        <p class="text-base-content/50 text-sm mt-0.5 truncate">{{ $task->title }}</p>
    </div>

    @if ($errors->any())
        <div class="animate-in alert alert-error mb-5 shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <div>
                @foreach ($errors->all() as $error)
                    <p class="text-sm">{{ $error }}</p>
                @endforeach
            </div>
        </div>
    @endif

    <form action="{{ route('tasks.update', $task->id) }}" method="POST" class="space-y-4">
        @csrf
        @method('PUT')

        <div class="animate-in card bg-base-100 shadow-sm border border-base-300/50 p-5">
            <div class="form-control mb-4">
                <label class="label pb-1.5">
                    <span class="label-text font-semibold text-sm">Title</span>
                    <span class="label-text-alt text-error">Required</span>
                </label>
                <input
                    type="text"
                    name="title"
                    value="{{ old('title', $task->title) }}"
                    placeholder="Task title"
                    class="input input-bordered w-full @error('title') input-error @enderror"
                >
            </div>

            <div class="form-control mb-4">
                <label class="label pb-1.5">
                    <span class="label-text font-semibold text-sm">Description</span>
                    <span class="label-text-alt text-base-content/40">Optional</span>
                </label>
                <textarea
                    name="description"
                    placeholder="Add more details..."
                    class="textarea textarea-bordered w-full h-28 resize-none @error('description') textarea-error @enderror"
                >{{ old('description', $task->description) }}</textarea>
            </div>

            <div class="form-control">
                <label class="label cursor-pointer justify-start gap-4 py-3 px-4 rounded-xl bg-base-200/50 hover:bg-base-200 transition-colors">
                    <input type="hidden" name="completed" value="0">
                    <input
                        type="checkbox"
                        name="completed"
                        value="1"
                        class="toggle toggle-success"
                        {{ old('completed', $task->completed) ? 'checked' : '' }}
                    >
                    <div>
                        <span class="label-text font-semibold text-sm">Mark as completed</span>
                        <p class="text-base-content/40 text-xs">Toggle to update task status</p>
                    </div>
                </label>
            </div>
        </div>

        <div class="animate-in-3 flex gap-3 pt-2">
            <a href="{{ route('tasks.index') }}" class="btn btn-ghost flex-1">Cancel</a>
            <button type="submit" class="btn btn-primary flex-1 shadow-md shadow-primary/20">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                Save Changes
            </button>
        </div>
    </form>
@endsection
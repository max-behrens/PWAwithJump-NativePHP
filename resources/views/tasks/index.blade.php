@extends('layouts.app')

@section('title', 'My Tasks · TaskFlow')

@section('content')
    <div class="mb-5">
        <h1 class="text-2xl font-bold">My Tasks</h1>
        <p class="text-base-content/50 text-sm mt-0.5">
            @if(isset($tasks))
                {{ $tasks->count() }} {{ Str::plural('task', $tasks->count()) }}
            @endif
        </p>
    </div>

    @if ($message = Session::get('success'))
        <div class="alert alert-success mb-4 shadow-sm" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" x-transition>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
            <span>{{ $message }}</span>
        </div>
    @endif

    @forelse ($tasks as $task)
        <div class="task-card card bg-base-100 shadow-sm border border-base-300/50 mb-3 hover:border-primary/30 transition-all duration-200">
            <div class="card-body py-4 px-4">
                <div class="flex justify-between items-start gap-3">
                    <div class="flex-1 min-w-0">
                        <h2 class="font-semibold text-base truncate {{ $task->completed ? 'line-through text-base-content/40' : '' }}">
                            {{ $task->title }}
                        </h2>
                        @if($task->description)
                            <p class="text-base-content/50 text-sm mt-0.5 truncate">{{ $task->description }}</p>
                        @endif
                        <div class="mt-2">
                            <span class="badge badge-sm {{ $task->completed ? 'badge-success' : 'badge-warning' }} gap-1">
                                @if($task->completed)
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                    Done
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    Pending
                                @endif
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center gap-1 shrink-0">
                        <a href="{{ route('tasks.edit', $task->id) }}" class="btn btn-ghost btn-sm btn-square" title="Edit">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                        </a>
                        <form action="{{ route('tasks.destroy', $task->id) }}" method="POST"
                            x-data
                            x-on:submit.prevent="if(confirm('Delete this task?')) $el.submit()">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-ghost btn-sm btn-square text-error/70 hover:text-error hover:bg-error/10" title="Delete">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="flex flex-col items-center justify-center py-20 text-center">
            <div class="w-16 h-16 rounded-full bg-base-300/50 flex items-center justify-center mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-base-content/30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
            </div>
            <p class="text-base-content/40 font-medium">No tasks yet</p>
            <p class="text-base-content/30 text-sm mt-1">Tap + to create your first task</p>
        </div>
    @endforelse
@endsection

@section('fab')
    <div class="fixed bottom-6 right-6 z-40">
        <a href="{{ route('tasks.create') }}" class="btn btn-primary btn-circle w-14 h-14 shadow-xl shadow-primary/30 text-xl">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
        </a>
    </div>
@endsection
@extends('layouts.app')

@section('title', 'AI Insights · TaskFlow')

@section('content')
<div class="animate-in mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold">AI Insights</h1>
        <p class="text-base-content/50 text-sm mt-0.5">Trained on your tasks</p>
    </div>
    <form action="{{ route('ai.train') }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-outline btn-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
            Retrain
        </button>
    </form>
</div>

@if(session('success'))
    <div class="alert alert-success mb-4 shadow-sm" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" x-transition>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
        <span>{{ session('success') }}</span>
    </div>
@endif

{{-- AI Server status --}}
@if(isset($stats['error']))
    <div class="alert alert-error mb-6 shadow-sm">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        <span>{{ $stats['error'] }}</span>
    </div>
@else

{{-- Stats cards --}}
<div class="grid grid-cols-2 gap-3 mb-6">
    <div class="animate-in card bg-base-100 border border-base-300/50 shadow-sm p-4">
        <p class="text-base-content/50 text-xs mb-1">Total Tasks</p>
        <p class="text-3xl font-bold text-primary">{{ $stats['total_tasks'] ?? 0 }}</p>
    </div>
    <div class="animate-in card bg-base-100 border border-base-300/50 shadow-sm p-4">
        <p class="text-base-content/50 text-xs mb-1">Completion Rate</p>
        <p class="text-3xl font-bold text-success">{{ $stats['completion_rate'] ?? 0 }}%</p>
    </div>
    <div class="animate-in card bg-base-100 border border-base-300/50 shadow-sm p-4">
        <p class="text-base-content/50 text-xs mb-1">Completed</p>
        <p class="text-3xl font-bold text-success">{{ $stats['completed'] ?? 0 }}</p>
    </div>
    <div class="animate-in card bg-base-100 border border-base-300/50 shadow-sm p-4">
        <p class="text-base-content/50 text-xs mb-1">Pending</p>
        <p class="text-3xl font-bold text-warning">{{ $stats['pending'] ?? 0 }}</p>
    </div>
</div>

{{-- Word insights --}}
@if(!empty($stats['top_completed_words']) || !empty($stats['top_pending_words']))
<div class="animate-in card bg-base-100 border border-base-300/50 shadow-sm p-4 mb-6">
    <h2 class="font-semibold text-sm mb-3">What words predict completion?</h2>
    <div class="flex flex-col gap-2">
        @if(!empty($stats['top_completed_words']))
        <div>
            <p class="text-xs text-base-content/50 mb-1">✅ Words in completed tasks</p>
            <div class="flex flex-wrap gap-1">
                @foreach($stats['top_completed_words'] as $word)
                    <span class="badge badge-success badge-sm">{{ $word }}</span>
                @endforeach
            </div>
        </div>
        @endif
        @if(!empty($stats['top_pending_words']))
        <div>
            <p class="text-xs text-base-content/50 mb-1">⏳ Words in pending tasks</p>
            <div class="flex flex-wrap gap-1">
                @foreach($stats['top_pending_words'] as $word)
                    <span class="badge badge-warning badge-sm">{{ $word }}</span>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endif

{{-- Predict form --}}
<div class="animate-in card bg-base-100 border border-base-300/50 shadow-sm p-4 mb-6">
    <h2 class="font-semibold text-sm mb-3">Will I complete this task?</h2>
    <form action="{{ route('ai.predict') }}" method="POST" class="flex gap-2">
        @csrf
        <input type="text" name="text" placeholder="Describe a task..."
            value="{{ session('predicted_text', '') }}"
            class="input input-bordered input-sm flex-1 text-sm">
        <button type="submit" class="btn btn-primary btn-sm">Predict</button>
    </form>

    @if(session('prediction') && !isset(session('prediction')['error']))
        @php $p = session('prediction'); @endphp
        <div class="mt-3 p-3 rounded-xl bg-base-200/50">
            <p class="text-xs text-base-content/50 mb-1">For: <span class="font-medium text-base-content">{{ session('predicted_text') }}</span></p>
            <div class="flex items-center justify-between">
                <span class="font-bold {{ $p['prediction'] === 'completed' ? 'text-success' : 'text-warning' }}">
                    {{ $p['prediction'] === 'completed' ? '✅ Likely to complete' : '⏳ Might stay pending' }}
                </span>
                <span class="text-xs text-base-content/50">{{ $p['completion_likelihood'] }}% chance</span>
            </div>
            <progress class="progress {{ $p['prediction'] === 'completed' ? 'progress-success' : 'progress-warning' }} w-full mt-2"
                value="{{ $p['completion_likelihood'] }}" max="100"></progress>
        </div>
    @elseif(session('prediction') && isset(session('prediction')['error']))
        <div class="mt-3 alert alert-error alert-sm text-sm">{{ session('prediction')['error'] }}</div>
    @endif
</div>

{{-- Suggestions --}}
@if(!empty($suggestions['suggestions']))
<div class="animate-in card bg-base-100 border border-base-300/50 shadow-sm p-4 mb-6">
    <h2 class="font-semibold text-sm mb-3">Pending tasks ranked by completion likelihood</h2>
    <div class="flex flex-col gap-2">
        @foreach($suggestions['suggestions'] as $s)
        <div class="flex items-center justify-between py-2 border-b border-base-300/30 last:border-0">
            <span class="text-sm flex-1 truncate mr-2">{{ $s['title'] }}</span>
            <div class="flex items-center gap-2 shrink-0">
                <progress class="progress progress-primary w-16 h-1.5"
                    value="{{ $s['completion_likelihood'] }}" max="100"></progress>
                <span class="text-xs text-base-content/50 w-8 text-right">{{ $s['completion_likelihood'] }}%</span>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

@endif {{-- end no error --}}

{{-- Recent predictions --}}
@if($recentInsights->count() > 0)
<div class="animate-in card bg-base-100 border border-base-300/50 shadow-sm p-4">
    <h2 class="font-semibold text-sm mb-3">Recent predictions</h2>
    <div class="flex flex-col gap-2">
        @foreach($recentInsights as $insight)
        <div class="flex items-center justify-between py-1.5 border-b border-base-300/30 last:border-0">
            <span class="text-sm flex-1 truncate mr-2 text-base-content/70">{{ $insight->input_text }}</span>
            <span class="badge badge-sm {{ $insight->prediction === 'completed' ? 'badge-success' : 'badge-warning' }} shrink-0">
                {{ $insight->completion_likelihood }}%
            </span>
        </div>
        @endforeach
    </div>
</div>
@endif

@endsection
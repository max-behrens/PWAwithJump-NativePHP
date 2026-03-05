<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>My Tasks · TaskFlow</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@5/themes.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'DM Sans', sans-serif; }
        .mono { font-family: 'DM Mono', monospace; }
        .glass-nav { backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .task-card { animation: slideUp 0.25s ease forwards; }
        .task-card:nth-child(1) { animation-delay: 0.05s; }
        .task-card:nth-child(2) { animation-delay: 0.1s; }
        .task-card:nth-child(3) { animation-delay: 0.15s; }
        .task-card:nth-child(4) { animation-delay: 0.2s; }
        .task-card:nth-child(5) { animation-delay: 0.25s; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-base-200 min-h-screen pb-28"
    x-data="{ theme: localStorage.getItem('theme') || 'dark' }"
    x-init="$el.closest('html').setAttribute('data-theme', theme)"
    x-cloak>

    <!-- Navbar -->
    <div class="navbar glass-nav bg-base-100/80 sticky top-0 z-50 border-b border-base-300/50 px-4">
        <div class="navbar-start">
            <span class="font-bold text-lg mono"><span class="text-primary">task</span>flow</span>
        </div>
        <div class="navbar-end">
            <label class="swap swap-rotate btn btn-ghost btn-sm btn-circle">
                <input type="checkbox"
                    x-on:change="theme = $event.target.checked ? 'light' : 'dark'; localStorage.setItem('theme', theme); $el.closest('html').setAttribute('data-theme', theme)"
                    x-bind:checked="theme === 'light'">
                <svg class="swap-on h-5 w-5 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M5.64,17l-.71.71a1,1,0,0,0,0,1.41,1,1,0,0,0,1.41,0l.71-.71A1,1,0,0,0,5.64,17ZM5,12a1,1,0,0,0-1-1H3a1,1,0,0,0,0,2H4A1,1,0,0,0,5,12Zm7-7a1,1,0,0,0,1-1V3a1,1,0,0,0-2,0V4A1,1,0,0,0,12,5ZM5.64,7,5,6.36a1,1,0,0,0-1.41,1.41L4.22,8.46A1,1,0,0,0,5.64,7Zm12,.71.71-.71A1,1,0,0,0,16.93,5.64l-.71.71A1,1,0,0,0,17.66,7.66ZM21,11H20a1,1,0,0,0,0,2h1a1,1,0,0,0,0-2Zm-9,8a1,1,0,0,0-1,1v1a1,1,0,0,0,2,0V20A1,1,0,0,0,12,19ZM18.36,17A1,1,0,0,0,17,18.36l.71.71a1,1,0,0,0,1.41-1.41ZM12,6.5A5.5,5.5,0,1,0,17.5,12,5.51,5.51,0,0,0,12,6.5Zm0,9A3.5,3.5,0,1,1,15.5,12,3.5,3.5,0,0,1,12,15.5Z"/></svg>
                <svg class="swap-off h-5 w-5 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M21.64,13a1,1,0,0,0-1.05-.14,8.05,8.05,0,0,1-3.37.73A8.15,8.15,0,0,1,9.08,5.49a8.59,8.59,0,0,1,.25-2A1,1,0,0,0,8,2.36,10.14,10.14,0,1,0,22,14.05,1,1,0,0,0,21.64,13Zm-9.5,6.69A8.14,8.14,0,0,1,7.08,5.22v.27A10.15,10.15,0,0,0,17.22,15.63a9.79,9.79,0,0,0,2.1-.22A8.11,8.11,0,0,1,12.14,19.73Z"/></svg>
            </label>
        </div>
    </div>

    <div class="max-w-lg mx-auto px-4 py-5">

        <!-- Page header -->
        <div class="mb-5">
            <h1 class="text-2xl font-bold">My Tasks</h1>
            <p class="text-base-content/50 text-sm mt-0.5">
                @if(isset($tasks))
                    {{ $tasks->count() }} {{ Str::plural('task', $tasks->count()) }}
                @endif
            </p>
        </div>

        <!-- Success toast -->
        @if ($message = Session::get('success'))
            <div class="alert alert-success mb-4 shadow-sm" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" x-transition>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                <span>{{ $message }}</span>
            </div>
        @endif

        <!-- Tasks list -->
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
    </div>

    <!-- FAB -->
    <div class="fixed bottom-6 right-6 z-40">
        <a href="{{ route('tasks.create') }}" class="btn btn-primary btn-circle w-14 h-14 shadow-xl shadow-primary/30 text-xl">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
        </a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3/dist/cdn.min.js" defer></script>
</body>
</html>
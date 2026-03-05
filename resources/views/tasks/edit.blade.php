<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Edit Task · TaskFlow</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@5/themes.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'DM Sans', sans-serif; }
        .mono { font-family: 'DM Mono', monospace; }
        .glass-nav { backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-in { animation: slideUp 0.3s ease forwards; }
        .animate-in-2 { animation: slideUp 0.3s ease 0.08s both; }
        .animate-in-3 { animation: slideUp 0.3s ease 0.16s both; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-base-200 min-h-screen"
    x-data="{ theme: localStorage.getItem('theme') || 'dark' }"
    x-init="$el.closest('html').setAttribute('data-theme', theme)"
    x-cloak>

    <!-- Navbar -->
    <div class="navbar glass-nav bg-base-100/80 sticky top-0 z-50 border-b border-base-300/50 px-2">
        <div class="navbar-start">
            <a href="{{ route('tasks.index') }}" class="btn btn-ghost btn-sm btn-circle">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </a>
            <span class="font-bold text-lg mono ml-1"><span class="text-primary">task</span>flow</span>
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

    <div class="max-w-lg mx-auto px-4 py-6">

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

                <!-- Completed toggle -->
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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3/dist/cdn.min.js" defer></script>
</body>
</html>
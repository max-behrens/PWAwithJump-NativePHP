<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>TaskFlow</title>
<link href="/css/daisyui-themes.css" rel="stylesheet">
<link href="/css/daisyui.css" rel="stylesheet">
<script src="/js/tailwind.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'DM Sans', sans-serif; }
        .mono { font-family: 'DM Mono', monospace; }
        .glass { backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes pulse-slow {
            0%, 100% { opacity: 0.4; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(1.05); }
        }
        .fade-up { animation: fadeUp 0.6s ease forwards; }
        .fade-up-1 { animation: fadeUp 0.6s ease 0.1s both; }
        .fade-up-2 { animation: fadeUp 0.6s ease 0.2s both; }
        .fade-up-3 { animation: fadeUp 0.6s ease 0.35s both; }
        .fade-up-4 { animation: fadeUp 0.6s ease 0.5s both; }
        .blob { animation: pulse-slow 6s ease-in-out infinite; }
        .blob-2 { animation: pulse-slow 8s ease-in-out 2s infinite; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-base-200 min-h-screen overflow-x-hidden"
    x-data="{ theme: localStorage.getItem('theme') || 'dark' }"
    x-init="$el.closest('html').setAttribute('data-theme', theme)"
    x-cloak>

    <!-- Theme toggle -->
    <div class="fixed top-4 right-4 z-50">
        <label class="swap swap-rotate btn btn-ghost btn-sm btn-circle glass bg-base-100/50">
            <input type="checkbox"
                x-on:change="theme = $event.target.checked ? 'light' : 'dark'; localStorage.setItem('theme', theme); $el.closest('html').setAttribute('data-theme', theme)"
                x-bind:checked="theme === 'light'">
            <svg class="swap-on h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M5.64,17l-.71.71a1,1,0,0,0,0,1.41,1,1,0,0,0,1.41,0l.71-.71A1,1,0,0,0,5.64,17ZM5,12a1,1,0,0,0-1-1H3a1,1,0,0,0,0,2H4A1,1,0,0,0,5,12Zm7-7a1,1,0,0,0,1-1V3a1,1,0,0,0-2,0V4A1,1,0,0,0,12,5ZM5.64,7,5,6.36a1,1,0,0,0-1.41,1.41L4.22,8.46A1,1,0,0,0,5.64,7Zm12,.71.71-.71A1,1,0,0,0,16.93,5.64l-.71.71A1,1,0,0,0,17.66,7.66ZM21,11H20a1,1,0,0,0,0,2h1a1,1,0,0,0,0-2Zm-9,8a1,1,0,0,0-1,1v1a1,1,0,0,0,2,0V20A1,1,0,0,0,12,19ZM18.36,17A1,1,0,0,0,17,18.36l.71.71a1,1,0,0,0,1.41-1.41ZM12,6.5A5.5,5.5,0,1,0,17.5,12,5.51,5.51,0,0,0,12,6.5Zm0,9A3.5,3.5,0,1,1,15.5,12,3.5,3.5,0,0,1,12,15.5Z"/></svg>
            <svg class="swap-off h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M21.64,13a1,1,0,0,0-1.05-.14,8.05,8.05,0,0,1-3.37.73A8.15,8.15,0,0,1,9.08,5.49a8.59,8.59,0,0,1,.25-2A1,1,0,0,0,8,2.36,10.14,10.14,0,1,0,22,14.05,1,1,0,0,0,21.64,13Zm-9.5,6.69A8.14,8.14,0,0,1,7.08,5.22v.27A10.15,10.15,0,0,0,17.22,15.63a9.79,9.79,0,0,0,2.1-.22A8.11,8.11,0,0,1,12.14,19.73Z"/></svg>
        </label>
    </div>

    <!-- Ambient blobs -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="blob absolute -top-20 -left-20 w-80 h-80 rounded-full bg-primary/20 blur-3xl"></div>
        <div class="blob-2 absolute top-1/2 -right-20 w-64 h-64 rounded-full bg-secondary/15 blur-3xl"></div>
        <div class="blob absolute bottom-20 left-1/4 w-48 h-48 rounded-full bg-accent/10 blur-3xl"></div>
    </div>

    <!-- Hero -->
    <div class="relative min-h-screen flex flex-col justify-center items-center px-6 text-center">

        <div class="fade-up mb-6">
            <div class="w-16 h-16 rounded-2xl bg-primary flex items-center justify-center mx-auto shadow-lg shadow-primary/30">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-primary-content" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
            </div>
        </div>

        <h1 class="fade-up-1 text-5xl font-bold tracking-tight mb-3">
            <span class="mono text-primary">task</span><span class="mono">flow</span>
        </h1>

        <p class="fade-up-2 text-base-content/60 text-lg font-light max-w-xs leading-relaxed mb-10">
            Test Task Management PWA.<br>With NativePHP & Jump
        </p>

        <div class="fade-up-3 flex flex-wrap gap-2 justify-center mb-10">
            <span class="badge badge-outline badge-lg gap-1 font-normal">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                Fast
            </span>
            <span class="badge badge-outline badge-lg gap-1 font-normal">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                Mobile-first
            </span>
            <span class="badge badge-outline badge-lg gap-1 font-normal">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                Organised
            </span>
        </div>

        <div class="fade-up-4 flex flex-col gap-3 w-full max-w-xs">
            @if (Route::has('login'))
                @auth
                    <a href="{{ url('/tasks') }}" class="btn btn-primary btn-lg w-full shadow-lg shadow-primary/20">
                        Go to Tasks
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary btn-lg w-full shadow-lg shadow-primary/20">
                        Sign In
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                    </a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="btn btn-ghost btn-lg w-full">Create Account</a>
                    @endif
                @endauth
            @else
                <a href="{{ url('/tasks') }}" class="btn btn-primary btn-lg w-full shadow-lg shadow-primary/20">
                    Open Tasks
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                </a>
            @endif
        </div>

        <p class="absolute bottom-8 text-base-content/30 text-xs mono">
            Laravel v{{ Illuminate\Foundation\Application::VERSION }} · PHP v{{ PHP_VERSION }}
        </p>
    </div>

<script src="/js/alpine.js" defer></script>
</body>
</html>
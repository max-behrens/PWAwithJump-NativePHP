<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>@yield('title', 'TaskFlow')</title>
    <link href="/css/daisyui-themes.css" rel="stylesheet">
    <link href="/css/daisyui.css" rel="stylesheet">
    <script src="/js/tailwind.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'DM Sans', ui-sans-serif, system-ui, sans-serif; }
        .mono { font-family: 'DM Mono', ui-monospace, monospace; }
        .glass-nav { backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-in { animation: slideUp 0.3s ease forwards; }
        .animate-in-2 { animation: slideUp 0.3s ease 0.08s both; }
        .animate-in-3 { animation: slideUp 0.3s ease 0.16s both; }
        .animate-in-delay { animation: slideUp 0.3s ease 0.1s both; }
        .animate-in-delay-2 { animation: slideUp 0.3s ease 0.2s both; }
        .task-card { animation: slideUp 0.25s ease forwards; }
        .task-card:nth-child(1) { animation-delay: 0.05s; }
        .task-card:nth-child(2) { animation-delay: 0.1s; }
        .task-card:nth-child(3) { animation-delay: 0.15s; }
        .task-card:nth-child(4) { animation-delay: 0.2s; }
        .task-card:nth-child(5) { animation-delay: 0.25s; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-base-200 min-h-screen"
    x-data="{ theme: localStorage.getItem('theme') || 'dark' }"
    x-init="$el.closest('html').setAttribute('data-theme', theme)"
    x-cloak>

    <div class="navbar glass-nav bg-base-100/80 sticky top-0 z-50 border-b border-base-300/50 px-2">
        <div class="navbar-start">
            @yield('back_button')
         <a href="{{ url('/') }}" class="font-bold text-lg mono btn btn-ghost px-2 gap-0 tracking-tight">
                <span class="text-primary">task</span>flow
            </a>
        </div>
        <div class="navbar-end">
            <a href="{{ route('ai.index') }}" class="btn btn-ghost btn-sm btn-circle" title="AI">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" /></svg>
</a>
 <button class="btn btn-ghost btn-sm btn-circle"
                x-on:click="theme = theme === 'dark' ? 'light' : 'dark'; localStorage.setItem('theme', theme); $el.closest('html').setAttribute('data-theme', theme)">
                <svg x-show="theme === 'dark'" class="h-5 w-5 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M21.64,13a1,1,0,0,0-1.05-.14,8.05,8.05,0,0,1-3.37.73A8.15,8.15,0,0,1,9.08,5.49a8.59,8.59,0,0,1,.25-2A1,1,0,0,0,8,2.36,10.14,10.14,0,1,0,22,14.05,1,1,0,0,0,21.64,13Zm-9.5,6.69A8.14,8.14,0,0,1,7.08,5.22v.27A10.15,10.15,0,0,0,17.22,15.63a9.79,9.79,0,0,0,2.1-.22A8.11,8.11,0,0,1,12.14,19.73Z"/></svg>
                <svg x-show="theme === 'light'" class="h-5 w-5 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M5.64,17l-.71.71a1,1,0,0,0,0,1.41,1,1,0,0,0,1.41,0l.71-.71A1,1,0,0,0,5.64,17ZM5,12a1,1,0,0,0-1-1H3a1,1,0,0,0,0,2H4A1,1,0,0,0,5,12Zm7-7a1,1,0,0,0,1-1V3a1,1,0,0,0-2,0V4A1,1,0,0,0,12,5ZM5.64,7,5,6.36a1,1,0,0,0-1.41,1.41L4.22,8.46A1,1,0,0,0,5.64,7Zm12,.71.71-.71A1,1,0,0,0,16.93,5.64l-.71.71A1,1,0,0,0,17.66,7.66ZM21,11H20a1,1,0,0,0,0,2h1a1,1,0,0,0,0-2Zm-9,8a1,1,0,0,0-1,1v1a1,1,0,0,0,2,0V20A1,1,0,0,0,12,19ZM18.36,17A1,1,0,0,0,17,18.36l.71.71a1,1,0,0,0,1.41-1.41ZM12,6.5A5.5,5.5,0,1,0,17.5,12,5.51,5.51,0,0,0,12,6.5Zm0,9A3.5,3.5,0,1,1,15.5,12,3.5,3.5,0,0,1,12,15.5Z"/></svg>
            </button>
        </div>
    </div>

    <div class="@yield('container_class', 'max-w-lg mx-auto px-4 py-6 pb-28')">
        @yield('content')
    </div>

    @yield('fab')

    <script src="/js/alpine.js" defer></script>
</body>
</html>
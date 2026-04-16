@props(['title' => 'Rajón', 'fullWidth' => false])
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Rajón' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 font-sans" style="padding-top: 3.5rem;">

    {{-- Horní lišta --}}
    <header class="bg-white shadow-sm fixed top-0 left-0 right-0 z-50">
        <nav class="px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                @auth
                    <button onclick="toggleSidebar()" class="text-gray-500 hover:text-gray-700 transition" title="Menu">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                @endauth

                <a href="/" class="flex items-center gap-2">
                    <span class="text-xl font-bold" style="color: var(--c-primary);">Rajón</span>
                </a>

                <div class="hidden md:flex items-center gap-4 ml-6">
                    <a href="{{ url('/akce') }}" class="text-sm font-medium {{ request()->is('akce*') ? 'text-primary' : 'text-gray-500 hover:text-gray-700' }} transition">Akce</a>
                    <a href="{{ url('/mapa') }}" class="text-sm font-medium {{ request()->is('mapa') ? 'text-primary' : 'text-gray-500 hover:text-gray-700' }} transition">Mapa</a>
                </div>
            </div>

            <div class="flex items-center gap-3">
                @guest
                    <a href="{{ route('login') }}" class="text-sm text-gray-600 hover:text-primary transition">Přihlásit</a>
                    <a href="{{ route('register') }}" class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary-dark transition">Registrace</a>
                @endguest
            </div>
        </nav>
    </header>

    <div class="flex relative z-0">
        @include('partials.sidebar')

        <main class="flex-1 {{ $fullWidth ? 'px-2 py-2' : 'max-w-6xl px-4 py-8 mx-auto' }}">
            {{-- Flash messages --}}
            @if(session('success'))
                <div class="mb-4 rounded-lg bg-green-50 border border-green-200 p-4 text-sm text-green-700">{{ session('success') }}</div>
            @endif
            @if(session('info'))
                <div class="mb-4 rounded-lg bg-blue-50 border border-blue-200 p-4 text-sm text-blue-700">{{ session('info') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-4 rounded-lg bg-red-50 border border-red-200 p-4 text-sm text-red-700">{{ session('error') }}</div>
            @endif

            {{ $slot }}
        </main>
    </div>

    @include('partials.footer')
</body>
</html>

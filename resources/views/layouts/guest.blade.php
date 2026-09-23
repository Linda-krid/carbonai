<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-[#0f1f3a] antialiased">
        @if (trim($__env->yieldContent('guest-layout')) === 'split')
            {{ $slot }}
        @else
            <div class="flex min-h-screen items-center justify-center bg-[linear-gradient(120deg,#0f2a3f_0%,#0a3a34_100%)] px-4 py-10">
                <main class="w-full @yield('guest-width', 'max-w-sm')">
                    <div class="mb-7 text-center text-white">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-[#16bd83] shadow-lg shadow-emerald-950/30">
                            <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M19.5 4.5c-7.2.7-11 4.2-11 10.4 0 3 2 4.6 4.7 4.6 5.2 0 7.6-5.4 6.3-15Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M5 19c2.6-4.7 6.1-7.4 10.7-8.1" stroke="white" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <div class="mt-5 text-2xl font-extrabold">CarbonAI</div>
                        <div class="mt-2 text-sm font-medium text-[#9bb0c5]">@yield('guest-subtitle', 'Empreinte carbone des entreprises.')</div>
                    </div>

                    <div class="rounded-2xl bg-white p-8 shadow-2xl shadow-slate-950/25">
                        {{ $slot }}
                    </div>
                </main>
            </div>
        @endif
    </body>
</html>

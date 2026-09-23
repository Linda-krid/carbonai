<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', config('app.name', 'Laravel'))</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#f3f6fa] font-sans text-[#0f1f3a] antialiased">
        @php
            $isAdmin = auth()->user()?->isAdmin();
            $headerContext = $isAdmin ? 'Administration' : 'Utilisateur';
            $navLink = 'group flex items-center justify-between rounded-xl px-4 py-3 text-sm font-semibold transition';
            $navActive = 'bg-[#16bd83] text-white shadow-lg shadow-emerald-900/20';
            $navIdle = 'text-[#9fb0ca] hover:bg-white/10 hover:text-white';
            $navIcon = 'mr-3 inline-flex h-5 w-5 shrink-0 items-center justify-center opacity-80';
            $sidebarIcons = [
                'admin_dashboard' => '<path d="M4 5a1 1 0 0 1 1-1h5v7H4V5Z"/><path d="M14 4h5a1 1 0 0 1 1 1v4h-6V4Z"/><path d="M4 15h6v5H5a1 1 0 0 1-1-1v-4Z"/><path d="M14 13h6v6a1 1 0 0 1-1 1h-5v-7Z"/>',
                'users' => '<path d="M16 21v-2a4 4 0 0 0-8 0v2"/><path d="M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/><path d="M20 20v-1.5a3 3 0 0 0-2-2.8"/><path d="M18 5a3 3 0 0 1 0 5.5"/>',
                'factors' => '<path d="M4 6h9"/><path d="M17 6h3"/><path d="M4 12h3"/><path d="M11 12h9"/><path d="M4 18h11"/><path d="M19 18h1"/><path d="M13 4v4"/><path d="M7 10v4"/><path d="M15 16v4"/>',
                'configuration' => '<path d="M5 4h14a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1Z"/><path d="M12 8v8"/><path d="M8 12h8"/>',
                'formulaire' => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8Z"/><path d="M14 3v5h5"/><path d="M8 13h8"/><path d="M8 17h5"/>',
                'resultats' => '<path d="M4 19V5"/><path d="M4 19h16"/><path d="M8 16v-5"/><path d="M12 16V8"/><path d="M16 16v-3"/>',
                'rapport' => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8Z"/><path d="M14 3v5h5"/><path d="M9 17v-4"/><path d="M12 17v-7"/><path d="M15 17v-2"/>',
                'recommandations' => '<path d="M9 18h6"/><path d="M10 22h4"/><path d="M8.5 14.5A6 6 0 1 1 15.5 14c-.9.7-1.5 1.8-1.5 3h-4c0-1.1-.5-1.9-1.5-2.5Z"/>',
                'historique' => '<path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/><path d="M12 7v5l3 2"/>',
                'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/>',
            ];
        @endphp

        <div class="min-h-screen lg:flex">
            <aside class="border-b border-[#223f70] bg-[#213d70] lg:fixed lg:inset-y-0 lg:left-0 lg:w-[240px] lg:border-b-0 lg:border-r lg:border-[#294777]">
                <div class="flex h-full flex-col">
                    <div class="flex items-center gap-3 border-b border-white/10 px-6 py-6">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#16bd83] text-white shadow-lg shadow-emerald-950/25">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M19.5 4.5c-7.5.4-11.8 3.7-12.7 9.7-.4 2.7 1.2 4.8 3.8 5.2 5.9.8 9.1-4 8.9-14.9Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M5 19c3.5-5.1 7.4-7.7 11.8-7.9" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <div>
                            <div class="text-lg font-bold text-white">CarbonAI</div>
                            <div class="text-xs font-medium text-[#9fb0ca]">{{ $isAdmin ? 'Administration' : 'Espace entreprise' }}</div>
                        </div>
                    </div>

                    <nav class="grid gap-2 px-3 py-6">
                        @if ($isAdmin)
                            <div class="mb-2 rounded-xl border border-white/15 bg-white/5 px-4 py-2 text-xs font-semibold text-[#b7c5dc]">Espace administrateur</div>
                            <a href="{{ route('admin.dashboard') }}" class="{{ $navLink }} {{ request()->routeIs('admin.dashboard') ? $navActive : $navIdle }}">
                                <span class="flex items-center">
                                    <span class="{{ $navIcon }}">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true" data-sidebar-icon="admin-dashboard">
                                            <g stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $sidebarIcons['admin_dashboard'] !!}</g>
                                        </svg>
                                    </span>
                                    Tableau de bord
                                </span>
                                <span class="text-xs opacity-70">›</span>
                            </a>
                            <a href="{{ route('admin.users.index') }}" class="{{ $navLink }} {{ request()->routeIs('admin.users.*') ? $navActive : $navIdle }}">
                                <span class="flex items-center">
                                    <span class="{{ $navIcon }}">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true" data-sidebar-icon="users">
                                            <g stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $sidebarIcons['users'] !!}</g>
                                        </svg>
                                    </span>
                                    Utilisateurs
                                </span>
                            </a>
                            <a href="{{ route('admin.facteurs.index') }}" class="{{ $navLink }} {{ request()->routeIs('admin.facteurs.*') ? $navActive : $navIdle }}">
                                <span class="flex items-center">
                                    <span class="{{ $navIcon }}">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true" data-sidebar-icon="factors">
                                            <g stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $sidebarIcons['factors'] !!}</g>
                                        </svg>
                                    </span>
                                    Facteurs d'émission
                                </span>
                            </a>
                        @else
                            <a href="{{ route('configuration.index') }}" class="{{ $navLink }} {{ request()->routeIs('configuration.*') ? $navActive : $navIdle }}">
                                <span class="flex items-center">
                                    <span class="{{ $navIcon }}">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true" data-sidebar-icon="configuration">
                                            <g stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $sidebarIcons['configuration'] !!}</g>
                                        </svg>
                                    </span>
                                    Nouveau calcul
                                </span>
                                <span class="text-xs opacity-70">›</span>
                            </a>
                            <a href="{{ route('formulaires.index') }}" class="{{ $navLink }} {{ request()->routeIs('formulaires.*') ? $navActive : $navIdle }}">
                                <span class="flex items-center">
                                    <span class="{{ $navIcon }}">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true" data-sidebar-icon="formulaire">
                                            <g stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $sidebarIcons['formulaire'] !!}</g>
                                        </svg>
                                    </span>
                                    Formulaire généré
                                </span>
                            </a>
                            <a href="{{ route('resultats.index') }}" class="{{ $navLink }} {{ request()->routeIs('resultats.*') ? $navActive : $navIdle }}">
                                <span class="flex items-center">
                                    <span class="{{ $navIcon }}">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true" data-sidebar-icon="resultats">
                                            <g stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $sidebarIcons['resultats'] !!}</g>
                                        </svg>
                                    </span>
                                    Résultats
                                </span>
                            </a>
                            <a href="{{ route('rapports.index') }}" class="{{ $navLink }} {{ request()->routeIs('rapports.*') ? $navActive : $navIdle }}">
                                <span class="flex items-center">
                                    <span class="{{ $navIcon }}">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true" data-sidebar-icon="rapport">
                                            <g stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $sidebarIcons['rapport'] !!}</g>
                                        </svg>
                                    </span>
                                    Rapport
                                </span>
                            </a>
                            <a href="{{ route('recommandations.index') }}" class="{{ $navLink }} {{ request()->routeIs('recommandations.*') ? $navActive : $navIdle }}">
                                <span class="flex items-center">
                                    <span class="{{ $navIcon }}">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true" data-sidebar-icon="recommandations">
                                            <g stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $sidebarIcons['recommandations'] !!}</g>
                                        </svg>
                                    </span>
                                    Recommandations
                                </span>
                            </a>
                            <a href="{{ route('historique.index') }}" class="{{ $navLink }} {{ request()->routeIs('historique.*') ? $navActive : $navIdle }}">
                                <span class="flex items-center">
                                    <span class="{{ $navIcon }}">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true" data-sidebar-icon="historique">
                                            <g stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $sidebarIcons['historique'] !!}</g>
                                        </svg>
                                    </span>
                                    Historique
                                </span>
                            </a>
                        @endif
                    </nav>

                    <div class="mt-auto border-t border-white/10 p-4">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="flex w-full items-center rounded-xl px-3 py-3 text-left text-sm font-semibold text-[#9fb0ca] transition hover:bg-white/10 hover:text-white">
                                <span class="{{ $navIcon }}">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true" data-sidebar-icon="logout">
                                        <g stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $sidebarIcons['logout'] !!}</g>
                                    </svg>
                                </span>
                                <span>Déconnexion</span>
                            </button>
                        </form>
                    </div>
                </div>
            </aside>

            <div class="flex min-h-screen flex-1 flex-col lg:pl-[240px]">
                <header class="sticky top-0 z-20 border-b border-slate-200 bg-white px-4 py-3 shadow-sm sm:px-8">
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3 text-sm">
                            <span class="font-bold text-[#0f1f3a]">CarbonAI</span>
                            <span class="text-slate-300">/</span>
                            <span class="font-medium text-slate-500">{{ $headerContext }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-[#183768] text-white shadow-sm">
                                    <span class="text-xs font-bold">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                                </div>
                                <div class="hidden sm:block">
                                    <div class="text-sm font-bold text-[#0f1f3a]">{{ $isAdmin ? 'Administrateur' : auth()->user()->name }}</div>
                                    @unless ($isAdmin)
                                        <div class="text-xs text-slate-500">{{ auth()->user()->email }}</div>
                                    @endunless
                                </div>
                            </div>
                        </div>
                    </div>
                </header>

                <main class="flex-1 px-4 py-8 sm:px-8 lg:px-8 xl:px-12">
                    @hasSection('hide-page-heading')
                    @else
                        <div class="mx-auto mb-6 flex w-full max-w-7xl flex-col gap-4 sm:flex-row sm:items-start sm:justify-between @yield('page-heading-class')">
                            <div>
                                @hasSection('eyebrow')
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">@yield('eyebrow')</p>
                                @endif
                                <h1 class="text-3xl font-bold tracking-tight text-[#0f1f3a]">@yield('page-title')</h1>
                                @hasSection('page-subtitle')
                                    <p class="mt-2 text-sm text-slate-600">@yield('page-subtitle')</p>
                                @endif
                            </div>
                            @yield('header-actions')
                        </div>
                    @endif

                    @php
                        $flashMessages = collect([
                            'success' => session('success') ?? session('status'),
                            'error' => session('error'),
                            'warning' => session('warning'),
                        ])->filter(fn ($message) => filled($message));

                        $flashStyles = [
                            'success' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
                            'error' => 'border-rose-200 bg-rose-50 text-rose-800',
                            'warning' => 'border-amber-200 bg-amber-50 text-amber-800',
                        ];
                    @endphp

                    @if ($flashMessages->isNotEmpty())
                        <div class="mx-auto mb-5 max-w-7xl space-y-3">
                            @foreach ($flashMessages as $flashType => $flashMessage)
                                <div class="rounded-xl border px-4 py-3 text-sm font-semibold {{ $flashStyles[$flashType] }}">
                                    {{ is_scalar($flashMessage) ? $flashMessage : json_encode($flashMessage, JSON_UNESCAPED_UNICODE) }}
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @yield('content')
                </main>
            </div>
        </div>
    </body>
</html>

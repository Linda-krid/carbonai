@php
    $initialMode = $initialMode ?? 'login';
@endphp

@once
    <style>
        @keyframes carbonPulse {
            0%, 100% { transform: scale(1); opacity: .78; }
            50% { transform: scale(1.05); opacity: 1; }
        }

        @keyframes carbonFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        @keyframes carbonDash {
            to { stroke-dashoffset: 0; }
        }

        @keyframes carbonNeedle {
            0%, 100% { transform: rotate(-14deg); }
            50% { transform: rotate(10deg); }
        }

        @keyframes carbonRise {
            0%, 100% { transform: translateY(0); opacity: .55; }
            50% { transform: translateY(-14px); opacity: .95; }
        }

        @keyframes carbonFlow {
            0% { transform: translateX(-8px); opacity: .25; }
            50% { opacity: 1; }
            100% { transform: translateX(42px); opacity: .25; }
        }

        .carbon-orbit {
            animation: carbonPulse 4s ease-in-out infinite;
            transform-origin: center;
        }

        .carbon-float {
            animation: carbonFloat 4.5s ease-in-out infinite;
        }

        .carbon-float-delayed {
            animation: carbonFloat 5s ease-in-out infinite;
            animation-delay: .9s;
        }

        .carbon-dash {
            stroke-dasharray: 220;
            stroke-dashoffset: 220;
            animation: carbonDash 2.8s ease-out forwards;
        }

        .carbon-needle {
            transform-box: fill-box;
            transform-origin: 50% 100%;
            animation: carbonNeedle 5s ease-in-out infinite;
        }

        .carbon-rise {
            animation: carbonRise 4.8s ease-in-out infinite;
        }

        .carbon-rise:nth-child(2) { animation-delay: .7s; }
        .carbon-rise:nth-child(3) { animation-delay: 1.4s; }

        .carbon-flow-dot {
            animation: carbonFlow 3.2s ease-in-out infinite;
        }

        .carbon-flow-dot:nth-child(2) { animation-delay: .8s; }
        .carbon-flow-dot:nth-child(3) { animation-delay: 1.6s; }

        .carbon-auth-shell {
            min-height: 100vh;
            position: relative;
            overflow: hidden;
            background: #ffffff;
            color: #0f1f3a;
        }

        .carbon-auth-visual,
        .carbon-auth-panel {
            position: absolute;
            top: 0;
            bottom: 0;
            width: 50%;
            min-height: 100vh;
            transition: transform .65s cubic-bezier(.22, 1, .36, 1);
            will-change: transform;
        }

        .carbon-auth-visual {
            left: 0;
            display: flex !important;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: linear-gradient(135deg, #edf7f3 0%, #f3f8fb 48%, #eaf1fb 100%);
            padding: 48px;
        }

        .carbon-auth-panel {
            left: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-y: auto;
            background: #ffffff;
            padding: 48px 64px;
        }

        .carbon-auth-shell[data-mode="login"] .carbon-auth-visual {
            transform: translateX(100%);
        }

        .carbon-auth-shell[data-mode="login"] .carbon-auth-panel {
            transform: translateX(-100%);
        }

        .carbon-auth-shell[data-mode="register"] .carbon-auth-visual,
        .carbon-auth-shell[data-mode="register"] .carbon-auth-panel {
            transform: translateX(0);
        }

        .carbon-auth-card {
            width: 100%;
            max-width: 430px;
        }

        .carbon-auth-card input,
        .carbon-auth-card select {
            width: 100%;
            height: 48px;
            border: 0;
            border-radius: 10px;
            background: #eef3f8;
            padding: 0 16px;
            color: #0f1f3a;
            font-size: 14px;
            font-weight: 600;
            outline: none;
            box-shadow: 0 1px 2px rgba(15, 31, 58, .06);
        }

        .carbon-auth-card input:focus,
        .carbon-auth-card select:focus {
            background: #ffffff;
            box-shadow: 0 0 0 2px #16bd83;
        }

        .carbon-auth-card label {
            display: block;
            color: #152342;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .carbon-auth-card form {
            margin-top: 32px;
        }

        .carbon-auth-card form > * + * {
            margin-top: 20px;
        }

        .carbon-auth-card form > .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .carbon-auth-field {
            margin-top: 8px;
        }

        .carbon-auth-card button[type="submit"],
        .carbon-auth-primary {
            width: 100%;
            height: 48px;
            border: 0;
            border-radius: 10px;
            background: #213d70;
            color: #ffffff;
            font-size: 14px;
            font-weight: 800;
            box-shadow: 0 14px 24px rgba(33, 61, 112, .20);
            cursor: pointer;
            transition: background .18s ease, transform .18s ease;
        }

        .carbon-auth-card button[type="submit"]:hover,
        .carbon-auth-primary:hover {
            background: #183768;
            transform: translateY(-1px);
        }

        .carbon-auth-switch {
            border: 0;
            background: transparent;
            padding: 0;
            color: #16a875;
            font-weight: 800;
            cursor: pointer;
        }

        .carbon-auth-switch:hover {
            color: #0d8b63;
        }

        @media (max-width: 1023px) {
            .carbon-auth-shell {
                display: block;
                overflow: auto;
            }

            .carbon-auth-visual {
                display: none !important;
            }

            .carbon-auth-panel {
                position: relative;
                inset: auto;
                width: 100%;
                min-height: 100vh;
                transform: none !important;
                padding: 32px 24px;
            }

            .carbon-auth-card form > .grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endonce

<div
    x-data="{
        mode: @js($initialMode),
        switchMode(next) {
            this.mode = next;
            window.history.replaceState({}, '', next === 'register' ? @js(route('register')) : @js(route('login')));
        }
    }"
    data-mode="{{ $initialMode }}"
    x-bind:data-mode="mode"
    class="carbon-auth-shell"
>
    <section class="carbon-auth-visual">
        <div class="absolute left-8 top-8 inline-flex items-center gap-3">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#16bd83] text-white shadow-lg shadow-emerald-900/15">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M19.5 4.5c-7.2.7-11 4.2-11 10.4 0 3 2 4.6 4.7 4.6 5.2 0 7.6-5.4 6.3-15Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M5 19c2.6-4.7 6.1-7.4 10.7-8.1" stroke="white" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </span>
            <span class="text-xl font-extrabold tracking-tight text-[#15345b]">CarbonAI</span>
        </div>

        <div class="absolute -left-12 bottom-0 h-48 w-48 rounded-full border-[18px] border-[#16bd83]/20"></div>
        <div class="absolute right-14 top-16 h-24 w-24 rounded-full bg-[#ddf6ec]"></div>
        <div class="absolute bottom-24 right-24 h-36 w-36 rounded-full bg-[#dfe7f4]"></div>

        <div class="relative w-full max-w-3xl">
            <svg class="mx-auto block h-auto w-full max-w-[700px]" viewBox="0 0 760 520" fill="none" aria-hidden="true">
                <rect x="80" y="78" width="600" height="360" rx="44" fill="#ffffff" opacity=".52"/>
                <ellipse cx="382" cy="398" rx="260" ry="62" fill="#dce8f4" opacity=".62"/>

                <g class="carbon-rise">
                    <circle cx="170" cy="130" r="26" fill="#e3f7ef"/>
                    <text x="170" y="136" text-anchor="middle" font-size="18" font-weight="800" fill="#213d70">CO2e</text>
                </g>
                <g class="carbon-rise">
                    <circle cx="590" cy="112" r="18" fill="#dce8f4"/>
                    <path d="M584 113h12M590 107v12" stroke="#16bd83" stroke-width="3" stroke-linecap="round"/>
                </g>
                <g class="carbon-rise">
                    <circle cx="648" cy="300" r="21" fill="#e3f7ef"/>
                    <path d="M638 301c8 8 18 8 26-5" stroke="#16bd83" stroke-width="4" stroke-linecap="round"/>
                </g>

                <path class="carbon-dash" d="M185 285C250 248 318 246 380 271C448 299 505 287 578 242" stroke="#16bd83" stroke-opacity=".65" stroke-width="5" stroke-linecap="round"/>
                <g>
                    <circle class="carbon-flow-dot" cx="228" cy="269" r="6" fill="#16bd83"/>
                    <circle class="carbon-flow-dot" cx="338" cy="260" r="6" fill="#213d70"/>
                    <circle class="carbon-flow-dot" cx="492" cy="275" r="6" fill="#16bd83"/>
                </g>

                <g filter="url(#shadow)">
                    <rect x="128" y="283" width="230" height="116" rx="18" fill="#ffffff"/>
                    <path d="M151 399V338l46-23v84" fill="#213d70" opacity=".96"/>
                    <path d="M197 399v-75l52 25v50" fill="#2a4a83" opacity=".95"/>
                    <path d="M249 399v-96h58v96" fill="#183768"/>
                    <rect x="159" y="350" width="16" height="16" rx="3" fill="#e7f5f0"/>
                    <rect x="204" y="354" width="16" height="16" rx="3" fill="#e7f5f0"/>
                    <rect x="270" y="324" width="14" height="18" rx="3" fill="#e7f5f0"/>
                    <rect x="292" y="324" width="14" height="18" rx="3" fill="#e7f5f0"/>
                    <path d="M276 303V247h26v56" fill="#213d70"/>
                    <path d="M296 247c20-20 42-18 55 4" stroke="#c9d3e3" stroke-width="11" stroke-linecap="round"/>
                    <path d="M321 237c17-18 39-16 51 5" stroke="#e3f7ef" stroke-width="13" stroke-linecap="round"/>
                    <path d="M141 399h206" stroke="#16bd83" stroke-width="5" stroke-linecap="round"/>
                </g>

                <g filter="url(#shadow)">
                    <rect x="380" y="186" width="166" height="166" rx="32" fill="#ffffff"/>
                    <path d="M424 286a52 52 0 1 1 78-1" stroke="#dce5ef" stroke-width="15" stroke-linecap="round"/>
                    <path d="M424 286a52 52 0 0 1 31-74" stroke="#16bd83" stroke-width="15" stroke-linecap="round"/>
                    <path d="M463 269l32-35" stroke="#213d70" stroke-width="7" stroke-linecap="round"/>
                    <circle cx="463" cy="269" r="9" fill="#213d70"/>
                    <g class="carbon-needle">
                        <path d="M463 269L494 225" stroke="#16bd83" stroke-width="5" stroke-linecap="round"/>
                    </g>
                    <text x="463" y="319" text-anchor="middle" font-size="19" font-weight="800" fill="#213d70">CO2e</text>
                    <text x="463" y="340" text-anchor="middle" font-size="11" font-weight="700" fill="#6d7f95">calcul vérifié</text>
                </g>

                <g class="carbon-float" filter="url(#shadow)">
                    <rect x="136" y="145" width="192" height="74" rx="17" fill="#ffffff"/>
                    <circle cx="168" cy="182" r="13" fill="#16bd83"/>
                    <rect x="194" y="166" width="94" height="8" rx="4" fill="#213d70"/>
                    <rect x="194" y="188" width="116" height="8" rx="4" fill="#c9d3e3"/>
                    <text x="194" y="210" font-size="11" font-weight="800" fill="#6d7f95">Facteurs actifs</text>
                </g>

                <g class="carbon-float-delayed" filter="url(#shadow)">
                    <rect x="522" y="145" width="158" height="82" rx="17" fill="#ffffff"/>
                    <rect x="545" y="169" width="44" height="44" rx="13" fill="#e3f7ef"/>
                    <path d="M558 191l8 8 16-18" stroke="#16bd83" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
                    <rect x="604" y="171" width="52" height="7" rx="3.5" fill="#213d70"/>
                    <rect x="604" y="193" width="64" height="7" rx="3.5" fill="#c9d3e3"/>
                    <text x="604" y="215" font-size="11" font-weight="800" fill="#6d7f95">Rapport vérifié</text>
                </g>

                <g class="carbon-float" filter="url(#shadow)">
                    <rect x="495" y="355" width="174" height="86" rx="18" fill="#ffffff"/>
                    <text x="520" y="382" font-size="12" font-weight="800" fill="#213d70">Objectif réduction</text>
                    <path d="M520 418L552 395L585 408L640 373" stroke="#16bd83" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="640" cy="373" r="8" fill="#213d70"/>
                    <rect x="520" y="428" width="108" height="7" rx="3.5" fill="#dce5ef"/>
                    <rect x="520" y="428" width="72" height="7" rx="3.5" fill="#16bd83"/>
                </g>

                <defs>
                    <filter id="shadow" x="95" y="110" width="620" height="370" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
                        <feDropShadow dx="0" dy="14" stdDeviation="14" flood-color="#0f1f3a" flood-opacity=".14"/>
                    </filter>
                </defs>
            </svg>

            <div class="mt-8 text-center">
                <p class="text-sm font-extrabold tracking-wide text-[#213d70]">Calcul carbone fiable pour entreprises industrielles</p>
                <p class="mx-auto mt-3 max-w-md text-sm leading-6 text-[#6d7f95]">
                    Des facteurs actifs, des sources traçables et des rapports carbone prêts à exploiter.
                </p>
            </div>
        </div>
    </section>

    <main class="carbon-auth-panel">
        <section class="carbon-auth-card">
            <div class="mb-10 text-center lg:hidden">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-[#16bd83] text-white shadow-lg shadow-emerald-900/15">
                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M19.5 4.5c-7.2.7-11 4.2-11 10.4 0 3 2 4.6 4.7 4.6 5.2 0 7.6-5.4 6.3-15Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M5 19c2.6-4.7 6.1-7.4 10.7-8.1" stroke="white" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </div>
                <div class="mt-4 text-2xl font-extrabold text-[#15345b]">CarbonAI</div>
            </div>

            <div x-cloak x-show="mode === 'login'" x-transition.opacity.duration.200ms>
                <div class="text-center">
                    <h1 class="text-3xl font-extrabold tracking-tight text-[#15345b]">
                        Bienvenue sur <span class="text-[#16a875]">CarbonAI</span>
                    </h1>
                    <p class="mt-4 text-sm font-bold text-[#0f1f3a]">Connexion à votre espace</p>
                    <p class="mt-2 text-sm text-slate-500">Suivez vos calculs, rapports et recommandations carbone.</p>
                </div>

                <x-auth-session-status class="mt-6" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
                    @csrf

                    <div>
                        <label for="login-email" class="block text-xs font-extrabold uppercase tracking-wide text-[#152342]">Email</label>
                        <input id="login-email" class="mt-2 block h-12 w-full rounded-lg border-0 bg-[#eaf1fb] px-4 text-sm font-semibold text-[#0f1f3a] shadow-sm placeholder:text-[#7d8da5] focus:bg-white focus:ring-2 focus:ring-[#16bd83]" type="email" name="email" value="{{ old('email') }}" placeholder="email@entreprise.tn" required autofocus autocomplete="username">
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <div class="flex items-center justify-between">
                            <label for="login-password" class="block text-xs font-extrabold uppercase tracking-wide text-[#152342]">Mot de passe</label>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="text-xs font-bold text-[#213d70] hover:text-[#16a875]">Oublié ?</a>
                            @endif
                        </div>
                        <input id="login-password" class="mt-2 block h-12 w-full rounded-lg border-0 bg-[#eaf1fb] px-4 text-sm font-semibold text-[#0f1f3a] shadow-sm placeholder:text-[#7d8da5] focus:bg-white focus:ring-2 focus:ring-[#16bd83]" type="password" name="password" placeholder="Mot de passe" required autocomplete="current-password">
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <button type="submit" class="flex h-12 w-full items-center justify-center rounded-lg bg-[#213d70] px-4 text-sm font-extrabold text-white shadow-lg shadow-blue-950/20 transition hover:bg-[#183768] focus:outline-none focus:ring-2 focus:ring-[#213d70] focus:ring-offset-2">
                        Se connecter
                    </button>

                    <p class="pt-2 text-center text-sm text-slate-500">
                        Pas encore de compte ?
                        <button type="button" class="font-extrabold text-[#16a875] hover:text-[#0d8b63]" x-on:click="switchMode('register')">Créer un compte</button>
                    </p>
                </form>
            </div>

            <div x-cloak x-show="mode === 'register'" x-transition.opacity.duration.200ms>
                <div class="text-center">
                    <h1 class="text-3xl font-extrabold tracking-tight text-[#15345b]">
                        Créer votre espace <span class="text-[#16a875]">CarbonAI</span>
                    </h1>
                    <p class="mt-4 text-sm font-bold text-[#0f1f3a]">Compte entreprise</p>
                    <p class="mt-2 text-sm text-slate-500">Configurez votre accès et démarrez votre premier calcul carbone.</p>
                </div>

                <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-4">
                    @csrf

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="register-name" class="block text-xs font-extrabold uppercase tracking-wide text-[#152342]">Nom</label>
                            <input id="register-name" class="mt-2 block h-11 w-full rounded-lg border-0 bg-[#eaf1fb] px-4 text-sm font-semibold text-[#0f1f3a] shadow-sm placeholder:text-[#7d8da5] focus:bg-white focus:ring-2 focus:ring-[#16bd83]" type="text" name="name" value="{{ old('name') }}" placeholder="Prénom Nom" required autocomplete="name">
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div>
                            <label for="register-email" class="block text-xs font-extrabold uppercase tracking-wide text-[#152342]">Email</label>
                            <input id="register-email" class="mt-2 block h-11 w-full rounded-lg border-0 bg-[#eaf1fb] px-4 text-sm font-semibold text-[#0f1f3a] shadow-sm placeholder:text-[#7d8da5] focus:bg-white focus:ring-2 focus:ring-[#16bd83]" type="email" name="email" value="{{ old('email') }}" placeholder="email@entreprise.tn" required autocomplete="username">
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="register-password" class="block text-xs font-extrabold uppercase tracking-wide text-[#152342]">Mot de passe</label>
                            <input id="register-password" class="mt-2 block h-11 w-full rounded-lg border-0 bg-[#eaf1fb] px-4 text-sm font-semibold text-[#0f1f3a] shadow-sm placeholder:text-[#7d8da5] focus:bg-white focus:ring-2 focus:ring-[#16bd83]" type="password" name="password" placeholder="Mot de passe" required autocomplete="new-password">
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>

                        <div>
                            <label for="register-password-confirmation" class="block text-xs font-extrabold uppercase tracking-wide text-[#152342]">Confirmation</label>
                            <input id="register-password-confirmation" class="mt-2 block h-11 w-full rounded-lg border-0 bg-[#eaf1fb] px-4 text-sm font-semibold text-[#0f1f3a] shadow-sm placeholder:text-[#7d8da5] focus:bg-white focus:ring-2 focus:ring-[#16bd83]" type="password" name="password_confirmation" placeholder="Confirmer" required autocomplete="new-password">
                            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <label for="nom_entreprise" class="block text-xs font-extrabold uppercase tracking-wide text-[#152342]">Entreprise</label>
                        <input id="nom_entreprise" class="mt-2 block h-11 w-full rounded-lg border-0 bg-[#eaf1fb] px-4 text-sm font-semibold text-[#0f1f3a] shadow-sm placeholder:text-[#7d8da5] focus:bg-white focus:ring-2 focus:ring-[#16bd83]" type="text" name="nom_entreprise" value="{{ old('nom_entreprise') }}" placeholder="Industrielle SA" required>
                        <x-input-error :messages="$errors->get('nom_entreprise')" class="mt-2" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="secteur_activite" class="block text-xs font-extrabold uppercase tracking-wide text-[#152342]">Secteur</label>
                            <select id="secteur_activite" name="secteur_activite" class="mt-2 block h-11 w-full rounded-lg border-0 bg-[#eaf1fb] px-4 text-sm font-semibold text-[#0f1f3a] shadow-sm focus:bg-white focus:ring-2 focus:ring-[#16bd83]" required>
                                <option value="">Sélectionner</option>
                                @foreach (['Industrie manufacturière', 'Agroalimentaire', 'Textile', 'BTP', 'Chimie', 'Autre'] as $sector)
                                    <option value="{{ $sector }}" @selected(old('secteur_activite') === $sector)>{{ $sector }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('secteur_activite')" class="mt-2" />
                        </div>

                        <div>
                            <label for="ville" class="block text-xs font-extrabold uppercase tracking-wide text-[#152342]">Ville</label>
                            <select id="ville" name="ville" class="mt-2 block h-11 w-full rounded-lg border-0 bg-[#eaf1fb] px-4 text-sm font-semibold text-[#0f1f3a] shadow-sm focus:bg-white focus:ring-2 focus:ring-[#16bd83]" required>
                                <option value="">Sélectionner</option>
                                @foreach (['Tunis', 'Sfax', 'Sousse', 'Bizerte', 'Kairouan', 'Autre'] as $city)
                                    <option value="{{ $city }}" @selected(old('ville') === $city)>{{ $city }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('ville')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <label for="pays" class="block text-xs font-extrabold uppercase tracking-wide text-[#152342]">Pays</label>
                        <input id="pays" class="mt-2 block h-11 w-full rounded-lg border-0 bg-slate-100 px-4 text-sm font-semibold text-[#0f1f3a] shadow-sm focus:ring-2 focus:ring-[#16bd83]" type="text" name="pays" value="{{ old('pays', 'Tunisie') }}" required>
                        <x-input-error :messages="$errors->get('pays')" class="mt-2" />
                    </div>

                    <button type="submit" class="flex h-12 w-full items-center justify-center rounded-lg bg-[#213d70] px-4 text-sm font-extrabold text-white shadow-lg shadow-blue-950/20 transition hover:bg-[#183768] focus:outline-none focus:ring-2 focus:ring-[#213d70] focus:ring-offset-2">
                        Créer un compte
                    </button>

                    <p class="pt-2 text-center text-sm text-slate-500">
                        Déjà inscrit ?
                        <button type="button" class="font-extrabold text-[#16a875] hover:text-[#0d8b63]" x-on:click="switchMode('login')">Se connecter</button>
                    </p>
                </form>
            </div>

            <p class="mt-10 text-center text-xs font-medium text-slate-400">© {{ now()->year }} CarbonAI. Tous droits réservés.</p>
        </section>
    </main>
</div>

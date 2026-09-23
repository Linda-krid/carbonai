@extends('layouts.dashboard')

@section('title', 'Nouveau calcul carbone')
@section('hide-page-heading', true)

@section('content')
    @php
        $defaultPosts = ['carburant', 'machines', 'transport', 'dechets', 'production'];
        $selectedPosts = old('postes_emission', $defaultPosts);
        $postPayload = collect($posts)->map(fn ($post, $key) => [
            'key' => $key,
            'label' => $post['label'],
            'unit' => $post['unit'] ?? '',
        ])->values();
        $postIcons = [
            'carburant' => '<path d="M6 20V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v15"/><path d="M7 9h8"/><path d="M16 8h2l2 2v7a2 2 0 0 1-4 0v-5"/>',
            'transport' => '<path d="M3 7h11v10H3z"/><path d="M14 11h4l3 3v3h-7z"/><path d="M7 20a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"/><path d="M17 20a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"/>',
            'batiment' => '<path d="M4 21V5a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v16"/><path d="M9 21v-4h3v4"/><path d="M8 7h1"/><path d="M12 7h1"/><path d="M8 11h1"/><path d="M12 11h1"/>',
            'transport_personnel' => '<path d="M5 17h14l-1.2-5.2A2.4 2.4 0 0 0 15.5 10h-7a2.4 2.4 0 0 0-2.3 1.8L5 17Z"/><path d="M7 17v2"/><path d="M17 17v2"/><path d="M8 14h.1"/><path d="M16 14h.1"/>',
            'machines' => '<path d="M12 8.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7Z"/><path d="M12 2v3"/><path d="M12 19v3"/><path d="m4.9 4.9 2.1 2.1"/><path d="m17 17 2.1 2.1"/><path d="M2 12h3"/><path d="M19 12h3"/><path d="m4.9 19.1 2.1-2.1"/><path d="m17 7 2.1-2.1"/>',
            'materiaux' => '<path d="M12 3 3.5 7.5 12 12l8.5-4.5L12 3Z"/><path d="M3.5 7.5V16L12 21l8.5-5V7.5"/><path d="M12 12v9"/>',
            'dechets' => '<path d="M5 7h14"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M7 7l1 14h8l1-14"/><path d="M9 7V4h6v3"/>',
            'voyages' => '<path d="M10.5 13.5 3 11l1.2-2 7.5.7 4.6-5.7a1.5 1.5 0 0 1 2.3 1.9l-3.7 6.3 4.1 4.7-1.5 1.5-5.7-3.1-3.2 4.1-2-.9 3.9-5Z"/>',
            'employes' => '<path d="M16 21v-2a4 4 0 0 0-8 0v2"/><path d="M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/><path d="M20 20v-1.5a3 3 0 0 0-2-2.8"/><path d="M18 5a3 3 0 0 1 0 5.5"/>',
            'production' => '<path d="M3 21V9l5 3V9l5 3V7h8v14H3Z"/><path d="M7 17h2"/><path d="M12 17h2"/><path d="M17 17h2"/>',
        ];
        $groups = [
            [
                'title' => 'Énergie et exploitation',
                'description' => 'Émissions liées à la consommation énergétique et aux équipements utilisés.',
                'accent' => 'orange',
                'items' => ['carburant', 'batiment', 'machines'],
            ],
            [
                'title' => 'Logistique et mobilité',
                'description' => 'Émissions liées aux déplacements, livraisons et transport.',
                'accent' => 'blue',
                'items' => ['transport', 'transport_personnel', 'voyages'],
            ],
            [
                'title' => 'Ressources et production',
                'description' => 'Émissions liées aux matières premières, déchets et procédés de fabrication.',
                'accent' => 'emerald',
                'items' => ['materiaux', 'dechets', 'employes', 'production'],
            ],
        ];
        $inputClass = 'mt-2 block w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-3 text-sm shadow-sm focus:border-[#16bd83] focus:ring-[#16bd83]';
        $defaultCountry = 'Tunisie';
        $entrepriseDefaults = array_merge([
            'nom' => '',
            'secteur_activite' => '',
            'pays' => $defaultCountry,
            'ville' => '',
            'nombre_employes' => '',
            'type_production' => '',
            'annee_calcul' => '',
        ], $entrepriseDefaults ?? []);
        $hasOldInput = session()->hasOldInput();
        $oldForm = [
            'nom' => old('nom', $entrepriseDefaults['nom']),
            'secteur_activite' => old('secteur_activite', $entrepriseDefaults['secteur_activite']),
            'pays' => old('pays', $entrepriseDefaults['pays'] ?: $defaultCountry),
            'ville' => old('ville', $entrepriseDefaults['ville']),
            'nombre_employes' => old('nombre_employes', $entrepriseDefaults['nombre_employes']),
            'type_production' => old('type_production', $entrepriseDefaults['type_production']),
            'annee_calcul' => old('annee_calcul', $entrepriseDefaults['annee_calcul']),
        ];
    @endphp

    <form
        method="POST"
        action="{{ route('configuration.store') }}"
        autocomplete="off"
        class="mx-auto max-w-6xl space-y-6"
        x-data="{
            preserveInput: @js($hasOldInput),
            selected: @js(array_values($selectedPosts)),
            posts: @js($postPayload),
            form: @js($oldForm),
            defaultForm: @js($oldForm),
            selectedCount() { return this.selected.length },
            isSelected(key) { return this.selected.includes(key) },
            postLabel(key) { return (this.posts.find((post) => post.key === key) || {}).label || key },
            resetPosts() { this.selected = [] },
            selectAllPosts() { this.selected = this.posts.map((post) => post.key) },
            display(value) { return value && String(value).length ? value : 'À compléter' },
            clearBrowserDefaults() {
                if (this.preserveInput) return;

                ['nom', 'secteur_activite', 'ville', 'nombre_employes', 'type_production', 'annee_calcul'].forEach((field) => {
                    this.form[field] = this.defaultForm[field] || '';
                    const input = document.getElementById(field);
                    if (input) input.value = this.form[field];
                });

                this.form.pays = this.defaultForm.pays || @js($defaultCountry);
                const countryInput = document.getElementById('pays');
                if (countryInput) countryInput.value = this.form.pays;
            }
        }"
        x-init="clearBrowserDefaults(); setTimeout(() => clearBrowserDefaults(), 150)"
    >
        @csrf

        <section class="overflow-hidden rounded-2xl bg-[#25457d] px-8 py-7 text-white shadow-sm">
            <div class="max-w-2xl">
                <div class="text-xs font-semibold uppercase tracking-[0.22em] text-blue-100">Étape 1 — Configuration</div>
                <h1 class="mt-3 text-3xl font-bold">Nouveau calcul carbone</h1>
                <p class="mt-3 text-sm leading-6 text-blue-100">
                    Renseignez les informations de votre entreprise et sélectionnez les catégories d'émission à inclure. Le système génère ensuite une section par catégorie sélectionnée.
                </p>
                <div class="mt-6 flex gap-2">
                    <span class="h-1 w-8 rounded-full bg-[#16bd83]"></span>
                    <span class="h-1 w-8 rounded-full bg-white/25"></span>
                    <span class="h-1 w-8 rounded-full bg-white/25"></span>
                </div>
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-[1fr_325px]">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-6 flex items-center gap-3">
                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-[#213d70] text-xs font-bold text-white">1</span>
                    <h2 class="text-sm font-bold uppercase tracking-[0.18em] text-slate-600">Informations de l'entreprise</h2>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <label for="nom" class="block text-xs font-bold uppercase tracking-wide text-slate-600">Nom de l'entreprise</label>
                        <input id="nom" name="nom" x-model="form.nom" value="{{ $oldForm['nom'] }}" autocomplete="off" required class="{{ $inputClass }}">
                        <x-input-error :messages="$errors->get('nom')" class="mt-2" />
                    </div>
                    <div>
                        <label for="secteur_activite" class="block text-xs font-bold uppercase tracking-wide text-slate-600">Secteur d'activité</label>
                        <input id="secteur_activite" name="secteur_activite" x-model="form.secteur_activite" value="{{ $oldForm['secteur_activite'] }}" autocomplete="off" required class="{{ $inputClass }}">
                        <x-input-error :messages="$errors->get('secteur_activite')" class="mt-2" />
                    </div>
                    <div>
                        <label for="pays" class="block text-xs font-bold uppercase tracking-wide text-slate-600">Pays</label>
                        <input id="pays" name="pays" x-model="form.pays" value="{{ $oldForm['pays'] }}" autocomplete="off" required class="{{ $inputClass }}">
                        <x-input-error :messages="$errors->get('pays')" class="mt-2" />
                    </div>
                    <div>
                        <label for="ville" class="block text-xs font-bold uppercase tracking-wide text-slate-600">Ville</label>
                        <input id="ville" name="ville" x-model="form.ville" value="{{ $oldForm['ville'] }}" autocomplete="off" required class="{{ $inputClass }}">
                        <x-input-error :messages="$errors->get('ville')" class="mt-2" />
                    </div>
                    <div>
                        <label for="nombre_employes" class="block text-xs font-bold uppercase tracking-wide text-slate-600">Nombre d'employés</label>
                        <input id="nombre_employes" type="number" min="1" name="nombre_employes" x-model="form.nombre_employes" value="{{ $oldForm['nombre_employes'] }}" autocomplete="off" required class="{{ $inputClass }}">
                        <x-input-error :messages="$errors->get('nombre_employes')" class="mt-2" />
                    </div>
                    <div>
                        <label for="type_production" class="block text-xs font-bold uppercase tracking-wide text-slate-600">Type de production</label>
                        <input id="type_production" name="type_production" x-model="form.type_production" value="{{ $oldForm['type_production'] }}" autocomplete="off" class="{{ $inputClass }}">
                        <x-input-error :messages="$errors->get('type_production')" class="mt-2" />
                    </div>
                    <div>
                        <label for="annee_calcul" class="block text-xs font-bold uppercase tracking-wide text-slate-600">Année du calcul</label>
                        <input id="annee_calcul" type="number" min="2000" max="2040" name="annee_calcul" x-model="form.annee_calcul" value="{{ $oldForm['annee_calcul'] }}" autocomplete="off" required class="{{ $inputClass }}">
                        <x-input-error :messages="$errors->get('annee_calcul')" class="mt-2" />
                    </div>
                </div>
            </section>

            <aside class="space-y-4">
                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-sm font-bold uppercase tracking-[0.18em] text-slate-600">Récapitulatif</h2>
                    <dl class="mt-5 grid gap-3 text-sm">
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Entreprise</dt><dd class="max-w-36 truncate font-semibold" x-text="display(form.nom)"></dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Secteur</dt><dd class="max-w-36 truncate font-semibold" x-text="display(form.secteur_activite)"></dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Pays</dt><dd class="max-w-36 truncate font-semibold" x-text="display(form.pays)"></dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Ville</dt><dd class="max-w-36 truncate font-semibold" x-text="display(form.ville)"></dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Employés</dt><dd class="font-semibold" x-text="display(form.nombre_employes)"></dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Année</dt><dd class="font-semibold" x-text="display(form.annee_calcul)"></dd></div>
                    </dl>
                </section>

                <section class="rounded-2xl bg-[#16ad7b] p-6 text-white shadow-sm">
                    <div class="text-sm text-emerald-50">Catégories d'émission sélectionnées</div>
                    <div class="mt-2 text-4xl font-bold" x-text="selectedCount()"></div>
                    <div class="text-sm text-emerald-100">sur {{ count($posts) }} disponibles</div>
                </section>
            </aside>
        </div>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-slate-100 px-6 py-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-3">
                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-[#213d70] text-xs font-bold text-white">2</span>
                    <div>
                        <h2 class="font-bold text-[#0f1f3a]">Catégories d'émission à inclure</h2>
                        <p class="mt-1 text-sm text-slate-500">Une section sera générée par catégorie sélectionnée. Les facteurs actifs de chaque catégorie seront regroupés dans son select.</p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-4">
                    <span class="rounded-full border border-emerald-300 bg-emerald-50 px-4 py-2 text-sm font-bold text-emerald-700">
                        <span x-text="selectedCount()"></span> catégories sélectionnées
                    </span>
                    <button type="button" class="text-sm font-semibold text-slate-500 hover:text-[#213d70]" x-on:click="resetPosts()">Réinitialiser</button>
                    <button type="button" class="text-sm font-semibold text-[#00a875] hover:text-emerald-700" x-on:click="selectAllPosts()">Tout sélectionner</button>
                </div>
            </div>

            <div class="space-y-4 p-6">
                @foreach ($groups as $group)
                    @php
                        $accentClasses = [
                            'orange' => 'border-orange-200 bg-orange-50 text-orange-600',
                            'blue' => 'border-blue-200 bg-blue-50 text-blue-600',
                            'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-600',
                        ][$group['accent']];
                    @endphp
                    <div class="overflow-hidden rounded-2xl border {{ $accentClasses }}">
                        <div class="flex items-center justify-between gap-4 border-b border-current/20 px-5 py-4">
                            <div class="flex items-center gap-4">
                                <span class="h-9 w-2 rounded-full bg-current"></span>
                                <div>
                                    <h3 class="font-bold text-[#0f1f3a]">{{ $group['title'] }}</h3>
                                    <p class="mt-1 text-sm text-slate-500">{{ $group['description'] }}</p>
                                </div>
                            </div>
                            <span class="hidden rounded-full border border-current/30 bg-white px-3 py-1 text-xs font-bold sm:inline">
                                <span x-text="selected.filter((key) => @js($group['items']).includes(key)).length"></span>/{{ count($group['items']) }} sélectionnée(s)
                            </span>
                        </div>

                        <div class="divide-y divide-slate-200 bg-white/70">
                            @foreach ($group['items'] as $key)
                                @continue(! isset($posts[$key]))
                                <label
                                    class="flex cursor-pointer items-center gap-4 px-5 py-4 transition hover:bg-white"
                                    x-bind:class="isSelected(@js($key)) ? 'bg-white ring-1 ring-inset ring-slate-200' : ''"
                                >
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-current/20 bg-white/70 text-current">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true" data-category-icon="{{ $key }}">
                                            <g stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                                {!! $postIcons[$key] ?? '<path d="M12 3 4 7v10l8 4 8-4V7l-8-4Z"/><path d="M12 21V11"/>' !!}
                                            </g>
                                        </svg>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block font-bold text-[#0f1f3a]">{{ $posts[$key]['label'] }}</span>
                                        <span class="mt-1 block text-sm text-slate-500">{{ $posts[$key]['description'] }}</span>
                                    </span>
                                    <span class="hidden rounded-lg bg-white px-3 py-1 text-xs font-bold text-slate-600 sm:inline">{{ $posts[$key]['unit'] }}</span>
                                    <input type="checkbox" name="postes_emission[]" value="{{ $key }}" x-model="selected" class="h-5 w-5 rounded-md border-slate-300 text-[#16bd83] focus:ring-[#16bd83]">
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <x-input-error :messages="$errors->get('postes_emission')" />

                <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-5 py-4">
                    <div class="mb-3 text-xs font-bold text-slate-500">Catégories d'émission sélectionnées :</div>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="key in selected" :key="key">
                            <span class="rounded-lg border border-emerald-200 bg-white px-3 py-1 text-xs font-bold text-[#00a875]" x-text="postLabel(key)"></span>
                        </template>
                        <span class="text-sm text-slate-400" x-show="selectedCount() === 0">Aucune catégorie sélectionnée</span>
                    </div>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <a href="{{ route('historique.index') }}" class="inline-flex justify-center rounded-xl border border-slate-300 bg-white px-6 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                        Annuler
                    </a>
                    <button class="inline-flex justify-center rounded-xl bg-[#213d70] px-7 py-3 text-sm font-bold text-white shadow-lg shadow-blue-950/20 transition hover:bg-[#183768]">
                        Continuer
                        <span class="ml-2 text-blue-100">(<span x-text="selectedCount()"></span> catégories)</span>
                    </button>
                </div>
            </div>
        </section>
    </form>
@endsection

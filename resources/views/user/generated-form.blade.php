@extends('layouts.dashboard')

@section('title', 'Formulaire carbone généré')
@section('hide-page-heading', true)

@section('content')
    @php
        $schema = $formulaire->schema_json ?? [];
        $envelopeSuccess = data_get($schema, 'success');
        $formNode = (array) data_get($schema, 'formulaire', []);
        $sections = collect($sections ?? [])->values();
        $sectionCount = max($sections->count(), 1);
        $formSuccess = $envelopeSuccess === null ? true : (bool) $envelopeSuccess;
        $formMessage = (string) data_get($schema, 'message', '');
        $toneClasses = [
            'carburant' => 'from-emerald-50 to-white text-emerald-700 bg-emerald-100',
            'transport' => 'from-blue-50 to-white text-blue-700 bg-blue-100',
            'transport_personnel' => 'from-cyan-50 to-white text-cyan-700 bg-cyan-100',
            'batiment' => 'from-indigo-50 to-white text-indigo-700 bg-indigo-100',
            'machines' => 'from-rose-50 to-white text-rose-700 bg-rose-100',
            'materiaux' => 'from-amber-50 to-white text-amber-700 bg-amber-100',
            'dechets' => 'from-lime-50 to-white text-lime-700 bg-lime-100',
            'voyages' => 'from-fuchsia-50 to-white text-fuchsia-700 bg-fuchsia-100',
            'employes' => 'from-sky-50 to-white text-sky-700 bg-sky-100',
            'production' => 'from-emerald-50 to-white text-emerald-700 bg-emerald-100',
        ];
        $periodOptions = [
            'jour' => 'Jour',
            'semaine' => 'Semaine',
            'mois' => 'Mois',
            'trimestre' => 'Trimestre',
            'annee' => 'Année',
        ];
        $labelFromKey = function ($value, string $fallback = 'Section'): string {
            if (! is_scalar($value)) {
                return $fallback;
            }

            $label = trim(str_replace(['_', '-'], ' ', (string) $value));

            return $label !== '' ? ucfirst($label) : $fallback;
        };
        $normalizeFieldName = function ($value): string {
            if (! is_scalar($value)) {
                return 'champ';
            }

            $name = \Illuminate\Support\Str::of((string) $value)
                ->ascii()
                ->lower()
                ->replace([' ', '-'], '_')
                ->trim('_')
                ->toString();

            return match ($name) {
                'value' => 'valeur',
                'unit' => 'unite',
                'period' => 'periode',
                'periode_declaration' => 'periode',
                'declared_days', 'days_declared', 'jours' => 'jours_declares',
                'start_date', 'date_start', 'debut' => 'date_debut',
                'end_date', 'date_end', 'fin' => 'date_fin',
                default => $name !== '' ? $name : 'champ',
            };
        };
        $selectOptions = function ($options) use ($labelFromKey): array {
            if (! is_iterable($options)) {
                return [];
            }

            return collect($options)
                ->map(function ($option, $key) use ($labelFromKey) {
                    if (is_array($option)) {
                        $value = data_get($option, 'value')
                            ?? data_get($option, 'key')
                            ?? data_get($option, 'id')
                            ?? data_get($option, 'name')
                            ?? data_get($option, 'nom')
                            ?? data_get($option, 'label');
                        $label = data_get($option, 'label')
                            ?? data_get($option, 'title')
                            ?? data_get($option, 'titre')
                            ?? data_get($option, 'name')
                            ?? data_get($option, 'nom')
                            ?? $value;

                        return [
                            'value' => is_scalar($value) ? (string) $value : '',
                            'label' => is_scalar($label) ? (string) $label : $labelFromKey($value, 'Option'),
                        ];
                    }

                    if (is_string($key) && ! is_numeric($key)) {
                        return [
                            'value' => $key,
                            'label' => is_scalar($option) ? (string) $option : $labelFromKey($key, 'Option'),
                        ];
                    }

                    return [
                        'value' => is_scalar($option) ? (string) $option : '',
                        'label' => is_scalar($option) ? $labelFromKey($option, 'Option') : 'Option',
                    ];
                })
                ->filter(fn (array $option) => $option['value'] !== '')
                ->values()
                ->all();
        };
        $factorSelectFieldNames = [
            'type_carburant',
            'type_transport',
            'type_materiau',
            'type_dechet',
            'moyen_transport',
        ];
    @endphp

    <form
        method="POST"
        action="{{ route('formulaires.calculate', $formulaire) }}"
        enctype="multipart/form-data"
        class="mx-auto max-w-4xl space-y-6"
        x-data="{ current: 0, total: {{ $sectionCount }}, next() { if (this.current < this.total - 1) this.current++ }, previous() { if (this.current > 0) this.current-- } }"
    >
        @csrf

        @if ($errors->any())
            <section class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800">
                <div>Veuillez corriger les champs du formulaire.</div>
                <ul class="mt-2 list-disc space-y-1 pl-5 font-medium">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </section>
        @endif

        <header class="text-center">
            <h1 class="text-3xl font-bold tracking-tight text-[#0f1f3a]">Formulaire carbone généré</h1>
            <p class="mt-2 text-sm text-slate-600">Saisissez vos données pour chaque poste d'émission.</p>
        </header>

        @if (! $formSuccess)
            <section class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm font-semibold text-amber-800">
                <div>{{ $formMessage !== '' ? $formMessage : 'Élément à régénérer' }}</div>
                <button type="submit" form="regenerate-form" class="mt-3 rounded-xl bg-amber-600 px-5 py-2 text-sm font-bold text-white transition hover:bg-amber-700">
                    Régénérer le formulaire
                </button>
            </section>
        @endif

        @if ($sections->isEmpty())
            <section class="rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm">
                <h2 class="text-lg font-bold text-[#0f1f3a]">Aucune section disponible</h2>
                <p class="mt-2 text-sm text-slate-500">Le formulaire JSON ne contient pas encore de sections exploitables.</p>
            </section>
        @else
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-center gap-3">
                    @foreach ($sections as $index => $section)
                        @php
                            $tabSectionKey = data_get($section, 'key') ?? 'section';
                            $tabSectionTitle = data_get($section, 'titre')
                                ?? data_get($section, 'title')
                                ?? $labelFromKey($tabSectionKey);
                            $tabSectionTitle = is_scalar($tabSectionTitle) ? (string) $tabSectionTitle : $labelFromKey($tabSectionKey);
                        @endphp
                        <button
                            type="button"
                            x-on:click="current = {{ $index }}"
                            class="inline-flex min-w-28 items-center justify-center gap-2 rounded-xl px-4 py-3 text-sm font-bold transition"
                            x-bind:class="current === {{ $index }} ? 'bg-[#16bd83] text-white shadow-lg shadow-emerald-900/20' : (current > {{ $index }} ? 'border border-emerald-200 bg-emerald-50 text-emerald-700' : 'bg-slate-50 text-slate-500')"
                        >
                            <span class="truncate">{{ $tabSectionTitle }}</span>
                            <span x-show="current > {{ $index }}">✓</span>
                        </button>
                        @if (! $loop->last)
                            <span class="hidden h-px w-5 bg-slate-200 sm:block"></span>
                        @endif
                    @endforeach
                </div>
                <p class="mt-4 text-sm text-slate-600">Étape <span x-text="current + 1"></span> sur {{ $sections->count() }}</p>
            </section>

            @foreach ($sections as $index => $section)
                @php
                    $sectionKey = data_get($section, 'key') ?? 'section';
                    $sectionKey = is_scalar($sectionKey) && trim((string) $sectionKey) !== '' ? (string) $sectionKey : 'section';
                    $sectionTitle = data_get($section, 'title')
                        ?? $labelFromKey($sectionKey);
                    $sectionTitle = is_scalar($sectionTitle) ? (string) $sectionTitle : $labelFromKey($sectionKey);
                    $sectionDescription = data_get($section, 'description') ?? 'Renseignez vos données pour ce poste d\'émission';
                    $sectionDescription = is_scalar($sectionDescription) ? (string) $sectionDescription : 'Renseignez vos données pour ce poste d\'émission';
                    $rawSectionFields = data_get($section, 'fields', []);
                    $sectionFields = is_iterable($rawSectionFields) ? collect($rawSectionFields)->values() : collect();
                    $tone = $toneClasses[$sectionKey] ?? 'from-slate-50 to-white text-slate-700 bg-slate-100';
                    $lineName = "lignes[{$index}]";
                    $lineOldPath = "lignes.{$index}";
                    $factorOptions = collect($factorsByCategory[$sectionKey] ?? collect())
                        ->map(fn ($factor) => [
                            'id' => $factor->id,
                            'nom' => $factor->nom,
                            'coefficient' => (float) $factor->coefficient,
                            'unite' => $factor->unite,
                            'description' => $factor->description,
                        ])
                        ->values()
                        ->all();
                    $defaultFactorId = old("{$lineOldPath}.facteur_emission_id", $factorOptions[0]['id'] ?? '');
                    $calculationField = $sectionFields->firstWhere('utilise_pour_calcul', true);
                    $calculationFieldName = $calculationField ? $normalizeFieldName(data_get($calculationField, 'name')) : '';
                    $calculationFieldName = old("{$lineOldPath}.champ_calcul", $calculationFieldName);
                    $calculationFieldName = is_scalar($calculationFieldName) ? (string) $calculationFieldName : '';
                    $calculationPeriods = $calculationField ? data_get($calculationField, 'periods', []) : [];
                    $sectionPeriodOptions = $periodOptions;

                    if (is_iterable($calculationPeriods) && collect($calculationPeriods)->isNotEmpty()) {
                        $sectionPeriodOptions = collect($calculationPeriods)
                            ->mapWithKeys(fn ($period) => [
                                (string) $period => $periodOptions[(string) $period] ?? $labelFromKey($period, 'Période'),
                            ])
                            ->all();
                    }

                    $defaultPeriodValue = array_key_exists('mois', $sectionPeriodOptions)
                        ? 'mois'
                        : (array_key_first($sectionPeriodOptions) ?? 'mois');
                    $defaultPeriod = old("{$lineOldPath}.periode", $defaultPeriodValue);
                    $renderedFieldNames = $sectionFields
                        ->map(fn ($field) => $normalizeFieldName(data_get($field, 'name')))
                        ->all();
                    $fieldsToRender = $sectionFields->values();
                    $defaultFields = [
                        [
                            'name' => 'periode',
                            'label' => 'Période',
                            'type' => 'select',
                            'required' => true,
                            'options' => $sectionPeriodOptions,
                        ],
                        [
                            'name' => 'date_debut',
                            'label' => 'Date de début',
                            'type' => 'date',
                            'required' => false,
                        ],
                        [
                            'name' => 'date_fin',
                            'label' => 'Date de fin',
                            'type' => 'date',
                            'required' => false,
                        ],
                        [
                            'name' => 'justificatif',
                            'label' => 'Justificatif',
                            'type' => 'file',
                            'required' => false,
                        ],
                    ];

                    foreach ($defaultFields as $defaultField) {
                        if (! in_array($defaultField['name'], $renderedFieldNames, true)) {
                            $fieldsToRender->push($defaultField);
                        }
                    }
                    [$gradient, $iconText, $iconBg] = [
                        trim(collect(explode(' ', $tone))->take(2)->implode(' ')),
                        collect(explode(' ', $tone))->slice(2, 1)->first(),
                        collect(explode(' ', $tone))->slice(3, 1)->first(),
                    ];
                @endphp
                <section
                    x-show="current === {{ $index }}"
                    x-cloak
                    class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
                    x-data="{
                        factorId: @js($defaultFactorId),
                        period: @js($defaultPeriod),
                    }"
                >
                    <div class="flex items-center justify-between gap-4 bg-gradient-to-r {{ $gradient }} px-6 py-5">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl {{ $iconBg }} {{ $iconText }}">
                                <span class="h-2.5 w-2.5 rounded-full bg-current"></span>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-[#0f1f3a]">{{ $sectionTitle }}</h2>
                                <p class="mt-1 text-sm text-slate-600">{{ $sectionDescription }}</p>
                            </div>
                        </div>
                        <span class="text-sm font-medium text-slate-500">{{ $loop->iteration }}/{{ $sections->count() }}</span>
                    </div>

                    <div class="grid gap-4 p-6 md:grid-cols-2">
                        <input type="hidden" name="{{ $lineName }}[categorie]" value="{{ $sectionKey }}">
                        @if ($calculationFieldName !== '')
                            <input type="hidden" name="{{ $lineName }}[champ_calcul]" value="{{ $calculationFieldName }}">
                        @else
                            <div class="md:col-span-2 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800">
                                Aucun champ de calcul n’est défini pour cette section.
                            </div>
                        @endif

                        <div>
                            <label class="block text-sm font-bold text-[#0f1f3a]">Catégorie</label>
                            <div class="mt-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700">
                                {{ $sectionTitle }}
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-[#0f1f3a]">Facteur d'émission</label>
                            <select
                                name="{{ $lineName }}[facteur_emission_id]"
                                x-model="factorId"
                                required
                                class="mt-2 block w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-3 text-sm shadow-sm focus:border-[#16bd83] focus:ring-[#16bd83]"
                            >
                                @if ($factorOptions === [])
                                    <option value="">Aucun facteur d’émission disponible</option>
                                @elseif ((string) $defaultFactorId === '')
                                    <option value="">Sélectionner un facteur d’émission</option>
                                @endif
                                @foreach ($factorOptions as $factor)
                                    <option value="{{ $factor['id'] }}" @selected((string) $defaultFactorId === (string) $factor['id'])>
                                        {{ $factor['nom'] }} — {{ $factor['unite'] }} — {{ number_format($factor['coefficient'], 6, ',', ' ') }} kgCO2e
                                    </option>
                                @endforeach
                            </select>
                            @if ($factorOptions === [])
                                <div class="mt-1 text-xs font-medium text-amber-700">Aucun facteur actif n'est configuré pour cette catégorie.</div>
                            @endif
                        </div>

                        @foreach ($fieldsToRender as $field)
                            @php
                                $fieldName = $normalizeFieldName(data_get($field, 'name', 'champ'));
                                $fieldLabel = data_get($field, 'label')
                                    ?? data_get($field, 'title')
                                    ?? data_get($field, 'titre')
                                    ?? $labelFromKey($fieldName, 'Champ');
                                $fieldLabel = is_scalar($fieldLabel) ? (string) $fieldLabel : $labelFromKey($fieldName, 'Champ');
                                $fieldType = strtolower((string) data_get($field, 'type', 'text'));
                                $fieldType = in_array($fieldType, ['text', 'number', 'select', 'textarea', 'date', 'file'], true) ? $fieldType : 'text';
                                $fieldRequired = (bool) data_get($field, 'required', false);
                                $isCalculationField = (bool) data_get($field, 'utilise_pour_calcul', false);
                                $fieldUnit = data_get($field, 'unit', '');
                                $fieldUnit = is_scalar($fieldUnit) ? (string) $fieldUnit : '';
                                $coreField = in_array($fieldName, ['periode', 'date_debut', 'date_fin', 'justificatif'], true);
                                $inputName = $coreField
                                    ? "{$lineName}[{$fieldName}]"
                                    : "{$lineName}[champs][{$fieldName}]";
                                $oldPath = $coreField
                                    ? "{$lineOldPath}.{$fieldName}"
                                    : "{$lineOldPath}.champs.{$fieldName}";
                                $defaultValue = data_get($field, 'default', '');
                                $defaultValue = is_scalar($defaultValue) ? (string) $defaultValue : '';
                                $fieldValue = old($oldPath, $fieldName === 'periode' ? $defaultPeriod : $defaultValue);
                                $fieldDescription = data_get($field, 'description', '');
                                $fieldDescription = is_scalar($fieldDescription) ? (string) $fieldDescription : '';
                                $fieldStep = data_get($field, 'step', $fieldName === 'jours_declares' ? '1' : ($fieldName === 'valeur' ? '0.001' : 'any'));
                                $fieldMin = data_get($field, 'min', $fieldName === 'jours_declares' ? '1' : ($fieldName === 'valeur' ? '0' : null));
                                $rawOptions = data_get($field, 'options', []);

                                if ($fieldName === 'periode') {
                                    $periods = data_get($field, 'periods', []);

                                    if (is_iterable($periods) && collect($periods)->isNotEmpty()) {
                                        $rawOptions = collect($periods)
                                            ->mapWithKeys(fn ($period) => [
                                                (string) $period => $periodOptions[(string) $period] ?? $labelFromKey($period, 'Période'),
                                            ])
                                            ->all();
                                    } elseif (! is_iterable($rawOptions) || collect($rawOptions)->isEmpty()) {
                                        $rawOptions = $periodOptions;
                                    }
                                }

                                $fieldOptions = $fieldType === 'select' ? $selectOptions($rawOptions) : [];

                                if ($fieldType === 'select' && $fieldOptions === []) {
                                    if (in_array($fieldName, $factorSelectFieldNames, true) && $factorOptions !== []) {
                                        $fieldOptions = collect($factorOptions)
                                            ->map(fn ($factor) => [
                                                'value' => $factor['nom'],
                                                'label' => $factor['nom'].' — '.$factor['unite'].' — '.number_format($factor['coefficient'], 6, ',', ' ').' kgCO2e',
                                            ])
                                            ->values()
                                            ->all();
                                    } else {
                                        $fieldType = 'text';
                                    }
                                }
                            @endphp

                            <div
                                class="{{ in_array($fieldType, ['textarea', 'file'], true) ? 'md:col-span-2' : '' }}"
                                @if ($fieldName === 'jours_declares') x-show="period === 'jour'" x-cloak @endif
                                @if ($fieldType === 'file') x-data="{ fileName: '' }" @endif
                            >
                                <label class="block text-sm font-bold text-[#0f1f3a]">
                                    {{ $fieldLabel }}
                                    @if ($fieldRequired)
                                        <span class="text-rose-500">*</span>
                                    @endif
                                    @if ($fieldUnit !== '' && $fieldName !== 'unite')
                                        <span class="ml-2 text-xs font-semibold text-slate-400">{{ $fieldUnit }}</span>
                                    @endif
                                </label>

                                @if ($fieldType === 'textarea')
                                    <textarea
                                        name="{{ $inputName }}"
                                        rows="4"
                                        @if ($isCalculationField) data-calculation-field="true" @endif
                                        @if ($fieldRequired) required @endif
                                        class="mt-2 block w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-3 text-sm shadow-sm focus:border-[#16bd83] focus:ring-[#16bd83]"
                                    >{{ $fieldValue }}</textarea>
                                @elseif ($fieldType === 'select')
                                    <select
                                        name="{{ $inputName }}"
                                        @if ($fieldName === 'periode') x-model="period" @endif
                                        @if ($isCalculationField) data-calculation-field="true" @endif
                                        @if ($fieldRequired) required @endif
                                        class="mt-2 block w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-3 text-sm shadow-sm focus:border-[#16bd83] focus:ring-[#16bd83]"
                                    >
                                        @if ($fieldName !== 'periode' || (string) $fieldValue === '')
                                            <option value="">{{ $fieldName === 'periode' ? 'Sélectionner une période' : 'Sélectionner une option' }}</option>
                                        @endif
                                        @foreach ($fieldOptions as $option)
                                            <option value="{{ $option['value'] }}" @selected((string) $fieldValue === (string) $option['value'])>
                                                {{ $option['label'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                @elseif ($fieldType === 'number')
                                    <input
                                        type="number"
                                        name="{{ $inputName }}"
                                        value="{{ $fieldValue }}"
                                        @if ($isCalculationField) data-calculation-field="true" @endif
                                        @if (is_scalar($fieldStep) && (string) $fieldStep !== '') step="{{ $fieldStep }}" @endif
                                        @if (is_scalar($fieldMin) && (string) $fieldMin !== '') min="{{ $fieldMin }}" @endif
                                        @if ($fieldRequired) required @endif
                                        class="mt-2 block w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-3 text-sm shadow-sm focus:border-[#16bd83] focus:ring-[#16bd83]"
                                    >
                                @elseif ($fieldType === 'date')
                                    <input
                                        type="date"
                                        name="{{ $inputName }}"
                                        value="{{ $fieldValue }}"
                                        @if ($isCalculationField) data-calculation-field="true" @endif
                                        @if ($fieldRequired) required @endif
                                        class="mt-2 block w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-3 text-sm shadow-sm focus:border-[#16bd83] focus:ring-[#16bd83]"
                                    >
                                @elseif ($fieldType === 'file')
                                    @php($fileInputId = "justificatif-{$index}-{$fieldName}")
                                    <label
                                        for="{{ $fileInputId }}"
                                        class="mt-2 flex cursor-pointer items-center gap-4 rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-4 text-sm transition hover:border-[#16bd83] hover:bg-emerald-50"
                                    >
                                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white text-[#16bd83] shadow-sm" aria-hidden="true">
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M12 16V4"></path>
                                                <path d="M7 9l5-5 5 5"></path>
                                                <path d="M20 16.5V19a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-2.5"></path>
                                            </svg>
                                        </span>
                                        <span class="min-w-0">
                                            <span class="block font-bold text-[#0f1f3a]">Ajouter un justificatif</span>
                                            <span class="block text-xs font-medium text-slate-500">PDF, image ou document</span>
                                            <span x-show="fileName" x-text="fileName" class="mt-2 block truncate text-xs font-bold text-[#16ad7b]" x-cloak></span>
                                        </span>
                                    </label>
                                    <input
                                        id="{{ $fileInputId }}"
                                        type="file"
                                        name="{{ $inputName }}"
                                        accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                        class="sr-only"
                                        x-on:change="fileName = $event.target.files.length ? $event.target.files[0].name : ''"
                                    >
                                @else
                                    <input
                                        type="text"
                                        name="{{ $inputName }}"
                                        value="{{ $fieldValue }}"
                                        @if ($isCalculationField) data-calculation-field="true" @endif
                                        @if ($fieldRequired) required @endif
                                        class="mt-2 block w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-3 text-sm shadow-sm focus:border-[#16bd83] focus:ring-[#16bd83]"
                                    >
                                @endif

                                @if ($fieldDescription !== '')
                                    <p class="mt-1 text-xs font-medium text-slate-500">{{ $fieldDescription }}</p>
                                @endif
                            </div>
                        @endforeach

                    </div>

                    <div class="flex flex-col gap-4 border-t border-slate-100 bg-slate-50 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <button type="button" x-on:click="previous()" x-bind:disabled="current === 0" class="rounded-xl border border-slate-200 bg-white px-6 py-3 text-sm font-bold text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">
                            Précédent
                        </button>
                        <div class="flex justify-center gap-2">
                            @foreach ($sections as $dotIndex => $dotSection)
                                <span class="h-2 w-3 rounded-full transition" x-bind:class="current === {{ $dotIndex }} ? 'bg-[#16bd83]' : 'bg-slate-200'"></span>
                            @endforeach
                        </div>
                        <button type="button" x-show="current < total - 1" x-on:click="next()" class="rounded-xl bg-[#16bd83] px-7 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-900/20 transition hover:bg-emerald-700">
                            Suivant
                        </button>
                        <button type="submit" x-show="current === total - 1" class="rounded-xl bg-[#16bd83] px-7 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-900/20 transition hover:bg-emerald-700">
                            Calculer l'empreinte carbone
                        </button>
                    </div>
                </section>
            @endforeach
        @endif
    </form>

    @if (! $formSuccess)
        <form id="regenerate-form" method="POST" action="{{ route('formulaires.regenerate', $formulaire) }}" class="hidden">
            @csrf
        </form>
    @endif
@endsection

@extends('layouts.dashboard')

@section('title', 'Rapport carbone')
@section('page-title', 'Rapport carbone généré')
@section('page-subtitle')
    Bilan carbone {{ $resultat->entreprise->annee_calcul }} — {{ $resultat->entreprise->nom }}
@endsection

@section('header-actions')
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('resultats.show', $resultat) }}" class="rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">Retour aux résultats</a>
        <form method="POST" action="{{ route('resultats.generate-report', $resultat) }}">
            @csrf
            <button type="submit" class="rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">Régénérer</button>
        </form>
        <a href="{{ route('rapports.download', $resultat) }}" class="rounded-xl bg-[#213d70] px-4 py-3 text-sm font-bold text-white shadow-lg shadow-blue-950/20 transition hover:bg-[#183768]">Télécharger le rapport PDF</a>
    </div>
@endsection

@section('content')
    @php
        $details = collect($resultat->details_calcul ?? [])->map(function ($detail) {
            $detail['emissions_kgco2e'] = (float) ($detail['emissions_kgco2e'] ?? 0);
            $detail['emissions_tco2e'] = (float) ($detail['emissions_tco2e'] ?? 0);
            return $detail;
        });
        $highest = collect($resultat->facteurs_eleves ?? [])->first() ?? $details->sortByDesc('emissions_kgco2e')->first();
        $totalKg = max((float) $resultat->total_kg_co2e, 0.001);
        $colors = ['#16bd83', '#3b82f6', '#8b5cf6', '#f59e0b', '#ef4444', '#64748b'];
        $contenu = $rapport?->contenu_json ?? [];
    @endphp

    <div class="mx-auto max-w-5xl space-y-6">
        @if (session('show_regenerate_report_button') || session('show_regenerate_recommendations_button'))
            <section class="flex flex-wrap items-center gap-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                @if (session('show_regenerate_report_button'))
                    <form method="POST" action="{{ route('resultats.generate-report', $resultat) }}">
                        @csrf
                        <button type="submit" class="rounded-xl bg-[#213d70] px-5 py-3 text-sm font-bold text-white shadow-lg shadow-blue-950/20 transition hover:bg-[#183768]">
                            Régénérer le rapport
                        </button>
                    </form>
                @endif

                @if (session('show_regenerate_recommendations_button'))
                    <form method="POST" action="{{ route('resultats.generate-recommendations', $resultat) }}">
                        @csrf
                        <button type="submit" class="rounded-xl bg-[#16bd83] px-5 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-900/20 transition hover:bg-emerald-700">
                            Régénérer les recommandations
                        </button>
                    </form>
                @endif
            </section>
        @endif

        @if ($rapport)
            <section class="flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800">
                <span class="mt-0.5 flex h-5 w-5 items-center justify-center rounded-full border border-emerald-500 text-xs">✓</span>
                <div>
                    <div class="font-bold">Rapport généré avec succès.</div>
                    <div class="mt-1">Statut : {{ ucfirst($rapport->statut) }} @if($rapport->generated_at) — Généré le {{ $rapport->generated_at->format('d/m/Y') }} @endif</div>
                </div>
            </section>
        @else
            <section class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm font-semibold text-amber-800">
                Aucun rapport n'a encore été généré pour ce résultat.
            </section>
        @endif

        <div class="grid gap-4 lg:grid-cols-3">
            <div class="rounded-2xl bg-[#16ad7b] p-6 text-white shadow-sm">
                <div class="text-sm text-emerald-50">Émissions totales</div>
                <div class="mt-4 text-3xl font-bold">{{ number_format($resultat->total_t_co2e_an, 3, ',', ' ') }}</div>
                <div class="mt-1 text-sm text-emerald-50">tCO2e/an</div>
                <div class="mt-3 text-xs text-emerald-50">{{ number_format($resultat->total_kg_co2e, 3, ',', ' ') }} kgCO2e</div>
            </div>
            <div class="rounded-2xl bg-[#25457d] p-6 text-white shadow-sm">
                <div class="text-sm text-blue-100">Source principale</div>
                <div class="mt-4 text-3xl font-bold">{{ $highest ? ($highest['facteur_nom'] ?? $highest['label'] ?? 'Non disponible') : 'Non disponible' }}</div>
                <div class="mt-1 text-sm text-blue-100">{{ $highest ? round(($highest['emissions_kgco2e'] / $totalKg) * 100).'%' : '0%' }} des émissions</div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="text-sm text-slate-500">Statut du calcul</div>
                <div class="mt-4 text-3xl font-bold text-[#0f1f3a]">{{ str_replace('_', ' ', ucfirst($resultat->statut_calcul)) }}</div>
                <div class="mt-1 text-sm text-slate-500">Empreinte carbone calculée</div>
            </div>
        </div>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-[#0f1f3a]">Résumé exécutif</h2>
            <p class="mt-4 text-sm leading-6 text-slate-700">
                {{ data_get($contenu, 'resume_executif', $rapport?->resume ?? 'Le résumé sera disponible après génération du rapport.') }}
            </p>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-[#0f1f3a]">Analyse globale</h2>
            <p class="mt-4 text-sm leading-6 text-slate-700">
                {{ data_get($contenu, 'analyse_globale', 'L’analyse globale sera disponible après génération complète du rapport.') }}
            </p>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-[#0f1f3a]">Informations de l'entreprise</h2>
            <div class="mt-5 grid gap-3 md:grid-cols-3">
                <div class="rounded-xl bg-slate-50 p-4">
                    <div class="text-xs text-slate-400">Nom</div>
                    <div class="mt-1 font-bold">{{ $resultat->entreprise->nom }}</div>
                </div>
                <div class="rounded-xl bg-slate-50 p-4">
                    <div class="text-xs text-slate-400">Secteur</div>
                    <div class="mt-1 font-bold">{{ $resultat->entreprise->secteur_activite }}</div>
                </div>
                <div class="rounded-xl bg-slate-50 p-4">
                    <div class="text-xs text-slate-400">Pays</div>
                    <div class="mt-1 font-bold">{{ $resultat->entreprise->pays }}</div>
                </div>
                <div class="rounded-xl bg-slate-50 p-4">
                    <div class="text-xs text-slate-400">Ville</div>
                    <div class="mt-1 font-bold">{{ $resultat->entreprise->ville }}</div>
                </div>
                <div class="rounded-xl bg-slate-50 p-4">
                    <div class="text-xs text-slate-400">Nombre d'employés</div>
                    <div class="mt-1 font-bold">{{ $resultat->entreprise->nombre_employes }}</div>
                </div>
                <div class="rounded-xl bg-slate-50 p-4">
                    <div class="text-xs text-slate-400">Année du calcul</div>
                    <div class="mt-1 font-bold">{{ $resultat->entreprise->annee_calcul }}</div>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-[#0f1f3a]">Résultats par source</h2>
            <div class="mt-6 space-y-4">
                @forelse ($details->values() as $index => $detail)
                    @php
                        $percentage = round(($detail['emissions_kgco2e'] / $totalKg) * 100);
                        $barWidth = min(($detail['emissions_kgco2e'] / max((float) ($details->max('emissions_kgco2e') ?? 0), 0.001)) * 100, 100);
                    @endphp
                    <div class="grid gap-3 sm:grid-cols-[130px_1fr_120px_60px] sm:items-center">
                        <div class="font-medium text-slate-700">
                            <span class="mr-2 inline-block h-3 w-3 rounded-full" style="background: {{ $colors[$index % count($colors)] }}"></span>
                            {{ $detail['facteur_nom'] ?? $detail['label'] ?? 'Catégorie' }}
                        </div>
                        <div class="h-2 rounded-full bg-slate-100">
                            <div class="h-2 rounded-full" style="width: {{ $barWidth }}%; background: {{ $colors[$index % count($colors)] }}"></div>
                        </div>
                        <div class="font-bold text-[#16ad7b]">{{ number_format($detail['emissions_tco2e'], 3, ',', ' ') }} tCO2e</div>
                        <div class="text-sm text-slate-500">{{ $percentage }}%</div>
                    </div>
                @empty
                    <div class="rounded-xl bg-slate-50 p-5 text-center text-sm text-slate-500">Aucun détail d'émission disponible.</div>
                @endforelse
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-[#0f1f3a]">Analyse par catégorie</h2>
            <div class="mt-4 space-y-3">
                @forelse (collect(data_get($contenu, 'analyse_par_categorie', [])) as $analyse)
                    <div class="rounded-xl bg-slate-50 p-4 text-sm text-slate-700">
                        @if (is_array($analyse))
                            <div class="font-bold text-[#0f1f3a]">{{ data_get($analyse, 'label', data_get($analyse, 'categorie', 'Catégorie')) }}</div>
                            <div class="mt-1">{{ data_get($analyse, 'analyse', data_get($analyse, 'commentaire', json_encode($analyse, JSON_UNESCAPED_UNICODE))) }}</div>
                        @else
                            {{ $analyse }}
                        @endif
                    </div>
                @empty
                    <div class="rounded-xl bg-slate-50 p-5 text-center text-sm text-slate-500">Aucune analyse par catégorie disponible.</div>
                @endforelse
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-[#0f1f3a]">Points critiques</h2>
            <div class="mt-4 space-y-2">
                @forelse (collect(data_get($contenu, 'points_critiques', [])) as $point)
                    <div class="rounded-xl border border-amber-100 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800">
                        {{ is_array($point) ? data_get($point, 'titre', json_encode($point, JSON_UNESCAPED_UNICODE)) : $point }}
                    </div>
                @empty
                    <div class="rounded-xl bg-slate-50 p-5 text-center text-sm text-slate-500">Aucun point critique renseigné.</div>
                @endforelse
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-[#0f1f3a]">Conclusion</h2>
            <p class="mt-4 text-sm leading-6 text-slate-700">
                {{ data_get($contenu, 'conclusion', 'La conclusion sera disponible après génération complète du rapport.') }}
            </p>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-[#0f1f3a]">Recommandations</h2>

            @if ($recommendationThresholdExceeded)
                <p class="mt-3 text-sm leading-6 text-slate-600">
                    Certains postes dépassent le seuil interne de {{ number_format($recommendationThresholdTco2e, 0, ',', ' ') }} tCO2e. Vous pouvez générer des recommandations ciblées à partir de ce résultat.
                </p>
                <div class="mt-5 flex flex-wrap gap-3">
                    @if ($hasRecommendations)
                        <a href="{{ route('recommandations.show', $resultat) }}" class="rounded-xl bg-[#213d70] px-5 py-3 text-sm font-bold text-white shadow-lg shadow-blue-950/20 transition hover:bg-[#183768]">Voir les recommandations</a>
                        <form method="POST" action="{{ route('resultats.generate-recommendations', $resultat) }}">
                            @csrf
                            <button type="submit" class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                                Régénérer les recommandations
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('resultats.generate-recommendations', $resultat) }}">
                            @csrf
                            <button type="submit" class="rounded-xl bg-[#16bd83] px-5 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-900/20 transition hover:bg-emerald-700">
                                Générer les recommandations
                            </button>
                        </form>
                    @endif
                </div>
            @else
                <p class="mt-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                    Les seuils d’émission sont acceptables. Aucune recommandation prioritaire n’est nécessaire pour ce résultat.
                </p>
            @endif
        </section>
    </div>
@endsection

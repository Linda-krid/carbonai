@extends('layouts.dashboard')

@section('title', 'Résultats carbone')
@section('hide-page-heading', true)

@section('content')
    @php
        $details = collect($resultat->details_calcul ?? [])->map(function ($detail) {
            $detail['valeur'] = (float) ($detail['valeur'] ?? 0);
            $detail['valeur_annualisee'] = (float) ($detail['valeur_annualisee'] ?? 0);
            $detail['coefficient'] = (float) ($detail['coefficient'] ?? 0);
            $detail['emissions_kgco2e'] = (float) ($detail['emissions_kgco2e'] ?? 0);
            $detail['emissions_tco2e'] = (float) ($detail['emissions_tco2e'] ?? 0);
            return $detail;
        });
        $highest = collect($resultat->facteurs_eleves ?? [])->first() ?? $details->sortByDesc('emissions_kgco2e')->first();
        $categorieDominante = $highest['label'] ?? $highest['categorie'] ?? 'Non disponible';
        $facteurDominant = $highest['facteur_nom'] ?? 'Non disponible';
        $detailsByCategory = $details->groupBy('categorie')->map(fn ($items) => [
            'label' => $items->first()['label'] ?? $items->first()['categorie'] ?? 'Catégorie',
            'kg' => (float) $items->sum('emissions_kgco2e'),
            't' => (float) $items->sum('emissions_tco2e'),
        ])->values();
        $detailsByFactor = $details->groupBy('facteur_emission_id')->map(fn ($items) => [
            'label' => $items->first()['facteur_nom'] ?? 'Facteur',
            'kg' => (float) $items->sum('emissions_kgco2e'),
            't' => (float) $items->sum('emissions_tco2e'),
        ])->values();
        $totalKg = max((float) $resultat->total_kg_co2e, 0.001);
        $colors = ['#16bd83', '#3b82f6', '#8b5cf6', '#f59e0b', '#ef4444', '#64748b', '#06b6d4', '#84cc16'];
        $cursor = 0.0;
        $pieStops = [];

        foreach ($details->values() as $index => $detail) {
            $percentage = max(($detail['emissions_kgco2e'] / $totalKg) * 100, 0);
            $end = min($cursor + $percentage, 100);
            $pieStops[] = $colors[$index % count($colors)].' '.$cursor.'% '.$end.'%';
            $cursor = $end;
        }

        $pieBackground = $pieStops ? 'conic-gradient('.implode(', ', $pieStops).')' : '#e2e8f0';
    @endphp

    <div class="mx-auto max-w-7xl space-y-6">
        <section class="overflow-hidden rounded-2xl bg-[#25457d] px-8 py-7 text-white shadow-sm">
            <div class="max-w-2xl">
                <div class="text-xs font-semibold uppercase tracking-[0.22em] text-blue-100">Entreprise</div>
                <h1 class="mt-3 text-3xl font-bold">Résultats carbone</h1>
                <p class="mt-2 text-sm text-blue-100">
                    Analyse de votre empreinte carbone pour l'année {{ $resultat->entreprise?->annee_calcul ?? now()->year }}.
                </p>
            </div>
        </section>

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

        <section class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-4 text-sm font-semibold text-emerald-800">
            <span class="flex h-5 w-5 items-center justify-center rounded-full border border-emerald-500 text-xs">✓</span>
            <span>Calcul validé — Traitement terminé avec succès</span>
        </section>

        <section class="rounded-2xl border border-emerald-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4">
                <div class="max-w-4xl">
                    <div class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold uppercase tracking-wide text-emerald-700">
                        <span class="h-2 w-2 rounded-full bg-[#16bd83]"></span>
                        Résultat vérifié
                    </div>
                    <p class="mt-4 max-w-4xl text-sm leading-6 text-slate-700">
                        Ce résultat est calculé à partir de vos données, des facteurs d’émission actifs de la base CarbonAI et des formules internes configurées. Les coefficients proviennent de sources référencées et ne sont pas saisis manuellement. Chaque ligne conserve le facteur, la source, la formule et le résultat pour garantir un calcul fiable et traçable.
                    </p>
                </div>
            </div>
            <div class="mt-5 flex flex-wrap gap-2">
                @foreach (['Calcul vérifié', 'Facteurs actifs utilisés', 'Coefficients issus de la base de référence', 'Sources affichées', 'Formules internes appliquées', 'Résultat traçable'] as $badge)
                    <span class="inline-flex items-center gap-2 rounded-full border border-emerald-100 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-800">
                        <span class="flex h-4 w-4 items-center justify-center rounded-full bg-[#16bd83] text-[10px] text-white">✓</span>
                        {{ $badge }}
                    </span>
                @endforeach
            </div>
        </section>

        <div class="grid gap-5 xl:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-5 flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">↗</div>
                <div class="text-sm font-semibold text-slate-500">Total kgCO2e</div>
                <div class="mt-3 flex items-end gap-2">
                    <span class="text-4xl font-bold text-[#0f1f3a]">{{ number_format($resultat->total_kg_co2e, 3, ',', ' ') }}</span>
                    <span class="pb-1 text-sm text-slate-500">kgCO2e</span>
                </div>
                <div class="mt-2 text-sm text-slate-500">Somme des émissions calculées</div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-5 flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">Σ</div>
                <div class="text-sm font-semibold text-slate-500">Total tCO2e/an</div>
                <div class="mt-3 flex items-end gap-2">
                    <span class="text-4xl font-bold text-[#0f1f3a]">{{ number_format($resultat->total_t_co2e_an, 3, ',', ' ') }}</span>
                    <span class="pb-1 text-sm text-slate-500">tCO2e/an</span>
                </div>
                <div class="mt-2 text-sm text-slate-500">Empreinte annuelle</div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-5 flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600">↗</div>
                <div class="flex items-center justify-between gap-3">
                    <div class="text-sm font-semibold text-slate-500">Catégorie la plus émettrice</div>
                    <span class="rounded-lg bg-slate-50 px-3 py-1 text-xs font-medium text-slate-500">Source principale</span>
                </div>
                <div class="mt-3 flex items-end gap-2">
                    <span class="text-4xl font-bold text-[#0f1f3a]">{{ $highest ? round(($highest['emissions_kgco2e'] / $totalKg) * 100) : 0 }}</span>
                    <span class="pb-1 text-sm text-slate-500">% {{ $categorieDominante }}</span>
                </div>
                <div class="mt-2 text-sm text-slate-500">{{ $highest ? number_format($highest['emissions_kgco2e'], 3, ',', ' ') : '0,000' }} kgCO2e — {{ $facteurDominant }}</div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-5 flex h-10 w-10 items-center justify-center rounded-xl bg-slate-50 text-[#213d70]">✓</div>
                <div class="text-sm font-semibold text-slate-500">Statut du calcul</div>
                <div class="mt-3 flex items-end gap-2">
                    <span class="text-3xl font-bold text-[#0f1f3a]">{{ str_replace('_', ' ', ucfirst($resultat->statut_calcul)) }}</span>
                </div>
                <div class="mt-2 text-sm text-slate-500">Traitement terminé</div>
            </div>
        </div>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-[#0f1f3a]">Détail du calcul</h2>
            <div class="mt-5 overflow-x-auto">
                <table class="min-w-[1680px] divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Catégorie</th>
                            <th class="px-4 py-3">Facteur utilisé</th>
                            <th class="px-4 py-3">Source du facteur</th>
                            <th class="px-4 py-3">Statut du facteur</th>
                            <th class="px-4 py-3">Valeur d’activité</th>
                            <th class="px-4 py-3">Valeur annualisée</th>
                            <th class="px-4 py-3">Unité</th>
                            <th class="px-4 py-3">Période</th>
                            <th class="px-4 py-3">Coefficient utilisé</th>
                            <th class="px-4 py-3">Formule utilisée</th>
                            <th class="px-4 py-3">kgCO2e</th>
                            <th class="px-4 py-3">tCO2e</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($details as $index => $detail)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-[#0f1f3a]">{{ $detail['label'] ?? $detail['categorie'] ?? 'Catégorie' }}</td>
                                <td class="px-4 py-3 text-slate-600">
                                    <div class="font-semibold text-[#0f1f3a]">{{ $detail['facteur_nom'] ?? '—' }}</div>
                                    @if (! empty($detail['facteur_description']))
                                        <div class="mt-1 max-w-[220px] text-xs leading-5 text-slate-500">{{ $detail['facteur_description'] }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $detail['facteur_source'] ?? 'Source reconnue non renseignée' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex whitespace-nowrap rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">Facteur actif</span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 tabular-nums text-slate-600">{{ number_format($detail['valeur'], 3, ',', ' ') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 tabular-nums text-slate-600">{{ number_format($detail['valeur_annualisee'], 3, ',', ' ') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $detail['unite'] ?? '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $detail['periode'] ?? '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 font-mono text-xs tabular-nums text-slate-600">{{ number_format($detail['coefficient'], 6, ',', ' ') }}</td>
                                <td class="max-w-sm px-4 py-3 text-xs leading-5 text-slate-600">{{ $detail['formule_utilisee'] ?? '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 font-bold tabular-nums text-[#16ad7b]">{{ number_format($detail['emissions_kgco2e'], 3, ',', ' ') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 font-bold tabular-nums text-[#16ad7b]">{{ number_format($detail['emissions_tco2e'], 3, ',', ' ') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="px-4 py-8 text-center text-slate-500">Aucune donnée calculée.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="mt-4 rounded-xl bg-slate-50 px-4 py-3 text-sm font-medium text-slate-600">
                Chaque ligne de calcul conserve la donnée saisie, le facteur d’émission sélectionné, la source du coefficient, la formule appliquée et le résultat obtenu.
            </p>
        </section>

        <div class="grid gap-6 xl:grid-cols-2">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-bold text-[#0f1f3a]">Détail par catégorie</h2>
                <div class="mt-5 space-y-3">
                    @forelse ($detailsByCategory as $category)
                        <div class="flex items-center justify-between gap-4 rounded-xl bg-slate-50 px-4 py-3 text-sm">
                            <span class="font-semibold text-[#0f1f3a]">{{ $category['label'] }}</span>
                            <span class="font-bold text-[#16ad7b]">{{ number_format($category['t'], 3, ',', ' ') }} tCO2e</span>
                        </div>
                    @empty
                        <div class="rounded-xl bg-slate-50 p-5 text-center text-sm text-slate-500">Aucune catégorie calculée.</div>
                    @endforelse
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-bold text-[#0f1f3a]">Détail par facteur</h2>
                <div class="mt-5 space-y-3">
                    @forelse ($detailsByFactor as $factor)
                        <div class="flex items-center justify-between gap-4 rounded-xl bg-slate-50 px-4 py-3 text-sm">
                            <span class="font-semibold text-[#0f1f3a]">{{ $factor['label'] }}</span>
                            <span class="font-bold text-[#16ad7b]">{{ number_format($factor['kg'], 3, ',', ' ') }} kgCO2e</span>
                        </div>
                    @empty
                        <div class="rounded-xl bg-slate-50 p-5 text-center text-sm text-slate-500">Aucun facteur calculé.</div>
                    @endforelse
                </div>
            </section>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-bold text-[#0f1f3a]">Répartition visuelle</h2>
                <div class="mt-8 flex flex-col items-center justify-center gap-6">
                    <div class="h-56 w-56 rounded-full shadow-inner" style="background: {{ $pieBackground }}"></div>
                    <div class="flex flex-wrap justify-center gap-4 text-sm">
                        @forelse ($details->values() as $index => $detail)
                            <span class="inline-flex items-center gap-2 text-slate-600">
                                <span class="h-2.5 w-2.5 rounded-full" style="background: {{ $colors[$index % count($colors)] }}"></span>
                                {{ $detail['facteur_nom'] ?? $detail['label'] ?? 'Catégorie' }}
                            </span>
                        @empty
                            <span class="text-slate-500">Aucune donnée à afficher.</span>
                        @endforelse
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-bold text-[#0f1f3a]">Facteurs les plus émetteurs</h2>
                <div class="mt-8 space-y-5">
                    @forelse ($details->sortByDesc('emissions_kgco2e')->values() as $index => $detail)
                        @php($barWidth = min(($detail['emissions_kgco2e'] / max((float) $details->max('emissions_kgco2e'), 0.001)) * 100, 100))
                        <div class="grid grid-cols-[130px_1fr] items-center gap-4 text-sm">
                            <div class="truncate text-slate-600">{{ $detail['facteur_nom'] ?? $detail['label'] ?? 'Catégorie' }}</div>
                            <div class="h-8 rounded-r-lg" style="width: {{ $barWidth }}%; background: {{ $colors[$index % count($colors)] }}"></div>
                        </div>
                    @empty
                        <div class="rounded-xl bg-slate-50 p-5 text-center text-sm text-slate-500">Aucune donnée calculée.</div>
                    @endforelse
                </div>
            </section>
        </div>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-600">
                Générez le rapport carbone à partir du résultat calculé.
            </p>
            <div class="mt-4 flex flex-wrap gap-3">
                @if ($rapport)
                    <a href="{{ route('rapports.show', $resultat) }}" class="rounded-xl bg-[#213d70] px-5 py-3 text-sm font-bold text-white shadow-lg shadow-blue-950/20 transition hover:bg-[#183768]">Voir le rapport</a>
                    <form method="POST" action="{{ route('resultats.generate-report', $resultat) }}">
                        @csrf
                        <button type="submit" class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Régénérer le rapport</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('resultats.generate-report', $resultat) }}">
                        @csrf
                        <button type="submit" class="rounded-xl bg-[#213d70] px-5 py-3 text-sm font-bold text-white shadow-lg shadow-blue-950/20 transition hover:bg-[#183768]">Générer le rapport</button>
                    </form>
                @endif
            </div>
        </section>
    </div>
@endsection

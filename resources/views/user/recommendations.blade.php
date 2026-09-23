@extends('layouts.dashboard')

@section('title', 'Recommandations')
@section('page-title', 'Recommandations')
@section('page-subtitle', 'Actions personnalisées pour réduire votre empreinte carbone.')

@section('header-actions')
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('resultats.show', $resultat) }}" class="rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">Retour aux résultats</a>
        @if ($recommendationThresholdExceeded)
            <form method="POST" action="{{ route('resultats.generate-recommendations', $resultat) }}">
                @csrf
                <button type="submit" class="rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                    {{ $recommandations->isNotEmpty() ? 'Régénérer' : 'Générer' }}
                </button>
            </form>
        @endif
        @if ($recommandations->isNotEmpty())
            <a href="{{ route('recommandations.download', $resultat) }}" class="rounded-xl bg-[#213d70] px-4 py-3 text-sm font-bold text-white shadow-lg shadow-blue-950/20 transition hover:bg-[#183768]">Télécharger PDF</a>
        @endif
    </div>
@endsection

@section('content')
    @php
        $latestSuggestion = $suggestionsIa->first();
        $highCount = $recommandations->where('priorite', 'elevee')->count();
        $mediumCount = $recommandations->where('priorite', 'moyenne')->count();
        $lowCount = $recommandations->where('priorite', 'faible')->count();
        $impactTotal = $recommandations->whereNotNull('impact_carbone_estime')->sum(fn ($item) => (float) $item->impact_carbone_estime);
        $priorityClasses = [
            'elevee' => 'border-red-500 bg-red-50 text-red-700',
            'moyenne' => 'border-orange-500 bg-orange-50 text-orange-700',
            'faible' => 'border-emerald-500 bg-emerald-50 text-emerald-700',
        ];
    @endphp

    <div class="mx-auto max-w-7xl space-y-6">
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

        @if ($recommandations->isNotEmpty())
            <section class="flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800">
                <span class="mt-0.5 flex h-5 w-5 items-center justify-center rounded-full border border-emerald-500 text-xs">✓</span>
                <div>
                    <div class="font-bold">Recommandations générées avec succès</div>
                    <div class="mt-1">{{ $recommandations->count() }} action(s) identifiée(s) à partir du résultat carbone.</div>
                </div>
            </section>
        @elseif (! $recommendationThresholdExceeded)
            <section class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-800">
                Les seuils d’émission sont acceptables. Aucune recommandation prioritaire n’est nécessaire pour ce résultat.
            </section>
        @else
            <section class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm font-semibold text-amber-800">
                Aucune recommandation n'a encore été générée pour ce résultat.
            </section>
        @endif

        <div class="grid gap-5 lg:grid-cols-3">
            <div class="rounded-2xl bg-[#16ad7b] p-6 text-white shadow-sm">
                <div class="text-sm text-emerald-50">Potentiel de réduction</div>
                <div class="mt-3 text-3xl font-bold">
                    {{ $impactTotal > 0 ? number_format($impactTotal, 3, ',', ' ').' kgCO2e' : 'À estimer' }}
                </div>
                <div class="mt-2 text-sm text-emerald-50">Selon les impacts renseignés</div>
            </div>

            <div class="rounded-2xl bg-[#25457d] p-6 text-white shadow-sm">
                <div class="text-sm text-blue-100">Actions prioritaires</div>
                <div class="mt-3 text-3xl font-bold">{{ $highCount }} élevée(s)</div>
                <div class="mt-2 text-sm text-blue-100">{{ $mediumCount }} moyenne(s) · {{ $lowCount }} faible(s)</div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="text-sm text-slate-500">Nombre de recommandations</div>
                <div class="mt-3 text-3xl font-bold text-[#0f1f3a]">{{ $recommandations->count() }}</div>
                <div class="mt-2 text-sm text-slate-500">{{ $highCount }} élevée · {{ $mediumCount }} moyenne · {{ $lowCount }} faible</div>
            </div>
        </div>

        @if ($latestSuggestion)
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-bold text-[#0f1f3a]">Suggestion globale</h2>
                <p class="mt-3 text-sm leading-6 text-slate-700">{{ $latestSuggestion->contenu }}</p>
            </section>
        @endif

        <div class="space-y-5">
            @forelse ($recommandations as $item)
                @php($tone = $priorityClasses[$item->priorite] ?? $priorityClasses['moyenne'])
                <article class="rounded-2xl border border-slate-200 border-l-4 {{ $tone }} bg-white p-6 shadow-sm">
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                        <div class="flex gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white/70">
                                <span class="h-2.5 w-2.5 rounded-full bg-current"></span>
                            </div>
                            <div>
                                <div class="flex flex-wrap items-center gap-3">
                                    <h2 class="text-lg font-bold text-[#0f1f3a]">Source : {{ $item->source_emission }}</h2>
                                    <span class="rounded-full bg-white px-3 py-1 text-xs font-bold">
                                        Priorité {{ ucfirst($item->priorite) }}
                                    </span>
                                </div>
                                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $item->probleme_detecte }}</p>
                            </div>
                        </div>
                        <div class="text-sm font-bold text-[#00a875] xl:text-right">
                            Impact estimé<br>
                            {{ $item->impact_carbone_estime ? number_format($item->impact_carbone_estime, 3, ',', ' ').' kgCO2e' : 'À estimer' }}
                        </div>
                    </div>
                    <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Action recommandée</div>
                        <p class="mt-2 text-sm leading-6 text-[#0f1f3a]">{{ $item->action_proposee }}</p>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm">
                    @if (! $recommendationThresholdExceeded)
                        <h2 class="text-lg font-bold text-[#0f1f3a]">Seuils acceptables</h2>
                        <p class="mt-2 text-sm text-slate-500">Aucune recommandation n’est nécessaire pour ce résultat, car les seuils d’émission sont acceptables.</p>
                    @else
                        <h2 class="text-lg font-bold text-[#0f1f3a]">Aucune recommandation générée</h2>
                        <p class="mt-2 text-sm text-slate-500">Les recommandations apparaîtront ici après génération.</p>
                        <form method="POST" action="{{ route('resultats.generate-recommendations', $resultat) }}" class="mt-5">
                            @csrf
                            <button type="submit" class="rounded-xl bg-[#16bd83] px-5 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-900/20 transition hover:bg-emerald-700">
                                Générer les recommandations
                            </button>
                        </form>
                    @endif
                </div>
            @endforelse
        </div>

    </div>
@endsection

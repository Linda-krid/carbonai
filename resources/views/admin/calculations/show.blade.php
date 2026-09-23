@extends('layouts.dashboard')

@section('title', 'Détail du calcul')
@section('page-title', 'Détail du calcul carbone')
@section('page-subtitle')
    {{ $resultat->entreprise?->nom ?? 'Entreprise supprimée' }} — {{ $resultat->created_at->format('d/m/Y') }}
@endsection

@section('header-actions')
    <a href="{{ route('admin.calculs.index') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">Retour aux calculs</a>
@endsection

@section('content')
    @php
        $statusLabel = \App\Http\Controllers\Admin\CalculationController::statusLabel($resultat->statut);
        $highest = $details->sortByDesc(fn ($detail) => (float) ($detail['emissions_kgco2e'] ?? 0))->first();
    @endphp

    <div class="mx-auto max-w-7xl space-y-6">
        <div class="grid gap-5 lg:grid-cols-3">
            <section class="rounded-2xl bg-[#16ad7b] p-6 text-white shadow-sm">
                <div class="text-sm text-emerald-50">Émissions totales</div>
                <div class="mt-3 text-3xl font-bold">{{ number_format((float) $resultat->total_t_co2e_an, 3, ',', ' ') }}</div>
                <div class="mt-1 text-sm text-emerald-50">tCO2e/an</div>
            </section>

            <section class="rounded-2xl bg-[#25457d] p-6 text-white shadow-sm">
                <div class="text-sm text-blue-100">Statut</div>
                <div class="mt-3 text-3xl font-bold">{{ $statusLabel }}</div>
                <div class="mt-1 text-sm text-blue-100">Calcul enregistré</div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="text-sm text-slate-500">Poste principal</div>
                <div class="mt-3 text-xl font-bold text-[#0f1f3a]">{{ $highest['facteur_nom'] ?? $highest['label'] ?? 'Non disponible' }}</div>
                <div class="mt-2 text-sm text-slate-500">{{ $highest ? number_format((float) ($highest['emissions_tco2e'] ?? 0), 3, ',', ' ').' tCO2e' : 'Aucune ligne' }}</div>
            </section>
        </div>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-[#0f1f3a]">Entreprise</h2>
            <div class="mt-5 grid gap-3 md:grid-cols-4">
                <div class="rounded-xl bg-slate-50 p-4">
                    <div class="text-xs text-slate-400">Nom</div>
                    <div class="mt-1 font-bold text-[#0f1f3a]">{{ $resultat->entreprise?->nom ?? 'Non renseigné' }}</div>
                </div>
                <div class="rounded-xl bg-slate-50 p-4">
                    <div class="text-xs text-slate-400">Secteur</div>
                    <div class="mt-1 font-bold text-[#0f1f3a]">{{ $resultat->entreprise?->secteur_activite ?? 'Non renseigné' }}</div>
                </div>
                <div class="rounded-xl bg-slate-50 p-4">
                    <div class="text-xs text-slate-400">Ville</div>
                    <div class="mt-1 font-bold text-[#0f1f3a]">{{ $resultat->entreprise?->ville ?? 'Non renseigné' }}</div>
                </div>
                <div class="rounded-xl bg-slate-50 p-4">
                    <div class="text-xs text-slate-400">Utilisateur</div>
                    <div class="mt-1 font-bold text-[#0f1f3a]">{{ $resultat->user?->name ?? 'Utilisateur supprimé' }}</div>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 p-6">
                <h2 class="text-lg font-bold text-[#0f1f3a]">Lignes de calcul</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Catégorie</th>
                            <th class="px-4 py-3">Facteur</th>
                            <th class="px-4 py-3">Source</th>
                            <th class="px-4 py-3">Valeur d’activité</th>
                            <th class="px-4 py-3">Valeur annualisée</th>
                            <th class="px-4 py-3">Période</th>
                            <th class="px-4 py-3">Coefficient</th>
                            <th class="px-4 py-3">kgCO2e</th>
                            <th class="px-4 py-3">tCO2e</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($details as $detail)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-[#0f1f3a]">{{ $detail['label'] ?? $detail['categorie'] ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $detail['facteur_nom'] ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $detail['facteur_source'] ?? 'Source non renseignée' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-700">{{ number_format((float) ($detail['valeur'] ?? 0), 3, ',', ' ') }} {{ $detail['unite'] ?? '' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-700">{{ number_format((float) ($detail['valeur_annualisee'] ?? 0), 3, ',', ' ') }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ ucfirst((string) ($detail['periode'] ?? '—')) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 font-mono text-slate-700">{{ $detail['coefficient'] ?? '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 font-bold text-[#0f1f3a]">{{ number_format((float) ($detail['emissions_kgco2e'] ?? 0), 3, ',', ' ') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 font-bold text-[#00a875]">{{ number_format((float) ($detail['emissions_tco2e'] ?? 0), 3, ',', ' ') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-5 py-10 text-center text-slate-500">Aucune ligne de calcul disponible.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection

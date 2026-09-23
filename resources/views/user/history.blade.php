@extends('layouts.dashboard')

@section('title', 'Historique')
@section('hide-page-heading', true)

@section('content')
    <div class="mx-auto max-w-7xl space-y-6">
        <section class="overflow-hidden rounded-2xl bg-[#25457d] px-8 py-7 text-white shadow-sm">
            <div class="max-w-2xl">
                <div class="text-xs font-semibold uppercase tracking-[0.22em] text-blue-100">Entreprise</div>
                <h1 class="mt-3 text-3xl font-bold">Historique des calculs</h1>
                <p class="mt-2 text-sm text-blue-100">Tous les bilans carbone enregistrés pour votre entreprise.</p>
            </div>
        </section>

        <div class="grid gap-5 lg:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-5 flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-[#213d70]">▣</div>
                <div class="text-sm font-semibold text-slate-500">Total des calculs</div>
                <div class="mt-3 text-4xl font-bold text-[#0f1f3a]">{{ $stats['total'] }}</div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-5 flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-[#16bd83]">↗</div>
                <div class="text-sm font-semibold text-slate-500">Calculs validés</div>
                <div class="mt-3 text-4xl font-bold text-[#00a875]">{{ $stats['validated'] }}</div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-5 flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-[#16bd83]">↗</div>
                <div class="text-sm font-semibold text-slate-500">Moyenne émissions</div>
                <div class="mt-3 flex items-end gap-2">
                    <span class="text-4xl font-bold text-[#0f1f3a]">{{ number_format($stats['average_t_co2e'], 3, ',', ' ') }}</span>
                    <span class="pb-1 text-sm text-slate-500">tCO2e</span>
                </div>
            </div>
        </div>

        <form method="GET" action="{{ route('historique.index') }}" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                <input
                    name="q"
                    value="{{ $filters['q'] }}"
                    placeholder="Rechercher par entreprise, ville ou secteur..."
                    class="min-w-0 flex-1 rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm shadow-sm focus:border-[#16bd83] focus:ring-[#16bd83]"
                >
                <div class="flex flex-wrap gap-2">
                    <button name="statut" value="tous" class="rounded-xl px-4 py-3 text-sm font-bold transition {{ $filters['statut'] === 'tous' ? 'bg-[#213d70] text-white' : 'bg-slate-50 text-slate-600 hover:bg-slate-100' }}">Tous</button>
                    <button name="statut" value="calcule" class="rounded-xl px-4 py-3 text-sm font-bold transition {{ $filters['statut'] === 'calcule' ? 'bg-[#213d70] text-white' : 'bg-slate-50 text-slate-600 hover:bg-slate-100' }}">Validé</button>
                    <button name="statut" value="en_attente" class="rounded-xl px-4 py-3 text-sm font-bold transition {{ $filters['statut'] === 'en_attente' ? 'bg-[#213d70] text-white' : 'bg-slate-50 text-slate-600 hover:bg-slate-100' }}">En attente</button>
                </div>
            </div>
        </form>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-4">Date du calcul</th>
                            <th class="px-5 py-4">Entreprise</th>
                            <th class="px-5 py-4">Émissions (tCO2e/an)</th>
                            <th class="px-5 py-4">Statut</th>
                            <th class="px-5 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($resultats as $resultat)
                            <tr>
                                <td class="px-5 py-4 text-slate-600">{{ $resultat->created_at->format('d/m/Y') }}</td>
                                <td class="px-5 py-4">
                                    <div class="font-bold text-[#0f1f3a]">{{ $resultat->entreprise->nom }}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ $resultat->entreprise->ville }} — {{ $resultat->entreprise->annee_calcul }}</div>
                                </td>
                                <td class="px-5 py-4 font-bold text-[#00a875]">
                                    {{ number_format($resultat->total_t_co2e_an, 3, ',', ' ') }}
                                    <span class="text-xs font-medium text-slate-500">tCO2e/an</span>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">
                                        {{ $resultat->statut === 'calcule' ? 'Validé' : str_replace('_', ' ', ucfirst($resultat->statut)) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('resultats.show', $resultat) }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50">Résultats</a>
                                        @if (count($resultat->facteurs_eleves ?? []) > 0)
                                            <a href="{{ route('recommandations.show', $resultat) }}" class="rounded-lg bg-[#16bd83] px-3 py-2 text-xs font-bold text-white transition hover:bg-emerald-700">Reco.</a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-10 text-center text-slate-500">Aucun calcul enregistré.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $resultats->links() }}
            </div>
        </section>
    </div>
@endsection

@extends('layouts.dashboard')

@section('title', 'Calculs carbone')
@section('page-title', 'Calculs carbone')
@section('page-subtitle', 'Consultez les calculs réalisés par les entreprises de la plateforme.')

@section('content')
    @php
        $statusFilters = [
            '' => 'Tous',
            'calcule' => 'Calculé',
            'a_verifier' => 'À vérifier',
            'en_attente' => 'En attente',
        ];
    @endphp

    <div class="mx-auto max-w-7xl space-y-6">
        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center">
                <form method="GET" action="{{ route('admin.calculs.index') }}" class="min-w-0 flex-1">
                    <input type="hidden" name="statut" value="{{ $filters['statut'] }}">
                    <input
                        name="q"
                        value="{{ $filters['q'] }}"
                        placeholder="Rechercher une entreprise, ville ou secteur..."
                        class="block w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm shadow-sm focus:border-[#16bd83] focus:ring-[#16bd83]"
                    >
                </form>

                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-sm text-slate-500">Statut :</span>
                    @foreach ($statusFilters as $key => $label)
                        <a href="{{ route('admin.calculs.index', array_filter(['q' => $filters['q'], 'statut' => $key])) }}" class="rounded-xl px-4 py-3 text-sm font-bold transition {{ $filters['statut'] === $key ? 'bg-[#213d70] text-white' : 'bg-slate-50 text-slate-600 hover:bg-slate-100' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-4">Entreprise</th>
                            <th class="px-5 py-4">Utilisateur</th>
                            <th class="px-5 py-4">Date</th>
                            <th class="px-5 py-4">Statut</th>
                            <th class="px-5 py-4">Total tCO2e/an</th>
                            <th class="px-5 py-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($calculs as $calcul)
                            @php($statusLabel = \App\Http\Controllers\Admin\CalculationController::statusLabel($calcul->statut))
                            <tr>
                                <td class="px-5 py-4">
                                    <div class="font-bold text-[#0f1f3a]">{{ $calcul->entreprise?->nom ?? 'Entreprise supprimée' }}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ $calcul->entreprise?->ville ?? '-' }} — {{ $calcul->entreprise?->annee_calcul ?? '-' }}</div>
                                </td>
                                <td class="px-5 py-4 text-slate-600">{{ $calcul->user?->name ?? 'Utilisateur supprimé' }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ $calcul->created_at->format('d/m/Y') }}</td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">{{ $statusLabel }}</span>
                                </td>
                                <td class="px-5 py-4 font-bold text-[#00a875]">{{ number_format($calcul->total_t_co2e_an, 3, ',', ' ') }} tCO2e/an</td>
                                <td class="px-5 py-4 text-right">
                                    <a href="{{ route('admin.calculs.show', $calcul) }}" class="text-sm font-bold text-[#213d70] transition hover:text-[#00a875]">Voir détails</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center text-slate-500">Aucun calcul disponible.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $calculs->links() }}
            </div>
        </section>
    </div>
@endsection

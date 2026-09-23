@extends('layouts.dashboard')

@section('title', 'Tableau de bord admin')
@section('hide-page-heading', true)

@section('content')
    @php
        $monthMax = max((int) $monthlySeries->max('count'), 1);
        $sectorTotal = max((int) $sectorDistribution->sum('count'), 1);
        $sectorColors = ['#16bd83', '#3b82f6', '#8b5cf6', '#f59e0b', '#94a3b8', '#06b6d4'];
        $cursor = 0;
        $sectorStops = [];
        foreach ($sectorDistribution as $index => $sector) {
            $percentage = ($sector['count'] / $sectorTotal) * 100;
            $end = min($cursor + $percentage, 100);
            $sectorStops[] = $sectorColors[$index % count($sectorColors)].' '.$cursor.'% '.$end.'%';
            $cursor = $end;
        }
        $sectorBackground = $sectorStops ? 'conic-gradient('.implode(', ', $sectorStops).')' : '#e2e8f0';
    @endphp

    <div class="mx-auto max-w-7xl space-y-6">
        <section class="rounded-2xl bg-[#25457d] px-8 py-8 text-white shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-[0.22em] text-blue-100">Administration</div>
            <h1 class="mt-3 text-3xl font-bold">Tableau de bord</h1>
            <p class="mt-2 text-sm text-blue-100">Supervision de la plateforme CarbonAI — Mise à jour : {{ now()->format('d/m/Y') }}</p>
        </section>

        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-blue-100 bg-white p-6 shadow-sm">
                <div class="mb-5 flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600">♙</div>
                <div class="text-sm text-slate-500">Utilisateurs</div>
                <div class="mt-3 text-4xl font-bold text-[#0f1f3a]">{{ $stats['users'] }}</div>
            </div>
            <div class="rounded-2xl border border-violet-100 bg-white p-6 shadow-sm">
                <div class="mb-5 flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50 text-violet-600">▥</div>
                <div class="text-sm text-slate-500">Entreprises</div>
                <div class="mt-3 text-4xl font-bold text-[#0f1f3a]">{{ $stats['entreprises'] }}</div>
            </div>
            <div class="rounded-2xl border border-emerald-100 bg-white p-6 shadow-sm">
                <div class="mb-5 flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-[#16bd83]">▤</div>
                <div class="text-sm text-slate-500">Facteurs d'émission</div>
                <div class="mt-3 text-4xl font-bold text-[#0f1f3a]">{{ $stats['facteurs'] }}</div>
            </div>
            <div class="rounded-2xl border border-orange-100 bg-white p-6 shadow-sm">
                <div class="mb-5 flex h-10 w-10 items-center justify-center rounded-xl bg-orange-50 text-orange-600">!</div>
                <div class="text-sm text-slate-500">Bilans à vérifier</div>
                <div class="mt-3 text-4xl font-bold text-[#0f1f3a]">{{ $stats['a_verifier'] }}</div>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold text-[#0f1f3a]">Calculs par mois</h2>
                    <span class="rounded-lg bg-slate-50 px-3 py-1 text-xs font-medium text-slate-500">{{ now()->year }}</span>
                </div>
                <div class="mt-7 grid h-56 grid-cols-6 items-end gap-4 border-b border-l border-dashed border-slate-100 px-4">
                    @foreach ($monthlySeries as $month)
                        @php($height = max(($month['count'] / $monthMax) * 100, $month['count'] > 0 ? 8 : 0))
                        <div class="flex h-full flex-col justify-end gap-2">
                            <div class="rounded-t-lg bg-[#16bd83] transition" style="height: {{ $height }}%"></div>
                            <div class="text-center text-xs text-slate-500">{{ $month['label'] }}</div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-bold text-[#0f1f3a]">Répartition par secteur</h2>
                <div class="mt-8 flex flex-col items-center gap-6">
                    <div class="h-52 w-52 rounded-full shadow-inner" style="background: {{ $sectorBackground }}"></div>
                    <div class="flex flex-wrap justify-center gap-4 text-sm">
                        @forelse ($sectorDistribution as $index => $sector)
                            <span class="inline-flex items-center gap-2 text-slate-600">
                                <span class="h-2.5 w-2.5 rounded-full" style="background: {{ $sectorColors[$index % count($sectorColors)] }}"></span>
                                {{ $sector['label'] }}
                            </span>
                        @empty
                            <span class="text-slate-500">Aucune entreprise enregistrée.</span>
                        @endforelse
                    </div>
                </div>
            </section>
        </div>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 p-6">
                <h2 class="text-lg font-bold text-[#0f1f3a]">Activité récente</h2>
                <a href="{{ route('admin.calculs.index') }}" class="text-sm font-bold text-[#00a875] hover:text-emerald-700">Voir tout →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-4">Entreprise</th>
                            <th class="px-5 py-4">Dernier calcul</th>
                            <th class="px-5 py-4">Statut</th>
                            <th class="px-5 py-4">Total tCO2e/an</th>
                            <th class="px-5 py-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($derniersCalculs as $calcul)
                            @php($statusLabel = \App\Http\Controllers\Admin\CalculationController::statusLabel($calcul->statut))
                            <tr>
                                <td class="px-5 py-4 font-bold text-[#0f1f3a]">{{ $calcul->entreprise?->nom ?? 'Entreprise supprimée' }} — {{ $calcul->entreprise?->ville ?? '-' }}</td>
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
                                <td colspan="5" class="px-5 py-10 text-center text-slate-500">Aucun calcul disponible.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection

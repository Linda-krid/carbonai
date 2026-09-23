@extends('layouts.dashboard')

@section('title', 'Résultats carbone')
@section('hide-page-heading', true)

@section('content')
    @php
        $zeroCategories = [
            ['label' => 'Production', 'color' => '#16bd83'],
            ['label' => 'Carburant', 'color' => '#3b82f6'],
            ['label' => 'Machines', 'color' => '#8b5cf6'],
            ['label' => 'Transport', 'color' => '#f59e0b'],
            ['label' => 'Déchets', 'color' => '#ef4444'],
        ];
    @endphp

    <div class="mx-auto max-w-7xl space-y-6">
        <section class="overflow-hidden rounded-2xl bg-[#25457d] px-8 py-7 text-white shadow-sm">
            <div class="max-w-2xl">
                <div class="text-xs font-semibold uppercase tracking-[0.22em] text-blue-100">Entreprise</div>
                <h1 class="mt-3 text-3xl font-bold">Résultats carbone</h1>
                <p class="mt-2 text-sm text-blue-100">Analyse de votre empreinte carbone.</p>
            </div>
        </section>

        <div class="grid gap-5 xl:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-5 flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">↗</div>
                <div class="text-sm font-semibold text-slate-500">Total kgCO2e</div>
                <div class="mt-3 flex items-end gap-2">
                    <span class="text-4xl font-bold text-[#0f1f3a]">0,000</span>
                    <span class="pb-1 text-sm text-slate-500">kgCO2e</span>
                </div>
                <div class="mt-2 text-sm text-slate-500">Somme des émissions calculées</div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-5 flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">Σ</div>
                <div class="text-sm font-semibold text-slate-500">Total tCO2e/an</div>
                <div class="mt-3 flex items-end gap-2">
                    <span class="text-4xl font-bold text-[#0f1f3a]">0,000</span>
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
                    <span class="text-4xl font-bold text-[#0f1f3a]">0</span>
                    <span class="pb-1 text-sm text-slate-500">%</span>
                </div>
                <div class="mt-2 text-sm text-slate-500">&nbsp;</div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-5 flex h-10 w-10 items-center justify-center rounded-xl bg-slate-50 text-[#213d70]">✓</div>
                <div class="text-sm font-semibold text-slate-500">Statut du calcul</div>
                <div class="mt-3 flex items-end gap-2">
                    <span class="text-3xl font-bold text-[#0f1f3a]">0</span>
                </div>
                <div class="mt-2 text-sm text-slate-500">Calculs effectués</div>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-bold text-[#0f1f3a]">Répartition visuelle</h2>
                <div class="mt-8 flex min-h-64 flex-col items-center justify-center gap-6">
                    <div class="flex h-56 w-56 items-center justify-center rounded-full bg-slate-100 text-4xl font-bold text-slate-400 shadow-inner">
                        0
                    </div>
                    <div class="flex flex-wrap justify-center gap-4 text-sm">
                        @foreach ($zeroCategories as $category)
                            <span class="inline-flex items-center gap-2 text-slate-600">
                                <span class="h-2.5 w-2.5 rounded-full" style="background: {{ $category['color'] }}"></span>
                                {{ $category['label'] }} 0%
                            </span>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-bold text-[#0f1f3a]">Facteurs les plus émetteurs</h2>
                <div class="mt-8 min-h-64 space-y-5">
                    @foreach ($zeroCategories as $category)
                        <div class="grid grid-cols-[130px_1fr_90px] items-center gap-4 text-sm">
                            <div class="truncate text-slate-600">{{ $category['label'] }}</div>
                            <div class="h-8 rounded-r-lg bg-slate-100"></div>
                            <div class="text-right font-semibold text-slate-500">0,0 tCO2e</div>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 p-6">
                <h2 class="text-lg font-bold text-[#0f1f3a]">Détail du calcul</h2>
            </div>
            <div class="overflow-x-auto">
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
                        <tr>
                            <td class="px-4 py-4 font-semibold text-[#0f1f3a]">
                                <span class="mr-2 inline-flex h-2.5 w-2.5 rounded-full bg-slate-300"></span>
                                0
                            </td>
                            <td class="px-4 py-4 text-slate-600">0</td>
                            <td class="px-4 py-4 text-slate-600">0</td>
                            <td class="px-4 py-4">
                                <span class="inline-flex whitespace-nowrap rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-500">Aucun facteur</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-4 tabular-nums text-slate-600">0,000</td>
                            <td class="whitespace-nowrap px-4 py-4 tabular-nums text-slate-600">0,000</td>
                            <td class="px-4 py-4 text-slate-600">0</td>
                            <td class="px-4 py-4 text-slate-600">0</td>
                            <td class="whitespace-nowrap px-4 py-4 font-mono text-xs tabular-nums text-slate-600">0,000000</td>
                            <td class="px-4 py-4 text-xs text-slate-600">0</td>
                            <td class="whitespace-nowrap px-4 py-4 font-bold tabular-nums text-[#16ad7b]">0,000</td>
                            <td class="whitespace-nowrap px-4 py-4 font-bold tabular-nums text-[#16ad7b]">0,000</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="border-t border-slate-100 px-6 py-4 text-sm font-medium text-slate-600">
                Chaque ligne de calcul conservera la donnée saisie, le facteur d’émission sélectionné, la source du coefficient, la formule appliquée et le résultat obtenu.
            </p>
        </section>
    </div>
@endsection

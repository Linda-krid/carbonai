@extends('layouts.dashboard')

@section('title', "Gestion des facteurs d'émission")
@section('page-title', "Gestion des facteurs d'émission")
@section('page-subtitle', "Ajoutez, modifiez ou supprimez les facteurs d'émission utilisés dans les calculs.")

@section('header-actions')
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admin.facteurs.export', request()->only(['q', 'categorie', 'statut'])) }}" class="rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-bold text-[#0f1f3a] shadow-sm transition hover:bg-slate-50">Exporter</a>
        <a href="{{ route('admin.facteurs.create') }}" class="rounded-xl bg-[#16bd83] px-5 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-900/20 transition hover:bg-emerald-700">+ Ajouter un facteur</a>
    </div>
@endsection

@section('content')
    @php
        $statusFilters = ['tous' => 'Tous', 'actif' => 'Actif', 'inactif' => 'Inactif'];
    @endphp

    <div class="mx-auto max-w-7xl space-y-6">
        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="grid gap-4">
                <div class="flex flex-col gap-3 xl:flex-row xl:items-center">
                    <form method="GET" action="{{ route('admin.facteurs.index') }}" class="min-w-0 flex-1">
                        <input type="hidden" name="categorie" value="{{ $filters['categorie'] }}">
                        <input type="hidden" name="statut" value="{{ $filters['statut'] }}">
                        <input
                            name="q"
                            value="{{ $filters['q'] }}"
                            placeholder="Rechercher un facteur..."
                            class="block w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm shadow-sm focus:border-[#16bd83] focus:ring-[#16bd83]"
                        >
                    </form>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-sm text-slate-500">Statut :</span>
                        @foreach ($statusFilters as $key => $label)
                            <a href="{{ route('admin.facteurs.index', array_filter(['q' => $filters['q'], 'categorie' => $filters['categorie'], 'statut' => $key !== 'tous' ? $key : null])) }}" class="rounded-xl px-4 py-3 text-sm font-bold transition {{ $filters['statut'] === $key ? 'bg-[#213d70] text-white' : 'bg-slate-50 text-slate-600 hover:bg-slate-100' }}">
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('admin.facteurs.index', array_filter(['q' => $filters['q'], 'statut' => $filters['statut'] !== 'tous' ? $filters['statut'] : null])) }}" class="rounded-xl px-4 py-2 text-sm font-bold transition {{ $filters['categorie'] === '' ? 'bg-[#16bd83] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        Tous
                    </a>
                    @foreach ($categories as $key => $category)
                        <a href="{{ route('admin.facteurs.index', array_filter(['q' => $filters['q'], 'categorie' => $key, 'statut' => $filters['statut'] !== 'tous' ? $filters['statut'] : null])) }}" class="rounded-xl px-4 py-2 text-sm font-bold transition {{ $filters['categorie'] === $key ? 'bg-[#16bd83] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                            {{ $category['label'] }}
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
                            <th class="px-5 py-4">Nom du facteur</th>
                            <th class="px-5 py-4">Catégorie</th>
                            <th class="px-5 py-4">Valeur</th>
                            <th class="px-5 py-4">Unité</th>
                            <th class="px-5 py-4">Source</th>
                            <th class="px-5 py-4">Mise à jour</th>
                            <th class="px-5 py-4">Statut</th>
                            <th class="px-5 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($facteurs as $facteur)
                            <tr>
                                <td class="px-5 py-4 font-bold text-[#0f1f3a]">{{ $facteur->nom }}</td>
                                <td class="px-5 py-4">
                                    <span class="rounded-lg bg-slate-100 px-3 py-1 text-xs font-semibold text-[#0f1f3a]">
                                        {{ data_get($categories, $facteur->categorie.'.label', $facteur->categorie) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 font-mono font-bold text-[#00a875]">{{ $facteur->coefficient }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ $facteur->unite }}</td>
                                <td class="px-5 py-4">
                                    @if ($facteur->source)
                                        <span class="rounded-lg bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">{{ $facteur->source }}</span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-slate-600">{{ $facteur->updated_at->format('d/m/Y') }}</td>
                                <td class="px-5 py-4">
                                    <span class="rounded-lg px-3 py-1 text-xs font-bold {{ $facteur->actif ? 'bg-emerald-50 text-emerald-700' : 'bg-orange-50 text-orange-700' }}">
                                        {{ $facteur->actif ? 'Actif' : 'Inactif' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right align-middle">
                                    <div class="inline-flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.facteurs.edit', $facteur) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-emerald-100 bg-white text-[#16bd83] shadow-sm transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-200" title="Modifier" aria-label="Modifier">
                                            <svg class="h-4 w-4 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 20h9" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5Z" />
                                            </svg>
                                        </a>
                                        <form method="POST" action="{{ route('admin.facteurs.destroy', $facteur) }}" class="m-0 inline-flex h-9 w-9 items-center justify-center" onsubmit="return confirm('Supprimer ce facteur ?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-red-100 bg-white text-red-500 shadow-sm transition hover:border-red-200 hover:bg-red-50 hover:text-red-700 focus:outline-none focus:ring-2 focus:ring-red-200" title="Supprimer" aria-label="Supprimer">
                                                <svg class="h-4 w-4 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 6V4h8v2" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 6l-1 14H6L5 6" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 11v6" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 11v6" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-5 py-10 text-center text-slate-500">
                                    Aucun facteur enregistré. Ajoutez les facteurs validés par l'administrateur pour permettre les calculs.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $facteurs->links() }}
            </div>
        </section>
    </div>
@endsection

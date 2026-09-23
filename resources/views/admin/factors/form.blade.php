@extends('layouts.dashboard')

@section('title', $facteur->exists ? "Modifier un facteur d'émission" : "Ajouter un facteur d'émission")
@section('hide-page-heading', true)

@section('content')
    <div class="mx-auto max-w-2xl">
        <header class="mb-8 flex items-start gap-5">
            <a href="{{ route('admin.facteurs.index') }}" class="mt-2 rounded-xl p-2 text-2xl text-[#0f1f3a] transition hover:bg-slate-100">←</a>
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-[#0f1f3a]">{{ $facteur->exists ? "Modifier un facteur d'émission" : "Ajouter un facteur d'émission" }}</h1>
                <p class="mt-2 text-sm text-slate-600">{{ $facteur->exists ? 'Mettez à jour ce facteur dans la base de données.' : 'Créer un nouveau facteur dans la base de données.' }}</p>
            </div>
        </header>

        <form method="POST" action="{{ $facteur->exists ? route('admin.facteurs.update', $facteur) : route('admin.facteurs.store') }}" class="space-y-6">
            @csrf
            @if ($facteur->exists)
                @method('PATCH')
            @endif

            <section class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
                <div class="space-y-5">
                    <div>
                        <label for="nom" class="block text-sm font-bold text-[#0f1f3a]">Nom du facteur <span class="text-red-500">*</span></label>
                        <input id="nom" name="nom" value="{{ old('nom', $facteur->nom) }}" placeholder="Nom du facteur" required class="mt-2 block w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-3 text-sm shadow-sm focus:border-[#16bd83] focus:ring-[#16bd83]">
                        <x-input-error :messages="$errors->get('nom')" class="mt-2" />
                    </div>

                    <div>
                        <label for="categorie" class="block text-sm font-bold text-[#0f1f3a]">Catégorie <span class="text-red-500">*</span></label>
                        <select id="categorie" name="categorie" required class="mt-2 block w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-3 text-sm shadow-sm focus:border-[#16bd83] focus:ring-[#16bd83]">
                            <option value="">Sélectionner une catégorie</option>
                            @foreach ($categories as $key => $category)
                                <option value="{{ $key }}" @selected(old('categorie', $facteur->categorie) === $key)>{{ $category['label'] }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('categorie')" class="mt-2" />
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <div>
                            <label for="coefficient" class="block text-sm font-bold text-[#0f1f3a]">Valeur du coefficient <span class="text-red-500">*</span></label>
                            <input id="coefficient" type="number" step="0.000001" min="0" name="coefficient" value="{{ old('coefficient', $facteur->coefficient) }}" placeholder="0.000000" required class="mt-2 block w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-3 text-sm shadow-sm focus:border-[#16bd83] focus:ring-[#16bd83]">
                            <x-input-error :messages="$errors->get('coefficient')" class="mt-2" />
                        </div>
                        <div>
                            <label for="unite" class="block text-sm font-bold text-[#0f1f3a]">Unité <span class="text-red-500">*</span></label>
                            <input id="unite" name="unite" value="{{ old('unite', $facteur->unite) }}" placeholder="Unité du facteur" required class="mt-2 block w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-3 text-sm shadow-sm focus:border-[#16bd83] focus:ring-[#16bd83]">
                            <x-input-error :messages="$errors->get('unite')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <label for="source" class="block text-sm font-bold text-[#0f1f3a]">Source</label>
                        <input id="source" name="source" value="{{ old('source', $facteur->source) }}" placeholder="Source documentaire ou référence" class="mt-2 block w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-3 text-sm shadow-sm focus:border-[#16bd83] focus:ring-[#16bd83]">
                        <x-input-error :messages="$errors->get('source')" class="mt-2" />
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-bold text-[#0f1f3a]">Description</label>
                        <textarea id="description" name="description" rows="4" placeholder="Description optionnelle du facteur d'émission..." class="mt-2 block w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-3 text-sm shadow-sm focus:border-[#16bd83] focus:ring-[#16bd83]">{{ old('description', $facteur->description ?? '') }}</textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>

                    <div>
                        <div class="mb-3 text-sm font-bold text-[#0f1f3a]">Statut</div>
                        <div class="flex gap-3" x-data="{ active: @js((bool) old('actif', $facteur->actif)) }">
                            <input type="hidden" name="actif" x-bind:value="active ? 1 : 0">
                            <button type="button" x-on:click="active = true" x-bind:class="active ? 'border-[#16bd83] bg-emerald-50 text-[#00a875]' : 'border-slate-300 bg-white text-slate-600'" class="rounded-xl border px-6 py-3 text-sm font-bold transition">Actif</button>
                            <button type="button" x-on:click="active = false" x-bind:class="!active ? 'border-[#213d70] bg-blue-50 text-[#213d70]' : 'border-slate-300 bg-white text-slate-600'" class="rounded-xl border px-6 py-3 text-sm font-bold transition">Inactif</button>
                        </div>
                    </div>
                </div>
            </section>

            <div class="flex items-center justify-between">
                <a href="{{ route('admin.facteurs.index') }}" class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Annuler</a>
                <button class="rounded-xl bg-[#16bd83] px-8 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-900/20 transition hover:bg-emerald-700">
                    Enregistrer
                </button>
            </div>
        </form>
    </div>
@endsection

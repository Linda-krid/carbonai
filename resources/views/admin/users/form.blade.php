@extends('layouts.dashboard')

@section('title', 'Modifier un utilisateur')
@section('hide-page-heading', true)

@section('content')
    <div class="mx-auto max-w-2xl">
        <header class="mb-8 flex items-start gap-5">
            <a href="{{ route('admin.users.index') }}" class="mt-2 rounded-xl p-2 text-2xl text-[#0f1f3a] transition hover:bg-slate-100">←</a>
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-[#0f1f3a]">Modifier un utilisateur</h1>
                <p class="mt-2 text-sm text-slate-600">Mettez à jour les informations du compte utilisateur.</p>
            </div>
        </header>

        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-6">
            @csrf
            @method('PATCH')

            <section class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
                <div class="space-y-5">
                    <div>
                        <label for="name" class="block text-sm font-bold text-[#0f1f3a]">Nom complet <span class="text-red-500">*</span></label>
                        <input id="name" name="name" value="{{ old('name', $user->name) }}" required class="mt-2 block w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-3 text-sm shadow-sm focus:border-[#16bd83] focus:ring-[#16bd83]">
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-bold text-[#0f1f3a]">Email <span class="text-red-500">*</span></label>
                        <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required class="mt-2 block w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-3 text-sm shadow-sm focus:border-[#16bd83] focus:ring-[#16bd83]">
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-bold text-[#0f1f3a]">Nouveau mot de passe</label>
                        <input id="password" type="password" name="password" placeholder="Laisser vide pour conserver le mot de passe actuel" class="mt-2 block w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-3 text-sm shadow-sm focus:border-[#16bd83] focus:ring-[#16bd83]">
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div>
                        <label for="entreprise" class="block text-sm font-bold text-[#0f1f3a]">Entreprise</label>
                        <input id="entreprise" name="entreprise" value="{{ old('entreprise', $entreprise?->nom) }}" class="mt-2 block w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-3 text-sm shadow-sm focus:border-[#16bd83] focus:ring-[#16bd83]">
                        <x-input-error :messages="$errors->get('entreprise')" class="mt-2" />
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <div>
                            <label for="role" class="block text-sm font-bold text-[#0f1f3a]">Rôle <span class="text-red-500">*</span></label>
                            <select id="role" name="role" required class="mt-2 block w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-3 text-sm shadow-sm focus:border-[#16bd83] focus:ring-[#16bd83]">
                                <option value="utilisateur" @selected(old('role', $user->role) === 'utilisateur')>Utilisateur</option>
                                <option value="admin" @selected(old('role', $user->role) === 'admin')>Admin</option>
                            </select>
                            <x-input-error :messages="$errors->get('role')" class="mt-2" />
                        </div>

                        <div>
                            <label for="statut" class="block text-sm font-bold text-[#0f1f3a]">Statut <span class="text-red-500">*</span></label>
                            <select id="statut" name="statut" required class="mt-2 block w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-3 text-sm shadow-sm focus:border-[#16bd83] focus:ring-[#16bd83]">
                                <option value="actif" @selected(old('statut', filled($user->email_verified_at) ? 'actif' : 'inactif') === 'actif')>Actif</option>
                                <option value="inactif" @selected(old('statut', filled($user->email_verified_at) ? 'actif' : 'inactif') === 'inactif')>Inactif</option>
                            </select>
                            <x-input-error :messages="$errors->get('statut')" class="mt-2" />
                        </div>
                    </div>
                </div>
            </section>

            <div class="flex items-center justify-between">
                <a href="{{ route('admin.users.index') }}" class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Annuler</a>
                <button class="rounded-xl bg-[#16bd83] px-8 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-900/20 transition hover:bg-emerald-700">
                    Enregistrer
                </button>
            </div>
        </form>
    </div>
@endsection

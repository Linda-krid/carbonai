@extends('layouts.dashboard')

@section('title', 'Gestion des utilisateurs')
@section('page-title', 'Gestion des utilisateurs')
@section('page-subtitle', 'Gérez les comptes des utilisateurs de la plateforme.')

@section('header-actions')
    <button type="button" x-data x-on:click="$dispatch('open-create-user')" class="rounded-xl bg-[#16bd83] px-5 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-900/20 transition hover:bg-emerald-700">
        + Ajouter un utilisateur
    </button>
@endsection

@section('content')
    @php
        $roleFilters = ['tous' => 'Tous', 'admin' => 'Admin', 'utilisateur' => 'Utilisateur'];
        $statusFilters = ['tous' => 'Tous', 'actif' => 'Actif', 'inactif' => 'Inactif'];
    @endphp

    <div
        class="mx-auto max-w-7xl space-y-6"
        x-data="{ showCreateUser: @js($errors->any() && old('create_user')) }"
        x-on:open-create-user.window="showCreateUser = true"
    >
        @if ($errors->has('user'))
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                {{ $errors->first('user') }}
            </div>
        @endif

        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center">
                <form method="GET" action="{{ route('admin.users.index') }}" class="min-w-0 flex-1">
                    <input type="hidden" name="role" value="{{ $filters['role'] }}">
                    <input type="hidden" name="statut" value="{{ $filters['statut'] }}">
                    <input
                        name="q"
                        value="{{ $filters['q'] }}"
                        placeholder="Rechercher..."
                        class="block w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm shadow-sm focus:border-[#16bd83] focus:ring-[#16bd83]"
                    >
                </form>

                <div class="flex flex-wrap items-center gap-2">
                    @foreach ($roleFilters as $key => $label)
                        <a href="{{ route('admin.users.index', array_filter(['q' => $filters['q'], 'role' => $key, 'statut' => $filters['statut'] !== 'tous' ? $filters['statut'] : null])) }}" class="rounded-xl px-4 py-3 text-sm font-bold transition {{ $filters['role'] === $key ? 'bg-[#16bd83] text-white' : 'bg-slate-50 text-slate-600 hover:bg-slate-100' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                    <span class="mx-1 hidden h-6 w-px bg-slate-200 sm:inline"></span>
                    @foreach ($statusFilters as $key => $label)
                        <a href="{{ route('admin.users.index', array_filter(['q' => $filters['q'], 'role' => $filters['role'] !== 'tous' ? $filters['role'] : null, 'statut' => $key])) }}" class="rounded-xl px-4 py-3 text-sm font-bold transition {{ $filters['statut'] === $key ? 'bg-[#213d70] text-white' : 'bg-slate-50 text-slate-600 hover:bg-slate-100' }}">
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
                            <th class="px-5 py-4">Nom</th>
                            <th class="px-5 py-4">Email</th>
                            <th class="px-5 py-4">Entreprise</th>
                            <th class="px-5 py-4">Rôle</th>
                            <th class="px-5 py-4">Statut</th>
                            <th class="px-5 py-4">Inscription</th>
                            <th class="px-5 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($users as $user)
                            @php
                                $isActive = filled($user->email_verified_at);
                                $entreprise = $user->entreprises->first();
                            @endphp
                            <tr>
                                <td class="px-5 py-4 font-bold text-[#0f1f3a]">{{ $user->name }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ $user->email }}</td>
                                <td class="px-5 py-4 text-[#0f1f3a]">{{ $entreprise?->nom ?? '—' }}</td>
                                <td class="px-5 py-4">
                                    <span class="rounded-lg px-3 py-1 text-xs font-bold {{ $user->role === 'admin' ? 'bg-violet-50 text-violet-700' : 'bg-blue-50 text-blue-700' }}">
                                        {{ $user->role === 'admin' ? 'Admin' : 'Utilisateur' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="rounded-lg px-3 py-1 text-xs font-bold {{ $isActive ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">
                                        {{ $isActive ? 'Actif' : 'Inactif' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-slate-600">{{ $user->created_at->format('d/m/Y') }}</td>
                                <td class="px-5 py-4 text-right align-middle">
                                    <div class="inline-flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.users.edit', $user) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-emerald-100 bg-white text-[#16bd83] shadow-sm transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-200" title="Modifier" aria-label="Modifier">
                                            <svg class="h-4 w-4 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 20h9" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5Z" />
                                            </svg>
                                        </a>
                                        @if (! $user->is(auth()->user()))
                                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="m-0 inline-flex h-9 w-9 items-center justify-center" onsubmit="return confirm('Supprimer cet utilisateur ?')">
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
                                        @else
                                            <span class="h-9 w-9" aria-hidden="true"></span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-10 text-center text-slate-500">Aucun utilisateur trouvé.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $users->links() }}
            </div>
        </section>

        <div x-cloak x-show="showCreateUser" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/45 px-4" x-transition.opacity>
            <div class="w-full max-w-xl overflow-hidden rounded-2xl bg-white shadow-2xl" x-on:click.outside="showCreateUser = false">
                <div class="flex items-start justify-between border-b border-slate-100 px-6 py-5">
                    <div>
                        <h2 class="text-xl font-bold text-[#0f1f3a]">Ajouter un utilisateur</h2>
                        <p class="mt-1 text-sm text-slate-500">Remplissez les informations du nouvel utilisateur.</p>
                    </div>
                    <button type="button" x-on:click="showCreateUser = false" class="rounded-xl p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700">×</button>
                </div>

                <form method="POST" action="{{ route('admin.users.store') }}">
                    @csrf
                    <input type="hidden" name="create_user" value="1">
                    <div class="grid gap-4 px-6 py-5 sm:grid-cols-2">
                        <div>
                            <label for="name" class="block text-sm font-bold text-slate-700">Nom complet <span class="text-red-500">*</span></label>
                            <input id="name" name="name" value="{{ old('name') }}" placeholder="Prénom Nom" required class="mt-2 block w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-3 text-sm shadow-sm focus:border-[#16bd83] focus:ring-[#16bd83]">
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-bold text-slate-700">Email <span class="text-red-500">*</span></label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="email@entreprise.tn" required class="mt-2 block w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-3 text-sm shadow-sm focus:border-[#16bd83] focus:ring-[#16bd83]">
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>
                        <div class="sm:col-span-2">
                            <label for="password" class="block text-sm font-bold text-slate-700">Mot de passe temporaire <span class="text-red-500">*</span></label>
                            <input id="password" type="password" name="password" placeholder="Minimum 8 caractères" required class="mt-2 block w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-3 text-sm shadow-sm focus:border-[#16bd83] focus:ring-[#16bd83]">
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>
                        <div class="sm:col-span-2">
                            <label for="entreprise" class="block text-sm font-bold text-slate-700">Entreprise</label>
                            <input id="entreprise" name="entreprise" value="{{ old('entreprise') }}" placeholder="Nom de l'entreprise" class="mt-2 block w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-3 text-sm shadow-sm focus:border-[#16bd83] focus:ring-[#16bd83]">
                            <x-input-error :messages="$errors->get('entreprise')" class="mt-2" />
                        </div>
                        <div>
                            <label for="role" class="block text-sm font-bold text-slate-700">Rôle</label>
                            <select id="role" name="role" class="mt-2 block w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-3 text-sm shadow-sm focus:border-[#16bd83] focus:ring-[#16bd83]">
                                <option value="utilisateur" @selected(old('role') === 'utilisateur')>Utilisateur</option>
                                <option value="admin" @selected(old('role') === 'admin')>Admin</option>
                            </select>
                            <x-input-error :messages="$errors->get('role')" class="mt-2" />
                        </div>
                        <div>
                            <label for="statut" class="block text-sm font-bold text-slate-700">Statut</label>
                            <select id="statut" name="statut" class="mt-2 block w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-3 text-sm shadow-sm focus:border-[#16bd83] focus:ring-[#16bd83]">
                                <option value="actif" @selected(old('statut') === 'actif')>Actif</option>
                                <option value="inactif" @selected(old('statut') === 'inactif')>Inactif</option>
                            </select>
                            <x-input-error :messages="$errors->get('statut')" class="mt-2" />
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 border-t border-slate-100 bg-slate-50 px-6 py-5">
                        <button type="button" x-on:click="showCreateUser = false" class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Annuler</button>
                        <button class="rounded-xl bg-[#16bd83] px-5 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-900/20 transition hover:bg-emerald-700">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

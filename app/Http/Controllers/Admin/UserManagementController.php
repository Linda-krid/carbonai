<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Entreprise;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->with('entreprises')
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = $request->string('q')->toString();

                $query->where(function ($userQuery) use ($search) {
                    $userQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('entreprises', function ($entrepriseQuery) use ($search) {
                            $entrepriseQuery->where('nom', 'like', "%{$search}%");
                        });
                });
            })
            ->when($request->filled('role') && $request->string('role')->toString() !== 'tous', fn ($query) => $query->where('role', $request->string('role')->toString()))
            ->when($request->filled('statut') && $request->string('statut')->toString() !== 'tous', function ($query) use ($request) {
                $request->string('statut')->toString() === 'actif'
                    ? $query->whereNotNull('email_verified_at')
                    : $query->whereNull('email_verified_at');
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'filters' => [
                'q' => $request->string('q')->toString(),
                'role' => $request->string('role', 'tous')->toString(),
                'statut' => $request->string('statut', 'tous')->toString(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'min:8'],
            'entreprise' => ['nullable', 'string', 'max:255'],
            'role' => ['required', Rule::in(['utilisateur', 'admin'])],
            'statut' => ['required', Rule::in(['actif', 'inactif'])],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'email_verified_at' => $validated['statut'] === 'actif' ? now() : null,
        ]);

        if (! empty($validated['entreprise'])) {
            Entreprise::create([
                'user_id' => $user->id,
                'nom' => $validated['entreprise'],
                'secteur_activite' => 'Non renseigné',
                'pays' => 'Non renseigné',
                'ville' => 'Non renseigné',
                'nombre_employes' => 0,
                'type_production' => null,
                'annee_calcul' => now()->year,
            ]);
        }

        return redirect()->route('admin.users.index')->with('status', 'Utilisateur ajouté.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.form', [
            'user' => $user->load('entreprises'),
            'entreprise' => $user->entreprises->first(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'entreprise' => ['nullable', 'string', 'max:255'],
            'role' => ['required', Rule::in(['utilisateur', 'admin'])],
            'statut' => ['required', Rule::in(['actif', 'inactif'])],
        ]);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'email_verified_at' => $validated['statut'] === 'actif' ? ($user->email_verified_at ?? now()) : null,
        ];

        if (! empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->forceFill($data)->save();
        $this->updateEntrepriseName($user, $validated['entreprise'] ?? null);

        return redirect()->route('admin.users.index')->with('status', 'Utilisateur modifié.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->is(auth()->user())) {
            return back()->withErrors(['user' => 'Vous ne pouvez pas supprimer votre propre compte administrateur.']);
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('status', 'Utilisateur supprimé.');
    }

    private function updateEntrepriseName(User $user, ?string $entrepriseName): void
    {
        $entrepriseName = trim((string) $entrepriseName);

        if ($entrepriseName === '') {
            return;
        }

        $entreprise = $user->entreprises()->oldest()->first();

        if ($entreprise) {
            $entreprise->update(['nom' => $entrepriseName]);

            return;
        }

        Entreprise::create([
            'user_id' => $user->id,
            'nom' => $entrepriseName,
            'secteur_activite' => 'Non renseigné',
            'pays' => 'Tunisie',
            'ville' => 'Non renseigné',
            'nombre_employes' => 0,
            'type_production' => null,
            'annee_calcul' => now()->year,
        ]);
    }
}

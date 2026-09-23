<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Entreprise;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'nom_entreprise' => ['required', 'string', 'max:255'],
            'secteur_activite' => ['required', 'string', 'max:255'],
            'ville' => ['required', 'string', 'max:255'],
            'pays' => ['required', 'string', 'max:255'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'role' => 'utilisateur',
            'password' => Hash::make($request->password),
        ]);

        Entreprise::create([
            'user_id' => $user->id,
            'nom' => $request->string('nom_entreprise')->toString(),
            'secteur_activite' => $request->string('secteur_activite')->toString(),
            'pays' => $request->string('pays')->toString(),
            'ville' => $request->string('ville')->toString(),
            'nombre_employes' => 0,
            'type_production' => null,
            'annee_calcul' => now()->year,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('configuration.index', absolute: false));
    }
}

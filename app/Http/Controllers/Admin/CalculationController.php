<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ResultatCarbone;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CalculationController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.calculations.index', [
            'calculs' => ResultatCarbone::query()
                ->with('user', 'entreprise')
                ->when($request->query('q'), function ($query, string $search) {
                    $query->whereHas('entreprise', function ($entrepriseQuery) use ($search) {
                        $entrepriseQuery
                            ->where('nom', 'like', "%{$search}%")
                            ->orWhere('ville', 'like', "%{$search}%")
                            ->orWhere('secteur_activite', 'like', "%{$search}%");
                    });
                })
                ->when($request->query('statut'), fn ($query, string $statut) => $query->where('statut', $statut))
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'filters' => [
                'q' => $request->query('q', ''),
                'statut' => $request->query('statut', ''),
            ],
        ]);
    }

    public function show(ResultatCarbone $resultatCarbone): View
    {
        return view('admin.calculations.show', [
            'resultat' => $resultatCarbone->load('user', 'entreprise', 'formulaireGenere'),
            'details' => collect($resultatCarbone->details_calcul ?? []),
        ]);
    }

    public static function statusLabel(?string $status): string
    {
        return match ($status) {
            'calcule' => 'Calculé',
            'valide', 'validé' => 'Validé',
            'a_verifier' => 'À vérifier',
            'en_attente' => 'En attente',
            'verification' => 'Vérification',
            null, '' => 'Non renseigné',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }
}

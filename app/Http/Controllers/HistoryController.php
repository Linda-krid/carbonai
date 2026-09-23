<?php

namespace App\Http\Controllers;

use App\Models\ResultatCarbone;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HistoryController extends Controller
{
    public function index(Request $request): View
    {
        $baseQuery = ResultatCarbone::query()
            ->where('user_id', auth()->id());

        $filteredQuery = (clone $baseQuery)
            ->with('entreprise')
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = $request->string('q')->toString();

                $query->whereHas('entreprise', function ($entrepriseQuery) use ($search) {
                    $entrepriseQuery
                        ->where('nom', 'like', "%{$search}%")
                        ->orWhere('ville', 'like', "%{$search}%")
                        ->orWhere('secteur_activite', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('statut') && $request->string('statut')->toString() !== 'tous', function ($query) use ($request) {
                $query->where('statut', $request->string('statut')->toString());
            });

        return view('user.history', [
            'resultats' => $filteredQuery
                ->latest()
                ->paginate(10)
                ->withQueryString(),
            'stats' => [
                'total' => (clone $baseQuery)->count(),
                'validated' => (clone $baseQuery)->whereIn('statut', ['calcule', 'valide', 'validé'])->count(),
                'average_t_co2e' => (float) ((clone $baseQuery)->avg('total_t_co2e_an') ?? 0),
            ],
            'filters' => [
                'q' => $request->string('q')->toString(),
                'statut' => $request->string('statut', 'tous')->toString(),
            ],
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Entreprise;
use App\Models\FacteurEmission;
use App\Models\ResultatCarbone;
use App\Models\User;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(): View
    {
        $monthStart = now()->subMonths(5)->startOfMonth();
        $monthlyCalculs = ResultatCarbone::query()
            ->where('created_at', '>=', $monthStart)
            ->get(['created_at'])
            ->groupBy(fn (ResultatCarbone $resultat) => $resultat->created_at->format('Y-m'));

        $monthlySeries = collect(range(0, 5))
            ->map(function (int $offset) use ($monthStart, $monthlyCalculs) {
                $month = $monthStart->copy()->addMonths($offset);

                return [
                    'label' => ucfirst($month->translatedFormat('M')),
                    'count' => $monthlyCalculs->get($month->format('Y-m'), collect())->count(),
                ];
            });

        $sectorDistribution = Entreprise::query()
            ->get(['secteur_activite'])
            ->groupBy('secteur_activite')
            ->map(fn ($items, $sector) => [
                'label' => $sector ?: 'Non renseigné',
                'count' => $items->count(),
            ])
            ->sortByDesc('count')
            ->values();

        return view('admin.dashboard', [
            'stats' => [
                'users' => User::count(),
                'entreprises' => Entreprise::count(),
                'calculs' => ResultatCarbone::count(),
                'facteurs' => FacteurEmission::count(),
                'a_verifier' => ResultatCarbone::whereIn('statut', ['a_verifier', 'en_attente', 'verification'])->count(),
            ],
            'derniersCalculs' => ResultatCarbone::with('user', 'entreprise')->latest()->take(8)->get(),
            'monthlySeries' => $monthlySeries,
            'sectorDistribution' => $sectorDistribution,
        ]);
    }
}

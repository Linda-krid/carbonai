<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FacteurEmission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmissionFactorController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.factors.index', [
            'facteurs' => $this->filteredFactorsQuery($request)
                ->paginate(15)
                ->withQueryString(),
            'categories' => config('carbon.emission_posts'),
            'filters' => [
                'q' => $request->query('q', ''),
                'categorie' => $request->query('categorie', ''),
                'statut' => $request->query('statut', 'tous'),
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filename = 'facteurs-emission-'.now()->format('Y-m-d-His').'.csv';
        $categories = config('carbon.emission_posts');

        return response()->streamDownload(function () use ($request, $categories) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'ID',
                'Nom',
                'Catégorie',
                'Unité',
                'Coefficient',
                'Source',
                'Description',
                'Statut',
                'Mise à jour',
            ], ';');

            $this->filteredFactorsQuery($request)
                ->chunk(500, function ($facteurs) use ($handle, $categories) {
                    foreach ($facteurs as $facteur) {
                        fputcsv($handle, [
                            $facteur->id,
                            $facteur->nom,
                            data_get($categories, $facteur->categorie.'.label', $facteur->categorie),
                            $facteur->unite,
                            $facteur->coefficient,
                            $facteur->source,
                            $facteur->description,
                            $facteur->actif ? 'Actif' : 'Inactif',
                            $facteur->updated_at?->format('Y-m-d'),
                        ], ';');
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function create(): View
    {
        return view('admin.factors.form', [
            'facteur' => new FacteurEmission(['actif' => true]),
            'categories' => config('carbon.emission_posts'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        FacteurEmission::create($this->validated($request));

        return redirect()->route('admin.facteurs.index')->with('status', 'Facteur ajoute.');
    }

    public function edit(FacteurEmission $facteur): View
    {
        return view('admin.factors.form', [
            'facteur' => $facteur,
            'categories' => config('carbon.emission_posts'),
        ]);
    }

    public function update(Request $request, FacteurEmission $facteur): RedirectResponse
    {
        $facteur->update($this->validated($request));

        return redirect()->route('admin.facteurs.index')->with('status', 'Facteur modifie.');
    }

    public function destroy(FacteurEmission $facteur): RedirectResponse
    {
        $facteur->delete();

        return redirect()->route('admin.facteurs.index')->with('status', 'Facteur supprime.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'categorie' => ['required', Rule::in(array_keys(config('carbon.emission_posts')))],
            'unite' => ['required', 'string', 'max:50'],
            'coefficient' => ['required', 'numeric', 'min:0'],
            'source' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'actif' => ['nullable', 'boolean'],
        ]) + ['actif' => false];
    }

    private function filteredFactorsQuery(Request $request): Builder
    {
        return FacteurEmission::query()
            ->when($request->query('q'), function (Builder $query, string $search) {
                $query->where(function (Builder $factorQuery) use ($search) {
                    $factorQuery
                        ->where('nom', 'like', "%{$search}%")
                        ->orWhere('source', 'like', "%{$search}%")
                        ->orWhere('unite', 'like', "%{$search}%");
                });
            })
            ->when($request->query('categorie'), fn (Builder $query, string $categorie) => $query->where('categorie', $categorie))
            ->when($request->query('statut') === 'actif', fn (Builder $query) => $query->where('actif', true))
            ->when(in_array($request->query('statut'), ['inactif', 'revision'], true), fn (Builder $query) => $query->where('actif', false))
            ->orderBy('categorie')
            ->orderBy('nom');
    }
}

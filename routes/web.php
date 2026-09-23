<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\CalculationController;
use App\Http\Controllers\Admin\EmissionFactorController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\CarbonResultController;
use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\GeneratedFormController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! auth()->check()) {
        return redirect()->route('login');
    }

    return auth()->user()->isAdmin()
        ? redirect()->route('admin.dashboard')
        : redirect()->route('configuration.index');
});

Route::get('/dashboard', function () {
    return auth()->user()->isAdmin()
        ? redirect()->route('admin.dashboard')
        : redirect()->route('configuration.index');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::get('/configuration', [ConfigurationController::class, 'index'])->name('configuration.index');
    Route::post('/configuration', [ConfigurationController::class, 'store'])->name('configuration.store');

    Route::get('/formulaire-genere', [GeneratedFormController::class, 'index'])->name('formulaires.index');
    Route::get('/formulaire-genere/{formulaireGenere}', [GeneratedFormController::class, 'show'])->name('formulaires.show');
    Route::post('/formulaire-genere/{formulaireGenere}', [GeneratedFormController::class, 'submit'])->name('formulaires.submit');
    Route::post('/formulaire-genere/{formulaireGenere}/calculer', [GeneratedFormController::class, 'calculate'])->name('formulaires.calculate');
    Route::post('/formulaire-genere/{formulaireGenere}/regenerer', [GeneratedFormController::class, 'regenerate'])->name('formulaires.regenerate');

    Route::get('/resultats', [CarbonResultController::class, 'index'])->name('resultats.index');
    Route::get('/resultats/{resultatCarbone}', [CarbonResultController::class, 'show'])->name('resultats.show');
    Route::post('/resultats/{resultatCarbone}/generer-rapport', [CarbonResultController::class, 'generateReport'])->name('resultats.generate-report');
    Route::post('/resultats/{resultatCarbone}/generer-recommandations', [CarbonResultController::class, 'generateRecommendations'])->name('resultats.generate-recommendations');

    Route::get('/rapport', [ReportController::class, 'index'])->name('rapports.index');
    Route::get('/rapport/{resultatCarbone}', [ReportController::class, 'show'])->name('rapports.show');
    Route::post('/rapport/{resultatCarbone}', [ReportController::class, 'generate'])->name('rapports.generate');
    Route::get('/rapport/{resultatCarbone}/download', [ReportController::class, 'download'])->name('rapports.download');

    Route::get('/recommandations', [RecommendationController::class, 'index'])->name('recommandations.index');
    Route::get('/recommandations/{resultatCarbone}', [RecommendationController::class, 'show'])->name('recommandations.show');
    Route::post('/recommandations/{resultatCarbone}', [RecommendationController::class, 'generate'])->name('recommandations.generate');
    Route::get('/recommandations/{resultatCarbone}/download', [RecommendationController::class, 'download'])->name('recommandations.download');

    Route::get('/historique', [HistoryController::class, 'index'])->name('historique.index');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/calculs', [CalculationController::class, 'index'])->name('calculs.index');
        Route::get('/calculs/{resultatCarbone}', [CalculationController::class, 'show'])->name('calculs.show');
        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserManagementController::class, 'edit'])->name('users.edit');
        Route::patch('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');

        Route::get('/facteurs', [EmissionFactorController::class, 'index'])->name('facteurs.index');
        Route::get('/facteurs/export', [EmissionFactorController::class, 'export'])->name('facteurs.export');
        Route::get('/facteurs/create', [EmissionFactorController::class, 'create'])->name('facteurs.create');
        Route::post('/facteurs', [EmissionFactorController::class, 'store'])->name('facteurs.store');
        Route::get('/facteurs/{facteur}/edit', [EmissionFactorController::class, 'edit'])->name('facteurs.edit');
        Route::patch('/facteurs/{facteur}', [EmissionFactorController::class, 'update'])->name('facteurs.update');
        Route::delete('/facteurs/{facteur}', [EmissionFactorController::class, 'destroy'])->name('facteurs.destroy');
    });

require __DIR__.'/auth.php';

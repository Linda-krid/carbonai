<?php

namespace Tests\Unit;

use App\Support\CarbonCategory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CarbonCategoryTest extends TestCase
{
    #[DataProvider('categoryAliases')]
    public function test_it_normalizes_category_aliases(string $input, string $expected): void
    {
        $this->assertSame($expected, CarbonCategory::normalizeCategorySlug($input));
    }

    public static function categoryAliases(): array
    {
        return [
            ['carburant', 'carburant'],
            ['Carburant', 'carburant'],
            ['transport', 'transport'],
            ['Transport', 'transport'],
            ['batiment', 'batiment'],
            ['bâtiment', 'batiment'],
            ['Bâtiment', 'batiment'],
            ['Batiment', 'batiment'],
            ['energie', 'batiment'],
            ['énergie', 'batiment'],
            ['Énergie', 'batiment'],
            ['Energie', 'batiment'],
            ['transport personnel', 'transport_personnel'],
            ['Transport personnel', 'transport_personnel'],
            ['transport_personnel', 'transport_personnel'],
            ['deplacements employes', 'transport_personnel'],
            ['machines', 'machines'],
            ['Machines', 'machines'],
            ['machine', 'machines'],
            ['machines et équipements', 'machines'],
            ['Machines et équipements', 'machines'],
            ['machines_et_equipements', 'machines'],
            ['equipements', 'machines'],
            ['équipements', 'machines'],
            ['materiaux', 'materiaux'],
            ['Materiaux', 'materiaux'],
            ['matériaux', 'materiaux'],
            ['Matériaux', 'materiaux'],
            ['materiel', 'materiaux'],
            ['matériel', 'materiaux'],
            ['Matériel', 'materiaux'],
            ['dechets', 'dechets'],
            ['Dechets', 'dechets'],
            ['déchets', 'dechets'],
            ['Déchets', 'dechets'],
            ['dechets solides', 'dechets'],
            ['déchets solides', 'dechets'],
            ['Déchets solides', 'dechets'],
            ['voyages', 'voyages'],
            ['Voyages', 'voyages'],
            ['voyage', 'voyages'],
            ['voyages professionnels', 'voyages'],
            ['employes', 'employes'],
            ['Employes', 'employes'],
            ['employés', 'employes'],
            ['Employés', 'employes'],
            ['employe', 'employes'],
            ['employé', 'employes'],
            ['production', 'production'],
            ['Production', 'production'],
        ];
    }
}

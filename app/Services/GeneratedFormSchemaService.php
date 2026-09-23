<?php

namespace App\Services;

use App\Support\CarbonCategory;
use Illuminate\Support\Str;

class GeneratedFormSchemaService
{
    public function normalizeSections(array $schema, array $selectedCategories = []): array
    {
        $selectedCategories = CarbonCategory::normalizeMany($selectedCategories);
        $sections = $this->rawSections($schema);
        $normalized = collect($sections)
            ->filter(fn ($section) => is_array($section))
            ->values()
            ->map(fn (array $section, int $index) => $this->normalizeSection($section, $index))
            ->filter(fn (array $section) => $section['key'] !== '')
            ->values();

        $existingKeys = $normalized->pluck('key')->all();

        foreach (array_diff($selectedCategories, $existingKeys) as $missingCategory) {
            $normalized->push($this->fallbackSection($missingCategory));
        }

        return $normalized->values()->all();
    }

    public function completeSchema(array $schema, array $selectedCategories): array
    {
        $selectedCategories = CarbonCategory::normalizeMany($selectedCategories);
        $existingSections = collect($this->rawSections($schema))
            ->filter(fn ($section) => is_array($section))
            ->values()
            ->map(fn (array $section, int $index) => $this->normalizeSection($section, $index))
            ->filter(fn (array $section) => $section['key'] !== '')
            ->values();

        $existingKeys = $existingSections->pluck('key')->all();
        $missingCategories = array_values(array_diff($selectedCategories, $existingKeys));

        foreach ($missingCategories as $missingCategory) {
            $existingSections->push($this->fallbackSection($missingCategory));
        }

        data_set($schema, 'formulaire.sections', $existingSections->values()->all());
        data_set($schema, 'categories_selectionnees', $selectedCategories);

        if ($missingCategories !== []) {
            data_set($schema, 'categories_completees_par_laravel', $missingCategories);
        }

        return $schema;
    }

    private function rawSections(array $schema): array
    {
        $sections = data_get($schema, 'formulaire.sections');

        if (! is_array($sections)) {
            $sections = $schema['sections'] ?? [];
        }

        return is_array($sections) ? $sections : [];
    }

    private function normalizeSection(array $section, int $index): array
    {
        $key = $this->normalizeSectionKey($section);
        $title = $this->stringValue($section['title'] ?? $section['titre'] ?? null);

        if ($title === '') {
            $title = $key !== '' ? CarbonCategory::label($key) : "Section ".($index + 1);
        }

        $fields = $section['fields'] ?? $section['champs'] ?? [];

        if (! is_iterable($fields)) {
            $fields = [];
        }

        return array_merge($section, [
            'key' => $key,
            'title' => $title,
            'fields' => collect($fields)
                ->filter(fn ($field) => is_array($field))
                ->values()
                ->map(fn (array $field, int $fieldIndex) => $this->normalizeField($field, $fieldIndex))
                ->all(),
        ]);
    }

    private function normalizeSectionKey(array $section): string
    {
        foreach (['key', 'categorie', 'category', 'title', 'titre'] as $attribute) {
            $key = CarbonCategory::normalizeCategorySlug($section[$attribute] ?? null);

            if ($key !== '') {
                return $key;
            }
        }

        return '';
    }

    private function normalizeField(array $field, int $index): array
    {
        $name = $this->normalizeFieldName($field['name'] ?? $field['nom'] ?? $field['key'] ?? null);

        if ($name === '') {
            $name = "champ_{$index}";
        }

        $type = Str::of($this->stringValue($field['type'] ?? 'text'))
            ->lower()
            ->trim()
            ->toString();

        return array_merge($field, [
            'name' => $name,
            'type' => $type !== '' ? $type : 'text',
            'unit' => $field['unit'] ?? $field['unite'] ?? null,
            'required' => $this->booleanValue($field['required'] ?? $field['obligatoire'] ?? false),
            'periods' => $field['periods'] ?? $field['periodes'] ?? [],
            'utilise_pour_calcul' => $this->booleanValue(
                $field['utilise_pour_calcul']
                    ?? $field['used_for_calculation']
                    ?? $field['use_for_calculation']
                    ?? $field['calculation_field']
                    ?? false
            ),
        ]);
    }

    private function normalizeFieldName(mixed $value): string
    {
        if (! is_scalar($value)) {
            return '';
        }

        return Str::of((string) $value)
            ->ascii()
            ->lower()
            ->replace([' ', '-'], '_')
            ->replaceMatches('/[^a-z0-9_]+/', '_')
            ->trim('_')
            ->toString();
    }

    private function fallbackSection(string $category): array
    {
        return [
            'key' => $category,
            'title' => CarbonCategory::label($category),
            'description' => CarbonCategory::description($category) ?? "Renseignez vos donnees pour ce poste d'emission.",
            'fallback' => true,
            'fields' => [
                [
                    'name' => 'quantite',
                    'label' => 'Quantité à calculer',
                    'type' => 'number',
                    'unit' => CarbonCategory::unit($category),
                    'required' => true,
                    'utilise_pour_calcul' => true,
                ],
                [
                    'name' => 'periode',
                    'label' => 'Periode',
                    'type' => 'select',
                    'default' => 'mois',
                    'periods' => ['jour', 'semaine', 'mois', 'trimestre', 'annee'],
                    'required' => true,
                ],
            ],
        ];
    }

    private function stringValue(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }

    private function booleanValue(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (bool) $value;
        }

        $normalized = Str::of($this->stringValue($value))
            ->ascii()
            ->lower()
            ->toString();

        return in_array($normalized, ['1', 'true', 'vrai', 'yes', 'oui', 'required', 'obligatoire'], true);
    }
}

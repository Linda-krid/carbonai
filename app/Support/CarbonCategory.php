<?php

namespace App\Support;

use Illuminate\Support\Str;

class CarbonCategory
{
    public const VALID_SLUGS = [
        'carburant',
        'transport',
        'batiment',
        'transport_personnel',
        'machines',
        'materiaux',
        'dechets',
        'voyages',
        'employes',
        'production',
    ];

    public static function normalizeCategorySlug(mixed $value): string
    {
        if (! is_scalar($value)) {
            return '';
        }

        $phrase = Str::of((string) $value)
            ->ascii()
            ->lower()
            ->replace(['&', '+'], ' et ')
            ->replace(['_', '-'], ' ')
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->toString();

        if ($phrase === '') {
            return '';
        }

        $slug = str_replace(' ', '_', $phrase);

        if (in_array($slug, self::VALID_SLUGS, true)) {
            return $slug;
        }

        return self::aliases()[$phrase] ?? '';
    }

    public static function normalizeMany(iterable $values): array
    {
        return collect($values)
            ->map(fn ($value) => self::normalizeCategorySlug($value))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public static function label(string $slug): string
    {
        $label = config("carbon.emission_posts.{$slug}.label");

        if (is_string($label) && trim($label) !== '') {
            return $label;
        }

        return Str::of($slug)->replace('_', ' ')->ucfirst()->toString();
    }

    public static function description(string $slug): ?string
    {
        $description = config("carbon.emission_posts.{$slug}.description");

        return is_string($description) && trim($description) !== '' ? $description : null;
    }

    public static function unit(string $slug): string
    {
        $unit = config("carbon.emission_posts.{$slug}.unit");

        return is_string($unit) && trim($unit) !== '' ? $unit : 'unite';
    }

    private static function aliases(): array
    {
        return [
            'carburant' => 'carburant',
            'transport' => 'transport',
            'batiment' => 'batiment',
            'energie' => 'batiment',
            'transport personnel' => 'transport_personnel',
            'deplacements employes' => 'transport_personnel',
            'machines' => 'machines',
            'machine' => 'machines',
            'machines et equipements' => 'machines',
            'machines equipements' => 'machines',
            'equipements' => 'machines',
            'materiaux' => 'materiaux',
            'materiel' => 'materiaux',
            'dechets' => 'dechets',
            'dechets solides' => 'dechets',
            'voyages' => 'voyages',
            'voyage' => 'voyages',
            'voyages professionnels' => 'voyages',
            'employes' => 'employes',
            'employe' => 'employes',
            'production' => 'production',
        ];
    }
}

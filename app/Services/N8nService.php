<?php

namespace App\Services;

use App\Support\CarbonCategory;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class N8nService
{
    public function generateForm(array $payload): array
    {
        $url = (string) config('services.n8n.generate_form_url', '');

        return $this->postOrMock($url, $payload, function () use ($payload) {
            $posts = CarbonCategory::normalizeMany(
                $payload['categories_selectionnees'] ?? $payload['facteurs_selectionnes'] ?? array_keys(config('carbon.emission_posts'))
            );

            return [
                'success' => true,
                'statut' => 'formulaire_valide',
                'message' => 'Formulaire généré avec succès',
                'is_mock' => true,
                'formulaire' => [
                    'statut' => 'genere',
                    'nom_formulaire' => 'Formulaire carbone',
                    'sections' => collect($posts)->map(function (string $key) {
                        $post = config("carbon.emission_posts.$key", ['label' => ucfirst($key), 'unit' => 'unite']);

                        return [
                            'key' => $key,
                            'title' => $post['label'],
                            'description' => $post['description'] ?? null,
                            'fields' => [
                                [
                                    'name' => 'valeur',
                                    'label' => 'Valeur',
                                    'type' => 'number',
                                    'unit' => $post['unit'] ?? 'unite',
                                ],
                                [
                                    'name' => 'unite',
                                    'label' => 'Unite',
                                    'type' => 'text',
                                    'default' => $post['unit'] ?? 'unite',
                                ],
                                [
                                    'name' => 'periode',
                                    'label' => 'Periode',
                                    'type' => 'text',
                                    'default' => 'mois',
                                ],
                            ],
                        ];
                    })->values()->all(),
                ],
            ];
        }, 'formulaire');
    }

    public function generateReport(array $payload): array
    {
        $url = config('services.n8n.generate_report_url') ?? env('N8N_GENERATE_REPORT_URL');

        if (! $url) {
            return [
                'success' => false,
                'statut' => 'n8n_url_missing',
                'message' => 'URL n8n generate-report non configurée.',
            ];
        }

        try {
            $response = Http::timeout(120)
                ->acceptJson()
                ->asJson()
                ->post($url, $payload);

            Log::info('N8N generate-report response', [
                'status' => $response->status(),
                'body' => $response->json(),
                'raw_body' => substr($response->body(), 0, 1000),
            ]);

            if (! $response->successful()) {
                return [
                    'success' => false,
                    'statut' => 'http_error',
                    'message' => 'Erreur HTTP n8n : '.$response->status(),
                    'body' => $response->body(),
                ];
            }

            $json = $this->normalizedJsonResponse($response);

            return is_array($json) ? $json : [
                'success' => false,
                'statut' => trim($response->body()) === '' ? 'empty_response' : 'invalid_json',
                'message' => trim($response->body()) === ''
                    ? 'n8n a répondu sans contenu JSON.'
                    : 'Réponse n8n invalide.',
            ];
        } catch (Throwable $exception) {
            Log::error('Erreur appel n8n generate-report', [
                'message' => $exception->getMessage(),
                'payload' => $payload,
            ]);

            return [
                'success' => false,
                'statut' => 'exception',
                'message' => $exception->getMessage(),
            ];
        }
    }

    public function generateRecommendations(array $payload): array
    {
        $url = config('services.n8n.generate_recommendations_url') ?? env('N8N_GENERATE_RECOMMENDATIONS_URL');

        if (! $url) {
            return [
                'success' => false,
                'statut' => 'n8n_url_missing',
                'message' => 'URL n8n generate-recommendations non configurée.',
            ];
        }

        Log::info('N8N generate-recommendations request', [
            'url' => $url,
            'payload' => $payload,
        ]);

        try {
            $response = Http::timeout(120)
                ->acceptJson()
                ->asJson()
                ->post($url, $payload);

            Log::info('N8N generate-recommendations response', [
                'status' => $response->status(),
                'body' => $response->json(),
                'raw_body' => substr($response->body(), 0, 1000),
            ]);

            if (! $response->successful()) {
                return [
                    'success' => false,
                    'statut' => 'http_error',
                    'message' => 'Erreur HTTP n8n : '.$response->status(),
                    'body' => $response->body(),
                ];
            }

            $json = $this->normalizedJsonResponse($response);

            return is_array($json) ? $json : [
                'success' => false,
                'statut' => trim($response->body()) === '' ? 'empty_response' : 'invalid_json',
                'message' => trim($response->body()) === ''
                    ? 'n8n a répondu sans contenu JSON. Vérifie que le workflow generate-recommendations retourne bien un JSON.'
                    : 'Réponse n8n invalide.',
            ];
        } catch (Throwable $exception) {
            Log::error('Erreur appel n8n generate-recommendations', [
                'message' => $exception->getMessage(),
                'payload' => $payload,
            ]);

            return [
                'success' => false,
                'statut' => 'exception',
                'message' => $exception->getMessage(),
            ];
        }
    }

    private function postOrMock(string $url, array $payload, callable $mock, string $workflow): array
    {
        if (trim($url) === '') {
            return $mock();
        }

        try {
            $response = Http::timeout(60)->post($url, $payload);

            if (! $response->successful()) {
                return $this->errorResponse($workflow, "Le service de génération est indisponible (HTTP {$response->status()}).");
            }

            $json = $response->json();

            if (! is_array($json)) {
                return $this->errorResponse($workflow, 'Réponse invalide du service de génération.');
            }

            return $json + ['is_mock' => false];
        } catch (ConnectionException|RequestException $exception) {
            Log::warning('N8n request failed', [
                'workflow' => $workflow,
                'message' => $exception->getMessage(),
            ]);

            return $this->errorResponse($workflow, 'Le service de génération est momentanément indisponible. Réessayez.');
        } catch (Throwable $exception) {
            Log::error('N8n unexpected error', [
                'workflow' => $workflow,
                'message' => $exception->getMessage(),
            ]);

            return $this->errorResponse($workflow, 'Une erreur est survenue pendant la génération.');
        }
    }

    private function normalizedJsonResponse(HttpResponse $response): mixed
    {
        $json = $response->json();

        if (is_string($json)) {
            $decoded = json_decode($json, true);

            return is_array($decoded) ? $decoded : $json;
        }

        if (! is_array($json)) {
            return $json;
        }

        if (isset($json['json']) && is_array($json['json'])) {
            return $json['json'];
        }

        if (array_is_list($json) && isset($json[0]) && is_array($json[0])) {
            return isset($json[0]['json']) && is_array($json[0]['json'])
                ? $json[0]['json']
                : $json[0];
        }

        return $json;
    }

    private function errorResponse(string $workflow, string $message): array
    {
        $status = match ($workflow) {
            'formulaire' => 'formulaire_a_regenerer',
            'rapport' => 'rapport_a_regenerer',
            'recommandations' => 'recommandations_a_regenerer',
            default => 'generation_en_erreur',
        };

        return [
            'success' => false,
            'statut' => $status,
            'message' => $message,
            'is_mock' => false,
        ];
    }
}

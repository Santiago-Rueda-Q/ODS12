<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use JsonException;
use Throwable;

class EcoAnalysisService
{
    /** Texto máximo enviado al modelo (optimización de tokens). */
    private const int AI_PROMPT_MAX_CHARS = 1500;

    /** Validación previa (posts pueden guardar descripciones más largas). */
    private const int MAX_DESCRIPTION_CHARS = 12000;

    /**
     * Invoca el modelo configurado y devuelve el JSON normalizado o null si falla / no hay API key.
     *
     * @return array{historia: string, afinidad: string, equilibrio: string, recomendacion: string, score: int}|null
     */
    public function analyzeDescription(?string $description): ?array
    {
        $apiKey = config('services.eco_analysis_ai.api_key');
        if (! is_string($apiKey) || $apiKey === '') {
            Log::warning('EcoAnalysisService: ECO_ANALYSIS_AI_API_KEY ausente o vacía en config.');

            return null;
        }

        $text = trim(strip_tags((string) $description));
        if ($text === '' || mb_strlen($text) < 12) {
            Log::info('EcoAnálisisAiAnalysisService: descripción vacía o demasiado corta para analizar.', [
                'length' => mb_strlen($text),
            ]);

            return null;
        }

        if (mb_strlen($text) > self::MAX_DESCRIPTION_CHARS) {
            $text = mb_substr($text, 0, self::MAX_DESCRIPTION_CHARS);
        }

        $text = Str::limit($text, self::AI_PROMPT_MAX_CHARS, '');

        try {
            $quotedDescription = json_encode($text, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            Log::warning('EcoAnálisisAiAnalysisService: no se pudo codificar descripción para prompt.', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }

        $baseUrl = rtrim((string) config('services.eco_analysis_ai.base_url'), '/');
        $model   = (string) config('services.eco_analysis_ai.model');
        $timeout = (int) config('services.eco_analysis_ai.timeout', 90);

        $userPrompt = <<<PROMPT
Analiza la siguiente práctica o contenido relacionado con consumo sostenible (ODS 12) descrito en esta cadena JSON (texto plano del usuario):

{$quotedDescription}

Devuelve un JSON con:

* impacto (máx 80 palabras): impacto ambiental o sostenible de lo descrito
* alineacion_ods (máx 80 palabras): cómo se alinea con el ODS 12 (Producción y consumo responsables)
* recomendacion (máx 80 palabras): sugerencia práctica para mejorar su sostenibilidad
* score (1 a 10): puntuación de impacto positivo en sostenibilidad

No más de 250 palabras en total.
No inventar información.
Ser claro y propositivo.
PROMPT;

        try {
            $response = Http::timeout($timeout)
                ->withToken($apiKey)
                ->acceptJson()
                ->post("{$baseUrl}/chat/completions", [
                    'model' => $model,
                    'response_format' => ['type' => 'json_object'],
                    'temperature' => 0.35,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Eres un experto en sostenibilidad y consumo responsable (ODS 12). Respondes únicamente JSON válido UTF-8 con las claves solicitadas en español.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $userPrompt,
                        ],
                    ],
                ]);
        } catch (Throwable $e) {
            Log::warning('EcoAnálisisAiAnalysisService: fallo de red.', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('EcoAnálisisAiAnalysisService: respuesta HTTP no exitosa.', [
                'status' => $response->status(),
                'body' => Str::limit($response->body(), 500),
            ]);

            return null;
        }

        /** @var array<string, mixed>|null $decoded */
        $decoded = $response->json();
        $content = data_get($decoded, 'choices.0.message.content');
        if (! is_string($content) || $content === '') {
            Log::warning('EcoAnálisisAiAnalysisService: contenido vacío del modelo.');

            return null;
        }

        $parsed = $this->decodeModelJson($content);

        return $this->normalizePayload($parsed);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeModelJson(string $content): ?array
    {
        $trimmed = trim($content);
        $decoded = json_decode($trimmed, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{[\s\S]*\}/u', $trimmed, $m)) {
            $decoded = json_decode($m[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        Log::warning('EcoAnálisisAiAnalysisService: JSON inválido del modelo.', [
            'snippet' => Str::limit($trimmed, 400),
        ]);

        return null;
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array{impacto: string, alineacion_ods: string, recomendacion: string, score: int}|null
     */
    private function normalizePayload(array $raw): ?array
    {
        $impacto = trim((string) ($raw['impacto'] ?? ''));
        $alineacion_ods = trim((string) ($raw['alineacion_ods'] ?? ''));
        $recomendacion = trim((string) ($raw['recomendacion'] ?? ''));

        if ($impacto === '' || $alineacion_ods === '' || $recomendacion === '') {
            Log::warning('EcoAnalysisService: faltan campos obligatorios en JSON.');

            return null;
        }

        $scoreRaw = $raw['score'] ?? null;
        $score = is_numeric($scoreRaw) ? (int) round((float) $scoreRaw) : 0;
        $score = max(1, min(10, $score));

        return [
            'impacto' => Str::limit($impacto, 520),
            'alineacion_ods' => Str::limit($alineacion_ods, 400),
            'recomendacion' => Str::limit($recomendacion, 400),
            'score' => $score,
        ];
    }
}

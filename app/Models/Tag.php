<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Cache;

class Tag extends Model
{
    /**
     * No guardar modelos Eloquent en caché serializada (p. ej. driver database): al deserializar
     * puede aparecer __PHP_Incomplete_Class. Solo persistimos filas como array y usamos hydrate().
     * Ante cualquier error o payload inválido: limpiar claves y volver a leer de BD.
     */
    private const string CACHE_KEY = 'tags.catalog.v5';

    /** @var list<string> */
    private const LEGACY_CACHE_KEYS = ['tags', 'tags.catalog', 'tags.catalog.v3'];

    public const TYPE_COUNTRY = 'country';

    public const TYPE_ODS12 = 'ods12';

    /** @var list<string> */
    public const TYPES = [
        self::TYPE_COUNTRY,
        self::TYPE_ODS12,
    ];

    protected $fillable = [
        'type',
        'slug',
        'name',
        'iso_code',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class);
    }

    /**
     * Catálogo de etiquetas ordenado (TTL 1 h). Invalidar con eventos del modelo.
     *
     * @return EloquentCollection<int, self>
     */
    public static function cachedCatalog(): EloquentCollection
    {
        try {
            /** @var mixed $payload */
            $payload = Cache::get(self::CACHE_KEY);

            if (! self::isValidTagsCatalogPayload($payload)) {
                self::forgetAllCatalogCacheKeys();

                $payload = self::fetchCatalogRowsForCache();
                Cache::put(self::CACHE_KEY, $payload, now()->addHour());
            }

            /** @var array<int, array<string, mixed>> $payload */
            return self::hydrate($payload);
        } catch (\Throwable) {
            self::forgetAllCatalogCacheKeys();

            return self::query()
                ->orderBy('type')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function fetchCatalogRowsForCache(): array
    {
        return self::query()
            ->orderBy('type')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (self $tag) => $tag->getAttributes())
            ->all();
    }

    private static function forgetAllCatalogCacheKeys(): void
    {
        foreach (array_merge([self::CACHE_KEY], self::LEGACY_CACHE_KEYS) as $key) {
            Cache::forget($key);
        }
    }

    private static function isValidTagsCatalogPayload(mixed $payload): bool
    {
        if (! is_array($payload)) {
            return false;
        }

        foreach ($payload as $row) {
            if (! is_array($row) || ! isset($row['id'], $row['type'])) {
                return false;
            }
        }

        return true;
    }

    protected static function booted(): void
    {
        static::saved(fn () => self::forgetAllCatalogCacheKeys());
        static::deleted(fn () => self::forgetAllCatalogCacheKeys());
    }
}

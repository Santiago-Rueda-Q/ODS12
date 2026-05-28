<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable(['first_name', 'last_name', 'username', 'eco_username', 'email', 'password', 'country', 'description', 'profile_photo', 'linkedin', 'birthdate', 'preferences'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    /**
     * Publicaciones a las que este usuario dio «me gusta» (tabla pivote `likes`).
     *
     * @return BelongsToMany<Post, $this>
     */
    public function likedPosts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'likes')->withTimestamps();
    }

    /**
     * Usuarios que este usuario sigue.
     *
     * @return BelongsToMany<User, $this>
     */
    public function following(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'following_id')->withTimestamps();
    }

    /**
     * Seguidores de este usuario.
     *
     * @return BelongsToMany<User, $this>
     */
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'following_id', 'follower_id')->withTimestamps();
    }

    /** @var list<string> */
    public const PREFERENCE_OPTIONS = [
        'Reciclaje activo',
        'Consumo consciente',
        'Energía renovable',
        'Movilidad sostenible',
        'Alimentación plant-based',
        'Economía circular',
        'Activismo ambiental',
        'Educación ecológica',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unread_notifications_count' => 'integer',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birthdate' => 'date',
            'preferences' => 'array',
        ];
    }

    /**
     * Normaliza un handle de Instagram para derivar username (sin @).
     */
    public static function normalizeInstagramHandle(?string $handle): ?string
    {
        if ($handle === null || $handle === '') {
            return null;
        }

        $handle = trim($handle);
        $handle = ltrim($handle, '@/');
        $handle = trim($handle);

        return $handle !== '' ? $handle : null;
    }

    /** @var list<string> */
    private const SUSTAINABILITY_TERMS = [
        'recicla', 'circular', 'verde', 'eco', 'sostenible', 'residuo', 'compost', 'reutiliza', 'limpio', 'neutro', 'bioma', 'huella', 'renovable', 'consciente', 'impacto'
    ];

    /**
     * Base creativa para username: primer nombre + término sostenible + número.
     */
    public static function creativeUsernameBase(string $firstName): string
    {
        $firstToken = trim((string) Str::of($firstName)->explode(' ')->first());
        $namePart = Str::slug($firstToken, '');
        $term = self::SUSTAINABILITY_TERMS[array_rand(self::SUSTAINABILITY_TERMS)];
        $salt = (string) random_int(10, 99);

        $raw = $namePart.$term.$salt;
        $base = Str::slug($raw, '');

        if ($base === '') {
            $base = 'usuario'.self::SUSTAINABILITY_TERMS[array_rand(self::SUSTAINABILITY_TERMS)].(string) random_int(10, 99);
        }

        if (strlen($base) > 24) {
            $base = substr($base, 0, 24);
        }

        return $base;
    }

    /**
     * Genera un eco_username único (minúsculas, slug).
     */
    public static function generateUniqueUsername(string $firstName, string $lastName, ?string $linkedinHandle = null): string
    {
        // $lastName se mantiene por compatibilidad aunque no se use para el fallback creativo.
        unset($lastName);

        $base = self::creativeUsernameBase($firstName);

        if ($base === '') {
            $base = 'user';
        }

        $username = $base;
        $original = $username;
        $count = 1;

        while (static::query()->where('eco_username', $username)->exists() || static::query()->where('username', $username)->exists()) {
            $username = $original.$count;
            $count++;
        }

        return $username;
    }

    /**
     * URL pública de la foto de perfil (disco public) o imagen por defecto.
     * Ruta en BD: p. ej. "profiles/{usuario}/avatar.webp" relativa al disco public.
     */
    protected function profilePhotoUrl(): Attribute
    {
        return Attribute::get(function (): string {
            $normalized = self::normalizePublicDiskPath($this->profile_photo);
            if ($normalized === null) {
                return '/images/default.png';
            }

            $timestamp = $this->updated_at?->timestamp ?? time();
            return '/storage/'.ltrim($normalized, '/').'?v='.$timestamp;
        });
    }

    /**
     * Variante pequeña (p. ej. listados); si no existe, coincide con la foto principal.
     */
    protected function profilePhotoThumbUrl(): Attribute
    {
        return Attribute::get(function (): string {
            return $this->publicUrlForProfileVariant((string) config('profile_photo.filenames.thumb', 'avatar_thumb.webp'))
                ?? $this->profile_photo_url;
        });
    }

    /**
     * Variante media (p. ej. cabeceras compactas).
     */
    protected function profilePhotoMediumUrl(): Attribute
    {
        return Attribute::get(function (): string {
            return $this->publicUrlForProfileVariant((string) config('profile_photo.filenames.medium', 'avatar_medium.webp'))
                ?? $this->profile_photo_url;
        });
    }

    /**
     * @return string|null URL bajo /storage/… o null si el archivo no está en disco
     */
    private function publicUrlForProfileVariant(string $filename): ?string
    {
        $base = self::normalizePublicDiskPath($this->profile_photo);
        if ($base === null) {
            return null;
        }

        $dir = dirname($base);
        if ($dir === '.' || $dir === '') {
            return null;
        }

        $relative = $dir.'/'.$filename;
        if (! Storage::disk('public')->exists($relative)) {
            return null;
        }

        $timestamp = $this->updated_at?->timestamp ?? time();
        return '/storage/'.ltrim($relative, '/').'?v='.$timestamp;
    }

    /**
     * Normaliza valores mal guardados (storage/..., public/storage/...) a la ruta del disco public.
     */
    public static function normalizePublicDiskPath(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        $path = trim(str_replace('\\', '/', $path), '/');

        foreach (['public/storage/', 'public/', 'storage/'] as $prefix) {
            if (str_starts_with($path, $prefix)) {
                $path = substr($path, strlen($prefix));
                break;
            }
        }

        return $path !== '' ? $path : null;
    }
}

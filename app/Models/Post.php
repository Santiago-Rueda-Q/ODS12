<?php

namespace App\Models;

use App\Support\CountryNameIsoMap;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory, SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_REJECTED = 'rejected';

    public const ANALYSIS_STATUS_PENDING = 'pending';

    public const ANALYSIS_STATUS_PROCESSING = 'processing';

    public const ANALYSIS_STATUS_COMPLETED = 'completed';

    public const ANALYSIS_STATUS_FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'content',
        'impacto_estimado',
        'eco_score',
        'image_path',
        'status',
        'analysis_status',
        'analysis_result',
        'moderation_reason',
        'ai_analysis',
        'eco_analysis',
    ];

    protected function casts(): array
    {
        return [
            'moderation_reason' => 'array',
            'analysis_result' => 'array',
            'ai_analysis' => 'array',
            'eco_analysis' => 'array',
        ];
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->image_path === null) {
                    return null;
                }
                $timestamp = $this->updated_at?->timestamp ?? time();
                return Storage::disk('public')->url($this->image_path).'?v='.$timestamp;
            }
        );
    }

    /**
     * País a mostrar en UI:
     * 1) etiqueta de país del post, 2) país del autor como fallback.
     *
     * @return Attribute<array{name:string,iso_code:?string,flag_url:?string}|null, never>
     */
    protected function displayCountry(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->resolveDisplayCountry(),
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
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
     * Usuarios que marcaron esta publicación con «me gusta».
     *
     * @return BelongsToMany<User, $this>
     */
    public function likedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'likes')->withTimestamps();
    }

    public function excerpt(): string
    {
        if ($this->description === null || $this->description === '') {
            return '';
        }

        return Str::limit(trim(strip_tags($this->description)), 80);
    }

    /**
     * @return array{name:string,iso_code:?string,flag_url:?string}|null
     */
    private function resolveDisplayCountry(): ?array
    {
        $countryTag = $this->resolveCountryTag();
        if ($countryTag !== null) {
            $iso = $countryTag->iso_code !== null ? strtoupper((string) $countryTag->iso_code) : null;
            if ($iso === null) {
                $iso = CountryNameIsoMap::isoForCountryName((string) $countryTag->name);
            }

            return [
                'name' => (string) $countryTag->name,
                'iso_code' => $iso,
                'flag_url' => $this->resolveFlagUrl($iso),
            ];
        }

        $userCountry = $this->relationLoaded('user')
            ? (string) ($this->user?->country ?? '')
            : (string) ($this->user()->value('country') ?? '');

        $userCountry = trim($userCountry);
        if ($userCountry === '') {
            return null;
        }

        $iso = CountryNameIsoMap::isoForCountryName($userCountry);

        return [
            'name' => $userCountry,
            'iso_code' => $iso,
            'flag_url' => $this->resolveFlagUrl($iso),
        ];
    }

    private function resolveCountryTag(): ?Tag
    {
        if ($this->relationLoaded('tags')) {
            $tag = $this->tags->firstWhere('type', Tag::TYPE_COUNTRY);

            return $tag instanceof Tag ? $tag : null;
        }

        $tag = $this->tags()
            ->select('tags.id', 'tags.type', 'tags.slug', 'tags.name', 'tags.iso_code')
            ->where('type', Tag::TYPE_COUNTRY)
            ->first();

        return $tag instanceof Tag ? $tag : null;
    }

    private function resolveFlagUrl(?string $iso): ?string
    {
        $iso = is_string($iso) ? strtolower(trim($iso)) : '';
        if (strlen($iso) !== 2 || ! ctype_alpha($iso)) {
            return null;
        }

        $relative = "flags/{$iso}.svg";
        if (! is_file(public_path($relative))) {
            return null;
        }

        return asset($relative);
    }
}

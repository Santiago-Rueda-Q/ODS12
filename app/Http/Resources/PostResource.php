<?php

namespace App\Http\Resources;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Tag;
use App\Support\CountryNameIsoMap;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Formato único de post para API y serialización emimpacto en vistas.
 *
 * @mixin Post
 */
class PostResource extends JsonResource
{
    /** @var array<string, bool> */
    private static array $flagExistsCache = [];

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this->user;
        $tags = $this->whenLoaded('tags', fn () => $this->tags, collect());

        $countryTag = $tags instanceof Collection
            ? $tags->firstWhere('type', Tag::TYPE_COUNTRY)
            : null;

        $ecoAnalysis = is_array($this->eco_analysis) ? $this->eco_analysis : null;
        $analysisStatus = is_string($this->analysis_status) ? $this->analysis_status : Post::ANALYSIS_STATUS_PENDING;
        $analysisResult = is_array($this->analysis_result) ? $this->analysis_result : null;

        $base = [
            'id'               => $this->id,
            'title'            => $this->title,
            'content'          => $this->content ?? $this->description,
            'description'      => $this->description,
            'impacto_estimado' => $this->impacto_estimado,
            'eco_score'        => (int) ($this->eco_score ?? 0),
            'status'           => $this->status,
            'excerpt'          => self::makeExcerpt($this->description),
            'image_url'        => $this->image_url,
            'comments_count'   => (int) ($this->comments_count ?? 0),
            'likes_count'      => (int) ($this->likes_count ?? 0),
            'engagement_score' => ((int) ($this->likes_count ?? 0)) * 2 + ((int) ($this->comments_count ?? 0)) * 3 + ((int) ($this->eco_score ?? 0)) * 2,
            'liked'            => auth()->check() && (bool) data_get($this->resource, 'liked_by_me', false),
            'created_at'       => $this->created_at?->toIso8601String(),
            'analysis_status'  => $analysisStatus,
            'analysis_result'  => $analysisResult,
            'moderation_reason' => $this->moderation_reason,
            'eco_analysis'     => $ecoAnalysis,
            'eco_highlighted'  => self::isEcoHighlighted($ecoAnalysis),
            'can_edit' => auth()->check() && auth()->id() === (int) $this->user_id,
            'tags' => $tags instanceof Collection
                ? $tags->map(fn ($t) => [
                    'id' => $t->id,
                    'type' => $t->type,
                    'slug' => $t->slug,
                    'name' => $t->name,
                ])->values()->all()
                : [],
            'country' => $countryTag !== null
                ? [
                    'name' => $countryTag->name,
                    'iso_code' => $countryTag->iso_code,
                    'flag_url' => self::resolvedCountryFlagUrl($countryTag->iso_code),
                ]
                : null,
            'user' => [
                'id' => $user->id,
                'name' => trim($user->first_name.' '.$user->last_name),
                'username' => $user->username,
                'avatar' => $user->profile_photo_url,
                'avatar_thumb' => $user->profile_photo_thumb_url,
                'avatar_medium' => $user->profile_photo_medium_url,
                'profile_url' => route('profile.show', ['username' => $user->username]),
                'country' => self::userCountryMeta($user->country),
            ],
        ];

        if ($this->relationLoaded('comments')) {
            $base['comments'] = self::commentsTree($this->comments);
        }

        return $base;
    }

    /**
     * @param  array<string, mixed>|null  $analysis
     */
    private static function isEcoHighlighted(?array $analysis): bool
    {
        if ($analysis === null) {
            return false;
        }
        $score = (int) ($analysis['score'] ?? 0);
        return $score >= 8;
    }

    /**
     * @param  Collection<int, Comment>  $flat
     * @return array<int, array<string, mixed>>
     */
    public static function commentsTree(Collection $flat): array
    {
        $roots = $flat->filter(fn (Comment $c) => $c->parent_id === null)->sortBy('created_at')->values();

        return $roots->map(function (Comment $comment) use ($flat) {
            $row = (new CommentResource($comment))->resolve();
            $nested = self::nestedReplies($flat, $comment->id);
            if ($nested !== []) {
                $row['replies'] = $nested;
            }

            return $row;
        })->values()->all();
    }

    /**
     * @param  Collection<int, Comment>  $flat
     * @return array<int, array<string, mixed>>
     */
    private static function nestedReplies(Collection $flat, int $parentId): array
    {
        return $flat
            ->filter(fn (Comment $c) => (int) $c->parent_id === $parentId)
            ->sortBy('created_at')
            ->values()
            ->map(function (Comment $comment) use ($flat) {
                $row = (new CommentResource($comment))->resolve();
                $nested = self::nestedReplies($flat, $comment->id);
                if ($nested !== []) {
                    $row['replies'] = $nested;
                }

                return $row;
            })
            ->all();
    }

    public static function makeExcerpt(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        return Str::limit(trim(strip_tags($text)), 80);
    }

    /**
     * URL pública del SVG en /public/flags/{iso}.svg solo si el archivo existe.
     */
    public static function resolvedCountryFlagUrl(?string $iso): ?string
    {
        $iso = is_string($iso) ? strtolower(trim($iso)) : '';

        if (strlen($iso) !== 2 || ! ctype_alpha($iso)) {
            return null;
        }
        $relative = "flags/{$iso}.svg";

        if (! array_key_exists($iso, self::$flagExistsCache)) {
            self::$flagExistsCache[$iso] = is_file(public_path($relative));
        }

        if (! self::$flagExistsCache[$iso]) {
            return null;
        }

        return asset($relative);
    }

    /**
     * @return array{name: string, iso_code: string|null, flag_url: string|null}|null
     */
    private static function userCountryMeta(?string $countryName): ?array
    {
        $countryName = is_string($countryName) ? trim($countryName) : '';
        if ($countryName === '') {
            return null;
        }
        $iso = CountryNameIsoMap::isoForCountryName($countryName);

        return [
            'name' => $countryName,
            'iso_code' => $iso,
            'flag_url' => self::resolvedCountryFlagUrl($iso),
        ];
    }
}

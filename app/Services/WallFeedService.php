<?php

namespace App\Services;

use App\Http\Requests\FilterPostsRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Contracts\Pagination\Paginator as PaginatorContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Feed del muro: mezcla seguidos/popular, ranking por engagement y filtros de exploración.
 */
class WallFeedService
{
    private const POST_COLUMNS = [
        'posts.id',
        'posts.user_id',
        'posts.title',
        'posts.description',
        'posts.image_path',
        'posts.created_at',
    ];

    /** @var array<string, bool>|null */
    private static ?array $postColumnPresence = null;

    public static function engagementScoreExpression(): string
    {
        return '(COALESCE(likes_count, 0) * 2 + COALESCE(comments_count, 0) * 3)';
    }

    /**
     * Engagement del feed + refuerzo por eco_score (likes + comentarios + eco_score*2).
     */
    public static function engagementWithEcoScoreExpression(): string
    {
        return '('.self::engagementScoreExpression().' + COALESCE(posts.eco_score, 0) * 2)';
    }

    public function respond(FilterPostsRequest $request): JsonResponse
    {
        $perPage = min(max(1, $request->integer('per_page', 20)), 50);
        $page = max(1, $request->integer('page', 1));
        $sort = $request->input('sort', 'recent');

        if ($request->boolean('following')) {
            return $this->followingFeed($request, $perPage, $page, $sort);
        }

        if ($sort === 'recent' && auth()->check()) {
            return $this->mixedOrGlobalRecent($request, $perPage, $page);
        }

        return $this->globalExploreFeed($request, $perPage, $page, $sort);
    }

    /**
     * Solo cuentas que sigues (respeta sort y filtros).
     */
    private function followingFeed(FilterPostsRequest $request, int $perPage, int $page, string $sort): JsonResponse
    {
        if (! auth()->check()) {
            return response()->json([
                'posts' => [],
                'meta' => [
                    'guest_following' => true,
                    'has_more' => false,
                    'current_page' => 1,
                    'feed_mode' => 'following_guest',
                ],
            ]);
        }

        $query = $this->baseQuery();
        $this->applyExploreFilters($query, $request);

        $ids = DB::table('follows')->where('follower_id', auth()->id())->pluck('following_id');
        if ($ids->isEmpty()) {
            $query->whereRaw('0 = 1');
        } else {
            $query->whereIn('user_id', $ids);
        }

        $this->applySort($query, $sort);

        return $this->jsonPaginate($query->simplePaginate($perPage, self::POST_COLUMNS, 'page', $page), 'following');
    }

    /**
     * FYP autenticado con sort "recientes": 70 % seguidos + 30 % ranking popular.
     */
    private function mixedOrGlobalRecent(FilterPostsRequest $request, int $perPage, int $page): JsonResponse
    {
        $followedIds = DB::table('follows')->where('follower_id', auth()->id())->pluck('following_id');

        if ($followedIds->isEmpty()) {
            $query = $this->baseQuery();
            $this->applyExploreFilters($query, $request);
            $query->latest();

            return $this->jsonPaginate($query->simplePaginate($perPage, self::POST_COLUMNS, 'page', $page), 'global_recent_no_follows');
        }

        $nFollow = max(1, (int) round($perPage * 0.7));
        $nPop = max(1, $perPage - $nFollow);

        $followPosts = $this->baseQuery();
        $this->applyExploreFilters($followPosts, $request);
        $followPosts->whereIn('user_id', $followedIds)->latest();
        $followChunk = $followPosts->offset(($page - 1) * $nFollow)->limit($nFollow)->get();

        $excludeIds = $followChunk->pluck('id')->all();

        $popPosts = $this->baseQuery();
        $this->applyExploreFilters($popPosts, $request);
        if ($excludeIds !== []) {
            $popPosts->whereNotIn('posts.id', $excludeIds);
        }
        $popPosts
            ->orderByRaw(self::engagementWithEcoScoreExpression().' DESC')
            ->orderByDesc('posts.created_at');
        $popChunk = $popPosts->offset(($page - 1) * $nPop)->limit($nPop)->get();

        $merged = $followChunk->concat($popChunk)->unique('id')->values();

        if ($merged->count() < $perPage) {
            $need = $perPage - $merged->count();
            if ($need > 0) {
                $already = $merged->pluck('id')->all();
                $pad = $this->baseQuery();
                $this->applyExploreFilters($pad, $request);
                if ($already !== []) {
                    $pad->whereNotIn('posts.id', $already);
                }
                $pad->latest()->limit($need);
                $merged = $merged->concat($pad->get())->unique('id')->values()->take($perPage);
            }
        }

        $hasMore = $merged->isNotEmpty() && $merged->count() >= $perPage;

        return response()->json([
            'posts' => $merged
                ->map(fn (Post $post) => (new PostResource($post))->resolve())
                ->values()
                ->all(),
            'meta' => [
                'total_posts' => null,
                'limit' => $perPage,
                'current_page' => $page,
                'next_page' => $hasMore ? $page + 1 : null,
                'has_more' => $hasMore,
                'feed_mode' => 'mixed_70_30',
                'mixed' => [
                    'following_slots' => $nFollow,
                    'popular_slots' => $nPop,
                ],
            ],
        ]);
    }

    /**
     * Exploración global: recientes / populares / tendencia (invitados solo aquí para FYP recent van a mixed branch solo auth — guests hit globalExploreFeed with recent).
     */
    private function globalExploreFeed(FilterPostsRequest $request, int $perPage, int $page, string $sort): JsonResponse
    {
        $ttl = (int) config('performance.wall_guest_feed_ttl', 0);
        $guestCacheKey = null;

        if (! auth()->check() && $ttl > 0) {
            $guestCacheKey = $this->guestExploreCacheKey($request, $perPage, $page, $sort);
            $cached = Cache::get($guestCacheKey);
            if (is_array($cached)) {
                return response()->json($cached);
            }
        }

        $query = $this->baseQuery();
        $this->applyExploreFilters($query, $request);
        $this->applySort($query, $sort);

        $response = $this->jsonPaginate($query->simplePaginate($perPage, self::POST_COLUMNS, 'page', $page), 'explore_'.$sort);

        if ($guestCacheKey !== null && $ttl > 0) {
            $payload = json_decode($response->getContent(), true);
            if (is_array($payload)) {
                Cache::put($guestCacheKey, $payload, now()->addSeconds($ttl));
            }
        }

        return $response;
    }

    /**
     * Clave de caché para exploración global solo invitados (sin estado de «like» propio).
     */
    private function guestExploreCacheKey(FilterPostsRequest $request, int $perPage, int $page, string $sort): string
    {
        $tagIds = array_values(array_unique(array_map(
            'intval',
            $request->input('tag_ids', []) ?? []
        )));
        sort($tagIds);
        $search = trim((string) ($request->input('search') ?? $request->input('q', '')));

        $payload = [
            'sort' => $sort,
            'page' => $page,
            'per' => $perPage,
            'tags' => $tagIds,
            'search' => $search,
        ];

        return 'wall:guest:v1:'.hash('xxh128', json_encode($payload, JSON_THROW_ON_ERROR));
    }

    private function baseQuery(): Builder
    {
        $columns = self::POST_COLUMNS;
        foreach (['content', 'impacto_estimado', 'eco_score', 'eco_analysis', 'status', 'analysis_status', 'analysis_result', 'moderation_reason'] as $optional) {
            if (self::postColumnExists($optional)) {
                $columns[] = 'posts.'.$optional;
            }
        }

        $query = Post::query()
            ->select($columns)
            ->with([
                'user:id,first_name,last_name,username,profile_photo',
                'tags' => fn ($q) => $q
                    ->select('tags.id', 'tags.type', 'tags.slug', 'tags.name', 'tags.iso_code', 'tags.sort_order')
                    ->orderBy('type')
                    ->orderBy('sort_order'),
            ])
            ->withCount(['comments', 'likes']);

        if (self::postColumnExists('status')) {
            if (auth()->check()) {
                $query->where(function (Builder $sub): void {
                    $sub->where('posts.status', Post::STATUS_ACTIVE)
                        ->orWhere(function (Builder $own): void {
                            $own->where('posts.user_id', auth()->id())
                                ->whereIn('posts.status', [Post::STATUS_PENDING, Post::STATUS_ACTIVE]);
                        });
                });
            } else {
                $query->where('posts.status', Post::STATUS_ACTIVE);
            }
        }

        if (auth()->check()) {
            $query->withExists([
                'likes as liked_by_me' => fn ($q) => $q->where('user_id', auth()->id()),
            ]);
        }

        return $query;
    }

    private static function postColumnExists(string $column): bool
    {
        if (self::$postColumnPresence === null) {
            self::$postColumnPresence = [];

            try {
                $listing = Schema::getColumnListing('posts');
                foreach ($listing as $name) {
                    self::$postColumnPresence[(string) $name] = true;
                }
            } catch (\Throwable) {
                // Si no podemos inspeccionar esquema, asumimos columnas legacy mínimas.
            }
        }

        return self::$postColumnPresence[$column] ?? false;
    }

    private function applyExploreFilters(Builder $query, FilterPostsRequest $request): void
    {
        $tagIds = array_values(array_unique(array_map(
            'intval',
            $request->input('tag_ids', []) ?? []
        )));
        if ($tagIds !== []) {
            $requiredTags = count($tagIds);
            $query->whereIn('posts.id', function ($sub) use ($tagIds, $requiredTags) {
                $sub->from('post_tag')
                    ->select('post_tag.post_id')
                    ->whereIn('post_tag.tag_id', $tagIds)
                    ->groupBy('post_tag.post_id')
                    ->havingRaw('COUNT(DISTINCT post_tag.tag_id) = ?', [$requiredTags]);
            });
        }

        $search = trim((string) ($request->input('search') ?? $request->input('q', '')));
        if ($search !== '') {
            $safe = '%'.addcslashes($search, '%_\\').'%';
            $query->where(function ($w) use ($safe) {
                $w->where('posts.title', 'like', $safe)
                    ->orWhere('posts.description', 'like', $safe)
                    ->orWhereHas('tags', fn ($tq) => $tq->where('tags.name', 'like', $safe));
            });
        }
    }

    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'popular' => $query
                ->orderByRaw(self::engagementWithEcoScoreExpression().' DESC')
                ->orderByDesc('posts.created_at'),
            'trending' => $query
                ->where('posts.created_at', '>=', now()->subDays(30))
                ->orderByRaw(self::engagementWithEcoScoreExpression().' DESC')
                ->orderByDesc('posts.created_at'),
            default => $query->latest('posts.created_at'),
        };
    }

    private function jsonPaginate(LengthAwarePaginator|PaginatorContract $paginator, string $feedMode): JsonResponse
    {
        $nextPage = $paginator->hasMorePages()
            ? $paginator->currentPage() + 1
            : null;

        return response()->json([
            'posts' => collect($paginator->items())
                ->map(fn (Post $post) => (new PostResource($post))->resolve())
                ->values()
                ->all(),
            'meta' => [
                'total_posts' => $paginator instanceof LengthAwarePaginator ? $paginator->total() : null,
                'limit' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'next_page' => $nextPage,
                'has_more' => $paginator->hasMorePages(),
                'feed_mode' => $feedMode,
            ],
        ]);
    }
}

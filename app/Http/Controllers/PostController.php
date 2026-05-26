<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Jobs\GeneratePostAnalysisJob;
use App\Models\Post;
use App\Services\ContentGuard;
use App\Support\OperationalLogger;
use App\Support\OperationalMetrics;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PostController extends Controller
{
    public function store(StorePostRequest $request, ContentGuard $contentGuard): JsonResponse
    {
        $this->authorize('create', Post::class);

        $validated = $request->validated();
        $guardResult = $contentGuard->inspectPostPayload(
            $validated['title'] ?? null,
            $validated['description'] ?? null,
        );

        if ($guardResult['blocked'] === true) {
            Log::channel('structured')->warning('post.content_guard.blocked', [
                'user_id' => $request->user()?->id,
                'ip' => $request->ip(),
                'title_excerpt' => mb_substr((string) ($validated['title'] ?? ''), 0, 120),
                'description_excerpt' => mb_substr((string) ($validated['description'] ?? ''), 0, 280),
                'reasons' => $guardResult['reasons'],
            ]);

            return response()->json([
                'message' => 'Esta combinación no encaja con nuestro menú 🍽️',
                'errors' => [
                    'description' => ['Detectamos instrucciones fuera de contexto. Ajusta el texto y vuelve a intentarlo.'],
                ],
                'guard' => [
                    'blocked' => true,
                ],
            ], 422);
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('posts', 'public');
        }

        $post = $request->user()->posts()->create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'content' => $validated['description'],
            'impacto_estimado' => $validated['impacto_estimado'] ?? null,
            'image_path' => $imagePath,
            'status' => Post::STATUS_ACTIVE,
            'analysis_status' => Post::ANALYSIS_STATUS_PENDING,
            'analysis_result' => null,
            'ai_analysis' => null,
        ]);

        $tagIds = array_values(array_unique($validated['tags']));
        $post->tags()->sync($tagIds);

        OperationalLogger::postCreated($post, $request, count($tagIds));
        OperationalMetrics::incrementPostsCreated();

        $this->queueEcoAnalysis($post);

        $post->load([
            'user:id,first_name,last_name,username,profile_photo',
            'tags' => fn ($q) => $q->orderBy('type')->orderBy('sort_order'),
        ]);
        $post->loadCount(['comments', 'likes']);

        return response()->json([
            'post' => (new PostResource($post))->resolve(),
        ], 201);
    }

    public function show(Request $request, Post $post): JsonResponse|View
    {
        $post = $this->hydratePostForShow($post);

        if ($request->expectsJson()) {
            return response()->json([
                'post' => (new PostResource($post))->resolve(),
            ]);
        }

        $payload = (new PostResource($post))->resolve();

        return view('posts.show', [
            'post' => $post,
            'postPayload' => $payload,
            'pageTitle' => $post->title.' — '.config('app.name'),
            'metaDescription' => $payload['excerpt'] !== '' ? $payload['excerpt'] : $post->title,
            'ogImage' => $post->image_url ? url($post->image_url) : null,
            'ogUrl' => route('posts.show', $post),
            'postShowConfig' => [
                'postId' => $post->id,
                'postBaseUrl' => '/posts',
                'commentStoreUrl' => route('posts.comments.store', ['post' => $post], false),
                'loginUrl' => route('login', [], false),
                'isAuthenticated' => auth()->check(),
                'authUserId' => auth()->id(),
            ],
        ]);
    }

    /**
     * Regenera el análisis IA (solo autor del post).
     */
    public function reanalyze(Request $request, Post $post): JsonResponse
    {
        $this->authorize('update', $post);

        $this->markEcoAnalysisPending($post);
        $this->queueEcoAnalysis($post);

        return response()->json([
            'ok' => true,
            'message' => 'Hemos puesto el análisis en cola; en unos momentos estará listo.',
        ]);
    }

    public function update(UpdatePostRequest $request, Post $post): JsonResponse
    {
        $this->authorize('update', $post);

        $validated = $request->validated();

        $post->forceFill([
            'title' => (string) $validated['title'],
            'description' => (string) $validated['description'],
            'content' => (string) $validated['description'],
            'impacto_estimado' => $validated['impacto_estimado'] ?? null,
        ])->save();

        $tagIds = array_values(array_unique($validated['tags']));
        $post->tags()->sync($tagIds);

        $this->markEcoAnalysisPending($post);
        $this->queueEcoAnalysis($post);

        $post->load([
            'user:id,first_name,last_name,username,profile_photo',
            'tags' => fn ($q) => $q->orderBy('type')->orderBy('sort_order'),
        ]);
        $post->loadCount(['comments', 'likes']);

        return response()->json([
            'ok' => true,
            'message' => 'Post actualizado, re-analizando...',
            'post' => (new PostResource($post))->resolve(),
        ]);
    }

    /**
     * Reinicia solo el análisis Eco (IA). El post sigue visible (status active).
     */
    private function markEcoAnalysisPending(Post $post): void
    {
        $post->forceFill([
            'analysis_status' => Post::ANALYSIS_STATUS_PENDING,
            'analysis_result' => null,
            'moderation_reason' => null,
            'eco_analysis'    => null,
            'eco_score'       => 0,
        ])->save();
    }

    private function queueEcoAnalysis(Post $post): void
    {
        GeneratePostAnalysisJob::dispatch($post->id)->afterCommit();
    }

    private function hydratePostForShow(Post $post): Post
    {
        $post->load([
            'user:id,first_name,last_name,username,profile_photo',
            'tags' => fn ($q) => $q->orderBy('type')->orderBy('sort_order'),
            'comments' => fn ($q) => $q->with('user:id,first_name,last_name,username,profile_photo')
                ->orderBy('created_at'),
        ]);
        $post->loadCount(['comments', 'likes']);

        if (auth()->check()) {
            $post->setAttribute(
                'liked_by_me',
                $post->likes()->where('user_id', auth()->id())->exists(),
            );
        }

        return $post;
    }
}

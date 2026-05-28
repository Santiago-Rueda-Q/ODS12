<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function index(): JsonResponse
    {
        $tags = Tag::cachedCatalog()->map(fn (Tag $t) => $t->only([
            'id', 'type', 'slug', 'name', 'sort_order',
        ]));

        $grouped = $tags->groupBy('type')->map(
            fn ($group) => $group->values()->all()
        )->all();

        return response()->json([
            'tags_by_type' => $grouped,
        ]);
    }

    /**
     * Búsqueda de etiquetas por nombre (solo catálogo existente).
     *
     * @return JsonResponse{tags: list<array{id: int, name: string, slug: string, type: string}>}
     */
    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) > 80) {
            return response()->json(['tags' => []]);
        }

        $query = Tag::query()->limit(50);

        if ($q === '') {
            $query->orderBy('sort_order')->orderBy('name');
        } else {
            $like = '%'.addcslashes($q, '%_\\').'%';
            $query->where('name', 'LIKE', $like)->orderBy('name');
        }

        $tags = $query->get(['id', 'name', 'slug', 'type']);

        return response()->json([
            'tags' => $tags->map(fn (Tag $t) => [
                'id' => $t->id,
                'name' => $t->name,
                'slug' => $t->slug,
                'type' => $t->type,
            ])->values()->all(),
        ]);
    }
}

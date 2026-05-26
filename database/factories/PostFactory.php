<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraphs(3, true),
            'content' => fake()->paragraphs(3, true),
            'impacto_estimado' => fake()->sentence(),
            'eco_score' => fake()->numberBetween(1, 10),
            'image_path' => null,
            'status' => Post::STATUS_ACTIVE,
            'analysis_status' => Post::ANALYSIS_STATUS_COMPLETED,
            'analysis_result' => null,
            'moderation_reason' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Post $post): void {
            $country = Tag::query()->where('type', Tag::TYPE_COUNTRY)->inRandomOrder()->first();
            $ods12 = Tag::query()->where('type', Tag::TYPE_ODS12)->inRandomOrder()->take(2)->get();

            $ids = collect([$country?->id])
                ->merge($ods12->pluck('id'))
                ->filter()
                ->toArray();

            if ($ids !== []) {
                $post->tags()->sync($ids);
            }
        });
    }
}

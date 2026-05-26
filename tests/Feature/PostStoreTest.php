<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\TagSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PostStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_post_without_image(): void
    {
        $this->seed(TagSeeder::class);
        $user = User::factory()->create();
        $tagIds = Tag::query()->pluck('id')->take(4)->all();
        $this->assertNotEmpty($tagIds);

        $response = $this->actingAs($user)->postJson(route('posts.store'), [
            'title' => 'EcoAnálisis de prueba',
            'description' => 'Descripción con detalle.',
            'tags' => $tagIds,
        ]);

        $response->assertCreated()
            ->assertJsonPath('post.title', 'EcoAnálisis de prueba')
            ->assertJsonPath('post.status', Post::STATUS_ACTIVE);
    }

    public function test_authenticated_user_can_create_post_with_image(): void
    {
        $this->seed(TagSeeder::class);
        $user = User::factory()->create();
        $tagId = Tag::query()->value('id');
        $this->assertNotNull($tagId);

        $file = UploadedFile::fake()->image('dish.jpg', 800, 600);

        $response = $this->actingAs($user)->post(route('posts.store'), [
            'title' => 'Con imagen',
            'description' => 'Texto',
            'tags' => [$tagId],
            'image' => $file,
        ]);

        $response->assertCreated();
    }
}

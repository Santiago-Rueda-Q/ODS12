<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('user_id')->constrained('comments')->cascadeOnDelete();
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->text('description')->nullable()->after('title');
            $table->string('image_path')->nullable()->after('description');
        });

        if (Schema::hasColumn('posts', 'story')) {
            DB::statement('UPDATE posts SET description = story WHERE description IS NULL');
        }

        $this->seedTags();

        $this->migrateLegacyPostTags();

        // MySQL: hay que eliminar la FK por nombre real; dropForeign() a veces no la retira y el índice
        // compuesto (country_id, created_at) sigue «atado» a la FK → error 1553 al hacer dropIndex.
        if (Schema::hasColumn('posts', 'country_id')) {
            $this->dropPostsCountryForeignKeyIfExists();

            Schema::table('posts', function (Blueprint $table) {
                $table->dropIndex(['country_id', 'created_at']);
                $table->dropIndex(['experience_type', 'drink_type']);
            });

            Schema::table('posts', function (Blueprint $table) {
                $table->dropColumn([
                    'country_id',
                    'story',
                    'food_label',
                    'drink_label',
                    'experience_type',
                    'drink_type',
                ]);
            });
        }

        Schema::table('posts', function (Blueprint $table) {
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        throw new RuntimeException(
            'La migración refactor_posts_for_tags_and_comment_threads es irreversible. En desarrollo usa php artisan migrate:fresh.'
        );
    }

    private function seedTags(): void
    {
        $now = now();
        $rows = [];

        $blocks = [
            'country' => [
                ['slug' => 'colombia', 'name' => 'Colombia', 'sort_order' => 1],
                ['slug' => 'mexico', 'name' => 'México', 'sort_order' => 2],
                ['slug' => 'argentina', 'name' => 'Argentina', 'sort_order' => 3],
                ['slug' => 'espana', 'name' => 'España', 'sort_order' => 4],
            ],
            'food_type' => [
                ['slug' => 'dulce', 'name' => 'Dulce', 'sort_order' => 1],
                ['slug' => 'salado', 'name' => 'Salado', 'sort_order' => 2],
            ],
            'experience' => [
                ['slug' => 'tradicional', 'name' => 'Tradicional', 'sort_order' => 1],
                ['slug' => 'callejero', 'name' => 'Callejero', 'sort_order' => 2],
                ['slug' => 'gourmet', 'name' => 'Gourmet', 'sort_order' => 3],
            ],
            'drink' => [
                ['slug' => 'cafe', 'name' => 'Café', 'sort_order' => 1],
                ['slug' => 'vino', 'name' => 'Vino', 'sort_order' => 2],
                ['slug' => 'cerveza', 'name' => 'Cerveza', 'sort_order' => 3],
                ['slug' => 'impactos_tradicionales', 'name' => 'Bebidas tradicionales', 'sort_order' => 4],
            ],
        ];

        foreach ($blocks as $type => $items) {
            foreach ($items as $item) {
                $rows[] = [
                    'type' => $type,
                    'slug' => $item['slug'],
                    'name' => $item['name'],
                    'sort_order' => $item['sort_order'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach ($rows as $row) {
            DB::table('tags')->updateOrInsert(
                ['type' => $row['type'], 'slug' => $row['slug']],
                [
                    'name' => $row['name'],
                    'sort_order' => $row['sort_order'],
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at'],
                ]
            );
        }
    }

    /**
     * En MySQL 8 la FK sobre country_id puede seguir bloqueando el drop del índice compuesto
     * si el nombre de restricción no coincide con el que espera Blueprint::dropForeign().
     */
    private function dropPostsCountryForeignKeyIfExists(): void
    {
        $connection = Schema::getConnection();

        if ($connection->getDriverName() !== 'mysql') {
            Schema::table('posts', function (Blueprint $table) {
                $table->dropForeign(['country_id']);
            });

            return;
        }

        $database = $connection->getDatabaseName();
        $rows = DB::select(
            'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
               AND REFERENCED_TABLE_NAME IS NOT NULL',
            [$database, 'posts', 'country_id']
        );

        foreach ($rows as $row) {
            $name = $row->CONSTRAINT_NAME;
            DB::statement('ALTER TABLE posts DROP FOREIGN KEY `'.$name.'`');
        }
    }

    private function migrateLegacyPostTags(): void
    {
        if (! Schema::hasColumn('posts', 'country_id')) {
            return;
        }

        $posts = DB::table('posts')->select('*')->get();
        $tagIdByKey = DB::table('tags')->get()->mapWithKeys(fn ($t) => ["{$t->type}:{$t->slug}" => $t->id]);

        foreach ($posts as $p) {
            $ids = [];

            $country = DB::table('countries')->where('id', $p->country_id)->first();
            if ($country !== null) {
                $key = 'country:'.$country->slug;
                if (isset($tagIdByKey[$key])) {
                    $ids[] = $tagIdByKey[$key];
                }
            }

            $exp = $p->experience_type ?? '';
            if (in_array($exp, ['tradicional', 'callejero', 'gourmet'], true)) {
                $k = 'experience:'.$exp;
                if (isset($tagIdByKey[$k])) {
                    $ids[] = $tagIdByKey[$k];
                }
            } elseif ($exp === 'dulce') {
                if (isset($tagIdByKey['food_type:dulce'])) {
                    $ids[] = $tagIdByKey['food_type:dulce'];
                }
                if (isset($tagIdByKey['experience:tradicional'])) {
                    $ids[] = $tagIdByKey['experience:tradicional'];
                }
            } elseif ($exp === 'salado') {
                if (isset($tagIdByKey['food_type:salado'])) {
                    $ids[] = $tagIdByKey['food_type:salado'];
                }
                if (isset($tagIdByKey['experience:tradicional'])) {
                    $ids[] = $tagIdByKey['experience:tradicional'];
                }
            }

            $drink = $p->drink_type ?? '';
            $drinkSlug = match ($drink) {
                'cafe' => 'cafe',
                'vino' => 'vino',
                'cerveza' => 'cerveza',
                'tradicional' => 'impactos_tradicionales',
                default => null,
            };
            if ($drinkSlug !== null) {
                $k = 'drink:'.$drinkSlug;
                if (isset($tagIdByKey[$k])) {
                    $ids[] = $tagIdByKey[$k];
                }
            }

            $ids = array_unique($ids);
            foreach ($ids as $tagId) {
                DB::table('post_tag')->insertOrIgnore([
                    'post_id' => $p->id,
                    'tag_id' => $tagId,
                ]);
            }
        }
    }
};

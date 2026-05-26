<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            $this->command?->warn('PostSeeder deshabilitado fuera de entorno local.');

            return;
        }

        $user = User::query()->first();
        if ($user === null) {
            return;
        }

        $t = static fn (string $type, string $slug): ?int => Tag::query()
            ->where('type', $type)
            ->where('slug', $slug)
            ->value('id');

        $posts = [
            [
                'tags' => array_filter([
                    $t(Tag::TYPE_COUNTRY, 'colombia'),
                    $t(Tag::TYPE_FOOD_TYPE, 'salado'),
                    $t(Tag::TYPE_EXPERIENCE, 'tradicional'),
                    $t(Tag::TYPE_DRINK, 'cafe'),
                ]),
                'title' => 'Café de origen con arepa de choclo',
                'description' => "Un domingo en Bogotá descubrimos un pequeño tostadero que marida arepas de choclo calientes con un café de origen de Huila, frutal y con notas a panela. La grasa suave del queso untado equilibra la acidez del café y deja un final largo y reconfortable.\n\nLo mejor: pedir el café en método V60 para resaltar los aromas florales junto al maíz dulce de la arepa.",
            ],
            [
                'tags' => array_filter([
                    $t(Tag::TYPE_COUNTRY, 'colombia'),
                    $t(Tag::TYPE_FOOD_TYPE, 'salado'),
                    $t(Tag::TYPE_EXPERIENCE, 'callejero'),
                    $t(Tag::TYPE_DRINK, 'impactos-tradicionales'),
                ]),
                'title' => 'Aguardiente y empanadas santandereanas',
                'description' => "El contraste entre el anís del aguardiente antioqueño y la sal de las empanadas de pipián crea un puente sorprendente: primero frescura anisada, luego umami de la carne cocida lentamente.\n\nSirve el aguardiente muy frío y las empanadas recién horneadas.",
            ],
            [
                'tags' => array_filter([
                    $t(Tag::TYPE_COUNTRY, 'mexico'),
                    $t(Tag::TYPE_FOOD_TYPE, 'salado'),
                    $t(Tag::TYPE_EXPERIENCE, 'callejero'),
                    $t(Tag::TYPE_DRINK, 'cerveza'),
                ]),
                'title' => 'Tacos al pastor y michelada',
                'description' => "La piña asada de los tacos al pastor pide algo cítrico y refrescante. Una michelada con sal en el borde realza el chile guajillo sin tapar el cerdo marinado.\n\nIdeal para la noche en un puesto de la ciudad: comparte tabla y prueba dos salsas distintas entre taco y taco.",
            ],
            [
                'tags' => array_filter([
                    $t(Tag::TYPE_COUNTRY, 'mexico'),
                    $t(Tag::TYPE_FOOD_TYPE, 'salado'),
                    $t(Tag::TYPE_EXPERIENCE, 'gourmet'),
                    $t(Tag::TYPE_DRINK, 'impactos-tradicionales'),
                ]),
                'title' => 'Mole negro Oaxaqueño y mezcal joven',
                'description' => "El mole lleva decenas de ingredientes; el mezcal joven aporta notas herbáneas y ahumadas que dialogan con el chocolate amargo del mole sin dominarlo.\n\nSirve porciones pequeñas: la intensidad es alta y el paladar se fatiga rápido.",
            ],
            [
                'tags' => array_filter([
                    $t(Tag::TYPE_COUNTRY, 'argentina') ?: $t(Tag::TYPE_COUNTRY, 'mexico'),
                    $t(Tag::TYPE_FOOD_TYPE, 'salado'),
                    $t(Tag::TYPE_EXPERIENCE, 'tradicional'),
                    $t(Tag::TYPE_DRINK, 'vino'),
                ]),
                'title' => 'Malbec y empanadas salteñas',
                'description' => "Los taninos del malbec cortan la grasa jugosa de la empanada salteña y amplifican las especias del relleno. Temperatura ambiente para el vino y empanadas calientes.\n\nUn EcoAnálisis de domingo que nunca falla entre amigos.",
            ],
        ];

        foreach ($posts as $data) {
            $tagIds = $data['tags'];
            if ($tagIds === []) {
                continue;
            }

            $post = Post::query()->create([
                'user_id' => $user->id,
                'title' => $data['title'],
                'description' => $data['description'],
                'image_path' => null,
            ]);

            $post->tags()->sync($tagIds);

            Comment::query()->create([
                'post_id' => $post->id,
                'user_id' => $user->id,
                'body' => '¡Gran combinación! Lo probaré el fin de semana.',
            ]);
        }
    }
}

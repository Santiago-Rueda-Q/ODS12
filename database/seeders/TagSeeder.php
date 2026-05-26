<?php

namespace Database\Seeders;

use App\Models\Tag;
use App\Support\CountryNameIsoMap;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Ods12TagsCatalog::byType() as $type => $names) {
            foreach ($names as $idx => $name) {
                $base = Str::slug($name);
                $slug = $base !== '' ? $base : 'item-'.($idx + 1);
                $unique = $slug;
                $suffix = 2;
                while (
                    Tag::query()
                        ->where('type', $type)
                        ->where('slug', $unique)
                        ->where('name', '!=', $name)
                        ->exists()
                ) {
                    $unique = $slug.'-'.$suffix;
                    $suffix++;
                }

                Tag::query()->updateOrCreate(
                    ['type' => $type, 'slug' => $unique],
                    [
                        'name' => $name,
                        'sort_order' => $idx + 1,
                        'iso_code' => $type === Tag::TYPE_COUNTRY
                            ? CountryNameIsoMap::isoForCountryName($name)
                            : null,
                    ],
                );
            }
        }
    }
}

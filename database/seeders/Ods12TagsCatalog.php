<?php

namespace Database\Seeders;

use App\Models\Tag;

final class Ods12TagsCatalog
{
    /**
     * @return array<string, list<string>>
     */
    public static function byType(): array
    {
        return [
            Tag::TYPE_ODS12 => self::ods12(),
        ];
    }

    /**
     * @return list<string>
     */
    private static function ods12(): array
    {
        return [
            'Reciclaje',
            'Economía Circular',
            'Consumo Responsable',
            'Reducción de Residuos',
            'Moda Sostenible',
            'Energía Limpia',
            'Agricultura Sostenible',
            'Agua y Recursos',
            'Transporte Verde',
            'Educación Ambiental',
            'Compostaje',
            'Cero Basura',
            'Energía Solar',
            'Consumo Local',
            'Huella de Carbono'
        ];
    }
}

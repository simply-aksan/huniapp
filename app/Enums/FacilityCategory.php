<?php

declare(strict_types=1);

namespace App\Enums;

enum FacilityCategory: string
{
    case Kamar = 'kamar';
    case Umum = 'umum';
    case Keamanan = 'keamanan';
    case Parkir = 'parkir';
    case Lainnya = 'lainnya';

    public function label(): string
    {
        return match ($this) {
            self::Kamar => 'Fasilitas Kamar',
            self::Umum => 'Fasilitas Umum',
            self::Keamanan => 'Keamanan',
            self::Parkir => 'Area Parkir',
            self::Lainnya => 'Lainnya',
        };
    }

    /**
     * Get all possible raw values.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

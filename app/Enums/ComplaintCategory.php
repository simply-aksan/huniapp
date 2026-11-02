<?php

declare(strict_types=1);

namespace App\Enums;

enum ComplaintCategory: string
{
    case Kebersihan = 'Kebersihan';
    case FasilitasKamar = 'Fasilitas Kamar';
    case Listrik = 'Listrik';
    case AirSanitasi = 'Air & Sanitasi';
    case Keamanan = 'Keamanan & Ketertiban';
    case Internet = 'Internet / Wi-Fi';
    case Lainnya = 'Lainnya';

    public function label(): string
    {
        return $this->value;
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

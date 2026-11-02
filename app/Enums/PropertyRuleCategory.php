<?php

declare(strict_types=1);

namespace App\Enums;

enum PropertyRuleCategory: string
{
    case Umum = 'umum';
    case JamMalam = 'jam_malam';
    case Tamu = 'tamu';
    case Kebersihan = 'kebersihan';
    case Fasilitas = 'fasilitas';
    case Keamanan = 'keamanan';
    case Parkir = 'parkir';
    case Pembayaran = 'pembayaran';
    case Lainnya = 'lainnya';

    public function label(): string
    {
        return match ($this) {
            self::Umum => 'Aturan Umum',
            self::JamMalam => 'Jam Malam & Gerbang',
            self::Tamu => 'Tamu & Menginap',
            self::Kebersihan => 'Kebersihan & Sampah',
            self::Fasilitas => 'Fasilitas Bersama',
            self::Keamanan => 'Keamanan & Ketertiban',
            self::Parkir => 'Parkir Kendaraan',
            self::Pembayaran => 'Ketentuan Pembayaran',
            self::Lainnya => 'Lain-lain',
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

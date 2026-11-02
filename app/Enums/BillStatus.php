<?php

declare(strict_types=1);

namespace App\Enums;

enum BillStatus: string
{
    case MenungguPembayaran = 'menunggu_pembayaran';
    case Sebagian = 'sebagian';
    case Lunas = 'lunas';
    case JatuhTempo = 'jatuh_tempo';
    case Dibatalkan = 'dibatalkan';

    public function label(): string
    {
        return match ($this) {
            self::MenungguPembayaran => 'Menunggu Pembayaran',
            self::Sebagian => 'Sebagian',
            self::Lunas => 'Lunas',
            self::JatuhTempo => 'Jatuh Tempo',
            self::Dibatalkan => 'Dibatalkan',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::MenungguPembayaran => 'warning',
            self::Sebagian => 'warning',
            self::Lunas => 'aktif',
            self::JatuhTempo => 'danger',
            self::Dibatalkan => 'secondary',
        };
    }

    /**
     * Determine if this bill has been completely paid.
     */
    public function isPaid(): bool
    {
        return $this === self::Lunas;
    }

    /**
     * Determine if this bill has been cancelled.
     */
    public function isCancelled(): bool
    {
        return $this === self::Dibatalkan;
    }

    /**
     * Determine if this status allows accepting incoming payments.
     */
    public function canAcceptPayment(): bool
    {
        return in_array($this, [self::MenungguPembayaran, self::Sebagian, self::JatuhTempo], true);
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

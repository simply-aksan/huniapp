<?php

declare(strict_types=1);

namespace App\Http\Requests\Owner;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReportFilterRequest extends FormRequest
{
    /**
     * Allowed report types.
     */
    public const ALLOWED_REPORT_TYPES = [
        'revenue',
        'payments',
        'bills',
        'occupancy',
        'complaints',
        'inventory',
        // Indonesian aliases for convenience
        'pendapatan',
        'pembayaran',
        'tagihan',
        'tunggakan',
        'hunian',
        'komplain',
        'inventaris',
    ];

    /**
     * Determine if the user is authorized to make this request.
     * Prevents cross-owner data leakage by rejecting unauthorized property_id access with 403.
     */
    public function authorize(): bool
    {
        /** @var User $user */
        $user = $this->user();

        if ($this->filled('property_id')) {
            $propertyId = (int) $this->input('property_id');
            if ($propertyId > 0 && ! $user->properties()->where('id', $propertyId)->exists()) {
                return false; // Automatically aborts with 403 Forbidden
            }
        }

        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('report_type') || empty($this->input('report_type'))) {
            $this->merge(['report_type' => 'revenue']);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'report_type' => ['required', 'string', 'in:'.implode(',', self::ALLOWED_REPORT_TYPES)],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'property_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'string', 'max:50'],
            'period_type' => ['nullable', 'string', 'in:month,quarter,year'],
            'period' => ['nullable', 'string', 'max:20'],
        ];
    }

    /**
     * Normalize report type alias to canonical English key.
     */
    public function normalizedReportType(): string
    {
        $type = strtolower((string) $this->input('report_type', 'revenue'));

        return match ($type) {
            'pendapatan', 'revenue' => 'revenue',
            'pembayaran', 'payments' => 'payments',
            'tagihan', 'tunggakan', 'bills' => 'bills',
            'hunian', 'occupancy' => 'occupancy',
            'komplain', 'complaints' => 'complaints',
            'inventaris', 'fasilitas', 'inventory' => 'inventory',
            default => 'revenue',
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests\Owner;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AnalyticsFilterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Prevents cross-owner data leakage by rejecting unauthorized property_id access.
     */
    public function authorize(): bool
    {
        /** @var User $user */
        $user = $this->user();

        if ($this->filled('property_id')) {
            $propertyId = (int) $this->input('property_id');
            if ($propertyId > 0 && ! $user->properties()->where('id', $propertyId)->exists()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'period_type' => ['nullable', 'string', 'in:month,quarter,year'],
            'period' => ['nullable', 'string', 'max:20'],
            'property_id' => ['nullable', 'integer'],
        ];
    }
}

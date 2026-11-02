<?php

declare(strict_types=1);

namespace App\Http\Requests\Owner;

use App\Models\Contract;
use App\Services\RoomOccupancyService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class RenewContractRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $contract = $this->route('contract');

        return $contract instanceof Contract
            && $this->user()?->isOwner()
            && (bool) $contract->property?->isOwnedBy($this->user());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'duration_months' => ['nullable', 'integer', 'min:1'],
            'monthly_rent' => ['required', 'numeric', 'min:0'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'deposit_status' => ['nullable', 'string', 'in:belum_dibayar,sudah_dibayar,lunas,pending'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }

    /**
     * Cross-entity business rule validations.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $contract = $this->route('contract');
            if (! $contract instanceof Contract) {
                return;
            }

            if ($contract->isCancelled()) {
                $validator->errors()->add('start_date', 'Kontrak yang telah dibatalkan tidak dapat diperpanjang.');

                return;
            }

            $startDate = (string) $this->input('start_date');
            $endDate = (string) $this->input('end_date');

            if ($startDate && $endDate && $contract->room) {
                // Must not overlap with any OTHER active contract for this room (exclude the current parent contract)
                $occupancyService = app(RoomOccupancyService::class);
                $overlapping = $occupancyService->getOverlappingContract($contract->room, $startDate, $endDate, $contract->id);
                if ($overlapping) {
                    $validator->errors()->add('start_date', 'Periode perpanjangan sewa bertabrakan dengan kontrak aktif lain ('.$overlapping->contract_number.').');
                }
            }
        });
    }

    /**
     * Custom Indonesian validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'start_date.required' => 'Tanggal mulai perpanjangan wajib diisi.',
            'end_date.required' => 'Tanggal berakhir perpanjangan wajib diisi.',
            'end_date.after' => 'Tanggal berakhir harus setelah tanggal mulai sewa.',
            'monthly_rent.required' => 'Harga sewa per bulan wajib diisi.',
            'monthly_rent.numeric' => 'Harga sewa harus berupa angka valid.',
            'monthly_rent.min' => 'Harga sewa tidak boleh bernilai negatif.',
            'deposit_amount.numeric' => 'Nominal deposit harus berupa angka valid.',
            'deposit_amount.min' => 'Nominal deposit tidak boleh bernilai negatif.',
            'document.mimes' => 'Format dokumen harus berupa PDF, JPG, JPEG, atau PNG.',
            'document.max' => 'Ukuran dokumen maksimal adalah 10MB.',
        ];
    }
}

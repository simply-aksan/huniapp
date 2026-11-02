<?php

declare(strict_types=1);

namespace App\Http\Requests\Owner;

use App\Enums\ContractStatus;
use App\Models\Contract;
use App\Services\RoomOccupancyService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateContractRequest extends FormRequest
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
        $contract = $this->route('contract');
        $isDraft = $contract instanceof Contract && $contract->isDraft();

        return [
            'start_date' => $isDraft ? ['required', 'date'] : ['nullable', 'date'],
            'end_date' => ['required', 'date'],
            'duration_months' => ['nullable', 'integer', 'min:1'],
            'monthly_rent' => $isDraft ? ['required', 'numeric', 'min:0'] : ['nullable', 'numeric', 'min:0'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'deposit_status' => ['nullable', 'string', 'in:belum_dibayar,sudah_dibayar,lunas,pending'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'status' => $isDraft ? ['nullable', 'string', 'in:'.implode(',', [ContractStatus::Draft->value, ContractStatus::Aktif->value])] : ['nullable', 'string'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Configure the validator instance with cross-entity business rules.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $contract = $this->route('contract');
            if (! $contract instanceof Contract) {
                return;
            }

            // 1. Prevent editing already completed or cancelled contracts
            if ($contract->isCompleted() || $contract->isCancelled()) {
                $validator->errors()->add('status', 'Kontrak dengan status '.$contract->status.' sudah terkunci dan tidak dapat diubah.');

                return;
            }

            $startDate = $this->filled('start_date') ? (string) $this->input('start_date') : $contract->start_date?->toDateString();
            $endDate = (string) $this->input('end_date');
            $targetStatus = $this->filled('status') ? (string) $this->input('status') : $contract->status;

            if ($startDate && $endDate && $endDate <= $startDate) {
                $validator->errors()->add('end_date', 'Tanggal berakhir sewa harus setelah tanggal mulai sewa.');

                return;
            }

            // 2. If status is or will be aktif, verify no overlapping active contracts
            if ($targetStatus === ContractStatus::Aktif->value && $startDate && $endDate && $contract->room) {
                $occupancyService = app(RoomOccupancyService::class);
                $overlapping = $occupancyService->getOverlappingContract($contract->room, $startDate, $endDate, $contract->id);
                if ($overlapping) {
                    $validator->errors()->add('end_date', 'Periode sewa bertabrakan dengan kontrak aktif lain ('.$overlapping->contract_number.').');
                }
            }

            // 3. Financial history integrity (Audit Finding B-4)
            // If the contract has issued bills/financial records, prevent altering financial terms
            // (end_date, duration_months, monthly_rent, deposit_amount) that retroactively recalculate agreed totals
            if ($contract->bills()->exists()) {
                $endDateChanged = $this->filled('end_date') && $this->input('end_date') !== $contract->end_date?->toDateString();
                $depositChanged = $this->filled('deposit_amount') && (float) $this->input('deposit_amount') !== (float) $contract->deposit_amount;
                $rentChanged = $this->filled('monthly_rent') && (float) $this->input('monthly_rent') !== (float) $contract->monthly_rent;

                if ($endDateChanged || $depositChanged || $rentChanged) {
                    $validator->errors()->add('end_date', 'Kontrak ini telah memiliki riwayat tagihan/keuangan sehingga periode sewa dan ketentuan finansial tidak dapat diubah secara retroaktif.');
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
            'start_date.required' => 'Tanggal mulai sewa wajib diisi.',
            'end_date.required' => 'Tanggal berakhir sewa wajib diisi.',
            'end_date.after' => 'Tanggal berakhir sewa harus setelah tanggal mulai sewa.',
            'monthly_rent.required' => 'Harga sewa per bulan wajib diisi.',
            'monthly_rent.numeric' => 'Harga sewa harus berupa angka valid.',
            'monthly_rent.min' => 'Harga sewa tidak boleh bernilai negatif.',
            'deposit_amount.numeric' => 'Nominal deposit harus berupa angka valid.',
            'deposit_amount.min' => 'Nominal deposit tidak boleh bernilai negatif.',
        ];
    }
}

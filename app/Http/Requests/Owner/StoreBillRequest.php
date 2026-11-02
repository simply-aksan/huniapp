<?php

declare(strict_types=1);

namespace App\Http\Requests\Owner;

use App\Enums\BillType;
use App\Models\Bill;
use App\Models\Contract;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreBillRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isOwner() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'contract_id' => ['required', 'integer', 'exists:contracts,id'],
            'type' => ['required', 'string', 'in:'.implode(',', BillType::values())],
            'billing_period' => ['nullable', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'issue_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.amount' => ['required', 'numeric', 'min:1'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'contract_id.required' => 'Pilih kontrak sewa terlebih dahulu.',
            'contract_id.exists' => 'Kontrak sewa yang dipilih tidak valid.',
            'type.required' => 'Pilih jenis tagihan.',
            'type.in' => 'Jenis tagihan yang dipilih tidak valid.',
            'billing_period.regex' => 'Format periode penagihan harus YYYY-MM (contoh: 2026-10).',
            'issue_date.required' => 'Tanggal terbit tagihan wajib diisi.',
            'due_date.after_or_equal' => 'Tanggal jatuh tempo tidak boleh mendahului tanggal terbit.',
            'items.required' => 'Tagihan harus memiliki minimal satu rincian item.',
            'items.min' => 'Tagihan harus memiliki minimal satu rincian item.',
            'items.*.description.required' => 'Deskripsi rincian item wajib diisi.',
            'items.*.amount.required' => 'Nominal rincian item wajib diisi.',
            'items.*.amount.min' => 'Nominal rincian item harus lebih besar dari 0.',
            'items.*.quantity.min' => 'Jumlah item minimal 1.',
        ];
    }

    /**
     * Configure the validator instance with business logic assertions.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $contractId = (int) $this->input('contract_id');
            $type = (string) $this->input('type');
            $billingPeriod = $this->filled('billing_period') ? trim((string) $this->input('billing_period')) : null;

            if ($contractId <= 0) {
                return;
            }

            /** @var Contract|null $contract */
            $contract = Contract::with('property')->find($contractId);

            if (! $contract) {
                $v->errors()->add('contract_id', 'Kontrak sewa tidak ditemukan.');

                return;
            }

            // Scoping: Contract must belong to a property owned by the authenticated Owner
            if ($contract->property?->owner_id !== $this->user()?->id) {
                $v->errors()->add('contract_id', 'Anda tidak memiliki hak akses terhadap kontrak pada properti ini.');

                return;
            }

            // Type 1: Rent (sewa)
            if ($type === BillType::Sewa->value) {
                if (! $contract->isActive()) {
                    $v->errors()->add('contract_id', 'Tagihan sewa bulanan hanya dapat dibuat untuk kontrak yang berstatus aktif.');
                }

                if (empty($billingPeriod)) {
                    $v->errors()->add('billing_period', 'Periode penagihan (YYYY-MM) wajib diisi untuk tagihan sewa bulanan.');
                } else {
                    $dedupKey = "rent:{$contract->id}:{$billingPeriod}";
                    if (Bill::where('deduplication_key', $dedupKey)->exists()) {
                        $v->errors()->add('billing_period', "Tagihan sewa untuk kontrak ini pada periode {$billingPeriod} sudah pernah dibuat.");
                    }
                }
            }

            // Type 2: Deposit (deposit)
            if ($type === BillType::Deposit->value) {
                $dedupKey = "deposit:{$contract->id}";
                $hasDepositBill = Bill::where('deduplication_key', $dedupKey)
                    ->orWhere(fn ($q) => $q->where('contract_id', $contract->id)->where('type', BillType::Deposit->value))
                    ->exists();

                if ($hasDepositBill) {
                    $v->errors()->add('type', 'Tagihan uang jaminan (deposit) untuk kontrak sewa ini sudah pernah dibuat sebelumnya.');
                }

                if ((float) $contract->deposit_amount <= 0) {
                    $v->errors()->add('type', 'Kontrak sewa ini tidak memiliki nominal uang jaminan (deposit).');
                }
            }

            // Type 3: Additional (tambahan)
            // Multiple additional bills for the same contract and period are permitted.
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Models\Bill;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isTenant() ?? false;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Support single file input named 'proof' as array in 'proofs'
        if ($this->hasFile('proof') && ! $this->hasFile('proofs')) {
            $this->merge([
                'proofs' => [$this->file('proof')],
            ]);
        }

        // Default payment date to now if not provided
        if (! $this->filled('payment_date')) {
            $this->merge([
                'payment_date' => now()->toDateString(),
            ]);
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
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', 'string', 'max:50'],
            'payment_date' => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'proofs' => ['required', 'array', 'min:1', 'max:5'],
            'proofs.*' => [
                'required',
                'file',
                'mimes:jpeg,jpg,png,pdf',
                'max:5120', // Max 5MB per file
            ],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'Nominal pembayaran wajib diisi.',
            'amount.numeric' => 'Nominal pembayaran harus berupa angka.',
            'amount.min' => 'Nominal pembayaran harus lebih besar dari 0.',
            'payment_method.required' => 'Pilih metode pembayaran yang digunakan.',
            'payment_date.required' => 'Tanggal pembayaran wajib diisi.',
            'proofs.required' => 'Wajib mengunggah minimal satu file bukti pembayaran.',
            'proofs.min' => 'Wajib mengunggah minimal satu file bukti pembayaran.',
            'proofs.*.required' => 'File bukti pembayaran wajib disertakan.',
            'proofs.*.file' => 'Bukti pembayaran harus berupa file yang valid.',
            'proofs.*.mimes' => 'Format bukti pembayaran harus berupa JPEG, PNG, atau PDF.',
            'proofs.*.max' => 'Ukuran file bukti pembayaran maksimal 5MB per file.',
        ];
    }

    /**
     * Configure the validator instance with business rule assertions.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            /** @var Bill|null $bill */
            $bill = $this->route('bill');

            if (! $bill instanceof Bill) {
                $v->errors()->add('amount', 'Tagihan tidak ditemukan.');

                return;
            }

            // 1. Authorization & Scoping: Invoice must belong to the authenticated Tenant
            if ($bill->user_id !== $this->user()?->id) {
                $v->errors()->add('amount', 'Anda tidak memiliki hak akses terhadap tagihan ini.');

                return;
            }

            // 2. Eligibility: Invoice must not be cancelled or fully paid
            if ($bill->isPaid()) {
                $v->errors()->add('amount', 'Tagihan ini telah lunas dan tidak menerima pembayaran baru.');

                return;
            }

            if ($bill->isCancelled()) {
                $v->errors()->add('amount', 'Tagihan ini telah dibatalkan dan tidak menerima pembayaran.');

                return;
            }

            // 3. Pending Payment Guard: Only one pending payment awaiting verification is allowed
            if ($bill->hasPendingPayment()) {
                $v->errors()->add('amount', 'Tagihan ini memiliki pembayaran yang sedang menunggu verifikasi pemilik kos.');

                return;
            }

            // 4. Overpayment Guard: Amount cannot exceed the outstanding balance
            $amount = (float) $this->input('amount', 0);
            $outstanding = $bill->outstandingAmount();

            if ($amount > $outstanding) {
                $v->errors()->add(
                    'amount',
                    'Nominal pembayaran (Rp '.number_format($amount, 0, ',', '.').') melebihi sisa tagihan (Rp '.number_format($outstanding, 0, ',', '.').').'
                );
            }

            // 5. Strict MIME Type Validation for uploaded proof files
            if ($this->hasFile('proofs')) {
                $allowedMimes = ['image/jpeg', 'image/png', 'application/pdf'];
                foreach ((array) $this->file('proofs') as $index => $file) {
                    if ($file && ! in_array($file->getMimeType(), $allowedMimes, true)) {
                        $v->errors()->add(
                            "proofs.{$index}",
                            'File bukti pembayaran harus berupa dokumen gambar (JPEG, PNG) atau PDF yang valid.'
                        );
                    }
                }
            }
        });
    }
}

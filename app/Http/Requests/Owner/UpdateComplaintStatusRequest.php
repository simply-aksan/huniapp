<?php

declare(strict_types=1);

namespace App\Http\Requests\Owner;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateComplaintStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user || ! $user->isOwner()) {
            return false;
        }

        /** @var Complaint|null $complaint */
        $complaint = $this->route('complaint');

        if (! $complaint instanceof Complaint) {
            return false;
        }

        return (bool) $complaint->property?->isOwnedBy($user);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(ComplaintStatus::values())],
            'handled_by' => ['nullable', 'string', 'max:100'],
            'estimated_completion' => ['nullable', 'date'],
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
            'status.required' => 'Status tiket pengaduan wajib dipilih.',
            'status.in' => 'Status tiket pengaduan tidak valid.',
            'handled_by.max' => 'Nama teknisi/penanggung jawab maksimal 100 karakter.',
            'estimated_completion.date' => 'Estimasi waktu penyelesaian harus berupa format tanggal/waktu yang valid.',
        ];
    }
}

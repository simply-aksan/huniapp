<?php

declare(strict_types=1);

namespace App\Http\Requests\Owner;

use App\Models\Contract;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreContractDocumentRequest extends FormRequest
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
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'title' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Custom Indonesian messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'document.required' => 'File dokumen wajib dipilih.',
            'document.file' => 'File dokumen tidak valid.',
            'document.mimes' => 'Format file dokumen harus berupa PDF, JPG, JPEG, atau PNG.',
            'document.max' => 'Ukuran dokumen maksimal adalah 10MB.',
            'title.max' => 'Judul dokumen maksimal 255 karakter.',
        ];
    }
}

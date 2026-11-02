<?php

declare(strict_types=1);

namespace App\Http\Requests\Owner;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePropertyRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:properties,slug'],
            'address' => ['required', 'string'],
            'city' => ['required', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in(['aktif', 'nonaktif'])],
            'is_primary' => ['nullable', 'boolean'],
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
            'name.required' => 'Nama properti wajib diisi.',
            'slug.unique' => 'Slug properti sudah digunakan oleh properti lain.',
            'address.required' => 'Alamat lengkap wajib diisi.',
            'city.required' => 'Kota/Kabupaten wajib diisi.',
            'status.required' => 'Status operasional properti wajib dipilih.',
            'status.in' => 'Status properti harus berupa aktif atau nonaktif.',
        ];
    }
}

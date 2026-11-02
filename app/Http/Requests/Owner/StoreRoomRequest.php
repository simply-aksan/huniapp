<?php

declare(strict_types=1);

namespace App\Http\Requests\Owner;

use App\Models\Property;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoomRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var Property|null $property */
        $property = $this->route('property');

        return $this->user()?->can('update', $property) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Property $property */
        $property = $this->route('property');

        return [
            'room_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('rooms', 'room_number')->where('property_id', $property?->id),
            ],
            'floor' => ['required', 'integer', 'min:1', 'max:50'],
            'type' => ['required', 'string', 'max:50'],
            'price' => ['required', 'numeric', 'min:0'],
            'size' => ['nullable', 'string', 'max:50'],
            'capacity' => ['required', 'integer', 'min:1', 'max:10'],
            'status' => ['required', 'string', Rule::in(['kosong', 'terisi', 'perbaikan'])],
            'description' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
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
            'room_number.required' => 'Nomor kamar wajib diisi.',
            'room_number.unique' => 'Nomor kamar sudah terdaftar pada properti ini.',
            'floor.required' => 'Lantai kamar wajib diisi.',
            'type.required' => 'Tipe kamar wajib diisi.',
            'price.required' => 'Harga sewa per bulan wajib diisi.',
            'price.numeric' => 'Harga sewa harus berupa angka yang valid.',
            'capacity.required' => 'Kapasitas kamar wajib diisi.',
            'status.required' => 'Status kamar wajib dipilih.',
            'status.in' => 'Status kamar harus berupa kosong, terisi, atau perbaikan.',
        ];
    }
}

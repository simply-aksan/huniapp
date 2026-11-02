<?php

declare(strict_types=1);

namespace App\Http\Requests\Owner;

use App\Enums\InventoryCondition;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInventoryRequest extends FormRequest
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
        $ownerId = $this->user()?->id;

        return [
            'property_id' => [
                'required',
                'integer',
                Rule::exists('properties', 'id')->where('owner_id', $ownerId),
            ],
            'room_id' => [
                'nullable',
                'integer',
                Rule::exists('rooms', 'id')->where('property_id', $this->input('property_id')),
            ],
            'name' => ['required', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'category' => ['required', 'string', 'max:100'],
            'brand' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'condition' => [
                'required',
                'string',
                Rule::in(array_merge(InventoryCondition::values(), ['rusak_ringan', 'rusak_berat'])),
            ],
            'purchase_date' => ['nullable', 'date'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'warranty_until' => ['nullable', 'date'],
            'last_checked_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Custom attribute names for validation.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'property_id' => 'Properti',
            'room_id' => 'Kamar',
            'name' => 'Nama Barang',
            'serial_number' => 'Nomor Seri / Kode',
            'category' => 'Kategori',
            'brand' => 'Merek',
            'model' => 'Model',
            'location' => 'Lokasi',
            'quantity' => 'Jumlah Unit',
            'condition' => 'Kondisi',
            'purchase_date' => 'Tanggal Pembelian',
            'purchase_price' => 'Harga Pembelian',
            'warranty_until' => 'Masa Berlaku Garansi',
            'last_checked_at' => 'Terakhir Dicek',
            'notes' => 'Catatan Tambahan',
        ];
    }
}

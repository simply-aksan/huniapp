<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Enums\ComplaintCategory;
use App\Enums\ComplaintPriority;
use App\Models\Contract;
use App\Models\Room;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreComplaintRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isTenant() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'property_id' => ['required', 'integer', 'exists:properties,id'],
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'title' => ['required', 'string', 'min:5', 'max:255'],
            'description' => ['required', 'string', 'min:10', 'max:3000'],
            'category' => ['required', 'string', Rule::in(ComplaintCategory::values())],
            'priority' => ['required', 'string', Rule::in(ComplaintPriority::values())],
            'photo' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
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
            'property_id.required' => 'Properti kos wajib dipilih.',
            'property_id.exists' => 'Properti kos yang dipilih tidak valid.',
            'room_id.required' => 'Kamar kos wajib dipilih.',
            'room_id.exists' => 'Kamar kos yang dipilih tidak valid.',
            'title.required' => 'Judul pengaduan wajib diisi.',
            'title.min' => 'Judul pengaduan minimal 5 karakter.',
            'description.required' => 'Deskripsi keluhan wajib dijelaskan secara rinci.',
            'description.min' => 'Deskripsi keluhan minimal 10 karakter.',
            'category.required' => 'Kategori pengaduan wajib dipilih.',
            'category.in' => 'Kategori pengaduan tidak valid.',
            'priority.required' => 'Tingkat prioritas wajib dipilih.',
            'priority.in' => 'Tingkat prioritas tidak valid.',
            'photo.file' => 'Foto bukti pengaduan harus berupa berkas yang valid.',
            'photo.image' => 'Berkas pengaduan harus berupa gambar.',
            'photo.mimes' => 'Format foto harus berupa JPEG, JPG, PNG, atau WebP.',
            'photo.max' => 'Ukuran foto maksimal adalah 5 MB.',
        ];
    }

    /**
     * Configure the validator instance with occupancy authorization checks.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $user = $this->user();
            if (! $user) {
                return;
            }

            $propertyId = (int) $this->input('property_id');
            $roomId = (int) $this->input('room_id');

            // 1. Room hierarchy check: room must belong to the selected property
            $room = Room::find($roomId);
            if ($room && $room->property_id !== $propertyId) {
                $v->errors()->add('room_id', 'Kamar yang dipilih tidak berada di dalam properti kos yang bersangkutan.');

                return;
            }

            // 2. Active contract check: tenant must have an active lease for this room and property
            $hasActiveContract = Contract::where('user_id', $user->id)
                ->where('property_id', $propertyId)
                ->where('room_id', $roomId)
                ->where('status', 'aktif')
                ->exists();

            if (! $hasActiveContract) {
                $v->errors()->add('room_id', 'Anda tidak memiliki kontrak sewa aktif untuk kamar atau properti ini.');
            }
        });
    }
}

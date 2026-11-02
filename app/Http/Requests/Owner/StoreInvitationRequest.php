<?php

declare(strict_types=1);

namespace App\Http\Requests\Owner;

use App\Models\Contract;
use App\Models\Invitation;
use App\Models\Property;
use App\Models\Room;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreInvitationRequest extends FormRequest
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
            'property_id' => ['required', 'exists:properties,id'],
            'room_id' => ['required', 'exists:rooms,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'valid_days' => ['nullable', 'integer', 'min:1', 'max:30'],
            'valid_until' => ['nullable', 'date', 'after:now'],
            'message' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Configure the validator instance with cross-entity business rules.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $owner = $this->user();
            $roomId = (int) $this->input('room_id');
            $propertyId = (int) $this->input('property_id');
            $email = strtolower(trim((string) $this->input('email')));

            // 1. Verify property ownership
            $property = Property::find($propertyId);
            if (! $property || $property->owner_id !== $owner->id) {
                $validator->errors()->add('property_id', 'Properti yang dipilih tidak valid atau bukan milik Anda.');
            }

            // 2. Verify that the room belongs to the selected property and that the owner owns that property
            $room = Room::with('property')->find($roomId);
            if (! $room || ! $room->property || $room->property->owner_id !== $owner->id) {
                $validator->errors()->add('room_id', 'Kamar yang dipilih tidak valid atau bukan milik properti Anda.');

                return;
            }

            if ($room->property_id !== $propertyId) {
                $validator->errors()->add('room_id', 'Kamar tidak sesuai dengan properti yang dipilih.');

                return;
            }

            // 2. Verify that room is available and does NOT have an active rental contract
            $hasActiveContract = Contract::where('room_id', $room->id)
                ->where('status', 'aktif')
                ->exists();

            if ($hasActiveContract || $room->status === 'terisi') {
                $validator->errors()->add('room_id', 'Kamar sedang ditempati oleh penyewa aktif dan tidak dapat diundang saat ini.');
            }

            // 3. Prevent duplicate active/pending invitation for the same email and room
            $hasDuplicatePending = Invitation::where('room_id', $room->id)
                ->whereRaw('LOWER(email) = ?', [$email])
                ->whereIn('status', ['aktif', 'pending'])
                ->where('valid_until', '>', now())
                ->exists();

            if ($hasDuplicatePending) {
                $validator->errors()->add('email', 'Sudah ada undangan aktif yang belum kedaluwarsa untuk calon penyewa dan kamar ini.');
            }
        });
    }

    /**
     * Custom Indonesian messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'property_id.required' => 'Properti tujuan wajib dipilih.',
            'room_id.required' => 'Kamar tujuan wajib dipilih.',
            'name.required' => 'Nama calon penyewa wajib diisi.',
            'email.required' => 'Alamat email calon penyewa wajib diisi.',
            'email.email' => 'Format email calon penyewa tidak valid.',
            'valid_until.after' => 'Masa berlaku undangan harus di masa mendatang.',
        ];
    }
}

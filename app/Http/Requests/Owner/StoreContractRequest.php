<?php

declare(strict_types=1);

namespace App\Http\Requests\Owner;

use App\Enums\ContractStatus;
use App\Models\Contract;
use App\Models\Invitation;
use App\Models\Property;
use App\Models\Room;
use App\Models\User;
use App\Services\RoomOccupancyService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreContractRequest extends FormRequest
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
            'property_id' => ['required', 'integer', 'exists:properties,id'],
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'duration_months' => ['nullable', 'integer', 'min:1'],
            'monthly_rent' => ['required', 'numeric', 'min:0'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'deposit_status' => ['nullable', 'string', 'in:belum_dibayar,sudah_dibayar,lunas,pending'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'string', 'in:'.implode(',', [ContractStatus::Draft->value, ContractStatus::Aktif->value])],
            'notes' => ['nullable', 'string', 'max:2000'],
            'document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }

    /**
     * Configure the validator instance with cross-entity business rules.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $owner = $this->user();
            if (! $owner) {
                return;
            }

            $propertyId = (int) $this->input('property_id');
            $roomId = (int) $this->input('room_id');
            $tenantId = (int) $this->input('user_id');
            $startDate = (string) $this->input('start_date');
            $endDate = (string) $this->input('end_date');
            $status = (string) $this->input('status');

            // 1. Verify property ownership
            $property = Property::find($propertyId);
            if (! $property || $property->owner_id !== $owner->id) {
                $validator->errors()->add('property_id', 'Properti yang dipilih tidak valid atau bukan milik Anda.');

                return;
            }

            // 2. Verify room ownership & property matching
            $room = Room::find($roomId);
            if (! $room || $room->property_id !== $property->id) {
                $validator->errors()->add('room_id', 'Kamar yang dipilih tidak valid atau bukan milik properti ini.');

                return;
            }

            if ($room->status === 'perbaikan') {
                $validator->errors()->add('room_id', 'Kamar sedang dalam perbaikan dan tidak dapat disewakan.');

                return;
            }

            // 3. Verify tenant role
            $tenant = User::find($tenantId);
            if (! $tenant || ! $tenant->isTenant()) {
                $validator->errors()->add('user_id', 'Pengguna yang dipilih bukan penyewa yang valid.');

                return;
            }

            // 4. Verify legitimate tenant relationship to this room/property
            // Tenant must have an accepted invitation for this room/property or a prior contract
            $hasAcceptedInvitation = Invitation::where('room_id', $room->id)
                ->where(function ($q) use ($tenantId, $tenant) {
                    $q->where('user_id', $tenantId)
                        ->orWhere('email', $tenant->email);
                })
                ->whereIn('status', ['diterima', 'accepted'])
                ->exists();

            $hasPriorContract = Contract::where('room_id', $room->id)
                ->where('user_id', $tenantId)
                ->exists();

            if (! $hasAcceptedInvitation && ! $hasPriorContract) {
                $validator->errors()->add('user_id', 'Penyewa harus terhubung melalui undangan yang telah diterima untuk kamar ini.');

                return;
            }

            // 5. If status is aktif, check for overlapping active contracts
            if ($status === ContractStatus::Aktif->value && $startDate && $endDate) {
                $occupancyService = app(RoomOccupancyService::class);
                $overlapping = $occupancyService->getOverlappingContract($room, $startDate, $endDate);
                if ($overlapping) {
                    $validator->errors()->add('room_id', 'Kamar sudah memiliki kontrak aktif ('.$overlapping->contract_number.') yang bertabrakan pada periode sewa ini.');
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
            'property_id.required' => 'Properti wajib dipilih.',
            'room_id.required' => 'Kamar wajib dipilih.',
            'user_id.required' => 'Penyewa wajib dipilih.',
            'start_date.required' => 'Tanggal mulai sewa wajib diisi.',
            'start_date.date' => 'Format tanggal mulai sewa tidak valid.',
            'end_date.required' => 'Tanggal berakhir sewa wajib diisi.',
            'end_date.date' => 'Format tanggal berakhir sewa tidak valid.',
            'end_date.after' => 'Tanggal berakhir sewa harus setelah tanggal mulai.',
            'monthly_rent.required' => 'Harga sewa per bulan wajib diisi.',
            'monthly_rent.numeric' => 'Harga sewa harus berupa angka valid.',
            'monthly_rent.min' => 'Harga sewa tidak boleh bernilai negatif.',
            'deposit_amount.numeric' => 'Nominal deposit harus berupa angka valid.',
            'deposit_amount.min' => 'Nominal deposit tidak boleh bernilai negatif.',
            'status.required' => 'Status kontrak wajib ditentukan.',
            'status.in' => 'Status kontrak harus berupa draft atau aktif.',
            'document.mimes' => 'Format dokumen harus berupa PDF, JPG, JPEG, atau PNG.',
            'document.max' => 'Ukuran dokumen maksimal adalah 10MB.',
        ];
    }
}

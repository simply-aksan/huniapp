<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Complaint;
use App\Services\ComplaintService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreComplaintReplyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        /** @var Complaint|null $complaint */
        $complaint = $this->route('complaint');

        if (! $complaint instanceof Complaint) {
            return false;
        }

        $service = app(ComplaintService::class);

        return $complaint->canBeReplied() && $service->canUserParticipateInTicket($user, $complaint);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'min:2', 'max:3000'],
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
            'message.required' => 'Pesan balasan wajib diisi.',
            'message.min' => 'Pesan balasan minimal 2 karakter.',
            'message.max' => 'Pesan balasan maksimal 3000 karakter.',
        ];
    }
}

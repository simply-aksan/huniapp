<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Enums\ComplaintCategory;
use App\Enums\ComplaintPriority;
use App\Enums\ComplaintStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\UpdateComplaintStatusRequest;
use App\Http\Requests\StoreComplaintReplyRequest;
use App\Models\Complaint;
use App\Services\ComplaintService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ComplaintController extends Controller
{
    public function __construct(
        protected ComplaintService $complaintService
    ) {}

    /**
     * Display a listing of complaints scoped to properties owned by the authenticated owner.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        // 1. Owner's properties for dropdown filtering
        $properties = $user->properties()->orderBy('name')->get();

        // 2. KPI metrics strictly isolated to this owner's properties
        $stats = [
            'total' => Complaint::forOwner($user)->count(),
            'baru_count' => Complaint::forOwner($user)->where('status', ComplaintStatus::Baru->value)->count(),
            'in_progress_count' => Complaint::forOwner($user)->where('status', ComplaintStatus::Diproses->value)->count(),
            'pending_response_count' => Complaint::forOwner($user)->where('status', ComplaintStatus::MenungguTanggapan->value)->count(),
            'resolved_count' => Complaint::forOwner($user)->where('status', ComplaintStatus::Selesai->value)->count(),
            'closed_count' => Complaint::forOwner($user)->where('status', ComplaintStatus::Ditutup->value)->count(),
        ];

        // 3. Server-side Query Builder with eager loading
        $query = Complaint::query()
            ->forOwner($user)
            ->with(['property', 'room', 'user']);

        // Search by ticket number, title, or tenant name
        $searchQuery = trim((string) $request->input('q', ''));
        if ($searchQuery !== '') {
            $query->where(function ($q) use ($searchQuery) {
                $q->where('ticket_number', 'like', "%{$searchQuery}%")
                    ->orWhere('title', 'like', "%{$searchQuery}%")
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$searchQuery}%"));
            });
        }

        // Filter by property
        $selectedPropertyId = null;
        if ($propInput = $request->input('property_id')) {
            if ($properties->contains('id', (int) $propInput)) {
                $selectedPropertyId = (int) $propInput;
                $query->where('property_id', $selectedPropertyId);
            }
        }

        // Filter by status
        $selectedStatus = null;
        if ($statusInput = $request->input('status')) {
            foreach (ComplaintStatus::cases() as $st) {
                if (strcasecmp($st->value, (string) $statusInput) === 0 || strcasecmp($st->name, (string) $statusInput) === 0) {
                    $selectedStatus = $st->value;
                    $query->where('status', $selectedStatus);
                    break;
                }
            }
        }

        // Filter by category
        $selectedCategory = null;
        if ($catInput = $request->input('category')) {
            foreach (ComplaintCategory::cases() as $cat) {
                if (strcasecmp($cat->value, (string) $catInput) === 0 || strcasecmp($cat->name, (string) $catInput) === 0) {
                    $selectedCategory = $cat->value;
                    $query->where('category', $selectedCategory);
                    break;
                }
            }
        }

        // Filter by priority
        $selectedPriority = null;
        if ($prioInput = $request->input('priority')) {
            foreach (ComplaintPriority::cases() as $prio) {
                if (strcasecmp($prio->value, (string) $prioInput) === 0 || strcasecmp($prio->name, (string) $prioInput) === 0) {
                    $selectedPriority = $prio->value;
                    $query->where('priority', $selectedPriority);
                    break;
                }
            }
        }

        $complaints = $query->latest('id')->paginate(10)->withQueryString();

        return view('owner.complaints.index', [
            'complaints' => $complaints,
            'stats' => $stats,
            'properties' => $properties,
            'searchQuery' => $searchQuery,
            'selectedPropertyId' => $selectedPropertyId,
            'selectedStatus' => $selectedStatus,
            'selectedCategory' => $selectedCategory,
            'selectedPriority' => $selectedPriority,
            'statuses' => ComplaintStatus::cases(),
            'categories' => ComplaintCategory::cases(),
            'priorities' => ComplaintPriority::cases(),
        ]);
    }

    /**
     * Display the specified complaint ticket details and chronological conversation.
     */
    public function show(Request $request, Complaint $complaint): View
    {
        Gate::authorize('view', $complaint);

        $complaint->load([
            'property',
            'room',
            'user',
            'replies' => fn ($query) => $query->with('user')->orderBy('created_at', 'asc')->orderBy('id', 'asc'),
        ]);

        return view('owner.complaints.show', [
            'complaint' => $complaint,
            'statuses' => ComplaintStatus::cases(),
        ]);
    }

    /**
     * Store a reply message to the complaint from the owner.
     */
    public function storeReply(StoreComplaintReplyRequest $request, Complaint $complaint): RedirectResponse
    {
        Gate::authorize('reply', $complaint);

        if ($complaint->isClosed()) {
            return redirect()->route('owner.complaints.show', $complaint)
                ->with('error', 'Tiket pengaduan ini telah ditutup permanen dan tidak dapat menerima balasan baru.');
        }

        $this->complaintService->addReply(
            $complaint,
            $request->user(),
            (string) $request->validated('message')
        );

        return redirect()->route('owner.complaints.show', $complaint)
            ->with('success', 'Tanggapan Anda berhasil dikirim.');
    }

    /**
     * Update complaint lifecycle status, technician, and estimated completion.
     */
    public function updateStatus(UpdateComplaintStatusRequest $request, Complaint $complaint): RedirectResponse
    {
        Gate::authorize('updateStatus', $complaint);

        $status = $request->validated('status');
        $handledBy = $request->validated('handled_by');
        $estimatedCompletion = $request->filled('estimated_completion')
            ? Carbon::parse((string) $request->validated('estimated_completion'))
            : null;

        $this->complaintService->updateStatus(
            $complaint,
            $status,
            $request->user(),
            $handledBy,
            $estimatedCompletion
        );

        return redirect()->route('owner.complaints.show', $complaint)
            ->with('success', "Status tiket pengaduan #{$complaint->ticket_number} berhasil diperbarui.");
    }
}

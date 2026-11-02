<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Enums\ComplaintCategory;
use App\Enums\ComplaintPriority;
use App\Enums\ComplaintStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreComplaintReplyRequest;
use App\Http\Requests\Tenant\StoreComplaintRequest;
use App\Models\Complaint;
use App\Models\Contract;
use App\Services\ComplaintService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ComplaintController extends Controller
{
    public function __construct(
        protected ComplaintService $complaintService
    ) {}

    /**
     * Display a paginated listing of complaints filed by the authenticated tenant.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        // 1. KPI Statistics strictly scoped to this tenant
        $stats = [
            'total' => Complaint::where('user_id', $user->id)->count(),
            'active_count' => Complaint::where('user_id', $user->id)
                ->whereIn('status', [
                    ComplaintStatus::Baru->value,
                    ComplaintStatus::Diproses->value,
                    ComplaintStatus::MenungguTanggapan->value,
                ])->count(),
            'resolved_count' => Complaint::where('user_id', $user->id)
                ->where('status', ComplaintStatus::Selesai->value)
                ->count(),
            'closed_count' => Complaint::where('user_id', $user->id)
                ->where('status', ComplaintStatus::Ditutup->value)
                ->count(),
        ];

        // 2. Server-side Query Builder
        $query = Complaint::query()
            ->where('user_id', $user->id)
            ->with(['property', 'room']);

        $searchQuery = trim((string) $request->input('q', ''));
        if ($searchQuery !== '') {
            $query->where(function ($q) use ($searchQuery) {
                $q->where('ticket_number', 'like', "%{$searchQuery}%")
                    ->orWhere('title', 'like', "%{$searchQuery}%");
            });
        }

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

        return view('tenant.complaints.index', [
            'complaints' => $complaints,
            'stats' => $stats,
            'searchQuery' => $searchQuery,
            'selectedStatus' => $selectedStatus,
            'selectedCategory' => $selectedCategory,
            'selectedPriority' => $selectedPriority,
            'statuses' => ComplaintStatus::cases(),
            'categories' => ComplaintCategory::cases(),
            'priorities' => ComplaintPriority::cases(),
        ]);
    }

    /**
     * Show the form for creating a new complaint ticket.
     */
    public function create(Request $request): View
    {
        $user = $request->user();

        // Retrieve active contracts for this tenant
        $activeContracts = Contract::where('user_id', $user->id)
            ->where('status', 'aktif')
            ->with(['property', 'room'])
            ->get();

        return view('tenant.complaints.create', [
            'activeContracts' => $activeContracts,
            'categories' => ComplaintCategory::cases(),
            'priorities' => ComplaintPriority::cases(),
        ]);
    }

    /**
     * Store a newly created complaint ticket in storage.
     */
    public function store(StoreComplaintRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $photoPath = null;

        try {
            // Upload photo to private storage if present
            if ($request->hasFile('photo')) {
                $photoFile = $request->file('photo');
                if ($photoFile && $photoFile->isValid()) {
                    $photoPath = Storage::disk('local')->putFile('complaints', $photoFile);
                }
            }

            $complaint = $this->complaintService->createComplaint([
                'property_id' => (int) $validated['property_id'],
                'room_id' => (int) $validated['room_id'],
                'title' => (string) $validated['title'],
                'description' => (string) $validated['description'],
                'category' => $validated['category'],
                'priority' => $validated['priority'],
                'photo_url' => $photoPath,
            ], $user);

            return redirect()->route('tenant.complaints.show', $complaint)
                ->with('success', "Pengaduan dengan nomor tiket {$complaint->ticket_number} berhasil dibuat.");
        } catch (\Throwable $e) {
            // Clean up newly uploaded file if persistence failed
            if ($photoPath && Storage::disk('local')->exists($photoPath)) {
                try {
                    Storage::disk('local')->delete($photoPath);
                } catch (\Throwable $cleanupException) {
                    Log::error("Gagal menghapus file foto komplain sementara '{$photoPath}': ".$cleanupException->getMessage());
                }
            }

            throw $e;
        }
    }

    /**
     * Display the specified complaint ticket and its conversation history.
     */
    public function show(Request $request, Complaint $complaint): View
    {
        Gate::authorize('view', $complaint);

        $complaint->load([
            'property',
            'room',
            'replies' => fn ($query) => $query->with('user')->orderBy('created_at', 'asc'),
        ]);

        return view('tenant.complaints.show', [
            'complaint' => $complaint,
        ]);
    }

    /**
     * Store a new conversation reply for the specified complaint ticket.
     */
    public function storeReply(StoreComplaintReplyRequest $request, Complaint $complaint): RedirectResponse
    {
        Gate::authorize('reply', $complaint);

        if ($complaint->isClosed()) {
            return redirect()->route('tenant.complaints.show', $complaint)
                ->with('error', 'Tiket pengaduan ini telah ditutup permanen dan tidak dapat menerima balasan baru.');
        }

        $this->complaintService->addReply(
            $complaint,
            $request->user(),
            (string) $request->validated('message')
        );

        return redirect()->route('tenant.complaints.show', $complaint)
            ->with('success', 'Tanggapan Anda berhasil dikirim.');
    }

    /**
     * Close a resolved complaint ticket (tenant confirms completion).
     */
    public function close(Request $request, Complaint $complaint): RedirectResponse
    {
        Gate::authorize('view', $complaint);

        if (! $complaint->isResolved()) {
            return redirect()->route('tenant.complaints.show', $complaint)
                ->with('error', 'Hanya tiket yang berstatus selesai yang dapat ditutup oleh penyewa.');
        }

        $this->complaintService->updateStatus(
            $complaint,
            ComplaintStatus::Ditutup,
            $request->user()
        );

        return redirect()->route('tenant.complaints.show', $complaint)
            ->with('success', 'Terima kasih, konfirmasi perbaikan telah tercatat dan tiket pengaduan berhasil ditutup.');
    }

    /**
     * Download or preview the complaint photo attachment with strict authorization.
     */
    public function photo(Request $request, Complaint $complaint): StreamedResponse
    {
        Gate::authorize('downloadPhoto', $complaint);

        if (! $complaint->photo_url) {
            abort(404, 'Tiket ini tidak memiliki lampiran foto.');
        }

        $disk = Storage::disk('local');
        if (! $disk->exists($complaint->photo_url)) {
            abort(404, 'Berkas foto tidak ditemukan di penyimpanan server.');
        }

        if ($request->boolean('download')) {
            $extension = pathinfo($complaint->photo_url, PATHINFO_EXTENSION);
            $filename = "foto-{$complaint->ticket_number}.".($extension ?: 'jpg');

            return $disk->download($complaint->photo_url, $filename);
        }

        return $disk->response($complaint->photo_url);
    }
}

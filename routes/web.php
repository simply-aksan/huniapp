<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\InvitationAcceptanceController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Owner\AnalyticsController;
use App\Http\Controllers\Owner\BillController;
use App\Http\Controllers\Owner\ComplaintController as OwnerComplaintController;
use App\Http\Controllers\Owner\ContractController;
use App\Http\Controllers\Owner\DashboardController as OwnerDashboardController;
use App\Http\Controllers\Owner\FacilityController;
use App\Http\Controllers\Owner\InventoryController;
use App\Http\Controllers\Owner\InvitationController;
use App\Http\Controllers\Owner\PaymentController;
use App\Http\Controllers\Owner\PropertyController;
use App\Http\Controllers\Owner\PropertyKnowledgeController;
use App\Http\Controllers\Owner\ReportExportController;
use App\Http\Controllers\Owner\RoomController;
use App\Http\Controllers\Owner\TenantController;
use App\Http\Controllers\Tenant\ChatController;
use App\Http\Controllers\Tenant\ComplaintController;
use App\Http\Controllers\Tenant\DashboardController as TenantDashboardController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — HUNI App
|--------------------------------------------------------------------------
*/

// Application Entry Point: Redirect to Login for guests, or role Dashboard for authenticated users
Route::get('/', function () {
    if (Auth::check()) {
        /** @var User $user */
        $user = Auth::user();

        return redirect()->route($user->isOwner() ? 'owner.dashboard' : 'tenant.dashboard');
    }

    return redirect()->route('login');
})->name('home');

// Legacy foundation route redirects to home
Route::get('/foundation', function () {
    return redirect()->route('home');
})->name('foundation');

// Public Tenant Invitation Acceptance Routes (Rate-limited for token abuse prevention)
Route::prefix('invitations')->name('invitations.')->middleware('throttle:60,1')->group(function () {
    Route::get('/{token}', [InvitationAcceptanceController::class, 'show'])->name('show');
    Route::post('/{token}/accept', [InvitationAcceptanceController::class, 'accept'])->name('accept');
    Route::post('/{token}/register', [InvitationAcceptanceController::class, 'registerAndAccept'])->name('register');
});

// Authentication Routes (Guest only)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

// Logout Route (Authenticated)
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

// Owner Portal Routes (Authenticated + Owner Role only)
Route::middleware(['auth', 'role:owner'])->prefix('owner')->name('owner.')->group(function () {
    Route::get('/', [OwnerDashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [OwnerDashboardController::class, 'index']);

    // Property & Room Management
    Route::resource('properties', PropertyController::class);
    Route::resource('properties.rooms', RoomController::class);

    // Property Knowledge Base (Rules & FAQs) Management
    Route::get('/properties/{property}/knowledge', [PropertyKnowledgeController::class, 'index'])
        ->name('properties.knowledge.index');
    Route::post('/properties/{property}/knowledge/rules', [PropertyKnowledgeController::class, 'storeRule'])
        ->name('properties.knowledge.rules.store');
    Route::put('/properties/{property}/knowledge/rules/{rule}', [PropertyKnowledgeController::class, 'updateRule'])
        ->name('properties.knowledge.rules.update');
    Route::patch('/properties/{property}/knowledge/rules/{rule}/toggle', [PropertyKnowledgeController::class, 'toggleRule'])
        ->name('properties.knowledge.rules.toggle');
    Route::delete('/properties/{property}/knowledge/rules/{rule}', [PropertyKnowledgeController::class, 'destroyRule'])
        ->name('properties.knowledge.rules.destroy');

    Route::post('/properties/{property}/knowledge/faqs', [PropertyKnowledgeController::class, 'storeFaq'])
        ->name('properties.knowledge.faqs.store');
    Route::put('/properties/{property}/knowledge/faqs/{faq}', [PropertyKnowledgeController::class, 'updateFaq'])
        ->name('properties.knowledge.faqs.update');
    Route::patch('/properties/{property}/knowledge/faqs/{faq}/toggle', [PropertyKnowledgeController::class, 'toggleFaq'])
        ->name('properties.knowledge.faqs.toggle');
    Route::delete('/properties/{property}/knowledge/faqs/{faq}', [PropertyKnowledgeController::class, 'destroyFaq'])
        ->name('properties.knowledge.faqs.destroy');

    Route::post('/properties/{property}/knowledge/sync', [PropertyKnowledgeController::class, 'sync'])
        ->name('properties.knowledge.sync');

    // Tenant Management
    Route::get('/tenants', [TenantController::class, 'index'])->name('tenants.index');
    Route::get('/tenants/{tenant}', [TenantController::class, 'show'])->name('tenants.show');

    // Tenant Invitations Management
    Route::resource('invitations', InvitationController::class)->except(['edit', 'update']);
    Route::post('/invitations/{invitation}/revoke', [InvitationController::class, 'revoke'])->name('invitations.revoke');

    // Contract Management
    Route::resource('contracts', ContractController::class);
    Route::post('/contracts/{contract}/terminate', [ContractController::class, 'terminate'])
        ->name('contracts.terminate');
    Route::post('/contracts/{contract}/cancel', [ContractController::class, 'cancel'])
        ->name('contracts.cancel');
    Route::post('/contracts/{contract}/renew', [ContractController::class, 'renew'])
        ->name('contracts.renew');
    Route::post('/contracts/{contract}/documents', [ContractController::class, 'uploadDocument'])
        ->name('contracts.documents.upload');
    Route::get('/contracts/{contract}/documents/{document}/download', [ContractController::class, 'downloadDocument'])
        ->name('contracts.documents.download');
    Route::delete('/contracts/{contract}/documents/{document}', [ContractController::class, 'destroyDocument'])
        ->name('contracts.documents.destroy');

    // Invoice Management
    Route::resource('bills', BillController::class)->except(['edit', 'update', 'destroy']);
    Route::post('/bills/{bill}/cancel', [BillController::class, 'cancel'])
        ->name('bills.cancel');

    // Payment Verification Management
    Route::get('/payments', [PaymentController::class, 'index'])
        ->name('payments.index');
    Route::get('/payments/{payment}', [PaymentController::class, 'show'])
        ->name('payments.show');
    Route::post('/payments/{payment}/approve', [PaymentController::class, 'approve'])
        ->name('payments.approve');
    Route::post('/payments/{payment}/reject', [PaymentController::class, 'reject'])
        ->name('payments.reject');
    Route::get('/payments/{payment}/proofs/{proof}/download', [App\Http\Controllers\Tenant\PaymentController::class, 'downloadProof'])
        ->name('payments.proofs.download');

    // Complaint & Ticketing Management
    Route::get('/complaints', [OwnerComplaintController::class, 'index'])
        ->name('complaints.index');
    Route::get('/complaints/{complaint}', [OwnerComplaintController::class, 'show'])
        ->name('complaints.show');
    Route::post('/complaints/{complaint}/replies', [OwnerComplaintController::class, 'storeReply'])
        ->name('complaints.replies.store');
    Route::match(['patch', 'post'], '/complaints/{complaint}/status', [OwnerComplaintController::class, 'updateStatus'])
        ->name('complaints.status.update');

    // Facility & Inventory Management
    Route::resource('inventory', InventoryController::class);
    Route::resource('facilities', FacilityController::class)->except(['show']);
    Route::post('/properties/{property}/facilities', [FacilityController::class, 'assignProperty'])
        ->name('properties.facilities.assign');
    Route::delete('/properties/{property}/facilities/{facility}', [FacilityController::class, 'detachProperty'])
        ->name('properties.facilities.detach');
    Route::post('/properties/{property}/rooms/{room}/facilities', [FacilityController::class, 'assignRoom'])
        ->name('properties.rooms.facilities.assign');
    Route::delete('/properties/{property}/rooms/{room}/facilities/{facility}', [FacilityController::class, 'detachRoom'])
        ->name('properties.rooms.facilities.detach');

    // Dashboard Analytics & Interactive Charts
    Route::get('/analytics', [AnalyticsController::class, 'index'])
        ->name('analytics.index');

    // Financial Reports, Print-Ready View & CSV Stream Export
    Route::get('/reports/print', [ReportExportController::class, 'print'])
        ->name('reports.print');
    Route::get('/reports/export', [ReportExportController::class, 'export'])
        ->name('reports.export');
});

// Tenant Portal Routes (Authenticated + Tenant Role only)
Route::middleware(['auth', 'role:tenant'])->prefix('tenant')->name('tenant.')->group(function () {
    Route::get('/', [TenantDashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [TenantDashboardController::class, 'index']);

    // Tenant Invoice Portal
    Route::get('/bills', [App\Http\Controllers\Tenant\BillController::class, 'index'])->name('bills.index');
    Route::get('/bills/{bill}', [App\Http\Controllers\Tenant\BillController::class, 'show'])->name('bills.show');

    // Payment Submission
    Route::post('/bills/{bill}/payments', [App\Http\Controllers\Tenant\PaymentController::class, 'store'])
        ->name('bills.payments.store');
    Route::get('/payments/{payment}/proofs/{proof}/download', [App\Http\Controllers\Tenant\PaymentController::class, 'downloadProof'])
        ->name('payments.proofs.download');

    // Complaint & Ticketing Portal
    Route::get('/complaints', [ComplaintController::class, 'index'])->name('complaints.index');
    Route::get('/complaints/create', [ComplaintController::class, 'create'])->name('complaints.create');
    Route::post('/complaints', [ComplaintController::class, 'store'])->name('complaints.store');
    Route::get('/complaints/{complaint}', [ComplaintController::class, 'show'])->name('complaints.show');
    Route::post('/complaints/{complaint}/replies', [ComplaintController::class, 'storeReply'])->name('complaints.replies.store');
    Route::post('/complaints/{complaint}/close', [ComplaintController::class, 'close'])->name('complaints.close');
    Route::get('/complaints/{complaint}/photo', [ComplaintController::class, 'photo'])->name('complaints.photo');

    // Tenant AI Assistant (Chatbot & Knowledge Base RAG)
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::post('/chat/ask', [ChatController::class, 'ask'])->name('chat.ask');
    Route::post('/chat/clear', [ChatController::class, 'clearHistory'])->name('chat.clear');
});

// Shared Authenticated Payment Proof Download Route
Route::middleware('auth')->get('/payments/{payment}/proofs/{proof}/download', [App\Http\Controllers\Tenant\PaymentController::class, 'downloadProof'])
    ->name('payments.proofs.download');

// Shared Authenticated Complaint Photo Route
Route::middleware('auth')->get('/complaints/{complaint}/photo', [ComplaintController::class, 'photo'])
    ->name('complaints.photo');

// Notification Center Routes (Authenticated for both Owner and Tenant)
Route::middleware('auth')->prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('readAll');
    Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('read');
});

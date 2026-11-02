<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Pagination\LengthAwarePaginator;

class NotificationController extends Controller
{
    /**
     * Display the authenticated user's notification center.
     */
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $filter = (string) $request->query('filter', 'all');

        try {
            $query = $user->notifications();

            if ($filter === 'unread') {
                $query = $user->unreadNotifications();
            }

            $notifications = $query->latest()->paginate(15)->withQueryString();
            $unreadCount = $user->unreadNotifications()->count();
            $totalCount = $user->notifications()->count();
        } catch (\Throwable $e) {
            $notifications = new LengthAwarePaginator([], 0, 15);
            $unreadCount = 0;
            $totalCount = 0;
        }

        return view('notifications.index', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'totalCount' => $totalCount,
            'currentFilter' => $filter,
        ]);
    }

    /**
     * Mark a single notification as read and safely redirect to its destination.
     */
    public function markAsRead(Request $request, string $id): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Enforce strict multi-tenant authorization: User can ONLY mark their own notification as read
        /** @var DatabaseNotification $notification */
        $notification = $user->notifications()->where('id', $id)->firstOrFail();

        $notification->markAsRead();

        // Extract and strictly sanitize the target destination to prevent open redirect vulnerabilities
        $data = (array) $notification->data;
        $targetUrl = isset($data['url']) && is_string($data['url']) ? trim($data['url']) : null;

        $safeUrl = $this->sanitizeInternalRedirect($targetUrl, $request);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'unread_count' => $user->unreadNotifications()->count(),
                'redirect_url' => $safeUrl,
            ]);
        }

        if ($safeUrl) {
            return redirect()->to($safeUrl);
        }

        return redirect()->route('notifications.index')
            ->with('success', 'Notifikasi berhasil ditandai sebagai dibaca.');
    }

    /**
     * Mark all unread notifications for the authenticated user as read.
     */
    public function markAllAsRead(Request $request): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->unreadNotifications->markAsRead();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'unread_count' => 0,
            ]);
        }

        return redirect()->back()
            ->with('success', 'Semua notifikasi telah ditandai sebagai dibaca.');
    }

    /**
     * Sanitize URL to ensure it is strictly an internal app route.
     */
    protected function sanitizeInternalRedirect(?string $url, Request $request): ?string
    {
        if (! $url) {
            return null;
        }

        // Relative path starting with single slash (reject protocol-relative "//attacker.com")
        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return $url;
        }

        // Full URL with exact matching host and scheme
        $parsed = parse_url($url);
        if (isset($parsed['host'])) {
            $appHost = parse_url(config('app.url', ''), PHP_URL_HOST) ?: $request->getHost();

            if (strcasecmp((string) $parsed['host'], (string) $appHost) === 0 || strcasecmp((string) $parsed['host'], $request->getHost()) === 0) {
                return $url;
            }
        }

        return null;
    }
}

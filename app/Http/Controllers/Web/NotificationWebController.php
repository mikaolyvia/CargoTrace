<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationWebController extends Controller
{
    /**
     * Display all notifications for authenticated user.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $filter = $request->query('filter', 'all');

        $query = Notification::where('user_id', $user?->id)->orderByDesc('created_at');

        if ($filter === 'unread') {
            $query->unread();
        }

        $notifications = $query->paginate(15)->withQueryString();
        $unreadCount = Notification::where('user_id', $user?->id)->unread()->count();

        return view('notifications.index', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'currentFilter' => $filter,
        ]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(Request $request, Notification $notification): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        if ($notification->user_id !== $user?->id) {
            abort(403, 'Anda tidak memiliki hak akses terhadap notifikasi ini.');
        }

        $notification->markAsRead();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Notifikasi ditandai telah dibaca.']);
        }

        return back()->with('success', 'Notifikasi berhasil ditandai sebagai telah dibaca.');
    }

    /**
     * Mark all unread notifications of the authenticated user as read.
     */
    public function markAllAsRead(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        if ($user) {
            Notification::where('user_id', $user->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Semua notifikasi berhasil ditandai telah dibaca.']);
        }

        return back()->with('success', 'Semua notifikasi berhasil ditandai sebagai telah dibaca.');
    }
}

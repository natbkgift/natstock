<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Gate;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('access-staff');

        /** @var LengthAwarePaginator $notifications */
        $notifications = $request->user()
            ->notifications()
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('admin.notifications.index', [
            'notifications' => $notifications,
        ]);
    }

    public function markAsRead(Request $request, string $id): RedirectResponse
    {
        Gate::authorize('access-staff');

        /** @var DatabaseNotification $notification */
        $notification = $request->user()
            ->notifications()
            ->whereKey($id)
            ->firstOrFail();

        $notification->markAsRead();

        return redirect()->route('admin.notifications.index')->with('status', 'ทำเครื่องหมายว่าอ่านแล้วเรียบร้อย');
    }

    public function markAll(Request $request): RedirectResponse
    {
        Gate::authorize('access-staff');

        $request->user()->unreadNotifications->markAsRead();

        return redirect()->route('admin.notifications.index')->with('status', 'ทำเครื่องหมายแจ้งเตือนทั้งหมดว่าอ่านแล้ว');
    }
}

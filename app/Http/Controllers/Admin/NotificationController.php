<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $this->admin()->notifications()
            ->when($request->query('filter') === 'unread', fn ($q) => $q->whereNull('read_at'))
            ->paginate(25)
            ->withQueryString();

        return view('admin.notifications.index', compact('notifications'));
    }

    public function feed(): JsonResponse
    {
        $admin = $this->admin();

        return response()->json([
            'unread' => $admin->unreadNotifications()->count(),
            'items'  => $admin->notifications()->take(10)->get()->map(fn (DatabaseNotification $n) => [
                'id'       => $n->id,
                'title'    => $n->data['title'] ?? 'Notification',
                'body'     => $n->data['body'] ?? '',
                'icon'     => $n->data['icon'] ?? 'bi-bell',
                'color'    => $n->data['color'] ?? 'slate',
                'time'     => $n->created_at?->diffForHumans(),
                'read'     => $n->read_at !== null,
                'open_url' => route('admin.notifications.open', $n->id),
            ]),
        ]);
    }

    /**
     * Marks the notification read and follows its link.
     */
    public function open(string $id)
    {
        $notification = $this->admin()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $url = $notification->data['url'] ?? null;

        // Only same-site paths are followed.
        return is_string($url) && str_starts_with($url, '/') && !str_starts_with($url, '//')
            ? redirect($url)
            : redirect()->route('admin.notifications.index');
    }

    public function readAll(Request $request)
    {
        $this->admin()->unreadNotifications()->update(['read_at' => now()]);

        return $request->expectsJson()
            ? response()->json(['unread' => 0])
            : back()->with('success', 'All notifications marked as read.');
    }

    public function destroy(string $id)
    {
        $this->admin()->notifications()->whereKey($id)->delete();

        return back()->with('success', 'Notification removed.');
    }

    private function admin()
    {
        return Auth::guard('admin')->user();
    }
}

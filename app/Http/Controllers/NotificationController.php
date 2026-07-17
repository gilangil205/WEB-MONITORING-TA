<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Constructor - Apply auth middleware
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Ambil daftar notifikasi user yang sedang login
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = Notification::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc');

        // Filter: unread only
        if ($request->get('unread_only') === 'true') {
            $query->where('is_read', false);
        }

        $notifications = $query->limit(20)->get();

        return response()->json([
            'success' => true,
            'data' => $notifications->map(function ($notif) {
                return [
                    'id' => $notif->id,
                    'title' => $notif->title,
                    'message' => $notif->message,
                    'status' => $notif->status,
                    'fuzzy_value' => $notif->fuzzy_value,
                    'created_at' => $notif->created_at->diffForHumans(),
                    'created_at_raw' => $notif->created_at->toISOString(),
                    'is_read' => $notif->is_read,
                    'read_at' => $notif->read_at?->diffForHumans(),
                ];
            }),
            'unread_count' => Notification::where('user_id', Auth::id())
                ->where('is_read', false)
                ->count(),
        ]);
    }

    /**
     * Tandai satu notifikasi sebagai sudah dibaca
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRead(int $id)
    {
        $notification = Notification::where('user_id', Auth::id())
            ->findOrFail($id);

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi ditandai sudah dibaca',
        ]);
    }

    /**
     * Tandai semua notifikasi sebagai sudah dibaca
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAllAsRead()
    {
        Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Semua notifikasi sudah dibaca',
        ]);
    }

    /**
     * Hapus notifikasi
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id)
    {
        $notification = Notification::where('user_id', Auth::id())
            ->findOrFail($id);

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi dihapus',
        ]);
    }
}
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminNotificationViewData;

class AdminNotificationController extends Controller
{
    public function index(AdminNotificationViewData $notifications)
    {
        return view('pages.admin.notifications', [
            'notifications' => $notifications->items(
                session('bbh_api_token'),
                $this->readNotificationIds()
            ),
        ]);
    }

    public function markAllRead(AdminNotificationViewData $notifications)
    {
        $ids = array_column($notifications->items(session('bbh_api_token')), 'id');

        session([
            'bbh_read_notifications' => array_values(array_unique([
                ...$this->readNotificationIds(),
                ...array_filter($ids, 'is_string'),
            ])),
        ]);

        return back()->with('formMessage', 'Sukses: Semua notifikasi telah ditandai sebagai dibaca.');
    }

    /**
     * @return array<int, string>
     */
    private function readNotificationIds(): array
    {
        $ids = session('bbh_read_notifications', []);

        return is_array($ids) ? array_values(array_filter($ids, 'is_string')) : [];
    }
}

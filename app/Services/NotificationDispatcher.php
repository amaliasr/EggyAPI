<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Helper ringan untuk menulis notifikasi in-app (tabel `notifications`).
 * Pengiriman push FCM sungguhan (kreait/laravel-firebase) sengaja BELUM
 * dipasang — ditunda ke fase notifikasi karena butuh ext-sodium di server
 * (lihat README backend, Problem terkait Firebase). Baris di tabel ini tetap
 * dibuat sekarang supaya endpoint /notifications sudah bisa dipakai; kalau
 * FCM sudah aktif nanti, tinggal tambahkan pengiriman push di method send().
 */
class NotificationDispatcher
{
    public function send(User $user, string $type, string $title, string $body, array $data = []): AppNotification
    {
        return AppNotification::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data ?: null,
        ]);
    }

    /** Kirim ke semua karyawan aktif — dipakai untuk pengumuman & perubahan harga. */
    public function sendToActiveEmployees(string $type, string $title, string $body, array $data = []): void
    {
        User::where('role', 'karyawan')->where('is_active', true)->get()
            ->each(fn (User $user) => $this->send($user, $type, $title, $body, $data));
    }

    /** Kirim ke semua pengguna aktif (atasan + karyawan). */
    public function sendToAllActive(string $type, string $title, string $body, array $data = []): void
    {
        User::where('is_active', true)->get()
            ->each(fn (User $user) => $this->send($user, $type, $title, $body, $data));
    }

    public function listFor(User $user, array $filters, int $perPage = 20)
    {
        $query = AppNotification::where('user_id', $user->id);

        if (array_key_exists('is_read', $filters)) {
            $query->where('is_read', (bool) $filters['is_read']);
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function markRead(User $user, int $id): AppNotification
    {
        $notification = AppNotification::where('user_id', $user->id)->findOrFail($id);
        $notification->markAsRead();

        return $notification;
    }

    public function markAllRead(User $user): int
    {
        return AppNotification::where('user_id', $user->id)->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);
    }
}

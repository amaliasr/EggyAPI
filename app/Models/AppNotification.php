<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Notifikasi in-app milik pengguna (tabel `notifications`).
 * Diberi nama AppNotification agar tidak bentrok dengan sistem
 * notifikasi bawaan Laravel (Illuminate\Notifications).
 */
class AppNotification extends Model
{
    protected $table = 'notifications';

    const UPDATED_AT = null;

    protected $fillable = [
        'uuid', 'user_id', 'type', 'title', 'body', 'data', 'is_read', 'read_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'is_read' => 'boolean',
            'read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markAsRead(): void
    {
        $this->update(['is_read' => true, 'read_at' => now()]);
    }
}

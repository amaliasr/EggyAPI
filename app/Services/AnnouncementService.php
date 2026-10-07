<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class AnnouncementService
{
    public function __construct(
        private readonly AuditLogService $auditLog,
        private readonly NotificationDispatcher $notifier,
    ) {}

    /** Untuk semua pengguna: hanya yang aktif & sudah waktunya terbit. */
    public function listActive(int $perPage = 20): LengthAwarePaginator
    {
        return Announcement::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->orderByDesc('published_at')
            ->paginate($perPage);
    }

    /** Untuk atasan: semua pengumuman termasuk yang nonaktif/draft. */
    public function listAll(int $perPage = 20): LengthAwarePaginator
    {
        return Announcement::orderByDesc('created_at')->paginate($perPage);
    }

    public function find(int $id): Announcement
    {
        return Announcement::findOrFail($id);
    }

    public function create(array $data, User $actor): Announcement
    {
        $announcement = Announcement::create([
            'uuid' => (string) Str::uuid(),
            'title' => $data['title'],
            'body' => $data['body'],
            'published_at' => $data['published_at'] ?? now(),
            'is_active' => true,
            'created_by' => $actor->id,
        ]);

        $this->auditLog->record($actor, 'create', $announcement, null, $announcement->only(['title']));

        if ($announcement->published_at <= now()) {
            $this->notifier->sendToAllActive('pengumuman', $announcement->title, str($announcement->body)->limit(200)->toString(), [
                'announcement_id' => $announcement->id,
            ]);
        }

        return $announcement;
    }

    public function update(int $id, array $data, User $actor): Announcement
    {
        $announcement = $this->find($id);
        $before = $announcement->only(['title', 'body']);

        $announcement->fill(array_intersect_key($data, array_flip(['title', 'body', 'published_at'])))
            ->forceFill(['updated_by' => $actor->id])
            ->save();

        $this->auditLog->record($actor, 'update', $announcement, $before, $announcement->only(['title', 'body']));

        return $announcement;
    }

    public function setActive(int $id, bool $isActive, User $actor): Announcement
    {
        $announcement = $this->find($id);
        $announcement->update(['is_active' => $isActive, 'updated_by' => $actor->id]);

        $this->auditLog->record($actor, $isActive ? 'activate' : 'deactivate', $announcement, null, ['is_active' => $isActive]);

        return $announcement;
    }
}

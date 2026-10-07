<?php

namespace App\Services;

use App\Models\CorrectionRequest;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

/**
 * Pengajuan koreksi data yang sudah terlanjur tersimpan/dikunci (misal salah
 * input produksi setelah is_locked). Persetujuan di sini HANYA mengubah
 * status permintaan (menunggu -> disetujui/ditolak) dan mencatatnya —
 * TIDAK otomatis menerapkan `proposed_changes` ke data aslinya. Penerapan
 * perubahan sesungguhnya (mis. edit ulang daily_productions/sales) sengaja
 * dilakukan atasan secara manual lewat endpoint modul terkait, supaya tidak
 * ada mass-assignment otomatis ke model sembarangan berdasarkan reference_type
 * yang dikirim klien. Kalau nanti mau diotomasi untuk tipe referensi tertentu,
 * ini best-effort awal yang mudah dikembangkan.
 */
class CorrectionRequestService
{
    public function __construct(
        private readonly AuditLogService $auditLog,
        private readonly NotificationDispatcher $notifier,
    ) {}

    public function list(array $filters, ?User $scopeToUser = null, int $perPage = 20): LengthAwarePaginator
    {
        $query = CorrectionRequest::query()->with(['requester', 'reviewer']);

        if ($scopeToUser) {
            $query->where('requested_by', $scopeToUser->id);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function find(int $id): CorrectionRequest
    {
        return CorrectionRequest::with(['requester', 'reviewer'])->findOrFail($id);
    }

    public function submit(array $data, User $actor): CorrectionRequest
    {
        $request = CorrectionRequest::create([
            'uuid' => (string) Str::uuid(),
            'reference_type' => $data['reference_type'],
            'reference_id' => $data['reference_id'],
            'requested_by' => $actor->id,
            'reason' => $data['reason'],
            'proposed_changes' => $data['proposed_changes'],
            'status' => 'menunggu',
        ]);

        $this->auditLog->record($actor, 'create', $request, null, $request->only(['reference_type', 'reference_id']));

        return $request->load('requester');
    }

    public function review(int $id, string $decision, ?string $reviewNotes, User $actor): CorrectionRequest
    {
        $request = $this->find($id);

        if ($request->status !== 'menunggu') {
            throw new \RuntimeException('Permintaan ini sudah pernah ditinjau.');
        }

        $request->update([
            'status' => $decision,
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
            'review_notes' => $reviewNotes,
        ]);

        $this->notifier->send(
            $request->requester,
            'koreksi',
            $decision === 'disetujui' ? 'Koreksi disetujui' : 'Koreksi ditolak',
            $reviewNotes ?: "Permintaan koreksi Anda telah {$decision}.",
            ['correction_request_id' => $request->id]
        );

        $this->auditLog->record($actor, 'review:'.$decision, $request, null, ['status' => $decision]);

        return $request->load(['requester', 'reviewer']);
    }
}

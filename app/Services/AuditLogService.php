<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

/**
 * Mencatat aksi penting ke tabel audit_logs (append-only).
 * Dipakai oleh controller/service lain: app(AuditLogService::class)->record(...)
 */
class AuditLogService
{
    public function record(
        ?User $actor,
        string $action,
        ?Model $model = null,
        ?array $oldValues = null,
        ?array $newValues = null,
    ): AuditLog {
        return AuditLog::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $actor?->id,
            'action' => $action,
            'model_type' => $model ? class_basename($model) : 'system',
            'model_id' => $model?->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => Request::header('User-Agent'),
        ]);
    }
}

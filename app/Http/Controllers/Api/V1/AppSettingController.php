<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAppSettingRequest;
use App\Http\Resources\AppSettingResource;
use App\Models\AppSetting;
use App\Services\AuditLogService;

class AppSettingController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    public function index()
    {
        return AppSettingResource::collection(AppSetting::orderBy('key')->get());
    }

    public function show(string $key)
    {
        $setting = AppSetting::where('key', $key)->firstOrFail();

        return response()->json(['success' => true, 'data' => new AppSettingResource($setting)]);
    }

    public function update(UpdateAppSettingRequest $request, string $key)
    {
        $data = $request->validated();

        $setting = AppSetting::updateOrCreate(
            ['key' => $key],
            ['value' => $data['value'], 'description' => $data['description'] ?? null, 'updated_by' => $request->user()->id]
        );

        $this->auditLog->record($request->user(), 'update', $setting, null, ['key' => $key, 'value' => $data['value']]);

        return response()->json(['success' => true, 'data' => new AppSettingResource($setting)]);
    }
}

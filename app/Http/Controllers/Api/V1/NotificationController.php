<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AppNotificationResource;
use App\Services\NotificationDispatcher;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(private readonly NotificationDispatcher $service) {}

    public function index(Request $request)
    {
        return AppNotificationResource::collection(
            $this->service->listFor($request->user(), $request->only(['is_read']))
        );
    }

    public function markRead(Request $request, int $notification)
    {
        $updated = $this->service->markRead($request->user(), $notification);

        return response()->json(['success' => true, 'data' => new AppNotificationResource($updated)]);
    }

    public function markAllRead(Request $request)
    {
        $count = $this->service->markAllRead($request->user());

        return response()->json(['success' => true, 'message' => "{$count} notifikasi ditandai terbaca."]);
    }
}

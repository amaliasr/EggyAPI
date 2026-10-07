<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportDateRangeRequest;
use App\Services\ReportService;
use App\Services\StockService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reports,
        private readonly StockService $stock,
    ) {}

    public function production(ReportDateRangeRequest $request)
    {
        $user = $request->user();
        $scope = $user->isAtasan() ? null : $user;

        return response()->json(['success' => true, 'data' => $this->reports->productionSummary($request->validated(), $scope)]);
    }

    public function sales(ReportDateRangeRequest $request)
    {
        $user = $request->user();
        $scope = $user->isAtasan() ? null : $user;

        return response()->json(['success' => true, 'data' => $this->reports->salesSummary($request->validated(), $scope)]);
    }

    public function stock()
    {
        return response()->json(['success' => true, 'data' => $this->stock->balances()]);
    }

    public function incentives(Request $request, int $incentiveProgram)
    {
        return response()->json(['success' => true, 'data' => $this->reports->incentiveSummary($incentiveProgram)]);
    }
}

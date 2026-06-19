<?php

namespace App\Http\Controllers\Dashboard;

use App\DTO\Dashboard\DashboardDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\BugRatio\BugRatioRequest;
use App\Http\Requests\Overview\OverviewRequest;
use App\Services\Dashboard\DashboardService;

class DashboardController extends Controller
{
    protected DashboardService $service;

    public function __construct(DashboardService $service)
    {
        $this->service = $service;
    }

    public function overview(OverviewRequest $request)
    {
        $dto = DashboardDTO::fromArray($request->validated());
        $data = $this->service->getOverview(
            $dto->period_start,
            $dto->period_end,
            $dto->project_names
        );

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function getBugRatio(BugRatioRequest $request)
    {
        $dto = DashboardDTO::fromArray($request->validated());
        $result = $this->service->getBugRatio(
            $dto->period_start,
            $dto->period_end,
            $dto->user_name,
            $dto->project_names
        );

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

}

<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $this->dashboardService->getStatsForUser($request->user())
        );
    }
}

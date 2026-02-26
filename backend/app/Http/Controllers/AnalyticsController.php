<?php

namespace App\Http\Controllers;

use App\Services\AnalyticsService;

class AnalyticsController extends Controller
{
    protected $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Get the general HR dashboard analytics (headcount, leaves, performance)
     */
    public function dashboard()
    {
        $data = $this->analyticsService->getDashboardAnalytics();

        return response()->json(['data' => $data]);
    }

    /**
     * Get the attrition risk report
     */
    public function attritionRisk()
    {
        $data = $this->analyticsService->getAttritionRiskReport();

        return response()->json(['data' => $data]);
    }
}

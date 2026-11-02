<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\AnalyticsFilterRequest;
use App\Models\User;
use App\Services\AnalyticsService;
use Illuminate\Contracts\View\View;

class AnalyticsController extends Controller
{
    public function __construct(
        protected AnalyticsService $analyticsService
    ) {}

    /**
     * Display the Owner Dashboard Analytics & Interactive Charts page.
     */
    public function index(AnalyticsFilterRequest $request): View
    {
        /** @var User $owner */
        $owner = $request->user();

        $data = $this->analyticsService->getAnalyticsData($owner, $request->validated());

        return view('owner.analytics.index', $data);
    }
}

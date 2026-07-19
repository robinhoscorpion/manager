<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\PlatformGoal;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $now = Carbon::now();
        $currentGoal = PlatformGoal::where('month', $now->month)
                                   ->where('year', $now->year)
                                   ->first();

        // Pass any other necessary data to the dashboard here

        return Inertia::render('Dashboard', [
            'current_goal' => $currentGoal
        ]);
    }
}

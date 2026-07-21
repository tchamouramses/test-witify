<?php

namespace App\Http\Controllers;

use App\Actions\GetDashboardOverview;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(GetDashboardOverview $getDashboardOverview): Response
    {
        return Inertia::render('Dashboard', $getDashboardOverview->handle());
    }
}

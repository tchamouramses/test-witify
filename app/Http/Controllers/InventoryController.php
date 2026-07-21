<?php

namespace App\Http\Controllers;

use App\Actions\GetInventoryOverview;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
    public function index(GetInventoryOverview $getInventoryOverview): Response
    {
        return Inertia::render('inventory/Index', $getInventoryOverview->handle());
    }
}

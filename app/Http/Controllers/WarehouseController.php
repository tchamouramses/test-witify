<?php

namespace App\Http\Controllers;

use App\Actions\ListWarehouses;
use Inertia\Inertia;
use Inertia\Response;

class WarehouseController extends Controller
{
    public function index(ListWarehouses $listWarehouses): Response
    {
        return Inertia::render('warehouses/Index', [
            'warehouses' => $listWarehouses->handle(),
        ]);
    }
}

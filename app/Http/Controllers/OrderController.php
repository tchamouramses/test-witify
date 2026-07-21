<?php

namespace App\Http\Controllers;

use App\Actions\GetOrderDetails;
use App\Actions\ListOrders;
use App\Models\Order;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(ListOrders $listOrders): Response
    {
        return Inertia::render('orders/Index', [
            'orders' => $listOrders->handle(),
        ]);
    }

    public function show(Order $order, GetOrderDetails $getOrderDetails): Response
    {
        return Inertia::render('orders/Show', [
            'order' => $getOrderDetails->handle($order),
        ]);
    }
}

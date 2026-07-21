<?php

namespace App\Enums;

enum InventoryReservationErrorEnum: string
{
    case OrderLineQuantityExceeded = 'order_line_quantity_exceeded';
    case WarehouseAvailabilityExceeded = 'warehouse_availability_exceeded';
    case ConcurrentUpdate = 'concurrent_update';

    public function message(int $availableQuantity = 0): string
    {
        return match ($this) {
            self::OrderLineQuantityExceeded => __('Only :quantity units remain to be reserved for this order line.', [
                'quantity' => $availableQuantity,
            ]),
            self::WarehouseAvailabilityExceeded => __('Only :quantity units are available in this warehouse.', [
                'quantity' => $availableQuantity,
            ]),
            self::ConcurrentUpdate => __('Inventory changed while the reservation was being processed. Please try again.'),
        };
    }
}

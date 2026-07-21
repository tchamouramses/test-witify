<?php

namespace App\Actions;

use App\Enums\InventoryReservationErrorEnum;
use App\Models\InventoryBalance;
use App\Models\InventoryReservation;
use App\Models\OrderLine;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class ReserveInventory implements Action
{
    public function __construct(private InventoryReservationRetryPolicy $retryPolicy) {}

    /**
     * Reserve inventory without changing the physical balance or its journal.
     *
     * @throws ValidationException when the line or warehouse has insufficient availability
     */
    public function handle(OrderLine $orderLine, int $warehouseId, int $quantity): InventoryReservation
    {
        for ($attempt = 1; $attempt <= $this->retryPolicy->maxAttempts(); $attempt++) {
            $initialTransactionLevel = DB::transactionLevel();

            try {
                DB::beginTransaction();

                $reservation = $this->reserve($orderLine, $warehouseId, $quantity);

                DB::commit();

                return $reservation;
            } catch (Throwable $exception) {
                $this->rollBackToLevel($initialTransactionLevel);

                if ($exception instanceof ValidationException) {
                    throw $exception;
                }

                if ($this->retryPolicy->shouldRetry($exception, $attempt, $initialTransactionLevel)) {
                    Log::warning('Inventory reservation encountered a concurrency conflict and will be retried.', [
                        ...$this->logContext($orderLine, $warehouseId, $quantity),
                        'attempt' => $attempt,
                        'max_attempts' => $this->retryPolicy->maxAttempts(),
                        ...$this->databaseErrorContext($exception),
                    ]);

                    continue;
                }

                if ($this->retryPolicy->isConcurrencyError($exception) && $initialTransactionLevel === 0) {
                    $this->rejectAfterConcurrencyFailure(
                        orderLine: $orderLine,
                        warehouseId: $warehouseId,
                        quantity: $quantity,
                        exception: $exception,
                    );
                }

                Log::error('Inventory reservation failed unexpectedly.', [
                    ...$this->logContext($orderLine, $warehouseId, $quantity),
                    'attempt' => $attempt,
                    'exception' => $exception,
                ]);

                throw $exception;
            }
        }

        throw new \LogicException('The inventory reservation retry loop ended unexpectedly.');
    }

    private function reserve(OrderLine $orderLine, int $warehouseId, int $quantity): InventoryReservation
    {
        $lockedOrderLine = OrderLine::query()
            ->whereKey($orderLine->getKey())
            ->lockForUpdate()
            ->firstOrFail();

        $balance = InventoryBalance::query()
            ->where('product_id', $lockedOrderLine->product_id)
            ->where('warehouse_id', $warehouseId)
            ->lockForUpdate()
            ->first();

        $quantityReservedForLine = (int) InventoryReservation::query()
            ->whereBelongsTo($lockedOrderLine, 'orderLine')
            ->sum('quantity');
        $quantityRemaining = max(0, $lockedOrderLine->quantity_ordered - $quantityReservedForLine);

        if ($quantity > $quantityRemaining) {
            $this->reject(
                error: InventoryReservationErrorEnum::OrderLineQuantityExceeded,
                availableQuantity: $quantityRemaining,
                orderLine: $lockedOrderLine,
                warehouseId: $warehouseId,
                requestedQuantity: $quantity,
            );
        }

        $quantityReservedInWarehouse = (int) InventoryReservation::query()
            ->where('warehouse_id', $warehouseId)
            ->whereIn(
                'order_line_id',
                OrderLine::query()
                    ->select('id')
                    ->where('product_id', $lockedOrderLine->product_id),
            )
            ->sum('quantity');
        $quantityAvailable = max(0, ($balance->quantity ?? 0) - $quantityReservedInWarehouse);

        if ($quantity > $quantityAvailable) {
            $this->reject(
                error: InventoryReservationErrorEnum::WarehouseAvailabilityExceeded,
                availableQuantity: $quantityAvailable,
                orderLine: $lockedOrderLine,
                warehouseId: $warehouseId,
                requestedQuantity: $quantity,
            );
        }

        $reservation = InventoryReservation::query()->firstOrNew([
            'order_line_id' => $lockedOrderLine->id,
            'warehouse_id' => $warehouseId,
        ]);
        $reservation->quantity = ($reservation->exists ? $reservation->quantity : 0) + $quantity;
        $reservation->save();

        return $reservation;
    }

    private function reject(
        InventoryReservationErrorEnum $error,
        int $availableQuantity,
        OrderLine $orderLine,
        int $warehouseId,
        int $requestedQuantity,
    ): never {
        $message = $error->message($availableQuantity);

        Log::warning('Inventory reservation rejected.', [
            ...$this->logContext($orderLine, $warehouseId, $requestedQuantity),
            'error_code' => $error->value,
            'available_quantity' => $availableQuantity,
            'error_message' => $message,
        ]);

        throw ValidationException::withMessages([
            'quantity' => $message,
        ]);
    }

    private function rejectAfterConcurrencyFailure(
        OrderLine $orderLine,
        int $warehouseId,
        int $quantity,
        Throwable $exception,
    ): never {
        $error = InventoryReservationErrorEnum::ConcurrentUpdate;
        $message = $error->message();

        Log::error('Inventory reservation failed after all concurrency retries.', [
            ...$this->logContext($orderLine, $warehouseId, $quantity),
            'error_code' => $error->value,
            'max_attempts' => $this->retryPolicy->maxAttempts(),
            ...$this->databaseErrorContext($exception),
            'exception' => $exception,
        ]);

        throw ValidationException::withMessages([
            'quantity' => $message,
        ]);
    }

    private function rollBackToLevel(int $transactionLevel): void
    {
        if (DB::transactionLevel() > $transactionLevel) {
            DB::rollBack();
        }
    }

    /**
     * @return array{order_line_id: int, product_id: int, warehouse_id: int, requested_quantity: int}
     */
    private function logContext(OrderLine $orderLine, int $warehouseId, int $quantity): array
    {
        return [
            'order_line_id' => $orderLine->id,
            'product_id' => $orderLine->product_id,
            'warehouse_id' => $warehouseId,
            'requested_quantity' => $quantity,
        ];
    }

    /**
     * @return array{sql_state: mixed, driver_code: mixed}
     */
    private function databaseErrorContext(Throwable $exception): array
    {
        if (! $exception instanceof QueryException) {
            return [
                'sql_state' => null,
                'driver_code' => null,
            ];
        }

        return [
            'sql_state' => $exception->errorInfo[0] ?? null,
            'driver_code' => $exception->errorInfo[1] ?? null,
        ];
    }
}

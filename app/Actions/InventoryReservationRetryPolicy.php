<?php

namespace App\Actions;

use Illuminate\Database\QueryException;
use Throwable;

class InventoryReservationRetryPolicy
{
    private const int MAX_ATTEMPTS = 3;

    public function maxAttempts(): int
    {
        return self::MAX_ATTEMPTS;
    }

    public function shouldRetry(Throwable $exception, int $attempt, int $initialTransactionLevel): bool
    {
        return $initialTransactionLevel === 0
            && $attempt < self::MAX_ATTEMPTS
            && $this->isConcurrencyError($exception);
    }

    public function isConcurrencyError(Throwable $exception): bool
    {
        if (! $exception instanceof QueryException) {
            return false;
        }

        $sqlState = $exception->errorInfo[0] ?? null;
        $driverCode = (int) ($exception->errorInfo[1] ?? 0);

        return $sqlState === '40001' || in_array($driverCode, [1205, 1213], true);
    }
}

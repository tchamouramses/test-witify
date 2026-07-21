<?php

use App\Actions\InventoryReservationRetryPolicy;
use Illuminate\Database\QueryException;

test('mysql concurrency errors are recognized', function (string $sqlState, int $driverCode) {
    $policy = new InventoryReservationRetryPolicy;

    expect($policy->isConcurrencyError(concurrencyQueryException($sqlState, $driverCode)))->toBeTrue();
})->with([
    'serialization failure' => ['40001', 0],
    'lock wait timeout' => ['HY000', 1205],
    'deadlock' => ['HY000', 1213],
]);

test('non concurrency errors are not retried', function () {
    $policy = new InventoryReservationRetryPolicy;

    expect($policy->isConcurrencyError(new RuntimeException('Unexpected failure')))->toBeFalse()
        ->and($policy->isConcurrencyError(concurrencyQueryException('23000', 1062)))->toBeFalse();
});

test('retry is limited to three root transaction attempts', function () {
    $policy = new InventoryReservationRetryPolicy;
    $exception = concurrencyQueryException('40001', 1213);

    expect($policy->maxAttempts())->toBe(3)
        ->and($policy->shouldRetry($exception, 1, 0))->toBeTrue()
        ->and($policy->shouldRetry($exception, 2, 0))->toBeTrue()
        ->and($policy->shouldRetry($exception, 3, 0))->toBeFalse()
        ->and($policy->shouldRetry($exception, 1, 1))->toBeFalse();
});

function concurrencyQueryException(string $sqlState, int $driverCode): QueryException
{
    $pdoException = new PDOException('Simulated database error', $driverCode);
    $pdoException->errorInfo = [$sqlState, $driverCode, 'Simulated database error'];

    return new QueryException('mysql', 'select 1', [], $pdoException);
}

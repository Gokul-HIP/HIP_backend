<?php

namespace App\Modules\Automation\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Runs automation dispatch after the domain transaction commits.
 * Failures are logged and never rethrown so a successful booking/payment
 * cannot become "Something went wrong" because a listener/event TypeError.
 */
final class SafeAutomationDispatch
{
    public static function afterCommit(callable $callback, string $channel): void
    {
        $run = function () use ($callback, $channel): void {
            try {
                $callback();
            } catch (Throwable $e) {
                Log::error('['.$channel.'] Automation dispatch failed after successful domain write', [
                    'exception' => $e::class,
                    'message' => $e->getMessage(),
                ]);
            }
        };

        if (app()->runningUnitTests() || DB::transactionLevel() === 0) {
            $run();

            return;
        }

        DB::afterCommit($run);
    }
}

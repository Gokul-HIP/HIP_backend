<?php

namespace App\Modules\Automation\Support;

use Illuminate\Support\Facades\DB;

final class AfterCommit
{
    public static function run(callable $callback): void
    {
        if (app()->runningUnitTests() || DB::transactionLevel() === 0) {
            $callback();

            return;
        }

        DB::afterCommit($callback);
    }
}

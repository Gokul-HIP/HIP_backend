<?php

namespace App\Http\Controllers\Receptionist;

use App\Http\Controllers\Controller;
use App\Services\Cashier\PaymentsExportService;

class PaymentsExportController extends Controller
{
    public function __invoke(PaymentsExportService $export)
    {
        $csv = $export->getCsvContent();
        $filename = 'payments-export-' . now()->format('Y-m-d-His') . '.csv';

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}

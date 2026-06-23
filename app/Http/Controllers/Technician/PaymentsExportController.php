<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Models\HIPUser;
use App\Services\Cashier\PaymentsExportService;

class PaymentsExportController extends Controller
{
    public function __invoke(PaymentsExportService $export)
    {
        $user = auth()->user();
        $hospitalId = $user instanceof HIPUser ? $user->hospital_id : null;
        $csv = $export->getCsvContent($hospitalId);
        $filename = 'payments-export-' . now()->format('Y-m-d-His') . '.csv';

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}

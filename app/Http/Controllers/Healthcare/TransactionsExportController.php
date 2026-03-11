<?php

namespace App\Http\Controllers\Healthcare;

use App\Http\Controllers\Controller;
use App\Services\Healthcare\TransactionsExportService;
use Illuminate\Http\Request;

class TransactionsExportController extends Controller
{
    public function __invoke(Request $request, TransactionsExportService $export)
    {
        $csv = $export->getCsvContent($request->user(), [
            'search' => $request->string('search')->toString(),
            'service_type_filter' => $request->string('service_type_filter')->toString() ?: 'all',
            'status_filter' => $request->string('status_filter')->toString() ?: 'all',
            'hospital_filter' => $request->string('hospital_filter')->toString() ?: 'all',
            'from_date' => $request->string('from_date')->toString(),
            'to_date' => $request->string('to_date')->toString(),
        ]);

        $filename = 'healthcare-transactions-' . now()->format('Y-m-d-His') . '.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}

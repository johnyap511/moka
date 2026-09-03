<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\EzeeRevenueExport;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The revenue export finance reconciles against EZEE's detail revenue
 * report. A second export beside the older booking export, which is left as
 * it is; see EzeeRevenueExport for the shape.
 */
class EzeeRevenueExportController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.ezee.revenue_export', [
            'hotels' => EzeeRevenueExport::HOTELS,
            'month'  => $request->input('month', date('Y-m', strtotime('first day of last month'))),
        ]);
    }

    public function download(Request $request): StreamedResponse
    {
        $request->validate([
            'month'       => 'required|date_format:Y-m',
            'hotel'       => 'nullable|string',
            'ezee_files'  => 'nullable|array',
            'ezee_files.*' => 'file|mimes:csv,txt|max:10240',
        ]);

        $files = [];
        foreach ($request->file('ezee_files', []) as $f) {
            if ($f && $f->isValid()) {
                $files[] = $f->getRealPath();
            }
        }

        $export   = new EzeeRevenueExport($request->input('month'), $request->input('hotel') ?: null);
        $compared = count($files) > 0;
        $lines    = $export->lines($files);
        $name     = sprintf('moka-revenue-%s%s%s.csv', $request->input('month'),
            $request->input('hotel') ? '-' . $request->input('hotel') : '', $compared ? '-vs-ezee' : '');

        return response()->streamDownload(function () use ($export, $lines, $compared) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $export->headers($compared));
            foreach ($lines as $l) {
                fputcsv($out, $export->row($l, $compared));
            }
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}

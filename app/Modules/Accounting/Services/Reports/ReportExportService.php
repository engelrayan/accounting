<?php

namespace App\Modules\Accounting\Services\Reports;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Response;
use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;

class ReportExportService
{
    public function downloadExcel(string $view, array $data, string $filename): Response
    {
        $html = "\xEF\xBB\xBF" . view($view, $data)->render();

        return response($html, 200, [
            'Content-Type'        => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.xls"',
            'Cache-Control'       => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma'              => 'public',
        ]);
    }

    public function downloadPdf(
        string $view,
        array $data,
        string $filename,
        string $orientation = 'portrait',
    ): Response {
        $html = view($view, $data)->render();

        // Inline local CSS <link> tags — mPDF resolves local file paths
        $html = $this->inlineLocalCss($html);

        $mpdf = new Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4',
            'orientation'   => strtoupper($orientation[0]), // 'P' or 'L'
            'margin_top'    => 15,
            'margin_bottom' => 15,
            'margin_left'   => 10,
            'margin_right'  => 10,
            'tempDir'       => storage_path('app/mpdf'),
            'default_font'  => 'dejavusans',
        ]);

        $mpdf->SetDirectionality('rtl');
        $mpdf->autoScriptToLang    = true;
        $mpdf->autoLangToFont      = true;
        $mpdf->baseScript          = 1;
        $mpdf->autoVietnamese      = true;
        $mpdf->autoArabic          = true;

        $mpdf->WriteHTML($html);

        return response($mpdf->Output('', 'S'), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.pdf"',
        ]);
    }

    public function filename(string $base, ?string $from, ?string $to): string
    {
        if (! $from && ! $to) {
            return $base . '-all-periods';
        }

        return $base . '-' . ($from ?? 'start') . '-to-' . ($to ?? 'today');
    }

    // -------------------------------------------------------------------------

    /**
     * Replace <link href="...css"> pointing to local absolute paths with
     * inline <style> blocks so mPDF can render them without HTTP requests.
     */
    private function inlineLocalCss(string $html): string
    {
        return preg_replace_callback(
            '/<link[^>]+href="([^"]+\.css)"[^>]*\/?>/i',
            function (array $m): string {
                $path = $m[1];
                if (file_exists($path)) {
                    return '<style>' . file_get_contents($path) . '</style>';
                }
                return '';
            },
            $html,
        );
    }
}

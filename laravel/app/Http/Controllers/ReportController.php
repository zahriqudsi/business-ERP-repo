<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\Reports\Report;
use App\Services\Reports\ReportBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public const PERIODS = [
        'this_month' => 'This month',
        'last_month' => 'Last month',
        'this_quarter' => 'This quarter',
        'this_year' => 'This year',
        'last_year' => 'Last year',
        'custom' => 'Custom dates',
    ];

    public function index(): View
    {
        return view('reports.index', ['reports' => ReportBuilder::REPORTS]);
    }

    /**
     * One report on screen, or downloaded with ?format=csv / ?format=pdf.
     */
    public function show(Request $request, string $report, ReportBuilder $builder): View|Response
    {
        abort_unless(array_key_exists($report, ReportBuilder::REPORTS), 404);

        $input = $request->validate([
            'period' => ['nullable', Rule::in(array_keys(self::PERIODS))],
            'from' => ['nullable', 'required_if:period,custom', 'date_format:Y-m-d'],
            'to' => ['nullable', 'required_if:period,custom', 'date_format:Y-m-d', 'after_or_equal:from'],
            'customer_id' => [Rule::requiredIf($report === 'statement' && $request->filled('format')), 'nullable', 'integer', 'exists:customers,id'],
            'format' => ['nullable', Rule::in(['csv', 'pdf'])],
        ]);

        $period = $input['period'] ?? ($report === 'statement' ? 'this_year' : 'this_month');
        [$from, $to] = $this->range($period, $input['from'] ?? null, $input['to'] ?? null);

        // The statement needs a customer before there is anything to show
        $result = $report === 'statement' && empty($input['customer_id'])
            ? null
            : $builder->build($report, $from, $to, ['customer_id' => $input['customer_id'] ?? null]);

        return match ($input['format'] ?? null) {
            'csv' => $this->csv($result),
            'pdf' => Pdf::loadView('reports.pdf', ['report' => $result])
                ->setPaper('a4', $this->isWide($result) ? 'landscape' : 'portrait')
                ->download($this->fileName($result, 'pdf')),
            default => view('reports.show', [
                'key' => $report,
                'meta' => ReportBuilder::REPORTS[$report],
                'report' => $result,
                'period' => $period,
                'from' => $from,
                'to' => $to,
                'customerId' => $input['customer_id'] ?? null,
                'customers' => $report === 'statement' ? Customer::orderBy('name')->get(['id', 'name']) : collect(),
            ]),
        };
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function range(string $period, ?string $from, ?string $to): array
    {
        $now = now();

        return match ($period) {
            'last_month' => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth()],
            'this_quarter' => [$now->copy()->firstOfQuarter(), $now->copy()->lastOfQuarter()],
            'this_year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            'last_year' => [$now->copy()->subYear()->startOfYear(), $now->copy()->subYear()->endOfYear()],
            'custom' => [Carbon::parse($from)->startOfDay(), Carbon::parse($to)->endOfDay()],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };
    }

    /**
     * Every table of the report, one after another, with a title row above each.
     */
    private function csv(Report $report): StreamedResponse
    {
        return response()->streamDownload(function () use ($report) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel shows "Rs." and names correctly
            fputcsv($out, [$report->title], escape: '');
            fputcsv($out, [$report->periodLabel()], escape: '');
            fputcsv($out, [], escape: '');

            foreach ($report->summary as $label => [$value, $type]) {
                fputcsv($out, [$label, Report::raw($value, $type)], escape: '');
            }

            foreach ($report->tables as $table) {
                fputcsv($out, [], escape: '');
                fputcsv($out, [$table['title']], escape: '');
                fputcsv($out, array_column($table['columns'], 0), escape: '');
                foreach ($table['rows'] as $row) {
                    fputcsv($out, array_map(
                        fn (string $key, array $column) => Report::raw($row[$key] ?? null, $column[1]),
                        array_keys($table['columns']),
                        $table['columns'],
                    ), escape: '');
                }
                if (! empty($table['totals'])) {
                    fputcsv($out, array_map(
                        fn (string $key, array $column) => array_key_exists($key, $table['totals'])
                            ? Report::raw($table['totals'][$key], $column[1])
                            : ($key === array_key_first($table['columns']) ? 'Total' : ''),
                        array_keys($table['columns']),
                        $table['columns'],
                    ), escape: '');
                }
            }

            fclose($out);
        }, $this->fileName($report, 'csv'), ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function fileName(Report $report, string $extension): string
    {
        $dates = $report->from ? $report->from->format('Y-m-d').'_to_'.$report->to->format('Y-m-d') : $report->to->format('Y-m-d');

        return Str::slug($report->title, '_').'_'.$dates.'.'.$extension;
    }

    private function isWide(Report $report): bool
    {
        return collect($report->tables)->max(fn (array $table) => count($table['columns'])) > 6;
    }
}

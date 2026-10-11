<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>{{ $report->title }}</title>
  <style>
    @page { margin: 28px 30px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #1f2937; }
    .header { border-bottom: 2px solid #1e3a8a; padding-bottom: 8px; margin-bottom: 14px; }
    .company { font-size: 15px; font-weight: bold; color: #1e3a8a; }
    .muted { color: #6b7280; }
    h1 { font-size: 14px; margin: 4px 0 0; }
    .summary { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin: 0 -6px 14px; }
    .summary td { background: #f3f4f6; padding: 7px 8px; border-radius: 4px; vertical-align: top; }
    .summary .label { font-size: 8px; color: #6b7280; text-transform: uppercase; }
    .summary .value { font-size: 11px; font-weight: bold; margin-top: 2px; }
    .report-table-block { margin-bottom: 14px; }
    .report-table-title { font-size: 11px; margin: 0 0 5px; color: #1e3a8a; }
    table.data { width: 100%; border-collapse: collapse; }
    table.data th { background: #1e3a8a; color: #fff; text-align: left; padding: 4px 5px; font-size: 8.5px; }
    table.data td { padding: 4px 5px; border-bottom: 1px solid #e5e7eb; }
    table.data tbody tr:nth-child(even) td { background: #f9fafb; }
    table.data tfoot th { background: #e5e7eb; color: #111827; }
    .text-end { text-align: right !important; }
    .text-center { text-align: center; }
    .text-danger { color: #b91c1c; }
    .text-muted { color: #6b7280; }
    .report-note { font-size: 8px; color: #6b7280; }
    .footer { position: fixed; bottom: -14px; left: 0; right: 0; font-size: 8px; color: #9ca3af; text-align: right; }
  </style>
</head>
<body>
  <div class="header">
    <div class="company">{{ config('sjpos.company.name') }}</div>
    <div class="muted">{{ config('sjpos.company.address') }} · {{ config('sjpos.company.phone') }}</div>
    <h1>{{ $report->title }}</h1>
    <div class="muted">{{ $report->periodLabel() }}</div>
  </div>

  @if ($report->summary)
  <table class="summary">
    <tr>
      @foreach ($report->summary as $label => [$value, $type])
        <td>
          <div class="label">{{ $label }}</div>
          <div class="value {{ $type === 'money' && $value < 0 ? 'text-danger' : '' }}">{{ \App\Services\Reports\Report::format($value, $type) }}</div>
        </td>
      @endforeach
    </tr>
  </table>
  @endif

  @include('reports._tables', ['tableClass' => 'data'])

  <div class="footer">Generated {{ now()->format('d M Y H:i') }}</div>
</body>
</html>

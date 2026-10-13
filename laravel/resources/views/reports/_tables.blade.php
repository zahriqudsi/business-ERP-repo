{{-- Summary figures and tables of a Report; shared by the screen and PDF views. Expects $report and $tableClass. --}}
@use('App\Services\Reports\Report')
@foreach ($report->tables as $table)
  <div class="report-table-block">
    <h3 class="report-table-title">{{ $table['title'] }}</h3>
    <div class="table-responsive">
      <table class="{{ $tableClass }}">
        <thead>
          <tr>
            @foreach ($table['columns'] as [$heading, $type])
              <th class="{{ in_array($type, ['money', 'int', 'percent']) ? 'text-end' : '' }}">{{ $heading }}</th>
            @endforeach
          </tr>
        </thead>
        <tbody>
          @forelse ($table['rows'] as $row)
            <tr>
              @foreach ($table['columns'] as $key => [$heading, $type])
                @php($value = $row[$key] ?? null)
                <td class="{{ in_array($type, ['money', 'int', 'percent']) ? 'text-end' : '' }} {{ $type === 'money' && is_numeric($value) && $value < 0 ? 'text-danger' : '' }}">{{ Report::format($value, $type) }}</td>
              @endforeach
            </tr>
          @empty
            <tr><td colspan="{{ count($table['columns']) }}" class="text-center text-muted">{{ $table['empty'] ?? 'Nothing to show.' }}</td></tr>
          @endforelse
        </tbody>
        @if (! empty($table['totals']) && ! empty($table['rows']))
          <tfoot>
            <tr>
              @foreach ($table['columns'] as $key => [$heading, $type])
                <th class="{{ in_array($type, ['money', 'int', 'percent']) ? 'text-end' : '' }}">
                  @if (array_key_exists($key, $table['totals']))
                    {{ Report::format($table['totals'][$key], $type) }}
                  @elseif ($loop->first)
                    Total
                  @endif
                </th>
              @endforeach
            </tr>
          </tfoot>
        @endif
      </table>
    </div>
  </div>
@endforeach

@if ($report->note)
  <p class="report-note">{{ $report->note }}</p>
@endif

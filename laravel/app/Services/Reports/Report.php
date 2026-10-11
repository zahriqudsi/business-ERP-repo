<?php

namespace App\Services\Reports;

use Illuminate\Support\Carbon;

/**
 * A finished report: rendered on screen, as CSV or as PDF by the same views.
 *
 * Column types control formatting: "text", "date", "int", "money", "percent".
 */
final class Report
{
    /**
     * @param  array<string, array{0: float|int|string, 1: string}>  $summary  label => [value, type]
     * @param  list<array{title: string, columns: array<string, array{0: string, 1: string}>, rows: list<array<string, mixed>>, totals?: array<string, mixed>, empty?: string}>  $tables
     *                                                                                                                                                                                    columns are key => [heading, type]
     */
    public function __construct(
        public readonly string $title,
        public readonly ?Carbon $from,
        public readonly ?Carbon $to,
        public readonly array $summary = [],
        public readonly array $tables = [],
        public readonly ?string $note = null,
    ) {}

    public function periodLabel(): string
    {
        return match (true) {
            $this->from === null && $this->to !== null => 'As of '.$this->to->format('d M Y'),
            $this->from === null => 'All time',
            $this->from->isSameDay($this->to) => $this->from->format('d M Y'),
            default => $this->from->format('d M Y').' – '.$this->to->format('d M Y'),
        };
    }

    /**
     * Display text for one cell.
     */
    public static function format(mixed $value, string $type): string
    {
        if ($value === null || $value === '') {
            return $type === 'text' ? '' : '-';
        }

        return match ($type) {
            'money' => rs($value),
            'int' => number_format((float) $value),
            'percent' => number_format((float) $value, 1).'%',
            'date' => $value instanceof \DateTimeInterface ? $value->format('d M Y') : Carbon::parse($value)->format('d M Y'),
            default => (string) $value,
        };
    }

    /**
     * Raw value for CSV: numbers stay numbers so spreadsheets can sum them.
     */
    public static function raw(mixed $value, string $type): string|int|float
    {
        if ($value === null) {
            return '';
        }

        return match ($type) {
            'money', 'percent' => round((float) $value, 2),
            'int' => (int) $value,
            'date' => $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : Carbon::parse($value)->toDateString(),
            default => (string) $value,
        };
    }
}

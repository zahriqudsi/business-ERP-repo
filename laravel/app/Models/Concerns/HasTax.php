<?php

namespace App\Models\Concerns;

/**
 * Tax on an invoice or quotation. `subtotal` is the amount payable (net + tax).
 *
 * @property float $subtotal
 * @property float|null $net_amount
 * @property float $tax_rate
 * @property float $tax_amount
 */
trait HasTax
{
    /**
     * Work out net, tax and payable totals from the line total.
     *
     * @return array{net_amount: float, tax_rate: float, tax_amount: float, subtotal: float}
     */
    public static function taxTotals(float $net, float $rate): array
    {
        $net = round($net, 2);
        $tax = round($net * $rate / 100, 2);

        return ['net_amount' => $net, 'tax_rate' => $rate, 'tax_amount' => $tax, 'subtotal' => round($net + $tax, 2)];
    }

    /**
     * The rate to use when saving: keep the document's own rate once it has one,
     * otherwise today's rate from Settings.
     */
    public function rateFor(bool $applyTax): float
    {
        if (! $applyTax) {
            return 0.0;
        }

        return (float) $this->tax_rate > 0 ? (float) $this->tax_rate : (float) config('sjpos.tax.rate');
    }

    public function hasTax(): bool
    {
        return (float) $this->tax_amount > 0;
    }

    /**
     * Line total before tax (documents saved before tax existed have no net amount).
     */
    public function getNetTotalAttribute(): float
    {
        return (float) ($this->net_amount ?? $this->subtotal);
    }

    /**
     * Whether the form should offer the tax checkbox for this document.
     */
    public function offersTax(): bool
    {
        return (bool) config('sjpos.tax.enabled') || $this->hasTax();
    }
}

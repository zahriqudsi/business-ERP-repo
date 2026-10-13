<?php

namespace App\Models\Concerns;

/**
 * Shared balance handling for documents that are settled by payments
 * (invoices via payments_received, purchases via payments_made).
 *
 * @property float $subtotal
 * @property float $amount_paid
 * @property string $status
 */
trait TracksPayments
{
    /**
     * Add (or, with a negative amount, reverse) a payment and refresh the status.
     *
     * Callers validate the amount against the balance first, so the clamp is only a safety net.
     */
    public function applyPayment(float $amount): void
    {
        $this->amount_paid = max(0, min((float) $this->subtotal, (float) $this->amount_paid + $amount));
        $this->syncPaymentStatus();
        $this->save();
    }

    /**
     * Set "paid" / "confirmed" from the amounts. Draft and cancelled documents are left alone.
     */
    public function syncPaymentStatus(): void
    {
        if (! in_array($this->status, ['confirmed', 'paid'], true)) {
            return;
        }

        $this->status = $this->subtotal > 0 && (float) $this->amount_paid >= (float) $this->subtotal ? 'paid' : 'confirmed';
    }

    /**
     * Whether the document can still take a new payment.
     */
    public function acceptsPayments(): bool
    {
        return $this->status === 'confirmed' && $this->balance > 0;
    }

    public function getBalanceAttribute(): float
    {
        return round((float) $this->subtotal - (float) $this->amount_paid, 2);
    }
}

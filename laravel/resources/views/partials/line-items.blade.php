{{--
    Editable line items for invoices and quotations.
    Expects: $items (collection, may be empty), $accent ('primary'|'success'), $totalLabel, $productOptions
--}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 text-dark small fw-bold text-uppercase">{{ $heading }}</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table data-line-items data-product-price="price" class="table table-hover align-middle mb-0 custom_invoice_tbl">
                <thead class="table-light">
                    <tr>
                        <th style="width:10%;" class="ps-4">Item #</th>
                        <th style="width:40%;">Description</th>
                        <th style="width:15%;">Brand</th>
                        <th style="width:10%;">Qty</th>
                        <th style="width:12%;">Unit Price</th>
                        <th style="width:13%;" class="pe-4 text-end">Total</th>
                        <th style="width:5%;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items->isEmpty() ? [null] : $items as $item)
                    <tr>
                        <td class="ps-4"><input type="text" name="item_no[]" class="form-control form-control-sm" value="{{ $item?->item_no ?? '01' }}" placeholder="e.g. 01" required></td>
                        <td>
                            <div class="input-group input-group-sm mb-1">
                                <span class="input-group-text"><i class="bi bi-box-seam"></i></span>
                                <input type="text" class="form-control product-picker" list="product-list" value="{{ $item?->product?->picker_label }}" placeholder="Pick a product (optional)" autocomplete="off" aria-label="Product">
                            </div>
                            <input type="hidden" name="product_id[]" value="{{ $item?->product_id }}">
                            <textarea name="description[]" rows="2" class="form-control form-control-sm description-textarea" placeholder="Describe the service or product..." required>{{ $item?->description }}</textarea>
                            <span class="product-stock small"></span>
                        </td>
                        <td><input type="text" name="brand[]" class="form-control form-control-sm" value="{{ $item?->brand }}" placeholder="Brand" required></td>
                        <td><input type="number" name="quantity[]" min="1" class="form-control form-control-sm" value="{{ $item?->quantity }}" placeholder="Qty" required oninput="updateTotal(this.closest('tr'))"></td>
                        <td><input type="number" step="0.01" name="unit_price[]" class="form-control form-control-sm" value="{{ $item?->unit_price }}" placeholder="0.00" required oninput="updateTotal(this.closest('tr'))"></td>
                        <td class="pe-4"><input type="number" step="0.01" name="total[]" class="form-control form-control-sm border-0 bg-transparent text-end fw-bold formatted-total" value="{{ number_format((float) ($item?->total ?? 0), 2, '.', '') }}" readonly tabindex="-1"></td>
                        <td class="text-center pe-3">
                            <div class="d-flex gap-1 justify-content-center">
                                <button type="button" class="action-btn-circle btn-light-success" onclick="addRow(this)" title="Add Row">
                                    <i class="bi bi-plus"></i>
                                </button>
                                <button type="button" class="action-btn-circle btn-light-danger" onclick="removeRow(this)" title="Remove Row">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @include('partials.product-picker')
    @php
        $offersTax = $document->offersTax();
        $taxRate = (float) $document->tax_rate > 0 ? (float) $document->tax_rate : (float) config('sjpos.tax.rate');
        // New documents get tax when it's switched on in Settings; saved ones keep their choice
        $applyTax = $document->exists ? $document->hasTax() : (bool) config('sjpos.tax.enabled');
    @endphp
    <div class="card-footer bg-light border-0 p-4">
        <div class="row justify-content-end">
            <div class="col-md-6 col-lg-4">
                @if ($offersTax)
                <div class="d-flex justify-content-between align-items-center mb-2 small">
                    <span class="text-muted fw-semibold">Before tax</span>
                    <span id="formatted_net">{{ currency() }} 0.00</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2 small">
                    <div class="form-check form-switch mb-0">
                        <input type="hidden" name="apply_tax" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="apply_tax" name="apply_tax" value="1" data-rate="{{ $taxRate }}" @checked($applyTax) onchange="updateSubtotal()">
                        <label class="form-check-label fw-semibold" for="apply_tax">Add {{ config('sjpos.tax.name') ?: 'tax' }} ({{ rtrim(rtrim(number_format($taxRate, 2), '0'), '.') }}%)</label>
                    </div>
                    <span id="formatted_tax">{{ currency() }} 0.00</span>
                </div>
                <hr class="my-2">
                @endif
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted fw-bold small text-uppercase">{{ $totalLabel }}</span>
                    <div class="text-end">
                        <input type="hidden" id="subtotal" value="0.00">
                        <div id="formatted_subtotal" class="h4 mb-0 fw-bold text-{{ $accent }}">{{ currency() }} 0.00</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

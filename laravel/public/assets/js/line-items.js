/**
 * Editable line-item table used by the invoice and quotation forms.
 * Markup: <table data-line-items> with rows containing item_no[], quantity[], unit_price[], total[].
 * Inline handlers call addRow(this), removeRow(this) and updateTotal(row).
 */
function lineItemsBody() {
    return document.querySelector('[data-line-items] tbody');
}

function formatRs(value) {
    return (window.SJ_CURRENCY || 'Rs.') + ' ' + value.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function addRow(btn) {
    const row = btn.closest('tr');
    const newRow = row.cloneNode(true);

    newRow.querySelectorAll('input, textarea').forEach(input => {
        input.value = input.name === 'total[]' ? '0.00' : '';
    });
    newRow.querySelectorAll('.product-stock').forEach(hint => { hint.textContent = ''; });

    row.insertAdjacentElement('afterend', newRow);
    updateItemNumbers();
}

function removeRow(btn) {
    const tbody = lineItemsBody();
    if (tbody.rows.length <= 1) {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('warningModal')).show();
        return;
    }
    btn.closest('tr').remove();
    updateItemNumbers();
    updateSubtotal();
}

function updateItemNumbers() {
    Array.from(lineItemsBody().rows).forEach((tr, index) => {
        const input = tr.querySelector('input[name="item_no[]"]');
        if (input) input.value = (index + 1).toString().padStart(2, '0');
    });
}

function updateTotal(row) {
    const qty = parseFloat(row.querySelector('input[name="quantity[]"]').value) || 0;
    const price = parseFloat(row.querySelector('input[name="unit_price[]"]').value) || 0;
    const total = row.querySelector('input[name="total[]"]');
    if (total) total.value = (qty * price).toFixed(2);
    updateSubtotal();
}

function updateSubtotal() {
    let sum = 0;
    document.querySelectorAll('input[name="total[]"]').forEach(input => {
        sum += parseFloat(input.value) || 0;
    });

    // Optional tax switch (shown when tax is on in Settings)
    const taxSwitch = document.getElementById('apply_tax');
    const tax = taxSwitch && taxSwitch.checked ? Math.round(sum * parseFloat(taxSwitch.dataset.rate || 0)) / 100 : 0;
    const net = document.getElementById('formatted_net');
    const taxOut = document.getElementById('formatted_tax');
    if (net) net.textContent = formatRs(sum);
    if (taxOut) taxOut.textContent = formatRs(tax);

    const subtotal = document.getElementById('subtotal');
    const formatted = document.getElementById('formatted_subtotal');
    if (subtotal) subtotal.value = (sum + tax).toFixed(2);
    if (formatted) formatted.textContent = formatRs(sum + tax);
}

// "Display on invoice" selector: show the bank or notes preview block.
function toggleInfoType() {
    const select = document.getElementById('info_type');
    if (!select) return;
    const bank = document.getElementById('bank_details_section');
    const notes = document.getElementById('notes_section');
    if (bank) bank.style.display = select.value === 'bank' || select.value === 'bank_details' ? 'block' : 'none';
    if (notes) notes.style.display = select.value === 'notes' ? 'block' : 'none';
}

document.addEventListener('DOMContentLoaded', () => {
    updateSubtotal();
    toggleInfoType();
});

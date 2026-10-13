/**
 * Product picker for line-item rows (invoices, quotations, purchases).
 *
 * Each row has <input class="product-picker" list="product-list"> and <input type="hidden" name="product_id[]">.
 * Choosing a product fills the row's description, brand and price; the table's data-product-price
 * attribute picks which price ("price" = selling, "cost" = cost). Products come from window.SJ_PRODUCTS.
 */
(function () {
    const products = () => window.SJ_PRODUCTS || [];

    function setValue(row, selector, value) {
        const field = row.querySelector(selector);
        if (!field) return;
        field.value = value;
        field.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function showStock(row, product) {
        const hint = row.querySelector('.product-stock');
        if (!hint) return;
        if (!product || product.stock === null) {
            hint.textContent = '';
            hint.className = 'product-stock small';
            return;
        }
        hint.textContent = product.stock + ' in stock';
        hint.className = 'product-stock small ' + (product.stock > 0 ? 'text-success' : 'text-danger fw-bold');
    }

    function pick(input) {
        const row = input.closest('tr');
        const product = products().find(p => p.label === input.value.trim());
        const hidden = row.querySelector('input[name="product_id[]"]');

        if (!product) {
            if (hidden) hidden.value = '';
            showStock(row, null);
            return;
        }

        const priceField = input.closest('[data-product-price]')?.dataset.productPrice === 'cost' ? 'cost' : 'price';
        if (hidden) hidden.value = product.id;
        setValue(row, '[name="description[]"]', product.name);
        setValue(row, '[name="brand[]"]', product.brand);
        setValue(row, '[name="unit_price[]"]', Number(product[priceField]).toFixed(2));

        const qty = row.querySelector('[name="quantity[]"]');
        if (qty && !qty.value) setValue(row, '[name="quantity[]"]', 1);
        showStock(row, product);
        qty?.focus();
    }

    document.addEventListener('change', e => {
        if (e.target.matches('.product-picker')) pick(e.target);
    });

    // Rows loaded for editing show their product's stock straight away
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.product-picker').forEach(input => {
            const product = products().find(p => p.label === input.value.trim());
            if (product) showStock(input.closest('tr'), product);
        });
    });
})();

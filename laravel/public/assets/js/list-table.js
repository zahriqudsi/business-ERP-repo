/**
 * List pages: client-side search + pagination, "view" in a modal and delete with confirmation.
 *
 * <table data-list-table data-per-page="10" data-empty-text="...">
 * <input id="searchInput">          filters rows by their text
 * <div id="pagination">             page buttons
 * [data-view-url]                    loads the URL into #documentModal
 * [data-delete-url][data-delete-label]  confirms in #deleteModal, then DELETEs and removes the row
 *                                    (add data-delete-reload to reload the page instead)
 */
(function () {
    const table = document.querySelector('[data-list-table]');
    if (!table) return;

    const tbody = table.tBodies[0];
    const perPage = parseInt(table.dataset.perPage || '10', 10);
    const emptyText = table.dataset.emptyText || 'No records found matching your search.';
    const search = document.getElementById('searchInput');
    const pagination = document.getElementById('pagination');
    const colspan = table.tHead ? table.tHead.rows[0].cells.length : 1;
    let currentPage = 1;

    function dataRows() {
        return Array.from(tbody.rows).filter(row => !row.hasAttribute('data-placeholder'));
    }

    function render() {
        const query = search ? search.value.toLowerCase() : '';
        const rows = dataRows().filter(row => !row.hasAttribute('data-empty'));
        const matches = rows.filter(row => row.textContent.toLowerCase().includes(query));

        const totalPages = Math.ceil(matches.length / perPage);
        if (currentPage > totalPages) currentPage = totalPages || 1;

        rows.forEach(row => { row.style.display = 'none'; });
        matches.slice((currentPage - 1) * perPage, currentPage * perPage).forEach(row => { row.style.display = ''; });

        // "No matches" placeholder (only when there are rows to search)
        let placeholder = tbody.querySelector('[data-placeholder]');
        if (rows.length && !matches.length) {
            if (!placeholder) {
                placeholder = tbody.insertRow();
                placeholder.setAttribute('data-placeholder', '');
                placeholder.innerHTML = `<td colspan="${colspan}" class="text-center py-5 text-muted"></td>`;
                placeholder.cells[0].textContent = emptyText;
            }
        } else if (placeholder) {
            placeholder.remove();
        }

        if (!pagination) return;
        pagination.innerHTML = '';
        if (totalPages > 1) {
            for (let i = 1; i <= totalPages; i++) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.textContent = i;
                btn.className = 'btn btn-sm' + (i === currentPage ? ' active' : '');
                btn.addEventListener('click', () => {
                    currentPage = i;
                    render();
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
                pagination.appendChild(btn);
            }
        }
    }

    if (search) {
        search.addEventListener('input', () => { currentPage = 1; render(); });
    }

    // View document in modal
    document.addEventListener('click', e => {
        const trigger = e.target.closest('[data-view-url]');
        if (!trigger) return;
        e.preventDefault();
        sjFetch(trigger.dataset.viewUrl, { headers: { 'Accept': 'text/html' } })
            .then(res => res.text())
            .then(html => {
                document.getElementById('documentModalBody').innerHTML = html;
                bootstrap.Modal.getOrCreateInstance(document.getElementById('documentModal')).show();
            })
            .catch(() => alert('Failed to load the document.'));
    });

    // Delete with confirmation
    let pendingDelete = null;
    document.addEventListener('click', e => {
        const trigger = e.target.closest('[data-delete-url]');
        if (!trigger) return;
        e.preventDefault();
        pendingDelete = trigger;
        document.getElementById('deleteModalBody').textContent =
            `Are you sure you want to delete ${trigger.dataset.deleteLabel}?`;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteModal')).show();
    });

    const confirmBtn = document.getElementById('confirmDeleteBtn');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', () => {
            if (!pendingDelete) return;
            const trigger = pendingDelete;
            const body = new FormData();
            body.append('_method', 'DELETE');

            sjFetch(trigger.dataset.deleteUrl, { method: 'POST', body })
                .then(res => res.json())
                .then(data => {
                    if (data.status !== 'success') {
                        alert(data.message || 'Failed to delete.');
                        return;
                    }
                    bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
                    pendingDelete = null;
                    if (trigger.hasAttribute('data-delete-reload')) {
                        window.location.reload();
                        return;
                    }
                    const row = trigger.closest('tr');
                    row.classList.add('animate-fade-out');
                    setTimeout(() => { row.remove(); render(); }, 300);
                })
                .catch(() => alert('An error occurred while deleting.'));
        });
    }

    document.addEventListener('DOMContentLoaded', render);
    if (document.readyState !== 'loading') render();
})();

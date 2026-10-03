(() => {
    const search = document.getElementById('deliveryHistorySearch');
    const status = document.getElementById('deliveryHistoryStatus');
    const dateFrom = document.getElementById('deliveryHistoryFrom');
    const dateTo = document.getElementById('deliveryHistoryTo');
    const clear = document.getElementById('deliveryHistoryClear');
    const list = document.getElementById('deliveryHistoryList');
    const count = document.getElementById('deliveryHistoryCount');
    const empty = document.getElementById('deliveryHistoryNoResults');
    if (!list || !search || !status || !dateFrom || !dateTo) return;

    const items = Array.from(list.querySelectorAll('.history-item'));

    function aplicarFiltros() {
        const query = search.value.trim().toLocaleLowerCase();
        const selectedStatus = status.value;
        const from = dateFrom.value;
        const to = dateTo.value;
        let visibleCount = 0;

        items.forEach((item) => {
            const matchesText = !query || (item.dataset.search || '').toLocaleLowerCase().includes(query);
            const matchesStatus = !selectedStatus || item.dataset.status === selectedStatus;
            const itemDate = item.dataset.date || '';
            const matchesDate = (!from || (itemDate && itemDate >= from)) && (!to || (itemDate && itemDate <= to));
            const visible = matchesText && matchesStatus && matchesDate;
            item.hidden = !visible;
            if (visible) visibleCount += 1;
        });

        count.textContent = `${visibleCount} de ${items.length} entregas`;
        if (empty) empty.hidden = visibleCount > 0;
    }

    [search, status, dateFrom, dateTo].forEach((control) => {
        control.addEventListener('input', aplicarFiltros);
        control.addEventListener('change', aplicarFiltros);
    });

    if (clear) {
        clear.addEventListener('click', () => {
            search.value = '';
            status.value = '';
            dateFrom.value = '';
            dateTo.value = '';
            aplicarFiltros();
            search.focus();
        });
    }

    aplicarFiltros();
})();
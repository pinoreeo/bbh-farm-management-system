export const initLiveSearch = () => {
    document.querySelectorAll('[data-live-search-form]').forEach((form) => {
        const input = form.querySelector('[data-live-search-input]');
        const table = form.parentElement?.nextElementSibling?.querySelector('[data-live-search-table]')
            ?? document.querySelector('[data-live-search-table]');
        const rows = Array.from(table?.querySelectorAll('[data-live-search-row]') ?? []);
        const emptyRow = table?.querySelector('[data-live-search-empty]');

        input?.addEventListener('input', () => {
            const keyword = input.value.trim().toLowerCase();
            let visibleRows = 0;

            rows.forEach((row) => {
                const isMatch = row.textContent.toLowerCase().includes(keyword);
                row.hidden = !isMatch;
                if (isMatch) {
                    visibleRows += 1;
                }
            });

            if (emptyRow) {
                emptyRow.hidden = visibleRows > 0;
            }
        });
    });
};

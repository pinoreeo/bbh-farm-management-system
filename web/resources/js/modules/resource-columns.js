export const initResourceColumns = () => {
    document.querySelectorAll('[data-table-column-menu]').forEach((menu) => {
        const table = document.querySelector('[data-live-search-table]');
        if (!table) return;

        menu.querySelectorAll('input[type="checkbox"]').forEach((input) => {
            input.addEventListener('change', () => {
                const column = input.value;
                table.querySelectorAll(`[data-table-column-index="${column}"]`).forEach((cell) => {
                    cell.hidden = !input.checked;
                });
            });
        });
    });
};

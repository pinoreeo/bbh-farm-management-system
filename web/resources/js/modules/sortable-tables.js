const normalizeSortValue = (value) => {
    const text = value.trim();

    if (!text || text === '-') {
        return { type: 'empty', value: '' };
    }

    const normalizedNumber = text
        .replace(/[^\d,.-]/g, '')
        .replace(/\.(?=\d{3}(?:\D|$))/g, '')
        .replace(',', '.');

    if (/^-?\d+(?:\.\d+)?$/.test(normalizedNumber)) {
        return { type: 'number', value: Number(normalizedNumber) };
    }

    const isoDate = text.match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (isoDate) {
        return { type: 'date', value: new Date(`${isoDate[1]}-${isoDate[2]}-${isoDate[3]}T00:00:00`).getTime() };
    }

    const localDate = text.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
    if (localDate) {
        return { type: 'date', value: new Date(`${localDate[3]}-${localDate[2]}-${localDate[1]}T00:00:00`).getTime() };
    }

    return { type: 'text', value: text.toLocaleLowerCase('id-ID') };
};

const compareSortValues = (left, right, direction) => {
    if (left.type === 'empty' && right.type !== 'empty') return 1;
    if (right.type === 'empty' && left.type !== 'empty') return -1;

    let result = 0;

    if (left.type === right.type && ['number', 'date'].includes(left.type)) {
        result = left.value - right.value;
    } else {
        result = String(left.value).localeCompare(String(right.value), 'id-ID', {
            numeric: true,
            sensitivity: 'base',
        });
    }

    return direction === 'asc' ? result : -result;
};

export const initSortableTables = () => {
    document.querySelectorAll('.ui-table').forEach((table) => {
        const tbody = table.tBodies[0];
        const headers = Array.from(table.tHead?.rows[0]?.cells || []);

        if (!tbody || headers.length === 0 || table.dataset.sortReady === 'true') {
            return;
        }

        table.dataset.sortReady = 'true';

        headers.forEach((header, columnIndex) => {
            const label = header.textContent.trim();
            const isActionColumn = label.toLocaleLowerCase('id-ID') === 'aksi';

            if (!label || isActionColumn) {
                return;
            }

            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'ui-sort-btn';
            button.setAttribute('aria-label', `Urutkan berdasarkan ${label}`);
            button.innerHTML = `<span>${label}</span><span class="ui-sort-icon" aria-hidden="true"></span>`;

            header.textContent = '';
            header.append(button);

            button.addEventListener('click', () => {
                const nextDirection = header.dataset.sortDirection === 'asc' ? 'desc' : 'asc';
                const rows = Array.from(tbody.rows).filter((row) => row.cells.length === headers.length);

                headers.forEach((item) => {
                    item.removeAttribute('data-sort-direction');
                    item.setAttribute('aria-sort', 'none');
                });

                rows
                    .map((row, index) => ({ row, index, value: normalizeSortValue(row.cells[columnIndex]?.innerText || '') }))
                    .sort((left, right) => {
                        const result = compareSortValues(left.value, right.value, nextDirection);

                        return result === 0 ? left.index - right.index : result;
                    })
                    .forEach(({ row }) => tbody.append(row));

                header.dataset.sortDirection = nextDirection;
                header.setAttribute('aria-sort', nextDirection === 'asc' ? 'ascending' : 'descending');
            });
        });
    });
};

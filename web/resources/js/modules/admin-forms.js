const initSelectLists = () => {
    document.querySelectorAll('.js-select-list').forEach((wrapper) => {
        const select = wrapper.querySelector('.js-select-list-picker');
        const box = wrapper.querySelector('.js-select-list-box');
        const list = wrapper.querySelector('.js-select-list-items');
        const empty = wrapper.querySelector('.js-select-list-empty');
        const template = wrapper.querySelector('.js-select-list-template');
        const selectedValues = new Set();

        if (!select || !box || !list || !empty || !template) return;

        const syncEmptyState = () => {
            empty.classList.toggle('hidden', selectedValues.size > 0);
            box.classList.toggle('hidden', selectedValues.size === 0);
        };

        const syncSeparators = () => {
            const items = [...list.querySelectorAll('[data-value]')];
            items.forEach((item, index) => {
                item.querySelector('.js-select-list-separator')?.classList.toggle('hidden', index === items.length - 1);
            });
        };

        const syncOptions = () => {
            [...select.options].forEach((option) => {
                if (option.value === '') return;

                const selected = selectedValues.has(option.value);
                option.hidden = selected;
                option.disabled = selected;
            });
        };

        const addItem = (value, label) => {
            if (!value || selectedValues.has(value)) return;

            selectedValues.add(value);

            const item = template.content.firstElementChild.cloneNode(true);
            item.dataset.value = value;
            item.querySelector('.js-select-list-label').textContent = label;
            item.querySelector('input[type="hidden"]').value = value;
            item.querySelector('button').addEventListener('click', () => {
                selectedValues.delete(value);
                item.remove();
                syncOptions();
                syncEmptyState();
                syncSeparators();
            });

            list.appendChild(item);
            syncOptions();
            syncEmptyState();
            syncSeparators();
        };

        [...select.options].forEach((option) => {
            if (option.dataset.selected === '1') {
                addItem(option.value, option.textContent.trim());
            }
        });

        select.addEventListener('change', () => {
            const option = select.selectedOptions[0];
            if (!option || option.value === '') return;

            addItem(option.value, option.textContent.trim());
            select.value = '';
        });

        syncOptions();
        syncEmptyState();
    });
};

const initConditionalFields = () => {
    document.querySelectorAll('[data-show-when-field]').forEach((field) => {
        const source = field.closest('form')?.querySelector(`[name="${field.dataset.showWhenField}"]`);
        if (!source) return;

        const toggleField = () => {
            field.classList.toggle('hidden', source.value !== field.dataset.showWhenValue);
        };

        source.addEventListener('change', toggleField);
        toggleField();
    });

    document.querySelectorAll('[data-show-when-filled]').forEach((field) => {
        const source = field.closest('form')?.querySelector(`[name="${field.dataset.showWhenFilled}"]`);
        if (!source) return;

        const toggleField = () => field.classList.toggle('hidden', source.value === '');

        source.addEventListener('input', toggleField);
        source.addEventListener('change', toggleField);
        toggleField();
    });
};

const initFilteredSelects = () => {
    document.querySelectorAll('select[data-filter-dead-when]').forEach((select) => {
        const source = select.closest('form')?.querySelector(`[name="${select.dataset.filterDeadWhen}"]`);
        if (!source) return;

        const filterOptions = () => {
            const requiresDeadAnimal = source.value === select.dataset.filterDeadValue;
            let hasVisibleSelection = false;

            [...select.options].forEach((option) => {
                const visible = !requiresDeadAnimal || option.value === '' || option.dataset.optionStatus === 'dead';
                option.hidden = !visible;
                option.disabled = !visible;
                if (visible && option.selected) hasVisibleSelection = true;
            });

            if (!hasVisibleSelection) {
                select.value = '';
            }
        };

        source.addEventListener('change', filterOptions);
        filterOptions();
    });

    document.querySelectorAll('select[data-depends-on]').forEach((select) => {
        const parent = select.closest('form')?.querySelector(`[name="${select.dataset.dependsOn}"]`);
        if (!parent) return;

        const filterOptions = () => {
            const selectedParent = parent.value;
            let hasVisibleSelection = false;

            [...select.options].forEach((option) => {
                const parentValue = option.dataset.parentValue;
                const visible = !parentValue || parentValue === selectedParent;
                option.hidden = !visible;
                option.disabled = !visible;
                if (visible && option.selected) hasVisibleSelection = true;
            });

            if (!hasVisibleSelection) {
                select.value = '';
            }
        };

        parent.addEventListener('change', filterOptions);
        filterOptions();
    });
};

const initCurrentPenPreview = () => {
    const movementAnimalSelect = document.querySelector('select[name="animal_id"]');
    const currentPenPreview = document.querySelector('[data-current-pen-preview]');

    if (!movementAnimalSelect || !currentPenPreview) {
        return;
    }

    const syncCurrentPen = () => {
        const selected = movementAnimalSelect.selectedOptions[0];
        currentPenPreview.value = selected?.dataset.currentPenLabel || 'Belum ada koloni aktif';
    };

    movementAnimalSelect.addEventListener('change', syncCurrentPen);
    syncCurrentPen();
};

const addMonthsNoOverflow = (date, months) => {
    const originalDay = date.getDate();
    const target = new Date(date.getFullYear(), date.getMonth() + months, 1);
    const lastDay = new Date(target.getFullYear(), target.getMonth() + 1, 0).getDate();

    target.setDate(Math.min(originalDay, lastDay));

    return target;
};

const formatDate = (date) => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
};

const initBirthEstimatePreview = () => {
    const matingDateInput = document.querySelector('input[name="mating_date"]');
    const birthEstimatePreview = document.querySelector('[data-birth-estimate-preview]');

    if (!matingDateInput || !birthEstimatePreview) {
        return;
    }

    const placeholder = birthEstimatePreview.value || 'Otomatis dari tanggal kawin + 5 bulan 10 hari';

    const syncBirthEstimate = () => {
        if (!matingDateInput.value) {
            birthEstimatePreview.value = placeholder;
            return;
        }

        const date = addMonthsNoOverflow(new Date(`${matingDateInput.value}T00:00:00`), 5);
        date.setDate(date.getDate() + 10);
        birthEstimatePreview.value = formatDate(date);
    };

    matingDateInput.addEventListener('input', syncBirthEstimate);
    matingDateInput.addEventListener('change', syncBirthEstimate);
    syncBirthEstimate();
};

export const initAdminForms = () => {
    initSelectLists();
    initConditionalFields();
    initFilteredSelects();
    initCurrentPenPreview();
    initBirthEstimatePreview();
};

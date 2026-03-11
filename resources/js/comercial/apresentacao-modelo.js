const repeaterConfigs = {
    desafios: {
        type: 'card',
        baseName: 'layout[desafios][items]',
        labels: {
            title: 'Titulo do desafio',
            description: 'Descricao',
        },
        placeholders: {
            title: 'Titulo do desafio',
            description: 'Descricao',
        },
    },
    solucoes: {
        type: 'card',
        baseName: 'layout[solucoes][cards]',
        labels: {
            title: 'Titulo',
            description: 'Descricao',
        },
        placeholders: {
            title: 'Titulo',
            description: 'Descricao',
        },
    },
    diferenciais: {
        type: 'card',
        baseName: 'layout[diferenciais][cards]',
        labels: {
            title: 'Titulo',
            description: 'Descricao',
        },
        placeholders: {
            title: 'Titulo',
            description: 'Descricao',
        },
    },
    processo: {
        type: 'card',
        baseName: 'layout[processo][items]',
        labels: {
            title: 'Titulo da etapa',
            description: 'Descricao',
        },
        placeholders: {
            title: 'Etapa 1',
            description: 'Descricao',
        },
    },
    palestras: {
        type: 'row',
        baseName: 'layout[palestras][items]',
        placeholders: {
            title: 'Janeiro',
            description: 'Seguranca no trabalho',
        },
    },
    exames: {
        type: 'exam',
        baseName: 'layout[exames_ocupacionais][items]',
        placeholders: {
            title: 'Hemograma Completo',
            value: 'R$ 100,00',
        },
    },
    investimento: {
        type: 'offer',
        baseName: 'layout[investimento][cards]',
        labels: {
            title: 'Titulo',
            value: 'Valor',
            description: 'Descricao',
            items: 'Itens internos',
        },
        placeholders: {
            title: 'Nome do card',
            value: 'R$ 350,00',
            description: 'Texto complementar',
            items: 'Item 1\nItem 2',
        },
    },
};

function updateRepeaterNames(container, config) {
    const items = Array.from(container.querySelectorAll('[data-repeater-item]'));

    items.forEach((item, index) => {
        item.querySelectorAll('[data-field-input]').forEach((input) => {
            const field = input.getAttribute('data-field-input');
            input.name = `${config.baseName}[${index}][${field}]`;

            if (config.placeholders?.[field] && !input.getAttribute('placeholder')) {
                input.setAttribute('placeholder', config.placeholders[field]);
            }
        });

        item.querySelectorAll('[data-field-hidden]').forEach((input) => {
            const field = input.getAttribute('data-field-hidden');
            input.name = `${config.baseName}[${index}][${field}]`;
        });
    });

    container.dataset.nextIndex = String(items.length);
}

function createRepeaterItem(type) {
    const templateId = {
        card: 'repeater-card-template',
        row: 'repeater-row-template',
        exam: 'repeater-exam-template',
        offer: 'repeater-offer-template',
    }[type];

    const template = document.getElementById(templateId);
    if (!template) return null;

    return template.content.firstElementChild.cloneNode(true);
}

function addRepeaterItem(key) {
    const container = document.querySelector(`[data-repeater="${key}"]`);
    const config = repeaterConfigs[key];
    if (!container || !config) return;

    const item = createRepeaterItem(config.type);
    if (!item) return;

    if (config.type === 'card' || config.type === 'offer') {
        item.querySelectorAll('[data-field-label]').forEach((labelEl) => {
            const field = labelEl.getAttribute('data-field-label');
            labelEl.textContent = config.labels?.[field] || field;
        });
    }

    container.appendChild(item);
    updateRepeaterNames(container, config);
}

function initRepeaters() {
    Object.entries(repeaterConfigs).forEach(([key, config]) => {
        const container = document.querySelector(`[data-repeater="${key}"]`);
        if (!container) return;
        updateRepeaterNames(container, config);
    });

    document.querySelectorAll('[data-repeater-add]').forEach((button) => {
        button.addEventListener('click', () => addRepeaterItem(button.getAttribute('data-repeater-add')));
    });

    document.addEventListener('click', (event) => {
        const removeButton = event.target.closest('[data-repeater-remove]');
        if (!removeButton) return;

        const item = removeButton.closest('[data-repeater-item]');
        const container = removeButton.closest('[data-repeater]');
        if (!item || !container) return;

        item.remove();

        const key = container.getAttribute('data-repeater');
        if (repeaterConfigs[key]) {
            updateRepeaterNames(container, repeaterConfigs[key]);
        }
    });
}

function initSyncFields() {
    const tituloSource = document.querySelector('[data-sync-titulo]');
    const tituloTarget = document.querySelector('[data-sync-titulo-target]');
    if (tituloSource && tituloTarget) {
        const sync = () => {
            tituloTarget.value = tituloSource.value;
        };
        tituloSource.addEventListener('input', sync);
        sync();
    }

}

function initManualTables() {
    const wrapper = document.getElementById('manual-tables-wrapper');
    const orderWrap = document.getElementById('manual-tables-order');
    const btnAdd = document.getElementById('btn-add-manual-table');
    const template = document.getElementById('manual-table-template');

    if (!wrapper || !orderWrap || !btnAdd || !template) return;

    let draggedTable = null;

    function syncTablesOrder() {
        orderWrap.innerHTML = '';
        wrapper.querySelectorAll('.manual-table').forEach((table) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'manual_tables_order[]';
            input.value = table.dataset.tableId || '';
            orderWrap.appendChild(input);
        });
    }

    function syncRowsOrder(table) {
        const rowsWrap = table.querySelector('.manual-rows-order');
        if (!rowsWrap) return;

        rowsWrap.innerHTML = '';
        table.querySelectorAll('.manual-row').forEach((row) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = `manual_tables[${table.dataset.tableId}][rows_order][]`;
            input.value = row.dataset.rowId || '';
            rowsWrap.appendChild(input);
        });
    }

    function reindexColumns(table) {
        const columns = table.querySelectorAll('.manual-column');

        columns.forEach((column, idx) => {
            column.dataset.colIndex = String(idx);
        });

        table.querySelectorAll('.manual-row').forEach((row) => {
            const cellsWrap = row.querySelector('.manual-row-cells');
            row.querySelectorAll('.manual-cell').forEach((cell, idx) => {
                cell.dataset.colIndex = String(idx);
            });

            if (cellsWrap) {
                cellsWrap.style.gridTemplateColumns = `repeat(${Math.max(columns.length, 1)}, minmax(0, 1fr))`;
            }
        });
    }

    function addColumn(table) {
        const columnsWrap = table.querySelector('.manual-columns');
        if (!columnsWrap) return;

        const colIndex = columnsWrap.querySelectorAll('.manual-column').length;
        const col = document.createElement('div');
        col.className = 'manual-column col-md-4 d-flex gap-2';
        col.dataset.colIndex = String(colIndex);
        col.innerHTML = `
            <input type="text" class="form-control" name="manual_tables[${table.dataset.tableId}][columns][]" placeholder="Coluna">
            <button type="button" class="btn btn-outline-success btn-remove-col">Check</button>
        `;
        columnsWrap.appendChild(col);

        table.querySelectorAll('.manual-row').forEach((row) => {
            const cellsWrap = row.querySelector('.manual-row-cells');
            if (!cellsWrap) return;

            const cell = document.createElement('div');
            cell.className = 'manual-cell';
            cell.dataset.colIndex = String(colIndex);
            cell.innerHTML = `
                <input type="text" class="form-control" name="manual_tables[${table.dataset.tableId}][rows][${row.dataset.rowId}][]" placeholder="Valor">
            `;
            cellsWrap.appendChild(cell);
        });

        reindexColumns(table);
    }

    function addRow(table) {
        const rowsWrap = table.querySelector('.manual-rows');
        const columns = table.querySelectorAll('.manual-column');
        if (!rowsWrap) return;

        const rowId = `new_${Date.now()}`;
        const cols = Math.max(columns.length, 1);
        const row = document.createElement('div');
        row.className = 'manual-row border rounded-4 p-3 bg-light-subtle';
        row.dataset.rowId = rowId;

        const cells = Array.from({ length: cols }).map(() => {
            return `
                <div class="manual-cell">
                    <input type="text" class="form-control" name="manual_tables[${table.dataset.tableId}][rows][${rowId}][]" placeholder="Valor">
                </div>
            `;
        }).join('');

        row.innerHTML = `
            <div class="manual-row-cells d-grid gap-2" style="grid-template-columns: repeat(${cols}, minmax(0, 1fr));">${cells}</div>
            <div class="d-flex justify-content-end mt-2">
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">Remover linha</button>
            </div>
        `;
        rowsWrap.appendChild(row);
        syncRowsOrder(table);
    }

    function initTable(table) {
        table.addEventListener('dragstart', (event) => {
            draggedTable = table;
            event.dataTransfer.effectAllowed = 'move';
        });

        table.addEventListener('dragover', (event) => {
            event.preventDefault();
        });

        table.addEventListener('drop', (event) => {
            event.preventDefault();
            if (!draggedTable || draggedTable === table) return;

            const tables = Array.from(wrapper.querySelectorAll('.manual-table'));
            const draggedIndex = tables.indexOf(draggedTable);
            const targetIndex = tables.indexOf(table);

            if (draggedIndex < targetIndex) {
                wrapper.insertBefore(draggedTable, table.nextSibling);
            } else {
                wrapper.insertBefore(draggedTable, table);
            }

            syncTablesOrder();
        });

        table.querySelector('.btn-remove-table')?.addEventListener('click', () => {
            table.remove();
            syncTablesOrder();
        });

        table.querySelector('.btn-add-col')?.addEventListener('click', () => addColumn(table));
        table.querySelector('.btn-add-row')?.addEventListener('click', () => addRow(table));

        table.addEventListener('click', (event) => {
            const removeCol = event.target.closest('.btn-remove-col');
            if (removeCol) {
                const column = removeCol.closest('.manual-column');
                if (!column) return;
                const colIndex = Number(column.dataset.colIndex || 0);
                column.remove();
                table.querySelectorAll('.manual-row').forEach((row) => {
                    row.querySelector(`.manual-cell[data-col-index="${colIndex}"]`)?.remove();
                });
                reindexColumns(table);
                return;
            }

            const removeRow = event.target.closest('.btn-remove-row');
            if (removeRow) {
                removeRow.closest('.manual-row')?.remove();
                syncRowsOrder(table);
            }
        });

        syncRowsOrder(table);
        reindexColumns(table);
    }

    btnAdd.addEventListener('click', () => {
        const id = `new_${Date.now()}`;
        const holder = document.createElement('div');
        holder.innerHTML = template.innerHTML.replaceAll('__ID__', id);
        const table = holder.firstElementChild;
        if (!table) return;

        wrapper.appendChild(table);
        initTable(table);
        syncTablesOrder();
        addColumn(table);
        addColumn(table);
        addColumn(table);
        addRow(table);
    });

    wrapper.querySelectorAll('.manual-table').forEach(initTable);
    syncTablesOrder();
}

function initSectionNavigation() {
    const links = Array.from(document.querySelectorAll('[data-section-nav]'));
    const sections = links
        .map((link) => document.getElementById(link.getAttribute('data-section-nav')))
        .filter(Boolean);

    if (!links.length || !sections.length) return;

    const setActive = (id) => {
        links.forEach((link) => {
            link.classList.toggle('is-active', link.getAttribute('data-section-nav') === id);
        });
    };

    const fromHash = window.location.hash.replace('#', '');
    setActive(fromHash || sections[0].id);

    const observer = new IntersectionObserver((entries) => {
        const visible = entries
            .filter((entry) => entry.isIntersecting)
            .sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];

        if (visible?.target?.id) {
            setActive(visible.target.id);
        }
    }, {
        rootMargin: '-20% 0px -65% 0px',
        threshold: [0.15, 0.35, 0.6],
    });

    sections.forEach((section) => observer.observe(section));

    window.addEventListener('hashchange', () => {
        const id = window.location.hash.replace('#', '');
        if (id) {
            setActive(id);
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initRepeaters();
    initSyncFields();
    initManualTables();
    initSectionNavigation();
});

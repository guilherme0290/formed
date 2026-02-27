import './bootstrap';

import Alpine from 'alpinejs';

import Sortable from 'sortablejs';

window.Alpine = Alpine;

Alpine.start();

window.Sortable = Sortable;

if (!window.Swal) {
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
    script.defer = true;
    document.head.appendChild(script);
}

window.renderLucideIcons = (root = document) => {
    if (!window.lucide || typeof window.lucide.createIcons !== 'function') return false;
    const hasIcons = (root || document).querySelector?.('[data-lucide]');
    if (!hasIcons) return false;

    window.lucide.createIcons({
        attrs: {
            'stroke-width': 1.8,
        },
    });

    return true;
};

const ensureLucideLoaded = () => {
    if (document.querySelector('[data-lucide]') === null) return;

    if (window.renderLucideIcons()) return;

    if (document.getElementById('lucide-cdn-script')) return;

    const script = document.createElement('script');
    script.id = 'lucide-cdn-script';
    script.src = 'https://unpkg.com/lucide@latest/dist/umd/lucide.min.js';
    script.defer = true;
    script.onload = () => window.renderLucideIcons?.();
    document.head.appendChild(script);
};

window.uiAlert = (message, options = {}) => {
    if (window.Swal) {
        return window.Swal.fire({
            icon: options.icon || 'info',
            title: options.title || 'Atenção',
            text: message,
            confirmButtonText: options.confirmText || 'OK',
        });
    }

    alert(message);
    return Promise.resolve();
};

window.uiConfirm = (message, options = {}) => {
    if (window.Swal) {
        return window.Swal.fire({
            icon: options.icon || 'warning',
            title: options.title || 'Confirmar ação',
            text: message,
            showCancelButton: true,
            confirmButtonText: options.confirmText || 'Confirmar',
            cancelButtonText: options.cancelText || 'Cancelar',
        }).then((result) => result.isConfirmed);
    }

    return Promise.resolve(confirm(message));
};

window.initTailwindAutocomplete = (inputRef, listRef, options = [], config = {}) => {
    const input = typeof inputRef === 'string' ? document.getElementById(inputRef) : inputRef;
    const list = typeof listRef === 'string' ? document.getElementById(listRef) : listRef;
    if (!input || !list) return;

    const maxItems = Number(config.maxItems || 10);
    const dataset = Array.from(new Set((Array.isArray(options) ? options : [])
        .map((value) => (value ?? '').toString().trim())
        .filter(Boolean)));
    let filteredItems = [];
    let activeIndex = -1;

    const normalize = (value) => (value || '')
        .toString()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase();

    const closeList = () => {
        list.classList.add('hidden');
        activeIndex = -1;
    };

    const setActive = (nextIndex) => {
        const buttons = Array.from(list.querySelectorAll('[data-autocomplete-item="1"]'));
        buttons.forEach((btn, idx) => {
            const isActive = idx === nextIndex;
            btn.classList.toggle('bg-blue-50', isActive);
            btn.classList.toggle('text-blue-700', isActive);
            btn.classList.toggle('font-semibold', isActive);
            if (isActive) {
                btn.scrollIntoView({ block: 'nearest' });
            }
        });
    };

    const selectItem = (value) => {
        input.value = value;
        closeList();
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
    };

    const render = (items) => {
        list.innerHTML = '';
        if (!items.length) {
            closeList();
            return;
        }

        filteredItems = items;
        activeIndex = 0;

        items.forEach((value, idx) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.setAttribute('data-autocomplete-item', '1');
            btn.className = 'w-full text-left px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 transition';
            btn.textContent = value;
            btn.addEventListener('mouseenter', () => {
                activeIndex = idx;
                setActive(activeIndex);
            });
            btn.addEventListener('click', () => {
                selectItem(value);
            });
            list.appendChild(btn);
        });

        list.classList.remove('hidden');
        setActive(activeIndex);
    };

    const filterOptions = () => {
        const query = normalize(input.value);
        const filtered = dataset
            .filter((value) => !query || normalize(value).includes(query))
            .slice(0, maxItems);
        render(filtered);
    };

    input.addEventListener('input', () => {
        filterOptions();
    });

    input.addEventListener('focus', () => {
        filterOptions();
    });

    input.addEventListener('keydown', (event) => {
        if (list.classList.contains('hidden')) return;
        if (!filteredItems.length) return;

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            activeIndex = (activeIndex + 1) % filteredItems.length;
            setActive(activeIndex);
            return;
        }

        if (event.key === 'ArrowUp') {
            event.preventDefault();
            activeIndex = (activeIndex - 1 + filteredItems.length) % filteredItems.length;
            setActive(activeIndex);
            return;
        }

        if (event.key === 'Enter') {
            event.preventDefault();
            const selected = filteredItems[activeIndex] ?? filteredItems[0];
            if (selected) {
                selectItem(selected);
            }
            return;
        }

        if (event.key === 'Escape') {
            event.preventDefault();
            closeList();
        }
    });

    document.addEventListener('click', (event) => {
        if (!list.contains(event.target) && event.target !== input) {
            closeList();
        }
    });
};

document.addEventListener('submit', async (e) => {
    const form = e.target.closest('form[data-confirm]');
    if (!form) return;
    e.preventDefault();
    const message = form.dataset.confirm || 'Deseja confirmar esta ação?';
    const ok = await window.uiConfirm(message, {
        title: form.dataset.confirmTitle || 'Confirmar ação',
        confirmText: form.dataset.confirmOk || 'Confirmar',
        cancelText: form.dataset.confirmCancel || 'Cancelar',
    });
    if (ok) {
        form.submit();
    }
});

document.addEventListener('click', function (e) {
    const senhaAtalho = e.target.closest('[data-only-my-password]');
    if (senhaAtalho) {
        try {
            localStorage.setItem('acessosOnlyMe', '1');
        } catch (err) {
            // ignore storage failures
        }
        return;
    }
    const btn = e.target.closest('[data-funcao-open-modal]');
    if (!btn) return;

    const modalId = btn.getAttribute('data-funcao-open-modal');

    window.dispatchEvent(new CustomEvent('open-funcao-modal', {
        detail: { modalId }
    }));
});

const normalizeText = (value) => (value || '')
    .toString()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase();

const hasNoPermissionHint = (el) => {
    if (!el) return false;
    const title = normalizeText(el.getAttribute('title') || '');
    const dataHint = normalizeText(el.getAttribute('data-permission-msg') || '');
    return title.includes('sem permissao') || dataHint.includes('sem permissao');
};

const isPermissionBlocked = (el) => {
    if (!el) return false;
    if (el.getAttribute('data-no-permission') === '1') return true;
    if (el.getAttribute('aria-disabled') === 'true') return true;
    if (hasNoPermissionHint(el)) return true;
    if (el.tagName === 'A' && (el.getAttribute('href') || '').trim().toLowerCase() === 'javascript:void(0)') return true;
    return false;
};

const markNoPermissionElements = () => {
    document.querySelectorAll('a,button,[role="button"]').forEach((el) => {
        if (!isPermissionBlocked(el)) return;
        el.setAttribute('data-no-permission', '1');
        el.classList.add('opacity-60', 'cursor-not-allowed');
    });
};

document.addEventListener('DOMContentLoaded', () => {
    markNoPermissionElements();
    ensureLucideLoaded();
    setTimeout(() => window.renderLucideIcons?.(), 150);
});

document.addEventListener('click', function (e) {
    const blocked = e.target.closest('[data-no-permission="1"]');
    if (!blocked) return;

    e.preventDefault();
    e.stopPropagation();

    window.uiAlert('Usuario sem permissao.', {
        title: 'Acesso negado',
        icon: 'warning',
        confirmText: 'OK',
    });
}, true);

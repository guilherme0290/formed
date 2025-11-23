import './bootstrap';

import Alpine from 'alpinejs';

import Sortable from 'sortablejs';

window.Alpine = Alpine;

Alpine.start();

window.Sortable = Sortable;

document.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-funcao-open-modal]');
    if (!btn) return;

    const modalId = btn.getAttribute('data-funcao-open-modal');

    window.dispatchEvent(new CustomEvent('open-funcao-modal', {
        detail: { modalId }
    }));
});

(() => {
    document.querySelectorAll('[data-repeater]').forEach(group => {
        const rows = group.querySelector('[data-rows]');
        let index = Math.max(-1, ...Array.from(rows.querySelectorAll('[name]'), field => Number(field.name.match(/\[(\d+)\]/)?.[1] ?? -1))) + 1;
        group.addEventListener('click', event => {
            const button = event.target.closest('button');
            if (!button) return;
            if (button.hasAttribute('data-add')) {
                const template = group.querySelector('template');
                const fragment = template.content.cloneNode(true);
                fragment.querySelectorAll('[name], [id], label[for]').forEach(element => {
                    ['name', 'id', 'for'].forEach(attribute => {
                        if (element.hasAttribute(attribute)) element.setAttribute(attribute, element.getAttribute(attribute).replaceAll('__INDEX__', index));
                    });
                });
                index++;
                rows.append(fragment);
                rows.lastElementChild.querySelector('input,select,textarea')?.focus();
                return;
            }
            const row = button.closest('[data-row]');
            if (!row) return;
            if (button.hasAttribute('data-remove')) {
                row.remove();
                group.querySelector('[data-add]').focus();
            }
            if (button.dataset.move === 'up' && row.previousElementSibling) rows.insertBefore(row, row.previousElementSibling);
            if (button.dataset.move === 'down' && row.nextElementSibling) rows.insertBefore(row.nextElementSibling, row);
        });
    });
})();

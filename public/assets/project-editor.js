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

(() => {
    const status = document.querySelector('[data-photo-status]');
    const grid = document.querySelector('.project-photo-grid');
    if (!grid) return;
    const refreshCards = () => {
        const cards = Array.from(grid.querySelectorAll('[data-photo-card]'));
        const occupied = cards.filter(card => !card.querySelector('[data-photo-preview]').hidden);
        const emptyCards = cards.filter(card => card.querySelector('[data-photo-preview]').hidden);
        [...occupied, ...emptyCards].forEach((card, index) => {
            grid.append(card);
            card.hidden = index > occupied.length;
            card.querySelector('[data-photo-input]').name = `images[${index}]`;
            card.querySelector('[data-keep-photo]').name = `keep_images[${index}]`;
            card.querySelector('[data-photo-picker]').setAttribute('aria-label', index < occupied.length ? `Replace project image ${index + 1}` : 'Add project image');
            card.querySelector('[data-photo-delete]').setAttribute('aria-label', `Remove project image ${index + 1}`);
        });
    };
    grid.querySelectorAll('[data-photo-card]').forEach((card) => {
        const input = card.querySelector('[data-photo-input]');
        const keep = card.querySelector('[data-keep-photo]');
        const picker = card.querySelector('[data-photo-picker]');
        const preview = card.querySelector('[data-photo-preview]');
        const empty = card.querySelector('[data-photo-empty]');
        const hint = card.querySelector('[data-photo-replace]');
        const remove = card.querySelector('[data-photo-delete]');
        const caption = card.querySelector('[data-photo-caption]');
        let objectUrl;
        let selectedFile;
        const release = () => { if (objectUrl) URL.revokeObjectURL(objectUrl); objectUrl = null; };
        picker.addEventListener('click', () => input.click());
        input.addEventListener('change', () => {
            const file = input.files[0];
            if (!file) return;
            if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 20 * 1024 * 1024) {
                input.value = '';
                if (selectedFile) {
                    const files = new DataTransfer();
                    files.items.add(selectedFile);
                    input.files = files.files;
                }
                status.textContent = 'Choose a JPEG, PNG or WebP image no larger than 20 MB.';
                return;
            }
            release();
            selectedFile = file;
            objectUrl = URL.createObjectURL(file);
            preview.src = objectUrl;
            preview.alt = file.name;
            preview.hidden = false;
            empty.hidden = true;
            hint.hidden = false;
            remove.hidden = false;
            keep.disabled = true;
            caption.textContent = file.name;
            refreshCards();
            status.textContent = 'Image selected. Save the project to apply changes.';
        });
        remove.addEventListener('click', () => {
            release();
            selectedFile = null;
            input.value = '';
            keep.disabled = true;
            preview.removeAttribute('src');
            preview.hidden = true;
            empty.hidden = false;
            hint.hidden = true;
            remove.hidden = true;
            caption.textContent = 'JPEG, PNG or WebP';
            refreshCards();
            status.textContent = 'Image removed from this draft. Save the project to apply changes.';
            grid.querySelector('[data-photo-card]:not([hidden]) [data-photo-empty]:not([hidden])').closest('button').focus();
        });
        window.addEventListener('pagehide', release, {once: true});
    });
    refreshCards();
})();

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
    document.querySelectorAll('[data-photo-card]').forEach((card, index) => {
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
            picker.setAttribute('aria-label', `Replace project image ${index + 1}`);
            status.textContent = `Image ${index + 1} selected. Save the project to apply changes.`;
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
            picker.setAttribute('aria-label', `Add project image ${index + 1}`);
            status.textContent = `Image ${index + 1} removed from this draft. Save the project to apply changes.`;
            picker.focus();
        });
        window.addEventListener('pagehide', release, {once: true});
    });
})();

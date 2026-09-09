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
    const form = document.querySelector('[data-project-upload]');
    if (!form) return;
    form.addEventListener('submit', async event => {
        event.preventDefault();
        const button = form.querySelector('button');
        const status = form.querySelector('[data-upload-status]');
        button.disabled = true;
        status.textContent = 'Uploading…';
        try {
            const response = await fetch(form.action, {method:'POST', body:new FormData(form), headers:{Accept:'application/json'}, credentials:'same-origin'});
            const data = await response.json();
            if (!response.ok) throw new Error(data.errors ? Object.values(data.errors).flat().join(' ') : 'Upload failed. Check your session and file, then retry.');
            if (data.selectable) {
                const addOption = root => root.querySelectorAll('select').forEach(select => {
                    const name = select.name;
                    const image = data.mime.startsWith('image/');
                    const gallery = name.startsWith('gallery[') && name.endsWith('[media_id]');
                    const imageField = ['cover_media_id','before_media_id','after_media_id','panorama_media_id'].includes(name) || (name.startsWith('timeline[') && name.endsWith('[media_id]'));
                    if ((gallery && (image || data.mime === 'video/mp4')) || (imageField && image) || (name === 'document_ids[]' && data.mime === 'application/pdf')) {
                        select.add(new Option(data.title, data.id));
                    }
                });
                addOption(document);
                document.querySelectorAll('[data-repeater] template').forEach(template => addOption(template.content));
                status.textContent = data.mime === 'application/pdf' ? 'Document uploaded. It is available in the Media library.' : 'Uploaded. Select this file in the gallery above, then save your project draft.';
            } else {
                status.textContent = 'Uploaded privately. Review and publish it in Media library before adding it to the public project.';
            }
            form.querySelector('[name=file]').value = '';
        } catch (error) {
            status.textContent = error.message || 'Upload failed. Your project edits are still here.';
        } finally {
            button.disabled = false;
        }
    });
})();

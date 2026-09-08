(() => {
    const icon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true" focusable="false"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/><path class="password-eye-slash" d="m3 3 18 18"/></svg>';

    document.querySelectorAll('input[type="password"]').forEach((input, index) => {
        if (input.closest('.password-field')) return;
        if (!input.id) input.id = `password-field-${index}`;

        const wrapper = document.createElement('div');
        wrapper.className = 'password-field';
        input.before(wrapper);
        wrapper.append(input);

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'password-toggle';
        button.setAttribute('aria-controls', input.id);
        button.innerHTML = icon;
        const fieldLabel = input.labels?.[0]?.textContent.trim() || 'password';

        const setVisible = (visible) => {
            input.type = visible ? 'text' : 'password';
            button.setAttribute('aria-pressed', String(visible));
            button.setAttribute('aria-label', `${visible ? 'Hide' : 'Show'} password: ${fieldLabel}`);
            button.title = visible ? 'Hide password' : 'Show password';
        };

        setVisible(false);
        button.addEventListener('click', () => setVisible(input.type === 'password'));
        input.form?.addEventListener('submit', () => setVisible(false));
        window.addEventListener('pagehide', () => setVisible(false));
        wrapper.append(button);
    });
})();

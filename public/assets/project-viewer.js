(() => {
    const range = document.querySelector('#stage-range');
    if (range) {
        const control = range.closest('.stage-control');
        const stages = Array.from(document.querySelectorAll('.project-timeline > li'));
        const output = control.querySelector('output');
        control.hidden = false;
        range.addEventListener('input', () => {
            stages.forEach((stage, index) => { stage.hidden = index !== Number(range.value); });
            output.textContent = stages[Number(range.value)].querySelector('h3').textContent;
        });
        control.querySelector('[data-all-stages]').addEventListener('click', () => {
            stages.forEach(stage => { stage.hidden = false; });
            output.textContent = 'All stages';
        });
        output.textContent = 'All stages';
    }
    const filter = document.querySelector('#gallery-category');
    if (filter) {
        filter.closest('.gallery-control').hidden = false;
        filter.addEventListener('change', () => {
            document.querySelectorAll('.project-gallery figure').forEach(item => {
                item.hidden = Boolean(filter.value && item.dataset.category !== filter.value);
            });
        });
    }
})();

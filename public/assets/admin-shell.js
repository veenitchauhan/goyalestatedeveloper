(() => {
    const menu = document.querySelector('.sidebar-menu');
    if (!menu) return;
    const mobile = window.matchMedia('(max-width: 760px)');
    const updateMenu = () => { menu.open = !mobile.matches; };
    updateMenu();
    mobile.addEventListener('change', updateMenu);
})();

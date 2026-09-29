const resetPosition = (panel) => {
    panel.style.removeProperty('left');
    panel.style.removeProperty('position');
    panel.style.removeProperty('right');
    panel.style.removeProperty('top');
    panel.style.removeProperty('visibility');
    panel.style.removeProperty('z-index');
};

const positionMenu = (menu) => {
    const trigger = menu.querySelector('summary');
    const panel = menu.querySelector('.admin-row-menu-panel');
    if (!trigger || !panel || !menu.open) return;

    panel.style.position = 'fixed';
    panel.style.right = 'auto';
    panel.style.visibility = 'hidden';
    panel.style.zIndex = '80';

    const triggerRect = trigger.getBoundingClientRect();
    const panelRect = panel.getBoundingClientRect();
    const gap = 6;
    const edge = 8;
    const top = triggerRect.bottom + gap + panelRect.height > window.innerHeight - edge
        ? Math.max(edge, triggerRect.top - panelRect.height - gap)
        : triggerRect.bottom + gap;
    const left = Math.max(edge, Math.min(triggerRect.right - panelRect.width, window.innerWidth - panelRect.width - edge));

    panel.style.left = `${left}px`;
    panel.style.top = `${top}px`;
    panel.style.visibility = 'visible';
};

export const initRowActions = () => {
    const menus = Array.from(document.querySelectorAll('[data-row-menu]'));
    if (menus.length === 0) return;

    document.documentElement.classList.add('has-row-actions');

    const repositionOpenMenus = () => menus.filter((menu) => menu.open).forEach(positionMenu);

    menus.forEach((menu) => {
        menu.addEventListener('toggle', () => {
            const panel = menu.querySelector('.admin-row-menu-panel');
            if (!panel) return;

            if (!menu.open) {
                resetPosition(panel);
                return;
            }

            menus.filter((otherMenu) => otherMenu !== menu && otherMenu.open).forEach((otherMenu) => {
                otherMenu.open = false;
            });
            positionMenu(menu);
        });
    });

    document.addEventListener('click', (event) => {
        menus.filter((menu) => menu.open && !menu.contains(event.target)).forEach((menu) => {
            menu.open = false;
        });
    });

    window.addEventListener('resize', repositionOpenMenus);
    document.addEventListener('scroll', repositionOpenMenus, true);
};

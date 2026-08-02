export const initMobileSidebar = () => {
    const sidebar = document.querySelector('[data-mobile-sidebar]');
    const main = document.querySelector('[data-admin-main]');
    const openButtons = document.querySelectorAll('[data-mobile-sidebar-open]');
    const closeButtons = document.querySelectorAll('[data-mobile-sidebar-close]');
    let lastFocusedElement = null;

    if (!sidebar || openButtons.length === 0) {
        return;
    }

    const setExpanded = (expanded) => {
        openButtons.forEach((button) => {
            button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        });
    };

    const openSidebar = () => {
        lastFocusedElement = document.activeElement;
        sidebar.classList.remove('hidden');
        sidebar.setAttribute('aria-hidden', 'false');
        main?.setAttribute('inert', '');
        document.body.style.overflow = 'hidden';
        setExpanded(true);
        sidebar.querySelector('[data-mobile-sidebar-close]')?.focus();
    };

    const closeSidebar = () => {
        sidebar.classList.add('hidden');
        sidebar.setAttribute('aria-hidden', 'true');
        main?.removeAttribute('inert');
        document.body.style.overflow = '';
        setExpanded(false);
        if (lastFocusedElement instanceof HTMLElement) {
            lastFocusedElement.focus();
        }
    };

    openButtons.forEach((button) => {
        button.addEventListener('click', openSidebar);
    });

    closeButtons.forEach((button) => {
        button.addEventListener('click', closeSidebar);
    });

    sidebar.querySelectorAll('[data-mobile-sidebar-link]').forEach((link) => {
        link.addEventListener('click', closeSidebar);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !sidebar.classList.contains('hidden')) {
            closeSidebar();
        }
    });
};

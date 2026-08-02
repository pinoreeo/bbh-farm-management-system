export const initMobileSidebar = () => {
    const sidebar = document.querySelector('[data-mobile-sidebar]');
    const openButtons = document.querySelectorAll('[data-mobile-sidebar-open]');
    const closeButtons = document.querySelectorAll('[data-mobile-sidebar-close]');

    if (!sidebar || openButtons.length === 0) {
        return;
    }

    const setExpanded = (expanded) => {
        openButtons.forEach((button) => {
            button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        });
    };

    const openSidebar = () => {
        sidebar.classList.remove('hidden');
        sidebar.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        setExpanded(true);
    };

    const closeSidebar = () => {
        sidebar.classList.add('hidden');
        sidebar.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        setExpanded(false);
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

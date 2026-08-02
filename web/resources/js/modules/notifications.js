export const initNotificationMenu = () => {
    document.querySelectorAll('[data-notification-menu]').forEach((menu) => {
        const toggle = menu.querySelector('[data-notification-toggle]');
        const panel = menu.querySelector('[data-notification-panel]');

        if (!toggle || !panel) return;

        const close = () => {
            panel.setAttribute('hidden', '');
            toggle.setAttribute('aria-expanded', 'false');
        };

        const open = () => {
            panel.removeAttribute('hidden');
            toggle.setAttribute('aria-expanded', 'true');
        };

        toggle.addEventListener('click', (event) => {
            event.stopPropagation();
            panel.hasAttribute('hidden') ? open() : close();
        });

        panel.addEventListener('click', (event) => {
            event.stopPropagation();
        });

        document.addEventListener('click', (event) => {
            if (!menu.contains(event.target)) close();
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') close();
        });
    });
};

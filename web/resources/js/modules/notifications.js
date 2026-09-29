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

export const initProfileMenu = () => {
    document.querySelectorAll('[data-profile-menu]').forEach((menu) => {
        const toggle = menu.querySelector('[data-profile-toggle]');
        const panel = menu.querySelector('[data-profile-panel]');

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

        panel.addEventListener('click', (event) => event.stopPropagation());
        document.addEventListener('click', (event) => {
            if (!menu.contains(event.target)) close();
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') close();
        });
    });
};

export const initNotificationFeed = () => {
    document.querySelectorAll('[data-notification-feed]').forEach((feed) => {
        const items = [...feed.querySelectorAll('[data-notification-item]')];
        const empty = feed.querySelector('[data-notification-empty]');

        feed.querySelectorAll('[data-notification-filter]').forEach((tab) => {
            tab.addEventListener('click', () => {
                const filter = tab.dataset.notificationFilter || 'all';
                let shown = 0;

                feed.querySelectorAll('[data-notification-filter]').forEach((item) => {
                    item.setAttribute('aria-pressed', String(item === tab));
                });

                items.forEach((item) => {
                    const visible = filter === 'all' || item.dataset.notificationState === filter;
                    item.hidden = !visible;
                    if (visible) shown += 1;
                });

                if (empty) empty.hidden = shown > 0;
            });
        });
    });
};

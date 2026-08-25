export const initPublicNavigation = () => {
    const button = document.querySelector('[data-public-menu-button]');
    const menu = document.querySelector('[data-public-menu]');

    const closeMenu = () => {
        if (!button || !menu) return;

        menu.classList.add('hidden');
        button.setAttribute('aria-expanded', 'false');
    };

    const cleanCurrentHash = () => {
        if (!window.location.hash) return;

        window.history.replaceState(null, '', window.location.pathname + window.location.search);
    };

    if (window.location.hash) {
        window.addEventListener('load', cleanCurrentHash, { once: true });
    }

    if (document.documentElement.dataset.publicScrollReady !== 'true') {
        document.documentElement.dataset.publicScrollReady = 'true';
        document.addEventListener('click', (event) => {
            const link = event.target.closest('a[href*="#"]');
            if (!link) return;

            const url = new URL(link.href, window.location.href);
            const isSamePage = url.origin === window.location.origin
                && url.pathname === window.location.pathname
                && url.search === window.location.search;

            if (!isSamePage || !url.hash) return;

            const target = document.getElementById(decodeURIComponent(url.hash.slice(1)));
            if (!target) return;

            event.preventDefault();
            closeMenu();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            window.history.replaceState(null, '', window.location.pathname + window.location.search);
        });
    }

    if (!button || !menu || button.dataset.ready === 'true') return;

    button.dataset.ready = 'true';
    button.addEventListener('click', () => {
        const isOpen = !menu.classList.contains('hidden');
        menu.classList.toggle('hidden', isOpen);
        button.setAttribute('aria-expanded', String(!isOpen));
    });
    menu.querySelectorAll('a').forEach((link) => link.addEventListener('click', closeMenu));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeMenu();
        }
    });
    window.addEventListener('scroll', () => {
        if (menu.classList.contains('hidden')) return;

        closeMenu();
    }, { passive: true });
};

const isSamePageHashLink = (url) => {
    return url.origin === window.location.origin
        && url.pathname === window.location.pathname
        && url.search === window.location.search
        && url.hash;
};

const isDownloadUrl = (url) => {
    return /\/(pdf|xlsx)(?:\/)?$/i.test(url.pathname);
};

const inferAdminSkeleton = (url) => {
    const path = url.pathname.replace(/\/+$/, '') || '/';

    if (!path.startsWith('/admin')) return null;
    if (path === '/admin' || path === '/admin/dashboard') return 'dashboard';
    if (path === '/admin/profile') return 'form';

    if (/\/(create|edit|mating|exit)$/.test(path)) return 'form';
    if (/\/preview-frame$/.test(path)) return 'detail';

    const segments = path.split('/').filter(Boolean);

    if (segments.length === 2) return 'table';
    if (segments.length >= 3) return 'detail';

    return 'table';
};

const setSkeletonVariant = (loader, type) => {
    const variant = type || loader.dataset.currentSkeleton || 'table';

    loader.querySelectorAll('[data-skeleton-variant]').forEach((panel) => {
        if (panel.dataset.skeletonVariant === variant) {
            panel.removeAttribute('hidden');
        } else {
            panel.setAttribute('hidden', '');
        }
    });
};

const setPageSkeleton = (loading, type = null) => {
    document.querySelectorAll('[data-page-loader]').forEach((loader) => {
        if (loading) {
            setSkeletonVariant(loader, type);
            loader.removeAttribute('hidden');
            document.documentElement.classList.add('is-page-loading');
            loader.setAttribute('aria-busy', 'true');
        } else {
            loader.setAttribute('hidden', '');
            document.documentElement.classList.remove('is-page-loading');
            loader.removeAttribute('aria-busy');
        }
    });
};

const targetSkeletonFor = (element, url) => {
    return element.dataset.skeletonTarget || inferAdminSkeleton(url);
};

export const initPageSkeleton = () => {
    window.addEventListener('pageshow', () => setPageSkeleton(false));

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[href]');

        if (!link || event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }

        if (link.target && link.target !== '_self') return;
        if (link.hasAttribute('download')) return;
        if (link.dataset.noSkeleton !== undefined) return;

        const url = new URL(link.href, window.location.href);

        if (url.origin !== window.location.origin || isSamePageHashLink(url) || isDownloadUrl(url)) {
            return;
        }

        window.requestAnimationFrame(() => setPageSkeleton(true, targetSkeletonFor(link, url)));
    });

    document.addEventListener('submit', (event) => {
        const form = event.target;

        if (!(form instanceof HTMLFormElement) || event.defaultPrevented) {
            return;
        }

        if (form.dataset.noSkeleton !== undefined) return;
        if (form.target && form.target !== '_self') return;
        if (!form.checkValidity()) return;

        const url = new URL(form.action || window.location.href, window.location.href);

        window.requestAnimationFrame(() => setPageSkeleton(true, targetSkeletonFor(form, url)));
    });
};

const isSamePageHashLink = (url) => {
    return url.origin === window.location.origin
        && url.pathname === window.location.pathname
        && url.search === window.location.search
        && url.hash;
};

const isDownloadUrl = (url) => {
    return /\/(pdf|xlsx)(?:\/)?$/i.test(url.pathname);
};

const setPageSkeleton = (loading) => {
    document.querySelectorAll('[data-page-loader]').forEach((loader) => {
        if (loading) {
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

        window.requestAnimationFrame(() => setPageSkeleton(true));
    });

    document.addEventListener('submit', (event) => {
        const form = event.target;

        if (!(form instanceof HTMLFormElement) || event.defaultPrevented) {
            return;
        }

        if (form.dataset.noSkeleton !== undefined) return;
        if (form.target && form.target !== '_self') return;
        if (!form.checkValidity()) return;

        window.requestAnimationFrame(() => setPageSkeleton(true));
    });
};

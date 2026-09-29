export const initFlashToasts = () => {
    document.querySelectorAll('[data-flash-toast]').forEach((toast) => {
        window.setTimeout(() => {
            toast.classList.add('is-leaving');
            window.setTimeout(() => toast.remove(), 180);
        }, 4200);
    });
};

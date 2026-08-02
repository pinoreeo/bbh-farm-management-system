const applyTheme = (theme) => {
    const root = document.documentElement;
    root.classList.toggle('dark', theme === 'dark');
    root.dataset.theme = theme;
};

export const initThemeToggle = () => {
    const storedTheme = localStorage.getItem('bbh-theme');
    const preferredTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    const forcedTheme = document.documentElement.dataset.forceTheme;

    applyTheme(forcedTheme || storedTheme || preferredTheme);

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-theme-toggle]');

        if (!button || forcedTheme) {
            return;
        }

        const nextTheme = document.documentElement.classList.contains('dark') ? 'light' : 'dark';
        localStorage.setItem('bbh-theme', nextTheme);
        applyTheme(nextTheme);
    });
};

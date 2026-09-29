export const initSettingsTabs = () => {
    document.querySelectorAll('[data-settings-tabs]').forEach((container) => {
        const tabs = [...container.querySelectorAll('[data-settings-tab]')];
        const panels = [...container.querySelectorAll('[data-settings-panel]')];

        const selectTab = (selectedTab) => {
            const selectedPanel = selectedTab.dataset.settingsTab;

            tabs.forEach((tab) => {
                const isSelected = tab === selectedTab;

                tab.setAttribute('aria-selected', String(isSelected));
                tab.classList.toggle('is-active', isSelected);
            });

            panels.forEach((panel) => {
                panel.hidden = panel.dataset.settingsPanel !== selectedPanel;
            });
        };

        tabs.forEach((tab) => {
            tab.addEventListener('click', () => selectTab(tab));
        });
    });
};

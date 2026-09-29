const pointPosition = (index) => 8.9 + ((Number(index) / 11) * 83.5);

export const initDashboardCharts = () => {
    document.querySelectorAll('[data-dashboard-chart]').forEach((chart) => {
        const tooltip = chart.querySelector('[data-dashboard-chart-tooltip]');
        const guide = chart.querySelector('[data-dashboard-chart-guide]');
        const month = chart.querySelector('[data-dashboard-chart-month]');
        const events = chart.querySelector('[data-dashboard-chart-events]');
        const offspring = chart.querySelector('[data-dashboard-chart-offspring]');

        if (!tooltip || !guide || !month || !events || !offspring) return;

        const showPoint = (point) => {
            month.textContent = point.dataset.month || '';
            events.textContent = point.dataset.events || '0';
            offspring.textContent = point.dataset.offspring || '0';
            guide.style.left = `${pointPosition(point.dataset.index)}%`;
            tooltip.style.left = `${pointPosition(point.dataset.index)}%`;
        };

        chart.querySelectorAll('[data-dashboard-chart-point]').forEach((point) => {
            point.addEventListener('pointerenter', () => showPoint(point));
            point.addEventListener('focus', () => showPoint(point));
            point.addEventListener('click', () => showPoint(point));
        });

        const initialPoint = chart.querySelector('[data-dashboard-chart-default]');
        if (initialPoint) showPoint(initialPoint);
    });
};

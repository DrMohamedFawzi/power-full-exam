import Chart from 'chart.js/auto';

/**
 * Threats-over-time line chart for the Overwatch dashboard.
 *
 * Colours are pulled from the DaisyUI theme's CSS custom properties at render
 * time (never hard-coded), so the chart follows light/dark theme switches
 * automatically without any JS-side palette duplication.
 */
const themeColor = (name, fallback) => {
    const value = getComputedStyle(document.documentElement).getPropertyValue(name).trim();

    return value === '' ? fallback : value;
};

export default (payload) => ({
    labels: payload?.labels ?? [],
    data: payload?.data ?? [],
    chart: null,

    init(canvas) {
        const primary = themeColor('--color-error', '#dc2626');
        const gridColor = themeColor('--color-base-300', '#e4e4e7');
        const textColor = themeColor('--color-base-content', '#3f3f46');

        this.chart = new Chart(canvas, {
            type: 'line',
            data: {
                labels: this.labels,
                datasets: [
                    {
                        label: 'التهديدات',
                        data: this.data,
                        borderColor: primary,
                        backgroundColor: primary,
                        tension: 0.35,
                        fill: false,
                        pointRadius: 2,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                },
                scales: {
                    x: {
                        grid: { color: gridColor },
                        ticks: { color: textColor },
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: gridColor },
                        ticks: { color: textColor, precision: 0 },
                    },
                },
            },
        });
    },
});

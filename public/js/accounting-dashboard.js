(function () {
  'use strict';

  const dataNode = document.getElementById('ac-dashboard-data');
  const parseData = () => {
    if (!dataNode) {
      return {};
    }

    try {
      return JSON.parse(dataNode.textContent || '{}');
    } catch (error) {
      return {};
    }
  };

  const formatNumber = (value) => new Intl.NumberFormat('ar-EG', {
    maximumFractionDigits: 2,
    minimumFractionDigits: 0,
  }).format(Number(value || 0));

  document.querySelectorAll('.ac-db-progress').forEach((progress) => {
    const fill = progress.querySelector('span');
    const width = Math.max(0, Math.min(100, Number(progress.dataset.progress || 0)));

    if (fill) {
      requestAnimationFrame(() => {
        fill.style.width = `${width}%`;
      });
    }
  });

  if (typeof Chart === 'undefined') {
    return;
  }

  Chart.defaults.font.family = "'Cairo', 'Segoe UI', sans-serif";
  Chart.defaults.font.size = 12;
  Chart.defaults.color = '#64748b';

  const { chartData = {}, breakdownData = {}, weeklyChartData = {} } = parseData();

  const chartRevExp = document.getElementById('chartRevExp');
  if (chartRevExp && chartData.labels) {
    new Chart(chartRevExp, {
      data: {
        labels: chartData.labels,
        datasets: [
          {
            type: 'bar',
            label: 'الإيرادات',
            data: chartData.revenues || [],
            backgroundColor: 'rgba(22, 163, 74, .78)',
            borderRadius: 8,
            borderSkipped: false,
            order: 2,
          },
          {
            type: 'bar',
            label: 'المصروفات',
            data: chartData.expenses || [],
            backgroundColor: 'rgba(220, 38, 38, .68)',
            borderRadius: 8,
            borderSkipped: false,
            order: 2,
          },
          {
            type: 'line',
            label: 'صافي الربح',
            data: chartData.profit || [],
            borderColor: '#2563eb',
            backgroundColor: 'rgba(37, 99, 235, .08)',
            borderWidth: 3,
            pointRadius: 4,
            pointHoverRadius: 7,
            pointBackgroundColor: '#2563eb',
            fill: true,
            tension: .38,
            order: 1,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
          legend: {
            position: 'top',
            align: 'end',
            rtl: true,
            labels: { boxWidth: 12, usePointStyle: true, pointStyle: 'circle' },
          },
          tooltip: {
            rtl: true,
            callbacks: {
              label: (context) => ` ${context.dataset.label}: ${formatNumber(context.parsed.y)}`,
            },
          },
        },
        scales: {
          x: { grid: { display: false } },
          y: {
            beginAtZero: true,
            grid: { color: 'rgba(148, 163, 184, .14)' },
            ticks: { callback: (value) => formatNumber(value) },
          },
        },
      },
    });
  }

  const chartBreakdown = document.getElementById('chartBreakdown');
  if (chartBreakdown && Array.isArray(breakdownData.values) && breakdownData.values.length > 0) {
    const total = breakdownData.values.reduce((sum, value) => sum + Number(value || 0), 0) || 1;
    const colors = ['#2563eb', '#0f766e', '#f59e0b', '#dc2626', '#7c3aed', '#0891b2'];

    new Chart(chartBreakdown, {
      type: 'doughnut',
      data: {
        labels: breakdownData.labels || [],
        datasets: [{
          data: breakdownData.values,
          backgroundColor: colors,
          borderColor: '#fff',
          borderWidth: 3,
          hoverOffset: 8,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '68%',
        plugins: {
          legend: { display: false },
          tooltip: {
            rtl: true,
            callbacks: {
              label: (context) => {
                const percentage = ((Number(context.parsed || 0) / total) * 100).toFixed(1);
                return ` ${context.label}: ${formatNumber(context.parsed)} (${percentage}%)`;
              },
            },
          },
        },
      },
    });

    const legend = document.getElementById('breakdownLegend');
    if (legend) {
      legend.textContent = '';
      (breakdownData.labels || []).forEach((label, index) => {
        const value = Number(breakdownData.values[index] || 0);
        const item = document.createElement('div');
        const dot = document.createElement('span');
        const name = document.createElement('strong');
        const amount = document.createElement('b');

        dot.className = `ac-db-legend-dot ac-db-legend-dot--${index + 1}`;
        name.textContent = label;
        amount.textContent = `${formatNumber(value)} (${((value / total) * 100).toFixed(1)}%)`;

        item.append(dot, name, amount);
        legend.appendChild(item);
      });
    }
  }

  const chartWeekly = document.getElementById('chartWeekly');
  if (chartWeekly && weeklyChartData.labels) {
    new Chart(chartWeekly, {
      type: 'bar',
      data: {
        labels: weeklyChartData.labels,
        datasets: [
          {
            label: 'الإيرادات',
            data: weeklyChartData.revenues || [],
            backgroundColor: 'rgba(22, 163, 74, .78)',
            borderRadius: 8,
            borderSkipped: false,
          },
          {
            label: 'المصروفات',
            data: weeklyChartData.expenses || [],
            backgroundColor: 'rgba(220, 38, 38, .66)',
            borderRadius: 8,
            borderSkipped: false,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'top',
            align: 'end',
            rtl: true,
            labels: { boxWidth: 12, usePointStyle: true, pointStyle: 'circle' },
          },
          tooltip: {
            rtl: true,
            callbacks: {
              label: (context) => ` ${context.dataset.label}: ${formatNumber(context.parsed.y)}`,
            },
          },
        },
        scales: {
          x: { grid: { display: false } },
          y: {
            beginAtZero: true,
            grid: { color: 'rgba(148, 163, 184, .14)' },
            ticks: { callback: (value) => formatNumber(value) },
          },
        },
      },
    });
  }
})();

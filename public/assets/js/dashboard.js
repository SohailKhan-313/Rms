/**
 * RMS Dashboard Analytics & Charts
 * Dedicated script for Dashboard widgets, charts and interactive maps
 */

document.addEventListener('DOMContentLoaded', function () {
  // 1. Sales & Revenue Area Chart
  const revenueChartEl = document.querySelector('#revenue-chart');
  if (revenueChartEl && typeof ApexCharts !== 'undefined') {
    const salesChartOptions = {
      series: [
        {
          name: 'Dine-In Orders',
          data: [28, 48, 40, 19, 86, 27, 90],
        },
        {
          name: 'Takeaway & Delivery',
          data: [65, 59, 80, 81, 56, 55, 40],
        },
      ],
      chart: {
        height: 300,
        type: 'area',
        toolbar: { show: false },
      },
      legend: { show: true },
      colors: ['#4f46e5', '#10b981'],
      dataLabels: { enabled: false },
      stroke: { curve: 'smooth', width: 2 },
      xaxis: {
        type: 'datetime',
        categories: [
          '2024-01-01',
          '2024-02-01',
          '2024-03-01',
          '2024-04-01',
          '2024-05-01',
          '2024-06-01',
          '2024-07-01',
        ],
      },
      tooltip: {
        x: { format: 'MMMM yyyy' },
      },
    };

    const salesChart = new ApexCharts(revenueChartEl, salesChartOptions);
    salesChart.render();
  }

  // 2. World Map (if element exists)
  const worldMapEl = document.querySelector('#world-map');
  if (worldMapEl && typeof jsVectorMap !== 'undefined') {
    new jsVectorMap({
      selector: '#world-map',
      map: 'world',
    });
  }

  // 3. Mini Sparkline Charts
  const sparkline1El = document.querySelector('#sparkline-1');
  if (sparkline1El && typeof ApexCharts !== 'undefined') {
    new ApexCharts(sparkline1El, {
      series: [{ data: [1000, 1200, 920, 927, 931, 1027, 819, 930, 1021] }],
      chart: { type: 'area', height: 50, sparkline: { enabled: true } },
      stroke: { curve: 'straight' },
      fill: { opacity: 0.3 },
      colors: ['#4f46e5'],
    }).render();
  }

  const sparkline2El = document.querySelector('#sparkline-2');
  if (sparkline2El && typeof ApexCharts !== 'undefined') {
    new ApexCharts(sparkline2El, {
      series: [{ data: [515, 519, 520, 522, 652, 810, 370, 627, 319, 630, 921] }],
      chart: { type: 'area', height: 50, sparkline: { enabled: true } },
      stroke: { curve: 'straight' },
      fill: { opacity: 0.3 },
      colors: ['#10b981'],
    }).render();
  }

  const sparkline3El = document.querySelector('#sparkline-3');
  if (sparkline3El && typeof ApexCharts !== 'undefined') {
    new ApexCharts(sparkline3El, {
      series: [{ data: [15, 19, 20, 22, 33, 27, 31, 27, 19, 30, 21] }],
      chart: { type: 'area', height: 50, sparkline: { enabled: true } },
      stroke: { curve: 'straight' },
      fill: { opacity: 0.3 },
      colors: ['#f59e0b'],
    }).render();
  }
});

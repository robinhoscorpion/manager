<script setup>
import { Line } from 'vue-chartjs';
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    LineElement,
    CategoryScale,
    LinearScale,
    PointElement,
    Filler
} from 'chart.js';

ChartJS.register(
    Title,
    Tooltip,
    Legend,
    LineElement,
    CategoryScale,
    LinearScale,
    PointElement,
    Filler
);

const props = defineProps({
    data: {
        type: Object,
        required: true
    }
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    layout: {
        padding: { top: 10, bottom: 0 }
    },
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: 'rgba(11, 14, 20, 0.95)',
            titleColor: '#fff',
            titleFont: { family: "'Inter', sans-serif", size: 12, weight: 'bold' },
            bodyColor: '#ccc',
            bodyFont: { family: "'Inter', sans-serif", size: 11 },
            borderColor: 'rgba(255, 255, 255, 0.1)',
            borderWidth: 1,
            padding: 12,
            boxPadding: 8,
            cornerRadius: 12,
            usePointStyle: true,
            callbacks: {
                label: function(context) {
                    let label = context.dataset.label || '';
                    if (label) label += ': ';
                    if (context.parsed.y !== null) label += context.parsed.y;
                    return label;
                }
            }
        }
    },
    interaction: {
        intersect: false,
        mode: 'index',
    },
    scales: {
        y: {
            grid: {
                color: 'rgba(255, 255, 255, 0.03)',
                drawBorder: false
            },
            ticks: {
                color: 'rgba(255, 255, 255, 0.3)',
                font: { family: "'Inter', sans-serif", size: 10 },
                padding: 10,
                callback: (value) => value === 0 ? '0' : value
            },
            min: 0
        },
        x: {
            grid: { display: false },
            ticks: {
                color: 'rgba(255, 255, 255, 0.3)',
                font: { family: "'Inter', sans-serif", size: 10 },
                padding: 10
            }
        }
    },
    elements: {
        point: {
            radius: 0,
            hoverRadius: 6,
            hoverBorderWidth: 3,
            backgroundColor: '#00d2ff',
            borderColor: '#fff',
        }
    }
};
</script>

<template>
    <div class="h-[320px] w-full mt-6">
        <Line :data="data" :options="chartOptions" />
    </div>
</template>

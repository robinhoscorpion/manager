<script setup>
import { Bar } from 'vue-chartjs';
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale
} from 'chart.js';

ChartJS.register(
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale
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
    plugins: {
        legend: {
            display: false
        }
    },
    scales: {
        y: {
            grid: {
                color: 'rgba(255, 255, 255, 0.05)',
                drawBorder: false
            },
            ticks: {
                color: 'rgba(255, 255, 255, 0.4)',
                font: { size: 10 }
            }
        },
        x: {
            grid: {
                display: false,
                drawBorder: false
            },
            ticks: {
                color: 'rgba(255, 255, 255, 0.4)',
                font: { size: 10 }
            }
        }
    }
};
</script>

<template>
    <div class="h-[250px] w-full">
        <Bar :data="data" :options="chartOptions" />
    </div>
</template>

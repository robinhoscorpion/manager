<script setup>
import { Doughnut } from 'vue-chartjs';
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    ArcElement,
    CategoryScale,
    LinearScale
} from 'chart.js';

ChartJS.register(
    Title,
    Tooltip,
    Legend,
    ArcElement,
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
    cutout: '70%',
    plugins: {
        legend: {
            position: 'right',
            labels: {
                color: 'rgba(255, 255, 255, 0.6)',
                font: { size: 10, weight: 'bold' },
                usePointStyle: true,
                padding: 15
            }
        },
        tooltip: {
            backgroundColor: 'rgba(11, 14, 20, 0.9)',
            cornerRadius: 8,
            padding: 12
        }
    }
};
</script>

<template>
    <div class="h-[180px] w-full">
        <Doughnut :data="data" :options="chartOptions" />
    </div>
</template>

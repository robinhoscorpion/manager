<script setup>
defineProps({
    title: String,
    value: String,
    color: {
        type: String,
        default: 'text-slate-900 dark:text-white'
    },
    trend: {
        type: String,
        default: '+0.0%'
    },
    trendUp: {
        type: Boolean,
        default: true
    },
    subtitle: {
        type: String,
        default: 'vs último mês'
    }
});
</script>

<template>
    <div class="stat-card group">
        <div class="flex items-start justify-between">
            <!-- Left: info -->
            <div class="flex-1 min-w-0">
                <p class="stat-label">{{ title }}</p>
                <p class="stat-value" :class="color">{{ value }}</p>

                <div class="stat-footer">
                    <div class="trend-pill" :class="trendUp ? 'trend-up-pill' : 'trend-down-pill'">
                        <!-- Arrow up -->
                        <svg v-if="trendUp" class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7" />
                        </svg>
                        <!-- Arrow down -->
                        <svg v-else class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                        </svg>
                        <span>{{ trend }}</span>
                    </div>
                    <span class="stat-trend-label">{{ subtitle }}</span>
                </div>
            </div>

            <!-- Right: icon -->
            <div class="stat-icon-box">
                <slot name="icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </slot>
            </div>
        </div>
    </div>
</template>

<style scoped>
.stat-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    padding: 24px;
    box-shadow: var(--shadow-card);
    transition: box-shadow 0.25s ease, transform 0.25s ease, border-color 0.25s ease;
    position: relative;
    overflow: hidden;
}

.stat-card:hover {
    box-shadow: var(--shadow-card-hover);
    transform: translateY(-3px);
    border-color: var(--primary-border);
}

.stat-label {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-secondary);
    margin-bottom: 6px;
}

.stat-value {
    font-family: var(--font-heading);
    font-size: 28px;
    font-weight: 800;
    line-height: 1.1;
    color: var(--text-main);
    letter-spacing: -0.5px;
}

.stat-footer {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 14px;
}

.trend-pill {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 700;
}

.trend-up-pill {
    background: rgba(22, 163, 74, 0.1);
    color: #16a34a;
}

.dark .trend-up-pill {
    background: rgba(74, 222, 128, 0.15);
    color: #4ade80;
}

.trend-down-pill {
    background: rgba(220, 38, 38, 0.1);
    color: #dc2626;
}

.dark .trend-down-pill {
    background: rgba(248, 113, 113, 0.15);
    color: #f87171;
}

.stat-trend-label {
    font-size: 11.5px;
    font-weight: 500;
    color: var(--text-muted);
}

.stat-icon-box {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: var(--primary-light);
    border: 1px solid var(--primary-border);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    color: var(--primary);
    transition: all 0.25s ease;
}

.stat-card:hover .stat-icon-box {
    background: var(--primary);
    border-color: var(--primary);
    color: #ffffff;
    transform: scale(1.05) rotate(3deg);
}
</style>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    title: String,
    subtitle: {
        type: String,
        default: '',
    },
    sellers: {
        type: Array,
        required: true,
    },
    // Cor de destaque da categoria (hex)
    accent: {
        type: String,
        default: '#5A6B46',
    },
});

const currency = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL', maximumFractionDigits: 0 });

const totalOf = (seller) => Number(seller?.total) || 0;

const leader = computed(() => props.sellers[0] || null);
const podium = computed(() => props.sellers.slice(1, 3)); // 2º e 3º
const chasers = computed(() => props.sellers.slice(3));   // 4º em diante
const categoryTotal = computed(() => props.sellers.reduce((sum, s) => sum + totalOf(s), 0));

const photo = (seller) => seller?.profile_photo_url || seller?.avatar || null;

const initials = (name) => {
    const parts = String(name || '').trim().split(/\s+/).filter(Boolean);
    if (!parts.length) return '?';
    return (parts[0][0] + (parts.length > 1 ? parts[parts.length - 1][0] : '')).toUpperCase();
};

const firstName = (name) => String(name || '').trim().split(/\s+/)[0] || '';

const money = (value) => currency.format(value);

// Vantagem do líder sobre o 2º colocado
const leaderLead = computed(() => {
    if (props.sellers.length < 2) return null;
    return totalOf(props.sellers[0]) - totalOf(props.sellers[1]);
});

// Participação do líder no total do top
const leaderShare = computed(() => {
    if (!categoryTotal.value) return 0;
    return Math.round((totalOf(leader.value) / categoryTotal.value) * 100);
});

// Quanto falta para ultrapassar quem está logo acima (position = 1-based)
const chaseText = (position) => {
    const current = props.sellers[position - 1];
    const ahead = props.sellers[position - 2];
    if (!ahead) return '';
    const gap = totalOf(ahead) - totalOf(current);
    return gap > 0
        ? `Faltam ${money(gap)} para passar ${firstName(ahead.name)}`
        : `Empatado com ${firstName(ahead.name)}`;
};

// Barra em relação ao líder
const shareOfLeader = (seller) => {
    const top = totalOf(leader.value);
    if (!top) return 0;
    return Math.max(4, Math.round((totalOf(seller) / top) * 100));
};

const medal = (position) => (position === 2 ? 'silver' : 'bronze');
</script>

<template>
    <div class="ranking-card" :style="{ '--accent': accent }">
        <!-- Cabeçalho -->
        <div class="ranking-header">
            <div class="flex items-center gap-2.5 min-w-0">
                <span class="accent-dot"></span>
                <div class="min-w-0">
                    <h3 class="ranking-title">{{ title }}</h3>
                    <p v-if="subtitle" class="ranking-subtitle">{{ subtitle }}</p>
                </div>
            </div>
            <div v-if="sellers.length" class="text-right shrink-0">
                <p class="eyebrow">Volume do top {{ sellers.length }}</p>
                <p class="header-total">{{ money(categoryTotal) }}</p>
            </div>
        </div>

        <!-- Estado vazio -->
        <div v-if="!sellers.length" class="empty-state">
            <div class="empty-icon">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M8 21h8m-4-4v4m-5-17h10v5a5 5 0 01-10 0V4zm10 1h2a2 2 0 010 4h-2M7 5H5a2 2 0 000 4h2" />
                </svg>
            </div>
            <p class="text-sm font-semibold text-[var(--text-secondary)]">A disputa ainda não começou</p>
            <p class="text-xs text-[var(--text-muted)]">Nenhuma venda registrada neste mês.</p>
        </div>

        <template v-else>
            <!-- 1º lugar -->
            <div class="champion">
                <div class="champion-glow"></div>
                <div class="relative flex items-center gap-4">
                    <div class="relative shrink-0">
                        <img v-if="photo(leader)" :src="photo(leader)" :alt="leader.name" class="champion-avatar object-cover">
                        <div v-else class="champion-avatar champion-initials">{{ initials(leader.name) }}</div>
                        <span class="champion-rank">1</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="champion-eyebrow">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 21h8m-4-4v4m-5-17h10v5a5 5 0 01-10 0V4zm10 1h2a2 2 0 010 4h-2M7 5H5a2 2 0 000 4h2" />
                            </svg>
                            Líder do mês
                        </p>
                        <p class="champion-name" :title="leader.name">{{ leader.name }}</p>
                    </div>
                </div>

                <div class="relative mt-4 flex items-end justify-between gap-3">
                    <p class="champion-value">{{ leader.value || money(totalOf(leader)) }}</p>
                    <div class="flex flex-col items-end gap-1 shrink-0">
                        <span v-if="leaderLead !== null" class="lead-chip">
                            <template v-if="leaderLead > 0">+{{ money(leaderLead) }} de vantagem</template>
                            <template v-else>Empatado na liderança</template>
                        </span>
                        <span class="champion-share">{{ leaderShare }}% do volume do top</span>
                    </div>
                </div>
            </div>

            <!-- 2º e 3º lugar -->
            <div v-if="podium.length" class="podium">
                <div v-for="(seller, index) in podium" :key="`p-${seller.name}-${index}`" class="podium-tile" :class="medal(index + 2)">
                    <div class="flex items-center gap-2.5">
                        <div class="relative shrink-0">
                            <img v-if="photo(seller)" :src="photo(seller)" :alt="seller.name" class="medal-avatar object-cover">
                            <div v-else class="medal-avatar medal-initials">{{ initials(seller.name) }}</div>
                            <span class="medal-badge">{{ index + 2 }}</span>
                        </div>
                        <div class="min-w-0">
                            <p class="tile-name" :title="seller.name">{{ seller.name }}</p>
                            <p class="tile-value">{{ seller.value || money(totalOf(seller)) }}</p>
                        </div>
                    </div>
                    <p class="chase">{{ chaseText(index + 2) }}</p>
                </div>
            </div>

            <!-- 4º em diante -->
            <ol v-if="chasers.length" class="chasers">
                <li v-for="(seller, index) in chasers" :key="`c-${seller.name}-${index}`" class="chaser-row">
                    <span class="chaser-rank">{{ index + 4 }}º</span>
                    <img v-if="photo(seller)" :src="photo(seller)" :alt="seller.name" class="chaser-avatar object-cover">
                    <div v-else class="chaser-avatar medal-initials">{{ initials(seller.name) }}</div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-baseline justify-between gap-3">
                            <p class="chaser-name" :title="seller.name">{{ seller.name }}</p>
                            <p class="chaser-value">{{ seller.value || money(totalOf(seller)) }}</p>
                        </div>
                        <div class="progress-track">
                            <div class="progress-fill" :style="{ width: shareOfLeader(seller) + '%' }"></div>
                        </div>
                        <p class="chase mt-1">{{ chaseText(index + 4) }}</p>
                    </div>
                </li>
            </ol>
        </template>
    </div>
</template>

<style scoped>
.ranking-card {
    --gold: #c9a227;
    --silver: #8a94a6;
    --bronze: #b0703c;
    display: flex;
    flex-direction: column;
    height: 100%;
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 18px;
    padding: 20px;
    box-shadow: var(--shadow-card);
    transition: box-shadow 0.25s ease, border-color 0.25s ease;
}

.ranking-card:hover {
    box-shadow: var(--shadow-card-hover);
    border-color: color-mix(in srgb, var(--accent) 30%, var(--border-color));
}

/* Cabeçalho */
.ranking-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 16px;
}

.accent-dot {
    width: 10px;
    height: 10px;
    border-radius: 9999px;
    background: var(--accent);
    box-shadow: 0 0 0 4px color-mix(in srgb, var(--accent) 15%, transparent);
    flex-shrink: 0;
}

.ranking-title {
    font-family: var(--font-heading);
    font-size: 16px;
    font-weight: 700;
    color: var(--text-main);
    line-height: 1.2;
    letter-spacing: -0.01em;
}

.ranking-subtitle {
    font-size: 11px;
    color: var(--text-muted);
    margin-top: 1px;
}

.eyebrow {
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--text-muted);
}

.header-total {
    font-family: var(--font-heading);
    font-size: 14px;
    font-weight: 700;
    color: var(--text-secondary);
    font-variant-numeric: tabular-nums;
}

/* 1º lugar */
.champion {
    position: relative;
    overflow: hidden;
    border-radius: 14px;
    padding: 18px;
    color: #fff;
    background: linear-gradient(135deg,
        color-mix(in srgb, var(--accent) 92%, #000) 0%,
        color-mix(in srgb, var(--accent) 60%, #0b1215) 100%);
    box-shadow: 0 10px 24px -12px color-mix(in srgb, var(--accent) 70%, transparent);
}

.champion-glow {
    position: absolute;
    top: -60px;
    right: -40px;
    width: 180px;
    height: 180px;
    border-radius: 9999px;
    background: radial-gradient(circle, rgba(232, 196, 90, 0.35) 0%, rgba(232, 196, 90, 0) 70%);
    pointer-events: none;
}

.champion-avatar {
    width: 56px;
    height: 56px;
    border-radius: 9999px;
    border: 2px solid var(--gold);
    box-shadow: 0 0 0 4px rgba(201, 162, 39, 0.25);
}

.champion-initials {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
    font-weight: 700;
    background: rgba(255, 255, 255, 0.14);
    color: #fff;
}

.champion-rank {
    position: absolute;
    right: -4px;
    bottom: -4px;
    width: 22px;
    height: 22px;
    border-radius: 9999px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 800;
    color: #3b2f05;
    background: linear-gradient(135deg, #f3d46b, var(--gold));
    border: 2px solid color-mix(in srgb, var(--accent) 80%, #000);
}

.champion-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: #f3d46b;
}

.champion-name {
    font-family: var(--font-heading);
    font-size: 17px;
    font-weight: 600;
    margin-top: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.champion-value {
    font-family: var(--font-heading);
    font-size: 26px;
    font-weight: 700;
    line-height: 1;
    letter-spacing: -0.02em;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

.lead-chip {
    font-size: 11px;
    font-weight: 600;
    padding: 3px 9px;
    border-radius: 9999px;
    background: rgba(255, 255, 255, 0.14);
    border: 1px solid rgba(255, 255, 255, 0.18);
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
}

.champion-share {
    font-size: 10px;
    color: rgba(255, 255, 255, 0.7);
    white-space: nowrap;
}

/* 2º e 3º lugar */
.podium {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
    margin-top: 12px;
}

.podium-tile {
    --medal: var(--silver);
    border-radius: 12px;
    padding: 12px;
    border: 1px solid color-mix(in srgb, var(--medal) 30%, var(--border-color));
    background: color-mix(in srgb, var(--medal) 6%, var(--card-bg));
}

.podium-tile.bronze {
    --medal: var(--bronze);
}

.medal-avatar {
    width: 36px;
    height: 36px;
    border-radius: 9999px;
    border: 2px solid var(--medal);
}

.medal-initials {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
    color: var(--accent);
    background: color-mix(in srgb, var(--accent) 10%, var(--card-bg));
}

.medal-badge {
    position: absolute;
    right: -3px;
    bottom: -3px;
    width: 17px;
    height: 17px;
    border-radius: 9999px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 9px;
    font-weight: 800;
    color: #fff;
    background: var(--medal);
    border: 2px solid var(--card-bg);
}

.tile-name {
    font-size: 12px;
    font-weight: 600;
    color: var(--text-main);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.tile-value {
    font-family: var(--font-heading);
    font-size: 15px;
    font-weight: 700;
    color: var(--text-main);
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

.chase {
    font-size: 10.5px;
    line-height: 1.35;
    color: var(--text-muted);
    margin-top: 8px;
}

/* 4º em diante */
.chasers {
    display: flex;
    flex-direction: column;
    margin-top: 12px;
    padding-top: 4px;
    border-top: 1px dashed var(--border-color);
}

.chaser-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 4px 6px;
}

.chaser-rank {
    width: 24px;
    font-size: 12px;
    font-weight: 700;
    color: var(--text-muted);
    font-variant-numeric: tabular-nums;
    flex-shrink: 0;
}

.chaser-avatar {
    width: 30px;
    height: 30px;
    border-radius: 9999px;
    flex-shrink: 0;
}

.chaser-name {
    font-size: 13px;
    font-weight: 500;
    color: var(--text-main);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.chaser-value {
    font-size: 13px;
    font-weight: 600;
    color: var(--text-secondary);
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

.chaser-row .chase {
    margin-top: 4px;
}

.progress-track {
    height: 4px;
    margin-top: 6px;
    border-radius: 9999px;
    background: var(--border-light);
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    border-radius: 9999px;
    background: linear-gradient(90deg, color-mix(in srgb, var(--accent) 55%, transparent), var(--accent));
    transition: width 0.6s ease;
}

/* Vazio */
.empty-state {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4px;
    padding: 36px 12px;
    text-align: center;
}

.empty-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 8px;
    color: var(--accent);
    background: color-mix(in srgb, var(--accent) 10%, transparent);
}
</style>

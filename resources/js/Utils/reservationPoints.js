/**
 * Utility for calculating stay points and season breakdown for reservations.
 */

export const normalizeStr = (str) => {
    if (!str) return '';
    return String(str)
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim();
};

export const findBestScoreForSeason = (scoresForSeason, currentPax) => {
    if (!scoresForSeason || scoresForSeason.length === 0) return null;

    const targetPax = Number(currentPax) || 1;
    const exact = scoresForSeason.find(s => Number(s.pax) === targetPax);
    if (exact) return exact;

    let best = scoresForSeason[0];
    let minDiff = Math.abs(Number(best.pax) - targetPax);

    for (let i = 1; i < scoresForSeason.length; i++) {
        const diff = Math.abs(Number(scoresForSeason[i].pax) - targetPax);
        if (diff < minDiff) {
            minDiff = diff;
            best = scoresForSeason[i];
        }
    }
    return best;
};

export const calculateStayBreakdown = (resData, destinations = [], holidays = []) => {
    if (!resData || !resData.destination || !resData.accommodation || !resData.check_in || !resData.check_out) {
        return null;
    }

    const start = new Date(resData.check_in + 'T00:00:00');
    const end = new Date(resData.check_out + 'T00:00:00');

    if (isNaN(start.getTime()) || isNaN(end.getTime())) return null;

    const diffTime = end.getTime() - start.getTime();
    if (diffTime <= 0) return null;

    const nights = Math.ceil(diffTime / (1000 * 3600 * 24));
    if (nights <= 0) return null;

    const resort = (destinations || []).find(d => normalizeStr(d.name) === normalizeStr(resData.destination));
    if (!resort || !resort.accommodations) return null;

    const acc = resort.accommodations.find(a => normalizeStr(a.name) === normalizeStr(resData.accommodation));
    if (!acc || !acc.scores || acc.scores.length === 0) return null;

    const currentPax = Math.max(1, (resData.adults || 1) + (resData.children || 0));

    const monthNames = [
        'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
        'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'
    ];

    let totalPoints = 0;
    const dailyList = [];
    let weeklyBasePoints = 0;
    let hasHolidayInStay = false;
    const seasonNamesSet = new Set();

    for (let i = 0; i < nights; i++) {
        const currentDate = new Date(start);
        currentDate.setDate(currentDate.getDate() + i);

        const year = currentDate.getFullYear();
        const month = String(currentDate.getMonth() + 1).padStart(2, '0');
        const day = String(currentDate.getDate()).padStart(2, '0');
        const currentISO = `${year}-${month}-${day}`;
        const rawMonthName = monthNames[currentDate.getMonth()];
        const normMonthName = normalizeStr(rawMonthName);
        const dateStr = currentDate.toLocaleDateString('pt-BR');

        // Check holiday
        const holidayMatch = (holidays || []).find(h => {
            const hDate = h.holiday_date ? String(h.holiday_date).split('T')[0] : null;
            const hStart = h.start_date ? String(h.start_date).split('T')[0] : hDate;
            const hEnd = h.end_date ? String(h.end_date).split('T')[0] : hDate;

            if (hStart && hEnd) {
                return currentISO >= hStart && currentISO <= hEnd;
            }
            return hDate === currentISO;
        });

        let targetSeasonName = null;
        let holidayName = null;

        if (holidayMatch) {
            hasHolidayInStay = true;
            holidayName = holidayMatch.name;
            targetSeasonName = holidayMatch.classification;
        }

        let matchedScore = null;

        // 1. Match by Holiday target season
        if (targetSeasonName) {
            const normTarget = normalizeStr(targetSeasonName);
            const holidayScores = acc.scores.filter(s => s.season && normalizeStr(s.season.name) === normTarget);
            matchedScore = findBestScoreForSeason(holidayScores, currentPax);
        }

        // 2. Match by season months_active
        if (!matchedScore) {
            const monthScores = acc.scores.filter(s => {
                if (!s.season || !s.season.months_active) return false;
                let months = s.season.months_active;
                if (typeof months === 'string') {
                    try { months = JSON.parse(months); } catch(e) { months = []; }
                }
                if (!Array.isArray(months)) months = [];
                return months.some(m => normalizeStr(m) === normMonthName);
            });
            matchedScore = findBestScoreForSeason(monthScores, currentPax);
        }

        // 3. Fallback to closest pax among all acc.scores
        if (!matchedScore) {
            matchedScore = findBestScoreForSeason(acc.scores, currentPax);
        }

        const baseWeekly = parseFloat(matchedScore?.points || matchedScore?.points_raw || 0);
        const dailyPoints = Math.round(baseWeekly / 7);
        totalPoints += dailyPoints;

        const effectiveSeasonName = matchedScore?.season ? matchedScore.season.name : (targetSeasonName || 'Padrão');
        const displaySeasonName = holidayName ? `${holidayName} (${effectiveSeasonName})` : effectiveSeasonName;
        seasonNamesSet.add(displaySeasonName);

        if (i === 0) {
            weeklyBasePoints = baseWeekly;
        }

        dailyList.push({
            date: dateStr,
            seasonName: effectiveSeasonName,
            holidayName: holidayName,
            isHoliday: !!holidayMatch,
            points: dailyPoints
        });
    }

    const seasonName = Array.from(seasonNamesSet).join(' / ');

    return {
        nights,
        totalPoints,
        weeklyBasePoints,
        seasonName,
        hasHolidayInStay,
        dailyList
    };
};

/**
 * Gets the actual points for a reservation item.
 * Calculates dynamically using destinations & holidays if available,
 * or falls back to item.points_used if calculation isn't possible.
 */
export const getReservationPoints = (item, destinations = [], holidays = []) => {
    if (!item) return 0;
    const breakdown = calculateStayBreakdown(item, destinations, holidays);
    if (breakdown && breakdown.totalPoints > 0) {
        return breakdown.totalPoints;
    }
    return parseFloat(item.points_used) || 0;
};

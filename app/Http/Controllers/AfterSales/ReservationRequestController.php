<?php

namespace App\Http\Controllers\AfterSales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReservationRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = \App\Models\ReservationRequest::with(['service.client', 'service.proposal.bills', 'service.reservationRequests', 'user'])->latest();
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('service.client', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('cpf', 'like', "%{$search}%");
            });
        }

        $reservations = $query->paginate(15)->withQueryString();
        $destinations = \App\Models\PointTable\Resort::with(['accommodations' => function($q) { $q->with(['scores.season']); }])->orderBy('name')->get();
        $holidays = \App\Models\PointTable\Holiday::orderBy('start_date')->get();

        return \Inertia\Inertia::render('AfterSales/ReservationRequest/Index', [
            'reservations' => $reservations,
            'filters' => $request->only('search'),
            'destinations' => $destinations,
            'holidays' => $holidays,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sales_service_id' => 'required|exists:sales_services,id',
            'destination' => 'required|string|max:255',
            'accommodation' => 'nullable|string|max:255',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'adults' => 'required|integer|min:1',
            'children' => 'nullable|integer|min:0',
            'guests_list' => 'nullable|array',
            'points_used' => 'nullable|numeric|min:0',
            'reservation_code' => 'nullable|string|max:255',
            'observations' => 'nullable|string',
        ]);

        $validated['user_id'] = auth()->id();
        $validated['status'] = 'pending';

        if (empty($validated['points_used']) || (float)$validated['points_used'] <= 0) {
            $validated['points_used'] = $this->calculatePointsForReservation($validated);
        }

        \App\Models\ReservationRequest::create($validated);

        return back()->with('success', 'Solicitação de reserva criada com sucesso.');
    }

    public function update(Request $request, \App\Models\ReservationRequest $reservation)
    {
        $validated = $request->validate([
            'sales_service_id' => 'sometimes|required|exists:sales_services,id',
            'destination' => 'sometimes|required|string|max:255',
            'accommodation' => 'nullable|string|max:255',
            'check_in' => 'sometimes|required|date',
            'check_out' => 'sometimes|required|date|after:check_in',
            'adults' => 'nullable|integer|min:1',
            'children' => 'nullable|integer|min:0',
            'guests_list' => 'nullable|array',
            'points_used' => 'nullable|numeric|min:0',
            'status' => 'required|in:pending,analyzing,confirmed,canceled',
            'reservation_code' => 'nullable|string|max:255',
            'observations' => 'nullable|string',
        ]);

        $mergedData = array_merge($reservation->toArray(), $validated);

        if (!isset($validated['points_used']) || (float)$validated['points_used'] <= 0) {
            $calcPoints = $this->calculatePointsForReservation($mergedData);
            if ($calcPoints > 0) {
                $validated['points_used'] = $calcPoints;
            }
        }

        // Validação de saldo de pontos disponível para o atendimento se status não for cancelado
        if ($validated['status'] !== 'canceled') {
            $service = $reservation->service;
            if ($service && $service->proposal) {
                $service->load(['proposal.bills', 'reservationRequests']);
                $totalPoints = (float) ($service->proposal->quantity ?? 0);
                $totalValue = (float) ($service->proposal->total_value ?? 0);

                $paidAmount = $service->proposal->bills
                    ? $service->proposal->bills
                        ->filter(fn($b) => in_array($b->category, ['entrada', 'saldo']) && $b->status === 'paid')
                        ->sum('amount')
                    : 0;

                $ratio = $totalValue > 0 ? min(1, $paidAmount / $totalValue) : 0;
                $releasedPoints = (int) floor($totalPoints * $ratio);

                $otherUsedPoints = $service->reservationRequests
                    ? $service->reservationRequests
                        ->filter(fn($r) => $r->status !== 'canceled' && $r->id !== $reservation->id)
                        ->sum('points_used')
                    : 0;

                $availablePoints = max(0, $releasedPoints - $otherUsedPoints);
                $requiredPoints = isset($validated['points_used']) ? (float) $validated['points_used'] : (float) $reservation->points_used;

                if ($requiredPoints > $availablePoints) {
                    return back()->withErrors([
                        'points_used' => "Saldo de pontos insuficiente para este contrato! Liberados: {$releasedPoints} pts, Usados em outras reservas: {$otherUsedPoints} pts, Disponível: {$availablePoints} pts, Necessário: {$requiredPoints} pts."
                    ]);
                }
            }
        }

        $reservation->update($validated);

        return back()->with('success', 'Solicitação de reserva atualizada com sucesso.');
    }

    public function destroy(\App\Models\ReservationRequest $reservation)
    {
        $reservation->delete();
        return back()->with('success', 'Solicitação de reserva removida com sucesso.');
    }

    protected function calculatePointsForReservation(array $data): float
    {
        if (empty($data['destination']) || empty($data['accommodation']) || empty($data['check_in']) || empty($data['check_out'])) {
            return 0.0;
        }

        try {
            $start = \Carbon\Carbon::parse($data['check_in']);
            $end = \Carbon\Carbon::parse($data['check_out']);
            $nights = $start->diffInDays($end);

            if ($nights <= 0) return 0.0;

            $resort = \App\Models\PointTable\Resort::where('name', $data['destination'])
                ->with(['accommodations' => function($q) use ($data) {
                    $q->where('name', $data['accommodation'])->with(['scores.season']);
                }])->first();

            if (!$resort || $resort->accommodations->isEmpty()) return 0.0;

            $acc = $resort->accommodations->first();
            if (!$acc || $acc->scores->isEmpty()) return 0.0;

            $adults = isset($data['adults']) ? (int)$data['adults'] : 1;
            $children = isset($data['children']) ? (int)$data['children'] : 0;
            $currentPax = max(1, $adults + $children);

            $holidays = \App\Models\PointTable\Holiday::all();

            $monthNames = [
                1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
                5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
                9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
            ];

            $totalPoints = 0;

            for ($i = 0; $i < $nights; $i++) {
                $currentDate = $start->copy()->addDays($i);
                $currentISO = $currentDate->format('Y-m-d');
                $monthName = $monthNames[$currentDate->month];
                $normMonth = $this->normalizeStr($monthName);

                $holidayMatch = $holidays->first(function($h) use ($currentISO) {
                    $hDate = $h->holiday_date ? explode('T', $h->holiday_date)[0] : null;
                    $hStart = $h->start_date ? explode('T', $h->start_date)[0] : $hDate;
                    $hEnd = $h->end_date ? explode('T', $h->end_date)[0] : $hDate;

                    if ($hStart && $hEnd) {
                        return $currentISO >= $hStart && $currentISO <= $hEnd;
                    }
                    return $hDate === $currentISO;
                });

                $targetSeasonName = $holidayMatch ? $holidayMatch->classification : null;
                $matchedScore = null;

                if ($targetSeasonName) {
                    $normTarget = $this->normalizeStr($targetSeasonName);
                    $holidayScores = $acc->scores->filter(fn($s) => $s->season && $this->normalizeStr($s->season->name) === $normTarget);
                    $matchedScore = $this->findBestScoreForSeason($holidayScores, $currentPax);
                }

                if (!$matchedScore) {
                    $monthScores = $acc->scores->filter(function($s) use ($normMonth) {
                        if (!$s->season || !$s->season->months_active) return false;
                        $months = $s->season->months_active;
                        if (is_string($months)) {
                            $months = json_decode($months, true) ?? [];
                        }
                        if (!is_array($months)) $months = [];
                        return collect($months)->contains(fn($m) => $this->normalizeStr($m) === $normMonth);
                    });
                    $matchedScore = $this->findBestScoreForSeason($monthScores, $currentPax);
                }

                if (!$matchedScore) {
                    $matchedScore = $this->findBestScoreForSeason($acc.scores, $currentPax);
                }

                $baseWeekly = (float)($matchedScore->points ?? 0);
                $dailyPoints = (int)round($baseWeekly / 7);
                $totalPoints += $dailyPoints;
            }

            return (float)$totalPoints;
        } catch (\Exception $e) {
            return 0.0;
        }
    }

    private function findBestScoreForSeason($scores, int $currentPax)
    {
        if (!$scores || $scores->isEmpty()) return null;

        $exact = $scores->first(fn($s) => (int)$s->pax === $currentPax);
        if ($exact) return $exact;

        return $scores->sortBy(fn($s) => abs((int)$s->pax - $currentPax))->first();
    }

    private function normalizeStr(?string $str): string
    {
        if (!$str) return '';
        $unaccented = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
        return strtolower(trim($unaccented !== false ? $unaccented : $str));
    }
}

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
}

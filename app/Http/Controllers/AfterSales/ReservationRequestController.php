<?php

namespace App\Http\Controllers\AfterSales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReservationRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = \App\Models\ReservationRequest::with(['service.client', 'user'])->latest();
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('service.client', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('cpf', 'like', "%{$search}%");
            });
        }

        $reservations = $query->paginate(15)->withQueryString();

        return \Inertia\Inertia::render('AfterSales/ReservationRequest/Index', [
            'reservations' => $reservations,
            'filters' => $request->only('search'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sales_service_id' => 'required|exists:sales_services,id',
            'destination' => 'required|string|max:255',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'adults' => 'required|integer|min:1',
            'children' => 'nullable|integer|min:0',
            'guests_list' => 'nullable|array',
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
            'status' => 'required|in:pending,analyzing,confirmed,canceled',
            'observations' => 'nullable|string',
        ]);

        $reservation->update($validated);

        return back()->with('success', 'Solicitação de reserva atualizada com sucesso.');
    }

    public function destroy(\App\Models\ReservationRequest $reservation)
    {
        $reservation->delete();
        return back()->with('success', 'Solicitação de reserva removida com sucesso.');
    }
}

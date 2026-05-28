<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentMethodController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Admin/Settings/PaymentMethod/Index', [
            'paymentMethods' => PaymentMethod::all()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        PaymentMethod::create($validated);

        return redirect()->back()->with('success', 'Forma de pagamento criada com sucesso.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PaymentMethod $paymentMethod)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $paymentMethod->update($validated);

        return redirect()->back()->with('success', 'Forma de pagamento atualizada com sucesso.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PaymentMethod $paymentMethod)
    {
        $paymentMethod->delete();

        return redirect()->back()->with('success', 'Forma de pagamento removida com sucesso.');
    }

    /**
     * API for products/proposals selection.
     */
    public function apiList()
    {
        return response()->json(
            PaymentMethod::where('is_active', true)
                ->get(['id', 'name', 'type'])
                ->map(fn($m) => [
                    'label' => $m->name,
                    'value' => $m->name, // Mantemos o nome como valor para compatibilidade com o que é salvo hoje, ou podemos mudar para ID se preferir
                    'type' => $m->type
                ])
        );
    }
}

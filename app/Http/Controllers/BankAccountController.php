<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BankAccountController extends Controller
{
    public function index()
    {
        $accounts = BankAccount::orderBy('name')->get();
        return Inertia::render('Admin/Settings/BankAccount/Index', [
            'accounts' => $accounts
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'owner_type' => 'required|string|in:PROPRIETARIO,COMERCIALIZADORA',
            'bank_name' => 'nullable|string|max:255',
            'agency' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'gateway' => 'nullable|string|max:50',
            'api_token' => 'nullable|string',
            'webhook_secret' => 'nullable|string|max:255',
        ]);

        BankAccount::create($validated);

        return redirect()->back()->with('success', 'Conta bancária cadastrada com sucesso!');
    }

    public function update(Request $request, BankAccount $bankAccount)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'owner_type' => 'required|string|in:PROPRIETARIO,COMERCIALIZADORA',
            'bank_name' => 'nullable|string|max:255',
            'agency' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'gateway' => 'nullable|string|max:50',
            'api_token' => 'nullable|string',
            'webhook_secret' => 'nullable|string|max:255',
        ]);

        $bankAccount->update($validated);

        return redirect()->back()->with('success', 'Conta bancária atualizada com sucesso!');
    }

    public function destroy(BankAccount $bankAccount)
    {
        if ($bankAccount->paymentMethods()->exists() || $bankAccount->bills()->exists()) {
            return redirect()->back()->with('error', 'Esta conta não pode ser excluída pois possui vínculos (parcelas ou formas de pagamento). Você pode inativá-la em vez disso.');
        }

        $bankAccount->delete();

        return redirect()->back()->with('success', 'Conta bancária excluída com sucesso!');
    }
}

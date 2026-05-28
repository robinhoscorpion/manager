<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Employees/Index', [
            'employees' => Employee::with('user')->latest()->paginate(10)
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Employees/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'nullable|string|max:255',
            'email' => 'required|string|email|max:255|unique:' . Employee::class,
            'position' => 'required|string|max:255',
            'salary' => 'required|numeric|min:0',
            'type' => 'required|string|max:255',
            'workload' => 'required|string|max:255',
            'status' => 'boolean',
            'is_active' => 'boolean',
            'hired_at' => 'nullable|date',
        ]);

        Employee::create($validated);

        return redirect()->route('employees.index')->with('message', 'Funcionário criado com sucesso!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Employee $employee)
    {
        return Inertia::render('Employees/Edit', [
            'employee' => $employee
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'nullable|string|max:255',
            'email' => 'required|string|email|max:255|unique:' . Employee::class . ',email,' . $employee->id,
            'position' => 'required|string|max:255',
            'salary' => 'required|numeric|min:0',
            'type' => 'required|string|max:255',
            'workload' => 'required|string|max:255',
            'status' => 'boolean',
            'is_active' => 'boolean',
            'hired_at' => 'nullable|date',
        ]);

        $employee->update($validated);

        return redirect()->route('employees.index')->with('message', 'Dados do funcionário atualizados!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Employee $employee)
    {
        $employee->delete();

        return redirect()->route('employees.index')->with('message', 'Funcionário removido com sucesso!');
    }

    public function convertFromUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'position' => 'required|string|max:255',
            'salary' => 'required|numeric|min:0',
            'type' => 'required|string|max:255',
            'workload' => 'required|string|max:255',
            'hired_at' => 'nullable|date',
        ]);

        $user->employee()->create(array_merge($validated, [
            'name' => $user->name,
            'email' => $user->email,
            'status' => true,
            'is_active' => true,
        ]));

        return redirect()->back()->with('message', 'Usuário transformado em funcionário!');
    }
}

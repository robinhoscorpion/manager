<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Schedule;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $query = Schedule::query();

        // Controle de Acesso Restrito (RBAC)
        $user = auth()->user();
        // Se o usuário não for admin e não tiver a permissão "agendamentos.ver_todos"
        if ($user && !$user->roles->contains('slug', 'admin') && !$user->roles->flatMap->permissions->pluck('slug')->contains('agendamentos.ver_todos')) {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        } else {
            $query->whereDate('date', '>=', now()->toDateString());
        }

        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        } else {
            $query->whereDate('date', '<=', now()->toDateString());
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $schedules = $query->orderBy('date', 'asc')
            ->orderBy('time', 'asc')
            ->get();

        $metrics = [
            'today' => Schedule::whereDate('date', now()->toDateString())->count(),
            'scheduled' => Schedule::where('status', 'scheduled')->whereDate('date', '>=', now()->toDateString())->count(),
            'confirmed' => Schedule::where('status', 'confirmed')->whereDate('date', '>=', now()->toDateString())->count(),
        ];

        return Inertia::render('Sales/Scheduling/Index', [
            'schedules' => $schedules,
            'metrics' => $metrics,
            'filters' => [
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'search' => $request->search,
                'status' => $request->status,
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'date' => 'required|date',
            'time' => 'nullable|date_format:H:i',
            'observations' => 'nullable|string',
        ]);

        $validated['user_id'] = auth()->id();
        $validated['status'] = 'scheduled';

        Schedule::create($validated);

        return back()->with('success', 'Agendamento criado com sucesso!');
    }

    public function updateStatus(Request $request, Schedule $schedule)
    {
        $validated = $request->validate([
            'status' => 'required|in:scheduled,confirmed,show,no_show,cancelled'
        ]);

        $schedule->update(['status' => $validated['status']]);

        if ($validated['status'] === 'show') {
            \Illuminate\Support\Facades\DB::transaction(function () use ($schedule) {
                $client = \App\Models\Client::create([
                    'nome' => $schedule->name,
                    'celular1' => $schedule->phone ?: '00000000000',
                    'email' => $schedule->email,
                ]);

                // Create associated address to prevent errors in Atendimentos queries
                $client->address()->create([
                    'cep' => '',
                    'rua' => '',
                    'bairro' => '',
                    'numero' => '',
                    'cidade' => '',
                    'estado' => '',
                ]);

                $client->services()->create([
                    'date' => $schedule->date,
                    'time' => $schedule->time ?? '00:00',
                    'clients' => $schedule->name,
                    'local' => 'SALA',
                    'status' => 'table', // Using 'table' as 'fila' was removed
                    'qualification' => 'Q',
                    'observacoes' => $schedule->observations,
                ]);
            });

            return back()->with('success', 'Status atualizado e Ficha de Atendimento gerada na Mesa!');
        }

        return back()->with('success', 'Status atualizado com sucesso!');
    }
}

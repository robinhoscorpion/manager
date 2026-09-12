<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Schedule;
use App\Models\ComplimentaryItem;
use App\Models\User;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $query = Schedule::query();

        // Controle de Acesso Restrito (RBAC)
        $user = auth()->user();
        // Se o usuário não for admin e não tiver a permissão "agendamentos.ver_todos"
        if ($user && !$user->hasRole('admin') && !$user->hasPermission('agendamentos.ver_todos')) {
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
            'complimentaryItems' => ComplimentaryItem::where('is_active', true)->where('type', 'atendimento')->orderBy('name')->get(),
            'users' => User::select('id', 'name')->with('roles:name,slug')->get()->map(function($u) {
                return [ 'id' => $u->id, 'name' => $u->name, 'roles' => $u->roles->pluck('slug') ];
            }),
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
            'nacionalidade' => 'nullable|string|max:255',
            'data_nascimento' => 'nullable|date',
            'profissao' => 'nullable|string|max:255',
            'date' => 'required|date',
            'time' => 'nullable|date_format:H:i',
            'has_spouse' => 'nullable|boolean',
            'spouse_name' => 'nullable|string|max:255',
            'spouse_phone' => 'nullable|string|max:20',
            'spouse_email' => 'nullable|email|max:255',
            'spouse_nacionalidade' => 'nullable|string|max:255',
            'spouse_data_nascimento' => 'nullable|date',
            'spouse_profissao' => 'nullable|string|max:255',
            'observations' => 'nullable|string',
            'renda_familiar' => 'nullable|string|max:50',
            'cortesia' => 'nullable|array',
            'cortesia.*' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $validated['user_id'] = $request->input('user_id') ?? auth()->id();
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
                    'nacionalidade' => $schedule->nacionalidade,
                    'data_nascimento' => $schedule->data_nascimento,
                    'profissao' => $schedule->profissao,
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

                $clientsName = $schedule->name;
                if ($schedule->has_spouse && $schedule->spouse_name) {
                    $clientsName .= ' & ' . $schedule->spouse_name;
                }

                $client->services()->create([
                    'date' => $schedule->date,
                    'time' => $schedule->time ?? '00:00',
                    'clients' => $clientsName,
                    'local' => 'SALA',
                    'status' => 'table', // Using 'table' as 'fila' was removed
                    'qualification' => 'Q',
                    'tem_conjuge' => $schedule->has_spouse ?? false,
                    'nome_conjuge' => $schedule->spouse_name,
                    'celular_conjuge' => $schedule->spouse_phone,
                    'email_conjuge' => $schedule->spouse_email,
                    'nacionalidade_conjuge' => $schedule->spouse_nacionalidade,
                    'data_nascimento_conjuge' => $schedule->spouse_data_nascimento,
                    'profissao_conjuge' => $schedule->spouse_profissao,
                    'tipo_relacionamento' => 'Casal/Namorados', // default
                    'observacoes' => $schedule->observations,
                    'renda_familiar' => $schedule->renda_familiar,
                    'cortesia' => $schedule->cortesia,
                    'opc_id' => $schedule->user_id,
                ]);
            });

            return back()->with('success', 'Status atualizado e Ficha de Atendimento gerada na Mesa!');
        }

        return back()->with('success', 'Status atualizado com sucesso!');
    }
}

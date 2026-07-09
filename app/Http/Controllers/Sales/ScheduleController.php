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

        return back()->with('success', 'Status atualizado com sucesso!');
    }
}

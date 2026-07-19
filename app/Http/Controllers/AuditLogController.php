<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user')->orderBy('created_at', 'desc');

        // Simple filtering
        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        if ($request->filled('module')) {
            $query->where('auditable_type', 'like', "%{$request->module}%");
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('date_start')) {
            $query->whereDate('created_at', '>=', $request->date_start);
        }

        if ($request->filled('date_end')) {
            $query->whereDate('created_at', '<=', $request->date_end);
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('description', 'like', "%{$request->search}%")
                  ->orWhereHas('user', function($qu) use ($request) {
                      $qu->where('name', 'like', "%{$request->search}%");
                  });
            });
        }

        // Get distinct users who have logs to populate the filter dropdown
        $usersWithLogs = \App\Models\User::whereIn('id', AuditLog::select('user_id')->distinct())
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('AuditLogs/Index', [
            'logs' => $query->paginate(20)->withQueryString(),
            'users' => $usersWithLogs,
            'filters' => $request->only(['event', 'module', 'user_id', 'date_start', 'date_end', 'search'])
        ]);
    }
}

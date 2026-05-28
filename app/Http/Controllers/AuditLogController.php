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
        if ($request->has('event')) {
            $query->where('event', $request->event);
        }

        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('auditable_type', 'like', "%{$request->search}%")
                  ->orWhere('description', 'like', "%{$request->search}%")
                  ->orWhereHas('user', function($qu) use ($request) {
                      $qu->where('name', 'like', "%{$request->search}%");
                  });
            });
        }

        return Inertia::render('AuditLogs/Index', [
            'logs' => $query->paginate(20)->withQueryString(),
            'filters' => $request->only(['event', 'search'])
        ]);
    }
}

<?php

namespace App\Http\Controllers\AfterSales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Protocol;

class ProtocolController extends Controller
{
    public function index(Request $request)
    {
        $query = Protocol::with(['service.client', 'service.proposal', 'user', 'replies.user']);

        // Default to today if no date is provided, and no search is performed
        if (!$request->has('start_date') && !$request->has('end_date') && !$request->filled('search') && !$request->filled('status') && !$request->filled('priority')) {
            $query->whereDate('created_at', today());
        } else {
            if ($request->filled('start_date')) {
                $query->whereDate('created_at', '>=', $request->start_date);
            }
            if ($request->filled('end_date')) {
                $query->whereDate('created_at', '<=', $request->end_date);
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('protocol_number', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhereHas('service.client', function ($q) use ($search) {
                      $q->where('nome', 'like', "%{$search}%");
                  });
            });
        }

        $protocols = $query->orderBy('created_at', 'desc')->paginate(20)->through(function ($protocol) {
            return [
                'id' => $protocol->id,
                'protocol_number' => $protocol->protocol_number,
                'subject' => $protocol->subject,
                'priority' => $protocol->priority,
                'status' => $protocol->status,
                'created_at' => $protocol->created_at->format('d/m/Y H:i'),
                'user' => $protocol->user ? $protocol->user->name : 'Sistema',
                'client_name' => $protocol->service && $protocol->service->client ? $protocol->service->client->nome : 'N/A',
                'service_id' => $protocol->sales_service_id,
                'contract_number' => $protocol->service && $protocol->service->proposal ? $protocol->service->proposal->contract_number : null,
                'description' => $protocol->message,
                'replies' => $protocol->replies->map(function ($reply) {
                    return [
                        'id' => $reply->id,
                        'message' => $reply->message,
                        'user_name' => $reply->user ? $reply->user->name : 'Usuário Removido',
                        'created_at' => $reply->created_at->format('d/m/Y H:i'),
                    ];
                }),
            ];
        });

        $metrics = [
            'total_today' => Protocol::whereDate('created_at', today())->count(),
            'pending' => Protocol::where('status', 'aberto')->orWhere('status', 'open')->count(),
        ];

        return Inertia::render('AfterSales/Protocol/Index', [
            'protocols' => $protocols,
            'metrics' => $metrics,
            'filters' => $request->only(['start_date', 'end_date', 'status', 'priority', 'search']),
        ]);
    }
}

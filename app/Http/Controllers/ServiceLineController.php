<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ServiceLinePosition;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ServiceLineController extends Controller
{
    /**
     * Group keywords mapping based on Spatie roles. Returns null if the user doesn't match any target group.
     */
    private function determineGroup(User $user): ?string
    {
        $roles = $user->roles->pluck('name')->map(fn($role) => strtolower(trim($role)))->toArray();

        // 1. Supervisor / Closer
        if (array_intersect($roles, ['closer', 'supervisor', 'gerente', 'gerente de sala de vendas'])) {
            return 'supervisor';
        }

        // 2. Consultor / Liner
        if (array_intersect($roles, ['liner', 'liner opc', 'consultor'])) {
            return 'consultor';
        }

        // 3. Promotor / OPC
        if (array_intersect($roles, ['opc', 'promotor', 'captador'])) {
            return 'promotor';
        }

        // Not a sales team user
        return null;
    }

    /**
     * Show the service line management page.
     */
    public function index()
    {
        // Ensure all active sales users have a line position record
        $users = User::with('roles')->where('status', true)->get();

        foreach ($users as $user) {
            $group = $this->determineGroup($user);
            
            // Only add to queue if they belong to a sales group
            if (!$group) {
                continue;
            }

            $existingPosition = ServiceLinePosition::where('user_id', $user->id)->first();

            if (!$existingPosition) {
                $maxOrder = ServiceLinePosition::where('group', $group)->max('position_order') ?? 0;
                ServiceLinePosition::create([
                    'user_id'        => $user->id,
                    'group'          => $group,
                    'position_order' => $maxOrder + 1,
                    'status'         => 'available',
                ]);
            }
        }

        // Load all positions grouped
        $rawGroups = ServiceLinePosition::with(['user.roles'])
            ->orderBy('position_order')
            ->get()
            ->groupBy('group');

        $groups = [
            'promotor'   => $rawGroups->get('promotor', collect())->values(),
            'consultor'  => $rawGroups->get('consultor', collect())->values(),
            'supervisor' => $rawGroups->get('supervisor', collect())->values(),
        ];

        return Inertia::render('Sales/ServiceLine/Index', [
            'groups' => $groups,
        ]);
    }

    /**
     * Reorder employees within a group.
     * Expects: { group: string, ordered_ids: int[] }
     */
    public function updateOrder(Request $request)
    {
        $validated = $request->validate([
            'group'       => 'required|string|in:promotor,consultor,supervisor',
            'ordered_ids' => 'required|array',
            'ordered_ids.*' => 'integer|exists:service_line_positions,id',
        ]);

        foreach ($validated['ordered_ids'] as $index => $id) {
            ServiceLinePosition::where('id', $id)
                ->where('group', $validated['group'])
                ->update(['position_order' => $index + 1]);
        }

        return back();
    }

    /**
     * Update the status of a single employee in the queue.
     * Expects: { status: string }
     */
    public function updateStatus(Request $request, ServiceLinePosition $position)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:available,busy,absent',
        ]);

        $position->update(['status' => $validated['status']]);

        return back();
    }

    /**
     * Move a specific employee to the end of their group queue and mark as "Em Atendimento".
     */
    public function callNext(Request $request, string $group)
    {
        $request->validate([
            'group' => 'sometimes|string',
        ]);

        // Find the first available person in the group (lowest position_order)
        $next = ServiceLinePosition::where('group', $group)
            ->where('status', 'available')
            ->orderBy('position_order')
            ->first();

        if (!$next) {
            return back()->with('error', 'Nenhum funcionário disponível na fila.');
        }

        // Mark as busy
        $next->update(['status' => 'busy']);

        return back()->with('success', 'Próximo chamado!');
    }

    /**
     * Reset all statuses in a group to "available" and re-sort alphabetically.
     */
    public function reset(string $group)
    {
        $positions = ServiceLinePosition::where('group', $group)
            ->with('user')
            ->get()
            ->sortBy(fn($p) => $p->user->name ?? '')
            ->values();

        foreach ($positions as $index => $position) {
            $position->update([
                'status'          => 'available',
                'position_order'  => $index + 1,
            ]);
        }

        return back()->with('success', 'Fila reiniciada!');
    }
}

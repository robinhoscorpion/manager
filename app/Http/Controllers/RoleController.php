<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    public function index()
    {
        return Inertia::render('Roles/Index', [
            'roles' => Role::with('permissions')->get(),
            'all_permissions' => Permission::all()->groupBy('group'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'description' => 'nullable|string',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        Role::create($validated);

        return redirect()->back()->with('message', 'Cargo criado com sucesso!');
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'description' => 'nullable|string',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        
        $role->update($validated);

        return redirect()->back()->with('message', 'Cargo atualizado com sucesso!');
    }

    public function updatePermissions(Request $request, Role $role)
    {
        $validated = $request->validate([
            'permissions' => 'required|array',
        ]);

        $role->permissions()->sync($validated['permissions']);

        return redirect()->back()->with('message', 'Permissões atualizadas com sucesso!');
    }

    public function destroy(Role $role)
    {
        $protectedSlugs = ['admin', 'promotor', 'consultor', 'supervisor'];

        if (in_array($role->slug, $protectedSlugs)) {
            return redirect()->back()->with('error', "O cargo {$role->name} é um cargo crítico do sistema e não pode ser excluído!");
        }

        $role->delete();

        return redirect()->back()->with('message', 'Cargo removido com sucesso!');
    }
}

<?php

namespace App\Http\Controllers;
 
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
 
class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Users/Index', [
            'users' => User::with('roles')->latest()->get(),
            'roles' => Role::all(),
        ]);
    }
 
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'roles' => 'required|array|min:1',
        ]);
 
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);
 
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('profile-photos', 'public');
            $user->update(['profile_photo_path' => $path]);
        }

        $user->roles()->sync($request->roles);

        return redirect()->back()->with('message', 'Usuário criado com sucesso!');
    }
 
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'roles' => 'required|array|min:1',
        ]);
 
        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        if ($request->hasFile('photo')) {
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }
            $path = $request->file('photo')->store('profile-photos', 'public');
            $user->update(['profile_photo_path' => $path]);
        }

        $user->roles()->sync($request->roles);
 
        if ($request->password) {
            $user->update(['password' => Hash::make($request->password)]);
        }
 
        return redirect()->back()->with('message', 'Usuário atualizado com sucesso!');
    }
 
    /**
     * Toggle the status of the specified resource.
     */
    public function toggleStatus(User $user)
    {
        // Prevent users from deactivating themselves
        if (auth()->id() === $user->id) {
            return redirect()->back()->with('error', 'Você não pode desativar seu próprio acesso.');
        }

        // Prevent disabling the only active user to avoid locking everyone out
        if (User::where('status', true)->count() <= 1 && $user->status) {
            return redirect()->back()->with('error', 'Não é possível desativar o único usuário ativo do sistema.');
        }

        $user->update([
            'status' => !$user->status
        ]);

        return redirect()->back()->with('message', 'Status do usuário atualizado com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        // Prevent users from deleting themselves
        if (auth()->id() === $user->id) {
            return redirect()->back()->with('error', 'Você não pode remover seu próprio acesso.');
        }

        // Don't delete the last user
        if (User::count() <= 1) {
            return redirect()->back()->with('error', 'Não é possível remover o único usuário.');
        }
 
        // Check if user is linked to any sales service
        $hasServices = \App\Models\SalesService::where('opc_id', $user->id)
            ->orWhere('liner_id', $user->id)
            ->orWhere('closer_id', $user->id)
            ->orWhere('mkt_id', $user->id)
            ->exists();

        if ($hasServices) {
            return redirect()->back()->with('error', 'Este usuário está vinculado a um ou mais atendimentos e não pode ser excluído.');
        }

        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        $user->delete();
        return redirect()->back()->with('message', 'Usuário removido com sucesso!');
    }

    /**
     * Impersonate a user safely.
     */
    public function impersonate(User $user)
    {
        if (auth()->id() === $user->id) {
            return redirect()->back()->with('error', 'Você já está acessando esta conta.');
        }

        // Only allow admins or those with 'usuarios.gerenciar' to impersonate.
        // Handled by middleware 'permission:usuarios.gerenciar' in routes.

        session()->put('impersonated_by', auth()->id());
        auth()->login($user);

        return redirect()->route('dashboard')->with('message', 'Você acessou a conta de ' . $user->name);
    }

    /**
     * Leave impersonation and return to original account.
     */
    public function leaveImpersonation()
    {
        if (session()->has('impersonated_by')) {
            $adminId = session()->pull('impersonated_by');
            auth()->loginUsingId($adminId);
            return redirect()->route('users.index')->with('message', 'Você retornou para a sua conta principal.');
        }

        return redirect()->route('dashboard');
    }
}

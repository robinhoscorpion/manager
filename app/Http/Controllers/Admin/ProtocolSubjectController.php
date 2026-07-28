<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProtocolSubject;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProtocolSubjectController extends Controller
{
    public function index()
    {
        $subjects = ProtocolSubject::orderBy('name')->get();
        return Inertia::render('Admin/ProtocolSubjects/Index', [
            'subjects' => $subjects
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:protocol_subjects,name',
            'active' => 'boolean'
        ]);

        ProtocolSubject::create([
            'name' => $request->name,
            'active' => $request->has('active') ? $request->active : true
        ]);

        return redirect()->back()->with('success', 'Assunto de protocolo criado com sucesso.');
    }

    public function update(Request $request, ProtocolSubject $protocolSubject)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:protocol_subjects,name,' . $protocolSubject->id,
            'active' => 'boolean'
        ]);

        $protocolSubject->update($request->only(['name', 'active']));

        return redirect()->back()->with('success', 'Assunto de protocolo atualizado com sucesso.');
    }

    public function destroy(ProtocolSubject $protocolSubject)
    {
        $protocolSubject->delete();
        return redirect()->back()->with('success', 'Assunto de protocolo excluído com sucesso.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\SalesService;
use App\Models\Client;
use App\Models\Address;
use App\Models\Qualification;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use App\Models\ComplimentaryItem;

class SalesServiceController extends Controller
{
    public function index(Request $request)
    {
        $query = SalesService::with(['client.address', 'proposal.payments', 'proposal.product', 'opcUser', 'linerUser', 'closerUser', 'mktUser']);

        // Filtro de Busca (Local)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('client', function($cq) use ($search) {
                    $cq->where('nome', 'like', "%{$search}%")
                       ->orWhere('cpf', 'like', "%{$search}%")
                       ->orWhere('email', 'like', "%{$search}%")
                       ->orWhere('celular1', 'like', "%{$search}%");
                })->orWhereHas('proposal', function($pq) use ($search) {
                    $pq->where('contract_number', 'like', "%{$search}%");
                });
            });
        }

        return Inertia::render('Sales/Service/Index', [
            'services' => $query->orderBy('created_at', 'desc')->get(),
            'filters' => $request->only(['search']),
            'qualifications' => Qualification::where('is_active', true)->get(),
            'complimentaryItems' => ComplimentaryItem::where('is_active', true)->where('type', 'atendimento')->orderBy('name')->get(),
            'columnSettings' => \App\Models\Setting::where('key', 'service_list_columns')->first()?->value ?? [
                'id' => true,
                'date' => true,
                'time' => true,
                'clients' => true,
                'mkt' => true,
                'opc' => true,
                'liner' => true,
                'closer' => true,
                'qualification' => true,
                'status' => true,
                'actions' => true,
            ],
            'availableAvatars' => \App\Models\User::with('roles')->where('status', true)->get()->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'roles' => $user->roles->pluck('name')->map(fn($r) => strtolower(trim($r)))->toArray(),
                    'path' => $user->profile_photo_url,
                ];
            })->toArray()
        ]);
    }

    public function store(Request $request)
    {
        $rules = [
            'date' => 'required|string',
            'time' => 'required|string',
            'local' => 'required|string',
            'opc_id' => 'required|exists:users,id',
            'qualification' => 'required|string',
            
            // Titular
            'nome' => 'required|string|max:255',
            'cpf' => 'nullable|string|max:14',
            'rg' => 'nullable|string|max:20',
            'nacionalidade' => 'nullable|string|max:100',
            'dataNascimento' => 'required|date',
            'profissao' => 'required|string',
            'estadoCivil' => 'required|string',
            'celular1' => 'required|string',
            'email' => 'required|email|max:255',
            
            // Família
            'quantidadeFilhos' => 'required|integer|min:0',
            'tempoJuntos' => 'required|string',
            'rendaFamiliar' => 'required|string',
            
            // Endereço
            'cep' => 'required|string',
            'rua' => 'required|string',
            'bairro' => 'required|string',
            'numero' => 'required|string',
            'cidade' => 'required|string',
            'estado' => 'required|string',
            
            // Logística
            'cortesia' => 'required|array',
            
            // Opcionais
            'celular2' => 'nullable|string',
            'complemento' => 'nullable|string',
            'pontoReferencia' => 'nullable|string',
            'observacoes' => 'nullable|string',
            'temConjuge' => 'boolean',
        ];

        // Validação Condicional do Cônjuge
        if ($request->temConjuge) {
            $rules['nomeConjuge'] = 'required|string|max:255';
            $rules['dataNascimentoConjuge'] = 'required|date';
            $rules['profissaoConjuge'] = 'required|string';
        }

        $messages = [
            'required' => 'O campo :attribute é obrigatório.',
            'date' => 'A data informada no campo :attribute não é válida.',
            'email' => 'O formato do e-mail informado no campo :attribute é inválido.',
            'integer' => 'O campo :attribute deve ser um número inteiro.',
            'min' => 'O campo :attribute deve ter pelo menos :min.',
            'max' => 'O campo :attribute não pode ultrapassar :max caracteres.',
            'boolean' => 'O campo :attribute deve ser verdadeiro ou falso.',
        ];

        $attributes = [
            'date' => 'Data',
            'time' => 'Hora',
            'local' => 'Local',
            'status' => 'Status',
            'opc_id' => 'OPC (Atendente)',
            'qualification' => 'Qualificação',
            'nome' => 'Nome',
            'dataNascimento' => 'Data de Nascimento',
            'profissao' => 'Profissão',
            'estadoCivil' => 'Estado Civil',
            'celular1' => 'Celular',
            'email' => 'E-mail',
            'quantidadeFilhos' => 'Quantidade de Filhos',
            'tempoJuntos' => 'Tempo Juntos',
            'rendaFamiliar' => 'Renda Familiar',
            'cep' => 'CEP',
            'rua' => 'Rua',
            'bairro' => 'Bairro',
            'numero' => 'Número',
            'cidade' => 'Cidade',
            'estado' => 'Estado',
            'cortesia' => 'Cortesia',
            'nomeConjuge' => 'Nome do Cônjuge',
            'dataNascimentoConjuge' => 'Data de Nascimento do Cônjuge',
            'profissaoConjuge' => 'Profissão do Cônjuge',
        ];

        $request->validate($rules, $messages, $attributes);

        DB::transaction(function() use ($request) {
            // 1. Criar Cliente
            $client = Client::create([
                'nome' => $request->nome,
                'cpf' => $request->cpf,
                'rg' => $request->rg,
                'nacionalidade' => $request->nacionalidade ?? 'Brasileira',
                'data_nascimento' => $request->dataNascimento,
                'idade' => $request->idade,
                'profissao' => $request->profissao,
                'estado_civil' => $request->estadoCivil,
                'celular1' => $request->celular1,
                'celular2' => $request->celular2,
                'email' => $request->email,
            ]);

            // 2. Criar Endereço associado ao Cliente
            $client->address()->create([
                'cep' => $request->cep,
                'rua' => $request->rua,
                'bairro' => $request->bairro,
                'numero' => $request->numero,
                'cidade' => $request->cidade,
                'estado' => $request->estado,
                'pais' => $request->pais ?? 'Brasil', // Assuming 'pais' might not be validated but can be set
                'complemento' => $request->complemento,
                'ponto_referencia' => $request->pontoReferencia,
            ]);

            // 3. Criar Atendimento associado ao Cliente
            $client->services()->create([
                'date' => $request->date,
                'time' => $request->time,
                'clients' => $request->clients,
                'local' => $request->local,
                'opc_id' => $request->opc_id,
                'qualification' => $request->qualification,
                'status' => SalesService::STATUS_MESA,
                
                // Cônjuge
                'tem_conjuge' => $request->temConjuge,
                'nome_conjuge' => $request->nomeConjuge,
                'data_nascimento_conjuge' => $request->dataNascimentoConjuge,
                'idade_conjuge' => $request->idadeConjuge, // Assuming 'idadeConjuge' is passed or calculated
                'profissao_conjuge' => $request->profissaoConjuge,
                
                // Família
                'quantidade_filhos' => $request->quantidadeFilhos,
                'tempo_juntos' => $request->tempoJuntos,
                'renda_familiar' => $request->rendaFamiliar,
                
                // Logística
                'cortesia' => $request->cortesia,
                'observacoes' => $request->observacoes,
            ]);
        });

    }

    public function update(Request $request, SalesService $service)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'celular1' => 'required|string|max:20',
            'date' => 'required|string',
            'time' => 'required|string',
            'local' => 'required|string',
            'cortesia' => 'required|array',
        ]);

        DB::transaction(function () use ($request, $service) {
            // 1. Atualizar Cliente
            $client = $service->client;
            $client->update([
                'nome' => $request->nome,
                'cpf' => $request->cpf,
                'rg' => $request->rg,
                'nacionalidade' => $request->nacionalidade,
                'data_nascimento' => $request->dataNascimento,
                'idade' => $request->idade,
                'profissao' => $request->profissao,
                'estado_civil' => $request->estadoCivil,
                'celular1' => $request->celular1,
                'celular2' => $request->celular2,
                'email' => $request->email,
            ]);

            // 2. Atualizar ou Criar Endereço
            $client->address()->updateOrCreate([], [
                'cep' => $request->cep,
                'rua' => $request->rua,
                'bairro' => $request->bairro,
                'numero' => $request->numero,
                'cidade' => $request->cidade,
                'estado' => $request->estado,
                'complemento' => $request->complemento,
                'ponto_referencia' => $request->pontoReferencia,
            ]);

            // 3. Atualizar Atendimento
            $service->update([
                'date' => $request->date,
                'time' => $request->time,
                'local' => $request->local,
                'opc_id' => $request->opc_id,
                'qualification' => $request->qualification,
                'status' => $request->status,
                'tem_conjuge' => $request->temConjuge,
                'nome_conjuge' => $request->nomeConjuge,
                'data_nascimento_conjuge' => $request->dataNascimentoConjuge,
                'idade_conjuge' => $request->idade_conjuge,
                'profissao_conjuge' => $request->profissao_conjuge,
                'quantidade_filhos' => $request->quantidadeFilhos,
                'tempo_juntos' => $request->tempoJuntos,
                'renda_familiar' => $request->rendaFamiliar,
                'cortesia' => $request->cortesia,
                'observacoes' => $request->observacoes,
            ]);

            // Sincronizar Cancelamento com a Proposta
            if ($service->status === SalesService::STATUS_CANCELADO && $service->proposal) {
                $service->proposal->update(['status' => 'cancelled']);
            }
        });

        return redirect()->back()->with('success', 'Atendimento atualizado com sucesso!');
    }

    public function quickUpdate(Request $request, SalesService $service)
    {
        $validated = $request->validate([
            'qualification' => 'nullable|string',
            'status' => 'nullable|string',
            'opc_id' => 'nullable|exists:users,id',
            'liner_id' => 'nullable|exists:users,id',
            'closer_id' => 'nullable|exists:users,id',
            'mkt_id' => 'nullable|exists:users,id',
            'opc' => 'nullable|boolean',
            'closer' => 'nullable|boolean',
        ]);

        $service->update($validated);
        
        // Sincronizar Cancelamento com a Proposta
        if ($service->status === SalesService::STATUS_CANCELADO && $service->proposal) {
            $service->proposal->update(['status' => 'cancelled']);
        }

        if ($request->qualification === 'Q') {
            return redirect()->back()->with('message', 'Atendimento qualificado como Q para proposta.');
        }

        return redirect()->back();
    }

    /**
     * Deleta o atendimento e todos os registros associados.
     */
    public function destroy(SalesService $service)
    {
        DB::transaction(function () use ($service) {
            if ($service->proposal) {
                $service->proposal->bills()->delete();
                $service->proposal->payments()->delete();
                $service->proposal->delete();
            }
            $service->delete();
        });

        return redirect()->back()->with('success', 'Atendimento e dados associados excluídos permanentemente!');
    }

    /**
     * Exibe o Dashboard Detalhado de um contrato/atendimento.
     */
    public function show(SalesService $service)
    {
        $service->load([
            'client.address', 
            'proposal.payments', 
            'proposal.bills',
            'proposal.product.proposalTemplate',
            'proposal.product.contractTemplate'
        ]);

        return Inertia::render('Sales/Service/Details', [
            'service' => $service
        ]);
    }

    /**
     * API para Busca Global (Command Palette).
     */
    public function globalSearch(Request $request)
    {
        $search = $request->query('q');
        
        if (empty($search)) {
            return response()->json([]);
        }

        $results = SalesService::with(['client', 'proposal'])
            ->whereHas('client', function($q) use ($search) {
                $q->where('nome', 'like', "%{$search}%")
                  ->orWhere('cpf', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('celular1', 'like', "%{$search}%");
            })
            ->orWhereHas('proposal', function($q) use ($search) {
                $q->where('contract_number', 'like', "%{$search}%");
            })
            ->limit(10)
            ->get()
            ->map(function($service) {
                return [
                    'id' => $service->id,
                    'title' => $service->client?->nome ?? 'Cliente não identificado',
                    'subtitle' => "Contrato: " . ($service->proposal?->contract_number ?? 'S/N') . " | CPF: " . ($service->client?->cpf ?? '-'),
                    'url' => route('sales.atendimentos.show', $service->id),
                    'type' => 'Contrato'
                ];
            });

        return response()->json($results);
    }

    /**
     * Gera o PDF nativo da cortesia de um atendimento específico, substituindo variáveis mágicas (Shortcodes).
     */
    public function pdfCortesia(SalesService $service)
    {
        // 1. O campo $service->cortesia pode ser um array (novo) ou string (antigo).
        $codes = $service->cortesia;
        if (!is_array($codes)) {
            $codes = $codes ? [$codes] : [];
        }

        // Filtra opções vazias ou 'nenhuma'
        $codes = array_filter($codes, fn($c) => $c && $c !== 'nenhuma');

        if (empty($codes)) {
            abort(404, 'Atendimento não possui Cortesias formais cadastradas.');
        }

        $items = ComplimentaryItem::whereIn('code', $codes)->get();

        if ($items->isEmpty()) {
            abort(404, 'Nenhum modelo de Cortesia encontrado para os códigos selecionados.');
        }

        // 2. Variáveis suportadas (Mail Merge Engine)
        $client = $service->client;
        
        $parseDate = function($date) {
            if (!$date) return null;
            try {
                if (str_contains($date, '/')) {
                    return \Carbon\Carbon::createFromFormat('d/m/Y', $date);
                }
                return \Carbon\Carbon::parse($date);
            } catch (\Exception $e) {
                return null;
            }
        };

        $replacements = [
            '[NOME_TITULAR]' => $client ? $client->nome : '',
            '[DATA_NASCIMENTO]' => ($client && $client->data_nascimento && ($d = $parseDate($client->data_nascimento))) ? $d->format('d/m/Y') : '',
            '[CPF]' => $client ? $client->cpf : '',
            '[EMAIL]' => $client ? $client->email : '',
            '[CELULAR]' => $client ? $client->celular1 : '',
            '[PROFISSAO]' => $client ? $client->profissao : '',
            
            // Cônjuge
            '[NOME_CONJUGE]' => $service->nome_conjuge ?? '',
            '[DATA_NASCIMENTO_CONJUGE]' => ($service->data_nascimento_conjuge && ($d = $parseDate($service->data_nascimento_conjuge))) ? $d->format('d/m/Y') : '',
            '[PROFISSAO_CONJUGE]' => $service->profissao_conjuge ?? '',
            
            // Serviço
            '[DATA]' => ($service->date && ($d = $parseDate($service->date))) ? $d->format('d/m/Y') : date('d/m/Y'),
            '[HORA]' => $service->time ?? '',
            '[LOCAL]' => $service->local ?? '',
            '[OBSERVACOES]' => $service->observacoes ?? '',
            '[ID_ATENDIMENTO]' => str_pad($service->id, 5, '0', STR_PAD_LEFT),
        ];

        // Endereço
        if ($client && $client->address) {
            $addr = $client->address;
            $replacements['[CEP]'] = $addr->cep ?? '';
            $replacements['[RUA]'] = $addr->rua ?? '';
            $replacements['[NUMERO]'] = $addr->numero ?? '';
            $replacements['[BAIRRO]'] = $addr->bairro ?? '';
            $replacements['[CIDADE]'] = $addr->cidade ?? '';
            $replacements['[ESTADO]'] = $addr->estado ?? '';
        }

        // 3. Processar cada item (Mail Merge)
        foreach ($items as $item) {
            $content = $item->content;
            foreach ($replacements as $tag => $val) {
                $content = str_replace($tag, $val, $content);
            }
            $item->processed_content = $content;
        }

        return view('pdf.complimentary-item', [
            'service' => $service,
            'items' => $items
        ]);
    }

    /**
     * Gera o PDF da Ficha de Atendimento (Resumo dos dados do cliente e atendimento).
     */
    public function pdfFicha(SalesService $service)
    {
        $client = $service->client;
        $client->load('address');

        $parseDate = function($date) {
            if (!$date) return null;
            try {
                if (str_contains($date, '/')) {
                    return \Carbon\Carbon::createFromFormat('d/m/Y', $date);
                }
                return \Carbon\Carbon::parse($date);
            } catch (\Exception $e) {
                return null;
            }
        };

        $formattedDates = [
            'service_date' => ($service->date && ($d = $parseDate($service->date))) ? $d->format('d/m/Y') : date('d/m/Y'),
            'client_birth' => ($client && $client->data_nascimento && ($d = $parseDate($client->data_nascimento))) ? $d->format('d/m/Y') : '-',
            'spouse_birth' => ($service->data_nascimento_conjuge && ($d = $parseDate($service->data_nascimento_conjuge))) ? $d->format('d/m/Y') : '-',
        ];

        return view('pdf.service-sheet', array_merge(compact('service', 'client'), $formattedDates));
    }

    /**
     * Gera o PDF da Proposta de Venda baseado no modelo HTML configurado para o produto.
     */
    public function pdfProposta(SalesService $service)
    {
        $service->load(['client.address', 'proposal.product.proposalTemplate']);
        
        $proposal = $service->proposal;
        if (!$proposal) {
            return redirect()->back()->with('error', 'Este atendimento ainda não possui uma proposta gerada.');
        }

        $template = $proposal->product?->proposalTemplate;
        
        // Se não houver template, podemos usar um padrão ou o primeiro ativo
        if (!$template) {
            $template = \App\Models\ProposalTemplate::where('is_active', true)->first();
        }

        if (!$template) {
            abort(404, 'Nenhum modelo de proposta configurado no sistema.');
        }

        $client = $service->client;
        
        $parseDate = function($date) {
            if (!$date) return null;
            try {
                if (str_contains($date, '/')) {
                    return \Carbon\Carbon::createFromFormat('d/m/Y', $date);
                }
                return \Carbon\Carbon::parse($date);
            } catch (\Exception $e) {
                return null;
            }
        };

        $replacements = [
            '[NOME_TITULAR]' => $client ? $client->nome : '',
            '[DATA_NASCIMENTO]' => ($client && $client->data_nascimento && ($d = $parseDate($client->data_nascimento))) ? $d->format('d/m/Y') : '',
            '[CPF]' => $client ? $client->cpf : '',
            '[EMAIL]' => $client ? $client->email : '',
            '[CELULAR]' => $client ? $client->celular1 : '',
            '[PROFISSAO]' => $client ? $client->profissao : '',
            
            // Cônjuge
            '[NOME_CONJUGE]' => $service->nome_conjuge ?? '',
            '[DATA_NASCIMENTO_CONJUGE]' => ($service->data_nascimento_conjuge && ($d = $parseDate($service->data_nascimento_conjuge))) ? $d->format('d/m/Y') : '',
            '[PROFISSAO_CONJUGE]' => $service->profissao_conjuge ?? '',
            
            // Serviço
            '[DATA]' => ($service->date && ($d = $parseDate($service->date))) ? $d->format('d/m/Y') : date('d/m/Y'),
            '[HORA]' => $service->time ?? '',
            '[LOCAL]' => $service->local ?? '',
            '[OBSERVACOES]' => $service->observacoes ?? '',
            '[ID_ATENDIMENTO]' => str_pad($service->id, 5, '0', STR_PAD_LEFT),

            // Proposta / Produto
            '[PRODUTO_NOME]' => $proposal->product?->name ?? 'Produto não especificado',
            '[VALOR_TOTAL]' => $proposal->total_value ? 'R$ ' . number_format($proposal->total_value, 2, ',', '.') : 'R$ 0,00',
            '[QUANTIDADE]' => $proposal->quantity ?? '1',
            '[METODO_PAGAMENTO]' => $proposal->payment_method ?? 'A combinar',
            '[NUMERO_CONTRATO]' => $proposal->contract_number ?? 'S/N',
        ];

        // Endereço
        if ($client && $client->address) {
            $addr = $client->address;
            $replacements['[CEP]'] = $addr->cep ?? '';
            $replacements['[RUA]'] = $addr->rua ?? '';
            $replacements['[NUMERO]'] = $addr->numero ?? '';
            $replacements['[BAIRRO]'] = $addr->bairro ?? '';
            $replacements['[CIDADE]'] = $addr->cidade ?? '';
            $replacements['[ESTADO]'] = $addr->estado ?? '';
        }

        $content = $template->content;
        foreach ($replacements as $tag => $val) {
            $content = str_replace($tag, $val, $content);
        }

        return view('pdf.proposal', [
            'service' => $service,
            'content' => $content
        ]);
    }

    /**
     * Gera o PDF do Contrato baseado no modelo HTML configurado (do produto ou global).
     */
    public function pdfContrato(SalesService $service)
    {
        $service->load(['client.address', 'proposal.product.contractTemplate']);
        
        $proposal = $service->proposal;
        if (!$proposal) {
            return redirect()->back()->with('error', 'Este atendimento ainda não possui uma proposta gerada para o contrato.');
        }

        $template = $proposal->product?->contractTemplate;
        
        // Se o produto não tiver contrato, usa o Global Padrão
        if (!$template) {
            $template = \App\Models\ContractTemplate::where('is_default', true)->first();
        }

        if (!$template) {
            abort(404, 'Nenhum modelo de contrato (específico ou global) configurado no sistema.');
        }

        $client = $service->client;
        
        $parseDate = function($date) {
            if (!$date) return null;
            try {
                if (str_contains($date, '/')) {
                    return \Carbon\Carbon::createFromFormat('d/m/Y', $date);
                }
                return \Carbon\Carbon::parse($date);
            } catch (\Exception $e) {
                return null;
            }
        };

        $replacements = [
            '[NOME_TITULAR]' => $client ? $client->nome : '',
            '[DATA_NASCIMENTO]' => ($client && $client->data_nascimento && ($d = $parseDate($client->data_nascimento))) ? $d->format('d/m/Y') : '',
            '[CPF]' => $client ? $client->cpf : '-',
            '[EMAIL]' => $client ? $client->email : '',
            '[CELULAR]' => $client ? $client->celular1 : '',
            '[PROFISSAO]' => $client ? $client->profissao : '',
            
            // Cônjuge
            '[NOME_CONJUGE]' => $service->nome_conjuge ?? '',
            '[DATA_NASCIMENTO_CONJUGE]' => ($service->data_nascimento_conjuge && ($d = $parseDate($service->data_nascimento_conjuge))) ? $d->format('d/m/Y') : '',
            '[PROFISSAO_CONJUGE]' => $service->profissao_conjuge ?? '',
            
            // Serviço
            '[DATA]' => ($service->date && ($d = $parseDate($service->date))) ? $d->format('d/m/Y') : date('d/m/Y'),
            '[HORA]' => $service->time ?? '',
            '[LOCAL]' => $service->local ?? '',
            '[ID_ATENDIMENTO]' => str_pad($service->id, 5, '0', STR_PAD_LEFT),

            // Proposta / Produto
            '[PRODUTO_NOME]' => $proposal->product?->name ?? 'Produto não especificado',
            '[VALOR_TOTAL]' => $proposal->total_value ? 'R$ ' . number_format($proposal->total_value, 2, ',', '.') : 'R$ 0,00',
            '[NUMERO_CONTRATO]' => $proposal->contract_number ?? 'S/N',
        ];

        // Endereço
        if ($client && $client->address) {
            $addr = $client->address;
            $replacements['[CEP]'] = $addr->cep ?? '';
            $replacements['[RUA]'] = $addr->rua ?? '';
            $replacements['[NUMERO]'] = $addr->numero ?? '';
            $replacements['[BAIRRO]'] = $addr->bairro ?? '';
            $replacements['[CIDADE]'] = $addr->cidade ?? '';
            $replacements['[ESTADO]'] = $addr->estado ?? '';
        }

        $content = $template->content;
        foreach ($replacements as $tag => $val) {
            $content = str_replace($tag, $val, $content);
        }

        return view('pdf.contract', [
            'service' => $service,
            'content' => $content
        ]);
    }

    /**
     * Gera o PDF da Proposta RCI (Variação da proposta padrão).
     */
    public function pdfRci(SalesService $service)
    {
        $service->load(['client.address', 'proposal.product.proposalTemplate']);
        
        $proposal = $service->proposal;
        if (!$proposal) {
            return redirect()->back()->with('error', 'Este atendimento ainda não possui uma proposta gerada para o RCI.');
        }

        $template = $proposal->product?->proposalTemplate;
        
        if (!$template) {
            $template = \App\Models\ProposalTemplate::where('is_active', true)->first();
        }

        if (!$template) {
            abort(404, 'Nenhum modelo de proposta/rci configurado no sistema.');
        }

        $client = $service->client;
        
        $parseDate = function($date) {
            if (!$date) return null;
            try {
                if (str_contains($date, '/')) {
                    return \Carbon\Carbon::createFromFormat('d/m/Y', $date);
                }
                return \Carbon\Carbon::parse($date);
            } catch (\Exception $e) {
                return null;
            }
        };

        $replacements = [
            '[NOME_TITULAR]' => $client ? $client->nome : '',
            '[DATA_NASCIMENTO]' => ($client && $client->data_nascimento && ($d = $parseDate($client->data_nascimento))) ? $d->format('d/m/Y') : '',
            '[CPF]' => $client ? $client->cpf : '',
            '[EMAIL]' => $client ? $client->email : '',
            '[CELULAR]' => $client ? $client->celular1 : '',
            '[PROFISSAO]' => $client ? $client->profissao : '',
            
            // Cônjuge
            '[NOME_CONJUGE]' => $service->nome_conjuge ?? '',
            '[DATA_NASCIMENTO_CONJUGE]' => ($service->data_nascimento_conjuge && ($d = $parseDate($service->data_nascimento_conjuge))) ? $d->format('d/m/Y') : '',
            '[PROFISSAO_CONJUGE]' => $service->profissao_conjuge ?? '',
            
            // Serviço
            '[DATA]' => ($service->date && ($d = $parseDate($service->date))) ? $d->format('d/m/Y') : date('d/m/Y'),
            '[HORA]' => $service->time ?? '',
            '[LOCAL]' => $service->local ?? '',
            '[ID_ATENDIMENTO]' => str_pad($service->id, 5, '0', STR_PAD_LEFT),

            // Proposta / Produto
            '[PRODUTO_NOME]' => $proposal->product?->name ?? 'Produto não especificado',
            '[VALOR_TOTAL]' => $proposal->total_value ? 'R$ ' . number_format($proposal->total_value, 2, ',', '.') : 'R$ 0,00',
            '[NUMERO_CONTRATO]' => $proposal->contract_number ?? 'S/N',
        ];

        // Endereço
        if ($client && $client->address) {
            $addr = $client->address;
            $replacements['[CEP]'] = $addr->cep ?? '';
            $replacements['[RUA]'] = $addr->rua ?? '';
            $replacements['[NUMERO]'] = $addr->numero ?? '';
            $replacements['[BAIRRO]'] = $addr->bairro ?? '';
            $replacements['[CIDADE]'] = $addr->cidade ?? '';
            $replacements['[ESTADO]'] = $addr->estado ?? '';
        }

        $content = $template->content;
        foreach ($replacements as $tag => $val) {
            $content = str_replace($tag, $val, $content);
        }

        return view('pdf.rci', [
            'service' => $service,
            'content' => $content
        ]);
    }

    /**
     * Gera o PDF do Checklist (Similar à proposta/rci).
     */
    public function pdfChecklist(SalesService $service)
    {
        $service->load(['client.address', 'proposal.product.proposalTemplate']);
        
        $proposal = $service->proposal;
        if (!$proposal) {
            return redirect()->back()->with('error', 'Este atendimento ainda não possui uma proposta gerada para o Checklist.');
        }

        $template = $proposal->product?->proposalTemplate;
        
        if (!$template) {
            $template = \App\Models\ProposalTemplate::where('is_active', true)->first();
        }

        if (!$template) {
            abort(404, 'Nenhum modelo de proposta/checklist configurado no sistema.');
        }

        $client = $service->client;
        
        $parseDate = function($date) {
            if (!$date) return null;
            try {
                if (str_contains($date, '/')) {
                    return \Carbon\Carbon::createFromFormat('d/m/Y', $date);
                }
                return \Carbon\Carbon::parse($date);
            } catch (\Exception $e) {
                return null;
            }
        };

        $replacements = [
            '[NOME_TITULAR]' => $client ? $client->nome : '',
            '[DATA_NASCIMENTO]' => ($client && $client->data_nascimento && ($d = $parseDate($client->data_nascimento))) ? $d->format('d/m/Y') : '',
            '[CPF]' => $client ? $client->cpf : '',
            '[EMAIL]' => $client ? $client->email : '',
            '[CELULAR]' => $client ? $client->celular1 : '',
            '[PROFISSAO]' => $client ? $client->profissao : '',
            
            // Cônjuge
            '[NOME_CONJUGE]' => $service->nome_conjuge ?? '',
            '[DATA_NASCIMENTO_CONJUGE]' => ($service->data_nascimento_conjuge && ($d = $parseDate($service->data_nascimento_conjuge))) ? $d->format('d/m/Y') : '',
            '[PROFISSAO_CONJUGE]' => $service->profissao_conjuge ?? '',
            
            // Serviço
            '[DATA]' => ($service->date && ($d = $parseDate($service->date))) ? $d->format('d/m/Y') : date('d/m/Y'),
            '[HORA]' => $service->time ?? '',
            '[LOCAL]' => $service->local ?? '',
            '[ID_ATENDIMENTO]' => str_pad($service->id, 5, '0', STR_PAD_LEFT),

            // Proposta / Produto
            '[PRODUTO_NOME]' => $proposal->product?->name ?? 'Produto não especificado',
            '[VALOR_TOTAL]' => $proposal->total_value ? 'R$ ' . number_format($proposal->total_value, 2, ',', '.') : 'R$ 0,00',
            '[NUMERO_CONTRATO]' => $proposal->contract_number ?? 'S/N',
        ];

        // Endereço
        if ($client && $client->address) {
            $addr = $client->address;
            $replacements['[CEP]'] = $addr->cep ?? '';
            $replacements['[RUA]'] = $addr->rua ?? '';
            $replacements['[NUMERO]'] = $addr->numero ?? '';
            $replacements['[BAIRRO]'] = $addr->bairro ?? '';
            $replacements['[CIDADE]'] = $addr->cidade ?? '';
            $replacements['[ESTADO]'] = $addr->estado ?? '';
        }

        $content = $template->content;
        foreach ($replacements as $tag => $val) {
            $content = str_replace($tag, $val, $content);
        }

        return view('pdf.checklist', [
            'service' => $service,
            'content' => $content
        ]);
    }
}

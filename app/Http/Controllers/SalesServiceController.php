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

        // Controle de Acesso Restrito (RBAC)
        $user = auth()->user();
        // Se o usuário não for admin e não tiver a permissão para ver todos os atendimentos
        if (!$user->hasRole('admin') && !$user->hasPermission('atendimentos.ver_todos')) {
            $query->where(function ($q) use ($user) {
                $q->where('opc_id', $user->id)
                    ->orWhere('liner_id', $user->id)
                    ->orWhere('closer_id', $user->id)
                    ->orWhere('mkt_id', $user->id);
            });
        }

        // Filtro de Busca (Local)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('client', function ($cq) use ($search) {
                    $cq->where('nome', 'like', "%{$search}%")
                        ->orWhere('cpf', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('celular1', 'like', "%{$search}%");
                })->orWhereHas('proposal', function ($pq) use ($search) {
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
            'quantidadeFilhos' => 'nullable|integer|min:0',
            'tempoJuntos' => 'nullable|string',
            'rendaFamiliar' => 'nullable|string',
            'tipoRelacionamento' => 'nullable|string',

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

        // Validação Condicional do Acompanhante
        if ($request->temConjuge) {
            $rules['nomeConjuge'] = 'required|string|max:255';
            $rules['dataNascimentoConjuge'] = 'required|date';
            $rules['profissaoConjuge'] = 'required|string';
            $rules['tipoRelacionamento'] = 'required|string';

            if ($request->tipoRelacionamento === 'Casal/Namorados') {
                $rules['tempoJuntos'] = 'required|string';
            }
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
            'rendaFamiliar' => 'Renda Mensal',
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

        DB::transaction(function () use ($request) {
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
                'opc' => $request->opc_id ? true : false,
                'qualification' => $request->qualification,
                'status' => SalesService::STATUS_MESA,

                // Acompanhante
                'tem_conjuge' => $request->temConjuge,
                'tipo_relacionamento' => $request->tipoRelacionamento,
                'nome_conjuge' => $request->nomeConjuge,
                'cpf_conjuge' => $request->cpfConjuge,
                'rg_conjuge' => $request->rgConjuge,
                'nacionalidade_conjuge' => $request->nacionalidadeConjuge,
                'data_nascimento_conjuge' => $request->dataNascimentoConjuge,
                'idade_conjuge' => $request->idadeConjuge, // Assuming 'idadeConjuge' is passed or calculated
                'profissao_conjuge' => $request->profissaoConjuge,
                'estado_civil_conjuge' => $request->estadoCivilConjuge,

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
                'opc' => $request->opc_id ? true : false,
                'qualification' => $request->qualification,
                'status' => $request->status,
                'tem_conjuge' => $request->temConjuge,
                'tipo_relacionamento' => $request->tipoRelacionamento,
                'nome_conjuge' => $request->nomeConjuge,
                'cpf_conjuge' => $request->cpfConjuge,
                'rg_conjuge' => $request->rgConjuge,
                'nacionalidade_conjuge' => $request->nacionalidadeConjuge,
                'data_nascimento_conjuge' => $request->dataNascimentoConjuge,
                'idade_conjuge' => $request->idadeConjuge,
                'profissao_conjuge' => $request->profissaoConjuge,
                'estado_civil_conjuge' => $request->estadoCivilConjuge,
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
        if ($request->has('qualification') && !auth()->user()->can('atendimentos.alterar_qualificacao')) {
            abort(403, 'Você não tem permissão para alterar a qualificação.');
        }

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
            'proposal.product.contractTemplate',
            'protocols.user',
            'protocols.replies.user'
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
            ->whereHas('client', function ($q) use ($search) {
                $q->where('nome', 'like', "%{$search}%")
                    ->orWhere('cpf', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('celular1', 'like', "%{$search}%");
            })
            ->orWhereHas('proposal', function ($q) use ($search) {
                $q->where('contract_number', 'like', "%{$search}%");
            })
            ->limit(10)
            ->get()
            ->map(function ($service) {
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

        $parseDate = function ($date) {
            if (!$date)
                return null;
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
            '[PROMOTOR]' => $service->opcUser?->name ?? 'Não informado',
            '[CONSULTOR]' => $service->linerUser?->name ?? 'Não informado',
            '[SUPERVISOR]' => $service->closerUser?->name ?? 'Não informado',
        ];

        // QR Code Data
        $qrData = "Cliente: " . ($client ? $client->nome : 'N/A') . " | CPF: " . ($client ? $client->cpf : 'N/A') . " | Atendimento: CO-" . str_pad($service->id, 5, '0', STR_PAD_LEFT);
        $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=85x85&data=" . urlencode($qrData);
        $replacements['[QR_CODE]'] = '<img src="' . $qrUrl . '" class="qr-code-img" alt="QR Code" >';

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
            if ($item->template_type === 'image') {
                $metadata = is_array($item->metadata) ? $item->metadata : json_decode($item->metadata, true);
                if (isset($metadata['elements']) && is_array($metadata['elements'])) {
                    foreach ($metadata['elements'] as &$element) {
                        $text = $element['content'] ?? '';
                        foreach ($replacements as $tag => $val) {
                            $text = str_replace($tag, $val, $text);
                        }
                        $element['content'] = $text;
                    }
                }
                $item->processed_metadata = $metadata;
            } else {
                $content = $item->content;
                foreach ($replacements as $tag => $val) {
                    $content = str_replace($tag, $val, $content);
                }
                $item->processed_content = $content;
            }
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

        $parseDate = function ($date) {
            if (!$date)
                return null;
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

        $template = \App\Models\FichaTemplate::where('is_default', true)->first();
        if (!$template) {
            $template = \App\Models\FichaTemplate::where('is_active', true)->first();
        }

        if ($template && !empty($template->content)) {
            $html = $template->content;
            $replacements = [
                '{{lead_id}}' => str_pad($service->id, 4, '0', STR_PAD_LEFT),
                '{{hora_entrada}}' => $service->time ? date('H:i', strtotime($service->time)) : '',
                '{{hora_saida}}' => '',
                '{{nome_cliente}}' => $client->nome ?? '',
                '{{data_nascimento_cliente}}' => $formattedDates['client_birth'],
                '{{ocupacao_cliente}}' => $client->profissao ?? '',
                '{{area_cliente}}' => '',
                '{{cpf_cliente}}' => $client->cpf ?? 'N/A',
                '{{email_cliente}}' => $client->email ?? 'N/A',
                '{{celular_cliente}}' => $client->celular1 ?? 'N/A',
                '{{nome_conjuge}}' => $service->nome_conjuge ?? '',
                '{{data_nascimento_conjuge}}' => $formattedDates['spouse_birth'],
                '{{ocupacao_conjuge}}' => $service->profissao_conjuge ?? '',
                '{{area_conjuge}}' => '',
                '{{qtd_filhos}}' => $service->quantidade_filhos ?? '',
                '{{nomes_filhos}}' => '',
                '{{endereco_cliente}}' => trim(($client->address->rua ?? '') . ' ' . ($client->address->numero ?? '') . ' ' . ($client->address->complemento ?? '')),
                '{{bairro_cliente}}' => $client->address->bairro ?? '',
                '{{cidade_cliente}}' => $client->address->cidade ?? '',
                '{{uf_cliente}}' => $client->address->estado ?? '',
                '{{cep_cliente}}' => $client->address->cep ?? '',
                '{{consultor}}' => $service->linerUser?->name ?? '',
                '{{supervisor}}' => $service->closerUser?->name ?? '',
                '{{data_atendimento}}' => $formattedDates['service_date'],
                '{{local_atendimento}}' => $service->local ?? 'N/A',
                '{{promotor}}' => $service->opcUser?->name ?? 'Não informado',
                '{{brindes}}' => is_array($service->cortesia) ? implode(', ', $service->cortesia) : ($service->cortesia ?: 'Nenhum'),
            ];

            foreach ($replacements as $tag => $val) {
                $html = str_replace($tag, $val, $html);
            }

            return view('pdf.custom-sheet', ['html' => $html]);
        }

        return view('pdf.service-sheet', array_merge(compact('service', 'client'), $formattedDates));
    }

    /**
     * Gera o PDF da Proposta de Venda baseado no modelo HTML configurado para o produto.
     */
    public function pdfProposta(SalesService $service)
    {
        $service->load(['client.address', 'proposal.product.proposalTemplate', 'proposal.payments', 'opcUser', 'closerUser', 'linerUser']);

        $proposal = $service->proposal;
        if (!$proposal) {
            return redirect()->back()->with('error', 'Este atendimento ainda não possui uma proposta gerada.');
        }

        $template = $proposal->product?->proposalTemplate;

        // Se não houver template para o produto, tenta usar um ativo padrão
        if (!$template) {
            $template = \App\Models\ProposalTemplate::where('is_active', true)->first();
        }

        if (!$template || !$template->file_path || !\Illuminate\Support\Facades\Storage::exists($template->file_path)) {
            return abort(404, 'Nenhum modelo de proposta em Word configurado no sistema ou o arquivo não foi encontrado.');
        }

        $filePath = \Illuminate\Support\Facades\Storage::path($template->file_path);

        try {
            $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($filePath);
        } catch (\Exception $e) {
            return abort(500, 'Erro ao processar o arquivo Word: ' . $e->getMessage());
        }

        $client = $service->client;

        $parseDate = function ($date) {
            if (!$date)
                return null;
            try {
                if (str_contains($date, '/')) {
                    return \Carbon\Carbon::createFromFormat('d/m/Y', $date);
                }
                return \Carbon\Carbon::parse($date);
            } catch (\Exception $e) {
                return null;
            }
        };

        $buildPaymentSummary = function ($category) use ($proposal) {
            if (!$proposal || !$proposal->payments)
                return '';
            $payments = $proposal->payments->where('category', $category);
            if ($payments->isEmpty())
                return '';
            $lines = [];
            foreach ($payments as $payment) {
                $start = $payment->start_date ? \Carbon\Carbon::parse($payment->start_date)->format('d/m/Y') : 'A combinar';
                $val = number_format($payment->installment_value, 2, ',', '.');
                $lines[] = "{$payment->installments}x de R$ {$val} no {$payment->payment_method} (Início: {$start})";
            }
            return implode("\n + ", $lines);
        };

        $mockData = [
            '${CLIENTE_NOME}' => $client->nome ?? '',
            '${CLIENTE_CPF}' => $client->cpf ?? '',
            '${CLIENTE_RG}' => $client->rg ?? '',
            '${CLIENTE_NASCIMENTO}' => ($client && $client->data_nascimento && ($d = $parseDate($client->data_nascimento))) ? $d->format('d/m/Y') : '',
            '${CLIENTE_ENDERECO}' => ($client && $client->address) ? "{$client->address->rua}, {$client->address->numero}, {$client->address->bairro}, {$client->address->cidade}/{$client->address->estado}, {$client->address->cep}" : '',
            '${CLIENTE_ESTADO_CIVIL}' => $client->estado_civil ?? '',
            '${CLIENTE_PROFISSAO}' => $client->profissao ?? '',
            '${CLIENTE_NACIONALIDADE}' => $client->nacionalidade ?? '',
            '${CLIENTE_EMAIL}' => $client->email ?? '',
            '${CLIENTE_TELEFONE}' => $client->celular1 ?? '',
            '${CLIENTE_CIDADE_UF}' => ($client && $client->address) ? "{$client->address->cidade} / {$client->address->estado}" : '',

            '${CONJUNGE_NOME}' => $service->nome_conjuge ?? '',
            '${CONJUNGE_CPF}' => $service->cpf_conjuge ?? '',
            '${CONJUNGE_RG}' => $service->rg_conjuge ?? '',
            '${CONJUNGE_NASCIMENTO}' => ($service->data_nascimento_conjuge && ($d = $parseDate($service->data_nascimento_conjuge))) ? $d->format('d/m/Y') : '',
            '${CONJUNGE_ESTADO_CIVIL}' => $service->estado_civil_conjuge ?? '',
            '${CONJUNGE_PROFISSAO}' => $service->profissao_conjuge ?? '',
            '${CONJUNGE_NACIONALIDADE}' => $service->nacionalidade_conjuge ?? '',

            '${PROPOSTA_NUMERO}' => $proposal->contract_number ?? str_pad($proposal->id, 5, '0', STR_PAD_LEFT),
            '${CONTRATO_NUMERO}' => $proposal->contract_number ?? str_pad($proposal->id, 5, '0', STR_PAD_LEFT),
            '${PROPOSTA_PLANO}' => $proposal->product?->name ?? '',
            '${CONTRATO_PLANO}' => $proposal->product?->name ?? '',
            '${CATEGORIA_PACOTE}' => $proposal->product?->name ?? '',
            '${PRODUTO_NOME}' => $proposal->product?->name ?? '',
            '${PROPOSTA_CATEGORIA}' => $proposal->product?->category ?? '',
            '${CONTRATO_CATEGORIA}' => $proposal->product?->category ?? '',
            '${PROPOSTA_PACOTE}' => $proposal->product?->package ?? '',
            '${CONTRATO_PACOTE}' => $proposal->product?->package ?? '',
            '${PROPOSTA_PONTOS}' => $proposal->quantity ? number_format($proposal->quantity, 0, '', '.') . ' Pontos' : '',
            '${CONTRATO_PONTOS}' => $proposal->quantity ? number_format($proposal->quantity, 0, '', '.') . ' Pontos' : '',
            '${PROPOSTA_USO_INICIAL}' => date('Y') + 1,
            '${PROPOSTA_VIGENCIA}' => is_numeric(trim($proposal->product?->duration ?? '')) ? trim($proposal->product->duration) . ' Anos' : ($proposal->product?->duration ?? ''),
            '${CONTRATO_VIGENCIA}' => is_numeric(trim($proposal->product?->duration ?? '')) ? trim($proposal->product->duration) . ' Anos' : ($proposal->product?->duration ?? ''),
            '${PROPOSTA_VALOR_TOTAL}' => $proposal->total_value ? 'R$ ' . number_format($proposal->total_value, 2, ',', '.') : '',
            '${CONTRATO_VALOR_TOTAL}' => $proposal->total_value ? 'R$ ' . number_format($proposal->total_value, 2, ',', '.') : '',
            '${PROPOSTA_ENTRADA}' => 'R$ ' . number_format($proposal->payments->where('category', 'entrada')->sum('total_value'), 2, ',', '.'),
            '${CONTRATO_ENTRADA}' => 'R$ ' . number_format($proposal->payments->where('category', 'entrada')->sum('total_value'), 2, ',', '.'),
            '${CONTRATO_DATA_ENTRADA}' => $proposal->payments->where('category', 'entrada')->first()?->start_date ? \Carbon\Carbon::parse($proposal->payments->where('category', 'entrada')->first()->start_date)->format('d/m/Y') : '',
            '${PROPOSTA_RESUMO_ENTRADA}' => $buildPaymentSummary('entrada'),
            '${CONTRATO_RESUMO_ENTRADA}' => $buildPaymentSummary('entrada'),
            '${PROPOSTA_SALDO}' => 'R$ ' . number_format($proposal->payments->where('category', 'saldo')->sum('total_value'), 2, ',', '.'),
            '${CONTRATO_SALDO}' => 'R$ ' . number_format($proposal->payments->where('category', 'saldo')->sum('total_value'), 2, ',', '.'),
            '${CONTRATO_DATA_SALDO}' => $proposal->payments->where('category', 'saldo')->first()?->start_date ? \Carbon\Carbon::parse($proposal->payments->where('category', 'saldo')->first()->start_date)->format('d/m/Y') : '',
            '${PROPOSTA_RESUMO_SALDO}' => $buildPaymentSummary('saldo'),
            '${CONTRATO_RESUMO_SALDO}' => $buildPaymentSummary('saldo'),
            '${PROPOSTA_RESUMO_TAXA}' => $buildPaymentSummary('taxa_contrato'),
            '${CONTRATO_RESUMO_TAXA}' => $buildPaymentSummary('taxa_contrato'),
            '${CONTRATO_TAXA}' => 'R$ ' . number_format($proposal->payments->where('category', 'taxa_contrato')->sum('total_value'), 2, ',', '.'),
            '${PROPOSTA_RESUMO_MANUTENCAO}' => $buildPaymentSummary('taxa_manutencao'),
            '${CONTRATO_RESUMO_MANUTENCAO}' => $buildPaymentSummary('taxa_manutencao'),
            '${CONTRATO_TAXA_MANUTENCAO}' => 'R$ ' . number_format($proposal->payments->where('category', 'taxa_manutencao')->sum('total_value'), 2, ',', '.'),
            '${CONTRATO_FORMA_PAGAMENTO_ENTRADA}' => $proposal->payments->where('category', 'entrada')->first()?->payment_method ?? '',
            '${CONTRATO_FORMA_PAGAMENTO_SALDO}' => $proposal->payments->where('category', 'saldo')->first()?->payment_method ?? '',
            '${CONTRATO_FORMA_PAGAMENTO}' => implode(' e ', array_filter(array_unique([
                $proposal->payments->where('category', 'entrada')->first()?->payment_method,
                $proposal->payments->where('category', 'saldo')->first()?->payment_method
            ]))),
            '${CONTRATO_DATA}' => ($service->date && ($d = $parseDate($service->date))) ? $d->format('d/m/Y') : date('d/m/Y'),
            '${CONTRATO_DATA_EXTENSO}' => ($service->date && ($d = $parseDate($service->date)))
                ? $d->format('d') . ' de ' . ucfirst($d->locale('pt_BR')->translatedFormat('F')) . ' de ' . $d->format('Y')
                : date('d') . ' de ' . ucfirst(\Carbon\Carbon::now()->locale('pt_BR')->translatedFormat('F')) . ' de ' . date('Y'),
            '${EMPRESA_EMAIL}' => 'contato@itacare.com.br',
            '${EMPRESA_WHATSAPP}' => '(73) 9999-8888',

            // Equipe
            '${VENDEDOR_NOME}' => $service->closerUser?->name ?? '',
            '${PROMOTOR_NOME}' => $service->opcUser?->name ?? '',
            '${CONSULTOR_NOME}' => $service->linerUser?->name ?? '',
            '${SUPERVISOR_NOME}' => $service->closerUser?->name ?? '',
            '${GERENTE_NOME}' => $service->closerUser?->name ?? '',
            '${DATA_ATUAL}' => date('d/m/Y'),
            '${HORA_ATUAL}' => date('H:i:s'),
            '${PROPOSTA_DATA}' => ($service->date && ($d = $parseDate($service->date))) ? $d->format('d/m/Y') : date('d/m/Y'),
            '${USUARIO_IMPRESSAO}' => auth()->user()->name ?? 'Administrador',
        ];

        foreach ($mockData as $tag => $value) {
            $cleanTag = str_replace(['${', '}'], '', $tag);
            $templateProcessor->setValue($cleanTag, $value);
        }

        $tempFileName = 'PROPOSTA_' . ($proposal->contract_number ?? $proposal->id) . '.docx';
        $tempPath = storage_path('app/temp/' . $tempFileName);

        if (!\Illuminate\Support\Facades\File::exists(storage_path('app/temp'))) {
            \Illuminate\Support\Facades\File::makeDirectory(storage_path('app/temp'), 0755, true);
        }

        $templateProcessor->saveAs($tempPath);

        // Convert to PDF
        $pdfFileName = str_replace('.docx', '.pdf', $tempFileName);
        $pdfPath = storage_path('app/temp/' . $pdfFileName);

        try {
            $converter = new \NcJoes\OfficeConverter\OfficeConverter($tempPath, storage_path('app/temp'), 'soffice', false);
            $converter->convertTo($pdfFileName);
        } catch (\Exception $e) {
            // Se falhar a conversão para PDF (ex: LibreOffice não instalado), retorna o próprio DOCX
            return response()->download($tempPath, $tempFileName)->deleteFileAfterSend(true);
        }

        @unlink($tempPath);

        if (!\Illuminate\Support\Facades\File::exists($pdfPath)) {
            return back()->with('error', 'Falha ao gerar o arquivo PDF.');
        }

        return response()->file($pdfPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $pdfFileName . '"'
        ])->deleteFileAfterSend(true);
    }

    /**
     * Gera o PDF do Contrato baseado no modelo HTML configurado (do produto ou global).
     */
    public function pdfContrato(SalesService $service)
    {
        $service->load(['client.address', 'proposal.product.contractTemplate', 'proposal.payments', 'opcUser', 'closerUser', 'linerUser']);

        $proposal = $service->proposal;
        if (!$proposal) {
            return redirect()->back()->with('error', 'Este atendimento ainda não possui uma proposta gerada para o contrato.');
        }

        $template = $proposal->product?->contractTemplate;

        // Se o produto não tiver contrato, usa o Global Padrão
        if (!$template) {
            $template = \App\Models\ContractTemplate::where('is_default', true)->first();
        }

        if (!$template || !$template->file_path || !\Illuminate\Support\Facades\Storage::exists($template->file_path)) {
            return abort(404, 'Nenhum modelo de contrato (específico ou global) configurado no sistema ou o arquivo não foi encontrado.');
        }

        $filePath = \Illuminate\Support\Facades\Storage::path($template->file_path);

        try {
            $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($filePath);
        } catch (\Exception $e) {
            return abort(500, 'Erro ao processar o arquivo Word: ' . $e->getMessage());
        }

        $client = $service->client;

        $parseDate = function ($date) {
            if (!$date)
                return null;
            try {
                if (str_contains($date, '/')) {
                    return \Carbon\Carbon::createFromFormat('d/m/Y', $date);
                }
                return \Carbon\Carbon::parse($date);
            } catch (\Exception $e) {
                return null;
            }
        };

        $buildPaymentSummary = function ($category) use ($proposal) {
            if (!$proposal || !$proposal->payments)
                return '';
            $payments = $proposal->payments->where('category', $category);
            if ($payments->isEmpty())
                return '';
            $lines = [];
            foreach ($payments as $payment) {
                $start = $payment->start_date ? \Carbon\Carbon::parse($payment->start_date)->format('d/m/Y') : 'A combinar';
                $val = number_format($payment->installment_value, 2, ',', '.');
                $lines[] = "{$payment->installments}x de R$ {$val} no {$payment->payment_method} (Início: {$start})";
            }
            return implode("\n + ", $lines);
        };

        $mockData = [
            '${CLIENTE_NOME}' => $client->nome ?? '',
            '${CLIENTE_CPF}' => $client->cpf ?? '',
            '${CLIENTE_RG}' => $client->rg ?? '',
            '${CLIENTE_NASCIMENTO}' => ($client && $client->data_nascimento && ($d = $parseDate($client->data_nascimento))) ? $d->format('d/m/Y') : '',
            '${CLIENTE_ENDERECO}' => ($client && $client->address) ? "{$client->address->rua}, {$client->address->numero}, {$client->address->bairro}, {$client->address->cidade}/{$client->address->estado}, {$client->address->cep}" : '',
            '${CLIENTE_ESTADO_CIVIL}' => $client->estado_civil ?? '',
            '${CLIENTE_PROFISSAO}' => $client->profissao ?? '',
            '${CLIENTE_NACIONALIDADE}' => $client->nacionalidade ?? '',
            '${CLIENTE_EMAIL}' => $client->email ?? '',
            '${CLIENTE_TELEFONE}' => $client->celular1 ?? '',
            '${CLIENTE_CIDADE_UF}' => ($client && $client->address) ? "{$client->address->cidade} / {$client->address->estado}" : '',

            '${CONJUNGE_NOME}' => $service->nome_conjuge ?? '',
            '${CONJUNGE_CPF}' => $service->cpf_conjuge ?? '',
            '${CONJUNGE_RG}' => $service->rg_conjuge ?? '',
            '${CONJUNGE_NASCIMENTO}' => ($service->data_nascimento_conjuge && ($d = $parseDate($service->data_nascimento_conjuge))) ? $d->format('d/m/Y') : '',
            '${CONJUNGE_ESTADO_CIVIL}' => $service->estado_civil_conjuge ?? '',
            '${CONJUNGE_PROFISSAO}' => $service->profissao_conjuge ?? '',
            '${CONJUNGE_NACIONALIDADE}' => $service->nacionalidade_conjuge ?? '',

            '${PROPOSTA_NUMERO}' => $proposal->contract_number ?? str_pad($proposal->id, 5, '0', STR_PAD_LEFT),
            '${CONTRATO_NUMERO}' => $proposal->contract_number ?? str_pad($proposal->id, 5, '0', STR_PAD_LEFT),
            '${PROPOSTA_PLANO}' => $proposal->product?->name ?? '',
            '${CONTRATO_PLANO}' => $proposal->product?->name ?? '',
            '${CATEGORIA_PACOTE}' => $proposal->product?->name ?? '',
            '${PRODUTO_NOME}' => $proposal->product?->name ?? '',
            '${PROPOSTA_CATEGORIA}' => $proposal->product?->category ?? '',
            '${CONTRATO_CATEGORIA}' => $proposal->product?->category ?? '',
            '${PROPOSTA_PACOTE}' => $proposal->product?->package ?? '',
            '${CONTRATO_PACOTE}' => $proposal->product?->package ?? '',
            '${PROPOSTA_PONTOS}' => $proposal->quantity ? number_format($proposal->quantity, 0, '', '.') . ' Pontos' : '',
            '${CONTRATO_PONTOS}' => $proposal->quantity ? number_format($proposal->quantity, 0, '', '.') . ' Pontos' : '',
            '${PROPOSTA_USO_INICIAL}' => date('Y') + 1,
            '${PROPOSTA_VIGENCIA}' => is_numeric(trim($proposal->product?->duration ?? '')) ? trim($proposal->product->duration) . ' Anos' : ($proposal->product?->duration ?? ''),
            '${CONTRATO_VIGENCIA}' => is_numeric(trim($proposal->product?->duration ?? '')) ? trim($proposal->product->duration) . ' Anos' : ($proposal->product?->duration ?? ''),
            '${PROPOSTA_VALOR_TOTAL}' => $proposal->total_value ? 'R$ ' . number_format($proposal->total_value, 2, ',', '.') : '',
            '${CONTRATO_VALOR_TOTAL}' => $proposal->total_value ? 'R$ ' . number_format($proposal->total_value, 2, ',', '.') : '',
            '${PROPOSTA_ENTRADA}' => 'R$ ' . number_format($proposal->payments->where('category', 'entrada')->sum('total_value'), 2, ',', '.'),
            '${CONTRATO_ENTRADA}' => 'R$ ' . number_format($proposal->payments->where('category', 'entrada')->sum('total_value'), 2, ',', '.'),
            '${CONTRATO_DATA_ENTRADA}' => $proposal->payments->where('category', 'entrada')->first()?->start_date ? \Carbon\Carbon::parse($proposal->payments->where('category', 'entrada')->first()->start_date)->format('d/m/Y') : '',
            '${PROPOSTA_RESUMO_ENTRADA}' => $buildPaymentSummary('entrada'),
            '${CONTRATO_RESUMO_ENTRADA}' => $buildPaymentSummary('entrada'),
            '${PROPOSTA_SALDO}' => 'R$ ' . number_format($proposal->payments->where('category', 'saldo')->sum('total_value'), 2, ',', '.'),
            '${CONTRATO_SALDO}' => 'R$ ' . number_format($proposal->payments->where('category', 'saldo')->sum('total_value'), 2, ',', '.'),
            '${CONTRATO_DATA_SALDO}' => $proposal->payments->where('category', 'saldo')->first()?->start_date ? \Carbon\Carbon::parse($proposal->payments->where('category', 'saldo')->first()->start_date)->format('d/m/Y') : '',
            '${PROPOSTA_RESUMO_SALDO}' => $buildPaymentSummary('saldo'),
            '${CONTRATO_RESUMO_SALDO}' => $buildPaymentSummary('saldo'),
            '${PROPOSTA_RESUMO_TAXA}' => $buildPaymentSummary('taxa_contrato'),
            '${CONTRATO_RESUMO_TAXA}' => $buildPaymentSummary('taxa_contrato'),
            '${CONTRATO_TAXA}' => 'R$ ' . number_format($proposal->payments->where('category', 'taxa_contrato')->sum('total_value'), 2, ',', '.'),
            '${PROPOSTA_RESUMO_MANUTENCAO}' => $buildPaymentSummary('taxa_manutencao'),
            '${CONTRATO_RESUMO_MANUTENCAO}' => $buildPaymentSummary('taxa_manutencao'),
            '${CONTRATO_TAXA_MANUTENCAO}' => 'R$ ' . number_format($proposal->payments->where('category', 'taxa_manutencao')->sum('total_value'), 2, ',', '.'),
            '${CONTRATO_FORMA_PAGAMENTO_ENTRADA}' => $proposal->payments->where('category', 'entrada')->first()?->payment_method ?? '',
            '${CONTRATO_FORMA_PAGAMENTO_SALDO}' => $proposal->payments->where('category', 'saldo')->first()?->payment_method ?? '',
            '${CONTRATO_FORMA_PAGAMENTO}' => implode(' e ', array_filter(array_unique([
                $proposal->payments->where('category', 'entrada')->first()?->payment_method,
                $proposal->payments->where('category', 'saldo')->first()?->payment_method
            ]))),
            '${CONTRATO_DATA}' => ($service->date && ($d = $parseDate($service->date))) ? $d->format('d/m/Y') : date('d/m/Y'),
            '${CONTRATO_DATA_EXTENSO}' => ($service->date && ($d = $parseDate($service->date)))
                ? $d->format('d') . ' de ' . ucfirst($d->locale('pt_BR')->translatedFormat('F')) . ' de ' . $d->format('Y')
                : date('d') . ' de ' . ucfirst(\Carbon\Carbon::now()->locale('pt_BR')->translatedFormat('F')) . ' de ' . date('Y'),
            '${EMPRESA_EMAIL}' => 'contato@itacare.com.br',
            '${EMPRESA_WHATSAPP}' => '(73) 9999-8888',

            // Equipe
            '${VENDEDOR_NOME}' => $service->closerUser?->name ?? '',
            '${PROMOTOR_NOME}' => $service->opcUser?->name ?? '',
            '${CONSULTOR_NOME}' => $service->linerUser?->name ?? '',
            '${SUPERVISOR_NOME}' => $service->closerUser?->name ?? '',
            '${GERENTE_NOME}' => $service->closerUser?->name ?? '',
            '${DATA_ATUAL}' => date('d/m/Y'),
            '${HORA_ATUAL}' => date('H:i:s'),
            '${PROPOSTA_DATA}' => ($service->date && ($d = $parseDate($service->date))) ? $d->format('d/m/Y') : date('d/m/Y'),
            '${USUARIO_IMPRESSAO}' => auth()->user()->name ?? 'Administrador',
        ];

        foreach ($mockData as $tag => $value) {
            $cleanTag = str_replace(['${', '}'], '', $tag);
            $templateProcessor->setValue($cleanTag, $value);
        }

        $tempFileName = 'CONTRATO_' . ($proposal->contract_number ?? $proposal->id) . '.docx';
        $tempPath = storage_path('app/temp/' . $tempFileName);

        if (!\Illuminate\Support\Facades\File::exists(storage_path('app/temp'))) {
            \Illuminate\Support\Facades\File::makeDirectory(storage_path('app/temp'), 0755, true);
        }

        $templateProcessor->saveAs($tempPath);

        // Convert to PDF
        $pdfFileName = str_replace('.docx', '.pdf', $tempFileName);
        $pdfPath = storage_path('app/temp/' . $pdfFileName);

        try {
            $converter = new \NcJoes\OfficeConverter\OfficeConverter($tempPath, storage_path('app/temp'), 'soffice', false);
            $converter->convertTo($pdfFileName);
        } catch (\Exception $e) {
            return response()->download($tempPath, $tempFileName)->deleteFileAfterSend(true);
        }

        @unlink($tempPath);

        if (!\Illuminate\Support\Facades\File::exists($pdfPath)) {
            return back()->with('error', 'Falha ao gerar o arquivo PDF.');
        }

        return response()->file($pdfPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $pdfFileName . '"'
        ])->deleteFileAfterSend(true);
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

        $parseDate = function ($date) {
            if (!$date)
                return null;
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

        $parseDate = function ($date) {
            if (!$date)
                return null;
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

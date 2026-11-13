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
        $query = SalesService::with(['client.address', 'proposal.payments', 'proposal.product', 'opcUser', 'linerUser', 'closerUser', 'mktUser', 'schedule.user:id,name', 'signedDocuments']);

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
            'capturePoints' => \App\Models\CapturePoint::orderBy('name')->get(['name', 'value', 'active']),
            'professions' => \App\Models\Profession::activeNames(),
            'serviceFormSettings' => \App\Models\Setting::serviceForm(),
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
        $this->applyRelationshipSetting($request);

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
            'viagensPorAno' => 'nullable|integer|min:0|max:365',
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
            $rules['estadoCivilConjuge'] = 'required|string';

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
            'local' => 'Ponto de captação',
            'status' => 'Status',
            'opc_id' => 'OPC (Atendente)',
            'qualification' => 'Qualificação',
            'nome' => 'Nome',
            'dataNascimento' => 'Data de Nascimento',
            'profissao' => 'Profissão',
            'estadoCivil' => 'Estado Civil',
            'estadoCivilConjuge' => 'Estado Civil do 2º Titular',
            'tipoRelacionamento' => 'Relação com o 1º Titular',
            'celular1' => 'Celular',
            'email' => 'E-mail',
            'quantidadeFilhos' => 'Quantidade de Filhos',
            'viagensPorAno' => 'Quantidade de Viagens por Ano',
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

        // Profissão digitada que não existe entra no cadastro como "a revisar" (Configurações → Profissões)
        $request->merge([
            'profissao' => \App\Models\Profession::register($request->profissao),
            'profissaoConjuge' => \App\Models\Profession::register($request->profissaoConjuge),
        ]);

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
                'viagens_por_ano' => $request->filled('viagensPorAno') ? (int) $request->viagensPorAno : null,
                'observacoes' => $request->observacoes,
            ]);
        });

    }

    public function update(Request $request, SalesService $service)
    {
        $this->applyRelationshipSetting($request, $service);

        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'celular1' => 'required|string|max:20',
            'date' => 'required|string',
            'time' => 'required|string',
            'local' => 'required|string',
            'cortesia' => 'required|array',
            'nacionalidade' => 'nullable|string|max:100',
            'dataNascimento' => 'nullable|date',
            'profissao' => 'nullable|string',
            'estadoCivil' => 'required|string',
            'estadoCivilConjuge' => 'required_if:temConjuge,true,1|nullable|string',
            'rendaFamiliar' => 'nullable|string',
            'nomeConjuge' => 'nullable|string|max:255',
            'dataNascimentoConjuge' => 'nullable|date',
            'profissaoConjuge' => 'nullable|string',
            'tipoRelacionamento' => 'nullable|string',
            'tempoJuntos' => 'nullable|string',
            'viagensPorAno' => 'nullable|integer|min:0|max:365',
        ], [
            'required' => 'O campo :attribute é obrigatório.',
            'required_if' => 'O campo :attribute é obrigatório.',
            'date' => 'A data informada no campo :attribute não é válida.',
            'integer' => 'O campo :attribute deve ser um número inteiro.',
            'array' => 'Selecione ao menos uma opção no campo :attribute.',
        ], [
            'nome' => 'Nome',
            'celular1' => 'Celular',
            'date' => 'Data',
            'time' => 'Hora',
            'local' => 'Ponto de captação',
            'cortesia' => 'Cortesia',
            'estadoCivil' => 'Estado Civil',
            'estadoCivilConjuge' => 'Estado Civil do 2º Titular',
            'viagensPorAno' => 'Quantidade de Viagens por Ano',
        ]);

        // Profissão digitada que não existe entra no cadastro como "a revisar" (Configurações → Profissões)
        $request->merge([
            'profissao' => \App\Models\Profession::register($request->profissao),
            'profissaoConjuge' => \App\Models\Profession::register($request->profissaoConjuge),
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
                'viagens_por_ano' => $request->filled('viagensPorAno') ? (int) $request->viagensPorAno : null,
                'observacoes' => $request->observacoes,
            ]);

            // Sincronizar Cancelamento com a Proposta
            if ($service->status === SalesService::STATUS_CANCELADO && $service->proposal) {
                $service->proposal->update(['status' => 'cancelled']);
                \App\Models\CommissionInstallment::whereHas('commission', function ($q) use ($service) {
                    $q->where('proposal_id', $service->proposal->id);
                })->whereIn('status', ['pending'])->update([
                    'status' => 'cancelled',
                    'notes' => 'Cancelado automaticamente devido ao cancelamento do atendimento/contrato'
                ]);
            }
        });

        return redirect()->back()->with('success', 'Atendimento atualizado com sucesso!');
    }

    /**
     * Com "Relação com o 1º Titular" desligada (Configurações → Ficha de Atendimento),
     * o 2º titular é tratado como casal e o estado civil dele acompanha o do 1º titular.
     * Atendimentos antigos com outra relação (irmãos, sócios...) mantêm o que já estava gravado.
     */
    private function applyRelationshipSetting(Request $request, ?SalesService $service = null): void
    {
        if (\App\Models\Setting::serviceForm()['relationship_field'] || !$request->temConjuge) {
            return;
        }

        $relation = $service?->tipo_relacionamento ?: 'Casal/Namorados';
        $request->merge(['tipoRelacionamento' => $relation]);

        if ($relation === 'Casal/Namorados') {
            $request->merge(['estadoCivilConjuge' => $request->estadoCivil]);
        }
    }

    public function quickUpdate(Request $request, SalesService $service)
    {
        if ($request->has('qualification') && !auth()->user()->hasPermission('atendimentos.alterar_qualificacao')) {
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
            'protocols.replies.user',
            'reservationRequests.user',
            'schedule.user:id,name',
            'signedDocuments'
        ]);

        $destinations = \App\Models\PointTable\Resort::with(['accommodations' => function($q) { $q->with(['scores.season']); }])->orderBy('name')->get();
        $holidays = \App\Models\PointTable\Holiday::orderBy('start_date')->get();

        return Inertia::render('Sales/Service/Details', [
            'service' => $service,
            'destinations' => $destinations,
            'holidays' => $holidays,
        ]);
    }

    /**
     * API para Busca Global (Command Palette).
     */
    public function globalSearch(Request $request)
    {
        $search = $request->query('q');
        $requireContract = $request->query('require_contract') === 'true';

        if (empty($search)) {
            return response()->json([]);
        }

        $query = SalesService::with(['client', 'proposal.bills'])
            ->where(function ($q) use ($search) {
                $q->whereHas('client', function ($cq) use ($search) {
                    $cq->where('nome', 'like', "%{$search}%")
                        ->orWhere('cpf', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('celular1', 'like', "%{$search}%");
                })
                ->orWhereHas('proposal', function ($pq) use ($search) {
                    $pq->where('contract_number', 'like', "%{$search}%");
                });
            });

        if ($requireContract) {
            $query->has('proposal');
        }

        $results = $query->limit(10)
            ->get()
            ->map(function ($service) {
                $proposal = $service->proposal;
                $totalPoints = $proposal?->quantity ? (int) $proposal->quantity : 0;
                $totalValue = (float) ($proposal?->total_value ?? 0);

                $totalPaid = 0;
                if ($proposal && $proposal->bills) {
                    $totalPaid = (float) $proposal->bills
                        ->filter(function ($b) {
                            return in_array($b->category, ['entrada', 'saldo']) && $b->status === 'paid';
                        })
                        ->sum(function ($b) {
                            return (float) ($b->paid_amount ?: $b->amount);
                        });
                }

                if ($proposal && $totalValue > 0) {
                    $ratio = $totalPaid / $totalValue;
                    $releasedPoints = (int) floor($totalPoints * $ratio);
                } else {
                    $releasedPoints = $totalPoints;
                }

                $usedPoints = (int) \App\Models\ReservationRequest::where('sales_service_id', $service->id)
                    ->where('status', '!=', 'canceled')
                    ->sum('points_used');

                $availablePoints = max(0, $releasedPoints - $usedPoints);

                return [
                    'id' => $service->id,
                    'title' => $service->client?->nome ?? 'Cliente não identificado',
                    'subtitle' => "Contrato: " . ($proposal?->contract_number ?? 'S/N') . " | CPF: " . ($service->client?->cpf ?? '-'),
                    'url' => route('sales.atendimentos.show', $service->id),
                    'type' => 'Contrato',
                    'total_points' => $totalPoints,
                    'released_points' => $releasedPoints,
                    'used_points' => $usedPoints,
                    'available_points' => $availablePoints,
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

        $primeiroSegundoNome = '';
        if ($client && $client->nome) {
            $parts = explode(' ', trim($client->nome));
            $primeiroSegundoNome = $parts[0] . (isset($parts[1]) ? ' ' . $parts[1] : '');
        }

        $replacements = [
            '[NOME_TITULAR]' => $client ? $client->nome : '',
            '[NOME_CURTO]' => $primeiroSegundoNome,
            '[DATA_IMPRESSAO]' => date('d/m/Y'),
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
                // Título + texto livre (escapado, com quebras de linha); vazio quando não há detalhes
                '{{detalhes_adicionais}}' => trim($service->observacoes ?? '') === '' ? '' :
                    '<div class="detalhes-adicionais"><strong>Detalhes Adicionais:</strong><br>' . nl2br(e(trim($service->observacoes))) . '</div>',
                '{{viagens_por_ano}}' => $service->viagens_por_ano === null ? '' : $service->viagens_por_ano . ($service->viagens_por_ano == 1 ? ' viagem por ano' : ' viagens por ano'),
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
    public function pdfProposta(Request $request, SalesService $service)
    {
        // Proposta já assinada digitalmente: exibe o arquivo assinado (?modelo=1 gera de novo do modelo)
        if (!$request->boolean('modelo') && ($signed = $this->signedDocumentResponse($service, \App\Models\ContractSignature::DOCUMENT_PROPOSAL))) {
            return $signed;
        }

        $documents = app(\App\Services\Contracts\ContractDocumentService::class);

        try {
            $docx = $documents->generateProposalDocx($service);
        } catch (\App\Services\Contracts\ContractGenerationException $e) {
            if ($e->getCode() === 422) {
                return redirect()->back()->with('error', $e->getMessage());
            }
            return abort($e->getCode() ?: 500, $e->getMessage());
        }

        try {
            $pdfPath = $documents->convertToPdf($docx['docx']);
        } catch (\App\Services\Contracts\ContractGenerationException $e) {
            // Sem LibreOffice: entrega o Word preenchido
            if (\Illuminate\Support\Facades\File::exists($docx['docx'])) {
                return response()->download($docx['docx'], $docx['filename'])->deleteFileAfterSend(true);
            }
            return back()->with('error', $e->getMessage());
        }

        return response()->file($pdfPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . basename($pdfPath) . '"'
        ])->deleteFileAfterSend(true);
    }

    /**
     * Resposta com o arquivo assinado digitalmente de um documento (proposta, RCI), ou null se não houver.
     */
    private function signedDocumentResponse(SalesService $service, string $documentType)
    {
        $signature = \App\Models\ContractSignature::latestSignedFile($service->id, $documentType);
        $disk = \Illuminate\Support\Facades\Storage::disk(config('signature.disk', 'local'));

        if (!$signature || !$disk->exists($signature->signed_file_path)) {
            return null;
        }

        $service->loadMissing('proposal');
        $number = $service->proposal?->contract_number ?? $service->id;

        return response()->file($disk->path($signature->signed_file_path), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . strtoupper($documentType) . '_' . $number . '_ASSINADO.pdf"',
        ]);
    }

    /**
     * Gera o PDF do Contrato baseado no modelo HTML configurado (do produto ou global).
     */
    public function pdfContrato(Request $request, SalesService $service)
    {
        // Contrato já assinado (assinatura digital ou upload em Entrega de Contrato): exibe o arquivo assinado.
        // ?modelo=1 gera de novo a partir do modelo (ex.: para reimprimir em branco).
        if (!$request->boolean('modelo')
            && $service->contract_signature_status === 'signed'
            && $service->contract_file_path
            && \Illuminate\Support\Facades\Storage::disk('local')->exists($service->contract_file_path)) {
            $disk = \Illuminate\Support\Facades\Storage::disk('local');
            $extension = strtolower(pathinfo($service->contract_file_path, PATHINFO_EXTENSION)) ?: 'pdf';
            $number = $service->proposal?->contract_number ?? $service->id;

            return response()->file($disk->path($service->contract_file_path), [
                'Content-Type' => $disk->mimeType($service->contract_file_path) ?: 'application/pdf',
                'Content-Disposition' => 'inline; filename="CONTRATO_' . $number . '_ASSINADO.' . $extension . '"',
            ]);
        }

        $documents = app(\App\Services\Contracts\ContractDocumentService::class);

        try {
            $docx = $documents->generateDocx($service);
        } catch (\App\Services\Contracts\ContractGenerationException $e) {
            if ($e->getCode() === 422) {
                return redirect()->back()->with('error', $e->getMessage());
            }
            return abort($e->getCode() ?: 500, $e->getMessage());
        }

        try {
            $pdfPath = $documents->convertToPdf($docx['docx']);
        } catch (\App\Services\Contracts\ContractGenerationException $e) {
            // Sem LibreOffice: entrega o Word preenchido
            if (\Illuminate\Support\Facades\File::exists($docx['docx'])) {
                return response()->download($docx['docx'], $docx['filename'])->deleteFileAfterSend(true);
            }
            return back()->with('error', $e->getMessage());
        }

        return response()->file($pdfPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . basename($pdfPath) . '"'
        ])->deleteFileAfterSend(true);
    }

    /**
     * Gera o PDF da Proposta RCI (Variação da proposta padrão).
     */
    public function pdfRci(Request $request, SalesService $service)
    {
        // RCI já assinado digitalmente: exibe o arquivo assinado (?modelo=1 gera de novo do modelo)
        if (!$request->boolean('modelo') && ($signed = $this->signedDocumentResponse($service, \App\Models\ContractSignature::DOCUMENT_RCI))) {
            return $signed;
        }

        try {
            $outputPath = app(\App\Services\Contracts\ContractDocumentService::class)->generateRciPdf($service);
        } catch (\App\Services\Contracts\ContractGenerationException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            \Log::error('Erro ao gerar RCI: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erro inesperado ao gerar PDF: ' . \App\Support\Utf8::clean($e->getMessage()));
        }

        return response()->file($outputPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . basename($outputPath) . '"'
        ])->deleteFileAfterSend(true);
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

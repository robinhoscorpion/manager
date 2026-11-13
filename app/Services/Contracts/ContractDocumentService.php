<?php

namespace App\Services\Contracts;

use App\Models\SalesService;

/**
 * Gera o documento do contrato (Word → PDF) a partir do modelo configurado.
 * Usado pelo download do contrato e pelo envio para assinatura digital.
 */
class ContractDocumentService
{
    /**
     * Preenche o modelo Word do contrato e devolve o caminho do .docx gerado (em storage/app/temp).
     *
     * @return array{docx: string, filename: string}
     * @throws ContractGenerationException
     */
    public function generateDocx(SalesService $service): array
    {
        $service->load(['client.address', 'proposal.product.contractTemplate', 'proposal.payments', 'opcUser', 'closerUser', 'linerUser']);

        $proposal = $service->proposal;
        if (!$proposal) {
            throw new ContractGenerationException('Este atendimento ainda não possui uma proposta gerada para o contrato.', 422);
        }

        $template = $proposal->product?->contractTemplate;

        // Se o produto não tiver contrato, usa o Global Padrão
        if (!$template) {
            $template = \App\Models\ContractTemplate::where('is_default', true)->first();
        }

        if (!$template || !$template->file_path || !\Illuminate\Support\Facades\Storage::exists($template->file_path)) {
            throw new ContractGenerationException('Nenhum modelo de contrato (específico ou global) configurado no sistema ou o arquivo não foi encontrado.', 404);
        }

        $filePath = \Illuminate\Support\Facades\Storage::path($template->file_path);

        try {
            $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($filePath);
        } catch (\Exception $e) {
            throw new ContractGenerationException('Erro ao processar o arquivo Word: ' . $e->getMessage(), 500);
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

        $paymentMethodsMap = \App\Models\PaymentMethod::pluck('type', 'name')->toArray();

        $formatPaymentType = function ($type, $name = null) {
            if ($name) {
                $lowerName = mb_strtolower($name, 'UTF-8');
                if (str_contains($lowerName, 'cartão de credito') || str_contains($lowerName, 'cartão de crédito')) return 'Cartão de Crédito';
                if (str_contains($lowerName, 'boleto')) return 'Boleto Bancário';
                if (str_contains($lowerName, 'pix')) return 'PIX';
            }
            $map = [
                'credit_card' => 'Cartão de Crédito',
                'debit_card' => 'Cartão de Débito',
                'pix' => 'PIX',
                'boleto' => 'Boleto Bancário',
                'cash' => 'Dinheiro',
                'bank_transfer' => 'Transferência Bancária'
            ];
            return $map[$type] ?? ucfirst(str_replace('_', ' ', $type));
        };

        $buildPaymentSummary = function ($category) use ($proposal, $paymentMethodsMap, $formatPaymentType) {
            if (!$proposal || !$proposal->payments)
                return '';
            $payments = $proposal->payments->where('category', $category);
            if ($payments->isEmpty())
                return '';
            $lines = [];
            foreach ($payments as $payment) {
                $start = $payment->start_date ? \Carbon\Carbon::parse($payment->start_date)->format('d/m/Y') : 'A combinar';
                $val = number_format($payment->installment_value, 2, ',', '.');
                $methodName = $payment->payment_method;
                $methodType = $paymentMethodsMap[$methodName] ?? $methodName;
                $formattedType = $formatPaymentType($methodType, $methodName);
                $lines[] = "{$payment->installments}x de R$ {$val} no {$formattedType} (Início: {$start})";
            }
            return implode('</w:t><w:br/><w:t>+ ', $lines);
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
            '${CONTRATO_FORMA_PAGAMENTO_ENTRADA}' => $formatPaymentType($paymentMethodsMap[$proposal->payments->where('category', 'entrada')->first()?->payment_method] ?? ($proposal->payments->where('category', 'entrada')->first()?->payment_method ?? '')),
            '${CONTRATO_FORMA_PAGAMENTO_SALDO}' => $formatPaymentType($paymentMethodsMap[$proposal->payments->where('category', 'saldo')->first()?->payment_method] ?? ($proposal->payments->where('category', 'saldo')->first()?->payment_method ?? '')),
            '${CONTRATO_FORMA_PAGAMENTO}' => implode(' e ', array_filter(array_unique([
                $formatPaymentType($paymentMethodsMap[$proposal->payments->where('category', 'entrada')->first()?->payment_method] ?? $proposal->payments->where('category', 'entrada')->first()?->payment_method),
                $formatPaymentType($paymentMethodsMap[$proposal->payments->where('category', 'saldo')->first()?->payment_method] ?? $proposal->payments->where('category', 'saldo')->first()?->payment_method)
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

        return ['docx' => $tempPath, 'filename' => $tempFileName];
    }

    /**
     * Preenche o modelo Word da proposta e devolve o caminho do .docx gerado.
     *
     * @return array{docx: string, filename: string}
     * @throws ContractGenerationException
     */
    public function generateProposalDocx(SalesService $service): array
    {
        $service->load(['client.address', 'proposal.product.proposalTemplate', 'proposal.payments', 'opcUser', 'closerUser', 'linerUser']);

        $proposal = $service->proposal;
        if (!$proposal) {
            throw new ContractGenerationException('Este atendimento ainda não possui uma proposta gerada.', 422);
        }

        $template = $proposal->product?->proposalTemplate;

        // Se não houver template para o produto, tenta usar um ativo padrão
        if (!$template) {
            $template = \App\Models\ProposalTemplate::where('is_active', true)->first();
        }

        if (!$template || !$template->file_path || !\Illuminate\Support\Facades\Storage::exists($template->file_path)) {
            throw new ContractGenerationException('Nenhum modelo de proposta em Word configurado no sistema ou o arquivo não foi encontrado.', 404);
        }

        $filePath = \Illuminate\Support\Facades\Storage::path($template->file_path);

        try {
            $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($filePath);
        } catch (\Exception $e) {
            throw new ContractGenerationException('Erro ao processar o arquivo Word: ' . $e->getMessage(), 500);
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

        $paymentMethodsMap = \App\Models\PaymentMethod::pluck('type', 'name')->toArray();

        $formatPaymentType = function ($type, $name = null) {
            if ($name) {
                $lowerName = mb_strtolower($name, 'UTF-8');
                if (str_contains($lowerName, 'cartão de credito') || str_contains($lowerName, 'cartão de crédito')) return 'Cartão de Crédito';
                if (str_contains($lowerName, 'boleto')) return 'Boleto Bancário';
                if (str_contains($lowerName, 'pix')) return 'PIX';
            }
            $map = [
                'credit_card' => 'Cartão de Crédito',
                'debit_card' => 'Cartão de Débito',
                'pix' => 'PIX',
                'boleto' => 'Boleto Bancário',
                'cash' => 'Dinheiro',
                'bank_transfer' => 'Transferência Bancária'
            ];
            return $map[$type] ?? ucfirst(str_replace('_', ' ', $type));
        };

        $buildPaymentSummary = function ($category) use ($proposal, $paymentMethodsMap, $formatPaymentType) {
            if (!$proposal || !$proposal->payments)
                return '';
            $payments = $proposal->payments->where('category', $category);
            if ($payments->isEmpty())
                return '';
            $lines = [];
            foreach ($payments as $payment) {
                $start = $payment->start_date ? \Carbon\Carbon::parse($payment->start_date)->format('d/m/Y') : 'A combinar';
                $val = number_format($payment->installment_value, 2, ',', '.');
                $methodName = $payment->payment_method;
                $methodType = $paymentMethodsMap[$methodName] ?? $methodName;
                $formattedType = $formatPaymentType($methodType, $methodName);
                $lines[] = "{$payment->installments}x de R$ {$val} no {$formattedType} (Início: {$start})";
            }
            return implode('</w:t><w:br/><w:t>+ ', $lines);
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
            '${CONTRATO_FORMA_PAGAMENTO_ENTRADA}' => $formatPaymentType($paymentMethodsMap[$proposal->payments->where('category', 'entrada')->first()?->payment_method] ?? ($proposal->payments->where('category', 'entrada')->first()?->payment_method ?? '')),
            '${CONTRATO_FORMA_PAGAMENTO_SALDO}' => $formatPaymentType($paymentMethodsMap[$proposal->payments->where('category', 'saldo')->first()?->payment_method] ?? ($proposal->payments->where('category', 'saldo')->first()?->payment_method ?? '')),
            '${CONTRATO_FORMA_PAGAMENTO}' => implode(' e ', array_filter(array_unique([
                $formatPaymentType($paymentMethodsMap[$proposal->payments->where('category', 'entrada')->first()?->payment_method] ?? $proposal->payments->where('category', 'entrada')->first()?->payment_method),
                $formatPaymentType($paymentMethodsMap[$proposal->payments->where('category', 'saldo')->first()?->payment_method] ?? $proposal->payments->where('category', 'saldo')->first()?->payment_method)
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

        return ['docx' => $tempPath, 'filename' => $tempFileName];
    }

    /**
     * Preenche o PDF modelo do RCI (script fill_pdf_fields.py) e devolve o caminho do PDF gerado.
     *
     * @throws ContractGenerationException
     */
    public function generateRciPdf(SalesService $service): string
    {
        $service->load(['client.address', 'proposal.product']);
        $client = $service->client;
        $proposal = $service->proposal;

        $template = \App\Models\RciTemplate::where('is_default', true)->first();

        if (!$template || !$template->mapping_config) {
            throw new ContractGenerationException('Nenhum template RCI padrão configurado ou mapeado no sistema.', 422);
        }

        $pdfPath = \Illuminate\Support\Facades\Storage::path($template->file_path);
        
        if (!file_exists($pdfPath)) {
            \Log::error('RCI PDF not found at: ' . $pdfPath);
            throw new ContractGenerationException('Arquivo PDF do modelo RCI não encontrado no servidor.', 422);
        }

        $parseDate = function ($date) {
            if (!$date) return null;
            try {
                if (str_contains($date, '/')) return \Carbon\Carbon::createFromFormat('d/m/Y', $date);
                return \Carbon\Carbon::parse($date);
            } catch (\Exception $e) { return null; }
        };

        // Extrai primeiro/sobrenome
        $clientePrimeiroNome = $client->nome;
        $clienteSobrenome = '';
        if (strpos($client->nome, ' ') !== false) {
            $clientePrimeiroNome = explode(' ', $client->nome)[0];
            $clienteSobrenome = substr(strstr($client->nome, ' '), 1);
        }

        $conjugePrimeiroNome = $service->nome_conjuge ?? '';
        $conjugeSobrenome = '';
        if ($service->nome_conjuge && strpos($service->nome_conjuge, ' ') !== false) {
            $conjugePrimeiroNome = explode(' ', $service->nome_conjuge)[0];
            $conjugeSobrenome = substr(strstr($service->nome_conjuge, ' '), 1);
        }

        $realDataMapping = [
            'resort_nome' => $proposal && $proposal->product ? $proposal->product->name : 'Itacaré Vacation Club',
            'resort_id' => '',
            'data_atual' => date('d/m/Y'),
            'assoc1_nome_completo' => $client->nome ?? '',
            'assoc1_sobrenome' => trim($clienteSobrenome),
            'assoc1_nomes' => trim($clientePrimeiroNome),
            'assoc1_rg_cpf' => $client->cpf ?? '',
            'assoc2_nome_completo' => $service->nome_conjuge ?? '',
            'assoc2_sobrenome' => trim($conjugeSobrenome),
            'assoc2_nomes' => trim($conjugePrimeiroNome),
            'assoc2_rg_cpf' => $service->cpf_conjuge ?? '',
            'assoc1_data_nascimento' => ($client && $client->data_nascimento && ($d = $parseDate($client->data_nascimento))) ? $d->format('d/m/Y') : '',
            'assoc2_data_nascimento' => ($service->data_nascimento_conjuge && ($d = $parseDate($service->data_nascimento_conjuge))) ? $d->format('d/m/Y') : '',
            'assoc1_nacionalidade' => $client->nacionalidade ?? '',
            'assoc2_nacionalidade' => $service->nacionalidade_conjuge ?? '',
            'endereco_residencial' => $client->address ? trim($client->address->rua . ', ' . $client->address->numero . ($client->address->complemento ? ' - ' . $client->address->complemento : '')) : '',
            'endereco_pais' => 'Brasil',
            'endereco_cidade' => $client->address->cidade ?? '',
            'endereco_estado' => $client->address->estado ?? '',
            'endereco_bairro' => $client->address->bairro ?? '',
            'endereco_cep' => $client->address->cep ?? '',
            'telefone_1' => $client->celular1 ?? '',
            'telefone_2' => $client->celular2 ?? '',
            'telefone_3' => '',
            'email_1' => $client->email ?? '',
            'email_2' => '',
        ];

        $mappingConfig = is_string($template->mapping_config) ? json_decode($template->mapping_config, true) : $template->mapping_config;
        
        $pdfFieldsData = [];
        if (is_array($mappingConfig)) {
            foreach ($mappingConfig as $mapping) {
                $pdfFieldName = $mapping['pdf_field'] ?? null;
                $systemVar = $mapping['system_var'] ?? null;
                
                if ($pdfFieldName && $systemVar) {
                    if (str_starts_with($systemVar, 'CUSTOM:')) {
                        $pdfFieldsData[$pdfFieldName] = substr($systemVar, 7);
                    } else {
                        $pdfFieldsData[$pdfFieldName] = $realDataMapping[$systemVar] ?? '';
                    }
                }
            }
        }

        $outputFilename = 'rci_atendimento_' . $service->id . '_' . time() . '.pdf';
        $outputPath = storage_path('app/public/temp/' . $outputFilename);
        $jsonPath = storage_path('app/public/temp/rci_data_' . time() . '_' . rand(1000, 9999) . '.json');

        if (!file_exists(storage_path('app/public/temp'))) {
            mkdir(storage_path('app/public/temp'), 0755, true);
        }

        file_put_contents($jsonPath, json_encode($pdfFieldsData));

        $scriptPath = base_path('fill_pdf_fields.py');
        $pythonPath = env('PYTHON_PATH', 'python');
        $process = new \Symfony\Component\Process\Process([$pythonPath, $scriptPath, $pdfPath, $outputPath, $jsonPath]);
        $process->run();

        @unlink($jsonPath);

        if (!$process->isSuccessful() || !is_file($outputPath)) {
            \Log::error('Erro ao gerar RCI (Python): ' . \App\Support\Utf8::clean($process->getErrorOutput()) . ' | STDOUT: ' . \App\Support\Utf8::clean($process->getOutput()));
            throw new ContractGenerationException('Falha ao processar o PDF do RCI. Verifique os logs.', 500);
        }

        return $outputPath;
    }

    /**
     * Converte o .docx para PDF com o LibreOffice (soffice). Apaga o .docx em caso de sucesso.
     *
     * @throws ContractGenerationException quando a conversão falha
     */
    public function convertToPdf(string $docxPath): string
    {
        $pdfFileName = preg_replace('/\.docx$/i', '.pdf', basename($docxPath));
        $pdfPath = dirname($docxPath) . DIRECTORY_SEPARATOR . $pdfFileName;

        try {
            $converter = new \NcJoes\OfficeConverter\OfficeConverter($docxPath, dirname($docxPath), $this->libreOfficeBinary(), false);
            $converter->convertTo($pdfFileName);
        } catch (\Exception $e) {
            // O detalhe técnico (saída do sistema) vai para o log; o usuário recebe uma mensagem clara
            \Illuminate\Support\Facades\Log::warning('Contrato: falha na conversão para PDF', ['erro' => \App\Support\Utf8::clean($e->getMessage())]);
            throw new ContractGenerationException('Não foi possível converter o contrato para PDF. Verifique se o LibreOffice está instalado no servidor.', 500);
        }

        if (!\Illuminate\Support\Facades\File::exists($pdfPath)) {
            throw new ContractGenerationException('Falha ao gerar o arquivo PDF do contrato. Verifique se o LibreOffice está instalado no servidor.', 500);
        }

        @unlink($docxPath);

        return $pdfPath;
    }

    /**
     * Executável do LibreOffice: LIBREOFFICE_BINARY no .env, ou a instalação padrão do Windows, ou "soffice" do PATH.
     */
    public function libreOfficeBinary(): string
    {
        $configured = config('services.libreoffice.binary');
        if ($configured) {
            return $configured;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            foreach ([
                'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
                'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe',
            ] as $path) {
                if (is_file($path)) {
                    return $path;
                }
            }
        }

        return 'soffice';
    }

    /**
     * Gera o documento já em PDF (obrigatório para assinatura digital): contrato, proposta ou rci.
     *
     * @throws ContractGenerationException
     */
    public function generatePdf(SalesService $service, string $documentType = 'contrato'): string
    {
        if ($documentType === 'rci') {
            return $this->generateRciPdf($service);
        }

        $docx = $documentType === 'proposta' ? $this->generateProposalDocx($service) : $this->generateDocx($service);

        try {
            return $this->convertToPdf($docx['docx']);
        } catch (ContractGenerationException $e) {
            @unlink($docx['docx']);
            throw $e;
        }
    }
}

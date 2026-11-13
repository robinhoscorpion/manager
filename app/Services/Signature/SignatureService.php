<?php

namespace App\Services\Signature;

use App\Models\ContractSignature;
use App\Models\ContractSignatureSigner;
use App\Models\SalesService;
use App\Models\User;
use App\Services\Contracts\ContractDocumentService;
use App\Services\Contracts\ContractGenerationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Fluxo de assinatura digital, independente do provedor:
 * gerar PDF → enviar → acompanhar (webhook/consulta) → baixar assinado → marcar o contrato como assinado.
 */
class SignatureService
{
    public function __construct(
        private SignatureManager $manager,
        private ContractDocumentService $documents,
    ) {
    }

    /**
     * Signatários sugeridos para um atendimento, conforme a ficha e as configurações.
     *
     * @return array<int, array{role: string, role_label: string, name: string, email: ?string, phone: ?string, cpf: ?string}>
     */
    public function suggestedSigners(SalesService $service): array
    {
        $service->loadMissing('client');
        $settings = SignatureSettings::get();
        $client = $service->client;

        $signers = [];

        if ($client) {
            $signers[] = [
                'role' => ContractSignatureSigner::ROLE_HOLDER,
                'name' => $client->nome,
                'email' => $client->email ?: null,
                'phone' => $client->celular1 ?: null,
                'cpf' => $client->cpf ?: null,
            ];
        }

        if ($settings['include_second_holder'] && $service->tem_conjuge && $service->nome_conjuge) {
            $signers[] = [
                'role' => ContractSignatureSigner::ROLE_SECOND_HOLDER,
                'name' => $service->nome_conjuge,
                'email' => $service->email_conjuge ?: null,
                'phone' => $service->celular_conjuge ?: null,
                'cpf' => $service->cpf_conjuge ?: null,
            ];
        }

        $company = $settings['company_signer'];
        if (!empty($company['enabled']) && !empty($company['name'])) {
            $signers[] = [
                'role' => ContractSignatureSigner::ROLE_COMPANY,
                'name' => $company['name'],
                'email' => $company['email'] ?: null,
                'phone' => null,
                'cpf' => $company['cpf'] ?: null,
            ];
        }

        return array_map(fn ($s) => $s + ['role_label' => ContractSignatureSigner::ROLE_LABELS[$s['role']]], $signers);
    }

    /**
     * Envia um documento do atendimento (contrato, proposta ou rci) para assinatura no provedor configurado.
     * Cada documento é um envio separado, com status próprio.
     *
     * @param array<int, array{role: string, name: string, email?: ?string, phone?: ?string, cpf?: ?string}> $signers
     * @throws SignatureException
     */
    public function send(SalesService $service, array $signers, string $deliveryMethod, ?User $user = null, string $documentType = ContractSignature::DOCUMENT_CONTRACT): ContractSignature
    {
        if (!array_key_exists($documentType, ContractSignature::DOCUMENT_LABELS)) {
            throw new SignatureException('Tipo de documento inválido para assinatura.');
        }
        $documentLabel = ContractSignature::DOCUMENT_LABELS[$documentType];

        $settings = SignatureSettings::get();
        $provider = $this->manager->provider();

        if (!$provider->supportsSending()) {
            throw new SignatureException('Nenhum provedor de assinatura digital ativo. Escolha um em Configurações → Assinatura Digital.');
        }

        if (ContractSignature::where('sales_service_id', $service->id)->where('document_type', $documentType)->whereIn('status', ContractSignature::OPEN_STATUSES)->exists()) {
            throw new SignatureException("{$documentLabel}: já existe um envio de assinatura em andamento. Cancele-o antes de enviar novamente.");
        }

        $signers = $this->validateSigners($signers, $deliveryMethod);

        // 1. Gera o PDF (assinatura digital exige PDF; sem LibreOffice o envio é interrompido)
        try {
            $tempPdf = $this->documents->generatePdf($service, $documentType);
        } catch (ContractGenerationException $e) {
            throw new SignatureException("{$documentLabel}: " . $e->getMessage(), 0, $e);
        }

        $service->loadMissing('proposal', 'client');
        $contractNumber = $service->proposal?->contract_number ?: str_pad((string) $service->proposal?->id, 5, '0', STR_PAD_LEFT);
        $documentName = trim($documentLabel . ' ' . $contractNumber . ' - ' . ($service->client->nome ?? ''));

        $disk = config('signature.disk', 'local');
        $originalPath = config('signature.directory') . '/' . $service->id . '/' . $documentType . '-' . Str::uuid() . '.pdf';
        Storage::disk($disk)->put($originalPath, file_get_contents($tempPdf));
        @unlink($tempPdf);

        // 2. Registra o envio e os signatários
        $signature = DB::transaction(function () use ($service, $provider, $settings, $documentName, $deliveryMethod, $originalPath, $signers, $user, $documentType) {
            $signature = ContractSignature::create([
                'sales_service_id' => $service->id,
                'document_type' => $documentType,
                'provider' => $provider->key(),
                'status' => ContractSignature::STATUS_DRAFT,
                'document_name' => $documentName,
                'delivery_method' => $deliveryMethod,
                'sandbox' => (bool) $settings['sandbox'],
                'original_file_path' => $originalPath,
                'sent_by' => $user?->id,
            ]);

            foreach (array_values($signers) as $i => $signer) {
                $signature->signers()->create($signer + ['order' => $i, 'status' => ContractSignatureSigner::STATUS_PENDING]);
            }

            return $signature;
        });

        // 3. Envia ao provedor
        try {
            $result = $provider->send(
                Storage::disk($disk)->path($originalPath),
                $documentName,
                array_values($signers),
                ['delivery_method' => $deliveryMethod, 'message' => $settings['message'] ?? null]
            );
        } catch (\Throwable $e) {
            $signature->status = ContractSignature::STATUS_ERROR;
            $signature->error_message = \App\Support\Utf8::clean($e->getMessage());
            $signature->addLog('Falha ao enviar para ' . $provider->label(), ['erro' => $e->getMessage()]);
            $signature->save();

            Log::warning('Assinatura digital: falha no envio', ['contract_signature_id' => $signature->id, 'error' => $e->getMessage()]);

            throw $e instanceof SignatureException ? $e : new SignatureException('Falha ao enviar para assinatura: ' . $e->getMessage(), 0, $e);
        }

        foreach ($signature->signers as $i => $signer) {
            $signer->update([
                'external_id' => $result['signers'][$i]['external_id'] ?? null,
                'sign_url' => $result['signers'][$i]['sign_url'] ?? null,
            ]);
        }

        $signature->external_id = $result['external_id'];
        $signature->status = ContractSignature::STATUS_SENT;
        $signature->sent_at = now();
        $signature->error_message = null;
        $signature->addLog('Enviado para assinatura via ' . $provider->label() . ($signature->sandbox ? ' (sandbox)' : ''), [
            'canal' => $deliveryMethod,
            'por' => $user?->name,
        ]);
        $signature->save();

        if ($documentType === ContractSignature::DOCUMENT_CONTRACT) {
            $service->update(['contract_signature_status' => 'pending']);
        }

        return $signature->fresh('signers');
    }

    /**
     * Consulta o provedor e atualiza status/signatários; ao concluir, baixa o PDF assinado.
     */
    public function sync(ContractSignature $signature): ContractSignature
    {
        if (!$signature->external_id || !$this->manager->has($signature->provider)) {
            return $signature;
        }

        $provider = $this->manager->provider($signature->provider);
        $remote = $provider->fetch($signature->external_id);

        $signature->loadMissing('signers');
        foreach ($remote['signers'] as $remoteSigner) {
            $local = $signature->signers->firstWhere('external_id', $remoteSigner['external_id'])
                ?? ($remoteSigner['email'] ? $signature->signers->first(fn ($s) => $s->email && strcasecmp($s->email, $remoteSigner['email']) === 0) : null);

            if (!$local) {
                continue;
            }

            $before = $local->status;
            $local->fill([
                'external_id' => $local->external_id ?: $remoteSigner['external_id'],
                'status' => $remoteSigner['status'],
                'viewed_at' => $remoteSigner['viewed_at'] ?? $local->viewed_at,
                'signed_at' => $remoteSigner['signed_at'] ?? $local->signed_at,
                'refused_at' => $remoteSigner['refused_at'] ?? $local->refused_at,
            ])->save();

            if ($before !== $local->status) {
                $signature->addLog("{$local->role_label} ({$local->name}): " . match ($local->status) {
                    ContractSignatureSigner::STATUS_SIGNED => 'assinou',
                    ContractSignatureSigner::STATUS_REFUSED => 'recusou',
                    ContractSignatureSigner::STATUS_VIEWED => 'visualizou',
                    default => $local->status,
                });
            }
        }

        $previousStatus = $signature->status;
        $signature->status = $remote['status'];
        $signature->last_synced_at = now();

        if ($signature->status === ContractSignature::STATUS_SIGNED && !$signature->signed_file_path && $remote['signed_file_available']) {
            $this->storeSignedFile($signature, $provider->downloadSigned($signature->external_id));
        }

        if ($previousStatus !== $signature->status) {
            $signature->addLog('Status: ' . $signature->status_label);
        }

        $signature->save();

        return $signature->fresh('signers');
    }

    /**
     * Sincroniza sem lançar exceção (webhook e rotina agendada).
     */
    public function syncQuietly(ContractSignature $signature): void
    {
        try {
            $this->sync($signature);
        } catch (\Throwable $e) {
            $signature->addLog('Falha ao consultar o provedor', ['erro' => $e->getMessage()]);
            $signature->save();
            Log::warning('Assinatura digital: falha na sincronização', ['contract_signature_id' => $signature->id, 'error' => $e->getMessage()]);
        }
    }

    public function cancel(ContractSignature $signature, ?User $user = null): ContractSignature
    {
        if (!$signature->isOpen() && $signature->status !== ContractSignature::STATUS_ERROR) {
            throw new SignatureException('Somente envios em andamento podem ser cancelados.');
        }

        if ($signature->external_id && $this->manager->has($signature->provider)) {
            $this->manager->provider($signature->provider)->cancel($signature->external_id);
        }

        $signature->status = ContractSignature::STATUS_CANCELLED;
        $signature->cancelled_at = now();
        $signature->addLog('Envio cancelado', ['por' => $user?->name]);
        $signature->save();

        return $signature;
    }

    public function resend(ContractSignature $signature): void
    {
        if (!$signature->isOpen()) {
            throw new SignatureException('Somente envios em andamento podem ser reenviados.');
        }

        $pending = $signature->signers()
            ->whereNotIn('status', [ContractSignatureSigner::STATUS_SIGNED, ContractSignatureSigner::STATUS_REFUSED])
            ->pluck('external_id')->filter()->all();

        $this->manager->provider($signature->provider)->resend($pending);

        $signature->addLog('Lembrete reenviado aos signatários pendentes');
        $signature->save();
    }

    /**
     * Guarda o PDF assinado e marca o contrato do atendimento como assinado
     * (mesmos campos usados pelo upload manual em Entrega de Contrato).
     */
    private function storeSignedFile(ContractSignature $signature, string $contents): void
    {
        $disk = config('signature.disk', 'local');
        $path = config('signature.directory') . '/' . $signature->sales_service_id . '/' . $signature->document_type . '-' . Str::uuid() . '-assinado.pdf';
        Storage::disk($disk)->put($path, $contents);

        $signature->signed_file_path = $path;
        $signature->signed_at = now();
        $signature->addLog('PDF assinado baixado e guardado');

        // Só o contrato alimenta os campos usados em Entrega de Contrato / botão "Contrato"
        if ($signature->document_type === ContractSignature::DOCUMENT_CONTRACT) {
            $signature->salesService?->update([
                'contract_signature_status' => 'signed',
                'contract_signed_at' => now(),
                'contract_file_path' => $path,
            ]);
        }
    }

    /**
     * Confere os signatários antes de enviar.
     *
     * @throws SignatureException
     */
    private function validateSigners(array $signers, string $deliveryMethod): array
    {
        $clean = [];

        foreach ($signers as $signer) {
            $role = $signer['role'] ?? '';
            $name = trim((string) ($signer['name'] ?? ''));
            if (!$name || !array_key_exists($role, ContractSignatureSigner::ROLE_LABELS)) {
                continue;
            }

            $item = [
                'role' => $role,
                'name' => $name,
                'email' => filled($signer['email'] ?? null) ? trim($signer['email']) : null,
                'phone' => filled($signer['phone'] ?? null) ? preg_replace('/\D+/', '', $signer['phone']) : null,
                'cpf' => filled($signer['cpf'] ?? null) ? trim($signer['cpf']) : null,
            ];

            $label = ContractSignatureSigner::ROLE_LABELS[$role] . " ({$name})";

            if ($item['email'] && !filter_var($item['email'], FILTER_VALIDATE_EMAIL)) {
                throw new SignatureException("E-mail inválido para {$label}.");
            }
            if ($role === ContractSignatureSigner::ROLE_COMPANY && !$item['email']) {
                throw new SignatureException("Informe o e-mail do representante da empresa ({$name}).");
            }
            if ($role !== ContractSignatureSigner::ROLE_COMPANY && $deliveryMethod === 'email' && !$item['email']) {
                throw new SignatureException("Informe o e-mail de {$label} ou escolha outro canal de envio.");
            }
            if ($role !== ContractSignatureSigner::ROLE_COMPANY && $deliveryMethod === 'whatsapp' && strlen((string) $item['phone']) < 10) {
                throw new SignatureException("Informe o celular com DDD de {$label} ou escolha outro canal de envio.");
            }

            $clean[] = $item;
        }

        if (!collect($clean)->contains('role', ContractSignatureSigner::ROLE_HOLDER)) {
            throw new SignatureException('O 1º titular precisa estar entre os signatários.');
        }

        return $clean;
    }
}

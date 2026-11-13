<?php

namespace App\Services\Signature\Contracts;

use Illuminate\Http\Request;

/**
 * Contrato que todo provedor de assinatura digital implementa.
 * O restante do sistema só conversa com esta interface — nunca com a API do provedor.
 *
 * Status de documento (padronizados): ver App\Models\ContractSignature::STATUS_*
 * Status de signatário (padronizados): pending, viewed, signed, refused
 */
interface SignatureProvider
{
    /** Identificador usado nas configurações (ex.: "autentique"). */
    public function key(): string;

    /** Nome exibido na tela. */
    public function label(): string;

    /** false = não envia documentos (ex.: provedor Manual, que usa o upload do contrato assinado). */
    public function supportsSending(): bool;

    /**
     * Campos de credencial exibidos em Configurações → Assinatura Digital.
     *
     * @return array<int, array{key: string, label: string, type: string, help?: string}>
     */
    public function credentialFields(): array;

    /**
     * Canais de envio aceitos pelo provedor.
     *
     * @return array<string, string> ex.: ['email' => 'E-mail', 'whatsapp' => 'WhatsApp', 'link' => 'Link']
     */
    public function deliveryMethods(): array;

    /** @param array $credentials credenciais já descriptografadas */
    public function configure(array $credentials, bool $sandbox): static;

    /** @return array{ok: bool, message: string} */
    public function testConnection(): array;

    /**
     * Envia o PDF para assinatura.
     *
     * @param array<int, array{name: string, email: ?string, phone: ?string, cpf: ?string, role: string}> $signers
     * @param array{delivery_method: string, message?: ?string} $options
     * @return array{external_id: string, signers: array<int, array{external_id: ?string, sign_url: ?string}>}
     *         signers na mesma ordem recebida
     */
    public function send(string $pdfPath, string $documentName, array $signers, array $options): array;

    /**
     * Situação atual do documento no provedor.
     *
     * @return array{
     *     status: string,
     *     signed_file_available: bool,
     *     signers: array<int, array{external_id: ?string, email: ?string, name: ?string, status: string, viewed_at: ?string, signed_at: ?string, refused_at: ?string}>
     * }
     */
    public function fetch(string $externalId): array;

    /** Conteúdo binário do PDF assinado. */
    public function downloadSigned(string $externalId): string;

    public function cancel(string $externalId): void;

    /** @param string[] $signerExternalIds */
    public function resend(array $signerExternalIds): void;

    /** Valida a autenticidade do aviso (webhook) recebido. */
    public function verifyWebhook(Request $request, ?string $secret): bool;

    /** ID externo do documento citado no webhook (null se o aviso não for de documento). */
    public function webhookDocumentId(Request $request): ?string;
}

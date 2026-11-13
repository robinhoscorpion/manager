<?php

namespace App\Services\Signature\Providers;

use App\Services\Signature\Contracts\SignatureProvider;
use App\Services\Signature\SignatureException;
use Illuminate\Http\Request;

/**
 * Sem assinatura digital: o contrato é impresso/assinado à parte e o arquivo assinado
 * é anexado em Pós-venda → Entrega de Contrato (fluxo que já existia).
 */
class ManualProvider implements SignatureProvider
{
    public function key(): string
    {
        return 'manual';
    }

    public function label(): string
    {
        return 'Manual (anexar contrato assinado)';
    }

    public function supportsSending(): bool
    {
        return false;
    }

    public function credentialFields(): array
    {
        return [];
    }

    public function deliveryMethods(): array
    {
        return [];
    }

    public function configure(array $credentials, bool $sandbox): static
    {
        return $this;
    }

    public function testConnection(): array
    {
        return ['ok' => true, 'message' => 'Modo manual não usa integração externa.'];
    }

    public function send(string $pdfPath, string $documentName, array $signers, array $options): array
    {
        throw new SignatureException('Nenhum provedor de assinatura digital configurado. Anexe o contrato assinado em Entrega de Contrato ou escolha um provedor em Configurações → Assinatura Digital.');
    }

    public function fetch(string $externalId): array
    {
        throw new SignatureException('O modo manual não consulta status em provedores.');
    }

    public function downloadSigned(string $externalId): string
    {
        throw new SignatureException('O modo manual não baixa contratos de provedores.');
    }

    public function cancel(string $externalId): void
    {
    }

    public function resend(array $signerExternalIds): void
    {
    }

    public function verifyWebhook(Request $request, ?string $secret): bool
    {
        return false;
    }

    public function webhookDocumentId(Request $request): ?string
    {
        return null;
    }
}

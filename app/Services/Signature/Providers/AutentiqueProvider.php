<?php

namespace App\Services\Signature\Providers;

use App\Models\ContractSignature;
use App\Models\ContractSignatureSigner;
use App\Services\Signature\Contracts\SignatureProvider;
use App\Services\Signature\SignatureException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

/**
 * Autentique — API GraphQL v2 (https://docs.autentique.com.br/api).
 */
class AutentiqueProvider implements SignatureProvider
{
    public const ENDPOINT = 'https://api.autentique.com.br/v2/graphql';

    private string $token = '';
    private bool $sandbox = true;

    public function key(): string
    {
        return 'autentique';
    }

    public function label(): string
    {
        return 'Autentique';
    }

    public function supportsSending(): bool
    {
        return true;
    }

    public function credentialFields(): array
    {
        return [
            [
                'key' => 'token',
                'label' => 'Token da API',
                'type' => 'password',
                'help' => 'Gerado no painel da Autentique em Configurações → API (Painel do Desenvolvedor).',
            ],
        ];
    }

    public function deliveryMethods(): array
    {
        return [
            'email' => 'E-mail',
            'whatsapp' => 'WhatsApp',
            'link' => 'Link (assinar na hora / enviar manualmente)',
        ];
    }

    public function configure(array $credentials, bool $sandbox): static
    {
        $this->token = (string) ($credentials['token'] ?? '');
        $this->sandbox = $sandbox;

        return $this;
    }

    public function testConnection(): array
    {
        try {
            $data = $this->graphql('query { me { id name email } }');
            $me = $data['me'] ?? null;

            return [
                'ok' => (bool) $me,
                'message' => $me ? "Conectado como {$me['name']} ({$me['email']})." : 'Resposta inesperada da Autentique.',
            ];
        } catch (SignatureException $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    public function send(string $pdfPath, string $documentName, array $signers, array $options): array
    {
        if (!is_file($pdfPath)) {
            throw new SignatureException('Arquivo PDF do contrato não encontrado para envio.');
        }

        $delivery = $options['delivery_method'] ?? 'email';

        $signerInputs = array_map(fn ($signer) => $this->signerInput($signer, $delivery), $signers);

        $document = array_filter([
            'name' => $documentName,
            'message' => $options['message'] ?? null,
            'refusable' => true,
        ], fn ($value) => $value !== null && $value !== '');

        $mutation = 'mutation CreateDocumentMutation($document: DocumentInput!, $signers: [SignerInput!]!, $file: Upload!, $sandbox: Boolean) {
            createDocument(document: $document, signers: $signers, file: $file, sandbox: $sandbox) {
                id
                name
                signatures { public_id name email action { name } link { short_link } }
            }
        }';

        $operations = [
            'query' => $mutation,
            'variables' => [
                'document' => $document,
                'signers' => $signerInputs,
                'file' => null,
                'sandbox' => $this->sandbox,
            ],
        ];

        $response = $this->http()
            ->attach('operations', json_encode($operations))
            ->attach('map', json_encode(['file' => ['variables.file']]))
            ->attach('file', file_get_contents($pdfPath), basename($pdfPath), ['Content-Type' => 'application/pdf'])
            ->post(self::ENDPOINT);

        $created = $this->extract($response)['createDocument'] ?? null;
        if (!$created || empty($created['id'])) {
            throw new SignatureException('A Autentique não retornou o documento criado.');
        }

        return [
            'external_id' => $created['id'],
            'signers' => $this->matchSigners($signers, $created['signatures'] ?? []),
        ];
    }

    public function fetch(string $externalId): array
    {
        $query = 'query {
            document(id: ' . $this->literal($externalId) . ') {
                id
                files { original signed }
                signatures {
                    public_id name email
                    action { name }
                    viewed { created_at }
                    signed { created_at }
                    rejected { created_at }
                }
            }
        }';

        $document = $this->graphql($query)['document'] ?? null;
        if (!$document) {
            throw new SignatureException('Documento não encontrado na Autentique (pode ter sido excluído).');
        }

        // Somente quem tem ação (assinar/testemunhar...) é signatário; o dono do documento pode aparecer sem ação
        $signatures = array_values(array_filter($document['signatures'] ?? [], fn ($s) => !empty($s['action'])));

        $signers = array_map(function ($s) {
            $status = ContractSignatureSigner::STATUS_PENDING;
            if (!empty($s['rejected'])) {
                $status = ContractSignatureSigner::STATUS_REFUSED;
            } elseif (!empty($s['signed'])) {
                $status = ContractSignatureSigner::STATUS_SIGNED;
            } elseif (!empty($s['viewed'])) {
                $status = ContractSignatureSigner::STATUS_VIEWED;
            }

            return [
                'external_id' => $s['public_id'] ?? null,
                'email' => $s['email'] ?? null,
                'name' => $s['name'] ?? null,
                'status' => $status,
                'viewed_at' => Arr::get($s, 'viewed.created_at'),
                'signed_at' => Arr::get($s, 'signed.created_at'),
                'refused_at' => Arr::get($s, 'rejected.created_at'),
            ];
        }, $signatures);

        $statuses = array_column($signers, 'status');
        $allSigned = $statuses && !array_diff($statuses, [ContractSignatureSigner::STATUS_SIGNED]);

        return [
            'status' => $this->documentStatus($statuses),
            'signed_file_available' => $allSigned && !empty(Arr::get($document, 'files.signed')),
            'signers' => $signers,
            'signed_file_url' => Arr::get($document, 'files.signed'),
        ];
    }

    public function downloadSigned(string $externalId): string
    {
        $url = $this->fetch($externalId)['signed_file_url'] ?? null;
        if (!$url) {
            throw new SignatureException('O PDF assinado ainda não está disponível na Autentique.');
        }

        $response = $this->http()->get($url);
        $body = $response->body();

        if (!$response->successful() || !str_starts_with($body, '%PDF')) {
            throw new SignatureException('Não foi possível baixar o PDF assinado da Autentique (HTTP ' . $response->status() . ').');
        }

        return $body;
    }

    public function cancel(string $externalId): void
    {
        $this->graphql('mutation { deleteDocument(id: ' . $this->literal($externalId) . ') }');
    }

    public function resend(array $signerExternalIds): void
    {
        $ids = array_values(array_filter($signerExternalIds));
        if (!$ids) {
            return;
        }

        $list = implode(', ', array_map(fn ($id) => $this->literal($id), $ids));
        $this->graphql('mutation { resendSignatures(public_ids: [' . $list . ']) }');
    }

    public function verifyWebhook(Request $request, ?string $secret): bool
    {
        // Sem segredo configurado, a proteção é o token na URL + a reconsulta na API (o conteúdo do aviso não é usado como verdade)
        if (!$secret) {
            return true;
        }

        $signature = (string) ($request->header('X-Autentique-Signature') ?? '');
        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        return $signature !== '' && hash_equals($expected, strtolower($signature));
    }

    public function webhookDocumentId(Request $request): ?string
    {
        $type = (string) $request->input('event.type', '');
        $isDocumentEvent = str_starts_with($type, 'document.');

        // Eventos de assinatura trazem a própria assinatura em event.data (com o ID do documento em "document");
        // alguns formatos usam event.data.object — os dois são aceitos
        $candidates = [];
        foreach ([(array) $request->input('event.data', []), (array) $request->input('event.data.object', [])] as $data) {
            $candidates[] = Arr::get($data, 'document.id');
            $candidates[] = is_string(Arr::get($data, 'document')) ? Arr::get($data, 'document') : null;
            $candidates[] = Arr::get($data, 'document_id');
            $candidates[] = $isDocumentEvent ? Arr::get($data, 'id') : null;
        }

        // formato antigo (descontinuado) de webhook
        $candidates[] = $request->input('documento.uuid');

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && $candidate !== '') {
                return $candidate;
            }
        }

        return null;
    }

    // ─── Internos ──────────────────────────────────────────────────────────

    private function http(): PendingRequest
    {
        if ($this->token === '') {
            throw new SignatureException('Token da API da Autentique não configurado (Configurações → Assinatura Digital).');
        }

        return Http::withToken($this->token)
            ->acceptJson()
            ->timeout((int) config('signature.http_timeout', 60));
    }

    private function graphql(string $query, array $variables = []): array
    {
        $response = $this->http()->post(self::ENDPOINT, ['query' => $query, 'variables' => (object) $variables]);

        return $this->extract($response);
    }

    private function extract(Response $response): array
    {
        if ($response->status() === 401) {
            throw new SignatureException('Token da Autentique inválido ou expirado.');
        }

        $json = $response->json();

        if (!empty($json['errors'])) {
            $messages = collect($json['errors'])->pluck('message')->filter()->implode('; ');
            throw new SignatureException('Autentique: ' . ($messages ?: 'erro desconhecido.'));
        }

        if (!$response->successful() || !is_array($json) || !array_key_exists('data', $json)) {
            throw new SignatureException('Falha na comunicação com a Autentique (HTTP ' . $response->status() . ').');
        }

        return (array) $json['data'];
    }

    /**
     * Monta o SignerInput conforme o canal: e-mail, WhatsApp ou link (por nome).
     */
    private function signerInput(array $signer, string $delivery): array
    {
        $action = $signer['role'] === ContractSignatureSigner::ROLE_WITNESS ? 'SIGN_AS_A_WITNESS' : 'SIGN';

        // Representante da empresa sempre recebe por e-mail quando houver
        if ($signer['role'] === ContractSignatureSigner::ROLE_COMPANY && !empty($signer['email'])) {
            return ['email' => $signer['email'], 'action' => $action];
        }

        if ($delivery === 'whatsapp' && !empty($signer['phone'])) {
            return ['phone' => $this->phone($signer['phone']), 'delivery_method' => 'DELIVERY_METHOD_WHATSAPP', 'action' => $action];
        }

        if ($delivery === 'email' && !empty($signer['email'])) {
            return ['email' => $signer['email'], 'action' => $action];
        }

        // Link: a Autentique devolve um link de assinatura exclusivo do signatário
        return ['name' => $signer['name'], 'action' => $action];
    }

    /**
     * String GraphQL com escape (IDs são inseridos direto na consulta para não depender do tipo declarado no schema).
     */
    private function literal(string $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function phone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if (strlen($digits) <= 11) {
            $digits = '55' . $digits;
        }

        return '+' . $digits;
    }

    /**
     * Relaciona os signatários enviados com as assinaturas devolvidas (e-mail, nome ou ordem).
     */
    private function matchSigners(array $signers, array $signatures): array
    {
        $signatures = array_values(array_filter($signatures, fn ($s) => !empty($s['action'])));
        $used = [];
        $result = [];

        foreach ($signers as $signer) {
            $index = null;
            foreach ($signatures as $i => $s) {
                if (isset($used[$i])) {
                    continue;
                }
                $sameEmail = !empty($signer['email']) && !empty($s['email']) && strcasecmp($signer['email'], $s['email']) === 0;
                $sameName = !empty($s['name']) && mb_strtolower(trim($s['name'])) === mb_strtolower(trim($signer['name']));
                if ($sameEmail || $sameName) {
                    $index = $i;
                    break;
                }
            }

            if ($index === null) {
                foreach ($signatures as $i => $s) {
                    if (!isset($used[$i])) {
                        $index = $i;
                        break;
                    }
                }
            }

            if ($index !== null) {
                $used[$index] = true;
            }

            $result[] = [
                'external_id' => $index !== null ? ($signatures[$index]['public_id'] ?? null) : null,
                'sign_url' => $index !== null ? Arr::get($signatures[$index], 'link.short_link') : null,
            ];
        }

        return $result;
    }

    private function documentStatus(array $signerStatuses): string
    {
        if (!$signerStatuses) {
            return ContractSignature::STATUS_SENT;
        }
        if (in_array(ContractSignatureSigner::STATUS_REFUSED, $signerStatuses, true)) {
            return ContractSignature::STATUS_REFUSED;
        }

        $signed = count(array_keys($signerStatuses, ContractSignatureSigner::STATUS_SIGNED, true));
        if ($signed === count($signerStatuses)) {
            return ContractSignature::STATUS_SIGNED;
        }
        if ($signed > 0) {
            return ContractSignature::STATUS_PARTIALLY_SIGNED;
        }
        if (in_array(ContractSignatureSigner::STATUS_VIEWED, $signerStatuses, true)) {
            return ContractSignature::STATUS_VIEWED;
        }

        return ContractSignature::STATUS_SENT;
    }
}

<?php

namespace App\Http\Controllers\AfterSales;

use App\Http\Controllers\Controller;
use App\Models\ContractSignature;
use App\Models\SalesService;
use App\Services\Signature\SignatureException;
use App\Services\Signature\SignatureManager;
use App\Services\Signature\SignatureService;
use App\Services\Signature\SignatureSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Ações de assinatura digital de um contrato (enviar, acompanhar, cancelar, reenviar).
 */
class ContractSignatureController extends Controller
{
    public function __construct(private SignatureService $signatures)
    {
    }

    /**
     * Dados para a janela "Enviar para assinatura": signatários sugeridos, canais e histórico de envios.
     */
    public function show(Request $request, SalesService $salesService, SignatureManager $manager)
    {
        $settings = SignatureSettings::get();
        $provider = $manager->provider();

        // Acompanhamento em tempo real da janela: consulta o provedor no máximo a cada 10s por envio
        if ($request->boolean('sync')) {
            ContractSignature::where('sales_service_id', $salesService->id)
                ->where(function ($q) {
                    $q->whereIn('status', ContractSignature::OPEN_STATUSES)
                      ->orWhere(fn ($q) => $q->where('status', ContractSignature::STATUS_SIGNED)->whereNull('signed_file_path'));
                })
                ->whereNotNull('external_id')
                ->where(fn ($q) => $q->whereNull('last_synced_at')->orWhere('last_synced_at', '<', now()->subSeconds(10)))
                ->get()
                ->each(fn ($signature) => $this->signatures->syncQuietly($signature));
        }

        return response()->json([
            'provider' => ['key' => $provider->key(), 'label' => $provider->label(), 'supports_sending' => $provider->supportsSending()],
            'sandbox' => (bool) $settings['sandbox'],
            'default_delivery' => $settings['default_delivery'],
            'delivery_methods' => $provider->deliveryMethods(),
            'document_types' => ContractSignature::DOCUMENT_LABELS,
            'suggested_signers' => $this->signatures->suggestedSigners($salesService),
            'signatures' => ContractSignature::with('signers', 'sender:id,name')
                ->where('sales_service_id', $salesService->id)
                ->latest()
                ->get()
                ->map(fn ($s) => $this->present($s)),
        ]);
    }

    public function store(Request $request, SalesService $salesService)
    {
        $validated = $request->validate([
            'delivery_method' => 'required|string|in:email,whatsapp,link',
            'document_types' => 'required|array|min:1',
            'document_types.*' => 'required|string|in:' . implode(',', array_keys(ContractSignature::DOCUMENT_LABELS)),
            'signers' => 'required|array|min:1',
            'signers.*.role' => 'required|string',
            'signers.*.name' => 'required|string|max:255',
            'signers.*.email' => 'nullable|string|max:255',
            'signers.*.phone' => 'nullable|string|max:30',
            'signers.*.cpf' => 'nullable|string|max:20',
        ]);

        // Cada documento é um envio separado; um erro em um não impede os outros
        $sent = [];
        $errors = [];
        foreach (array_unique($validated['document_types']) as $type) {
            try {
                $this->signatures->send($salesService, $validated['signers'], $validated['delivery_method'], $request->user(), $type);
                $sent[] = ContractSignature::DOCUMENT_LABELS[$type];
            } catch (SignatureException $e) {
                $errors[] = $e->getMessage();
            }
        }

        if ($errors) {
            $prefix = $sent ? 'Enviado: ' . implode(', ', $sent) . '. ' : '';
            return back()->with('error', $prefix . 'Não enviado — ' . implode(' | ', $errors));
        }

        return back()->with('success', implode(', ', $sent) . (count($sent) > 1 ? ' enviados' : ' enviado') . ' para assinatura!');
    }

    public function sync(ContractSignature $contractSignature)
    {
        try {
            $this->signatures->sync($contractSignature);
        } catch (SignatureException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Status da assinatura atualizado.');
    }

    public function cancel(Request $request, ContractSignature $contractSignature)
    {
        try {
            $this->signatures->cancel($contractSignature, $request->user());
        } catch (SignatureException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Envio de assinatura cancelado.');
    }

    public function resend(ContractSignature $contractSignature)
    {
        try {
            $this->signatures->resend($contractSignature);
        } catch (SignatureException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Lembrete reenviado aos signatários pendentes.');
    }

    /**
     * Baixa o PDF enviado (original) ou o assinado.
     */
    public function download(ContractSignature $contractSignature, string $type)
    {
        $path = $type === 'assinado' ? $contractSignature->signed_file_path : $contractSignature->original_file_path;
        $disk = Storage::disk(config('signature.disk', 'local'));

        abort_unless($path && $disk->exists($path), 404, 'Arquivo não encontrado.');

        $suffix = $type === 'assinado' ? '-assinado' : '';

        return $disk->download($path, \Illuminate\Support\Str::slug($contractSignature->document_name) . $suffix . '.pdf');
    }

    private function present(ContractSignature $signature): array
    {
        return [
            'id' => $signature->id,
            'document_type' => $signature->document_type,
            'document_label' => $signature->document_label,
            'provider' => $signature->provider,
            'status' => $signature->status,
            'status_label' => $signature->status_label,
            'is_open' => $signature->isOpen(),
            'sandbox' => $signature->sandbox,
            'delivery_method' => $signature->delivery_method,
            'document_name' => $signature->document_name,
            'error_message' => $signature->error_message,
            'sent_by' => $signature->sender?->name,
            'sent_at' => $signature->sent_at?->format('d/m/Y H:i'),
            'signed_at' => $signature->signed_at?->format('d/m/Y H:i'),
            'has_signed_file' => (bool) $signature->signed_file_path,
            'log' => $signature->log ?? [],
            'signers' => $signature->signers->map(fn ($s) => [
                'role_label' => $s->role_label,
                'name' => $s->name,
                'email' => $s->email,
                'phone' => $s->phone,
                'status' => $s->status,
                'sign_url' => $s->sign_url,
                'signed_at' => $s->signed_at?->format('d/m/Y H:i'),
            ]),
        ];
    }
}

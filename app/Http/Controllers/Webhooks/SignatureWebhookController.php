<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\ContractSignature;
use App\Services\Signature\SignatureManager;
use App\Services\Signature\SignatureService;
use App\Services\Signature\SignatureSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Recebe os avisos (webhooks) dos provedores de assinatura.
 * O aviso só serve de gatilho: o status real é sempre consultado na API do provedor.
 */
class SignatureWebhookController extends Controller
{
    public function __invoke(Request $request, string $provider, string $token, SignatureManager $manager, SignatureService $signatures)
    {
        $settings = SignatureSettings::get();

        if (!$settings['webhook_token'] || !hash_equals($settings['webhook_token'], $token) || !$manager->has($provider)) {
            return response()->json(['message' => 'not found'], 404);
        }

        $driver = $manager->provider($provider);

        if (!$driver->verifyWebhook($request, $settings['webhook_secret'] ?: null)) {
            Log::warning('Assinatura digital: webhook com assinatura inválida', ['provider' => $provider]);
            return response()->json(['message' => 'invalid signature'], 401);
        }

        $documentId = $driver->webhookDocumentId($request);

        $signature = $documentId
            ? ContractSignature::where('provider', $provider)->where('external_id', $documentId)->first()
            : null;

        // Documento de fora do sistema ou evento sem documento: confirma o recebimento e ignora
        if ($signature) {
            $signatures->syncQuietly($signature);
        }

        return response()->json(['received' => true]);
    }
}

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Socio\SocioAuthController;
use App\Http\Controllers\Api\Socio\SocioFinanceApiController;
use App\Http\Controllers\Api\Socio\SocioReservationApiController;
use App\Http\Controllers\Api\Socio\SocioPointApiController;
use App\Http\Controllers\Api\Socio\SocioProtocolApiController;

/*
|--------------------------------------------------------------------------
| API Routes - Portal do Sócio
|--------------------------------------------------------------------------
*/

// Avisos (webhooks) dos provedores de assinatura digital — protegidos por token na URL
Route::post('/webhooks/assinatura/{provider}/{token}', \App\Http\Controllers\Webhooks\SignatureWebhookController::class)
    ->name('webhooks.signature')
    ->middleware('throttle:120,1');

Route::prefix('socio')->group(function () {
    // Autenticação Pública
    Route::post('/login', [SocioAuthController::class, 'login']);
    Route::post('/primeiro-acesso', [SocioAuthController::class, 'firstAccess']);

    // Rotas Protegidas por Token Sanctum
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [SocioAuthController::class, 'me']);
        Route::post('/logout', [SocioAuthController::class, 'logout']);

        // Financeiro
        Route::get('/financeiro', [SocioFinanceApiController::class, 'index']);
        Route::get('/financeiro/boletos/{id}', [SocioFinanceApiController::class, 'show']);

        // Reservas
        Route::get('/reservas', [SocioReservationApiController::class, 'index']);
        Route::post('/reservas', [SocioReservationApiController::class, 'store']);

        // Pontos e Acomodações
        Route::get('/pontos', [SocioPointApiController::class, 'index']);
        Route::get('/acomodacoes', [SocioPointApiController::class, 'accommodations']);

        // Protocolos e Atendimento
        Route::get('/protocolos', [SocioProtocolApiController::class, 'index']);
        Route::post('/protocolos', [SocioProtocolApiController::class, 'store']);
        Route::post('/protocolos/{id}/reply', [SocioProtocolApiController::class, 'reply']);
    });
});
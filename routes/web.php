<?php

use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SalesServiceController;
use App\Http\Controllers\ServiceLineController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\AdminMaintenanceController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'permission:dashboard.acessar'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Funcionários
    Route::resource('employees', EmployeeController::class)->except(['index', 'show', 'destroy'])->middleware('permission:funcionarios.gerenciar');
    Route::resource('employees', EmployeeController::class)->only(['index', 'show'])->middleware('permission:funcionarios.acessar');
    Route::resource('employees', EmployeeController::class)->only(['destroy'])->middleware('permission:funcionarios.deletar');
    Route::post('/employees/convert/{user}', [EmployeeController::class, 'convertFromUser'])->name('employees.convert')->middleware('permission:funcionarios.gerenciar');
    
    // Usuários
    Route::resource('users', UserController::class)->except(['index', 'show', 'destroy'])->middleware('permission:usuarios.gerenciar');
    Route::resource('users', UserController::class)->only(['index', 'show'])->middleware('permission:usuarios.acessar');
    Route::resource('users', UserController::class)->only(['destroy'])->middleware('permission:usuarios.deletar');
    Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status')->middleware('permission:usuarios.gerenciar');
    Route::post('/users/{user}/impersonate', [UserController::class, 'impersonate'])->name('users.impersonate')->middleware('permission:usuarios.gerenciar');
    Route::post('/impersonate/leave', [UserController::class, 'leaveImpersonation'])->name('users.leave-impersonation');

    // Logs do Sistema
    Route::get('/admin/audit-logs', [AuditLogController::class, 'index'])->name('admin.audit-logs.index')->middleware('permission:configuracoes.logs.acessar');

    // Roles & Permissions
    Route::resource('roles', RoleController::class)->except(['index', 'show'])->middleware('permission:cargos.gerenciar');
    Route::resource('roles', RoleController::class)->only(['index', 'show'])->middleware('permission:cargos.acessar');
    Route::post('/roles/{role}/permissions', [RoleController::class, 'updatePermissions'])->name('roles.permissions.update')->middleware('permission:cargos.gerenciar');

    // Sala de Vendas - Agendamentos
    Route::get('/agendamentos', [\App\Http\Controllers\Sales\ScheduleController::class, 'index'])->name('sales.agendamentos.index')->middleware('permission:agendamentos.acessar');
    Route::post('/agendamentos', [\App\Http\Controllers\Sales\ScheduleController::class, 'store'])->name('sales.agendamentos.store')->middleware('permission:agendamentos.gerenciar');
    Route::patch('/agendamentos/{schedule}/status', [\App\Http\Controllers\Sales\ScheduleController::class, 'updateStatus'])->name('sales.agendamentos.status.update')->middleware('permission:agendamentos.gerenciar');
    
    // Sala de Vendas - Atendimentos
    Route::get('/atendimentos', [SalesServiceController::class, 'index'])->name('sales.atendimentos')->middleware('permission:atendimentos.acessar');
    Route::post('/atendimentos', [SalesServiceController::class, 'store'])->name('sales.atendimentos.store')->middleware('permission:atendimentos.criar');
    Route::put('/atendimentos/{service}', [SalesServiceController::class, 'update'])->name('sales.atendimentos.update')->middleware('permission:atendimentos.editar');
    Route::patch('/atendimentos/{service}/quick-update', [SalesServiceController::class, 'quickUpdate'])->name('sales.atendimentos.quick-update')->middleware('permission:atendimentos.editar');
    Route::delete('/atendimentos/{service}', [SalesServiceController::class, 'destroy'])->name('sales.atendimentos.destroy')->middleware('permission:atendimentos.deletar');
    Route::get('/atendimentos/{service}', [SalesServiceController::class, 'show'])->name('sales.atendimentos.show')->middleware('permission:atendimentos.acessar');
    Route::get('/atendimentos/{service}/cortesia/pdf', [SalesServiceController::class, 'pdfCortesia'])->name('sales.atendimentos.cortesia.pdf')->middleware('permission:atendimentos.acessar');
    Route::get('/atendimentos/{service}/ficha/pdf', [SalesServiceController::class, 'pdfFicha'])->name('sales.atendimentos.ficha.pdf')->middleware('permission:atendimentos.acessar');
    Route::get('/atendimentos/{service}/proposta/pdf', [\App\Http\Controllers\SalesServiceController::class, 'pdfProposta'])->name('sales.atendimentos.proposta.pdf')->middleware('permission:atendimentos.acessar');
    Route::get('/atendimentos/{service}/contrato/pdf', [\App\Http\Controllers\SalesServiceController::class, 'pdfContrato'])->name('sales.atendimentos.contrato.pdf')->middleware('permission:atendimentos.acessar');
    Route::get('/atendimentos/{service}/rci/pdf', [\App\Http\Controllers\SalesServiceController::class, 'pdfRci'])->name('sales.atendimentos.rci.pdf')->middleware('permission:atendimentos.acessar');
    Route::get('/atendimentos/{service}/checklist/pdf', [\App\Http\Controllers\SalesServiceController::class, 'pdfChecklist'])->name('sales.atendimentos.checklist.pdf')->middleware('permission:atendimentos.acessar');
    Route::post('/atendimentos/{service}/protocols', [\App\Http\Controllers\ProtocolController::class, 'store'])->name('sales.atendimentos.protocols.store')->middleware('permission:pos_venda.protocolos.criar');
    Route::patch('/protocols/{protocol}/status', [\App\Http\Controllers\ProtocolController::class, 'updateStatus'])->name('sales.protocols.status.update')->middleware('permission:atendimentos.editar');
    Route::post('/protocols/bulk-update', [\App\Http\Controllers\ProtocolController::class, 'bulkUpdate'])->name('sales.protocols.bulk-update')->middleware('permission:atendimentos.editar');
    Route::post('/protocols/{protocol}/replies', [\App\Http\Controllers\ProtocolReplyController::class, 'store'])->name('sales.protocols.replies.store')->middleware('permission:atendimentos.editar');
    Route::get('/linha-atendimento', [ServiceLineController::class, 'index'])->name('sales.linha')->middleware('permission:atendimentos.acessar');

    // Configurações de Dashboard
    Route::get('/configuracoes/colunas', [\App\Http\Controllers\Admin\ServiceSettingsController::class, 'index'])->name('admin.settings.columns.index')->middleware('permission:configuracoes.colunas.acessar');
    Route::put('/configuracoes/colunas', [\App\Http\Controllers\Admin\ServiceSettingsController::class, 'update'])->name('admin.settings.columns.update')->middleware('permission:configuracoes.colunas.gerenciar');

    Route::get('configuracoes/ficha-templates/{ficha_template}/preview-pdf', [\App\Http\Controllers\Admin\FichaTemplateController::class, 'previewPdf'])->name('admin.settings.ficha_templates.preview_pdf')->middleware('permission:configuracoes.modelos_contrato.acessar');
    Route::resource('configuracoes/ficha-templates', \App\Http\Controllers\Admin\FichaTemplateController::class)->names('admin.settings.ficha_templates')->middleware('permission:configuracoes.modelos_contrato.acessar');

    // Metas da Plataforma
    Route::resource('admin/platform-goals', \App\Http\Controllers\Admin\PlatformGoalController::class)
        ->only(['index', 'show'])
        ->names('admin.platform_goals')
        ->middleware('permission:configuracoes.metas.acessar');
    Route::resource('admin/platform-goals', \App\Http\Controllers\Admin\PlatformGoalController::class)
        ->except(['index', 'show'])
        ->names('admin.platform_goals')
        ->middleware('permission:configuracoes.metas.gerenciar');

    // Modelo RCI
    Route::get('admin/modelo-rci', [\App\Http\Controllers\Admin\RciController::class, 'index'])
        ->name('admin.rci.index')
        ->middleware('permission:configuracoes.modelos_contrato.acessar');
    Route::post('admin/modelo-rci', [\App\Http\Controllers\Admin\RciController::class, 'store'])
        ->name('admin.rci.store')
        ->middleware('permission:configuracoes.modelos_contrato.acessar');
    Route::post('admin/modelo-rci/{template}/set-default', [\App\Http\Controllers\Admin\RciController::class, 'setAsDefault'])
        ->name('admin.rci.set_default')
        ->middleware('permission:configuracoes.modelos_contrato.acessar');
    Route::post('admin/modelo-rci/{template}/mapping', [\App\Http\Controllers\Admin\RciController::class, 'updateMapping'])
        ->name('admin.rci.update_mapping')
        ->middleware('permission:configuracoes.modelos_contrato.acessar');
    Route::get('admin/modelo-rci/{template}/file', [\App\Http\Controllers\Admin\RciController::class, 'getFile'])->name('admin.rci.file');
    Route::post('admin/modelo-rci/{template}/preview', [\App\Http\Controllers\Admin\RciController::class, 'preview'])->name('admin.rci.preview');
    Route::get('admin/modelo-rci/{template}/fields', [\App\Http\Controllers\Admin\RciController::class, 'getFields'])->name('admin.rci.fields');
    Route::delete('admin/modelo-rci/{template}', [\App\Http\Controllers\Admin\RciController::class, 'destroy'])
        ->name('admin.rci.destroy')
        ->middleware('permission:configuracoes.modelos_contrato.acessar');
    Route::post('admin/modelo-rci/gerar', [\App\Http\Controllers\Admin\RciController::class, 'generate'])
        ->name('admin.rci.generate')
        ->middleware('permission:configuracoes.modelos_contrato.acessar');

    // Propostas
    Route::post('/propostas', [\App\Http\Controllers\ProposalController::class, 'store'])->name('sales.propostas.store')->middleware('permission:atendimentos.editar');
    Route::put('/propostas/{proposal}', [\App\Http\Controllers\ProposalController::class, 'update'])->name('sales.propostas.update')->middleware('permission:atendimentos.editar');
    Route::post('/propostas/{proposal}/aprovar', [\App\Http\Controllers\ProposalController::class, 'approve'])->name('sales.propostas.approve')->middleware('permission:atendimentos.editar');
    Route::get('/api/products', [\App\Http\Controllers\ProposalController::class, 'getProducts'])->name('api.products'); // Public for those with access to forms
    Route::get('/api/payment-methods', [\App\Http\Controllers\PaymentMethodController::class, 'apiList'])->name('api.payment-methods');

    // Administração de Produtos
    Route::resource('admin/products', \App\Http\Controllers\AdminProductController::class)->only(['index', 'show'])->names([
        'index' => 'admin.products.index',
        'show' => 'admin.products.show'
    ])->middleware('permission:configuracoes.produtos.acessar');
    Route::resource('admin/products', \App\Http\Controllers\AdminProductController::class)->except(['index', 'show'])->names([
        'store' => 'admin.products.store',
        'update' => 'admin.products.update',
        'destroy' => 'admin.products.destroy',
        'create' => 'admin.products.create',
        'edit' => 'admin.products.edit'
    ])->middleware('permission:configuracoes.produtos.gerenciar');

    // Gestão de Manutenção
    Route::get('admin/manutencao', [AdminMaintenanceController::class, 'index'])->name('admin.maintenance.index')->middleware('permission:configuracoes.manutencao.acessar');
    Route::put('admin/manutencao/{product}', [AdminMaintenanceController::class, 'update'])->name('admin.maintenance.update')->middleware('permission:configuracoes.manutencao.gerenciar');
    Route::post('admin/manutencao/global', [AdminMaintenanceController::class, 'applyGlobal'])->name('admin.maintenance.global')->middleware('permission:configuracoes.manutencao.gerenciar');

    // Gestão de Formas de Pagamento
    Route::resource('admin/payment-methods', \App\Http\Controllers\PaymentMethodController::class)->only(['index', 'show'])->names([
        'index' => 'admin.payment_methods.index',
        'show' => 'admin.payment_methods.show'
    ])->middleware('permission:configuracoes.formas_pagamento.acessar');
    Route::resource('admin/payment-methods', \App\Http\Controllers\PaymentMethodController::class)->except(['index', 'show'])->names([
        'store' => 'admin.payment_methods.store',
        'update' => 'admin.payment_methods.update',
        'destroy' => 'admin.payment_methods.destroy',
        'create' => 'admin.payment_methods.create',
        'edit' => 'admin.payment_methods.edit'
    ])->middleware('permission:configuracoes.formas_pagamento.gerenciar');

    // Gestão de Qualificações
    Route::resource('admin/qualifications', \App\Http\Controllers\QualificationController::class)->only(['index', 'show'])->names('admin.qualifications')->middleware('permission:configuracoes.qualificacao.acessar');
    Route::resource('admin/qualifications', \App\Http\Controllers\QualificationController::class)->except(['index', 'show'])->names('admin.qualifications')->middleware('permission:configuracoes.qualificacao.gerenciar');

    // Gestão de Cortesias
    Route::post('admin/complimentary-items/upload-image', [\App\Http\Controllers\Admin\ComplimentaryItemController::class, 'uploadImage'])->name('admin.complimentary_items.upload_image')->middleware('permission:configuracoes.cortesias.gerenciar');
    Route::get('admin/complimentary-items/{complimentary_item}/pdf', [\App\Http\Controllers\Admin\ComplimentaryItemController::class, 'pdf'])->name('admin.complimentary_items.pdf')->middleware('permission:configuracoes.cortesias.acessar');
    Route::resource('admin/complimentary-items', \App\Http\Controllers\Admin\ComplimentaryItemController::class)->only(['index', 'show'])->names('admin.complimentary_items')->middleware('permission:configuracoes.cortesias.acessar');
    Route::resource('admin/complimentary-items', \App\Http\Controllers\Admin\ComplimentaryItemController::class)->except(['index', 'show'])->names('admin.complimentary_items')->middleware('permission:configuracoes.cortesias.gerenciar');

    // Gestão de Modelos de Proposta
    Route::get('admin/proposal-templates/{proposalTemplate}/download', [\App\Http\Controllers\Admin\ProposalTemplateController::class, 'download'])->name('admin.proposal_templates.download')->middleware('permission:configuracoes.modelos_proposta.acessar');
    Route::get('admin/proposal-templates/{proposalTemplate}/test-print', [\App\Http\Controllers\Admin\ProposalTemplateController::class, 'testPrint'])->name('admin.proposal_templates.test_print')->middleware('permission:configuracoes.modelos_proposta.acessar');
    Route::get('admin/proposal-templates/{proposalTemplate}/download-pdf', [\App\Http\Controllers\Admin\ProposalTemplateController::class, 'downloadPdf'])->name('admin.proposal_templates.download_pdf')->middleware('permission:configuracoes.modelos_proposta.acessar');
    Route::resource('admin/proposal-templates', \App\Http\Controllers\Admin\ProposalTemplateController::class)->only(['index', 'show'])->names('admin.proposal_templates')->middleware('permission:configuracoes.modelos_proposta.acessar');
    Route::resource('admin/proposal-templates', \App\Http\Controllers\Admin\ProposalTemplateController::class)->except(['index', 'show'])->names('admin.proposal_templates')->middleware('permission:configuracoes.modelos_proposta.gerenciar');

    // Gestão de Modelos de Contrato
    Route::post('admin/contract-templates/upload-image', [\App\Http\Controllers\Admin\ContractTemplateController::class, 'uploadImage'])->name('admin.contract_templates.upload_image')->middleware('permission:configuracoes.modelos_contrato.gerenciar');
    Route::get('admin/contract-templates/{contractTemplate}/download', [\App\Http\Controllers\Admin\ContractTemplateController::class, 'download'])->name('admin.contract_templates.download')->middleware('permission:configuracoes.modelos_contrato.acessar');
    Route::get('admin/contract-templates/{contractTemplate}/test-print', [\App\Http\Controllers\Admin\ContractTemplateController::class, 'testPrint'])->name('admin.contract_templates.test_print')->middleware('permission:configuracoes.modelos_contrato.acessar');
    Route::get('admin/contract-templates/{contractTemplate}/download-pdf', [\App\Http\Controllers\Admin\ContractTemplateController::class, 'downloadPdf'])->name('admin.contract_templates.download_pdf')->middleware('permission:configuracoes.modelos_contrato.acessar');
    Route::resource('admin/contract-templates', \App\Http\Controllers\Admin\ContractTemplateController::class)->only(['index', 'show'])->names('admin.contract_templates')->middleware('permission:configuracoes.modelos_contrato.acessar');
    Route::resource('admin/contract-templates', \App\Http\Controllers\Admin\ContractTemplateController::class)->except(['index', 'show'])->names('admin.contract_templates')->middleware('permission:configuracoes.modelos_contrato.gerenciar');


    // Gestão de Assuntos de Protocolo
    Route::get('admin/protocol-subjects', [\App\Http\Controllers\Admin\ProtocolSubjectController::class, 'index'])->name('admin.protocol_subjects.index')->middleware('permission:configuracoes.assuntos_protocolo.acessar');
    Route::post('admin/protocol-subjects', [\App\Http\Controllers\Admin\ProtocolSubjectController::class, 'store'])->name('admin.protocol_subjects.store')->middleware('permission:configuracoes.assuntos_protocolo.gerenciar');
    Route::put('admin/protocol-subjects/{protocolSubject}', [\App\Http\Controllers\Admin\ProtocolSubjectController::class, 'update'])->name('admin.protocol_subjects.update')->middleware('permission:configuracoes.assuntos_protocolo.gerenciar');
    Route::delete('admin/protocol-subjects/{protocolSubject}', [\App\Http\Controllers\Admin\ProtocolSubjectController::class, 'destroy'])->name('admin.protocol_subjects.destroy')->middleware('permission:configuracoes.assuntos_protocolo.gerenciar');

    // Módulo Financeiro
    Route::prefix('financeiro')->name('finance.')->group(function () {
        Route::get('/recebiveis', [\App\Http\Controllers\Finance\ReceivableController::class, 'index'])->name('receivables.index')->middleware('permission:recebiveis.acessar');
        Route::post('/recebiveis/bulk-pay', [\App\Http\Controllers\Finance\ReceivableController::class, 'bulkPayGlobal'])->name('receivables.bulk-pay')->middleware('permission:recebiveis.gerenciar');
        
        // Controle de Vendas (Auditoria)
        Route::get('/controle-vendas', [\App\Http\Controllers\Finance\SalesControlController::class, 'index'])->name('sales-control.index')->middleware('permission:controle_vendas.acessar');
        Route::patch('/controle-vendas/{proposal}/audit', [\App\Http\Controllers\Finance\SalesControlController::class, 'auditProposal'])->name('sales-control.audit')->middleware('permission:controle_vendas.gerenciar');
        Route::patch('/controle-vendas/{proposal}/conciliation', [\App\Http\Controllers\Finance\SalesControlController::class, 'saveConciliation'])->name('sales-control.conciliation')->middleware('permission:controle_vendas.gerenciar');
    });

    // Módulo Pós-venda
    Route::prefix('pos-venda')->name('after-sales.')->group(function () {
        Route::get('/boas-vindas', [\App\Http\Controllers\AfterSales\WelcomeController::class, 'index'])->name('welcome.index')->middleware('permission:pos_venda.boas_vindas.acessar');
        Route::patch('/boas-vindas/{salesService}/status', [\App\Http\Controllers\AfterSales\WelcomeController::class, 'updateStatus'])->name('welcome.status.update')->middleware('permission:pos_venda.boas_vindas.gerenciar');
        
        Route::get('/entrega-contrato', [\App\Http\Controllers\AfterSales\ContractDeliveryController::class, 'index'])->name('contract-delivery.index')->middleware('permission:pos_venda.gestao_contratos.acessar');
        Route::patch('/entrega-contrato/{salesService}/status', [\App\Http\Controllers\AfterSales\ContractDeliveryController::class, 'updateStatus'])->name('contract-delivery.status.update')->middleware('permission:pos_venda.gestao_contratos.gerenciar');
        Route::post('/entrega-contrato/{salesService}/upload', [\App\Http\Controllers\AfterSales\ContractDeliveryController::class, 'uploadSignedContract'])->name('contract-delivery.upload')->middleware('permission:pos_venda.gestao_contratos.gerenciar');
        Route::get('/entrega-contrato/{salesService}/download', [\App\Http\Controllers\AfterSales\ContractDeliveryController::class, 'downloadContract'])->name('contract-delivery.download')->middleware('permission:pos_venda.gestao_contratos.acessar');

        Route::get('/aniversariantes', [\App\Http\Controllers\AfterSales\BirthdayController::class, 'index'])->name('birthdays.index')->middleware('permission:pos_venda.aniversariantes.acessar');
        
        Route::get('/protocolos', [\App\Http\Controllers\AfterSales\ProtocolController::class, 'index'])->name('protocols.index')->middleware('permission:pos_venda.protocolos.acessar');
        Route::delete('/protocolos/{protocol}', [\App\Http\Controllers\AfterSales\ProtocolController::class, 'destroy'])->name('protocols.destroy')->middleware('permission:pos_venda.protocolos.excluir');

        Route::get('/reservas', [\App\Http\Controllers\AfterSales\ReservationRequestController::class, 'index'])->name('reservations.index')->middleware('permission:pos_venda.reservas.acessar');
        Route::post('/reservas', [\App\Http\Controllers\AfterSales\ReservationRequestController::class, 'store'])->name('reservations.store')->middleware('permission:pos_venda.reservas.gerenciar');
        Route::put('/reservas/{reservation}', [\App\Http\Controllers\AfterSales\ReservationRequestController::class, 'update'])->name('reservations.update')->middleware('permission:pos_venda.reservas.gerenciar');
        Route::delete('/reservas/{reservation}', [\App\Http\Controllers\AfterSales\ReservationRequestController::class, 'destroy'])->name('reservations.destroy')->middleware('permission:pos_venda.reservas.gerenciar');
    });

    // Busca Global
    Route::get('/api/search/global', [SalesServiceController::class, 'globalSearch'])->name('api.search.global');

    // Gestão de Parcelas (Bills)
    Route::post('/propostas/{proposal}/bills', [\App\Http\Controllers\BillController::class, 'store'])->name('bills.store')->middleware('permission:recebiveis.gerenciar');
    Route::post('/propostas/{proposal}/renegotiate-bills', [\App\Http\Controllers\BillController::class, 'renegotiate'])->name('bills.renegotiate')->middleware('permission:recebiveis.gerenciar');
    Route::post('/propostas/{proposal}/bulk-pay-bills', [\App\Http\Controllers\BillController::class, 'bulkPay'])->name('bills.bulk-pay')->middleware('permission:recebiveis.gerenciar');
    Route::put('/bills/{bill}', [\App\Http\Controllers\BillController::class, 'update'])->name('bills.update')->middleware('permission:recebiveis.gerenciar');
    Route::delete('/bills/{bill}', [\App\Http\Controllers\BillController::class, 'destroy'])->name('bills.destroy')->middleware('permission:recebiveis.gerenciar');
});

require __DIR__ . '/auth.php';

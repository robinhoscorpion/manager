<?php

use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SalesServiceController;
use App\Http\Controllers\ServiceLineController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\AdminMaintenanceController;
use App\Http\Controllers\AuditLogController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('employees', EmployeeController::class);
    Route::post('/employees/convert/{user}', [EmployeeController::class, 'convertFromUser'])->name('employees.convert');
    Route::resource('users', UserController::class);
    Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');

    // Logs do Sistema
    Route::get('/admin/audit-logs', [AuditLogController::class, 'index'])->name('admin.audit-logs.index');

    // Roles & Permissions
    Route::resource('roles', RoleController::class);
    Route::post('/roles/{role}/permissions', [RoleController::class, 'updatePermissions'])->name('roles.permissions.update');

    // Sala de Vendas
    Route::get('/atendimentos', [SalesServiceController::class, 'index'])->name('sales.atendimentos');
    Route::post('/atendimentos', [SalesServiceController::class, 'store'])->name('sales.atendimentos.store');
    Route::put('/atendimentos/{service}', [SalesServiceController::class, 'update'])->name('sales.atendimentos.update');
    Route::patch('/atendimentos/{service}/quick-update', [SalesServiceController::class, 'quickUpdate'])->name('sales.atendimentos.quick-update');
    Route::delete('/atendimentos/{service}', [SalesServiceController::class, 'destroy'])->name('sales.atendimentos.destroy');
    Route::get('/atendimentos/{service}', [SalesServiceController::class, 'show'])->name('sales.atendimentos.show');
    Route::get('/atendimentos/{service}/cortesia/pdf', [SalesServiceController::class, 'pdfCortesia'])->name('sales.atendimentos.cortesia.pdf');
    Route::get('/atendimentos/{service}/ficha/pdf', [SalesServiceController::class, 'pdfFicha'])->name('sales.atendimentos.ficha.pdf');
    Route::get('/atendimentos/{service}/proposta/pdf', [\App\Http\Controllers\SalesServiceController::class, 'pdfProposta'])->name('sales.atendimentos.proposta.pdf');
    Route::get('/atendimentos/{service}/contrato/pdf', [\App\Http\Controllers\SalesServiceController::class, 'pdfContrato'])->name('sales.atendimentos.contrato.pdf');
    Route::get('/atendimentos/{service}/rci/pdf', [\App\Http\Controllers\SalesServiceController::class, 'pdfRci'])->name('sales.atendimentos.rci.pdf');
    Route::get('/atendimentos/{service}/checklist/pdf', [\App\Http\Controllers\SalesServiceController::class, 'pdfChecklist'])->name('sales.atendimentos.checklist.pdf');
    Route::get('/linha-atendimento', [ServiceLineController::class, 'index'])->name('sales.linha');

    // Configurações de Dashboard
    Route::get('/configuracoes/colunas', [\App\Http\Controllers\Admin\ServiceSettingsController::class, 'index'])->name('admin.settings.columns.index');
    Route::put('/configuracoes/colunas', [\App\Http\Controllers\Admin\ServiceSettingsController::class, 'update'])->name('admin.settings.columns.update');

    // Propostas
    Route::post('/propostas', [\App\Http\Controllers\ProposalController::class, 'store'])->name('sales.propostas.store');
    Route::put('/propostas/{proposal}', [\App\Http\Controllers\ProposalController::class, 'update'])->name('sales.propostas.update');
    Route::post('/propostas/{proposal}/aprovar', [\App\Http\Controllers\ProposalController::class, 'approve'])->name('sales.propostas.approve');
    Route::get('/api/products', [\App\Http\Controllers\ProposalController::class, 'getProducts'])->name('api.products');
    Route::get('/api/payment-methods', [\App\Http\Controllers\PaymentMethodController::class, 'apiList'])->name('api.payment-methods');

    // Administração de Produtos
    Route::resource('admin/products', \App\Http\Controllers\AdminProductController::class)->names([
        'index' => 'admin.products.index',
        'store' => 'admin.products.store',
        'update' => 'admin.products.update',
        'destroy' => 'admin.products.destroy',
    ]);

    // Gestão de Manutenção
    Route::get('admin/manutencao', [AdminMaintenanceController::class, 'index'])->name('admin.maintenance.index');
    Route::put('admin/manutencao/{product}', [AdminMaintenanceController::class, 'update'])->name('admin.maintenance.update');
    Route::post('admin/manutencao/global', [AdminMaintenanceController::class, 'applyGlobal'])->name('admin.maintenance.global');

    // Gestão de Formas de Pagamento
    Route::resource('admin/payment-methods', \App\Http\Controllers\PaymentMethodController::class)->names([
        'index' => 'admin.payment_methods.index',
        'store' => 'admin.payment_methods.store',
        'update' => 'admin.payment_methods.update',
        'destroy' => 'admin.payment_methods.destroy',
    ]);

    // Gestão de Qualificações
    Route::resource('admin/qualifications', \App\Http\Controllers\QualificationController::class)->names('admin.qualifications');

    // Gestão de Cortesias
    Route::post('admin/complimentary-items/upload-image', [\App\Http\Controllers\Admin\ComplimentaryItemController::class, 'uploadImage'])->name('admin.complimentary_items.upload_image');
    Route::get('admin/complimentary-items/{complimentary_item}/pdf', [\App\Http\Controllers\Admin\ComplimentaryItemController::class, 'pdf'])->name('admin.complimentary_items.pdf');
    Route::resource('admin/complimentary-items', \App\Http\Controllers\Admin\ComplimentaryItemController::class)->names('admin.complimentary_items');

    // Gestão de Modelos de Proposta
    Route::post('admin/proposal-templates/upload-image', [\App\Http\Controllers\Admin\ProposalTemplateController::class, 'uploadImage'])->name('admin.proposal_templates.upload_image');
    Route::resource('admin/proposal-templates', \App\Http\Controllers\Admin\ProposalTemplateController::class)->names('admin.proposal_templates');

    // Gestão de Modelos de Contrato
    Route::post('admin/contract-templates/upload-image', [\App\Http\Controllers\Admin\ContractTemplateController::class, 'uploadImage'])->name('admin.contract_templates.upload_image');
    Route::resource('admin/contract-templates', \App\Http\Controllers\Admin\ContractTemplateController::class)->names('admin.contract_templates');

    // Busca Global
    Route::get('/api/search/global', [SalesServiceController::class, 'globalSearch'])->name('api.search.global');

    // Gestão de Parcelas (Bills)
    Route::post('/propostas/{proposal}/bills', [\App\Http\Controllers\BillController::class, 'store'])->name('bills.store');
    Route::post('/propostas/{proposal}/renegotiate-bills', [\App\Http\Controllers\BillController::class, 'renegotiate'])->name('bills.renegotiate');
    Route::post('/propostas/{proposal}/bulk-pay-bills', [\App\Http\Controllers\BillController::class, 'bulkPay'])->name('bills.bulk-pay');
    Route::put('/bills/{bill}', [\App\Http\Controllers\BillController::class, 'update'])->name('bills.update');
    Route::delete('/bills/{bill}', [\App\Http\Controllers\BillController::class, 'destroy'])->name('bills.destroy');
});

require __DIR__ . '/auth.php';

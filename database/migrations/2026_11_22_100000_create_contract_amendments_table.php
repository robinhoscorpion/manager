<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aditivos de contrato (Upgrade / Downgrade).
 *
 * O contrato (proposals) mantém o MESMO número. Cada aditivo gera uma nova
 * "versão" da negociação e guarda um espelho (snapshot) completo da versão
 * anterior: produto, valores, pagamentos negociados e parcelas financeiras.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_amendments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('type'); // upgrade | downgrade
            $table->unsignedInteger('from_version');
            $table->unsignedInteger('to_version');

            $table->foreignId('from_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('to_product_id')->nullable()->constrained('products')->nullOnDelete();

            $table->decimal('old_total_value', 15, 2)->default(0);
            $table->decimal('new_total_value', 15, 2)->default(0);
            $table->integer('old_quantity')->nullable();
            $table->integer('new_quantity')->nullable();

            // Valor pago (entrada + saldo) no contrato anterior que entra como pagamento no novo
            $table->decimal('paid_amount', 15, 2)->default(0);
            // Parte do valor pago efetivamente aplicada no novo valor
            $table->decimal('credit_applied', 15, 2)->default(0);
            // Sobra do valor pago (downgrade) - fica como saldo credor do cliente, sem devolução
            $table->decimal('credit_balance', 15, 2)->default(0);
            // Novo valor - valor antigo (positivo no upgrade, negativo no downgrade)
            $table->decimal('difference_amount', 15, 2)->default(0);
            // Valor restante a negociar no novo contrato (novo valor - crédito aplicado)
            $table->decimal('remaining_amount', 15, 2)->default(0);

            $table->boolean('charge_contract_fee')->default(false);
            $table->decimal('contract_fee_amount', 15, 2)->default(0);

            // Quem arca com o estorno de comissão já paga (downgrade): employee | client | undefined
            $table->string('commission_reversal_responsible')->nullable();

            $table->json('snapshot'); // espelho do contrato anterior
            $table->string('reason');
            $table->text('details')->nullable();
            $table->string('status')->default('completed');
            $table->timestamps();

            $table->index(['proposal_id', 'to_version']);
        });

        Schema::table('proposals', function (Blueprint $table) {
            $table->unsignedInteger('current_version')->default(1)->after('contract_number');
            $table->decimal('credit_balance', 15, 2)->default(0)->after('received_balance');
        });

        Schema::table('proposal_payments', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1)->after('proposal_id');
            $table->boolean('is_current')->default(true)->after('version');
            $table->foreignId('contract_amendment_id')->nullable()->after('is_current')
                ->constrained('contract_amendments')->nullOnDelete();
            $table->index(['proposal_id', 'is_current']);
        });

        Schema::table('bills', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1)->after('proposal_id');
            // Parcela gerada por um aditivo
            $table->foreignId('contract_amendment_id')->nullable()->after('version')
                ->constrained('contract_amendments')->nullOnDelete();
            // Parcela da versão anterior que foi substituída por um aditivo
            $table->foreignId('replaced_by_amendment_id')->nullable()->after('contract_amendment_id')
                ->constrained('contract_amendments')->nullOnDelete();
        });

        Schema::table('commissions', function (Blueprint $table) {
            $table->foreignId('contract_amendment_id')->nullable()->after('proposal_id')
                ->constrained('contract_amendments')->nullOnDelete();
        });

        Schema::create('commission_reversals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_amendment_id')->constrained('contract_amendments')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // funcionário da comissão
            $table->decimal('reversal_amount', 12, 2)->default(0);       // total a estornar
            $table->decimal('pending_reduced_amount', 12, 2)->default(0); // abatido de parcelas ainda não pagas
            $table->decimal('to_recover_amount', 12, 2)->default(0);      // já pago - precisa ser recuperado
            $table->string('responsible')->default('undefined');          // employee | client | undefined
            $table->string('status')->default('pending');                 // pending | resolved | waived
            $table->text('notes')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_reversals');

        Schema::table('commissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('contract_amendment_id');
        });

        Schema::table('bills', function (Blueprint $table) {
            $table->dropConstrainedForeignId('replaced_by_amendment_id');
            $table->dropConstrainedForeignId('contract_amendment_id');
            $table->dropColumn('version');
        });

        Schema::table('proposal_payments', function (Blueprint $table) {
            $table->dropIndex(['proposal_id', 'is_current']);
            $table->dropConstrainedForeignId('contract_amendment_id');
            $table->dropColumn(['version', 'is_current']);
        });

        Schema::table('proposals', function (Blueprint $table) {
            $table->dropColumn(['current_version', 'credit_balance']);
        });

        Schema::dropIfExists('contract_amendments');
    }
};

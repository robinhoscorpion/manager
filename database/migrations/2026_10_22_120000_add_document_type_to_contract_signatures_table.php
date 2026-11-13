<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cada envio de assinatura passa a ser de um documento: contrato, proposta ou rci.
     * Envios já existentes são do contrato.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('contract_signatures', 'document_type')) {
            Schema::table('contract_signatures', function (Blueprint $table) {
                $table->string('document_type', 20)->default('contrato')->after('sales_service_id')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('contract_signatures', 'document_type')) {
            Schema::table('contract_signatures', function (Blueprint $table) {
                $table->dropIndex(['document_type']);
                $table->dropColumn('document_type');
            });
        }
    }
};

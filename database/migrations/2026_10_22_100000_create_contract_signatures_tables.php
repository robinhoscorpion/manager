<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('contract_signatures')) {
            Schema::create('contract_signatures', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sales_service_id')->constrained('sales_services')->cascadeOnDelete();
                $table->string('provider', 50);
                $table->string('external_id')->nullable()->index();
                // draft, sent, viewed, partially_signed, signed, refused, cancelled, expired, error
                $table->string('status', 30)->default('draft')->index();
                $table->string('document_name');
                $table->string('delivery_method', 20)->nullable();
                $table->boolean('sandbox')->default(false);
                $table->string('original_file_path')->nullable();
                $table->string('signed_file_path')->nullable();
                $table->text('error_message')->nullable();
                $table->json('log')->nullable();
                $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('signed_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamp('last_synced_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('contract_signature_signers')) {
            Schema::create('contract_signature_signers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('contract_signature_id')->constrained('contract_signatures')->cascadeOnDelete();
                // titular, segundo_titular, empresa, testemunha
                $table->string('role', 30);
                $table->string('name');
                $table->string('email')->nullable();
                $table->string('phone', 30)->nullable();
                $table->string('cpf', 20)->nullable();
                $table->string('external_id')->nullable()->index();
                // pending, viewed, signed, refused
                $table->string('status', 20)->default('pending');
                $table->text('sign_url')->nullable();
                $table->timestamp('viewed_at')->nullable();
                $table->timestamp('signed_at')->nullable();
                $table->timestamp('refused_at')->nullable();
                $table->unsignedSmallInteger('order')->default(0);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_signature_signers');
        Schema::dropIfExists('contract_signatures');
    }
};

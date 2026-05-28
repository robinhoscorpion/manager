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
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('product_type_id')->after('id')->nullable()->constrained()->onDelete('set null');
            $table->string('duration')->after('name')->nullable(); // Ex: "10 anos"
            $table->decimal('min_price', 15, 2)->after('price')->nullable(); // Valor ajustável
            $table->string('contract_prefix')->after('description')->nullable(); // Ex: "35"
            $table->string('contract_format')->after('contract_prefix')->default('seq_only'); // prefix_sep_seq, prefix_seq, seq_only
            $table->integer('current_sequence')->after('contract_format')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            //
        });
    }
};

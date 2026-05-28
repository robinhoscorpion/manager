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
        Schema::create('maintenance_adjustments', function (Blueprint $table) {
            $table->id();
            $table->integer('target_month');
            $table->integer('target_year');
            $table->decimal('igpm_rate', 8, 4); // e.g. 5.5000 for 5.5%
            $table->enum('base_type', ['current', 'previous'])->default('current');
            $table->json('exempt_proposal_ids')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_adjustments');
    }
};

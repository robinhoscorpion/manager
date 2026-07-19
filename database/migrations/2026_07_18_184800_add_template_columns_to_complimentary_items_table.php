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
        Schema::table('complimentary_items', function (Blueprint $table) {
            $table->string('template_type')->default('html')->after('description');
            $table->string('file_path')->nullable()->after('template_type');
            $table->json('metadata')->nullable()->after('content');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('complimentary_items', function (Blueprint $table) {
            $table->dropColumn(['template_type', 'file_path', 'metadata']);
        });
    }
};

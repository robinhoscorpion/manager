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
        Schema::table('rci_templates', function (Blueprint $table) {
            $table->json('mapping_config')->nullable()->after('is_default');
            $table->string('preview_image_path')->nullable()->after('mapping_config');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rci_templates', function (Blueprint $table) {
            $table->dropColumn(['mapping_config', 'preview_image_path']);
        });
    }
};

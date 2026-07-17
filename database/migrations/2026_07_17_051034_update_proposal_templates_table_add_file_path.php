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
        Schema::table('proposal_templates', function (Blueprint $table) {
            $table->dropColumn('content');
            $table->string('file_path')->nullable()->after('name');
            $table->string('original_filename')->nullable()->after('file_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proposal_templates', function (Blueprint $table) {
            $table->longText('content')->nullable();
            $table->dropColumn('file_path');
            $table->dropColumn('original_filename');
        });
    }
};

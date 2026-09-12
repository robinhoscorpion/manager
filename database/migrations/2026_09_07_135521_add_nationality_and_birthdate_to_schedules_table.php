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
        Schema::table('schedules', function (Blueprint $table) {
            $table->string('nacionalidade')->default('Brasileira')->after('name');
            $table->date('data_nascimento')->nullable()->after('nacionalidade');
            $table->string('spouse_nacionalidade')->default('Brasileira')->after('spouse_name');
            $table->date('spouse_data_nascimento')->nullable()->after('spouse_nacionalidade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn([
                'nacionalidade',
                'data_nascimento',
                'spouse_nacionalidade',
                'spouse_data_nascimento'
            ]);
        });
    }
};

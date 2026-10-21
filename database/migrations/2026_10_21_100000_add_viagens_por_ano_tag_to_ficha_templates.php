<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Preenche a linha em branco da pergunta "quantas vezes por ano ... viajar"
     * dos modelos de ficha com a variável {{viagens_por_ano}}.
     */
    public function up(): void
    {
        $pattern = '/(quantas vezes por ano.*?viaj.*?<br>\s*<span class="blank-line[^"]*"[^>]*>)\s*(<\/span>)/isu';

        DB::table('ficha_templates')->get(['id', 'content'])->each(function ($template) use ($pattern) {
            if (!$template->content || str_contains($template->content, '{{viagens_por_ano}}')) {
                return;
            }

            $content = preg_replace($pattern, '$1{{viagens_por_ano}}$2', $template->content, 1, $count);

            if ($count > 0) {
                DB::table('ficha_templates')->where('id', $template->id)->update(['content' => $content, 'updated_at' => now()]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('ficha_templates')->get(['id', 'content'])->each(function ($template) {
            if ($template->content && str_contains($template->content, '{{viagens_por_ano}}')) {
                DB::table('ficha_templates')->where('id', $template->id)
                    ->update(['content' => str_replace('{{viagens_por_ano}}', '', $template->content)]);
            }
        });
    }
};

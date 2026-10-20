<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('professions')) {
            Schema::create('professions', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->boolean('active')->default(true);
                // false = digitada no atendimento/agendamento e ainda não revisada em Configurações
                $table->boolean('reviewed')->default(true);
                $table->timestamps();
            });
        }

        $now = now();

        // Lista que antes era fixa no sistema (resources/js/Constants/Professions.js)
        $defaults = [
            'Administrador(a)',
            'Advogado(a)',
            'Agricultor(a)',
            'Analista de Sistemas',
            'Aposentado(a)',
            'Arquiteto(a)',
            'Artesão(ã)',
            'Assistente Administrativo',
            'Assistente Social',
            'Atendente',
            'Autônomo(a)',
            'Auxiliar de Cozinha',
            'Auxiliar de Limpeza',
            'Auxiliar Geral',
            'Bancário(a)',
            'Biólogo(a)',
            'Bombeiro(a)',
            'Cabeleireiro(a)',
            'Caixa',
            'Caminhoneiro(a)',
            'Carpinteiro(a)',
            'Chef de Cozinha',
            'Cientista',
            'Comerciante',
            'Consultor(a)',
            'Contador(a)',
            'Corretor(a) de Imóveis',
            'Corretor(a) de Seguros',
            'Cozinheiro(a)',
            'Dentista',
            'Designer Gráfico',
            'Digitador(a)',
            'Diretor(a)',
            'Dona de Casa',
            'Economista',
            'Educador(a) Físico',
            'Eletricista',
            'Empreendedor(a)',
            'Enfermeiro(a)',
            'Engenheiro(a) Civil',
            'Engenheiro(a) Eletricista',
            'Engenheiro(a) Mecânico',
            'Escritor(a)',
            'Estagiário(a)',
            'Esteticista',
            'Estudante',
            'Farmacêutico(a)',
            'Faxineiro(a)',
            'Fiscal',
            'Fisioterapeuta',
            'Fotógrafo(a)',
            'Funcionário(a) Público',
            'Garçom/Garçonete',
            'Gerente',
            'Guia de Turismo',
            'Instalador(a)',
            'Jornalista',
            'Juiz(a)',
            'Manicure/Pedicure',
            'Maquiador(a)',
            'Marceneiro(a)',
            'Mecânico(a)',
            'Médico(a)',
            'Militar',
            'Motorista de Aplicativo',
            'Motorista Particular',
            'Músico(a)',
            'Nutricionista',
            'Odontologista',
            'Operador(a) de Telemarketing',
            'Operário(a)',
            'Padeiro(a)',
            'Pedreiro(a)',
            'Piloto(a)',
            'Pintor(a)',
            'Policial',
            'Porteiro(a)',
            'Professor(a)',
            'Programador(a)',
            'Psicólogo(a)',
            'Publicitário(a)',
            'Recepcionista',
            'Representante Comercial',
            'Secretário(a)',
            'Segurança',
            'Servidor(a) Público',
            'Soldador(a)',
            'Técnico(a) em Edificações',
            'Técnico(a) em Enfermagem',
            'Técnico(a) em Informática',
            'Terapeuta Ocupacional',
            'Vendedor(a)',
            'Veterinário(a)',
            'Vigilante',
            'Web Designer',
            'Zelador(a)',
        ];
        foreach ($defaults as $name) {
            DB::table('professions')->insertOrIgnore(['name' => $name, 'active' => true, 'reviewed' => true, 'created_at' => $now, 'updated_at' => $now]);
        }

        // Profissões já gravadas que não estão na lista entram como "a revisar"
        $existing = collect()
            ->merge(DB::table('clients')->whereNotNull('profissao')->distinct()->pluck('profissao'))
            ->merge(DB::table('sales_services')->whereNotNull('profissao_conjuge')->distinct()->pluck('profissao_conjuge'))
            ->merge(DB::table('schedules')->whereNotNull('profissao')->distinct()->pluck('profissao'))
            ->merge(DB::table('schedules')->whereNotNull('spouse_profissao')->distinct()->pluck('spouse_profissao'))
            ->map(fn($name) => trim(preg_replace('/\s+/u', ' ', (string) $name)))
            ->filter()
            ->unique(fn($name) => mb_strtolower($name));

        foreach ($existing as $name) {
            // A comparação do MySQL ignora maiúsculas/acentos, então "advogado(a)" não duplica "Advogado(a)"
            if (!DB::table('professions')->where('name', $name)->exists()) {
                DB::table('professions')->insertOrIgnore(['name' => $name, 'active' => true, 'reviewed' => false, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('professions');
    }
};

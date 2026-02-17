<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('apr_solicitacoes', function (Blueprint $table) {
            $table->string('contratante_razao_social')->nullable()->after('responsavel_id');
            $table->string('contratante_cnpj', 20)->nullable()->after('contratante_razao_social');
            $table->string('contratante_responsavel_nome')->nullable()->after('contratante_cnpj');
            $table->string('contratante_telefone', 25)->nullable()->after('contratante_responsavel_nome');
            $table->string('contratante_email')->nullable()->after('contratante_telefone');

            $table->string('obra_nome')->nullable()->after('contratante_email');
            $table->string('obra_endereco')->nullable()->after('obra_nome');
            $table->string('obra_cidade')->nullable()->after('obra_endereco');
            $table->string('obra_uf', 2)->nullable()->after('obra_cidade');
            $table->string('obra_cep', 10)->nullable()->after('obra_uf');
            $table->string('obra_area_setor')->nullable()->after('obra_cep');

            $table->text('atividade_descricao')->nullable()->after('obra_area_setor');
            $table->date('atividade_data_inicio')->nullable()->after('atividade_descricao');
            $table->date('atividade_data_termino_prevista')->nullable()->after('atividade_data_inicio');

            $table->json('etapas_json')->nullable()->after('atividade_data_termino_prevista');
            $table->json('equipe_json')->nullable()->after('etapas_json');
            $table->json('epis_json')->nullable()->after('equipe_json');

            $table->string('status', 20)->default('rascunho')->after('epis_json');
            $table->timestamp('aprovada_em')->nullable()->after('status');
            $table->unsignedBigInteger('aprovada_por')->nullable()->after('aprovada_em');
            $table->foreign('aprovada_por')->references('id')->on('users');
        });

        DB::table('apr_solicitacoes')->update([
            'status' => 'aprovada',
        ]);
    }

    public function down(): void
    {
        Schema::table('apr_solicitacoes', function (Blueprint $table) {
            $table->dropForeign(['aprovada_por']);
            $table->dropColumn([
                'contratante_razao_social',
                'contratante_cnpj',
                'contratante_responsavel_nome',
                'contratante_telefone',
                'contratante_email',
                'obra_nome',
                'obra_endereco',
                'obra_cidade',
                'obra_uf',
                'obra_cep',
                'obra_area_setor',
                'atividade_descricao',
                'atividade_data_inicio',
                'atividade_data_termino_prevista',
                'etapas_json',
                'equipe_json',
                'epis_json',
                'status',
                'aprovada_em',
                'aprovada_por',
            ]);
        });
    }
};


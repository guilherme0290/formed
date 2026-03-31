<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('anexos', function (Blueprint $table) {
            $table->string('public_token', 16)->nullable()->unique()->after('path');
        });

        Schema::table('pcmso_solicitacoes', function (Blueprint $table) {
            $table->string('pgr_public_token', 16)->nullable()->unique()->after('pgr_arquivo_path');
        });

        DB::table('anexos')
            ->whereNull('public_token')
            ->orderBy('id')
            ->select(['id'])
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('anexos')
                        ->where('id', $row->id)
                        ->update(['public_token' => $this->gerarTokenCurtoUnico('anexos', 'public_token')]);
                }
            });

        DB::table('pcmso_solicitacoes')
            ->whereNotNull('pgr_arquivo_path')
            ->whereNull('pgr_public_token')
            ->orderBy('id')
            ->select(['id'])
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('pcmso_solicitacoes')
                        ->where('id', $row->id)
                        ->update(['pgr_public_token' => $this->gerarTokenCurtoUnico('pcmso_solicitacoes', 'pgr_public_token')]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('pcmso_solicitacoes', function (Blueprint $table) {
            $table->dropUnique(['pgr_public_token']);
            $table->dropColumn('pgr_public_token');
        });

        Schema::table('anexos', function (Blueprint $table) {
            $table->dropUnique(['public_token']);
            $table->dropColumn('public_token');
        });
    }

    private function gerarTokenCurtoUnico(string $table, string $column): string
    {
        do {
            $token = Str::random(8);
        } while (DB::table($table)->where($column, $token)->exists());

        return $token;
    }
};

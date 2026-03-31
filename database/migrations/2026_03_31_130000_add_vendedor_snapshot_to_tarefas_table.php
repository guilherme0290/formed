<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tarefas', function (Blueprint $table) {
            $table->foreignId('vendedor_snapshot_id')
                ->nullable()
                ->after('cliente_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->string('vendedor_snapshot_nome')
                ->nullable()
                ->after('vendedor_snapshot_id');
        });

        DB::table('tarefas')
            ->join('clientes', 'clientes.id', '=', 'tarefas.cliente_id')
            ->leftJoin('users', 'users.id', '=', 'clientes.vendedor_id')
            ->whereNull('tarefas.vendedor_snapshot_id')
            ->update([
                'tarefas.vendedor_snapshot_id' => DB::raw('clientes.vendedor_id'),
                'tarefas.vendedor_snapshot_nome' => DB::raw('users.name'),
            ]);
    }

    public function down(): void
    {
        Schema::table('tarefas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vendedor_snapshot_id');
            $table->dropColumn('vendedor_snapshot_nome');
        });
    }
};

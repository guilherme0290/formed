<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('empresas', function (Blueprint $t) {
            $t->foreignId('cidade_id')->nullable()->after('endereco')->constrained('cidades')->nullOnDelete();
            $t->string('bairro')->nullable()->after('cidade_id');
            $t->string('numero', 20)->nullable()->after('bairro');
            $t->string('cep', 10)->nullable()->after('numero');
        });
        Schema::table('clientes', function (Blueprint $t) {
            $t->foreignId('cidade_id')->nullable()->after('endereco')->constrained('cidades')->nullOnDelete();
            $t->string('bairro')->nullable()->after('cidade_id');
            $t->string('numero', 20)->nullable()->after('bairro');
            $t->string('cep', 10)->nullable()->after('numero');
        });
    }
    public function down(): void {
        Schema::table('empresas', function (Blueprint $t) {
            $t->dropConstrainedForeignId('cidade_id');
            $t->dropColumn(['bairro','numero','cep']);
        });
        Schema::table('clientes', function (Blueprint $t) {
            $t->dropConstrainedForeignId('cidade_id');
            $t->dropColumn(['bairro','numero','cep']);
        });
    }
};

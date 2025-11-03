<?php // database/migrations/2025_01_01_000005_create_estados_cidades.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('estados', function (Blueprint $t) {
            $t->id();
            $t->string('nome', 100);
            $t->string('uf', 2)->unique();
            $t->timestamps();
        });

        Schema::create('cidades', function (Blueprint $t) {
            $t->id();
            $t->foreignId('estado_id')->constrained('estados')->cascadeOnDelete();
            $t->string('nome', 150);
            $t->timestamps();
            $t->unique(['estado_id','nome']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('cidades');
        Schema::dropIfExists('estados');
    }
};

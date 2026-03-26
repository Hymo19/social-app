<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // ✅ Ajoute mood aux posts seulement si la colonne n'existe pas
        if (!Schema::hasColumn('posts', 'mood')) {
            Schema::table('posts', function (Blueprint $table) {
                $table->string('mood')->nullable()->after('visibility');
            });
        }

        // ✅ Crée post_reactions seulement si elle n'existe pas déjà
        if (!Schema::hasTable('post_reactions')) {
            Schema::create('post_reactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('post_id')->constrained()->cascadeOnDelete();
                $table->string('reaction');
                $table->timestamps();
                $table->unique(['user_id', 'post_id']);
            });
        }
    }

    public function down(): void {
        Schema::table('posts', fn($t) => $t->dropColumnIfExists('mood'));
        Schema::dropIfExists('post_reactions');
    }
};
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('photo_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('profile_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('photo_type'); // profile, cover
            $table->string('reaction');
            $table->timestamps();
            $table->unique(['user_id', 'profile_user_id', 'photo_type']);
        });
    }
    public function down(): void { Schema::dropIfExists('photo_reactions'); }
};
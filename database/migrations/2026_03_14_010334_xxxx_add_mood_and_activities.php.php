<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->string('mood')->nullable()->after('story_privacy');
        });
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // profile_photo, cover_photo, mood
            $table->json('data')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::table('users', fn($t) => $t->dropColumn('mood'));
        Schema::dropIfExists('activities');
    }
};
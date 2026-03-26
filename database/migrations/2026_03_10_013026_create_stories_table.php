<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['image', 'video', 'text']);
            $table->string('media')->nullable();        // image ou video
            $table->string('music')->nullable();        // audio uploadé
            $table->string('music_name')->nullable();   // nom du morceau
            $table->text('text_content')->nullable();   // texte si type=text
            $table->string('bg_color')->default('#1877f2'); // fond coloré
            $table->string('text_color')->default('#ffffff');
            $table->timestamp('expires_at');            // +24h
            $table->timestamps();
        });

        Schema::create('story_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('story_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unique(['story_id', 'user_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('story_views');
        Schema::dropIfExists('stories');
    }
};
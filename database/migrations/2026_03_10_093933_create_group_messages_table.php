<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('group_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('content')->nullable();
            $table->string('image')->nullable();
            $table->enum('type', ['text', 'image', 'call_started', 'call_ended'])->default('text');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('group_messages'); }
};
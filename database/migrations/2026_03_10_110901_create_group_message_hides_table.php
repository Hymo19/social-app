<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('group_message_hides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_message_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unique(['group_message_id', 'user_id']);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('group_message_hides'); }
};
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('shares', function (Blueprint $table) {
            $table->string('destination')->default('friend')->after('status');
            // friend | self_feed | self_profile | group
        });
    }
    public function down(): void {
        Schema::table('shares', function (Blueprint $table) {
            $table->dropColumn('destination');
        });
    }
};
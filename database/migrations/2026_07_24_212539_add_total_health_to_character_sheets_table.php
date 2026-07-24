<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('character_sheets', function (Blueprint $table) {
            $table->integer('total_health')->default(100)->after('health');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('character_sheets', function (Blueprint $table) {
            $table->dropColumn('total_health');
        });
    }
};

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
        Schema::create('character_sheet_character_status', function (Blueprint $table) {
            $table->foreignId('character_sheet_id')->constrained()->onDelete('cascade');
            $table->foreignId('character_status_id')->constrained()->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('character_sheet_character_status');
    }
};

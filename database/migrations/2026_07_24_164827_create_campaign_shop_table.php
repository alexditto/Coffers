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
        Schema::create('campaign_shop', function (Blueprint $table) {
            $table->unsignedBigInteger('campaign_id');
            $table->unsignedBigInteger('shop_id');
            $table->foreign('campaign_id')->references('id')->on('campaigns')
                ->onDelete('cascade');
            $table->foreign('shop_id')->references('id')->on('shops')
                ->onDelete('cascade');
            $table->primary(['campaign_id', 'shop_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_shop');
    }
};

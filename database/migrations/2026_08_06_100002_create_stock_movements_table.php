<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->string('product_id')->index();
            $table->string('sku')->nullable();
            $table->integer('quantity'); // signed delta: negative reduces, positive restocks
            $table->string('reason')->nullable();
            $table->string('reference')->nullable()->index();
            $table->text('note')->nullable();
            $table->string('user_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};

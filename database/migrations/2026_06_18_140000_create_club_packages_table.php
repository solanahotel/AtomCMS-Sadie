<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('club_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('months')->default(0);
            $table->unsignedInteger('duration_days');
            $table->decimal('price_usd', 10, 2);
            $table->unsignedInteger('order_num')->default(0);
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('club_packages');
    }
};

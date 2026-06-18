<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_challenges', function (Blueprint $table) {
            $table->id();
            $table->string('wallet_address', 44)->index();
            $table->string('nonce', 128)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_challenges');
    }
};
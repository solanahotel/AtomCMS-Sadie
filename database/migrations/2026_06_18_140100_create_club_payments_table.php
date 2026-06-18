<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('club_payments', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('player_id');                 // players.id (signed, matches emulator)
            $table->string('wallet_address', 44);            // payer wallet locked at intent time
            $table->unsignedBigInteger('package_id');
            $table->string('reference', 64)->unique();       // one-time nonce, must appear in the tx memo
            $table->unsignedBigInteger('expected_lamports'); // SOL amount locked at intent time
            $table->decimal('price_usd', 10, 2);
            $table->decimal('sol_usd_rate', 18, 8)->nullable();
            $table->string('signature', 128)->nullable()->unique(); // tx signature, redeemable once
            $table->string('status', 16)->default('pending'); // pending | verified | failed
            $table->string('treasury_wallet', 44);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['player_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('club_payments');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The Sadie emulator owns the `players` table and does not include Laravel's
     * auth `remember_token` column. Wallet registration/login calls
     * Auth::login($user, remember: true), which writes remember_token and throws
     * a "Unknown column 'remember_token'" error after the account is already
     * created — the "error, then it works on refresh" symptom. Add the column.
     */
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            if (! Schema::hasColumn('players', 'remember_token')) {
                $table->rememberToken(); // remember_token VARCHAR(100) NULL
            }
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            if (Schema::hasColumn('players', 'remember_token')) {
                $table->dropColumn('remember_token');
            }
        });
    }
};

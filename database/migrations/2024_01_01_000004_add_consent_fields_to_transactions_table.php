<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('nonce', 30)->nullable()->after('dcb_response');
            $table->text('consent_url')->nullable()->after('nonce');
            $table->text('consent_payload')->nullable()->after('consent_url');
            $table->timestamp('consent_initiated_at')->nullable()->after('consent_payload');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['nonce', 'consent_url', 'consent_payload', 'consent_initiated_at']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consent_logs', function (Blueprint $table) {
            $table->id();
            $table->string('txn_ref', 40)->index();
            $table->string('msisdn', 15)->index();
            $table->string('step', 40); // consent_generated, redirected, callback_received, ticket_assigned, sms_sent, sms_failed, failed
            $table->json('data')->nullable();    // outbound payload or inbound params
            $table->string('note', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consent_logs');
    }
};

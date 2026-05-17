<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->string('msisdn', 15)->index();
            $table->text('message');
            $table->string('txn_ref', 40)->nullable()->index();
            $table->string('url', 512)->nullable();
            $table->text('request_body')->nullable();
            $table->text('response')->nullable();
            $table->string('status_message', 100)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};

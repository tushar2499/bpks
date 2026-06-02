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
        Schema::create('blink_notify_logs', function (Blueprint $table) {
            $table->id();
            $table->string('blink_txn_id', 50)->index();
            $table->string('txn_ref', 40)->nullable()->index();
            $table->string('status', 20)->nullable();
            $table->decimal('charge_amount', 8, 2)->nullable();
            $table->text('payload');
            $table->string('matched', 10)->default('no'); // yes / no / duplicate
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blink_notify_logs');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('txn_ref', 40)->unique()->index();   // our reference
            $table->foreignId('ticket_id')->nullable()->constrained('tickets')->nullOnDelete();
            $table->string('phone', 15)->index();
            $table->string('operator', 20);
            $table->decimal('amount', 8, 2)->default(20);
            $table->enum('status', ['pending', 'success', 'failed', 'cancelled'])->default('pending')->index();
            $table->string('dcb_txn_id', 100)->nullable();      // operator's transaction ID
            $table->text('dcb_response')->nullable();           // raw operator response
            $table->string('failure_reason', 255)->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};

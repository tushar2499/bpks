<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recharge_imports', function (Blueprint $table) {
            $table->id();
            $table->string('source_file')->nullable();
            $table->timestamp('trx_time')->nullable();
            $table->string('msisdn', 20);
            $table->string('invoice_no', 100);
            $table->string('dob_msisdn', 20)->nullable();
            $table->decimal('dob_amount', 10, 2)->default(0);
            $table->string('sof_status', 20)->nullable();
            $table->string('ers_status', 20)->nullable();
            $table->string('dob_status', 20)->nullable();
            $table->text('remarks')->nullable();
            $table->integer('ticket_count')->default(1);
            $table->tinyInteger('ticket_status')->default(0); // 0=not generated, 1=generated
            $table->string('txn_ref', 50)->nullable();
            $table->timestamps();

            $table->unique(['msisdn', 'invoice_no']);
            $table->index('ticket_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recharge_imports');
    }
};

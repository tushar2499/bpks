<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_no', 20)->unique();
            $table->string('phone', 15)->nullable()->index();
            $table->string('operator', 20)->nullable();
            $table->tinyInteger('status')->default(0)->index(); // 0=unsold, 1=sold
            $table->decimal('sell_price', 8, 2)->default(20);
            $table->timestamp('sold_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};

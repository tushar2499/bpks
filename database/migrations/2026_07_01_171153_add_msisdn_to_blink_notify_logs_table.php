<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blink_notify_logs', function (Blueprint $table) {
            $table->string('msisdn', 20)->nullable()->after('blink_txn_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('blink_notify_logs', function (Blueprint $table) {
            $table->dropColumn('msisdn');
        });
    }
};

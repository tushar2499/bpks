<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('tickets', 'series')) {
                $table->string('series', 10)->nullable()->after('ticket_no');
            }
            if (!Schema::hasColumn('tickets', 'sale_tier')) {
                $table->smallInteger('sale_tier')->default(1)->after('series');
            }
            if (!collect(DB::select("SHOW INDEX FROM tickets WHERE Key_name = 'tickets_op_series_tier_status'"))->count()) {
                $table->index(['operator', 'series', 'sale_tier', 'status'], 'tickets_op_series_tier_status');
            }
        });

        // Backfill: group by (operator, series), sort by numeric suffix, assign tiers
        $groups = [];
        DB::table('tickets')->orderBy('id')->chunk(2000, function ($rows) use (&$groups) {
            foreach ($rows as $row) {
                $series = rtrim($row->ticket_no, '0123456789');
                $op     = $row->operator ?? '__none__';
                $groups[$op][$series][] = ['id' => $row->id, 'ticket_no' => $row->ticket_no];
            }
        });

        foreach ($groups as $seriesMap) {
            foreach ($seriesMap as $series => $tickets) {
                usort($tickets, function ($a, $b) use ($series) {
                    $numA = (int) ltrim(substr($a['ticket_no'], strlen($series)), '0') ?: 0;
                    $numB = (int) ltrim(substr($b['ticket_no'], strlen($series)), '0') ?: 0;
                    return $numA <=> $numB;
                });
                foreach ($tickets as $pos => $t) {
                    DB::table('tickets')->where('id', $t['id'])->update([
                        'series'    => $series,
                        'sale_tier' => (int) floor($pos / 5000) + 1,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex('tickets_op_series_tier_status');
            $table->dropColumn(['series', 'sale_tier']);
        });
    }
};

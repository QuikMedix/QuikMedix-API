<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Co-pay by cash, check or card: the patient's chosen way to pay on the order, a "Paid by check"
     * status beside "Paid in cash", and whether the money a driver has to bring back is cash or a check.
     */
    public function up(): void
    {
        if (Schema::hasTable('orders') && ! Schema::hasColumn('orders', 'copay_method')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->string('copay_method', 10)->nullable();
            });
        }
        if (Schema::hasTable('cash_log') && ! Schema::hasColumn('cash_log', 'method')) {
            Schema::table('cash_log', function (Blueprint $table): void {
                $table->string('method', 10)->default('cash');
            });
        }
        if (Schema::hasTable('statuses_copay') && DB::table('statuses_copay')->where('id', 7)->doesntExist()) {
            DB::table('statuses_copay')->insert([
                'id' => 7,
                'name' => 'Paid by check',
                // The same badge colour as "Paid in cash".
                'color' => DB::table('statuses_copay')->where('id', 4)->value('color') ?? 'success',
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('statuses_copay')) {
            DB::table('statuses_copay')->where('id', 7)->delete();
        }
        if (Schema::hasColumn('cash_log', 'method')) {
            Schema::table('cash_log', function (Blueprint $table): void {
                $table->dropColumn('method');
            });
        }
        if (Schema::hasColumn('orders', 'copay_method')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->dropColumn('copay_method');
            });
        }
    }
};

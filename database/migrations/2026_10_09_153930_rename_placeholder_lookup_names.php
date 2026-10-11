<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Production lookup names, keyed by table and row ID.
     *
     * Status names follow the codes sent to BestRx (SendOrderStatusToBestRx); copay names follow
     * the places that set each copay status (card payment, driver cash, pharmacy, not paid).
     *
     * @var array<string, array<int, string>>
     */
    private array $names = [
        'delivery_methods' => [
            1 => 'Hand to patient',
            2 => 'Signature required',
            3 => 'Leave at door',
            4 => 'Leave with front desk',
        ],
        'statuses' => [
            1 => 'Ready for pickup',
            2 => 'In process',
            3 => 'On the way',
            4 => 'Delivered',
            5 => 'Cancelled',
            6 => 'Picked up',
            7 => 'Hub',
            8 => 'Patient unavailable',
            9 => 'Refused',
            10 => 'Returned to pharmacy',
        ],
        'statuses_copay' => [
            1 => 'No copay',
            2 => 'Copay due',
            3 => 'Paid by card',
            4 => 'Paid in cash',
            5 => 'Not paid',
            6 => 'Paid at pharmacy',
        ],
    ];

    /**
     * Replace the "(dev)" placeholder names left from the legacy dump; names already edited are kept.
     */
    public function up(): void
    {
        foreach ($this->names as $table => $names) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($names as $id => $name) {
                DB::table($table)->where('id', $id)->where('name', 'like', '%(dev)')->update(['name' => $name]);
            }
        }
    }

    /**
     * Restore the placeholder names for rows this migration renamed.
     */
    public function down(): void
    {
        $placeholders = ['delivery_methods' => 'Delivery method', 'statuses' => 'Status', 'statuses_copay' => 'Copay status'];

        foreach ($this->names as $table => $names) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($names as $id => $name) {
                DB::table($table)->where('id', $id)->where('name', $name)->update(['name' => "{$placeholders[$table]} {$id} (dev)"]);
            }
        }
    }
};

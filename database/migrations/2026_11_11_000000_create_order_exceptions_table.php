<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A driver's "patient unavailable" or "refused" report, held for dispatcher approval.
     */
    public function up(): void
    {
        Schema::create('order_exceptions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('driver_id');
            $table->unsignedInteger('requested_status_id');
            $table->string('status', 16)->default('pending');
            $table->string('driver_note', 1000)->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->string('review_note', 1000)->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_exceptions');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dispatch zones (the places deliveries are grouped by), the zone of each order, and route
     * offers: a set of orders in one zone that dispatch publishes with what it pays and a driver
     * accepts in the app.
     *
     * Zones are kept apart from the price areas in `area`: reports and prices read every row of
     * that table, and a county outline can have several parts, which its editor cannot show.
     */
    public function up(): void
    {
        if (! Schema::hasTable('dispatch_zones')) {
            Schema::create('dispatch_zones', function (Blueprint $table): void {
                $table->id();
                $table->string('name', 100);
                // The prefix of the routes made in this zone, such as BK in BK-1127-01.
                $table->string('code', 10)->unique();
                $table->string('state', 2);
                $table->string('county', 100)->nullable();
                $table->string('county_fips', 5)->nullable()->unique();
                $table->boolean('active')->default(true);
                $table->unsignedSmallInteger('target_stops')->default(35);
                $table->decimal('pay_per_stop', 8, 2)->default(3.75);
                // The box around the outline, to rule a zone out without reading its outline.
                $table->double('min_lat');
                $table->double('max_lat');
                $table->double('min_lng');
                $table->double('max_lng');
                // Where zones overlap, the smaller one is the order's zone.
                $table->double('size');
                // The outline as GeoJSON MultiPolygon coordinates: polygons of rings of [longitude, latitude].
                $table->longText('boundary');
                $table->timestamps();
            });
        }
        if (Schema::hasTable('orders') && ! Schema::hasColumn('orders', 'dispatch_zone_id')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->unsignedBigInteger('dispatch_zone_id')->nullable()->index();
            });
        }
        if (! Schema::hasTable('route_offers')) {
            Schema::create('route_offers', function (Blueprint $table): void {
                $table->id();
                $table->string('code', 30)->unique();
                $table->unsignedBigInteger('dispatch_zone_id')->index();
                $table->date('delivery_date')->nullable();
                $table->string('pickup_time', 20)->nullable();
                $table->unsignedSmallInteger('stops');
                $table->decimal('miles', 8, 1)->nullable();
                $table->decimal('pay', 8, 2);
                $table->string('note', 255)->nullable();
                // open: drivers can accept it; accepted: it is a driver's route; withdrawn: dispatch took it back.
                $table->string('status', 20)->default('open')->index();
                $table->unsignedBigInteger('driver_id')->nullable()->index();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamp('accepted_at')->nullable();
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('route_offer_orders')) {
            Schema::create('route_offer_orders', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('route_offer_id')->index();
                $table->unsignedBigInteger('order_id')->index();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('route_offer_orders');
        Schema::dropIfExists('route_offers');
        if (Schema::hasColumn('orders', 'dispatch_zone_id')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->dropIndex(['dispatch_zone_id']);
                $table->dropColumn('dispatch_zone_id');
            });
        }
        Schema::dropIfExists('dispatch_zones');
    }
};

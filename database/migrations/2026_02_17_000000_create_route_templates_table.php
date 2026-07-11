<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRouteTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('route_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('route_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_template_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['pharmacy', 'patient', 'office']);
            $table->unsignedBigInteger('type_id'); // ID of the pharmacy/patient/office
            $table->integer('priority')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('route_template_items');
        Schema::dropIfExists('route_templates');
    }
}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrderShipmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_shipments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('status')->default('created');
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('courier_id');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('length')->nullable();
            $table->string('breadth')->nullable();
            $table->string('height')->nullable();
            $table->string('weight')->nullable();
            $table->string('waybill_no')->nullable();
            $table->string('shipment_type')->default('forward');
            $table->boolean('re_attempt')->default(0);
            $table->unsignedBigInteger('re_attempt_from')->nullable();
            $table->datetime('date_time');
            $table->unsignedBigInteger('rma_request_id')->nullable();
            $table->timestamps();
            $table->foreign('order_id')->references('id')->on('orders');
            $table->foreign('user_id')->references('id')->on('users');
            // $table->foreign('warehouse_id')->references('id')->on('warehouses');
            // $table->foreign('courier_id')->references('id')->on('couriers');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_shipments');
    }
}

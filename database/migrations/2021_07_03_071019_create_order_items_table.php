<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrderItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->integer('quantity')->default(0);
            $table->string('coupon_code')->nullable();
            $table->decimal('tax_amount',10,4)->default(0.0000);
            $table->decimal('tax_percentage',10,4)->nullable();
            $table->decimal('taxable_amount',10,4)->default(0.0000);
            $table->decimal('discount',10,4)->default(0.0000);
            $table->decimal('price',10,4)->default(0.0000);
            $table->decimal('total',10,4)->default(0.0000);
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->decimal('rate',10,4)->default(0.0000);
            $table->json('additional')->nullable();
            $table->string('type')->nullable();
            $table->unsignedBigInteger('type_id')->nullable();
            $table->timestamps();
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_items');
    }
}

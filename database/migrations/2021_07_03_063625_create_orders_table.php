<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('orders');
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->dateTime('date');
            $table->unsignedInteger('customer_id')->nullable();
            $table->unsignedBigInteger('channel_id');
            $table->string('coupon_code')->nullable();
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->decimal('rate', 10, 4)->nullable();
            $table->boolean('is_guest');
            $table->unsignedBigInteger('coupon_id')->nullable();
            $table->integer('items_count')->nullable();
            $table->decimal('sub_total', 10, 4)->default(0.0000)->nullable();
            $table->decimal('discount_amount', 10, 4)->default(0.0000)->nullable();
            $table->decimal('tax_total', 10, 4)->default(0.0000)->nullable();
            $table->decimal('grand_total', 10, 4)->default(0.0000)->nullable();
            $table->unsignedBigInteger('shipping_method_id')->nullable();
            $table->string('priority')->default('Normal');
            $table->string('platform')->nullable();
            $table->string('status');
            $table->softDeletes();
            $table->timestamps();
            $table->foreign('customer_id')->references('id')->on('users')->onDelete('cascade');
            // $table->foreign('channel_id')->references('id')->on('channels')->onDelete('cascade');
            // $table->foreign('coupon_id')->references('id')->on('coupons')->onDelete('set null');
            //$table->foreign('shipping_method_id')->references('id')->on('shipping_methods')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
}

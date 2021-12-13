<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrderInvoiceItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_invoice_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->integer('quantity');
            $table->decimal('rate',10,4)->default(0.0000);
            $table->decimal('total_amount',10,4)->default(0.0000);
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->decimal('currency_rate',10,4)->default(0.0000);
            $table->string('type')->nullable();
            $table->timestamps();
            $table->foreign('order_invoice_id')->references('id')->on('order_invoices')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_invoice_items');
    }
}

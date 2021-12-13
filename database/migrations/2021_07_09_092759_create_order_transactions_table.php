<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrderTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_transactions', function (Blueprint $table) {
            $table->id();
            // $table->unsignedBigInteger('order_id');
            // $table->unsignedBigInteger('refund_id')->nullable();
            $table->unsignedBigInteger('parent_id');
            $table->string('parent_type')->default('order');
            $table->datetime('transaction_date');
            $table->string('transaction_no')->nullable();
            $table->unsignedBigInteger('payment_method_id')->nullable();
            $table->decimal('amount',10,4);
            $table->string('mode')->default('in'); // in/out
            $table->string('remarks')->nullable();
            $table->timestamps();
            // $table->foreign('payment_method_id')->references('id')->on('payment_methods')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_transactions');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserApplicationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_applications', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->unsigned();
            $table->float('amount')->unsigned();
            $table->integer('term')->unsigned();
            $table->float('weekly_repay_amount')->unsigned();
            $table->float('amount_left')->unsigned();
            $table->float('interest_rate')->unsigned();
            $table->tinyInteger('loan_status')->default(0);
            $table->boolean('is_completed')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_applications');
    }
}

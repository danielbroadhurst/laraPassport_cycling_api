<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ClubEvent extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('club_event', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('club_id');
            $table->unsignedBigInteger('admin_id');
            $table->string('event_name');
            $table->string('description')->nullable();
            $table->date('event_date');
            $table->time('start_time', 0);
            $table->string('start_address');
            $table->string('city');
            $table->string('county')->nullable();
            $table->string('country');
            $table->string('country_short');
            $table->string('lat')->nullable();
            $table->string('lng')->nullable();
            $table->string('profile_picture')->nullable();
            $table->timestamps();

            $table->foreign('club_id')->references('id')->on('cycling_club')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}

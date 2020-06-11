<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CyclingClub extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cycling_club', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('club_name')->unique();
            $table->string('bio')->nullable();
            $table->string('town');
            $table->string('region')->nullable();
            $table->string('country');
            $table->string('lat')->nullable();
            $table->string('lng')->nullable();
            $table->string('preferred_style')->nullable();
            $table->binary('profile_picture')->nullable();
            $table->boolean('is_active')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
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

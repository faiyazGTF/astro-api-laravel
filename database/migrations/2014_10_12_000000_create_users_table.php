<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->integer('country_code')->default('91');
            $table->string('mobile')->nullable();
            $table->string('otp')->nullable();
            $table->integer('status')->default(0);
            $table->string('user_type')->default('USER');
            $table->string('image')->nullable();
            $table->bigInteger('referral_id')->nullable();
            $table->string('password')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->string('email_verification_token')->nullable();

            
            $table->tinyInteger('is_signup_complete')->default(0);
            $table->longText('firebase_tokens')->nullable();
            $table->longText('socket_token')->nullable();
            $table->integer('astroera_account')->default(0);
            $table->tinyInteger('is_deleted')->default(0);
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
        Schema::dropIfExists('users');
    }
};

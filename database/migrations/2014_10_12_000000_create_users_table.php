<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 190)->nullable();
            $table->string('lastname', 190)->nullable();
            $table->string('avatar', 190)->nullable();
            $table->enum('role', ['admin', 'beekeeper'])->default('beekeeper');
            $table->string('city', 190)->nullable();
            $table->date('birthdate')->nullable();	
            $table->enum('gender', ['Male', 'Female', 'Other'])->nullable();
            $table->string('email', 191)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('numero_renapa', 190)->nullable();
            $table->rememberToken();
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
}

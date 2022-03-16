<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificacionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->increments('id');     
            $table->integer('apicultor_id')->unsigned();
            $table->integer('apiario_id')->unsigned();
            $table->integer('colmena_id')->unsigned();
            $table->text('icono')->nullable();
            $table->text('class')->nullable();
            $table->text('texto')->nullable();
            $table->boolean('leida')->default(false);
            $table->boolean('eliminada')->default(false);
            $table->date('fecha')->nullable();
            $table->time('hora')->nullable();
            $table->enum('tipo', ['temperatura', 'humedad', 'senial'])->nullable();
            $table->timestamps();
            $table->foreign('apicultor_id')->references('id')->on('users');
            $table->foreign('apiario_id')->references('id')->on('apiarios');
            $table->foreign('colmena_id')->references('id')->on('colmenas');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notificaciones');
    }
}

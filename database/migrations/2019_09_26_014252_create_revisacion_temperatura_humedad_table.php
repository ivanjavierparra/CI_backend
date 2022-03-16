<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRevisacionTemperaturaHumedadTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('revisacion_temperatura_humedad', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('apiario_id')->unsigned();
            $table->integer('colmena_id')->unsigned();
            $table->decimal('temperatura', 8, 2);
            $table->decimal('humedad', 8, 2);
            $table->date('fecha_revisacion');
            $table->time('hora_revisacion');
            $table->timestamps();
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
        Schema::dropIfExists('revisacion_temperatura_humedad');
    }
}

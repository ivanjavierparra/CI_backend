<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClimaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('climas', function (Blueprint $table) {
            $table->increments('id');     
            $table->enum('ciudad', ['Rawson', 'Trelew', 'Gaiman', 'Dolavon','28 de Julio']);   
            $table->decimal('temperatura', 8, 3)->nullable();
            $table->decimal('temperatura_minima', 8, 3)->nullable();
            $table->decimal('temperatura_maxima', 8, 3)->nullable();
            $table->decimal('sensacion_termica', 8, 3)->nullable();
            $table->decimal('sensacion_termica_minima', 8, 3)->nullable();
            $table->decimal('sensacion_termica_maxima', 8, 3)->nullable();
            $table->decimal('humedad', 8, 3)->nullable();
            $table->string('direccion_del_viento', 250)->nullable();
            $table->decimal('velocidad_del_viento_km_hs', 8, 3)->nullable();
            $table->decimal('presion_hpa', 8, 3)->nullable();
            $table->decimal('horas_de_sol', 8, 3)->nullable();
            $table->text('descripcion')->nullable();
            $table->text('descripcion_dia')->nullable();
            $table->text('descripcion_noche')->nullable();
            $table->date('fecha')->nullable();
            $table->time('hora')->nullable();
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
        Schema::dropIfExists('climas');
    }
}

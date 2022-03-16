<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApiarioTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('apiarios', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('apicultor_id')->unsigned()->default('1')->nullable(false);
            $table->string('nombre_fantasia', 250);
            $table->float('latitud', 40, 20);
            $table->float('longitud', 40, 20);
            $table->date('fecha_creacion');
            $table->text('descripcion');
            $table->string('localidad_chacra', 250);
            $table->string('direccion_chacra', 250);
            $table->string('propietario_chacra', 250);
            $table->boolean('eliminado')->default(false);
            $table->timestamps();
            $table->foreign('apicultor_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('apiarios');
    }
}

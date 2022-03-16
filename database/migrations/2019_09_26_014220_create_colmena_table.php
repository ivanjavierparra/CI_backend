<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateColmenaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('colmenas', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('apiario_id')->unsigned();
            $table->string('identificacion', 250);
            $table->date('fecha_creacion');
            $table->enum('raza_abeja', ['Italiana', 'Buckfast', 'Carniola', 'Caucasica', 'Otros'])->default("Italiana")->nullable();
            $table->text('descripcion');
            $table->boolean('eliminado')->default(false);
            $table->timestamps();
            $table->foreign('apiario_id')->references('id')->on('apiarios');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('colmenas');
    }
}

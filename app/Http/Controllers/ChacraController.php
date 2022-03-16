<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Chacra;

class ChacraController extends Controller
{
    
    
    /**
     * Retorna todas las chacras existentes.
     * 
     * @return Chacras
     */
    public function getChacras(Request $request) {
        // Busco todas las chacras.
        $chacras = Chacra::all();

        // Retorno resultado
        return response()->json($chacras, 200);

    }
    
    /**
     * Crea una nueva chacra.
     */
    public function crearChacra(Request $request) {

        // Obtengo datos de la chacra.
        $datos = array(
            'direccion' => "Chacra ".$request['direccion'],
            'localidad' => $request['localidad'],
            'propietario' => $request['propietario']
        );
        
        // Creo nueva chacra.
        $resultado = Chacra::crearChacra($datos);

        // Retorno resultado
        return response()->json($resultado, 200);
    }
}

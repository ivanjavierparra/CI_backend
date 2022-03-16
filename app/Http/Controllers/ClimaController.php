<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Clima;
use App\RevisacionTemperaturaHumedad;
use Illuminate\Support\Facades\Auth;
use JWTAuth;

class ClimaController extends Controller
{
    
    /**
     * Dada la ciudad recibida como parámetro, devuelvo el último registro
     * del clima que hay en la bd.
     * 
     * @return Array (Clima)
     */
    public function obtenerUltimoClima(Request $request) {

        $ciudad = $request['ciudad'];

        $clima = Clima::where('ciudad',$ciudad)->orderBy('id','desc')->first();

        if ( !$clima ) return response()->json(array(), 200);

        return response()->json($clima, 200);
    }


    /**
     * Dada una ciudad y una variable climática, genero un rango de fechas, y para cada fecha
     * busco el valor de la variable climática, devolviendo todo en un array.
     * 
     * @return Array (Clima)
     */
    public function obtenerVariableClimatica(Request $request) {
        
        $ciudad = $request['ciudad'];
        $variable = $request['variable']; 
        $tipoAccion = json_decode($request['tipoAccion'],true);

        // Obtengo el array de fechas
        $rango_fechas = RevisacionTemperaturaHumedad::getFechasInglesasParaRango( $tipoAccion );

        $dataset = Clima::getDatosVariable($ciudad, $variable, $rango_fechas);

        $clima = array(
            'dataset' => $dataset['clima'],
        );

        return response()->json($clima, 200);

    }

    /**
     * Dada una ciudad, devuelvo todos los climas asociados.
     * 
     * @return Array Clima
     */
    public function obtenerHistoricoCiudad(Request $request) {

        $ciudad = $request['ciudad'];
        $tipoAccion = json_decode($request['tipoAccion'],true);

        // Obtengo el array de fechas
        $rango_fechas = RevisacionTemperaturaHumedad::getFechasInglesasParaRango( $tipoAccion );

        $dataset = Clima::obtenerClimaCiudad($ciudad, $rango_fechas);

        $clima = array(
            'datos' => $dataset['datos'],
        );

        return response()->json($clima, 200);

    }



    /**
     * Devuelve las revisaciones de una colmena de un apiario, 
     * en base a los parámetros recibidos: variable, rango de fechas, rango horario, etc.
     */
    public function getClimaCiudadGraficos(Request $request) {
        
        // Validar que el apiario sea del usuario
        $usuario = JWTAuth::parseToken()->authenticate();
        
        $ciudad = $request['ciudad'];
        $variable_ambiental = $request['variable'];
        $tipoAccion = json_decode($request['tipoAccion'],true);
        $ciudades = json_decode($request['ciudades'],true);
        

        if(  ($tipoAccion['accion'] == "Comparacion")  &&  ($tipoAccion['tipo'] == "dia") ) {
            
            
            $clima_pasado = Clima::obtenerClimaDiaChart($ciudad, $variable_ambiental, $tipoAccion['fecha_pasada']);
            $clima_actual = Clima::obtenerClimaDiaChart($ciudad, $variable_ambiental, $tipoAccion['fecha_actual']);
            
            $extras = array();
            foreach( $ciudades as $c ) {
                $pasado = Clima::obtenerClimaDiaChart($c, $variable_ambiental, $tipoAccion['fecha_pasada']);     
                $actual = Clima::obtenerClimaDiaChart($c, $variable_ambiental, $tipoAccion['fecha_actual']);
                $parcial = array(
                    "ciudad" => $c,
                    "variable_pasada" => $pasado,
                    "variable_actual" => $actual,
                );
                array_push($extras,$parcial);
            }

            $resultado = array(
                'variable_pasada' => $clima_pasado,
                'variable_actual' => $clima_actual,
                'extras' => $extras,
            );

            return response()->json($resultado, 200); 
        } 
        elseif( $tipoAccion['accion'] == "Comparacion"  &&  $tipoAccion['tipo'] == "mes" ) {

            $clima_pasado = Clima::obtenerClimaMesesChart($ciudad, $variable_ambiental, $tipoAccion['fecha_pasada']);
            $clima_actual = Clima::obtenerClimaMesesChart($ciudad, $variable_ambiental, $tipoAccion['fecha_actual']);

            $extras = array();
            foreach( $ciudades as $c ) {
                $pasado = Clima::obtenerClimaMesesChart($c, $variable_ambiental, $tipoAccion['fecha_pasada']);     
                $actual = Clima::obtenerClimaMesesChart($c, $variable_ambiental, $tipoAccion['fecha_actual']);
                $parcial = array(
                    "ciudad" => $c,
                    "variable_pasada" => $pasado,
                    "variable_actual" => $actual,
                );
                array_push($extras,$parcial);
            }

            $resultado = array(
                'variable_pasada' => $clima_pasado,
                'variable_actual' => $clima_actual,
                'extras' => $extras,
            );

            return response()->json($resultado, 200); 

        }
        elseif( $tipoAccion['accion'] == "Rango" ) {
            
            $rango_fechas = RevisacionTemperaturaHumedad::getFechasInglesasParaRango( $tipoAccion );

            $extras = array();
            foreach( $ciudades as $c ) {   
                $actual = Clima::obtenerClimaRangoFechasChart($c, $variable_ambiental, $rango_fechas);
                $parcial = array(
                    "ciudad" => $c,
                    "variable_actual" => $actual,
                );
                array_push($extras,$parcial);
            }

            $resultado = array(
                'variable_actual' => Clima::obtenerClimaRangoFechasChart($ciudad, $variable_ambiental, $rango_fechas),
                'extras' => $extras,
            );

            return response()->json($resultado, 200); 

        }
        else {

            // Naranja fanta...

        }

    }


    /**
     * Devuelve todos los climas.
     */
    public function getAllClimas(Request $request) {

        $climas = Clima::all();

        return response()->json($climas, 200); 
    }
}

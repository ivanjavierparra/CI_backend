<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\RevisacionTemperaturaHumedad;
use App\Colmena;
use App\Apiario;
use App\Clima;
use App\Notificacion;
use App\Script;
use Illuminate\Support\Facades\Auth;
use JWTAuth;

class RevisacionTemperaturaHumedadController extends Controller
{
    /**
     * VER BIEN SI LOS DATOS VIENEN POR POST O GET....MODIFICAR EN WEB.
     */
    public function crearRevisacion(Request $request) {
        
    }


    /**
     * Devuelve la temperatura de una determinada colmena.
     * 
     * @return Array
     */
    public static function getTemperaturaHumedad(Request $request) {
        
        $datos = RevisacionTemperaturaHumedad::crearDataset($request['apiario'], $request['colmena']);
        return response()->json($datos, 200);
    }
    

    /**
     * Recibe los datos provenientes del SIM800L y los guarda en un archivo txt
     * llamado "testing_arduino.txt", el cual se encuentre en el directorio 
     * del proyecto: backend/storage/app/testing_arduino.txt
     * 
     */
    public function procesarArduino(Request $request) {
        /*$archivo = fopen("archivo.txt","w+") or die ("Error al crear archivo.");
        fwrite($archivo,json_encode($request,true));
        fwrite($archivo,"--------------------------------------- \n \n");
        fclose($archivo);*/

        
        # Se guarda en: backend/storage/app/testing_arduino
        $resultado = array(
            "apiario" => $request['apiario_id'],
            "colmena" => $request['colmena_id'],
            "temperatura" => $request['temperatura'],
            "humedad" => $request['humedad'],
            "fecha_hora" => $request['fecha_hora'], # Formato hh:mm:ss d,m,Y: 15:03:03 05,07,2020
            //'fecha_revisacion' => $request['fecha'],
            //'hora_revisacion' => $request['hora'],
        );


        Storage::disk('local')->put('testing_arduino.txt', json_encode($resultado));

        # Validaciones
        $apiario = Apiario::where('id', $request['apiario_id'])->first();
        if( !$apiario ) return;
        $colmena = Colmena::where('apiario_id', $request['apiario_id'])->where('id', $request['colmena_id'])->first();
        if( !$colmena ) return;
        if( !$request['temperatura'] || !$request['humedad'] ) return;
        if( $request['temperatura'] > 80 || $request['humedad'] > 100 || $request['humedad'] < 0 ) return;
        if( !$request['fecha_hora'] ) return;

        # Procesamiento
        $fecha_hora = explode(" ", $request['fecha_hora']);
        $hora = $fecha_hora[0];
        $hora = substr($hora,0,5);
        # Si queremos solo la hora, y no los minutos
        # $hora = substr($hora,0,2).":00";
        $fecha = $fecha_hora[1];
        $fecha = explode(",", $fecha)[2]."-".explode(",", $fecha)[1]."-".explode(",", $fecha)[0];

        # Valido que la hora de la revisación no esté desfasada.
        $ultima_revisacion = RevisacionTemperaturaHumedad::where("apiario_id",$apiario->id)->where("colmena_id",$colmena->id)->orderBy("id","desc")->first();
        if( $ultima_revisacion ) {
            # Si fecha_hora_nueva_revisacion < fecha_hora_ultima_revisacion => NO PROCESAR.
            $fechaHoraUltimaRevisacion = $ultima_revisacion->fecha_revisacion." ".$ultima_revisacion->hora_revisacion;
            $fechaHoraNuevaRevisacion = $fecha." ".$hora;
            if ( Script::compararHorarios($fechaHoraUltimaRevisacion, $fechaHoraNuevaRevisacion) <= 0 ) return;
        }

        $datos = array(
            'apiario_id' => $apiario->id,
            'colmena_id'=> $colmena->id,
            'temperatura' => $request['temperatura'],
            'humedad' => $request['humedad'],
            'fecha_revisacion' => $fecha,
            'hora_revisacion' => $hora,
        );

        # Se crean notificaciones de temperatura y humedad.
        Notificacion::crearNotificaciones($apiario->id, $colmena->id, $request['temperatura'], $request['humedad']);
        $objetoRevisacion = RevisacionTemperaturaHumedad::create($datos);

        

        

        //Storage::disk('local')->put('testing_arduino.txt', $request['apiario']);
        //Storage::disk('local')->put('testing_arduino.txt', $request);
        //Storage::disk('local')->put('testing_arduino.txt', json_encode($resultado));

        return response()->json(array("Entraste al proyecto!"), 200);
        /*
        $objetoRevisacion = RevisacionTemperaturaHumedad::create($datos); */
        // echo($request);

        # Se crean notificaciones de temperatura y humedad.
        # Notificacion::crearNotificaciones($request['apiario_id'], $request['colmena_id'], $request['temperatura'], $request['humedad']);
        
        # return response()->json(array("Entraste al proyecto!"), 200);

    }

    /**
     * Devuelve las revisaciones de una colmena de un apiario.
     * Se devuelven varias cosas para poder armar el gráfico de colmena usando la libreria Chart.js
     * YA NO SE USA ESTE MÉTODO.
     */
    public function getRevisaciones(Request $request) {
       
        $apiario = $request['apiario'];
        $colmena = $request['colmena']; 
        $variable = $request['variable'];
        $horario = $request['horario'];
        $tipo = json_decode($request['tipoAccion'],true);

        if( $tipo['accion'] == 'Rango' ) {
            $fechas = RevisacionTemperaturaHumedad::getFechas($tipo); 
            $resultado = RevisacionTemperaturaHumedad::getDatosRangoFecha($apiario,$colmena,$variable,$horario,$fechas);
            return response()->json($resultado, 200);
        }
        else {
            if( $tipo['tipo'] == 'dia' ) {
                
                // Paso a Array las fechas que estan como String
                $fecha_actual = array($tipo['fecha_actual']);
                $fecha_pasada = array($tipo['fecha_pasada']);

                $dataset_1 = RevisacionTemperaturaHumedad::getDatosRangoFecha($apiario,$colmena,$variable,$horario,$fecha_actual);
                $dataset_2 = RevisacionTemperaturaHumedad::getDatosRangoFecha($apiario,$colmena,$variable,$horario,$fecha_pasada);

                $labels = RevisacionTemperaturaHumedad::procesarLabels($dataset_1['labels'], $dataset_2['labels']);

                $subtitulo_1 = "Día ".date('m-d-Y', strtotime( $tipo['fecha_actual'] ));
                $subtitulo_2 = "Día ".date('m-d-Y', strtotime( $tipo['fecha_pasada'] ));
                $eje_x = RevisacionTemperaturaHumedad::procesarSubtitulo($tipo['fecha_actual'], false);
                $backgroundColor = RevisacionTemperaturaHumedad::procesarColores($dataset_1['temperatura'],$dataset_1['humedad'],$dataset_2['temperatura'],$dataset_2['humedad']);

                $resultado = array(
                    'labels' => $labels,                    
                    'temperatura_1' => $dataset_1['temperatura'],
                    'temperatura_2' => $dataset_2['temperatura'],
                    'humedad_1' => $dataset_1['humedad'],
                    'humedad_2' => $dataset_2['humedad'],
                    'backgroundColor_1' => $dataset_1['backgroundColor'],
                    'backgroundColor_2' => $dataset_2['backgroundColor'],
                    'subtitulo_1' => $subtitulo_1,
                    'subtitulo_2' => $subtitulo_2,
                    'subtitulo_eje_x' => $eje_x,
                    'colores_temperatura_1' => $backgroundColor['colores_temperatura_1'],
                    'colores_temperatura_2' => $backgroundColor['colores_temperatura_2'],
                    'colores_humedad_1' => $backgroundColor['colores_humedad_1'],
                    'colores_humedad_2' => $backgroundColor['colores_humedad_2']
                );

                return response()->json($resultado, 200);
            }
            else {
                
                // Recorda que el mes viene con formato 2020-01
                $fechas_anio_actual = RevisacionTemperaturaHumedad::formatearFechas($tipo['fecha_actual']);
                $fechas_anio_pasado = RevisacionTemperaturaHumedad::formatearFechasAnioAnterior($tipo['fecha_pasada'],$fechas_anio_actual[1]);

                $fechas_dataset_1 = RevisacionTemperaturaHumedad::getRangoFechas($fechas_anio_actual[0], $fechas_anio_actual[1]);
                $fechas_dataset_2 = RevisacionTemperaturaHumedad::getRangoFechas($fechas_anio_pasado[0], $fechas_anio_pasado[1]);

                $dataset_1 = RevisacionTemperaturaHumedad::getDatosRangoFecha($apiario,$colmena,$variable,$horario,$fechas_dataset_1);
                $dataset_2 = RevisacionTemperaturaHumedad::getDatosRangoFecha($apiario,$colmena,$variable,$horario,$fechas_dataset_2);

                $labels = RevisacionTemperaturaHumedad::procesarLabels($dataset_1['labels'], $dataset_2['labels']);

                $subtitulo_1 = RevisacionTemperaturaHumedad::procesarSubtitulo($tipo['fecha_actual'], true);
                $subtitulo_2 = RevisacionTemperaturaHumedad::procesarSubtitulo($tipo['fecha_pasada'], true);
                $eje_x = RevisacionTemperaturaHumedad::procesarSubtitulo($tipo['fecha_actual'], false);
                $backgroundColor = RevisacionTemperaturaHumedad::procesarColores($dataset_1['temperatura'],$dataset_1['humedad'],$dataset_2['temperatura'],$dataset_2['humedad']);


                $resultado = array(
                    'labels' => $labels,                    
                    'temperatura_1' => $dataset_1['temperatura'],
                    'temperatura_2' => $dataset_2['temperatura'],
                    'humedad_1' => $dataset_1['humedad'],
                    'humedad_2' => $dataset_2['humedad'],
                    'backgroundColor_1' => $dataset_1['backgroundColor'],
                    'backgroundColor_2' => $dataset_2['backgroundColor'],
                    'subtitulo_1' => $subtitulo_1,
                    'subtitulo_2' => $subtitulo_2,
                    'subtitulo_eje_x' => $eje_x,
                    'colores_temperatura_1' => $backgroundColor['colores_temperatura_1'],
                    'colores_temperatura_2' => $backgroundColor['colores_temperatura_2'],
                    'colores_humedad_1' => $backgroundColor['colores_humedad_1'],
                    'colores_humedad_2' => $backgroundColor['colores_humedad_2']
                );

                return response()->json($resultado, 200);
            }            
        }       
    }


    /**
     * Devuelve las revisaciones de una colmena de un apiario, 
     * en base a los parámetros recibidos: variable, rango de fechas, rango horario, etc.
     */
    public function getRevisacionesTyH(Request $request) {
        
        // Validar que el apiario sea del usuario
        $usuario = JWTAuth::parseToken()->authenticate();
        $apiario_validar = Apiario::where('id',$request['apiario'])->where('apicultor_id',$usuario->id)->first();
        if( !$apiario_validar ) { 
            // Retornar error...
        }
        
        $apiario = $request['apiario'];
        $colmena = $request['colmena']; 
        $variable = $request['variable'];
        $tipoAccion = json_decode($request['tipoAccion'],true);
        $variable_ambiental = $request['variable_ambiental'];

        $inicio = "00:00";
        $fin = "23:59";
        $rango = "60"; // Minutos

        if( $tipoAccion['horario_desde']  !=  "Todo el dia" ) {
            $inicio = $tipoAccion['horario_desde'];
            $fin = $tipoAccion['horario_hasta'];
            $rango = $tipoAccion['rango'];
        }

        $intervalos_horarios = RevisacionTemperaturaHumedad::obtenerRangoHorarios($inicio, $fin, $rango);

        // Recorrer los temp_y_hum de la colmena { label: {rango de horarios + fecha}  y={valor de T o H}} 
        // Si es comparar-dia entonce proceso una sola vez $intervalos_horarios
        // Si es comparar-mes entonces hago un forach(fechas){ foreach($intervalos_horarios) }
        // Si es rango, entonces para cada fecha hago un foreach del intervalo.
        // NOTA: si para algun intervalo NO existe un dato de T y/o H, entonces poner NULL.

        if(  ($tipoAccion['accion'] == "Comparacion")  &&  ($tipoAccion['tipo'] == "dia") ) {
            
            // llamada 1: fecha pasada
            $pasados = RevisacionTemperaturaHumedad::comparacion_dias($apiario, $colmena, $variable, $tipoAccion['fecha_pasada'], $intervalos_horarios);

            // llamada 2: fecha actual
            $actuales = RevisacionTemperaturaHumedad::comparacion_dias($apiario, $colmena, $variable, $tipoAccion['fecha_actual'], $intervalos_horarios);

            // llamada N: Si hay variables ambientales......
            $clima_pasado = Clima::obtenerClimaDia($apiario, $variable_ambiental, $tipoAccion['fecha_pasada']);
            $clima_actual = Clima::obtenerClimaDia($apiario, $variable_ambiental, $tipoAccion['fecha_actual']);
            
            $resultado = array(
                'temperatura_colmena_pasada' => $pasados['temperatura'],
                'temperatura_colmena_actual' => $actuales['temperatura'],
                'humedad_colmena_pasada' => $pasados['humedad'],
                'humedad_colmena_actual' => $actuales['humedad'],
                'clima_temperatura_pasada' => $clima_pasado['temperatura'],
                'clima_humedad_pasada' => $clima_pasado['humedad'],
                'clima_temperatura_actual' => $clima_actual['temperatura'],
                'clima_humedad_actual' => $clima_actual['humedad'],
            );

            return response()->json($resultado, 200); 
        } 
        elseif( $tipoAccion['accion'] == "Comparacion"  &&  $tipoAccion['tipo'] == "mes" ) {


            // llamada 1: fecha pasada
            $mes_pasado = RevisacionTemperaturaHumedad::comparacion_meses($apiario, $colmena, $variable, $tipoAccion['fecha_pasada'], $intervalos_horarios);

            // llamada 2: fecha actual
            $mes_actual = RevisacionTemperaturaHumedad::comparacion_meses($apiario, $colmena, $variable, $tipoAccion['fecha_actual'], $intervalos_horarios);

            // llamada N: Si hay variables ambientales......
            $clima_pasado = Clima::obtenerClimaMeses($apiario, $variable_ambiental, $tipoAccion['fecha_pasada']);
            $clima_actual = Clima::obtenerClimaMeses($apiario, $variable_ambiental, $tipoAccion['fecha_actual']);

            $resultado = array(
                'temperatura_colmena_pasada' => $mes_pasado['temperatura'],
                'temperatura_colmena_actual' => $mes_actual['temperatura'],
                'humedad_colmena_pasada' => $mes_pasado['humedad'],
                'humedad_colmena_actual' => $mes_actual['humedad'],
                'clima_temperatura_pasada' => $clima_pasado['temperatura'],
                'clima_humedad_pasada' => $clima_pasado['humedad'],
                'clima_temperatura_actual' => $clima_actual['temperatura'],
                'clima_humedad_actual' => $clima_actual['humedad'],
            );

            return response()->json($resultado, 200); 

        }
        elseif( $tipoAccion['accion'] == "Rango" ) {
            
            $rango_fechas = RevisacionTemperaturaHumedad::getFechasInglesasParaRango( $tipoAccion );

            // llamada 1
            $datos_actual = RevisacionTemperaturaHumedad::rango_fechas($apiario, $colmena, $variable, $rango_fechas, $intervalos_horarios);            

            // llamada N: Si hay variables ambientales......
            $clima_actual = Clima::obtenerClimaRangoFechas($apiario, $variable_ambiental, $rango_fechas);

            $resultado = array(
                'temperatura_colmena' => $datos_actual['temperatura'],
                'humedad_colmena' => $datos_actual['humedad'],
                'clima_temperatura' => $clima_actual['temperatura'],
                'clima_humedad' => $clima_actual['humedad'],
            );

            return response()->json($resultado, 200); 

        }
        else {

            // Naranja fanta...

        }

    }


    /**
     * Dado una colmena de un apiario, y un array de fechas, devuelve un dataset que contiene {fecha, activo/inactivo}
     * indicando si para cada fecha existe una revisación.
     * 
     * 
     */
    public function getSenialDiaria(Request $request) {

        // Validar que el apiario sea del usuario
        $usuario = JWTAuth::parseToken()->authenticate();
        $apiario_validar = Apiario::where('id',$request['apiario'])->where('apicultor_id',$usuario->id)->first();
        if( !$apiario_validar ) { 
            // Retornar error...
        }

        $apiario = $request['apiario'];
        $colmena = $request['colmena']; 
        $tipoAccion = json_decode($request['tipoAccion'],true);

        // Obtengo el array de fechas
        $rango_fechas = RevisacionTemperaturaHumedad::getFechasInglesasParaRango( $tipoAccion );

        // Obtengo el dataset
        $datos = RevisacionTemperaturaHumedad::getSenialDiaria($apiario, $colmena, $rango_fechas);

        $resultado = array(
            'senial' => $datos['senial'],
            'fechas' => $rango_fechas,
        );

        return response()->json($resultado, 200); 

    }


     /**
     * Dado una colmena de un apiario, y un array de fechas, devuelve un dataset que contiene {fecha, activo/inactivo}
     * indicando si para cada fecha existe una revisación.
     * 
     * 
     */
    public function getSenialRangoFechas(Request $request) {

        $usuario = JWTAuth::parseToken()->authenticate();

        // Validar que el apiario sea del usuario
        //$apiario_validar = Apiario::where('id',$request['apiario'])->where('apicultor_id',$usuario->id)->first();
        //if( !$apiario_validar ) { 
            // Retornar error...
        //}

        $apiario = $request['apiario'];
        $colmena = $request['colmena']; 
        $tipoAccion = json_decode($request['tipoAccion'],true);

        $inicio = "00:00";
        $fin = "23:59";
        $rango = "60"; // Minutos

        if( $tipoAccion['horario_desde']  !=  "Todo el dia" ) {
            $inicio = $tipoAccion['horario_desde'];
            $fin = $tipoAccion['horario_hasta'];
            $rango = $tipoAccion['rango'];
        }

        $intervalos_horarios = RevisacionTemperaturaHumedad::obtenerRangoHorarios($inicio, $fin, $rango);

        // Obtengo el array de fechas
        $rango_fechas = RevisacionTemperaturaHumedad::getFechasInglesasParaRango( $tipoAccion );

        $datos = array();

        if( $tipoAccion['horario_desde']  ==  "Todo el dia" ) { 
            // Obtengo el dataset
            $datos = RevisacionTemperaturaHumedad::getSenialFechas($apiario, $colmena, $rango_fechas, array());
        }
        else {
            // Obtengo el dataset
            $datos = RevisacionTemperaturaHumedad::getSenialFechas($apiario, $colmena, $rango_fechas, $intervalos_horarios);
        }
        

        $resultado = array(
            'senial' => $datos['senial'],
            'fechas' => $rango_fechas,
            'apiario' => $apiario,
            'colmena' => $colmena,
        );

        return response()->json($resultado, 200); 

    }

    
    /**
     * Devuelve todas las revisaciones de una colmena de un apiario en el rango de
     * fechas pasada como parámetros.
     * 
     */
    public function getRevisacionesColmena(Request $request) {

        // Valido que la colmena y el apiario sean del apicultor.
        // Valido que el apiario sea del apicultor.
        $usuario = JWTAuth::parseToken()->authenticate();       
        $apiario_validar = Apiario::where('id',$request['apiario'])->where('apicultor_id',$usuario->id)->first();
        if( !$apiario_validar ) {
            // Return error...
        }
        $colmena_validar = Colmena::where('apiario_id',$apiario_validar->id)->where('id',$request['colmena'])->first();
        if( !$colmena_validar ) {
            // Return error...
        }
        
        $apiario = $request['apiario'];
        $colmena = $request['colmena']; 
        $tipoAccion = json_decode($request['tipoAccion'],true);

        // Obtengo el array de fechas
        $rango_fechas = RevisacionTemperaturaHumedad::getFechasInglesasParaRango( $tipoAccion );

        // Obtengo el dataset
        $datos = RevisacionTemperaturaHumedad::obtenerTyHColmenaMerge($apiario, $colmena, $rango_fechas);

        $resultado = array(
            'datos' => $datos['datos'],
            'fechas' => $rango_fechas,
        );

        return response()->json($resultado, 200); 
    }


    /**
     * Devuelve todas las revisaciones de la última semana de una colmena de un apiario.
     */
    public function obtenerTyHUltimaSemana(Request $request) {

        // Validar que el apiario sea del conductor.
        $usuario = JWTAuth::parseToken()->authenticate();
        $apiario_validar = Apiario::where('id',$request['apiario'])->where('apicultor_id',$usuario->id)->first();
        if( !$apiario_validar ) { 
            // Retornar error...
        }
        
        $apiario = $request['apiario'];
        $colmena = $request['colmena'];

        // Obtengo el array de fechas
        $rango_fechas = RevisacionTemperaturaHumedad::obtener_arreglo_fechas_ultima_semana();
        
        // Obtengo el dataset
        $datos = RevisacionTemperaturaHumedad::obtenerTyHColmenaMerge($apiario, $colmena, $rango_fechas);

        
        $mensaje = "";
        $mensaje_temperatura = "";
        $mensaje_humedad = "";

        $ultima_revisacion = RevisacionTemperaturaHumedad::where('apiario_id',$request['apiario'])->where('colmena_id',$request['colmena'])->orderBy('id', 'desc')->first();

        if ( $ultima_revisacion ) {
            
            $colorSenial = RevisacionTemperaturaHumedad::getColorSenial($ultima_revisacion);
            if( $colorSenial == "rojo"  ) $mensaje = "Datos obsoletos";

            $mensaje_temperatura = RevisacionTemperaturaHumedad::getMensajeTempertura($ultima_revisacion);
            $mensaje_humedad = RevisacionTemperaturaHumedad::getMensajeHumedad($ultima_revisacion);
        }
        
        $resultado = array(
            'datos' => $datos['datos'],
            'fechas' => $rango_fechas,
            "mensaje" => $mensaje,
            "mensaje_temperatura" => $mensaje_temperatura,
            "mensaje_humedad" => $mensaje_humedad,
            
        );

        return response()->json($resultado, 200); 
    }

    /**
     * Utilizado en "Comparación Colmenas". Por cada una de las colmenas recibidas, busca el apiario al que pertenece y 
     * sus datos de temperatura y humedad. Luego retorna un arreglo con el formato [{A1,C1,T1,H1},{A2,C2,T2,H2}] siendo: A=apiario, C=colmena, T=temperatura, H=humedad.
     * 
     * @param String variable {temperatura, humedad}
     * @param array of string colmenas
     * @param array tipoAccion
     */
    public function getComparacionColmenas(Request $request) {

        // Validar que el apiario sea del usuario
        $usuario = JWTAuth::parseToken()->authenticate();
        
        $variable = $request['variable'];
        $colmenas = json_decode($request['colmenas'], true); 
        $tipoAccion = json_decode($request['tipoAccion'],true);
        
        $inicio = "00:00";
        $fin = "23:59";
        $rango = "60"; // Minutos

        if( $tipoAccion['horario_desde']  !=  "Todo el dia" ) {
            $inicio = $tipoAccion['horario_desde'];
            $fin = $tipoAccion['horario_hasta'];
            $rango = $tipoAccion['rango'];
        }

        $intervalos_horarios = RevisacionTemperaturaHumedad::obtenerRangoHorarios($inicio, $fin, $rango);

        if(  ($tipoAccion['accion'] == "Comparacion")  &&  ($tipoAccion['tipo'] == "dia") ) { 


            $resultado_pasado = array();
            $resultado_actual = array();

            foreach( $colmenas as $colmena ) { 

                $colmena_completa = Colmena::find($colmena);
                $apiario = Apiario::where('id', $colmena_completa->apiario_id)->first();

                // llamada 1: fecha pasada
                $pasados = RevisacionTemperaturaHumedad::comparacion_dias($apiario->id, $colmena, $variable, $tipoAccion['fecha_pasada'], $intervalos_horarios);

                // llamada 2: fecha actual
                $actuales = RevisacionTemperaturaHumedad::comparacion_dias($apiario->id, $colmena, $variable, $tipoAccion['fecha_actual'], $intervalos_horarios);

                $resultado_parcial_pasado = array(
                    'apiario' => $apiario,
                    'colmena' => $colmena_completa,
                    'temperatura' => $pasados['temperatura'],
                    'humedad' => $pasados['humedad'],
                );

                $resultado_parcial_actual = array(
                    'apiario' => $apiario,
                    'colmena' => $colmena_completa,
                    'temperatura' => $actuales['temperatura'],
                    'humedad' => $actuales['humedad'],
                );

                array_push($resultado_pasado, $resultado_parcial_pasado);
                array_push($resultado_actual, $resultado_parcial_actual);
            }

            return response()->json(array(
                "pasado" => $resultado_pasado,
                "actual" => $resultado_actual,
            ), 200); 
        }
        elseif( $tipoAccion['accion'] == "Comparacion"  &&  $tipoAccion['tipo'] == "mes" ) { 

            $resultado_pasado = array();
            $resultado_actual = array();

            foreach( $colmenas as $colmena ) { 

                $colmena_completa = Colmena::find($colmena);
                $apiario = Apiario::where('id', $colmena_completa->apiario_id)->first();

                // llamada 1: fecha pasada
                $mes_pasado = RevisacionTemperaturaHumedad::comparacion_meses($apiario->id, $colmena, $variable, $tipoAccion['fecha_pasada'], $intervalos_horarios);

                // llamada 2: fecha actual
                $mes_actual = RevisacionTemperaturaHumedad::comparacion_meses($apiario->id, $colmena, $variable, $tipoAccion['fecha_actual'], $intervalos_horarios);

                $resultado_parcial_pasado = array(
                    'apiario' => $apiario,
                    'colmena' => $colmena_completa,
                    'temperatura' => $mes_pasado['temperatura'],
                    'humedad' => $mes_pasado['humedad'],
                );

                $resultado_parcial_actual = array(
                    'apiario' => $apiario,
                    'colmena' => $colmena_completa,
                    'temperatura' => $mes_actual['temperatura'],
                    'humedad' => $mes_actual['humedad'],
                );

                array_push($resultado_pasado, $resultado_parcial_pasado);
                array_push($resultado_actual, $resultado_parcial_actual);
            }
            
            return response()->json(array(
                "pasado" => $resultado_pasado,
                "actual" => $resultado_actual,
            ), 200); 

        }
        elseif( $tipoAccion['accion'] == "Rango" ) { 

            $rango_fechas = RevisacionTemperaturaHumedad::getFechasInglesasParaRango( $tipoAccion );

            $resultado = array();
    
            foreach( $colmenas as $colmena ) {
    
                $colmena_completa = Colmena::find($colmena);
                $apiario = Apiario::where('id', $colmena_completa->apiario_id)->first();
    
                // N llamadas..... 
                $datos_actual = RevisacionTemperaturaHumedad::rango_fechas($apiario->id, $colmena, $variable, $rango_fechas, $intervalos_horarios);            
    
                $resultado_parcial = array(
                    'apiario' => $apiario,
                    'colmena' => $colmena_completa,
                    'temperatura' => $datos_actual['temperatura'],
                    'humedad' => $datos_actual['humedad'],
                );
    
                array_push($resultado, $resultado_parcial);
            }
              
            return response()->json($resultado, 200);

        }
        else {
            // :====>
        }
            
        
    }


    /**
     * Crea un archivo CSV con los datos de temperatura y humedad del apiario/colmena
     * seleccionado por el usuario. Luego retorna el archivo CSV.
     * 
     * @param int apiario
     * @param int colmena
     * @param String variable {temperatura, humedad, temperatura_y_humedad}
     * @param array tipoAccion
     * @param String variable_ambiental
     */
    public function crearCSV(Request $request) {

        // Validar que el apiario sea del usuario
        $usuario = JWTAuth::parseToken()->authenticate();
        $apiario_validar = Apiario::where('id',$request['apiario'])->where('apicultor_id',$usuario->id)->first();
        if( !$apiario_validar ) { 
            // Retornar error...
        }
        
        $apiario = $request['apiario'];
        $colmena = $request['colmena']; 
        $variable = $request['variable'];
        $tipoAccion = json_decode($request['tipoAccion'],true);
        $variable_ambiental = $request['variable_ambiental'];
        $apiario_csv = Apiario::where('id',$apiario)->first();
        $ciudad = $apiario_csv->localidad_chacra;
        $nombre_apiario = $apiario_csv->direccion_chacra." - ".$apiario_csv->nombre_fantasia; 

        $inicio = "00:00";
        $fin = "23:59";
        $rango = "60"; // Minutos

        if( $tipoAccion['horario_desde']  !=  "Todo el dia" ) {
            $inicio = $tipoAccion['horario_desde'];
            $fin = $tipoAccion['horario_hasta'];
            $rango = $tipoAccion['rango'];
        }

        $intervalos_horarios = RevisacionTemperaturaHumedad::obtenerRangoHorarios($inicio, $fin, $rango);

        /* COMPARACIÓN - DIA */
        if(  ($tipoAccion['accion'] == "Comparacion")  &&  ($tipoAccion['tipo'] == "dia") ) {
            
            $revisaciones = array();
            // Si el usuario selcciono una colmena y no todo el apiario.
            if( $request['colmena'] != null ) {
                
                $colmena_csv = Colmena::where('id', $request['colmena'])->first();
                // llamada 1: fecha pasada
                $revisaciones_pasados = RevisacionTemperaturaHumedad::comparacion_dias_csv($apiario, $colmena, $variable, $tipoAccion['fecha_pasada'], $intervalos_horarios);
                // llamada 2: fecha actual
                $revisaciones_actuales = RevisacionTemperaturaHumedad::comparacion_dias_csv($apiario, $colmena, $variable, $tipoAccion['fecha_actual'], $intervalos_horarios);
                // Junto las revisaciones
                $revisaciones = array_merge($revisaciones_pasados->toArray(), $revisaciones_actuales->toArray());

                $filename = "tweets.csv";
                $file = fopen($filename, 'w+');
                if( $variable == "temperatura" ) {
                    fputcsv($file, array("Fecha", "Hora", "Ciudad", "Apiario", "Colmena", "Tempertura"));
                    foreach( $revisaciones as $revisacion ) {
                        fputcsv($file, array($revisacion['fecha_revisacion'], $revisacion['hora_revisacion'], $ciudad, $nombre_apiario, $colmena_csv->identificacion, $revisacion['temperatura']));
                    }
                }
                elseif( $variable == "humedad" ) {
                    fputcsv($file, array("Fecha", "Hora", "Ciudad", "Apiario", "Colmena", "Humedad"));
                    foreach( $revisaciones as $revisacion ) {
                        fputcsv($file, array($revisacion['fecha_revisacion'], $revisacion['hora_revisacion'], $ciudad, $nombre_apiario, $colmena_csv->identificacion, $revisacion['humedad']));
                    }
                }
                else {
                    fputcsv($file, array("Fecha", "Hora", "Ciudad", "Apiario", "Colmena", "Tempertura", "Humedad"));
                    foreach( $revisaciones as $revisacion ) {
                        fputcsv($file, array($revisacion['fecha_revisacion'], $revisacion['hora_revisacion'], $ciudad, $nombre_apiario, $colmena_csv->identificacion, $revisacion['temperatura'], $revisacion['humedad']));
                    }
                }
                fclose($file);
                $headers = array(
                    'Content-Type' => 'text/csv',
                );
                return response()->download($filename, 'file '.date("d-m-Y H:i").'.csv', $headers);

            }
            else {
                $colmenas = Colmena::where('apiario_id',$request['apiario'])->get();
                $filename = "tweets.csv";
                $file = fopen($filename, 'w+');
                if( $variable == "temperatura" ) fputcsv($file, array("Fecha", "Hora", "Ciudad", "Apiario", "Colmena", "Tempertura"));
                else if( $variable == "humedad" ) fputcsv($file, array("Fecha", "Hora", "Ciudad", "Apiario", "Colmena", "Humedad"));
                else fputcsv($file, array("Fecha", "Hora", "Ciudad", "Apiario", "Colmena", "Tempertura", "Humedad"));
                foreach( $colmenas as $colmena ) {
                    // llamada 1: fecha pasada
                    $revisaciones_pasados = RevisacionTemperaturaHumedad::comparacion_dias_csv($apiario, $colmena->id, $variable, $tipoAccion['fecha_pasada'], $intervalos_horarios);
                    // llamada 2: fecha actual
                    $revisaciones_actuales = RevisacionTemperaturaHumedad::comparacion_dias_csv($apiario, $colmena->id, $variable, $tipoAccion['fecha_actual'], $intervalos_horarios);
                    // Junto las revisaciones
                    $revisaciones = array_merge($revisaciones_pasados->toArray(), $revisaciones_actuales->toArray());
                    if( $variable == "temperatura" ) {
                        foreach( $revisaciones as $revisacion ) {
                            fputcsv($file, array($revisacion['fecha_revisacion'], $revisacion['hora_revisacion'], $ciudad, $nombre_apiario, $colmena->identificacion, $revisacion['temperatura']));
                        }
                    }
                    elseif( $variable == "humedad" ) {
                        foreach( $revisaciones as $revisacion ) {
                            fputcsv($file, array($revisacion['fecha_revisacion'], $revisacion['hora_revisacion'], $ciudad, $nombre_apiario, $colmena->identificacion, $revisacion['humedad']));
                        }
                    }
                    else {
                        foreach( $revisaciones as $revisacion ) {
                            fputcsv($file, array($revisacion['fecha_revisacion'], $revisacion['hora_revisacion'], $ciudad, $nombre_apiario, $colmena->identificacion, $revisacion['temperatura'], $revisacion['humedad']));
                        }
                    }
                }
                fclose($file);
                    $headers = array(
                        'Content-Type' => 'text/csv',
                );
                return response()->download($filename, 'file '.date("d-m-Y H:i").'.csv', $headers);
            }

            
        } /* COMPARACIÓN - MES */
        elseif( $tipoAccion['accion'] == "Comparacion"  &&  $tipoAccion['tipo'] == "mes" ) {

            $colmena_csv = Colmena::where('id', $request['colmena'])->first();
            $revisaciones = array();
            if( $request['colmena'] != null ) { 
                // llamada 1: fecha pasada
                $revisaciones_pasados = RevisacionTemperaturaHumedad::comparacion_meses_csv($apiario, $colmena, $variable, $tipoAccion['fecha_pasada'], $intervalos_horarios);
                // llamada 2: fecha actual
                $revisaciones_actuales = RevisacionTemperaturaHumedad::comparacion_meses_csv($apiario, $colmena, $variable, $tipoAccion['fecha_actual'], $intervalos_horarios);
                // Junto las revisaciones
                $revisaciones = array_merge($revisaciones_pasados->toArray(), $revisaciones_actuales->toArray());

                $filename = "tweets.csv";
                $file = fopen($filename, 'w+');
                if( $variable == "temperatura" ) {
                    fputcsv($file, array("Fecha", "Hora", "Ciudad", "Apiario", "Colmena", "Tempertura"));
                    foreach( $revisaciones as $revisacion ) {
                        fputcsv($file, array($revisacion['fecha_revisacion'], $revisacion['hora_revisacion'], $ciudad, $nombre_apiario, $colmena_csv->identificacion, $revisacion['temperatura']));
                    }
                }
                elseif( $variable == "humedad" ) {
                    fputcsv($file, array("Fecha", "Hora", "Ciudad", "Apiario", "Colmena", "Humedad"));
                    foreach( $revisaciones as $revisacion ) {
                        fputcsv($file, array($revisacion['fecha_revisacion'], $revisacion['hora_revisacion'], $ciudad, $nombre_apiario, $colmena_csv->identificacion, $revisacion['humedad']));
                    }
                }
                else {
                    fputcsv($file, array("Fecha", "Hora", "Ciudad", "Apiario", "Colmena", "Tempertura", "Humedad"));
                    foreach( $revisaciones as $revisacion ) {
                        fputcsv($file, array($revisacion['fecha_revisacion'], $revisacion['hora_revisacion'], $ciudad, $nombre_apiario, $colmena_csv->identificacion, $revisacion['temperatura'], $revisacion['humedad']));
                    }
                }
                fclose($file);
                $headers = array(
                    'Content-Type' => 'text/csv',
                );
                return response()->download($filename, 'file '.date("d-m-Y H:i").'.csv', $headers);
            }
            else {

                $colmenas = Colmena::where('apiario_id',$request['apiario'])->get();
                $filename = "tweets.csv";
                $file = fopen($filename, 'w+');
                if( $variable == "temperatura" ) fputcsv($file, array("Fecha", "Hora", "Ciudad", "Apiario", "Colmena", "Tempertura"));
                else if( $variable == "humedad" ) fputcsv($file, array("Fecha", "Hora", "Ciudad", "Apiario", "Colmena", "Humedad"));
                else fputcsv($file, array("Fecha", "Hora", "Ciudad", "Apiario", "Colmena", "Tempertura", "Humedad"));
                foreach( $colmenas as $colmena ) {
                    // llamada 1: fecha pasada
                    $revisaciones_pasados = RevisacionTemperaturaHumedad::comparacion_meses_csv($apiario, $colmena->id, $variable, $tipoAccion['fecha_pasada'], $intervalos_horarios);
                    // llamada 2: fecha actual
                    $revisaciones_actuales = RevisacionTemperaturaHumedad::comparacion_meses_csv($apiario, $colmena->id, $variable, $tipoAccion['fecha_actual'], $intervalos_horarios);
                    // Junto las revisaciones
                    $revisaciones = array_merge($revisaciones_pasados->toArray(), $revisaciones_actuales->toArray());
                    if( $variable == "temperatura" ) {
                        foreach( $revisaciones as $revisacion ) {
                            fputcsv($file, array($revisacion['fecha_revisacion'], $revisacion['hora_revisacion'], $ciudad, $nombre_apiario, $colmena->identificacion, $revisacion['temperatura']));
                        }
                    }
                    elseif( $variable == "humedad" ) {
                        foreach( $revisaciones as $revisacion ) {
                            fputcsv($file, array($revisacion['fecha_revisacion'], $revisacion['hora_revisacion'], $ciudad, $nombre_apiario, $colmena->identificacion, $revisacion['humedad']));
                        }
                    }
                    else {
                        foreach( $revisaciones as $revisacion ) {
                            fputcsv($file, array($revisacion['fecha_revisacion'], $revisacion['hora_revisacion'], $ciudad, $nombre_apiario, $colmena->identificacion, $revisacion['temperatura'], $revisacion['humedad']));
                        }
                    }
                }
                fclose($file);
                    $headers = array(
                        'Content-Type' => 'text/csv',
                );
                return response()->download($filename, 'file '.date("d-m-Y H:i").'.csv', $headers);

            }

            
        } /* RANGO DE FECHAS */
        elseif( $tipoAccion['accion'] == "Rango" ) {
            
            $rango_fechas = RevisacionTemperaturaHumedad::getFechasInglesasParaRango( $tipoAccion );

            $revisaciones = array();
            if( $request['colmena'] != null ) {  

                $colmena_csv = Colmena::where('id', $request['colmena'])->first();
                $revisaciones = RevisacionTemperaturaHumedad::rango_fechas_csv($apiario, $colmena, $variable, $rango_fechas, $intervalos_horarios);            
                $filename = "tweets.csv";
                $file = fopen($filename, 'w+');
                if( $variable == "temperatura" ) {
                    fputcsv($file, array("Fecha", "Hora", "Ciudad", "Apiario", "Colmena", "Tempertura"));
                    foreach( $revisaciones as $revisacion ) {
                        fputcsv($file, array($revisacion['fecha_revisacion'], $revisacion['hora_revisacion'], $ciudad, $nombre_apiario, $colmena_csv->identificacion, $revisacion['temperatura']));
                    }
                }
                elseif( $variable == "humedad" ) {
                    fputcsv($file, array("Fecha", "Hora", "Ciudad", "Apiario", "Colmena", "Humedad"));
                    foreach( $revisaciones as $revisacion ) {
                        fputcsv($file, array($revisacion['fecha_revisacion'], $revisacion['hora_revisacion'], $ciudad, $nombre_apiario, $colmena_csv->identificacion, $revisacion['humedad']));
                    }
                }
                else {
                    fputcsv($file, array("Fecha", "Hora", "Ciudad", "Apiario", "Colmena", "Tempertura", "Humedad"));
                    foreach( $revisaciones as $revisacion ) {
                        fputcsv($file, array($revisacion['fecha_revisacion'], $revisacion['hora_revisacion'], $ciudad, $nombre_apiario, $colmena_csv->identificacion, $revisacion['temperatura'], $revisacion['humedad']));
                    }
                }
                fclose($file);
                $headers = array(
                    'Content-Type' => 'text/csv',
                );
                return response()->download($filename, 'file '.date("d-m-Y H:i").'.csv', $headers);

            }
            else {

                $colmenas = Colmena::where('apiario_id',$request['apiario'])->get();
                $filename = "tweets.csv";
                $file = fopen($filename, 'w+');
                if( $variable == "temperatura" ) fputcsv($file, array("Fecha", "Hora", "Ciudad", "Apiario", "Colmena", "Tempertura"));
                else if( $variable == "humedad" ) fputcsv($file, array("Fecha", "Hora", "Ciudad", "Apiario", "Colmena", "Humedad"));
                else fputcsv($file, array("Fecha", "Hora", "Ciudad", "Apiario", "Colmena", "Tempertura", "Humedad"));
                foreach( $colmenas as $colmena ) {
                    $revisaciones = RevisacionTemperaturaHumedad::rango_fechas_csv($apiario, $colmena->id, $variable, $rango_fechas, $intervalos_horarios);            
                    if( $variable == "temperatura" ) {
                        foreach( $revisaciones as $revisacion ) {
                            fputcsv($file, array($revisacion['fecha_revisacion'], $revisacion['hora_revisacion'], $ciudad, $nombre_apiario, $colmena->identificacion, $revisacion['temperatura']));
                        }
                    }
                    elseif( $variable == "humedad" ) {
                        foreach( $revisaciones as $revisacion ) {
                            fputcsv($file, array($revisacion['fecha_revisacion'], $revisacion['hora_revisacion'], $ciudad, $nombre_apiario, $colmena->identificacion, $revisacion['humedad']));
                        }
                    }
                    else {
                        foreach( $revisaciones as $revisacion ) {
                            fputcsv($file, array($revisacion['fecha_revisacion'], $revisacion['hora_revisacion'], $ciudad, $nombre_apiario, $colmena->identificacion, $revisacion['temperatura'], $revisacion['humedad']));
                        }
                    }
                }
                fclose($file);
                $headers = array(
                        'Content-Type' => 'text/csv',
                );
                return response()->download($filename, 'file '.date("d-m-Y H:i").'.csv', $headers);
            }

            

        }
        else {

            // Naranja fanta...

        }
    
    }


    public function crearColmenasCSV(Request $request) {

        // Validar que el apiario sea del usuario
        $usuario = JWTAuth::parseToken()->authenticate();
        
        $variable = $request['variable'];
        $colmenas = json_decode($request['colmenas'], true); 
        $tipoAccion = json_decode($request['tipoAccion'],true);
        
        $inicio = "00:00";
        $fin = "23:59";
        $rango = "60"; // Minutos

        if( $tipoAccion['horario_desde']  !=  "Todo el dia" ) {
            $inicio = $tipoAccion['horario_desde'];
            $fin = $tipoAccion['horario_hasta'];
            $rango = $tipoAccion['rango'];
        }

        $intervalos_horarios = RevisacionTemperaturaHumedad::obtenerRangoHorarios($inicio, $fin, $rango);

        /* COMPARACIÓN - DIA */
        if(  ($tipoAccion['accion'] == "Comparacion")  &&  ($tipoAccion['tipo'] == "dia") ) { 

            $filename = "tweets.csv";
            $file = fopen($filename, 'w+');
            if( $variable == "temperatura" ) fputcsv($file, array("Fecha", "Hora", "Ciudad", "Apiario", "Colmena", "Tempertura"));
            else fputcsv($file, array("Fecha", "Hora", "Ciudad", "Apiario", "Colmena", "Humedad"));
            
            foreach( $colmenas as $colmena ) { 

                $colmena_completa = Colmena::find($colmena);
                $apiario = Apiario::where('id', $colmena_completa->apiario_id)->first();
                $ciudad = $apiario->localidad_chacra;
                $nombre_apiario = $apiario->direccion_chacra." - ".$apiario->nombre_fantasia;

                // llamada 1: fecha pasada
                $revisaciones_pasados = RevisacionTemperaturaHumedad::comparacion_dias_csv($apiario->id, $colmena, $variable, $tipoAccion['fecha_pasada'], $intervalos_horarios);
                // llamada 2: fecha actual
                $revisaciones_actuales = RevisacionTemperaturaHumedad::comparacion_dias_csv($apiario->id, $colmena, $variable, $tipoAccion['fecha_actual'], $intervalos_horarios);
                // Junto las revisaciones
                $revisaciones = array_merge($revisaciones_pasados->toArray(), $revisaciones_actuales->toArray());

                if( $variable == "temperatura" ) {
                    foreach( $revisaciones as $revisacion ) {
                        fputcsv($file, array($revisacion['fecha_revisacion'], $revisacion['hora_revisacion'], $ciudad, $nombre_apiario, $colmena_completa->identificacion, $revisacion['temperatura']));
                    }
                }
                elseif( $variable == "humedad" ) {
                    foreach( $revisaciones as $revisacion ) {
                        fputcsv($file, array($revisacion['fecha_revisacion'], $revisacion['hora_revisacion'], $ciudad, $nombre_apiario, $colmena_completa->identificacion, $revisacion['humedad']));
                    }
                }
                else {
                    // Naranja...
                }
            }

            fclose($file);
            $headers = array(
                'Content-Type' => 'text/csv',
            );
            return response()->download($filename, 'file '.date("d-m-Y H:i").'.csv', $headers);
        }
        elseif( $tipoAccion['accion'] == "Comparacion"  &&  $tipoAccion['tipo'] == "mes" ) { 

            $filename = "tweets.csv";
            $file = fopen($filename, 'w+');
            if( $variable == "temperatura" ) fputcsv($file, array("Fecha", "Hora", "Ciudad", "Apiario", "Colmena", "Tempertura"));
            else fputcsv($file, array("Fecha", "Hora", "Ciudad", "Apiario", "Colmena", "Humedad"));
            
            foreach( $colmenas as $colmena ) { 

                $colmena_completa = Colmena::find($colmena);
                $apiario = Apiario::where('id', $colmena_completa->apiario_id)->first();
                $ciudad = $apiario->localidad_chacra;
                $nombre_apiario = $apiario->direccion_chacra." - ".$apiario->nombre_fantasia;

                // llamada 1: fecha pasada
                $revisaciones_pasados = RevisacionTemperaturaHumedad::comparacion_meses_csv($apiario->id, $colmena, $variable, $tipoAccion['fecha_pasada'], $intervalos_horarios);
                // llamada 2: fecha actual
                $revisaciones_actuales = RevisacionTemperaturaHumedad::comparacion_meses_csv($apiario->id, $colmena, $variable, $tipoAccion['fecha_actual'], $intervalos_horarios);
                // Junto las revisaciones
                $revisaciones = array_merge($revisaciones_pasados->toArray(), $revisaciones_actuales->toArray());

                if( $variable == "temperatura" ) {
                    foreach( $revisaciones as $revisacion ) {
                        fputcsv($file, array($revisacion['fecha_revisacion'], $revisacion['hora_revisacion'], $ciudad, $nombre_apiario, $colmena_completa->identificacion, $revisacion['temperatura']));
                    }
                }
                elseif( $variable == "humedad" ) {
                    foreach( $revisaciones as $revisacion ) {
                        fputcsv($file, array($revisacion['fecha_revisacion'], $revisacion['hora_revisacion'], $ciudad, $nombre_apiario, $colmena_completa->identificacion, $revisacion['humedad']));
                    }
                }
                else {
                    // Naranja...
                }
            }

            fclose($file);
            $headers = array(
                'Content-Type' => 'text/csv',
            );
            return response()->download($filename, 'file '.date("d-m-Y H:i").'.csv', $headers);


        }
        elseif( $tipoAccion['accion'] == "Rango" ) { 

            $rango_fechas = RevisacionTemperaturaHumedad::getFechasInglesasParaRango( $tipoAccion );

            $filename = "tweets.csv";
            $file = fopen($filename, 'w+');
            if( $variable == "temperatura" ) fputcsv($file, array("Fecha", "Hora", "Ciudad", "Apiario", "Colmena", "Tempertura"));
            else fputcsv($file, array("Fecha", "Hora", "Ciudad", "Apiario", "Colmena", "Humedad"));
            foreach( $colmenas as $colmena ) {
    
                $colmena_completa = Colmena::find($colmena);
                $apiario = Apiario::where('id', $colmena_completa->apiario_id)->first();
                $ciudad = $apiario->localidad_chacra;
                $nombre_apiario = $apiario->direccion_chacra." - ".$apiario->nombre_fantasia;
    
                // N llamadas..... 
                $revisaciones = RevisacionTemperaturaHumedad::rango_fechas_csv($apiario->id, $colmena, $variable, $rango_fechas, $intervalos_horarios);            
    
                if( $variable == "temperatura" ) {
                    foreach( $revisaciones as $revisacion ) {
                        fputcsv($file, array($revisacion['fecha_revisacion'], $revisacion['hora_revisacion'], $ciudad, $nombre_apiario, $colmena_completa->identificacion, $revisacion['temperatura']));
                    }
                }
                elseif( $variable == "humedad" ) {
                    foreach( $revisaciones as $revisacion ) {
                        fputcsv($file, array($revisacion['fecha_revisacion'], $revisacion['hora_revisacion'], $ciudad, $nombre_apiario, $colmena_completa->identificacion, $revisacion['humedad']));
                    }
                }
                else {
                    // Naranja...
                }
            }
    
            fclose($file);
            $headers = array(
                    'Content-Type' => 'text/csv',
            );
            return response()->download($filename, 'file '.date("d-m-Y H:i").'.csv', $headers);
        }
        else {
            // Frutilla ......
        }
    }


    /**
     * Crea y devuelve un CSV con los datos correspondientes a la cantidad de mensajes
     * recibidos por apiario/colmena en una rango de fechas dado.
     */
    public function crearSenialCSV(Request $request) {

        $usuario = JWTAuth::parseToken()->authenticate();

        $apiario = $request['apiario'];
        $colmena = $request['colmena']; 
        $tipoAccion = json_decode($request['tipoAccion'],true);
        $apiario_recibido = Apiario::where('id',$apiario)->first();
        $ciudad = $apiario_recibido->localidad_chacra;
        $nombre_apiario = $apiario_recibido->direccion_chacra." - ".$apiario_recibido->nombre_fantasia;
        
        // Intervalo Horario
        $inicio = "00:00";
        $fin = "23:59";
        $rango = "60"; // Minutos

        if( $tipoAccion['horario_desde']  !=  "Todo el dia" ) {
            $inicio = $tipoAccion['horario_desde'];
            $fin = $tipoAccion['horario_hasta'];
            $rango = $tipoAccion['rango'];
        }

        $intervalos_horarios = RevisacionTemperaturaHumedad::obtenerRangoHorarios($inicio, $fin, $rango);

        // Titulo Horario
        $horario = "00:00 - 23:59";
        if( $tipoAccion['horario_desde'] != "Todo el dia") $horario = $tipoAccion['horario_desde']." - ".$tipoAccion['horario_hasta'];

        // Obtengo el array de fechas
        $rango_fechas = RevisacionTemperaturaHumedad::getFechasInglesasParaRango( $tipoAccion );

        $filename = "tweets.csv";
        $file = fopen($filename, 'w+');
        fputcsv($file, array("Fecha", "Hora", "Ciudad", "Apiario", "Colmena", "Mensajes Recibidos"));

        // Verifico si el usuario seleccionó sólo una colmena del apiario.
        if( $colmena != null ) {
            $colmena_csv = Colmena::where('id', $colmena)->first();
            $datos = array();

            if( $tipoAccion['horario_desde']  ==  "Todo el dia" ) $datos = RevisacionTemperaturaHumedad::getSenialFechasCSV($apiario, $colmena, $rango_fechas, array());
            else $datos = RevisacionTemperaturaHumedad::getSenialFechasCSV($apiario, $colmena, $rango_fechas, $intervalos_horarios);
                
            foreach( $datos as $dato  ) {
                fputcsv($file, array($dato['fecha'], $horario, $ciudad, $nombre_apiario, $colmena_csv->identificacion, $dato['cantidad']));
            }
        }
        else {
            // Si entró aquí es porque se quiere información de cada colmena del apiario.
            $colmenas = Colmena::where('apiario_id', $apiario)->get();
            if( $tipoAccion['horario_desde']  ==  "Todo el dia" ) {
                foreach( $colmenas as $c ) {
                    $datos = RevisacionTemperaturaHumedad::getSenialFechasCSV($apiario, $c->id, $rango_fechas, array());
                    foreach( $datos as $dato  ) {
                        fputcsv($file, array($dato['fecha'], $horario, $ciudad, $nombre_apiario, $c->identificacion, $dato['cantidad']));
                    }
                }
            }
            else {
                foreach( $colmenas as $c ) {
                    $datos = RevisacionTemperaturaHumedad::getSenialFechasCSV($apiario, $c->id, $rango_fechas, $intervalos_horarios);
                    foreach( $datos as $dato  ) {
                        fputcsv($file, array($dato['fecha'], $horario, $ciudad, $nombre_apiario, $c->identificacion, $dato['cantidad']));
                    }
                }
            }
        }

        fclose($file);
        $headers = array(
                'Content-Type' => 'text/csv',
        );
        return response()->download($filename, 'file '.date("d-m-Y H:i").'.csv', $headers);

    }

    /**
     * Crea y devuelve un CSV con datos de temperatura y humedad a partir
     * de la colmena pasada como parámetro y del rango de fechas.
     * 
     */
    public function crearDatatableCSV(Request $request) {

        // Valido que la colmena y el apiario sean del apicultor.
        // Valido que el apiario sea del apicultor.
        $usuario = JWTAuth::parseToken()->authenticate();       
        $apiario_validar = Apiario::where('id',$request['apiario'])->where('apicultor_id',$usuario->id)->first();
        if( !$apiario_validar ) {
            // Return error...
        }
        $colmena_validar = Colmena::where('apiario_id',$apiario_validar->id)->where('id',$request['colmena'])->first();
        if( !$colmena_validar ) {
            // Return error...
        }
        
        $apiario = $request['apiario'];
        $colmena = $request['colmena']; 
        $tipoAccion = json_decode($request['tipoAccion'],true);

        // Obtengo el array de fechas
        $rango_fechas = RevisacionTemperaturaHumedad::getFechasInglesasParaRango( $tipoAccion );
        $apiario_seleccionado = Apiario::where('id',$apiario)->first();
        $ciudad = $apiario_seleccionado->localidad_chacra;
        $nombre_apiario = $apiario_seleccionado->direccion_chacra." - ".$apiario_seleccionado->nombre_fantasia;
        $colmena_seleccionada = Colmena::where('id',$colmena)->first();

        // Obtengo el dataset
        //$datos = RevisacionTemperaturaHumedad::obtenerTyHColmenaMerge($apiario, $colmena, $rango_fechas);
        $filename = "tweets.csv";
        $file = fopen($filename, 'w+');
        fputcsv($file, array("Fecha", "Hora", "Ciudad", "Apiario", "Colmena", "Temperatura", "Humedad"));


        foreach( $rango_fechas as $fecha ) {

            $revisaciones = RevisacionTemperaturaHumedad::where('apiario_id',$apiario)
                                                        ->where('colmena_id',$colmena)
                                                        ->where('fecha_revisacion',$fecha)
                                                        ->get();

            foreach( $revisaciones as $revisacion) {
                fputcsv($file, array($revisacion->fecha_revisacion, 
                                    $revisacion->hora_revisacion,
                                    $ciudad, 
                                    $nombre_apiario, 
                                    $colmena_seleccionada->identificacion, 
                                    $revisacion->temperatura, 
                                    $revisacion->humedad));
            }

        }

        fclose($file);
        $headers = array(
                'Content-Type' => 'text/csv',
        );
        return response()->download($filename, 'file '.date("d-m-Y H:i").'.csv', $headers);

        
    }


    public function getAllRevisacionesTyH(Request $request) {

        $ciudad = $request['ciudad'];
        $apicultor = $request['apicultor'];
        $apiario = $request['apiario'];
        $colmena = $request['colmena'];
        $tipoAccion = json_decode($request['tipoAccion'],true);

        // Obtengo el array de fechas
        $fechas = RevisacionTemperaturaHumedad::getFechasInglesasParaRango( $tipoAccion );


        $revisaciones = RevisacionTemperaturaHumedad::where('colmena_id',$colmena)
                                                    ->where('fecha_revisacion','>=',$fechas[0])
                                                    ->where('fecha_revisacion','<=',$fechas[sizeof($fechas)-1])
                                                    ->get();

        $resultado = array();
        $a = Apiario::where('id',$apiario)->first();
        $c = Colmena::where('id',$colmena)->first();
        foreach( $revisaciones as $revisacion ) {

            $parcial = array(
                "revisacion" => $revisacion,
                //"apiario" => Apiario::where('id',$revisacion->apiario_id)->first(),
                //"colmena" => Colmena::where('id',$revisacion->colmena_id)->first(),
                "apiario" => $a,
                "colmena" => $c,
            );

            array_push($resultado,$parcial);
        }

        return response()->json(  $resultado  , 200);
    }


    public function getAllRevisacionesSenal(Request $request) {

        $ciudad = $request['ciudad'];
        $apicultor = $request['apicultor'];
        $apiario = $request['apiario'];
        $colmena = $request['colmena'];
        $tipoAccion = json_decode($request['tipoAccion'],true);

        // Obtengo el array de fechas
        $fechas = RevisacionTemperaturaHumedad::getFechasInglesasParaRango( $tipoAccion );
        
        
        $revisaciones = RevisacionTemperaturaHumedad::all();

        //$fechas = $revisaciones->pluck('fecha_revisacion')->toArray();
        //$fechas = array_values(array_unique($fechas));

        //$colmenas = $revisaciones->pluck('colmena_id')->toArray();
        //$colmenas = array_values(array_unique($colmenas));

        $resultado = array();

        //foreach(  $colmenas as $colmena ) {

            //$revisacion = RevisacionTemperaturaHumedad::where('colmena_id',$colmena->id)->first();

            foreach( $fechas as $fecha ) {     
                
                $revisaciones = RevisacionTemperaturaHumedad::where('colmena_id',$colmena)->where('fecha_revisacion',$fecha)->get();  

                if( sizeof($revisaciones) == 0 ) continue;

    
                $parcial = array(
                    "apiario" => Apiario::where('id',$revisaciones[0]['apiario_id'])->first(),
                    "colmena" => Colmena::where('id',$revisaciones[0]['colmena_id'])->first(),
                    "mensajes" => sizeof($revisaciones),
                    'fecha' => $fecha,
                );
    
                array_push($resultado,$parcial);
            }
        //}

        

        return response()->json(  $resultado  , 200);

    }


    public function getAllRevisacionesSenalApicultor(Request $request) {
     
        
        $ciudad = $request['ciudad'];
        $apiario = $request['apiario'];
        $colmena = $request['colmena'];
        $tipoAccion = json_decode($request['tipoAccion'],true);

        // Obtengo el array de fechas
        $fechas = RevisacionTemperaturaHumedad::getFechasInglesasParaRango( $tipoAccion );
        $fechas = array_reverse($fechas);
        
        
        

        //$fechas = $revisaciones->pluck('fecha_revisacion')->toArray();
        //$fechas = array_values(array_unique($fechas));

        //$colmenas = $revisaciones->pluck('colmena_id')->toArray();
        //$colmenas = array_values(array_unique($colmenas));

        $resultado = array();

        //foreach(  $colmenas as $colmena ) {

            //$revisacion = RevisacionTemperaturaHumedad::where('colmena_id',$colmena->id)->first();

            foreach( $fechas as $fecha ) {     
                
                $revisaciones = RevisacionTemperaturaHumedad::where('colmena_id',$colmena)->where('fecha_revisacion',$fecha)->get();  

                if( sizeof($revisaciones) == 0 ) continue;

    
                $parcial = array(
                    "apiario" => Apiario::where('id',$revisaciones[0]['apiario_id'])->first(),
                    "colmena" => Colmena::where('id',$revisaciones[0]['colmena_id'])->first(),
                    "mensajes" => sizeof($revisaciones),
                    'fecha' => $fecha,
                );
    
                array_push($resultado,$parcial);
            }
        //}

        

        return response()->json(  $resultado  , 200);
    }


    public function getDetalleSenialDiaria(Request $request) {

        $fecha = $request['fecha'];
        $colmena_id = $request['colmena_id'];

        $revisaciones = RevisacionTemperaturaHumedad::where('colmena_id',$colmena_id)->where('fecha_revisacion',$fecha)->orderBy('hora_revisacion','desc')->get();

        return response()->json(  $revisaciones  , 200);
    }

}

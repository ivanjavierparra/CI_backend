<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use \DatePeriod;
use \DateTime;
use \DateInterval;
use App\Colmena;
use App\Apiario;
use App\RevisacionTemperaturaHumedad;
use App\Clima;

class Script extends Model
{
    
    public static function crearRevisacion($fecha_desde, $fecha_hasta = '') {
                
        $fechas = RevisacionTemperaturaHumedad::getRangoFechasInglesas($fecha_desde, $fecha_hasta);

        $MAX_SMS_DIA = 24;

        //$HORARIO_SMS = array("06:00","10:00","14:00","18:00","22:00");
        $HORARIO_SMS = array(
            "00:00","01:00","02:00","03:00","04:00","05:00",
            "06:00","07:00","08:00","09:00","10:00","11:00",
            "12:00","13:00","14:00","15:00","16:00","17:00",
            "18:00","19:00","20:00","21:00","22:00","23:00"
        );
      
        foreach( $fechas as $fecha ) {
            
            $apiarios = Apiario::all();

            foreach( $apiarios as $apiario ) {

                $colmenas = Colmena::where("apiario_id",$apiario->id)->get();

                if( sizeof( $colmenas ) == 0 ) continue;

                $PORCENTAJE_VERDE = mt_rand(70,80);

                $VERDE = (int)floor($PORCENTAJE_VERDE * sizeof($colmenas) / 100); 
                $subtotal = sizeof($colmenas) - $VERDE;
                $AMARILLO = mt_rand(1,$subtotal); 
                $ROJO = $subtotal - $AMARILLO;

                echo "[Colores][".$VERDE."][".$AMARILLO."][".$ROJO."]\n";

                $arreglo_ids_colmenas = $colmenas->pluck("id")->toArray();

                for( $i = 0; $i < $VERDE; $i++ ) {
                    // Debo elegir un id al azar del arreglo
                    // busco temporada a partir de la fecha
                    // creo revisacion
                    // elimino id del arreglo 

                    echo "[verde][".$i."]\n";
                    echo "[array]".json_encode($arreglo_ids_colmenas)."\n";

                    $index = mt_rand(0,sizeof($arreglo_ids_colmenas) - 1);
                    
                    $colmena_id = $arreglo_ids_colmenas[$index];

                    for( $j = 0; $j < $MAX_SMS_DIA; $j++ ) {
                        
                        Script::crear_revisacion_buen_estado($apiario->id, $colmena_id, $fecha, $HORARIO_SMS[$j]);
                    }

                    unset($arreglo_ids_colmenas[$index]);
                    $arreglo_ids_colmenas = array_values($arreglo_ids_colmenas);
                }
            
                

                for( $i = 0; $i < $AMARILLO; $i++ ) {

                    echo "[amarillo][".$i."]\n";
                    
                    $index = mt_rand(0,sizeof($arreglo_ids_colmenas) - 1);
                    
                    $colmena_id = $arreglo_ids_colmenas[$index];

                    for( $j = 0; $j < $MAX_SMS_DIA; $j++ ) {
                        
                        Script::crear_revisacion_en_alerta($apiario->id, $colmena_id, $fecha, $HORARIO_SMS[$j]);
                    }

                    unset($arreglo_ids_colmenas[$index]);
                    $arreglo_ids_colmenas = array_values($arreglo_ids_colmenas);
                }

                for( $i = 0; $i < $ROJO; $i++ ) {

                    echo "[rojo][".$i."]\n";
                    
                    $index = mt_rand(0,sizeof($arreglo_ids_colmenas) - 1);
                    
                    $colmena_id = $arreglo_ids_colmenas[$index];

                    for( $j = 0; $j < $MAX_SMS_DIA; $j++ ) {
                        
                        Script::crear_revisacion_en_peligro($apiario->id, $colmena_id, $fecha, $HORARIO_SMS[$j]);
                    }

                    unset($arreglo_ids_colmenas[$index]);
                    $arreglo_ids_colmenas = array_values($arreglo_ids_colmenas);
                }
            }

        }
    }


    /**
     * Creación de revisaciones para todas las colmenas registradas, con un 70% de las colmenas
     * del apiario con "buen estado".
     * @param fecha_desde "Y-m-d"
     * @param fecha_hasta "Y-m-d"
     */
    public static function crearRevisacionAutomatica($fecha_desde, $fecha_hasta) {
        $apiarios = Apiario::all();

        foreach( $apiarios as $apiario ) {
            echo "Creando revisaciones para Apiario ".$apiario->direccion_chacra."\n";
            Script::crearRevisacionApiario($apiario->id, $fecha_desde, $fecha_hasta);
        }
    }


    /**
     * Creación de revisaciones para un apiario en el rango de fechas ingresado,
     * con una sola revisacion en el día que tiene mal estado (alerta/peligro).
     * @param apiario_id int
     * @param fecha_desde "Y-m-d"
     * @param fecha_hasta "Y-m-d"
     */
    public static function crearRevisacionApiarioNormal($apiario_id, $fecha_desde, $fecha_hasta = '') {

        $colmenas = Colmena::where("apiario_id",$apiario_id)->get();
        if( sizeof( $colmenas ) == 0 ) return;
        
        $fechas = RevisacionTemperaturaHumedad::getRangoFechasInglesas($fecha_desde, $fecha_hasta);

        $HORARIO_SMS = array(
            "00:00","01:00","02:00","03:00","04:00","05:00",
            "06:00","07:00","08:00","09:00","10:00","11:00",
            "12:00","13:00","14:00","15:00","16:00","17:00",
            "18:00","19:00","20:00","21:00","22:00","23:00"
        );

        foreach ($fechas as $fecha) {

            foreach( $colmenas as $colmena ) {
                
                # Horario al azar donde la revisacion será Alerta o Peligro.
                $horario_mal_estado = mt_rand(0,23);
                $mal_estado = mt_rand(0,1); # 0 = Alerta || 1 = Peligro.

                # Si la fecha es la de hoy, creo revisacion hasta la hora actual.
                $fecha_de_hoy = date("Y-m-d");
                $limite = sizeof($HORARIO_SMS);
                if( $fecha == $fecha_de_hoy ) $limite = date("H");

                for( $i = 0; $i < $limite; $i++ ) {

                    if( $i == $horario_mal_estado ) {
                        if( $mal_estado == 0 ) { echo "Creación de revisación alerta [".$HORARIO_SMS[$i]."] \n"; Script::crear_revisacion_en_alerta($apiario_id, $colmena->id, $fecha, $HORARIO_SMS[$i]);}
                        else {echo "Creación de revisación peligro [".$HORARIO_SMS[$i]."] \n"; Script::crear_revisacion_en_peligro($apiario_id, $colmena->id, $fecha, $HORARIO_SMS[$i]);}
                    }

                    Script::crear_revisacion_buen_estado($apiario_id, $colmena->id, $fecha, $HORARIO_SMS[$i]);
                }
            }
        }
    }

    /**
     * Creación de revisaciones para todos los apiarios, en el rango
     * de fechas ingresados por parámetros.
     * @param fecha_desde "Y-m-d"
     * @param fecha_hasta "Y-m-d"
     */
    public static function medicionesApiario($fecha_desde, $fecha_hasta = '') {
        $apiarios = Apiario::all();

        foreach( $apiarios as $apiario ) {
            echo "Creando revisaciones para Apiario ".$apiario->direccion_chacra."\n";
            Script::crearRevisacionApiarioNormal($apiario->id, $fecha_desde, $fecha_hasta);
        }
    }

    
    /**
     * Elimino todas las revisaciones existentes.
     */
    public static function eliminarMediciones() {
        // $apiarios = (Apiario::where("apicultor_id",1)->get())->pluck("id");
        // $colmenas = (Colmena::where("apiario_id",$apiarios)->get())->pluck("id");
        $fecha_limite = strtotime("2018-01-01");
        $revisaciones = RevisacionTemperaturaHumedad::all();
        echo "[Inicio] \n";
        foreach( $revisaciones as $revisacion ) {
            $fecha_revisacion= strtotime($revisacion->fecha_revisacion);
            if($fecha_revisacion < $fecha_limite) continue;
            $revisacion->delete();
            echo "[Eliminado] ".$revisacion->id."\n";
        }
        echo "[Fin] \n";
    }

    /**
     * Creacion de revisaciones para un apiario con 70% de las colmenas en buen estado.
     * @param apiario_id int
     * @param fecha_desde "Y-m-d"
     * @param fecha_hasta "Y-m-d"
     */
    public static function crearRevisacionApiario($apiario_id, $fecha_desde, $fecha_hasta = '') {
                
        $fechas = RevisacionTemperaturaHumedad::getRangoFechasInglesas($fecha_desde, $fecha_hasta);

        $MAX_SMS_DIA = 24;

        //$HORARIO_SMS = array("06:00","10:00","14:00","18:00","22:00");
        $HORARIO_SMS = array(
            "00:00","01:00","02:00","03:00","04:00","05:00",
            "06:00","07:00","08:00","09:00","10:00","11:00",
            "12:00","13:00","14:00","15:00","16:00","17:00",
            "18:00","19:00","20:00","21:00","22:00","23:00"
        );
      
        foreach( $fechas as $fecha ) {
            
            $colmenas = Colmena::where("apiario_id",$apiario_id)->get();

            if( sizeof( $colmenas ) == 0 ) return;

            $PORCENTAJE_VERDE = mt_rand(70,80);

            $VERDE = (int)floor($PORCENTAJE_VERDE * sizeof($colmenas) / 100); 
            $subtotal = sizeof($colmenas) - $VERDE;
            $AMARILLO = mt_rand(1,$subtotal); 
            $ROJO = $subtotal - $AMARILLO;

            echo "[Colores][".$VERDE."][".$AMARILLO."][".$ROJO."]\n";

            $arreglo_ids_colmenas = $colmenas->pluck("id")->toArray();

            for( $i = 0; $i < $VERDE; $i++ ) {
                // Debo elegir un id al azar del arreglo
                // busco temporada a partir de la fecha
                // creo revisacion
                // elimino id del arreglo 

                echo "[verde][".$i."]\n";
                echo "[array]".json_encode($arreglo_ids_colmenas)."\n";

                $index = mt_rand(0,sizeof($arreglo_ids_colmenas) - 1);
                
                $colmena_id = $arreglo_ids_colmenas[$index];

                for( $j = 0; $j < $MAX_SMS_DIA; $j++ ) {
                    
                    Script::crear_revisacion_buen_estado($apiario_id, $colmena_id, $fecha, $HORARIO_SMS[$j]);
                }

                unset($arreglo_ids_colmenas[$index]);
                $arreglo_ids_colmenas = array_values($arreglo_ids_colmenas);
            }
        
            

            for( $i = 0; $i < $AMARILLO; $i++ ) {

                echo "[amarillo][".$i."]\n";
                
                $index = mt_rand(0,sizeof($arreglo_ids_colmenas) - 1);
                
                $colmena_id = $arreglo_ids_colmenas[$index];

                for( $j = 0; $j < $MAX_SMS_DIA; $j++ ) {
                    
                    Script::crear_revisacion_en_alerta($apiario_id, $colmena_id, $fecha, $HORARIO_SMS[$j]);
                }

                unset($arreglo_ids_colmenas[$index]);
                $arreglo_ids_colmenas = array_values($arreglo_ids_colmenas);
            }

            for( $i = 0; $i < $ROJO; $i++ ) {

                echo "[rojo][".$i."]\n";
                
                $index = mt_rand(0,sizeof($arreglo_ids_colmenas) - 1);
                
                $colmena_id = $arreglo_ids_colmenas[$index];

                for( $j = 0; $j < $MAX_SMS_DIA; $j++ ) {
                    
                    Script::crear_revisacion_en_peligro($apiario_id, $colmena_id, $fecha, $HORARIO_SMS[$j]);
                }

                unset($arreglo_ids_colmenas[$index]);
                $arreglo_ids_colmenas = array_values($arreglo_ids_colmenas);
            }
            

        }
    }


    /**
     * Creación de revisaciones para una colmena.
     * El estado es al azar por cada hora de cada día o ingresado por el usuario.
     * @param apiario_id int
     * @param colmena_id int
     * @param fecha_desde "Y-m-d"
     * @param fecha_hasta "Y-m-d"
     * @param estado {"", "verde", "amarillo", "rojo"}
     */
    public static function crearRevisacionColmena($apiario_id, $colmena_id, $fecha_desde, $fecha_hasta = '', $estado = '') {
                
        $fechas = RevisacionTemperaturaHumedad::getRangoFechasInglesas($fecha_desde, $fecha_hasta);

        $MAX_SMS_DIA = 24;

        //$HORARIO_SMS = array("06:00","10:00","14:00","18:00","22:00");
        $HORARIO_SMS = array(
            "00:00","01:00","02:00","03:00","04:00","05:00",
            "06:00","07:00","08:00","09:00","10:00","11:00",
            "12:00","13:00","14:00","15:00","16:00","17:00",
            "18:00","19:00","20:00","21:00","22:00","23:00"
        );

        

        $colmena = Colmena::where("apiario_id",$apiario_id)->where("id",$colmena_id)->first();

        if( !$colmena ) return;

        // El usuario no eligió estado, entonces es al azar.
        if( $estado == '' ) { 
            
            foreach( $fechas as $fecha ) {

                    for( $j = 0; $j < $MAX_SMS_DIA; $j++ ) {
                        
                        $estado = mt_rand(1,3);

                        if( $estado == 1 ) { // verde
                            Script::crear_revisacion_buen_estado($apiario_id, $colmena_id, $fecha, $HORARIO_SMS[$j]);
                        }
                        elseif( $estado == 2 ) { // amarillo
                            Script::crear_revisacion_en_alerta($apiario_id, $colmena_id, $fecha, $HORARIO_SMS[$j]);
                        }
                        elseif( $estado == 3 ) { // rojo
                            Script::crear_revisacion_en_peligro($apiario_id, $colmena_id, $fecha, $HORARIO_SMS[$j]);
                        }
                        else {
                            // ...
                        }
                    }
            }
        }
        else {
            // El usuario eligio el estado que quiere para la colmena.
            foreach( $fechas as $fecha ) {

                if( $estado == "verde" ) {
                    for( $j = 0; $j < $MAX_SMS_DIA; $j++ ) {
                        Script::crear_revisacion_buen_estado($apiario_id, $colmena_id, $fecha, $HORARIO_SMS[$j]);
                    }
                }
                elseif( $estado == "amarillo" ) {
                    for( $j = 0; $j < $MAX_SMS_DIA; $j++ ) {
                        Script::crear_revisacion_en_alerta($apiario_id, $colmena_id, $fecha, $HORARIO_SMS[$j]);
                    }
                }
                elseif( $estado == "rojo" ) {
                    for( $j = 0; $j < $MAX_SMS_DIA; $j++ ) {
                        Script::crear_revisacion_en_peligro($apiario_id, $colmena_id, $fecha, $HORARIO_SMS[$j]);
                    }
                }
                else {
                    // ...
                }
            }
        }
    }



    /** 
     * El estado de tyh lo defino yo x veces al día.
    */
    public static function crearRevisacionColmena_conEstados($apiario_id, $colmena_id, $fecha_desde, $fecha_hasta = '') {
                
        $fechas = RevisacionTemperaturaHumedad::getRangoFechasInglesas($fecha_desde, $fecha_hasta);

        $MAX_SMS_DIA = 24;

        $CANTIDAD_ROJOS_DIA = 1;
        $CANTIDAD_AMARILLOS_DIA = 1;

        # Horarios de revisaciones.
        $HORARIO_SMS = array(
            "00:00","01:00","02:00","03:00","04:00","05:00",
            "06:00","07:00","08:00","09:00","10:00","11:00",
            "12:00","13:00","14:00","15:00","16:00","17:00",
            "18:00","19:00","20:00","21:00","22:00","23:00"
        );

        
        $colmena = Colmena::where("apiario_id",$apiario_id)->where("id",$colmena_id)->first();

        if( !$colmena ) return;
        
        foreach( $fechas as $fecha ) {

                // inicializacion
                $array_rojos = array();
                $array_amarillos = array();
        

                // Completo array de rojos con valores entre 0 y 23.
                for( $i = 0; $i < $CANTIDAD_ROJOS_DIA; $i++ ) {
                    $bandera = true;
                    do {
                        
                        $index = mt_rand(0,$MAX_SMS_DIA-1);  
                        if (!in_array($index,$array_rojos)) {
                            $bandera = false;
                            array_push($array_rojos,$index);
                        }
                        
                    } while($bandera);
                }


                // Completo array de amarillos con valores entre 0 y 23.
                for( $i = 0; $i < $CANTIDAD_AMARILLOS_DIA; $i++ ) {
                    $bandera = true;
                    do {
                        
                        $index = mt_rand(0,$MAX_SMS_DIA-1);  
                        if (!in_array($index,$array_rojos)) {
                            $bandera = false;
                            array_push($array_amarillos,$index);
                        }
                        
                    } while($bandera);
                }


                // Creo las revisaciones
                for( $j = 0; $j < $MAX_SMS_DIA; $j++ ) {
                    
                    if( in_array($j, $array_rojos) ) { // verde
                        Script::crear_revisacion_en_peligro($apiario_id, $colmena_id, $fecha, $HORARIO_SMS[$j]);
                    }
                    elseif( in_array($j, $array_amarillos) ) { // amarillo
                        Script::crear_revisacion_en_alerta($apiario_id, $colmena_id, $fecha, $HORARIO_SMS[$j]);
                    }
                    else {
                        Script::crear_revisacion_buen_estado($apiario_id, $colmena_id, $fecha, $HORARIO_SMS[$j]);
                    }
                }
        }
    }


    public static function crear_revisacion_buen_estado($apiario_id, $colmena_id, $fecha, $hora) {

        // obtengo temporada
        // random temperatura en buen estado
        // random humedad en buen estado
        // creo revisacion

        $temporada = RevisacionTemperaturaHumedad::getTemporada($fecha);
        $temperatura = 0;
        if( $temporada == "otonio" || $temporada == "invierno" ) {
            $temperatura = mt_rand(18, 36) + round(mt_rand() / mt_getrandmax(),2);
        }
        else {
            $temperatura = mt_rand(34, 36) + round(mt_rand() / mt_getrandmax(),2);
            if( $temperatura < 34.0 ) $temperatura = 34.00;
            elseif( $temperatura > 36.0 ) $temperatura = 36.00;
        }

        echo $temperatura."\n";

        $revisacion = RevisacionTemperaturaHumedad::create(array(
            'apiario_id' => $apiario_id,
            'colmena_id' => $colmena_id,
            'temperatura' => $temperatura,
            'humedad' => mt_rand(65, 75) + round(mt_rand() / mt_getrandmax(),2),
            'fecha_revisacion' => $fecha,
            'hora_revisacion' => $hora
        ));
    }


    public static function crear_revisacion_en_alerta($apiario_id, $colmena_id, $fecha, $hora) {

        # obtengo temporada
        # random temperatura en buen estado
        # random humedad en buen estado
        # creo revisacion

        $temporada = RevisacionTemperaturaHumedad::getTemporada($fecha);
        $temperatura = 0;
        if( $temporada == "otonio" || $temporada == "invierno" ) {
            $opcion = mt_rand(1,2);
            if( $opcion == 1 ) {
                $temperatura = mt_rand(14, 18) + round(mt_rand() / mt_getrandmax(),2);
                if( $temperatura < 14.5 ) $temperatura = 14.50;
            }
            else {
                $temperatura = 36 + round(mt_rand() / mt_getrandmax(),2);
                if( $temperatura > 36.5 ) $temperatura = 36.50;
            }            
        }
        else {
            $opcion = mt_rand(1,2);
            if( $opcion == 1 ) {
                $temperatura = mt_rand(33, 34) + round(mt_rand() / mt_getrandmax(),2);
                if( $temperatura < 33.5 ) $temperatura = 33.50;
            }
            else {
                $temperatura = 36 + round(mt_rand() / mt_getrandmax(),2);
                if( $temperatura > 36.5 ) $temperatura = 36.50;
            }            
        }

        echo $temperatura."\n";

        $revisacion = RevisacionTemperaturaHumedad::create(array(
            'apiario_id' => $apiario_id,
            'colmena_id' => $colmena_id,
            'temperatura' => $temperatura,
            'humedad' => mt_rand(65, 75) + round(mt_rand() / mt_getrandmax(),2),
            'fecha_revisacion' => $fecha,
            'hora_revisacion' => $hora
        ));
    }


    public static function crear_revisacion_en_peligro($apiario_id, $colmena_id, $fecha, $hora) {

        // obtengo temporada
        // random temperatura en buen estado
        // random humedad en buen estado
        // creo revisacion

        $temporada = RevisacionTemperaturaHumedad::getTemporada($fecha);
        $temperatura = 0;
        if( $temporada == "otonio" || $temporada == "invierno" ) {
            $opcion = mt_rand(1,2);
            if( $opcion == 1 ) {
                $temperatura = mt_rand(0, 14) + round(mt_rand() / mt_getrandmax(),2);
                if( $temperatura > 14.5 ) $temperatura = 14.50;
            }
            else {
                $temperatura = mt_rand(36, 46) + round(mt_rand() / mt_getrandmax(),2);
                if( $temperatura < 36.5 ) $temperatura = 36.60;
            }            
        }
        else {
            $opcion = mt_rand(1,2);
            if( $opcion == 1 ) {
                $temperatura = mt_rand(25, 33) + round(mt_rand() / mt_getrandmax(),2);
                if( $temperatura > 33.5 ) $temperatura = 33.40;
            }
            else {
                $temperatura = mt_rand(36, 46) + round(mt_rand() / mt_getrandmax(),2);
                if( $temperatura < 36.5 ) $temperatura = 36.60;
            }            
        }

        echo $temperatura."\n";

        $revisacion = RevisacionTemperaturaHumedad::create(array(
            'apiario_id' => $apiario_id,
            'colmena_id' => $colmena_id,
            'temperatura' => $temperatura,
            'humedad' => mt_rand(65, 75) + round(mt_rand() / mt_getrandmax(),2),
            'fecha_revisacion' => $fecha,
            'hora_revisacion' => $hora
        ));
    }

   
    
    public static function crearColmenas($apiario_id, $cantidad_de_colmenas) {

        $ultima_colmena = Colmena::where('apiario_id',$apiario_id)->orderBy('id','desc')->first();

        $nuevo_id = 1;

        if( $ultima_colmena ) $ultima_colmena->id + 1;

        $RAZAS = array("Caucasica", "Italiana", "Buckfast", "Carniola", "Otros");
        
        for( $i = 0; $i < $cantidad_de_colmenas; $i++ ) {

            $index_raza = mt_rand(0,4);
            
            $colmena = Colmena::create(array(
                'apiario_id' => $apiario_id,
                'raza_abeja' => $RAZAS[$index_raza],
                'identificacion' => "C".$nuevo_id,
                'fecha_creacion' => Date("Y-m-d"),
                'descripcion' => "Sin descripcion.",
                'eliminado' => false,
            ));

            $nuevo_id++;
        }
    }


    public static function crearApiarios($ciudad, $cantidad_de_apiarios, $apicultor_id = '', $cantidad_de_colmenas = '') {
        
        $apicultores = User::where("role",'Beekeeper')->get();
        $apicultores = $apicultores->pluck("id")->toArray();

        $ciudades = array(
            "Trelew" => array(-43.273208786647814, -65.31430221468997),
            "Rawson" => array(-43.299273018873976, -65.09775433567452),
            "Gaiman" => array(-43.29717412521412, -65.4957083227961),
            "Dolavon" => array(-43.31225573054642, -65.70283875155955),
            "28 de Julio" => array(-43.460855176822086, -66.11666821980911),
        );

        if( $apicultor_id == '' ) {
            for( $i = 0; $i < $cantidad_de_apiarios; $i++ ) { 

                $chacra = "Chacra ".mt_rand(1,1000);
                $latitud = 

                $apiario = Apiario::create(array(
                    'apicultor_id' => $apicultor_id,
                    'nombre_fantasia' => $chacra,
                    'latitud' => $ciudades[$ciudad][0] + ( $i / 100000),
                    'longitud' => $ciudades[$ciudad][1] + ( $i / 100000),
                    'fecha_creacion' => Date("Y-m-d"),
                    'descripcion' => "Sin descripcion.",
                    'localidad_chacra' => $ciudad,
                    'direccion_chacra' => $chacra,
                    'propietario_chacra' => "N/N",
                    'eliminado' => false,
                ));

                if( $cantidad_de_colmenas != '' ) {
                    Script::crearColmenas($apiario->id, $cantidad_de_colmenas);
                }
            }
        }
        else {
            for( $i = 0; $i < $cantidad_de_apiarios; $i++ ) { 

                $chacra = "Chacra ".mt_rand(1,1000);

                $apiario = Apiario::create(array(
                    'apicultor_id' => $apicultores[mt_rand(0,sizeof($apicultores)-1)],
                    'nombre_fantasia' => $chacra,
                    'latitud' => $ciudades[$ciudad][0] + ( $i / 100000),
                    'longitud' => $ciudades[$ciudad][1] + ( $i / 100000),
                    'fecha_creacion' => Date("Y-m-d"),
                    'descripcion' => "Sin descripcion.",
                    'localidad_chacra' => $ciudad,
                    'direccion_chacra' => $chacra,
                    'propietario_chacra' => "N/N",
                    'eliminado' => false,
                ));

                if( $cantidad_de_colmenas != '' ) {
                    Script::crearColmenas($apiario->id, $cantidad_de_colmenas);
                }
            }
        }
    }


    public static function crearClima($ciudad, $fecha_desde, $fecha_hasta) {

        $fechas = RevisacionTemperaturaHumedad::getRangoFechasInglesas($fecha_desde, $fecha_hasta);

        $MAX_SMS_DIA = 24;

        $HORARIO_SMS = array(
            "00:00","01:00","02:00","03:00","04:00","05:00",
            "06:00","07:00","08:00","09:00","10:00","11:00",
            "12:00","13:00","14:00","15:00","16:00","17:00",
            "18:00","19:00","20:00","21:00","22:00","23:00"
        );

        $DESCRIPCION = array(
            "Soleado",
            "Parcialmente soleado",
            "Mayormente soleado",
            "Ventoso",
            "Lluvioso",
            "Nublado",
            "Parcilamente nublado",
            "Mayormente nublado"
        );
      
        foreach( $fechas as $fecha ) {

            for( $j = 0; $j < $MAX_SMS_DIA; $j++ ) {
                
                $temperatura_minima = mt_rand(0, 20) + round(mt_rand() / mt_getrandmax(),2);

                $clima = Clima::create(array(
                    'ciudad' => $ciudad,
                    'fecha' => $fecha,
                    'hora' => $HORARIO_SMS[$j],
                    'descripcion' => "Soleado.",
                    'temperatura' => mt_rand(0, 20) + round(mt_rand() / mt_getrandmax(),2),
                    'sensacion_termica' => mt_rand(0, 20) + round(mt_rand() / mt_getrandmax(),2),
                    'humedad' => mt_rand(0, 100) + round(mt_rand() / mt_getrandmax(),2),
                    'direccion_del_viento' => "W",
                    'velocidad_del_viento_km_hs' => mt_rand(10, 100) + round(mt_rand() / mt_getrandmax(),2),
                    'presion_hpa' => mt_rand(900, 1000) + round(mt_rand() / mt_getrandmax(),2),
                    'temperatura_minima' => $temperatura_minima,
                    'temperatura_maxima' => $temperatura_minima + mt_rand(0, 10) + round(mt_rand() / mt_getrandmax(),2),
                    'sensacion_termica_minima' => $temperatura_minima,
                    'sensacion_termica_maxima' => $temperatura_minima + mt_rand(0, 10) + round(mt_rand() / mt_getrandmax(),2),
                    'horas_de_sol' => mt_rand(6, 9) + round(mt_rand() / mt_getrandmax(),2),
                    'descripcion_dia' => $DESCRIPCION[mt_rand(0,7)],
                    'descripcion_noche' => $DESCRIPCION[mt_rand(0,7)],
                ));
            }
        }
    }

    /**
     * Creación de climas para el VIRCH.
     */
    public static function createClimaVirch($fecha_desde, $fecha_hasta = '') {
        
        $ciudades = array(
            "Rawson",
            "Trelew",
            "Gaiman",
            "Dolavon",
            "28 de Julio"
        );

        foreach($ciudades as $ciudad) {

            echo "[".$ciudad."] \n";
            Script::crearClima($ciudad, $fecha_desde, $fecha_hasta);
        }
    }

    /**
     * Se eliminan todas las notificaciones.
     */
    public static function eliminarClima() {
        
        $climas = Clima::all();

        $fecha_limite = strtotime("2018-01-01");

        echo "[Inicio] \n";
        foreach( $climas as $clima ) {
            $fecha_clima = strtotime($clima->fecha );
            if($fecha_clima  < $fecha_limite) continue;
            $clima->delete();
            echo "[Eliminado] ".$clima->id."\n";
        }
        echo "[Fin] \n";
    }

    /**
     * Método para testear la función "getColorSenial()".
     * @param fyhs String fecha y hora con formato "2020-10-04 16:00"
     * @return colorSenial
     */
    public static function prueba($fyhs) {
        $horaRevisacion = new DateTime($fyhs); // $fyhs = "2020-10-04 16:00"
        $horaActual = new DateTime();
        $diferencia = $horaRevisacion->diff($horaActual);
        $horas = $diferencia->days * 24 * 60;
        $horas += $diferencia->h * 60;
        $horas += $diferencia->i;
        $horas = $horas / 60;
        echo $horas."\n";
        # Si la diferencia horaria entre la hora actual y la hora revisacion es menor a 6 horas, entonces verde. Ej.: hora_revisacion = 23:00 de ayer y hora_actual = 03:00 de hoy.
        if( $horas <= 2 ) return "verde"; // 6
        elseif( $horas > 2 && $horas <= 4 ) return "amarillo"; // Entre 6 y 12
        else return "rojo";
    }

    public static function compararHorarios($fhs1, $fhs2) {
        $fechaHoraUltimaRevisacion = new DateTime($fhs1);
        $fechaHoraNuevaRevisacion = new DateTime($fhs2);
        //$diferencia = $fechaHoraUltimaRevisacion->diff($fechaHoraNuevaRevisacion);
        if( $fechaHoraUltimaRevisacion == $fechaHoraNuevaRevisacion ) return 0;
        if( $fechaHoraUltimaRevisacion > $fechaHoraNuevaRevisacion ) return -1;
        return 1;
    }
}

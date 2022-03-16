<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Clima extends Model
{
    protected $table = 'climas';
        
    protected $primaryKey = 'id';

    protected $fillable = array(
        'ciudad',
        'fecha',
        'hora',
        'descripcion',
        'temperatura',
        'sensacion_termica',
        'humedad',
        'direccion_del_viento',
        'velocidad_del_viento_km_hs',
        'presion_hpa',
        'temperatura_minima',
        'temperatura_maxima',
        'sensacion_termica_minima',
        'sensacion_termica_maxima',
        'horas_de_sol',
        'descripcion_dia',
        'descripcion_noche',
    );
            

    /**
     * Obtiene el clima de la ciudad pasada como parámetro haciendo dos consultas
     * a la API de Accuweather. Crea una instancia de Clima.
     * 
     * @param String $ciudad {Trelew, Gaiman, Dolavon, 28 de Julio}
     * @param int $ciudad_key {es el número de identificación de la ciudad dentro de la API de Accuweather}
     * 
     */
    public static function obtenerClima($ciudad, $ciudad_key) {

        // API_KEY_ACCUWEATHER
        $KEY = "HmNCgt37q5wVuKhJroHAGpqnStSjzN0A";

        $datos_actuales = Clima::obtenerClimaActual($KEY, $ciudad, $ciudad_key);
        $datos_diarios = Clima::obtenerClimaDiario($KEY, $ciudad, $ciudad_key);

        $resultado = array_merge($datos_actuales, $datos_diarios);

        // Creo el chat.
        $clima = Clima::create($resultado);
    }


    /**
     * Obtengo los datos actuales del clima: temperatura, sensación térmica, humedad, etc.
     * 
     * @param String $KEY {Es la key_app de Accuweather}
     * @param String $ciudad {Trelew, Gaiman, Dolavon, 28 de Julio}
     * @param int $ciudad_key {id de la ciudad en Accuweather}
     * @return Array {datos}
     * 
     */
    public static function obtenerClimaActual($KEY, $ciudad, $ciudad_key) {
        $URL = "http://dataservice.accuweather.com/currentconditions/v1/".$ciudad_key."?apikey=".$KEY."&language=es-es&details=true";
        
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_TIMEOUT => 30000,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
            ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
            
            return array();

        } else { 
            $respuesta = array(
                'ciudad' => $ciudad,
                'fecha' => "",
                'hora' => "",
                'descripcion' => "",
                'temperatura' => "",
                'sensacion_termica' => "",
                'humedad' => "",
                'direccion_del_viento' => "",
                'velocidad_del_viento_km_hs' => "",
                'presion_hpa' => "",
            );

            $datos = json_decode($response,true);
            $datos = $datos[0];

            // Recorro los datos
            $respuesta['fecha'] = Clima::procesarFechaHora($datos["LocalObservationDateTime"])[0];
            $respuesta['hora'] = Clima::procesarFechaHora($datos["LocalObservationDateTime"])[1];
            $respuesta['descripcion'] = $datos['WeatherText'];
            $respuesta['temperatura'] = $datos['Temperature']['Metric']['Value'];
            $respuesta['sensacion_termica'] = $datos['RealFeelTemperature']['Metric']['Value'];
            $respuesta['humedad'] = $datos['RelativeHumidity'];
            $respuesta['direccion_del_viento'] = $datos['Wind']['Direction']['English'];
            $respuesta['velocidad_del_viento_km_hs'] = $datos['Wind']['Speed']['Metric']['Value'];
            $respuesta['presion_hpa'] = $datos['Pressure']['Metric']['Value'];

            return $respuesta;
        }

    }


    /**
     * Obtengo los datos generales del clima de hoy: temperatura máxima y mínima,
     * usando la API de Accuweather.
     * 
     * @param String $KEY {Es la key_app de Accuweather}
     * @param String $ciudad {Trelew, Gaiman, Dolavon, 28 de Julio}
     * @param int $ciudad_key {id de la ciudad en Accuweather}
     * @return Array {datos}
     */
    public static function obtenerClimaDiario($KEY, $ciudad, $ciudad_key) {
        $URL = "http://dataservice.accuweather.com/forecasts/v1/daily/1day/".$ciudad_key."?apikey=".$KEY."&language=es-es&details=true&metric=true";

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_TIMEOUT => 30000,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
            ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        

        if ($err) {
            echo "cURL Error #:" . $err;
            
            return array();

        } else { 
            $respuesta = array(
                'temperatura_maxima' => "",
                'temperatura_minima' => "",
                'sensacion_termica_maxima' => "",
                'sensacion_termica_minima' => "",
                'horas_de_sol' => "",
                'descripcion_dia' => "",
                'descripcion_noche' => "",
            );

            $datos = json_decode($response,true);
            $datos = $datos['DailyForecasts'][0];

            $respuesta['temperatura_maxima'] = $datos['Temperature']['Maximum']['Value'];
            $respuesta['temperatura_minima'] = $datos['Temperature']['Minimum']['Value'];
            $respuesta['sensacion_termica_maxima'] = $datos['RealFeelTemperature']['Maximum']['Value'];
            $respuesta['sensacion_termica_minima'] = $datos['RealFeelTemperature']['Minimum']['Value'];
            $respuesta['horas_de_sol'] = $datos['HoursOfSun'];
            $respuesta['descripcion_dia'] = $datos['Day']['ShortPhrase'];
            $respuesta['descripcion_noche'] = $datos['Night']['ShortPhrase'];

            return $respuesta;
        }
    }


    /**
     * Dado un String de fecha y hora con el formato 2020-01-07T16:20:00-03:00,
     * devuelvo la fecha y la hora respectivamente.
     * 
     * @param String $fecha_hora
     * @return Array (fecha, hora)
     */
    public static function procesarFechaHora($fecha_hora) {
        // 2020-01-07T16:20:00-03:00
        $array_fecha_hora = explode('T',$fecha_hora);
        $fecha = $array_fecha_hora[0];
        $hora = substr(explode('-',$array_fecha_hora[1])[0],0,5);

        return array($fecha,$hora);
    }


    /**
     * Transforma un String recibido en otro.
     * 
     * @return String
     */
    public static function getVariableBuscada($variable) {
        if( $variable == "temperatura" ) return 'temperatura';
        if( $variable == "humedad" ) return 'humedad';
        if( $variable == "velocidad_viento" ) return 'velocidad_del_viento_km_hs';
        if( $variable == "presion" ) return 'presion_hpa';
        if( $variable == "horas_sol" ) return 'horas_de_sol';
    }

    /**
     * Para el rango de fechas pasado por parámetro, y la variable climática buscada,
     * se devuelve un array de climas.
     * 
     * @return Array Clima
     */
    public static function getDatosVariable($ciudad, $variable, $fechas) {
        
        // Busco el nombre de la variable en la bd.
        $columna_bd = Clima::getVariableBuscada($variable);

        // Datasets que voy a usar.
        $dataset = array();

        foreach( $fechas as $fecha ) {
            $climas = Clima::where('ciudad',$ciudad)
                            ->where('fecha','=',$fecha)
                            ->select($columna_bd,'fecha','hora')
                            ->orderBy('id','asc')
                            ->get(); 

            if( sizeof($climas) == 0 ) {
                $x = $fecha." 09:00";
                array_push($dataset, array($x, null) );
            }
            else {
                foreach( $climas as $clima ) {
                    $x = $clima->fecha." ".$clima->hora;
                    array_push($dataset, array($x, $clima->$columna_bd));
                }
            }
        }

        return array(
            'clima' => $dataset,
        );
    }


    /**
     * Para un rango de fechas pasado por parámetro, y una ciudad, se devuelven todos
     * los climas en ese rango de fechas.
     */
    public static function obtenerClimaCiudad($ciudad, $fechas) {

        $datos = array();

        if( $ciudad == "Todos" ) {
            $datos = Clima::where('fecha', '>=',$fechas[0])->where('fecha','<=',$fechas[sizeof($fechas)-1])->get();  
            return array(
                'datos' => $datos,
            );      
        }
        

        $datos = Clima::where('ciudad',$ciudad)->where('fecha', '>=',$fechas[0])->where('fecha','<=',$fechas[sizeof($fechas)-1])->get();        

        // foreach( $fechas as $fecha ) {
        //     $clima = Clima::where('ciudad',$ciudad)->where('fecha', $fecha)->get();

        //     if( sizeof($clima) != 0 ) array_push($datos,$clima);
        // }

        return array(
            'datos' => $datos,
        );
    }


    /**
     * Permite obtener el clima de la ciudad en una fecha dada.
     * 
     * @param int $apiario
     * @param String $variable_ambiental
     * @param String $fecha
     * @return Array
     */
    public static function obtenerClimaDia($apiario, $variable_ambiental, $fecha) {

        $dataset_temperatura = array();
        $dataset_humedad = array();

        // Verifico si existe variable ambiental
        if( $variable_ambiental == "" ) {
            return array(
                'temperatura' => $dataset_temperatura,
                'humedad' => $dataset_humedad,
            );
        }

        $ciudad = (Apiario::where('id',$apiario)->select('localidad_chacra')->first())->localidad_chacra;

        
        $climas = Clima::where('ciudad',$ciudad)
                                    ->where('fecha', $fecha)
                                    ->select('temperatura','humedad','fecha','hora')
                                    ->orderBy('id','asc')
                                    ->get(); 
        
        foreach($climas as $clima) {

            if( $variable_ambiental == "temperatura" ) {
                $x = $clima->fecha." ".$clima->hora;
                array_push($dataset_temperatura, array($x,$clima->temperatura));
            }
            elseif( $variable_ambiental == "humedad") {
                $x = $clima->fecha." ".$clima->hora;
                array_push($dataset_humedad, array($x,$clima->humedad));
            }
            else {
                $x = $clima->fecha." ".$clima->hora;
                array_push($dataset_temperatura, array($x,$clima->temperatura));
                array_push($dataset_humedad, array($x,$clima->humedad));
            }
        }        
        
        return array(
            'temperatura' => $dataset_temperatura,
            'humedad' => $dataset_humedad,
        );

    }


    /**
     * Obtiene clima de una ciudad en un rango de fechas.
     * 
     * @param int $apiario
     * @param string $variable_ambiental
     * @param string $fecha
     * 
     * @return array
     * 
     */
    public static function obtenerClimaMeses($apiario, $variable_ambiental, $fecha) {

        $dataset_temperatura = array();
        $dataset_humedad = array();

        // Verifico si existe variable ambiental
        if( $variable_ambiental == "" ) {
            return array(
                'temperatura' => $dataset_temperatura,
                'humedad' => $dataset_humedad,
            );
        }

        $ciudad = (Apiario::where('id',$apiario)->select('localidad_chacra')->first())->localidad_chacra;

        // Obtengo el rango de días del mes
        $fechas = RevisacionTemperaturaHumedad::getFechasMes($fecha);

        $climas = Clima::where('ciudad',$ciudad)
                                    ->where('fecha', '>=',$fechas[0])
                                    ->where('fecha', '<=',$fechas[sizeof($fechas)-1])
                                    ->select('temperatura','humedad','fecha','hora')
                                    ->orderBy('id','asc')
                                    ->get(); 

        foreach($climas as $clima) {

            if( $variable_ambiental == "temperatura" ) {
                $x = $clima->fecha." ".$clima->hora;
                array_push($dataset_temperatura, array($x,$clima->temperatura));
            }
            elseif( $variable_ambiental == "humedad") {
                $x = $clima->fecha." ".$clima->hora;
                array_push($dataset_humedad, array($x,$clima->humedad));
            }
            else {
                $x = $clima->fecha." ".$clima->hora;
                array_push($dataset_temperatura, array($x,$clima->temperatura));
                array_push($dataset_humedad, array($x,$clima->humedad));
            }
        }        
        
        return array(
            'temperatura' => $dataset_temperatura,
            'humedad' => $dataset_humedad,
        );
        
    }


    /**
     * Devuelve el clima de una ciudad en una rango de fechas recibido.
     * 
     * @param int $apiario
     * @param string $variable_ambiental
     * @param array $rango_fechas
     * 
     * @return array
     */
    public static function obtenerClimaRangoFechas($apiario, $variable_ambiental, $rango_fechas) {
        
        $dataset_temperatura = array();
        $dataset_humedad = array();

        // Verifico si existe variable ambiental
        if( $variable_ambiental == "" ) {
            return array(
                'temperatura' => $dataset_temperatura,
                'humedad' => $dataset_humedad,
            );
        }

        $ciudad = (Apiario::where('id',$apiario)->select('localidad_chacra')->first())->localidad_chacra;

        $climas = Clima::where('ciudad',$ciudad)
                                    ->where('fecha', '>=',$rango_fechas[0])
                                    ->where('fecha', '<=',$rango_fechas[sizeof($rango_fechas)-1])
                                    ->select('temperatura','humedad','fecha','hora')
                                    ->orderBy('id','asc')
                                    ->get(); 

        foreach($climas as $clima) {

            if( $variable_ambiental == "temperatura" ) {
                $x = $clima->fecha." ".$clima->hora;
                array_push($dataset_temperatura, array($x,$clima->temperatura));
            }
            elseif( $variable_ambiental == "humedad") {
                $x = $clima->fecha." ".$clima->hora;
                array_push($dataset_humedad, array($x,$clima->humedad));
            }
            else {
                $x = $clima->fecha." ".$clima->hora;
                array_push($dataset_temperatura, array($x,$clima->temperatura));
                array_push($dataset_humedad, array($x,$clima->humedad));
            }
        }        
        
        return array(
            'temperatura' => $dataset_temperatura,
            'humedad' => $dataset_humedad,
        );

    }

    /**
     * Devulve el clima de una ciudad en la fecha dada como parámetro.
     * 
     * @param string $ciudad
     * @param string $variable_ambiental
     * @param array $rango_fechas
     * 
     * @return array
     */
    public static function obtenerClimaDiaChart($ciudad, $variable_ambiental, $fecha) {
        
        $dataset = array();

        $climas = Clima::where('ciudad',$ciudad)
                                    ->where('fecha', $fecha)
                                    ->orderBy('id','asc')
                                    ->get(); 
        
        foreach($climas as $clima) {

            if( $variable_ambiental == "temperatura" ) {
                $x = $clima->fecha." ".$clima->hora;
                array_push($dataset, array($x,$clima->temperatura));
            }
            elseif( $variable_ambiental == "humedad") {
                $x = $clima->fecha." ".$clima->hora;
                array_push($dataset, array($x,$clima->humedad));
            }
            elseif( $variable_ambiental == "velocidad_viento") {
                $x = $clima->fecha." ".$clima->hora;
                array_push($dataset, array($x,$clima->velocidad_del_viento_km_hs));
            }
            elseif( $variable_ambiental == "presion") {
                $x = $clima->fecha." ".$clima->hora;
                array_push($dataset, array($x,$clima->presion_hpa));
            }
            elseif( $variable_ambiental == "horas_sol") {
                $x = $clima->fecha." ".$clima->hora;
                array_push($dataset, array($x,$clima->horas_de_sol));
            }
           
        }        
        
        return $dataset;  
           
        
    }


    /**
     * Devulve el clima de una ciudad en el rango de fechas dado (mes específico).
     * 
     * @param string $ciudad
     * @param string $variable_ambiental
     * @param array $rango_fechas
     * 
     * @return array
     */
    public static function obtenerClimaMesesChart($ciudad, $variable_ambiental, $fecha) {

        $dataset = array();

        // Obtengo el rango de días del mes
        $fechas = RevisacionTemperaturaHumedad::getFechasMes($fecha);

        $climas = Clima::where('ciudad',$ciudad)
                                    ->where('fecha', '>=',$fechas[0])
                                    ->where('fecha', '<=',$fechas[sizeof($fechas)-1])
                                    ->orderBy('id','asc')
                                    ->get(); 

        foreach($climas as $clima) {

            if( $variable_ambiental == "temperatura" ) {
                $x = $clima->fecha." ".$clima->hora;
                array_push($dataset, array($x,$clima->temperatura));
            }
            elseif( $variable_ambiental == "humedad") {
                $x = $clima->fecha." ".$clima->hora;
                array_push($dataset, array($x,$clima->humedad));
            }
            elseif( $variable_ambiental == "velocidad_viento") {
                $x = $clima->fecha." ".$clima->hora;
                array_push($dataset, array($x,$clima->velocidad_del_viento_km_hs));
            }
            elseif( $variable_ambiental == "presion") {
                $x = $clima->fecha." ".$clima->hora;
                array_push($dataset, array($x,$clima->presion_hpa));
            }
            elseif( $variable_ambiental == "horas_sol") {
                $x = $clima->fecha." ".$clima->hora;
                array_push($dataset, array($x,$clima->horas_de_sol));
            }
        }        
        
        return $dataset;

    }

    /**
     * Devulve el clima de una ciudad en el rango de fechas dado.
     * 
     * @param string $ciudad
     * @param string $variable_ambiental
     * @param array $rango_fechas
     * 
     * @return array
     */
    public static function obtenerClimaRangoFechasChart($ciudad, $variable_ambiental, $rango_fechas) {

        $dataset = array();

        $climas = Clima::where('ciudad',$ciudad)
                                    ->where('fecha', '>=',$rango_fechas[0])
                                    ->where('fecha', '<=',$rango_fechas[sizeof($rango_fechas)-1])
                                    ->orderBy('id','asc')
                                    ->get();

        foreach($climas as $clima) {

            if( $variable_ambiental == "temperatura" ) {
                $x = $clima->fecha." ".$clima->hora;
                array_push($dataset, array($x,$clima->temperatura));
            }
            elseif( $variable_ambiental == "humedad") {
                $x = $clima->fecha." ".$clima->hora;
                array_push($dataset, array($x,$clima->humedad));
            }
            elseif( $variable_ambiental == "velocidad_viento") {
                $x = $clima->fecha." ".$clima->hora;
                array_push($dataset, array($x,$clima->velocidad_del_viento_km_hs));
            }
            elseif( $variable_ambiental == "presion") {
                $x = $clima->fecha." ".$clima->hora;
                array_push($dataset, array($x,$clima->presion_hpa));
            }
            elseif( $variable_ambiental == "horas_sol") {
                $x = $clima->fecha." ".$clima->hora;
                array_push($dataset, array($x,$clima->horas_de_sol));
            }
        }        
        
        return $dataset;
    }

}

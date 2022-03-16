<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use \DatePeriod;
use \DateTime;
use \DateInterval;
use App\Colmena;

class RevisacionTemperaturaHumedad extends Model
{
    protected $table = 'revisacion_temperatura_humedad';
        
    protected $primaryKey = 'id';

    protected $fillable = array(
        'apiario_id',
        'colmena_id',
        'temperatura',
        'humedad',
        'fecha_revisacion',
        'hora_revisacion'
    );

    /**
     * Permite crear una Revisación de temperatura y Humedad.
     * 
     */
    public static function crearRevisacion($datos) {
        $objetoRevisacion = RevisacionTemperaturaHumedad::create(array(
            $datos
        ));

        return $objetoRevisacion;
    }

    
    /**
     * Retorna un arreglo que contiene las fechas desde hace 7 días hasta hoy.
     * 
     * @return $arreglo 
     * 
     */
    public static function getDiasDeUltimaSemana() {        

        date_default_timezone_set('UTC');
        // Arreglo de fechas
        $arreglo = array();
        // Start date
        $date = date('d-m-Y', strtotime('-6 days'));
        // End date
        $end_date = date('d-m-Y');

        while (strtotime($date) <= strtotime($end_date)) {
            array_push($arreglo, $date);
            $date = date ("d-m-Y", strtotime("+1 day", strtotime($date)));
        }

        return $arreglo;
    }


    /**
     * Dada una colmena de un apiario, devuelve todas sus revisaciones de temperatura de la última semana.
     * 
     */
    public static function crearDataSetTemperatura($apiario_id, $colmena_id) {
        // Defino constantes
        $HORARIO_MANANA = array(8,9,10,11,12);
        $HORARIO_TARDE = array(13,14,15,16,17);

        // Obtengo fechas
        $fechas = RevisacionTemperaturaHumedad::getDiasDeUltimaSemana();

        // Obtengo la fecha desde del arreglo de fechas pasado como parametros.
        $fecha_desde = $fechas[0];
        
        // Me busco las temperaturas a partir de fecha_desde
        $revisaciones = RevisacionTemperaturaHumedad::where('apiario_id',$apiario_id)
                                                    ->where('colmena_id',$colmena_id)
                                                    ->where('fecha_revisacion','>=',$fecha_desde)
                                                    ->orderBy('id', 'ASC')
                                                    ->get();

        $temperaturas = array();
        foreach($revisaciones as $revisacion) {
            array_push($temperaturas, $revisacion['temperatura']);
        }

        return $temperaturas;
    }


    /**
     * Dada una colmena de un apiario, devuelve todas sus revisaciones de humedad de la última semana.
     * 
     */
    public static function crearDataSetHumedad($apiario_id, $colmena_id) {
        // Defino constantes
        $HORARIO_MANANA = array(8,9,10,11,12);
        $HORARIO_TARDE = array(13,14,15,16,17);

        // Obtengo fechas
        $fechas = RevisacionTemperaturaHumedad::getDiasDeUltimaSemana();

        // Obtengo la fecha desde del arreglo de fechas pasado como parametros.
        $fecha_desde = $fechas[0];
        
        // Me busco las temperaturas a partir de fecha_desde
        $revisaciones = RevisacionTemperaturaHumedad::where('apiario_id',$apiario_id)
                                                    ->where('colmena_id',$colmena_id)
                                                    ->where('fecha_revisacion','>=',$fecha_desde)
                                                    ->orderBy('id', 'ASC')
                                                    ->get();

        $humedades = array();
        foreach($revisaciones as $revisacion) {
            array_push($humedades, $revisacion['humedad']);
        }

        return $humedades;
    }

    
    /**
     * TODO
     * BUG: VERIFICAR EL HORARIO:: que la revisacion de la tarde no se haya hecho!!!
     */
    public static function crearDataset($apiario_id, $colmena_id) {
        
        // Defino constantes
        $HORARIO_MANANA = array(8,9,10,11,12);
        $HORARIO_TARDE = array(13,14,15,16,17);

        $revisaciones = RevisacionTemperaturaHumedad::where('apiario_id',$apiario_id)
                                                    ->where('colmena_id',$colmena_id)
                                                    ->orderBy('id', 'DESC')
                                                    ->take(14)
                                                    ->get();
        
        $revisaciones = $revisaciones->sortBy('id');
        
        $labels = array();
        $temperaturas = array();
        $humedades = array();
        foreach($revisaciones as $revisacion) {
            $date = date('d-m-Y', strtotime( $revisacion['fecha_revisacion'] ));
            array_push($temperaturas, $revisacion['temperatura']);
            array_push($humedades, $revisacion['humedad']);
            // HORARIO MAÑANA
            if($revisacion['hora_revisacion'] < "13:00:00"){
                $label = $date." AM";
                if(!in_array($label,$labels)) {
                    array_push($labels,$label);
                }
            }
            // HORARIO TARDE
            else {
                $label = $date." PM";
                if(!in_array($label,$labels)) {
                    array_push($labels,$label);
                }
            }
           
        }
        return array(
            'numero_de_colmena' => Colmena::getNumeroDeColmena($colmena_id),
            'labels' => $labels,
            'temperaturas' => $temperaturas,
            'humedades' => $humedades
        );
    }



    /** 
     * Busca las fechas entre dos rangos, y las retorna en un arreglo.
     * 
     * 
     * @param $fecha_desde
     * @param $fecha_hasta Si no viene en la llamada, se toma la fecha de hoy como $fecha_hasta.
     * 
     * @return $arreglo Es un array que contiene un string de fechas.
     */
    public static function getRangoFechas($fecha_desde, $fecha_hasta = '') {
        date_default_timezone_set('UTC');
        // Arreglo de fechas
        $arreglo = array();
        // Start date
        $date = date('d-m-Y', strtotime($fecha_desde));
        // End date
        $end_date = "";
        if($fecha_hasta == '')  $end_date = date('d-m-Y');
        else $end_date = date('d-m-Y', strtotime($fecha_hasta));
        

        while (strtotime($date) <= strtotime($end_date)) {
            array_push($arreglo, $date);
            $date = date ("d-m-Y", strtotime("+1 day", strtotime($date)));
        }

        return $arreglo;
    }


    /**
     * Dada una fecha inicial y una fecha final, devuelve un arreglo que contiene todos los días
     * entre esas fechas.
     * @param String $fecha_desde {"Y-m-d"}
     * @param String $fecha_hasta {"Y-m-d"}
     * 
     * @return Array {fechas}
     */
    public static function getRangoFechasInglesas($fecha_desde, $fecha_hasta = '') {
        date_default_timezone_set('UTC');
        // Arreglo de fechas
        $arreglo = array();
        // Start date
        $date = date('Y-m-d', strtotime($fecha_desde));
        // End date
        $end_date = "";
        if($fecha_hasta == '')  $end_date = date('Y-m-d');
        else $end_date = date('d-m-Y', strtotime($fecha_hasta));
        

        while (strtotime($date) <= strtotime($end_date)) {
            array_push($arreglo, $date);
            $date = date ("Y-m-d", strtotime("+1 day", strtotime($date)));
        }

        return $arreglo;
    }
    

    /**
     * Dado un mes en el formato "Y-m", devuelve un arreglo con todos los 
     * días de ese mes.
     * 
     * @param String $mes es una fecha con formato Y-m, por ejemplo, "2020-02" donde "02" es Febrero.
     * @return Array String de fechas con formato Y-m-d
     */
    public static function getFechasMes($mes) {
        $mes_anio = explode("-",$mes);
        $list=array();
        $month = $mes_anio[1];
        $year = $mes_anio[0];

        for($d=1; $d<=31; $d++)
        {
            $time=mktime(12, 0, 0, $month, $d, $year);          
            if (date('m', $time)==$month)       
                array_push($list,date('Y-m-d', $time));
        }
       
        return $list;
    }


    /**
     * Dada una fecha desde y una fecha hasta, devuelve un arreglo
     * con las fechas entre ellas.
     * 
     * @param String $fecha_desde {Y-m-d}
     * @param String $fecha_hasta {Y-m-d}
     * 
     * @return Array de fechas.
     */
    public static function getDias($fecha_desde, $fecha_hasta) {
        $period = new DatePeriod(
            new DateTime($fecha_desde),
            new DateInterval('P1D'),
            new DateTime($fecha_hasta)
       );

       $dias = array();

       foreach ($period as $key => $value) {
            array_push($dias,$value->format('Y-m-d'));       
        }

        if(sizeof($dias)==0) array_push($dias,$fecha_desde);       
        else {
            $ultima_fecha = $dias[sizeof($dias)-1];
            array_push($dias, date ("Y-m-d", strtotime("+1 day", strtotime($ultima_fecha))));
        }

       return $dias;
    }


    
    /**
     * Script para cargar datos de Ejemplo para las revisaciones: APIARIO = 1, COLMENA = 2.
     *
     * Toma como rango de fechas la fecha de la última revisación y la fecha de hoy, y por cada una de ellas
     * genera dos revisaciones, una con horario de mañana y otra de tarde.
     * 
     * 
     * 
     */
    public static function cargarDatosEjemplo($apiario_id, $colmena_id) {
        // Obtengo la ultima revisacion. 
        $revisacion = RevisacionTemperaturaHumedad::where('apiario_id', $apiario_id)
                                                    ->where('colmena_id', $colmena_id)
                                                    ->orderBy('id', 'DESC')
                                                    ->first();
        
        $start_date = "";
        $end_date = date('d-m-Y');
        if($revisacion){
            $start_date = date ("d-m-Y", strtotime("+1 day", strtotime($revisacion['fecha_revisacion'])));
        }
        else {
            $start_date = date ("d-m-Y", strtotime("-6 day", strtotime($end_date)));
        }

        // Obtengo fechas
        $fechas = RevisacionTemperaturaHumedad::getRangoFechas($start_date);
        $cantidad_revisaciones_por_fecha = 2;
        foreach($fechas as $fecha) {
            for($i=0; $i<$cantidad_revisaciones_por_fecha; $i++) {
                $revisacion = array();
                if( $i == 0 ){
                    $revisacion = RevisacionTemperaturaHumedad::create(array(
                        'apiario_id' => $apiario_id,
                        'colmena_id' => $colmena_id,
                        'temperatura' => random_int( 30 , 40 ),
                        'humedad' => random_int( 30 , 40 ),
                        'fecha_revisacion' => date('Y-m-d', strtotime($fecha)),
                        'hora_revisacion' => '09:00'
                    ));
                
                }
                elseif( $i == 1 ){
                    $revisacion = RevisacionTemperaturaHumedad::create(array(
                        'apiario_id' => $apiario_id,
                        'colmena_id' => $colmena_id,
                        'temperatura' => random_int( 30 , 40 ),
                        'humedad' => random_int( 30 , 40 ),
                        'fecha_revisacion' => date('Y-m-d', strtotime($fecha)),
                        'hora_revisacion' => '15:00'
                    ));
                }

                echo "[Revisacion]".json_encode($revisacion)."\n";
                
            }
        }
    }

    /**
     * Devuelve un arreglo de fechas en base a los datos que contiene el parámetro $tipoAccion.
     * 
     * @param Array $tipoAccion(accion, tipo, fecha_actual, fecha_pasada).
     * @return Array String de fechas.
     */
    public static function getFechas($tipoAccion) {
        if( $tipoAccion['tipo'] == 'hoy' ) {
            return array(date('d-m-Y'));
        }
        elseif( $tipoAccion['tipo'] == 'ayer' ) {
            $date = date('d-m-Y');
            $date = date ("d-m-Y", strtotime("-1 day", strtotime($date)));
            return array($date);
        }
        elseif( $tipoAccion['tipo'] == '7' ) {
            $date = date('d-m-Y');
            $date = date ("d-m-Y", strtotime("-6 day", strtotime($date)));
            return RevisacionTemperaturaHumedad::getRangoFechas($date);
        }
        elseif( $tipoAccion['tipo'] == '14' ) {
            $date = date('d-m-Y');
            $date = date ("d-m-Y", strtotime("-13 day", strtotime($date)));
            return RevisacionTemperaturaHumedad::getRangoFechas($date);
        }
        elseif( $tipoAccion['tipo'] == '30' ) {
            $date = date('d-m-Y');
            $date = date ("d-m-Y", strtotime("-29 day", strtotime($date)));
            return RevisacionTemperaturaHumedad::getRangoFechas($date);
        }
        else {
            return RevisacionTemperaturaHumedad::getRangoFechas($tipoAccion['fecha_actual'], $tipoAccion['fecha_pasada']);
        }
    }



    /**
     * Devuelve un arreglo de fechas en base a los datos que contiene el parámetro $tipoAccion.
     * 
     * @param Array $tipoAccion(accion, tipo, fecha_actual, fecha_pasada).
     * @return Array String de fechas.
     */
    public static function getFechasParaRango($tipoAccion) {
        if( $tipoAccion['tipo'] == 'hoy' ) {
            return array(date('d-m-Y'));
        }
        elseif( $tipoAccion['tipo'] == '7' ) {
            $date = date('d-m-Y');
            $date = date ("d-m-Y", strtotime("-6 day", strtotime($date)));
            return RevisacionTemperaturaHumedad::getRangoFechas($date);
        }
        elseif( $tipoAccion['tipo'] == '14' ) {
            $date = date('d-m-Y');
            $date = date ("d-m-Y", strtotime("-13 day", strtotime($date)));
            return RevisacionTemperaturaHumedad::getRangoFechas($date);
        }
        elseif( $tipoAccion['tipo'] == '30' ) {
            $date = date('d-m-Y');
            $date = date ("d-m-Y", strtotime("-29 day", strtotime($date)));
            return RevisacionTemperaturaHumedad::getRangoFechas($date);
        }
        else {
            return RevisacionTemperaturaHumedad::getDias($tipoAccion['fecha_actual'], $tipoAccion['fecha_pasada']);
        }
    }


    /**
     * Devuelve un arreglo de fechas en base a los datos que contiene el parámetro $tipoAccion.
     * 
     * @param Array $tipoAccion(accion, tipo, fecha_actual, fecha_pasada).
     * @return Array String de fechas.
     */
    public static function getFechasInglesasParaRango($tipoAccion) {
        if( $tipoAccion['tipo'] == 'hoy' ) {
            return array(date('Y-m-d'));
        }
        elseif( $tipoAccion['tipo'] == 'ayer' ) {
            $date = date('Y-m-d');
            $date = date ("Y-m-d", strtotime("-1 day", strtotime($date)));
            return array($date);
        }
        elseif( $tipoAccion['tipo'] == "7" ) {
            $date = date('Y-m-d');
            $date = date ("Y-m-d", strtotime("-6 day", strtotime($date)));
            return RevisacionTemperaturaHumedad::getDias($date, date('Y-m-d'));
        }
        elseif( $tipoAccion['tipo'] == '14' ) {
            $date = date('Y-m-d');
            $date = date ("Y-m-d", strtotime("-13 day", strtotime($date)));
            return RevisacionTemperaturaHumedad::getDias($date, date('Y-m-d'));
        }
        elseif( $tipoAccion['tipo'] == '30' ) {
            $date = date('Y-m-d');
            $date = date ("Y-m-d", strtotime("-29 day", strtotime($date)));
            return RevisacionTemperaturaHumedad::getDias($date, date('Y-m-d'));
        }
        else {
            return RevisacionTemperaturaHumedad::getDias($tipoAccion['fecha_actual'], $tipoAccion['fecha_pasada']);
        }
    }

    /**
     * Devuelve un arreglo con las fechas de los últimos 7 días.
     * 
     * @return Array fechas
     */
    public static function obtener_arreglo_fechas_ultima_semana() {
        $date = date('Y-m-d');
        $date = date ("Y-m-d", strtotime("-6 day", strtotime($date)));
        return RevisacionTemperaturaHumedad::getDias($date, date('Y-m-d'));
    }
    
    
    /**
     * Procesa los parámetros recibidos para retornar todos los datos para renderizar los gráficos de 
     * temperatura y humedad de una colmena. 
     * Se utilizó para la librería Chart.js
     * ÉSTE MÉTODO YA NO SE USA.
     * 
     * @param int $apiario es el id de un apiario
     * @param int $colmena es el id de una colmena
     * @param String $variable {temperatura, humedad, temperatura_y_humedad}
     * @param String $horario {mañana, tarde, mañana_y_tarde}
     * @param Array $fechas es un arreglo de fechas en formato español (d-m-Y)
     * @return Array
     */
    public static function getDatosRangoFecha($apiario,$colmena,$variable,$horario,$fechas){
        
        $fechas_inglesas = array();
        foreach($fechas as $fecha) {
            array_push($fechas_inglesas,date('Y-m-d', strtotime( $fecha )));
        }

        $revisaciones = "";

        if( $horario == 'mañana' ) {
            
                $revisaciones = RevisacionTemperaturaHumedad::where('apiario_id',$apiario)
                                                            ->where('colmena_id',$colmena)
                                                            ->whereIn('fecha_revisacion',$fechas_inglesas)
                                                            ->where('hora_revisacion','<','13:00:00')
                                                            ->get();
            
        }
        elseif ( $horario == 'tarde' ) {
           
                $revisaciones = RevisacionTemperaturaHumedad::where('apiario_id',$apiario)
                                                            ->where('colmena_id',$colmena)
                                                            ->whereIn('fecha_revisacion',$fechas_inglesas)
                                                            ->where('hora_revisacion','>=','13:00:00')
                                                            ->get();
            
        }
        else{
            
                $revisaciones = RevisacionTemperaturaHumedad::where('apiario_id',$apiario)
                                                                ->where('colmena_id',$colmena)
                                                                ->whereIn('fecha_revisacion',$fechas_inglesas)
                                                                ->get();
            
        }

        // Defino variables        
        $labels = array();        
        $dataset_temperatura = array();
        $dataset_humedad = array();
        $colores = array(
            'rgba(54, 162, 235, 0.6)',
            'rgba(255, 99, 132, 0.6)',
            'rgba(255, 206, 86, 0.6)',
            'rgba(75, 192, 192, 0.6)',
            'rgba(153, 102, 255, 0.6)',
            'rgba(255, 159, 64, 0.6)',
            'rgba(255, 99, 132, 0.6)'
        );
        $backgroundColor = array();

        if(  $variable == 'temperatura'  ) {
            foreach( $revisaciones as $revisacion ) {
                //TEMPERATURA
                array_push($dataset_temperatura, $revisacion->temperatura);

                $fecha = date('d-m-Y', strtotime( $revisacion->fecha_revisacion ));
                // HORARIO MAÑANA
                if($revisacion->hora_revisacion < "13:00"){
                    $label = $fecha." AM";
                    if(!in_array($label,$labels)) {
                        array_push($labels,$label);
                    }
                }
                // HORARIO TARDE
                else {
                    $label = $fecha." PM";
                    if(!in_array($label,$labels)) {
                        array_push($labels,$label);
                    }
                }

                // COLORES
                array_push($backgroundColor, $colores[random_int( 0 , sizeof($colores) - 1 )]);
            }
        }
        elseif(  $variable == 'humedad'  ) {
            foreach( $revisaciones as $revisacion ) {
                // HUMEDAD
                array_push($dataset_humedad, $revisacion->humedad);

                $fecha = date('d-m-Y', strtotime( $revisacion->fecha_revisacion ));
                // HORARIO MAÑANA
                if($revisacion->hora_revisacion < "13:00:00"){
                    $label = $fecha." AM";
                    if(!in_array($label,$labels)) {
                        array_push($labels,$label);
                    }
                }
                // HORARIO TARDE
                else {
                    $label = $fecha." PM";
                    if(!in_array($label,$labels)) {
                        array_push($labels,$label);
                    }
                }

                // COLORES
                array_push($backgroundColor, $colores[random_int( 0 , sizeof($colores) - 1 )]);
            }
        }
        else {
            foreach( $revisaciones as $revisacion ) {
                // TEMPERATURA Y HUMEDAD
                array_push($dataset_temperatura, $revisacion->temperatura);
                array_push($dataset_humedad, $revisacion->humedad);

                $fecha = date('d-m-Y', strtotime( $revisacion->fecha_revisacion ));
                // HORARIO MAÑANA
                if($revisacion->hora_revisacion < "13:00:00"){
                    $label = $fecha." AM";
                    if(!in_array($label,$labels)) {
                        array_push($labels,$label);
                    }
                }
                // HORARIO TARDE
                else {
                    $label = $fecha." PM";
                    if(!in_array($label,$labels)) {
                        array_push($labels,$label);
                    }
                }

                // COLORES
                array_push($backgroundColor, $colores[random_int( 0 , sizeof($colores) - 1 )]);
            }
        }

        return array(
            'labels' => $labels,
            'temperatura' => $dataset_temperatura,
            'humedad' => $dataset_humedad,
            'backgroundColor' => $backgroundColor,
        );
            
    }


    /**
     * Dado una fecha que solo contiene el mes y el año, devuelvo una fecha desde y 
     * una fecha hasta.
     * 
     * @param String $mes tiene el formato de fecha 'Y-m'.
     * @return Array [fecha_desde, fecha_hasta]
     */
    public static function formatearFechas($fecha) {
        $MESES_28 = array('02');
        $MESES_30 = array('04','06','08','11');
        $MESES_31 = array('01','03','05','07','09','10','12');
        
        // Obtengo la fecha de hoy
        $fecha_de_hoy = date('Y-m-d');
        $fecha_de_hoy = explode('-', $fecha_de_hoy);

        // Obtengo los meses, cuyo formato es "2020-01"
        $mes_anio = explode('-', $fecha);
        
    
        // Fecha desde y hasta del año actual
        $fecha_desde = "01-".$mes_anio[1]."-".$mes_anio[0];
        $fecha_hasta = "";

        // Si la fecha elegida es del mes y año actual, entonces la fecha hasta es la fecha de hoy.
        if( ($fecha_de_hoy[0] == $mes_anio[0]) && ($fecha_de_hoy[1] == $mes_anio[1]) ) {
             $fecha_hasta = $fecha_de_hoy[2]."-".$mes_anio[1]."-".$mes_anio[0];
        }
        elseif( in_array($mes_anio[1], $MESES_28) ) $fecha_hasta = "28-".$mes_anio[1]."-".$mes_anio[0];
        elseif( in_array($mes_anio[1], $MESES_30) ) $fecha_hasta = "30-".$mes_anio[1]."-".$mes_anio[0];
        else $fecha_hasta = "31-".$mes_anio[1]."-".$mes_anio[0]; 

        return array($fecha_desde, $fecha_hasta);
        
    }

    /**
     * Dada una fecha que contiene solo el mes y el año, devuelve una fecha desde y hasta.
     * La fecha hasta tendrá como día el mismo día que $fecha_limite.
     * 
     * @param String $fecha con formato Y-m
     * @param String $fecha_limite con formato d-m-Y
     * @return Array (fecha_desde, fecha_hasta) con formato d-m-Y
     */
    public static function formatearFechasAnioAnterior($fecha, $fecha_limite) {

        // Obtengo la fecha de hoy
        $fecha_arreglo = explode('-', $fecha);
        
        // Creo fecha desde
        $fecha_desde = "01-".$fecha_arreglo[1]."-".$fecha_arreglo[0];

        // Paso a un arreglo la fecha limite
        $limite = explode('-', $fecha_limite);

        // Creo la fecha hasta
        $fecha_hasta = $limite[0]."-".$fecha_arreglo[1]."-".$fecha_arreglo[0];

        return array($fecha_desde, $fecha_hasta);
    }


    /**
     * Dado el formato de un label 'd-m-Y AM' devuelve el mismo array pero sin el año,
     * es decir, con el formato 'd-m AM'.
     * 
     * @param Array $labels_1 es un array de String
     * @param Array $labels_2 es un array de String
     * @return Array $labels es un Array con la misma cantidad de elementos pero distinto formato.
     */
    public static function procesarLabels($labels_1, $labels_2) {

        // Si los arreglos no tienen nada, devuelvo un arreglo vacío.
        if( (sizeof($labels_1) == 0) && (sizeof($labels_2) == 0) ) return array();

        $labels = array();
        //04-01-2020 AM

        if( sizeof($labels_1) >= sizeof($labels_2) ) {
            foreach($labels_1 as $label) {
                $aux_1 = explode(' ', $label);
                $aux_2 = explode('-',$aux_1[0]);
                $l = $aux_2[0]."-".$aux_2[1]." ".$aux_1[1];
                array_push($labels, $l);
            }
        }
        else {
            foreach($labels_2 as $label) {
                $aux_1 = explode(' ', $label);
                $aux_2 = explode('-',$aux_1[0]);
                $l = $aux_2[0]."-".$aux_2[1]." ".$aux_1[1];
                array_push($labels, $l);
            }
        }

        return $labels;
    }
    

    /**
     * Dada una fecha con que contiene solo el mes y el año, devuelve el nombre 
     * del mes.
     * 
     * @param String $fecha con el formato Y-m
     * @param Boolean $completo es un boolean que si es true devuelve una determinada cadena, y false otra.
     * @return String un subtitulo.
     */
    public static function procesarSubtitulo($fecha, $completo) {
        $MESES = array(
            'Enero',
            'Febrero',
            'Marzo',
            'Abril',
            'Mayo',
            'Junio',
            'Julio',
            'Agosto',
            'Septiembre',
            'Octubre',
            'Noviembre',
            'Diciembre'
        );

        $anio_mes = explode('-', $fecha);
        $index = (int)$anio_mes[1];
        $index = $index - 1;

        if( $completo ) {
            return $MESES[$index]." de ".$anio_mes[0];
        }
        else {
            return $MESES[$index];
        }
        
    }

    /**
     * Devuelve cuatro arreglos, cada uno con un determinado color, y cuyo tamaño es igual al arreglo original.
     * 
     * @param Array $temperatura_1 es un arreglo de temperaturas.
     * @param Array $temperatura_2 es un arreglo de temperaturas.
     * @param Array $humedad_1 es un arreglo de humedades.
     * @param Array $humedad_2 es un arreglo de humedades.
     * @return Array $colores que contiene cuatro arreglos.
     */
    public static function procesarColores($temperatura_1,$humedad_1,$temperatura_2,$humedad_2) {
        $colores = array(
            'rgba(54, 162, 235, 0.6)',
            'rgba(255, 99, 132, 0.6)',
            'rgba(255, 206, 86, 0.6)',
            'rgba(75, 192, 192, 0.6)',
            'rgba(153, 102, 255, 0.6)',
            'rgba(255, 159, 64, 0.6)',
            'rgba(255, 99, 132, 0.6)'
        );
        $colores_temperatura_1 = array();
        $colores_temperatura_2 = array();
        $colores_humedad_1 = array();
        $colores_humedad_2 = array();

        foreach($temperatura_1 as $t1) {
            array_push($colores_temperatura_1, $colores[0]);
        }

        foreach($temperatura_2 as $t2) {
            array_push($colores_temperatura_2, $colores[1]);
        }

        foreach($humedad_1 as $h1) {
            array_push($colores_humedad_1, $colores[2]);
        }

        foreach($humedad_2 as $h2) {
            array_push($colores_humedad_2, $colores[3]);
        }

        return array(
            'colores_temperatura_1' => $colores_temperatura_1,
            'colores_temperatura_2' => $colores_temperatura_2,
            'colores_humedad_1' => $colores_humedad_1,
            'colores_humedad_2' => $colores_humedad_2,
        );
    }


    /**
     * Dado un intervalo de horarios y un rango establecido en minutos, devuelvo un array con los intervalos
     * entre ese intervalo de horario.
     * 
     * @param String $inicio {Formato "17:30"}
     * @param String $fin {Formato "18:30"}
     * @param String $rango {Está establecido en minutos.}
     * @return Array $horarios
     */
    public static function obtenerRangoHorarios($inicio, $fin, $rango) {
        $init_array = explode(":",$inicio);
        $init = date('H:i',strtotime($init_array[0].":00"));
        
        $aux_de_referencia = "2020-01-01 ".$init;
        $horarios = array();
        array_push($horarios, $init);

        $bandera = true;

        while( $bandera ) {
            
            $aux_de_referencia = date('Y-m-d H:i', strtotime("+".$rango." minutes", strtotime($aux_de_referencia)));
            $array_aux_de_referencia = explode(" ", $aux_de_referencia);

            if( $array_aux_de_referencia[0] != "2020-01-01") {
                array_push($horarios,"00:00");
                $bandera = false;
            }
            elseif( $array_aux_de_referencia[1] > $fin ) {
                // array_push($horarios, $array_aux_de_referencia[1]);
                $bandera = false;
            }
            else {
                array_push($horarios, $array_aux_de_referencia[1]);
            }
           
        }

        return $horarios;
    }

    
    /**
     * Dada una fecha y un rango horario, devuelve la temperatura y humedad de la colmena del apiario en esos 
     * parámetros.
     * 
     * @param int $apiario 
     * @param int $colmena
     * @param String $variable puede ser {temperatura, humedad, temperatura_y_humedad}
     * @param String $fecha con el formato Y-m-d
     * @param Array $rango_horario por ejemplo ["11:00","12:00","13:00"]
     * 
     * @return Array [temperaturas, humedades]
     */
    public static function comparacion_dias($apiario, $colmena, $variable, $fecha, $rango_horario) {

        $dataset_temperatura = array();
        $dataset_humedad = array();
        $aux = $rango_horario;


        $fin = $rango_horario[sizeof($rango_horario) - 1];
        if( $fin == "00:00" ) $fin = "23:59";

        $revisaciones = RevisacionTemperaturaHumedad::where('apiario_id',$apiario)
                                    ->where('colmena_id',$colmena)
                                    ->where('fecha_revisacion', $fecha)
                                    ->where('hora_revisacion',">=",$rango_horario[0])
                                    ->where('hora_revisacion','<=',$fin)
                                    ->select('temperatura','humedad','fecha_revisacion','hora_revisacion')
                                    ->orderBy('id','asc')
                                    ->get(); 
        
        foreach($revisaciones as $revisacion) {

            if( $variable == "temperatura" ) {
                $x = $revisacion->fecha_revisacion." ".$revisacion->hora_revisacion;
                array_push($dataset_temperatura, array($x, $revisacion->temperatura, RevisacionTemperaturaHumedad::getColorTemperatura($revisacion)));
            }
            elseif( $variable == "humedad") {
                $x = $revisacion->fecha_revisacion." ".$revisacion->hora_revisacion;
                array_push($dataset_humedad, array($x, $revisacion->humedad, RevisacionTemperaturaHumedad::getColorHumedad($revisacion)));
            }
            else {
                $x = $revisacion->fecha_revisacion." ".$revisacion->hora_revisacion;
                array_push($dataset_temperatura, array($x, $revisacion->temperatura, RevisacionTemperaturaHumedad::getColorTemperatura($revisacion)));
                array_push($dataset_humedad, array($x, $revisacion->humedad, RevisacionTemperaturaHumedad::getColorHumedad($revisacion)));
            }
        }        
        
        return array(
            'temperatura' => $dataset_temperatura,
            'humedad' => $dataset_humedad,
        );

    }


    /**
     * Dada una fecha y un rango horario, devuelve la temperatura y humedad de la colmena del apiario en esos 
     * parámetros.
     * 
     * @param int $apiario 
     * @param int $colmena
     * @param String $variable puede ser {temperatura, humedad, temperatura_y_humedad}
     * @param String $fecha con el formato Y-m-d
     * @param Array $rango_horario por ejemplo ["11:00","12:00","13:00"]
     * 
     * @return Array [temperaturas, humedades]
     */
    public static function comparacion_dias_csv($apiario, $colmena, $variable, $fecha, $rango_horario) {

        $dataset_temperatura = array();
        $dataset_humedad = array();
        $aux = $rango_horario;


        $fin = $rango_horario[sizeof($rango_horario) - 1];
        if( $fin == "00:00" ) $fin = "23:59";

        $revisaciones = RevisacionTemperaturaHumedad::where('apiario_id',$apiario)
                                    ->where('colmena_id',$colmena)
                                    ->where('fecha_revisacion', $fecha)
                                    ->where('hora_revisacion',">=",$rango_horario[0])
                                    ->where('hora_revisacion','<=',$fin)
                                    ->select('temperatura','humedad','fecha_revisacion','hora_revisacion')
                                    ->orderBy('id','asc')
                                    ->get(); 
        
        return $revisaciones;

    }

    /**
     * Dado un arreglo de fechas, y un rango horario, devuelve dos datasets, uno de temperatura y otro de humedad, 
     * de la colmena perteneciente al apiario pasado como parámetro.
     * 
     */
    public static function comparacion_meses($apiario, $colmena, $variable, $fecha, $rango_horario) { 

        // Datasets que voy a usar.
        $dataset_temperatura = array();
        $dataset_humedad = array();
        
        // Clono el rango horario
        $aux = $rango_horario;

        // Obtengo el rango de días del mes
        $fechas = RevisacionTemperaturaHumedad::getFechasMes($fecha);

        $fin = $rango_horario[sizeof($rango_horario) - 1];
        if( $fin == "00:00" ) $fin = "23:59";

        $revisaciones = RevisacionTemperaturaHumedad::where('apiario_id',$apiario)
                                    ->where('colmena_id',$colmena)
                                    ->where('fecha_revisacion', '>=',$fechas[0])
                                    ->where('fecha_revisacion', '<=',$fechas[sizeof($fechas)-1])
                                    ->where('hora_revisacion',">=",$rango_horario[0])
                                    ->where('hora_revisacion','<=',$fin)
                                    ->select('temperatura','humedad','fecha_revisacion','hora_revisacion')
                                    ->orderBy('id','asc')
                                    ->get(); 
        
        foreach($revisaciones as $revisacion) {

            if( $variable == "temperatura" ) {
                $x = $revisacion->fecha_revisacion." ".$revisacion->hora_revisacion;
                array_push($dataset_temperatura, array($x, $revisacion->temperatura, RevisacionTemperaturaHumedad::getColorTemperatura($revisacion)));
            }
            elseif( $variable == "humedad") {
                $x = $revisacion->fecha_revisacion." ".$revisacion->hora_revisacion;
                array_push($dataset_humedad, array($x, $revisacion->humedad, RevisacionTemperaturaHumedad::getColorHumedad($revisacion)));
            }
            else {
                $x = $revisacion->fecha_revisacion." ".$revisacion->hora_revisacion;
                array_push($dataset_temperatura, array($x, $revisacion->temperatura, RevisacionTemperaturaHumedad::getColorTemperatura($revisacion)));
                array_push($dataset_humedad, array($x, $revisacion->humedad, RevisacionTemperaturaHumedad::getColorHumedad($revisacion)));
            }
        
        }
        
        return array(
            'temperatura' => $dataset_temperatura,
            'humedad' => $dataset_humedad,
        );
    }


    /**
     * Dado un arreglo de fechas, y un rango horario, devuelve dos datasets, uno de temperatura y otro de humedad, 
     * de la colmena perteneciente al apiario pasado como parámetro.
     * 
     */
    public static function comparacion_meses_csv($apiario, $colmena, $variable, $fecha, $rango_horario) { 

        // Datasets que voy a usar.
        $dataset_temperatura = array();
        $dataset_humedad = array();
        
        // Clono el rango horario
        $aux = $rango_horario;

        // Obtengo el rango de días del mes
        $fechas = RevisacionTemperaturaHumedad::getFechasMes($fecha);

        $fin = $rango_horario[sizeof($rango_horario) - 1];
        if( $fin == "00:00" ) $fin = "23:59";

        $revisaciones = RevisacionTemperaturaHumedad::where('apiario_id',$apiario)
                                    ->where('colmena_id',$colmena)
                                    ->where('fecha_revisacion', '>=',$fechas[0])
                                    ->where('fecha_revisacion', '<=',$fechas[sizeof($fechas)-1])
                                    ->where('hora_revisacion',">=",$rango_horario[0])
                                    ->where('hora_revisacion','<=',$fin)
                                    ->select('temperatura','humedad','fecha_revisacion','hora_revisacion')
                                    ->orderBy('id','asc')
                                    ->get(); 
        
        
        
        return $revisaciones;
    }


    /**
     * Dado un rango de fechas y horarios, devuelve dos datasets (temperatura y humedad) asociados a la colmena
     * del apiario pasado como parámetros.
     *  
     * 
     * */    
    public static function rango_fechas($apiario, $colmena, $variable, $fechas, $rango_horario) {  
        
        // Datasets que voy a usar.
        $dataset_temperatura = array();
        $dataset_humedad = array();
        
        // Clono el rango horario
        $aux = $rango_horario; 
        
        $fin = $rango_horario[sizeof($rango_horario) - 1];
        if( $fin == "00:00" ) $fin = "23:59";

        $revisaciones = RevisacionTemperaturaHumedad::where('apiario_id',$apiario)
                                    ->where('colmena_id',$colmena)
                                    ->where('fecha_revisacion', '>=',$fechas[0])
                                    ->where('fecha_revisacion', '<=',$fechas[sizeof($fechas)-1])
                                    ->where('hora_revisacion',">=",$rango_horario[0])
                                    ->where('hora_revisacion','<=',$fin)
                                    ->select('temperatura','humedad','fecha_revisacion','hora_revisacion')
                                    ->orderBy('id','asc')
                                    ->get(); 
        
        foreach($revisaciones as $revisacion) {

            if( $variable == "temperatura" ) {
                $x = $revisacion->fecha_revisacion." ".$revisacion->hora_revisacion;
                array_push($dataset_temperatura, array($x, $revisacion->temperatura, RevisacionTemperaturaHumedad::getColorTemperatura($revisacion)));
            }
            elseif( $variable == "humedad") {
                $x = $revisacion->fecha_revisacion." ".$revisacion->hora_revisacion;
                array_push($dataset_humedad, array($x, $revisacion->humedad, RevisacionTemperaturaHumedad::getColorHumedad($revisacion)));
            }
            else {
                $x = $revisacion->fecha_revisacion." ".$revisacion->hora_revisacion;
                array_push($dataset_temperatura, array($x, $revisacion->temperatura, RevisacionTemperaturaHumedad::getColorTemperatura($revisacion)));
                array_push($dataset_humedad, array($x, $revisacion->humedad, RevisacionTemperaturaHumedad::getColorHumedad($revisacion)));
            }
        
        }

        
        return array(
            'temperatura' => $dataset_temperatura,
            'humedad' => $dataset_humedad,
        );


    }


    /**
     * Dado un rango de fechas y horarios, devuelve dos datasets (temperatura y humedad) asociados a la colmena
     * del apiario pasado como parámetros.
     *  
     * 
     * */    
    public static function rango_fechas_csv($apiario, $colmena, $variable, $fechas, $rango_horario) {  
        
        // Datasets que voy a usar.
        $dataset_temperatura = array();
        $dataset_humedad = array();
        
        // Clono el rango horario
        $aux = $rango_horario; 
        
        $fin = $rango_horario[sizeof($rango_horario) - 1];
        if( $fin == "00:00" ) $fin = "23:59";

        $revisaciones = RevisacionTemperaturaHumedad::where('apiario_id',$apiario)
                                    ->where('colmena_id',$colmena)
                                    ->where('fecha_revisacion', '>=',$fechas[0])
                                    ->where('fecha_revisacion', '<=',$fechas[sizeof($fechas)-1])
                                    ->where('hora_revisacion',">=",$rango_horario[0])
                                    ->where('hora_revisacion','<=',$fin)
                                    ->select('temperatura','humedad','fecha_revisacion','hora_revisacion')
                                    ->orderBy('id','asc')
                                    ->get(); 
        
        return $revisaciones;

    }



    /**
     * Dado un rango de fechas, se verifica sin en cada una de esas fechas hubo señal o no.
     * Si existe señal, entonces se setea un 1, sino un 0.
     * 
     * */    
    public static function getSenialDiaria($apiario, $colmena, $fechas) {  
        
        // Datasets que voy a usar.
        $dataset = array();
        
        foreach( $fechas as $fecha ) {
            $revisaciones = RevisacionTemperaturaHumedad::where('apiario_id',$apiario)
            ->where('colmena_id',$colmena)
            ->where('fecha_revisacion', '=',$fecha)
            ->select('temperatura','humedad','fecha_revisacion','hora_revisacion')
            ->orderBy('id','asc')
            ->get(); 

            if( sizeof($revisaciones) == 0 ) {
                $x = $fecha." 09:00";
                array_push($dataset, array($x, 0) );
            }
            else {
                foreach( $revisaciones as $revisacion ) {
                    $x = $revisacion->fecha_revisacion." ".$revisacion->hora_revisacion;
                    array_push($dataset, array($x, 1));
                }
            }
        }

        return array(
            'senial' => $dataset,
        );


    }


    public static function getSenialFechas($apiario, $colmena, $fechas, $horario) {

        // Datasets que voy a usar.
        $dataset = array();
        
        if( sizeof($horario) == 0  ) {

            foreach( $fechas as $fecha ) {
                $revisaciones = RevisacionTemperaturaHumedad::where('apiario_id',$apiario)
                ->where('colmena_id',$colmena)
                ->where('fecha_revisacion', '=',$fecha)
                ->select('temperatura','humedad','fecha_revisacion','hora_revisacion')
                ->orderBy('id','asc')
                ->get(); 
    
    
                $x = $fecha." 09:00";
                array_push($dataset, array($x, sizeof($revisaciones)) );
            }

        }
        else {

            foreach( $fechas as $fecha ) {
                $revisaciones = RevisacionTemperaturaHumedad::where('apiario_id',$apiario)
                ->where('colmena_id',$colmena)
                ->where('fecha_revisacion', '=',$fecha)
                ->where('hora_revisacion','>=',$horario[0])
                ->where('hora_revisacion','<=',$horario[sizeof($horario)-1])
                ->select('temperatura','humedad','fecha_revisacion','hora_revisacion')
                ->orderBy('id','asc')
                ->get(); 
    
                $x = "";
                if( sizeof($revisaciones) > 0  )  $x = $fecha." ".$revisaciones[0]['hora_revisacion'];
                else $x = $fecha." ".$horario[0];
                array_push($dataset, array($x, sizeof($revisaciones)) );
            }

        }

        

        return array(
            'senial' => $dataset,
        );
    }


    public static function getSenialFechasCSV($apiario, $colmena, $fechas, $horario) {

        // Datasets que voy a usar.
        $dataset = array();

        if( sizeof($horario) == 0  ) { 
            foreach( $fechas as $fecha ) {
                $revisaciones = RevisacionTemperaturaHumedad::where('apiario_id',$apiario)
                ->where('colmena_id',$colmena)
                ->where('fecha_revisacion', '=',$fecha)
                ->select('temperatura','humedad','fecha_revisacion','hora_revisacion')
                ->orderBy('id','asc')
                ->get(); 
    
                array_push($dataset, array(
                    "fecha" => $fecha,
                    "cantidad" => sizeof($revisaciones),
                ));
            }
        } else{
            foreach( $fechas as $fecha ) {
                $revisaciones = RevisacionTemperaturaHumedad::where('apiario_id',$apiario)
                ->where('colmena_id',$colmena)
                ->where('fecha_revisacion', '=',$fecha)
                ->where('hora_revisacion','>=',$horario[0])
                ->where('hora_revisacion','<=',$horario[sizeof($horario)-1])
                ->select('temperatura','humedad','fecha_revisacion','hora_revisacion')
                ->orderBy('id','asc')
                ->get(); 
    
                array_push($dataset, array(
                    "fecha" => $fecha,
                    "cantidad" => sizeof($revisaciones),
                ));
            }
        }
        
        

        return $dataset;
    }


    /**
     * Dado un apiario, devuelve un array para temperatura, otro para la humedad y otro
     * para la señal, cada uno clasificado como en verde, amarillo y rojo, en base a los valores 
     * de sus temperaturas y humedades.
     * 
     * @return Array (temperaturas, humedades, señales)
     */
    public static function getDashboardApiarios($apiario_id) {
        
        // Datasets
        $temperatura = array(
            'verde' => 0,
            'amarillo' => 0,
            'rojo' => 0,
        );

        $humedad = array(
            'verde' => 0,
            'amarillo' => 0,
            'rojo' => 0,
        );

        $senial = array(
            'verde' => 0,
            'amarillo' => 0,
            'rojo' => 0,
        );
        
        // Obtengo las colmenas
        $colmenas = Colmena::where('apiario_id',$apiario_id)->get();

        // Recorro las colmenas
        foreach($colmenas as $colmena) {
            
            // Busco la última revisación.
            $revisacion = RevisacionTemperaturaHumedad::where('colmena_id',$colmena->id)
                                                        ->orderBy('id','desc')
                                                        ->first();
            
            if( $revisacion ) {
                // Proceso la revisacion para obtener el color.
                $colorSenial = RevisacionTemperaturaHumedad::getColorSenial($revisacion);
                $colorTemperatura = RevisacionTemperaturaHumedad::getColorTemperatura($revisacion);
                $colorHumedad = RevisacionTemperaturaHumedad::getColorHumedad($revisacion);

                // Incremento los datasets
                $senial[$colorSenial]++;
                $temperatura[$colorTemperatura]++;
                $humedad[$colorHumedad]++;
            }
                                                        
        }

        // Obtengo la ultima t_y_h de la colmena 
        // Si la fecha es de antes de ayer, señal.push(amarillo)
        // Si la fecha es mayor a antes de ayer, rojo.
        // Sino verde
        // get color temperatura (estacion del año)
        // get color humedad (estacion del año)

        return array(
            'temperatura' => $temperatura,
            'humedad' => $humedad,
            'senial' => $senial,
        );
    }


    /**
     * Dada una revisación, determino el estado de la "señal" de esa revisación: verde, amarillo o rojo.
     * 
     * @return String
     */
    public static function getColorSenial($revisacion) {
        
        // Obtengo la fecha de hoy
        # $hoy = Date("Y-m-d");
        # $ayer = date('Y-m-d',strtotime("-1 days"));
        # $hace_dos_dias = date('Y-m-d',strtotime("-2 days"));

        // Si es hoy, estado verde.
        // Si es ayer y la hora actual es menor o igual a las 15hs, entonces verde.
        // Si es ayer y la hora actual es mayor a las 15hs, entonces amarillo.
        // Sino rojo.
        
        /*
                Logica vieja
                ------------
        if( $revisacion->fecha_revisacion == $hoy ) return "verde";
        elseif( $revisacion->fecha_revisacion == $ayer ) {
            $hora_actual = Date("H:i"); // Hora Argentina
            if( $hora_actual <= "15:00" ) {
                return "verde";
            }
            else{
                return "amarillo";
            }
        }
        //elseif( $revisacion->fecha_revisacion == $hace_dos_dias ) return "amarillo";
        else  return "rojo";
        
        */
        


        # Si hay diferencia horaria de 6 horas máximo, entonces, verde.
        # Si la diferencia horaria es de entre 6 y 12 horas, entonces amarillo.
        # Si es mayor a 12, rojo.
        if( !$revisacion ) return "rojo";

        $horaRevisacion = new DateTime($revisacion->fecha_revisacion." ".$revisacion->hora_revisacion);
        $horaActual = new DateTime();
        $diferencia = $horaRevisacion->diff($horaActual);
        $horas = $diferencia->days * 24 * 60;
        $horas += $diferencia->h * 60;
        $horas += $diferencia->i;
        $horas = $horas / 60;
        
        # Si la diferencia horaria entre la hora actual y la hora revisacion es menor a 6 horas, entonces verde. Ej.: hora_revisacion = 23:00 de ayer y hora_actual = 03:00 de hoy.
        if( $horas <= 2 ) return "verde"; // 6
        elseif( $horas > 2 && $horas <= 4 ) return "amarillo"; // Entre 6 y 12
        else return "rojo"; // Mayor a 12
    }


    /**
     * Dada una revisación, determino el estado de la temperatura: verde, amarillo o rojo.
     * 
     * @return String
     */
    public static function getColorTemperatura($revisacion) {

        // Get temporada
        $temporada = RevisacionTemperaturaHumedad::getTemporada($revisacion->fecha_revisacion);
        if( $temporada == "otonio" || $temporada == "invierno" ) {
            if( $revisacion->temperatura >= 18 && $revisacion->temperatura <= 36 ) return "verde";
            if( $revisacion->temperatura >= 14.5 && $revisacion->temperatura < 18 ) return "amarillo";
            if( $revisacion->temperatura > 36 && $revisacion->temperatura <= 36.5 ) return "amarillo";
            if( $revisacion->temperatura < 14.5 || $revisacion->temperatura > 36.5 ) return "rojo";
        }
        elseif( $temporada == "primavera" || $temporada == "verano" ) {
            if( $revisacion->temperatura >= 34 && $revisacion->temperatura <= 36 ) return "verde";
            if( $revisacion->temperatura >= 33.5 && $revisacion->temperatura < 34 ) return "amarillo";
            if( $revisacion->temperatura > 36 && $revisacion->temperatura <= 36.5 ) return "amarillo";
            if( $revisacion->temperatura > 36.5 || $revisacion->temperatura < 33.5  ) return "rojo";   
        }

    }


    public static function validarTemperatura($temperatura) {
        $temporada = RevisacionTemperaturaHumedad::getTemporada( date('Y-m-d') );
        if( $temporada == "otonio" || $temporada == "invierno" ) {
            if( $temperatura >= 18 && $temperatura <= 36 ) return "verde";
            if( $temperatura >= 14.5 && $temperatura < 18 ) return "amarillo";
            if( $temperatura > 36 && $temperatura <= 36.5 ) return "amarillo";
            if( $temperatura < 14.5 || $temperatura > 36.5 ) return "rojo";
        }
        elseif( $temporada == "primavera" || $temporada == "verano" ) {
            if( $temperatura >= 34 && $temperatura <= 36 ) return "verde";
            if( $temperatura >= 33.5 && $temperatura < 34 ) return "amarillo";
            if( $temperatura > 36 && $temperatura <= 36.5 ) return "amarillo";
            if( $temperatura > 36.5 || $temperatura < 33.5  ) return "rojo"; 
        }
    }



    /**
     * Dada una fecha, determina la temporada a la que pertenece.
     * 
     * Otoño: empieza el 21 de marzo, el día número 79 del año.
     * Invierno: empieza el 22 de junio, el día número 172 del año.
     * Primavera: empieza el 23 de septiembre, el día número 265 del año.
     * Verano: empieza el 19 de diciembre, el día número 352 del año.
     * 
     * @return String   
     */
    public static function getTemporada($fecha) {

        // Guardamos en una variable el día del año
        $dia = date("z", strtotime($fecha));
        
        // Comprobamos si es bisiesto
        $bisiesto = date('L');

        $verano=78;
        $otonio=171;
        $invierno=263;
        $verano=352;

        if( $bisiesto==1){
            $verano=79;
            $otonio=172;
            $primavera=264;
            $verano=353;
        }
        
        // Si la fecha actual es anterior al 21 de marzo
        if ( $dia <= 79 ) {
            $estacion = 'verano';
    
        // Si la fecha actual es anterior al 22 de junio
        } elseif ( $dia < 172 ) {
            $estacion = 'otonio';
    
        // Si la fecha actual es anterior al 23 de septiembre
        } elseif ( $dia < 264 ) {
            $estacion = 'invierno';
    
        // Si la fecha actual es anterior al 19 de diciembre
        } elseif ( $dia <= 354 ) {
            $estacion = 'primavera';
    
        // Si no es ninguna de las anteriores
        } else {
            $estacion = 'verano';
    
        }

        return $estacion;
        
    }

    /**
     * Dada una revisación, determina el estado de su humedad: verde, amarillo o rojo.
     * 
     * @return String
     */
    public static function getColorHumedad($revisacion) {

        //$temporada = RevisacionTemperaturaHumedad::getTemporada($revisacion->fecha_revisacion);

        if( $revisacion->humedad >= 65 && $revisacion->humedad <= 75 ) return "verde";
        if( $revisacion->humedad >= 50 && $revisacion->humedad < 65 ) return "amarillo";
        if( $revisacion->humedad > 75 && $revisacion->humedad <= 80 ) return "amarillo";
        if( $revisacion->humedad < 50 || $revisacion->humedad > 80 ) return "rojo";

    }

    public static function validarHumedad($humedad) {

        if( $humedad >= 65 && $humedad <= 75 ) return "verde";
        if( $humedad >= 50 && $humedad < 65 ) return "amarillo";
        if( $humedad > 75 && $humedad <= 80 ) return "amarillo";
        if( $humedad < 50 || $humedad > 80 ) return "rojo";
    }


    /**
     * Dada una colmena de un apiario, y un rango de fechas, devuelve todas las revisaciones 
     * en ese rango.
     * 
     * @return Array
     */
    public static function obtenerTyHColmena($apiario, $colmena, $fechas) {

        $dataset = array();
        
        foreach( $fechas as $fecha ) {

            $revisaciones = RevisacionTemperaturaHumedad::where('apiario_id',$apiario)
                                                        ->where('colmena_id',$colmena)
                                                        ->where('fecha_revisacion',$fecha)
                                                        ->get();

            if( sizeof($revisaciones) != 0 ) array_push($dataset, $revisaciones);

        }

        return array(
            'datos' => $dataset,
        );
    }


    /**
     * Hace lo mismo que el método obtenerTyHColmena, nada más que aplica el 
     * método "merge" para unificar el array de revisaiones. Esto solucionó
     * un bugfix en el frontend.
     * 
     */
    public static function obtenerTyHColmenaMerge($apiario, $colmena, $fechas) {

        $dataset = array();
        
        foreach( $fechas as $fecha ) {

            $revisaciones = RevisacionTemperaturaHumedad::where('apiario_id',$apiario)
                                                        ->where('colmena_id',$colmena)
                                                        ->where('fecha_revisacion',$fecha)
                                                        ->get();

            if( sizeof($revisaciones) != 0 ) $dataset=array_merge($dataset, $revisaciones->toArray());

        }

        return array(
            'datos' => $dataset,
        );
    }


    /**
     * En base al estado de la colmena, devuelve un mensaje respecto a su estado, 
     * cuanta diferencia hay entre la temperatura de la colmena y los parámetros normales, y 
     * 
     * 
     * @param RevisacionTemperaturaHumedad
     * @return String
     *  
     *
     */
    public static function getMensajeTempertura($revisacion) {

        $TEMPERATURA_MINIMA = 0;
        $TEMPERATURA_MAXIMA = 0;

        $color_temperatura = RevisacionTemperaturaHumedad::validarTemperatura($revisacion->temperatura);
        $estado = "";
        if( $color_temperatura == "amarillo" ) $estado = "Alerta";
        elseif( $color_temperatura == "rojo" ) $estado = "Peligro";

        $temporada = RevisacionTemperaturaHumedad::getTemporada($revisacion->fecha_revisacion);
        if( $temporada == "otonio" || $temporada == "invierno" ) {
            $TEMPERATURA_MINIMA = 18;
            $TEMPERATURA_MAXIMA = 36;
        }
        elseif( $temporada == "primavera" || $temporada == "verano" ) {
            $TEMPERATURA_MINIMA = 34;
            $TEMPERATURA_MAXIMA = 36;
        }


        if( $revisacion->temperatura > $TEMPERATURA_MAXIMA ) {
            $diferencia = round($revisacion->temperatura - $TEMPERATURA_MAXIMA,2);
            return "La Temperatura está ".$diferencia."°C por encima de lo normal. Estado: ".$estado.".";
        }
        elseif( $revisacion->temperatura < $TEMPERATURA_MINIMA ) {
            $diferencia = round($TEMPERATURA_MINIMA - $revisacion->temperatura,2);
            return "La Temperatura está ".$diferencia."°C por debajo de lo normal. Estado: ".$estado.".";
        }
        else {
            return "La Temperatura es correcta.";
        }
    }


    /**
     * En base al estado de la colmena, devuelve un mensaje respecto a su estado, 
     * cuanta diferencia hay entre la humedad de la colmena y los parámetros normales.
     * 
     * 
     * @param RevisacionTemperaturaHumedad
     * @return String
     *  
     *
     */
    public static function getMensajeHumedad($revisacion) {

        $HUMEDAD_MINIMA = 65;
        $HUMEDAD_MAXIMA = 75;

        $color_humedad = RevisacionTemperaturaHumedad::validarHumedad($revisacion->humedad);
        $estado = "";
        if( $color_humedad == "amarillo" ) $estado = "Alerta";
        elseif( $color_humedad == "rojo" ) $estado = "Peligro";
    
        if( $revisacion->humedad > $HUMEDAD_MAXIMA ) {
            $diferencia = round($revisacion->humedad - $HUMEDAD_MAXIMA,2);
            return "La Humedad está ".$diferencia."% por encima de lo normal. Estado: ".$estado.".";
        }
        elseif( $revisacion->humedad < $HUMEDAD_MINIMA ) {
            $diferencia = $HUMEDAD_MINIMA - $revisacion->humedad;
            return "La Humedad está ".$diferencia."% por debajo de lo normal. Estado: ".$estado.".";
        }
        else {
            return "La Humedad es correcta.";
        }
    }
    

    /**
     * Dada una colmena retorna su estado {Buen estado, Alerta, Peligro}.
     * 
     * @param Colmena
     * @return Array (colmena_id, estado)
     */
    public static function getEstadoColmena($colmena) {

        // Busco la última revisación.
        $revisacion = RevisacionTemperaturaHumedad::where('colmena_id',$colmena->id)
        ->orderBy('id','desc')
        ->first();

        if( !$revisacion ) return array($colmena->id, "Peligro");
        
        $estado = "";

        // Proceso la revisacion para obtener el color.
        $colorSenial = RevisacionTemperaturaHumedad::getColorSenial($revisacion);
        $colorTemperatura = RevisacionTemperaturaHumedad::getColorTemperatura($revisacion);
        $colorHumedad = RevisacionTemperaturaHumedad::getColorHumedad($revisacion);

        if( $colorSenial == "rojo"  ) $estado = "Peligro";
        else if( $colorTemperatura == "rojo"  || $colorHumedad == "rojo" ) $estado = "Peligro";
        else if( $colorSenial == "amarillo" ) $estado = "Alerta"; // corregido :)
        else if( $colorTemperatura == "amarillo"  || $colorHumedad == "amarillo" ) $estado = "Alerta";
        else $estado = "Buen estado.";

        return array($colmena->id,$estado);
    }
       
    
}

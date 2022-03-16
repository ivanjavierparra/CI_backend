<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Colmena;
use App\Apiario;
use App\Chacra;
use \DateTime;

class Colmena extends Model
{
    protected $table = 'colmenas';
        
    protected $primaryKey = 'id';

    protected $fillable = array(
        'apiario_id',
        'raza_abeja',
        'identificacion',
        'fecha_creacion',
        'descripcion',
        'eliminado',
    );

    /**
     * Permite crear una Colmena.
     * 
     */
    public static function crearColmena($datos) {
        
        // Verifico si existe alguna colmena con un numero ya existente.
        $colmena = Colmena::where('apiario_id', $datos['apiario_id'])->where('identificacion',$datos['identificacion'])->first();

        // Si existe chacra, retorno error.
        if($colmena) {
            return array(
                'resultado' => 500,
                'mensaje' => 'Ya existe una colmena con la identificación ingresada.'
            );
        }
        else {
            try {
                $objetoColmena = Colmena::create($datos);
                return array(
                    'resultado' => 200,
                    'mensaje' => 'Colmena creada.'
                );
            } catch(\Exception $e) {
                return array(
                    'resultado' => 200,
                    'mensaje' => $e->getMessage()
                );
            }
        }

    }

    /**
     * Permite editar la identificación y la descripción de una colmena.
     * @param int $datos es un array que contiene [id_colmena, identificacion, descripcion]
     *
     * @return Array [resultado, mensaje, colmena_editada]
     */
    public static function editarColmena($datos) {

        // Verifico si existe alguna colmena con un numero ya existente.
        $colmena = Colmena::where('id', $datos['colmena_id'])->first();

        // Si existe chacra, retorno error.
        if(!$colmena) {
            return array(
                'resultado' => 500,
                'mensaje' => 'El id de la colmena no se encuentra en la Base de Datos.'
            );
        }
        else {
            try {
                $colmena->identificacion = $datos['identificacion'];
                $colmena->raza_abeja = $datos['raza_abeja'];
                $colmena->descripcion = $datos['descripcion'];
                $colmena->save();
                return array(
                    'resultado' => 200,
                    'mensaje' => 'Colmena editada.',
                    'colmena' => $colmena,
                );
            } catch(\Exception $e) {
                return array(
                    'resultado' => 200,
                    'mensaje' => $e->getMessage()
                );
            }
        }
    }

    /**
     * Dado el id de una colmena, retorna su número.
     */
    public static function getNumeroDeColmena($colmena_id) { 
        $colmena = Colmena::where('id', $colmena_id)->first();
        return $colmena['numero'];
    }


    /**
     * Retorna todas las colmenas existentes.
     * 
     */
    public static function getColmenas() {
        // Creo un arreglo para guardar los resultados.
        $resultado = array();
        // Obtengo todas las colmenas.
        $colmenas = Colmena::all();
        // Recorro las colmenas.
        foreach($colmenas as $colmena) {
            // Creo estructura de respuesta.  
            $apiario = Apiario::where('id', $colmena->apiario_id)->first();  
            
            $datos = array(
                'id_colmena' => $colmena->id,
                'apiario' => "Apiario - ".$apiario->direccion_chacra." - (".$apiario->localidad_chacra.")",
                'identificacion' => $colmena->identificacion,
                'fecha_creacion' => $colmena->fecha_creacion,
                'descripcion' => $colmena->descripcion,
            );
            // Agrego datos al resultados.
            array_push($resultado,$datos);
        }
        return $resultado;
    }

    /**
     * Dado un estado o color {verde, amarillo, rojo}, y una ciudad, se devuelven la cantidad de colmenas
     * de un apiario que están en ese estado.
     * 
     * @return Integer 
     */
    public static function contarColmenasSegunEstado($ciudad, $color, $usuario) {

        $contador = 0;
        $apiarios = Apiario::where('localidad_chacra',$ciudad)->where('apicultor_id',$usuario->id)->get();

        foreach($apiarios as $apiario) {

            $colmenas = Colmena::where('apiario_id',$apiario->id)->select('id')->get();

            foreach( $colmenas as $colmena ) {
                // Busco la última revisación.
                $revisacion = RevisacionTemperaturaHumedad::where('colmena_id',$colmena->id)
                                                            ->orderBy('id','desc')
                                                            ->first();
                
                if( $revisacion ) {
                    // Proceso la revisacion para obtener el color.
                    $colorSenial = RevisacionTemperaturaHumedad::getColorSenial($revisacion);
                    $colorTemperatura = RevisacionTemperaturaHumedad::getColorTemperatura($revisacion);
                    $colorHumedad = RevisacionTemperaturaHumedad::getColorHumedad($revisacion);

                    
                    if( $color == "verde" )  {
                        // Solamente incremento el verde si temperatura, humedad y señal son verdes.
                        if( $colorTemperatura == "verde" && $colorHumedad == "verde" && $colorSenial == "verde" ) $contador++;
                    }
                    elseif( $color == "amarillo" ) {
                        // Solamente incremento el amarillo si temperatura y/o humedad  y/o señal es amarilla, si señal es roja no.
                        // if( $colorSenial == "rojo" ) continue;
                        if( $colorSenial == "rojo" || $colorTemperatura == "rojo" || $colorHumedad == "rojo" ) continue;
                        if( $colorTemperatura == "amarillo" || $colorHumedad == "amarillo" || $colorSenial == "amarillo" ) $contador++;
                    }
                    elseif( $color == "rojo")  {
                        if( $colorTemperatura == "rojo" || $colorHumedad == "rojo" || $colorSenial == "rojo" ) $contador++;
                    }
                    else{
                        // Naranja fanta...
                    }
                }
                else {
                    // Si entro acá es porque la colmena no tiene revisaciones, por lo que aumento el rojo.
                    if( $color == "rojo")  $contador++;
                }
            }
        }

        return $contador;
    }

    public static function getColmenasEnAlertayEnPeligro($ciudad, $apiario_id, $usuario) {
        
        $apiarios = array();

        if( $apiario_id != "" ) $apiarios = Apiario::where("id",$apiario_id)->get();
        elseif ( $ciudad == "Todos" ) $apiarios = Apiario::where("apicultor_id",$usuario->id)->get();
        elseif( $ciudad == "Trelew" || $ciudad == "Rawson" || $ciudad == "Gaiman" || $ciudad == "Dolavon" || $ciudad == "28 de Julio" ) $apiarios = Apiario::where("apicultor_id",$usuario->id)->where('localidad_chacra',$ciudad)->get();

        /* Codigo Viejo
        if( $ciudad == "Todos" ) $apiarios = Apiario::where("apicultor_id",$usuario->id)->get();
        else if( $apiario_id ) $apiarios = Apiario::where('id',$apiario_id)->where("apicultor_id",$usuario->id)->get();
        else $apiarios = Apiario::where('localidad_chacra',$ciudad)->where("apicultor_id",$usuario->id)->get();        
        */
        
        $resultado = array();

        foreach( $apiarios as $apiario ) {

            $colmenas = Colmena::where('apiario_id',$apiario->id)->get();
            $colmenas_en_alerta = array();
            $colmenas_en_peligro = array();

            foreach( $colmenas as $colmena ) {

                // Busco la última revisación.
                $revisacion = RevisacionTemperaturaHumedad::where('colmena_id',$colmena->id)
                ->orderBy('id','desc')
                ->first();
    
                if( $revisacion ) {
                    
                    // Proceso la revisacion para obtener el color.
                    $colorSenial = RevisacionTemperaturaHumedad::getColorSenial($revisacion);
                    $colorTemperatura = RevisacionTemperaturaHumedad::getColorTemperatura($revisacion);
                    $colorHumedad = RevisacionTemperaturaHumedad::getColorHumedad($revisacion);
    
                    if( $colorSenial == "rojo" ) array_push($colmenas_en_peligro, array($colmena,$revisacion));
                    else if( $colorSenial == "amarillo" || $colorTemperatura == "amarillo" || $colorHumedad == "amarillo" ) array_push($colmenas_en_alerta, array($colmena,$revisacion));
                    else continue;
    
                }
                else { // Si entro acá es porque la colmena no tiene revisaciones.
                     array_push($colmenas_en_peligro, array($colmena,$revisacion));
                }
            }

            if(  sizeof($colmenas_en_alerta) == 0 && sizeof($colmenas_en_peligro) == 0 ) continue;

            $parcial = array(
                "apiario" => $apiario,
                "amarillo" => $colmenas_en_alerta,
                "rojo" => $colmenas_en_peligro,
            );

            array_push($resultado,$parcial);
        }
        return $resultado;
    }



    /**
     * Dada una revisación, determino el estado de la temperatura: verde, amarillo o rojo.
     * 
     * @return String
     */
    public static function getMensajeTemperatura($colmena_id) {

        $revisacion = RevisacionTemperaturaHumedad::where("colmena_id",$colmena_id)->orderby("id","desc")->first();
        if( !$revisacion ) return "No hay datos";

        $temporada = RevisacionTemperaturaHumedad::getTemporada($revisacion->fecha_revisacion);
        if( $temporada == "otonio" || $temporada == "invierno" ) {
            if( $revisacion->temperatura >= 18 && $revisacion->temperatura <= 36 ) return "En buen estado (".$revisacion->temperatura."°C).";
            if( $revisacion->temperatura >= 14.5 && $revisacion->temperatura < 18 ) return "En alerta: Temperatura mínimamente desviada (".$revisacion->temperatura."°C).";
            if( $revisacion->temperatura > 36 && $revisacion->temperatura <= 36.5 ) return "En alerta: Temperatura mínimamente desviada (".$revisacion->temperatura."°C).";
            if( $revisacion->temperatura < 14.5 || $revisacion->temperatura > 36.5 ) return "En peligro: temperatura extrema (".$revisacion->temperatura."°C).";
        }
        elseif( $temporada == "primavera" || $temporada == "verano" ) {
            if( $revisacion->temperatura >= 34 && $revisacion->temperatura <= 36 ) return "En buen estado (".$revisacion->temperatura."°C).";
            if( $revisacion->temperatura >= 33.5 && $revisacion->temperatura < 34 ) return "En alerta: Temperatura mínimamente desviada (".$revisacion->temperatura."°C).";
            if( $revisacion->temperatura > 36 && $revisacion->temperatura <= 36.5 ) return "En alerta: Temperatura mínimamente desviada (".$revisacion->temperatura."°C).";
            if( $revisacion->temperatura > 36.5 || $revisacion->temperatura < 33.5  ) return "En peligro: temperatura extrema (".$revisacion->temperatura."°C).";   
        }
    }


    public static function getMensajeHumedad($colmena_id) {

        $revisacion = RevisacionTemperaturaHumedad::where("colmena_id",$colmena_id)->orderby("id","desc")->first();
        if( !$revisacion ) return "No hay datos";

        if( $revisacion->humedad >= 65 && $revisacion->humedad <= 75 ) return "En buen estado (".$revisacion->humedad."%).";
        if( $revisacion->humedad >= 50 && $revisacion->humedad < 65 ) return "En alerta: Humedad mínimamente desviada (".$revisacion->humedad."%).";
        if( $revisacion->humedad > 75 && $revisacion->humedad <= 80 ) return "En alerta: Humedad mínimamente desviada (".$revisacion->humedad."%).";
        if( $revisacion->humedad < 50 || $revisacion->humedad > 80 ) return "En peligro: Humedad extrema (".$revisacion->humedad."%).";

    }


    public static function getMensajeSenial($colmena_id) {
        
        $revisacion = RevisacionTemperaturaHumedad::where("colmena_id",$colmena_id)->orderby("id","desc")->first();
        if( !$revisacion ) return "No hay datos";

        $horaRevisacion = new DateTime($revisacion->fecha_revisacion." ".$revisacion->hora_revisacion);
        $horaActual = new DateTime();
        $diferencia = $horaRevisacion->diff($horaActual);
        $horas = $diferencia->days * 24 * 60;
        $horas += $diferencia->h * 60;
        $horas += $diferencia->i;
        $horas = $horas / 60;
        
        # Si la diferencia horaria entre la hora actual y la hora revisacion es menor a 6 horas, entonces verde. Ej.: hora_revisacion = 23:00 de ayer y hora_actual = 03:00 de hoy.
        if( $horas <= 2 ) return "Datos válidos."; // 6
        elseif( $horas > 2 && $horas <= 4 ) return "Datos semi-válidos."; // Entre 6 y 12
        else return "Datos obsoletos."; // Mayor a 12
    }
}

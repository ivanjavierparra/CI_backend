<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\RevisacionTemperaturaHumedad;
use App\Apiario;
use App\Colmena;
use App\User;

class Notificacion extends Model
{
    protected $table = 'notificaciones';
        
    protected $primaryKey = 'id';

    protected $fillable = array(
        'apicultor_id',
        'apiario_id',
        'colmena_id',
        'icono', // fa fa-warning    fa fa-wifi
        'class',  // text-yellow text-red     ====> <i className="fa fa-warning text-yellow"
        'texto',
        'leida',
        'eliminada',
        'fecha',
        'hora',
        'tipo',
    );

    /**
     * Crea notificaciones de temperatura y humedad para todos los apicultores.
     * @param $apiario_id
     * @param $colmena_id
     * @param $temperatura
     * @param $humedad
     */
    public static function crearNotificaciones($apiario_id, $colmena_id, $temperatura, $humedad) {

        // Validaciones
        $apiario = Apiario::where('id',$apiario_id)->first();
        if ( !$apiario ) return;
        $colmena = Colmena::where('id',$colmena_id)->where('apiario_id',$apiario_id)->first();
        if( !$colmena ) return;
        $apicultor = User::where('id',$apiario->apicultor_id)->first();

        // Obtengo la ultima revisacion
        $ultima_revisacion = RevisacionTemperaturaHumedad::where('apiario_id',$apiario_id)->where('colmena_id',$colmena_id)->orderBy('id','desc')->first();

        // Si no recibo datos desde hace varios días, entonces no creo notificacón de temperatura y humedad.
        $color_senial = RevisacionTemperaturaHumedad::getColorSenial($ultima_revisacion);
        if( $color_senial == "amarillo" || $color_senial == "rojo" ) return;


        Notificacion::crearNotificacionTemperatura($apicultor, $apiario, $colmena, $ultima_revisacion, $temperatura);        
        Notificacion::crearNotificacionHumedad($apicultor, $apiario, $colmena, $ultima_revisacion, $humedad); 
    }



    public static function crearNotificacionTemperatura($apicultor, $apiario, $colmena, $revisacion, $temperatura) {

        // Valido que la temperatura sea amarilla o roja
        $color_temperatura = RevisacionTemperaturaHumedad::validarTemperatura($temperatura);
        
        if( $color_temperatura == "amarillo" && $revisacion  ) {
            
            $diferencia = (float) ($revisacion->temperatura - $temperatura);
            if( $diferencia == 0.0 ) {$diferencia = $diferencia * -1; Notificacion::se_mantiene_temperatura($apicultor, $apiario, $colmena, "rojo",$diferencia * -1, $temperatura);}
            elseif( $diferencia <= 0 ) Notificacion::subio_temperatura($apicultor, $apiario, $colmena, "amarillo",$diferencia * -1, $temperatura);
            else Notificacion::bajo_temperatura($apicultor, $apiario, $colmena, "amarillo",$diferencia, $temperatura);
        }
        elseif( $color_temperatura == "rojo" && $revisacion ) {
            
            $diferencia = (float) ($revisacion->temperatura - $temperatura);
            if( $diferencia == 0.0 ) {$diferencia = $diferencia * -1; Notificacion::se_mantiene_temperatura($apicultor, $apiario, $colmena, "rojo",$diferencia * -1, $temperatura);}
            elseif( $diferencia <= 0 ) Notificacion::subio_temperatura($apicultor, $apiario, $colmena, "rojo",$diferencia * -1, $temperatura);
            else Notificacion::bajo_temperatura($apicultor, $apiario, $colmena, "rojo",$diferencia, $temperatura);
        }
    }

    public static function se_mantiene_temperatura($apicultor, $apiario, $colmena, $estado, $diferencia, $temperatura) {

        $datos = array(
            'apicultor_id' => $apicultor->id,
            'apiario_id' => $apiario->id,
            'colmena_id' => $colmena->id,
            'icono' => "fa fa-arrow-up", // fa fa-warning    fa fa-wifi
            'class' => $estado == "amarillo" ? "text-yellow" : "text-red",  // text-yellow text-red     ====> <i className="fa fa-warning text-yellow"
            'texto' => "Temperatura en la colmena ".$colmena->identificacion." del apiario ".$apiario->direccion_chacra.": ".$temperatura."°C.",
            'leida' => false,
            'eliminada' => false,
            'fecha' => date('Y-m-d'),
            'hora'=> date("H:i"),
            'tipo' => "temperatura",
        );
        
        Notificacion::create($datos);
    }

    public static function subio_temperatura($apicultor, $apiario, $colmena, $estado, $diferencia, $temperatura) {

        $datos = array(
            'apicultor_id' => $apicultor->id,
            'apiario_id' => $apiario->id,
            'colmena_id' => $colmena->id,
            'icono' => "fa fa-arrow-up", // fa fa-warning    fa fa-wifi
            'class' => $estado == "amarillo" ? "text-yellow" : "text-red",  // text-yellow text-red     ====> <i className="fa fa-warning text-yellow"
            'texto' => "La temperatura subió ".$diferencia."°C en la colmena ".$colmena->identificacion." del apiario ".$apiario->direccion_chacra.". Temperatura actual: ".$temperatura."°C",
            'leida' => false,
            'eliminada' => false,
            'fecha' => date('Y-m-d'),
            'hora'=> date("H:i"),
            'tipo' => "temperatura",
        );
        
        Notificacion::create($datos);
    }

    public static function bajo_temperatura($apicultor, $apiario, $colmena, $estado, $diferencia, $temperatura) {

        $datos = array(
            'apicultor_id' => $apicultor->id,
            'apiario_id' => $apiario->id,
            'colmena_id' => $colmena->id,
            'icono' => "fa fa-arrow-down", // fa fa-warning    fa fa-wifi
            'class' => $estado == "amarillo" ? "text-yellow" : "text-red",  // text-yellow text-red     ====> <i className="fa fa-warning text-yellow"
            'texto' => "La temperatura bajó ".$diferencia."°C en la colmena ".$colmena->identificacion." del apiario ".$apiario->direccion_chacra.". Temperatura actual: ".$temperatura."°C",
            'leida' => false,
            'eliminada' => false,
            'fecha' => date('Y-m-d'),
            'hora'=> date("H:i"),
            'tipo' => "temperatura",
        );
        
        Notificacion::create($datos);
    }


    public static function crearNotificacionHumedad($apicultor, $apiario, $colmena, $revisacion, $humedad) {

        // Valido que la temperatura sea amarilla o roja
        $color_humedad = RevisacionTemperaturaHumedad::validarHumedad($humedad);

        if( $color_humedad == "amarillo" && $revisacion  ) {

            $diferencia = $revisacion->humedad - $humedad;
            $diferencia = (float) ($revisacion->humedad - $humedad);
            if( $diferencia == 0.0 ) {$diferencia = $diferencia * -1; Notificacion::se_mantiene_humedad($apicultor, $apiario, $colmena, "rojo",$diferencia * -1, $humedad);}
            elseif( $diferencia <= 0 ) Notificacion::subio_humedad($apicultor, $apiario, $colmena, "amarillo",$diferencia * -1, $humedad);
            else Notificacion::bajo_humedad($apicultor, $apiario, $colmena, "amarillo",$diferencia, $humedad);
        }
        elseif( $color_humedad == "rojo" ) {
            
            $diferencia = (float) ($revisacion->humedad - $humedad);
            if( $diferencia == 0.0 ) {$diferencia = $diferencia * -1; Notificacion::se_mantiene_humedad($apicultor, $apiario, $colmena, "rojo",$diferencia * -1, $humedad);}
            elseif( $diferencia <= 0 ) Notificacion::subio_humedad($apicultor, $apiario, $colmena, "rojo",$diferencia * -1, $humedad);
            else Notificacion::bajo_humedad($apicultor, $apiario, $colmena, "rojo",$diferencia, $humedad);
        }
    }

    public static function se_mantiene_humedad($apicultor, $apiario, $colmena, $estado, $diferencia, $humedad) {

        $datos = array(
            'apicultor_id' => $apicultor->id,
            'apiario_id' => $apiario->id,
            'colmena_id' => $colmena->id,
            'icono' => "fa fa-arrow-up", // fa fa-warning    fa fa-wifi
            'class' => $estado == "amarillo" ? "text-yellow" : "text-red",  // text-yellow text-red     ====> <i className="fa fa-warning text-yellow"
            'texto' => "Humedad en la colmena ".$colmena->identificacion." del apiario ".$apiario->direccion_chacra.": ".$humedad."%.",
            'leida' => false,
            'eliminada' => false,
            'fecha' => date('Y-m-d'),
            'hora'=> date("H:i"),
            'tipo' => "temperatura",
        );
        
        Notificacion::create($datos);
    }

    public static function subio_humedad($apicultor, $apiario, $colmena, $estado, $diferencia, $humedad) {

        $datos = array(
            'apicultor_id' => $apicultor->id,
            'apiario_id' => $apiario->id,
            'colmena_id' => $colmena->id,
            'icono' => "fa fa-arrow-up", // fa fa-warning    fa fa-wifi
            'class' => $estado == "amarillo" ? "text-yellow" : "text-red",  // text-yellow text-red     ====> <i className="fa fa-warning text-yellow"
            'texto' => "La humedad subió ".$diferencia."% en la colmena ".$colmena->identificacion." del apiario ".$apiario->direccion_chacra.". Humedad actual: ".$humedad."%",
            'leida' => false,
            'eliminada' => false,
            'fecha' => date('Y-m-d'),
            'hora'=> date("H:i"),
            'tipo' => "humedad",
        );
        
        Notificacion::create($datos);
    }

    public static function bajo_humedad($apicultor, $apiario, $colmena, $estado, $diferencia, $humedad) {

        $datos = array(
            'apicultor_id' => $apicultor->id,
            'apiario_id' => $apiario->id,
            'colmena_id' => $colmena->id,
            'icono' => "fa fa-arrow-down", // fa fa-warning    fa fa-wifi
            'class' => $estado == "amarillo" ? "text-yellow" : "text-red",  // text-yellow text-red     ====> <i className="fa fa-warning text-yellow"
            'texto' => "La humedad bajó ".$diferencia."% en la colmena ".$colmena->identificacion." del apiario ".$apiario->direccion_chacra.". Humedad actual: ".$humedad."%",
            'leida' => false,
            'eliminada' => false,
            'fecha' => date('Y-m-d'),
            'hora'=> date("H:i"),
            'tipo' => "humedad",
        );
        
        Notificacion::create($datos);
    }

    public static function setNotificacionesLeidas($usuario_id) {

        $notificaciones = Notificacion::where('apicultor_id',$usuario_id)->where('leida',false)->where('eliminada',false)->get();

        foreach($notificaciones as $notificacion) {
            $notificacion->leida = true;
            $notificacion->save();
        }
    }


    /**
     * CRON
     */
    public static function crearNotificacionSenal() {

        $colmenas = Colmena::all();

        foreach( $colmenas as $colmena ) {

            // Busco la última revisación.
            $revisacion = RevisacionTemperaturaHumedad::where('colmena_id',$colmena->id)
            ->orderBy('id','desc')
            ->first();

            $apiario = Apiario::where('id',$colmena->apiario_id)->first();
            $apicultor = User::where('id',$apiario->apicultor_id)->first();
        
            if( $revisacion ) {
                
                // Proceso la revisacion para obtener el color.
                $colorSenial = RevisacionTemperaturaHumedad::getColorSenial($revisacion);
                
                if( $colorSenial == "amarillo" ){

                    $datos = array(
                        'apicultor_id' => $apicultor->id,
                        'apiario_id' => $apiario->id,
                        'colmena_id' => $colmena->id,
                        'icono' => "fa fa-wifi", // fa fa-warning    fa fa-wifi
                        'class' => $colorSenial == "amarillo" ? "text-yellow" : "text-red",  // text-yellow text-red     ====> <i className="fa fa-warning text-yellow"
                        'texto' => "La colmena ".$colmena->identificacion." del apiario ".$apiario->direccion_chacra." no recibe datos desde el día ".date("d-m-Y H:i", strtotime($revisacion->fecha_revisacion." ".$revisacion->hora_revisacion))." hs.",
                        'leida' => false,
                        'eliminada' => false,
                        'fecha' => date('Y-m-d'),
                        'hora'=> date("H:i"),
                        'tipo' => "senial",
                    );

                    Notificacion::create($datos);

                } 
                elseif ( $colorSenial == "rojo" ) {

                    $datos = array(
                        'apicultor_id' => $apicultor->id,
                        'apiario_id' => $apiario->id,
                        'colmena_id' => $colmena->id,
                        'icono' => "fa fa-wifi", // fa fa-warning    fa fa-wifi
                        'class' => $colorSenial == "amarillo" ? "text-yellow" : "text-red",  // text-yellow text-red     ====> <i className="fa fa-warning text-yellow"
                        'texto' => "La colmena ".$colmena->identificacion." del apiario ".$apiario->direccion_chacra." no recibe datos desde el día ".date("d-m-Y H:i", strtotime($revisacion->fecha_revisacion." ".$revisacion->hora_revisacion))." hs.",
                        'leida' => false,
                        'eliminada' => false,
                        'fecha' => date('Y-m-d'),
                        'hora'=> date("H:i"),
                        'tipo' => "senial",
                    );

                    Notificacion::create($datos);
                }
                

            }
            else { // Si entro acá es porque la colmena no tiene revisaciones.
                
                $datos = array(
                    'apicultor_id' => $apicultor->id,
                    'apiario_id' => $apiario->id,
                    'colmena_id' => $colmena->id,
                    'icono' => "fa fa-warning", // fa fa-warning    fa fa-wifi
                    'class' => "text-yellow",  // text-yellow text-red     ====> <i className="fa fa-warning text-yellow"
                    'texto' => "La colmena ".$colmena->identificacion." del apiario ".$apiario->direccion_chacra." nunca recibió datos.",
                    'leida' => false,
                    'eliminada' => false,
                    'fecha' => date('Y-m-d'),
                    'hora'=> date("H:i"),
                    'tipo' => "senial",
                );

                Notificacion::create($datos);
            }
        }

    }
}

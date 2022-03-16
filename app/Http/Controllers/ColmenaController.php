<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Colmena;
use App\Apiario;
use App\RevisacionTemperaturaHumedad;
use Illuminate\Support\Facades\Auth;
use JWTAuth;

class ColmenaController extends Controller
{
    
    /**
     * Crea una colmena.
     * 
     * @return Colmena
     */
    public function crearColmena(Request $request) {
        
        // Obtengo datos de la colmena.
        $datos = array(
            'apiario_id' => $request['apiario_id'],
            'identificacion' => $request['identificacion'],            
            'fecha_creacion' => $request['fecha_creacion'],
            'raza_abeja' => $request['raza_abeja'],
            'descripcion' => $request['descripcion'],
        );
        
        // Creo nueva chacra.
        $resultado = Colmena::crearColmena($datos);

        // Retorno resultado
        return response()->json($resultado, 200);
    }

    /**
     * Editar una colmena.
     * 
     * @return Colmena
     */
    public function editarColmena(Request $request) {

        // *TODO*: Verificación de que la colmena es de un apiario
        // que petenece al <apicultor class=""></apicultor>

        // Obtengo datos de la colmena.
        $datos = array(
            'colmena_id' => $request['id_colmena'],
            'identificacion' => $request['identificacion'],            
            'raza_abeja' => $request['raza_abeja'],  
            'descripcion' => $request['descripcion'],
        );
        
        // Creo nueva chacra.
        $resultado = Colmena::editarColmena($datos);

        // Retorno resultado
        return response()->json($resultado, 200);
    }

    
    /**
     * Retorna todas las colmenas existentes.
     * 
     */
    public static function getColmenas(Request $request) {
        
        // Retorno resultado
        return response()->json(Colmena::getColmenas(), 200);
    }


    /**
     * Dado una apiario y una colmena, devuelvo la última revisación existente.
     * 
     * @return Array (Revisacion)
     */
    public function getUltimaRevisacion(Request $request) {

        // Valido que el apiario y la colmena pertenezcan al apicultor.
        $usuario = JWTAuth::parseToken()->authenticate();
        /*$apiario_validar = Apiario::where('id',$request['apiario_id'])->where('apicultor_id',$usuario->id)->first();
        if( !$apiario_validar ) {
            // Return error.....
        }
        $colmena_validar = Colmena::where('apiario_id',$apiario_validar->id)->where('id',$request['colmena_id'])->first();
        if ( !$colmena_validar ) {
            // Return error....
        }*/

        $resultado = array();

        $ultima_revisacion = RevisacionTemperaturaHumedad::where('apiario_id',$request['apiario_id'])->where('colmena_id',$request['colmena_id'])->orderBy('id', 'desc')->first();

        if( $ultima_revisacion ) {

            // TODO **
            $colorSenial = RevisacionTemperaturaHumedad::getColorSenial($ultima_revisacion);

            $mensaje = "";
            if( $colorSenial == "rojo"  ) $mensaje = "Datos obsoletos";

            // Busco mensaje temperatura: Si la temperatua está ok: temperatua OK, sino temperatura 5°c por enciam de lo normal.
            $mensaje_temperatura = RevisacionTemperaturaHumedad::getMensajeTempertura($ultima_revisacion);

            // Busco mensaje humedad.
            $mensaje_humedad = RevisacionTemperaturaHumedad::getMensajeHumedad($ultima_revisacion);

            array_push($resultado, $ultima_revisacion, $mensaje, $mensaje_temperatura, $mensaje_humedad);
        }

        // Retorno resultado
        return response()->json($resultado, 200);

    }


    /**
     * Dado un color {verde, amarillo, rojo} que representa el estado de la colmena, devuelvo
     * las colmenas por ciudad que están en dicho estado (o sea, color).
     * 
     * @return Array (Ciudad, Datos)
     */
    public function alertasColmenasCiudad(Request $request) {

        $usuario = JWTAuth::parseToken()->authenticate();

        $color = $request['estado']; // verde, amarillo, rojo --> indican el estado de la colmena.
        
        $resultado = array(
            'Rawson' => Colmena::contarColmenasSegunEstado("Rawson", $color, $usuario),
            'Trelew' => Colmena::contarColmenasSegunEstado("Trelew", $color, $usuario),
            'Gaiman' => Colmena::contarColmenasSegunEstado("Gaiman", $color, $usuario),
            'Dolavon' => Colmena::contarColmenasSegunEstado("Dolavon", $color, $usuario),
            '28 de Julio' => Colmena::contarColmenasSegunEstado("28 de Julio", $color, $usuario),
       );

       return response()->json($resultado, 200);

    }

    /**
     * Por cada ciudad, devuelvo la cantidad de colmenas en cada estado (verde, amarillo, rojo).
     * 
     * @return Array (ciudad, colores, apiarios, colmenas)
     */
    public function alertasDashboard(Request $request) {

        $usuario = JWTAuth::parseToken()->authenticate();
        
        $apiarios_trelew = Apiario::where('localidad_chacra','Trelew')->where('apicultor_id',$usuario->id)->get();
        $apiarios_gaiman = Apiario::where('localidad_chacra','Gaiman')->where('apicultor_id',$usuario->id)->get();
        $apiarios_dolavon = Apiario::where('localidad_chacra','Dolavon')->where('apicultor_id',$usuario->id)->get();
        $apiarios_28_de_julio = Apiario::where('localidad_chacra','28 de Julio')->where('apicultor_id',$usuario->id)->get();


        $trelew = array(
            "ciudad" => "Trelew",
            "colores" => array(
                                "verde" => Colmena::contarColmenasSegunEstado("Trelew", "verde", $usuario),
                                "amarillo" => Colmena::contarColmenasSegunEstado("Trelew", "amarillo", $usuario),
                                "rojo" => Colmena::contarColmenasSegunEstado("Trelew", "rojo", $usuario),
            ),
            "apiarios" => sizeof($apiarios_trelew),
            "colmenas" => sizeof( Colmena::whereIn('apiario_id',$apiarios_trelew->pluck('id'))->get() )
        );

        $gaiman = array(
            "ciudad" => "Gaiman",
            "colores" => array(
                                "verde" => Colmena::contarColmenasSegunEstado("Gaiman", "verde", $usuario),
                                "amarillo" => Colmena::contarColmenasSegunEstado("Gaiman", "amarillo", $usuario),
                                "rojo" => Colmena::contarColmenasSegunEstado("Gaiman", "rojo", $usuario),
            ),
            "apiarios" => sizeof($apiarios_gaiman),
            "colmenas" => sizeof( Colmena::whereIn('apiario_id',$apiarios_gaiman->pluck('id'))->get() )
        );

        $dolavon = array(
            "ciudad" => "Dolavon",
            "colores" => array(
                                "verde" => Colmena::contarColmenasSegunEstado("Dolavon", "verde", $usuario),
                                "amarillo" => Colmena::contarColmenasSegunEstado("Dolavon", "amarillo", $usuario),
                                "rojo" => Colmena::contarColmenasSegunEstado("Dolavon", "rojo", $usuario),
            ),
            "apiarios" => sizeof($apiarios_dolavon),
            "colmenas" => sizeof( Colmena::whereIn('apiario_id',$apiarios_dolavon->pluck('id'))->get() )
        );

        $julio = array(
            "ciudad" => "28 de Julio",
            "colores" => array(
                                "verde" => Colmena::contarColmenasSegunEstado("28 de Julio", "verde", $usuario),
                                "amarillo" => Colmena::contarColmenasSegunEstado("28 de Julio", "amarillo", $usuario),
                                "rojo" => Colmena::contarColmenasSegunEstado("28 de Julio", "rojo", $usuario),
            ),
            "apiarios" => sizeof($apiarios_28_de_julio),
            "colmenas" => sizeof( Colmena::whereIn('apiario_id',$apiarios_28_de_julio->pluck('id'))->get() )
        );

        
        $resultado = array($trelew,$gaiman,$dolavon,$julio);
        return response()->json($resultado, 200); 

    }


    /**
     * Retorna un arreglo que contiene los apicultores con más colmenas.
     * 
     */
    public function getUsersMasColmenas(Request $request) {
        
        $usuario = JWTAuth::parseToken()->authenticate();

        // buscar apicultores con  más colmenas
        $apiarios = Apiario::all();
        $apicultores_ids = $apiarios->pluck('apicultor_id')->toArray();
        $apicultores_ids = array_unique($apicultores_ids);
        $apicultores_ids = array_slice($apicultores_ids, 0, 4);

        $resultado = array();
       
        foreach( $apicultores_ids as $id ) {
            $apiarios_apicultor = Apiario::where('apicultor_id',$id)->get();
            $contador = 0;
            
            foreach( $apiarios_apicultor as $apiario ) {
                $contador = $contador + Colmena::where('apiario_id',$apiario->id)->count();
            }

            $parcial = array(
                "apicultor" => User::where('id',$id)->first(),
                "apiarios" => sizeof($apiarios_apicultor),
                "colmenas" => $contador
            );

            array_push($resultado, $parcial);
        }

        return response()->json($resultado, 200); 
    }


    public function getAllColmenas(Request $request) {

        $usuario = JWTAuth::parseToken()->authenticate();
        $ciudad = $request['ciudad'];
        $apicultor = $request['apicultor'];
        $apiarios = Apiario::filtrarApiarios($ciudad,$apicultor);
        $resultado = array();
    
        foreach( $apiarios as $apiario ) {

            $colmenas = Colmena::where('apiario_id',$apiario['apiario']['id'])->get();

            foreach( $colmenas as $colmena ) {

                $parcial = array(
                    "colmena" => $colmena,
                    "apiario" => $apiario['apiario'],
                    "apicultor" => $apiario['apicultor'],
                );
    
                array_push($resultado,$parcial);

            }
        }

    
        return response()->json($resultado, 200); 
    }


    /**
     * Devuelve las colmenas que están en alertas y las que están en peligro.
     */
    public function getColmenasAlertayPeligro(Request $request) {

        $usuario = JWTAuth::parseToken()->authenticate();
        $ciudad = $request['ciudad'];
        $apiario = $request['apiario'];

        $colmenas_a_revisar = Colmena::getColmenasEnAlertayEnPeligro($ciudad, $apiario, $usuario);

        return response()->json($colmenas_a_revisar, 200); 
    }


    /**
     * Retorna el estado de cada una de las colmenas de un apiario.
     * 
     * 
     */
    public function obtenerEstadoColmenas(Request $request) {

        $usuario = JWTAuth::parseToken()->authenticate();
        
        $colmenas = Colmena::where('apiario_id',$request['apiario_id'])->get();

        $resultados = array();

        foreach( $colmenas as $colmena ) {

            array_push($resultados, RevisacionTemperaturaHumedad::getEstadoColmena($colmena));  
        }

        return response()->json($resultados, 200); 
    }


    /**
     * 
     */
    public function obtenerTodasColmenasSegunEstado(Request $request) {
        $usuario = JWTAuth::parseToken()->authenticate();
        $color = $request['estado'];
        $apiarios = Apiario::all();

        $resultados = array();
        

        foreach( $apiarios as $apiario ) {
            
            $colmenas = Colmena::where('apiario_id',$apiario->id)->get();
            $colmenas_a_guardar = array();
                
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
    
                    // Priorización: si la señal está en rojo, entonces incremento el dataset en rojo
                    // si la señal está en amarillo, entonces incremento el dataset en amarillo
                    // SI temperatura y/o humedad en rojo => rojo
                    // Si temperatura/humedad en amarillo => amarillo
                    // sino verde.
                    if( $color == "rojo" ) {
                        if( $colorSenial == "rojo" || $colorTemperatura == "rojo"  || $colorHumedad == "rojo" ) array_push($colmenas_a_guardar, array($colmena, $revisacion));;
                    }
                    elseif( $color == "amarillo" ) {
                        // valido que la colmena no sea roja.
                        if( $colorSenial == "rojo" || $colorTemperatura == "rojo"  || $colorHumedad == "rojo" ) continue;
                        if( $colorSenial == "amarillo" || $colorTemperatura == "amarillo"  || $colorHumedad == "amarillo" ) array_push($colmenas_a_guardar, array($colmena, $revisacion));
                    }
                    else {
                        if( ($colorTemperatura == "verde"  && $colorHumedad == "verde" && $colorSenial == "verde") && ($color == "verde") ) {
                            array_push($colmenas_a_guardar, array($colmena, $revisacion));
                        }
                    }
                }
                else { // Si entro acá es porque la colmena no tiene revisaciones
                    if( $color == "rojo" ) {
                        array_push($colmenas_a_guardar, array($colmena, null));
                    }
                }
            }

            if( sizeof($colmenas_a_guardar) == 0 ) continue;
            
            // Guardo el apiario, junto a sus colmenas y revisaciones asociada.
            array_push($resultados,array($apiario,$colmenas_a_guardar));
        }

        $resultado = array(
            'datos' => $resultados,
            'estados' => $request['estado']
        );

        return response()->json($resultado, 200);
    }

    
    /**
     * 
     */
    public function obtenerEstadoColmenasApicultor(Request $request) {

        $usuario = JWTAuth::parseToken()->authenticate();
        $color = $request['estado'];
        $apiarios = Apiario::where("apicultor_id", $usuario->id)->get();

        $resultados = array();
        

        foreach( $apiarios as $apiario ) {
            
            $colmenas = Colmena::where('apiario_id',$apiario->id)->get();
            $colmenas_a_guardar = array();
                
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
    
                    // Priorización: si la señal está en rojo, entonces incremento el dataset en rojo
                    // si la señal está en amarillo, entonces incremento el dataset en amarillo
                    // SI temperatura y/o humedad en rojo => rojo
                    // Si temperatura/humedad en amarillo => amarillo
                    // sino verde.
                    if( $color == "rojo" ) {
                        if( $colorSenial == "rojo" || $colorTemperatura == "rojo"  || $colorHumedad == "rojo" ) array_push($colmenas_a_guardar, array($colmena, $revisacion));;
                    }
                    elseif( $color == "amarillo" ) {
                        // valido que la colmena no sea roja.
                        if( $colorSenial == "rojo" || $colorTemperatura == "rojo"  || $colorHumedad == "rojo" ) continue;
                        if( $colorSenial == "amarillo" || $colorTemperatura == "amarillo"  || $colorHumedad == "amarillo" ) array_push($colmenas_a_guardar, array($colmena, $revisacion));
                    }
                    else {
                        if( ($colorTemperatura == "verde"  && $colorHumedad == "verde" && $colorSenial == "verde") && ($color == "verde") ) {
                            array_push($colmenas_a_guardar, array($colmena, $revisacion));
                        }
                    }
                }
                else { // Si entro acá es porque la colmena no tiene revisaciones
                    if( $color == "rojo" ) {
                        array_push($colmenas_a_guardar, array($colmena, null));
                    }
                }
            }

            if( sizeof($colmenas_a_guardar) == 0 ) continue;
            
            // Guardo el apiario, junto a sus colmenas y revisaciones asociada.
            array_push($resultados,array($apiario,$colmenas_a_guardar));
        }

        $resultado = array(
            'datos' => $resultados,
            'estados' => $request['estado']
        );

        return response()->json($resultado, 200);
    }

    
    
    public static function detalleEstado(Request $request) {

        $usuario = JWTAuth::parseToken()->authenticate();
        $colmena_id = $request['colmena_id'];

        $resultado = array(
            "temperatura" => Colmena::getMensajeTemperatura($colmena_id),
            "humedad" => Colmena::getMensajeHumedad($colmena_id),
            "senial" => Colmena::getMensajeSenial($colmena_id),
        );

        return response()->json($resultado, 200);
    }
}

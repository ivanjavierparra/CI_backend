<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use JWTAuth;
use Illuminate\Http\Request;
use App\User;
use App\Apiario;
use App\Colmena;
use App\RevisacionTemperaturaHumedad;

class ApiarioController extends Controller
{
    
    
     /**
     * Retorna un JSON con la información de todos los apiarios.
     * 
     * @return Apiarios
     */
    public static function getApiarios(Request $request) {
        
        $usuario = JWTAuth::parseToken()->authenticate();

        // Retorno resultado
        return response()->json(Apiario::getApiarios($usuario), 200);
    }

    /**
     * Crea un apiario con la información que viene en el request.
     * 
     * @return Apiario
     */
    public function crearApiario(Request $request) {

        $usuario = JWTAuth::parseToken()->authenticate();
        
        // Obtengo datos del apiario.
        $datos = array(
            'apicultor_id' => $usuario->id,
            'nombre_fantasia' => $request['nombre_fantasia'],
            'descripcion' => $request['descripcion'],
            'latitud' => $request['latitud'],
            'longitud' => $request['longitud'],
            'fecha_creacion' => $request['fecha_creacion'],
            'direccion_chacra' => $request['direccion_chacra'],
            'localidad_chacra' => $request['localidad_chacra'],
            'propietario_chacra' => $request['propietario_chacra'],
        );
        
        // Creo nueva chacra.
        $resultado = Apiario::crearApiario($datos);

        // Retorno resultado
        return response()->json($resultado, 200);
    }

    /**
     * Editar un apiario.
     * 
     * @return Apiario
     */
    public function editarApiario(Request $request) {

        // Obtengo datos del apiario.
        $datos = array(
            'id_apiario' => $request['id_apiario'],
            'nombre_fantasia' => $request['nombre_fantasia'],
            'latitud' => $request['latitud'],
            'longitud' => $request['longitud'],
            'descripcion' => $request['descripcion'],
            'propietario' => $request['propietario'],
        );
        
        // Creo nueva chacra.
        $resultado = Apiario::editarApiario($datos);

        // Retorno resultado
        return response()->json($resultado, 200);

    }

    /**
     * Retorna un json que contiene los apiarios con la siguiente estructura:
     *  const json = [
     *      { id_apiario: '1', nombre: 'Chacra Samarreño 1', colmenas: [1,2,3,4,5] },
     *      { id_apiario: '2', nombre: 'Chacra Gava', colmenas: [1,2,3,4,5,6,7] }
     *   ]
     */
    public static function getTodosApiarios(Request $request) {
        return Apiario::getTodosApiarios();
    }


    public static function getEstadoApiario(Request $request) {
        $usuario = JWTAuth::parseToken()->authenticate();

        $apiario_id = $request['apiario_id'];
        
        $apiario = Apiario::find($apiario_id);
        $estados = Apiario::getEstadoContador($apiario_id);
        $estado_apiario = Apiario::getColor($apiario_id);

        $resultado = array(
            "apiario" => $apiario['direccion_chacra']." - ".$apiario['nombre_fantasia'],
            "verde" => $estados["verde"],
            "amarillo" => $estados["amarillo"],
            "rojo" => $estados["rojo"],
            "estado" => $estado_apiario["color"],
        );

        return response()->json($resultado, 200);
    }

    /**
     * Devuelve todos los apiarios del apicultor con sus colmenas.
     */
    public static function getApiariosEstado(Request $request) {
        $usuario = JWTAuth::parseToken()->authenticate();

        // Retorno resultado
        return response()->json(Apiario::getApiariosEstados($usuario), 200);
    }


    public static function getApiariosColmenaEstado(Request $request) {
        $usuario = JWTAuth::parseToken()->authenticate();
        $apiario_id = $request['apiario_id'];
        $colmena_id = $request['colmena_id'];

        $colmena = Colmena::find($colmena_id);

        $ultima_revisacion = RevisacionTemperaturaHumedad::where("colmena_id",$colmena_id)->orderBy("id","desc")->first();

        $estado_colmena = (RevisacionTemperaturaHumedad::getEstadoColmena($colmena))[1];     
        
        $resultado = array(
            "colmena_id" => $colmena_id,
            "identificacion" => $colmena->identificacion,
            "temperatura" => $ultima_revisacion != null ? $ultima_revisacion['temperatura']."°C" : "",
            "humedad" => $ultima_revisacion != null ? $ultima_revisacion['humedad']."%" : "",
            "fecha_hora" => $ultima_revisacion != null ? Date("d-m-Y H:i", strtotime($ultima_revisacion['fecha_revisacion']." ".$ultima_revisacion['hora_revisacion'])) : "",
            "estado" => $estado_colmena,
        );

        return response()->json($resultado, 200);
    }


    public static function getDetalleEstadoColmenas(Request $request) {

        $usuario = JWTAuth::parseToken()->authenticate();
        $apiario_id = $request['apiario_id'];

        $resultado = array();
        $colmenas = Colmena::where("apiario_id", $apiario_id)->get();

        foreach( $colmenas as $colmena ) {

            $ultima_revisacion = RevisacionTemperaturaHumedad::where("colmena_id",$colmena->id)->orderBy("id","desc")->first();

            $estado_colmena = (RevisacionTemperaturaHumedad::getEstadoColmena($colmena))[1];    
            
            $parcial = array(
                "identificacion" => $colmena->identificacion,
                "temperatura" => $ultima_revisacion != null ? $ultima_revisacion['temperatura']."°C" : "",
                "humedad" => $ultima_revisacion != null ? $ultima_revisacion['humedad']."%" : "",
                "fecha_hora" => $ultima_revisacion != null ? Date("d-m-Y H:i", strtotime($ultima_revisacion['fecha_revisacion']." ".$ultima_revisacion['hora_revisacion'])) : "",
                "estado" => $estado_colmena,
            );

            array_push($resultado, $parcial);
        }

        return response()->json($resultado, 200);
    }

    /**
     * Devuelvo las colmenas del apiario_id pasado en el request.
     * 
     * @return Colmenas
     */
    public function getColmenas(Request $request, $id) {
        
        $usuario = JWTAuth::parseToken()->authenticate();
        
        $apiario = Apiario::where('id',$id)
                            ->where('apicultor_id',$usuario->id)
                            ->first();

        if( !$apiario ) {
            // Retorno resultado 500
            $resultado = array(
                'resultado' => 500,
                'mensaje' => 'El apiario '.$id.' no existe.',
                'colmenas' => []
            );
            return response()->json($resultado, 200);
        }

        $colmenas = $apiario->getColmenas();
        if( empty($colmenas) ) {
            // Retorno resultado 500
            $resultado = array(
                'resultado' => 500,
                'mensaje' => 'El apiario '.$id.' no tiene colmenas.',
                'apiario' => $apiario,
                'colmenas' => []
            );
            return response()->json($resultado, 200);
        }


        // Retorno resultado 500
        $resultado = array(
            'resultado' => 200,
            'mensaje' => 'El apiario posee '.sizeof($colmenas).' colmena/s.',
            'apiario' => $apiario,
            'colmenas' => $colmenas
        );
        return response()->json($resultado, 200);
    }   


    /**
     * Retorna un JSON con la información de todos los apiarios junto con sus colmenas.
     * 
     * @return Apiarios
     */
    public function getTodasColmenas(Request $request) {
        
        $usuario = JWTAuth::parseToken()->authenticate();
        
        // Retorno resultado
        return response()->json(Apiario::getApiariosConColmenas($usuario), 200);
    }

    
    /**
     * Devuelve los apiarios y colmenas del usuario autenticado.
     * 
     */
    public function getMisApiarios(Request $request) {

        $usuario = JWTAuth::parseToken()->authenticate();

        $apiarios = Apiario::where('apicultor_id',$usuario->id)->get();

        $estados = array();

        foreach( $apiarios as $apiario ) {

            $color = Apiario::getColor($apiario->id);
            $parcial = array($apiario->id, $color['color']);

            array_push($estados, $parcial);
        }
        
        $colmenas = Colmena::whereIn('apiario_id',$apiarios->pluck('id'))->get();

        $resultado = array(
            'apiarios' => $apiarios,
            'colmenas' => $colmenas,
            'estados' => $estados,
        );

        return response()->json($resultado, 200);
    }


    /**
     * Devuelve todas las colmenas del apiario recibido como parámetro.
     * 
     * @return Colmenas
     */
    public function getColmenasDelApiario(Request $request) {

        // Valido que el apiario sea del apicultor
        $usuario = JWTAuth::parseToken()->authenticate();
        $apiario_validar = Apiario::where('id',$request['apiario_id'])->where('apicultor_id',$usuario->id)->first();
        if( !$apiario_validar ) {
            // Return error.....
        }

        return response()->json(Colmena::where('apiario_id',$request['apiario_id'])->get(), 200);
    }

    /**
     * Devuelve todos los apiarios de una ciudad pasada por parámetro.
     * 
     */
    public function getApiariosPorCiudad(Request $request) {
        
        $usuario = JWTAuth::parseToken()->authenticate();
        $ciudad = $request['ciudad'];

        $apiarios = Apiario::where('localidad_chacra',$ciudad)
                            ->where('apicultor_id',$usuario->id)
                            ->get();
        
        return response()->json($apiarios, 200);
    }

    /**
     * Devuelve un arreglo que clasifica las colmenas del apiario pasado como parámetro
     * en verde, amarillo y rojo, en base a los valores de la última revisación.
     * 
     */
    public function getDashboardApiarios(Request $request) {

        $usuario = JWTAuth::parseToken()->authenticate(); 
        
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
        
        // Obtengo mis apiarios
        $apiarios = Apiario::where('apicultor_id',$usuario->id)->get();

        // Obtengo las colmenas
        $colmenas = Colmena::where('apiario_id',$request['apiario_id'])->whereIn('apiario_id',$apiarios->pluck('id'))->get();

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
            } else { // Si entro acá es porque la colmena no tiene revisaciones... pongo todo en rojo...
                $senial['rojo']++;
                $temperatura['rojo']++;
                $humedad['rojo']++;
            }
                                                        
        }

        $resultado = array(
            'temperatura' => $temperatura,
            'humedad' => $humedad,
            'senial' => $senial,
            'cantidad_colmenas' => sizeof($colmenas)
        );

        return response()->json($resultado, 200);
    }


    /**
     * Devuelve un arreglo que clasifica en en verde, amarillo y rojo, a todas las colmenas
     * de todos los apiarios, en base a los valores de la última revisación.
     * 
     */
    public function getDashboardColmenas(Request $request) {

        $usuario = JWTAuth::parseToken()->authenticate();
        
        // Datasets
        $dataset = array(
            'verde' => 0,
            'amarillo' => 0,
            'rojo' => 0,
        );

        // Obtengo mis apiarios
        $mis_apiarios = Apiario::where('apicultor_id', $usuario->id)->get();              
        // Obtengo las colmenas
        $colmenas = Colmena::whereIn('apiario_id',$mis_apiarios->pluck('id'))->select('id')->get();

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

                // Priorización: si la señal está en rojo, entonces incremento el dataset en rojo
                // si la señal está en amarillo, entonces incremento el dataset en amarillo
                // SI temperatura y/o humedad en rojo => rojo
                // Si temperatura/humedad en amarillo => amarillo
                // sino verde.
                if( $colorSenial == "rojo"  ) $dataset[$colorSenial]++;
                else if( $colorTemperatura == "rojo"  || $colorHumedad == "rojo" ) $dataset["rojo"]++;
                else if( $colorSenial == "amarillo" ) $dataset[$colorSenial]++; // corregido :)
                else if( $colorTemperatura == "amarillo"  || $colorHumedad == "amarillo" ) $dataset["amarillo"]++;
                else $dataset['verde']++;
            }
            else {
                // Si entró acá significa que NO encontró una revisación, por lo tanto, a esa colmena le aumento el rojo.
                $dataset['rojo']++;
            }
                                                        
        }

        $resultado = array(
            'datos' => $dataset,
            'cantidad_colmenas' => sizeof($colmenas),
        );

        return response()->json($resultado, 200);
   }



   /**
     * Devuelve un arreglo que clasifica en en verde, amarillo y rojo, a todas las colmenas
     * de todos los apiarios, en base a los valores de la última revisación.
     * 
     */
    public function getEstadoTodasLasColmenas(Request $request) {

        $usuario = JWTAuth::parseToken()->authenticate();
        
        // Datasets
        $dataset = array(
           'verde' => 0,
           'amarillo' => 0,
           'rojo' => 0,
        );

        // Obtengo las colmenas
        $colmenas = Colmena::all();

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

                // Priorización: si la señal está en rojo, entonces incremento el dataset en rojo
                // si la señal está en amarillo, entonces incremento el dataset en amarillo
                // SI temperatura y/o humedad en rojo => rojo
                // Si temperatura/humedad en amarillo => amarillo
                // sino verde.
                if( $colorSenial == "rojo"  ) $dataset[$colorSenial]++;
                else if( $colorTemperatura == "rojo"  || $colorHumedad == "rojo" ) $dataset["rojo"]++;
                else if( $colorSenial == "amarillo" ) $dataset[$colorSenial]++; // corregido :)
                else if( $colorTemperatura == "amarillo"  || $colorHumedad == "amarillo" ) $dataset["amarillo"]++;
                else $dataset['verde']++;
            }
            else {
                // Si entró acá significa que NO encontró una revisación, por lo tanto, a esa colmena le aumento el rojo.
                $dataset['rojo']++;
            }
                                                        
        }

        $resultado = array(
            'verde' => $dataset['verde'],
            'amarillo' => $dataset['amarillo'],
            'rojo' => $dataset['rojo'],
            'cantidad_colmenas' => sizeof($colmenas),
            //'usuario' => JWTAuth::parseToken()->authenticate()
        );

        return response()->json($resultado, 200);
   }

   /**
    * Dado un arreglo de ids de apiarios y un color (verde, amarillo, rojo) el cual representa un estado, 
    * devuelvo un arreglo que contiene por cada apiario, las colmenas que estan en ese estado. Esto se hace analizando
    * el color de la temperatura, humedad y señal.
    *
    * @return Array (apiarios, colmenas)
    */
   public function obtenerAlertasRevisacionesApiarios(Request $request) {

        $usuario = JWTAuth::parseToken()->authenticate();
        $apiarios = explode(',', $request['apiarios']);
        $color = $request['estado'];

        $resultados = array();
        

        foreach( $apiarios as $id ) {
            $apiario = Apiario::where('id',$id)->where('apicultor_id',$usuario->id)->first(); 
            if ( !$apiario ) continue;
            $colmenas = Colmena::where('apiario_id',$id)->get();
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
    * Dado un apiario, clasifico cada una de sus colmenas en (verde, amarillo, rojo)
    * analizando sus revisaciones (temperatura, humedad, señal).
    *
    * @return Array contador {verde,amarillo, rojo}
    */
   public function obtenerAlertarPorApiario(Request $request) {

        // Valido que el apiario sea del apicultor.
        $usuario = JWTAuth::parseToken()->authenticate();
        $apiario_id = $request['apiario'];
        $apiario = Apiario::where('id',$apiario_id)->where('apicultor_id',$usuario->id)->first();

        $contador = array(
            "verde" => 0,
            "amarillo" => 0,
            "rojo" => 0,
        );

        if( !$apiario ) {
            
            $resultado = array(
                'datos' => $contador,
            );
    
            return response()->json($resultado, 200);
        }

        $colmenas = Colmena::where('apiario_id',$apiario->id)->get();

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

                if( $colorSenial == "rojo"  ) $contador[$colorSenial]++;
                else if( $colorTemperatura == "rojo"  || $colorHumedad == "rojo" ) $contador["rojo"]++;
                else if( $colorSenial == "amarillo" ) $contador[$colorSenial]++; // corregido :)
                else if( $colorTemperatura == "amarillo"  || $colorHumedad == "amarillo" ) $contador["amarillo"]++;
                else $contador['verde']++;
                
            }
            else { // Si entro acá es porque la colmena no tiene ninguna revisación.
                $contador['rojo']++;
            }
        }

        $resultado = array(
            'datos' => $contador,
        );

        return response()->json($resultado, 200);
   }


   /**
    * Dado una ciudad y un color, devuelvo los apiarios y sus colmenas cuyas revisaciones
    * respeten el color (analizando temperatura, humedad y señal).
    *
    * @return Array (apiarios, colmenas)
    */
   public function obtenerAlertasPorEstadoyCiudad(Request $request) {

        $usuario = JWTAuth::parseToken()->authenticate();
        $color = $request['estado'];
        $ciudad = $request['ciudad'];
        $resultados = array();

        $apiarios = Apiario::where('apicultor_id',$usuario->id)->where('localidad_chacra',$ciudad)->get();

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

                    if( $colorSenial == "rojo" && $color == "rojo"  ) {
                        array_push($colmenas_a_guardar, array($colmena, $revisacion));
                    }
                    elseif( ($colorTemperatura == "rojo"  || $colorHumedad == "rojo") && ($color == "rojo") ) {
                        array_push($colmenas_a_guardar, array($colmena, $revisacion));
                    }
                    elseif( $colorSenial == "amarillo" && $color == "amarillo" ) { // corregido :)
                        array_push($colmenas_a_guardar, array($colmena, $revisacion)); 
                    } 
                    elseif( ($colorTemperatura == "amarillo"  || $colorHumedad == "amarillo") && ($color == "amarillo") ) {
                        array_push($colmenas_a_guardar, array($colmena, $revisacion));
                    }
                    elseif( ($colorTemperatura == "verde"  && $colorHumedad == "verde" && $colorSenial == "verde") && ($color == "verde") ) {
                        array_push($colmenas_a_guardar, array($colmena, $revisacion));
                    }
                }
                else { // Si entro acá es porque la colmena no tiene revisaciones.
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
    * Devuelve la cantidad de apiarios y la cantidad de colmenas que posee 
    * el usuario.
    */
   public function obtenerCantidadesApiariosColmenas(Request $request) {

        $usuario = JWTAuth::parseToken()->authenticate();
        $apiarios = Apiario::where('apicultor_id',$usuario->id)->get();
        $colmenas = Colmena::whereIn('apiario_id',$apiarios->pluck('id'))->get();
        
        $resultado = array(
            'apiarios' => sizeof($apiarios),
            'colmenas' => sizeof($colmenas)
        );

        return response()->json($resultado, 200);
   }


   /**
    * Devuelve todos los apiarios, con su estado {verde, amarillo, rojo} en base al estado de sus colmenas,
    * haciendo un porcentaje de la cantidad de ellas. Adenás devuelve la cantidad de colmenas en cada estado de ese apiario.
    *
    */
   public function getAdminApiarios(Request $request) {

        $usuario = JWTAuth::parseToken()->authenticate();

        $resultados = array();

        $apiarios = Apiario::all();

        foreach( $apiarios as $apiario ) {

            $apicultor = User::where('id',$apiario->apicultor_id)->first();

            $color = Apiario::getColor($apiario->id);

            $parcial = array (
                "apiario" => $apiario,
                "apicultor" => $apicultor->name." ".$apicultor->lastname,
                "color" => $color["color"],
                "contador" => $color["contador"],
                "colmenas" => Colmena::where('apiario_id',$apiario->id)->count(),
            );

            array_push($resultados, $parcial);
        }


        // Retorno resultado
        return response()->json($resultados, 200);
   }


   public function getAdminApiariosCompleto(Request $request) {

        $usuario = JWTAuth::parseToken()->authenticate();

        $resultados = array();

        $apiarios = Apiario::all();

        foreach( $apiarios as $apiario ) {

            $apicultor = User::where('id',$apiario->apicultor_id)->first();

            $color = Apiario::getColor($apiario->id);

            $parcial = array (
                "apiario" => $apiario,
                "apicultor" => $apicultor->name." ".$apicultor->lastname,
                "apicultores" => User::where('id',$apiario->apicultor_id)->first(),
                "color" => $color["color"],
                "contador" => $color["contador"],
                "colmenas" => Colmena::where('apiario_id',$apiario->id)->count(),
            );

            array_push($resultados, $parcial);
        }


        // Retorno resultado
        return response()->json($resultados, 200);   
    }


    /**
     * Devuelve todos los apiarios con su apicultor y sus colmenas.
     * 
     */
    public function getAdminApiariosApicultoresColmenas(Request $request) {
        $usuario = JWTAuth::parseToken()->authenticate();

        $resultados = array();

        $apiarios = Apiario::all();

        foreach( $apiarios as $apiario ) {

            $colmenas = Colmena::where('apiario_id',$apiario->id)->get();

            $estados = array();

            foreach( $colmenas as $colmena ) {
                array_push($estados, RevisacionTemperaturaHumedad::getEstadoColmena($colmena));
            }            

            $parcial = array (
                "apiario" => $apiario,
                "apicultor" => User::where('id',$apiario->apicultor_id)->first(),
                "colmenas" => $colmenas,
                "estados" => $estados,
            );

            array_push($resultados, $parcial);
        }

        // Retorno resultado
        return response()->json($resultados, 200); 
    }

    /**
     * Devuelve un arreglo que clasifica las colmenas del apiario pasado como parámetro
     * en verder, amarillo y rojo, en base a los valores de la última revisación.
     * 
     */
    public function getTyHApiarios(Request $request) {

        $usuario = JWTAuth::parseToken()->authenticate(); 
        
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
        $colmenas = Colmena::where('apiario_id',$request['apiario_id'])->get();

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
            } else { // Si entro acá es porque la colmena no tiene revisaciones... pongo todo en rojo...
                $senial['rojo']++;
                $temperatura['rojo']++;
                $humedad['rojo']++;
            }                                               
        }

        $resultado = array(
            'temperatura' => $temperatura,
            'humedad' => $humedad,
            'senial' => $senial,
            'cantidad_colmenas' => sizeof($colmenas)
        );

        return response()->json($resultado, 200);
    }


   /**
    * Retorno los apiarios del apicultor logueado, con los datos necesarios 
    * para mostrar los datos del mapa de la vista Home.
    *
    */
    public function getApiariosApicultor(Request $request) {

        $usuario = JWTAuth::parseToken()->authenticate();
        
        $apiarios = Apiario::where('apicultor_id',$usuario->id)->get();

        $resultados = array();

        foreach( $apiarios as $apiario ) {

            $color = Apiario::getColor($apiario->id);

            $parcial = array (
                "apiario" => $apiario,
                "apicultor" => $usuario->name." ".$usuario->lastname,
                "color" => $color["color"],
                "contador" => $color["contador"],
                "colmenas" => Colmena::where('apiario_id',$apiario->id)->count(),
            );

            array_push($resultados, $parcial);
        }


        // Retorno resultado
        return response()->json($resultados, 200);
    }


   /**
    * Devuleve 3 cosas: la cantidad de apicultores, la cantidad de apiarios y 
    * la cantidad de colmenas existentes.
    */
    public function getDatosTarjetas(Request $request) {

        $usuario = JWTAuth::parseToken()->authenticate();

        return response()->json(array(
            "apicultores" => User::where("role","Beekeeper")->count(),
            "apiarios" => Apiario::count(),
            "colmenas" => Colmena::count(),
            "revisaciones" => RevisacionTemperaturaHumedad::count(),
        ), 200);
    }

   
    /**
    * Devuelve la cantidad de apiarios en cada Ciudad.
    *
    */
    public function obtenerApiariosPorCiudad(Request $request) {

        $usuario = JWTAuth::parseToken()->authenticate();

        return response()->json(array(
            "Rawson" => Apiario::where("localidad_chacra","Rawson")->count(),
            "Trelew" => Apiario::where("localidad_chacra","Trelew")->count(),
            "Gaiman" => Apiario::where("localidad_chacra","Gaiman")->count(),
            "Dolavon" => Apiario::where("localidad_chacra","Dolavon")->count(),
            "28 de Julio" => Apiario::where("localidad_chacra","28 de Julio")->count(),
        ), 200);
    }

   
   /**
    * Devuelve los últimos 8 apicultores registrados en la plataforma.
    *
    */
    public function getLastUsers(Request $request) {
        
        $usuario = JWTAuth::parseToken()->authenticate();

        $resultado = array();

        $apicultores = User::where('role',"Beekeeper")->orderBy('id','desc')->take(8)->get();

        foreach( $apicultores as $apicultor ) {

            $apiarios = Apiario::where('apicultor_id',$apicultor->id)->get();

            $parcial = array(
                'apicultor' => $apicultor,
                'apiarios' => sizeof($apiarios),
                'colmenas' => Colmena::whereIn('apiario_id',$apiarios->pluck('id'))->count(),
            );

            array_push($resultado,$parcial);
        }

        return response()->json($resultado, 200);
    }


   /**
    * Agarra todos los apiarios, busca los que están en estado rojo, y saca un porcentaje de las colmenas 
    * en rojo en relación al total de colmenas del apiario. Ordena el arreglo de manera descendente, y retorna
    * solo los primeros 7 elementos.
    */
    public function obtenerApiariosComplicados(Request $request) {

        $usuario = JWTAuth::parseToken()->authenticate();

        $apiarios = Apiario::all();


        $apiarios = Apiario::get()->sortByDesc(function($apiarios)
        {
            return Colmena::where('apiario_id',$apiarios->id)->count();
        });


        $resultado = array();

        foreach( $apiarios as $apiario ) {

            $cantidad_colmenas = Colmena::where('apiario_id',$apiario->id)->count();
            if( $cantidad_colmenas == 0 ) continue;

            $color = Apiario::getColor($apiario->id); 

            if( $color['color'] != "rojo") continue;

            $porcentaje_colmenas_en_rojo = ($color['contador']['rojo'] * 100) / $cantidad_colmenas;

            $parcial = array(
                "apiario" => $apiario,
                "apicultor" => User::where('id',$apiario->apicultor_id)->first(),
                "porcentaje" => $porcentaje_colmenas_en_rojo,
                "colmenas" => $cantidad_colmenas,
            );

            array_push($resultado,$parcial);

        }

        usort($resultado, function ($item1, $item2) {
            return $item2['porcentaje'] <=> $item1['porcentaje'];
        });

        $resultado = array_slice($resultado, 0, 7); // Solo 7 resultados

        return response()->json($resultado, 200);

   }

   /**
    * Devuelve dos cosas: un arreglo con todos los apicultores y otro arreglo
    * con todos los apiarios y colmenas existentes.
    *
    */
    public function getApicultoresApiarios(Request $request) {

            $usuario = JWTAuth::parseToken()->authenticate();
            
            $apiarios = Apiario::all();

            $resultado = array();

            foreach( $apiarios as $apiario ) {

                $color = Apiario::getColor($apiario->id);

                $parcial = array(
                    "apiario" => $apiario,
                    "colmenas" => Colmena::where('apiario_id',$apiario->id)->count(),
                    "estado_apiario" => $color['color'],
                );

                array_push($resultado,$parcial);
            }

            return response()->json(array(
                "apicultores" => User::where('role','Beekeeper')->get(),
                "apiarios" => $resultado,
            ), 200);
    }

   
   /**
    * Devuelve dos cosas: un arreglo con todos los apicultores y otro arreglo
    * con todos los apiarios y colmenas existentes.
    *
    */
    public function getApicultoresApiariosColmenas(Request $request) {

        $usuario = JWTAuth::parseToken()->authenticate();
        
        $apiarios = Apiario::all();

        $resultado = array();

        foreach( $apiarios as $apiario ) {

            $color = Apiario::getColor($apiario->id);

            $parcial = array(
                "apiario" => $apiario,
                "colmenas" => Colmena::where('apiario_id',$apiario->id)->get(),
                "estado_apiario" => $color['color'],
            );

            array_push($resultado,$parcial);
        }

        return response()->json(array(
            "apicultores" => User::where('role','Beekeeper')->get(),
            "apiarios" => $resultado,
        ), 200);
    }

    
    /**
     * Devuelve para cada apicultor, sus colmenas.
     * 
     */
    public function getTodosLosUsuarios(Request $request) {

        $usuario = JWTAuth::parseToken()->authenticate();
        
        $usuarios = User::all();

        $resultado = array();

        foreach( $usuarios as $user ) {
            
            $apiarios = Apiario::where('apicultor_id',$user->id)->get();

            $colmenas =  Colmena::whereIn('apiario_id',$apiarios->pluck('id'))->count();

            $parcial = array(
                'usuario' => $user,
                'apiarios' => sizeof($apiarios),
                'colmenas' => $colmenas,
            );

            array_push($resultado,$parcial);
        }

        return response()->json(  $resultado  , 200);

    }


    /**
     * Devuelve los apiarios filtrando por apicultor y ciudad.
     * @param String $ciudad
     * @param int $apicultor
     * 
     * @return Array Apiarios
     */
    public function getTodosLosApiarios(Request $request) {

        $usuario = JWTAuth::parseToken()->authenticate();
        $resultado = array();

        $ciudad = $request['ciudad'];
        $apicultor = $request['apicultor'];


        $resultado = Apiario::filtrarApiarios($ciudad,$apicultor);
        
        return response()->json(  $resultado  , 200);
    }


    /**
     * Devuelve todos los apicultores.
     */
    public function getTodosApicultores(Request $request) {

        $apicultores = User::where('role','Beekeeper')->get();

        return response()->json(  $apicultores  , 200);

    }


    /**
     * Devuelve todos los apiarios y colmenas de un apicultor.
     * @return Array Apiarios y Colmenas
     */
    public function getApiariosColmenas(Request $request) {

        $usuario = JWTAuth::parseToken()->authenticate();

        $resultados = array();

        $apiarios = Apiario::where('apicultor_id',$usuario->id)->get();

        foreach( $apiarios as $apiario ) {

            $colmenas = Colmena::where('apiario_id',$apiario->id)->get();

            $estados = array();
            
            foreach( $colmenas as $colmena ) {
                array_push($estados, RevisacionTemperaturaHumedad::getEstadoColmena($colmena));
            }

            $parcial = array (
                "apiario" => $apiario,
                "colmenas" => $colmenas,
                "estados" => $estados,
            );

            array_push($resultados, $parcial);
        }


        // Retorno resultado
        return response()->json($resultados, 200); 
    }

    /**
     * Devuelve las colmenas de un apiario.
     * @param int $apiario
     * @return Array 
     */
    public function getColmenasDeUnApiario(Request $request) {

        $usuario = JWTAuth::parseToken()->authenticate();
        $apiario_id = $request['apiario'];
        $apiario = Apiario::where('id',$apiario_id)->first();
                            
        $colmenas = $apiario->getColmenas();
        if( empty($colmenas) ) {
            // Retorno resultado 500
            $resultado = array(
                'resultado' => 500,
                'mensaje' => 'El apiario '.$id.' no tiene colmenas.',
                'apiario' => $apiario,
                'colmenas' => []
            );
            return response()->json($resultado, 200);
        }

        // Retorno resultado 500
        $resultado = array(
            'resultado' => 200,
            'mensaje' => 'El apiario posee '.sizeof($colmenas).' colmena/s.',
            'apiario' => $apiario,
            'colmenas' => $colmenas
        );
        return response()->json($resultado, 200);
    }


    /**
     * Devuelve los datos de los apiarios del apicultor pasado como parámetro.
     * 
     * @param int $apicultor_id
     * @return array
     */
    public function getApiariosApicultorID(Request $request) {

        $usuario = User::where("id",$request['apicultor_id'])->first();
        $apiarios = Apiario::where('apicultor_id',$request['apicultor_id'])->get();

        $resultados = array();

        foreach( $apiarios as $apiario ) {

            $color = Apiario::getColor($apiario->id);

            $parcial = array (
                "apiario" => $apiario,
                "apicultor" => $usuario->name." ".$usuario->lastname,
                "color" => $color["color"],
                "contador" => $color["contador"],
                "colmenas" => Colmena::where('apiario_id',$apiario->id)->count(),
            );

            array_push($resultados, $parcial);
        }


        // Retorno resultado
        return response()->json($resultados, 200);
    }
   

    /**
     * Devuelve el detalle de un apiario, a partir de la direccion_chacra
     * pasada como parámetro.
     */
    public function getAdminApiarioDetalle(Request $request) {

        $direccion_chacra = $request['apiario_id'];

        $apiario = Apiario::where('direccion_chacra',$direccion_chacra)->first();

        $apicultor = User::where('id',$apiario->apicultor_id)->first();

        $resultado = array(
            "apicultor" => $apicultor,
            "apiarios" => $apiario,
            "colmenas" => Colmena::where('apiario_id',$apiario->id)->count(),
        );

        return response()->json($resultado, 200);
    }
}

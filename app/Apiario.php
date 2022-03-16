<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Chacra;
use App\Colmena;
use \DatePeriod;
use \DateTime;
use \DateInterval;

class Apiario extends Model
{
    protected $table = 'apiarios';
        
    protected $primaryKey = 'id';

    protected $fillable = array(
        'apicultor_id',
        'nombre_fantasia',
        'latitud',
        'longitud',
        'fecha_creacion',
        'descripcion',
        'localidad_chacra',
        'direccion_chacra',
        'propietario_chacra',
        'eliminado',
    );


    /**
     * Crea un Apiario.
     * 
     */
    public static function crearApiario($datos) {
        
        // Verifico si existe apiario.
        $apiario = Apiario::where('direccion_chacra', $datos['direccion_chacra'])
                            ->where('localidad_chacra', $datos['localidad_chacra'])
                            ->first();

        // Si existe apiario, retorno error.
        if($apiario) {
            return array(
                'resultado' => 500,
                'mensaje' => 'Ya existe un apiario en la chacra ingresada.'
            );
        }
        else {
            try {
                $objetoApiario = Apiario::create($datos);
                return array(
                    'resultado' => 200,
                    'mensaje' => 'Apiario creado.'
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
     * Permite editar un apiario.
     * 
     * @param array $datos [id_apiario, latitud, longitud, descripcion, propietario]
     * @return array [respuesta, mensaje, apiario]
     */
    public static function editarApiario($datos) {

        // Verifico si existe apiario.
        $apiario = Apiario::where('id', $datos['id_apiario'])
                            ->first();

        // Si existe apiario, retorno error.
        if(!$apiario) {
            return array(
                'resultado' => 500,
                'mensaje' => 'No se encontró el apiario.'
            );
        }
        else {
            try {
                $apiario->nombre_fantasia = $datos['nombre_fantasia'];
                $apiario->latitud = $datos['latitud'];
                $apiario->longitud = $datos['longitud'];
                $apiario->propietario_chacra = $datos['propietario'];
                $apiario->descripcion = $datos['descripcion'];
                $apiario->save();
                return array(
                    'resultado' => 200,
                    'mensaje' => 'Apiario editado.',
                    'apiario' => $apiario,
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
     * Retorna todos los apiarios existentes con un formato determinado.
     * 
     */
    public static function getApiarios($usuario) {
        // Creo un arreglo para guardar los resultados.
        $resultado = array();
        // Obtengo todos los apiarios.
        $apiarios = Apiario::where('apicultor_id',$usuario->id)->get();
        // Recorro los apiarios.
        foreach($apiarios as $apiario) {

            $color = Apiario::getColor($apiario->id);

            // Creo estructura de respuesta.    
            $datos = array(
                'id_apiario' => $apiario['id'],
                'localidad_chacra' => $apiario->localidad_chacra,
                'direccion_chacra' => $apiario->direccion_chacra,
                'nombre_fantasia' => $apiario->nombre_fantasia,
                'latitud' => $apiario->latitud,
                'longitud' => $apiario->longitud,
                'fecha_creacion' => $apiario->fecha_creacion,
                'propietario_chacra' => $apiario->propietario_chacra,
                'descripcion' => $apiario->descripcion,
                'eliminado' => $apiario->eliminado,
                'colmenas' => Apiario::getCantidadColmenas($apiario['id']),
                'estado_apiario' => $color['color'],
            );
            // Agrego datos al resultados.
            array_push($resultado,$datos);
        }
        return $resultado;
    }

    /**
     * 
     */
    public static function getApiariosEstados($usuario) {
        // Creo un arreglo para guardar los resultados.
        $resultado = array();
        // Obtengo todos los apiarios.
        $apiarios = Apiario::where('apicultor_id',$usuario->id)->get();
        // Recorro los apiarios.
        foreach($apiarios as $apiario) {

            $color = Apiario::getColor($apiario->id);

            // Creo estructura de respuesta.    
            $datos = array(
                'id_apiario' => $apiario['id'],
                'localidad_chacra' => $apiario->localidad_chacra,
                'direccion_chacra' => $apiario->direccion_chacra,
                'nombre_fantasia' => $apiario->nombre_fantasia,
                'latitud' => $apiario->latitud,
                'longitud' => $apiario->longitud,
                'fecha_creacion' => $apiario->fecha_creacion,
                'propietario_chacra' => $apiario->propietario_chacra,
                'descripcion' => $apiario->descripcion,
                'eliminado' => $apiario->eliminado,
                'colmenas' => Apiario::getCantidadColmenas($apiario['id']),
                'estado_apiario' => $color['color'],
                'colmena' => Colmena::where("apiario_id",$apiario['id'])->get(),
            );
            // Agrego datos al resultados.
            array_push($resultado,$datos);
        }
        return $resultado;
    }


    /**
     * Retornar todos los apiarios existentes con sus colmenas.
     * 
     * @return Array {Apiarios y Colmenas}
     */
    public static function getApiariosConColmenas($usuario) {
        // Creo un arreglo para guardar los resultados.
        $resultado = array();
        // Obtengo todos los apiarios.
        $apiarios = Apiario::where('apicultor_id',$usuario->id)->get();
        // Recorro los apiarios.
        foreach($apiarios as $apiario) {

            $color = Apiario::getColor($apiario->id);

            // Creo estructura de respuesta.    
            $datos = array(
                'id_apiario' => $apiario['id'],
                'nombre_fantasia' => $apiario->nombre_fantasia, 
                'localidad_chacra' => $apiario->localidad_chacra,
                'direccion_chacra' => $apiario->direccion_chacra,
                'latitud' => $apiario->latitud,
                'longitud' => $apiario->longitud,
                'fecha_creacion' => $apiario->fecha_creacion,
                'propietario_chacra' => $apiario->propietario_chacra,
                'descripcion' => $apiario->descripcion,
                'colmenas' => Colmena::where('apiario_id',$apiario['id'])->get(),
                'estado_apiario' => $color['color'],
            );
            // Agrego datos al resultados.
            array_push($resultado,$datos);
        }
        return $resultado;
    }

    /**
     * Obtengo los apiarios registrados.
     * @return array: con el siguiente formato: 
     *  [
     *      [
     *          'id_apiario': 1,
     *          'chacra' = "Chacra 365"
     *          'colmenas' = [1,2,3,4,5,6,7]    
     *      ],
     *      [
     *          'id_apiario': 2,
     *          'chacra' = "Chacra 363"
     *          'colmenas' = [1,2,3] 
     *      ]
     *  ]
     * 
     */
    public static function getTodosApiarios() {
        // Creo un arreglo para guardar los resultados.
        $resultado = array();
        // Obtengo todos los apiarios.
        $apiarios = Apiario::all();
        // Recorro los apiarios.
        foreach($apiarios as $apiario) {
            // Creo estructura de respuesta.    
            $datos = array(
                'id_apiario' => $apiario['id'],
                'chacra' => $apiario->getChacraDelApiario(),
                'colmenas' => Apiario::getColmenasDelApiario($apiario['id'])
            );
            // Agrego datos al resultados.
            array_push($resultado,$datos);
        }
        return $resultado;
    }

    /**
     * Obtengo la dirección de la chacra donde se encuentra el apiario.
     * 
     * @return String dirección de la chacra.
     */
    public function getChacraDelApiario() {
        $chacra = Chacra::where('id',$this->chacra_id)->first();
        return $chacra['direccion']." - (".$chacra['localidad'].")";
    }

   
    /**
     * Obtengo las colmenas de un apiario.
     * 
     * @param int $apiario_id
     * @return Colmenas
     */
    public function getColmenas() {
        return Colmena::where('apiario_id',$this->id)->get();
    }


    /**
     * Obtengo los ids de las colmenas del apiario.
     * 
     * @return array con los ids de las colmenas.
     */
    public static function getColmenasDelApiario($apiario_id) {
        return Colmena::where('apiario_id',$apiario_id)->pluck('id');
    }
    

    /**
     * Retorna la cantidad de colmenas para el apiario pasado como parámetro.
     * 
     * @param int $apiario_id
     * @return int cantidad de colmenas del apiario
     */
    public static function getCantidadColmenas($apiario_id) {
        return Colmena::where('apiario_id', $apiario_id)->count();
    }
    
    
    /**
     * Devuelve un contador con las colmenas en c/estado del apiario.
     */
    public static function getEstadoContador($apiario_id) {

        $contador = array(
            "verde" => 0,
            "amarillo" => 0,
            "rojo" => 0,
        );
        
        $colmenas = Colmena::where('apiario_id',$apiario_id)->get();
        $cantidad_colmenas = sizeof($colmenas);
       
        
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

                if( $colorSenial == "rojo"  ) $contador["rojo"]++;
                else if( $colorTemperatura == "rojo"  || $colorHumedad == "rojo" ) $contador["rojo"]++;
                else if( $colorSenial == "amarillo" ) $contador["amarillo"]++; // corregido :)
                else if( $colorTemperatura == "amarillo"  || $colorHumedad == "amarillo" ) $contador["amarillo"]++;
                else $contador["verde"]++;

            }
            else { // Si entro acá es porque la colmena no tiene revisaciones.
                $contador["rojo"]++;
            }
        }

        return $contador;
    }



    /**
     * Devuelve el estado {verde, amarillo, rojo} del apiario.
     */
    public static function getColor($apiario_id) {

        $contador = array(
            "verde" => 0,
            "amarillo" => 0,
            "rojo" => 0,
        );
        
        $colmenas = Colmena::where('apiario_id',$apiario_id)->get();
        $cantidad_colmenas = sizeof($colmenas);
        if( $cantidad_colmenas == 0 ) return array(
            "color" => "verde",
            "contador" => $contador
        );
        
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

                if( $colorSenial == "rojo"  ) $contador["rojo"]++;
                else if( $colorTemperatura == "rojo"  || $colorHumedad == "rojo" ) $contador["rojo"]++;
                else if( $colorSenial == "amarillo" ) $contador["amarillo"]++; // corregido :)
                else if( $colorTemperatura == "amarillo"  || $colorHumedad == "amarillo" ) $contador["amarillo"]++;
                else $contador["verde"]++;

            }
            else { // Si entro acá es porque la colmena no tiene revisaciones.
                $contador["rojo"]++;
            }
        }

        // Sacar porcentajes y determinar color del apiario
        // Si el rojo supera el 5% entonces retorno "rojo" ---> cambié la lógica: si hay al menos una colmena en rojo, el apiario es rojo.
        // Si existe al menos una colmena en amarillo entonces retorno amarillo
        // Si no retorno "verde"
        //$porcentaje_rojo = $contador["rojo"] * 100 / $cantidad_colmenas;
        // if( $porcentaje_rojo > 5 ) {
        if( $contador["rojo"] > 0 ) {
            return array(
                "color" => "rojo",
                "contador" => $contador
            );
        }
        //$porcentaje_amarillo = $contador["amarillo"] * 100 / $cantidad_colmenas;
        // if( $porcentaje_amarillo > 5 ) {
        if( $contador["amarillo"] > 0 ) {
            return array(
                "color" => "amarillo",
                "contador" => $contador
            );
        }

        return array(
                "color" => "verde",
                "contador" => $contador
        );
    }


    


   public static function filtrarApiarios($ciudad, $apicultor) {

        $resultado = array();
        $apiarios = array();

        if( $ciudad == "Todos" && $apicultor == "Todos"  ) {
            $apiarios = Apiario::all();
        }
        elseif( $ciudad == "Todos" ) {

            if(  $apicultor != "" ) {
                $apiarios = Apiario::where('apicultor_id',$apicultor)->get();                
            }
            else {
                $apiarios = Apiario::all();
            }

        }
        elseif( $apicultor == "Todos" ) {
            if( $ciudad != "" ) {
                $apiarios = Apiario::where('localidad_chacra',$ciudad)->get();
            }
            else {
                $apiarios = Apiario::all();
            }
        }
        else {
            if( $ciudad != "" && $apicultor != "" ) {
                $apiarios = Apiario::where('localidad_chacra',$ciudad)->where('apicultor_id',$apicultor)->get();                
            }
            elseif ($ciudad != "") {
                $apiarios = Apiario::where('localidad_chacra',$ciudad)->get();
            }
            elseif(  $apicultor != ""  ) {
                $apiarios = Apiario::where('apicultor_id',$apicultor)->get();                
            }

        }


        foreach( $apiarios as $apiario ) {
                
            $parcial = array(
                'apiario' => $apiario,
                'apicultor' => User::where('id',$apiario->apicultor_id)->first(),
                'colmenas' => Colmena::where('apiario_id',$apiario->id)->count(),
                'estado' => (Apiario::getColor($apiario->id))["color"],
            );

            array_push($resultado,$parcial);
        }


        return $resultado;
   }
}

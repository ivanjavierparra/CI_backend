<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Apiario;

class Chacra extends Model
{
    protected $table = 'chacras';
        
    protected $primaryKey = 'id';

    protected $fillable = array(
        'localidad',
        'direccion',
        'propietario'
    );
    
    
    /**
     * Permite crear una Chacra.
     * 
     * @param datos es un arreglo con direccion, localidad y propietario.
     */
    public static function crearChacra($datos) {

        // Verifico si existe chacra.
        $chacra = Chacra::where('direccion',$datos['direccion'])
                        ->where('localidad',$datos['localidad'])
                        ->first();
        
        // Si existe chacra, retorno error.
        if($chacra) {
            return array(
                'resultado' => 500,
                'mensaje' => 'Ya existe esa chacra.'
            );
        }
        else {
            try {
                $objetoChacra = Chacra::create($datos);
                return array(
                    'resultado' => 200,
                    'mensaje' => 'Chacra creada.'
                );
            } catch(\Exception $e) {
                return array(
                    'resultado' => 200,
                    'mensaje' => $e->getMessage()
                );
            }
        }
    }


    

   
}

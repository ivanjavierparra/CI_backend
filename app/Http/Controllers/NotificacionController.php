<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Notificacion;
use Illuminate\Support\Facades\Auth;
use JWTAuth;

class NotificacionController extends Controller
{
    public function getNotificaciones(Request $request) {

        $usuario = JWTAuth::parseToken()->authenticate();

        $notificaciones_no_leidas = Notificacion::where('apicultor_id',$usuario->id)->where('leida',false)->where('eliminada',false)->orderBy('id','desc')->get();
        $notificaciones_leidas = Notificacion::where('apicultor_id',$usuario->id)->where('leida',true)->where('eliminada',false)->orderBy('id','desc')->get();

        Notificacion::setNotificacionesLeidas($usuario->id);

        $resultado = array(
            "no_leidas" => $notificaciones_no_leidas,
            "leidas" => $notificaciones_leidas,
        );

        return response()->json($resultado, 200); 
    }

    public function delete(Request $request) {

        $usuario = JWTAuth::parseToken()->authenticate();
        $notificacion_id = $request['notificacion_id'];

        // Elimino la notificación
        $notificacion = Notificacion::where('id',$notificacion_id)->first();
        $notificacion->eliminada = true;
        $notificacion->save();

        // Busco mis notificaciones
        $notificaciones_no_leidas = Notificacion::where('apicultor_id',$usuario->id)->where('leida',false)->where('eliminada',false)->orderBy('id','desc')->get();
        $notificaciones_leidas = Notificacion::where('apicultor_id',$usuario->id)->where('leida',true)->where('eliminada',false)->orderBy('id','desc')->get();

        // Seteo las notificacion no leidas como leidas
        Notificacion::setNotificacionesLeidas($usuario->id);

        // Devuelvo las notificaciones restantes 
        $resultado = array(
            "no_leidas" => $notificaciones_no_leidas,
            "leidas" => $notificaciones_leidas,
        );

        return response()->json($resultado, 200); 

    }

    public function deleteMasive(Request $request) {

        $usuario = JWTAuth::parseToken()->authenticate();
        $notificacion_id = json_decode($request['notificacion_id'],true);

        foreach($notificacion_id as $id) {
            // Elimino la notificación
            $notificacion = Notificacion::where('id',$id)->first();
            $notificacion->eliminada = true;
            $notificacion->save();
        }

        // Busco mis notificaciones
        $notificaciones_no_leidas = Notificacion::where('apicultor_id',$usuario->id)->where('leida',false)->where('eliminada',false)->orderBy('id','desc')->get();
        $notificaciones_leidas = Notificacion::where('apicultor_id',$usuario->id)->where('leida',true)->where('eliminada',false)->orderBy('id','desc')->get();

        // Seteo las notificacion no leidas como leidas
        Notificacion::setNotificacionesLeidas($usuario->id);

        // Devuelvo las notificaciones restantes 
        $resultado = array(
            "no_leidas" => $notificaciones_no_leidas,
            "leidas" => $notificaciones_leidas,
        );

        return response()->json($resultado, 200);
    }


    public function getUltimasCincoNotificaciones(Request $request) {

        $usuario = JWTAuth::parseToken()->authenticate();

        $ultimas_cinco_notificaciones_no_leidas = Notificacion::where('apicultor_id',$usuario->id)->where('leida',false)->where('eliminada',false)->orderBy('id','desc')->take(5)->get();

        $total_notificaciones_no_leidas = Notificacion::where('apicultor_id',$usuario->id)->where('leida',false)->where('eliminada',false)->count();

        // Devuelvo  
        $resultado = array(
            "notificaciones" => $ultimas_cinco_notificaciones_no_leidas,
            "total" => $total_notificaciones_no_leidas,
        );

        return response()->json($resultado, 200);
    }
}

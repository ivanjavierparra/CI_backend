<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::group([

    'middleware' => 'api',
    'prefix' => 'auth'

], function ($router) {

    Route::post('login', 'AuthController@login');

    Route::post('register', 'AuthController@register');

    Route::post('edite', 'AuthController@edite');

    Route::post('avatar', 'AuthController@avatar');

    Route::post('update', 'AuthController@update');

    Route::post('logout', 'AuthController@logout');

    Route::post('refresh', 'AuthController@refresh');

    Route::post('me', 'AuthController@me');

    Route::post('password', 'AuthController@password');

});



/*
    ** Rutas para usuarios autenticados. **
 Por defecto se agrega el prefijo "api", por lo tanto, para acceder a una ruta
 tendremos que hacer http://localhost:8000/api/colmenas/dashboard
 Acá le paso un token, el middleware llamado "jwt.verify" trata de decodificar el 
 usuario a partir del token recibido. Si lo decodifica entonces permite el acceso a las rutas.

*/
Route::group(['middleware' => ['jwt.verify']], function() {
    
    /* Temperatura y Humedad de una colmena proveniente de Arduino. */
    Route::post('/revisacion', 'RevisacionTemperaturaHumedadController@crearRevisacion');
    Route::get('/revisacion/temperaturayhumedad', 'RevisacionTemperaturaHumedadController@getTemperaturaHumedad');
    Route::get('/revisacion/colmena', 'RevisacionTemperaturaHumedadController@getRevisaciones');
    Route::get('/revisacion/tyh/colmena', 'RevisacionTemperaturaHumedadController@getRevisacionesTyH');
    Route::get('/revisacion/apiario/colmena', 'RevisacionTemperaturaHumedadController@getRevisacionesColmena');
    Route::get('/revisacion/apiario/colmena/ultima_semana', 'RevisacionTemperaturaHumedadController@obtenerTyHUltimaSemana');
    Route::get('/revisacion/tyh/comparacion/colmenas', 'RevisacionTemperaturaHumedadController@getComparacionColmenas');
    Route::get('/revisaciones/csv','RevisacionTemperaturaHumedadController@crearCSV');
    Route::get('/revisaciones/colmenas/csv','RevisacionTemperaturaHumedadController@crearColmenasCSV');
    Route::get('/revisaciones/datatable/csv','RevisacionTemperaturaHumedadController@crearDatatableCSV');
    Route::get('/revisaciones/senal/todas','RevisacionTemperaturaHumedadController@getAllRevisacionesSenalApicultor');

    /*  Señal  */
    Route::get('/revisacion/senal', 'RevisacionTemperaturaHumedadController@getSenialDiaria');
    Route::get('/revisacion/senal/detalle', 'RevisacionTemperaturaHumedadController@getDetalleSenialDiaria');
    Route::get('/revisacion/senal/fechas', 'RevisacionTemperaturaHumedadController@getSenialRangoFechas');
    Route::get('/revisaciones/senial/csv','RevisacionTemperaturaHumedadController@crearSenialCSV');

    /* Chacras */
    Route::get('/chacras','ChacraController@getChacras');
    Route::post('/chacras','ChacraController@crearChacra');


    /* Apiarios */
    Route::get('/apiarios','ApiarioController@getApiarios');
    Route::post('/apiarios','ApiarioController@crearApiario');
    Route::get('/apiario/estado','ApiarioController@getEstadoApiario');
    Route::get('/apiarios/estados','ApiarioController@getApiariosEstado');
    Route::get('/apiarios/estado/detalle','ApiarioController@getDetalleEstadoColmenas');
    Route::get('/apiarios/colmenas/estado','ApiarioController@getApiariosColmenaEstado');
    Route::post('/apiario/editar','ApiarioController@editarApiario');
    Route::get('/apiarios/todos','ApiarioController@getTodosApiarios');
    Route::get('/apiario/colmenas','ApiarioController@getColmenasDelApiario');
    Route::get('/apiarios/colmenas','ApiarioController@getTodasColmenas');
    Route::get('/apiarios/apiariosycolmenas','ApiarioController@getMisApiarios');
    Route::get('/apiarios/{id?}/colmenas','ApiarioController@getColmenas');
    Route::get('/apiarios/ciudad','ApiarioController@getApiariosPorCiudad');
    Route::get('/apiarios/dashboard','ApiarioController@getDashboardApiarios');
    Route::get('/colmenas/dashboard','ApiarioController@getDashboardColmenas');
    Route::get('/apiarios/alertas','ApiarioController@obtenerAlertasRevisacionesApiarios');
    Route::get('/apiario/alertas/colores','ApiarioController@obtenerAlertarPorApiario');
    Route::get('/apiario/alertas/ciudad','ApiarioController@obtenerAlertasPorEstadoyCiudad');
    Route::get('/apiarios/cantidades','ApiarioController@obtenerCantidadesApiariosColmenas');
    Route::get('/apiarios/apicultor','ApiarioController@getApiariosApicultor');
    Route::get('/apiarios/apicultor/id','ApiarioController@getApiariosApicultorID');
    Route::get('/apicultores/apiarios/colmenas','ApiarioController@getApiariosColmenas');
    

    /* Colmenas */
    Route::get('/colmenas','ColmenaController@getColmenas');
    Route::post('/colmenas','ColmenaController@crearColmena');
    Route::post('/colmena/editar','ColmenaController@editarColmena');
    Route::get('/colmena/detalle/tyh','ColmenaController@getUltimaRevisacion');
    Route::get('/colmena/ciudad/alertas','ColmenaController@alertasColmenasCiudad');
    Route::get('/alertas/dashboard','ColmenaController@alertasDashboard');
    Route::get('/colmenas/estado/alerta_peligro','ColmenaController@getColmenasAlertayPeligro');
    Route::get('/colmenas/estados','ColmenaController@obtenerEstadoColmenas');
    Route::get('/colmenas/todos','ColmenaController@obtenerEstadoColmenasApicultor');
    Route::get('/colmenas/estado/detalle','ColmenaController@detalleEstado');

    /* Scraping de Temperatura y Humedad */
    Route::get('/scraping', 'ScrapingController@example')->name('scraping');

    /* Clima */
    Route::get('/clima/ciudad/dashboard', 'ClimaController@obtenerUltimoClima');
    Route::get('/clima/ciudad/variable', 'ClimaController@obtenerVariableClimatica');
    Route::get('/clima/ciudad/historico', 'ClimaController@obtenerHistoricoCiudad');
    Route::get('/clima/ciudad/charts', 'ClimaController@getClimaCiudadGraficos');

    /* Notificaciones */
    Route::get('/notificaciones/apicultor', 'NotificacionController@getNotificaciones');
    Route::get('/notificaciones/eliminar', 'NotificacionController@delete');
    Route::get('/notificaciones/eliminar/masiva', 'NotificacionController@deleteMasive');
    Route::get('/notificaciones/dashboard', 'NotificacionController@getUltimasCincoNotificaciones');
    
    
    /* Imagenes del Perfil de Usuario */
    Route::get('public/img/{filename}', function ($filename)
    {
        $path = public_path('\img\\' . $filename);

        if (!File::exists($path)) {
            abort(404);
        }

        $file = File::get($path);
        $type = File::mimeType($path);

        $response = Response::make($file, 200);
        $response->header("Content-Type", $type);

        return $response;
    });



    
    /*  **Rutas sólo para administradores**  */
    Route::group(['middleware' => ['admin.jwt.verify']], function() { 

        /* Apiarios */
        Route::get('/admin/apiarios','ApiarioController@getAdminApiarios');
        Route::get('/admin/apiarios/completo','ApiarioController@getAdminApiariosCompleto');
        Route::get('/admin/apicultores/apiarios/colmenas','ApiarioController@getAdminApiariosApicultoresColmenas');
        Route::get('/admin/home/tarjetas','ApiarioController@getDatosTarjetas');
        Route::get('/admin/apiarios/ciudad','ApiarioController@obtenerApiariosPorCiudad');
        Route::get('/admin/apiarios/complicados','ApiarioController@obtenerApiariosComplicados');
        Route::get('/admin/estados/colmenas','ApiarioController@getTyHApiarios');
        Route::get('/admin/estados/contador/colmenas','ApiarioController@getEstadoTodasLasColmenas');
        Route::get('/admin/todos/apiarios','ApiarioController@getTodosLosApiarios');
        Route::get('/admin/apiarios/colmenas','ApiarioController@getColmenasDeUnApiario');
        Route::get('/admin/apiarios/detalle','ApiarioController@getAdminApiarioDetalle');
        
        
        /* Colmenas */
        Route::get('/admin/todos/colmenas','ColmenaController@getAllColmenas');
        Route::get('/admin/colmenas/todos','ColmenaController@obtenerTodasColmenasSegunEstado');

        /* Clima */
        Route::get('/admin/clima/todos','ClimaController@getAllClimas');

        /* Revisaciones Temperatura, Humedad y Señal */
        Route::get('/admin/revisaciones/tyh/todas','RevisacionTemperaturaHumedadController@getAllRevisacionesTyH');
        Route::get('/admin/revisaciones/senal/todas','RevisacionTemperaturaHumedadController@getAllRevisacionesSenal');

        /* Usuarios */
        Route::get('/admin/users/lastusers','ApiarioController@getLastUsers');
        Route::get('/admin/users/mascolmenas','ColmenaController@getUsersMasColmenas');
        Route::get('/admin/apicultores_y_apiarios','ApiarioController@getApicultoresApiarios');
        Route::get('/admin/apicultores_apiarios_colmenas','ApiarioController@getApicultoresApiariosColmenas');
        Route::get('/admin/usuarios','ApiarioController@getTodosLosUsuarios');
        Route::get('admin/todos/apicultores','ApiarioController@getTodosApicultores');

        
        
    });
});
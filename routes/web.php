<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

/*Route::get('/', function () {
    return view('welcome');
});*/

/* Arduino  */
Route::get('/', 'RevisacionTemperaturaHumedadController@procesarArduino');


// /* Temperatura y Humedad de una colmena proveniente de Arduino. */
// Route::post('/revisacion', 'RevisacionTemperaturaHumedadController@crearRevisacion');
// Route::get('/revisacion/temperaturayhumedad', 'RevisacionTemperaturaHumedadController@getTemperaturaHumedad');
// Route::get('/revisacion/colmena', 'RevisacionTemperaturaHumedadController@getRevisaciones');
// Route::get('/revisacion/tyh/colmena', 'RevisacionTemperaturaHumedadController@getRevisacionesTyH');
// Route::get('/revisacion/apiario/colmena', 'RevisacionTemperaturaHumedadController@getRevisacionesColmena');
// Route::get('/revisacion/apiario/colmena/ultima_semana', 'RevisacionTemperaturaHumedadController@obtenerTyHUltimaSemana');


// /*  Señal  */
// Route::get('/revisacion/senal', 'RevisacionTemperaturaHumedadController@getSenialDiaria');

// /* Chacras */
// Route::get('/chacras','ChacraController@getChacras');
// Route::post('/chacras','ChacraController@crearChacra');


// /* Apiarios */
// Route::get('/apiarios','ApiarioController@getApiarios');
// Route::post('/apiarios','ApiarioController@crearApiario');
// Route::post('/apiario/editar','ApiarioController@editarApiario');
// Route::get('/apiarios/todos','ApiarioController@getTodosApiarios');
// Route::get('/apiario/colmenas','ApiarioController@getColmenasDelApiario');
// Route::get('/apiarios/colmenas','ApiarioController@getTodasColmenas');
// Route::get('/apiarios/{id?}/colmenas','ApiarioController@getColmenas');
// Route::get('/apiarios/ciudad','ApiarioController@getApiariosPorCiudad');
// Route::get('/apiarios/dashboard','ApiarioController@getDashboardApiarios');
// Route::get('/colmenas/dashboard','ApiarioController@getDashboardColmenas');
// Route::get('/apiarios/alertas','ApiarioController@obtenerAlertasRevisacionesApiarios');
// Route::get('/apiario/alertas/colores','ApiarioController@obtenerAlertarPorApiario');
// Route::get('/apiario/alertas/ciudad','ApiarioController@obtenerAlertasPorEstadoyCiudad');


// /* Colmenas */
// Route::get('/colmenas','ColmenaController@getColmenas');
// Route::post('/colmenas','ColmenaController@crearColmena');
// Route::post('/colmena/editar','ColmenaController@editarColmena');
// Route::get('/colmena/detalle/tyh','ColmenaController@getUltimaRevisacion');
// Route::get('/colmena/ciudad/alertas','ColmenaController@alertasColmenasCiudad');
// Route::get('/alertas/dashboard','ColmenaController@alertasDashboard');


// /* Scraping de Temperatura y Humedad */
// Route::get('/scraping', 'ScrapingController@example')->name('scraping');

// /* Clima */
// Route::get('/clima/ciudad/dashboard', 'ClimaController@obtenerUltimoClima');
// Route::get('/clima/ciudad/variable', 'ClimaController@obtenerVariableClimatica');
// Route::get('/clima/ciudad/historico', 'ClimaController@obtenerHistoricoCiudad');
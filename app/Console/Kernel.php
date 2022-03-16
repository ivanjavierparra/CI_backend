<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use DateTime;
use DateTimeZone;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        'App\Console\Commands\ObtenerClima',
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $dateObj = DateTime::createFromFormat('U.u', microtime(TRUE));
        $dateObj->setTimeZone(new DateTimeZone('America/Argentina/Buenos_Aires'));

        /**
         * Documentación: https://laravel.com/docs/5.8/scheduling
         */
        
        /* CLIMA */ 
        $schedule->command('clima:obtener') // Obtengo el clima de Trelew, Gaiman, Dolavon y 28 de Julio.
                ->twiceDaily(10, 15);

        /* NOTIFICACIONES */
        $schedule->command('notificaciones:crear') // Creo notificaciones para apicultores cuyas colmenas no reciben revisaciones desde hace más de un día.
                ->daily();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

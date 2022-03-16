<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Clima;

class ObtenerClima extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clima:obtener';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Este comando se encarga de ejecutar el método para realizar consultar a la API de AccuWeahter 
    y obtener así el clima de hoy.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        Clima::obtenerClima("Rawson","3092");
        Clima::obtenerClima("Trelew","3091");
        Clima::obtenerClima("Gaiman","8245");
        Clima::obtenerClima("Dolavon","8259");
        Clima::obtenerClima("28 de Julio","523079_pc");
    }
}

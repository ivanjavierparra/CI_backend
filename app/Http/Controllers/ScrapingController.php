<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Goutte\Client;
use App\Clima;
use Illuminate\Support\Facades\Auth;
use JWTAuth;

class ScrapingController extends Controller
{
    
    /**
     * Establece la página donde se realizar el scraping del clima.
     * 
     */
    public function example(Client $client) {

        /*
           Scraping de CLIMA:
            FINAL: SCRAPING DE https://www.meteored.com.ar/tiempo-en_28+De+Julio-America+Sur-Argentina-Chubut--1-318964.html
        */
        
        // URL PARSER ONLINE: https://www.freeformatter.com/url-parser-query-string-splitter.html
       
        $URL = "https://www.meteored.com.ar/tiempo-en_28+De+Julio-America+Sur-Argentina-Chubut--1-318964.html";
        $crawler = $client->request('GET', $URL);
        //$crawler = $client->request('GET', $URL, ['verify' => true]);
        
        
        dd($crawler);
    }


    /**
     * Obtiene el clima de las ciudades desde la API de ACCUWHEATHER
     * 
     * 
     */
    public function obtenerClima(Request $request) {

        Clima::obtenerClima("Rawson","3092");
        Clima::obtenerClima("Trelew","3091");
        Clima::obtenerClima("Gaiman","8245");
        Clima::obtenerClima("Dolavon","8259");
        Clima::obtenerClima("28 de Julio","523079_pc");

        /* curl -X GET "http://dataservice.accuweather.com/currentconditions/v1/523079_pc?apikey=HmNCgt37q5wVuKhJroHAGpqnStSjzN0A&language=es-es&details=true"
        curl -X GET "http://dataservice.accuweather.com/forecasts/v1/daily/1day/523079_pc?apikey=HmNCgt37q5wVuKhJroHAGpqnStSjzN0A&language=es-es&details=true&metric=true"

        https://openweathermap.org/city/3859278  //28 de julio y dolavon
        https://openweathermap.org/city/3855284 // gaiman
        https://openweathermap.org/city/3833883 // trelew */
    }


    /**
     * Ejemplo de Scraping con Goutte.
     * 
     */
    public function example_2(Client $client) {
        
        /*
            http://tiempoytemperatura.es/argentina/trelew.html#por-horas
            
            https://www.accuweather.com/es/ar/trelew/3091/current-weather/3091  
            https://www.accuweather.com/es/ar/gaiman/8245/current-weather/8245
            https://www.accuweather.com/es/ar/dolavon/8259/current-weather/8259
            https://www.accuweather.com/es/ar/veintiocho-de-julio/9107/current-weather/523079_pc
        */
        
        // URL PARSER ONLINE: https://www.freeformatter.com/url-parser-query-string-splitter.html
        $params = array(
            'file' => 'c-ride_list',
            'search' => 'place',
            'From_lat_long' => '(-38.0054771, -57.54261059999999)',
            'To_lat_long' => '(-34.6036844, -58.381559100000004)',
            'From' => 'Mar del Plata, Buenos Aires, Argentina',
            'To' => 'CABA, Buenos Aires, Argentina',
        );
        $URL = "https://jumpin.com.ar/index.php/?".http_build_query($params);
        $crawler = $client->request('GET', $URL);
        
        $K = $crawler->filterXPath('//div[@class="rides-available-lising-box"]')->filter('.rides-available-lising-box');
        $cantidad_viajes_disponibles = $crawler->filterXPath('//div[@class="rides-available"]/h3')->text();
        
        $viajes = $crawler->filterXPath('//div[@class="rides-available-lising-box"]')->filter('.rides-available-lising-box')->each(function($node){
            
            $url_detalle_viaje = $node->filter('a')->attr('href');
            $nombre_del_conductor = $node->filterXPath('//div[@class="rides-available-lising-box"]/a/div/div/h3/text()[1]')->text();
            $imagen_del_conductor = $node->filterXPath('//div[@class="rides-available-lising-box"]/a/div/div/div/strong/b/img/@src')->text();
            $fecha_hora = $node->filterXPath('//div[@class="rides-available-lising-box"]/a/div[1]/div[1]/h3[1]/b[1]')->text();
            
            // El unico estado que pone es el "Completo", por esta sentencia puede tirar error. Usar try catch.
            //$estado = $node->filterXPath('//div[@class="rides-available-lising-box"]/a/div[1]/div[1]/h3[1]/b[2]')->text();
            $tramos = $node->filterXPath('//div[@class="rides-available-lising-box"]/a/div/div/div[2]/p')->text();
            $origen = $node->filterXPath('//div[@class="rides-available-lising-box"]/a/div/div/div[2]/span[1]/text()')->text();
            $destino = $node->filterXPath('//div[@class="rides-available-lising-box"]/a/div/div/div[2]/span[2]/text()')->text();
            
            $precio_por_pasajero = $node->filterXPath('//div[@class="rides-available-lising-box"]/a/div/div[2]/h3/text()')->text();
            $vehiculo = $node->filterXPath('//div[@class="rides-available-lising-box"]/a/div/div[2]/p[1]/text()')->text();
            $tipo_de_combustible = $node->filterXPath('//div[@class="rides-available-lising-box"]/a/div/div[2]/p[2]/text()')->text();
            /* PREFERENCIAS: No todos los viajes tienen las 5 preferencias, algunas no están, por eso hacer un try catch porque puede tirar error.
            Además, vienen desordenadas.
            $prefrencia_1 = $node->filterXPath('//div[@class="rides-available-lising-box"]/a/div/div[2]/p[4]/span[1]/img/@src')->text();
            $preferencia_2 = $node->filterXPath('//div[@class="rides-available-lising-box"]/a/div/div[2]/p[4]/span[2]/img/@src')->text();
            $preferencia_3 = $node->filterXPath('//div[@class="rides-available-lising-box"]/a/div/div[2]/p[4]/span[3]/img/@src')->text();
            $prefrencia_4 = $node->filterXPath('//div[@class="rides-available-lising-box"]/a/div/div[2]/p[4]/span[4]/img/@src')->text();
            $preferencia_5 = $node->filterXPath('//div[@class="rides-available-lising-box"]/a/div/div[2]/p[4]/span[5]/img/@src')->text(); */
            $lugares_disponibles = $node->filterXPath('//div[@class="rides-available-lising-box"]/a/div/div[2]/span[1]')->text();
            print $node->filterXPath('//div[@class="rides-available-lising-box"]/a/div[1]/div[1]/h3[1]/b[1]')->text()."---putaso--- \n";
        });
           
        $a = "";
        dd($a);
    }


}

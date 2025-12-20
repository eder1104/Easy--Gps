<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Routing\Controller;
use Illuminate\Http\Client\Response;

class GpsController extends Controller
{
    private $auth = ['easygpsorg@gmail.com', 'Ederyair1231!'];
    private $host = 'http://127.0.0.1:8082/api';
    
    // Cambia esto a FALSE cuando quieras usar datos reales de Traccar
    private $useMockData = true;

    public function index()
    {
        if ($this->useMockData) {
            $devices = $this->getMockDevices();
        } else {
            /** @var Response $devicesResponse */
            $devicesResponse = Http::timeout(4)
                ->withBasicAuth($this->auth[0], $this->auth[1])
                ->get("{$this->host}/devices");

            if ($devicesResponse->failed()) {
                return view('welcome', ['devices' => []]);
            }
            $devices = $devicesResponse->json();
        }

        foreach ($devices as &$device) {
            if ($this->useMockData) {
                $lastPos = $this->getMockPosition($device['id']);
            } else {
                /** @var Response $posResponse */
                $posResponse = Http::timeout(2)
                    ->withBasicAuth($this->auth[0], $this->auth[1])
                    ->get("{$this->host}/positions", ['deviceId' => $device['id']]);

                $positions = $posResponse->json();
                $lastPos = !empty($positions) ? end($positions) : null;
            }

            $this->procesarInteligencia($device, $lastPos);
        }

        return view('welcome', ['devices' => $devices]);
    }

    public function show($id)
    {
        if ($this->useMockData) {
            $devices = $this->getMockDevices();
            $device = collect($devices)->firstWhere('id', $id);
            if (!$device) return redirect('/');
        } else {
            /** @var Response $deviceResponse */
            $deviceResponse = Http::timeout(4)
                ->withBasicAuth($this->auth[0], $this->auth[1])
                ->get("{$this->host}/devices", ['id' => $id]);

            if ($deviceResponse->failed() || empty($deviceResponse->json())) {
                return redirect('/');
            }
            $device = $deviceResponse->json()[0];
        }

        if ($this->useMockData) {
            $lastPos = $this->getMockPosition($id);
        } else {
            /** @var Response $posResponse */
            $posResponse = Http::timeout(4)
                ->withBasicAuth($this->auth[0], $this->auth[1])
                ->get("{$this->host}/positions", ['deviceId' => $id]);

            $positions = $posResponse->json();
            $lastPos = !empty($positions) ? end($positions) : null;
        }

        $this->procesarInteligencia($device, $lastPos);

        return view('vistas.infoCoche', ['device' => $device]);
    }

    private function procesarInteligencia(&$device, $lastPos)
    {
        if ($lastPos && isset($lastPos['latitude'])) {
            $device['posicion'] = $lastPos;
            
            $voltaje = $lastPos['attributes']['power'] ?? ($lastPos['attributes']['batteryLevel'] ?? 0);
            $device['salud_bateria'] = $this->calcularSaludBateria($voltaje);
            
            // Lógica Teltonika: Si speed > 1 nudo (aprox 2km/h) + Motion TRUE + Ignition FALSE = GRÚA
            $movimientoSospechoso = ($lastPos['speed'] > 1 && ($lastPos['attributes']['motion'] ?? false) && !($lastPos['attributes']['ignition'] ?? true));
            $device['alerta_grua'] = $movimientoSospechoso;
        } else {
            $device['posicion'] = null;
            $device['salud_bateria'] = ['estado' => 'Sin datos', 'color' => 'gray'];
            $device['alerta_grua'] = false;
        }
    }

    private function calcularSaludBateria($voltaje)
    {
        if ($voltaje == 0) return ['estado' => 'Desconectada', 'color' => 'red'];
        if ($voltaje >= 12.6) return ['estado' => 'Excelente', 'color' => 'green'];
        if ($voltaje >= 12.2) return ['estado' => 'Buena', 'color' => 'blue'];
        if ($voltaje >= 11.8) return ['estado' => 'Recargar pronto', 'color' => 'yellow'];
        return ['estado' => 'Crítica / Cambiar', 'color' => 'red'];
    }

    // --- MOCK DATA AREA (Simulación Teltonika FMB920) ---

    private function getMockDevices()
    {
        return [
            [
                'id' => 10,
                'name' => 'Toyota Hilux - Prueba',
                'uniqueId' => '864290040000000',
                'status' => 'online',
                'lastUpdate' => date('Y-m-d H:i:s'),
                'model' => 'FMB920',
                'contact' => ''
            ]
        ];
    }

    private function getMockPosition($deviceId)
    {
        // Escenario: Vehículo siendo remolcado (Grúa)
        // Ignición APAGADA, pero Velocidad y Movimiento ACTIVOS.
        return [
            'deviceId' => $deviceId,
            'latitude' => 4.5709,
            'longitude' => -74.2973,
            'altitude' => 2600,
            'speed' => 15.0, // Nudos (aprox 27 km/h)
            'course' => 0,
            'address' => 'Calle Falsa 123',
            'attributes' => [
                'ignition' => false, // IMPORTANTE: Motor Apagado
                'motion' => true,    // IMPORTANTE: Sensor de movimiento activo
                'power' => 12.1,     // Batería en 12.1V (Salud Regular)
                'batteryLevel' => 100,
                'totalDistance' => 15400500,
                'io200' => 0,        // Sleep Mode (0 = No sleep)
                'sat' => 8           // Satélites conectados
            ]
        ];
    }
}
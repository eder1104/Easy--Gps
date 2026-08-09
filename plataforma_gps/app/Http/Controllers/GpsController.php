<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

class GpsController extends Controller
{
    private $auth = ['easygpsorg@gmail.com', 'Ederyair1231!'];
    private $host = 'http://127.0.0.1:8082/api';
    
    private $useMockData = true;

    public function index()
    {
        $devices = [];
        try {
            if ($this->useMockData) {
                $devices = $this->getMockDevices();
            } else {
                /** @var Response $devicesResponse */
                $devicesResponse = Http::timeout(2)
                    ->withBasicAuth($this->auth[0], $this->auth[1])
                    ->get("{$this->host}/devices");

                if ($devicesResponse->failed()) {
                    $devices = $this->getMockDevices();
                } else {
                    $devices = $devicesResponse->json();
                }
            }
        } catch (\Exception $e) {
            $devices = $this->getMockDevices();
        }

        foreach ($devices as &$device) {
            $lastPos = null;

            try {
                if ($this->useMockData) {
                    $lastPos = $this->getMockPosition($device['id']);
                } else {
                    /** @var Response $posResponse */
                    $posResponse = Http::timeout(2)
                        ->withBasicAuth($this->auth[0], $this->auth[1])
                        ->get("{$this->host}/positions", ['deviceId' => $device['id']]);

                    if ($posResponse->successful()) {
                        $positions = $posResponse->json();
                        $lastPos = !empty($positions) ? end($positions) : $this->getMockPosition($device['id']);
                    } else {
                        $lastPos = $this->getMockPosition($device['id']);
                    }
                }
            } catch (\Exception $e) {
                $lastPos = $this->getMockPosition($device['id']);
            }

            $this->procesarInteligencia($device, $lastPos);
        }

        return view('welcome', ['devices' => $devices]);
    }

    public function show($id)
    {
        $device = null;
        try {
            if ($this->useMockData) {
                $devices = $this->getMockDevices();
                $device = collect($devices)->firstWhere('id', $id);
            } else {
                /** @var Response $deviceResponse */
                $deviceResponse = Http::timeout(2)
                    ->withBasicAuth($this->auth[0], $this->auth[1])
                    ->get("{$this->host}/devices", ['id' => $id]);

                if ($deviceResponse->successful() && !empty($deviceResponse->json())) {
                    $device = $deviceResponse->json()[0];
                } else {
                    $devices = $this->getMockDevices();
                    $device = collect($devices)->firstWhere('id', $id);
                }
            }
        } catch (\Exception $e) {
            $devices = $this->getMockDevices();
            $device = collect($devices)->firstWhere('id', $id);
        }

        if (!$device) {
            $devices = $this->getMockDevices();
            $device = $devices[0];
        }

        $lastPos = null;
        try {
            if ($this->useMockData) {
                $lastPos = $this->getMockPosition($id);
            } else {
                /** @var Response $posResponse */
                $posResponse = Http::timeout(2)
                    ->withBasicAuth($this->auth[0], $this->auth[1])
                    ->get("{$this->host}/positions", ['deviceId' => $id]);

                if ($posResponse->successful() && !empty($posResponse->json())) {
                    $positions = $posResponse->json();
                    $lastPos = end($positions);
                } else {
                    $lastPos = $this->getMockPosition($id);
                }
            }
        } catch (\Exception $e) {
            $lastPos = $this->getMockPosition($id);
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
        return [
            'deviceId' => $deviceId,
            'latitude' => 4.5709,
            'longitude' => -74.2973,
            'altitude' => 2600,
            'speed' => 15.0,
            'course' => 0,
            'address' => 'Bogotá, Colombia - Monitoreo GPS',
            'attributes' => [
                'ignition' => false,
                'motion' => true,
                'power' => 12.1,
                'batteryLevel' => 100,
                'totalDistance' => 15400500,
                'io200' => 0,
                'sat' => 8
            ]
        ];
    }
}
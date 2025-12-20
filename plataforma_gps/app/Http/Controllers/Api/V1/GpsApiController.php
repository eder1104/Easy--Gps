<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use App\Http\Resources\V1\VehiculoResource;

class GpsApiController extends Controller
{
    private $auth = ['easygpsorg@gmail.com', 'Ederyair1231!'];
    private $host = 'http://127.0.0.1:8082/api';
    private $useMockData = true;

    public function index(Request $request)
    {
        $devices = [];

        if ($this->useMockData) {
            $devices = $this->getMockDevices();
        } else {
            /** @var Response $response */
            $response = Http::timeout(5)
                ->withBasicAuth($this->auth[0], $this->auth[1])
                ->get("{$this->host}/devices");

            if ($response->successful()) {
                $devices = $response->json();
            }
        }

        $dataParaResource = [];

        foreach ($devices as $device) {
            $lastPos = null;

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

            $dataParaResource[] = [
                'id' => $device['id'],
                'name' => $device['name'],
                'status' => $device['status'],
                'plan' => $this->obtenerPlanVehiculo($device['id']),
                'posicion_cruda' => $lastPos
            ];
        }

        return VehiculoResource::collection($dataParaResource);
    }

    public function show($id)
    {
        $device = null;
        $lastPos = null;

        if ($this->useMockData) {
            $all = $this->getMockDevices();
            $device = collect($all)->firstWhere('id', $id);
            if (!$device) {
                return response()->json(['success' => false, 'message' => 'Vehículo no encontrado'], 404);
            }
            $lastPos = $this->getMockPosition($id);
        } else {
            /** @var Response $deviceResponse */
            $deviceResponse = Http::timeout(4)
                ->withBasicAuth($this->auth[0], $this->auth[1])
                ->get("{$this->host}/devices", ['id' => $id]);

            if ($deviceResponse->failed() || empty($deviceResponse->json())) {
                return response()->json(['success' => false, 'message' => 'Vehículo no encontrado'], 404);
            }
            $device = $deviceResponse->json()[0];

            /** @var Response $posResponse */
            $posResponse = Http::timeout(4)
                ->withBasicAuth($this->auth[0], $this->auth[1])
                ->get("{$this->host}/positions", ['deviceId' => $id]);

            $positions = $posResponse->json();
            $lastPos = !empty($positions) ? end($positions) : null;
        }

        $data = [
            'id' => $device['id'],
            'name' => $device['name'],
            'status' => $device['status'],
            'plan' => $this->obtenerPlanVehiculo($device['id']),
            'posicion_cruda' => $lastPos
        ];

        return new VehiculoResource($data);
    }

    private function obtenerPlanVehiculo($deviceId)
    {
        return $deviceId == 10 ? 'PREMIUM' : 'BASICO'; 
    }

    private function getMockDevices()
    {
        return [
            ['id' => 10, 'name' => 'Toyota Hilux - Premium', 'status' => 'online', 'uniqueId' => '123456'],
            ['id' => 11, 'name' => 'Chevrolet Spark - Básico', 'status' => 'offline', 'uniqueId' => '789012']
        ];
    }

    private function getMockPosition($deviceId)
    {
        if ($deviceId == 10) {
            return [
                'latitude' => 4.5709, 'longitude' => -74.2973, 'speed' => 15.0, 'course' => 90,
                'attributes' => ['ignition' => false, 'motion' => true, 'power' => 12.1, 'totalDistance' => 5000000]
            ];
        }
        return [
            'latitude' => 6.2442, 'longitude' => -75.5812, 'speed' => 0, 'course' => 0,
            'attributes' => ['ignition' => false, 'motion' => false, 'power' => 12.7, 'totalDistance' => 10000]
        ];
    }
}
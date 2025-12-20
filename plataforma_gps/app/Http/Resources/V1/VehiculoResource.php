<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehiculoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // $this representa el array de datos crudos que llega del controlador
        $plan = $this->resource['plan'] ?? 'BASICO';
        $pos = $this->resource['posicion_cruda'] ?? null;
        
        // Estructura Base (Siempre visible)
        $response = [
            'identificador' => $this->resource['id'],
            'alias' => $this->resource['name'],
            'estado_red' => $this->resource['status'],
            'plan_suscripcion' => $plan,
            'ultima_actualizacion' => date('c'), // ISO 8601
        ];

        // Si hay datos de GPS, los procesamos
        if ($pos) {
            $ignition = $pos['attributes']['ignition'] ?? false;
            $motion = $pos['attributes']['motion'] ?? false;
            $speedKmh = ($pos['speed'] ?? 0) * 1.852;

            $response['telemetria'] = [
                'coordenadas' => [
                    'latitud' => $pos['latitude'],
                    'longitud' => $pos['longitude'],
                    'orientacion' => $pos['course']
                ],
                'motor' => [
                    'encendido' => $ignition,
                    'en_movimiento' => $motion,
                    'velocidad_kmh' => round($speedKmh, 1),
                    'odometro_km' => round(($pos['attributes']['totalDistance'] ?? 0) / 1000, 1)
                ]
            ];

            // LOGICA PREMIUM: Solo agregamos estos bloques si el plan lo permite
            if ($plan === 'PREMIUM') {
                $voltaje = $pos['attributes']['power'] ?? null;
                $alertaGrua = ($speedKmh > 2 && $motion && !$ignition);

                $response['salud'] = [
                    'bateria_voltaje' => $voltaje,
                    'estado_bateria' => $this->getDiagnosticoBateria($voltaje),
                ];

                $response['seguridad'] = [
                    'alerta_grua' => $alertaGrua,
                    'nivel_riesgo' => $alertaGrua ? 'ALTO' : 'BAJO'
                ];
            }
        }

        return $response;
    }

    private function getDiagnosticoBateria($voltaje)
    {
        if ($voltaje === null) return 'DESCONOCIDO';
        if ($voltaje >= 12.6) return 'EXCELENTE';
        if ($voltaje >= 12.2) return 'BUENA';
        if ($voltaje >= 11.8) return 'REGULAR';
        return 'CRITICA';
    }
}
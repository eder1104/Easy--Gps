<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle: {{ $device['name'] }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style> .leaflet-container { background: #111827; } </style>
</head>
<body class="bg-gray-900 min-h-screen text-gray-100 p-6 font-sans">
    
    <div class="max-w-6xl mx-auto mb-8 flex flex-col md:flex-row justify-between items-center gap-4">
        <div class="flex items-center gap-4 w-full md:w-auto">
            <a href="/" class="bg-gray-800 hover:bg-gray-700 p-2 rounded-full transition border border-gray-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-white leading-none">{{ $device['name'] }}</h1>
                <p class="text-xs text-gray-400 mt-1">ID: {{ $device['uniqueId'] }}</p>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-bold uppercase ml-auto md:ml-4 {{ $device['status'] == 'online' ? 'bg-green-900 text-green-400' : 'bg-red-900 text-red-400' }}">
                {{ $device['status'] }}
            </span>
        </div>
        
        @if($device['alerta_grua'])
        <div class="animate-pulse w-full md:w-auto flex items-center justify-center bg-red-600 text-white px-6 py-3 rounded-lg font-bold shadow-lg shadow-red-900/50 border border-red-400">
            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            ALERTA: POSIBLE MOVIMIENTO EN GRÚA
        </div>
        @endif
    </div>

    <div class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-gray-800 rounded-xl p-6 shadow-lg border border-gray-700">
                <h2 class="text-gray-400 text-xs uppercase tracking-widest font-bold mb-4">Salud Eléctrica</h2>
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-4xl font-mono font-bold text-{{ $device['salud_bateria']['color'] }}-400">
                            {{ $device['posicion']['attributes']['power'] ?? 0 }}<span class="text-lg">V</span>
                        </span>
                        <p class="text-sm text-gray-400 mt-1">Batería del Vehículo</p>
                    </div>
                    <div class="h-14 w-14 rounded-full bg-{{ $device['salud_bateria']['color'] }}-900/30 flex items-center justify-center border border-{{ $device['salud_bateria']['color'] }}-500/50">
                        <svg class="w-7 h-7 text-{{ $device['salud_bateria']['color'] }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    </div>
                </div>
                <div class="mt-4 bg-gray-700 rounded-full h-1.5 w-full overflow-hidden">
                    <div class="h-full bg-{{ $device['salud_bateria']['color'] }}-500" style="width: 100%"></div>
                </div>
                <p class="text-xs text-right mt-2 text-{{ $device['salud_bateria']['color'] }}-300 font-bold">
                    {{ $device['salud_bateria']['estado'] }}
                </p>
            </div>

            <div class="bg-gray-800 rounded-xl p-6 shadow-lg border border-gray-700">
                <h2 class="text-gray-400 text-xs uppercase tracking-widest font-bold mb-4">Telemetría</h2>
                <div class="space-y-4">
                    <div class="flex justify-between items-center p-3 bg-gray-700/30 rounded-lg">
                        <span class="text-sm text-gray-400">Ignición</span>
                        <span class="text-sm font-bold {{ ($device['posicion']['attributes']['ignition'] ?? false) ? 'text-green-400' : 'text-gray-500' }}">
                            {{ ($device['posicion']['attributes']['ignition'] ?? false) ? 'ENCENDIDO' : 'APAGADO' }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-gray-700/30 rounded-lg">
                        <span class="text-sm text-gray-400">Movimiento</span>
                        <span class="text-sm font-bold {{ ($device['posicion']['attributes']['motion'] ?? false) ? 'text-blue-400' : 'text-gray-500' }}">
                            {{ ($device['posicion']['attributes']['motion'] ?? false) ? 'EN MARCHA' : 'DETENIDO' }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-gray-700/30 rounded-lg">
                        <span class="text-sm text-gray-400">Distancia Total</span>
                        <span class="text-sm font-bold text-gray-200 font-mono">
                            {{ round(($device['posicion']['attributes']['totalDistance'] ?? 0) / 1000, 1) }} km
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2 bg-gray-800 rounded-xl shadow-lg border border-gray-700 overflow-hidden flex flex-col h-[500px] lg:h-auto">
            <div id="mapDetail" class="flex-1 w-full h-full"></div>
            <div class="p-4 bg-gray-900 border-t border-gray-700 flex justify-between items-center">
                <div>
                    <p class="text-[10px] uppercase text-gray-500 font-bold">Última Conexión</p>
                    <p class="font-mono text-sm text-white">
                        {{ isset($device['lastUpdate']) ? date('d/m/Y H:i:s', strtotime($device['lastUpdate'])) : 'N/A' }}
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-[10px] uppercase text-gray-500 font-bold">Velocidad</p>
                    <p class="text-2xl font-bold text-blue-500 font-mono leading-none">
                        {{ round(($device['posicion']['speed'] ?? 0) * 1.852, 1) }} <span class="text-sm text-gray-400">km/h</span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    @if($device['posicion'])
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const lat = {{ $device['posicion']['latitude'] }};
        const lng = {{ $device['posicion']['longitude'] }};
        const map = L.map('mapDetail').setView([lat, lng], 16);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        }).addTo(map);

        const icon = L.divIcon({
            className: 'custom-div-icon',
            html: "<div style='background-color:#3B82F6; width: 12px; height: 12px; border-radius: 50%; border: 2px solid white; box-shadow: 0 0 10px rgba(59,130,246,0.5);'></div>",
            iconSize: [12, 12],
            iconAnchor: [6, 6]
        });

        L.marker([lat, lng]).addTo(map)
            .bindPopup("<b>{{ $device['name'] }}</b><br>Ubicación Actual")
            .openPopup();
    </script>
    @endif
</body>
</html>
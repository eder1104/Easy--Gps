<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Easy GPS - Panel Realtime</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        #map { height: 100vh; width: 100%; }
        .leaflet-container { background: #f8f9fa; }
    </style>
</head>
<body class="bg-gray-100 overflow-hidden">
    <div class="flex h-screen">
        <div class="w-80 bg-white p-6 shadow-xl z-[1000] border-r border-gray-200 flex flex-col">
            <h1 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                <span class="w-3 h-3 bg-green-500 rounded-full mr-2 animate-pulse"></span>
                Easy GPS Live
            </h1>
            
            <div class="space-y-4 overflow-y-auto flex-1 pr-2">
                @forelse($devices as $device)
                <a href="{{ route('coche.detalle', $device['id']) }}" class="block p-4 bg-gray-50 rounded-lg border-l-4 {{ ($device['status'] ?? '') == 'online' ? 'border-green-500' : 'border-red-500' }} transition hover:bg-gray-100 cursor-pointer shadow-sm">
                    <div class="flex justify-between items-start">
                        <h2 class="text-gray-800 font-semibold">{{ $device['name'] ?? 'Desconocido' }}</h2>
                        <span class="text-[10px] uppercase px-2 py-0.5 rounded bg-gray-200 text-gray-600 border border-gray-300">{{ $device['status'] ?? 'offline' }}</span>
                    </div>
                    
                    @if(isset($device['posicion']))
                        <div class="mt-2 space-y-1">
                            <p class="text-blue-600 text-xs flex justify-between">
                                <span class="font-medium">Velocidad:</span>
                                <span class="font-mono font-bold">{{ round(($device['posicion']['speed'] ?? 0) * 1.852, 1) }} km/h</span>
                            </p>
                            <p class="text-gray-500 text-[10px]">
                                Actualizado: {{ \Carbon\Carbon::parse($device['posicion']['deviceTime'] ?? now())->diffForHumans() }}
                            </p>
                        </div>
                    @else
                        <p class="text-gray-400 text-xs mt-2 italic">Esperando señal GPS...</p>
                    @endif
                </a>
                @empty
                <div class="text-center py-8">
                    <p class="text-gray-400 text-sm">No hay vehículos conectados.</p>
                </div>
                @endforelse
            </div>
            
            <div class="mt-auto pt-4 border-t border-gray-200">
                <button onclick="location.reload()" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-md text-sm font-medium transition shadow-md">
                    Sincronizar ahora
                </button>
            </div>
        </div>

        <div id="map" class="flex-1"></div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const map = L.map('map').setView([4.5709, -74.2973], 6);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        const bounds = [];

        @foreach($devices as $device)
            @if(isset($device['posicion']) && isset($device['posicion']['latitude']))
                (function() {
                    const lat = {{ $device['posicion']['latitude'] }};
                    const lng = {{ $device['posicion']['longitude'] }};
                    const name = "{{ $device['name'] }}";
                    
                    const marker = L.marker([lat, lng]).addTo(map)
                        .bindPopup(`<b class="text-gray-900 text-base">${name}</b><br>Ubicación actual.`);
                    
                    bounds.push([lat, lng]);
                })();
            @endif
        @endforeach

        if (bounds.length > 0) {
            map.fitBounds(bounds, { padding: [50, 50], maxZoom: 16 });
        }
    </script>
</body>
</html>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GPS Manager Pro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        #map { height: 100vh; }
        .leaflet-container { background: #111827; } 
    </style>
</head>
<body class="bg-gray-900 overflow-hidden">
    <div class="flex h-screen">
        <div class="w-80 bg-gray-800 p-6 shadow-xl z-[1000] border-r border-gray-700 flex flex-col">
            <h1 class="text-xl font-bold text-white mb-6 flex items-center">
                <span class="w-3 h-3 bg-blue-500 rounded-full mr-2"></span>
                Panel de Control
            </h1>
            
            <div class="space-y-4 overflow-y-auto flex-1 pr-2">
                @forelse($devices as $device)
                <a href="{{ route('coche.detalle', $device['id']) }}" class="block p-4 bg-gray-700 rounded-lg shadow-sm border-l-4 {{ ($device['status'] ?? '') == 'online' ? 'border-green-500' : 'border-red-500' }} transition hover:bg-gray-600 hover:scale-[1.02] cursor-pointer group">
                    <div class="flex justify-between items-start">
                        <h2 class="text-white font-semibold group-hover:text-blue-400 transition">{{ $device['name'] ?? 'Desconocido' }}</h2>
                        <span class="text-[10px] uppercase px-2 py-0.5 rounded bg-gray-900 text-gray-400">{{ $device['status'] ?? 'offline' }}</span>
                    </div>
                    
                    @if(isset($device['posicion']))
                        <div class="mt-2 space-y-1">
                            <p class="text-blue-400 text-xs flex justify-between">
                                <span>Velocidad:</span>
                                <span class="font-mono">{{ round(($device['posicion']['speed'] ?? 0) * 1.852, 1) }} km/h</span>
                            </p>
                            <p class="text-gray-400 text-[10px]">
                                Lat: {{ round($device['posicion']['latitude'], 4) }} | Lon: {{ round($device['posicion']['longitude'], 4) }}
                            </p>
                        </div>
                    @else
                        <p class="text-gray-500 text-xs mt-2 italic">Sin datos de ubicación</p>
                    @endif
                </a>
                @empty
                <p class="text-gray-500 text-sm text-center">No se encontraron dispositivos vinculados.</p>
                @endforelse
            </div>
            
            <div class="mt-auto pt-4 border-t border-gray-700">
                <button onclick="location.reload()" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-md text-sm font-medium transition">
                    Actualizar Datos
                </button>
            </div>
        </div>

        <div id="map" class="flex-1"></div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const map = L.map('map').setView([4.5709, -74.2973], 6);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        }).addTo(map);

        const bounds = [];

        @foreach($devices as $device)
            @if(isset($device['posicion']))
                (function() {
                    const lat = {{ $device['posicion']['latitude'] }};
                    const lng = {{ $device['posicion']['longitude'] }};
                    const name = "{{ $device['name'] }}";
                    const speed = "{{ round($device['posicion']['speed'] * 1.852, 1) }} km/h";
                    const id = {{ $device['id'] }};

                    const marker = L.marker([lat, lng]).addTo(map);
                    
                    marker.bindPopup(`
                        <div class="text-gray-900 font-sans min-w-[150px]">
                            <h3 class="font-bold border-b border-gray-200 mb-2 pb-1">${name}</h3>
                            <p class="text-sm mb-3">Velocidad: <b>${speed}</b></p>
                            <a href="/coche/${id}" class="block w-full bg-blue-600 text-white text-center py-1.5 rounded text-xs font-bold hover:bg-blue-700 no-underline">
                                VER DETALLES
                            </a>
                        </div>
                    `);

                    bounds.push([lat, lng]);
                })();
            @endif
        @endforeach

        if (bounds.length > 0) {
            map.fitBounds(bounds, { padding: [50, 50], maxZoom: 15 });
        }
    </script>
</body>
</html>
// ================= INIT =================
document.addEventListener("DOMContentLoaded", function () {

    const select = document.getElementById("diferido");
    const campos = document.getElementById("campos_diferido");

    if (select && campos) {
        function toggleCampos() {
            campos.style.display = (select.value === "Si") ? "block" : "none";
        }

        toggleCampos();
        select.addEventListener("change", toggleCampos);
    }

});


// ================= MAPA =================
let map;
let markers = [];
let rutas = [];

function limpiarMapa() {
    markers.forEach(m => map.removeLayer(m));
    rutas.forEach(r => map.removeLayer(r));

    markers = [];
    rutas = [];
}

function abrirMapa() {
    document.getElementById("mapModal").style.display = "block";

    // FIX render leaflet dentro de modal
    setTimeout(() => {
        if (map) map.invalidateSize();
    }, 200);
}

function cerrarMapa() {
    document.getElementById("mapModal").style.display = "none";
}


// ================= GEO =================
async function geocodificar(direccion) {

    direccion = direccion.trim();

    if (!direccion || direccion.length < 5) {
        return null;
    }

    let query = encodeURIComponent(direccion + ', Buenos Aires, Argentina');

    let url = `https://nominatim.openstreetmap.org/search?format=json&q=${query}&limit=1&countrycodes=ar`;

    try {
        let res = await fetch(url, {
            headers: {
                'Accept-Language': 'es'
            }
        });

        let data = await res.json();

        console.log("Geocoding:", direccion, data);

        if (!data || data.length === 0) return null;

        return data[0];

    } catch (error) {
        console.error("Error geocoding:", error);
        return null;
    }
}


// ================= VER UBICACION =================
async function verMapa(direccion) {

    let geo = await geocodificar(direccion);

    if (!geo) {
        alert("Dirección no encontrada");
        return;
    }

    abrirMapa();

    let lat = geo.lat;
    let lon = geo.lon;

    if (!map) {
        map = L.map('map').setView([lat, lon], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        }).addTo(map);

    } else {
        map.setView([lat, lon], 15);
    }

    limpiarMapa();

    let m = L.marker([lat, lon])
        .addTo(map)
        .bindPopup(direccion)
        .openPopup();

    markers.push(m);
}


// ================= VER RECORRIDO =================
async function verRecorrido() {
    let o = document.getElementById("dir_origen").value;
    let d = document.getElementById("dir_destino").value;

    if (!o || !d) return alert("Complete origen y destino");

    let geoO = await geocodificar(o);
    let geoD = await geocodificar(d);

    if (!geoO || !geoD) return alert("Direcciones inválidas");

    abrirMapa();
    initMap(geoO.lat, geoO.lon);
    limpiarMapa();

    let m1 = L.marker([geoO.lat, geoO.lon]).addTo(map).bindPopup("Origen");
    let m2 = L.marker([geoD.lat, geoD.lon]).addTo(map).bindPopup("Destino");

    markers.push(m1, m2);

    let url = `https://router.project-osrm.org/route/v1/driving/${geoO.lon},${geoO.lat};${geoD.lon},${geoD.lat}?overview=full&geometries=geojson`;

    let res = await fetch(url);
    let data = await res.json();

    let route = data.routes[0];

    let layer = L.geoJSON(route.geometry, {
        style: { color: 'blue', weight: 5 }
    }).addTo(map);

    rutas.push(layer);
    map.fitBounds(layer.getBounds());

    // DISTANCIA (km)
    let km = (route.distance / 1000).toFixed(2);
    
    // Se comenta el cálculo de tarifa para que no afecte el flujo
    // let importe = calcularTarifa(km); 

    // Alerta modificada: sin la línea del importe
    alert("Distancia: " + km + " km\nTiempo: " + Math.round(route.duration / 60) + " min");
}
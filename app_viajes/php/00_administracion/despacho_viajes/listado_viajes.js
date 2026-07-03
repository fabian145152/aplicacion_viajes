// ================= MAPA =================
let map;
let markers = [];
let rutas = [];

function initMap(lat = -34.6037, lon = -58.3816) {
    if (!map) {
        map = L.map('map').setView([lat, lon], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        }).addTo(map);
    }
}

function limpiarMapa() {
    markers.forEach(m => map.removeLayer(m));
    rutas.forEach(r => map.removeLayer(r));
    markers = [];
    rutas = [];
}

function abrirMapa() {
    document.getElementById("mapModal").style.display = "block";
    setTimeout(() => map.invalidateSize(), 200);
}

function cerrarMapa() {
    document.getElementById("mapModal").style.display = "none";
}

async function geocodificar(direccion) {
    let url = `https://nominatim.openstreetmap.org/search?format=json&q=${direccion+', Buenos Aires, Argentina'}`;
    let res = await fetch(url);
    let data = await res.json();
    return data[0] || null;
}

async function verMapa(inputId) {
    let direccion = document.getElementById(inputId).value;
    if (!direccion) return alert("Ingrese dirección");

    let geo = await geocodificar(direccion);
    if (!geo) return alert("No encontrada");

    abrirMapa();
    initMap(geo.lat, geo.lon);
    limpiarMapa();

    let m = L.marker([geo.lat, geo.lon]).addTo(map)
        .bindPopup(direccion).openPopup();

    markers.push(m);

    // GUARDAR LAT LNG
    document.getElementById(inputId + "_lat").value = geo.lat;
    document.getElementById(inputId + "_lng").value = geo.lon;
}

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
    alert("Distancia: " + km + " km\nTiempo: " + Math.round(route.duration / 60) + " min");
}

async function autocomplete(input) {
    let query = input.value;
    if (query.length < 3) return;

    let url = `https://nominatim.openstreetmap.org/search?format=json&q=${query}`;
    let res = await fetch(url);
    let data = await res.json();

    let list = document.getElementById(input.id + "_list");
    list.innerHTML = "";

    data.slice(0,5).forEach(item => {
        let option = document.createElement("div");
        option.innerText = item.display_name;

        option.onclick = () => {
            input.value = item.display_name;
            list.innerHTML = "";

            // guardar coords directo
            document.getElementById(input.id + "_lat").value = item.lat;
            document.getElementById(input.id + "_lng").value = item.lon;
        };

        list.appendChild(option);
    });
}

//  AGREGADO 
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
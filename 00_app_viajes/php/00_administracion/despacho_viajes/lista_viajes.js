// ================= FUNCIONES PRINCIPALES =================

function evaluarAccion(selectElement, viajeId) {
    if (selectElement.value === 'asignar_movil') {
        document.getElementById('modal-id-viaje').textContent = viajeId;
        document.getElementById('input-modal-viaje-id').value = viajeId;
        document.getElementById('modalAsignar').style.display = 'block';
        cargarVehiculos();
    } else if (selectElement.value === 'desasignar_movil') {
        if (confirm("¿Desea desasignar el móvil de este viaje?")) {
            window.location = "lista_viajes.php?desasignar=" + viajeId;
        } else {
            restablecerSelects();
        }
    } else if (selectElement.value === 'cancelar_viaje') {
        if (confirm("¿Está seguro de cancelar este viaje?")) {
            window.location = "lista_viajes.php?borrar=" + viajeId;
        } else {
            restablecerSelects();
        }
    }
}

function cerrarModalAsignar() {
    document.getElementById('modalAsignar').style.display = 'none';
    restablecerSelects();
}

function cerrarModalCancelar() {
    document.getElementById('modalCancelar').style.display = 'none';
    document.getElementById('obs_viaje').value = '';
    restablecerSelects();
}

function restablecerSelects() {
    const dropdowns = document.querySelectorAll('select[name="acciones_viaje"]');
    dropdowns.forEach(d => d.selectedIndex = 0);
}

// ================= CARGAR VEHÍCULOS =================

function cargarVehiculos() {
    fetch('obtener_vehiculos.php')
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('movil_select');
            select.innerHTML = '<option value="">-- Seleccionar Móvil --</option>';
            
            if (data.error) {
                select.innerHTML = '<option value="">Error: ' + data.error + '</option>';
                return;
            }
            
            if (data.length === 0) {
                select.innerHTML = '<option value="">No hay móviles activos</option>';
                return;
            }
            
            data.forEach(vehiculo => {
                const option = document.createElement('option');
                option.value = vehiculo.movil;
                // Mostrar con ícono verde de activo
                option.textContent = `🟢 Móvil ${vehiculo.movil} - ${vehiculo.descripcion}`;
                select.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Error cargando vehículos:', error);
            document.getElementById('movil_select').innerHTML = '<option value="">Error al cargar móviles</option>';
        });
}

// ================= CERRAR MODAL AL HACER CLIC FUERA =================

window.addEventListener('click', function(event) {
    const modalAsignar = document.getElementById('modalAsignar');
    const modalCancelar = document.getElementById('modalCancelar');
    if (event.target === modalAsignar) cerrarModalAsignar();
    if (event.target === modalCancelar) cerrarModalCancelar();
});

// ================= RELOJ DIGITAL =================

function iniciarReloj() {
    const reloj = document.getElementById('reloj-digital');
    if (!reloj) return;

    function actualizar() {
        const ahora = new Date();
        const horas = String(ahora.getHours()).padStart(2, '0');
        const minutos = String(ahora.getMinutes()).padStart(2, '0');
        const segundos = String(ahora.getSeconds()).padStart(2, '0');
        reloj.textContent = `${horas}:${minutos}:${segundos}`;
    }
    actualizar();
    setInterval(actualizar, 1000);
}

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
    if (!direccion) return null;
    let url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(direccion + ', Buenos Aires, Argentina')}`;
    try {
        let res = await fetch(url);
        let data = await res.json();
        return data[0] || null;
    } catch (error) {
        console.error('Error geocodificando:', error);
        return null;
    }
}

async function verMapa(inputId) {
    let direccion = document.getElementById(inputId).value;
    if (!direccion) return alert("Ingrese dirección");

    let geo = await geocodificar(direccion);
    if (!geo) return alert("Dirección no encontrada");

    abrirMapa();
    initMap(geo.lat, geo.lon);
    limpiarMapa();

    let m = L.marker([geo.lat, geo.lon]).addTo(map)
        .bindPopup(direccion).openPopup();

    markers.push(m);

    // GUARDAR LAT LNG
    let latInput = document.getElementById(inputId + "_lat");
    let lngInput = document.getElementById(inputId + "_lng");
    if (latInput) latInput.value = geo.lat;
    if (lngInput) lngInput.value = geo.lon;
}

async function verRecorrido(origen, destino) {
    if (!origen || !destino) return alert("Complete origen y destino");

    let geoO = await geocodificar(origen);
    let geoD = await geocodificar(destino);

    if (!geoO || !geoD) return alert("Direcciones inválidas");

    abrirMapa();
    initMap(geoO.lat, geoO.lon);
    limpiarMapa();

    let m1 = L.marker([geoO.lat, geoO.lon]).addTo(map).bindPopup("Origen");
    let m2 = L.marker([geoD.lat, geoD.lon]).addTo(map).bindPopup("Destino");

    markers.push(m1, m2);

    let url = `https://router.project-osrm.org/route/v1/driving/${geoO.lon},${geoO.lat};${geoD.lon},${geoD.lat}?overview=full&geometries=geojson`;

    try {
        let res = await fetch(url);
        let data = await res.json();

        if (!data.routes || !data.routes.length) {
            alert("No se encontró ruta");
            return;
        }

        let route = data.routes[0];

        let layer = L.geoJSON(route.geometry, {
            style: { color: 'blue', weight: 5 }
        }).addTo(map);

        rutas.push(layer);
        map.fitBounds(layer.getBounds());

        // DISTANCIA (km)
        let km = (route.distance / 1000).toFixed(2);
        let tiempo = Math.round(route.duration / 60);
        alert("Distancia: " + km + " km\nTiempo aproximado: " + tiempo + " min");
    } catch (error) {
        console.error('Error calculando ruta:', error);
        alert("Error al calcular la ruta");
    }
}

async function autocomplete(input) {
    let query = input.value;
    if (query.length < 3) {
        let list = document.getElementById(input.id + "_list");
        if (list) list.innerHTML = "";
        return;
    }

    let url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`;

    try {
        let res = await fetch(url);
        let data = await res.json();

        let list = document.getElementById(input.id + "_list");
        if (!list) return;
        list.innerHTML = "";

        data.slice(0, 5).forEach(item => {
            let option = document.createElement("div");
            option.innerText = item.display_name;
            option.style.cssText = "padding:5px 10px; cursor:pointer; border-bottom:1px solid #eee; background:white;";
            option.onmouseover = function() { this.style.backgroundColor = "#e9ecef"; };
            option.onmouseout = function() { this.style.backgroundColor = "white"; };

            option.onclick = () => {
                input.value = item.display_name;
                list.innerHTML = "";

                // guardar coords
                let latInput = document.getElementById(input.id + "_lat");
                let lngInput = document.getElementById(input.id + "_lng");
                if (latInput) latInput.value = item.lat;
                if (lngInput) lngInput.value = item.lon;
            };

            list.appendChild(option);
        });
    } catch (error) {
        console.error('Error en autocomplete:', error);
    }
}

// ================= AVISO DE VIAJES VENCIDOS =================

function avisarNuevosVencidos(idsVencidosActuales) {
    const clave = 'viajes_vencidos_avisados';
    const INTERVALO_REAVISO_MS = 5 * 60 * 1000;
    const ahora = Date.now();
    let avisados = {};

    try {
        avisados = JSON.parse(localStorage.getItem(clave)) || {};
    } catch (e) {
        avisados = {};
    }

    const paraAvisar = [];
    const nuevosAvisados = {};

    idsVencidosActuales.forEach(id => {
        const ultimoAviso = avisados[id];
        if (!ultimoAviso || (ahora - ultimoAviso) >= INTERVALO_REAVISO_MS) {
            paraAvisar.push(id);
            nuevosAvisados[id] = ahora;
        } else {
            nuevosAvisados[id] = ultimoAviso;
        }
    });

    if (paraAvisar.length === 1) {
        alert('⚠️ El viaje N° ' + paraAvisar[0] + ' superó el tiempo configurado desde su hora programada y sigue sin asignar/completar.');
    } else if (paraAvisar.length > 1) {
        alert('⚠️ Los siguientes viajes superaron el tiempo configurado desde su hora programada y siguen sin asignar/completar:\n' + paraAvisar.join(', '));
    }

    localStorage.setItem(clave, JSON.stringify(nuevosAvisados));
}

// ================= INICIALIZACIÓN =================

document.addEventListener('DOMContentLoaded', function() {
    // Iniciar reloj
    iniciarReloj();

    // Manejar campos de viaje diferido
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
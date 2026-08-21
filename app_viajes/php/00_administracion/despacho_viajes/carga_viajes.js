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

    // MOSTRAR DATOS GUARDADOS SI EXISTEN
    mostrarDatosGuardados();
});

// ================= MOSTRAR DATOS GUARDADOS =================
function mostrarDatosGuardados() {
    const distanciaInput = document.getElementById('distancia_recorrido');
    const tiempoInput = document.getElementById('tiempo_recorrido');
    const resultadoDistancia = document.getElementById('resultado_distancia');
    const resultadoTiempo = document.getElementById('resultado_tiempo');

    if (distanciaInput && distanciaInput.value && resultadoDistancia) {
        resultadoDistancia.textContent = distanciaInput.value + ' km';
    }
    if (tiempoInput && tiempoInput.value && resultadoTiempo) {
        resultadoTiempo.textContent = tiempoInput.value + ' min';
    }
}

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
    setTimeout(() => {
        if (map) map.invalidateSize();
    }, 300);
}

function cerrarMapa() {
    document.getElementById("mapModal").style.display = "none";
}

// ================= GEOCODIFICAR =================
async function geocodificar(direccion) {
    if (!direccion || direccion.trim().length < 3) {
        console.warn("Dirección muy corta o vacía:", direccion);
        return null;
    }

    direccion = direccion.trim();

    const queries = [
        direccion + ', Buenos Aires, Argentina',
        direccion + ', Argentina',
        direccion
    ];

    for (let query of queries) {
        try {
            const encodedQuery = encodeURIComponent(query);
            const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodedQuery}&limit=1&countrycodes=ar&accept-language=es`;

            console.log("🔍 Geocodificando:", query);

            const res = await fetch(url, {
                headers: {
                    'Accept-Language': 'es',
                    'User-Agent': 'AppViajes/1.0'
                }
            });

            if (!res.ok) continue;

            const data = await res.json();
            console.log("📥 Respuesta:", data);

            if (data && data.length > 0) {
                console.log("✅ Encontrado:", data[0].display_name);
                return data[0];
            }
        } catch (error) {
            console.warn("Error geocodificando:", query, error);
        }
    }

    console.error("❌ No se encontró la dirección:", direccion);
    return null;
}

// ================= VER UBICACION EN MAPA =================
async function verMapa(inputId) {
    const input = document.getElementById(inputId);
    if (!input) {
        alert("Campo no encontrado");
        return;
    }

    const direccion = input.value.trim();
    if (!direccion) {
        alert("Ingresa una dirección primero");
        return;
    }

    const geo = await geocodificar(direccion);
    if (!geo) {
        alert("❌ No se encontró la dirección:\n" + direccion);
        return;
    }

    abrirMapa();

    const lat = parseFloat(geo.lat);
    const lon = parseFloat(geo.lon);

    if (!map) {
        map = L.map('map').setView([lat, lon], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        }).addTo(map);
    } else {
        map.setView([lat, lon], 15);
    }

    limpiarMapa();

    const m = L.marker([lat, lon])
        .addTo(map)
        .bindPopup(direccion)
        .openPopup();

    markers.push(m);

    const latInput = document.getElementById(inputId + "_lat");
    const lngInput = document.getElementById(inputId + "_lng");
    if (latInput) latInput.value = lat;
    if (lngInput) lngInput.value = lon;
}

// ================= INICIALIZAR MAPA =================
function initMap(lat, lon) {
    if (!map) {
        map = L.map('map').setView([lat, lon], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        }).addTo(map);
    } else {
        map.setView([lat, lon], 13);
    }
}

// ================= 🔴 VER RECORRIDO + GUARDAR AUTOMÁTICAMENTE SIN ID DE VIAJE =================
async function verRecorrido() {
    const o = document.getElementById("dir_origen").value.trim();
    const d = document.getElementById("dir_destino").value.trim();

    if (!o || !d) {
        alert("❌ Complete origen y destino");
        return;
    }

    const btnRecorrido = document.querySelector('.btn-recorrido');
    const textoOriginal = btnRecorrido ? btnRecorrido.textContent : '';
    if (btnRecorrido) {
        btnRecorrido.textContent = '⏳ Calculando...';
        btnRecorrido.disabled = true;
    }

    try {
        const geoO = await geocodificar(o);
        const geoD = await geocodificar(d);

        if (!geoO) {
            alert("❌ No se encontró el origen:\n" + o);
            if (btnRecorrido) {
                btnRecorrido.textContent = textoOriginal || '➡️ RECORRIDO';
                btnRecorrido.disabled = false;
            }
            return;
        }

        if (!geoD) {
            alert("❌ No se encontró el destino:\n" + d);
            if (btnRecorrido) {
                btnRecorrido.textContent = textoOriginal || '➡️ RECORRIDO';
                btnRecorrido.disabled = false;
            }
            return;
        }

        const latO = parseFloat(geoO.lat);
        const lonO = parseFloat(geoO.lon);
        const latD = parseFloat(geoD.lat);
        const lonD = parseFloat(geoD.lon);

        abrirMapa();
        initMap(latO, lonO);
        limpiarMapa();

        const m1 = L.marker([latO, lonO]).addTo(map).bindPopup("Origen: " + o);
        const m2 = L.marker([latD, lonD]).addTo(map).bindPopup("Destino: " + d);
        markers.push(m1, m2);

        // Calcular ruta con OSRM
        const url = `https://router.project-osrm.org/route/v1/driving/${lonO},${latO};${lonD},${latD}?overview=full&geometries=geojson`;

        console.log("📡 Calculando ruta:", url);

        const res = await fetch(url);
        const data = await res.json();

        if (!data.routes || data.routes.length === 0) {
            alert("❌ No se encontró ruta entre las direcciones");
            if (btnRecorrido) {
                btnRecorrido.textContent = textoOriginal || '➡️ RECORRIDO';
                btnRecorrido.disabled = false;
            }
            return;
        }

        const route = data.routes[0];

        // Dibujar la ruta en el mapa
        const layer = L.geoJSON(route.geometry, {
            style: { color: '#0d6efd', weight: 5, opacity: 0.8 }
        }).addTo(map);

        rutas.push(layer);
        map.fitBounds(layer.getBounds());

        // CALCULAR DISTANCIA Y TIEMPO
        const km = (route.distance / 1000).toFixed(2);
        const minutos = Math.round(route.duration / 60);

        // MOSTRAR EN PANTALLA
        document.getElementById('resultado_distancia').textContent = km + ' km';
        document.getElementById('resultado_tiempo').textContent = minutos + ' min';
        document.getElementById('distancia_recorrido').value = km;
        document.getElementById('tiempo_recorrido').value = minutos;

        // 🔴 GUARDAR RECORRIDO AUTOMÁTICAMENTE (SIN ID DE VIAJE)
        await guardarRecorridoSinViaje(km, minutos, o, d, latO, lonO, latD, lonD);

        // Mostrar mensaje de éxito en el recuadro verde
        document.getElementById('recorridoGuardado').style.display = 'block';
        document.getElementById('recorridoDistancia').textContent = km + ' km';
        document.getElementById('recorridoTiempo').textContent = minutos + ' min';
        document.getElementById('recorridoMovil').textContent = 'Recorrido guardado (sin viaje)';

        // Deshabilitar botón "Guardar Recorrido" (ya no es necesario)
        const btnGuardar = document.getElementById('btnGuardarRecorrido');
        if (btnGuardar) {
            btnGuardar.disabled = true;
            btnGuardar.textContent = '✅ Recorrido guardado';
            btnGuardar.classList.add('guardado');
        }

        // Mostrar mensaje sin alert
        mostrarMensaje('✅ Recorrido guardado correctamente', 'exito');

    } catch (error) {
        console.error('Error calculando ruta:', error);
        alert("❌ Error al calcular la ruta. Verifica tu conexión a internet.");
    } finally {
        if (btnRecorrido) {
            btnRecorrido.textContent = textoOriginal || '➡️ RECORRIDO';
            btnRecorrido.disabled = false;
        }
    }
}

// ================= 🔴 GUARDAR RECORRIDO SIN ID DE VIAJE =================
async function guardarRecorridoSinViaje(km, minutos, origen, destino, latO, lonO, latD, lonD) {
    const data = {
        movil: 'SIN_VIAJE',
        origen: origen,
        destino: destino,
        origen_lat: latO || null,
        origen_lng: lonO || null,
        destino_lat: latD || null,
        destino_lng: lonD || null,
        distancia: km,
        tiempo: minutos
    };

    console.log('📤 Guardando recorrido (sin viaje):', data);

    try {
        const response = await fetch('guardar_recorrido_sin_viaje.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const resultado = await response.json();
        console.log('📥 Respuesta guardar recorrido:', resultado);

        if (resultado.res === 'OK') {
            console.log('✅ Recorrido guardado correctamente');
            // Guardar el ID del recorrido en un campo oculto para asociarlo después
            if (resultado.id_recorrido) {
                document.getElementById('id_recorrido_guardado').value = resultado.id_recorrido;
            }
            return true;
        } else {
            console.error('❌ Error al guardar recorrido:', resultado.msg);
            mostrarMensaje('⚠️ ' + resultado.msg, 'error');
            return false;
        }
    } catch (error) {
        console.error('❌ Error en guardarRecorridoSinViaje:', error);
        mostrarMensaje('⚠️ Error al guardar el recorrido', 'error');
        return false;
    }
}

// ================= MOSTRAR MENSAJE =================
function mostrarMensaje(texto, tipo) {
    const mensaje = document.getElementById('mensajeRecorrido');
    if (!mensaje) return;
    mensaje.textContent = texto;
    mensaje.className = 'mensaje-recorrido ' + tipo;
    setTimeout(() => {
        mensaje.className = 'mensaje-recorrido';
    }, 5000);
}

// ================= AUTOCOMPLETE =================
async function autocomplete(input) {
    let query = input.value.trim();
    const list = document.getElementById(input.id + "_list");
    
    if (query.length < 3) {
        if (list) {
            list.innerHTML = "";
            list.classList.remove('active');
        }
        return;
    }

    const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&countrycodes=ar&limit=5&accept-language=es`;

    try {
        const res = await fetch(url, {
            headers: {
                'Accept-Language': 'es',
                'User-Agent': 'AppViajes/1.0'
            }
        });
        
        const data = await res.json();

        if (!list) return;
        list.innerHTML = "";
        list.classList.add('active');

        if (data.length === 0) {
            const option = document.createElement("div");
            option.innerText = "No se encontraron resultados";
            option.style.cssText = "padding:8px 10px; color:#999; font-style:italic;";
            list.appendChild(option);
            return;
        }

        data.slice(0, 5).forEach(item => {
            const option = document.createElement("div");
            option.innerText = item.display_name;
            option.style.cssText = "padding:8px 12px; cursor:pointer; border-bottom:1px solid #eee; background:white; font-size:13px;";
            option.onmouseover = function() { this.style.backgroundColor = "#e9ecef"; };
            option.onmouseout = function() { this.style.backgroundColor = "white"; };

            option.onclick = function(e) {
                e.preventDefault();
                input.value = item.display_name;
                list.innerHTML = "";
                list.classList.remove('active');

                const latInput = document.getElementById(input.id + "_lat");
                const lngInput = document.getElementById(input.id + "_lng");
                if (latInput) latInput.value = item.lat;
                if (lngInput) lngInput.value = item.lon;
            };

            list.appendChild(option);
        });
    } catch (error) {
        console.error('Error en autocomplete:', error);
        if (list) {
            list.innerHTML = '<div style="padding:8px 10px; color:#dc3545;">Error al cargar sugerencias</div>';
        }
    }
}

// ================= CERRAR AUTOCOMPLETE AL HACER CLICK FUERA =================
document.addEventListener('click', function(e) {
    document.querySelectorAll('.autocomplete-box').forEach(box => {
        if (!box.contains(e.target)) {
            box.classList.remove('active');
        }
    });
});

// ================= VALIDAR FORMULARIO (SIN EL ALERT DE DESTINO) =================
function validarFormulario() {
    const nombre = document.getElementById('nombre_pasaj');
    const origen = document.getElementById('dir_origen');
    const destino = document.getElementById('dir_destino');
    const categoria = document.getElementById('categoria_movil_oculto');

    if (!nombre.value.trim()) {
        alert("❌ El campo 'Nombre del Pasajero' es obligatorio");
        nombre.focus();
        return false;
    }

    if (!origen.value.trim()) {
        alert("❌ El campo 'Origen' es obligatorio");
        origen.focus();
        return false;
    }

    // 🔴 ELIMINADO EL ALERT DEL DESTINO PORQUE ES OPCIONAL

    if (!categoria.value) {
        alert("❌ Selecciona una categoría de móvil");
        return false;
    }

    return true;
}
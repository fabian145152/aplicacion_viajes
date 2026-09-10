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

// ================= CONTAR PARADAS INTERMEDIAS =================
function contarParadas(input) {
    const info = document.getElementById('info_paradas');
    if (!info) return;

    const valor = input.value.trim();
    if (!valor) {
        info.textContent = '';
        return;
    }

    const paradas = valor.split('|').map(p => p.trim()).filter(p => p.length > 0);
    if (paradas.length === 0) {
        info.textContent = '';
    } else if (paradas.length === 1) {
        info.textContent = `📍 1 parada intermedia`;
    } else {
        info.textContent = `📍 ${paradas.length} paradas intermedias`;
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

// ================= VER RECORRIDO COMPLETO (ORIGEN + PARADAS + DESTINO) =================
async function verRecorrido() {
    const origen = document.getElementById("dir_origen").value.trim();
    const destino = document.getElementById("dir_destino").value.trim();
    let puntosPaso = document.getElementById("puntos_paso").value.trim();

    if (!origen || !destino) {
        alert("❌ Complete origen y destino");
        return;
    }

    // Obtener puntos de paso
    let pasos = [];
    if (puntosPaso) {
        // Si no tiene pipe, es una sola dirección
        if (!puntosPaso.includes('|')) {
            pasos = [puntosPaso];
        } else {
            pasos = puntosPaso.split('|').map(p => p.trim()).filter(p => p.length > 0);
        }
    }

    // Construir array de puntos: origen + puntos de paso + destino
    let puntos = [origen];
    puntos = puntos.concat(pasos);
    puntos.push(destino);

    console.log("📍 Puntos a geocodificar:", puntos);

    if (puntos.length < 2) {
        alert("❌ Necesita al menos origen y destino");
        return;
    }

    if (puntos.length > 25) {
        alert(`⚠️ Demasiados puntos (${puntos.length}). El máximo permitido es 25.`);
        return;
    }

    const btnRecorrido = document.querySelector('.btn-recorrido');
    const textoOriginal = btnRecorrido ? btnRecorrido.textContent : '';
    if (btnRecorrido) {
        btnRecorrido.textContent = '⏳ Calculando...';
        btnRecorrido.disabled = true;
    }

    try {
        // Geocodificar todos los puntos
        const puntosGeocodificados = [];
        let errores = [];

        for (const direccion of puntos) {
            const geo = await geocodificar(direccion);
            if (!geo) {
                errores.push(direccion);
                continue;
            }
            puntosGeocodificados.push({
                direccion: direccion,
                lat: parseFloat(geo.lat),
                lon: parseFloat(geo.lon)
            });
        }

        if (errores.length > 0) {
            alert("❌ No se encontraron las siguientes direcciones:\n" + errores.join('\n'));
            if (btnRecorrido) {
                btnRecorrido.textContent = textoOriginal || '➡️ RECORRIDO';
                btnRecorrido.disabled = false;
            }
            return;
        }

        if (puntosGeocodificados.length < 2) {
            alert("❌ No se pudo geocodificar origen y destino");
            if (btnRecorrido) {
                btnRecorrido.textContent = textoOriginal || '➡️ RECORRIDO';
                btnRecorrido.disabled = false;
            }
            return;
        }

        abrirMapa();
        const primerPunto = puntosGeocodificados[0];
        initMap(primerPunto.lat, primerPunto.lon);
        limpiarMapa();

        // Colores para los marcadores
        const colores = ['#0d6efd', '#fd7e14', '#28a745', '#dc3545', '#6f42c1', '#20c997', '#ffc107', '#17a2b8', '#e83e8c', '#6c757d'];
        let latLngs = [];
        let puntosInfo = [];

        const markerGroup = L.layerGroup().addTo(map);

        for (let i = 0; i < puntosGeocodificados.length; i++) {
            const p = puntosGeocodificados[i];
            const color = colores[i % colores.length];

            let etiqueta = '';
            if (i === 0) {
                etiqueta = 'ORIGEN';
            } else if (i === puntosGeocodificados.length - 1) {
                etiqueta = 'DESTINO';
            } else {
                etiqueta = `PARADA ${i}`;
            }

            const icono = L.divIcon({
                html: `<div style="background:${color};color:white;border-radius:50%;width:32px;height:32px;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:13px;border:3px solid white;box-shadow:0 2px 8px rgba(0,0,0,0.3);">${i + 1}</div>`,
                className: '',
                iconSize: [32, 32],
                iconAnchor: [16, 16]
            });

            const marker = L.marker([p.lat, p.lon], { icon: icono })
                .addTo(markerGroup)
                .bindPopup(`<strong>${etiqueta}</strong><br><span style="font-size:12px;">${p.direccion}</span>`);

            markers.push(marker);
            latLngs.push([p.lat, p.lon]);
            puntosInfo.push(`${i + 1}. ${etiqueta}: ${p.direccion}`);
        }

        // Calcular ruta entre puntos consecutivos
        let distanciaTotal = 0;
        let tiempoTotal = 0;
        let tramos = [];

        for (let i = 0; i < puntosGeocodificados.length - 1; i++) {
            const inicio = puntosGeocodificados[i];
            const fin = puntosGeocodificados[i + 1];

            const url = `https://router.project-osrm.org/route/v1/driving/${inicio.lon},${inicio.lat};${fin.lon},${fin.lat}?overview=full&geometries=geojson`;

            try {
                const res = await fetch(url);
                const data = await res.json();

                if (data.routes && data.routes.length > 0) {
                    const route = data.routes[0];
                    const km = route.distance / 1000;
                    const min = route.duration / 60;
                    distanciaTotal += km;
                    tiempoTotal += min;

                    const colorSegmento = colores[i % colores.length];

                    const layer = L.geoJSON(route.geometry, {
                        style: { color: colorSegmento, weight: 5, opacity: 0.8 }
                    }).addTo(map);
                    rutas.push(layer);

                    tramos.push({
                        desde: i,
                        hasta: i + 1,
                        km: km,
                        min: min
                    });
                } else {
                    console.warn(`⚠️ No se encontró ruta entre punto ${i} y ${i + 1}`);
                }
            } catch (error) {
                console.error(`❌ Error calculando ruta entre punto ${i} y ${i + 1}:`, error);
            }
        }

        // Ajustar mapa para mostrar todos los puntos
        if (latLngs.length > 0) {
            const bounds = L.latLngBounds(latLngs);
            map.fitBounds(bounds, { padding: [60, 60] });
        }

        // Mostrar resultados en la interfaz
        document.getElementById('resultado_distancia').textContent = distanciaTotal.toFixed(2) + ' km';
        document.getElementById('resultado_tiempo').textContent = Math.round(tiempoTotal) + ' min';
        document.getElementById('distancia_recorrido').value = distanciaTotal.toFixed(2);
        document.getElementById('tiempo_recorrido').value = Math.round(tiempoTotal);

        // Mostrar recuadro verde de recorrido guardado
        document.getElementById('recorridoGuardado').style.display = 'block';
        document.getElementById('recorridoDistancia').textContent = distanciaTotal.toFixed(2) + ' km';
        document.getElementById('recorridoTiempo').textContent = Math.round(tiempoTotal) + ' min';
        document.getElementById('recorridoMovil').textContent = 'Recorrido con ' + (puntosGeocodificados.length - 2) + ' paradas';

        // Mostrar detalle en alert
        let mensaje = '📍 RECORRIDO COMPLETO\n';
        mensaje += '═'.repeat(40) + '\n\n';
        mensaje += puntosInfo.join('\n');
        mensaje += '\n\n📏 Distancia total: ' + distanciaTotal.toFixed(2) + ' km';
        mensaje += '\n⏱️ Tiempo total: ' + Math.round(tiempoTotal) + ' min';

        if (tramos.length > 0) {
            mensaje += '\n\n📊 Detalle por tramo:\n';
            tramos.forEach((t, idx) => {
                mensaje += `  Tramo ${idx + 1}: ${t.km.toFixed(2)} km (${Math.round(t.min)} min)\n`;
            });
        }

        alert(mensaje);

        // Deshabilitar botón "Guardar Recorrido"
        const btnGuardar = document.getElementById('btnGuardarRecorrido');
        if (btnGuardar) {
            btnGuardar.disabled = true;
            btnGuardar.textContent = '✅ Recorrido guardado';
            btnGuardar.classList.add('guardado');
        }

        mostrarMensaje(`✅ Recorrido con ${puntosGeocodificados.length - 2} parada(s) calculado`, 'exito');

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

// ================= GUARDAR RECORRIDO SIN ID DE VIAJE =================
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
            option.onmouseover = function () { this.style.backgroundColor = "#e9ecef"; };
            option.onmouseout = function () { this.style.backgroundColor = "white"; };

            option.onclick = function (e) {
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
document.addEventListener('click', function (e) {
    document.querySelectorAll('.autocomplete-box').forEach(box => {
        if (!box.contains(e.target)) {
            box.classList.remove('active');
        }
    });
});

// ================= VALIDAR FORMULARIO =================
function validarFormulario() {
    const nombre = document.getElementById('nombre_pasaj');
    const origen = document.getElementById('dir_origen');
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

    if (!categoria.value) {
        alert("❌ Selecciona una categoría de móvil");
        return false;
    }

    return true;
}
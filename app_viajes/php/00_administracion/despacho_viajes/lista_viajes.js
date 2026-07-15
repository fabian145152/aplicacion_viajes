       function evaluarAccion(selectElement, viajeId) {

        if (selectElement.value === 'asignar_movil') {

        document.getElementById('modal-id-viaje').textContent = viajeId;
        document.getElementById('input-modal-viaje-id').value = viajeId;
        document.getElementById('modalAsignar').style.display = 'block';

    } else if (selectElement.value === 'desasignar_movil') {

        if (confirm("¿Desea desasignar el móvil de este viaje?")) {
            window.location = "lista_viajes.php?desasignar=" + viajeId;
        } else {
            restablecerSelects();
        }

    } else if (selectElement.value === 'cancelar_viaje') {

        document.getElementById('modal-cancelar-id-viaje').textContent = viajeId;
        document.getElementById('input-modal-cancelar-viaje-id').value = viajeId;
        document.getElementById('modalCancelar').style.display = 'block';

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

        function confirmarCancelacion() {
            return confirm("¿Estás completamente seguro de que deseas cancelar este viaje?");
        }

        window.addEventListener('click', function(event) {
            const modalAsignar = document.getElementById('modalAsignar');
            const modalCancelar = document.getElementById('modalCancelar');
            if (event.target === modalAsignar) cerrarModalAsignar();
            if (event.target === modalCancelar) cerrarModalCancelar();
        });

        // LÓGICA DEL RELOJ DIGITAL
        function iniciarReloj() {
            const reloj = document.getElementById('reloj-digital');

            function actualizar() {
                const ahora = new Date();
                const horas = String(ahora.getHours()).padStart(2, '0');
                const minutos = String(ahora.getMinutes()).padStart(2, '0');
                const segundos = String(ahora.getSeconds()).padStart(2, '0');
                if (reloj) {
                    reloj.textContent = `${horas}:${minutos}:${segundos}`;
                }
            }
            actualizar();
            setInterval(actualizar, 1000);
        }
        document.addEventListener('DOMContentLoaded', iniciarReloj);

        // AVISO DE VIAJES VENCIDOS (pasó fecha/hora programada + minutos de tolerancia, y sigue sin completar/cancelar)
        // Se repite cada 5 minutos mientras el viaje siga vencido (la página ya se recarga sola cada 30s por el meta refresh)
        function avisarNuevosVencidos(idsVencidosActuales) {
            const clave = 'viajes_vencidos_avisados';
            const INTERVALO_REAVISO_MS = 5 * 60 * 1000; // 5 minutos
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
                    nuevosAvisados[id] = ahora; // arranca de nuevo la cuenta de 5 minutos
                } else {
                    nuevosAvisados[id] = ultimoAviso; // todavía no pasaron los 5 min, se mantiene el timestamp original
                }
            });
            // Los que ya no están vencidos (se asignaron/completaron/cancelaron) se descartan solos, no se copian a nuevosAvisados

            if (paraAvisar.length === 1) {
                alert('El viaje N° ' + paraAvisar[0] + ' superó el tiempo configurado desde su hora programada y sigue sin asignar/completar.');
            } else if (paraAvisar.length > 1) {
                alert('Los siguientes viajes superaron el tiempo configurado desde su hora programada y siguen sin asignar/completar:\n' + paraAvisar.join(', '));
            }

            localStorage.setItem(clave, JSON.stringify(nuevosAvisados));
        }

        
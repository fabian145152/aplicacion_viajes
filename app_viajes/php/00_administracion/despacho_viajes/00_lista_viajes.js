  function evaluarAccion(selectElement, viajeId) {
            if (selectElement.value === 'asignar_movil') {
                document.getElementById('modal-id-viaje').textContent = viajeId;
                document.getElementById('input-modal-viaje-id').value = viajeId;
                document.getElementById('modalAsignar').style.display = 'block';
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

        
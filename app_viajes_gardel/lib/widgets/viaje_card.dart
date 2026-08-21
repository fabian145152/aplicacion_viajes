import 'package:flutter/material.dart';

class ViajeCard extends StatelessWidget {
  final dynamic viaje;
  final VoidCallback onAceptar;

  const ViajeCard({
    super.key,
    required this.viaje,
    required this.onAceptar,
  });

  @override
  Widget build(BuildContext context) {
    final esDiferido = viaje['estado'] == 'Diferido';

    // CAMBIADO: usar numero_cuenta en lugar de cc
    final numeroCuenta = viaje['numero_cuenta']?.toString() ?? 'Sin cuenta';
    final nombreEmpresa = viaje['empresa_nombre'] ?? 'Sin empresa';

    return Card(
      elevation: 3,
      margin: const EdgeInsets.only(bottom: 12),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
      ),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Cuenta y empresa
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Expanded(
                  child: Text(
                    'Cuenta $numeroCuenta - $nombreEmpresa',
                    style: const TextStyle(
                      fontWeight: FontWeight.bold,
                      fontSize: 14,
                      color: Colors.blue,
                    ),
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: Colors.green[100],
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Text(
                    viaje['categoria_movil'] ?? 'REMIS',
                    style: TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.bold,
                      color: Colors.green[800],
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 4),

            if (esDiferido)
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: Colors.orange[100],
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Text(
                  '📅 DIFERIDO',
                  style: TextStyle(
                    fontSize: 10,
                    fontWeight: FontWeight.bold,
                    color: Colors.orange[800],
                  ),
                ),
              ),

            const Divider(),

            if (esDiferido && viaje['fecha'] != null) _buildFechaHora(),

            _buildInfoRow(Icons.person, viaje['nombre_pasaj'] ?? 'Sin nombre'),
            const SizedBox(height: 4),

            _buildInfoRow(
                Icons.phone, viaje['cel_pasaj']?.toString() ?? 'Sin celular'),
            const SizedBox(height: 4),

            _buildInfoRow(
                Icons.location_on, viaje['direccion_origen'] ?? 'Sin origen'),

            _buildInfoRow(
                Icons.location_on, viaje['direccion_destino'] ?? 'Sin destino'),

            if (viaje['obs_operador'] != null &&
                viaje['obs_operador'].toString().isNotEmpty)
              _buildObservaciones(viaje['obs_operador']),

            const SizedBox(height: 12),

            _buildBotonAceptar(esDiferido),
          ],
        ),
      ),
    );
  }

  Widget _buildFechaHora() {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        children: [
          Icon(Icons.calendar_today, size: 16, color: Colors.orange[700]),
          const SizedBox(width: 6),
          Text(
            '${viaje['fecha']} ${viaje['hora'] ?? ''}',
            style: TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w500,
              color: Colors.orange[700],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildInfoRow(IconData icon, String texto) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, size: 18, color: Colors.grey),
        const SizedBox(width: 8),
        Expanded(
          child: Text(
            texto,
            style: const TextStyle(fontSize: 14),
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
          ),
        ),
      ],
    );
  }

  Widget _buildObservaciones(String texto) {
    return Padding(
      padding: const EdgeInsets.only(top: 6),
      child: Row(
        children: [
          const Icon(Icons.note, size: 18, color: Colors.grey),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              texto,
              style: const TextStyle(
                fontSize: 13,
                color: Colors.grey,
              ),
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildBotonAceptar(bool esDiferido) {
    return SizedBox(
      width: double.infinity,
      child: ElevatedButton(
        onPressed: onAceptar,
        style: ElevatedButton.styleFrom(
          backgroundColor: esDiferido ? Colors.orange : Colors.green,
          foregroundColor: Colors.white,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(8),
          ),
          padding: const EdgeInsets.symmetric(vertical: 12),
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              esDiferido ? Icons.schedule : Icons.check_circle,
              size: 20,
            ),
            const SizedBox(width: 8),
            Text(
              esDiferido ? 'ACEPTAR VIAJE DIFERIDO' : 'ACEPTAR VIAJE',
              style: const TextStyle(
                fontWeight: FontWeight.bold,
                fontSize: 14,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

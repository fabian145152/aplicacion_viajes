import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:url_launcher/url_launcher.dart';

class ViajeCard extends StatelessWidget {
  final dynamic viaje;
  final Function(Map<String, dynamic>) onAceptar;

  const ViajeCard({
    super.key,
    required this.viaje,
    required this.onAceptar,
  });

  Future<String> _calcularDistancia() async {
    try {
      LocationPermission permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
        if (permission == LocationPermission.denied) {
          return 'Sin permisos';
        }
      }
      if (permission == LocationPermission.deniedForever) {
        return 'Sin permisos';
      }

      Position position = await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.high,
        timeLimit: const Duration(seconds: 5),
      );

      double? origenLat =
          double.tryParse(viaje['origen_lat']?.toString() ?? '');
      double? origenLng =
          double.tryParse(viaje['origen_lng']?.toString() ?? '');

      if (origenLat == null ||
          origenLng == null ||
          (origenLat == 0.0 && origenLng == 0.0)) {
        return '';
      }

      double distanciaMetros = Geolocator.distanceBetween(
        position.latitude,
        position.longitude,
        origenLat,
        origenLng,
      );

      if (distanciaMetros >= 1000) {
        return '${(distanciaMetros / 1000).toStringAsFixed(1)} km';
      } else {
        return '${distanciaMetros.toStringAsFixed(0)} m';
      }
    } catch (e) {
      debugPrint('Error calculando distancia: $e');
      return '';
    }
  }

  Future<void> _abrirWhatsApp(String celular) async {
    if (celular.isEmpty || celular == 'Sin celular') {
      return;
    }
    final numeroLimpio = celular.replaceAll(RegExp(r'[^0-9]'), '');
    final url = 'https://wa.me/54$numeroLimpio';

    if (await canLaunchUrl(Uri.parse(url))) {
      await launchUrl(Uri.parse(url));
    } else {
      final webUrl = 'https://web.whatsapp.com/send?phone=54$numeroLimpio';
      if (await canLaunchUrl(Uri.parse(webUrl))) {
        await launchUrl(Uri.parse(webUrl));
      } else {
        debugPrint('❌ No se pudo abrir WhatsApp en ninguna versión');
      }
    }
  }

  void _mostrarInfo(BuildContext context) {
    final nombre = viaje['nombre_pasaj']?.toString() ?? 'Sin nombre';
    final celular = viaje['cel_pasaj']?.toString() ?? 'Sin celular';
    final origen = viaje['direccion_origen']?.toString() ?? 'Sin origen';
    final destino = viaje['direccion_destino']?.toString() ?? 'Sin destino';
    final obsOperador =
        viaje['obs_operador']?.toString() ?? 'Sin observaciones';
    final obsPasaj = viaje['obs_pasaj']?.toString() ?? 'Sin observaciones';

    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('📋 Información del Viaje',
            style: TextStyle(fontWeight: FontWeight.bold)),
        content: SingleChildScrollView(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisSize: MainAxisSize.min,
            children: [
              _infoRow('👤 Pasajero', nombre),
              const SizedBox(height: 8),
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 4.0),
                child: ElevatedButton.icon(
                  onPressed: () => _abrirWhatsApp(celular),
                  icon: const Icon(Icons.wechat, size: 24),
                  label: Text(
                    '📱 $celular',
                    style: const TextStyle(
                        fontSize: 16, fontWeight: FontWeight.bold),
                  ),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF25D366),
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(10),
                    ),
                    padding: const EdgeInsets.symmetric(
                        horizontal: 20, vertical: 12),
                    minimumSize: const Size(double.infinity, 48),
                  ),
                ),
              ),
              const SizedBox(height: 8),
              _infoRow('📍 Origen', origen),
              const SizedBox(height: 8),
              _infoRow('📍 Destino', destino),
              const SizedBox(height: 8),
              _infoRow('📝 Obs. Chofer', obsOperador),
              const SizedBox(height: 8),
              _infoRow('📝 Obs. Pasajero', obsPasaj),
            ],
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cerrar'),
          ),
        ],
      ),
    );
  }

  Widget _infoRow(String label, String value, {Color? color}) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          '$label: ',
          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
        ),
        Expanded(
          child: Text(
            value,
            style: TextStyle(
              fontSize: 14,
              color: color ?? Colors.black87,
              fontWeight: color != null ? FontWeight.w500 : FontWeight.normal,
            ),
            maxLines: 3,
            overflow: TextOverflow.ellipsis,
          ),
        ),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    final esDiferido = viaje['estado'] == 'Diferido';
    final cc = int.tryParse(viaje['cc']?.toString() ?? '0') ?? 0;
    final nombrePasaj = viaje['nombre_pasaj']?.toString() ?? 'Sin nombre';

    final String topText;
    if (cc != 0) {
      topText = 'Cuenta N° $cc - $nombrePasaj';
    } else {
      topText = 'Pasajero eventual - $nombrePasaj';
    }

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
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Expanded(
                  child: Text(
                    topText,
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
                    viaje['categoria_movil']?.toString() ?? 'REMIS',
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
            FutureBuilder<String>(
              future: _calcularDistancia(),
              builder: (context, snapshot) {
                final distancia = snapshot.data ?? '';
                final mostrarDistancia =
                    distancia.isNotEmpty && distancia != 'Sin permisos';

                return Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Icon(Icons.location_on, size: 18, color: Colors.blue),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        viaje['direccion_origen']?.toString() ?? 'Sin origen',
                        style: const TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w500,
                        ),
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                    if (mostrarDistancia)
                      Container(
                        padding: const EdgeInsets.symmetric(
                            horizontal: 8, vertical: 4),
                        decoration: BoxDecoration(
                          color: Colors.green[100],
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Text(
                          distancia,
                          style: TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.bold,
                            color: Colors.green[700],
                          ),
                        ),
                      ),
                  ],
                );
              },
            ),
            const SizedBox(height: 8),
            _buildInfoRow(Icons.person, nombrePasaj),
            if (viaje['obs_operador'] != null &&
                viaje['obs_operador'].toString().isNotEmpty)
              _buildObservaciones(viaje['obs_operador'].toString()),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  flex: 7,
                  child: ElevatedButton(
                    onPressed: () => onAceptar(viaje as Map<String, dynamic>),
                    style: ElevatedButton.styleFrom(
                      backgroundColor:
                          esDiferido ? Colors.orange : Colors.green,
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(8),
                      ),
                      padding: const EdgeInsets.symmetric(vertical: 12),
                    ),
                    child: const Text(
                      'PASAJERO A BORDO',
                      style: TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 14,
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  flex: 3,
                  child: ElevatedButton(
                    onPressed: () => _mostrarInfo(context),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.blue,
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(8),
                      ),
                      padding: const EdgeInsets.symmetric(vertical: 12),
                    ),
                    child: const Text(
                      '📋 Info',
                      style: TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 14,
                      ),
                    ),
                  ),
                ),
              ],
            ),
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
            '${viaje['fecha']?.toString() ?? ''} ${viaje['hora']?.toString() ?? ''}',
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

  Widget _buildInfoRow(IconData icon, String texto, {Color? color}) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, size: 18, color: color ?? Colors.grey),
        const SizedBox(width: 8),
        Expanded(
          child: Text(
            texto,
            style: TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w500,
              color: color ?? Colors.black,
            ),
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
}

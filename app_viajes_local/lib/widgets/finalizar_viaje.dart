import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';

class FinalizarViajePage extends StatefulWidget {
  final Map<String, dynamic> viaje;
  final String movilId;

  const FinalizarViajePage({
    super.key,
    required this.viaje,
    required this.movilId,
  });

  @override
  State<FinalizarViajePage> createState() => _FinalizarViajePageState();
}

class _FinalizarViajePageState extends State<FinalizarViajePage> {
  final TextEditingController _kmController = TextEditingController();
  final TextEditingController _peajesController = TextEditingController();
  final TextEditingController _tiempoEsperaController = TextEditingController();
  final TextEditingController _observacionesController =
      TextEditingController();

  bool _enviando = false;

  @override
  void dispose() {
    _kmController.dispose();
    _peajesController.dispose();
    _tiempoEsperaController.dispose();
    _observacionesController.dispose();
    super.dispose();
  }

  Future<void> _finalizarViaje() async {
    final String km = _kmController.text.trim();
    final String peajes = _peajesController.text.trim();
    final String tiempoEspera = _tiempoEsperaController.text.trim();
    final String observaciones = _observacionesController.text.trim();

    if (km.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('⚠️ Ingresa la cantidad de kilómetros recorridos'),
          backgroundColor: Colors.orange,
          duration: Duration(seconds: 2),
        ),
      );
      return;
    }

    if (peajes.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('⚠️ Ingresa el monto de peajes (0 si no hay)'),
          backgroundColor: Colors.orange,
          duration: Duration(seconds: 2),
        ),
      );
      return;
    }

    if (tiempoEspera.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('⚠️ Ingresa el tiempo de espera (0 si no hubo)'),
          backgroundColor: Colors.orange,
          duration: Duration(seconds: 2),
        ),
      );
      return;
    }

    setState(() {
      _enviando = true;
    });

    try {
      final viajeId = widget.viaje['id'];
      final url = Uri.parse(
          'http://192.168.0.225/aplicacion_viajes/app_viajes/php/01_mapeo/finalizar_viaje.php');

      // En finalizar_viaje.dart - ya está enviando movil_id
      final body = jsonEncode({
        'viaje_id': viajeId,
        'movil_id': widget.movilId, // ✅ Ya se envía el móvil
        'km': double.tryParse(km) ?? 0,
        'peajes': double.tryParse(peajes) ?? 0,
        'tiempo_espera': int.tryParse(tiempoEspera) ?? 0,
        'observaciones': observaciones,
        'estado': 'Completo',
      });

      if (kDebugMode) {
        print('📤 Finalizando viaje: $body');
      }

      final response = await http
          .post(
            url,
            headers: {"Content-Type": "application/json"},
            body: body,
          )
          .timeout(const Duration(seconds: 10));

      if (kDebugMode) {
        print('📥 Respuesta: ${response.body}');
      }

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['res'] == 'OK') {
          _mostrarMensajeExito();
        } else {
          _mostrarMensajeError('Error del servidor: ${data['msg']}');
        }
      } else {
        _mostrarMensajeError('Error HTTP: ${response.statusCode}');
      }
    } catch (e) {
      if (kDebugMode) print('❌ Error finalizando viaje: $e');
      _mostrarMensajeError('Error al finalizar viaje: $e');
    } finally {
      if (mounted) {
        setState(() {
          _enviando = false;
        });
      }
    }
  }

  void _mostrarMensajeExito() {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => AlertDialog(
        title: const Row(
          children: [
            Icon(Icons.check_circle, color: Colors.green, size: 28),
            SizedBox(width: 10),
            Text(
              '✅ Viaje Finalizado',
              style: TextStyle(
                fontWeight: FontWeight.bold,
                fontSize: 20,
              ),
            ),
          ],
        ),
        content: const Text(
          'El viaje se ha completado correctamente.',
          style: TextStyle(fontSize: 16),
        ),
        actions: [
          TextButton(
            onPressed: () {
              Navigator.of(context).pop();
              Navigator.of(context).pop(true);
            },
            child: const Text('VOLVER'),
          ),
        ],
      ),
    );
  }

  void _mostrarMensajeError(String mensaje) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('❌ $mensaje'),
        backgroundColor: Colors.red,
        duration: const Duration(seconds: 3),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final nombrePasaj =
        widget.viaje['nombre_pasaj']?.toString() ?? 'Sin nombre';
    final origen = widget.viaje['direccion_origen']?.toString() ?? 'Sin origen';
    final destino =
        widget.viaje['direccion_destino']?.toString() ?? 'Sin destino';

    return Scaffold(
      appBar: AppBar(
        title: const Text('🧾 Finalizar Viaje'),
        backgroundColor: Colors.green,
        foregroundColor: Colors.white,
        automaticallyImplyLeading: false,
        actions: [
          IconButton(
            icon: const Icon(Icons.close),
            onPressed: () => Navigator.pop(context, false),
          ),
        ],
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Card(
              elevation: 3,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
              ),
              child: Padding(
                padding: const EdgeInsets.all(16.0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        const Icon(Icons.person, color: Colors.blue, size: 18),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            'Pasajero: $nombrePasaj',
                            style: const TextStyle(
                              fontSize: 14,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 4),
                    Row(
                      children: [
                        const Icon(Icons.location_on,
                            color: Colors.green, size: 16),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            'Origen: $origen',
                            style: const TextStyle(fontSize: 12),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 2),
                    Row(
                      children: [
                        const Icon(Icons.location_on,
                            color: Colors.red, size: 16),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            'Destino: $destino',
                            style: const TextStyle(fontSize: 12),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 24),
            const Text(
              'Datos del viaje realizado',
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 16),
            TextField(
              controller: _kmController,
              keyboardType:
                  const TextInputType.numberWithOptions(decimal: true),
              decoration: const InputDecoration(
                labelText: '📏 Kilómetros recorridos',
                border: OutlineInputBorder(),
                hintText: 'Ej: 7.5',
                prefixIcon: Icon(Icons.straighten),
              ),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _peajesController,
              keyboardType:
                  const TextInputType.numberWithOptions(decimal: true),
              decoration: const InputDecoration(
                labelText: '💰 Peajes (\$)',
                border: OutlineInputBorder(),
                hintText: 'Ej: 0 o 1500',
                prefixIcon: Icon(Icons.money),
              ),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _tiempoEsperaController,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(
                labelText: '⏱️ Tiempo de espera (minutos)',
                border: OutlineInputBorder(),
                hintText: 'Ej: 0 o 15',
                prefixIcon: Icon(Icons.timer),
              ),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _observacionesController,
              maxLines: 3,
              decoration: const InputDecoration(
                labelText: '📝 Observaciones',
                border: OutlineInputBorder(),
                hintText: 'Observaciones sobre el viaje...',
                prefixIcon: Icon(Icons.note),
                alignLabelWithHint: true,
              ),
            ),
            const SizedBox(height: 24),
            SizedBox(
              width: double.infinity,
              height: 55,
              child: ElevatedButton(
                onPressed: _enviando ? null : _finalizarViaje,
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.green,
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(10),
                  ),
                ),
                child: _enviando
                    ? const Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          SizedBox(
                            height: 20,
                            width: 20,
                            child: CircularProgressIndicator(
                              strokeWidth: 2,
                              color: Colors.white,
                            ),
                          ),
                          SizedBox(width: 12),
                          Text('Finalizando...'),
                        ],
                      )
                    : const Text(
                        '✅ CERRAR VIAJE',
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

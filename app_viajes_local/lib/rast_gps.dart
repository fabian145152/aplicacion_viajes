import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart'; // <-- IMPORTANTE: Para kDebugMode
import 'package:http/http.dart' as http;
import 'package:geolocator/geolocator.dart';
import 'package:wakelock_plus/wakelock_plus.dart';
import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'login.dart';

class BotonCoordenadas extends StatefulWidget {
  final String numeroMovil;
  const BotonCoordenadas({super.key, required this.numeroMovil});

  @override
  State<BotonCoordenadas> createState() => _BotonCoordenadasState();
}

class _BotonCoordenadasState extends State<BotonCoordenadas> {
  bool _isActive = false;
  Timer? _timer;
  final TextEditingController _idController = TextEditingController();
  bool _isSending = false;

  final String _serverUrl =
      'http://192.168.0.225/aplicacion_viajes/app_viajes/php/01_mapeo/recibir.php';

  // Función para debug
  void _log(String message) {
    if (kDebugMode) {
      print(message);
    }
  }

  @override
  void initState() {
    super.initState();
    _idController.text = widget.numeroMovil;
    WakelockPlus.enable();
  }

  @override
  void dispose() {
    _timer?.cancel();
    _idController.dispose();
    WakelockPlus.disable();
    super.dispose();
  }

  Future<void> _sendLocation(String status) async {
    if (_isSending) return;
    _isSending = true;

    try {
      LocationPermission permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied ||
          permission == LocationPermission.deniedForever) {
        _log('❌ Sin permiso de ubicación');
        _isSending = false;
        return;
      }

      final position = await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.high,
      ).timeout(const Duration(seconds: 10));

      final url = Uri.parse(_serverUrl);

      final body = jsonEncode({
        'lat': position.latitude,
        'lng': position.longitude,
        'usuario_id': _idController.text,
        'status': status,
      });

      final response = await http
          .post(
            url,
            headers: {"Content-Type": "application/json"},
            body: body,
          )
          .timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        _log(
            '✅ Enviado: ${position.latitude}, ${position.longitude} - Status: $status');
      } else {
        _log('❌ Error: ${response.statusCode} - Status: $status');
      }
    } on TimeoutException {
      _log('❌ Timeout');
    } on SocketException {
      _log('❌ Error de red');
    } catch (e) {
      _log('❌ Error: $e');
    } finally {
      _isSending = false;
    }
  }

  // Función para enviar el estado 3 veces
  Future<void> _sendStatusMultipleTimes(String status) async {
    _log('📤 Enviando estado "$status" 3 veces...');

    // Enviar 3 veces con un pequeño delay entre cada envío
    for (int i = 1; i <= 3; i++) {
      _log('📤 Envío $i de 3 - Status: $status');
      await _sendLocation(status);
      // Esperar 500ms entre cada envío para no saturar
      await Future.delayed(const Duration(milliseconds: 500));
    }

    _log('✅ Estado "$status" enviado 3 veces correctamente');
  }

  void _toggleTracking(bool active) async {
    if (active) {
      // Al activar: enviar "activo" 3 veces y luego iniciar el timer
      _log('🟢 Activando seguimiento...');
      await _sendStatusMultipleTimes('activo');

      // Iniciar el timer para envíos periódicos
      _timer = Timer.periodic(const Duration(seconds: 5), (_) {
        _sendLocation('activo');
      });
    } else {
      // Al desactivar: cancelar timer y enviar "inactivo" 3 veces
      _log('🔴 Desactivando seguimiento...');
      _timer?.cancel();
      await _sendStatusMultipleTimes('inactivo');
    }
  }

  void _logout() async {
    if (_isActive) {
      _timer?.cancel();
      _log('🔄 Enviando estado inactivo antes de cerrar sesión...');
      await _sendStatusMultipleTimes('inactivo');
    }
    if (mounted) {
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(builder: (_) => const LoginPage()),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Móvil: ${widget.numeroMovil}'),
        backgroundColor: Colors.blue,
        foregroundColor: Colors.white,
        actions: [
          IconButton(
            icon: const Icon(Icons.logout),
            onPressed: _logout,
          ),
        ],
      ),
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 40.0),
              child: TextField(
                controller: _idController,
                textAlign: TextAlign.center,
                readOnly: true,
                decoration: const InputDecoration(
                  labelText: 'ID del Móvil',
                  border: OutlineInputBorder(),
                  prefixIcon: Icon(Icons.phone_android),
                ),
              ),
            ),
            const SizedBox(height: 40),
            GestureDetector(
              onTap: () {
                setState(() {
                  _isActive = !_isActive;
                  _toggleTracking(_isActive);
                });
              },
              child: AnimatedContainer(
                duration: const Duration(milliseconds: 300),
                width: 200,
                height: 200,
                decoration: BoxDecoration(
                  color: _isActive ? Colors.red : Colors.green,
                  shape: BoxShape.circle,
                  boxShadow: [
                    BoxShadow(
                      color: (_isActive ? Colors.red : Colors.green)
                          .withOpacity(0.4),
                      blurRadius: 20,
                    ),
                  ],
                ),
                child: Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(
                        _isActive ? Icons.stop : Icons.play_arrow,
                        color: Colors.white,
                        size: 50,
                      ),
                      const SizedBox(height: 10),
                      Text(
                        _isActive ? 'DETENER' : 'INICIAR',
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
            if (_isActive)
              const Padding(
                padding: EdgeInsets.only(top: 20),
                child: Text(
                  '🟢 Enviando ubicación...',
                  style: TextStyle(color: Colors.green),
                ),
              ),
            const SizedBox(height: 30),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.grey[100],
                borderRadius: BorderRadius.circular(8),
              ),
              child: const Column(
                children: [
                  Text(
                    'Servidor: 192.168.0.225',
                    style: TextStyle(fontSize: 12),
                  ),
                  Text(
                    '📤 Envía estado 3 veces al cambiar',
                    style: TextStyle(fontSize: 11, color: Colors.blue),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

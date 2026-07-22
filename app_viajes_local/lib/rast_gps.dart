import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'package:geolocator/geolocator.dart';
import 'package:wakelock_plus/wakelock_plus.dart';
import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'login.dart';

class BotonCoordenadas extends StatefulWidget {
  final String numeroMovil;
  final String nombreChofer;
  const BotonCoordenadas({
    super.key,
    required this.numeroMovil,
    required this.nombreChofer,
  });

  @override
  State<BotonCoordenadas> createState() => _BotonCoordenadasState();
}

class _BotonCoordenadasState extends State<BotonCoordenadas> {
  bool _isActive = false;
  Timer? _timer;
  Timer? _welcomeTimer;
  Timer? _viajesTimer;
  final TextEditingController _idController = TextEditingController();
  bool _isSending = false;
  List<dynamic> _viajesPendientes = [];
  bool _cargandoViajes = false;

  final String _serverUrl =
      'http://192.168.0.225/aplicacion_viajes/app_viajes/php/01_mapeo/recibir.php';
  final String _viajesUrl =
      'http://192.168.0.225/aplicacion_viajes/app_viajes/php/01_mapeo/obtener_viajes_pendientes.php';
  final String _asignarUrl =
      'http://192.168.0.225/aplicacion_viajes/app_viajes/php/01_mapeo/asignar_viaje.php';

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

    WidgetsBinding.instance.addPostFrameCallback((_) {
      _mostrarBienvenida();
    });

    // Iniciar polling de viajes cada 10 segundos
    _viajesTimer = Timer.periodic(const Duration(seconds: 10), (_) {
      _obtenerViajesPendientes();
    });
  }

  @override
  void dispose() {
    _timer?.cancel();
    _welcomeTimer?.cancel();
    _viajesTimer?.cancel();
    _idController.dispose();
    WakelockPlus.disable();
    super.dispose();
  }

  void _mostrarBienvenida() {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => AlertDialog(
        title: const Row(
          children: [
            Icon(Icons.verified_user, color: Colors.blue, size: 28),
            SizedBox(width: 10),
            Text(
              '¡Bienvenido!',
              style: TextStyle(
                fontWeight: FontWeight.bold,
                fontSize: 22,
              ),
            ),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Has iniciado sesión correctamente',
              style: TextStyle(
                fontSize: 16,
                color: Colors.grey,
              ),
            ),
            const SizedBox(height: 20),
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.blue[50],
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: Colors.blue[100]!),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      const Icon(Icons.person, color: Colors.blue, size: 20),
                      const SizedBox(width: 8),
                      Text(
                        'Chofer: ${widget.nombreChofer}',
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      const Icon(Icons.phone_android,
                          color: Colors.blue, size: 20),
                      const SizedBox(width: 8),
                      Text(
                        'Móvil: ${widget.numeroMovil}',
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),
            const Text(
              '⏱️ Esta ventana se cerrará automáticamente en 30 segundos',
              style: TextStyle(
                fontSize: 12,
                color: Colors.grey,
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () {
              Navigator.pop(context);
              _welcomeTimer?.cancel();
            },
            child: const Text('CERRAR'),
          ),
        ],
      ),
    );

    _welcomeTimer = Timer(const Duration(seconds: 30), () {
      if (mounted) {
        Navigator.pop(context);
      }
    });
  }

  // Obtener viajes pendientes desde el servidor
  Future<void> _obtenerViajesPendientes() async {
    if (_cargandoViajes) return;
    _cargandoViajes = true;

    try {
      final url = Uri.parse(_viajesUrl);
      final response = await http.get(url).timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['res'] == 'OK') {
          setState(() {
            _viajesPendientes = data['viajes'] ?? [];
          });
          _log('📋 Viajes pendientes: ${_viajesPendientes.length}');
        }
      }
    } catch (e) {
      _log('❌ Error al obtener viajes: $e');
    } finally {
      _cargandoViajes = false;
    }
  }

  // Asignar un viaje al móvil actual
  Future<void> _asignarViaje(int viajeId) async {
    try {
      final url = Uri.parse(_asignarUrl);
      final body = jsonEncode({
        'viaje_id': viajeId,
        'movil_id': widget.numeroMovil,
      });

      final response = await http
          .post(
            url,
            headers: {"Content-Type": "application/json"},
            body: body,
          )
          .timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['res'] == 'OK') {
          _log('✅ Viaje $viajeId asignado correctamente');
          _mostrarMensaje('✅ Viaje asignado correctamente');
          // Refrescar lista
          _obtenerViajesPendientes();
        } else {
          _mostrarMensaje('❌ ${data['msg'] ?? 'Error al asignar viaje'}');
        }
      }
    } catch (e) {
      _log('❌ Error al asignar viaje: $e');
      _mostrarMensaje('❌ Error al asignar viaje');
    }
  }

  void _mostrarMensaje(String mensaje) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(mensaje),
        backgroundColor: Colors.blue,
        duration: const Duration(seconds: 3),
      ),
    );
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
    } finally {
      _isSending = false;
    }
  }

  Future<void> _sendStatusMultipleTimes(String status) async {
    _log('📤 Enviando estado "$status" 3 veces...');
    for (int i = 1; i <= 3; i++) {
      _log('📤 Envío $i de 3 - Status: $status');
      await _sendLocation(status);
      await Future.delayed(const Duration(milliseconds: 500));
    }
    _log('✅ Estado "$status" enviado 3 veces correctamente');
  }

  void _toggleTracking(bool active) async {
    if (active) {
      _log('🟢 Activando seguimiento...');
      await _sendStatusMultipleTimes('activo');
      _timer = Timer.periodic(const Duration(seconds: 5), (_) {
        _sendLocation('activo');
      });
    } else {
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
        title: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Text('Móvil:'),
            const SizedBox(width: 8),
            Text(widget.numeroMovil),
            const SizedBox(width: 16),
            GestureDetector(
              onTap: () {
                setState(() {
                  _isActive = !_isActive;
                  _toggleTracking(_isActive);
                });
              },
              child: Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                decoration: BoxDecoration(
                  color: _isActive ? Colors.green : Colors.red,
                  borderRadius: BorderRadius.circular(20),
                  boxShadow: [
                    BoxShadow(
                      color: (_isActive ? Colors.green : Colors.red)
                          .withOpacity(0.3),
                      blurRadius: 8,
                      offset: const Offset(0, 2),
                    ),
                  ],
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(
                      _isActive ? Icons.play_arrow : Icons.stop,
                      color: Colors.white,
                      size: 16,
                    ),
                    const SizedBox(width: 4),
                    Text(
                      _isActive ? 'ACTIVO' : 'NO ACTIVO',
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 12,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
        backgroundColor: Colors.blue,
        foregroundColor: Colors.white,
        actions: [
          IconButton(
            icon: const Icon(Icons.logout),
            onPressed: _logout,
          ),
        ],
      ),
      body: Column(
        children: [
          // Indicador de estado
          Container(
            padding: const EdgeInsets.all(12),
            color: Colors.grey[100],
            child: Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(
                  _isActive ? Icons.gps_fixed : Icons.gps_off,
                  color: _isActive ? Colors.green : Colors.grey,
                ),
                const SizedBox(width: 8),
                Text(
                  _isActive ? '🟢 Enviando ubicación...' : '⚪ Esperando...',
                  style: TextStyle(
                    color: _isActive ? Colors.green : Colors.grey,
                    fontSize: 16,
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ],
            ),
          ),

          // Lista de viajes pendientes
          Expanded(
            child: _viajesPendientes.isEmpty
                ? const Center(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(Icons.inbox, size: 64, color: Colors.grey),
                        SizedBox(height: 16),
                        Text(
                          'No hay viajes pendientes',
                          style: TextStyle(
                            fontSize: 18,
                            color: Colors.grey,
                          ),
                        ),
                        SizedBox(height: 8),
                        Text(
                          'Esperando nuevos viajes...',
                          style: TextStyle(
                            fontSize: 14,
                            color: Colors.grey,
                          ),
                        ),
                      ],
                    ),
                  )
                : ListView.builder(
                    padding: const EdgeInsets.all(12),
                    itemCount: _viajesPendientes.length,
                    itemBuilder: (context, index) {
                      final viaje = _viajesPendientes[index];
                      final esDiferido = viaje['diferido'] == 'Si';

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
                              // Encabezado: Empresa y categoría
                              Row(
                                mainAxisAlignment:
                                    MainAxisAlignment.spaceBetween,
                                children: [
                                  Expanded(
                                    child: Text(
                                      viaje['empresa_nombre'] ?? 'Sin empresa',
                                      style: const TextStyle(
                                        fontWeight: FontWeight.bold,
                                        fontSize: 16,
                                        color: Colors.blue,
                                      ),
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                  ),
                                  Row(
                                    children: [
                                      // Mostrar si es diferido
                                      if (esDiferido)
                                        Container(
                                          padding: const EdgeInsets.symmetric(
                                              horizontal: 8, vertical: 4),
                                          decoration: BoxDecoration(
                                            color: Colors.orange[100],
                                            borderRadius:
                                                BorderRadius.circular(12),
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
                                      const SizedBox(width: 4),
                                      Container(
                                        padding: const EdgeInsets.symmetric(
                                            horizontal: 8, vertical: 4),
                                        decoration: BoxDecoration(
                                          color: Colors.green[100],
                                          borderRadius:
                                              BorderRadius.circular(12),
                                        ),
                                        child: Text(
                                          viaje['categoria_movil'] ?? 'REMIS',
                                          style: TextStyle(
                                            fontSize: 12,
                                            fontWeight: FontWeight.bold,
                                            color: Colors.green[800],
                                          ),
                                        ),
                                      ),
                                    ],
                                  ),
                                ],
                              ),
                              const Divider(),

                              // Fecha y hora si es diferido
                              if (esDiferido && viaje['fecha'] != null)
                                Padding(
                                  padding: const EdgeInsets.only(bottom: 8),
                                  child: Row(
                                    children: [
                                      Icon(Icons.calendar_today,
                                          size: 16, color: Colors.orange[700]),
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
                                ),

                              // Pasajero
                              Row(
                                children: [
                                  const Icon(Icons.person,
                                      size: 18, color: Colors.grey),
                                  const SizedBox(width: 8),
                                  Expanded(
                                    child: Text(
                                      viaje['nombre_pasaj'] ?? 'Sin nombre',
                                      style: const TextStyle(fontSize: 15),
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 6),

                              // Celular
                              Row(
                                children: [
                                  const Icon(Icons.phone,
                                      size: 18, color: Colors.grey),
                                  const SizedBox(width: 8),
                                  Text(
                                    viaje['cel_pasaj']?.toString() ??
                                        'Sin celular',
                                    style: const TextStyle(fontSize: 14),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 6),

                              // Origen
                              Row(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  const Icon(Icons.location_on,
                                      size: 18, color: Colors.grey),
                                  const SizedBox(width: 8),
                                  Expanded(
                                    child: Text(
                                      viaje['direccion_origen'] ?? 'Sin origen',
                                      style: const TextStyle(fontSize: 14),
                                      maxLines: 2,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                  ),
                                ],
                              ),

                              // Destino
                              Row(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  const Icon(Icons.location_on,
                                      size: 18, color: Colors.grey),
                                  const SizedBox(width: 8),
                                  Expanded(
                                    child: Text(
                                      viaje['direccion_destino'] ??
                                          'Sin destino',
                                      style: const TextStyle(fontSize: 14),
                                      maxLines: 2,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                  ),
                                ],
                              ),

                              // Observaciones
                              if (viaje['obs_operador'] != null &&
                                  viaje['obs_operador'].toString().isNotEmpty)
                                Padding(
                                  padding: const EdgeInsets.only(top: 6),
                                  child: Row(
                                    children: [
                                      const Icon(Icons.note,
                                          size: 18, color: Colors.grey),
                                      const SizedBox(width: 8),
                                      Expanded(
                                        child: Text(
                                          viaje['obs_operador'],
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
                                ),

                              const SizedBox(height: 12),

                              // Botón aceptar viaje
                              SizedBox(
                                width: double.infinity,
                                child: ElevatedButton(
                                  onPressed: () => _asignarViaje(viaje['id']),
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: esDiferido
                                        ? Colors.orange
                                        : Colors.green,
                                    foregroundColor: Colors.white,
                                    shape: RoundedRectangleBorder(
                                      borderRadius: BorderRadius.circular(8),
                                    ),
                                    padding: const EdgeInsets.symmetric(
                                        vertical: 12),
                                  ),
                                  child: Row(
                                    mainAxisAlignment: MainAxisAlignment.center,
                                    children: [
                                      Icon(
                                          esDiferido
                                              ? Icons.schedule
                                              : Icons.check_circle,
                                          size: 20),
                                      const SizedBox(width: 8),
                                      Text(
                                        esDiferido
                                            ? 'ACEPTAR VIAJE DIFERIDO'
                                            : 'ACEPTAR VIAJE',
                                        style: const TextStyle(
                                          fontWeight: FontWeight.bold,
                                          fontSize: 14,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                      );
                    },
                  ),
          ),
        ],
      ),
    );
  }
}

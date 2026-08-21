import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart';
import 'package:geolocator/geolocator.dart';
import 'package:wakelock_plus/wakelock_plus.dart';
import 'dart:async';
import 'dart:convert';
import 'package:http/http.dart' as http;
import 'login.dart';
import 'services/viaje_service.dart';
import 'widgets/viaje_card.dart';
import 'widgets/viaje_en_curso.dart';

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

class _BotonCoordenadasState extends State<BotonCoordenadas>
    with WidgetsBindingObserver {
  bool _isActive = false;
  Timer? _timer;
  Timer? _welcomeTimer;
  Timer? _viajesTimer;
  bool _isSending = false;
  List<dynamic> _viajesPendientes = [];
  bool _cargandoViajes = false;

  late ViajeService _viajeService;

  final String _actualizarLoginUrl =
      "http://192.168.0.225/aplicacion_viajes/app_viajes/php/01_mapeo/actualizar_login.php";

  final String _actualizarActivoUrl =
      "http://192.168.0.225/aplicacion_viajes/app_viajes/php/01_mapeo/actualizar_activo.php";

  void _log(String message) {
    if (kDebugMode) {
      debugPrint('[RastGPS] $message');
    }
  }

  Future<void> _actualizarEstadoActivo(int activo) async {
    try {
      final url = Uri.parse(_actualizarActivoUrl);
      final body = jsonEncode({
        'movil': widget.numeroMovil,
        'activo': activo,
      });
      await http
          .post(url, headers: {"Content-Type": "application/json"}, body: body)
          .timeout(const Duration(seconds: 5));
    } catch (e) {
      _log('❌ Error al actualizar estado activo: $e');
    }
  }

  Future<void> _actualizarEstadoLogin(int logeado) async {
    try {
      final url = Uri.parse(_actualizarLoginUrl);
      final body = jsonEncode({
        'movil': widget.numeroMovil,
        'logeado': logeado,
      });
      await http
          .post(url, headers: {"Content-Type": "application/json"}, body: body)
          .timeout(const Duration(seconds: 5));
    } catch (e) {
      _log('❌ Error al actualizar estado login: $e');
    }
  }

  Future<void> _solicitarPermisosUbicacion() async {
    LocationPermission permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
      if (permission == LocationPermission.denied) return;
    }
    if (permission == LocationPermission.deniedForever) return;
  }

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);

    _viajeService = ViajeService(
      viajesUrl:
          'http://192.168.0.225/aplicacion_viajes/app_viajes/php/01_mapeo/obtener_viajes_pendientes.php',
      asignarUrl:
          'http://192.168.0.225/aplicacion_viajes/app_viajes/php/01_mapeo/asignar_viaje.php',
      serverUrl:
          'http://192.168.0.225/aplicacion_viajes/app_viajes/php/01_mapeo/recibir.php',
    );

    WakelockPlus.enable();
    _solicitarPermisosUbicacion();
    WidgetsBinding.instance.addPostFrameCallback((_) => _mostrarBienvenida());
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _timer?.cancel();
    _welcomeTimer?.cancel();
    _viajesTimer?.cancel();
    WakelockPlus.disable();
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    super.didChangeAppLifecycleState(state);
    if (state == AppLifecycleState.paused) {
      _log('📱 App en segundo plano - Manteniendo estado activo');
    }
    if (state == AppLifecycleState.detached) {
      _log('📱 App cerrada completamente - Desconectando...');
      _realizarLogout(mostrarMensaje: false);
    }
  }

  void _realizarLogout({bool mostrarMensaje = true}) async {
    _timer?.cancel();
    _viajesTimer?.cancel();

    if (_isActive) {
      await _sendStatusMultipleTimes('inactivo');
      await _actualizarEstadoActivo(0);
    }

    await _actualizarEstadoLogin(0);

    if (mostrarMensaje && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
            content: Text('Sesión cerrada correctamente'),
            backgroundColor: Colors.blue),
      );
    }

    if (mounted) {
      Navigator.pushReplacement(
          context, MaterialPageRoute(builder: (_) => const LoginPage()));
    }
  }

  void _mostrarBienvenida() {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => AlertDialog(
        title: const Row(children: [
          Icon(Icons.verified_user, color: Colors.blue, size: 28),
          SizedBox(width: 10),
          Text('¡Bienvenido!',
              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 22))
        ]),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Has iniciado sesión correctamente',
                style: TextStyle(fontSize: 16, color: Colors.grey)),
            const SizedBox(height: 20),
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                  color: Colors.blue[50],
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: Colors.blue[100]!)),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(children: [
                    const Icon(Icons.person, color: Colors.blue, size: 20),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text('Chofer: ${widget.nombreChofer}',
                          style: const TextStyle(
                              fontSize: 16, fontWeight: FontWeight.w500)),
                    )
                  ]),
                  const SizedBox(height: 8),
                  Row(children: [
                    const Icon(Icons.phone_android,
                        color: Colors.blue, size: 20),
                    const SizedBox(width: 8),
                    Text('Móvil: ${widget.numeroMovil}',
                        style: const TextStyle(
                            fontSize: 16, fontWeight: FontWeight.w500))
                  ]),
                ],
              ),
            ),
            const SizedBox(height: 16),
            const Text(
                '⏱️ Esta ventana se cerrará automáticamente en 30 segundos',
                style: TextStyle(fontSize: 12, color: Colors.grey)),
          ],
        ),
        actions: [
          TextButton(
              onPressed: () {
                Navigator.pop(context);
                _welcomeTimer?.cancel();
              },
              child: const Text('CERRAR')),
        ],
      ),
    );
    _welcomeTimer = Timer(const Duration(seconds: 30), () {
      if (mounted) Navigator.pop(context);
    });
  }

  Future<void> _obtenerViajesPendientes() async {
    if (!_isActive || _cargandoViajes) return;
    _cargandoViajes = true;
    try {
      final viajes =
          await _viajeService.obtenerViajesPendientes(widget.numeroMovil);
      if (mounted) setState(() => _viajesPendientes = viajes);
    } catch (e) {
      _log('❌ ERROR en _obtenerViajesPendientes: $e');
    } finally {
      _cargandoViajes = false;
    }
  }

  void _asignarViaje(Map<String, dynamic> viaje) async {
    final viajeId = viaje['id'];

    if (kDebugMode) {
      debugPrint('🔴 1. Botón presionado. Viaje ID: $viajeId');
    }

    if (!_isActive) {
      _mostrarMensaje('⚠️ Activa el seguimiento para aceptar viajes');
      return;
    }

    int idNumerico;
    if (viajeId is int) {
      idNumerico = viajeId;
    } else if (viajeId is String) {
      idNumerico = int.tryParse(viajeId) ?? 0;
    } else {
      idNumerico = 0;
    }

    if (kDebugMode) {
      debugPrint('🔴 2. ID convertido a número: $idNumerico');
    }

    if (idNumerico == 0) {
      _mostrarMensaje('❌ ID de viaje inválido');
      return;
    }

    // 🔴 Ya no llamamos a asignarViaje() porque el viaje ya está asignado desde el panel web
    // Simplemente navegamos a la pantalla de viaje en curso

    if (mounted) {
      final resultado = await Navigator.push<bool>(
        context,
        MaterialPageRoute(
          builder: (context) => ViajeEnCursoPage(
            viaje: viaje,
            numeroMovil: widget.numeroMovil,
            onViajeCancelado: () {
              _obtenerViajesPendientes();
            },
          ),
        ),
      );

      if (resultado == true) {
        _obtenerViajesPendientes();
      }
    }

    await Future.delayed(const Duration(seconds: 1));
    _obtenerViajesPendientes();
  }

  void _mostrarMensaje(String mensaje) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: Text(mensaje),
        backgroundColor: Colors.blue,
        duration: const Duration(seconds: 3)));
  }

  Future<void> _sendLocation(String status) async {
    if (_isSending) return;
    _isSending = true;
    try {
      LocationPermission permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied ||
          permission == LocationPermission.deniedForever) {
        _isSending = false;
        return;
      }
      final position = await Geolocator.getCurrentPosition(
              desiredAccuracy: LocationAccuracy.high)
          .timeout(const Duration(seconds: 10));
      await _viajeService.sendLocation(
          widget.numeroMovil, position.latitude, position.longitude, status);
    } catch (e) {
      _log('❌ Error al enviar ubicación: $e');
    } finally {
      _isSending = false;
    }
  }

  Future<void> _sendStatusMultipleTimes(String status) async {
    for (int i = 1; i <= 3; i++) {
      await _sendLocation(status);
      await Future.delayed(const Duration(milliseconds: 500));
    }
  }

  void _toggleTracking(bool active) async {
    if (active) {
      await _actualizarEstadoActivo(1);
      await _sendStatusMultipleTimes('activo');
      _timer = Timer.periodic(
          const Duration(seconds: 5), (_) => _sendLocation('activo'));
      _viajesTimer = Timer.periodic(
          const Duration(seconds: 10), (_) => _obtenerViajesPendientes());
      _obtenerViajesPendientes();
    } else {
      _timer?.cancel();
      _viajesTimer?.cancel();
      await _actualizarEstadoActivo(0);
      await _sendStatusMultipleTimes('inactivo');
      if (mounted) setState(() => _viajesPendientes = []);
    }
  }

  void _preguntarSalir() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('🚪 ¿Cerrar sesión?'),
        content: const Text(
            '¿Estás seguro de que quieres salir de la aplicación? El seguimiento GPS se detendrá.'),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(context),
              child: const Text('Cancelar')),
          TextButton(
            onPressed: () {
              Navigator.pop(context);
              _realizarLogout(mostrarMensaje: true);
            },
            style: TextButton.styleFrom(foregroundColor: Colors.red),
            child: const Text('Salir'),
          ),
        ],
      ),
    );
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
                            .withValues(alpha: 0.3),
                        blurRadius: 8,
                        offset: const Offset(0, 2))
                  ],
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(_isActive ? Icons.play_arrow : Icons.stop,
                        color: Colors.white, size: 16),
                    const SizedBox(width: 4),
                    Text(_isActive ? 'ACTIVO' : 'NO ACTIVO',
                        style: const TextStyle(
                            color: Colors.white,
                            fontSize: 12,
                            fontWeight: FontWeight.bold)),
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
              icon: const Icon(Icons.logout), onPressed: _preguntarSalir),
        ],
      ),
      body: Column(
        children: [
          Container(
            padding: const EdgeInsets.all(12),
            color: Colors.grey[100],
            child: Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(_isActive ? Icons.gps_fixed : Icons.gps_off,
                    color: _isActive ? Colors.green : Colors.grey),
                const SizedBox(width: 8),
                Text(
                    _isActive
                        ? '🟢 Enviando ubicación...'
                        : '⏸️ Seguimiento detenido',
                    style: TextStyle(
                        color: _isActive ? Colors.green : Colors.grey,
                        fontSize: 16,
                        fontWeight: FontWeight.w500)),
              ],
            ),
          ),
          Expanded(
            child: _isActive
                ? (_viajesPendientes.isEmpty
                    ? const Center(
                        child: Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                            Icon(Icons.inbox, size: 64, color: Colors.grey),
                            SizedBox(height: 16),
                            Text('No hay viajes pendientes',
                                style: TextStyle(
                                    fontSize: 18, color: Colors.grey)),
                            SizedBox(height: 8),
                            Text('Esperando nuevos viajes...',
                                style:
                                    TextStyle(fontSize: 14, color: Colors.grey))
                          ]))
                    : ListView.builder(
                        padding: const EdgeInsets.all(12),
                        itemCount: _viajesPendientes.length,
                        itemBuilder: (context, index) {
                          final viaje = _viajesPendientes[index];
                          return ViajeCard(
                            viaje: viaje,
                            onAceptar: (viajeMap) => _asignarViaje(viajeMap),
                          );
                        }))
                : Center(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(Icons.pause_circle_outline,
                            size: 80, color: Colors.grey[400]),
                        const SizedBox(height: 16),
                        Text('Seguimiento desactivado',
                            style: TextStyle(
                                fontSize: 20,
                                fontWeight: FontWeight.bold,
                                color: Colors.grey[600])),
                        const SizedBox(height: 8),
                        Text(
                            'Activa el seguimiento para ver los viajes pendientes',
                            style: TextStyle(
                                fontSize: 14, color: Colors.grey[500])),
                        const SizedBox(height: 20),
                        ElevatedButton.icon(
                          onPressed: () {
                            setState(() {
                              _isActive = true;
                              _toggleTracking(true);
                            });
                          },
                          icon: const Icon(Icons.play_arrow),
                          label: const Text('RECIBIR VIAJES'),
                          style: ElevatedButton.styleFrom(
                              backgroundColor: Colors.green,
                              foregroundColor: Colors.white,
                              padding: const EdgeInsets.symmetric(
                                  horizontal: 30, vertical: 12),
                              shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(10))),
                        ),
                      ],
                    ),
                  ),
          ),
        ],
      ),
    );
  }
}

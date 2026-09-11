import 'dart:async';
import 'package:geolocator/geolocator.dart';
import 'package:device_info_plus/device_info_plus.dart';
import 'package:permission_handler/permission_handler.dart';
import '../database/ubicacion_dao.dart';
import '../models/ubicacion_model.dart';
import 'sync_services.dart';

class UbicacionService {
  static final UbicacionService _instance = UbicacionService._internal();
  factory UbicacionService() => _instance;
  UbicacionService._internal();

  Timer? _timer;
  String? _deviceId;
  bool _isRunning = false;
  String? _movil;
  String? _nombreChofer;
  String _estadoActual = 'logueado';
  int _viajeIdActual = 0;

  final UbicacionDAO _dao = UbicacionDAO();
  final SyncService _syncService = SyncService();

  void iniciar(String movil, String nombreChofer) {
    if (_isRunning) {
      print('[UbicacionService] ⚠️ Ya está corriendo');
      return;
    }

    _movil = movil;
    _nombreChofer = nombreChofer;
    _isRunning = true;
    _estadoActual = 'logueado';
    _viajeIdActual = 0;

    print('[UbicacionService] 🟢 Iniciando para móvil: $movil');

    _syncService.iniciar();
    _obtenerDeviceId();
  }

  /// 🔥 Actualiza estado y/o viaje, reiniciando el timer si cambia el intervalo
  void actualizarEstado(String nuevoEstado, {int viajeId = 0}) {
    if (!_isRunning) return;

    bool cambio = false;
    bool cambioIntervalo = false;

    // Detectar cambio de estado
    if (_estadoActual != nuevoEstado) {
      final intervaloAnterior = _getIntervaloPorEstado(_estadoActual);
      final intervaloNuevo = _getIntervaloPorEstado(nuevoEstado);

      print('[UbicacionService] 📊 Estado: $_estadoActual → $nuevoEstado');
      _estadoActual = nuevoEstado;
      cambio = true;

      if (intervaloAnterior != intervaloNuevo) {
        cambioIntervalo = true;
        print(
            '[UbicacionService] ⏱️ Intervalo: ${intervaloAnterior.inSeconds}s → ${intervaloNuevo.inSeconds}s');
      }
    }

    // Detectar cambio de viaje_id
    if (_viajeIdActual != viajeId) {
      print('[UbicacionService] 🚕 Viaje ID: $_viajeIdActual → $viajeId');
      _viajeIdActual = viajeId;
      cambio = true;
    }

    if (cambio) {
      // Enviar ubicación inmediatamente con el nuevo estado/viaje
      _generarYGuardarUbicacion();

      // Reiniciar timer solo si cambió el intervalo
      if (cambioIntervalo) {
        _reiniciarTimer();
      }
    }
  }

  /// 🔥 Intervalo según estado
  /// ┌─────────────────────────────────────┬──────────────┐
  /// │ Estado                              │ Intervalo    │
  /// ├─────────────────────────────────────┼──────────────┤
  /// │ ASIGNADO, A BORDO, CERRANDO         │ 10 segundos  │
  /// │ logueado, deslogueado, ACTIVO, INACTIVO │ 30 segundos │
  /// └─────────────────────────────────────┴──────────────┘
  Duration _getIntervaloPorEstado(String estado) {
    switch (estado) {
      // 🔥 Estados con viaje activo → 10s
      case 'ASIGNADO':
      case 'A BORDO':
      case 'CERRANDO':
        return const Duration(seconds: 10);

      // 🔥 Estados sin viaje → 30s
      case 'logueado':
      case 'deslogueado':
      case 'ACTIVO':
      case 'INACTIVO':
        return const Duration(seconds: 30);

      // Por defecto (por si llega algo raro)
      default:
        return const Duration(seconds: 30);
    }
  }

  /// 🔥 Reiniciar timer con el intervalo del estado actual
  void _reiniciarTimer() {
    _timer?.cancel();
    _timer = null;

    if (!_isRunning) return;

    final intervalo = _getIntervaloPorEstado(_estadoActual);
    print(
        '[UbicacionService] 🔄 Timer reiniciado a ${intervalo.inSeconds}s (estado: $_estadoActual)');

    _timer = Timer.periodic(
      intervalo,
      (timer) => _generarYGuardarUbicacion(),
    );
  }

  String get estadoActual => _estadoActual;
  int get viajeIdActual => _viajeIdActual;

  Future<void> _obtenerDeviceId() async {
    try {
      final DeviceInfoPlugin deviceInfo = DeviceInfoPlugin();
      final AndroidDeviceInfo androidInfo = await deviceInfo.androidInfo;
      _deviceId = androidInfo.id;

      print('[UbicacionService] 📱 Device ID: $_deviceId');

      if (_deviceId == null || _deviceId!.isEmpty) {
        _deviceId = 'dispositivo_${DateTime.now().millisecondsSinceEpoch}';
      }

      _iniciarEnvioPeriodico();
    } catch (e) {
      print('[UbicacionService] ❌ Error Device ID: $e');
      _deviceId = 'dispositivo_${DateTime.now().millisecondsSinceEpoch}';
      _iniciarEnvioPeriodico();
    }
  }

  /// 🔥 Iniciar timer con el intervalo del estado actual
  void _iniciarEnvioPeriodico() {
    if (_timer != null) return;

    final intervalo = _getIntervaloPorEstado(_estadoActual);
    print(
        '[UbicacionService] 📍 Iniciando envío cada ${intervalo.inSeconds}s (estado: $_estadoActual)');

    // Primera ubicación inmediata
    _generarYGuardarUbicacion();

    // Timer periódico
    _timer = Timer.periodic(
      intervalo,
      (timer) => _generarYGuardarUbicacion(),
    );
  }

  void detener() async {
    if (!_isRunning) return;

    print('[UbicacionService] 🔴 Deteniendo servicio...');

    _timer?.cancel();
    _timer = null;
    _isRunning = false;

    _estadoActual = 'deslogueado';
    _viajeIdActual = 0;
    await _generarYGuardarUbicacion();

    _syncService.detener();

    print('[UbicacionService] ✅ Servicio detenido');
  }

  Future<void> _generarYGuardarUbicacion() async {
    if (_movil == null || _deviceId == null) return;

    try {
      LocationPermission permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
        if (permission == LocationPermission.denied) return;
      }
      if (permission == LocationPermission.deniedForever) return;

      bool serviceEnabled = await Geolocator.isLocationServiceEnabled();
      if (!serviceEnabled) {
        await _guardarEnCola(0.0, 0.0);
        return;
      }

      final Position position = await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.high,
        timeLimit: const Duration(seconds: 10),
      );

      await _guardarEnCola(position.latitude, position.longitude);
    } catch (e) {
      print('[UbicacionService] ❌ Error: $e');
    }
  }

  Future<void> _guardarEnCola(double lat, double lng) async {
    final ubicacion = UbicacionModel(
      movil: _movil!,
      deviceId: _deviceId!,
      viajeId: _viajeIdActual,
      estado: _estadoActual,
      lat: lat,
      lng: lng,
      timestamp: DateTime.now().toIso8601String(),
    );

    await _dao.insertar(ubicacion);
    print(
        '[UbicacionService] 💾 Guardada - estado: $_estadoActual, viaje: $_viajeIdActual');

    _syncService.forzarSync();
  }

  bool get isRunning => _isRunning;
}

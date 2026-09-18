import 'dart:async';
import 'package:http/http.dart' as http;

class HeartbeatService {
  // ⚠️ Cambiá esta URL por la de tu servidor
  static const String _baseUrl =
      'http://10.0.2.2/aplicacion_viajes/app_viajes/php';
  // Pista: si probás en emulador Android, 10.0.2.2 = tu PC (localhost).
  // Si probás en celu real, poné la IP de tu PC (ej: 192.168.1.50).

  static HeartbeatService? _instancia;
  static HeartbeatService get instancia => _instancia ??= HeartbeatService._();

  HeartbeatService._();

  Timer? _timer;
  int _movil = 0;
  bool _activo = false;

  /// Arrancar el latido (llamar al loguearse)
  void iniciar(int movil) {
    if (_activo) return; // ya está corriendo
    _activo = true;
    _movil = movil;

    // Primer latido inmediato
    _latir();

    // Después cada 30 segundos
    _timer = Timer.periodic(const Duration(seconds: 30), (_) => _latir());
  }

  /// Detener el latido (llamar al desloguearse)
  void detener() {
    _activo = false;
    _timer?.cancel();
    _timer = null;
  }

  Future<void> _latir() async {
    if (_movil <= 0) return;
    try {
      await http.post(
        Uri.parse('$_baseUrl/heartbeat.php'),
        body: {'movil': _movil.toString()},
      ).timeout(const Duration(seconds: 10));
    } catch (_) {
      // Silencioso: si falla la red, el próximo intento reintenta
    }
  }
}

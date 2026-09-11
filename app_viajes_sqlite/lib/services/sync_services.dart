import 'dart:async';
import 'dart:convert';
import 'package:http/http.dart' as http;
import '../database/ubicacion_dao.dart';
import '../models/ubicacion_model.dart';

class SyncService {
  static final SyncService _instance = SyncService._internal();
  factory SyncService() => _instance;
  SyncService._internal();

  final UbicacionDAO _dao = UbicacionDAO();
  Timer? _syncTimer;
  bool _isSyncing = false;

  final String _urlRecibir =
      "http://181.47.100.96:8081/aplicacion_viajes/app_viajes/php/01_mapeo/recibir.php";

  void iniciar() {
    if (_syncTimer != null) return;
    print('[SyncService] 🟢 Iniciando sincronización automática');

    _sincronizar();

    _syncTimer = Timer.periodic(
      const Duration(minutes: 2),
      (_) => _sincronizar(),
    );
  }

  void detener() {
    _syncTimer?.cancel();
    _syncTimer = null;
    print('[SyncService] 🔴 Sincronización detenida');
  }

  Future<void> _sincronizar() async {
    if (_isSyncing) return;
    _isSyncing = true;

    try {
      final pendientes = await _dao.obtenerPendientes(limite: 20);

      if (pendientes.isEmpty) {
        _isSyncing = false;
        return;
      }

      print(
          '[SyncService] 📤 Sincronizando ${pendientes.length} ubicaciones...');

      for (final ubicacion in pendientes) {
        if (ubicacion.intentos >= 10) continue;

        final ok = await _enviarAlServidor(ubicacion);

        if (ok) {
          await _dao.marcarSincronizada(ubicacion.id!);
        } else {
          await _dao.incrementarIntentos(ubicacion.id!);
        }

        await Future.delayed(const Duration(milliseconds: 200));
      }

      await _dao.limpiarAntiguas();
      final pendientesRestantes = await _dao.contarPendientes();
      print('[SyncService] ✅ Sync completa. Pendientes: $pendientesRestantes');
    } catch (e) {
      print('[SyncService] ❌ Error: $e');
    } finally {
      _isSyncing = false;
    }
  }

  Future<void> forzarSync() async {
    await _sincronizar();
  }

  Future<bool> _enviarAlServidor(UbicacionModel ubicacion) async {
    try {
      final url = Uri.parse(_urlRecibir);
      final body = jsonEncode(ubicacion.toJson());

      final response = await http
          .post(
            url,
            headers: {"Content-Type": "application/json"},
            body: body,
          )
          .timeout(const Duration(seconds: 15));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['res'] == 'OK') return true;
        return false;
      }
      return false;
    } catch (e) {
      return false;
    }
  }

  bool get isSyncing => _isSyncing;
}

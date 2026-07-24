import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'dart:async';
import 'dart:io';

class ViajeService {
  final String viajesUrl;
  final String asignarUrl;
  final String serverUrl;

  ViajeService({
    required this.viajesUrl,
    required this.asignarUrl,
    required this.serverUrl,
  });

  void _log(String message) {
    if (kDebugMode) {
      print(message);
    }
  }

  // Obtener viajes pendientes
  Future<List<dynamic>> obtenerViajesPendientes() async {
    try {
      final url = Uri.parse(viajesUrl);
      final response = await http.get(url).timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['res'] == 'OK') {
          return data['viajes'] ?? [];
        }
      }
    } catch (e) {
      _log('❌ Error al obtener viajes: $e');
    }
    return [];
  }

  // Asignar un viaje
  Future<bool> asignarViaje(int viajeId, String movilId) async {
    try {
      final url = Uri.parse(asignarUrl);
      final body = jsonEncode({
        'viaje_id': viajeId,
        'movil_id': movilId,
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
          return true;
        } else {
          _log('❌ Error: ${data['msg']}');
          return false;
        }
      }
    } catch (e) {
      _log('❌ Error al asignar viaje: $e');
    }
    return false;
  }

  // Enviar ubicación
  Future<bool> sendLocation(
      String movilId, double lat, double lng, String status) async {
    try {
      final url = Uri.parse(serverUrl);
      final body = jsonEncode({
        'lat': lat,
        'lng': lng,
        'usuario_id': movilId,
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
        _log('✅ Enviado: $lat, $lng - Status: $status');
        return true;
      } else {
        _log('❌ Error: ${response.statusCode} - Status: $status');
        return false;
      }
    } on TimeoutException {
      _log('❌ Timeout');
      return false;
    } on SocketException {
      _log('❌ Error de red');
      return false;
    } catch (e) {
      _log('❌ Error: $e');
      return false;
    }
  }
}

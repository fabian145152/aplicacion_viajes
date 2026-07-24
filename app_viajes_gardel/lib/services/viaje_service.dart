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
      _log('📡 Obteniendo viajes de: $url');
      final response = await http.get(url).timeout(const Duration(seconds: 10));

      _log('📥 Status: ${response.statusCode}');
      _log('📥 Respuesta: ${response.body}');

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['res'] == 'OK') {
          final viajes = data['viajes'] ?? [];
          _log('📋 Viajes encontrados: ${viajes.length}');
          return viajes;
        } else {
          _log('❌ Error en respuesta: ${data['msg']}');
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
      final body = jsonEncode({'id': viajeId, 'movil': movilId});

      _log('📤 Asignando viaje $viajeId a móvil $movilId');
      _log('📤 URL: $url');
      _log('📤 Body: $body');

      final response = await http
          .post(url, headers: {"Content-Type": "application/json"}, body: body)
          .timeout(const Duration(seconds: 10));

      _log('📥 Status: ${response.statusCode}');
      _log('📥 Respuesta: ${response.body}');

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
    String movilId,
    double lat,
    double lng,
    String status,
  ) async {
    try {
      final url = Uri.parse(serverUrl);
      final body = jsonEncode({
        'movil': movilId,
        'lat': lat.toString(),
        'lng': lng.toString(),
        'status': status,
      });

      _log('📤 Enviando ubicación a: $url');
      _log('📤 Body: $body');

      final response = await http
          .post(url, headers: {"Content-Type": "application/json"}, body: body)
          .timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        _log('✅ Enviado: $lat, $lng - Status: $status');
        return true;
      } else {
        _log('❌ Error: ${response.statusCode} - Status: $status');
        _log('❌ Respuesta: ${response.body}');
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

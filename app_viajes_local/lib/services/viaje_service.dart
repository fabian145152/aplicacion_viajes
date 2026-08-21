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

  // En viaje_service.dart
  Future<List<dynamic>> obtenerViajesPendientes([String? movilId]) async {
    try {
      String urlFinal = viajesUrl;
      if (movilId != null && movilId.isNotEmpty) {
        urlFinal = '$viajesUrl?movil=$movilId';
      }

      final url = Uri.parse(urlFinal);
      _log(
          '📡 Obteniendo viajes${movilId != null ? " para móvil $movilId" : ""} desde: $url');

      final response = await http.get(url).timeout(const Duration(seconds: 10));

      _log('📥 Status: ${response.statusCode}');
      _log('📥 Respuesta: ${response.body}');

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['res'] == 'OK') {
          // 🔴 Verificar si hay un viaje activo
          if (data['tiene_viaje_activo'] == true) {
            _log('📋 Tiene viaje activo: ${data['viaje_activo']}');
            // Si tiene viaje activo, retornar lista vacía (o el viaje activo)
            return [];
          }

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

  Future<bool> asignarViaje(int viajeId, String movilId) async {
    try {
      final url = Uri.parse(asignarUrl);

      final body = jsonEncode({
        'viaje_id': viajeId,
        'movil_id': movilId,
      });

      _log('📤 Asignando viaje $viajeId a móvil $movilId');
      _log('📤 URL: $url');
      _log('📤 Body: $body');

      final response = await http
          .post(
            url,
            headers: {"Content-Type": "application/json"},
            body: body,
          )
          .timeout(const Duration(seconds: 10));

      _log('📥 Status: ${response.statusCode}');
      _log('📥 Respuesta: ${response.body}');

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['res'] == 'OK') {
          _log('✅ Viaje $viajeId asignado correctamente');
          return true;
        } else {
          _log('❌ Error del servidor: ${data['msg']}');
          return false;
        }
      }
      return false;
    } catch (e) {
      _log('❌ Error al asignar viaje: $e');
      return false;
    }
  }

  Future<bool> cambiarAEnCurso(int viajeId, String movilId) async {
    try {
      final url = Uri.parse(
          'http://192.168.0.225/aplicacion_viajes/app_viajes/php/01_mapeo/cambiar_a_en_curso.php');

      final body = jsonEncode({
        'viaje_id': viajeId,
        'movil_id': movilId,
      });

      _log('📤 Cambiando a En Curso: $body');

      final response = await http
          .post(
            url,
            headers: {"Content-Type": "application/json"},
            body: body,
          )
          .timeout(const Duration(seconds: 10));

      _log('📥 Respuesta: ${response.body}');

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['res'] == 'OK') {
          _log('✅ Viaje $viajeId en curso');
          return true;
        } else {
          _log('❌ Error: ${data['msg']}');
          return false;
        }
      }
      return false;
    } catch (e) {
      _log('❌ Error en cambiarAEnCurso: $e');
      return false;
    }
  }

  Future<bool> sendLocation(
      String movilId, double lat, double lng, String status) async {
    try {
      final url = Uri.parse(serverUrl);

      Map<String, String> datosJson = {
        'movil': movilId,
        'lat': lat.toString(),
        'lng': lng.toString(),
        'status': status,
      };

      final body = jsonEncode(datosJson);

      print('🔴🔴🔴 URL: $url');
      print('🔴🔴🔴 JSON A ENVIAR: $body');

      final response = await http
          .post(
            url,
            headers: {"Content-Type": "application/json"},
            body: body,
          )
          .timeout(const Duration(seconds: 10));

      print('🔴🔴🔴 RESPUESTA DEL SERVIDOR: ${response.body}');

      if (response.statusCode == 200) {
        return true;
      } else {
        print('❌ Error HTTP: ${response.statusCode}');
        return false;
      }
    } on TimeoutException {
      print('❌ Timeout');
      return false;
    } on SocketException {
      print('❌ Error de red');
      return false;
    } catch (e) {
      print('❌ Error general en sendLocation: $e');
      return false;
    }
  }
}

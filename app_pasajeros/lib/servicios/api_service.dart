import 'dart:convert';

import 'package:http/http.dart' as http;

class ApiService {
  // ⚠️ Ajustá esta URL a tu servidor
  static const String baseUrl =
      'http://192.168.0.225/aplicacion_viajes/app_viajes/php/00_administracion/pasajeros_de_calle';

  /// Registra un pasajero nuevo.
  static Future<Map<String, dynamic>> registrar({
    required String nombreApellido,
    required String email,
    required String password,
    required String celular,
  }) async {
    final url = Uri.parse('$baseUrl/registro_pasajero.php');
    final body = jsonEncode({
      'nombre_apellido': nombreApellido,
      'email': email,
      'password': password,
      'celular': celular,
    });

    final resp = await http
        .post(url, headers: {"Content-Type": "application/json"}, body: body)
        .timeout(const Duration(seconds: 15));

    if (resp.statusCode != 200) {
      return {
        'res': 'ERROR',
        'errores': ['Error ${resp.statusCode}'],
      };
    }
    return jsonDecode(resp.body);
  }

  /// Loguea un pasajero.
  static Future<Map<String, dynamic>> login({
    required String email,
    required String password,
  }) async {
    final url = Uri.parse('$baseUrl/login_pasajero.php');
    final body = jsonEncode({'email': email, 'password': password});

    final resp = await http
        .post(url, headers: {"Content-Type": "application/json"}, body: body)
        .timeout(const Duration(seconds: 15));

    if (resp.statusCode != 200) {
      return {'res': 'ERROR', 'msg': 'Error ${resp.statusCode}'};
    }
    return jsonDecode(resp.body);
  }

  /// Solicita recuperar la contraseña.
  static Future<Map<String, dynamic>> recuperarPassword({
    required String email,
  }) async {
    final url = Uri.parse('$baseUrl/recuperar_password.php');
    final body = jsonEncode({'email': email});

    final resp = await http
        .post(url, headers: {"Content-Type": "application/json"}, body: body)
        .timeout(const Duration(seconds: 15));

    if (resp.statusCode != 200) {
      return {'res': 'ERROR', 'msg': 'Error ${resp.statusCode}'};
    }
    return jsonDecode(resp.body);
  }

  /// Cambia la contraseña de un pasajero.
  static Future<Map<String, dynamic>> cambiarPassword({
    required String email,
    required String passwordActual,
    required String passwordNueva,
    required String passwordConfirmar,
  }) async {
    final url = Uri.parse('$baseUrl/cambiar_password.php');
    final body = jsonEncode({
      'email': email,
      'password_actual': passwordActual,
      'password_nueva': passwordNueva,
      'password_confirmar': passwordConfirmar,
    });

    final resp = await http
        .post(url, headers: {"Content-Type": "application/json"}, body: body)
        .timeout(const Duration(seconds: 15));

    if (resp.statusCode != 200) {
      return {'res': 'ERROR', 'msg': 'Error ${resp.statusCode}'};
    }
    return jsonDecode(resp.body);
  }

  /// Crea un viaje nuevo para un pasajero.
  static Future<Map<String, dynamic>> crearViaje({
    required int pasajeroId,
    required String direccion,
    required int cp,
  }) async {
    final url = Uri.parse('$baseUrl/crear_viaje.php');
    final body = jsonEncode({
      'pasajero_id': pasajeroId,
      'direccion': direccion,
      'cp': cp,
    });

    final resp = await http
        .post(url, headers: {"Content-Type": "application/json"}, body: body)
        .timeout(const Duration(seconds: 15));

    if (resp.statusCode != 200) {
      return {'res': 'ERROR', 'msg': 'Error ${resp.statusCode}'};
    }
    return jsonDecode(resp.body);
  }

  /// Lista los viajes de un pasajero.
  static Future<Map<String, dynamic>> listarViajes({
    required int pasajeroId,
  }) async {
    final url = Uri.parse(
      '$baseUrl/listar_viajes_pasajero.php?pasajero_id=$pasajeroId',
    );

    final resp = await http.get(url).timeout(const Duration(seconds: 15));

    if (resp.statusCode != 200) {
      return {'res': 'ERROR', 'msg': 'Error ${resp.statusCode}'};
    }
    return jsonDecode(resp.body);
  }
}

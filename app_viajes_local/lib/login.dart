import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'dart:io';
import 'dart:async';
import 'rast_gps.dart';

class LoginPage extends StatefulWidget {
  const LoginPage({super.key});

  @override
  State<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends State<LoginPage> {
  final TextEditingController _userController = TextEditingController();
  final TextEditingController _passwordController = TextEditingController();
  bool _isLoading = false;
  bool _obscurePassword = true;

  final List<String> _urlsToTry = [
    //"http://192.168.0.225/aplicacion_viajes/app_viajes/php/01_mapeo/login.php",
    "http://181.47.100.96:8081/aplicacion_viajes/app_viajes/php/01_mapeo/login.php",
  ];

  final String _actualizarLoginUrl =
      //"http://192.168.0.225/aplicacion_viajes/app_viajes/php/01_mapeo/actualizar_login.php";
      "http://181.47.100.96:8081/aplicacion_viajes/app_viajes/php/01_mapeo/actualizar_login.php";

  void _log(String message) {
    if (kDebugMode) {
      print('[Login] $message');
    }
  }

  Future<void> _actualizarEstadoLogin(String movil, int logeado) async {
    try {
      final url = Uri.parse(_actualizarLoginUrl);
      final body = jsonEncode({
        'movil': movil,
        'logeado': logeado,
      });

      _log('📤 Actualizando estado login...');
      _log('   Móvil: $movil');
      _log('   Estado: ${logeado == 1 ? "LOGUEADO" : "DESLOGUEADO"}');

      final response = await http
          .post(
            url,
            headers: {"Content-Type": "application/json"},
            body: body,
          )
          .timeout(const Duration(seconds: 5));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['res'] == 'OK') {
          _log('✅ Estado login actualizado correctamente');
        }
      }
    } catch (e) {
      _log('❌ Error al actualizar estado: $e');
    }
  }

  Future<void> _login() async {
    String username = _userController.text.trim();
    String password = _passwordController.text.trim();

    if (username.isEmpty || password.isEmpty) {
      _mostrarMensaje('Completa todos los campos');
      return;
    }

    setState(() {
      _isLoading = true;
    });

    try {
      String urlToUse = _urlsToTry[0];
      final url = Uri.parse(urlToUse);

      _log('🔄 Conectando a: $url');
      _log('👤 Usuario: $username');

      final response = await http
          .post(
            url,
            headers: {
              "Content-Type": "application/json",
              "Accept": "application/json",
            },
            body: jsonEncode({'user': username, 'clave': password}),
          )
          .timeout(const Duration(seconds: 15));

      _log('📥 Status: ${response.statusCode}');
      _log('📥 Respuesta: ${response.body}');

      if (response.statusCode == 200) {
        try {
          final data = jsonDecode(response.body);

          if (data['res'] == 'OK') {
            String movil = data['movil']?.toString() ?? '0';
            String nombre = data['nombre']?.toString() ?? '';
            String apellido = data['apellido']?.toString() ?? '';
            String nombreCompleto = '$nombre $apellido'.trim();

            if (nombreCompleto.isEmpty) {
              nombreCompleto = 'Chofer';
            }

            _log('👤 Nombre: $nombreCompleto');
            _log('📱 Móvil: $movil');

            await _actualizarEstadoLogin(movil, 1);

            if (!mounted) return;

            Navigator.pushReplacement(
              context,
              MaterialPageRoute(
                builder: (context) => BotonCoordenadas(
                  numeroMovil: movil,
                  nombreChofer: nombreCompleto,
                ),
              ),
            );
          } else {
            _mostrarMensaje(data['msg'] ?? 'Credenciales incorrectas');
          }
        } catch (e) {
          _log('❌ Error JSON: $e');
          _mostrarMensaje('Error al procesar respuesta del servidor');
        }
      } else {
        _mostrarMensaje('Error ${response.statusCode}');
      }
    } on SocketException catch (e) {
      _log('❌ SocketException: $e');
      _mostrarMensaje('❌ No se puede conectar al servidor');
    } on TimeoutException catch (e) {
      _log('❌ TimeoutException: $e');
      _mostrarMensaje('❌ Tiempo de espera agotado');
    } catch (e) {
      _log('❌ Error: $e');
      _mostrarMensaje('Error inesperado: $e');
    } finally {
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  void _mostrarMensaje(String mensaje) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          mensaje,
          style: const TextStyle(fontSize: 14),
        ),
        backgroundColor: Colors.redAccent,
        duration: const Duration(seconds: 5),
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  @override
  void dispose() {
    _userController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Iniciar Sesión'),
        backgroundColor: Colors.blue,
        foregroundColor: Colors.white,
        elevation: 2,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24.0),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          crossAxisAlignment: CrossAxisAlignment.center,
          children: [
            const Icon(
              Icons.gps_fixed,
              size: 80,
              color: Colors.blue,
            ),
            const SizedBox(height: 10),
            const Text(
              'Despacho de viajes',
              style: TextStyle(
                fontSize: 22,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 40),
            TextField(
              controller: _userController,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(
                labelText: 'Usuario (DNI)',
                border: OutlineInputBorder(),
                prefixIcon: Icon(Icons.person),
                hintText: 'Ej: 12345678',
              ),
              enabled: !_isLoading,
            ),
            const SizedBox(height: 16),
            TextField(
              controller: _passwordController,
              obscureText: _obscurePassword,
              keyboardType: TextInputType.number,
              decoration: InputDecoration(
                labelText: 'Contraseña',
                border: const OutlineInputBorder(),
                prefixIcon: const Icon(Icons.lock),
                hintText: 'Ej: 123456',
                suffixIcon: IconButton(
                  icon: Icon(
                    _obscurePassword ? Icons.visibility : Icons.visibility_off,
                  ),
                  onPressed: () {
                    setState(() {
                      _obscurePassword = !_obscurePassword;
                    });
                  },
                ),
              ),
              enabled: !_isLoading,
            ),
            const SizedBox(height: 30),
            SizedBox(
              width: double.infinity,
              height: 50,
              child: _isLoading
                  ? const Center(child: CircularProgressIndicator())
                  : ElevatedButton(
                      onPressed: _login,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: Colors.blue,
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(10),
                        ),
                      ),
                      child: const Text(
                        'INGRESAR',
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
            ),
            const SizedBox(height: 20),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.grey[100],
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: Colors.grey[300]!),
              ),
              child: const Column(
                children: [
                  Text(
                    '📡 Información de conexión',
                    style: TextStyle(
                      fontWeight: FontWeight.bold,
                      fontSize: 13,
                    ),
                  ),
                  SizedBox(height: 5),
                  Text(
                    'Servidor: Carlos Gardel',
                    style: TextStyle(fontSize: 12),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 30),
          ],
        ),
      ),
    );
  }
}

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

  // Lista de URLs para probar - ACTUALIZADA CON NUEVA IP
  final List<String> _urlsToTry = [
    "http://181.47.100.96/app_viajes/php/01_mapeo/login.php",
    "http://181.47.100.96/php/01_mapeo/login.php",
    "http://181.47.100.96/login.php",
  ];

  // Función para debug
  void _log(String message) {
    if (kDebugMode) {
      print(message);
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
      // Usar la primera URL de la lista
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
            // Obtener nombre y apellido de la respuesta
            String nombre = data['nombre']?.toString() ?? '';
            String apellido = data['apellido']?.toString() ?? '';
            String nombreCompleto = '$nombre $apellido'.trim();

            // Si no viene nombre y apellido, usar un valor por defecto
            if (nombreCompleto.isEmpty) {
              nombreCompleto = 'Chofer';
            }

            _log('👤 Nombre: $nombreCompleto');

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
      } else if (response.statusCode == 404) {
        _mostrarMensaje('❌ Archivo login.php no encontrado');
        _mostrarDialogoAyuda();
      } else {
        _mostrarMensaje(
          'Error ${response.statusCode}: ${response.reasonPhrase}',
        );
      }
    } on SocketException catch (e) {
      // Error de conexión de red
      _log('❌ SocketException: $e');
      _mostrarMensaje(
        '❌ No se puede conectar al servidor\nVerifica la IP y que el servidor esté encendido',
      );
    } on TimeoutException catch (e) {
      // Timeout
      _log('❌ TimeoutException: $e');
      _mostrarMensaje('❌ Tiempo de espera agotado\nEl servidor no responde');
    } catch (e) {
      // Cualquier otro error
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

  void _probarConexion() async {
    setState(() => _isLoading = true);
    try {
      final url = Uri.parse(_urlsToTry[0]);
      final response = await http.head(url).timeout(const Duration(seconds: 5));
      if (response.statusCode == 200) {
        _mostrarMensajeExito('✅ Conexión exitosa al servidor');
      } else {
        _mostrarMensaje('❌ Error ${response.statusCode}');
      }
    } catch (e) {
      _mostrarMensaje('❌ No se puede conectar al servidor');
    }
    setState(() => _isLoading = false);
  }

  void _mostrarMensajeExito(String mensaje) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(mensaje),
        backgroundColor: Colors.green,
        duration: const Duration(seconds: 3),
      ),
    );
  }

  void _mostrarDialogoAyuda() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('⚠️ Error 404 - Archivo no encontrado'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('El servidor no encuentra el archivo login.php'),
            const SizedBox(height: 10),
            const Text('Verifica:'),
            const SizedBox(height: 5),
            const Text('1. Que XAMPP esté corriendo (Apache)'),
            const Text('2. La IP del servidor sea correcta'),
            const Text('3. La ruta del archivo existe'),
            const Text('4. Firewall no esté bloqueando'),
            const SizedBox(height: 10),
            const Text(
              'Ruta esperada:',
              style: TextStyle(fontWeight: FontWeight.bold),
            ),
            Container(
              padding: const EdgeInsets.all(8),
              color: Colors.grey[200],
              child: const Text(
                'C:\\xampp\\htdocs\\app_viajes\\php\\01_mapeo\\login.php',
                style: TextStyle(fontSize: 11, fontFamily: 'monospace'),
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Entendido'),
          ),
        ],
      ),
    );
  }

  void _mostrarMensaje(String mensaje) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(mensaje, style: const TextStyle(fontSize: 14)),
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
      body: Padding(
        padding: const EdgeInsets.all(24.0),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.gps_fixed, size: 80, color: Colors.blue),
            const SizedBox(height: 10),
            const Text(
              'Sistema de Rastreo GPS',
              style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 40),
            TextField(
              controller: _userController,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(
                labelText: 'Usuario',
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
            const SizedBox(height: 10),
            SizedBox(
              width: double.infinity,
              height: 40,
              child: OutlinedButton(
                onPressed: _isLoading ? null : _probarConexion,
                style: OutlinedButton.styleFrom(
                  side: BorderSide(color: Colors.blue[200]!),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(10),
                  ),
                ),
                child: const Text(
                  '🔍 PROBAR CONEXIÓN',
                  style: TextStyle(fontSize: 14),
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
                    style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
                  ),
                  SizedBox(height: 5),
                  Text(
                    'Servidor: 181.47.100.96',
                    style: TextStyle(fontSize: 12),
                  ),
                  Text(
                    'Ruta: /app_viajes/php/01_mapeo/',
                    style: TextStyle(fontSize: 12),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

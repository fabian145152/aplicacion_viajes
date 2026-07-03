import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'rast_gps.dart'; // Importamos tu archivo de destino

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

  Future<void> _login() async {
    String username = _userController.text.trim();
    String password = _passwordController.text.trim();

    if (username.isEmpty || password.isEmpty) {
      _mostrarMensaje('Por favor, completa todos los campos.');
      return;
    }

    setState(() {
      _isLoading = true;
    });

    try {
      final url = Uri.parse(
        'http://192.168.0.225/app_viajes/php/01_mapeo/login.php',
      );

      final respuesta = await http
          .post(
            url,
            headers: {"Content-Type": "application/json; charset=UTF-8"},
            body: jsonEncode({'user': username, 'clave': password}),
          )
          .timeout(const Duration(seconds: 5));

      if (respuesta.statusCode == 200) {
        final datos = jsonDecode(respuesta.body);

        if (datos['res'] == 'OK') {
          if (!mounted) return;

          //--------------------------------------------------

          //--------------------------------------------------
          // Convertimos el móvil a String por seguridad (sea INT o STRING en la BD)
          String movilDetectado = datos['movil']?.toString() ?? 'Sin Móvil';

          // Navegamos pasando el número de móvil dinámico
          Navigator.pushReplacement(
            context,
            MaterialPageRoute(
              builder: (context) =>
                  BotonCoordenadas(numeroMovil: movilDetectado),
            ),
          );
        } else {
          _mostrarMensaje(datos['msg'] ?? 'Usuario o contraseña incorrectos');
        }
      } else {
        _mostrarMensaje('Error de servidor: Status ${respuesta.statusCode}');
      }
    } catch (e) {
      _mostrarMensaje(
        'No se pudo conectar con el servidor. Revisa tu red o IP.',
      );
      print('Error de Login: $e');
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
      SnackBar(content: Text(mensaje), backgroundColor: Colors.redAccent),
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
      appBar: AppBar(title: const Text('Iniciar Sesión')),
      body: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            TextField(
              controller: _userController,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(
                labelText: 'Usuario',
                border: OutlineInputBorder(),
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
            const SizedBox(height: 24),
            _isLoading
                ? const CircularProgressIndicator()
                : ElevatedButton(
                    onPressed: _login,
                    style: ElevatedButton.styleFrom(
                      minimumSize: const Size.fromHeight(50),
                    ),
                    child: const Text(
                      'Ingresar',
                      style: TextStyle(fontSize: 18),
                    ),
                  ),
          ],
        ),
      ),
    );
  }
}

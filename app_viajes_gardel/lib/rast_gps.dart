import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:geolocator/geolocator.dart';
import 'dart:async';
import 'dart:convert';
import 'login.dart'; // <-- Importamos LoginPage para poder regresar al cerrar sesión

class BotonCoordenadas extends StatefulWidget {
  final String numeroMovil;

  const BotonCoordenadas({super.key, required this.numeroMovil});

  @override
  State<BotonCoordenadas> createState() => _BotonCoordenadasState();
}

class _BotonCoordenadasState extends State<BotonCoordenadas> {
  bool estaActivo = false;
  Timer? _timer;
  late TextEditingController _idController;

  @override
  void initState() {
    super.initState();
    _idController = TextEditingController(text: widget.numeroMovil);
  }

  Future<void> enviarDatos(String status) async {
    try {
      Position position = await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.high,
      );

      final url = Uri.parse(
        'http://181.47.100.96:8081/app_viajes/php/01_mapeo/recibir.php',
      );

      final cuerpoJson = jsonEncode({
        'lat': position.latitude,
        'lng': position.longitude,
        'usuario_id': _idController.text,
        'status': status,
      });

      final respuesta = await http.post(
        url,
        headers: {"Content-Type": "application/json; charset=UTF-8"},
        body: cuerpoJson,
      );

      if (respuesta.statusCode == 200) {
        print("Enviado: ${respuesta.body}");
      } else {
        print("Error en servidor: Status ${respuesta.statusCode}");
      }
    } catch (e) {
      print("Error de conexión: $e");
    }
  }

  void controlarCiclo(bool activado) async {
    if (activado) {
      LocationPermission permiso = await Geolocator.checkPermission();

      if (permiso == LocationPermission.denied) {
        permiso = await Geolocator.requestPermission();
        if (permiso == LocationPermission.denied) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Permiso de ubicación denegado.')),
          );
          setState(() {
            estaActivo = false;
          });
          return;
        }
      }

      if (permiso == LocationPermission.deniedForever) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Habilite la ubicación desde ajustes.')),
        );
        setState(() {
          estaActivo = false;
        });
        return;
      }

      enviarDatos('activo');
      _timer = Timer.periodic(const Duration(seconds: 5), (timer) {
        enviarDatos('activo');
      });
    } else {
      _timer?.cancel();
      print("Iniciando ráfaga de cierre...");
      await enviarDatos('inactivo');
      await Future.delayed(const Duration(milliseconds: 500));
      print("Proceso finalizado.");
    }
  }

  // --- NUEVA FUNCIÓN PARA LOGOUT ---
  void _cerrarSesion() async {
    // 1. Si estaba transmitiendo, detenemos el timer y enviamos estado 'inactivo'
    if (estaActivo) {
      _timer?.cancel();
      await enviarDatos('inactivo');
    }

    if (!mounted) return;

    // 2. Navegamos al Login reemplazando la vista para que no pueda volver atrás con el botón físico
    Navigator.pushReplacement(
      context,
      MaterialPageRoute(builder: (context) => const LoginPage()),
    );
  }

  @override
  void dispose() {
    _timer?.cancel();
    _idController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Móvil N°: ${widget.numeroMovil}'),
        centerTitle: true,
        // Agregamos el botón de cerrar sesión en la parte derecha del AppBar
        actions: [
          IconButton(
            icon: const Icon(Icons.logout, color: Colors.redAccent),
            tooltip: 'Cerrar Sesión',
            onPressed: _cerrarSesion,
          ),
        ],
      ),
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 40.0),
              child: TextField(
                controller: _idController,
                textAlign: TextAlign.center,
                style: const TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                ),
                decoration: const InputDecoration(
                  labelText: "Identificador del Móvil",
                  border: OutlineInputBorder(),
                ),
                readOnly: true,
              ),
            ),
            const SizedBox(height: 40),
            GestureDetector(
              onTap: () {
                setState(() {
                  estaActivo = !estaActivo;
                  controlarCiclo(estaActivo);
                });
              },
              child: AnimatedContainer(
                duration: const Duration(milliseconds: 300),
                width: 220,
                height: 220,
                decoration: BoxDecoration(
                  color: estaActivo ? Colors.redAccent : Colors.green,
                  shape: BoxShape.circle,
                  boxShadow: [
                    BoxShadow(
                      color: (estaActivo ? Colors.redAccent : Colors.green)
                          .withOpacity(0.4),
                      blurRadius: 15,
                      offset: const Offset(0, 8),
                    ),
                  ],
                ),
                child: Center(
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(
                        estaActivo ? Icons.stop_circle : Icons.play_arrow,
                        color: Colors.white,
                        size: 30,
                      ),
                      const SizedBox(width: 10),
                      Text(
                        estaActivo ? 'DETENER' : 'INICIAR',
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 20,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
            if (estaActivo)
              const Padding(
                padding: EdgeInsets.only(top: 25),
                child: Text(
                  "Transmitiendo coordenadas en tiempo real...",
                  style: TextStyle(
                    fontStyle: FontStyle.italic,
                    color: Colors.grey,
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }
}

import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';
import 'package:http/http.dart' as http;

class VerMapaPage extends StatefulWidget {
  final String direccion;
  const VerMapaPage({super.key, required this.direccion});

  @override
  State<VerMapaPage> createState() => _VerMapaPageState();
}

class _VerMapaPageState extends State<VerMapaPage> {
  final MapController _mapController = MapController();

  LatLng? _punto;
  bool _cargando = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _buscarCoordenadas();
  }

  Future<void> _buscarCoordenadas() async {
    setState(() {
      _cargando = true;
      _error = null;
    });

    try {
      // Nominatim: convertir dirección a coordenadas
      final query = Uri.encodeComponent(widget.direccion);
      final url = Uri.parse(
        'https://nominatim.openstreetmap.org/search?q=$query&format=json&limit=1',
      );

      final resp = await http
          .get(
            url,
            headers: {
              // Nominatim pide un User-Agent para identificar la app
              'User-Agent': 'AppPasajeros/1.0',
            },
          )
          .timeout(const Duration(seconds: 15));

      if (resp.statusCode != 200) {
        setState(() {
          _error = 'Error al buscar la dirección (${resp.statusCode})';
          _cargando = false;
        });
        return;
      }

      final data = jsonDecode(resp.body) as List;
      if (data.isEmpty) {
        setState(() {
          _error =
              'No se encontró la dirección.\nProbá escribiendo más detalles.';
          _cargando = false;
        });
        return;
      }

      final lat = double.parse(data[0]['lat'].toString());
      final lng = double.parse(data[0]['lon'].toString());

      setState(() {
        _punto = LatLng(lat, lng);
        _cargando = false;
      });
    } catch (e) {
      setState(() {
        _error = 'Error de conexión:\n$e';
        _cargando = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Verificar dirección'),
        backgroundColor: Colors.blue,
        foregroundColor: Colors.white,
      ),
      body: Stack(
        children: [
          // ============================================
          // MAPA
          // ============================================
          if (_punto != null)
            FlutterMap(
              mapController: _mapController,
              options: MapOptions(
                initialCenter: _punto!,
                initialZoom: 16,
                minZoom: 5,
                maxZoom: 19,
              ),
              children: [
                // Capa base (OpenStreetMap)
                TileLayer(
                  urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                  userAgentPackageName: 'com.example.app_pasajeros',
                ),

                // Marcador en la dirección
                MarkerLayer(
                  markers: [
                    Marker(
                      point: _punto!,
                      width: 50,
                      height: 50,
                      child: const Icon(
                        Icons.location_on,
                        color: Colors.red,
                        size: 50,
                      ),
                    ),
                  ],
                ),
              ],
            ),

          // ============================================
          // SPINNER MIENTRAS CARGA
          // ============================================
          if (_cargando)
            Container(
              color: Colors.white.withValues(alpha: 0.9),
              child: const Center(
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    CircularProgressIndicator(),
                    SizedBox(height: 15),
                    Text(
                      'Buscando dirección...',
                      style: TextStyle(fontSize: 14, color: Colors.grey),
                    ),
                  ],
                ),
              ),
            ),

          // ============================================
          // MENSAJE DE ERROR
          // ============================================
          if (_error != null)
            Center(
              child: Padding(
                padding: const EdgeInsets.all(30),
                child: Card(
                  elevation: 4,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Padding(
                    padding: const EdgeInsets.all(20),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Icon(
                          Icons.error_outline,
                          color: Colors.orange,
                          size: 50,
                        ),
                        const SizedBox(height: 15),
                        Text(
                          _error!,
                          textAlign: TextAlign.center,
                          style: const TextStyle(fontSize: 14),
                        ),
                        const SizedBox(height: 20),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            OutlinedButton(
                              onPressed: _buscarCoordenadas,
                              child: const Text('Reintentar'),
                            ),
                            const SizedBox(width: 10),
                            ElevatedButton(
                              onPressed: () => Navigator.pop(context),
                              style: ElevatedButton.styleFrom(
                                backgroundColor: Colors.blue,
                                foregroundColor: Colors.white,
                              ),
                              child: const Text('Cerrar'),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ),

          // ============================================
          // BOTÓN CERRAR (abajo)
          // ============================================
          if (_punto != null)
            Positioned(
              bottom: 20,
              left: 20,
              right: 20,
              child: SafeArea(
                child: ElevatedButton.icon(
                  onPressed: () => Navigator.pop(context),
                  icon: const Icon(Icons.check),
                  label: const Text(
                    'CERRAR MAPA',
                    style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                  ),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.green,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                    elevation: 6,
                  ),
                ),
              ),
            ),

          // ============================================
          // BOTONES DE ZOOM (arriba a la derecha)
          // ============================================
          if (_punto != null)
            Positioned(
              top: 15,
              right: 15,
              child: Column(
                children: [
                  _botonZoom(Icons.add, () {
                    final z = _mapController.camera.zoom;
                    if (z < 19) _mapController.move(_punto!, z + 1);
                  }),
                  const SizedBox(height: 8),
                  _botonZoom(Icons.remove, () {
                    final z = _mapController.camera.zoom;
                    if (z > 5) _mapController.move(_punto!, z - 1);
                  }),
                ],
              ),
            ),
        ],
      ),
    );
  }

  Widget _botonZoom(IconData icono, VoidCallback onTap) {
    return Material(
      color: Colors.white,
      elevation: 3,
      borderRadius: BorderRadius.circular(8),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(8),
        child: Padding(
          padding: const EdgeInsets.all(10),
          child: Icon(icono, color: Colors.black87, size: 22),
        ),
      ),
    );
  }
}

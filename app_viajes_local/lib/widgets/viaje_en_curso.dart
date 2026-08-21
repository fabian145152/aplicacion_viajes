import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'dart:async';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';
import 'package:geolocator/geolocator.dart';
import 'package:url_launcher/url_launcher.dart';
import 'finalizar_viaje.dart';

class ViajeEnCursoPage extends StatefulWidget {
  final Map<String, dynamic> viaje;
  final VoidCallback? onViajeCancelado;
  final String numeroMovil;

  const ViajeEnCursoPage({
    super.key,
    required this.viaje,
    this.onViajeCancelado,
    required this.numeroMovil,
  });

  @override
  State<ViajeEnCursoPage> createState() => _ViajeEnCursoPageState();
}

class _ViajeEnCursoPageState extends State<ViajeEnCursoPage> {
  bool _cargandoRuta = true;
  String _distancia = '';
  String _tiempo = '';
  final List<Map<String, dynamic>> _puntosRuta = [];
  List<LatLng> _coordenadasRuta = [];
  bool _errorRuta = false;
  LatLng? _origen;
  LatLng? _destino;
  LatLng? _miUbicacion;
  bool _ubicacionCargada = false;
  String _errorUbicacion = '';
  bool _infoExpandida = false;
  bool _viajeCancelado = false;
  bool _cerrando = false;
  bool _enCurso = false;
  bool _cambiandoEstado = false;

  final MapController _mapController = MapController();
  Timer? _verificadorEstado;

  @override
  void initState() {
    super.initState();
    _calcularRuta();
    _iniciarSeguimientoUbicacion();
    _iniciarVerificadorEstado();
    _verificarEstadoInicial();
  }

  @override
  void dispose() {
    _verificadorEstado?.cancel();
    super.dispose();
  }

  Future<void> _verificarEstadoInicial() async {
    try {
      final viajeId = widget.viaje['id'];
      final url = Uri.parse(
          'http://192.168.0.225/aplicacion_viajes/app_viajes/php/01_mapeo/obtener_viaje_estado.php?id=$viajeId');

      final response = await http.get(url).timeout(const Duration(seconds: 5));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['res'] == 'OK') {
          final estado = data['estado'] ?? '';
          setState(() {
            _enCurso = estado == 'En Curso';
          });
        }
      }
    } catch (e) {
      if (kDebugMode) print('Error verificando estado inicial: $e');
    }
  }

  void _iniciarVerificadorEstado() {
    _verificadorEstado =
        Timer.periodic(const Duration(seconds: 5), (timer) async {
      if (_viajeCancelado || _cerrando) return;

      try {
        final viajeId = widget.viaje['id'];
        final url = Uri.parse(
            'http://192.168.0.225/aplicacion_viajes/app_viajes/php/01_mapeo/obtener_viaje_estado.php?id=$viajeId');

        final response =
            await http.get(url).timeout(const Duration(seconds: 5));

        if (response.statusCode == 200) {
          final data = jsonDecode(response.body);
          if (data['res'] == 'OK') {
            final estado = data['estado'] ?? '';
            final asignadoA = data['asignado_a'] ?? '';

            if (estado == 'Pendiente' ||
                estado == 'Cancelado' ||
                (asignadoA.isEmpty ||
                    asignadoA == '0' ||
                    asignadoA == 'NULL')) {
              if (mounted && !_viajeCancelado && !_cerrando) {
                setState(() {
                  _viajeCancelado = true;
                });
                _mostrarMensajeCancelacion();
              }
            }

            setState(() {
              _enCurso = estado == 'En Curso';
            });
          }
        }
      } catch (e) {
        if (kDebugMode) print('Error verificando estado: $e');
      }
    });
  }

  void _mostrarMensajeCancelacion() {
    if (_cerrando) return;
    _cerrando = true;

    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => AlertDialog(
        title: const Row(
          children: [
            Icon(Icons.cancel, color: Colors.red, size: 28),
            SizedBox(width: 10),
            Text(
              'Viaje Cancelado',
              style: TextStyle(
                fontWeight: FontWeight.bold,
                fontSize: 20,
              ),
            ),
          ],
        ),
        content: const Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'El viaje ha sido desasignado desde el servidor.',
              style: TextStyle(fontSize: 16),
            ),
            SizedBox(height: 8),
            Text(
              'Volviendo a la lista de viajes...',
              style: TextStyle(
                fontSize: 14,
                color: Colors.grey,
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () {
              Navigator.of(context).pop();
              _volverALista();
            },
            child: const Text('ENTENDIDO'),
          ),
        ],
      ),
    );

    Future.delayed(const Duration(seconds: 3), () {
      if (mounted) {
        Navigator.of(context, rootNavigator: true).pop();
        _volverALista();
      }
    });
  }

  void _volverALista() {
    if (_cerrando) return;
    _cerrando = true;

    if (widget.onViajeCancelado != null) {
      widget.onViajeCancelado!();
    }

    if (mounted) {
      Navigator.of(context, rootNavigator: true).pop();
    }
  }

  Future<bool> _cambiarAEnCurso() async {
    if (_cambiandoEstado) return false;
    setState(() {
      _cambiandoEstado = true;
    });

    try {
      final viajeId = widget.viaje['id'];
      final url = Uri.parse(
          'http://192.168.0.225/aplicacion_viajes/app_viajes/php/01_mapeo/cambiar_a_en_curso.php');

      final body = jsonEncode({
        'viaje_id': viajeId,
        'movil_id': widget.numeroMovil,
      });

      if (kDebugMode) {
        print('📤 Cambiando a En Curso: $body');
      }

      final response = await http
          .post(
            url,
            headers: {"Content-Type": "application/json"},
            body: body,
          )
          .timeout(const Duration(seconds: 10));

      if (kDebugMode) {
        print('📥 Respuesta: ${response.body}');
      }

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['res'] == 'OK') {
          setState(() {
            _enCurso = true;
          });
          return true;
        } else {
          if (kDebugMode) print('❌ Error: ${data['msg']}');
          return false;
        }
      }
      return false;
    } catch (e) {
      if (kDebugMode) print('❌ Error cambiando a En Curso: $e');
      return false;
    } finally {
      if (mounted) {
        setState(() {
          _cambiandoEstado = false;
        });
      }
    }
  }

  Future<void> _abrirWaze() async {
    final String origen = widget.viaje['direccion_origen']?.toString() ?? '';

    if (origen.isEmpty || origen == 'Sin origen') {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('⚠️ No hay dirección de origen disponible'),
          backgroundColor: Colors.orange,
          duration: Duration(seconds: 2),
        ),
      );
      return;
    }

    final String origenLat = widget.viaje['origen_lat']?.toString() ?? '';
    final String origenLng = widget.viaje['origen_lng']?.toString() ?? '';

    try {
      String url;

      if (origenLat.isNotEmpty &&
          origenLng.isNotEmpty &&
          double.tryParse(origenLat) != 0.0 &&
          double.tryParse(origenLng) != 0.0) {
        final String wazeNative =
            'waze://?ll=${origenLat},${origenLng}&navigate=yes';
        final Uri wazeUri = Uri.parse(wazeNative);

        if (await canLaunchUrl(wazeUri)) {
          await launchUrl(wazeUri, mode: LaunchMode.externalApplication);
          return;
        }

        url = 'https://waze.com/ul?ll=${origenLat},${origenLng}&navigate=yes';
      } else {
        final String direccionCodificada = Uri.encodeComponent(origen);

        final String wazeNative =
            'waze://?q=${direccionCodificada}&navigate=yes';
        final Uri wazeUri = Uri.parse(wazeNative);

        if (await canLaunchUrl(wazeUri)) {
          await launchUrl(wazeUri, mode: LaunchMode.externalApplication);
          return;
        }

        url = 'https://waze.com/ul?q=${direccionCodificada}&navigate=yes';
      }

      final Uri uri = Uri.parse(url);
      if (await canLaunchUrl(uri)) {
        await launchUrl(uri, mode: LaunchMode.externalApplication);
      } else {
        final String webUrl =
            'https://www.waze.com/ul?q=${Uri.encodeComponent(origen)}';
        if (await canLaunchUrl(Uri.parse(webUrl))) {
          await launchUrl(Uri.parse(webUrl),
              mode: LaunchMode.externalApplication);
        }
      }
    } catch (e) {
      if (kDebugMode) print('Error abriendo Waze: $e');
    }
  }

  Future<void> _abrirGoogleMaps() async {
    final String origen = widget.viaje['direccion_origen']?.toString() ?? '';

    if (origen.isEmpty || origen == 'Sin origen') {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('⚠️ No hay dirección de origen disponible'),
          backgroundColor: Colors.orange,
          duration: Duration(seconds: 2),
        ),
      );
      return;
    }

    final String origenLat = widget.viaje['origen_lat']?.toString() ?? '';
    final String origenLng = widget.viaje['origen_lng']?.toString() ?? '';

    try {
      String url;

      if (origenLat.isNotEmpty &&
          origenLng.isNotEmpty &&
          double.tryParse(origenLat) != 0.0 &&
          double.tryParse(origenLng) != 0.0) {
        url =
            'https://www.google.com/maps/dir/?api=1&destination=${origenLat},${origenLng}&travelmode=driving';
      } else {
        final String direccionCodificada = Uri.encodeComponent(origen);
        url =
            'https://www.google.com/maps/dir/?api=1&destination=${direccionCodificada}&travelmode=driving';
      }

      final Uri uri = Uri.parse(url);

      if (await canLaunchUrl(uri)) {
        await launchUrl(uri, mode: LaunchMode.externalApplication);
      }
    } catch (e) {
      if (kDebugMode) print('Error abriendo Google Maps: $e');
    }
  }

  Future<void> _iniciarSeguimientoUbicacion() async {
    try {
      LocationPermission permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
        if (permission == LocationPermission.denied) {
          setState(() {
            _errorUbicacion = 'Permiso de ubicación denegado';
          });
          return;
        }
      }
      if (permission == LocationPermission.deniedForever) {
        setState(() {
          _errorUbicacion = 'Permiso de ubicación denegado permanentemente';
        });
        return;
      }

      bool serviceEnabled = await Geolocator.isLocationServiceEnabled();
      if (!serviceEnabled) {
        setState(() {
          _errorUbicacion = 'GPS desactivado. Actívalo para ver tu ubicación.';
        });
        return;
      }

      final position = await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.high,
        timeLimit: const Duration(seconds: 10),
      );

      if (kDebugMode) {
        print(
            '📍 Ubicación obtenida: ${position.latitude}, ${position.longitude}');
      }

      setState(() {
        _miUbicacion = LatLng(position.latitude, position.longitude);
        _ubicacionCargada = true;
        _errorUbicacion = '';
      });

      WidgetsBinding.instance.addPostFrameCallback((_) {
        _centrarEnMiUbicacion();
      });

      Geolocator.getPositionStream(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.high,
          distanceFilter: 10,
        ),
      ).listen((Position position) {
        if (mounted && !_viajeCancelado) {
          if (kDebugMode) {
            print(
                '📍 Ubicación actualizada: ${position.latitude}, ${position.longitude}');
          }
          setState(() {
            _miUbicacion = LatLng(position.latitude, position.longitude);
            _ubicacionCargada = true;
            _errorUbicacion = '';
          });
        }
      });
    } catch (e) {
      if (kDebugMode) {
        print('❌ Error obteniendo ubicación: $e');
      }
      setState(() {
        _errorUbicacion = 'Error al obtener ubicación: $e';
      });
    }
  }

  Future<void> _calcularRuta() async {
    try {
      final origenLat =
          double.tryParse(widget.viaje['origen_lat']?.toString() ?? '');
      final origenLng =
          double.tryParse(widget.viaje['origen_lng']?.toString() ?? '');
      final destinoLat =
          double.tryParse(widget.viaje['destino_lat']?.toString() ?? '');
      final destinoLng =
          double.tryParse(widget.viaje['destino_lng']?.toString() ?? '');

      String origenDir = widget.viaje['direccion_origen']?.toString() ?? '';
      String destinoDir = widget.viaje['direccion_destino']?.toString() ?? '';

      if ((origenLat == null ||
              origenLng == null ||
              origenLat == 0.0 ||
              origenLng == 0.0) &&
          origenDir.isNotEmpty) {
        final geoOrigen = await _geocodificar(origenDir);
        if (geoOrigen != null) {
          _puntosRuta.add({
            'lat': double.parse(geoOrigen['lat']),
            'lng': double.parse(geoOrigen['lon'])
          });
        }
      } else if (origenLat != null && origenLng != null) {
        _puntosRuta.add({'lat': origenLat, 'lng': origenLng});
      }

      if ((destinoLat == null ||
              destinoLng == null ||
              destinoLat == 0.0 ||
              destinoLng == 0.0) &&
          destinoDir.isNotEmpty) {
        final geoDestino = await _geocodificar(destinoDir);
        if (geoDestino != null) {
          _puntosRuta.add({
            'lat': double.parse(geoDestino['lat']),
            'lng': double.parse(geoDestino['lon'])
          });
        }
      } else if (destinoLat != null && destinoLng != null) {
        _puntosRuta.add({'lat': destinoLat, 'lng': destinoLng});
      }

      if (_puntosRuta.length == 2) {
        _origen = LatLng(_puntosRuta[0]['lat'], _puntosRuta[0]['lng']);
        _destino = LatLng(_puntosRuta[1]['lat'], _puntosRuta[1]['lng']);
        await _obtenerRutaDesdeOSRM();
      } else {
        setState(() {
          _errorRuta = true;
          _cargandoRuta = false;
        });
      }
    } catch (e) {
      setState(() {
        _errorRuta = true;
        _cargandoRuta = false;
      });
      if (kDebugMode) print('Error calculando ruta: $e');
    }
  }

  Future<Map<String, dynamic>?> _geocodificar(String direccion) async {
    try {
      final url = Uri.parse(
          'https://nominatim.openstreetmap.org/search?format=json&q=${Uri.encodeComponent(direccion + ', Buenos Aires, Argentina')}&limit=1');
      final response = await http.get(url).timeout(const Duration(seconds: 5));
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data.isNotEmpty) {
          return {
            'lat': data[0]['lat'],
            'lon': data[0]['lon'],
          };
        }
      }
      return null;
    } catch (e) {
      if (kDebugMode) print('Error geocodificando: $e');
      return null;
    }
  }

  Future<void> _obtenerRutaDesdeOSRM() async {
    try {
      final origen = _puntosRuta[0];
      final destino = _puntosRuta[1];

      final url = Uri.parse('https://router.project-osrm.org/route/v1/driving/'
          '${origen['lng']},${origen['lat']};${destino['lng']},${destino['lat']}'
          '?overview=full&geometries=geojson');

      final response = await http.get(url).timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['routes'] != null && data['routes'].isNotEmpty) {
          final route = data['routes'][0];
          final geometry = route['geometry'];

          List<LatLng> coordenadas = [];
          if (geometry != null && geometry['coordinates'] != null) {
            for (var coord in geometry['coordinates']) {
              coordenadas.add(LatLng(coord[1], coord[0]));
            }
          }

          final distanciaKm = (route['distance'] / 1000).toStringAsFixed(1);
          final tiempoMin = (route['duration'] / 60).round();

          setState(() {
            _coordenadasRuta = coordenadas;
            _distancia = '$distanciaKm km';
            _tiempo = '$tiempoMin min';
            _cargandoRuta = false;
            _errorRuta = false;
          });
        } else {
          setState(() {
            _errorRuta = true;
            _cargandoRuta = false;
          });
        }
      } else {
        setState(() {
          _errorRuta = true;
          _cargandoRuta = false;
        });
      }
    } catch (e) {
      setState(() {
        _errorRuta = true;
        _cargandoRuta = false;
      });
      if (kDebugMode) print('Error obteniendo ruta: $e');
    }
  }

  void _centrarEnMiUbicacion() {
    if (_miUbicacion != null && !_viajeCancelado) {
      try {
        _mapController.move(_miUbicacion!, 15);
        if (kDebugMode) {
          print(
              '✅ Mapa centrado en: ${_miUbicacion!.latitude}, ${_miUbicacion!.longitude}');
        }
      } catch (e) {
        if (kDebugMode) {
          print('❌ Error al centrar mapa: $e');
        }
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_viajeCancelado) {
      return const Scaffold(
        body: Center(
          child: CircularProgressIndicator(),
        ),
      );
    }

    final nombrePasaj =
        widget.viaje['nombre_pasaj']?.toString() ?? 'Sin nombre';
    final celular = widget.viaje['cel_pasaj']?.toString() ?? 'Sin celular';
    final origen = widget.viaje['direccion_origen']?.toString() ?? 'Sin origen';
    final destino =
        widget.viaje['direccion_destino']?.toString() ?? 'Sin destino';
    final obsPasaj =
        widget.viaje['obs_pasaj']?.toString() ?? 'Sin observaciones';
    final categoria =
        widget.viaje['categoria_movil']?.toString() ?? 'Sin categoría';
    final cc = widget.viaje['cc']?.toString() ?? 'N/A';

    return Scaffold(
      appBar: AppBar(
        title: const Text('🚗 Viaje en Curso'),
        backgroundColor: Colors.green,
        foregroundColor: Colors.white,
        automaticallyImplyLeading: false,
        actions: [
          IconButton(
            icon: const Icon(Icons.my_location),
            onPressed: _centrarEnMiUbicacion,
            tooltip: 'Centrar en mi ubicación',
          ),
        ],
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(12.0),
            child: Card(
              elevation: 3,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
              ),
              child: Padding(
                padding: const EdgeInsets.all(12.0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        const Icon(Icons.person, color: Colors.blue, size: 18),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            'Pasajero: $nombrePasaj',
                            style: const TextStyle(
                              fontSize: 14,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 4),
                    Row(
                      children: [
                        const Icon(Icons.location_on,
                            color: Colors.green, size: 16),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            'Origen: $origen',
                            style: const TextStyle(fontSize: 12),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 2),
                    Row(
                      children: [
                        const Icon(Icons.location_on,
                            color: Colors.red, size: 16),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            'Destino: $destino',
                            style: const TextStyle(fontSize: 12),
                          ),
                        ),
                      ],
                    ),
                    if (_errorUbicacion.isNotEmpty)
                      Padding(
                        padding: const EdgeInsets.only(top: 8),
                        child: Row(
                          children: [
                            Icon(Icons.warning, color: Colors.orange, size: 16),
                            const SizedBox(width: 8),
                            Expanded(
                              child: Text(
                                _errorUbicacion,
                                style: TextStyle(
                                  fontSize: 12,
                                  color: Colors.orange[700],
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    if (_ubicacionCargada && _miUbicacion != null)
                      Padding(
                        padding: const EdgeInsets.only(top: 4),
                        child: Row(
                          children: [
                            Icon(Icons.gps_fixed,
                                color: Colors.green, size: 14),
                            const SizedBox(width: 8),
                            Text(
                              '📍 GPS activo',
                              style: TextStyle(
                                fontSize: 12,
                                color: Colors.green[700],
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ],
                        ),
                      ),
                    const SizedBox(height: 8),
                    GestureDetector(
                      onTap: () {
                        setState(() {
                          _infoExpandida = !_infoExpandida;
                        });
                      },
                      child: Container(
                        padding: const EdgeInsets.symmetric(
                            horizontal: 12, vertical: 6),
                        decoration: BoxDecoration(
                          color: Colors.blue.withValues(alpha: 0.1),
                          borderRadius: BorderRadius.circular(8),
                          border: Border.all(
                            color: Colors.blue.withValues(alpha: 0.3),
                          ),
                        ),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(
                              _infoExpandida
                                  ? Icons.expand_less
                                  : Icons.expand_more,
                              color: Colors.blue,
                              size: 18,
                            ),
                            const SizedBox(width: 8),
                            Text(
                              _infoExpandida
                                  ? 'Ocultar detalles'
                                  : 'Ver más detalles',
                              style: TextStyle(
                                fontSize: 12,
                                color: Colors.blue[700],
                                fontWeight: FontWeight.w500,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                    if (_infoExpandida) ...[
                      const Divider(height: 16),
                      _infoRow(Icons.phone, 'Celular', celular),
                      const SizedBox(height: 4),
                      _infoRow(Icons.directions_car, 'Categoría', categoria),
                      const SizedBox(height: 4),
                      _infoRow(Icons.numbers, 'Cuenta', cc),
                      const SizedBox(height: 4),
                      _infoRow(Icons.note, 'Obs. Pasajero', obsPasaj),
                    ],
                  ],
                ),
              ),
            ),
          ),
          Expanded(
            flex: 3,
            child: Container(
              margin: const EdgeInsets.symmetric(horizontal: 12),
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: Colors.grey.shade300),
              ),
              child: ClipRRect(
                borderRadius: BorderRadius.circular(12),
                child: _cargandoRuta
                    ? const Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            CircularProgressIndicator(),
                            SizedBox(height: 16),
                            Text('Calculando ruta...'),
                          ],
                        ),
                      )
                    : _errorRuta
                        ? const Center(
                            child: Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Icon(Icons.error_outline,
                                    size: 48, color: Colors.red),
                                SizedBox(height: 16),
                                Text(
                                  'No se pudo calcular la ruta',
                                  style: TextStyle(fontSize: 16),
                                ),
                              ],
                            ),
                          )
                        : _coordenadasRuta.isEmpty ||
                                _origen == null ||
                                _destino == null
                            ? const Center(
                                child: Text('No hay coordenadas disponibles'),
                              )
                            : _buildMap(),
              ),
            ),
          ),
          if (!_cargandoRuta && !_errorRuta && _coordenadasRuta.isNotEmpty)
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              child: Column(
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceAround,
                    children: [
                      _buildInfoChip(
                        icon: Icons.straighten,
                        label: 'Distancia',
                        value: _distancia,
                        color: Colors.blue,
                      ),
                      _buildInfoChip(
                        icon: Icons.timer,
                        label: 'Tiempo estimado',
                        value: _tiempo,
                        color: Colors.orange,
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton.icon(
                      onPressed: _abrirWaze,
                      icon: const Icon(Icons.navigation,
                          size: 20, color: Colors.white),
                      label: const Text(
                        'Navegar con Waze',
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF33CCFF),
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 10),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(8),
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(height: 6),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton.icon(
                      onPressed: _abrirGoogleMaps,
                      icon:
                          const Icon(Icons.map, size: 20, color: Colors.white),
                      label: const Text(
                        'Navegar con Google Maps',
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF34A853),
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 10),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(8),
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          const Spacer(),
          // En viaje_en_curso.dart - Botón principal
          Padding(
            padding: const EdgeInsets.all(16.0),
            child: SizedBox(
              width: double.infinity,
              height: 55,
              child: ElevatedButton(
                onPressed: _cambiandoEstado
                    ? null
                    : _enCurso
                        ? () async {
                            // 🔴 Si ya está en curso, abrir finalización
                            final resultado = await Navigator.push<bool>(
                              context,
                              MaterialPageRoute(
                                builder: (context) => FinalizarViajePage(
                                  viaje: widget.viaje,
                                  movilId: widget.numeroMovil,
                                ),
                              ),
                            );

                            if (resultado == true) {
                              Navigator.pop(context, true);
                            }
                          }
                        : () async {
                            // 🔴 Si NO está en curso, cambiar a "En Curso"
                            final exito = await _cambiarAEnCurso();
                            if (exito) {
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(
                                  content: Text(
                                      '✅ Pasajero a bordo. Viaje en curso.'),
                                  backgroundColor: Colors.green,
                                  duration: Duration(seconds: 2),
                                ),
                              );
                              setState(() {
                                _enCurso = true;
                              });
                            } else {
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(
                                  content: Text('❌ Error al iniciar el viaje'),
                                  backgroundColor: Colors.red,
                                  duration: Duration(seconds: 2),
                                ),
                              );
                            }
                          },
                style: ElevatedButton.styleFrom(
                  backgroundColor: _enCurso ? Colors.green : Colors.orange,
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(10),
                  ),
                ),
                child: _cambiandoEstado
                    ? const Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          SizedBox(
                            height: 20,
                            width: 20,
                            child: CircularProgressIndicator(
                              strokeWidth: 2,
                              color: Colors.white,
                            ),
                          ),
                          SizedBox(width: 12),
                          Text('Procesando...'),
                        ],
                      )
                    : Text(
                        _enCurso ? '✅ FINALIZAR VIAJE' : '🧑 PASAJERO A BORDO',
                        style: const TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _infoRow(IconData icon, String label, String value) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, size: 14, color: Colors.grey),
        const SizedBox(width: 8),
        SizedBox(
          width: 90,
          child: Text(
            '$label:',
            style: const TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w500,
              color: Colors.grey,
            ),
          ),
        ),
        Expanded(
          child: Text(
            value.isEmpty ? 'N/A' : value,
            style: const TextStyle(
              fontSize: 12,
              color: Colors.black87,
            ),
            maxLines: 5,
            overflow: TextOverflow.ellipsis,
          ),
        ),
      ],
    );
  }

  Widget _buildMap() {
    final List<Marker> markers = [];

    if (_origen != null) {
      markers.add(
        Marker(
          point: _origen!,
          width: 30,
          height: 30,
          child: const Icon(
            Icons.location_on,
            color: Colors.green,
            size: 30,
          ),
        ),
      );
    }

    if (_destino != null) {
      markers.add(
        Marker(
          point: _destino!,
          width: 30,
          height: 30,
          child: const Icon(
            Icons.location_on,
            color: Colors.red,
            size: 30,
          ),
        ),
      );
    }

    if (_miUbicacion != null && _ubicacionCargada && !_viajeCancelado) {
      markers.add(
        Marker(
          point: _miUbicacion!,
          width: 15,
          height: 15,
          child: Container(
            width: 15,
            height: 15,
            decoration: BoxDecoration(
              color: Colors.green,
              shape: BoxShape.circle,
              border: Border.all(
                color: Colors.white,
                width: 2.5,
              ),
              boxShadow: [
                BoxShadow(
                  color: Colors.green.withValues(alpha: 0.5),
                  blurRadius: 8,
                  spreadRadius: 2,
                ),
              ],
            ),
          ),
        ),
      );
    }

    final LatLng centro = _origen ?? const LatLng(-34.6037, -58.3816);
    if (_miUbicacion != null && _ubicacionCargada && !_viajeCancelado) {
      return FlutterMap(
        mapController: _mapController,
        options: MapOptions(
          initialCenter: _miUbicacion!,
          initialZoom: 15,
          interactionOptions: const InteractionOptions(
            flags: InteractiveFlag.all,
          ),
        ),
        children: [
          TileLayer(
            urlTemplate:
                'https://tile.thunderforest.com/atlas/{z}/{x}/{y}.png?apikey=d97c9f871ac547cb9293e95931c9f0cb',
            userAgentPackageName: 'rast_gps',
            additionalOptions: const {
              'apiKey': 'd97c9f871ac547cb9293e95931c9f0cb',
            },
          ),
          PolylineLayer(
            polylines: [
              Polyline(
                points: _coordenadasRuta,
                color: Colors.blue,
                strokeWidth: 5,
              ),
            ],
          ),
          MarkerLayer(
            markers: markers,
          ),
        ],
      );
    }

    return FlutterMap(
      mapController: _mapController,
      options: MapOptions(
        initialCenter: centro,
        initialZoom: 14,
        interactionOptions: const InteractionOptions(
          flags: InteractiveFlag.all,
        ),
      ),
      children: [
        TileLayer(
          urlTemplate:
              'https://tile.thunderforest.com/atlas/{z}/{x}/{y}.png?apikey=d97c9f871ac547cb9293e95931c9f0cb',
          userAgentPackageName: 'rast_gps',
          additionalOptions: const {
            'apiKey': 'd97c9f871ac547cb9293e95931c9f0cb',
          },
        ),
        PolylineLayer(
          polylines: [
            Polyline(
              points: _coordenadasRuta,
              color: Colors.blue,
              strokeWidth: 5,
            ),
          ],
        ),
        MarkerLayer(
          markers: markers,
        ),
      ],
    );
  }

  Widget _buildInfoChip({
    required IconData icon,
    required String label,
    required String value,
    required Color color,
  }) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: color.withValues(alpha: 0.3)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 16, color: color),
          const SizedBox(width: 8),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                label,
                style: TextStyle(
                  fontSize: 10,
                  color: Colors.grey[600],
                ),
              ),
              Text(
                value,
                style: TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.bold,
                  color: color,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

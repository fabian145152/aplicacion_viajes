import 'package:flutter/material.dart';

import 'cambiar_password.dart';
import 'login.dart';
import 'ver_mapa.dart';
import '../servicios/api_service.dart';

class HomePage extends StatefulWidget {
  final int id;
  final String nombre;
  final String email;
  final String celular;

  const HomePage({
    super.key,
    required this.id,
    required this.nombre,
    required this.email,
    required this.celular,
  });

  @override
  State<HomePage> createState() => _HomePageState();
}

class _HomePageState extends State<HomePage> {
  // Controllers del formulario
  final _direccionCtrl = TextEditingController();
  final _cpCtrl = TextEditingController();

  bool _loading = false;
  bool _direccionVerificada = false;
  List<dynamic> _viajes = [];
  bool _cargandoViajes = true;

  @override
  void initState() {
    super.initState();
    _cargarViajes();

    // Si el usuario cambia la dirección, tiene que volver a verificar
    _direccionCtrl.addListener(() {
      if (_direccionVerificada) {
        setState(() => _direccionVerificada = false);
      }
    });
  }

  // ============================================================
  // CARGAR VIAJES DEL PASAJERO
  // ============================================================
  Future<void> _cargarViajes() async {
    setState(() => _cargandoViajes = true);
    try {
      final r = await ApiService.listarViajes(pasajeroId: widget.id);
      if (r['res'] == 'OK') {
        setState(() => _viajes = r['viajes'] ?? []);
      }
    } catch (_) {
    } finally {
      if (mounted) setState(() => _cargandoViajes = false);
    }
  }

  // ============================================================
  // VER DIRECCIÓN EN EL MAPA
  // ============================================================
  Future<void> _verEnMapa() async {
    final direccion = _direccionCtrl.text.trim();
    final cp = _cpCtrl.text.trim();

    if (direccion.isEmpty) {
      _msg('Escribí una dirección primero', error: true);
      return;
    }

    final query = cp.isEmpty ? direccion : '$direccion, CP $cp';

    await Navigator.push(
      context,
      MaterialPageRoute(builder: (_) => VerMapaPage(direccion: query)),
    );

    // Cuando vuelve del mapa, marcar como verificada
    if (mounted) {
      setState(() => _direccionVerificada = true);
    }
  }

  // ============================================================
  // PEDIR VIAJE
  // ============================================================
  Future<void> _pedirViaje() async {
    final direccion = _direccionCtrl.text.trim();
    final cpTexto = _cpCtrl.text.trim();
    final cp = int.tryParse(cpTexto) ?? 0;

    // 1) Validar que hay dirección
    if (direccion.isEmpty) {
      _msg('Completá la dirección de origen', error: true);
      return;
    }

    // 2) Validar que verificó el mapa
    if (!_direccionVerificada) {
      await _alertaVerificarMapa();
      return;
    }

    // 3) Validar que no tenga un viaje activo
    final viajeActivo = _viajes.firstWhere(
      (v) => v['fecha_cerrado'] == null,
      orElse: () => null,
    );
    if (viajeActivo != null) {
      await _alertaViajeActivo(viajeActivo);
      return;
    }

    // 4) Validar CP
    if (cpTexto.isNotEmpty && cp == 0) {
      _msg('El código postal debe ser un número', error: true);
      return;
    }

    setState(() => _loading = true);

    try {
      final r = await ApiService.crearViaje(
        pasajeroId: widget.id,
        direccion: direccion,
        cp: cp,
      );

      if (r['res'] == 'OK') {
        // Limpiar el formulario y resetear la verificación
        _direccionCtrl.clear();
        _cpCtrl.clear();
        setState(() => _direccionVerificada = false);

        await _cargarViajes();
        await _mostrarExito(r['id'].toString());
      } else {
        final errs =
            (r['errores'] as List?)?.join('\n') ??
            r['msg']?.toString() ??
            'Error al crear el viaje';
        _msg(errs, error: true);
      }
    } catch (e) {
      _msg('Error de conexión: $e', error: true);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  // ============================================================
  // ALERTA: VERIFICAR MAPA
  // ============================================================
  Future<void> _alertaVerificarMapa() async {
    return showDialog<void>(
      context: context,
      builder: (context) {
        return AlertDialog(
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(16),
          ),
          contentPadding: const EdgeInsets.fromLTRB(24, 30, 24, 10),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 70,
                height: 70,
                decoration: BoxDecoration(
                  color: Colors.orange.withValues(alpha: 0.12),
                  shape: BoxShape.circle,
                ),
                child: const Icon(
                  Icons.map_outlined,
                  color: Colors.orange,
                  size: 40,
                ),
              ),
              const SizedBox(height: 18),
              const Text(
                'Verificá la dirección',
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 10),
              const Text(
                'Antes de pedir el viaje, tocá el botón azul del mapa '
                'para confirmar que la dirección de origen es correcta.',
                textAlign: TextAlign.center,
                style: TextStyle(fontSize: 14, color: Colors.black54),
              ),
            ],
          ),
          actions: [
            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: () {
                  Navigator.pop(context);
                  _verEnMapa();
                },
                icon: const Icon(Icons.map),
                label: const Text(
                  'VER EN EL MAPA',
                  style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold),
                ),
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.blue,
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 12),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(10),
                  ),
                ),
              ),
            ),
            const SizedBox(height: 8),
            SizedBox(
              width: double.infinity,
              child: TextButton(
                onPressed: () => Navigator.pop(context),
                child: const Text(
                  'Cancelar',
                  style: TextStyle(color: Colors.grey),
                ),
              ),
            ),
          ],
        );
      },
    );
  }

  // ============================================================
  // ALERTA: VIAJE ACTIVO
  // ============================================================
  Future<void> _alertaViajeActivo(Map viaje) async {
    return showDialog<void>(
      context: context,
      builder: (context) {
        return AlertDialog(
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(16),
          ),
          contentPadding: const EdgeInsets.fromLTRB(24, 30, 24, 10),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 70,
                height: 70,
                decoration: BoxDecoration(
                  color: Colors.red.withValues(alpha: 0.12),
                  shape: BoxShape.circle,
                ),
                child: const Icon(
                  Icons.warning_amber_rounded,
                  color: Colors.red,
                  size: 40,
                ),
              ),
              const SizedBox(height: 18),
              const Text(
                'Tenés un viaje en curso',
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 10),
              Text(
                'Tu viaje #${viaje['id']} todavía no terminó.\n\n'
                'Esperá a que se complete antes de pedir otro.',
                textAlign: TextAlign.center,
                style: const TextStyle(fontSize: 14, color: Colors.black54),
              ),
            ],
          ),
          actions: [
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: () => Navigator.pop(context),
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.blue,
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 12),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(10),
                  ),
                ),
                child: const Text(
                  'ENTENDIDO',
                  style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold),
                ),
              ),
            ),
          ],
        );
      },
    );
  }

  // ============================================================
  // DIÁLOGO: VIAJE CREADO OK
  // ============================================================
  Future<void> _mostrarExito(String idViaje) async {
    return showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (context) {
        return AlertDialog(
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(16),
          ),
          contentPadding: const EdgeInsets.fromLTRB(24, 30, 24, 10),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 70,
                height: 70,
                decoration: BoxDecoration(
                  color: Colors.green.withValues(alpha: 0.12),
                  shape: BoxShape.circle,
                ),
                child: const Icon(
                  Icons.check_circle,
                  color: Colors.green,
                  size: 40,
                ),
              ),
              const SizedBox(height: 18),
              const Text(
                '¡Viaje solicitado!',
                style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 10),
              const Text(
                'Tu pedido fue enviado. En breve un móvil va a ser asignado.',
                textAlign: TextAlign.center,
                style: TextStyle(fontSize: 14, color: Colors.black54),
              ),
              const SizedBox(height: 8),
              Text(
                'N° de viaje: #$idViaje',
                style: const TextStyle(fontSize: 12, color: Colors.grey),
              ),
            ],
          ),
          actions: [
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: () => Navigator.pop(context),
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.green,
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 12),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(10),
                  ),
                ),
                child: const Text(
                  'ACEPTAR',
                  style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold),
                ),
              ),
            ),
          ],
        );
      },
    );
  }

  // ============================================================
  // SNACKBAR
  // ============================================================
  void _msg(String m, {bool error = false}) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(m),
        backgroundColor: error ? Colors.redAccent : Colors.blue,
        duration: const Duration(seconds: 4),
      ),
    );
  }

  // ============================================================
  // HELPERS DE ESTADO
  // ============================================================
  Color _colorEstado(Map v) {
    if (v['fecha_cerrado'] != null) return Colors.grey;
    if (v['tomado_por'] != null && v['tomado_por'] != 0) return Colors.green;
    return Colors.orange;
  }

  String _textoEstado(Map v) {
    if (v['fecha_cerrado'] != null) return 'COMPLETO';
    if (v['tomado_por'] != null && v['tomado_por'] != 0) return 'ASIGNADO';
    return 'PENDIENTE';
  }

  String _formatearFecha(dynamic fecha) {
    if (fecha == null) return '-';
    final s = fecha.toString();
    if (s.length >= 16) return s.substring(0, 16);
    return s;
  }

  // ============================================================
  // UI
  // ============================================================
  @override
  Widget build(BuildContext context) {
    // Chequear si hay viaje activo (sin fecha_cerrado)
    final viajeActivo = _viajes.firstWhere(
      (v) => v['fecha_cerrado'] == null,
      orElse: () => null,
    );
    final hayViajeActivo = viajeActivo != null;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Pasajeros'),
        backgroundColor: Colors.blue,
        foregroundColor: Colors.white,
        actions: [
          IconButton(
            icon: const Icon(Icons.lock_reset),
            tooltip: 'Cambiar contraseña',
            onPressed: () {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (_) => CambiarPasswordPage(email: widget.email),
                ),
              );
            },
          ),
          IconButton(
            icon: const Icon(Icons.logout),
            tooltip: 'Cerrar sesión',
            onPressed: () {
              Navigator.pushReplacement(
                context,
                MaterialPageRoute(builder: (_) => const LoginPage()),
              );
            },
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _cargarViajes,
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // ==========================================
              // ENCABEZADO
              // ==========================================
              Container(
                padding: const EdgeInsets.all(20),
                color: Colors.blue.shade50,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Hola, ${widget.nombre}',
                      style: const TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      widget.email,
                      style: TextStyle(
                        fontSize: 13,
                        color: Colors.grey.shade700,
                      ),
                    ),
                  ],
                ),
              ),

              // ==========================================
              // FORMULARIO DE VIAJE NUEVO
              // ==========================================
              Container(
                margin: const EdgeInsets.all(16),
                padding: const EdgeInsets.all(20),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(14),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.05),
                      blurRadius: 10,
                      offset: const Offset(0, 2),
                    ),
                  ],
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Row(
                      children: [
                        Icon(
                          Icons.add_location_alt,
                          color: Colors.green,
                          size: 26,
                        ),
                        SizedBox(width: 8),
                        Text(
                          'Pedir viaje nuevo',
                          style: TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 20),

                    // Dirección con botón de mapa
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Expanded(
                          child: TextField(
                            controller: _direccionCtrl,
                            textCapitalization: TextCapitalization.sentences,
                            maxLength: 150,
                            enabled: !hayViajeActivo,
                            decoration: InputDecoration(
                              labelText: 'Dirección de origen *',
                              hintText: 'Calle, número, localidad',
                              prefixIcon: const Icon(
                                Icons.location_on,
                                color: Colors.green,
                              ),
                              border: const OutlineInputBorder(),
                              counterText: '',
                              // Check verde si ya verificó
                              suffixIcon: _direccionVerificada
                                  ? const Icon(
                                      Icons.check_circle,
                                      color: Colors.green,
                                    )
                                  : null,
                            ),
                          ),
                        ),
                        const SizedBox(width: 8),
                        Padding(
                          padding: const EdgeInsets.only(top: 4),
                          child: SizedBox(
                            height: 48,
                            width: 48,
                            child: ElevatedButton(
                              onPressed: hayViajeActivo ? null : _verEnMapa,
                              style: ElevatedButton.styleFrom(
                                backgroundColor: _direccionVerificada
                                    ? Colors.green
                                    : Colors.blue,
                                foregroundColor: Colors.white,
                                disabledBackgroundColor: Colors.grey.shade300,
                                padding: EdgeInsets.zero,
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(10),
                                ),
                              ),
                              child: Icon(
                                _direccionVerificada ? Icons.check : Icons.map,
                                size: 24,
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 15),

                    // Código postal
                    TextField(
                      controller: _cpCtrl,
                      keyboardType: TextInputType.number,
                      maxLength: 8,
                      enabled: !hayViajeActivo,
                      decoration: const InputDecoration(
                        labelText: 'Código postal (opcional)',
                        hintText: 'Ej: 1444',
                        prefixIcon: Icon(Icons.markunread_mailbox),
                        border: OutlineInputBorder(),
                        counterText: '',
                      ),
                    ),
                    const SizedBox(height: 25),

                    // Botón pedir viaje
                    SizedBox(
                      width: double.infinity,
                      height: 55,
                      child: _loading
                          ? const Center(child: CircularProgressIndicator())
                          : ElevatedButton.icon(
                              onPressed: hayViajeActivo ? null : _pedirViaje,
                              icon: Icon(
                                hayViajeActivo ? Icons.lock : Icons.send,
                              ),
                              label: Text(
                                hayViajeActivo
                                    ? 'VIAJE EN CURSO'
                                    : 'PEDIR VIAJE',
                                style: const TextStyle(
                                  fontSize: 17,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                              style: ElevatedButton.styleFrom(
                                backgroundColor: hayViajeActivo
                                    ? Colors.grey
                                    : Colors.green,
                                foregroundColor: Colors.white,
                                disabledBackgroundColor: Colors.grey,
                                disabledForegroundColor: Colors.white,
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(12),
                                ),
                              ),
                            ),
                    ),

                    // Mensaje si hay viaje activo
                    if (hayViajeActivo) ...[
                      const SizedBox(height: 12),
                      Container(
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: Colors.orange.withValues(alpha: 0.1),
                          borderRadius: BorderRadius.circular(8),
                          border: Border.all(
                            color: Colors.orange.withValues(alpha: 0.3),
                          ),
                        ),
                        child: const Row(
                          children: [
                            Icon(
                              Icons.info_outline,
                              color: Colors.orange,
                              size: 20,
                            ),
                            SizedBox(width: 8),
                            Expanded(
                              child: Text(
                                'Tenés un viaje en curso. Cuando termine vas a poder pedir otro.',
                                style: TextStyle(
                                  fontSize: 12,
                                  color: Colors.black87,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ],
                ),
              ),

              // ==========================================
              // HISTORIAL
              // ==========================================
              const Padding(
                padding: EdgeInsets.symmetric(horizontal: 20),
                child: Align(
                  alignment: Alignment.centerLeft,
                  child: Text(
                    'Historial de viajes',
                    style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold),
                  ),
                ),
              ),
              const SizedBox(height: 10),

              _cargandoViajes
                  ? const Padding(
                      padding: EdgeInsets.all(40),
                      child: Center(child: CircularProgressIndicator()),
                    )
                  : _viajes.isEmpty
                  ? const Padding(
                      padding: EdgeInsets.all(40),
                      child: Column(
                        children: [
                          Icon(Icons.inbox, size: 60, color: Colors.grey),
                          SizedBox(height: 12),
                          Text(
                            'No tenés viajes todavía',
                            style: TextStyle(fontSize: 16, color: Colors.grey),
                          ),
                          SizedBox(height: 5),
                          Text(
                            'Pedí tu primer viaje desde el formulario de arriba',
                            style: TextStyle(fontSize: 12, color: Colors.grey),
                            textAlign: TextAlign.center,
                          ),
                        ],
                      ),
                    )
                  : ListView.builder(
                      padding: const EdgeInsets.symmetric(horizontal: 16),
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      itemCount: _viajes.length,
                      itemBuilder: (context, i) {
                        final v = _viajes[i] as Map;
                        final color = _colorEstado(v);
                        return Card(
                          margin: const EdgeInsets.only(bottom: 10),
                          child: ListTile(
                            leading: CircleAvatar(
                              backgroundColor: color,
                              child: const Icon(
                                Icons.local_taxi,
                                color: Colors.white,
                                size: 20,
                              ),
                            ),
                            title: Text(
                              v['direccion']?.toString() ?? '',
                              style: const TextStyle(
                                fontWeight: FontWeight.bold,
                                fontSize: 14,
                              ),
                            ),
                            subtitle: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                if ((v['cp'] ?? 0) != 0)
                                  Text(
                                    'CP: ${v['cp']}',
                                    style: const TextStyle(fontSize: 12),
                                  ),
                                const SizedBox(height: 4),
                                Text(
                                  'Creado: ${_formatearFecha(v['fecha_creado'])} · #${v['id']}',
                                  style: const TextStyle(
                                    fontSize: 11,
                                    color: Colors.grey,
                                  ),
                                ),
                              ],
                            ),
                            trailing: Container(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 10,
                                vertical: 5,
                              ),
                              decoration: BoxDecoration(
                                color: color.withValues(alpha: 0.15),
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: Text(
                                _textoEstado(v),
                                style: TextStyle(
                                  fontSize: 10,
                                  fontWeight: FontWeight.bold,
                                  color: color,
                                ),
                              ),
                            ),
                          ),
                        );
                      },
                    ),

              const SizedBox(height: 20),
            ],
          ),
        ),
      ),
    );
  }

  @override
  void dispose() {
    _direccionCtrl.dispose();
    _cpCtrl.dispose();
    super.dispose();
  }
}

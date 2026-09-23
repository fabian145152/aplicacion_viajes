import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../servicios/api_service.dart';

class RegistroPage extends StatefulWidget {
  const RegistroPage({super.key});

  @override
  State<RegistroPage> createState() => _RegistroPageState();
}

class _RegistroPageState extends State<RegistroPage> {
  final _nombreCtrl = TextEditingController();
  final _emailCtrl = TextEditingController();
  final _passCtrl = TextEditingController();
  final _passConfirmCtrl = TextEditingController();
  final _celCtrl = TextEditingController();
  bool _loading = false;
  bool _verPass = false;
  bool _verPassConfirm = false;

  // ⚠️ Número de WhatsApp de la empresa (con código de país, sin + ni espacios)
  static const String _whatsappEmpresa = '5491121637073';

  Future<void> _registrar() async {
    final nombre = _nombreCtrl.text.trim();
    final email = _emailCtrl.text.trim();
    final pass = _passCtrl.text.trim();
    final passConfirm = _passConfirmCtrl.text.trim();
    final cel = _celCtrl.text.trim();

    if (nombre.isEmpty ||
        email.isEmpty ||
        pass.isEmpty ||
        passConfirm.isEmpty ||
        cel.isEmpty) {
      _msg('Completá todos los campos');
      return;
    }
    if (!RegExp(r'^\d{4}$').hasMatch(pass)) {
      _msg('La contraseña debe ser de 4 números');
      return;
    }
    if (pass != passConfirm) {
      _msg('Las contraseñas no coinciden');
      return;
    }
    if (!RegExp(r'^\d{8,15}$').hasMatch(cel)) {
      _msg('Celular inválido (solo números, 8 a 15 dígitos)');
      return;
    }

    setState(() => _loading = true);

    try {
      final r = await ApiService.registrar(
        nombreApellido: nombre,
        email: email,
        password: pass,
        celular: cel,
      );

      if (r['res'] == 'OK') {
        final codigo = r['codigo']?.toString() ?? '';

        // 1. Notificación emergente
        await _mostrarConfirmacion();
        if (!mounted) return;

        // 2. Abrir WhatsApp
        await _abrirWhatsApp(nombre, email, codigo);
        if (!mounted) return;

        // 3. Volver al login
        Navigator.pop(context);
      } else {
        final errs =
            (r['errores'] as List?)?.join('\n') ?? 'Error al registrar';
        _msg(errs);
      }
    } catch (e) {
      _msg('Error de conexión: $e');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _mostrarConfirmacion() async {
    return showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (BuildContext context) {
        return AlertDialog(
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(16),
          ),
          contentPadding: const EdgeInsets.fromLTRB(24, 30, 24, 10),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 80,
                height: 80,
                decoration: BoxDecoration(
                  color: Colors.green.withOpacity(0.12),
                  shape: BoxShape.circle,
                ),
                child: const Icon(
                  Icons.check_circle_outline,
                  color: Colors.green,
                  size: 50,
                ),
              ),
              const SizedBox(height: 20),
              const Text(
                '¡Registro en proceso!',
                style: TextStyle(
                  fontSize: 20,
                  fontWeight: FontWeight.bold,
                  color: Colors.black87,
                ),
              ),
              const SizedBox(height: 12),
              const Text(
                'En breve se le notificará sobre su registro.',
                textAlign: TextAlign.center,
                style: TextStyle(fontSize: 15, color: Colors.black54),
              ),
              const SizedBox(height: 8),
              const Text(
                'Le vamos a abrir WhatsApp para confirmar su teléfono.',
                textAlign: TextAlign.center,
                style: TextStyle(fontSize: 12, color: Colors.grey),
              ),
            ],
          ),
          actions: [
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: () => Navigator.of(context).pop(),
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
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                ),
              ),
            ),
          ],
        );
      },
    );
  }

  Future<void> _abrirWhatsApp(
    String nombre,
    String email,
    String codigo,
  ) async {
    final texto = Uri.encodeComponent(
      'Hola, me registré en la app de pasajeros.\n'
      'Nombre: $nombre\n'
      'Email: $email\n'
      'Código: $codigo',
    );
    final url = Uri.parse('https://wa.me/$_whatsappEmpresa?text=$texto');

    if (await canLaunchUrl(url)) {
      await launchUrl(url, mode: LaunchMode.externalApplication);
    } else {
      _msg('No se pudo abrir WhatsApp');
    }
  }

  void _msg(String m) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(m),
        backgroundColor: Colors.blue,
        duration: const Duration(seconds: 4),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Registrarse'),
        backgroundColor: Colors.blue,
        foregroundColor: Colors.white,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Column(
          children: [
            const SizedBox(height: 15),
            const Icon(Icons.person_add, size: 70, color: Colors.blue),
            const SizedBox(height: 20),

            TextField(
              controller: _nombreCtrl,
              textCapitalization: TextCapitalization.words,
              decoration: const InputDecoration(
                labelText: 'Nombre y Apellido',
                prefixIcon: Icon(Icons.person),
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 15),

            TextField(
              controller: _emailCtrl,
              keyboardType: TextInputType.emailAddress,
              decoration: const InputDecoration(
                labelText: 'Email',
                prefixIcon: Icon(Icons.email),
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 15),

            TextField(
              controller: _passCtrl,
              obscureText: !_verPass,
              keyboardType: TextInputType.number,
              maxLength: 4,
              decoration: InputDecoration(
                labelText: 'Contraseña (4 números)',
                prefixIcon: const Icon(Icons.lock),
                border: const OutlineInputBorder(),
                counterText: '',
                suffixIcon: IconButton(
                  icon: Icon(
                    _verPass ? Icons.visibility : Icons.visibility_off,
                  ),
                  onPressed: () => setState(() => _verPass = !_verPass),
                ),
              ),
            ),
            const SizedBox(height: 15),

            TextField(
              controller: _passConfirmCtrl,
              obscureText: !_verPassConfirm,
              keyboardType: TextInputType.number,
              maxLength: 4,
              decoration: InputDecoration(
                labelText: 'Repetir contraseña',
                prefixIcon: const Icon(Icons.lock_outline),
                border: const OutlineInputBorder(),
                counterText: '',
                suffixIcon: IconButton(
                  icon: Icon(
                    _verPassConfirm ? Icons.visibility : Icons.visibility_off,
                  ),
                  onPressed: () =>
                      setState(() => _verPassConfirm = !_verPassConfirm),
                ),
              ),
            ),
            const SizedBox(height: 15),

            TextField(
              controller: _celCtrl,
              keyboardType: TextInputType.phone,
              decoration: const InputDecoration(
                labelText: 'Celular (con código de área)',
                hintText: 'Ej: 1169356236',
                prefixIcon: Icon(Icons.phone),
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 30),

            SizedBox(
              width: double.infinity,
              height: 50,
              child: _loading
                  ? const Center(child: CircularProgressIndicator())
                  : ElevatedButton.icon(
                      onPressed: _registrar,
                      icon: const Icon(Icons.check_circle),
                      label: const Text(
                        'REGISTRARSE',
                        style: TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: Colors.green,
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(10),
                        ),
                      ),
                    ),
            ),
            const SizedBox(height: 20),
            const Text(
              '💬 Al registrarte, te vamos a abrir WhatsApp con un mensaje '
              'para que confirmes tu teléfono. Enviá ese mensaje y listo.',
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 12, color: Colors.grey),
            ),
          ],
        ),
      ),
    );
  }

  @override
  void dispose() {
    _nombreCtrl.dispose();
    _emailCtrl.dispose();
    _passCtrl.dispose();
    _passConfirmCtrl.dispose();
    _celCtrl.dispose();
    super.dispose();
  }
}

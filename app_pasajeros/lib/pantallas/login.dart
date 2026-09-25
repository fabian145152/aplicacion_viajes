import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import 'home.dart';
import '../servicios/api_service.dart';
import 'registro.dart';

class LoginPage extends StatefulWidget {
  const LoginPage({super.key});

  @override
  State<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends State<LoginPage> {
  final _emailCtrl = TextEditingController();
  final _passCtrl = TextEditingController();
  bool _loading = false;
  bool _verPass = false;

  // ⚠️ Número de WhatsApp de la empresa
  static const String _whatsappEmpresa = '5491121637073';

  Future<void> _login() async {
    final email = _emailCtrl.text.trim();
    final pass = _passCtrl.text.trim();

    if (email.isEmpty || pass.isEmpty) {
      _msg('Completá todos los campos');
      return;
    }

    setState(() => _loading = true);

    try {
      final r = await ApiService.login(email: email, password: pass);

      if (r['res'] == 'OK') {
        if (!mounted) return;
        Navigator.pushReplacement(
          context,
          MaterialPageRoute(
            builder: (_) => HomePage(
              id: (r['id'] as num).toInt(),
              nombre: r['nombre_apellido']?.toString() ?? '',
              email: r['email']?.toString() ?? email,
              celular: r['celular']?.toString() ?? '',
            ),
          ),
        );
      } else if (r['res'] == 'NO_CONFIRMADO') {
        _msg(r['msg'] ?? 'Cuenta no confirmada');
      } else {
        _msg(r['msg'] ?? 'Error al iniciar sesión');
      }
    } catch (e) {
      _msg('Error de conexión: $e');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  // ============================================================
  // OLVIDÓ SU CONTRASEÑA
  // ============================================================
  Future<void> _olvidoPassword() async {
    final emailCtrl = TextEditingController();

    // 1) Pedir el email
    final email = await showDialog<String>(
      context: context,
      builder: (context) {
        return AlertDialog(
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(14),
          ),
          title: const Row(
            children: [
              Icon(Icons.lock_reset, color: Colors.blue),
              SizedBox(width: 8),
              Text('Recuperar contraseña'),
            ],
          ),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Text(
                'Ingresá el email con el que te registraste.',
                style: TextStyle(fontSize: 13, color: Colors.black54),
              ),
              const SizedBox(height: 15),
              TextField(
                controller: emailCtrl,
                keyboardType: TextInputType.emailAddress,
                autofocus: true,
                decoration: const InputDecoration(
                  labelText: 'Email',
                  prefixIcon: Icon(Icons.email),
                  border: OutlineInputBorder(),
                ),
              ),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: const Text('Cancelar'),
            ),
            ElevatedButton(
              onPressed: () => Navigator.pop(context, emailCtrl.text.trim()),
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.blue,
                foregroundColor: Colors.white,
              ),
              child: const Text('ENVIAR'),
            ),
          ],
        );
      },
    );

    if (email == null || email.isEmpty) return;

    setState(() => _loading = true);

    try {
      final r = await ApiService.recuperarPassword(email: email);

      if (!mounted) return;

      if (r['res'] != 'OK') {
        _msg(r['msg'] ?? 'No se pudo procesar la solicitud');
        return;
      }

      if (r['nueva_password'] == null) {
        await _mostrarInfo(
          titulo: 'Solicitud enviada',
          mensaje: 'Si el email está registrado, la empresa se va a contactar con vos.',
          icono: Icons.info_outline,
          color: Colors.blue,
        );
        return;
      }

      final nuevaPass = r['nueva_password'].toString();
      final nombre = r['nombre_apellido']?.toString() ?? '';
      final emailUser = r['email']?.toString() ?? email;

      // Mostrar diálogo
      await _mostrarInfo(
        titulo: 'Solicitud enviada',
        mensaje:
            'Se generó una contraseña temporal.\n\n'
            'Vas a ser redirigido a WhatsApp para avisarle a la empresa.',
        icono: Icons.mark_email_read,
        color: Colors.green,
      );

      if (!mounted) return;

      // Abrir WhatsApp
      await _enviarWhatsAppRecuperacion(
        nombre: nombre,
        email: emailUser,
        nuevaPass: nuevaPass,
      );
    } catch (e) {
      _msg('Error de conexión: $e');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _enviarWhatsAppRecuperacion({
    required String nombre,
    required String email,
    required String nuevaPass,
  }) async {
    final texto = Uri.encodeComponent(
      'Hola, olvidé mi contraseña de la App de Pasajeros.\n'
      'Nombre: $nombre\n'
      'Email: $email\n'
      'Contraseña temporal: $nuevaPass',
    );
    final url = Uri.parse('https://wa.me/$_whatsappEmpresa?text=$texto');

    if (await canLaunchUrl(url)) {
      await launchUrl(url, mode: LaunchMode.externalApplication);
    } else {
      _msg('No se pudo abrir WhatsApp');
    }
  }

  Future<void> _mostrarInfo({
    required String titulo,
    required String mensaje,
    required IconData icono,
    required Color color,
  }) async {
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
                  color: color.withValues(alpha: 0.12),
                  shape: BoxShape.circle,
                ),
                child: Icon(icono, color: color, size: 40),
              ),
              const SizedBox(height: 18),
              Text(
                titulo,
                style: const TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                  color: Colors.black87,
                ),
              ),
              const SizedBox(height: 10),
              Text(
                mensaje,
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
                  backgroundColor: color,
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
        title: const Text('Pasajeros Baet'),
        backgroundColor: Colors.blue,
        foregroundColor: Colors.white,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Column(
          children: [
            const SizedBox(height: 30),
            const Icon(Icons.person_pin_circle, size: 80, color: Colors.blue),
            const SizedBox(height: 15),
            const Text(
              'Iniciar sesión',
              style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 30),

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
              maxLength: 8,
              decoration: InputDecoration(
                labelText: 'Contraseña (4 a 8 números)',
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

            // Olvidó su contraseña
            Align(
              alignment: Alignment.centerRight,
              child: TextButton(
                onPressed: _loading ? null : _olvidoPassword,
                style: TextButton.styleFrom(
                  foregroundColor: Colors.blue,
                  padding: const EdgeInsets.symmetric(vertical: 4),
                ),
                child: const Text(
                  '¿Olvidó su contraseña?',
                  style: TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                    decoration: TextDecoration.underline,
                  ),
                ),
              ),
            ),

            const SizedBox(height: 15),

            SizedBox(
              width: double.infinity,
              height: 50,
              child: _loading
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
            const SizedBox(height: 25),

            const Divider(),
            const SizedBox(height: 15),

            const Text('¿No tenés cuenta?'),
            const SizedBox(height: 10),
            SizedBox(
              width: double.infinity,
              height: 50,
              child: OutlinedButton(
                onPressed: () {
                  Navigator.push(
                    context,
                    MaterialPageRoute(builder: (_) => const RegistroPage()),
                  );
                },
                style: OutlinedButton.styleFrom(
                  foregroundColor: Colors.blue,
                  side: const BorderSide(color: Colors.blue, width: 2),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(10),
                  ),
                ),
                child: const Text(
                  'REGISTRARSE',
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  @override
  void dispose() {
    _emailCtrl.dispose();
    _passCtrl.dispose();
    super.dispose();
  }
}

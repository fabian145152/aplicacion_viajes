import 'package:flutter/material.dart';

import '../servicios/api_service.dart';

class CambiarPasswordPage extends StatefulWidget {
  final String email;
  const CambiarPasswordPage({super.key, required this.email});

  @override
  State<CambiarPasswordPage> createState() => _CambiarPasswordPageState();
}

class _CambiarPasswordPageState extends State<CambiarPasswordPage> {
  final _actualCtrl = TextEditingController();
  final _nuevaCtrl = TextEditingController();
  final _confirmarCtrl = TextEditingController();
  bool _loading = false;
  bool _verActual = false;
  bool _verNueva = false;
  bool _verConfirmar = false;

  Future<void> _cambiar() async {
    final actual = _actualCtrl.text.trim();
    final nueva = _nuevaCtrl.text.trim();
    final confirmar = _confirmarCtrl.text.trim();

    if (actual.isEmpty || nueva.isEmpty || confirmar.isEmpty) {
      _msg('Completá todos los campos');
      return;
    }
    if (!RegExp(r'^\d{4,8}$').hasMatch(nueva)) {
      _msg('La contraseña nueva debe tener entre 4 y 8 números');
      return;
    }
    if (nueva != confirmar) {
      _msg('Las contraseñas nuevas no coinciden');
      return;
    }
    if (actual == nueva) {
      _msg('La contraseña nueva debe ser distinta a la actual');
      return;
    }

    setState(() => _loading = true);

    try {
      final r = await ApiService.cambiarPassword(
        email: widget.email,
        passwordActual: actual,
        passwordNueva: nueva,
        passwordConfirmar: confirmar,
      );

      if (r['res'] == 'OK') {
        await _mostrarExito();
        if (!mounted) return;
        Navigator.pop(context);
      } else {
        final errs =
            (r['errores'] as List?)?.join('\n') ??
            r['msg']?.toString() ??
            'Error al cambiar la contraseña';
        _msg(errs);
      }
    } catch (e) {
      _msg('Error de conexión: $e');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _mostrarExito() async {
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
                '¡Listo!',
                style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 10),
              const Text(
                'Tu contraseña se cambió correctamente.',
                textAlign: TextAlign.center,
                style: TextStyle(fontSize: 14, color: Colors.black54),
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

  void _msg(String m) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(m),
        backgroundColor: Colors.redAccent,
        duration: const Duration(seconds: 4),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Cambiar contraseña'),
        backgroundColor: Colors.blue,
        foregroundColor: Colors.white,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Column(
          children: [
            const SizedBox(height: 15),
            const Icon(Icons.lock_reset, size: 70, color: Colors.blue),
            const SizedBox(height: 15),
            const Text(
              'Cambiá tu contraseña',
              style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 5),
            Text(
              widget.email,
              style: const TextStyle(fontSize: 13, color: Colors.grey),
            ),
            const SizedBox(height: 30),

            // Contraseña actual
            TextField(
              controller: _actualCtrl,
              obscureText: !_verActual,
              keyboardType: TextInputType.number,
              maxLength: 8,
              decoration: InputDecoration(
                labelText: 'Contraseña actual',
                prefixIcon: const Icon(Icons.lock_outline),
                border: const OutlineInputBorder(),
                counterText: '',
                suffixIcon: IconButton(
                  icon: Icon(
                    _verActual ? Icons.visibility : Icons.visibility_off,
                  ),
                  onPressed: () => setState(() => _verActual = !_verActual),
                ),
              ),
            ),
            const SizedBox(height: 15),

            // Contraseña nueva
            TextField(
              controller: _nuevaCtrl,
              obscureText: !_verNueva,
              keyboardType: TextInputType.number,
              maxLength: 8,
              decoration: InputDecoration(
                labelText: 'Contraseña nueva (4 a 8 números)',
                prefixIcon: const Icon(Icons.lock),
                border: const OutlineInputBorder(),
                counterText: '',
                suffixIcon: IconButton(
                  icon: Icon(
                    _verNueva ? Icons.visibility : Icons.visibility_off,
                  ),
                  onPressed: () => setState(() => _verNueva = !_verNueva),
                ),
              ),
            ),
            const SizedBox(height: 15),

            // Repetir nueva
            TextField(
              controller: _confirmarCtrl,
              obscureText: !_verConfirmar,
              keyboardType: TextInputType.number,
              maxLength: 8,
              decoration: InputDecoration(
                labelText: 'Repetir contraseña nueva (4 a 8 números)',
                prefixIcon: const Icon(Icons.lock),
                border: const OutlineInputBorder(),
                counterText: '',
                suffixIcon: IconButton(
                  icon: Icon(
                    _verConfirmar ? Icons.visibility : Icons.visibility_off,
                  ),
                  onPressed: () =>
                      setState(() => _verConfirmar = !_verConfirmar),
                ),
              ),
            ),
            const SizedBox(height: 30),

            SizedBox(
              width: double.infinity,
              height: 50,
              child: _loading
                  ? const Center(child: CircularProgressIndicator())
                  : ElevatedButton.icon(
                      onPressed: _cambiar,
                      icon: const Icon(Icons.save),
                      label: const Text(
                        'GUARDAR',
                        style: TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: Colors.blue,
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(10),
                        ),
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
    _actualCtrl.dispose();
    _nuevaCtrl.dispose();
    _confirmarCtrl.dispose();
    super.dispose();
  }
}

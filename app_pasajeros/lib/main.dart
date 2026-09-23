import 'package:flutter/material.dart';

import 'pantallas/login.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const AppPasajeros());
}

class AppPasajeros extends StatelessWidget {
  const AppPasajeros({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Pasajeros',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(seedColor: Colors.blue),
        useMaterial3: true,
      ),
      home: const LoginPage(),
    );
  }
}

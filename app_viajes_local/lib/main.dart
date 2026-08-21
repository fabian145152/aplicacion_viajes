import 'package:flutter/material.dart';
import 'package:wakelock_plus/wakelock_plus.dart';
import 'login.dart';

void main() {
  // Activar wakelock al inicio de la app
  WakelockPlus.enable();
  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'App GPS',
      theme: ThemeData(primarySwatch: Colors.blue),
      home: const LoginPage(),
    );
  }
}

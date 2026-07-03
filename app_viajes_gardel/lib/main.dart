import 'package:flutter/material.dart';
import 'login.dart'; // Importas tu pantalla de login

void main() {
  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});
  //aa
  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'App GPS',
      theme: ThemeData(primarySwatch: Colors.blue),
      home: const LoginPage(), // Pantalla inicial
    );
  }
}

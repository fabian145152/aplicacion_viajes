import 'package:flutter/material.dart';
import 'package:wakelock_plus/wakelock_plus.dart';
import 'login.dart';
import 'database/db_helper.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // 🔥 Inicializar SQLite
  await DBHelper().database;
  print('✅ SQLite inicializado');

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

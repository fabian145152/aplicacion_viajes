import 'package:flutter_test/flutter_test.dart';
import 'package:tu_app_gps/main.dart'; // <-- Nombre correcto del proyecto

void main() {
  testWidgets('Verificar que carga la pantalla de Login', (
    WidgetTester tester,
  ) async {
    // Carga la aplicación
    await tester.pumpWidget(const MyApp());

    // Espera un momento para que se construya la UI
    await tester.pumpAndSettle();

    // Verifica el título de la app
    expect(find.text('Sistema de Rastreo GPS'), findsOneWidget);

    // Verifica que existan los campos de texto
    expect(find.text('Usuario'), findsOneWidget);
    expect(find.text('Contraseña'), findsOneWidget);

    // Verifica el botón de ingresar (está en mayúsculas)
    expect(find.text('INGRESAR'), findsOneWidget);

    // Verifica el botón de prueba de conexión
    expect(find.text('🔍 PROBAR CONEXIÓN'), findsOneWidget);

    // Verifica la información de conexión
    expect(find.text('📡 Información de conexión'), findsOneWidget);
    expect(find.text('Servidor: 181.47.100.96'), findsOneWidget);
    expect(find.text('Ruta: /app_viajes/php/01_mapeo/'), findsOneWidget);
  });
}

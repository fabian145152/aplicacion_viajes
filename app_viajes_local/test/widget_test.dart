import 'package:flutter_test/flutter_test.dart';
import 'package:tu_app_gps/main.dart'; // <-- Cambiado a tu_app_gps

void main() {
  testWidgets('Verificar que carga la pantalla de Login', (
    WidgetTester tester,
  ) async {
    // Carga la aplicación
    await tester.pumpWidget(const MyApp());

    // Espera que se construya la UI
    await tester.pumpAndSettle();

    // Verifica el título de la app
    expect(find.text('Sistema de Rastreo GPS'), findsOneWidget);

    // Verifica que existan los campos de texto
    expect(find.text('Usuario'), findsOneWidget);
    expect(find.text('Contraseña'), findsOneWidget);

    // Verifica el botón de ingresar (está en mayúsculas)
    expect(find.text('INGRESAR'), findsOneWidget);
  });
}

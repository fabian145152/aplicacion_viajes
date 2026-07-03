import 'package:flutter_test/flutter_test.dart';
import 'package:untitled/main.dart'; // Asegúrate de que apunte a tu main.dart

void main() {
  testWidgets('Verificar que carga la pantalla de Login', (
    WidgetTester tester,
  ) async {
    // Carga la aplicación
    await tester.pumpWidget(const MyApp());

    // Verifica que existan los campos de texto y el botón de ingresar
    expect(find.text('Usuario'), findsOneWidget);
    expect(find.text('Contraseña'), findsOneWidget);
    expect(find.text('Ingresar'), findsOneWidget);
  });
}

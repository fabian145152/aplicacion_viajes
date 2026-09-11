import 'package:sqflite/sqflite.dart';
import '../models/ubicacion_model.dart';
import 'db_helper.dart';

class UbicacionDAO {
  static const String table = 'ubicaciones';

  /// Insertar una ubicación en la cola
  Future<int> insertar(UbicacionModel ubicacion) async {
    final db = await DBHelper().database;
    final id = await db.insert(table, ubicacion.toMap());
    print(
        '[UbicacionDAO] 📥 Insertada ubicación #$id (viaje_id: ${ubicacion.viajeId}, estado: ${ubicacion.estado})');
    return id;
  }

  /// Obtener todas las pendientes (sincronizado = 0)
  Future<List<UbicacionModel>> obtenerPendientes({int limite = 50}) async {
    final db = await DBHelper().database;
    final result = await db.query(
      table,
      where: 'sincronizado = ?',
      whereArgs: [0],
      orderBy: 'fecha_creacion ASC',
      limit: limite,
    );
    return result.map((map) => UbicacionModel.fromMap(map)).toList();
  }

  /// Marcar como sincronizada
  Future<void> marcarSincronizada(int id) async {
    final db = await DBHelper().database;
    await db.update(
      table,
      {'sincronizado': 1},
      where: 'id = ?',
      whereArgs: [id],
    );
    print('[UbicacionDAO] ✅ Ubicación #$id marcada como sincronizada');
  }

  /// Incrementar intentos
  Future<void> incrementarIntentos(int id) async {
    final db = await DBHelper().database;
    await db.rawUpdate(
      'UPDATE $table SET intentos = intentos + 1 WHERE id = ?',
      [id],
    );
  }

  /// Limpiar antiguas (> 24 horas)
  Future<int> limpiarAntiguas() async {
    final db = await DBHelper().database;
    final fechaLimite =
        DateTime.now().subtract(const Duration(hours: 24)).toIso8601String();

    final eliminados = await db.delete(
      table,
      where: 'sincronizado = ? AND fecha_creacion < ?',
      whereArgs: [1, fechaLimite],
    );
    if (eliminados > 0) {
      print('[UbicacionDAO] 🧹 Eliminadas $eliminados ubicaciones antiguas');
    }
    return eliminados;
  }

  /// Contar pendientes
  Future<int> contarPendientes() async {
    final db = await DBHelper().database;
    final result = await db.rawQuery(
      'SELECT COUNT(*) as total FROM $table WHERE sincronizado = 0',
    );
    return Sqflite.firstIntValue(result) ?? 0;
  }

  /// Obtener todas (debug)
  Future<List<UbicacionModel>> obtenerTodas() async {
    final db = await DBHelper().database;
    final result = await db.query(table, orderBy: 'id DESC');
    return result.map((map) => UbicacionModel.fromMap(map)).toList();
  }
}

import 'package:path/path.dart';
import 'package:sqflite/sqflite.dart';

class DBHelper {
  static final DBHelper _instance = DBHelper._internal();
  factory DBHelper() => _instance;
  DBHelper._internal();

  static Database? _database;

  Future<Database> get database async {
    if (_database != null) return _database!;
    _database = await _initDB();
    return _database!;
  }

  Future<Database> _initDB() async {
    final dbPath = await getDatabasesPath();
    final path = join(dbPath, 'app_viajes.db');

    return await openDatabase(
      path,
      version: 1,
      onCreate: _createDB,
      onUpgrade: _upgradeDB,
    );
  }

  Future<void> _createDB(Database db, int version) async {
    await db.execute('''
      CREATE TABLE ubicaciones (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        movil TEXT NOT NULL,
        device_id TEXT,
        viaje_id INTEGER DEFAULT 0,
        estado TEXT NOT NULL,
        lat REAL NOT NULL,
        lng REAL NOT NULL,
        timestamp TEXT NOT NULL,
        sincronizado INTEGER DEFAULT 0,
        intentos INTEGER DEFAULT 0,
        fecha_creacion TEXT NOT NULL
      )
    ''');

    await db.execute('''
      CREATE INDEX idx_sincronizado ON ubicaciones (sincronizado)
    ''');

    print('[DBHelper] ✅ Base de datos creada');
  }

  Future<void> _upgradeDB(Database db, int oldVersion, int newVersion) async {
    print('[DBHelper] ⚠️ Actualizando BD: $oldVersion → $newVersion');
  }

  Future<void> close() async {
    if (_database != null) {
      await _database!.close();
      _database = null;
    }
  }

  Future<void> deleteDB() async {
    final dbPath = await getDatabasesPath();
    final path = join(dbPath, 'app_viajes.db');
    await deleteDatabase(path);
  }
}

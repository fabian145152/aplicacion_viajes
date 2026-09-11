class UbicacionModel {
  int? id;
  final String movil;
  final String deviceId;
  final int viajeId;
  final String estado;
  final double lat;
  final double lng;
  final String timestamp;
  int sincronizado;
  int intentos;
  final String fechaCreacion;

  UbicacionModel({
    this.id,
    required this.movil,
    required this.deviceId,
    this.viajeId = 0,
    required this.estado,
    required this.lat,
    required this.lng,
    required this.timestamp,
    this.sincronizado = 0,
    this.intentos = 0,
    String? fechaCreacion,
  }) : fechaCreacion = fechaCreacion ?? DateTime.now().toIso8601String();

  /// JSON para enviar al servidor (compatible con recibir.php)
  Map<String, dynamic> toJson() {
    return {
      'movil': movil,
      'device_id': deviceId,
      'viaje_id': viajeId.toString(),
      'estado': estado,
      'lat': lat.toString(),
      'lng': lng.toString(),
      'status': estado,
      'timestamp': timestamp,
    };
  }

  /// Map para SQLite
  Map<String, dynamic> toMap() {
    return {
      if (id != null) 'id': id,
      'movil': movil,
      'device_id': deviceId,
      'viaje_id': viajeId,
      'estado': estado,
      'lat': lat,
      'lng': lng,
      'timestamp': timestamp,
      'sincronizado': sincronizado,
      'intentos': intentos,
      'fecha_creacion': fechaCreacion,
    };
  }

  /// Crear desde Map (SQLite)
  factory UbicacionModel.fromMap(Map<String, dynamic> map) {
    return UbicacionModel(
      id: map['id'] as int?,
      movil: map['movil'] as String,
      deviceId: map['device_id'] as String? ?? '',
      viajeId: map['viaje_id'] as int? ?? 0,
      estado: map['estado'] as String,
      lat: (map['lat'] as num).toDouble(),
      lng: (map['lng'] as num).toDouble(),
      timestamp: map['timestamp'] as String,
      sincronizado: map['sincronizado'] as int? ?? 0,
      intentos: map['intentos'] as int? ?? 0,
      fechaCreacion: map['fecha_creacion'] as String?,
    );
  }
}

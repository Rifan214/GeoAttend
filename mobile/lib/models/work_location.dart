class WorkLocation {
  final int id;
  final String name;
  final String? address;
  final double latitude;
  final double longitude;
  final int radiusMeters;
  final int maxAccuracyMeters;

  WorkLocation({
    required this.id,
    required this.name,
    this.address,
    required this.latitude,
    required this.longitude,
    required this.radiusMeters,
    required this.maxAccuracyMeters,
  });

  factory WorkLocation.fromJson(Map<String, dynamic> json) {
    return WorkLocation(
      id: json['id'] is int ? json['id'] : int.parse(json['id'].toString()),
      name: json['name'] as String? ?? '',
      address: json['address'] as String?,
      latitude: json['latitude'] is num ? (json['latitude'] as num).toDouble() : double.parse(json['latitude'].toString()),
      longitude: json['longitude'] is num ? (json['longitude'] as num).toDouble() : double.parse(json['longitude'].toString()),
      radiusMeters: json['radius_meters'] is int ? json['radius_meters'] : int.parse(json['radius_meters'].toString()),
      maxAccuracyMeters: json['max_accuracy_meters'] is int ? json['max_accuracy_meters'] : int.parse(json['max_accuracy_meters'].toString()),
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'address': address,
        'latitude': latitude,
        'longitude': longitude,
        'radius_meters': radiusMeters,
        'max_accuracy_meters': maxAccuracyMeters,
      };
}

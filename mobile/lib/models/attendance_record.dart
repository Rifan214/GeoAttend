class AttendanceRecord {
  final int id;
  final String attendanceDate;
  final String status; // 'present', 'late', 'incomplete'
  final String? checkInAt;
  final double? checkInLatitude;
  final double? checkInLongitude;
  final double? checkInAccuracyMeters;
  final double? checkInDistanceMeters;
  final String? checkOutAt;
  final double? checkOutLatitude;
  final double? checkOutLongitude;
  final double? checkOutAccuracyMeters;
  final double? checkOutDistanceMeters;
  final String? locationName;

  AttendanceRecord({
    required this.id,
    required this.attendanceDate,
    required this.status,
    this.checkInAt,
    this.checkInLatitude,
    this.checkInLongitude,
    this.checkInAccuracyMeters,
    this.checkInDistanceMeters,
    this.checkOutAt,
    this.checkOutLatitude,
    this.checkOutLongitude,
    this.checkOutAccuracyMeters,
    this.checkOutDistanceMeters,
    this.locationName,
  });

  bool get isPresent => status.toLowerCase() == 'present';
  bool get isLate => status.toLowerCase() == 'late';
  bool get isIncomplete => status.toLowerCase() == 'incomplete';

  factory AttendanceRecord.fromJson(Map<String, dynamic> json) {
    String? locName;
    if (json['work_location'] is Map) {
      locName = json['work_location']['name'] as String?;
    }

    return AttendanceRecord(
      id: json['id'] is int ? json['id'] : int.parse(json['id'].toString()),
      attendanceDate: json['attendance_date'] as String? ?? '',
      status: json['status'] as String? ?? 'incomplete',
      checkInAt: json['check_in_at'] as String?,
      checkInLatitude: json['check_in_latitude'] != null ? (json['check_in_latitude'] as num).toDouble() : null,
      checkInLongitude: json['check_in_longitude'] != null ? (json['check_in_longitude'] as num).toDouble() : null,
      checkInAccuracyMeters: json['check_in_accuracy_meters'] != null ? (json['check_in_accuracy_meters'] as num).toDouble() : null,
      checkInDistanceMeters: json['check_in_distance_meters'] != null ? (json['check_in_distance_meters'] as num).toDouble() : null,
      checkOutAt: json['check_out_at'] as String?,
      checkOutLatitude: json['check_out_latitude'] != null ? (json['check_out_latitude'] as num).toDouble() : null,
      checkOutLongitude: json['check_out_longitude'] != null ? (json['check_out_longitude'] as num).toDouble() : null,
      checkOutAccuracyMeters: json['check_out_accuracy_meters'] != null ? (json['check_out_accuracy_meters'] as num).toDouble() : null,
      checkOutDistanceMeters: json['check_out_distance_meters'] != null ? (json['check_out_distance_meters'] as num).toDouble() : null,
      locationName: locName,
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'attendance_date': attendanceDate,
        'status': status,
        'check_in_at': checkInAt,
        'check_in_latitude': checkInLatitude,
        'check_in_longitude': checkInLongitude,
        'check_in_accuracy_meters': checkInAccuracyMeters,
        'check_in_distance_meters': checkInDistanceMeters,
        'check_out_at': checkOutAt,
        'check_out_latitude': checkOutLatitude,
        'check_out_longitude': checkOutLongitude,
        'check_out_accuracy_meters': checkOutAccuracyMeters,
        'check_out_distance_meters': checkOutDistanceMeters,
        'work_location': locationName != null ? {'name': locationName} : null,
      };
}

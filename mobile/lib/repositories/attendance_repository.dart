import '../core/network/api_client.dart';
import '../core/network/api_exception.dart';
import '../models/attendance_history.dart';
import '../models/attendance_record.dart';
import '../models/today_attendance.dart';

class AttendanceRepository {
  final ApiClient _client;

  AttendanceRepository({ApiClient? client}) : _client = client ?? ApiClient();

  Future<TodayAttendance> getTodayAttendance() async {
    final response = await _client.get('/attendance/today');

    if (response is Map<String, dynamic> && response.containsKey('data')) {
      return TodayAttendance.fromJson(response['data'] as Map<String, dynamic>);
    }

    throw ApiException(message: 'Format data status presensi hari ini tidak valid.');
  }

  Future<AttendanceRecord> checkIn({
    required double latitude,
    required double longitude,
    required double accuracyMeters,
  }) async {
    final response = await _client.post(
      '/attendance/check-in',
      body: {
        'latitude': latitude,
        'longitude': longitude,
        'accuracy_meters': accuracyMeters,
      },
    );

    if (response is Map<String, dynamic> && response.containsKey('data')) {
      return AttendanceRecord.fromJson(response['data'] as Map<String, dynamic>);
    }

    throw ApiException(message: 'Format respons check-in tidak valid.');
  }

  Future<AttendanceRecord> checkOut({
    required double latitude,
    required double longitude,
    required double accuracyMeters,
  }) async {
    final response = await _client.post(
      '/attendance/check-out',
      body: {
        'latitude': latitude,
        'longitude': longitude,
        'accuracy_meters': accuracyMeters,
      },
    );

    if (response is Map<String, dynamic> && response.containsKey('data')) {
      return AttendanceRecord.fromJson(response['data'] as Map<String, dynamic>);
    }

    throw ApiException(message: 'Format respons check-out tidak valid.');
  }

  Future<AttendanceHistory> getHistory({
    String? from,
    String? to,
    int page = 1,
    int perPage = 15,
  }) async {
    final queryParams = <String, dynamic>{
      'page': page,
      'per_page': perPage,
    };

    if (from != null && from.isNotEmpty) {
      queryParams['from'] = from;
    }
    if (to != null && to.isNotEmpty) {
      queryParams['to'] = to;
    }

    final response = await _client.get('/attendance/history', queryParams: queryParams);

    if (response is Map<String, dynamic>) {
      return AttendanceHistory.fromJson(response);
    }

    throw ApiException(message: 'Format data riwayat presensi tidak valid.');
  }
}

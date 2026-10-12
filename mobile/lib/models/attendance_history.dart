import 'attendance_record.dart';

class AttendanceHistory {
  final List<AttendanceRecord> records;
  final int currentPage;
  final int lastPage;
  final int perPage;
  final int total;

  AttendanceHistory({
    required this.records,
    required this.currentPage,
    required this.lastPage,
    required this.perPage,
    required this.total,
  });

  bool get hasMore => currentPage < lastPage;

  factory AttendanceHistory.fromJson(Map<String, dynamic> json) {
    final list = <AttendanceRecord>[];
    if (json['data'] is List) {
      for (final item in json['data'] as List) {
        if (item is Map<String, dynamic>) {
          list.add(AttendanceRecord.fromJson(item));
        }
      }
    }

    int cur = 1;
    int last = 1;
    int per = 15;
    int tot = list.length;

    if (json['meta'] is Map) {
      final meta = json['meta'] as Map;
      cur = meta['current_page'] is int ? meta['current_page'] : int.tryParse(meta['current_page'].toString()) ?? 1;
      last = meta['last_page'] is int ? meta['last_page'] : int.tryParse(meta['last_page'].toString()) ?? 1;
      per = meta['per_page'] is int ? meta['per_page'] : int.tryParse(meta['per_page'].toString()) ?? 15;
      tot = meta['total'] is int ? meta['total'] : int.tryParse(meta['total'].toString()) ?? list.length;
    }

    return AttendanceHistory(
      records: list,
      currentPage: cur,
      lastPage: last,
      perPage: per,
      total: tot,
    );
  }
}

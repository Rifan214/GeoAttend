import 'attendance_record.dart';
import 'work_location.dart';
import 'work_schedule.dart';

class TodayAttendance {
  final bool hasCheckedIn;
  final bool hasCheckedOut;
  final AttendanceRecord? attendance;
  final WorkLocation? assignedLocation;
  final WorkSchedule? schedule;
  final String? serverTime;

  TodayAttendance({
    required this.hasCheckedIn,
    required this.hasCheckedOut,
    this.attendance,
    this.assignedLocation,
    this.schedule,
    this.serverTime,
  });

  factory TodayAttendance.fromJson(Map<String, dynamic> json) {
    return TodayAttendance(
      hasCheckedIn: json['has_checked_in'] == true,
      hasCheckedOut: json['has_checked_out'] == true,
      attendance: json['attendance'] != null && json['attendance'] is Map<String, dynamic>
          ? AttendanceRecord.fromJson(json['attendance'] as Map<String, dynamic>)
          : null,
      assignedLocation: json['assigned_location'] != null && json['assigned_location'] is Map<String, dynamic>
          ? WorkLocation.fromJson(json['assigned_location'] as Map<String, dynamic>)
          : null,
      schedule: json['schedule'] != null && json['schedule'] is Map<String, dynamic>
          ? WorkSchedule.fromJson(json['schedule'] as Map<String, dynamic>)
          : null,
      serverTime: json['server_time'] as String?,
    );
  }
}

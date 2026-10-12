import 'package:flutter_test/flutter_test.dart';
import 'package:geoattend/models/attendance_history.dart';
import 'package:geoattend/models/attendance_record.dart';
import 'package:geoattend/models/today_attendance.dart';
import 'package:geoattend/models/user.dart';
import 'package:geoattend/models/work_location.dart';
import 'package:geoattend/models/work_schedule.dart';

void main() {
  group('User Model Tests', () {
    test('parses employee user correctly', () {
      final json = {
        'id': 1,
        'name': 'Budi Pratama',
        'email': 'budi@example.com',
        'role': 'employee',
        'work_location_id': 5,
        'is_active': true,
        'created_at': '2026-10-12T08:00:00Z',
      };

      final user = User.fromJson(json);

      expect(user.id, 1);
      expect(user.name, 'Budi Pratama');
      expect(user.email, 'budi@example.com');
      expect(user.isEmployee, isTrue);
      expect(user.isAdmin, isFalse);
      expect(user.workLocationId, 5);
      expect(user.isActive, isTrue);
    });

    test('parses admin user correctly', () {
      final json = {
        'id': 2,
        'name': 'Admin User',
        'email': 'admin@example.com',
        'role': 'admin',
        'work_location_id': null,
        'is_active': true,
      };

      final user = User.fromJson(json);

      expect(user.isAdmin, isTrue);
      expect(user.isEmployee, isFalse);
      expect(user.workLocationId, isNull);
    });
  });

  group('WorkLocation & WorkSchedule Model Tests', () {
    test('parses work location correctly', () {
      final json = {
        'id': 10,
        'name': 'Kantor Pusat Jakarta',
        'address': 'Jl. Jend. Sudirman No. 1',
        'latitude': -6.2088,
        'longitude': 106.8456,
        'radius_meters': 100,
        'max_accuracy_meters': 50,
      };

      final loc = WorkLocation.fromJson(json);

      expect(loc.id, 10);
      expect(loc.name, 'Kantor Pusat Jakarta');
      expect(loc.latitude, -6.2088);
      expect(loc.longitude, 106.8456);
      expect(loc.radiusMeters, 100);
      expect(loc.maxAccuracyMeters, 50);
    });

    test('parses work schedule correctly', () {
      final json = {
        'id': 3,
        'name': 'Shift Pagi',
        'start_time': '08:00:00',
        'end_time': '17:00:00',
        'late_tolerance_minutes': 15,
      };

      final schedule = WorkSchedule.fromJson(json);

      expect(schedule.id, 3);
      expect(schedule.name, 'Shift Pagi');
      expect(schedule.startTime, '08:00:00');
      expect(schedule.endTime, '17:00:00');
      expect(schedule.lateToleranceMinutes, 15);
    });
  });

  group('AttendanceRecord & TodayAttendance Model Tests', () {
    test('parses attendance record with null check-out correctly', () {
      final json = {
        'id': 101,
        'attendance_date': '2026-10-12',
        'status': 'present',
        'check_in_at': '2026-10-12T08:05:00+07:00',
        'check_in_latitude': -6.2088,
        'check_in_longitude': 106.8456,
        'check_in_accuracy_meters': 12.5,
        'check_in_distance_meters': 18.0,
        'check_out_at': null,
        'check_out_latitude': null,
        'check_out_longitude': null,
        'check_out_accuracy_meters': null,
        'check_out_distance_meters': null,
        'work_location': {
          'id': 10,
          'name': 'Kantor Pusat Jakarta',
        },
      };

      final record = AttendanceRecord.fromJson(json);

      expect(record.id, 101);
      expect(record.status, 'present');
      expect(record.isPresent, isTrue);
      expect(record.isLate, isFalse);
      expect(record.isIncomplete, isFalse);
      expect(record.checkInAt, isNotNull);
      expect(record.checkOutAt, isNull);
      expect(record.locationName, 'Kantor Pusat Jakarta');
    });

    test('parses today attendance summary correctly', () {
      final json = {
        'has_checked_in': true,
        'has_checked_out': false,
        'attendance': {
          'id': 101,
          'attendance_date': '2026-10-12',
          'status': 'late',
          'check_in_at': '2026-10-12T08:25:00+07:00',
        },
        'assigned_location': {
          'id': 10,
          'name': 'Kantor Pusat Jakarta',
          'latitude': -6.2088,
          'longitude': 106.8456,
          'radius_meters': 100,
          'max_accuracy_meters': 50,
        },
        'schedule': {
          'id': 3,
          'name': 'Shift Pagi',
          'start_time': '08:00',
          'end_time': '17:00',
          'late_tolerance_minutes': 15,
        },
        'server_time': '2026-10-12T08:30:00+07:00',
      };

      final today = TodayAttendance.fromJson(json);

      expect(today.hasCheckedIn, isTrue);
      expect(today.hasCheckedOut, isFalse);
      expect(today.attendance, isNotNull);
      expect(today.attendance!.isLate, isTrue);
      expect(today.assignedLocation, isNotNull);
      expect(today.schedule, isNotNull);
    });

    test('parses attendance history pagination correctly', () {
      final json = {
        'data': [
          {
            'id': 1,
            'attendance_date': '2026-10-11',
            'status': 'present',
          },
          {
            'id': 2,
            'attendance_date': '2026-10-12',
            'status': 'incomplete',
          },
        ],
        'meta': {
          'current_page': 1,
          'last_page': 2,
          'per_page': 2,
          'total': 4,
        },
      };

      final history = AttendanceHistory.fromJson(json);

      expect(history.records.length, 2);
      expect(history.currentPage, 1);
      expect(history.lastPage, 2);
      expect(history.hasMore, isTrue);
      expect(history.total, 4);
    });
  });
}

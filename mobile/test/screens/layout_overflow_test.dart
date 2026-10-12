import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:geoattend/models/attendance_history.dart';
import 'package:geoattend/models/attendance_record.dart';
import 'package:geoattend/models/today_attendance.dart';
import 'package:geoattend/models/user.dart';
import 'package:geoattend/repositories/attendance_repository.dart';
import 'package:geoattend/repositories/auth_repository.dart';
import 'package:geoattend/screens/auth/login_screen.dart';
import 'package:geoattend/screens/history/history_screen.dart';
import 'package:geoattend/screens/home/dashboard_screen.dart';
import 'package:geoattend/widgets/status_badge.dart';

class FakeAttendanceRepository implements AttendanceRepository {
  TodayAttendance todayData;
  List<AttendanceRecord> historyRecords;

  FakeAttendanceRepository({
    required this.todayData,
    this.historyRecords = const [],
  });

  @override
  Future<TodayAttendance> getTodayAttendance() async => todayData;

  @override
  Future<AttendanceHistory> getHistory({
    String? from,
    String? to,
    int page = 1,
    int perPage = 15,
  }) async {
    return AttendanceHistory(
      records: historyRecords,
      currentPage: 1,
      lastPage: 1,
      total: historyRecords.length,
      perPage: perPage,
    );
  }

  @override
  Future<AttendanceRecord> checkIn({
    required double latitude,
    required double longitude,
    required double accuracyMeters,
  }) async {
    throw UnimplementedError();
  }

  @override
  Future<AttendanceRecord> checkOut({
    required double latitude,
    required double longitude,
    required double accuracyMeters,
  }) async {
    throw UnimplementedError();
  }
}

class FakeAuthRepository implements AuthRepository {
  @override
  Future<User> login({
    required String email,
    required String password,
    String deviceName = 'Flutter Android App',
  }) async {
    throw UnimplementedError();
  }

  @override
  Future<void> logout() async {}

  @override
  Future<AuthUserSummary> getCurrentUser() async {
    throw UnimplementedError();
  }

  @override
  Future<bool> hasToken() async => true;
}

void main() {
  const vivo1935Size = Size(360, 772);
  const narrowPhoneSize = Size(320, 640);

  group('StatusBadge Responsiveness Tests', () {
    testWidgets('renders without overflow in narrow 200px and 272px constraints', (tester) async {
      for (final status in ['present', 'late', 'incomplete', null]) {
        await tester.pumpWidget(
          MaterialApp(
            home: Scaffold(
              body: Center(
                child: SizedBox(
                  width: 200,
                  child: StatusBadge(status: status),
                ),
              ),
            ),
          ),
        );
        expect(tester.takeException(), isNull);
      }
    });
  });

  group('DashboardScreen Narrow Screen Layout Tests', () {
    final testUser = User.fromJson({
      'id': 1,
      'name': 'Dr. Raden Mas Bagus Muhammad Firdaus Wicaksono, S.Kom., M.T.',
      'email': 'employee@geoattend.test',
      'role': 'employee',
      'is_active': true,
      'work_location_id': 1,
    });

    for (final screenSize in [vivo1935Size, narrowPhoneSize]) {
      for (final testStatus in ['present', 'late', 'incomplete', null]) {
        testWidgets('renders smoothly on ${screenSize.width}x${screenSize.height} with status: $testStatus', (tester) async {
          tester.view.physicalSize = Size(screenSize.width * 2, screenSize.height * 2);
          tester.view.devicePixelRatio = 2.0;
          addTearDown(() {
            tester.view.resetPhysicalSize();
            tester.view.resetDevicePixelRatio();
          });

          final fakeRecord = testStatus == null
              ? null
              : AttendanceRecord.fromJson({
                  'id': 1,
                  'attendance_date': '2026-10-12',
                  'status': testStatus,
                  'check_in_at': '2026-10-12T08:14:22Z',
                  'check_out_at': testStatus == 'present' ? '2026-10-12T17:05:10Z' : null,
                  'check_in_latitude': -6.225,
                  'check_in_longitude': 106.809,
                  'check_in_accuracy_meters': 14.5,
                  'check_in_distance_meters': 38.2,
                  'check_out_latitude': -6.225,
                  'check_out_longitude': 106.809,
                  'check_out_accuracy_meters': 18.2,
                  'check_out_distance_meters': 42.1,
                  'location_name': 'Kantor Pusat Jakarta',
                });

          final fakeRepo = FakeAttendanceRepository(
            todayData: TodayAttendance.fromJson({
              'has_checked_in': testStatus != null,
              'has_checked_out': testStatus == 'present',
              'attendance': fakeRecord?.toJson(),
              'assigned_location': {
                'id': 1,
                'name': 'Kantor Pusat Jakarta - Gedung Menara Mandiri Lantai 21 Kawasan SCBD',
                'address': 'Jl. Jenderal Sudirman Kav. 54-55, Senayan, Kebayoran Baru, Jakarta Selatan',
                'latitude': -6.225,
                'longitude': 106.809,
                'radius_meters': 150,
                'max_accuracy_meters': 50,
              },
              'schedule': {
                'id': 1,
                'name': 'Jadwal Shift Pagi Operasional Lapangan & Kantor Pusat Reguler',
                'start_time': '08:00',
                'end_time': '17:00',
                'late_tolerance_minutes': 15,
              },
              'server_time': '2026-10-12T09:00:00Z',
            }),
          );

          await tester.pumpWidget(
            MaterialApp(
              home: DashboardScreen(
                user: testUser,
                attendanceRepo: fakeRepo,
                authRepo: FakeAuthRepository(),
              ),
            ),
          );

          await tester.pumpAndSettle();

          // Ensure no RenderFlex or layout exceptions occurred
          expect(tester.takeException(), isNull);
          expect(find.text('Status Presensi Hari Ini'), findsOneWidget);
        });
      }
    }
  });

  group('HistoryScreen Narrow Screen Layout Tests', () {
    for (final screenSize in [vivo1935Size, narrowPhoneSize]) {
      testWidgets('renders history records on ${screenSize.width}x${screenSize.height} without overflow', (tester) async {
        tester.view.physicalSize = Size(screenSize.width * 2, screenSize.height * 2);
        tester.view.devicePixelRatio = 2.0;
        addTearDown(() {
          tester.view.resetPhysicalSize();
          tester.view.resetDevicePixelRatio();
        });

        final records = [
          AttendanceRecord.fromJson({
            'id': 1,
            'attendance_date': '2026-10-12',
            'status': 'present',
            'check_in_at': '2026-10-12T07:55:00Z',
            'check_out_at': '2026-10-12T17:05:00Z',
            'check_in_distance_meters': 45.0,
            'check_in_accuracy_meters': 12.0,
            'check_out_distance_meters': 30.0,
            'check_out_accuracy_meters': 10.0,
            'location_name': 'Kantor Pusat Jakarta - Gedung Menara Mandiri Lantai 21 Kawasan Bisnis SCBD',
          }),
          AttendanceRecord.fromJson({
            'id': 2,
            'attendance_date': '2026-10-11',
            'status': 'incomplete',
            'check_in_at': '2026-10-11T08:10:00Z',
            'location_name': 'Kantor Cabang Pembantu Sudirman',
          }),
          AttendanceRecord.fromJson({
            'id': 3,
            'attendance_date': '2026-10-10',
            'status': 'late',
            'check_in_at': '2026-10-10T08:35:00Z',
            'check_out_at': '2026-10-10T17:00:00Z',
            'location_name': 'Kantor Pusat Jakarta',
          }),
        ];

        final fakeRepo = FakeAttendanceRepository(
          todayData: TodayAttendance.fromJson({
            'has_checked_in': false,
            'has_checked_out': false,
            'server_time': '2026-10-12T09:00:00Z',
          }),
          historyRecords: records,
        );

        await tester.pumpWidget(
          MaterialApp(
            home: HistoryScreen(attendanceRepo: fakeRepo),
          ),
        );

        await tester.pumpAndSettle();

        expect(tester.takeException(), isNull);
        expect(find.byType(Card), findsNWidgets(3));
      });
    }
  });

  group('LoginScreen Layout Tests', () {
    for (final screenSize in [vivo1935Size, narrowPhoneSize]) {
      testWidgets('renders login screen on ${screenSize.width}x${screenSize.height} without overflow', (tester) async {
        tester.view.physicalSize = Size(screenSize.width * 2, screenSize.height * 2);
        tester.view.devicePixelRatio = 2.0;
        addTearDown(() {
          tester.view.resetPhysicalSize();
          tester.view.resetDevicePixelRatio();
        });

        await tester.pumpWidget(
          const MaterialApp(
            home: LoginScreen(),
          ),
        );

        await tester.pumpAndSettle();

        expect(tester.takeException(), isNull);
        expect(find.text('Masuk ke Akun'), findsOneWidget);
      });
    }
  });
}

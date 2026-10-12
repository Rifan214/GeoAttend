import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:geoattend/core/network/api_exception.dart';
import 'package:geoattend/core/services/biometric_service.dart';
import 'package:geoattend/core/services/location_service.dart';
import 'package:geoattend/models/attendance_history.dart';
import 'package:geoattend/models/attendance_record.dart';
import 'package:geoattend/models/today_attendance.dart';
import 'package:geoattend/models/user.dart';
import 'package:geoattend/repositories/attendance_repository.dart';
import 'package:geoattend/repositories/auth_repository.dart';
import 'package:geoattend/screens/attendance/attendance_action_dialog.dart';
import 'package:geoattend/screens/auth/login_screen.dart';
import 'package:geoattend/screens/history/history_screen.dart';
import 'package:geoattend/screens/home/dashboard_screen.dart';

class ControllableAuthRepository implements AuthRepository {
  Completer<User>? loginCompleter;
  int loginCallCount = 0;
  bool shouldThrowLogin = false;
  ApiException? loginException;

  Completer<void>? logoutCompleter;
  int logoutCallCount = 0;

  @override
  Future<User> login({
    required String email,
    required String password,
    String deviceName = 'Flutter Android App',
  }) async {
    loginCallCount++;
    if (loginCompleter != null) {
      return await loginCompleter!.future;
    }
    if (shouldThrowLogin) {
      throw loginException ??
          ApiException(
            message: 'Email atau password salah.',
            statusCode: 401,
          );
    }
    return User.fromJson({
      'id': 1,
      'name': 'Test Employee',
      'email': email,
      'role': 'employee',
      'is_active': true,
      'work_location_id': 1,
    });
  }

  @override
  Future<void> logout() async {
    logoutCallCount++;
    if (logoutCompleter != null) {
      await logoutCompleter!.future;
    }
  }

  @override
  Future<AuthUserSummary> getCurrentUser() async {
    throw UnimplementedError();
  }

  @override
  Future<bool> hasToken() async => true;
}

class ControllableAttendanceRepository implements AttendanceRepository {
  Completer<TodayAttendance>? todayCompleter;
  int todayCallCount = 0;
  TodayAttendance? defaultToday;

  Completer<AttendanceHistory>? historyCompleter;
  int historyCallCount = 0;
  bool shouldThrowHistory = false;
  ApiException? historyException;

  Completer<AttendanceRecord>? checkInCompleter;
  int checkInCallCount = 0;
  bool shouldThrowCheckIn = false;
  ApiException? checkInException;

  ControllableAttendanceRepository({this.defaultToday});

  @override
  Future<TodayAttendance> getTodayAttendance() async {
    todayCallCount++;
    if (todayCompleter != null) {
      return await todayCompleter!.future;
    }
    return defaultToday ??
        TodayAttendance.fromJson({
          'has_checked_in': false,
          'has_checked_out': false,
          'attendance': null,
          'assigned_location': {
            'id': 1,
            'name': 'Kantor Pusat Jakarta',
            'latitude': -6.2,
            'longitude': 106.8,
            'radius_meters': 100,
            'max_accuracy_meters': 50,
          },
          'schedule': {
            'id': 1,
            'name': 'Shift Reguler',
            'start_time': '08:00',
            'end_time': '17:00',
            'late_tolerance_minutes': 15,
          },
          'server_time': '2026-10-12T08:00:00Z',
        });
  }

  @override
  Future<AttendanceHistory> getHistory({
    String? from,
    String? to,
    int page = 1,
    int perPage = 15,
  }) async {
    historyCallCount++;
    if (historyCompleter != null) {
      return await historyCompleter!.future;
    }
    if (shouldThrowHistory) {
      throw historyException ??
          ApiException(
            message: 'Gagal menghubungi server.',
            statusCode: 500,
          );
    }
    return AttendanceHistory(
      records: [],
      currentPage: 1,
      lastPage: 1,
      total: 0,
      perPage: perPage,
    );
  }

  @override
  Future<AttendanceRecord> checkIn({
    required double latitude,
    required double longitude,
    required double accuracyMeters,
  }) async {
    checkInCallCount++;
    if (checkInCompleter != null) {
      return await checkInCompleter!.future;
    }
    if (shouldThrowCheckIn) {
      throw checkInException ??
          ApiException(
            message: 'Di luar radius geofence kantor.',
            statusCode: 422,
          );
    }
    return AttendanceRecord.fromJson({
      'id': 1,
      'attendance_date': '2026-10-12',
      'status': 'present',
      'check_in_at': '2026-10-12T08:00:00Z',
    });
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

class FakeLocationService extends LocationService {
  Completer<LocationResult>? locationCompleter;
  LocationResult? locationResult;

  @override
  Future<LocationResult> getCurrentLocation() async {
    if (locationCompleter != null) {
      return await locationCompleter!.future;
    }
    return locationResult ??
        LocationResult.success(
          latitude: -6.2,
          longitude: 106.8,
          accuracyMeters: 10.0,
        );
  }
}

class FakeBiometricService extends BiometricService {
  Completer<BiometricResult>? bioCompleter;
  BiometricResult? bioResult;

  @override
  Future<BiometricResult> authenticate({required String actionName}) async {
    if (bioCompleter != null) {
      return await bioCompleter!.future;
    }
    return bioResult ??
        BiometricResult(
          status: BiometricStatus.success,
          message: 'Biometrik terverifikasi',
        );
  }
}

void main() {
  group('LoginScreen Loading & Duplicate Action Prevention', () {
    testWidgets('disables inputs and buttons during login, preventing duplicate calls', (tester) async {
      final fakeAuth = ControllableAuthRepository();
      fakeAuth.loginCompleter = Completer<User>();

      await tester.pumpWidget(
        MaterialApp(
          home: LoginScreen(authRepo: fakeAuth),
        ),
      );
      await tester.pumpAndSettle();

      // Enter credentials
      await tester.enterText(find.byType(TextFormField).at(0), 'user@geoattend.test');
      await tester.enterText(find.byType(TextFormField).at(1), 'password123');

      final loginBtnFinder = find.widgetWithText(FilledButton, 'Masuk ke Akun');
      expect(loginBtnFinder, findsOneWidget);

      // Tap login button
      await tester.tap(loginBtnFinder);
      await tester.pump();

      // Verify loading state is active
      expect(find.byType(CircularProgressIndicator), findsOneWidget);
      expect(fakeAuth.loginCallCount, 1);

      // Attempt second tap while still loading
      await tester.tap(find.byType(FilledButton));
      await tester.pump();
      expect(fakeAuth.loginCallCount, 1); // Guard prevented duplicate request!

      // Complete login
      fakeAuth.loginCompleter!.complete(User.fromJson({
        'id': 1,
        'name': 'Test User',
        'email': 'user@geoattend.test',
        'role': 'employee',
        'is_active': true,
      }));
      await tester.pump();
      await tester.pump(const Duration(milliseconds: 300));
      expect(find.byType(DashboardScreen), findsOneWidget);
    });

    testWidgets('recovers state gracefully and shows error when login fails', (tester) async {
      final fakeAuth = ControllableAuthRepository();
      fakeAuth.shouldThrowLogin = true;
      fakeAuth.loginException = ApiException(
        message: 'Kredensial tidak valid (401)',
        statusCode: 401,
      );

      await tester.pumpWidget(
        MaterialApp(
          home: LoginScreen(authRepo: fakeAuth),
        ),
      );
      await tester.pumpAndSettle();

      await tester.enterText(find.byType(TextFormField).at(0), 'wrong@geoattend.test');
      await tester.enterText(find.byType(TextFormField).at(1), 'wrongpass');

      await tester.tap(find.widgetWithText(FilledButton, 'Masuk ke Akun'));
      await tester.pumpAndSettle();

      // Error message is displayed
      expect(find.text('Kredensial tidak valid (401)'), findsOneWidget);
      // Button is re-enabled for retry (no persistent loading spinner)
      expect(find.byType(CircularProgressIndicator), findsNothing);
      expect(find.widgetWithText(FilledButton, 'Masuk ke Akun'), findsOneWidget);
    });
  });

  group('DashboardScreen Loading & Action Guards', () {
    final testUser = User.fromJson({
      'id': 1,
      'name': 'Budi Santoso',
      'email': 'budi@geoattend.test',
      'role': 'employee',
      'is_active': true,
    });

    testWidgets('disables attendance action buttons while fetching today data', (tester) async {
      final fakeAttendance = ControllableAttendanceRepository();
      fakeAttendance.todayCompleter = Completer<TodayAttendance>();

      await tester.pumpWidget(
        MaterialApp(
          home: DashboardScreen(
            user: testUser,
            attendanceRepo: fakeAttendance,
            authRepo: ControllableAuthRepository(),
          ),
        ),
      );
      await tester.pump(); // Initial frame where _isFetching = true

      // Action buttons should exist but have onPressed == null
      final checkInButton = tester.widget<FilledButton>(
        find.widgetWithText(FilledButton, 'Check In'),
      );
      expect(checkInButton.onPressed, isNull);

      // Now complete the fetch
      fakeAttendance.todayCompleter!.complete(
        TodayAttendance.fromJson({
          'has_checked_in': false,
          'has_checked_out': false,
          'server_time': '2026-10-12T08:00:00Z',
        }),
      );
      await tester.pumpAndSettle();

      // Action button should now be enabled
      final enabledCheckIn = tester.widget<FilledButton>(
        find.widgetWithText(FilledButton, 'Check In'),
      );
      expect(enabledCheckIn.onPressed, isNotNull);
    });

    testWidgets('shows loading modal and disables logout button during logout', (tester) async {
      final fakeAuth = ControllableAuthRepository();
      fakeAuth.logoutCompleter = Completer<void>();

      await tester.pumpWidget(
        MaterialApp(
          home: DashboardScreen(
            user: testUser,
            attendanceRepo: ControllableAttendanceRepository(),
            authRepo: fakeAuth,
          ),
        ),
      );
      await tester.pumpAndSettle();

      // Tap logout action in AppBar
      await tester.tap(find.widgetWithIcon(IconButton, Icons.logout_rounded));
      await tester.pumpAndSettle();

      // Confirm logout
      await tester.tap(find.widgetWithText(FilledButton, 'Keluar'));
      await tester.pump();

      // Modal loading dialog with text "Sedang keluar..." is shown
      expect(find.text('Sedang keluar...'), findsOneWidget);
      expect(fakeAuth.logoutCallCount, 1);

      // Finish logout
      fakeAuth.logoutCompleter!.complete();
      await tester.pump();
      await tester.pump(const Duration(milliseconds: 300));

      // Navigated to LoginScreen
      expect(find.byType(LoginScreen), findsOneWidget);
    });
  });

  group('HistoryScreen Loading & Filter Guards', () {
    testWidgets('disables date filter actions while history is loading', (tester) async {
      final fakeRepo = ControllableAttendanceRepository();
      fakeRepo.historyCompleter = Completer<AttendanceHistory>();

      await tester.pumpWidget(
        MaterialApp(
          home: HistoryScreen(attendanceRepo: fakeRepo),
        ),
      );
      await tester.pump(); // Starts loading

      // Shows loading indicator
      expect(find.byType(CircularProgressIndicator), findsOneWidget);

      // Filter button in AppBar is disabled while loading
      final filterIconButton = tester.widget<IconButton>(
        find.widgetWithIcon(IconButton, Icons.date_range_outlined),
      );
      expect(filterIconButton.onPressed, isNull);

      // Complete fetch
      fakeRepo.historyCompleter!.complete(
        AttendanceHistory(
          records: [],
          currentPage: 1,
          lastPage: 1,
          total: 0,
          perPage: 15,
        ),
      );
      await tester.pumpAndSettle();

      // Filter button re-enabled
      final enabledFilterBtn = tester.widget<IconButton>(
        find.widgetWithIcon(IconButton, Icons.date_range_outlined),
      );
      expect(enabledFilterBtn.onPressed, isNotNull);
    });

    testWidgets('displays error and enables retry button on history fetch failure', (tester) async {
      final fakeRepo = ControllableAttendanceRepository();
      fakeRepo.shouldThrowHistory = true;
      fakeRepo.historyException = ApiException(
        message: 'Koneksi database bermasalah (500)',
        statusCode: 500,
      );

      await tester.pumpWidget(
        MaterialApp(
          home: HistoryScreen(attendanceRepo: fakeRepo),
        ),
      );
      await tester.pumpAndSettle();

      // Informative error message is visible
      expect(find.text('Koneksi database bermasalah (500)'), findsOneWidget);

      final retryBtnFinder = find.widgetWithText(FilledButton, 'Coba Lagi');
      expect(retryBtnFinder, findsOneWidget);

      final retryButton = tester.widget<FilledButton>(retryBtnFinder);
      expect(retryButton.onPressed, isNotNull);
    });
  });

  group('AttendanceActionDialog PopScope & Execution Guards', () {
    testWidgets('is non-dismissible and disables buttons during workflow execution', (tester) async {
      final fakeLocation = FakeLocationService();
      fakeLocation.locationCompleter = Completer<LocationResult>();

      await tester.pumpWidget(
        MaterialApp(
          home: Scaffold(
            body: AttendanceActionDialog(
              actionType: AttendanceActionType.checkIn,
              attendanceRepo: ControllableAttendanceRepository(),
              locationService: fakeLocation,
              biometricService: FakeBiometricService(),
            ),
          ),
        ),
      );
      await tester.pump(); // Executes initState callback

      // PopScope must NOT allow popping while acquiring location
      final popScope = tester.widget<PopScope>(find.byType(PopScope));
      expect(popScope.canPop, isFalse);

      // Status indicator visible
      expect(find.byType(CircularProgressIndicator), findsOneWidget);
      expect(find.text('Membaca koordinat GPS & akurasi perangkat...'), findsOneWidget);

      // Fail location to allow pop and retry
      fakeLocation.locationCompleter!.complete(
        LocationResult.failure('GPS tidak akurat (±150m)'),
      );
      await tester.pumpAndSettle();

      // Now PopScope allows pop because an error occurred
      final popScopeAfterError = tester.widget<PopScope>(find.byType(PopScope));
      expect(popScopeAfterError.canPop, isTrue);

      expect(find.text('Presensi Gagal'), findsOneWidget);
      expect(find.text('GPS tidak akurat (±150m)'), findsOneWidget);
      expect(find.widgetWithText(TextButton, 'Tutup'), findsOneWidget);
      expect(find.widgetWithText(FilledButton, 'Coba Lagi'), findsOneWidget);
    });
  });
}

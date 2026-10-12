import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../../core/constants/app_colors.dart';
import '../../core/network/api_exception.dart';
import '../../core/services/biometric_service.dart';
import '../../core/services/location_service.dart';
import '../../models/today_attendance.dart';
import '../../models/user.dart';
import '../../repositories/attendance_repository.dart';
import '../../repositories/auth_repository.dart';
import '../../widgets/status_badge.dart';
import '../attendance/attendance_action_dialog.dart';
import '../auth/login_screen.dart';
import '../history/history_screen.dart';

class DashboardScreen extends StatefulWidget {
  final User user;
  final AttendanceRepository? attendanceRepo;
  final AuthRepository? authRepo;
  final LocationService? locationService;
  final BiometricService? biometricService;

  const DashboardScreen({
    super.key,
    required this.user,
    this.attendanceRepo,
    this.authRepo,
    this.locationService,
    this.biometricService,
  });

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  late final AttendanceRepository _attendanceRepo;
  late final AuthRepository _authRepo;
  late final LocationService _locationService;
  late final BiometricService _biometricService;

  TodayAttendance? _todayData;
  bool _isLoading = true;
  bool _isFetching = false;
  bool _isTriggeringAction = false;
  bool _isLoggingOut = false;
  String? _errorMessage;

  final _timeFormat = DateFormat('HH:mm:ss');
  final _dateFormat = DateFormat('EEEE, dd MMMM yyyy');

  @override
  void initState() {
    super.initState();
    _attendanceRepo = widget.attendanceRepo ?? AttendanceRepository();
    _authRepo = widget.authRepo ?? AuthRepository();
    _locationService = widget.locationService ?? LocationService();
    _biometricService = widget.biometricService ?? BiometricService();
    _fetchTodayAttendance();
  }

  Future<void> _fetchTodayAttendance() async {
    if (_isFetching) return;
    _isFetching = true;
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final data = await _attendanceRepo.getTodayAttendance();
      if (mounted) {
        setState(() {
          _todayData = data;
        });
      }
    } on ApiException catch (e) {
      if (mounted) {
        if (e.isUnauthorized) {
          // Token expired, logout and return to login
          _handleLogout(silent: true);
          return;
        }
        setState(() {
          _errorMessage = e.firstErrorMessage;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _errorMessage = 'Gagal memuat status presensi: $e';
        });
      }
    } finally {
      _isFetching = false;
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  Future<void> _handleLogout({bool silent = false}) async {
    if (_isLoggingOut) return;

    if (!silent) {
      final confirm = await showDialog<bool>(
        context: context,
        builder: (ctx) => AlertDialog(
          title: const Text('Konfirmasi Keluar'),
          content: const Text('Apakah Anda yakin ingin keluar dari aplikasi GeoAttend?'),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(ctx, false),
              child: const Text('Batal'),
            ),
            FilledButton(
              onPressed: () => Navigator.pop(ctx, true),
              style: FilledButton.styleFrom(backgroundColor: Colors.red),
              child: const Text('Keluar'),
            ),
          ],
        ),
      );
      if (confirm != true) return;
    }

    if (!mounted) return;
    setState(() => _isLoggingOut = true);

    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (ctx) => const PopScope(
        canPop: false,
        child: AlertDialog(
          content: Row(
            children: [
              CircularProgressIndicator(),
              SizedBox(width: 20),
              Text('Sedang keluar...'),
            ],
          ),
        ),
      ),
    );

    try {
      await _authRepo.logout();
    } finally {
      if (mounted) {
        Navigator.of(context).pushAndRemoveUntil(
          MaterialPageRoute(builder: (_) => const LoginScreen()),
          (route) => false,
        );
      }
    }
  }

  Future<void> _triggerAttendanceAction(AttendanceActionType type) async {
    if (_isTriggeringAction || _isLoading || _isLoggingOut) return;
    setState(() => _isTriggeringAction = true);

    try {
      final success = await showDialog<bool>(
        context: context,
        barrierDismissible: false,
        builder: (ctx) => AttendanceActionDialog(
          actionType: type,
          attendanceRepo: _attendanceRepo,
          locationService: _locationService,
          biometricService: _biometricService,
        ),
      );

      if (success == true) {
        await _fetchTodayAttendance();
      }
    } finally {
      if (mounted) {
        setState(() => _isTriggeringAction = false);
      }
    }
  }

  String _formatTime(String? isoString) {
    if (isoString == null || isoString.isEmpty) return '--:--';
    try {
      final dt = DateTime.parse(isoString).toLocal();
      return _timeFormat.format(dt);
    } catch (_) {
      return isoString;
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final today = _todayData;

    final hasCheckedIn = today?.hasCheckedIn ?? false;
    final hasCheckedOut = today?.hasCheckedOut ?? false;
    final record = today?.attendance;
    final location = today?.assignedLocation;
    final schedule = today?.schedule;

    return Scaffold(
      appBar: AppBar(
        title: const Text(
          'GeoAttend',
          style: TextStyle(fontWeight: FontWeight.bold, letterSpacing: -0.5),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.history_rounded),
            tooltip: 'Riwayat Presensi',
            onPressed: (_isTriggeringAction || _isLoggingOut)
                ? null
                : () {
                    Navigator.of(context).push(
                      MaterialPageRoute(
                        builder: (_) => HistoryScreen(attendanceRepo: _attendanceRepo),
                      ),
                    );
                  },
          ),
          IconButton(
            icon: const Icon(Icons.refresh_rounded),
            tooltip: 'Segarkan',
            onPressed: (_isLoading || _isFetching || _isTriggeringAction || _isLoggingOut)
                ? null
                : _fetchTodayAttendance,
          ),
          IconButton(
            icon: const Icon(Icons.logout_rounded),
            tooltip: 'Keluar',
            onPressed: (_isLoggingOut || _isTriggeringAction) ? null : () => _handleLogout(),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _fetchTodayAttendance,
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // User Greeting Header Card
              Container(
                padding: const EdgeInsets.all(20),
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    colors: [theme.colorScheme.primary, theme.colorScheme.tertiary],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
                  borderRadius: BorderRadius.circular(20),
                  boxShadow: [
                    BoxShadow(
                      color: theme.colorScheme.primary.withValues(alpha: 0.25),
                      blurRadius: 12,
                      offset: const Offset(0, 4),
                    ),
                  ],
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'Selamat Datang,',
                                style: TextStyle(
                                  color: Colors.white.withValues(alpha: 0.85),
                                  fontSize: 13,
                                ),
                              ),
                              const SizedBox(height: 2),
                              Text(
                                widget.user.name,
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 20,
                                  fontWeight: FontWeight.bold,
                                ),
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(width: 12),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                          decoration: BoxDecoration(
                            color: Colors.white.withValues(alpha: 0.2),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Text(
                            widget.user.role.toUpperCase(),
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 11,
                              fontWeight: FontWeight.bold,
                              letterSpacing: 0.5,
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    Text(
                      _dateFormat.format(DateTime.now()),
                      style: TextStyle(
                        color: Colors.white.withValues(alpha: 0.8),
                        fontSize: 12,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 20),

              // Error State Banner
              if (_errorMessage != null) ...[
                Container(
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: theme.colorScheme.errorContainer,
                    borderRadius: BorderRadius.circular(16),
                  ),
                  child: Row(
                    children: [
                      Icon(Icons.error_outline, color: theme.colorScheme.error),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Text(
                          _errorMessage!,
                          style: TextStyle(
                            color: theme.colorScheme.onErrorContainer,
                            fontSize: 13,
                          ),
                        ),
                      ),
                      IconButton(
                        icon: const Icon(Icons.refresh),
                        onPressed: (_isLoading || _isFetching || _isTriggeringAction || _isLoggingOut)
                            ? null
                            : _fetchTodayAttendance,
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 20),
              ],

              // Today's Attendance Main Status Card
              Card(
                elevation: 1,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                child: Padding(
                  padding: const EdgeInsets.all(20),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      LayoutBuilder(
                        builder: (context, constraints) {
                          final isNarrow = constraints.maxWidth < 360;
                          if (isNarrow) {
                            return Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                  children: [
                                    const Expanded(
                                      child: Text(
                                        'Status Presensi Hari Ini',
                                        style: TextStyle(
                                          fontSize: 15,
                                          fontWeight: FontWeight.bold,
                                          color: Colors.black87,
                                        ),
                                      ),
                                    ),
                                    if (_isLoading) ...[
                                      const SizedBox(width: 8),
                                      const SizedBox(
                                        width: 16,
                                        height: 16,
                                        child: CircularProgressIndicator(strokeWidth: 2),
                                      ),
                                    ],
                                  ],
                                ),
                                if (!_isLoading) ...[
                                  const SizedBox(height: 10),
                                  StatusBadge(status: record?.status),
                                ],
                              ],
                            );
                          }
                          return Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              const Expanded(
                                child: Text(
                                  'Status Presensi Hari Ini',
                                  style: TextStyle(
                                    fontSize: 15,
                                    fontWeight: FontWeight.bold,
                                    color: Colors.black87,
                                  ),
                                ),
                              ),
                              const SizedBox(width: 8),
                              if (_isLoading)
                                const SizedBox(
                                  width: 16,
                                  height: 16,
                                  child: CircularProgressIndicator(strokeWidth: 2),
                                )
                              else
                                Flexible(
                                  child: StatusBadge(status: record?.status),
                                ),
                            ],
                          );
                        },
                      ),
                      const SizedBox(height: 20),

                      // Check-in & Check-out Timestamps Box
                      Container(
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          color: theme.colorScheme.surfaceContainerHighest.withValues(alpha: 0.5),
                          borderRadius: BorderRadius.circular(16),
                        ),
                        child: Row(
                          children: [
                            // Check-in Time
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Row(
                                    children: [
                                      Icon(Icons.login_rounded, size: 16, color: AppColors.emeraldDark),
                                      const SizedBox(width: 6),
                                      const Flexible(
                                        child: Text(
                                          'Check-in',
                                          style: TextStyle(fontSize: 12, color: Colors.black54, fontWeight: FontWeight.w500),
                                          maxLines: 1,
                                          overflow: TextOverflow.ellipsis,
                                        ),
                                      ),
                                    ],
                                  ),
                                  const SizedBox(height: 6),
                                  FittedBox(
                                    fit: BoxFit.scaleDown,
                                    alignment: Alignment.centerLeft,
                                    child: Text(
                                      _formatTime(record?.checkInAt),
                                      style: const TextStyle(
                                        fontSize: 18,
                                        fontWeight: FontWeight.bold,
                                        fontFamily: 'monospace',
                                      ),
                                    ),
                                  ),
                                  if (record?.checkInDistanceMeters != null)
                                    Text(
                                      'dist: ${record!.checkInDistanceMeters!.round()}m (±${record.checkInAccuracyMeters?.round() ?? 0}m)',
                                      style: const TextStyle(fontSize: 10, color: Colors.black45),
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                ],
                              ),
                            ),
                            Container(width: 1, height: 48, color: Colors.black12),
                            const SizedBox(width: 16),

                            // Check-out Time
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Row(
                                    children: [
                                      Icon(Icons.logout_rounded, size: 16, color: Colors.blue.shade700),
                                      const SizedBox(width: 6),
                                      const Flexible(
                                        child: Text(
                                          'Check-out',
                                          style: TextStyle(fontSize: 12, color: Colors.black54, fontWeight: FontWeight.w500),
                                          maxLines: 1,
                                          overflow: TextOverflow.ellipsis,
                                        ),
                                      ),
                                    ],
                                  ),
                                  const SizedBox(height: 6),
                                  FittedBox(
                                    fit: BoxFit.scaleDown,
                                    alignment: Alignment.centerLeft,
                                    child: Text(
                                      _formatTime(record?.checkOutAt),
                                      style: const TextStyle(
                                        fontSize: 18,
                                        fontWeight: FontWeight.bold,
                                        fontFamily: 'monospace',
                                      ),
                                    ),
                                  ),
                                  if (record?.checkOutDistanceMeters != null)
                                    Text(
                                      'dist: ${record!.checkOutDistanceMeters!.round()}m (±${record.checkOutAccuracyMeters?.round() ?? 0}m)',
                                      style: const TextStyle(fontSize: 10, color: Colors.black45),
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                ],
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 20),

                      // Action Buttons (Check-in & Check-out)
                      Row(
                        children: [
                          Expanded(
                            child: FilledButton.icon(
                              onPressed: (!hasCheckedIn && !_isLoading && !_isTriggeringAction && !_isLoggingOut)
                                  ? () => _triggerAttendanceAction(AttendanceActionType.checkIn)
                                  : null,
                              icon: const Icon(Icons.fingerprint_rounded),
                              label: const Text('Check In'),
                              style: FilledButton.styleFrom(
                                padding: const EdgeInsets.symmetric(vertical: 14),
                                backgroundColor: AppColors.emerald,
                                disabledBackgroundColor: Colors.black12,
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                              ),
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: FilledButton.icon(
                              onPressed: (hasCheckedIn && !hasCheckedOut && !_isLoading && !_isTriggeringAction && !_isLoggingOut)
                                  ? () => _triggerAttendanceAction(AttendanceActionType.checkOut)
                                  : null,
                              icon: const Icon(Icons.exit_to_app_rounded),
                              label: const Text('Check Out'),
                              style: FilledButton.styleFrom(
                                padding: const EdgeInsets.symmetric(vertical: 14),
                                backgroundColor: Colors.blue.shade700,
                                disabledBackgroundColor: Colors.black12,
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                              ),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 20),

              // Location & Geofence Information Card
              Card(
                elevation: 1,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                child: Padding(
                  padding: const EdgeInsets.all(20),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Icon(Icons.business_rounded, color: theme.colorScheme.primary, size: 20),
                          const SizedBox(width: 8),
                          const Expanded(
                            child: Text(
                              'Lokasi Penugasan Kerja',
                              style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      if (location != null) ...[
                        Text(
                          location.name,
                          style: const TextStyle(fontSize: 15, fontWeight: FontWeight.bold),
                        ),
                        if (location.address != null && location.address!.isNotEmpty) ...[
                          const SizedBox(height: 2),
                          Text(
                            location.address!,
                            style: const TextStyle(fontSize: 12, color: Colors.black54),
                          ),
                        ],
                        const SizedBox(height: 10),
                        Wrap(
                          spacing: 8,
                          runSpacing: 6,
                          children: [
                            Chip(
                              avatar: const Icon(Icons.radar, size: 14),
                              label: Text('Radius: ${location.radiusMeters} m'),
                              labelStyle: const TextStyle(fontSize: 11),
                              visualDensity: VisualDensity.compact,
                            ),
                            Chip(
                              avatar: const Icon(Icons.gps_fixed, size: 14),
                              label: Text('Maks Akurasi: ${location.maxAccuracyMeters} m'),
                              labelStyle: const TextStyle(fontSize: 11),
                              visualDensity: VisualDensity.compact,
                            ),
                          ],
                        ),
                      ] else ...[
                        const Text(
                          'Belum ada lokasi kerja yang ditetapkan untuk akun Anda.',
                          style: TextStyle(fontSize: 13, color: Colors.black54, fontStyle: FontStyle.italic),
                        ),
                      ],
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 20),

              // Work Schedule Card
              if (schedule != null)
                Card(
                  elevation: 1,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                  child: Padding(
                    padding: const EdgeInsets.all(20),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Icon(Icons.schedule_rounded, color: theme.colorScheme.tertiary, size: 20),
                            const SizedBox(width: 8),
                            const Expanded(
                              child: Text(
                                'Jadwal Operasional',
                                style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 12),
                        LayoutBuilder(
                          builder: (context, constraints) {
                            final isVeryNarrow = constraints.maxWidth < 280;
                            if (isVeryNarrow) {
                              return Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    schedule.name,
                                    style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w600),
                                  ),
                                  const SizedBox(height: 4),
                                  Text(
                                    '${schedule.startTime.substring(0, 5)} – ${schedule.endTime.substring(0, 5)} WIB',
                                    style: const TextStyle(
                                      fontSize: 13,
                                      fontWeight: FontWeight.bold,
                                      fontFamily: 'monospace',
                                    ),
                                  ),
                                ],
                              );
                            }
                            return Row(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Expanded(
                                  child: Text(
                                    schedule.name,
                                    style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w600),
                                  ),
                                ),
                                const SizedBox(width: 12),
                                Text(
                                  '${schedule.startTime.substring(0, 5)} – ${schedule.endTime.substring(0, 5)} WIB',
                                  style: const TextStyle(
                                    fontSize: 13,
                                    fontWeight: FontWeight.bold,
                                    fontFamily: 'monospace',
                                  ),
                                ),
                              ],
                            );
                          },
                        ),
                        const SizedBox(height: 6),
                        Text(
                          'Toleransi keterlambatan: ${schedule.lateToleranceMinutes} menit',
                          style: const TextStyle(fontSize: 12, color: Colors.black54),
                        ),
                      ],
                    ),
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }
}

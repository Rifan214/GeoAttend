import 'package:flutter/material.dart';
import '../../core/constants/app_colors.dart';
import '../../core/network/api_exception.dart';
import '../../core/services/biometric_service.dart';
import '../../core/services/location_service.dart';
import '../../models/attendance_record.dart';
import '../../repositories/attendance_repository.dart';

enum AttendanceActionType { checkIn, checkOut }

class AttendanceActionDialog extends StatefulWidget {
  final AttendanceActionType actionType;
  final AttendanceRepository attendanceRepo;
  final LocationService locationService;
  final BiometricService biometricService;

  const AttendanceActionDialog({
    super.key,
    required this.actionType,
    required this.attendanceRepo,
    required this.locationService,
    required this.biometricService,
  });

  @override
  State<AttendanceActionDialog> createState() => _AttendanceActionDialogState();
}

enum _StepState {
  initial,
  acquiringLocation,
  authenticatingBiometrics,
  submittingToBackend,
  success,
  error,
}

class _AttendanceActionDialogState extends State<AttendanceActionDialog> {
  _StepState _currentState = _StepState.initial;
  String _statusText = 'Mempersiapkan proses presensi...';
  String? _errorMessage;
  bool _canOpenSettings = false;
  bool _isOpenLocationSettings = false;
  AttendanceRecord? _resultRecord;
  bool _isExecuting = false;

  String get _actionTitle => widget.actionType == AttendanceActionType.checkIn ? 'Check-in Masuk' : 'Check-out Pulang';

  @override
  void initState() {
    super.initState();
    // Start automated workflow after first frame
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _executeWorkflow();
    });
  }

  Future<void> _executeWorkflow() async {
    if (_isExecuting) return;
    setState(() {
      _isExecuting = true;
    });

    try {
      // -------------------------------------------------------------
      // Step 1: GPS Acquisition & Validation
      // -------------------------------------------------------------
      if (!mounted) return;
      setState(() {
        _currentState = _StepState.acquiringLocation;
        _statusText = 'Membaca koordinat GPS & akurasi perangkat...';
        _errorMessage = null;
        _canOpenSettings = false;
      });

      final locResult = await widget.locationService.getCurrentLocation();
      if (!mounted) return;
      if (!locResult.isSuccess) {
        setState(() {
          _currentState = _StepState.error;
          _errorMessage = locResult.errorMessage ?? 'Gagal membaca lokasi perangkat.';
          _canOpenSettings = locResult.isPermanentlyDenied;
          _isOpenLocationSettings = locResult.isServiceDisabled;
        });
        return;
      }

      final latitude = locResult.latitude!;
      final longitude = locResult.longitude!;
      final accuracy = locResult.accuracyMeters!;

      // -------------------------------------------------------------
      // Step 2: Local Device Biometric Verification (Strict Biometric Only)
      // -------------------------------------------------------------
      if (!mounted) return;
      setState(() {
        _currentState = _StepState.authenticatingBiometrics;
        _statusText = 'Verifikasi biometrik pada perangkat Anda...';
      });

      final bioResult = await widget.biometricService.authenticate(
        actionName: _actionTitle,
      );

      if (!mounted) return;
      if (!bioResult.isSuccess) {
        setState(() {
          _currentState = _StepState.error;
          _errorMessage = bioResult.message;
        });
        return;
      }

      // -------------------------------------------------------------
      // Step 3: Server API Submission (Authoritative Backend)
      // -------------------------------------------------------------
      if (!mounted) return;
      setState(() {
        _currentState = _StepState.submittingToBackend;
        _statusText = 'Mengirim data koordinat ke server untuk validasi geofence...';
      });

      try {
        final record = widget.actionType == AttendanceActionType.checkIn
            ? await widget.attendanceRepo.checkIn(
                latitude: latitude,
                longitude: longitude,
                accuracyMeters: accuracy,
              )
            : await widget.attendanceRepo.checkOut(
                latitude: latitude,
                longitude: longitude,
                accuracyMeters: accuracy,
              );

        if (!mounted) return;
        setState(() {
          _currentState = _StepState.success;
          _resultRecord = record;
          _statusText = 'Presensi ${_actionTitle.toLowerCase()} berhasil dicatat!';
        });
      } on ApiException catch (e) {
        if (!mounted) return;
        setState(() {
          _currentState = _StepState.error;
          _errorMessage = e.firstErrorMessage;
        });
      } catch (e) {
        if (!mounted) return;
        setState(() {
          _currentState = _StepState.error;
          _errorMessage = 'Terjadi kesalahan jaringan atau server: $e';
        });
      }
    } finally {
      if (mounted) {
        setState(() {
          _isExecuting = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return PopScope(
      canPop: _currentState == _StepState.error || _currentState == _StepState.success,
      child: AlertDialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      title: Row(
        children: [
          Icon(
            widget.actionType == AttendanceActionType.checkIn ? Icons.login_rounded : Icons.logout_rounded,
            color: widget.actionType == AttendanceActionType.checkIn ? AppColors.emerald : Colors.blue,
          ),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              _actionTitle,
              overflow: TextOverflow.ellipsis,
            ),
          ),
        ],
      ),
      content: SizedBox(
        width: double.maxFinite,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.center,
          children: [
            const SizedBox(height: 12),
            if (_currentState == _StepState.acquiringLocation ||
                _currentState == _StepState.authenticatingBiometrics ||
                _currentState == _StepState.submittingToBackend) ...[
              const SizedBox(
                width: 48,
                height: 48,
                child: CircularProgressIndicator(strokeWidth: 3),
              ),
              const SizedBox(height: 18),
              Text(
                _statusText,
                textAlign: TextAlign.center,
                style: const TextStyle(fontSize: 14, color: Colors.black87),
              ),
            ] else if (_currentState == _StepState.success) ...[
              Container(
                width: 56,
                height: 56,
                decoration: BoxDecoration(
                  color: AppColors.emerald.withValues(alpha: 0.15),
                  shape: BoxShape.circle,
                ),
                child: const Icon(Icons.check_circle_rounded, color: AppColors.emerald, size: 40),
              ),
              const SizedBox(height: 14),
              Text(
                _statusText,
                textAlign: TextAlign.center,
                style: const TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Colors.black87),
              ),
              const SizedBox(height: 8),
              if (_resultRecord != null)
                Text(
                  'Status: ${_resultRecord!.status.toUpperCase()}',
                  style: TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                    color: _resultRecord!.status == 'present' ? AppColors.emerald : Colors.amber.shade900,
                  ),
                ),
            ] else if (_currentState == _StepState.error) ...[
              Container(
                width: 56,
                height: 56,
                decoration: BoxDecoration(
                  color: theme.colorScheme.errorContainer,
                  shape: BoxShape.circle,
                ),
                child: Icon(Icons.error_outline_rounded, color: theme.colorScheme.error, size: 40),
              ),
              const SizedBox(height: 14),
              Text(
                'Presensi Gagal',
                style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: theme.colorScheme.error),
              ),
              const SizedBox(height: 8),
              Text(
                _errorMessage ?? 'Terjadi kesalahan.',
                textAlign: TextAlign.center,
                style: const TextStyle(fontSize: 13, color: Colors.black87),
              ),
              if (_canOpenSettings) ...[
                const SizedBox(height: 14),
                FilledButton.tonal(
                  onPressed: () => widget.locationService.openAppSettings(),
                  child: const Text('Buka Pengaturan Izin'),
                ),
              ],
              if (_isOpenLocationSettings) ...[
                const SizedBox(height: 14),
                FilledButton.tonal(
                  onPressed: () => widget.locationService.openLocationSettings(),
                  child: const Text('Aktifkan GPS Perangkat'),
                ),
              ],
            ],
            const SizedBox(height: 8),
          ],
        ),
      ),
      actions: [
        if (_currentState == _StepState.error)
          TextButton(
            onPressed: _isExecuting ? null : () => Navigator.pop(context, false),
            child: const Text('Tutup'),
          ),
        if (_currentState == _StepState.error)
          FilledButton(
            onPressed: _isExecuting ? null : _executeWorkflow,
            child: const Text('Coba Lagi'),
          ),
        if (_currentState == _StepState.success)
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Selesai'),
          ),
      ],
    ),
  );
}
}

import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../../core/constants/app_colors.dart';
import '../../core/network/api_exception.dart';
import '../../models/attendance_record.dart';
import '../../repositories/attendance_repository.dart';
import '../../widgets/status_badge.dart';

class HistoryScreen extends StatefulWidget {
  final AttendanceRepository attendanceRepo;

  const HistoryScreen({super.key, required this.attendanceRepo});

  @override
  State<HistoryScreen> createState() => _HistoryScreenState();
}

class _HistoryScreenState extends State<HistoryScreen> {
  final _records = <AttendanceRecord>[];
  bool _isLoading = false;
  bool _isPickingDate = false;
  String? _errorMessage;
  int _currentPage = 1;
  int _lastPage = 1;

  DateTime? _fromDate;
  DateTime? _toDate;

  final _scrollController = ScrollController();
  final _dateFormatter = DateFormat('dd MMM yyyy');
  final _timeFormatter = DateFormat('HH:mm:ss');

  @override
  void initState() {
    super.initState();
    _fetchHistory(page: 1);
    _scrollController.addListener(_onScroll);
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (_scrollController.position.pixels >= _scrollController.position.maxScrollExtent - 200) {
      if (!_isLoading && _currentPage < _lastPage) {
        _fetchHistory(page: _currentPage + 1);
      }
    }
  }

  Future<void> _fetchHistory({int page = 1, bool isRefresh = false}) async {
    if (_isLoading) return;

    setState(() {
      _isLoading = true;
      if (page == 1) _errorMessage = null;
    });

    try {
      final fromStr = _fromDate != null ? DateFormat('yyyy-MM-dd').format(_fromDate!) : null;
      final toStr = _toDate != null ? DateFormat('yyyy-MM-dd').format(_toDate!) : null;

      final history = await widget.attendanceRepo.getHistory(
        from: fromStr,
        to: toStr,
        page: page,
      );

      if (!mounted) return;
      setState(() {
        if (page == 1 || isRefresh) {
          _records.clear();
        }
        _records.addAll(history.records);
        _currentPage = history.currentPage;
        _lastPage = history.lastPage;
      });
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() {
        if (page == 1) _errorMessage = e.firstErrorMessage;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        if (page == 1) _errorMessage = 'Gagal memuat riwayat: $e';
      });
    } finally {
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  Future<void> _pickDateRange() async {
    if (_isPickingDate || _isLoading) return;
    _isPickingDate = true;

    try {
      final now = DateTime.now();
      final picked = await showDateRangePicker(
        context: context,
        firstDate: DateTime(now.year - 1),
        lastDate: DateTime(now.year + 1),
        initialDateRange: _fromDate != null && _toDate != null
            ? DateTimeRange(start: _fromDate!, end: _toDate!)
            : null,
      );

      if (!mounted) return;
      if (picked != null) {
        setState(() {
          _fromDate = picked.start;
          _toDate = picked.end;
        });
        _fetchHistory(page: 1, isRefresh: true);
      }
    } finally {
      _isPickingDate = false;
    }
  }

  void _clearFilter() {
    if (_isLoading) return;
    setState(() {
      _fromDate = null;
      _toDate = null;
    });
    _fetchHistory(page: 1, isRefresh: true);
  }

  String _formatIsoTime(String? isoString) {
    if (isoString == null || isoString.isEmpty) return '-';
    try {
      final dt = DateTime.parse(isoString).toLocal();
      return _timeFormatter.format(dt);
    } catch (_) {
      return isoString;
    }
  }

  String _formatIsoDate(String? isoDate) {
    if (isoDate == null || isoDate.isEmpty) return '-';
    try {
      final dt = DateTime.parse(isoDate);
      return _dateFormatter.format(dt);
    } catch (_) {
      return isoDate;
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Riwayat Presensi'),
        actions: [
          IconButton(
            icon: const Icon(Icons.date_range_outlined),
            tooltip: 'Filter Tanggal',
            onPressed: (_isLoading || _isPickingDate) ? null : _pickDateRange,
          ),
          if (_fromDate != null || _toDate != null)
            IconButton(
              icon: const Icon(Icons.filter_alt_off_outlined),
              tooltip: 'Hapus Filter',
              onPressed: _isLoading ? null : _clearFilter,
            ),
        ],
      ),
      body: Column(
        children: [
          // Filter Chips Banner
          if (_fromDate != null && _toDate != null)
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              color: theme.colorScheme.surfaceContainerHighest,
              child: Row(
                children: [
                  const Icon(Icons.filter_list, size: 16),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      'Filter: ${_dateFormatter.format(_fromDate!)} – ${_dateFormatter.format(_toDate!)}',
                      style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600),
                    ),
                  ),
                  InkWell(
                    onTap: _isLoading ? null : _clearFilter,
                    child: const Icon(Icons.close, size: 16),
                  ),
                ],
              ),
            ),

          // Main List / States
          Expanded(
            child: RefreshIndicator(
              onRefresh: () => _fetchHistory(page: 1, isRefresh: true),
              child: _buildBody(theme),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildBody(ThemeData theme) {
    if (_isLoading && _records.isEmpty) {
      return const Center(child: CircularProgressIndicator());
    }

    if (_errorMessage != null && _records.isEmpty) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(Icons.error_outline, size: 48, color: theme.colorScheme.error),
              const SizedBox(height: 12),
              Text(
                _errorMessage!,
                textAlign: TextAlign.center,
                style: const TextStyle(fontSize: 14),
              ),
              const SizedBox(height: 16),
              FilledButton(
                onPressed: _isLoading ? null : () => _fetchHistory(page: 1),
                child: const Text('Coba Lagi'),
              ),
            ],
          ),
        ),
      );
    }

    if (_records.isEmpty) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.event_busy_outlined, size: 56, color: Colors.black26),
              const SizedBox(height: 12),
              const Text(
                'Belum Ada Riwayat Presensi',
                style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.black87),
              ),
              const SizedBox(height: 4),
              const Text(
                'Data presensi harian Anda akan tercatat dan ditampilkan di sini.',
                textAlign: TextAlign.center,
                style: TextStyle(fontSize: 12, color: Colors.black54),
              ),
            ],
          ),
        ),
      );
    }

    return ListView.builder(
      controller: _scrollController,
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      itemCount: _records.length + (_currentPage < _lastPage ? 1 : 0),
      itemBuilder: (ctx, index) {
        if (index == _records.length) {
          return const Padding(
            padding: EdgeInsets.symmetric(vertical: 16),
            child: Center(child: CircularProgressIndicator(strokeWidth: 2)),
          );
        }

        final record = _records[index];
        return Card(
          margin: const EdgeInsets.only(bottom: 12),
          elevation: 1,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Date & Status Header
                LayoutBuilder(
                  builder: (context, constraints) {
                    final isNarrow = constraints.maxWidth < 340;
                    if (isNarrow) {
                      return Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            _formatIsoDate(record.attendanceDate),
                            style: const TextStyle(fontSize: 15, fontWeight: FontWeight.bold),
                          ),
                          const SizedBox(height: 8),
                          StatusBadge(status: record.status),
                        ],
                      );
                    }
                    return Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          _formatIsoDate(record.attendanceDate),
                          style: const TextStyle(fontSize: 15, fontWeight: FontWeight.bold),
                        ),
                        StatusBadge(status: record.status),
                      ],
                    );
                  },
                ),
                const SizedBox(height: 12),
                const Divider(height: 1),
                const SizedBox(height: 12),

                // Check-in and Check-out Grid
                Row(
                  children: [
                    // Check-in Column
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Icon(Icons.login_rounded, size: 14, color: AppColors.emeraldDark),
                              const SizedBox(width: 4),
                              const Flexible(
                                child: Text(
                                  'Check-in',
                                  style: TextStyle(fontSize: 12, color: Colors.black54),
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 4),
                          FittedBox(
                            fit: BoxFit.scaleDown,
                            alignment: Alignment.centerLeft,
                            child: Text(
                              _formatIsoTime(record.checkInAt),
                              style: const TextStyle(fontSize: 15, fontWeight: FontWeight.bold),
                            ),
                          ),
                          if (record.checkInDistanceMeters != null)
                            Text(
                              'jarak: ${record.checkInDistanceMeters!.round()}m (±${record.checkInAccuracyMeters?.round() ?? 0}m)',
                              style: const TextStyle(fontSize: 11, color: Colors.black45),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                        ],
                      ),
                    ),

                    // Check-out Column
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Icon(Icons.logout_rounded, size: 14, color: Colors.blue.shade700),
                              const SizedBox(width: 4),
                              const Flexible(
                                child: Text(
                                  'Check-out',
                                  style: TextStyle(fontSize: 12, color: Colors.black54),
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 4),
                          FittedBox(
                            fit: BoxFit.scaleDown,
                            alignment: Alignment.centerLeft,
                            child: Text(
                              _formatIsoTime(record.checkOutAt),
                              style: const TextStyle(fontSize: 15, fontWeight: FontWeight.bold),
                            ),
                          ),
                          if (record.checkOutDistanceMeters != null)
                            Text(
                              'jarak: ${record.checkOutDistanceMeters!.round()}m (±${record.checkOutAccuracyMeters?.round() ?? 0}m)',
                              style: const TextStyle(fontSize: 11, color: Colors.black45),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                        ],
                      ),
                    ),
                  ],
                ),

                if (record.locationName != null) ...[
                  const SizedBox(height: 10),
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Padding(
                        padding: EdgeInsets.only(top: 1),
                        child: Icon(Icons.location_on_outlined, size: 13, color: Colors.black45),
                      ),
                      const SizedBox(width: 4),
                      Expanded(
                        child: Text(
                          record.locationName!,
                          style: const TextStyle(fontSize: 11, color: Colors.black54),
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                    ],
                  ),
                ],
              ],
            ),
          ),
        );
      },
    );
  }
}

import 'dart:async';

import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../config/api_client.dart';
import '../theme/app_colors.dart';
import '../theme/app_spacing.dart';
import '../services/local_notification_service.dart';
import '../services/offline_sync_service.dart';
import '../widgets/app_button.dart';
import '../widgets/app_card.dart';
import '../widgets/state_widgets.dart';

class AttendanceScreen extends StatefulWidget {
  const AttendanceScreen({super.key});

  @override
  State<AttendanceScreen> createState() => _AttendanceScreenState();
}

enum _AttendanceTab { summary, applyLeaves, timecard }

enum _CalendarDotStatus { none, present, onLeave, absent }

class _AttendanceScreenState extends State<AttendanceScreen> {
  final Dio _api = ApiClient().dio;

  bool _isLoading = true;
  bool _isSubmitting = false;
  String? _loadError;
  Map<String, dynamic>? _todayRecord;

  bool _monthLoading = true;
  String? _monthError;
  List<Map<String, dynamic>> _monthAttendanceRows = [];
  List<Map<String, dynamic>> _leaveRequests = [];
  Map<String, dynamic>? _todaySchedule;

  _AttendanceTab _activeTab = _AttendanceTab.summary;
  DateTime _visibleMonth = DateTime(
    DateTime.now().year,
    DateTime.now().month,
    1,
  );
  int? _selectedDay;

  Timer? _shiftTimer;
  Duration _shiftDuration = Duration.zero;
  bool _showNineHourReminder = false;
  String? _nineHourReminderClockInKey;

  static const _monthNames = [
    'January',
    'February',
    'March',
    'April',
    'May',
    'June',
    'July',
    'August',
    'September',
    'October',
    'November',
    'December',
  ];

  String? _clockInValue() {
    if (_todayRecord == null) return null;
    return _todayRecord!['clock_in_time']?.toString() ??
        _todayRecord!['clock_in']?.toString();
  }

  String? _clockOutValue() {
    if (_todayRecord == null) return null;
    return _todayRecord!['clock_out_time']?.toString() ??
        _todayRecord!['clock_out']?.toString();
  }

  bool _hasClockedIn() => _clockInValue() != null;
  bool _hasClockedOut() => _clockOutValue() != null;

  bool _hasTimezoneSuffix(String raw) =>
      raw.endsWith('Z') || RegExp(r'[+-]\d{2}:\d{2}$').hasMatch(raw);

  DateTime? _parseAttendanceDateTime(String? raw) {
    if (raw == null) return null;
    final normalized = raw.trim();
    if (normalized.isEmpty) return null;

    try {
      final parsed = DateTime.parse(normalized);
      if (_hasTimezoneSuffix(normalized)) {
        return parsed.toLocal();
      }

      // Backward compatibility for old payloads that omitted timezone.
      final utcParsed = DateTime.parse('${normalized.replaceFirst(' ', 'T')}Z');
      return utcParsed.toLocal();
    } catch (_) {
      return null;
    }
  }

  Duration _nonNegativeDuration(DateTime start, DateTime end) {
    final diff = end.difference(start);
    return diff.isNegative ? Duration.zero : diff;
  }

  String _twoDigits(int n) => n.toString().padLeft(2, '0');

  String _formatClockLabel(String? raw) {
    final parsed = _parseAttendanceDateTime(raw);
    if (parsed == null) return '--:--';
    return '${_twoDigits(parsed.hour)}:${_twoDigits(parsed.minute)}';
  }

  String _formatDateLabel(String? raw) {
    final parsed = _parseAttendanceDateTime(raw);
    if (parsed == null) return '-';
    return '${parsed.year}-${_twoDigits(parsed.month)}-${_twoDigits(parsed.day)}';
  }

  void _maybeNotifyNineHourReminder(DateTime clockInTime, Duration worked) {
    if (worked.inHours < 9 || _hasClockedOut()) return;
    final reminderKey = clockInTime.toUtc().toIso8601String();
    if (_nineHourReminderClockInKey == reminderKey) return;
    _nineHourReminderClockInKey = reminderKey;
    unawaited(LocalNotificationService().showClockOutReminder());
  }

  @override
  void initState() {
    super.initState();
    _selectedDay = DateTime.now().day;
    _bootstrap();
  }

  @override
  void dispose() {
    _shiftTimer?.cancel();
    super.dispose();
  }

  void _startShiftTimer() {
    _shiftTimer?.cancel();
    final clockInTime = _parseAttendanceDateTime(_clockInValue());
    if (clockInTime == null) {
      _shiftDuration = Duration.zero;
      _showNineHourReminder = false;
      _nineHourReminderClockInKey = null;
      unawaited(LocalNotificationService().clearClockOutReminder());
      return;
    }

    final clockOutTime = _parseAttendanceDateTime(_clockOutValue());
    if (clockOutTime != null) {
      _shiftDuration = _nonNegativeDuration(clockInTime, clockOutTime);
      _showNineHourReminder = false;
      _nineHourReminderClockInKey = null;
      unawaited(LocalNotificationService().clearClockOutReminder());
      return;
    }

    final now = DateTime.now();
    _shiftDuration = _nonNegativeDuration(clockInTime, now);
    _showNineHourReminder = _shiftDuration.inHours >= 9;
    _maybeNotifyNineHourReminder(clockInTime, _shiftDuration);

    _shiftTimer = Timer.periodic(const Duration(seconds: 1), (_) {
      if (!mounted) return;
      final difference = _nonNegativeDuration(clockInTime, DateTime.now());
      _maybeNotifyNineHourReminder(clockInTime, difference);
      setState(() {
        _shiftDuration = difference;
        if (difference.inHours >= 9 && !_showNineHourReminder) {
          _showNineHourReminder = true;
        }
      });
    });
  }

  Map<String, dynamic>? _extractMap(dynamic payload) {
    if (payload is Map<String, dynamic>) {
      final data = payload['data'];
      if (data is Map<String, dynamic>) return data;
      return payload;
    }
    return null;
  }

  List<dynamic> _extractList(dynamic payload) {
    if (payload is List) return payload;
    if (payload is Map<String, dynamic>) {
      final data = payload['data'];
      if (data is List) return data;
      if (data is Map<String, dynamic> && data['data'] is List) {
        return List<dynamic>.from(data['data'] as List);
      }
    }
    return [];
  }

  Future<void> _bootstrap() async {
    setState(() {
      _isLoading = true;
      _loadError = null;
    });

    try {
      await Future.wait([
        _fetchTodayStatus(),
        _fetchTodaySchedule(),
        _fetchMonthData(),
      ]);
      if (mounted) {
        setState(() => _isLoading = false);
      }
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _isLoading = false;
        _loadError = 'Failed to load attendance status.';
      });
    }
  }

  Future<void> _fetchTodayStatus() async {
    final response = await _api.get('/attendance/today');
    final data = _extractMap(response.data);

    if (!mounted) return;
    setState(() {
      _todayRecord = data;
    });
    _startShiftTimer();
  }

  Future<void> _fetchMonthData() async {
    setState(() {
      _monthLoading = true;
      _monthError = null;
    });

    final monthStart = DateTime(_visibleMonth.year, _visibleMonth.month, 1);
    final monthEnd = DateTime(_visibleMonth.year, _visibleMonth.month + 1, 0);

    try {
      final responses = await Future.wait([
        _api.get(
          '/attendance',
          queryParameters: {
            'date_from': _toDate(monthStart),
            'date_to': _toDate(monthEnd),
          },
        ),
        _api.get('/leave-requests', queryParameters: {'mine': 1}),
      ]);

      if (!mounted) return;
      setState(() {
        _monthAttendanceRows = _extractList(
          responses[0].data,
        ).whereType<Map<String, dynamic>>().toList();
        _leaveRequests = _extractList(
          responses[1].data,
        ).whereType<Map<String, dynamic>>().toList();
        _monthLoading = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _monthLoading = false;
        _monthError = 'Failed to load month summary.';
      });
    }
  }

  Future<void> _fetchTodaySchedule() async {
    final today = _toDate(DateTime.now());
    final response = await _api.get(
      '/schedules',
      queryParameters: {'date_from': today, 'date_to': today},
    );

    final list = _extractList(response.data).whereType<Map<String, dynamic>>();
    if (!mounted) return;
    setState(() {
      _todaySchedule = list.isEmpty ? null : list.first;
    });
  }

  Future<void> _handleClockInOut() async {
    final hasClockedIn = _hasClockedIn();
    final hasClockedOut = _hasClockedOut();
    final isClockingOut = hasClockedIn && !hasClockedOut;

    if (!isClockingOut && hasClockedIn && hasClockedOut) {
      return;
    }

    if (isClockingOut) {
      final confirmed = await showDialog<bool>(
        context: context,
        builder: (context) => AlertDialog(
          title: const Text('Confirm clock out'),
          content: const Text('Are you sure you want to end your shift?'),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: const Text('Cancel'),
            ),
            FilledButton(
              onPressed: () => Navigator.pop(context, true),
              child: const Text('Clock Out'),
            ),
          ],
        ),
      );
      if (confirmed != true) return;
    }

    setState(() => _isSubmitting = true);
    try {
      final endpoint = isClockingOut
          ? '/attendance/clock-out'
          : '/attendance/clock-in';

      await _api.post(endpoint);

      await Future.wait([_fetchTodayStatus(), _fetchMonthData()]);

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              isClockingOut
                  ? 'Successfully clocked out.'
                  : 'Successfully clocked in.',
            ),
            backgroundColor: AppColors.success,
          ),
        );
      }
    } on DioException catch (e) {
      if (_isConnectivityError(e)) {
        await OfflineSyncService().queueAttendanceEvent(
          isClockOut: isClockingOut,
          localTime: DateTime.now(),
        );
        if (mounted) {
          _applyOfflineAttendanceSnapshot(isClockingOut: isClockingOut);
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text(
                'You are offline. Attendance event saved and will sync automatically.',
              ),
              backgroundColor: AppColors.warning,
            ),
          );
        }
        return;
      }

      final msg = (e.response?.data is Map<String, dynamic>)
          ? (e.response?.data['message']?.toString() ?? 'An error occurred.')
          : 'An error occurred.';

      if (_requiresSalesReportError(e) && mounted) {
        await showDialog<void>(
          context: context,
          builder: (context) => AlertDialog(
            title: const Text('Sales report required'),
            content: const Text(
              'This is a closing shift. Submit today\'s sales report before clocking out.',
            ),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(context),
                child: const Text('Later'),
              ),
              FilledButton(
                onPressed: () {
                  Navigator.pop(context);
                  context.push('/sales');
                },
                child: const Text('Open Sales'),
              ),
            ],
          ),
        );
      }

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(msg), backgroundColor: AppColors.danger),
        );
      }
    } catch (_) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Failed to perform action. Please try again.'),
            backgroundColor: AppColors.danger,
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _isSubmitting = false);
      }
    }
  }

  void _applyOfflineAttendanceSnapshot({required bool isClockingOut}) {
    final nowIso = DateTime.now().toIso8601String();
    final snapshot = Map<String, dynamic>.from(
      _todayRecord ?? <String, dynamic>{},
    );
    if (isClockingOut) {
      snapshot['clock_out_time'] = nowIso;
      snapshot['clock_out'] = nowIso;
    } else {
      snapshot['clock_in_time'] = nowIso;
      snapshot['clock_in'] = nowIso;
    }
    setState(() {
      _todayRecord = snapshot;
    });
    _startShiftTimer();
  }

  bool _isConnectivityError(DioException e) {
    return e.type == DioExceptionType.connectionError ||
        e.type == DioExceptionType.connectionTimeout ||
        e.type == DioExceptionType.receiveTimeout ||
        e.type == DioExceptionType.sendTimeout;
  }

  bool _requiresSalesReportError(DioException e) {
    final data = e.response?.data;
    if (data is Map<String, dynamic>) {
      final errors = data['errors'];
      if (errors is Map && errors['sales_report'] != null) return true;
      final message = data['message']?.toString().toLowerCase() ?? '';
      if (message.contains('sales report') && message.contains('clock-out')) {
        return true;
      }
    }
    return false;
  }

  String _toDate(DateTime date) => date.toIso8601String().split('T').first;

  Duration _rowWorkedDuration(Map<String, dynamic> row) {
    final inRaw =
        row['clock_in_time']?.toString() ?? row['clock_in']?.toString();
    final outRaw =
        row['clock_out_time']?.toString() ?? row['clock_out']?.toString();
    final start = _parseAttendanceDateTime(inRaw);
    if (start == null) return Duration.zero;
    final end = _parseAttendanceDateTime(outRaw) ?? DateTime.now();
    return _nonNegativeDuration(start, end);
  }

  int? _rowDay(Map<String, dynamic> row) {
    final inRaw =
        row['clock_in_time']?.toString() ?? row['clock_in']?.toString();
    final outRaw =
        row['clock_out_time']?.toString() ?? row['clock_out']?.toString();
    final raw = inRaw ?? outRaw;
    final dt = _parseAttendanceDateTime(raw);
    if (dt == null) return null;
    if (dt.year == _visibleMonth.year && dt.month == _visibleMonth.month) {
      return dt.day;
    }
    return null;
  }

  Map<int, Duration> _workedByDay() {
    final map = <int, Duration>{};
    for (final row in _monthAttendanceRows) {
      final day = _rowDay(row);
      if (day == null) continue;
      map[day] = (map[day] ?? Duration.zero) + _rowWorkedDuration(row);
    }
    return map;
  }

  Set<int> _approvedLeaveDaysInMonth() {
    final days = <int>{};
    final monthStart = DateTime(_visibleMonth.year, _visibleMonth.month, 1);
    final monthEnd = DateTime(_visibleMonth.year, _visibleMonth.month + 1, 0);

    for (final leave in _leaveRequests) {
      final status = (leave['status'] ?? '').toString().toLowerCase();
      final workflow = (leave['workflow_status'] ?? '')
          .toString()
          .toLowerCase();
      final approved = status == 'approved' || workflow == 'approved';
      if (!approved) continue;

      final startRaw = leave['start_date']?.toString();
      final endRaw = leave['end_date']?.toString();
      if (startRaw == null || endRaw == null) continue;

      try {
        DateTime start = DateTime.parse(startRaw);
        DateTime end = DateTime.parse(endRaw);
        if (end.isBefore(monthStart) || start.isAfter(monthEnd)) continue;

        if (start.isBefore(monthStart)) start = monthStart;
        if (end.isAfter(monthEnd)) end = monthEnd;

        DateTime cursor = start;
        while (!cursor.isAfter(end)) {
          days.add(cursor.day);
          cursor = cursor.add(const Duration(days: 1));
        }
      } catch (_) {}
    }

    return days;
  }

  int get _daysInMonth =>
      DateUtils.getDaysInMonth(_visibleMonth.year, _visibleMonth.month);

  int _elapsedWorkingDaysInMonth() {
    final now = DateTime.now();
    int endDay = _daysInMonth;

    if (_visibleMonth.year == now.year && _visibleMonth.month == now.month) {
      endDay = now.day;
    } else if (_visibleMonth.isAfter(DateTime(now.year, now.month, 1))) {
      return 0;
    }

    int count = 0;
    for (int day = 1; day <= endDay; day++) {
      final date = DateTime(_visibleMonth.year, _visibleMonth.month, day);
      if (date.weekday != DateTime.friday &&
          date.weekday != DateTime.saturday) {
        count++;
      }
    }
    return count;
  }

  String _durationToHoursLabel(Duration duration) {
    final hrs = duration.inHours;
    final mins = duration.inMinutes.remainder(60).toString().padLeft(2, '0');
    return '$hrs:$mins';
  }

  String _durationToDecimalHours(Duration duration) {
    final hours = duration.inMinutes / 60.0;
    return hours.toStringAsFixed(2);
  }

  Map<String, String> _kpis() {
    final workedByDay = _workedByDay();
    final leaveDays = _approvedLeaveDaysInMonth();

    final daysWorked = workedByDay.entries
        .where((e) => e.value > Duration.zero)
        .length;
    final halfDays = workedByDay.entries.where((e) {
      final h = e.value.inMinutes / 60.0;
      return h > 0 && h < 4;
    }).length;

    final totalDuration = workedByDay.values.fold<Duration>(
      Duration.zero,
      (prev, v) => prev + v,
    );
    final avgWh = daysWorked == 0
        ? 0
        : (totalDuration.inMinutes / 60.0) / daysWorked;
    final deficit = Duration(
      minutes:
          ((daysWorked * 9.0) - (totalDuration.inMinutes / 60.0) < 0
                  ? 0
                  : ((daysWorked * 9.0) - (totalDuration.inMinutes / 60.0)))
              .round() *
          60,
    );

    final absents =
        (_elapsedWorkingDaysInMonth() - daysWorked - leaveDays.length).clamp(
          0,
          9999,
        );

    return {
      'Absents': absents.toString(),
      'On Leave': leaveDays.length.toString(),
      'Half Days': halfDays.toString(),
      'Late In': '0',
      'Early Out': '0',
      'Deficit Hr': _durationToHoursLabel(deficit),
      'Total WH': _durationToDecimalHours(totalDuration),
      'Day(s) Worked': daysWorked.toString(),
      'Avg. WH': avgWh.toStringAsFixed(2),
    };
  }

  _CalendarDotStatus _dayStatus(int day) {
    final date = DateTime(_visibleMonth.year, _visibleMonth.month, day);
    final now = DateTime.now();

    if (date.isAfter(DateTime(now.year, now.month, now.day))) {
      return _CalendarDotStatus.none;
    }

    final leaveDays = _approvedLeaveDaysInMonth();
    if (leaveDays.contains(day)) return _CalendarDotStatus.onLeave;

    final workedByDay = _workedByDay();
    if ((workedByDay[day] ?? Duration.zero) > Duration.zero) {
      return _CalendarDotStatus.present;
    }

    if (date.weekday == DateTime.friday || date.weekday == DateTime.saturday) {
      return _CalendarDotStatus.none;
    }

    return _CalendarDotStatus.absent;
  }

  Color _dotColor(_CalendarDotStatus status) {
    switch (status) {
      case _CalendarDotStatus.present:
        return const Color(0xFF10B981);
      case _CalendarDotStatus.onLeave:
        return const Color(0xFF8B5CF6);
      case _CalendarDotStatus.absent:
        return const Color(0xFF64748B);
      case _CalendarDotStatus.none:
        return Colors.transparent;
    }
  }

  Widget _buildTabChip({
    required String label,
    required _AttendanceTab tab,
    required VoidCallback onTap,
  }) {
    final selected = _activeTab == tab;
    return Expanded(
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 4),
        child: InkWell(
          borderRadius: BorderRadius.circular(999),
          onTap: onTap,
          child: Container(
            alignment: Alignment.center,
            padding: const EdgeInsets.symmetric(vertical: 11, horizontal: 10),
            decoration: BoxDecoration(
              color: selected ? AppColors.brandAccent : Colors.white,
              borderRadius: BorderRadius.circular(999),
              border: Border.all(
                color: selected ? AppColors.brandAccent : AppColors.border,
              ),
            ),
            child: Text(
              label,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                fontWeight: FontWeight.w700,
                color: selected ? Colors.white : AppColors.brandAccent,
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildSummaryTab() {
    final hasClockedIn = _hasClockedIn();
    final hasClockedOut = hasClockedIn && _hasClockedOut();
    final actionLabel = hasClockedOut
        ? 'Shift Complete'
        : (hasClockedIn ? 'Clock Out' : 'Clock In');
    final isClosingShift = _todaySchedule?['is_closing_shift'] == true;
    final shiftName = (_todaySchedule?['shift'] is Map<String, dynamic>)
        ? (_todaySchedule!['shift']['name']?.toString() ?? 'Shift')
        : 'Shift';

    final kpis = _kpis();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        AppCard(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Monthly Summary',
                style: Theme.of(context).textTheme.titleMedium,
              ),
              const SizedBox(height: AppSpacing.sm),
              Row(
                children: [
                  Expanded(
                    child: _buildHighlightKpi(
                      'Absents',
                      kpis['Absents']!,
                      const Color(0xFFE11D48),
                    ),
                  ),
                  const SizedBox(width: AppSpacing.sm),
                  Expanded(
                    child: _buildHighlightKpi(
                      'On Leave',
                      kpis['On Leave']!,
                      const Color(0xFF8B5CF6),
                    ),
                  ),
                  const SizedBox(width: AppSpacing.sm),
                  Expanded(
                    child: _buildHighlightKpi(
                      'Half Days',
                      kpis['Half Days']!,
                      const Color(0xFFF59E0B),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: AppSpacing.sm),
              Wrap(
                runSpacing: AppSpacing.sm,
                spacing: AppSpacing.sm,
                children: [
                  _buildMiniKpi(
                    'Late In',
                    kpis['Late In']!,
                    Icons.schedule,
                    const Color(0xFF06B6D4),
                  ),
                  _buildMiniKpi(
                    'Early Out',
                    kpis['Early Out']!,
                    Icons.watch_later_outlined,
                    const Color(0xFF84CC16),
                  ),
                  _buildMiniKpi(
                    'Deficit Hr',
                    kpis['Deficit Hr']!,
                    Icons.timelapse,
                    const Color(0xFF0EA5E9),
                  ),
                  _buildMiniKpi(
                    'Total WH',
                    kpis['Total WH']!,
                    Icons.calendar_today,
                    const Color(0xFFA78BFA),
                  ),
                  _buildMiniKpi(
                    'Day(s) Worked',
                    kpis['Day(s) Worked']!,
                    Icons.assignment_turned_in,
                    const Color(0xFF60A5FA),
                  ),
                  _buildMiniKpi(
                    'Avg. WH',
                    kpis['Avg. WH']!,
                    Icons.bar_chart,
                    const Color(0xFF22C55E),
                  ),
                ],
              ),
            ],
          ),
        ),
        const SizedBox(height: AppSpacing.sm),
        AppCard(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('Today', style: Theme.of(context).textTheme.titleMedium),
              const SizedBox(height: AppSpacing.xs),
              if (_todaySchedule != null) ...[
                Text(
                  'Assigned: $shiftName${isClosingShift ? ' (Closing)' : ''}',
                  style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                    color: isClosingShift
                        ? AppColors.warning
                        : AppColors.textSecondary,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                const SizedBox(height: 2),
              ],
              Text(
                'Clock In: ${_formatClockLabel(_clockInValue())}',
                style: Theme.of(context).textTheme.bodyMedium,
              ),
              Text(
                'Clock Out: ${_formatClockLabel(_clockOutValue())}',
                style: Theme.of(context).textTheme.bodyMedium,
              ),
              Text(
                'Shift Duration: ${_formatDuration(_shiftDuration)}',
                style: Theme.of(context).textTheme.bodyMedium,
              ),
              if (_showNineHourReminder && !hasClockedOut) ...[
                const SizedBox(height: AppSpacing.xs),
                const Text(
                  'You have spent 9+ hours. Please clock out if your shift is complete.',
                  style: TextStyle(color: AppColors.danger),
                ),
              ],
              const SizedBox(height: AppSpacing.sm),
              Row(
                children: [
                  Expanded(
                    child: AppButton(
                      label: actionLabel,
                      onPressed: (hasClockedOut || _isSubmitting)
                          ? null
                          : _handleClockInOut,
                      leadingIcon: hasClockedOut
                          ? Icons.check_circle
                          : Icons.fingerprint,
                    ),
                  ),
                  const SizedBox(width: AppSpacing.sm),
                  AppButton(
                    label: 'Refresh',
                    variant: AppButtonVariant.outline,
                    onPressed: _isSubmitting ? null : _bootstrap,
                    leadingIcon: Icons.refresh,
                  ),
                ],
              ),
              if (_isSubmitting) ...[
                const SizedBox(height: AppSpacing.sm),
                const LinearProgressIndicator(minHeight: 3),
              ],
            ],
          ),
        ),
        const SizedBox(height: AppSpacing.sm),
        _buildCalendarCard(),
      ],
    );
  }

  Widget _buildHighlightKpi(String label, String value, Color color) {
    return Container(
      padding: const EdgeInsets.all(AppSpacing.sm),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(10),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            value,
            style: TextStyle(
              fontSize: 28,
              fontWeight: FontWeight.w700,
              color: color,
            ),
          ),
          Text(
            label,
            style: TextStyle(fontWeight: FontWeight.w600, color: color),
          ),
        ],
      ),
    );
  }

  Widget _buildMiniKpi(String label, String value, IconData icon, Color tone) {
    return SizedBox(
      width:
          (MediaQuery.of(context).size.width -
              (AppSpacing.md * 2) -
              (AppSpacing.sm * 2)) /
          3,
      child: Row(
        children: [
          CircleAvatar(
            radius: 16,
            backgroundColor: tone.withValues(alpha: 0.14),
            child: Icon(icon, size: 16, color: tone),
          ),
          const SizedBox(width: 6),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  value,
                  style: const TextStyle(fontWeight: FontWeight.w700),
                ),
                Text(
                  label,
                  style: const TextStyle(
                    fontSize: 12,
                    color: AppColors.textSecondary,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildCalendarCard() {
    final firstDay = DateTime(_visibleMonth.year, _visibleMonth.month, 1);
    final firstWeekday = firstDay.weekday % 7; // Sunday=0

    return AppCard(
      child: Column(
        children: [
          Row(
            children: [
              IconButton(
                onPressed: () async {
                  setState(() {
                    _visibleMonth = DateTime(
                      _visibleMonth.year,
                      _visibleMonth.month - 1,
                      1,
                    );
                    _selectedDay = 1;
                  });
                  await _fetchMonthData();
                },
                icon: const Icon(Icons.chevron_left),
              ),
              Expanded(
                child: Text(
                  '${_monthNames[_visibleMonth.month - 1]} ${_visibleMonth.year}',
                  textAlign: TextAlign.center,
                  style: Theme.of(context).textTheme.titleLarge,
                ),
              ),
              TextButton(
                onPressed: () async {
                  final now = DateTime.now();
                  setState(() {
                    _visibleMonth = DateTime(now.year, now.month, 1);
                    _selectedDay = now.day;
                  });
                  await _fetchMonthData();
                },
                child: const Text('Today'),
              ),
              IconButton(
                onPressed: () async {
                  setState(() {
                    _visibleMonth = DateTime(
                      _visibleMonth.year,
                      _visibleMonth.month + 1,
                      1,
                    );
                    _selectedDay = 1;
                  });
                  await _fetchMonthData();
                },
                icon: const Icon(Icons.chevron_right),
              ),
            ],
          ),
          const SizedBox(height: AppSpacing.xs),
          const Row(
            mainAxisAlignment: MainAxisAlignment.spaceAround,
            children: [
              Text(
                'SUN',
                style: TextStyle(
                  fontWeight: FontWeight.w600,
                  color: AppColors.textSecondary,
                ),
              ),
              Text(
                'MON',
                style: TextStyle(
                  fontWeight: FontWeight.w600,
                  color: AppColors.textSecondary,
                ),
              ),
              Text(
                'TUE',
                style: TextStyle(
                  fontWeight: FontWeight.w600,
                  color: AppColors.textSecondary,
                ),
              ),
              Text(
                'WED',
                style: TextStyle(
                  fontWeight: FontWeight.w600,
                  color: AppColors.textSecondary,
                ),
              ),
              Text(
                'THU',
                style: TextStyle(
                  fontWeight: FontWeight.w600,
                  color: AppColors.textSecondary,
                ),
              ),
              Text(
                'FRI',
                style: TextStyle(
                  fontWeight: FontWeight.w600,
                  color: AppColors.textSecondary,
                ),
              ),
              Text(
                'SAT',
                style: TextStyle(
                  fontWeight: FontWeight.w600,
                  color: AppColors.textSecondary,
                ),
              ),
            ],
          ),
          const SizedBox(height: AppSpacing.xs),
          GridView.builder(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            itemCount: firstWeekday + _daysInMonth,
            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: 7,
              mainAxisSpacing: 8,
              crossAxisSpacing: 8,
              childAspectRatio: 0.9,
            ),
            itemBuilder: (context, index) {
              if (index < firstWeekday) return const SizedBox.shrink();
              final day = index - firstWeekday + 1;
              final selected = _selectedDay == day;
              final status = _dayStatus(day);
              final dotColor = _dotColor(status);

              return InkWell(
                onTap: () => setState(() => _selectedDay = day),
                borderRadius: BorderRadius.circular(8),
                child: Container(
                  decoration: BoxDecoration(
                    color: selected
                        ? AppColors.brandAccent.withValues(alpha: 0.18)
                        : Colors.transparent,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Container(
                        width: 10,
                        height: 10,
                        decoration: BoxDecoration(
                          color: dotColor,
                          shape: BoxShape.circle,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        day.toString(),
                        style: TextStyle(
                          fontWeight: selected
                              ? FontWeight.w700
                              : FontWeight.w500,
                          color: selected
                              ? AppColors.brand
                              : AppColors.textPrimary,
                        ),
                      ),
                    ],
                  ),
                ),
              );
            },
          ),
          const SizedBox(height: AppSpacing.sm),
          const Wrap(
            spacing: AppSpacing.md,
            runSpacing: AppSpacing.xs,
            children: [
              _LegendItem(label: 'Present', color: Color(0xFF10B981)),
              _LegendItem(label: 'On Leave', color: Color(0xFF8B5CF6)),
              _LegendItem(label: 'Absent', color: Color(0xFF64748B)),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildApplyLeavesTab() {
    return AppCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Apply for Leave',
            style: Theme.of(context).textTheme.titleMedium,
          ),
          const SizedBox(height: AppSpacing.xs),
          Text(
            'Create a new leave request or review existing ones.',
            style: Theme.of(context).textTheme.bodyMedium,
          ),
          const SizedBox(height: AppSpacing.sm),
          Row(
            children: [
              Expanded(
                child: AppButton(
                  label: 'Apply Leave',
                  onPressed: () => context.push('/leaves'),
                  leadingIcon: Icons.calendar_month,
                ),
              ),
              const SizedBox(width: AppSpacing.sm),
              AppButton(
                label: 'My Leaves',
                variant: AppButtonVariant.outline,
                onPressed: () => context.push('/leaves'),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildTimecardTab() {
    if (_monthLoading) {
      return const AppLoadingState(message: 'Loading timecard...');
    }

    if (_monthError != null) {
      return AppErrorState(message: _monthError!, onRetry: _fetchMonthData);
    }

    if (_monthAttendanceRows.isEmpty) {
      return const AppEmptyState(
        title: 'No timecard entries this month.',
        icon: Icons.event_busy_outlined,
      );
    }

    final rows = List<Map<String, dynamic>>.from(_monthAttendanceRows)
      ..sort((a, b) {
        final aRaw = (a['clock_in_time'] ?? a['clock_in'])?.toString();
        final bRaw = (b['clock_in_time'] ?? b['clock_in'])?.toString();
        final aParsed = _parseAttendanceDateTime(aRaw);
        final bParsed = _parseAttendanceDateTime(bRaw);
        if (aParsed == null && bParsed == null) return 0;
        if (aParsed == null) return 1;
        if (bParsed == null) return -1;
        return bParsed.compareTo(aParsed);
      });

    return Column(
      children: rows.map((row) {
        final inRaw =
            row['clock_in_time']?.toString() ?? row['clock_in']?.toString();
        final outRaw =
            row['clock_out_time']?.toString() ?? row['clock_out']?.toString();
        return AppCard(
          margin: const EdgeInsets.only(bottom: AppSpacing.sm),
          child: Row(
            children: [
              const Icon(Icons.schedule, color: AppColors.brandAccent),
              const SizedBox(width: AppSpacing.sm),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      _formatDateLabel(inRaw ?? outRaw),
                      style: Theme.of(context).textTheme.bodyLarge,
                    ),
                    Text(
                      'In: ${_formatClockLabel(inRaw)} • Out: ${_formatClockLabel(outRaw)}',
                      style: Theme.of(context).textTheme.bodyMedium,
                    ),
                  ],
                ),
              ),
              Text(
                '${_durationToDecimalHours(_rowWorkedDuration(row))}h',
                style: const TextStyle(fontWeight: FontWeight.w700),
              ),
            ],
          ),
        );
      }).toList(),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Attendance')),
      body: SingleChildScrollView(
        padding: AppSpacing.pagePadding,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                _buildTabChip(
                  label: 'Summary',
                  tab: _AttendanceTab.summary,
                  onTap: () =>
                      setState(() => _activeTab = _AttendanceTab.summary),
                ),
                _buildTabChip(
                  label: 'Apply Leaves',
                  tab: _AttendanceTab.applyLeaves,
                  onTap: () =>
                      setState(() => _activeTab = _AttendanceTab.applyLeaves),
                ),
                _buildTabChip(
                  label: 'Timecard',
                  tab: _AttendanceTab.timecard,
                  onTap: () =>
                      setState(() => _activeTab = _AttendanceTab.timecard),
                ),
              ],
            ),
            const SizedBox(height: AppSpacing.md),
            if (_isLoading)
              const AppLoadingState(message: 'Loading attendance details...')
            else if (_loadError != null)
              AppErrorState(message: _loadError!, onRetry: _bootstrap)
            else ...[
              if (_activeTab == _AttendanceTab.summary) _buildSummaryTab(),
              if (_activeTab == _AttendanceTab.applyLeaves)
                _buildApplyLeavesTab(),
              if (_activeTab == _AttendanceTab.timecard) _buildTimecardTab(),
            ],
          ],
        ),
      ),
    );
  }

  String _formatDuration(Duration duration) {
    String twoDigits(int n) => n.toString().padLeft(2, '0');
    final minutes = twoDigits(duration.inMinutes.remainder(60));
    final seconds = twoDigits(duration.inSeconds.remainder(60));
    return '${twoDigits(duration.inHours)}:$minutes:$seconds';
  }
}

class _LegendItem extends StatelessWidget {
  final String label;
  final Color color;

  const _LegendItem({required this.label, required this.color});

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          width: 8,
          height: 8,
          decoration: BoxDecoration(color: color, shape: BoxShape.circle),
        ),
        const SizedBox(width: 6),
        Text(
          label,
          style: const TextStyle(
            color: AppColors.textSecondary,
            fontSize: 12,
            fontWeight: FontWeight.w600,
          ),
        ),
      ],
    );
  }
}

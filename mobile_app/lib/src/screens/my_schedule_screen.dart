import 'package:dio/dio.dart';
import 'package:flutter/material.dart';

import '../config/api_client.dart';
import '../theme/app_colors.dart';
import '../theme/app_spacing.dart';
import '../widgets/app_card.dart';
import '../widgets/state_widgets.dart';

class MyScheduleScreen extends StatefulWidget {
  const MyScheduleScreen({super.key});

  @override
  State<MyScheduleScreen> createState() => _MyScheduleScreenState();
}

class _MyScheduleScreenState extends State<MyScheduleScreen> {
  final Dio _api = ApiClient().dio;
  bool _loading = true;
  List<dynamic> _schedules = [];
  String? _errorMessage;
  String? _assignedStoreName;
  final DateTime _fromDate = DateTime.now();
  final DateTime _toDate = DateTime.now().add(const Duration(days: 7));

  static const List<String> _weekdays = [
    'Mon',
    'Tue',
    'Wed',
    'Thu',
    'Fri',
    'Sat',
    'Sun',
  ];
  static const List<String> _months = [
    'Jan',
    'Feb',
    'Mar',
    'Apr',
    'May',
    'Jun',
    'Jul',
    'Aug',
    'Sep',
    'Oct',
    'Nov',
    'Dec',
  ];

  @override
  void initState() {
    super.initState();
    _loadSchedules();
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

  String? _extractAssignedStoreName(dynamic payload) {
    if (payload is! Map<String, dynamic>) return null;
    final data = payload['data'];
    if (data is! Map<String, dynamic>) return null;
    final assigned = data['assigned_store'];
    if (assigned is! Map<String, dynamic>) return null;
    final name = assigned['name']?.toString().trim();
    if (name == null || name.isEmpty) return null;
    return name;
  }

  int? _extractEmployeeId(dynamic payload) {
    if (payload is! Map<String, dynamic>) return null;
    final data = payload['data'];
    if (data is! Map<String, dynamic>) return null;
    final id = data['id'];
    if (id is int) return id;
    if (id is num) return id.toInt();
    return int.tryParse(id?.toString() ?? '');
  }

  Future<void> _loadSchedules() async {
    setState(() {
      _loading = true;
      _errorMessage = null;
    });
    try {
      final meResponse = await _api.get('/employees/me');
      final employeeId = _extractEmployeeId(meResponse.data);
      final queryParameters = <String, dynamic>{
        'date_from': _fromDate.toIso8601String().split('T').first,
        'date_to': _toDate.toIso8601String().split('T').first,
      };
      if (employeeId != null) {
        queryParameters['employee_id'] = employeeId;
      }
      final schedulesResponse = await _api.get(
        '/schedules',
        queryParameters: queryParameters,
      );

      if (!mounted) return;
      setState(() {
        _schedules = _extractList(schedulesResponse.data);
        _assignedStoreName = _extractAssignedStoreName(meResponse.data);
        _loading = false;
      });
    } on DioException {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _errorMessage = 'Failed to load your schedule.';
      });
    }
  }

  String _dateRangeLabel() {
    final from = '${_months[_fromDate.month - 1]} ${_fromDate.day}';
    final to = '${_months[_toDate.month - 1]} ${_toDate.day}';
    if (_fromDate.year == _toDate.year) {
      return '$from - $to, ${_toDate.year}';
    }
    return '$from, ${_fromDate.year} - $to, ${_toDate.year}';
  }

  DateTime? _parseDate(dynamic value) {
    if (value == null) return null;
    return DateTime.tryParse(value.toString());
  }

  String _weekdayLabel(String dateRaw) {
    final dt = _parseDate(dateRaw);
    if (dt == null) return '--';
    return _weekdays[dt.weekday - 1];
  }

  String _dayOfMonthLabel(String dateRaw) {
    final dt = _parseDate(dateRaw);
    if (dt == null) return '--';
    return dt.day.toString().padLeft(2, '0');
  }

  String _fullDateLabel(String dateRaw) {
    final dt = _parseDate(dateRaw);
    if (dt == null) return dateRaw;
    return '${_weekdays[dt.weekday - 1]}, ${_months[dt.month - 1]} ${dt.day}, ${dt.year}';
  }

  String _formatTime(String rawValue) {
    final parsed = _parseDate(rawValue);
    if (parsed != null) {
      final hour = parsed.hour % 12 == 0 ? 12 : parsed.hour % 12;
      final minute = parsed.minute.toString().padLeft(2, '0');
      final meridian = parsed.hour >= 12 ? 'PM' : 'AM';
      return '$hour:$minute $meridian';
    }

    final value = rawValue.trim();
    final timePart = value.contains('T')
        ? value.split('T').last
        : value.contains(' ')
        ? value.split(' ').last
        : value;
    final match = RegExp(r'^(\d{2}):(\d{2})').firstMatch(timePart);
    if (match == null) return value;

    final sourceHour = int.tryParse(match.group(1) ?? '');
    final sourceMinute = match.group(2) ?? '00';
    if (sourceHour == null) return value;

    final hour = sourceHour % 12 == 0 ? 12 : sourceHour % 12;
    final meridian = sourceHour >= 12 ? 'PM' : 'AM';
    return '$hour:$sourceMinute $meridian';
  }

  int _closingShiftCount() {
    return _schedules.where((item) {
      if (item is! Map<String, dynamic>) return false;
      return item['is_closing_shift'] == true;
    }).length;
  }

  Color _shiftAccentColor({
    required String shiftName,
    required bool isClosing,
  }) {
    if (isClosing) return AppColors.warning;
    final normalized = shiftName.toLowerCase();
    if (normalized.contains('am')) return AppColors.info;
    if (normalized.contains('pm')) return const Color(0xFF0F766E);
    return AppColors.brandAccent;
  }

  bool _isCompactLayout(BuildContext context) {
    return MediaQuery.of(context).size.width <= 375;
  }

  EdgeInsets _listPadding(BuildContext context) {
    if (_isCompactLayout(context)) {
      return const EdgeInsets.symmetric(horizontal: 12, vertical: 12);
    }
    return AppSpacing.pagePadding;
  }

  String _displayDateLabel(String dateRaw, {required bool compact}) {
    if (!compact) return _fullDateLabel(dateRaw);
    final dt = _parseDate(dateRaw);
    if (dt == null) return dateRaw;
    return '${_weekdays[dt.weekday - 1]}, ${_months[dt.month - 1]} ${dt.day}';
  }

  Widget _buildHeaderCard({required bool compact}) {
    final total = _schedules.length;
    final closing = _closingShiftCount();

    return Container(
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(compact ? 14 : 16),
        gradient: const LinearGradient(
          colors: [Color(0xFF1E293B), Color(0xFF334155)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.12),
            blurRadius: 16,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      padding: EdgeInsets.all(compact ? AppSpacing.sm : AppSpacing.md),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Upcoming Schedule',
            style: TextStyle(
              fontSize: compact ? 18 : 20,
              fontWeight: FontWeight.w700,
              color: Colors.white,
            ),
          ),
          SizedBox(height: compact ? AppSpacing.xxs : AppSpacing.xs),
          Text(
            _dateRangeLabel(),
            style: TextStyle(
              color: Color(0xFFE2E8F0),
              fontSize: compact ? 13 : 14,
              fontWeight: FontWeight.w500,
            ),
          ),
          if (_assignedStoreName != null && _assignedStoreName!.isNotEmpty) ...[
            const SizedBox(height: AppSpacing.sm),
            Row(
              children: [
                const Icon(
                  Icons.storefront_outlined,
                  size: 16,
                  color: Color(0xFFBFDBFE),
                ),
                const SizedBox(width: AppSpacing.xs),
                Expanded(
                  child: Text(
                    _assignedStoreName!,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                      color: Color(0xFFDBEAFE),
                      fontSize: compact ? 12 : 13,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
              ],
            ),
          ],
          const SizedBox(height: AppSpacing.sm),
          Wrap(
            spacing: AppSpacing.xs,
            runSpacing: AppSpacing.xs,
            children: [
              _buildHeaderStatChip(
                label: 'Shifts',
                value: '$total',
                color: const Color(0xFF38BDF8),
                compact: compact,
              ),
              _buildHeaderStatChip(
                label: 'Closing',
                value: '$closing',
                color: const Color(0xFFFBBF24),
                compact: compact,
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildHeaderStatChip({
    required String label,
    required String value,
    required Color color,
    required bool compact,
  }) {
    return Container(
      padding: EdgeInsets.symmetric(
        horizontal: compact ? 8 : 10,
        vertical: compact ? 5 : 6,
      ),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.2),
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: color.withValues(alpha: 0.4)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(
            '$label: ',
            style: TextStyle(
              color: Colors.white,
              fontSize: compact ? 11 : 12,
              fontWeight: FontWeight.w600,
            ),
          ),
          Text(
            value,
            style: TextStyle(
              color: Colors.white,
              fontSize: compact ? 11 : 12,
              fontWeight: FontWeight.w800,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildScheduleCard(Map<String, dynamic> row, {required bool compact}) {
    final store = row['store'] is Map<String, dynamic>
        ? row['store'] as Map<String, dynamic>
        : null;
    final shift = row['shift'] is Map<String, dynamic>
        ? row['shift'] as Map<String, dynamic>
        : null;

    final date = row['date']?.toString() ?? '-';
    final storeName =
        store?['name']?.toString() ?? _assignedStoreName ?? 'Store';
    final shiftName = shift?['name']?.toString() ?? 'Shift';
    final start = shift?['start_time']?.toString() ?? '--';
    final end = shift?['end_time']?.toString() ?? '--';
    final isClosing = row['is_closing_shift'] == true;

    final accent = _shiftAccentColor(
      shiftName: shiftName,
      isClosing: isClosing,
    );
    final gap = compact ? AppSpacing.xs : AppSpacing.sm;

    return AppCard(
      margin: const EdgeInsets.only(bottom: AppSpacing.sm),
      borderColor: accent.withValues(alpha: 0.35),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 6,
            height: 92,
            decoration: BoxDecoration(
              color: accent,
              borderRadius: BorderRadius.circular(999),
            ),
          ),
          SizedBox(width: gap),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Container(
                      padding: EdgeInsets.symmetric(
                        horizontal: compact ? 6 : AppSpacing.xs,
                        vertical: compact ? 2 : AppSpacing.xxs,
                      ),
                      decoration: BoxDecoration(
                        color: AppColors.background,
                        borderRadius: BorderRadius.circular(compact ? 8 : 10),
                        border: Border.all(color: AppColors.border),
                      ),
                      child: Column(
                        children: [
                          Text(
                            _weekdayLabel(date),
                            style: TextStyle(
                              color: AppColors.textSecondary,
                              fontSize: compact ? 9 : 10,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                          Text(
                            _dayOfMonthLabel(date),
                            style: TextStyle(
                              color: AppColors.textPrimary,
                              fontSize: compact ? 13 : 14,
                              fontWeight: FontWeight.w800,
                            ),
                          ),
                        ],
                      ),
                    ),
                    SizedBox(width: gap),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            _displayDateLabel(date, compact: compact),
                            maxLines: compact ? 1 : 2,
                            overflow: TextOverflow.ellipsis,
                            style: TextStyle(
                              color: AppColors.textPrimary,
                              fontSize: compact ? 14 : 15,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                          const SizedBox(height: 2),
                          Row(
                            children: [
                              const Icon(
                                Icons.location_on_outlined,
                                size: 14,
                                color: AppColors.textSecondary,
                              ),
                              const SizedBox(width: 4),
                              Expanded(
                                child: Text(
                                  storeName,
                                  maxLines: compact ? 1 : 2,
                                  overflow: TextOverflow.ellipsis,
                                  style: TextStyle(
                                    color: AppColors.textSecondary,
                                    fontSize: compact ? 12 : 13,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                    if (isClosing && !compact)
                      Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: AppSpacing.xs,
                          vertical: AppSpacing.xxs,
                        ),
                        decoration: BoxDecoration(
                          color: AppColors.warning.withValues(alpha: 0.18),
                          borderRadius: BorderRadius.circular(999),
                          border: Border.all(
                            color: AppColors.warning.withValues(alpha: 0.45),
                          ),
                        ),
                        child: const Text(
                          'Closing',
                          style: TextStyle(
                            color: Color(0xFFB45309),
                            fontSize: 11,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ),
                  ],
                ),
                SizedBox(height: gap),
                Wrap(
                  spacing: AppSpacing.xs,
                  runSpacing: AppSpacing.xs,
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: AppSpacing.xs,
                        vertical: AppSpacing.xxs,
                      ),
                      decoration: BoxDecoration(
                        color: accent.withValues(alpha: 0.14),
                        borderRadius: BorderRadius.circular(999),
                      ),
                      child: Text(
                        shiftName.toUpperCase(),
                        style: TextStyle(
                          color: accent,
                          fontSize: compact ? 10 : 11,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ),
                    if (isClosing && compact)
                      Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: AppSpacing.xs,
                          vertical: AppSpacing.xxs,
                        ),
                        decoration: BoxDecoration(
                          color: AppColors.warning.withValues(alpha: 0.18),
                          borderRadius: BorderRadius.circular(999),
                          border: Border.all(
                            color: AppColors.warning.withValues(alpha: 0.45),
                          ),
                        ),
                        child: const Text(
                          'Closing',
                          style: TextStyle(
                            color: Color(0xFFB45309),
                            fontSize: 11,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ),
                  ],
                ),
                const SizedBox(height: 4),
                Text(
                  '${_formatTime(start)} - ${_formatTime(end)}',
                  style: TextStyle(
                    color: AppColors.textPrimary,
                    fontSize: compact ? 12 : 13,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                if (isClosing) ...[
                  const SizedBox(height: 6),
                  Text(
                    'Closing shift: complete handover and submit sales before clock out.',
                    style: TextStyle(
                      color: AppColors.textSecondary,
                      fontSize: compact ? 11 : 12,
                    ),
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('My Schedule'),
        backgroundColor: AppColors.brand,
        foregroundColor: Colors.white,
      ),
      body: RefreshIndicator(
        onRefresh: _loadSchedules,
        child: ListView(
          padding: _listPadding(context),
          children: [
            _buildHeaderCard(compact: _isCompactLayout(context)),
            const SizedBox(height: AppSpacing.md),
            if (_loading)
              const AppLoadingState(message: 'Loading your upcoming shifts...')
            else if (_errorMessage != null)
              AppErrorState(message: _errorMessage!, onRetry: _loadSchedules)
            else if (_schedules.isEmpty)
              const AppEmptyState(
                title: 'No schedule found for this period.',
                subtitle: 'New shifts will appear here once published.',
                icon: Icons.event_busy_outlined,
              )
            else
              ..._schedules.whereType<Map<String, dynamic>>().map(
                (row) =>
                    _buildScheduleCard(row, compact: _isCompactLayout(context)),
              ),
          ],
        ),
      ),
    );
  }
}

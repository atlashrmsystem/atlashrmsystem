import 'package:dio/dio.dart';
import 'package:flutter/material.dart';

import '../config/api_client.dart';
import '../theme/app_colors.dart';
import '../theme/app_spacing.dart';
import '../widgets/app_button.dart';
import '../widgets/app_card.dart';
import '../widgets/state_widgets.dart';

class ScheduleManagementScreen extends StatefulWidget {
  const ScheduleManagementScreen({super.key});

  @override
  State<ScheduleManagementScreen> createState() =>
      _ScheduleManagementScreenState();
}

class _ScheduleManagementScreenState extends State<ScheduleManagementScreen> {
  final Dio _api = ApiClient().dio;

  bool _loading = true;
  bool _saving = false;
  bool _publishing = false;
  bool _savingShiftTemplates = false;
  String? _errorMessage;

  List<Map<String, dynamic>> _stores = [];
  List<Map<String, dynamic>> _staff = [];
  List<Map<String, dynamic>> _shifts = [];
  List<Map<String, dynamic>> _schedules = [];

  int? _storeId;
  int? _assignedStoreId;
  String? _assignedStoreName;

  DateTime _weekStart = _startOfWeek(DateTime.now());
  bool _selectionMode = false;
  final Set<String> _selectedCells = <String>{};
  bool _advancedToolsExpanded = false;
  bool _editPublishedMode = false;
  String _weekPublishStatus = 'draft';
  String? _weekPublishedAt;
  List<DateTime> _knownScheduleWeeks = [];
  int? _bulkShiftId;
  String? _bulkTemplateCode;
  bool _bulkClosing = false;
  bool _bulkApplyOff = false;
  final Map<String, _ShiftTemplateDraft> _shiftTemplates = {
    'AM': const _ShiftTemplateDraft(),
    'MID': const _ShiftTemplateDraft(),
    'PM': const _ShiftTemplateDraft(),
    'PH': const _ShiftTemplateDraft(),
  };

  static DateTime _startOfWeek(DateTime date) {
    final weekday = date.weekday; // Mon=1..Sun=7
    return DateTime(
      date.year,
      date.month,
      date.day,
    ).subtract(Duration(days: weekday - 1));
  }

  @override
  void initState() {
    super.initState();
    _bootstrap();
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

  Map<String, dynamic>? _extractMap(dynamic payload) {
    if (payload is! Map<String, dynamic>) return null;
    final data = payload['data'];
    if (data is Map<String, dynamic>) {
      return Map<String, dynamic>.from(data);
    }
    return payload;
  }

  int? _toInt(dynamic value) {
    if (value is int) return value;
    if (value is num) return value.toInt();
    return int.tryParse(value?.toString() ?? '');
  }

  int? _scheduleEmployeeId(Map<String, dynamic> schedule) {
    final direct = _toInt(schedule['employee_id']);
    if (direct != null) return direct;
    final employee = schedule['employee'];
    if (employee is Map<String, dynamic>) {
      return _toInt(employee['id']);
    }
    return null;
  }

  int? _scheduleShiftId(Map<String, dynamic> schedule) {
    final direct = _toInt(schedule['shift_id']);
    if (direct != null) return direct;
    final shift = schedule['shift'];
    if (shift is Map<String, dynamic>) {
      return _toInt(shift['id']);
    }
    return null;
  }

  int? _extractAssignedStoreId(dynamic payload) {
    if (payload is! Map<String, dynamic>) return null;
    final data = payload['data'];
    if (data is! Map<String, dynamic>) return null;
    final assigned = data['assigned_store'];
    if (assigned is! Map<String, dynamic>) return null;
    return _toInt(assigned['id']);
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

  bool _storesContain(int id) {
    return _stores.any((s) => _toInt(s['id']) == id);
  }

  String _selectedStoreLabel() {
    if (_storeId == null) return _assignedStoreName ?? 'No assigned store';
    for (final store in _stores) {
      if (_toInt(store['id']) == _storeId) {
        final name = store['name']?.toString().trim();
        if (name != null && name.isNotEmpty) return name;
      }
    }
    return _assignedStoreName ?? 'No assigned store';
  }

  String _toDate(DateTime date) => date.toIso8601String().split('T').first;

  DateTime _normalizedDate(DateTime date) {
    return DateTime(date.year, date.month, date.day);
  }

  bool _sameWeekStart(DateTime a, DateTime b) {
    final left = _normalizedDate(a);
    final right = _normalizedDate(b);
    return left.year == right.year &&
        left.month == right.month &&
        left.day == right.day;
  }

  List<DateTime> _sortedKnownWeeks() {
    final copy = _knownScheduleWeeks.map(_normalizedDate).toList();
    copy.sort((a, b) => a.compareTo(b));
    return copy;
  }

  bool _weekExistsInKnownList(DateTime weekStart) {
    final target = _startOfWeek(weekStart);
    return _knownScheduleWeeks.any((known) => _sameWeekStart(known, target));
  }

  int _weekIndexFor(DateTime weekStart) {
    final target = _startOfWeek(weekStart);
    final sorted = _sortedKnownWeeks();
    for (var i = 0; i < sorted.length; i++) {
      if (_sameWeekStart(sorted[i], target)) {
        return i + 1;
      }
      if (target.isBefore(sorted[i])) {
        return i + 1;
      }
    }
    return sorted.length + 1;
  }

  String _weekHeadingLabel() {
    final index = _weekIndexFor(_weekStart);
    final total = _knownScheduleWeeks.length;
    if (total == 0) return 'Week 1';
    final exists = _weekExistsInKnownList(_weekStart);
    if (exists) return 'Week $index of $total';
    return 'Week $index (New)';
  }

  String _toTimeHHmm(dynamic raw) {
    final value = raw?.toString().trim() ?? '';
    if (value.isEmpty) return '';
    final match = RegExp(r'(\d{1,2}:\d{2})(?::\d{2})?$').firstMatch(value);
    if (match != null) {
      final parts = match.group(1)!.split(':');
      final hour = parts[0].padLeft(2, '0');
      return '$hour:${parts[1]}';
    }
    return '';
  }

  DateTime _nextUnplannedWeekAfter(DateTime weekStart) {
    var candidate = _startOfWeek(weekStart).add(const Duration(days: 7));
    var guard = 0;
    while (_weekExistsInKnownList(candidate) && guard < 104) {
      candidate = candidate.add(const Duration(days: 7));
      guard++;
    }
    return candidate;
  }

  bool get _isWeekPublished => _weekPublishStatus == 'published';

  bool get _hasUnpublishedChanges =>
      _weekPublishStatus == 'edited_after_publish';

  bool get _weekLocked =>
      (_isWeekPublished || _hasUnpublishedChanges) && !_editPublishedMode;

  bool get _canUnlockPublishedWeek =>
      _isWeekPublished || _hasUnpublishedChanges;

  String _publishStatusLabel() {
    switch (_weekPublishStatus) {
      case 'published':
        return 'Published';
      case 'edited_after_publish':
        return 'Changes Pending Publish';
      default:
        return 'Draft';
    }
  }

  Color _publishStatusColor() {
    switch (_weekPublishStatus) {
      case 'published':
        return AppColors.success;
      case 'edited_after_publish':
        return AppColors.warning;
      default:
        return AppColors.textSecondary;
    }
  }

  String _publishPrimaryActionLabel() {
    if (_publishing) return 'Publishing...';
    if (_hasUnpublishedChanges) return 'Update Changes';
    if (_isWeekPublished) return 'Validate Published Week';
    return 'Publish Week';
  }

  String _workflowHint() {
    if (_weekLocked) {
      return 'This week is locked. Tap "Edit Published Schedule" to make changes.';
    }
    if (_editPublishedMode && _canUnlockPublishedWeek) {
      return 'Edit mode active. Apply updates, then tap "Update Changes".';
    }
    if (_hasUnpublishedChanges) {
      return 'This week has edits after publish. Re-publish to update staff view.';
    }
    if (_isWeekPublished) {
      return 'Published week is visible to staff.';
    }
    return 'Set shift times, assign staff, and publish.';
  }

  String? _publishedAtLabel() {
    final value = _weekPublishedAt;
    if (value == null || value.isEmpty) return null;
    final parsed = DateTime.tryParse(value);
    if (parsed == null) return null;
    final local = parsed.toLocal();
    final mm = local.month.toString().padLeft(2, '0');
    final dd = local.day.toString().padLeft(2, '0');
    final hh = local.hour.toString().padLeft(2, '0');
    final min = local.minute.toString().padLeft(2, '0');
    return '${local.year}-$mm-$dd $hh:$min';
  }

  Future<void> _enablePublishedEditMode() async {
    if (_storeId == null) return;
    setState(() {
      _editPublishedMode = true;
      _selectionMode = false;
      _selectedCells.clear();
      _advancedToolsExpanded = false;
    });
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('Edit mode enabled for this published week.'),
        backgroundColor: AppColors.info,
      ),
    );
  }

  Future<void> _goToWeek(DateTime weekStart) async {
    setState(() {
      _weekStart = _startOfWeek(weekStart);
      _loading = true;
      _selectionMode = false;
      _selectedCells.clear();
      _editPublishedMode = false;
      _advancedToolsExpanded = false;
    });
    try {
      await _loadWeekData();
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _createNewSchedule() async {
    if (_storeId == null) return;

    final hasCurrentWeekData =
        _schedules.isNotEmpty ||
        _canUnlockPublishedWeek ||
        _weekExistsInKnownList(_weekStart);
    final targetWeek = hasCurrentWeekData
        ? _nextUnplannedWeekAfter(_weekStart)
        : _weekStart;

    setState(() {
      _weekStart = _startOfWeek(targetWeek);
      _loading = true;
      _selectionMode = false;
      _selectedCells.clear();
      _editPublishedMode = false;
      _advancedToolsExpanded = true;
    });

    try {
      await _loadWeekData();
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }

    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          'Ready to create ${_weekHeadingLabel()} • ${_weekRangeLabel()}',
        ),
        backgroundColor: AppColors.info,
      ),
    );
  }

  bool _looksLikeTemplateShift(String name, String template) {
    final lower = name.toLowerCase().trim();
    return switch (template) {
      'AM' => lower == 'am' || lower == 'morning',
      'MID' => lower == 'mid' || lower == 'middle',
      'PM' => lower == 'pm' || lower == 'evening' || lower == 'night',
      'PH' => lower == 'ph' || lower == 'public holiday' || lower == 'holiday',
      'OFF' => lower == 'off',
      _ => false,
    };
  }

  void _syncShiftTemplatesFromShifts() {
    final next = <String, _ShiftTemplateDraft>{
      'AM': const _ShiftTemplateDraft(),
      'MID': const _ShiftTemplateDraft(),
      'PM': const _ShiftTemplateDraft(),
      'PH': const _ShiftTemplateDraft(),
    };

    for (final entry in next.entries) {
      final key = entry.key;
      Map<String, dynamic>? matched;
      for (final shift in _shifts) {
        final name = shift['name']?.toString() ?? '';
        if (_looksLikeTemplateShift(name, key)) {
          matched = shift;
          break;
        }
      }
      if (matched != null) {
        next[key] = _ShiftTemplateDraft(
          id: _toInt(matched['id']),
          start: _toTimeHHmm(matched['start_time']),
          end: _toTimeHHmm(matched['end_time']),
        );
      }
    }

    _shiftTemplates
      ..clear()
      ..addAll(next);
  }

  List<DateTime> _weekDays() {
    return List<DateTime>.generate(
      7,
      (index) => _weekStart.add(Duration(days: index)),
    );
  }

  int? _templateShiftId(String template) {
    for (final shift in _shifts) {
      final name = shift['name']?.toString() ?? '';
      if (_looksLikeTemplateShift(name, template)) {
        return _toInt(shift['id']);
      }
    }
    return null;
  }

  ({String start, String end}) _defaultTimesForTemplate(String template) {
    return switch (template) {
      'AM' => (start: '08:00', end: '17:00'),
      'MID' => (start: '12:00', end: '21:00'),
      'PM' => (start: '16:00', end: '23:00'),
      'PH' => (start: '00:00', end: '00:00'),
      'OFF' => (start: '00:00', end: '00:00'),
      _ => (start: '09:00', end: '17:00'),
    };
  }

  Future<int?> _ensureTemplateShift(String template) async {
    final existing = _templateShiftId(template);
    if (existing != null || _storeId == null) return existing;

    final draft = _shiftTemplates[template] ?? const _ShiftTemplateDraft();
    final defaults = _defaultTimesForTemplate(template);
    final payload = {
      'store_id': _storeId,
      'name': template,
      'start_time': template == 'PH' || template == 'OFF'
          ? defaults.start
          : (draft.start.isEmpty ? defaults.start : draft.start),
      'end_time': template == 'PH' || template == 'OFF'
          ? defaults.end
          : (draft.end.isEmpty ? defaults.end : draft.end),
    };

    await _api.post('/shifts', data: payload);
    await _loadWeekData();
    return _templateShiftId(template);
  }

  String _weekRangeLabel() {
    final end = _weekStart.add(const Duration(days: 6));
    return '${_toDate(_weekStart)} to ${_toDate(end)}';
  }

  String _monthShort(int month) {
    const names = <String>[
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
    return names[month - 1];
  }

  String _weekRangePrettyLabel() {
    final start = _weekStart;
    final end = _weekStart.add(const Duration(days: 6));
    if (start.year == end.year && start.month == end.month) {
      return '${_monthShort(start.month)} ${start.day}-${end.day}, ${end.year}';
    }
    if (start.year == end.year) {
      return '${_monthShort(start.month)} ${start.day} - ${_monthShort(end.month)} ${end.day}, ${end.year}';
    }
    return '${_monthShort(start.month)} ${start.day}, ${start.year} - ${_monthShort(end.month)} ${end.day}, ${end.year}';
  }

  bool _isCurrentWeek() {
    return _sameWeekStart(_weekStart, _startOfWeek(DateTime.now()));
  }

  Future<void> _bootstrap() async {
    setState(() {
      _loading = true;
      _errorMessage = null;
    });

    try {
      final responses = await Future.wait([
        _api.get('/stores'),
        _api.get('/employees/me'),
      ]);

      _stores = _extractList(
        responses[0].data,
      ).whereType<Map<String, dynamic>>().toList();
      _assignedStoreId = _extractAssignedStoreId(responses[1].data);
      _assignedStoreName = _extractAssignedStoreName(responses[1].data);

      if (_stores.isNotEmpty) {
        if (_assignedStoreId != null && _storesContain(_assignedStoreId!)) {
          _storeId = _assignedStoreId;
        } else {
          _storeId = _toInt(_stores.first['id']);
        }
      }

      await _loadWeekData();

      if (!mounted) return;
      setState(() => _loading = false);
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _errorMessage = 'Failed to load schedule data.';
      });
    }
  }

  Future<void> _loadWeekData() async {
    if (_storeId == null) {
      if (!mounted) return;
      setState(() {
        _staff = [];
        _shifts = [];
        _schedules = [];
        _knownScheduleWeeks = [];
      });
      return;
    }

    final futures = await Future.wait([
      _api.get('/staff', queryParameters: {'store_id': _storeId}),
      _api.get('/shifts', queryParameters: {'store_id': _storeId}),
      _api.get(
        '/schedules',
        queryParameters: {
          'store_id': _storeId,
          'date_from': _toDate(_weekStart),
          'date_to': _toDate(_weekStart.add(const Duration(days: 6))),
        },
      ),
      _api.get(
        '/schedules/week-status',
        queryParameters: {
          'store_id': _storeId,
          'week_start': _toDate(_weekStart),
        },
      ),
      _api.get('/schedules/weeks', queryParameters: {'store_id': _storeId}),
    ]);

    if (!mounted) return;
    final weekStatusData = _extractMap(futures[3].data) ?? <String, dynamic>{};
    final nextWeekStatus = weekStatusData['status']?.toString() ?? 'draft';
    final currentWeekSchedules = _extractList(
      futures[2].data,
    ).whereType<Map<String, dynamic>>().toList();
    final rawWeeks = _extractList(
      futures[4].data,
    ).whereType<Map<String, dynamic>>();
    final weekMap = <int, DateTime>{};
    for (final row in rawWeeks) {
      final raw = row['week_start']?.toString();
      if (raw == null || raw.isEmpty) continue;
      final parsed = DateTime.tryParse(raw);
      if (parsed == null) continue;
      final normalized = _startOfWeek(parsed);
      weekMap[normalized.millisecondsSinceEpoch] = normalized;
    }
    if (currentWeekSchedules.isNotEmpty || nextWeekStatus != 'draft') {
      final normalizedCurrent = _startOfWeek(_weekStart);
      weekMap[normalizedCurrent.millisecondsSinceEpoch] = normalizedCurrent;
    }
    final knownWeeks = weekMap.values.toList()..sort((a, b) => a.compareTo(b));

    setState(() {
      _staff = _extractList(
        futures[0].data,
      ).whereType<Map<String, dynamic>>().toList();
      _shifts = _extractList(
        futures[1].data,
      ).whereType<Map<String, dynamic>>().toList();
      _schedules = currentWeekSchedules;
      _knownScheduleWeeks = knownWeeks;
      _weekPublishStatus = nextWeekStatus;
      _weekPublishedAt = weekStatusData['published_at']?.toString();
      if (nextWeekStatus == 'draft') {
        _editPublishedMode = false;
      }
      _syncShiftTemplatesFromShifts();
      final hasSelectedShift = _shifts.any(
        (s) => _toInt(s['id']) == _bulkShiftId,
      );
      if (!hasSelectedShift) {
        _bulkShiftId = _shifts.isNotEmpty ? _toInt(_shifts.first['id']) : null;
      }
      _bulkTemplateCode = null;
      _bulkApplyOff = false;
      _selectedCells.clear();
    });
  }

  Future<void> _changeWeek(int deltaDays) async {
    setState(() {
      _weekStart = _weekStart.add(Duration(days: deltaDays));
      _loading = true;
      _selectionMode = false;
      _selectedCells.clear();
      _editPublishedMode = false;
      _advancedToolsExpanded = false;
    });
    try {
      await _loadWeekData();
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _goToCurrentWeek() async {
    setState(() {
      _weekStart = _startOfWeek(DateTime.now());
      _loading = true;
      _selectionMode = false;
      _selectedCells.clear();
      _editPublishedMode = false;
      _advancedToolsExpanded = false;
    });
    try {
      await _loadWeekData();
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _pickShiftTime({
    required String template,
    required bool isStart,
  }) async {
    if (_weekLocked) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text(
            'Week is published. Tap "Edit Published Schedule" first.',
          ),
          backgroundColor: AppColors.warning,
        ),
      );
      return;
    }

    final current = _shiftTemplates[template] ?? const _ShiftTemplateDraft();
    final raw = isStart ? current.start : current.end;
    final parts = raw.split(':');
    final initial = (parts.length == 2)
        ? TimeOfDay(
            hour: int.tryParse(parts[0]) ?? 9,
            minute: int.tryParse(parts[1]) ?? 0,
          )
        : const TimeOfDay(hour: 9, minute: 0);

    final picked = await showTimePicker(context: context, initialTime: initial);
    if (picked == null) return;
    final value =
        '${picked.hour.toString().padLeft(2, '0')}:${picked.minute.toString().padLeft(2, '0')}';

    setState(() {
      final draft = _shiftTemplates[template] ?? const _ShiftTemplateDraft();
      _shiftTemplates[template] = isStart
          ? draft.copyWith(start: value)
          : draft.copyWith(end: value);
    });
  }

  Future<void> _saveShiftTemplates() async {
    if (_storeId == null) return;
    if (_weekLocked) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text(
            'Week is published. Tap "Edit Published Schedule" first.',
          ),
          backgroundColor: AppColors.warning,
        ),
      );
      return;
    }

    for (final key in const ['AM', 'MID', 'PM']) {
      final draft = _shiftTemplates[key] ?? const _ShiftTemplateDraft();
      if (draft.start.isEmpty || draft.end.isEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Set start/end time for $key shift.'),
            backgroundColor: AppColors.warning,
          ),
        );
        return;
      }
    }

    setState(() => _savingShiftTemplates = true);
    try {
      for (final key in const ['AM', 'MID', 'PM', 'PH']) {
        final draft = _shiftTemplates[key]!;
        final defaults = _defaultTimesForTemplate(key);
        final payload = {
          'store_id': _storeId,
          'name': key,
          'start_time': key == 'PH'
              ? defaults.start
              : (draft.start.isEmpty ? defaults.start : draft.start),
          'end_time': key == 'PH'
              ? defaults.end
              : (draft.end.isEmpty ? defaults.end : draft.end),
        };
        if (draft.id != null) {
          await _api.put('/shifts/${draft.id}', data: payload);
        } else {
          await _api.post('/shifts', data: payload);
        }
      }

      await _loadWeekData();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('AM/MID/PM/PH shift timings saved.'),
            backgroundColor: AppColors.success,
          ),
        );
      }
    } on DioException catch (e) {
      var message = 'Failed to save shifts.';
      final data = e.response?.data;
      if (data is Map<String, dynamic>) {
        message = data['message']?.toString() ?? message;
        final errors = data['errors'];
        if (errors is Map && errors.isNotEmpty) {
          final first = errors.values.first;
          if (first is List && first.isNotEmpty) {
            message = first.first.toString();
          }
        }
      }
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(message), backgroundColor: AppColors.danger),
        );
      }
    } finally {
      if (mounted) setState(() => _savingShiftTemplates = false);
    }
  }

  Map<String, dynamic>? _findSchedule(int employeeId, DateTime date) {
    final dateStr = _toDate(date);
    for (final schedule in _schedules) {
      final sid = _scheduleEmployeeId(schedule);
      final sdate = schedule['date']?.toString();
      if (sid == employeeId && sdate == dateStr) {
        return schedule;
      }
    }
    return null;
  }

  String _shiftShortName(Map<String, dynamic>? schedule) {
    if (schedule == null) return 'Off';
    final shift = schedule['shift'];
    String? rawName;
    if (shift is Map<String, dynamic>) {
      rawName = shift['name']?.toString();
    }

    if (rawName == null || rawName.trim().isEmpty) {
      final sid = _scheduleShiftId(schedule);
      if (sid != null) {
        final matched = _shifts.where((s) => _toInt(s['id']) == sid);
        if (matched.isNotEmpty) {
          rawName = matched.first['name']?.toString();
        }
      }
    }

    final normalized = rawName?.trim().toUpperCase() ?? '';
    if (normalized == 'AM' ||
        normalized == 'PM' ||
        normalized == 'MID' ||
        normalized == 'PH') {
      return normalized;
    }
    if (normalized == 'MORNING') return 'AM';
    if (normalized == 'EVENING' || normalized == 'NIGHT') return 'PM';
    if (normalized == 'MIDDLE') return 'MID';
    if (normalized == 'PUBLIC HOLIDAY' || normalized == 'HOLIDAY') return 'PH';

    if (normalized.isEmpty) return 'Shift';
    if (normalized.length <= 8) return normalized;
    return normalized.substring(0, 8);
  }

  String _scheduleStatusLabel(Map<String, dynamic>? schedule) {
    if (schedule == null) return 'Off';
    if (_shiftShortName(schedule) == 'OFF') return 'Off';
    if (schedule['is_closing_shift'] == true) return 'Closing';
    return 'Assigned';
  }

  Color _scheduleStatusColor(Map<String, dynamic>? schedule) {
    if (schedule == null) return AppColors.textSecondary;
    if (_shiftShortName(schedule) == 'OFF') return AppColors.textSecondary;
    if (schedule['is_closing_shift'] == true) return AppColors.warning;
    return AppColors.success;
  }

  Color _scheduleCellColor(Map<String, dynamic>? schedule) {
    if (schedule == null) return Colors.white;
    if (_shiftShortName(schedule) == 'OFF') return Colors.white;
    if (schedule['is_closing_shift'] == true) {
      return AppColors.warning.withValues(alpha: 0.14);
    }
    return AppColors.success.withValues(alpha: 0.12);
  }

  String _cellKey(int employeeId, DateTime date) {
    return '$employeeId|${_toDate(date)}';
  }

  void _toggleCellSelection(int employeeId, DateTime date) {
    final key = _cellKey(employeeId, date);
    setState(() {
      if (_selectedCells.contains(key)) {
        _selectedCells.remove(key);
      } else {
        _selectedCells.add(key);
      }
    });
  }

  Future<void> _applyBulkSelection() async {
    if (_storeId == null || _selectedCells.isEmpty) return;
    if (_weekLocked) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text(
            'Week is published. Tap "Edit Published Schedule" first.',
          ),
          backgroundColor: AppColors.warning,
        ),
      );
      return;
    }
    if (!_bulkApplyOff && _bulkShiftId == null && _bulkTemplateCode == null) {
      return;
    }

    if (_bulkApplyOff) {
      try {
        final ensuredOff = await _ensureTemplateShift('OFF');
        if (ensuredOff == null) return;
        _bulkShiftId = ensuredOff;
      } on DioException catch (e) {
        final message = (e.response?.data is Map<String, dynamic>)
            ? (e.response?.data['message']?.toString() ??
                  'Failed to create OFF shift.')
            : 'Failed to create OFF shift.';
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(message), backgroundColor: AppColors.danger),
          );
        }
        return;
      }
    } else if (_bulkShiftId == null && _bulkTemplateCode != null) {
      try {
        final ensured = await _ensureTemplateShift(_bulkTemplateCode!);
        if (ensured == null) return;
        _bulkShiftId = ensured;
      } on DioException catch (e) {
        final message = (e.response?.data is Map<String, dynamic>)
            ? (e.response?.data['message']?.toString() ??
                  'Failed to create shift template.')
            : 'Failed to create shift template.';
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(message), backgroundColor: AppColors.danger),
          );
        }
        return;
      }
    }

    final selectedEntries = <({int employeeId, String date})>[];
    for (final key in _selectedCells) {
      final parts = key.split('|');
      if (parts.length != 2) continue;
      final employeeId = int.tryParse(parts[0]);
      final dateStr = parts[1];
      if (employeeId == null || DateTime.tryParse(dateStr) == null) continue;
      selectedEntries.add((employeeId: employeeId, date: dateStr));
    }
    if (selectedEntries.isEmpty) return;

    final closerByDate = <String, int?>{};
    final affectedDates = selectedEntries.map((e) => e.date).toSet();
    var autoResolvedDays = 0;

    for (final date in affectedDates) {
      final selectedForDate =
          selectedEntries.where((e) => e.date == date).toList()
            ..sort((a, b) => a.employeeId.compareTo(b.employeeId));
      if (selectedForDate.isEmpty) continue;

      final selectedIds = selectedForDate.map((e) => e.employeeId).toSet();
      final existingClosers = _schedules
          .where((s) {
            return s['date']?.toString() == date &&
                s['is_closing_shift'] == true;
          })
          .map(_scheduleEmployeeId)
          .whereType<int>()
          .toList();

      int? chosenCloser;
      if (_bulkApplyOff) {
        final unselectedExisting = existingClosers
            .where((id) => !selectedIds.contains(id))
            .toList();
        chosenCloser = unselectedExisting.isNotEmpty
            ? unselectedExisting.first
            : null;
      } else if (_bulkClosing) {
        chosenCloser = selectedForDate.first.employeeId;
      } else {
        final unselectedExisting = existingClosers
            .where((id) => !selectedIds.contains(id))
            .toList();
        if (unselectedExisting.isNotEmpty) {
          chosenCloser = unselectedExisting.first;
        } else if (existingClosers.isNotEmpty) {
          chosenCloser = existingClosers.first;
        } else {
          chosenCloser = selectedForDate.first.employeeId;
        }
      }
      closerByDate[date] = chosenCloser;

      if (chosenCloser == null) {
        continue;
      }

      final selectedClosers = selectedForDate
          .where((e) => e.employeeId == chosenCloser)
          .length;
      final projectedCloserCount =
          selectedClosers +
          _schedules.where((s) {
            final sid = _scheduleEmployeeId(s);
            return s['date']?.toString() == date &&
                s['is_closing_shift'] == true &&
                sid != null &&
                !selectedIds.contains(sid);
          }).length;
      if (projectedCloserCount != 1) {
        autoResolvedDays += 1;
      }
    }

    setState(() => _saving = true);
    try {
      for (final entry in selectedEntries) {
        final employeeId = entry.employeeId;
        final dateStr = entry.date;
        final date = DateTime.parse(dateStr);
        final existing = _findSchedule(employeeId, date);
        final isClosing = !_bulkApplyOff && closerByDate[dateStr] == employeeId;
        final payload = {
          'store_id': _storeId,
          'employee_id': employeeId,
          'shift_id': _bulkShiftId,
          'date': dateStr,
          'is_closing_shift': isClosing,
        };

        if (existing != null) {
          final scheduleId = _toInt(existing['id']);
          if (scheduleId != null) {
            await _api.put('/schedules/$scheduleId', data: payload);
          }
        } else {
          await _api.post('/schedules', data: payload);
        }
      }

      for (final date in affectedDates) {
        final chosenCloser = closerByDate[date];
        if (chosenCloser == null) continue;
        final nonChosenClosers = _schedules.where((s) {
          final sid = _scheduleEmployeeId(s);
          return s['date']?.toString() == date &&
              s['is_closing_shift'] == true &&
              sid != null &&
              sid != chosenCloser;
        }).toList();

        for (final schedule in nonChosenClosers) {
          final scheduleId = _toInt(schedule['id']);
          final employeeId = _scheduleEmployeeId(schedule);
          final shiftId = _scheduleShiftId(schedule);
          if (scheduleId == null || employeeId == null || shiftId == null) {
            continue;
          }

          await _api.put(
            '/schedules/$scheduleId',
            data: {
              'store_id': _storeId,
              'employee_id': employeeId,
              'shift_id': shiftId,
              'date': date,
              'is_closing_shift': false,
            },
          );
        }
      }

      await _loadWeekData();
      if (mounted) {
        setState(() {
          _selectionMode = false;
          _selectedCells.clear();
        });
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              _bulkApplyOff
                  ? 'OFF shift applied to selected cells.'
                  : autoResolvedDays > 0
                  ? 'Bulk assignment applied. Closing conflicts auto-resolved for $autoResolvedDays day(s).'
                  : 'Bulk assignment applied successfully.',
            ),
            backgroundColor: AppColors.success,
          ),
        );
      }
    } on DioException catch (e) {
      var message = 'Failed to apply bulk assignment.';
      final data = e.response?.data;
      if (data is Map<String, dynamic>) {
        message = data['message']?.toString() ?? message;
      }
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(message), backgroundColor: AppColors.danger),
        );
      }
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  Future<void> _publishWeek() async {
    if (_storeId == null) return;

    setState(() => _publishing = true);
    try {
      final response = await _api.post(
        '/schedules/publish',
        data: {'store_id': _storeId, 'week_start': _toDate(_weekStart)},
      );
      final payload = response.data is Map<String, dynamic>
          ? Map<String, dynamic>.from(response.data)
          : <String, dynamic>{};
      final data = payload['data'] is Map<String, dynamic>
          ? Map<String, dynamic>.from(payload['data'])
          : <String, dynamic>{};
      final readyToPublish = data['ready_to_publish'] == true;

      if (!mounted) return;
      await showDialog<void>(
        context: context,
        builder: (context) => AlertDialog(
          title: const Text('Publish Week'),
          content: Text(
            'Assigned: ${data['assigned_slots'] ?? '-'} / ${data['expected_slots'] ?? '-'}\n'
            'Unassigned: ${data['unassigned_slots'] ?? '-'}\n'
            'Days without closing shift: ${data['days_without_closing_shift'] ?? '-'}\n'
            'Days with multiple closers: ${data['days_with_multiple_closing_shift'] ?? '-'}',
          ),
          actions: [
            FilledButton(
              onPressed: () => Navigator.pop(context),
              child: const Text('OK'),
            ),
          ],
        ),
      );

      await _loadWeekData();
      if (readyToPublish && mounted) {
        setState(() {
          _editPublishedMode = false;
          _selectionMode = false;
          _selectedCells.clear();
        });
      }

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              payload['message']?.toString() ?? 'Publish completed.',
            ),
            backgroundColor: readyToPublish
                ? AppColors.success
                : AppColors.warning,
          ),
        );
      }
    } on DioException catch (e) {
      final msg = (e.response?.data is Map<String, dynamic>)
          ? (e.response?.data['message']?.toString() ??
                'Failed to publish week.')
          : 'Failed to publish week.';
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(msg), backgroundColor: AppColors.danger),
        );
      }
    } finally {
      if (mounted) setState(() => _publishing = false);
    }
  }

  Future<void> _openAssignSheet({
    required Map<String, dynamic> employee,
    required DateTime date,
    Map<String, dynamic>? existing,
  }) async {
    if (_weekLocked) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text(
            'Week is published. Tap "Edit Published Schedule" first.',
          ),
          backgroundColor: AppColors.warning,
        ),
      );
      return;
    }

    final existingIsOff = _shiftShortName(existing) == 'OFF';
    int? selectedShiftId = existing != null ? _scheduleShiftId(existing) : null;
    selectedShiftId ??= _shifts.isNotEmpty ? _toInt(_shifts.first['id']) : null;
    bool isClosing = existing?['is_closing_shift'] == true;
    bool selectOff = existingIsOff;
    bool creatingTemplate = false;

    final result = await showModalBottomSheet<_AssignActionResult>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setSheetState) {
            return Padding(
              padding: EdgeInsets.only(
                left: AppSpacing.md,
                right: AppSpacing.md,
                top: AppSpacing.md,
                bottom:
                    MediaQuery.of(context).viewInsets.bottom + AppSpacing.md,
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Assign Shift',
                    style: Theme.of(context).textTheme.titleMedium,
                  ),
                  const SizedBox(height: AppSpacing.xs),
                  Text(
                    '${employee['full_name'] ?? 'Employee'} • ${_toDate(date)}',
                    style: Theme.of(context).textTheme.bodyMedium,
                  ),
                  const SizedBox(height: AppSpacing.md),
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: [
                      ...const ['AM', 'PM', 'MID', 'PH'].map((code) {
                        final id = _templateShiftId(code);
                        final selected =
                            !selectOff && id != null && id == selectedShiftId;
                        return ChoiceChip(
                          label: Text(code),
                          selected: selected,
                          onSelected: creatingTemplate
                              ? null
                              : (_) async {
                                  if (id != null) {
                                    setSheetState(() {
                                      selectOff = false;
                                      selectedShiftId = id;
                                    });
                                    return;
                                  }
                                  setSheetState(() => creatingTemplate = true);
                                  try {
                                    final ensured = await _ensureTemplateShift(
                                      code,
                                    );
                                    if (!mounted) return;
                                    setSheetState(() {
                                      creatingTemplate = false;
                                      if (ensured != null) {
                                        selectOff = false;
                                        selectedShiftId = ensured;
                                      }
                                    });
                                  } catch (_) {
                                    if (!mounted) return;
                                    setSheetState(
                                      () => creatingTemplate = false,
                                    );
                                  }
                                },
                        );
                      }),
                      ChoiceChip(
                        label: const Text('OFF'),
                        selected: selectOff,
                        onSelected: creatingTemplate
                            ? null
                            : (_) async {
                                final offId =
                                    _templateShiftId('OFF') ??
                                    await _ensureTemplateShift('OFF');
                                if (!mounted || offId == null) return;
                                setSheetState(() {
                                  selectOff = true;
                                  selectedShiftId = offId;
                                  isClosing = false;
                                });
                              },
                      ),
                    ],
                  ),
                  const SizedBox(height: AppSpacing.sm),
                  DropdownButtonFormField<int>(
                    initialValue: selectedShiftId,
                    isExpanded: true,
                    decoration: const InputDecoration(labelText: 'Shift'),
                    items: _shifts
                        .map(
                          (shift) => DropdownMenuItem<int>(
                            value: _toInt(shift['id']),
                            child: Text(
                              '${shift['name'] ?? 'Shift'} (${shift['start_time'] ?? '--'}-${shift['end_time'] ?? '--'})',
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        )
                        .toList(),
                    onChanged: (value) => setSheetState(() {
                      selectedShiftId = value;
                      final offId = _templateShiftId('OFF');
                      selectOff =
                          value != null && offId != null && value == offId;
                    }),
                  ),
                  if (creatingTemplate)
                    const Padding(
                      padding: EdgeInsets.only(top: 8),
                      child: LinearProgressIndicator(minHeight: 2),
                    ),
                  const SizedBox(height: AppSpacing.xs),
                  SwitchListTile(
                    contentPadding: EdgeInsets.zero,
                    value: isClosing,
                    title: const Text('Closing Shift'),
                    onChanged: selectOff
                        ? null
                        : (value) => setSheetState(() => isClosing = value),
                  ),
                  const SizedBox(height: AppSpacing.sm),
                  Row(
                    children: [
                      if (existing != null)
                        Expanded(
                          child: AppButton(
                            label: 'Clear',
                            variant: AppButtonVariant.outline,
                            onPressed: () => Navigator.pop(
                              context,
                              const _AssignActionResult.clear(),
                            ),
                          ),
                        ),
                      if (existing != null)
                        const SizedBox(width: AppSpacing.sm),
                      Expanded(
                        child: AppButton(
                          label: 'Save',
                          onPressed: (selectedShiftId == null
                              ? null
                              : () => Navigator.pop(
                                  context,
                                  _AssignActionResult.save(
                                    shiftId: selectedShiftId!,
                                    isClosing: selectOff ? false : isClosing,
                                  ),
                                )),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            );
          },
        );
      },
    );

    if (result == null) return;
    final employeeId = _toInt(employee['id']);
    if (employeeId == null || _storeId == null) return;

    final canContinue = await _confirmSingleCellChange(
      date: date,
      result: result,
      existing: existing,
      employeeName: employee['full_name']?.toString() ?? 'Employee',
    );
    if (!canContinue) return;

    setState(() => _saving = true);

    try {
      if (result.type == _AssignAction.clear && existing != null) {
        final scheduleId = _toInt(existing['id']);
        if (scheduleId != null) {
          await _api.delete('/schedules/$scheduleId');
        }
      } else if (result.type == _AssignAction.save) {
        final payload = {
          'store_id': _storeId,
          'employee_id': employeeId,
          'shift_id': result.shiftId,
          'date': _toDate(date),
          'is_closing_shift': result.isClosing,
        };

        if (existing != null) {
          final scheduleId = _toInt(existing['id']);
          if (scheduleId != null) {
            await _api.put('/schedules/$scheduleId', data: payload);
          }
        } else {
          await _api.post('/schedules', data: payload);
        }
      }

      await _loadWeekData();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Schedule updated successfully.'),
            backgroundColor: AppColors.success,
          ),
        );
      }
    } on DioException catch (e) {
      String message = 'Failed to update schedule.';
      final data = e.response?.data;
      if (data is Map<String, dynamic>) {
        message = data['message']?.toString() ?? message;
        final errors = data['errors'];
        if (errors is Map && errors.isNotEmpty) {
          final first = errors.values.first;
          if (first is List && first.isNotEmpty) {
            message = first.first.toString();
          }
        }
      }
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(message), backgroundColor: AppColors.danger),
        );
      }
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  int _assignedCountForDay(DateTime date) {
    final dateStr = _toDate(date);
    return _schedules.where((s) => s['date']?.toString() == dateStr).length;
  }

  int _closingCountForDay(DateTime date, {int? excludingScheduleId}) {
    final dateStr = _toDate(date);
    return _schedules.where((s) {
      if (s['date']?.toString() != dateStr) return false;
      if (s['is_closing_shift'] != true) return false;
      if (excludingScheduleId != null &&
          _toInt(s['id']) == excludingScheduleId) {
        return false;
      }
      return true;
    }).length;
  }

  Future<bool> _confirmAction({
    required String title,
    required String message,
    String confirmLabel = 'Continue',
  }) async {
    final result = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(title),
        content: Text(message),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancel'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: Text(confirmLabel),
          ),
        ],
      ),
    );
    return result == true;
  }

  Future<bool> _confirmSingleCellChange({
    required DateTime date,
    required _AssignActionResult result,
    required Map<String, dynamic>? existing,
    required String employeeName,
  }) async {
    final existingId = _toInt(existing?['id']);
    final existingIsClosing = existing?['is_closing_shift'] == true;
    final otherClosers = _closingCountForDay(
      date,
      excludingScheduleId: existingId,
    );
    final dateLabel = _toDate(date);

    if (result.type == _AssignAction.clear &&
        existingIsClosing &&
        otherClosers == 0) {
      return _confirmAction(
        title: 'Remove Only Closing Shift?',
        message:
            '$employeeName is currently the only closing shift on $dateLabel.',
        confirmLabel: 'Remove',
      );
    }

    if (result.type == _AssignAction.save) {
      if (result.isClosing && !existingIsClosing && otherClosers > 0) {
        return _confirmAction(
          title: 'Multiple Closers Warning',
          message:
              '$dateLabel already has a closing shift. Saving this will create multiple closers for that day.',
        );
      }
      if (!result.isClosing && existingIsClosing && otherClosers == 0) {
        return _confirmAction(
          title: 'No Closing Shift Warning',
          message:
              'This change removes the only closing shift on $dateLabel. Continue anyway?',
        );
      }
    }

    return true;
  }

  ({int unassignedSlots, int missingClosers, int multipleClosers}) _weekHealth(
    List<DateTime> weekDays,
  ) {
    var unassignedSlots = 0;
    var missingClosers = 0;
    var multipleClosers = 0;

    for (final date in weekDays) {
      final assigned = _assignedCountForDay(date);
      final unassigned = (_staff.length - assigned).clamp(0, _staff.length);
      unassignedSlots += unassigned;

      final closingCount = _closingCountForDay(date);
      if (closingCount == 0) {
        missingClosers += 1;
      } else if (closingCount > 1) {
        multipleClosers += 1;
      }
    }

    return (
      unassignedSlots: unassignedSlots,
      missingClosers: missingClosers,
      multipleClosers: multipleClosers,
    );
  }

  @override
  Widget build(BuildContext context) {
    final weekDays = _weekDays();
    final weekHealth = _weekHealth(weekDays);
    final publishStatusColor = _publishStatusColor();
    final publishedAt = _publishedAtLabel();
    final knownWeeks = _sortedKnownWeeks();
    final weekHeading = _weekHeadingLabel();
    final isCurrentWeek = _isCurrentWeek();
    final hasWeekWarnings =
        weekHealth.unassignedSlots > 0 ||
        weekHealth.missingClosers > 0 ||
        weekHealth.multipleClosers > 0;

    return Scaffold(
      appBar: AppBar(title: const Text('Schedule Management')),
      body: RefreshIndicator(
        onRefresh: _bootstrap,
        child: ListView(
          padding: AppSpacing.pagePadding,
          children: [
            if (_loading)
              const AppLoadingState(message: 'Loading schedules...')
            else if (_errorMessage != null)
              AppErrorState(message: _errorMessage!, onRetry: _bootstrap)
            else ...[
              AppCard(
                padding: const EdgeInsets.fromLTRB(
                  AppSpacing.md,
                  AppSpacing.sm,
                  AppSpacing.md,
                  AppSpacing.sm,
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            'Create New Schedule',
                            style: Theme.of(context).textTheme.titleMedium,
                          ),
                        ),
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 10,
                            vertical: 6,
                          ),
                          decoration: BoxDecoration(
                            color: publishStatusColor.withValues(alpha: 0.15),
                            borderRadius: BorderRadius.circular(999),
                          ),
                          child: Text(
                            _publishStatusLabel(),
                            style: TextStyle(
                              color: publishStatusColor,
                              fontWeight: FontWeight.w700,
                              fontSize: 12,
                            ),
                          ),
                        ),
                      ],
                    ),
                    if (_assignedStoreName != null) ...[
                      const SizedBox(height: AppSpacing.xxs),
                      Text(
                        'Assigned Store: $_assignedStoreName',
                        style: Theme.of(context).textTheme.bodyMedium,
                      ),
                    ],
                    const SizedBox(height: AppSpacing.xs),
                    DropdownButtonFormField<int>(
                      initialValue: _storeId,
                      decoration: InputDecoration(
                        labelText: 'Store: ${_selectedStoreLabel()}',
                      ),
                      items: _stores
                          .map(
                            (store) => DropdownMenuItem<int>(
                              value: _toInt(store['id']),
                              child: Text(store['name']?.toString() ?? 'Store'),
                            ),
                          )
                          .toList(),
                      onChanged: (value) async {
                        setState(() {
                          _storeId = value;
                          _selectionMode = false;
                          _selectedCells.clear();
                          _editPublishedMode = false;
                          _advancedToolsExpanded = false;
                        });
                        await _loadWeekData();
                      },
                    ),
                    const SizedBox(height: AppSpacing.xs),
                    Row(
                      children: [
                        IconButton(
                          onPressed: _saving ? null : () => _changeWeek(-7),
                          visualDensity: VisualDensity.compact,
                          constraints: const BoxConstraints.tightFor(
                            width: 34,
                            height: 34,
                          ),
                          icon: const Icon(Icons.chevron_left),
                        ),
                        Expanded(
                          child: Column(
                            children: [
                              Text(
                                _weekRangePrettyLabel(),
                                textAlign: TextAlign.center,
                                style: Theme.of(context).textTheme.bodyLarge
                                    ?.copyWith(fontWeight: FontWeight.w700),
                              ),
                              const SizedBox(height: 2),
                              Text(
                                _weekRangeLabel(),
                                textAlign: TextAlign.center,
                                style: Theme.of(context).textTheme.bodySmall
                                    ?.copyWith(color: AppColors.textSecondary),
                              ),
                            ],
                          ),
                        ),
                        IconButton(
                          onPressed: _saving ? null : () => _changeWeek(7),
                          visualDensity: VisualDensity.compact,
                          constraints: const BoxConstraints.tightFor(
                            width: 34,
                            height: 34,
                          ),
                          icon: const Icon(Icons.chevron_right),
                        ),
                      ],
                    ),
                    const SizedBox(height: AppSpacing.xxs),
                    Align(
                      alignment: Alignment.center,
                      child: isCurrentWeek
                          ? Container(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 10,
                                vertical: 6,
                              ),
                              decoration: BoxDecoration(
                                color: AppColors.success.withValues(
                                  alpha: 0.12,
                                ),
                                borderRadius: BorderRadius.circular(999),
                              ),
                              child: const Text(
                                'Current Week',
                                style: TextStyle(
                                  color: AppColors.success,
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                            )
                          : TextButton.icon(
                              onPressed: _saving ? null : _goToCurrentWeek,
                              icon: const Icon(Icons.today_outlined, size: 18),
                              label: const Text('Go to Current Week'),
                            ),
                    ),
                    const SizedBox(height: AppSpacing.xxs),
                    Text(
                      '$weekHeading • Created weeks: ${knownWeeks.length}',
                      style: Theme.of(context).textTheme.titleSmall?.copyWith(
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    if (knownWeeks.isNotEmpty) ...[
                      const SizedBox(height: AppSpacing.xxs),
                      SingleChildScrollView(
                        scrollDirection: Axis.horizontal,
                        child: Row(
                          children: [
                            ...knownWeeks.asMap().entries.map((entry) {
                              final week = entry.value;
                              final selected = _sameWeekStart(week, _weekStart);
                              return Padding(
                                padding: const EdgeInsets.only(right: 8),
                                child: ChoiceChip(
                                  label: Text('Week ${entry.key + 1}'),
                                  selected: selected,
                                  onSelected: selected
                                      ? null
                                      : (_) => _goToWeek(week),
                                ),
                              );
                            }),
                            ActionChip(
                              avatar: const Icon(Icons.add, size: 16),
                              label: Text('New Week ${knownWeeks.length + 1}'),
                              onPressed: _loading ? null : _createNewSchedule,
                            ),
                          ],
                        ),
                      ),
                    ],
                    if (publishedAt != null) ...[
                      const SizedBox(height: AppSpacing.xxs),
                      Text(
                        'Published at: $publishedAt',
                        style: Theme.of(context).textTheme.bodySmall?.copyWith(
                          color: AppColors.textSecondary,
                        ),
                      ),
                    ],
                    const SizedBox(height: AppSpacing.xs),
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.all(AppSpacing.xs),
                      decoration: BoxDecoration(
                        color: _weekLocked
                            ? AppColors.warning.withValues(alpha: 0.1)
                            : AppColors.info.withValues(alpha: 0.08),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Text(
                        _workflowHint(),
                        style: Theme.of(context).textTheme.bodyMedium,
                      ),
                    ),
                    const SizedBox(height: AppSpacing.xs),
                    if (_canUnlockPublishedWeek)
                      Row(
                        children: [
                          Expanded(
                            child: AppButton(
                              label: _weekLocked
                                  ? 'Edit Published Schedule'
                                  : 'Exit Edit Mode',
                              variant: _weekLocked
                                  ? AppButtonVariant.outline
                                  : AppButtonVariant.tonal,
                              onPressed: _weekLocked
                                  ? _enablePublishedEditMode
                                  : () {
                                      setState(() {
                                        _editPublishedMode = false;
                                        _selectionMode = false;
                                        _selectedCells.clear();
                                        _advancedToolsExpanded = false;
                                      });
                                    },
                            ),
                          ),
                        ],
                      ),
                    if (!_canUnlockPublishedWeek) ...[
                      SizedBox(
                        width: double.infinity,
                        child: AppButton(
                          label: 'Create New Schedule',
                          leadingIcon: Icons.playlist_add_check,
                          variant: AppButtonVariant.tonal,
                          onPressed: (_loading || _saving || _publishing)
                              ? null
                              : _createNewSchedule,
                        ),
                      ),
                    ],
                    const SizedBox(height: AppSpacing.xs),
                    Text(
                      'Shift Timings (AM / MID / PM / PH)',
                      style: Theme.of(context).textTheme.titleSmall,
                    ),
                    const SizedBox(height: AppSpacing.xs),
                    Text(
                      'Supervisor can set weekly shift timings for this store.',
                      style: Theme.of(context).textTheme.bodyMedium,
                    ),
                    const SizedBox(height: AppSpacing.xs),
                    ...const ['AM', 'MID', 'PM', 'PH'].map((key) {
                      final draft =
                          _shiftTemplates[key] ?? const _ShiftTemplateDraft();
                      if (key == 'PH') {
                        return Padding(
                          padding: const EdgeInsets.only(bottom: 8),
                          child: Row(
                            children: const [
                              SizedBox(
                                width: 44,
                                child: Text(
                                  'PH',
                                  style: TextStyle(fontWeight: FontWeight.w700),
                                ),
                              ),
                              Expanded(
                                child: Text(
                                  'Public Holiday (no timing required)',
                                  style: TextStyle(
                                    color: AppColors.textSecondary,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        );
                      }
                      return Padding(
                        padding: const EdgeInsets.only(bottom: 8),
                        child: Row(
                          children: [
                            SizedBox(
                              width: 44,
                              child: Text(
                                key,
                                style: const TextStyle(
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                            ),
                            Expanded(
                              child: OutlinedButton.icon(
                                onPressed:
                                    (_savingShiftTemplates || _weekLocked)
                                    ? null
                                    : () => _pickShiftTime(
                                        template: key,
                                        isStart: true,
                                      ),
                                icon: const Icon(Icons.schedule, size: 16),
                                label: Text(
                                  draft.start.isEmpty ? 'Start' : draft.start,
                                ),
                              ),
                            ),
                            const SizedBox(width: 8),
                            Expanded(
                              child: OutlinedButton.icon(
                                onPressed:
                                    (_savingShiftTemplates || _weekLocked)
                                    ? null
                                    : () => _pickShiftTime(
                                        template: key,
                                        isStart: false,
                                      ),
                                icon: const Icon(
                                  Icons.schedule_outlined,
                                  size: 16,
                                ),
                                label: Text(
                                  draft.end.isEmpty ? 'End' : draft.end,
                                ),
                              ),
                            ),
                          ],
                        ),
                      );
                    }),
                    SizedBox(
                      width: double.infinity,
                      child: AppButton(
                        label: _savingShiftTemplates
                            ? 'Saving Shifts...'
                            : (_hasUnpublishedChanges
                                  ? 'Update Shift Timings'
                                  : 'Save Shift Timings'),
                        onPressed:
                            (_savingShiftTemplates ||
                                _storeId == null ||
                                _weekLocked)
                            ? null
                            : _saveShiftTemplates,
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: AppSpacing.sm),
              if (hasWeekWarnings) ...[
                AppCard(
                  child: Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(AppSpacing.sm),
                    decoration: BoxDecoration(
                      color: AppColors.warning.withValues(alpha: 0.09),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Week Health Warning',
                          style: Theme.of(context).textTheme.titleSmall
                              ?.copyWith(
                                color: AppColors.textPrimary,
                                fontWeight: FontWeight.w700,
                              ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          'Unassigned slots: ${weekHealth.unassignedSlots} • '
                          'Days without closer: ${weekHealth.missingClosers} • '
                          'Days with multiple closers: ${weekHealth.multipleClosers}',
                          style: Theme.of(context).textTheme.bodyMedium,
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: AppSpacing.sm),
              ],
              AppCard(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Week Planner',
                      style: Theme.of(context).textTheme.titleMedium,
                    ),
                    const SizedBox(height: AppSpacing.xs),
                    Text(
                      _weekLocked
                          ? 'Published week is read-only until Edit mode is enabled.'
                          : (_selectionMode
                                ? 'Select cells then apply shift in bulk.'
                                : 'Tap a cell to assign or clear shifts.'),
                      style: Theme.of(context).textTheme.bodyMedium,
                    ),
                    const SizedBox(height: AppSpacing.sm),
                    Row(
                      children: [
                        Expanded(
                          child: AppButton(
                            label: _selectionMode
                                ? 'Exit Selection'
                                : 'Bulk Select',
                            variant: AppButtonVariant.outline,
                            onPressed: _weekLocked
                                ? _enablePublishedEditMode
                                : () {
                                    setState(() {
                                      _selectionMode = !_selectionMode;
                                      _selectedCells.clear();
                                      if (_selectionMode) {
                                        _advancedToolsExpanded = true;
                                      }
                                    });
                                  },
                          ),
                        ),
                        const SizedBox(width: AppSpacing.sm),
                        if (_selectionMode)
                          Expanded(
                            child: AppButton(
                              label: 'Clear Selected',
                              variant: AppButtonVariant.tonal,
                              onPressed: _selectedCells.isEmpty
                                  ? null
                                  : () =>
                                        setState(() => _selectedCells.clear()),
                            ),
                          ),
                      ],
                    ),
                    if (_selectionMode) ...[
                      const SizedBox(height: AppSpacing.sm),
                      ExpansionTile(
                        tilePadding: EdgeInsets.zero,
                        childrenPadding: const EdgeInsets.only(
                          bottom: AppSpacing.sm,
                        ),
                        initiallyExpanded: _advancedToolsExpanded,
                        onExpansionChanged: (expanded) =>
                            setState(() => _advancedToolsExpanded = expanded),
                        title: const Text('Bulk Tools'),
                        subtitle: Text(
                          _selectedCells.isEmpty
                              ? 'Select one or more cells.'
                              : '${_selectedCells.length} cell(s) selected',
                        ),
                        children: [
                          Wrap(
                            spacing: 8,
                            runSpacing: 8,
                            children: [
                              ...const ['AM', 'PM', 'MID', 'PH'].map((code) {
                                final id = _templateShiftId(code);
                                return ChoiceChip(
                                  label: Text(code),
                                  selected:
                                      !_bulkApplyOff &&
                                      ((id != null && _bulkShiftId == id) ||
                                          (id == null &&
                                              _bulkTemplateCode == code)),
                                  onSelected: (_) => setState(() {
                                    _bulkApplyOff = false;
                                    _bulkTemplateCode = code;
                                    _bulkShiftId = id;
                                  }),
                                );
                              }),
                              ChoiceChip(
                                label: const Text('OFF'),
                                selected: _bulkApplyOff,
                                onSelected: (_) => setState(() {
                                  _bulkApplyOff = true;
                                  _bulkClosing = false;
                                  _bulkTemplateCode = null;
                                }),
                              ),
                            ],
                          ),
                          const SizedBox(height: AppSpacing.xs),
                          DropdownButtonFormField<int>(
                            initialValue: _bulkShiftId,
                            isExpanded: true,
                            decoration: const InputDecoration(
                              labelText: 'Bulk Shift',
                            ),
                            items: _shifts
                                .map(
                                  (shift) => DropdownMenuItem<int>(
                                    value: _toInt(shift['id']),
                                    child: Text(
                                      shift['name']?.toString() ?? 'Shift',
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                  ),
                                )
                                .toList(),
                            onChanged: (value) => setState(() {
                              _bulkShiftId = value;
                              _bulkTemplateCode = null;
                              _bulkApplyOff = false;
                            }),
                          ),
                          const SizedBox(height: AppSpacing.xs),
                          SwitchListTile(
                            contentPadding: EdgeInsets.zero,
                            value: _bulkClosing,
                            title: const Text('Bulk Closing Shift'),
                            onChanged: _bulkApplyOff
                                ? null
                                : (value) =>
                                      setState(() => _bulkClosing = value),
                          ),
                          SizedBox(
                            width: double.infinity,
                            child: AppButton(
                              label: _saving
                                  ? 'Applying...'
                                  : 'Apply to Selected (${_selectedCells.length})',
                              onPressed:
                                  (_saving ||
                                      _selectedCells.isEmpty ||
                                      (!_bulkApplyOff &&
                                          _bulkShiftId == null &&
                                          _bulkTemplateCode == null))
                                  ? null
                                  : _applyBulkSelection,
                            ),
                          ),
                        ],
                      ),
                    ],
                    _buildLegend(),
                    const SizedBox(height: AppSpacing.sm),
                    if (_staff.isEmpty)
                      const AppEmptyState(
                        title: 'No staff found for selected store.',
                        icon: Icons.group_off,
                      )
                    else
                      _buildWeekGrid(weekDays),
                  ],
                ),
              ),
              const SizedBox(height: AppSpacing.sm),
              AppCard(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Coverage Summary',
                      style: Theme.of(context).textTheme.titleMedium,
                    ),
                    const SizedBox(height: AppSpacing.xs),
                    ...weekDays.map((date) {
                      final assigned = _assignedCountForDay(date);
                      final total = _staff.length;
                      final unassigned = (total - assigned).clamp(0, total);
                      final closingCount = _closingCountForDay(date);
                      final closingNote = closingCount == 0
                          ? ' • no closer'
                          : (closingCount > 1
                                ? ' • $closingCount closers'
                                : ' • 1 closer');
                      return Padding(
                        padding: const EdgeInsets.only(bottom: 4),
                        child: Text(
                          '${_weekdayShort(date)} ${date.day}: $assigned/$total assigned${unassigned > 0 ? ' • $unassigned unassigned' : ''}$closingNote',
                          style: Theme.of(context).textTheme.bodyMedium,
                        ),
                      );
                    }),
                    const SizedBox(height: AppSpacing.sm),
                    SizedBox(
                      width: double.infinity,
                      child: AppButton(
                        label: _publishPrimaryActionLabel(),
                        onPressed: _publishing ? null : _publishWeek,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildLegend() {
    Widget chip(String label, Color color) {
      return Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
        decoration: BoxDecoration(
          color: color.withValues(alpha: 0.12),
          borderRadius: BorderRadius.circular(999),
        ),
        child: Text(
          label,
          style: TextStyle(color: color, fontWeight: FontWeight.w600),
        ),
      );
    }

    return Wrap(
      spacing: AppSpacing.xs,
      runSpacing: AppSpacing.xs,
      children: [
        chip('Assigned', AppColors.success),
        chip('Closing', AppColors.warning),
        chip('Off', AppColors.textSecondary),
      ],
    );
  }

  Widget _buildWeekGrid(List<DateTime> weekDays) {
    const double nameColWidth = 150;
    const double dayColWidth = 120;

    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      child: Column(
        children: [
          Row(
            children: [
              _headerCell('Staff', width: nameColWidth),
              ...weekDays.map((date) {
                return _headerCell(
                  '${_weekdayShort(date)}\n${_toDate(date)}',
                  width: dayColWidth,
                  center: true,
                );
              }),
            ],
          ),
          ..._staff.map((employee) {
            final employeeId = _toInt(employee['id']);
            if (employeeId == null) return const SizedBox.shrink();
            return Row(
              children: [
                _employeeCell(employee, width: nameColWidth),
                ...weekDays.map((date) {
                  final existing = _findSchedule(employeeId, date);
                  final cellKey = _cellKey(employeeId, date);
                  return _scheduleCell(
                    width: dayColWidth,
                    schedule: existing,
                    selected: _selectedCells.contains(cellKey),
                    onTap: (_saving || _weekLocked)
                        ? null
                        : () {
                            if (_selectionMode) {
                              _toggleCellSelection(employeeId, date);
                            } else {
                              _openAssignSheet(
                                employee: employee,
                                date: date,
                                existing: existing,
                              );
                            }
                          },
                    onLongPress: (_saving || _weekLocked)
                        ? null
                        : () {
                            if (!_selectionMode) {
                              setState(() => _selectionMode = true);
                            }
                            _toggleCellSelection(employeeId, date);
                          },
                  );
                }),
              ],
            );
          }),
        ],
      ),
    );
  }

  Widget _headerCell(
    String text, {
    required double width,
    bool center = false,
  }) {
    return Container(
      width: width,
      padding: const EdgeInsets.all(8),
      decoration: BoxDecoration(
        border: Border.all(color: AppColors.border),
        color: AppColors.background,
      ),
      child: Text(
        text,
        textAlign: center ? TextAlign.center : TextAlign.left,
        style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 12),
      ),
    );
  }

  Widget _employeeCell(Map<String, dynamic> employee, {required double width}) {
    return Container(
      width: width,
      padding: const EdgeInsets.all(8),
      decoration: BoxDecoration(
        border: Border.all(color: AppColors.border),
        color: Colors.white,
      ),
      child: Text(
        employee['full_name']?.toString() ?? 'Employee',
        maxLines: 2,
        overflow: TextOverflow.ellipsis,
        style: const TextStyle(fontWeight: FontWeight.w600),
      ),
    );
  }

  Widget _scheduleCell({
    required double width,
    required Map<String, dynamic>? schedule,
    required bool selected,
    required VoidCallback? onTap,
    required VoidCallback? onLongPress,
  }) {
    final bgColor = _scheduleCellColor(schedule);
    final status = _scheduleStatusLabel(schedule);
    final statusColor = _scheduleStatusColor(schedule);

    return InkWell(
      onTap: onTap,
      onLongPress: onLongPress,
      child: Container(
        width: width,
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 10),
        decoration: BoxDecoration(
          border: Border.all(
            color: selected ? AppColors.brandAccent : AppColors.border,
            width: selected ? 2 : 1,
          ),
          color: bgColor,
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              _shiftShortName(schedule),
              style: const TextStyle(fontWeight: FontWeight.w700),
              overflow: TextOverflow.ellipsis,
            ),
            const SizedBox(height: 2),
            Text(
              status,
              style: TextStyle(
                fontSize: 11,
                color: statusColor,
                fontWeight: FontWeight.w600,
              ),
            ),
          ],
        ),
      ),
    );
  }

  String _weekdayShort(DateTime date) {
    const names = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    return names[date.weekday - 1];
  }
}

enum _AssignAction { save, clear }

class _ShiftTemplateDraft {
  final int? id;
  final String start;
  final String end;

  const _ShiftTemplateDraft({this.id, this.start = '', this.end = ''});

  _ShiftTemplateDraft copyWith({int? id, String? start, String? end}) {
    return _ShiftTemplateDraft(
      id: id ?? this.id,
      start: start ?? this.start,
      end: end ?? this.end,
    );
  }
}

class _AssignActionResult {
  final _AssignAction type;
  final int? shiftId;
  final bool isClosing;

  const _AssignActionResult._({
    required this.type,
    this.shiftId,
    this.isClosing = false,
  });

  const _AssignActionResult.save({
    required int shiftId,
    required bool isClosing,
  }) : this._(type: _AssignAction.save, shiftId: shiftId, isClosing: isClosing);

  const _AssignActionResult.clear() : this._(type: _AssignAction.clear);
}

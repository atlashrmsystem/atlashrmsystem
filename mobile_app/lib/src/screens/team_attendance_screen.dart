import 'package:dio/dio.dart';
import 'package:flutter/material.dart';

import '../config/api_client.dart';
import '../theme/app_colors.dart';
import '../theme/app_spacing.dart';
import '../widgets/app_card.dart';
import '../widgets/state_widgets.dart';

class TeamAttendanceScreen extends StatefulWidget {
  const TeamAttendanceScreen({super.key});

  @override
  State<TeamAttendanceScreen> createState() => _TeamAttendanceScreenState();
}

class _TeamAttendanceScreenState extends State<TeamAttendanceScreen> {
  final Dio _api = ApiClient().dio;

  bool _loading = true;
  String? _errorMessage;
  List<Map<String, dynamic>> _stores = [];
  List<Map<String, dynamic>> _onDutyRows = [];
  int? _storeId;

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

  String get _todayStr => DateTime.now().toIso8601String().split('T').first;

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

      final utcParsed = DateTime.parse('${normalized.replaceFirst(' ', 'T')}Z');
      return utcParsed.toLocal();
    } catch (_) {
      return null;
    }
  }

  String _clockLabel(String? raw) {
    final parsed = _parseAttendanceDateTime(raw);
    if (parsed == null) return '--:--';
    final hour = parsed.hour.toString().padLeft(2, '0');
    final minute = parsed.minute.toString().padLeft(2, '0');
    return '$hour:$minute';
  }

  Future<void> _bootstrap() async {
    setState(() {
      _loading = true;
      _errorMessage = null;
    });

    try {
      final storesRes = await _api.get('/stores');
      _stores = _extractList(
        storesRes.data,
      ).whereType<Map<String, dynamic>>().toList();
      if (_stores.isNotEmpty && _storeId == null) {
        _storeId = _stores.first['id'] as int?;
      }
      await _loadAttendance();
      if (mounted) setState(() => _loading = false);
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _errorMessage = 'Failed to load attendance data.';
      });
    }
  }

  Future<void> _loadAttendance() async {
    final query = <String, dynamic>{
      'date_from': _todayStr,
      'date_to': _todayStr,
    };
    if (_storeId != null) query['store_id'] = _storeId;

    final res = await _api.get('/attendance', queryParameters: query);
    final rows = _extractList(res.data).whereType<Map<String, dynamic>>();
    final onDuty = rows.where((data) => data['clock_out'] == null).toList();
    if (!mounted) return;
    setState(() => _onDutyRows = onDuty);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Current Staff On Duty')),
      body: RefreshIndicator(
        onRefresh: _bootstrap,
        child: ListView(
          padding: AppSpacing.pagePadding,
          children: [
            if (_loading)
              const AppLoadingState(message: 'Loading team attendance...')
            else if (_errorMessage != null)
              AppErrorState(message: _errorMessage!, onRetry: _bootstrap)
            else ...[
              AppCard(
                child: Column(
                  children: [
                    DropdownButtonFormField<int>(
                      initialValue: _storeId,
                      decoration: const InputDecoration(labelText: 'Store'),
                      items: _stores
                          .map(
                            (e) => DropdownMenuItem<int>(
                              value: e['id'] as int,
                              child: Text(e['name']?.toString() ?? 'Store'),
                            ),
                          )
                          .toList(),
                      onChanged: (value) async {
                        setState(() => _storeId = value);
                        await _loadAttendance();
                      },
                    ),
                    const SizedBox(height: AppSpacing.sm),
                    Row(
                      children: [
                        const Icon(Icons.people_alt_outlined, size: 18),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            'On duty now: ${_onDutyRows.length}',
                            style: Theme.of(context).textTheme.bodyLarge,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              const SizedBox(height: AppSpacing.sm),
              if (_onDutyRows.isEmpty)
                const AppEmptyState(
                  title: 'No staff currently on duty.',
                  icon: Icons.timelapse,
                )
              else
                ..._onDutyRows.map((data) {
                  final employee = data['employee'] as Map<String, dynamic>?;
                  return AppCard(
                    margin: const EdgeInsets.only(bottom: AppSpacing.sm),
                    child: Row(
                      children: [
                        const Icon(Icons.timelapse, color: AppColors.warning),
                        const SizedBox(width: AppSpacing.sm),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                employee?['full_name']?.toString() ??
                                    'Employee',
                                style: Theme.of(context).textTheme.bodyLarge,
                              ),
                              Text(
                                'Clock In: ${_clockLabel((data['clock_in_time'] ?? data['clock_in'])?.toString())}',
                                style: Theme.of(context).textTheme.bodyMedium,
                              ),
                            ],
                          ),
                        ),
                        const Text(
                          'ON DUTY',
                          style: TextStyle(
                            color: AppColors.warning,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ],
                    ),
                  );
                }),
            ],
          ],
        ),
      ),
    );
  }
}

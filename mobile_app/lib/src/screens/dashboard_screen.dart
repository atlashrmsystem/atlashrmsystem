import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../config/api_client.dart';
import '../providers/auth_provider.dart';
import '../theme/app_colors.dart';
import '../theme/app_spacing.dart';
import '../widgets/app_button.dart';
import '../widgets/app_card.dart';
import '../widgets/state_widgets.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  final Dio _api = ApiClient().dio;

  bool _loadingAttendanceSummary = true;
  String? _attendanceSummaryError;
  Map<String, dynamic>? _todayAttendance;

  bool _loadingLeaveHistory = true;
  String? _leaveHistoryError;
  List<Map<String, dynamic>> _recentLeaveHistory = [];
  List<Map<String, dynamic>> _allLeaveHistory = [];

  bool _loadingActionRequired = true;
  String? _actionRequiredError;
  List<_RequiredActionItem> _requiredActions = [];

  @override
  void initState() {
    super.initState();
    _bootstrap();
  }

  Future<void> _bootstrap() async {
    final auth = Provider.of<AuthProvider>(context, listen: false);
    await auth.refreshSessionUser().catchError((_) {});
    final futures = <Future<void>>[_fetchRecentLeaveHistory()];
    if (!auth.isManager) {
      futures.add(_fetchAttendanceSummary());
    } else {
      setState(() {
        _loadingAttendanceSummary = false;
        _attendanceSummaryError = null;
        _todayAttendance = null;
      });
    }
    await Future.wait(futures);
    await _fetchActionRequired(auth, allowRemoteFallback: false);
  }

  Future<void> _fetchAttendanceSummary() async {
    setState(() {
      _loadingAttendanceSummary = true;
      _attendanceSummaryError = null;
    });

    try {
      final response = await _api.get('/attendance/today');
      if (!mounted) return;
      setState(() {
        _todayAttendance = _extractMap(response.data);
        _loadingAttendanceSummary = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _loadingAttendanceSummary = false;
        _attendanceSummaryError = 'Could not load attendance summary.';
      });
    }
  }

  Future<void> _fetchRecentLeaveHistory() async {
    setState(() {
      _loadingLeaveHistory = true;
      _leaveHistoryError = null;
    });

    try {
      final response = await _api.get(
        '/leave-requests',
        queryParameters: {'mine': 1},
      );

      final list = _extractList(
        response.data,
      ).whereType<Map<String, dynamic>>().toList();

      if (!mounted) return;
      setState(() {
        _allLeaveHistory = list;
        _recentLeaveHistory = list.take(3).toList();
        _loadingLeaveHistory = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _allLeaveHistory = [];
        _loadingLeaveHistory = false;
        _leaveHistoryError = 'Could not load leave history.';
      });
    }
  }

  Future<void> _fetchActionRequired(
    AuthProvider auth, {
    bool allowRemoteFallback = true,
  }) async {
    setState(() {
      _loadingActionRequired = true;
      _actionRequiredError = null;
    });

    final actions = <_RequiredActionItem>[];

    try {
      if (!auth.isManager) {
        Map<String, dynamic>? today = _todayAttendance;
        if (today == null && allowRemoteFallback) {
          final attendanceResponse = await _api.get('/attendance/today');
          today = _extractMap(attendanceResponse.data);
        }
        final clockIn = today?['clock_in_time'] ?? today?['clock_in'];
        final clockOut = today?['clock_out_time'] ?? today?['clock_out'];

        if (clockIn == null) {
          actions.add(
            _RequiredActionItem(
              icon: Icons.fingerprint,
              title: 'Clock in for today',
              description: 'No attendance entry found for today.',
              route: '/attendance',
              color: AppColors.warning,
              ctaLabel: 'Clock In',
            ),
          );
        } else if (clockOut == null) {
          actions.add(
            _RequiredActionItem(
              icon: Icons.access_time_filled,
              title: 'Complete your shift',
              description: 'You are clocked in but not clocked out yet.',
              route: '/attendance',
              color: AppColors.info,
              ctaLabel: 'Clock Out',
            ),
          );
        }
      }

      List<Map<String, dynamic>> myLeaves = List<Map<String, dynamic>>.from(
        _allLeaveHistory,
      );
      if (myLeaves.isEmpty && allowRemoteFallback) {
        final myLeaveResponse = await _api.get(
          '/leave-requests',
          queryParameters: {'mine': 1},
        );
        myLeaves = _extractList(
          myLeaveResponse.data,
        ).whereType<Map<String, dynamic>>().toList();
      }

      final pendingMine = myLeaves.where((leave) {
        final status = (leave['status'] ?? '').toString().toLowerCase();
        final workflow = (leave['workflow_status'] ?? '')
            .toString()
            .toLowerCase();
        return status == 'pending' || workflow == 'pending';
      }).length;

      if (pendingMine > 0) {
        actions.add(
          _RequiredActionItem(
            icon: Icons.pending_actions,
            title:
                '$pendingMine leave request${pendingMine > 1 ? 's' : ''} pending',
            description: 'Track status or update your leave details.',
            route: '/leaves',
            color: AppColors.warning,
            ctaLabel: 'Review',
          ),
        );
      }

      if (auth.canApproveTeamActions) {
        final pendingTeamResponse = await _api.get('/leave-requests');
        final teamPending = _extractList(
          pendingTeamResponse.data,
        ).whereType<Map<String, dynamic>>().length;

        if (teamPending > 0) {
          actions.add(
            _RequiredActionItem(
              icon: Icons.approval,
              title:
                  '$teamPending team approval${teamPending > 1 ? 's' : ''} waiting',
              description: 'Pending leave approvals need your decision.',
              route: '/team-leave-queue',
              color: AppColors.danger,
              ctaLabel: 'Open Queue',
            ),
          );
        }
      }

      if (!mounted) return;
      setState(() {
        _requiredActions = actions;
        _loadingActionRequired = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _requiredActions = actions;
        _loadingActionRequired = false;
        _actionRequiredError = 'Could not load required actions.';
      });
    }
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
    if (payload is Map<String, dynamic>) {
      final data = payload['data'];
      if (data is Map<String, dynamic>) return data;
      return payload;
    }
    return null;
  }

  String _toTitle(String value) {
    return value
        .replaceAll('_', ' ')
        .split(' ')
        .where((e) => e.isNotEmpty)
        .map((e) => e[0].toUpperCase() + e.substring(1).toLowerCase())
        .join(' ');
  }

  Color _statusColor(String status) {
    switch (status.toLowerCase()) {
      case 'approved':
        return AppColors.success;
      case 'rejected':
        return AppColors.danger;
      default:
        return AppColors.warning;
    }
  }

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

  String _twoDigits(int n) => n.toString().padLeft(2, '0');

  String _clockTimeLabel(DateTime dt) =>
      '${_twoDigits(dt.hour)}:${_twoDigits(dt.minute)}';

  Duration _workedDuration() {
    final clockInRaw =
        _todayAttendance?['clock_in_time']?.toString() ??
        _todayAttendance?['clock_in']?.toString();
    final start = _parseAttendanceDateTime(clockInRaw);
    if (start == null) return Duration.zero;

    final clockOutRaw =
        _todayAttendance?['clock_out_time']?.toString() ??
        _todayAttendance?['clock_out']?.toString();

    final end = _parseAttendanceDateTime(clockOutRaw) ?? DateTime.now();
    final diff = end.difference(start);
    return diff.isNegative ? Duration.zero : diff;
  }

  String _workedDurationLabel() {
    final duration = _workedDuration();
    if (duration == Duration.zero) return '--:-- hrs';
    final hours = duration.inHours;
    final minutes = duration.inMinutes.remainder(60).toString().padLeft(2, '0');
    return '$hours:$minutes hrs';
  }

  List<_ActionItem> _buildActions(AuthProvider auth) {
    final actions = <_ActionItem>[];
    final seenRoutes = <String>{};

    void addAction(_ActionItem action) {
      if (seenRoutes.add(action.route)) actions.add(action);
    }

    // Core daily actions first.
    if (!auth.isManager) {
      addAction(
        _ActionItem(
          icon: Icons.access_time,
          title: 'Clock In/Out',
          color: AppColors.success,
          route: '/attendance',
          priority: 1,
        ),
      );
    }

    addAction(
      _ActionItem(
        icon: Icons.calendar_today,
        title: 'Request Leave',
        color: AppColors.warning,
        route: '/leaves',
        priority: 2,
      ),
    );

    addAction(
      _ActionItem(
        icon: Icons.receipt_long,
        title: 'My Payslips',
        color: Colors.deepPurple,
        route: '/payslips',
        priority: 3,
      ),
    );

    addAction(
      _ActionItem(
        icon: Icons.person_outline,
        title: 'My Profile',
        color: AppColors.info,
        route: '/profile',
        priority: 4,
      ),
    );
    addAction(
      _ActionItem(
        icon: Icons.grid_view_rounded,
        title: 'Open Menu',
        color: AppColors.brandAccent,
        route: '/menu',
        priority: 6,
      ),
    );

    if (auth.isStaff || auth.isSupervisor || auth.isSalesTeam) {
      addAction(
        _ActionItem(
          icon: Icons.calendar_month,
          title: 'My Schedule',
          color: Colors.teal.shade700,
          route: '/my-schedule',
          priority: 5,
        ),
      );
    }

    if (auth.isSupervisor) {
      addAction(
        _ActionItem(
          icon: Icons.task_alt,
          title: 'Leave Approvals',
          color: AppColors.warning,
          route: '/team-leave-queue',
          priority: 2,
        ),
      );
      addAction(
        _ActionItem(
          icon: Icons.calendar_month,
          title: 'Schedule',
          color: Colors.teal.shade700,
          route: '/schedule-management',
          priority: 3,
        ),
      );
      addAction(
        _ActionItem(
          icon: Icons.point_of_sale,
          title: 'Sales',
          color: Colors.indigo,
          route: '/sales',
          priority: 5,
        ),
      );
      addAction(
        _ActionItem(
          icon: Icons.groups_2_outlined,
          title: 'Staff List',
          color: Colors.brown,
          route: '/staff-list',
          priority: 6,
        ),
      );
    }

    if (auth.isManager) {
      addAction(
        _ActionItem(
          icon: Icons.task_alt,
          title: 'Leave Approvals',
          color: AppColors.warning,
          route: '/team-leave-queue',
          priority: 1,
        ),
      );
      addAction(
        _ActionItem(
          icon: Icons.query_stats,
          title: 'Team Attendance',
          color: Colors.cyan,
          route: '/team-attendance',
          priority: 2,
        ),
      );
      addAction(
        _ActionItem(
          icon: Icons.groups_2_outlined,
          title: 'Staff List',
          color: Colors.brown,
          route: '/staff-list',
          priority: 3,
        ),
      );
      addAction(
        _ActionItem(
          icon: Icons.point_of_sale,
          title: 'Sales',
          color: Colors.indigo,
          route: '/sales',
          priority: 4,
        ),
      );
    }

    if (auth.isSalesTeam) {
      addAction(
        _ActionItem(
          icon: Icons.point_of_sale,
          title: 'Sales',
          color: Colors.indigo,
          route: '/sales',
          priority: 2,
        ),
      );
    }

    if (auth.isAdmin) {
      addAction(
        _ActionItem(
          icon: Icons.approval,
          title: 'HR Leave Queue',
          color: Colors.deepOrange.shade700,
          route: '/team-leave-queue',
          priority: 1,
        ),
      );
      addAction(
        _ActionItem(
          icon: Icons.query_stats,
          title: 'Team Attendance',
          color: Colors.cyan,
          route: '/team-attendance',
          priority: 2,
        ),
      );
      addAction(
        _ActionItem(
          icon: Icons.groups_2_outlined,
          title: 'Staff List',
          color: Colors.brown,
          route: '/staff-list',
          priority: 3,
        ),
      );
    }

    actions.sort((a, b) => a.priority.compareTo(b.priority));
    return actions.take(8).toList();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('ATLAS ESS'),
        actions: [
          IconButton(
            icon: const Icon(Icons.logout),
            onPressed: () {
              Provider.of<AuthProvider>(context, listen: false).logout();
            },
          ),
        ],
      ),
      body: SingleChildScrollView(
        padding: AppSpacing.pagePadding,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            AppCard(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Welcome back!',
                    style: TextStyle(
                      fontSize: 14,
                      color: AppColors.textSecondary,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Consumer<AuthProvider>(
                    builder: (context, auth, child) => Text(
                      '${auth.roleLabel} Portal',
                      style: const TextStyle(
                        fontSize: 22,
                        fontWeight: FontWeight.bold,
                        color: AppColors.textPrimary,
                      ),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: AppSpacing.lg),

            Consumer<AuthProvider>(
              builder: (context, auth, _) => _buildActionRequiredSection(auth),
            ),
            const SizedBox(height: AppSpacing.lg),

            _buildQuickActionsSection(),
            const SizedBox(height: AppSpacing.lg),

            Consumer<AuthProvider>(
              builder: (context, auth, _) {
                if (auth.isManager) return const SizedBox.shrink();
                return Column(
                  children: [
                    _buildAttendanceSummarySection(),
                    const SizedBox(height: AppSpacing.lg),
                  ],
                );
              },
            ),

            _buildRecentLeaveHistorySection(),
          ],
        ),
      ),
    );
  }

  Widget _buildActionRequiredSection(AuthProvider auth) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('Action Required', style: Theme.of(context).textTheme.titleMedium),
        const SizedBox(height: AppSpacing.sm),
        if (_loadingActionRequired)
          const AppLoadingState(message: 'Checking your pending actions...')
        else if (_actionRequiredError != null)
          AppErrorState(
            message: _actionRequiredError!,
            onRetry: () => _fetchActionRequired(auth),
          )
        else if (_requiredActions.isEmpty)
          const AppEmptyState(
            title: 'No urgent actions right now.',
            subtitle: 'You are up to date for attendance and leave tasks.',
            icon: Icons.check_circle_outline,
          )
        else
          ..._requiredActions
              .take(2)
              .map(
                (item) => AppCard(
                  margin: const EdgeInsets.only(bottom: AppSpacing.sm),
                  child: Row(
                    children: [
                      Container(
                        width: 44,
                        height: 44,
                        decoration: BoxDecoration(
                          color: item.color.withValues(alpha: 0.12),
                          shape: BoxShape.circle,
                        ),
                        child: Icon(item.icon, color: item.color),
                      ),
                      const SizedBox(width: AppSpacing.sm),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              item.title,
                              style: Theme.of(context).textTheme.bodyLarge,
                            ),
                            const SizedBox(height: 2),
                            Text(
                              item.description,
                              style: Theme.of(context).textTheme.bodyMedium,
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(width: AppSpacing.sm),
                      AppButton(
                        label: item.ctaLabel,
                        variant: AppButtonVariant.outline,
                        onPressed: () => context.push(item.route),
                      ),
                    ],
                  ),
                ),
              ),
      ],
    );
  }

  Widget _buildQuickActionsSection() {
    return Consumer<AuthProvider>(
      builder: (context, auth, child) {
        final actions = _buildActions(auth);
        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Quick Actions',
              style: Theme.of(context).textTheme.titleMedium,
            ),
            const SizedBox(height: 4),
            Text(
              'Most-used tools for your role',
              style: Theme.of(context).textTheme.bodyMedium,
            ),
            const SizedBox(height: AppSpacing.md),
            GridView.count(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              crossAxisCount: 2,
              mainAxisSpacing: AppSpacing.sm,
              crossAxisSpacing: AppSpacing.sm,
              childAspectRatio: 1.1,
              children: actions
                  .map(
                    (action) => _buildActionCard(
                      icon: action.icon,
                      title: action.title,
                      color: action.color,
                      onTap: () => context.push(action.route),
                    ),
                  )
                  .toList(),
            ),
          ],
        );
      },
    );
  }

  Widget _buildAttendanceSummarySection() {
    final clockIn = _parseAttendanceDateTime(
      _todayAttendance?['clock_in']?.toString() ??
          _todayAttendance?['clock_in_time']?.toString(),
    );
    final clockOut = _parseAttendanceDateTime(
      _todayAttendance?['clock_out']?.toString() ??
          _todayAttendance?['clock_out_time']?.toString(),
    );

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(
              'Your Time & Attendance',
              style: Theme.of(context).textTheme.titleMedium,
            ),
            TextButton(
              onPressed: () => context.push('/attendance'),
              child: const Text('View All'),
            ),
          ],
        ),
        const SizedBox(height: AppSpacing.sm),
        if (_loadingAttendanceSummary)
          const AppLoadingState(message: 'Loading attendance summary...')
        else if (_attendanceSummaryError != null)
          AppErrorState(
            message: _attendanceSummaryError!,
            onRetry: _fetchAttendanceSummary,
          )
        else
          AppCard(
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        _workedDurationLabel(),
                        style: Theme.of(context).textTheme.titleLarge,
                      ),
                      const SizedBox(height: 2),
                      Text(
                        'Spent Today',
                        style: Theme.of(context).textTheme.bodyMedium,
                      ),
                      const SizedBox(height: AppSpacing.sm),
                      Text(
                        clockIn == null
                            ? 'No attendance entry found for today.'
                            : (clockOut == null
                                  ? 'Clock In: ${_clockTimeLabel(clockIn)}'
                                  : 'Clock In: ${_clockTimeLabel(clockIn)} • Clock Out: ${_clockTimeLabel(clockOut)}'),
                        style: Theme.of(context).textTheme.bodyMedium,
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: AppSpacing.sm),
                Column(
                  children: [
                    AppButton(
                      label: 'Apply Leave',
                      variant: AppButtonVariant.outline,
                      onPressed: () => context.push('/leaves'),
                    ),
                    const SizedBox(height: AppSpacing.xs),
                    AppButton(
                      label: 'Regularize',
                      variant: AppButtonVariant.tonal,
                      onPressed: () => context.push('/attendance'),
                    ),
                  ],
                ),
              ],
            ),
          ),
      ],
    );
  }

  Widget _buildRecentLeaveHistorySection() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(
              'Recent Leave History',
              style: Theme.of(context).textTheme.titleMedium,
            ),
            TextButton(
              onPressed: () => context.push('/leaves'),
              child: const Text('View All'),
            ),
          ],
        ),
        const SizedBox(height: AppSpacing.sm),
        if (_loadingLeaveHistory)
          const AppLoadingState(message: 'Loading leave history...')
        else if (_leaveHistoryError != null)
          AppErrorState(
            message: _leaveHistoryError!,
            onRetry: _fetchRecentLeaveHistory,
          )
        else if (_recentLeaveHistory.isEmpty)
          const AppEmptyState(
            title: 'No leave requests yet.',
            subtitle: 'Your recent leave requests will appear here.',
            icon: Icons.event_note_outlined,
          )
        else
          ..._recentLeaveHistory.map((leave) {
            final status = (leave['status'] ?? 'pending').toString();
            final workflow = (leave['workflow_status'] ?? 'pending').toString();
            final rejectionReason = leave['rejection_reason']
                ?.toString()
                .trim();
            final managerComment = leave['manager_comment']?.toString().trim();

            String? note;
            if (rejectionReason != null && rejectionReason.isNotEmpty) {
              note = 'Rejected reason: $rejectionReason';
            } else if (managerComment != null && managerComment.isNotEmpty) {
              note = 'Approval note: $managerComment';
            }

            return AppCard(
              margin: const EdgeInsets.only(bottom: 10),
              padding: const EdgeInsets.all(12),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Expanded(
                        child: Text(
                          leave['leave_type']?['name']?.toString() ?? 'Leave',
                          style: const TextStyle(
                            fontWeight: FontWeight.w600,
                            color: AppColors.textPrimary,
                          ),
                        ),
                      ),
                      Text(
                        _toTitle(status),
                        style: TextStyle(
                          color: _statusColor(status),
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 4),
                  Text(
                    '${leave['start_date']} to ${leave['end_date']}',
                    style: const TextStyle(color: AppColors.textPrimary),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    'Stage: ${_toTitle(workflow)}',
                    style: const TextStyle(
                      fontSize: 12,
                      color: AppColors.textSecondary,
                    ),
                  ),
                  if (note != null) ...[
                    const SizedBox(height: 2),
                    Text(
                      note,
                      style: TextStyle(
                        fontSize: 12,
                        color: status.toLowerCase() == 'rejected'
                            ? AppColors.danger
                            : AppColors.textSecondary,
                      ),
                    ),
                  ],
                ],
              ),
            );
          }),
      ],
    );
  }

  Widget _buildActionCard({
    required IconData icon,
    required String title,
    required Color color,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: AppSpacing.cardRadius,
      child: AppCard(
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: color.withValues(alpha: 0.1),
                shape: BoxShape.circle,
              ),
              child: Icon(icon, color: color, size: 22),
            ),
            const SizedBox(width: AppSpacing.sm),
            Expanded(
              child: Text(
                title,
                style: const TextStyle(
                  fontWeight: FontWeight.w600,
                  color: AppColors.textPrimary,
                ),
              ),
            ),
            const Icon(Icons.chevron_right, color: AppColors.textSecondary),
          ],
        ),
      ),
    );
  }
}

class _ActionItem {
  final IconData icon;
  final String title;
  final Color color;
  final String route;
  final int priority;

  _ActionItem({
    required this.icon,
    required this.title,
    required this.color,
    required this.route,
    required this.priority,
  });
}

class _RequiredActionItem {
  final IconData icon;
  final String title;
  final String description;
  final String route;
  final Color color;
  final String ctaLabel;

  _RequiredActionItem({
    required this.icon,
    required this.title,
    required this.description,
    required this.route,
    required this.color,
    required this.ctaLabel,
  });
}

import 'package:dio/dio.dart';
import 'package:flutter/material.dart';

import '../config/api_client.dart';
import '../theme/app_colors.dart';
import '../theme/app_spacing.dart';
import '../widgets/app_card.dart';
import '../widgets/state_widgets.dart';
import 'leave_request_modal.dart';

class LeavesScreen extends StatefulWidget {
  const LeavesScreen({super.key});

  @override
  State<LeavesScreen> createState() => _LeavesScreenState();
}

class _LeavesScreenState extends State<LeavesScreen> {
  final Dio _api = ApiClient().dio;

  bool _isLoading = true;
  String? _errorMessage;
  List<Map<String, dynamic>> _leaveRequests = [];

  @override
  void initState() {
    super.initState();
    _fetchLeaves();
  }

  Future<void> _fetchLeaves() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      Response<dynamic> response;
      try {
        response = await _api.get(
          '/leave-requests',
          queryParameters: {'mine': 1},
        );
      } on DioException {
        response = await _api.get('/leaves');
      }

      final payload = response.data;
      final extracted = _extractList(payload).whereType<Map<String, dynamic>>();

      if (!mounted) return;
      setState(() {
        _leaveRequests = extracted.toList();
        _isLoading = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _isLoading = false;
        _errorMessage = 'Failed to load leave requests.';
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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('My Leaves'),
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: () {
          showModalBottomSheet(
            context: context,
            isScrollControlled: true,
            backgroundColor: Colors.transparent,
            builder: (context) => LeaveRequestModal(onSuccess: _fetchLeaves),
          );
        },
        child: const Icon(Icons.add),
      ),
      body: RefreshIndicator(
        onRefresh: _fetchLeaves,
        child: ListView(
          padding: AppSpacing.pagePadding,
          children: [
            Text(
              'Leave Overview',
              style: Theme.of(context).textTheme.titleMedium,
            ),
            const SizedBox(height: AppSpacing.sm),
            if (_isLoading)
              const AppLoadingState(message: 'Loading leave requests...')
            else if (_errorMessage != null)
              AppErrorState(message: _errorMessage!, onRetry: _fetchLeaves)
            else if (_leaveRequests.isEmpty)
              const AppEmptyState(
                title: 'No leave history found.',
                subtitle: 'Use + to create your first leave request.',
                icon: Icons.event_busy_outlined,
              )
            else ...[
              _buildSummaryRow(),
              const SizedBox(height: AppSpacing.md),
              Text(
                'Recent Requests',
                style: Theme.of(context).textTheme.titleMedium,
              ),
              const SizedBox(height: AppSpacing.sm),
              ..._leaveRequests.map(_buildLeaveCard),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildSummaryRow() {
    final approved = _leaveRequests
        .where((leave) => (leave['status'] ?? '').toString().toLowerCase() == 'approved')
        .length;
    final rejected = _leaveRequests
        .where((leave) => (leave['status'] ?? '').toString().toLowerCase() == 'rejected')
        .length;
    final pending = _leaveRequests
        .where((leave) {
          final status = (leave['status'] ?? '').toString().toLowerCase();
          final workflow = (leave['workflow_status'] ?? '').toString().toLowerCase();
          return status == 'pending' || workflow == 'pending';
        })
        .length;

    return Row(
      children: [
        Expanded(
          child: _buildSummaryCard(
            title: 'Pending',
            value: pending.toString(),
            color: AppColors.warning,
            icon: Icons.hourglass_empty,
          ),
        ),
        const SizedBox(width: AppSpacing.sm),
        Expanded(
          child: _buildSummaryCard(
            title: 'Approved',
            value: approved.toString(),
            color: AppColors.success,
            icon: Icons.check_circle_outline,
          ),
        ),
        const SizedBox(width: AppSpacing.sm),
        Expanded(
          child: _buildSummaryCard(
            title: 'Rejected',
            value: rejected.toString(),
            color: AppColors.danger,
            icon: Icons.cancel_outlined,
          ),
        ),
      ],
    );
  }

  Widget _buildSummaryCard({
    required String title,
    required String value,
    required Color color,
    required IconData icon,
  }) {
    return AppCard(
      child: Column(
        children: [
          Icon(icon, color: color),
          const SizedBox(height: AppSpacing.xs),
          Text(value, style: Theme.of(context).textTheme.titleLarge),
          const SizedBox(height: 2),
          Text(title, style: Theme.of(context).textTheme.bodyMedium),
        ],
      ),
    );
  }

  Widget _buildLeaveCard(Map<String, dynamic> leave) {
    final status = (leave['status'] ?? 'pending').toString();
    final workflowStatus = (leave['workflow_status'] ?? 'pending').toString();
    final reason = leave['reason']?.toString().trim();
    final rejectionReason = leave['rejection_reason']?.toString().trim();
    final managerComment = leave['manager_comment']?.toString().trim();

    String? note;
    if (rejectionReason != null && rejectionReason.isNotEmpty) {
      note = 'Rejected reason: $rejectionReason';
    } else if (managerComment != null && managerComment.isNotEmpty) {
      note = 'Approval note: $managerComment';
    }

    return AppCard(
      margin: const EdgeInsets.only(bottom: AppSpacing.sm),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.only(top: 2),
            child: Icon(_statusIcon(status), color: _getStatusColor(status), size: 28),
          ),
          const SizedBox(width: AppSpacing.sm),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Expanded(
                      child: Text(
                        leave['leave_type']?['name']?.toString() ?? 'Leave',
                        style: Theme.of(context).textTheme.bodyLarge,
                      ),
                    ),
                    Text(
                      _toTitle(status),
                      style: TextStyle(
                        color: _getStatusColor(status),
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 2),
                Text(
                  '${leave['start_date']} to ${leave['end_date']}',
                  style: Theme.of(context).textTheme.bodyMedium,
                ),
                const SizedBox(height: 2),
                Text(
                  'Stage: ${_toTitle(workflowStatus)}',
                  style: Theme.of(context).textTheme.bodyMedium,
                ),
                if (reason != null && reason.isNotEmpty) ...[
                  const SizedBox(height: 2),
                  Text(
                    'Request: $reason',
                    style: Theme.of(context).textTheme.bodyMedium,
                  ),
                ],
                if (note != null) ...[
                  const SizedBox(height: 2),
                  Text(
                    note,
                    style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                          color: status.toLowerCase() == 'rejected'
                              ? AppColors.danger
                              : AppColors.textSecondary,
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

  Color _getStatusColor(String status) {
    switch (status.toLowerCase()) {
      case 'approved':
        return AppColors.success;
      case 'rejected':
        return AppColors.danger;
      default:
        return AppColors.warning;
    }
  }

  IconData _statusIcon(String status) {
    switch (status.toLowerCase()) {
      case 'approved':
        return Icons.check_circle_outline;
      case 'rejected':
        return Icons.cancel_outlined;
      default:
        return Icons.hourglass_empty;
    }
  }

  String _toTitle(String value) {
    return value
        .replaceAll('_', ' ')
        .split(' ')
        .where((e) => e.isNotEmpty)
        .map((e) => e[0].toUpperCase() + e.substring(1).toLowerCase())
        .join(' ');
  }
}

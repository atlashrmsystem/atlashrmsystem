import 'package:dio/dio.dart';
import 'package:flutter/material.dart';

import '../config/api_client.dart';
import '../theme/app_colors.dart';
import '../theme/app_spacing.dart';
import '../widgets/app_button.dart';
import '../widgets/app_card.dart';
import '../widgets/state_widgets.dart';

class TeamLeaveQueueScreen extends StatefulWidget {
  const TeamLeaveQueueScreen({super.key});

  @override
  State<TeamLeaveQueueScreen> createState() => _TeamLeaveQueueScreenState();
}

class _TeamLeaveQueueScreenState extends State<TeamLeaveQueueScreen> {
  final Dio _api = ApiClient().dio;

  bool _isLoading = true;
  String? _errorMessage;
  int? _processingId;
  List<Map<String, dynamic>> _items = [];

  @override
  void initState() {
    super.initState();
    _load();
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

  Future<void> _load() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final res = await _api.get('/leave-requests');
      if (!mounted) return;
      setState(() {
        _items = _extractList(res.data).whereType<Map<String, dynamic>>().toList();
        _isLoading = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _isLoading = false;
        _errorMessage = 'Failed to load leave queue.';
      });
    }
  }

  Future<void> _approve(int id) async {
    setState(() => _processingId = id);
    try {
      await _api.put(
        '/leave-requests/$id/approve',
        data: {'comment': 'Approved from mobile'},
      );
      await _load();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Leave request approved.'),
            backgroundColor: AppColors.success,
          ),
        );
      }
    } catch (e) {
      final msg = (e is DioException && e.response?.data is Map<String, dynamic>)
          ? (e.response?.data['message']?.toString() ?? 'Approval failed.')
          : 'Approval failed.';
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(msg), backgroundColor: AppColors.danger),
        );
      }
    } finally {
      if (mounted) setState(() => _processingId = null);
    }
  }

  Future<void> _reject(int id) async {
    final controller = TextEditingController();
    final reason = await showDialog<String>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Reject Leave Request'),
        content: TextField(
          controller: controller,
          decoration: const InputDecoration(hintText: 'Reason'),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancel'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, controller.text.trim()),
            child: const Text('Reject'),
          ),
        ],
      ),
    );

    if (reason == null || reason.isEmpty) return;

    setState(() => _processingId = id);
    try {
      await _api.put('/leave-requests/$id/reject', data: {'reason': reason});
      await _load();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Leave request rejected.'),
            backgroundColor: AppColors.success,
          ),
        );
      }
    } catch (e) {
      final msg = (e is DioException && e.response?.data is Map<String, dynamic>)
          ? (e.response?.data['message']?.toString() ?? 'Rejection failed.')
          : 'Rejection failed.';
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(msg), backgroundColor: AppColors.danger),
        );
      }
    } finally {
      if (mounted) setState(() => _processingId = null);
    }
  }

  String _statusLabel(Map<String, dynamic> item) {
    return (item['workflow_status'] ?? item['status'] ?? 'pending').toString();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Leave Approvals')),
      body: RefreshIndicator(
        onRefresh: _load,
        child: ListView(
          padding: AppSpacing.pagePadding,
          children: [
            if (_isLoading)
              const AppLoadingState(message: 'Loading leave queue...')
            else if (_errorMessage != null)
              AppErrorState(message: _errorMessage!, onRetry: _load)
            else if (_items.isEmpty)
              const AppEmptyState(
                title: 'No pending leave requests.',
                subtitle: 'All caught up for now.',
                icon: Icons.task_alt,
              )
            else
              ..._items.map((item) {
                final employee = item['employee'] as Map<String, dynamic>?;
                final id = item['id'] as int?;
                final status = _statusLabel(item);
                final isBusy = _processingId == id;

                return AppCard(
                  margin: const EdgeInsets.only(bottom: AppSpacing.sm),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Expanded(
                            child: Text(
                              employee?['full_name']?.toString() ?? 'Employee',
                              style: Theme.of(context).textTheme.bodyLarge,
                            ),
                          ),
                          Text(
                            status.toUpperCase(),
                            style: const TextStyle(
                              color: AppColors.warning,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 4),
                      Text(
                        '${item['start_date'] ?? '-'} to ${item['end_date'] ?? '-'}',
                        style: Theme.of(context).textTheme.bodyMedium,
                      ),
                      const SizedBox(height: AppSpacing.sm),
                      Row(
                        children: [
                          Expanded(
                            child: AppButton(
                              label: 'Reject',
                              variant: AppButtonVariant.outline,
                              onPressed: (id == null || isBusy)
                                  ? null
                                  : () => _reject(id),
                            ),
                          ),
                          const SizedBox(width: AppSpacing.sm),
                          Expanded(
                            child: AppButton(
                              label: isBusy ? 'Processing...' : 'Approve',
                              onPressed: (id == null || isBusy)
                                  ? null
                                  : () => _approve(id),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                );
              }),
          ],
        ),
      ),
    );
  }
}

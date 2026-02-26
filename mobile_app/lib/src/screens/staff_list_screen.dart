import 'package:dio/dio.dart';
import 'package:flutter/material.dart';

import '../config/api_client.dart';
import '../theme/app_colors.dart';
import '../theme/app_spacing.dart';
import '../widgets/app_card.dart';
import '../widgets/state_widgets.dart';

class StaffListScreen extends StatefulWidget {
  const StaffListScreen({super.key});

  @override
  State<StaffListScreen> createState() => _StaffListScreenState();
}

class _StaffListScreenState extends State<StaffListScreen> {
  final Dio _api = ApiClient().dio;
  final TextEditingController _searchController = TextEditingController();

  bool _loading = true;
  String? _errorMessage;
  List<Map<String, dynamic>> _stores = [];
  List<Map<String, dynamic>> _staff = [];
  int? _storeId;

  @override
  void initState() {
    super.initState();
    _bootstrap();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
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
      _loading = true;
      _errorMessage = null;
    });

    try {
      final storesRes = await _api.get('/stores');
      _stores = _extractList(storesRes.data).whereType<Map<String, dynamic>>().toList();
      if (_stores.isNotEmpty) {
        _storeId = _stores.first['id'] as int?;
      }
      await _loadStaff();
      if (mounted) setState(() => _loading = false);
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _errorMessage = 'Failed to load staff data.';
      });
    }
  }

  Future<void> _loadStaff() async {
    final query = <String, dynamic>{};
    if (_storeId != null) query['store_id'] = _storeId;
    if (_searchController.text.trim().isNotEmpty) {
      query['q'] = _searchController.text.trim();
    }

    final res = await _api.get('/staff', queryParameters: query);
    if (!mounted) return;
    setState(() {
      _staff = _extractList(res.data).whereType<Map<String, dynamic>>().toList();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Staff List')),
      body: RefreshIndicator(
        onRefresh: _bootstrap,
        child: ListView(
          padding: AppSpacing.pagePadding,
          children: [
            if (_loading)
              const AppLoadingState(message: 'Loading staff...')
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
                        await _loadStaff();
                      },
                    ),
                    const SizedBox(height: AppSpacing.sm),
                    TextField(
                      controller: _searchController,
                      decoration: InputDecoration(
                        labelText: 'Search staff',
                        suffixIcon: IconButton(
                          icon: const Icon(Icons.search),
                          onPressed: _loadStaff,
                        ),
                      ),
                      onSubmitted: (_) => _loadStaff(),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: AppSpacing.sm),
              if (_staff.isEmpty)
                const AppEmptyState(
                  title: 'No staff found.',
                  icon: Icons.group_off,
                )
              else
                ..._staff.map((data) {
                  final fullName = data['full_name']?.toString() ?? 'Staff';
                  final status = (data['status'] ?? 'active').toString();
                  final isActive = status.toLowerCase() == 'active';
                  return AppCard(
                    margin: const EdgeInsets.only(bottom: AppSpacing.sm),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        CircleAvatar(
                          child: Text(
                            fullName.isNotEmpty
                                ? fullName.substring(0, 1).toUpperCase()
                                : '?',
                          ),
                        ),
                        const SizedBox(width: AppSpacing.sm),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(fullName, style: Theme.of(context).textTheme.bodyLarge),
                              Text(
                                '${data['job_title'] ?? '-'} • ${data['department'] ?? '-'}',
                                style: Theme.of(context).textTheme.bodyMedium,
                              ),
                              Text(
                                '${data['phone'] ?? '-'}',
                                style: Theme.of(context).textTheme.bodyMedium,
                              ),
                            ],
                          ),
                        ),
                        Text(
                          status.toUpperCase(),
                          style: TextStyle(
                            color: isActive ? AppColors.success : AppColors.danger,
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

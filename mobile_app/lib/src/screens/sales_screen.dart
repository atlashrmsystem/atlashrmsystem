import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../config/api_client.dart';
import '../providers/auth_provider.dart';
import '../services/offline_sync_service.dart';
import '../theme/app_colors.dart';
import '../theme/app_spacing.dart';
import '../widgets/app_button.dart';
import '../widgets/app_card.dart';
import '../widgets/state_widgets.dart';

class SalesScreen extends StatefulWidget {
  const SalesScreen({super.key});

  @override
  State<SalesScreen> createState() => _SalesScreenState();
}

class _SalesScreenState extends State<SalesScreen> {
  final Dio _api = ApiClient().dio;
  final TextEditingController _amountController = TextEditingController();

  bool _loading = true;
  String? _errorMessage;
  bool _saving = false;

  List<Map<String, dynamic>> _stores = [];
  List<Map<String, dynamic>> _reportRows = [];

  int? _storeId;
  int? _assignedStoreId;
  String? _assignedStoreName;
  DateTime _selectedDate = DateTime.now();
  double _totalAmount = 0;
  bool _isReadOnlyView = false;

  @override
  void initState() {
    super.initState();
    _bootstrap();
  }

  @override
  void dispose() {
    _amountController.dispose();
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

  int? _toInt(dynamic value) {
    if (value is int) return value;
    if (value is num) return value.toInt();
    return int.tryParse(value?.toString() ?? '');
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
    for (final map in _stores) {
      if (_toInt(map['id']) == _storeId) {
        final name = map['name']?.toString().trim();
        if (name != null && name.isNotEmpty) return name;
      }
    }
    return _assignedStoreName ?? 'No assigned store';
  }

  String _storeNameById(dynamic rawStoreId) {
    final id = _toInt(rawStoreId);
    if (id == null) return 'Unknown Store';
    for (final store in _stores) {
      if (_toInt(store['id']) == id) {
        final name = store['name']?.toString().trim();
        if (name != null && name.isNotEmpty) return name;
      }
    }
    return 'Store #$id';
  }

  String _reportContextStoreLabel() {
    if (_storeId != null) {
      return _selectedStoreLabel();
    }
    return 'All Accessible Stores';
  }

  Future<void> _bootstrap() async {
    setState(() {
      _loading = true;
      _errorMessage = null;
    });

    final auth = Provider.of<AuthProvider>(context, listen: false);

    try {
      await OfflineSyncService().flushPending();
      _isReadOnlyView = auth.isManager || auth.isSalesTeam || auth.isAdmin;

      final responses = await Future.wait([
        _api.get('/stores'),
        _api.get('/employees/me'),
      ]);

      _stores = _extractList(
        responses[0].data,
      ).whereType<Map<String, dynamic>>().toList();
      _assignedStoreId = _extractAssignedStoreId(responses[1].data);
      _assignedStoreName = _extractAssignedStoreName(responses[1].data);

      if (_stores.isNotEmpty && !_isReadOnlyView) {
        if (_assignedStoreId != null && _storesContain(_assignedStoreId!)) {
          _storeId = _assignedStoreId;
        } else {
          _storeId = _stores.first['id'] as int?;
        }
      }

      await _loadReport();
      if (mounted) setState(() => _loading = false);
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _errorMessage = 'Failed to load sales data.';
      });
    }
  }

  Future<void> _loadReport() async {
    final dateStr = _selectedDate.toIso8601String().split('T').first;
    final query = <String, dynamic>{
      'group_by': 'day',
      'date_from': dateStr,
      'date_to': dateStr,
    };
    if (_storeId != null) {
      query['store_id'] = _storeId;
    }

    final reportRes = await _api.get('/sales/report', queryParameters: query);
    final rows = _extractList(
      reportRes.data,
    ).whereType<Map<String, dynamic>>().toList();
    final selectedDateRows = rows.where((row) {
      final period = row['period']?.toString() ?? '';
      return period == dateStr;
    }).toList();

    if (!mounted) return;
    setState(() {
      _reportRows = selectedDateRows;
      _totalAmount = selectedDateRows.fold<double>(
        0,
        (prev, row) =>
            prev + (double.tryParse(row['total_amount'].toString()) ?? 0),
      );
    });
  }

  Future<void> _saveSales() async {
    if (_storeId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('No store assigned to your profile.'),
          backgroundColor: AppColors.danger,
        ),
      );
      return;
    }

    final amount = double.tryParse(_amountController.text.trim());
    if (amount == null || amount < 0) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Enter a valid amount.'),
          backgroundColor: AppColors.danger,
        ),
      );
      return;
    }

    setState(() => _saving = true);
    try {
      await _api.post(
        '/sales',
        data: {
          'store_id': _storeId,
          'date': _selectedDate.toIso8601String().split('T').first,
          'amount': amount,
        },
      );
      _amountController.clear();
      await _loadReport();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Sales submitted successfully.'),
            backgroundColor: AppColors.success,
          ),
        );
      }
    } on DioException catch (e) {
      if (_isConnectivityError(e)) {
        await OfflineSyncService().queueSalesEvent(
          storeId: _storeId!,
          date: _selectedDate.toIso8601String().split('T').first,
          amount: amount,
          localTime: DateTime.now(),
        );
        _amountController.clear();
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text(
                'You are offline. Sales entry saved and will sync automatically.',
              ),
              backgroundColor: AppColors.warning,
            ),
          );
        }
        return;
      }

      final msg = (e.response?.data is Map<String, dynamic>)
          ? (e.response?.data['message']?.toString() ??
                'Failed to submit sales.')
          : 'Failed to submit sales.';
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(msg), backgroundColor: AppColors.danger),
        );
      }
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  bool _isConnectivityError(DioException e) {
    return e.type == DioExceptionType.connectionError ||
        e.type == DioExceptionType.connectionTimeout ||
        e.type == DioExceptionType.receiveTimeout ||
        e.type == DioExceptionType.sendTimeout;
  }

  bool _isSelectedDateToday() {
    final now = DateTime.now();
    return now.year == _selectedDate.year &&
        now.month == _selectedDate.month &&
        now.day == _selectedDate.day;
  }

  Future<void> _pickSalesDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _selectedDate,
      firstDate: DateTime.now().subtract(const Duration(days: 365)),
      lastDate: DateTime.now().add(const Duration(days: 365)),
    );
    if (picked == null) return;

    final normalized = DateTime(picked.year, picked.month, picked.day);
    if (!mounted) return;
    setState(() => _selectedDate = normalized);
    await _loadReport();
  }

  @override
  Widget build(BuildContext context) {
    final dateStr = _selectedDate.toIso8601String().split('T').first;
    final isToday = _isSelectedDateToday();
    final reportStoreLabel = _reportContextStoreLabel();

    return Scaffold(
      appBar: AppBar(title: const Text('Sales')),
      body: RefreshIndicator(
        onRefresh: _bootstrap,
        child: ListView(
          padding: AppSpacing.pagePadding,
          children: [
            if (_loading)
              const AppLoadingState(message: 'Loading sales data...')
            else if (_errorMessage != null)
              AppErrorState(message: _errorMessage!, onRetry: _bootstrap)
            else ...[
              AppCard(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      _isReadOnlyView
                          ? 'Selected Date Sales (Accessible Stores)'
                          : 'Submit Sales Amount',
                      style: Theme.of(context).textTheme.titleMedium,
                    ),
                    if (_assignedStoreName != null) ...[
                      const SizedBox(height: 6),
                      Text(
                        'Assigned Store: $_assignedStoreName',
                        style: Theme.of(context).textTheme.bodyMedium,
                      ),
                    ],
                    const SizedBox(height: AppSpacing.sm),
                    DropdownButtonFormField<int?>(
                      initialValue: _storeId,
                      decoration: InputDecoration(
                        labelText: 'Store: ${_selectedStoreLabel()}',
                      ),
                      items: [
                        if (_isReadOnlyView)
                          const DropdownMenuItem<int?>(
                            value: null,
                            child: Text('All Accessible Stores'),
                          ),
                        ..._stores.map(
                          (e) => DropdownMenuItem<int?>(
                            value: e['id'] as int,
                            child: Text(e['name']?.toString() ?? 'Store'),
                          ),
                        ),
                      ],
                      onChanged: (value) async {
                        setState(() => _storeId = value);
                        await _loadReport();
                      },
                    ),
                    const SizedBox(height: AppSpacing.sm),
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            'Date: $dateStr',
                            style: Theme.of(context).textTheme.bodyMedium,
                          ),
                        ),
                        TextButton.icon(
                          onPressed: _pickSalesDate,
                          icon: const Icon(
                            Icons.calendar_today_outlined,
                            size: 18,
                          ),
                          label: const Text('Select Date'),
                        ),
                      ],
                    ),
                    if (!_isReadOnlyView) ...[
                      const SizedBox(height: AppSpacing.sm),
                      TextField(
                        controller: _amountController,
                        keyboardType: const TextInputType.numberWithOptions(
                          decimal: true,
                        ),
                        decoration: const InputDecoration(
                          labelText: 'Amount',
                          prefixText: 'AED ',
                        ),
                      ),
                      const SizedBox(height: AppSpacing.sm),
                      SizedBox(
                        width: double.infinity,
                        child: AppButton(
                          label: _saving ? 'Submitting...' : 'Submit',
                          onPressed: _saving ? null : _saveSales,
                        ),
                      ),
                    ],
                  ],
                ),
              ),
              const SizedBox(height: AppSpacing.sm),
              AppCard(
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          isToday ? 'Today Total' : 'Selected Date Total',
                          style: Theme.of(context).textTheme.bodyLarge,
                        ),
                        Text(
                          dateStr,
                          style: Theme.of(context).textTheme.bodyMedium,
                        ),
                        const SizedBox(height: 2),
                        Text(
                          reportStoreLabel,
                          style: Theme.of(context).textTheme.bodySmall
                              ?.copyWith(
                                color: AppColors.textSecondary,
                                fontWeight: FontWeight.w600,
                              ),
                        ),
                      ],
                    ),
                    Text(
                      'AED ${_totalAmount.toStringAsFixed(2)}',
                      style: Theme.of(context).textTheme.titleMedium,
                    ),
                  ],
                ),
              ),
              const SizedBox(height: AppSpacing.sm),
              if (_reportRows.isEmpty)
                AppEmptyState(
                  title: isToday
                      ? 'No sales entries for today.'
                      : 'No sales entries for selected date.',
                  icon: Icons.point_of_sale,
                )
              else
                ..._reportRows.map((data) {
                  final storeName = _storeNameById(data['store_id']);
                  final periodLabel = data['period']?.toString() ?? dateStr;
                  return AppCard(
                    margin: const EdgeInsets.only(bottom: AppSpacing.sm),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              storeName,
                              style: Theme.of(context).textTheme.bodyLarge,
                            ),
                            Text(
                              '$periodLabel • Entries: ${data['entries_count'] ?? 0}',
                              style: Theme.of(context).textTheme.bodyMedium,
                            ),
                          ],
                        ),
                        Text('AED ${data['total_amount'] ?? 0}'),
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

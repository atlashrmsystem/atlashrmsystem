import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:open_filex/open_filex.dart';
import 'package:path_provider/path_provider.dart';
import 'package:provider/provider.dart';

import '../config/api_client.dart';
import '../providers/auth_provider.dart';
import '../theme/app_colors.dart';
import '../theme/app_spacing.dart';
import '../widgets/app_button.dart';
import '../widgets/app_card.dart';
import '../widgets/state_widgets.dart';

class PayslipsScreen extends StatefulWidget {
  const PayslipsScreen({super.key});

  @override
  State<PayslipsScreen> createState() => _PayslipsScreenState();
}

class _PayslipsScreenState extends State<PayslipsScreen> {
  final Dio _api = ApiClient().dio;

  bool _loading = true;
  String? _errorMessage;
  bool _requestingCertificate = false;
  List<Map<String, dynamic>> _payslips = [];
  List<Map<String, dynamic>> _certificateRequests = [];
  int? _downloadingPayslipId;

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

  int? _currentEmployeeId() {
    final raw = context.read<AuthProvider>().user?['employee_id'];
    if (raw is int) return raw;
    return int.tryParse(raw?.toString() ?? '');
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _errorMessage = null;
    });
    try {
      final employeeId = _currentEmployeeId();
      final query = employeeId == null ? null : {'employee_id': employeeId};
      final responses = await Future.wait([
        _api.get('/pay-slips', queryParameters: query),
        _api.get('/salary-certificate-requests', queryParameters: query),
      ]);

      if (!mounted) return;
      setState(() {
        _payslips = _extractList(
          responses[0].data,
        ).whereType<Map<String, dynamic>>().toList();
        _certificateRequests = _extractList(
          responses[1].data,
        ).whereType<Map<String, dynamic>>().toList();
        _loading = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _errorMessage = 'Failed to load payslips data.';
      });
    }
  }

  Future<void> _requestSalaryCertificate() async {
    setState(() => _requestingCertificate = true);
    try {
      await _api.post('/salary-certificate-requests', data: {});
      await _load();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Salary certificate request submitted.'),
            backgroundColor: AppColors.success,
          ),
        );
      }
    } on DioException catch (e) {
      final msg = (e.response?.data is Map<String, dynamic>)
          ? (e.response?.data['message']?.toString() ?? 'Request failed.')
          : 'Request failed.';
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(msg), backgroundColor: AppColors.danger),
        );
      }
    } finally {
      if (mounted) setState(() => _requestingCertificate = false);
    }
  }

  Future<void> _downloadPayslip(Map<String, dynamic> row) async {
    final rawId = row['id'];
    if (rawId is! int) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Invalid payslip id.'),
          backgroundColor: AppColors.danger,
        ),
      );
      return;
    }

    setState(() => _downloadingPayslipId = rawId);
    try {
      final dir = await getApplicationDocumentsDirectory();
      final monthLabel = (row['month']?.toString() ?? 'payslip').replaceAll(
        '/',
        '-',
      );
      final filePath = '${dir.path}/$monthLabel-$rawId.pdf';

      await _api.download('/pay-slips/$rawId/download', filePath);
      await OpenFilex.open(filePath);

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Payslip downloaded successfully.'),
            backgroundColor: AppColors.success,
          ),
        );
      }
    } on DioException catch (e) {
      final msg = (e.response?.data is Map<String, dynamic>)
          ? (e.response?.data['message']?.toString() ??
                'Failed to download payslip.')
          : 'Failed to download payslip.';
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(msg), backgroundColor: AppColors.danger),
        );
      }
    } catch (_) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Failed to open downloaded payslip.'),
            backgroundColor: AppColors.danger,
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _downloadingPayslipId = null);
    }
  }

  String _latestCertificateStatus() {
    if (_certificateRequests.isEmpty) return 'No requests yet';
    return (_certificateRequests.first['status']?.toString() ?? 'pending')
        .replaceAll('_', ' ')
        .toUpperCase();
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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Payslips & Certificates'),
      ),
      body: RefreshIndicator(
        onRefresh: _load,
        child: ListView(
          padding: AppSpacing.pagePadding,
          children: [
            if (_loading)
              const AppLoadingState(message: 'Loading payslips data...')
            else if (_errorMessage != null)
              AppErrorState(message: _errorMessage!, onRetry: _load)
            else ...[
              _buildCertificateCard(),
              const SizedBox(height: AppSpacing.md),
              Text(
                'My Payslips',
                style: Theme.of(context).textTheme.titleMedium,
              ),
              const SizedBox(height: AppSpacing.sm),
              if (_payslips.isEmpty)
                const AppEmptyState(
                  title: 'No payslips found.',
                  subtitle: 'Payslips will appear here once generated.',
                  icon: Icons.receipt_long,
                )
              else
                ..._payslips.map(_buildPayslipCard),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildCertificateCard() {
    final status = _certificateRequests.isEmpty
        ? 'pending'
        : (_certificateRequests.first['status']?.toString() ?? 'pending');

    return AppCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Salary Certificate',
            style: Theme.of(context).textTheme.titleMedium,
          ),
          const SizedBox(height: AppSpacing.xs),
          Row(
            children: [
              const Text('Latest status: '),
              Text(
                _latestCertificateStatus(),
                style: TextStyle(
                  color: _statusColor(status),
                  fontWeight: FontWeight.w700,
                ),
              ),
            ],
          ),
          const SizedBox(height: AppSpacing.sm),
          SizedBox(
            width: double.infinity,
            child: AppButton(
              label: _requestingCertificate
                  ? 'Submitting...'
                  : 'Request Certificate',
              leadingIcon: Icons.description_outlined,
              onPressed: _requestingCertificate ? null : _requestSalaryCertificate,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildPayslipCard(Map<String, dynamic> data) {
    final id = data['id'];

    return AppCard(
      margin: const EdgeInsets.only(bottom: AppSpacing.sm),
      child: Row(
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              color: AppColors.brandAccent.withValues(alpha: 0.12),
              shape: BoxShape.circle,
            ),
            child: const Icon(Icons.receipt_long, color: AppColors.brandAccent),
          ),
          const SizedBox(width: AppSpacing.sm),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  data['month']?.toString() ?? 'Payslip',
                  style: Theme.of(context).textTheme.bodyLarge,
                ),
                const SizedBox(height: 2),
                Text(
                  'Generated: ${data['generated_at'] ?? data['created_at'] ?? '-'}',
                  style: Theme.of(context).textTheme.bodyMedium,
                ),
              ],
            ),
          ),
          IconButton(
            icon: _downloadingPayslipId == id
                ? const SizedBox(
                    width: 20,
                    height: 20,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : const Icon(Icons.download_outlined),
            onPressed: _downloadingPayslipId == id
                ? null
                : () => _downloadPayslip(data),
          ),
        ],
      ),
    );
  }
}

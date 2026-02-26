import 'package:dio/dio.dart';
import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';

import '../config/api_client.dart';
import '../theme/app_colors.dart';
import '../theme/app_spacing.dart';
import '../widgets/app_button.dart';
import '../widgets/app_card.dart';

class LeaveRequestModal extends StatefulWidget {
  final VoidCallback onSuccess;

  const LeaveRequestModal({super.key, required this.onSuccess});

  @override
  State<LeaveRequestModal> createState() => _LeaveRequestModalState();
}

class _LeaveRequestModalState extends State<LeaveRequestModal> {
  final _formKey = GlobalKey<FormState>();
  final _api = ApiClient().dio;
  final TextEditingController _reasonController = TextEditingController();

  String _leaveType = 'Sick Leave';
  DateTime? _startDate;
  DateTime? _endDate;
  PlatformFile? _pickedFile;
  bool _isSubmitting = false;
  Map<String, int> _leaveTypeIds = {};

  @override
  void initState() {
    super.initState();
    _loadLeaveTypes();
  }

  @override
  void dispose() {
    _reasonController.dispose();
    super.dispose();
  }

  Future<void> _loadLeaveTypes() async {
    try {
      final response = await _api.get('/leave-types');
      final list = response.data is List ? response.data as List : <dynamic>[];
      final map = <String, int>{};
      for (final item in list) {
        if (item is Map<String, dynamic>) {
          final id = item['id'];
          final name = item['name']?.toString().toLowerCase();
          if (id is int && name != null && name.isNotEmpty) {
            map[name] = id;
          }
        }
      }
      if (mounted) {
        setState(() => _leaveTypeIds = map);
      }
    } catch (_) {
      if (mounted) {
        setState(() {
          _leaveTypeIds = {'annual': 1, 'sick': 2, 'maternity': 3};
        });
      }
    }
  }

  Future<void> _pickFile() async {
    final result = await FilePicker.platform.pickFiles(
      type: FileType.custom,
      allowedExtensions: ['pdf', 'jpg', 'jpeg', 'png'],
    );

    if (result != null && mounted) {
      setState(() => _pickedFile = result.files.first);
    }
  }

  Future<void> _selectDate(bool isStart) async {
    final picked = await showDatePicker(
      context: context,
      initialDate: isStart
          ? (_startDate ?? DateTime.now())
          : (_endDate ?? _startDate ?? DateTime.now()),
      firstDate: DateTime.now().subtract(const Duration(days: 30)),
      lastDate: DateTime.now().add(const Duration(days: 365)),
    );

    if (picked != null && mounted) {
      setState(() {
        if (isStart) {
          _startDate = picked;
          if (_endDate != null && _endDate!.isBefore(_startDate!)) {
            _endDate = null;
          }
        } else {
          _endDate = picked;
        }
      });
    }
  }

  Future<void> _submitRequest() async {
    if (!_formKey.currentState!.validate()) return;

    if (_startDate == null || _endDate == null) {
      _showMessage('Please select both start and end dates.', error: true);
      return;
    }

    if (_leaveType == 'Sick Leave' && _pickedFile == null) {
      final diff = _endDate!.difference(_startDate!).inDays + 1;
      if (diff > 2) {
        _showMessage(
          'Medical certificate is required for sick leave exceeding 2 days under UAE Law.',
          error: true,
        );
        return;
      }
    }

    setState(() => _isSubmitting = true);

    try {
      final typeMap = <String, String>{
        'Sick Leave': 'sick',
        'Annual Leave': 'annual',
        'Unpaid Leave': 'unpaid',
        'Maternity Leave': 'maternity',
      };
      final selectedType = typeMap[_leaveType] ?? 'annual';
      final leaveTypeId = _leaveTypeIds[selectedType];

      if (leaveTypeId == null) {
        throw DioException(
          requestOptions: RequestOptions(path: '/leave-requests'),
          response: Response(
            requestOptions: RequestOptions(path: '/leave-requests'),
            statusCode: 422,
            data: {'message': 'Leave type is not configured in database.'},
          ),
        );
      }

      final payload = <String, dynamic>{
        'leave_type_id': leaveTypeId,
        'start_date': _startDate!.toIso8601String().split('T')[0],
        'end_date': _endDate!.toIso8601String().split('T')[0],
        'reason': _reasonController.text.trim(),
      };

      if (_pickedFile?.path != null && _pickedFile!.path!.trim().isNotEmpty) {
        payload['attachment'] = await MultipartFile.fromFile(
          _pickedFile!.path!,
          filename: _pickedFile!.name,
        );
      }

      await _api.post('/leave-requests', data: FormData.fromMap(payload));

      if (!mounted) return;
      Navigator.pop(context);
      _showMessage('Leave request submitted successfully.');
      widget.onSuccess();
    } on DioException catch (e) {
      String msg = 'Failed to submit request.';
      final data = e.response?.data;
      if (data is Map<String, dynamic>) {
        msg =
            data['message']?.toString() ??
            data['error']?.toString() ??
            data['errors']?.toString() ??
            msg;
      }
      if (mounted) _showMessage(msg, error: true);
    } catch (_) {
      if (mounted) {
        _showMessage('An unexpected error occurred.', error: true);
      }
    } finally {
      if (mounted) {
        setState(() => _isSubmitting = false);
      }
    }
  }

  void _showMessage(String message, {bool error = false}) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: error ? AppColors.danger : AppColors.success,
      ),
    );
  }

  InputDecoration _fieldDecoration({String? hintText}) {
    return InputDecoration(
      hintText: hintText,
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(10),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(10),
        borderSide: const BorderSide(color: AppColors.border),
      ),
      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(context).viewInsets.bottom,
        left: AppSpacing.md,
        right: AppSpacing.md,
        top: AppSpacing.md,
      ),
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.only(
          topLeft: Radius.circular(20),
          topRight: Radius.circular(20),
        ),
      ),
      child: SafeArea(
        child: SingleChildScrollView(
          child: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      'Request Leave',
                      style: Theme.of(context).textTheme.titleLarge,
                    ),
                    IconButton(
                      icon: const Icon(Icons.close),
                      onPressed: _isSubmitting ? null : () => Navigator.pop(context),
                    ),
                  ],
                ),
                const SizedBox(height: AppSpacing.sm),

                Text('Leave Type', style: Theme.of(context).textTheme.bodyLarge),
                const SizedBox(height: AppSpacing.xs),
                DropdownButtonFormField<String>(
                  initialValue: _leaveType,
                  decoration: _fieldDecoration(),
                  items: const [
                    'Sick Leave',
                    'Annual Leave',
                    'Unpaid Leave',
                    'Maternity Leave',
                  ]
                      .map(
                        (label) => DropdownMenuItem(
                          value: label,
                          child: Text(label),
                        ),
                      )
                      .toList(),
                  onChanged: _isSubmitting
                      ? null
                      : (value) {
                          if (value == null) return;
                          setState(() => _leaveType = value);
                        },
                ),
                const SizedBox(height: AppSpacing.sm),

                if (_leaveType == 'Sick Leave')
                  AppCard(
                    borderColor: AppColors.info.withValues(alpha: 0.3),
                    child: const Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Icon(Icons.info_outline, color: AppColors.info, size: 18),
                        SizedBox(width: AppSpacing.xs),
                        Expanded(
                          child: Text(
                            'UAE Law: Medical certificate is required for sick leave over 2 days.',
                            style: TextStyle(
                              fontSize: 12,
                              color: AppColors.textSecondary,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),

                const SizedBox(height: AppSpacing.sm),
                Row(
                  children: [
                    Expanded(
                      child: _buildDateField(
                        label: 'Start Date',
                        value: _startDate,
                        onTap: () => _selectDate(true),
                      ),
                    ),
                    const SizedBox(width: AppSpacing.sm),
                    Expanded(
                      child: _buildDateField(
                        label: 'End Date',
                        value: _endDate,
                        onTap: () => _selectDate(false),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: AppSpacing.sm),

                Text('Reason', style: Theme.of(context).textTheme.bodyLarge),
                const SizedBox(height: AppSpacing.xs),
                TextFormField(
                  controller: _reasonController,
                  maxLines: 3,
                  enabled: !_isSubmitting,
                  decoration: _fieldDecoration(
                    hintText: 'Provide details for your leave request...',
                  ),
                  validator: (value) {
                    if (value == null || value.trim().isEmpty) {
                      return 'Reason is required';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: AppSpacing.sm),

                Text(
                  'Attachment (Medical Certificate)',
                  style: Theme.of(context).textTheme.bodyLarge,
                ),
                const SizedBox(height: AppSpacing.xs),
                InkWell(
                  onTap: _isSubmitting ? null : _pickFile,
                  borderRadius: BorderRadius.circular(10),
                  child: Container(
                    padding: const EdgeInsets.symmetric(
                      vertical: 12,
                      horizontal: 12,
                    ),
                    decoration: BoxDecoration(
                      color: AppColors.background,
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: AppColors.border),
                    ),
                    child: Row(
                      children: [
                        const Icon(Icons.upload_file, color: AppColors.textSecondary),
                        const SizedBox(width: AppSpacing.xs),
                        Expanded(
                          child: Text(
                            _pickedFile?.name ?? 'Upload document (PDF/Image)',
                            overflow: TextOverflow.ellipsis,
                            style: Theme.of(context).textTheme.bodyMedium,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: AppSpacing.md),

                SizedBox(
                  width: double.infinity,
                  child: AppButton(
                    label: _isSubmitting ? 'Submitting...' : 'Submit Request',
                    onPressed: _isSubmitting ? null : _submitRequest,
                    leadingIcon: Icons.send,
                  ),
                ),
                if (_isSubmitting) ...[
                  const SizedBox(height: AppSpacing.sm),
                  const LinearProgressIndicator(minHeight: 3),
                ],
                const SizedBox(height: AppSpacing.sm),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildDateField({
    required String label,
    required DateTime? value,
    required VoidCallback onTap,
  }) {
    final text = value == null ? 'Select date' : value.toString().split(' ')[0];

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: Theme.of(context).textTheme.bodyLarge),
        const SizedBox(height: AppSpacing.xs),
        InkWell(
          onTap: _isSubmitting ? null : onTap,
          borderRadius: BorderRadius.circular(10),
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
            decoration: BoxDecoration(
              border: Border.all(color: AppColors.border),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(text, style: Theme.of(context).textTheme.bodyMedium),
                const Icon(
                  Icons.calendar_today,
                  size: 16,
                  color: AppColors.textSecondary,
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }
}

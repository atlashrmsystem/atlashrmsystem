import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../config/api_client.dart';
import '../providers/auth_provider.dart';
import '../theme/app_colors.dart';
import '../theme/app_spacing.dart';
import '../widgets/app_card.dart';
import '../widgets/state_widgets.dart';

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  final _api = ApiClient().dio;

  bool _isLoading = true;
  String? _errorMessage;
  Map<String, dynamic>? _employeeData;

  @override
  void initState() {
    super.initState();
    _fetchProfile();
  }

  Future<void> _fetchProfile() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final profileResponse = await _api.get('/employees/me');
      final employee = _extractMap(profileResponse.data);

      if (!mounted) return;
      setState(() {
        _employeeData = employee;
        _isLoading = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _isLoading = false;
        _errorMessage = 'Failed to load profile data.';
      });
    }
  }

  Map<String, dynamic>? _extractMap(dynamic payload) {
    if (payload is Map<String, dynamic>) {
      final data = payload['data'];
      if (data is Map<String, dynamic>) return data;
      return payload;
    }
    return null;
  }

  String _str(dynamic value, {String fallback = 'Not provided'}) {
    final text = value?.toString().trim() ?? '';
    return text.isEmpty ? fallback : text;
  }

  bool _hasValue(dynamic value) {
    final text = value?.toString().trim() ?? '';
    return text.isNotEmpty;
  }

  String _toTitleCase(String text) {
    if (text.isEmpty) return text;
    final normalized = text.replaceAll('_', ' ').replaceAll('-', ' ');
    return normalized
        .split(' ')
        .where((part) => part.isNotEmpty)
        .map((part) => part[0].toUpperCase() + part.substring(1).toLowerCase())
        .join(' ');
  }

  String _assignedStoreRaw() {
    final assigned = _employeeData?['assigned_store'];
    if (assigned is Map<String, dynamic>) {
      return (assigned['name']?.toString() ?? '').trim();
    }
    return '';
  }

  String _assignedBrandRaw() {
    final assigned = _employeeData?['assigned_store'];
    if (assigned is Map<String, dynamic>) {
      return (assigned['brand_name']?.toString() ?? '').trim();
    }
    return '';
  }

  String _assignedAreaRaw() {
    final assigned = _employeeData?['assigned_store'];
    if (assigned is Map<String, dynamic>) {
      return (assigned['brand_area_name']?.toString() ?? '').trim();
    }
    return '';
  }

  int _completenessPercent(AuthProvider auth) {
    if (_employeeData == null) return 0;

    final checks = <bool>[
      _hasValue(_employeeData!['full_name']),
      _hasValue(_employeeData!['email']),
      _hasValue(_employeeData!['phone']),
      _hasValue(_employeeData!['nationality']),
      _hasValue(_employeeData!['emirates_id']),
      auth.roles.isNotEmpty,
    ];
    if (!auth.isManager) {
      checks.addAll([
        _hasValue(_assignedBrandRaw()),
        _hasValue(_assignedAreaRaw()),
        _hasValue(_assignedStoreRaw()),
      ]);
    }

    final completed = checks.where((v) => v).length;
    return ((completed / checks.length) * 100).round();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('My Profile'),
      ),
      body: SingleChildScrollView(
        padding: AppSpacing.pagePadding,
        child: Consumer<AuthProvider>(
          builder: (context, auth, _) {
            if (_isLoading) {
              return const AppLoadingState(message: 'Loading profile...');
            }

            if (_errorMessage != null) {
              return AppErrorState(
                message: _errorMessage!,
                onRetry: _fetchProfile,
              );
            }

            if (_employeeData == null) {
              return const AppEmptyState(
                title: 'Profile data not found.',
                subtitle: 'Please refresh to try again.',
                icon: Icons.person_off_outlined,
              );
            }

            final roleText = auth.roles.isEmpty
                ? 'Staff'
                : auth.roles.map((r) => _toTitleCase(r)).join(', ');
            final completeness = _completenessPercent(auth);
            final showOrganizationDetails = !auth.isManager;

            return Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                _buildHeaderCard(roleText),
                const SizedBox(height: AppSpacing.sm),
                _buildCompletenessCard(completeness),
                const SizedBox(height: AppSpacing.md),
                Text(
                  'Personal Details',
                  style: Theme.of(context).textTheme.titleMedium,
                ),
                const SizedBox(height: AppSpacing.sm),
                AppCard(
                  padding: EdgeInsets.zero,
                  child: Column(
                    children: [
                      _buildListTile('Assigned Role(s)', roleText, Icons.account_tree),
                      _buildDivider(),
                      _buildListTile('Email', _str(_employeeData!['email']), Icons.email),
                      _buildDivider(),
                      _buildListTile('Phone', _str(_employeeData!['phone']), Icons.phone),
                      _buildDivider(),
                      _buildListTile('Nationality', _str(_employeeData!['nationality']), Icons.flag),
                      _buildDivider(),
                      _buildListTile('Emirates ID', _str(_employeeData!['emirates_id']), Icons.badge),
                    ],
                  ),
                ),
                if (showOrganizationDetails) ...[
                  const SizedBox(height: AppSpacing.md),
                  Text(
                    'Organization Details',
                    style: Theme.of(context).textTheme.titleMedium,
                  ),
                  const SizedBox(height: AppSpacing.sm),
                  AppCard(
                    padding: EdgeInsets.zero,
                    child: Column(
                      children: [
                        _buildListTile(
                          'Assigned Brand',
                          _str(_assignedBrandRaw(), fallback: 'No Brand'),
                          Icons.storefront,
                        ),
                        _buildDivider(),
                        _buildListTile(
                          'Assigned Area',
                          _str(_assignedAreaRaw(), fallback: 'No Area'),
                          Icons.map_outlined,
                        ),
                        _buildDivider(),
                        _buildListTile(
                          'Assigned Store',
                          _str(_assignedStoreRaw(), fallback: 'No Store'),
                          Icons.location_on_outlined,
                        ),
                      ],
                    ),
                  ),
                ],
              ],
            );
          },
        ),
      ),
    );
  }

  Widget _buildHeaderCard(String roleText) {
    final fullName = _employeeData!['full_name']?.toString() ?? 'N/A';
    final status = _employeeData!['status']?.toString() ?? 'Active';
    final photoUrl = _employeeData!['photo_url']?.toString();

    return AppCard(
      child: Row(
        children: [
          CircleAvatar(
            radius: 34,
            backgroundColor: AppColors.brand,
            backgroundImage: (photoUrl != null && photoUrl.isNotEmpty)
                ? NetworkImage(photoUrl)
                : null,
            child: (photoUrl == null || photoUrl.isEmpty)
                ? Text(
                    fullName.isNotEmpty ? fullName[0].toUpperCase() : 'U',
                    style: const TextStyle(fontSize: 28, color: Colors.white),
                  )
                : null,
          ),
          const SizedBox(width: AppSpacing.md),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  fullName,
                  style: Theme.of(context).textTheme.titleLarge,
                ),
                const SizedBox(height: 2),
                Text(roleText, style: Theme.of(context).textTheme.bodyMedium),
                const SizedBox(height: AppSpacing.xs),
                Row(
                  children: [
                    Container(
                      width: 8,
                      height: 8,
                      decoration: BoxDecoration(
                        color: status.toLowerCase() == 'active'
                            ? AppColors.success
                            : AppColors.textSecondary,
                        shape: BoxShape.circle,
                      ),
                    ),
                    const SizedBox(width: 6),
                    Text(status, style: Theme.of(context).textTheme.bodyMedium),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildCompletenessCard(int completeness) {
    final double progress = (completeness.clamp(0, 100)) / 100;

    return AppCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                'Profile Completeness',
                style: Theme.of(context).textTheme.bodyLarge,
              ),
              Text(
                '$completeness%',
                style: Theme.of(context)
                    .textTheme
                    .bodyLarge
                    ?.copyWith(color: AppColors.brand),
              ),
            ],
          ),
          const SizedBox(height: AppSpacing.xs),
          ClipRRect(
            borderRadius: BorderRadius.circular(999),
            child: LinearProgressIndicator(
              value: progress,
              minHeight: 8,
              backgroundColor: AppColors.border,
              valueColor: const AlwaysStoppedAnimation<Color>(AppColors.brandAccent),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildListTile(String title, String subtitle, IconData icon) {
    return ListTile(
      leading: Icon(icon, color: AppColors.textSecondary),
      title: Text(
        title,
        style: const TextStyle(
          fontSize: 12,
          color: AppColors.textSecondary,
          fontWeight: FontWeight.w600,
        ),
      ),
      subtitle: Text(
        subtitle,
        style: const TextStyle(fontSize: 16, color: AppColors.textPrimary),
      ),
    );
  }

  Widget _buildDivider() {
    return const Divider(height: 1, color: AppColors.border);
  }
}

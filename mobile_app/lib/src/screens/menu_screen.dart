import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../providers/auth_provider.dart';
import '../theme/app_colors.dart';
import '../theme/app_spacing.dart';
import '../widgets/app_card.dart';
import '../widgets/state_widgets.dart';

class MenuScreen extends StatefulWidget {
  const MenuScreen({super.key});

  @override
  State<MenuScreen> createState() => _MenuScreenState();
}

class _MenuScreenState extends State<MenuScreen> {
  final TextEditingController _searchController = TextEditingController();
  String _searchQuery = '';

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Menu')),
      body: Consumer<AuthProvider>(
        builder: (context, auth, _) {
          final groups = _buildMenuGroups(auth);
          final filtered = _applySearch(groups, _searchQuery);

          return ListView(
            padding: AppSpacing.pagePadding,
            children: [
              _buildSearchField(),
              const SizedBox(height: AppSpacing.md),
              if (filtered.isEmpty)
                const AppEmptyState(
                  title: 'No modules found',
                  subtitle: 'Try another keyword.',
                  icon: Icons.search_off,
                )
              else
                ...filtered.map(
                  (group) => Padding(
                    padding: const EdgeInsets.only(bottom: AppSpacing.md),
                    child: _buildGroupCard(group),
                  ),
                ),
            ],
          );
        },
      ),
    );
  }

  Widget _buildSearchField() {
    return TextField(
      controller: _searchController,
      onChanged: (value) => setState(() => _searchQuery = value.trim()),
      decoration: InputDecoration(
        hintText: 'Search modules...',
        prefixIcon: const Icon(Icons.search),
        suffixIcon: _searchQuery.isEmpty
            ? null
            : IconButton(
                icon: const Icon(Icons.close),
                onPressed: () {
                  _searchController.clear();
                  setState(() => _searchQuery = '');
                },
              ),
        filled: true,
        fillColor: Colors.white,
        contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.border),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.border),
        ),
      ),
    );
  }

  Widget _buildGroupCard(_MenuGroup group) {
    return AppCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(group.title, style: Theme.of(context).textTheme.titleMedium),
          const SizedBox(height: AppSpacing.sm),
          Wrap(
            spacing: AppSpacing.sm,
            runSpacing: AppSpacing.sm,
            children: group.modules
                .map(
                  (module) => _buildModuleChip(module),
                )
                .toList(),
          ),
        ],
      ),
    );
  }

  Widget _buildModuleChip(_MenuModule module) {
    return InkWell(
      onTap: () => context.push(module.route),
      borderRadius: BorderRadius.circular(12),
      child: Container(
        constraints: const BoxConstraints(minWidth: 150),
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
        decoration: BoxDecoration(
          border: Border.all(color: AppColors.border),
          borderRadius: BorderRadius.circular(12),
          color: module.color.withValues(alpha: 0.08),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(module.icon, color: module.color, size: 18),
            const SizedBox(width: 8),
            Flexible(
              child: Text(
                module.label,
                style: const TextStyle(
                  color: AppColors.textPrimary,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  List<_MenuGroup> _applySearch(List<_MenuGroup> groups, String query) {
    if (query.isEmpty) return groups;

    final normalized = query.toLowerCase();
    final result = <_MenuGroup>[];

    for (final group in groups) {
      final matches = group.modules
          .where((module) =>
              module.label.toLowerCase().contains(normalized) ||
              group.title.toLowerCase().contains(normalized))
          .toList();
      if (matches.isNotEmpty) {
        result.add(_MenuGroup(group.title, matches));
      }
    }

    return result;
  }

  List<_MenuGroup> _buildMenuGroups(AuthProvider auth) {
    final productivity = <_MenuModule>[
      if (!auth.isManager)
        _MenuModule(
          label: 'Attendance',
          route: '/attendance',
          icon: Icons.access_time,
          color: AppColors.success,
        ),
      _MenuModule(
        label: 'My Leaves',
        route: '/leaves',
        icon: Icons.calendar_today,
        color: AppColors.warning,
      ),
      _MenuModule(
        label: 'Payslips',
        route: '/payslips',
        icon: Icons.receipt_long,
        color: Colors.deepPurple,
      ),
      _MenuModule(
        label: 'My Profile',
        route: '/profile',
        icon: Icons.person_outline,
        color: AppColors.info,
      ),
      if (auth.isStaff || auth.isSupervisor || auth.isSalesTeam)
        _MenuModule(
          label: 'My Schedule',
          route: '/my-schedule',
          icon: Icons.calendar_month,
          color: Colors.teal,
        ),
    ];

    final management = <_MenuModule>[
      if (auth.canApproveTeamActions)
        _MenuModule(
          label: 'Leave Queue',
          route: '/team-leave-queue',
          icon: Icons.approval,
          color: Colors.deepOrange,
        ),
      if (auth.isManager || auth.isAdmin)
        _MenuModule(
          label: 'Team Attendance',
          route: '/team-attendance',
          icon: Icons.query_stats,
          color: Colors.cyan,
        ),
      if (auth.isSupervisor)
        _MenuModule(
          label: 'Schedule Management',
          route: '/schedule-management',
          icon: Icons.event_note,
          color: Colors.teal,
        ),
      if (auth.isSupervisor || auth.isManager || auth.isAdmin)
        _MenuModule(
          label: 'Staff List',
          route: '/staff-list',
          icon: Icons.groups_2_outlined,
          color: Colors.brown,
        ),
    ];

    final business = <_MenuModule>[
      if (auth.isSupervisor || auth.isManager || auth.isSalesTeam)
        _MenuModule(
          label: 'Sales',
          route: '/sales',
          icon: Icons.point_of_sale,
          color: Colors.indigo,
        ),
    ];

    final groups = <_MenuGroup>[];
    if (productivity.isNotEmpty) {
      groups.add(_MenuGroup('Productivity', productivity));
    }
    if (management.isNotEmpty) {
      groups.add(_MenuGroup('Management', management));
    }
    if (business.isNotEmpty) {
      groups.add(_MenuGroup('Business', business));
    }
    return groups;
  }
}

class _MenuGroup {
  final String title;
  final List<_MenuModule> modules;

  _MenuGroup(this.title, this.modules);
}

class _MenuModule {
  final String label;
  final String route;
  final IconData icon;
  final Color color;

  _MenuModule({
    required this.label,
    required this.route,
    required this.icon,
    required this.color,
  });
}

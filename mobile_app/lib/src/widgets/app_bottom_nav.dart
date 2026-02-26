import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../providers/auth_provider.dart';

class AppBottomNav extends StatelessWidget {
  final String currentRoute;

  const AppBottomNav({
    super.key,
    required this.currentRoute,
  });

  int _currentIndex({required bool hideAttendance}) {
    if (currentRoute == '/') {
      return 0;
    }

    if (!hideAttendance && currentRoute == '/attendance') {
      return 1;
    }

    if (currentRoute == '/profile') {
      return hideAttendance ? 1 : 2;
    }

    if (hideAttendance) {
      return 2;
    }

    return 3;
  }

  void _onTap(BuildContext context, int index, {required bool hideAttendance}) {
    final routes = hideAttendance
        ? ['/', '/profile', '/menu']
        : ['/', '/attendance', '/profile', '/menu'];
    final target = routes[index];

    if (target == currentRoute) return;
    context.go(target);
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    final hideAttendance = auth.isManager;

    return NavigationBar(
      selectedIndex: _currentIndex(hideAttendance: hideAttendance),
      onDestinationSelected: (index) =>
          _onTap(context, index, hideAttendance: hideAttendance),
      destinations: [
        const NavigationDestination(
          icon: Icon(Icons.home_outlined),
          selectedIcon: Icon(Icons.home),
          label: 'Home',
        ),
        if (!hideAttendance)
          const NavigationDestination(
            icon: Icon(Icons.access_time_outlined),
            selectedIcon: Icon(Icons.access_time_filled),
            label: 'Attendance',
          ),
        const NavigationDestination(
          icon: Icon(Icons.person_outline),
          selectedIcon: Icon(Icons.person),
          label: 'Profile',
        ),
        const NavigationDestination(
          icon: Icon(Icons.grid_view_outlined),
          selectedIcon: Icon(Icons.grid_view_rounded),
          label: 'Menu',
        ),
      ],
    );
  }
}

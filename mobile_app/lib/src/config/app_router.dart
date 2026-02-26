import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../providers/auth_provider.dart';
import '../widgets/app_bottom_nav.dart';
import '../screens/login_screen.dart';
import '../screens/dashboard_screen.dart';
import '../screens/profile_screen.dart';
import '../screens/leaves_screen.dart';
import '../screens/attendance_screen.dart';
import '../screens/team_leave_queue_screen.dart';
import '../screens/schedule_management_screen.dart';
import '../screens/sales_screen.dart';
import '../screens/staff_list_screen.dart';
import '../screens/payslips_screen.dart';
import '../screens/team_attendance_screen.dart';
import '../screens/my_schedule_screen.dart';
import '../screens/menu_screen.dart';

class AppRouter {
  final AuthProvider authProvider;

  AppRouter(this.authProvider);

  late final GoRouter router = GoRouter(
    refreshListenable: authProvider,
    initialLocation: '/',
    redirect: (BuildContext context, GoRouterState state) {
      final bool isAuthenticated = authProvider.isAuthenticated;
      final bool isGoingToLogin = state.matchedLocation == '/login';
      final location = state.matchedLocation;

      if (!isAuthenticated && !isGoingToLogin) {
        return '/login';
      }

      if (isAuthenticated && isGoingToLogin) {
        return '/';
      }

      if (isAuthenticated && !_isRouteAllowed(location)) {
        return '/';
      }

      return null;
    },
    routes: <RouteBase>[
      GoRoute(
        path: '/login',
        builder: (BuildContext context, GoRouterState state) =>
            const LoginScreen(),
      ),
      ShellRoute(
        builder: (BuildContext context, GoRouterState state, Widget child) {
          if (authProvider.isLoading) {
            return const Scaffold(
              body: Center(child: CircularProgressIndicator()),
            );
          }

          return Scaffold(
            body: child,
            bottomNavigationBar: AppBottomNav(currentRoute: state.matchedLocation),
          );
        },
        routes: <GoRoute>[
          GoRoute(
            path: '/',
            builder: (BuildContext context, GoRouterState state) =>
                const DashboardScreen(),
          ),
          GoRoute(
            path: '/profile',
            builder: (BuildContext context, GoRouterState state) =>
                const ProfileScreen(),
          ),
          GoRoute(
            path: '/leaves',
            builder: (BuildContext context, GoRouterState state) =>
                const LeavesScreen(),
          ),
          GoRoute(
            path: '/attendance',
            builder: (BuildContext context, GoRouterState state) =>
                const AttendanceScreen(),
          ),
          GoRoute(
            path: '/team-leave-queue',
            builder: (BuildContext context, GoRouterState state) =>
                const TeamLeaveQueueScreen(),
          ),
          GoRoute(
            path: '/schedule-management',
            builder: (BuildContext context, GoRouterState state) =>
                const ScheduleManagementScreen(),
          ),
          GoRoute(
            path: '/sales',
            builder: (BuildContext context, GoRouterState state) =>
                const SalesScreen(),
          ),
          GoRoute(
            path: '/staff-list',
            builder: (BuildContext context, GoRouterState state) =>
                const StaffListScreen(),
          ),
          GoRoute(
            path: '/payslips',
            builder: (BuildContext context, GoRouterState state) =>
                const PayslipsScreen(),
          ),
          GoRoute(
            path: '/team-attendance',
            builder: (BuildContext context, GoRouterState state) =>
                const TeamAttendanceScreen(),
          ),
          GoRoute(
            path: '/my-schedule',
            builder: (BuildContext context, GoRouterState state) =>
                const MyScheduleScreen(),
          ),
          GoRoute(
            path: '/menu',
            builder: (BuildContext context, GoRouterState state) =>
                const MenuScreen(),
          ),
        ],
      ),
    ],
  );

  bool _isRouteAllowed(String location) {
    if (location == '/' || location == '/login') return true;

    switch (location) {
      case '/team-leave-queue':
        return authProvider.canApproveTeamActions;
      case '/schedule-management':
        return authProvider.isSupervisor;
      case '/team-attendance':
        return authProvider.isManager || authProvider.isAdmin;
      case '/sales':
        return authProvider.isSupervisor ||
            authProvider.isManager ||
            authProvider.isSalesTeam;
      case '/staff-list':
        return authProvider.isSupervisor ||
            authProvider.isManager ||
            authProvider.isAdmin;
      case '/attendance':
        return !authProvider.isManager;
      case '/my-schedule':
        return authProvider.isStaff ||
            authProvider.isSupervisor ||
            authProvider.isSalesTeam;
      default:
        return true;
    }
  }
}

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'dart:async';
import 'src/providers/auth_provider.dart';
import 'src/config/app_router.dart';
import 'src/theme/app_theme.dart';
import 'src/services/local_notification_service.dart';
import 'src/services/offline_sync_service.dart';

void main() {
  runApp(
    MultiProvider(
      providers: [ChangeNotifierProvider(create: (_) => AuthProvider())],
      child: const AtlasMobileApp(),
    ),
  );
}

class AtlasMobileApp extends StatefulWidget {
  const AtlasMobileApp({super.key});

  @override
  State<AtlasMobileApp> createState() => _AtlasMobileAppState();
}

class _AtlasMobileAppState extends State<AtlasMobileApp> {
  Timer? _syncTimer;

  @override
  void initState() {
    super.initState();
    unawaited(LocalNotificationService().initialize());
    _startSyncLoop();
  }

  void _startSyncLoop() {
    // Best-effort background sync loop. If offline, events remain queued.
    OfflineSyncService().flushPending();
    _syncTimer = Timer.periodic(const Duration(seconds: 30), (_) {
      OfflineSyncService().flushPending();
    });
  }

  @override
  void dispose() {
    _syncTimer?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    final appRouter = AppRouter(authProvider).router;

    return MaterialApp.router(
      title: 'ATLAS ESS',
      theme: AppTheme.light,
      routerConfig: appRouter,
      debugShowCheckedModeBanner: false,
    );
  }
}

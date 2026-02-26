import 'package:flutter_local_notifications/flutter_local_notifications.dart';

class LocalNotificationService {
  LocalNotificationService._();

  static final LocalNotificationService _instance =
      LocalNotificationService._();

  factory LocalNotificationService() => _instance;

  static const int clockOutReminderNotificationId = 9001;
  static const String _channelId = 'attendance_reminders';

  final FlutterLocalNotificationsPlugin _plugin =
      FlutterLocalNotificationsPlugin();
  bool _initialized = false;
  bool _enabled = true;

  Future<void> initialize() async {
    if (_initialized) return;

    const androidSettings = AndroidInitializationSettings(
      '@mipmap/ic_launcher',
    );
    const iosSettings = DarwinInitializationSettings(
      requestAlertPermission: true,
      requestBadgePermission: true,
      requestSoundPermission: true,
    );

    try {
      await _plugin.initialize(
        settings: const InitializationSettings(
          android: androidSettings,
          iOS: iosSettings,
        ),
      );

      await _plugin
          .resolvePlatformSpecificImplementation<
            IOSFlutterLocalNotificationsPlugin
          >()
          ?.requestPermissions(alert: true, badge: true, sound: true);

      await _plugin
          .resolvePlatformSpecificImplementation<
            MacOSFlutterLocalNotificationsPlugin
          >()
          ?.requestPermissions(alert: true, badge: true, sound: true);
    } catch (_) {
      _enabled = false;
    }

    _initialized = true;
  }

  Future<void> showClockOutReminder() async {
    await initialize();
    if (!_enabled) return;

    await _plugin.show(
      id: clockOutReminderNotificationId,
      title: 'Clock-out reminder',
      body:
          'You have completed 9 hours. Please clock out if your shift is complete.',
      notificationDetails: const NotificationDetails(
        android: AndroidNotificationDetails(
          _channelId,
          'Attendance Reminders',
          channelDescription: 'Clock-out reminders after 9 working hours.',
          importance: Importance.max,
          priority: Priority.high,
        ),
        iOS: DarwinNotificationDetails(
          presentAlert: true,
          presentBadge: true,
          presentSound: true,
        ),
      ),
      payload: 'clock_out_reminder',
    );
  }

  Future<void> clearClockOutReminder() async {
    await initialize();
    if (!_enabled) return;
    await _plugin.cancel(id: clockOutReminderNotificationId);
  }
}

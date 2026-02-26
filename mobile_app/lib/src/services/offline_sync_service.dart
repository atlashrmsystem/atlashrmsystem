import 'dart:async';
import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import '../config/api_client.dart';

class OfflineSyncService {
  OfflineSyncService._();
  static final OfflineSyncService _instance = OfflineSyncService._();
  factory OfflineSyncService() => _instance;

  static const String _queueKey = 'offline_sync_queue_v1';

  final FlutterSecureStorage _storage = const FlutterSecureStorage();
  final Dio _api = ApiClient().dio;

  bool _isFlushing = false;

  Future<void> queueAttendanceEvent({
    required bool isClockOut,
    Map<String, dynamic>? location,
    DateTime? localTime,
  }) async {
    final event = <String, dynamic>{
      'id': _eventId('att'),
      'type': isClockOut ? 'attendance_clock_out' : 'attendance_clock_in',
      'payload': {
        if (location != null) 'location': location,
      },
      'local_time': (localTime ?? DateTime.now()).toIso8601String(),
      'created_at': DateTime.now().toIso8601String(),
    };

    await _appendEvent(event);
  }

  Future<void> queueSalesEvent({
    required int storeId,
    required String date,
    required double amount,
    DateTime? localTime,
  }) async {
    final event = <String, dynamic>{
      'id': _eventId('sale'),
      'type': 'sales_submit',
      'payload': {
        'store_id': storeId,
        'date': date,
        'amount': amount,
      },
      'local_time': (localTime ?? DateTime.now()).toIso8601String(),
      'created_at': DateTime.now().toIso8601String(),
    };

    await _appendEvent(event);
  }

  Future<int> pendingCount() async {
    final queue = await _readQueue();
    return queue.length;
  }

  Future<SyncResult> flushPending() async {
    if (_isFlushing) {
      return const SyncResult(processed: 0, succeeded: 0, failed: 0);
    }

    _isFlushing = true;
    try {
      final queue = await _readQueue();
      if (queue.isEmpty) {
        return const SyncResult(processed: 0, succeeded: 0, failed: 0);
      }

      final retained = <Map<String, dynamic>>[];
      int succeeded = 0;
      int failed = 0;

      for (final event in queue) {
        try {
          await _dispatchEvent(event);
          succeeded++;
        } on DioException catch (e) {
          if (_isConnectivityError(e)) {
            retained.add(event);
          } else {
            retained.add({...event, 'last_error': _extractErrorMessage(e), 'last_failed_at': DateTime.now().toIso8601String()});
          }
          failed++;
        } catch (e) {
          retained.add({...event, 'last_error': e.toString(), 'last_failed_at': DateTime.now().toIso8601String()});
          failed++;
        }
      }

      await _writeQueue(retained);

      return SyncResult(
        processed: queue.length,
        succeeded: succeeded,
        failed: failed,
      );
    } finally {
      _isFlushing = false;
    }
  }

  Future<void> _dispatchEvent(Map<String, dynamic> event) async {
    final type = event['type']?.toString() ?? '';
    final payload = (event['payload'] is Map)
        ? Map<String, dynamic>.from(event['payload'] as Map)
        : <String, dynamic>{};

    switch (type) {
      case 'attendance_clock_in':
        await _api.post('/attendance/clock-in', data: payload);
        return;
      case 'attendance_clock_out':
        await _api.post('/attendance/clock-out', data: payload);
        return;
      case 'sales_submit':
        await _api.post('/sales', data: payload);
        return;
      default:
        throw StateError('Unknown event type: $type');
    }
  }

  Future<void> _appendEvent(Map<String, dynamic> event) async {
    final queue = await _readQueue();
    queue.add(event);
    await _writeQueue(queue);
  }

  Future<List<Map<String, dynamic>>> _readQueue() async {
    final raw = await _storage.read(key: _queueKey);
    if (raw == null || raw.trim().isEmpty) return [];

    try {
      final decoded = jsonDecode(raw);
      if (decoded is! List) return [];
      return decoded
          .whereType<Map>()
          .map((e) => Map<String, dynamic>.from(e))
          .toList();
    } catch (_) {
      return [];
    }
  }

  Future<void> _writeQueue(List<Map<String, dynamic>> queue) async {
    await _storage.write(key: _queueKey, value: jsonEncode(queue));
  }

  bool _isConnectivityError(DioException e) {
    return e.type == DioExceptionType.connectionError ||
        e.type == DioExceptionType.connectionTimeout ||
        e.type == DioExceptionType.receiveTimeout ||
        e.type == DioExceptionType.sendTimeout;
  }

  String _extractErrorMessage(DioException e) {
    final data = e.response?.data;
    if (data is Map<String, dynamic>) {
      return data['message']?.toString() ?? 'Request failed';
    }
    return e.message ?? 'Request failed';
  }

  String _eventId(String prefix) {
    return '$prefix-${DateTime.now().microsecondsSinceEpoch}-${UniqueKey().hashCode}';
  }
}

class SyncResult {
  final int processed;
  final int succeeded;
  final int failed;

  const SyncResult({
    required this.processed,
    required this.succeeded,
    required this.failed,
  });
}

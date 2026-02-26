import 'dart:async';
import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'dart:io';
import 'package:flutter/foundation.dart';

class ApiClient {
  static final ApiClient _instance = ApiClient._internal();
  static const String _baseUrlStorageKey = 'api_base_url_override';
  late Dio dio;
  final FlutterSecureStorage _storage = const FlutterSecureStorage();
  final StreamController<void> _unauthorizedEvents =
      StreamController<void>.broadcast();
  DateTime? _lastUnauthorizedEventAt;
  final String _configuredBaseUrl = const String.fromEnvironment(
    'API_BASE_URL',
  );

  factory ApiClient() {
    return _instance;
  }

  ApiClient._internal() {
    // Priority:
    // 1) --dart-define-from-file=env/local.json (recommended)
    // 2) --dart-define=API_BASE_URL=...
    // 3) Platform default (simulator/emulator only)
    String baseUrl;
    if (_configuredBaseUrl.isNotEmpty) {
      baseUrl = _configuredBaseUrl;
    } else if (!kIsWeb && Platform.isAndroid) {
      baseUrl = 'http://10.0.2.2:8000/api';
    } else if (!kIsWeb && Platform.isIOS) {
      // iOS simulator can use localhost.
      // Physical iPhone should set API URL once from Login > API Settings.
      baseUrl = 'http://127.0.0.1:8000/api';
    } else {
      baseUrl = 'http://127.0.0.1:8000/api';
    }

    dio = Dio(
      BaseOptions(
        baseUrl: baseUrl,
        receiveTimeout: const Duration(seconds: 15),
        connectTimeout: const Duration(seconds: 15),
        headers: {'Accept': 'application/json'},
      ),
    );

    dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          // If app is launched with --dart-define=API_BASE_URL, keep that as source of truth.
          // Otherwise, honor the user-defined API URL from app settings.
          final overrideBaseUrl = await _storage.read(key: _baseUrlStorageKey);
          if (_configuredBaseUrl.isEmpty &&
              overrideBaseUrl != null &&
              overrideBaseUrl.trim().isNotEmpty) {
            options.baseUrl = overrideBaseUrl.trim();
          }

          // Automatically inject Bearer token if it exists
          String? token = await _storage.read(key: 'auth_token');
          if (token != null) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          return handler.next(options);
        },
        onError: (DioException e, handler) async {
          if (e.response?.statusCode == 401) {
            // Token expired or invalid
            await _storage.delete(key: 'auth_token');
            final path = e.requestOptions.path;
            if (!path.endsWith('/login')) {
              _emitUnauthorizedEvent();
            }
          }
          return handler.next(e);
        },
      ),
    );
  }

  Stream<void> get unauthorizedEvents => _unauthorizedEvents.stream;

  void _emitUnauthorizedEvent() {
    final now = DateTime.now();
    if (_lastUnauthorizedEventAt != null &&
        now.difference(_lastUnauthorizedEventAt!).inMilliseconds < 500) {
      return;
    }
    _lastUnauthorizedEventAt = now;
    _unauthorizedEvents.add(null);
  }

  Future<String?> getBaseUrlOverride() async {
    final value = await _storage.read(key: _baseUrlStorageKey);
    if (value == null || value.trim().isEmpty) return null;
    return value.trim();
  }

  Future<void> setBaseUrlOverride(String baseUrl) async {
    final normalized = baseUrl.trim();
    await _storage.write(key: _baseUrlStorageKey, value: normalized);
    dio.options.baseUrl = normalized;
  }

  Future<void> clearBaseUrlOverride() async {
    await _storage.delete(key: _baseUrlStorageKey);
  }
}

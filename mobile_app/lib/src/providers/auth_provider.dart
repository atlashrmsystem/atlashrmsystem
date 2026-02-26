import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:dio/dio.dart';
import '../config/api_client.dart';

class AuthProvider with ChangeNotifier {
  final FlutterSecureStorage _storage = const FlutterSecureStorage();
  final ApiClient _apiClient = ApiClient();
  late final Dio _dio = _apiClient.dio;
  late final StreamSubscription<void> _unauthorizedSub;

  bool _isAuthenticated = false;
  bool _isLoading = true;
  String? _errorMessage;
  List<String> _roles = [];
  List<String> _permissions = [];
  Map<String, dynamic>? _user;
  bool _isHandlingUnauthorized = false;

  bool get isAuthenticated => _isAuthenticated;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;
  List<String> get roles => _roles;
  List<String> get permissions => _permissions;
  Map<String, dynamic>? get user => _user;
  String get apiBaseUrl => _dio.options.baseUrl;
  bool get isManager =>
      _roles.contains('manager') ||
      _permissions.contains('approve leave manager');
  bool get isSupervisor =>
      _roles.contains('supervisor') || _roles.contains('shift-supervisor');
  bool get isSalesTeam {
    if (_roles.contains('sales-team')) return true;
    // Fallback for inconsistent role payloads: sales access without leadership/admin role.
    return (_permissions.contains('view store reports') ||
            _permissions.contains('view store sales')) &&
        !isManager &&
        !isSupervisor &&
        !isAdmin;
  }
  bool get isAdmin =>
      _roles.contains('admin') || _roles.contains('super-admin');
  bool get isStaff => _roles.contains('staff') || _roles.contains('employee');
  bool get canApproveTeamActions => isManager || isSupervisor || isAdmin;
  String get roleLabel {
    if (isManager) return 'Manager';
    if (isSupervisor) return 'Supervisor';
    if (isSalesTeam) return 'Sales';
    if (isAdmin) return 'HR';
    if (isStaff) return 'Staff';
    return 'Staff';
  }

  AuthProvider() {
    _unauthorizedSub = _apiClient.unauthorizedEvents.listen((_) {
      _handleUnauthorizedSession();
    });
    _initializeApiBaseUrl();
    _checkAuthStatus();
  }

  Future<void> _handleUnauthorizedSession() async {
    if (_isHandlingUnauthorized) return;
    _isHandlingUnauthorized = true;
    try {
      await _storage.delete(key: 'auth_token');
      _isAuthenticated = false;
      _roles = [];
      _permissions = [];
      _user = null;
      _errorMessage = 'Session expired. Please log in again.';
      notifyListeners();
    } finally {
      _isHandlingUnauthorized = false;
    }
  }

  Future<void> _initializeApiBaseUrl() async {
    final override = await _apiClient.getBaseUrlOverride();
    if (override != null && override.isNotEmpty) {
      _dio.options.baseUrl = override;
      notifyListeners();
    }
  }

  String _normalizeKey(String value) {
    return value
        .toLowerCase()
        .trim()
        .replaceAll('_', '-')
        .replaceAll(' ', '-');
  }

  List<String> _extractRoles(Map<String, dynamic> user) {
    if (user['role_names'] is List) {
      return (user['role_names'] as List)
          .map((e) => _normalizeKey(e.toString()))
          .toList();
    }
    if (user['roles'] is List) {
      return (user['roles'] as List)
          .map((e) => _normalizeKey((e['name'] ?? '').toString()))
          .where((e) => e.isNotEmpty)
          .toList();
    }
    return [];
  }

  List<String> _extractPermissions(Map<String, dynamic> payload) {
    final names = <String>[];

    if (payload['permissions'] is List) {
      names.addAll(
        (payload['permissions'] as List).map((e) => e.toString().toLowerCase()),
      );
    }

    final userRaw = payload['user'];
    final user = userRaw is Map<String, dynamic>
        ? userRaw
        : (userRaw is Map ? Map<String, dynamic>.from(userRaw) : payload);

    if (user['permission_names'] is List) {
      names.addAll(
        (user['permission_names'] as List)
            .map((e) => e.toString().toLowerCase()),
      );
    }

    if (user['permissions'] is List) {
      names.addAll(
        (user['permissions'] as List).map((e) {
          if (e is Map && e['name'] != null) {
            return e['name'].toString().toLowerCase();
          }
          return e.toString().toLowerCase();
        }),
      );
    }

    return names.where((e) => e.isNotEmpty).toSet().toList();
  }

  Future<void> refreshSessionUser({bool notify = true}) async {
    final response = await _dio.get('/user');
    final payload = response.data is Map<String, dynamic>
        ? Map<String, dynamic>.from(response.data)
        : <String, dynamic>{};
    _user = payload;
    _roles = _extractRoles(payload);
    _permissions = _extractPermissions(payload);
    _isAuthenticated = true;
    if (notify) notifyListeners();
  }

  Future<void> _checkAuthStatus() async {
    String? token = await _storage.read(key: 'auth_token');
    if (token != null) {
      try {
        // Verify token is still valid and hydrate user/roles/permissions.
        await refreshSessionUser(notify: false);
      } catch (e) {
        await _storage.delete(key: 'auth_token');
        _isAuthenticated = false;
        _roles = [];
        _permissions = [];
        _user = null;
      }
    }
    _isLoading = false;
    notifyListeners();
  }

  Future<bool> login(String email, String password) async {
    try {
      _isLoading = true;
      _errorMessage = null;
      notifyListeners();

      final response = await _dio.post(
        '/login',
        data: {'email': email, 'password': password},
      );

      if (response.statusCode == 200) {
        final token = response.data['access_token'];
        final payload = response.data is Map<String, dynamic>
            ? Map<String, dynamic>.from(response.data)
            : <String, dynamic>{};
        final user = payload['user'] is Map<String, dynamic>
            ? Map<String, dynamic>.from(payload['user'])
            : <String, dynamic>{};
        _roles = _extractRoles(user);
        _permissions = _extractPermissions(payload);
        _user = user;

        await _storage.write(key: 'auth_token', value: token);
        _isAuthenticated = true;
        _isLoading = false;
        notifyListeners();
        return true;
      }
    } on DioException catch (e) {
      if (e.response?.statusCode == 401) {
        _errorMessage = 'Invalid email or password';
      } else if (e.response != null) {
        final responseData = e.response?.data;
        if (responseData is Map && responseData['message'] != null) {
          _errorMessage = responseData['message'].toString();
        } else {
          _errorMessage =
              'Server error (${e.response?.statusCode}). Please try again.';
        }
      } else if (e.type == DioExceptionType.connectionError ||
          e.type == DioExceptionType.connectionTimeout ||
          e.type == DioExceptionType.receiveTimeout ||
          e.type == DioExceptionType.sendTimeout) {
        _errorMessage =
            'Cannot reach server: ${_dio.options.baseUrl}. Open API Settings and set your Mac LAN URL.';
      } else {
        _errorMessage = 'Connection error. Please try again.';
      }
    } catch (e) {
      _errorMessage = 'An unexpected error occurred.';
    }

    _isLoading = false;
    notifyListeners();
    return false;
  }

  Future<void> logout() async {
    try {
      await _dio.post('/logout');
    } catch (e) {
      // Ignore errors on logout, just clear local state
    } finally {
      await _storage.delete(key: 'auth_token');
      _isAuthenticated = false;
      _roles = [];
      _permissions = [];
      _user = null;
      _errorMessage = null;
      notifyListeners();
    }
  }

  Future<void> updateApiBaseUrl(String baseUrl) async {
    await _apiClient.setBaseUrlOverride(baseUrl);
    notifyListeners();
  }

  @override
  void dispose() {
    _unauthorizedSub.cancel();
    super.dispose();
  }
}

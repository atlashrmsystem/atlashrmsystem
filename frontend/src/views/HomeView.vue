<template>
  <AdminDashboard v-if="userRole === 'admin' || userRole === 'super-admin'" />
  <EmployeeDashboard v-else />
</template>

<script setup>
import { computed } from 'vue';
import AdminDashboard from './AdminDashboard.vue';
import EmployeeDashboard from './EmployeeDashboard.vue';

const authUser = computed(() => {
  const userStr = sessionStorage.getItem('auth_user');
  return userStr ? JSON.parse(userStr) : null;
});

const authPermissions = computed(() => {
  const raw = sessionStorage.getItem('auth_permissions');
  if (raw) {
    try {
      return JSON.parse(raw) || [];
    } catch {
      return [];
    }
  }

  const permissions = authUser.value?.permission_names || authUser.value?.permissions || [];
  return Array.isArray(permissions) ? permissions : [];
});

const userRole = computed(() => {
  if (!authUser.value) return 'employee';
  
  // Check if roles are loaded from the backend (auth_user.roles or auth_user.role_names)
  const roles = authUser.value.role_names || (authUser.value.roles?.map(r => r.name)) || [];
  const normalizedRoles = roles.map(role => String(role).toLowerCase().trim());
  const isSuperAdmin = normalizedRoles.some(role =>
    ['superadmin', 'super-admin', 'super_admin', 'super admin'].includes(role)
  );
  if (isSuperAdmin) return 'super-admin';

  if (normalizedRoles.includes('admin')) return 'admin';
  const normalizedPermissions = authPermissions.value.map(permission => String(permission).toLowerCase().trim());
  const isAdminByPermission = normalizedPermissions.some(permission =>
    [
      'manage employees',
      'manage payroll',
      'manage attendance',
      'manage leaves',
      'manage recruitment',
      'manage performance',
      'manage benefits',
      'manage compliance',
    ].includes(permission)
  );

  const isAdmin = normalizedRoles.includes('admin') || isAdminByPermission;
  return isAdmin ? 'admin' : 'employee';
});
</script>

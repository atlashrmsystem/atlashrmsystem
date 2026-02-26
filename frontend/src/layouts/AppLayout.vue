<template>
  <div class="min-h-screen bg-[var(--color-brand-bg)] flex" :dir="layoutDirection">
    <!-- Sidebar -->
    <aside class="w-64 bg-[var(--color-brand-sidebar)] text-white flex flex-col transition-all duration-300">
      <div class="h-16 flex items-center justify-center border-b border-gray-700 font-bold text-lg tracking-wider">
        ATLAS HRM System
      </div>
      <nav class="flex-1 px-4 py-4 space-y-4 overflow-y-auto">
        
        <!-- Dashboard -->
        <div>
          <button @click="toggleMenu('dashboard')" class="w-full flex items-center justify-between px-3 py-2 text-xs font-bold text-white tracking-wider bg-gray-800 hover:bg-gray-700 rounded-md transition-colors shadow-sm">
            <span class="uppercase">Dashboard</span>
            <svg class="h-4 w-4 transition-transform duration-200 text-gray-400" :class="{'rotate-180': menuState.dashboard}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
          </button>
          <div v-show="menuState.dashboard" class="space-y-1 mt-1 pl-2">
             <router-link to="/" class="block px-4 py-2 rounded-md text-sm text-gray-400 hover:bg-gray-800 hover:text-white transition-colors" exact-active-class="bg-[var(--color-brand-primary)] text-white font-medium">Overview</router-link>
          </div>
        </div>

        <!-- Employee Management -->
        <div v-if="!isSupervisorPortalAccess">
          <button @click="toggleMenu('employees')" class="w-full flex items-center justify-between px-3 py-2 text-xs font-bold text-white tracking-wider bg-gray-800 hover:bg-gray-700 rounded-md transition-colors shadow-sm">
            <span class="uppercase">Employee Management</span>
            <svg class="h-4 w-4 transition-transform duration-200 text-gray-400" :class="{'rotate-180': menuState.employees}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
          </button>
          <div v-show="menuState.employees" class="space-y-1 mt-1 pl-2">
            <router-link to="/employees" class="block px-4 py-2 rounded-md text-sm text-gray-400 hover:bg-gray-800 hover:text-white transition-colors" active-class="bg-[var(--color-brand-primary)] text-white font-medium">Employees Directory</router-link>
          </div>
        </div>

        <!-- Brand Management -->
        <div v-if="isAdminAccess">
          <button @click="toggleMenu('brands')" class="w-full flex items-center justify-between px-3 py-2 text-xs font-bold text-white tracking-wider bg-gray-800 hover:bg-gray-700 rounded-md transition-colors shadow-sm">
            <span class="uppercase">Brand Management</span>
            <svg class="h-4 w-4 transition-transform duration-200 text-gray-400" :class="{'rotate-180': menuState.brands}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
          </button>
          <div v-show="menuState.brands" class="space-y-1 mt-1 pl-2">
            <router-link to="/brands" class="block px-4 py-2 rounded-md text-sm text-gray-400 hover:bg-gray-800 hover:text-white transition-colors" active-class="bg-[var(--color-brand-primary)] text-white font-medium">Brand Categories</router-link>
            <router-link to="/brands/management" class="block px-4 py-2 rounded-md text-sm text-gray-400 hover:bg-gray-800 hover:text-white transition-colors" active-class="bg-[var(--color-brand-primary)] text-white font-medium">Brand Management</router-link>
          </div>
        </div>

        <!-- Request Management -->
        <div v-if="!isSupervisorPortalAccess">
          <button @click="toggleMenu('leaves')" class="w-full flex items-center justify-between px-3 py-2 text-xs font-bold text-white tracking-wider bg-gray-800 hover:bg-gray-700 rounded-md transition-colors shadow-sm">
            <span class="uppercase">Request Management</span>
            <svg class="h-4 w-4 transition-transform duration-200 text-gray-400" :class="{'rotate-180': menuState.leaves}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
          </button>
          <div v-show="menuState.leaves" class="space-y-1 mt-1 pl-2">
            <router-link v-if="userRole === 'employee'" to="/leaves" class="block px-4 py-2 rounded-md text-sm text-gray-400 hover:bg-gray-800 hover:text-white transition-colors" active-class="bg-[var(--color-brand-primary)] text-white font-medium" exact>My Leaves</router-link>
            <router-link
              v-if="isAdminAccess"
              :to="{ path: '/leaves/approvals', query: { kind: 'leave' } }"
              :class="[
                'block px-4 py-2 rounded-md text-sm transition-colors',
                isApprovalsKindActive('leave')
                  ? 'bg-[var(--color-brand-primary)] text-white font-medium'
                  : 'text-gray-400 hover:bg-gray-800 hover:text-white'
              ]"
            >
              Leave Approvals
            </router-link>
            <router-link
              v-if="isAdminAccess"
              :to="{ path: '/leaves/approvals', query: { kind: 'salary-certificate' } }"
              :class="[
                'block px-4 py-2 rounded-md text-sm transition-colors',
                isApprovalsKindActive('salary-certificate')
                  ? 'bg-[var(--color-brand-primary)] text-white font-medium'
                  : 'text-gray-400 hover:bg-gray-800 hover:text-white'
              ]"
            >
              Salary Certificate Approvals
            </router-link>
          </div>
        </div>
        
        <!-- Recruitment -->
        <div v-if="!isSupervisorPortalAccess">
          <button @click="toggleMenu('recruitment')" class="w-full flex items-center justify-between px-3 py-2 text-xs font-bold text-white tracking-wider bg-gray-800 hover:bg-gray-700 rounded-md transition-colors shadow-sm">
            <span class="uppercase">Recruitment</span>
            <svg class="h-4 w-4 transition-transform duration-200 text-gray-400" :class="{'rotate-180': menuState.recruitment}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
          </button>
          <div v-show="menuState.recruitment" class="space-y-1 mt-1 pl-2">
            <router-link to="/recruitment" class="block px-4 py-2 rounded-md text-sm text-gray-400 hover:bg-gray-800 hover:text-white transition-colors" active-class="bg-[var(--color-brand-primary)] text-white font-medium" exact>Overview</router-link>
            <router-link v-if="isAdminAccess" to="/recruitment/jobs" class="block px-4 py-2 rounded-md text-sm text-gray-400 hover:bg-gray-800 hover:text-white transition-colors" active-class="bg-[var(--color-brand-primary)] text-white font-medium">Job Postings</router-link>
            <router-link v-if="isAdminAccess" to="/recruitment/candidates" class="block px-4 py-2 rounded-md text-sm text-gray-400 hover:bg-gray-800 hover:text-white transition-colors" active-class="bg-[var(--color-brand-primary)] text-white font-medium">Candidates</router-link>
          </div>
        </div>

        <!-- Performance -->
        <div v-if="!isSupervisorPortalAccess">
          <button @click="toggleMenu('performance')" class="w-full flex items-center justify-between px-3 py-2 text-xs font-bold text-white tracking-wider bg-gray-800 hover:bg-gray-700 rounded-md transition-colors shadow-sm">
            <span class="uppercase">Performance</span>
            <svg class="h-4 w-4 transition-transform duration-200 text-gray-400" :class="{'rotate-180': menuState.performance}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
          </button>
          <div v-show="menuState.performance" class="space-y-1 mt-1 pl-2">
            <router-link to="/performance" class="block px-4 py-2 rounded-md text-sm text-gray-400 hover:bg-gray-800 hover:text-white transition-colors" active-class="bg-[var(--color-brand-primary)] text-white font-medium" exact>Appraisals & Goals</router-link>
          </div>
        </div>

        <!-- Benefits -->
        <div v-if="isAdminAccess">
          <button @click="toggleMenu('benefits')" class="w-full flex items-center justify-between px-3 py-2 text-xs font-bold text-white tracking-wider bg-gray-800 hover:bg-gray-700 rounded-md transition-colors shadow-sm">
            <span class="uppercase">Benefits</span>
            <svg class="h-4 w-4 transition-transform duration-200 text-gray-400" :class="{'rotate-180': menuState.benefits}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
          </button>
          <div v-show="menuState.benefits" class="space-y-1 mt-1 pl-2">
             <router-link to="/benefits" class="block px-4 py-2 rounded-md text-sm text-gray-400 hover:bg-gray-800 hover:text-white transition-colors" active-class="bg-[var(--color-brand-primary)] text-white font-medium" exact>Benefits Admin</router-link>
          </div>
        </div>

        <!-- Time & Attendance -->
        <div v-if="!isSupervisorPortalAccess">
          <button @click="toggleMenu('attendance')" class="w-full flex items-center justify-between px-3 py-2 text-xs font-bold text-white tracking-wider bg-gray-800 hover:bg-gray-700 rounded-md transition-colors shadow-sm">
            <span class="uppercase">Time & Attendance</span>
            <svg class="h-4 w-4 transition-transform duration-200 text-gray-400" :class="{'rotate-180': menuState.attendance}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
          </button>
          <div v-show="menuState.attendance" class="space-y-1 mt-1 pl-2">
            <router-link v-if="userRole === 'employee'" to="/attendance" class="block px-4 py-2 rounded-md text-sm text-gray-400 hover:bg-gray-800 hover:text-white transition-colors" active-class="bg-[var(--color-brand-primary)] text-white font-medium" exact>My Attendance</router-link>
            <router-link v-if="isAdminAccess" to="/timesheets" class="block px-4 py-2 rounded-md text-sm text-gray-400 hover:bg-gray-800 hover:text-white transition-colors" active-class="bg-[var(--color-brand-primary)] text-white font-medium" exact>Timesheets</router-link>
          </div>
        </div>

        <!-- Payroll & WPS -->
        <div v-if="!isSupervisorPortalAccess">
          <button @click="toggleMenu('payroll')" class="w-full flex items-center justify-between px-3 py-2 text-xs font-bold text-white tracking-wider bg-gray-800 hover:bg-gray-700 rounded-md transition-colors shadow-sm">
            <span class="uppercase">Payroll & WPS</span>
            <svg class="h-4 w-4 transition-transform duration-200 text-gray-400" :class="{'rotate-180': menuState.payroll}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
          </button>
          <div v-show="menuState.payroll" class="space-y-1 mt-1 pl-2">
            <router-link v-if="userRole === 'employee'" to="/payroll/my-payslips" class="block px-4 py-2 rounded-md text-sm text-gray-400 hover:bg-gray-800 hover:text-white transition-colors" active-class="bg-[var(--color-brand-primary)] text-white font-medium" exact>My Payslips</router-link>
            <router-link v-if="isAdminAccess" to="/payroll/admin" class="block px-4 py-2 rounded-md text-sm text-gray-400 hover:bg-gray-800 hover:text-white transition-colors" active-class="bg-[var(--color-brand-primary)] text-white font-medium" exact>Payroll Admin</router-link>
          </div>
        </div>

        <!-- Analytics & Reports -->
        <div v-if="isAdminAccess">
          <button @click="toggleMenu('analytics')" class="w-full flex items-center justify-between px-3 py-2 text-xs font-bold text-white tracking-wider bg-gray-800 hover:bg-gray-700 rounded-md transition-colors shadow-sm">
            <span class="uppercase">Analytics & Reports</span>
            <svg class="h-4 w-4 transition-transform duration-200 text-gray-400" :class="{'rotate-180': menuState.analytics}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
          </button>
          <div v-show="menuState.analytics" class="space-y-1 mt-1 pl-2">
            <router-link to="/analytics" class="block px-4 py-2 rounded-md text-sm text-gray-400 hover:bg-gray-800 hover:text-white transition-colors" active-class="bg-[var(--color-brand-primary)] text-white font-medium" exact>HR Analytics</router-link>
            <router-link to="/reports" class="block px-4 py-2 rounded-md text-sm text-gray-400 hover:bg-gray-800 hover:text-white transition-colors" active-class="bg-[var(--color-brand-primary)] text-white font-medium" exact>System Reports</router-link>
          </div>
        </div>

        <!-- Compliance & Legal -->
        <div v-if="isAdminAccess">
          <button @click="toggleMenu('compliance')" class="w-full flex items-center justify-between px-3 py-2 text-xs font-bold text-white tracking-wider bg-gray-800 hover:bg-gray-700 rounded-md transition-colors shadow-sm">
            <span class="uppercase">Compliance & Legal</span>
            <svg class="h-4 w-4 transition-transform duration-200 text-gray-400" :class="{'rotate-180': menuState.compliance}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
          </button>
          <div v-show="menuState.compliance" class="space-y-1 mt-1 pl-2">
             <router-link to="/compliance" class="block px-4 py-2 rounded-md text-sm text-gray-400 hover:bg-gray-800 hover:text-white transition-colors" active-class="bg-[var(--color-brand-primary)] text-white font-medium" exact>Compliance Dashboard</router-link>
          </div>
        </div>

        <!-- Platform Admin -->
        <div v-if="userRole === 'super-admin'">
          <button @click="toggleMenu('platform')" class="w-full flex items-center justify-between px-3 py-2 text-xs font-bold text-white tracking-wider bg-gray-800 hover:bg-gray-700 rounded-md transition-colors shadow-sm">
            <span class="uppercase">Platform Admin</span>
            <svg class="h-4 w-4 transition-transform duration-200 text-gray-400" :class="{'rotate-180': menuState.platform}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
          </button>
          <div v-show="menuState.platform" class="space-y-1 mt-1 pl-2">
             <router-link to="/admin/users" class="block px-4 py-2 rounded-md text-sm text-gray-400 hover:bg-gray-800 hover:text-white transition-colors" active-class="bg-[var(--color-brand-primary)] text-white font-medium" exact>User Management</router-link>
             <router-link to="/admin/audit-logs" class="block px-4 py-2 rounded-md text-sm text-gray-400 hover:bg-gray-800 hover:text-white transition-colors" active-class="bg-[var(--color-brand-primary)] text-white font-medium" exact>Audit Logs</router-link>
          </div>
        </div>
      </nav>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
      <!-- Top Header -->
      <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10">
        <div class="flex-1 flex items-center">
          <div class="relative w-80">
            <input
              v-model="employeeSearchQuery"
              type="text"
              placeholder="Search employee or brand..."
              :disabled="!canSearchEmployees"
              @input="onSearchInput"
              @focus="handleSearchFocus"
              @blur="handleSearchBlur"
              @keydown.enter.prevent="openFirstSearchResult"
              class="w-full px-4 py-2 rounded-md border border-gray-200 focus:outline-none focus:ring-1 focus:ring-[var(--color-brand-primary)] text-sm disabled:bg-gray-100 disabled:text-gray-400"
            />
            <div
              v-if="shouldShowSearchDropdown"
              class="absolute top-full left-0 right-0 mt-2 bg-white border border-gray-200 rounded-md shadow-lg max-h-72 overflow-y-auto z-30"
            >
              <div v-if="isSearchingEmployees" class="px-4 py-3 text-xs text-gray-500">
                Searching...
              </div>
              <button
                v-for="employee in employeeSearchResults"
                :key="employee.id"
                @mousedown.prevent="goToEmployeeProfile(employee.id)"
                class="w-full text-left px-4 py-3 hover:bg-gray-50 border-b border-gray-100 last:border-b-0"
              >
                <div class="text-sm font-medium text-gray-900">{{ employee.full_name }}</div>
                <div class="text-xs text-gray-500">
                  {{ employee.job_title || 'Employee' }}
                  <span v-if="employee.store?.brand?.name"> • {{ employee.store.brand.name }}</span>
                </div>
              </button>
              <div
                v-if="!isSearchingEmployees && employeeSearchQuery.trim().length >= 2 && employeeSearchResults.length === 0"
                class="px-4 py-3 text-xs text-gray-500"
              >
                No employees found.
              </div>
            </div>
          </div>
        </div>
        <div class="flex items-center space-x-4 rtl:space-x-reverse">
          <NotificationDropdown />
          <div class="flex items-center space-x-2">
            <div class="h-8 w-8 rounded-full bg-[var(--color-brand-primary)] text-white flex items-center justify-center font-bold text-sm">
              {{ authUser?.name?.charAt(0) || 'U' }}
            </div>
            <span class="text-sm font-medium text-gray-700 mr-4">{{ authUser?.name || 'User' }}</span>
          </div>
          <button @click="handleLogout" class="text-sm font-medium text-red-600 hover:text-red-800 transition-colors ml-4 border border-red-200 px-3 py-1 rounded-md hover:bg-red-50">Log out</button>
        </div>
      </header>

      <!-- Page Content -->
      <main class="flex-1 overflow-x-hidden overflow-y-auto bg-[var(--color-brand-bg)] p-6">
        <router-view></router-view>
      </main>
    </div>
  </div>
</template>

<script setup>
import { reactive, computed, ref, onBeforeUnmount } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import NotificationDropdown from '../components/NotificationDropdown.vue';

const router = useRouter();
const route = useRoute();

const { locale } = useI18n();
const layoutDirection = computed(() => locale.value === 'ar' ? 'rtl' : 'ltr');

// Parse authenticated user
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

// Parse authenticated user role from the object returned by backend
const userRole = computed(() => {
  if (!authUser.value) return 'employee';
  
  // The backend now returns role_names or roles relation
  const roles = authUser.value.role_names || (authUser.value.roles?.map(r => r.name)) || [];
  const normalizedRoles = roles.map(role => String(role).toLowerCase().trim());
  const isSuperAdmin = normalizedRoles.some(role =>
    ['superadmin', 'super-admin', 'super_admin', 'super admin'].includes(role)
  );
  if (isSuperAdmin) return 'super-admin';

  if (normalizedRoles.includes('admin')) return 'admin';
  if (normalizedRoles.includes('manager')) return 'manager';
  if (normalizedRoles.includes('supervisor') || normalizedRoles.includes('shift-supervisor')) {
    return 'supervisor';
  }
  if (normalizedRoles.includes('sales-team')) return 'sales-team';
  if (normalizedRoles.includes('staff')) return 'employee';

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

  if (isAdminByPermission) return 'admin';
  return 'employee';
});

const isApprovalsKindActive = (kind) => {
  if (route.path !== '/leaves/approvals') return false;
  const activeKind = route.query.kind === 'salary-certificate' ? 'salary-certificate' : 'leave';
  return activeKind === kind;
};

const isAdminAccess = computed(() => userRole.value === 'admin' || userRole.value === 'super-admin');
const isSupervisorPortalAccess = computed(() => userRole.value === 'supervisor' || userRole.value === 'manager');
const normalizedPermissions = computed(() =>
  authPermissions.value.map(permission => String(permission).toLowerCase().trim())
);
const canSearchEmployees = computed(() =>
  isAdminAccess.value
  || normalizedPermissions.value.includes('view employees')
  || userRole.value === 'manager'
  || userRole.value === 'supervisor'
);

const employeeSearchQuery = ref('');
const employeeSearchResults = ref([]);
const isSearchingEmployees = ref(false);
const searchDropdownFocused = ref(false);

let searchDebounceTimeout = null;
let latestSearchRequestId = 0;

const menuState = reactive({
  dashboard: true,
  employees: false,
  brands: false,
  leaves: false,
  recruitment: false,
  performance: false,
  benefits: false,
  attendance: false,
  payroll: false,
  analytics: false,
  compliance: false,
  platform: false
});

const toggleMenu = (menu) => {
  menuState[menu] = !menuState[menu];
};

const shouldShowSearchDropdown = computed(() =>
  canSearchEmployees.value
  && searchDropdownFocused.value
  && employeeSearchQuery.value.trim().length >= 2
);

const runEmployeeSearch = async () => {
  const query = employeeSearchQuery.value.trim();
  if (!canSearchEmployees.value || query.length < 2) {
    employeeSearchResults.value = [];
    isSearchingEmployees.value = false;
    return;
  }

  const requestId = ++latestSearchRequestId;
  isSearchingEmployees.value = true;

  try {
    const response = await axios.get('/employees', {
      params: {
        per_page: 8,
        q: query,
      },
    });

    if (requestId !== latestSearchRequestId) return;
    employeeSearchResults.value = response.data?.data || [];
  } catch (error) {
    if (requestId !== latestSearchRequestId) return;
    employeeSearchResults.value = [];
    console.error('Employee search failed:', error);
  } finally {
    if (requestId === latestSearchRequestId) {
      isSearchingEmployees.value = false;
    }
  }
};

const debouncedEmployeeSearch = () => {
  if (searchDebounceTimeout) {
    clearTimeout(searchDebounceTimeout);
  }
  searchDebounceTimeout = setTimeout(() => {
    runEmployeeSearch();
  }, 250);
};

const handleSearchFocus = () => {
  searchDropdownFocused.value = true;
  debouncedEmployeeSearch();
};

const handleSearchBlur = () => {
  setTimeout(() => {
    searchDropdownFocused.value = false;
  }, 120);
};

const goToEmployeeProfile = (employeeId) => {
  employeeSearchQuery.value = '';
  employeeSearchResults.value = [];
  searchDropdownFocused.value = false;
  router.push(`/employees/${employeeId}`);
};

const openFirstSearchResult = () => {
  if (employeeSearchResults.value.length > 0) {
    goToEmployeeProfile(employeeSearchResults.value[0].id);
  }
};

const onSearchInput = () => {
  debouncedEmployeeSearch();
};

onBeforeUnmount(() => {
  if (searchDebounceTimeout) {
    clearTimeout(searchDebounceTimeout);
  }
});

const handleLogout = async () => {
    try {
        await axios.post('/logout');
    } catch (e) {
        console.error('Logout error', e);
    } finally {
        sessionStorage.removeItem('auth_token');
        sessionStorage.removeItem('auth_user');
        sessionStorage.removeItem('auth_permissions');
        localStorage.removeItem('auth_token');
        localStorage.removeItem('auth_user');
        localStorage.removeItem('auth_permissions');
        router.push('/login');
    }
};
</script>

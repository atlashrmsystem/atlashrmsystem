import { createRouter, createWebHistory } from 'vue-router'
import AppLayout from '../layouts/AppLayout.vue'
import LoginView from '../views/LoginView.vue'
import HomeView from '../views/HomeView.vue'
import EmployeeList from '../views/Employees/EmployeeList.vue'
import EmployeeForm from '../views/Employees/EmployeeForm.vue'
import EmployeeDetails from '../views/Employees/EmployeeDetails.vue' // Added import for EmployeeDetails
import LeaveDashboard from '../views/Leaves/LeaveDashboard.vue'
import LeaveApprovals from '../views/Leaves/LeaveApprovals.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: LoginView,
    },
    {
      path: '/',
      component: AppLayout,
      // Need meta auth guard here later
      children: [
        {
          path: '',
          name: 'dashboard',
          component: () => import('../views/HomeView.vue'),
        },
        {
          path: 'employees',
          name: 'employees',
          component: EmployeeList,
        },
        {
          path: 'brands',
          name: 'brands',
          component: () => import('../views/Brands/BrandManagement.vue'),
        },
        {
          path: 'brands/management',
          name: 'brands-management',
          component: () => import('../views/Brands/BrandDirectory.vue'),
        },
        {
          path: 'employees/create',
          name: 'employee-create',
          component: EmployeeForm,
        },
        {
          path: 'employees/:id', // Added route for employee details
          name: 'employee-details',
          component: EmployeeDetails,
        },
        {
          path: 'employees/:id/edit',
          name: 'employee-edit',
          component: EmployeeForm,
        },
        {
          path: 'leaves',
          name: 'leaves',
          component: LeaveDashboard,
        },
        {
          path: 'leaves/approvals',
          name: 'leave-approvals',
          component: LeaveApprovals,
        },
        {
          path: 'recruitment',
          name: 'recruitment-dashboard',
          component: () => import('../views/Recruitment/RecruitmentDashboard.vue'),
        },
        {
          path: 'recruitment/jobs',
          name: 'job-postings',
          component: () => import('../views/Recruitment/JobPostingList.vue'),
        },
        {
          path: 'recruitment/candidates',
          name: 'candidates',
          component: () => import('../views/Recruitment/CandidateList.vue'),
        },
        {
          path: 'performance',
          name: 'performance-dashboard',
          component: () => import('../views/Performance/PerformanceDashboard.vue'),
        },
        {
          path: 'benefits',
          name: 'benefits-dashboard',
          component: () => import('../views/Benefits/BenefitsDashboard.vue'),
        },
        {
          path: 'analytics',
          name: 'analytics-dashboard',
          component: () => import('../views/Analytics/AnalyticsDashboard.vue'),
        },
        {
          path: 'attendance',
          name: 'attendance-tracker',
          component: () => import('../views/Attendance/AttendanceTracker.vue'),
        },
        {
          path: 'timesheets',
          name: 'timesheet-admin',
          component: () => import('../views/Attendance/TimesheetAdmin.vue'),
        },
        {
          path: 'payroll/admin',
          name: 'payroll-admin',
          component: () => import('../views/Payroll/PayrollAdmin.vue'),
        },
        {
          path: 'payroll/my-payslips',
          name: 'my-payslips',
          component: () => import('../views/Payroll/MyPayslips.vue'),
        },
        {
          path: 'compliance',
          name: 'compliance-dashboard',
          component: () => import('../views/Compliance/ComplianceDashboard.vue'),
        },
        {
          path: 'reports',
          name: 'reports-dashboard',
          component: () => import('../views/Reports/ReportsDashboard.vue'),
        },
        {
          path: 'admin/users',
          name: 'admin-users',
          component: () => import('../views/SuperAdmin/UserManagement.vue'),
        },
        {
          path: 'admin/audit-logs',
          name: 'admin-audit-logs',
          component: () => import('../views/SuperAdmin/AuditLogs.vue'),
        }
      ]
    },
    // Catch-all route to redirect unknown paths back to the home page
    {
      path: '/:pathMatch(.*)*',
      redirect: '/'
    }
  ],
})

router.beforeEach((to) => {
  const isLoggedIn = !!sessionStorage.getItem('auth_token');
  const isLoginRoute = to.path === '/login';

  if (!isLoginRoute && !isLoggedIn) {
    return '/login';
  }

  if (to.path.startsWith('/admin/')) {
    const userStr = sessionStorage.getItem('auth_user');
    const authUser = userStr ? JSON.parse(userStr) : null;
    const roles = authUser?.role_names || authUser?.roles?.map(r => r.name) || [];
    const normalized = roles.map(r => String(r).toLowerCase().trim());
    const isSuperAdmin = normalized.some(r => ['superadmin', 'super-admin', 'super_admin', 'super admin'].includes(r));
    if (!isSuperAdmin) {
      return '/';
    }
  }

  return true;
});

export default router

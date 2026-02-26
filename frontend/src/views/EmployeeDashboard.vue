<template>
  <div>
    <h1 class="text-2xl font-semibold text-gray-900 mb-6">Welcome, {{ authUser?.name }}</h1>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
      <!-- Leave Balance -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Leave Balance</h3>
        <p class="text-3xl font-bold text-gray-900 mt-2">12 Days</p>
        <router-link to="/leaves" class="text-xs text-blue-600 hover:underline mt-2 block">Request Time Off</router-link>
      </div>

      <!-- Attendance Today -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Attendance Status</h3>
        <p class="text-lg font-semibold text-green-600 mt-2">Clocked In</p>
        <p class="text-xs text-gray-500 mt-1">Since 08:30 AM</p>
        <router-link to="/attendance" class="text-xs text-blue-600 hover:underline mt-2 block">View History</router-link>
      </div>

      <!-- Recent Payslip -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Latest Payslip</h3>
        <p class="text-lg font-semibold text-gray-900 mt-2">January 2026</p>
        <router-link to="/payroll/my-payslips" class="text-xs text-blue-600 hover:underline mt-2 block">Download PDF</router-link>
      </div>
    </div>

    <!-- Quick Actions Card -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Quick ESS Actions</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <router-link to="/leaves" class="p-4 border border-gray-100 rounded-lg hover:bg-gray-50 text-center">
                <p class="text-sm font-medium">Apply Leave</p>
            </router-link>
            <router-link to="/performance" class="p-4 border border-gray-100 rounded-lg hover:bg-gray-50 text-center">
                <p class="text-sm font-medium">My Goals</p>
            </router-link>
            <router-link to="/attendance" class="p-4 border border-gray-100 rounded-lg hover:bg-gray-50 text-center">
                <p class="text-sm font-medium">Clock In/Out</p>
            </router-link>
            <router-link to="/payroll/my-payslips" class="p-4 border border-gray-100 rounded-lg hover:bg-gray-50 text-center">
                <p class="text-sm font-medium">My Documents</p>
            </router-link>
        </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const authUser = computed(() => {
  const userStr = sessionStorage.getItem('auth_user');
  return userStr ? JSON.parse(userStr) : null;
});
</script>

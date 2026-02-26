<template>
  <div>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">System Reports Overview</h1>
        <button class="bg-[var(--color-brand-primary)] text-white px-4 py-2 rounded-md hover:bg-blue-700 transition flex items-center shadow-sm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
            Export Global Summary
        </button>
    </div>

    <div v-if="loading" class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 flex justify-center py-12">
        <div class="flex flex-col items-center">
            <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-[var(--color-brand-primary)] mb-4"></div>
            <p class="text-gray-500">Generating latest reports...</p>
        </div>
    </div>

    <div v-else-if="summary" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        
        <!-- Total Headcount -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 flex flex-col justify-between">
            <div class="flex justify-between items-start mb-4">
                <div class="p-2 bg-blue-50 text-blue-600 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
                <span class="text-xs font-semibold text-green-600 bg-green-50 px-2 py-1 rounded-full flex items-center">
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                    Active
                </span>
            </div>
            <div>
                <p class="text-3xl font-bold text-gray-900 mb-1">{{ summary.headcount }}</p>
                <p class="text-sm font-medium text-gray-500">Total Headcount</p>
            </div>
        </div>

        <!-- Pending Leaves -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 flex flex-col justify-between">
            <div class="flex justify-between items-start mb-4">
                <div class="p-2 bg-orange-50 text-orange-600 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
                <!-- Warning badge if there are a lot of pending leaves -->
                <span v-if="summary.pending_leaves > 5" class="text-xs font-semibold text-red-600 bg-red-50 px-2 py-1 rounded-full">High Volume</span>
            </div>
            <div>
                <p class="text-3xl font-bold text-gray-900 mb-1">{{ summary.pending_leaves }}</p>
                <p class="text-sm font-medium text-gray-500">Pending Leave Requests</p>
            </div>
        </div>

        <!-- Open Positions -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 flex flex-col justify-between">
            <div class="flex justify-between items-start mb-4">
                <div class="p-2 bg-purple-50 text-purple-600 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                </div>
                <span class="text-xs font-semibold text-purple-600 bg-purple-50 px-2 py-1 rounded-full text-center">
                    +{{ summary.new_hires_mtd }} Hires This Month
                </span>
            </div>
            <div>
                <p class="text-3xl font-bold text-gray-900 mb-1">{{ summary.open_positions }}</p>
                <p class="text-sm font-medium text-gray-500">Open Job Postings</p>
            </div>
        </div>

        <!-- MTD Payroll Cost -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 flex flex-col justify-between">
            <div class="flex justify-between items-start mb-4">
                <div class="p-2 bg-green-50 text-green-600 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <span class="text-xs font-semibold text-gray-600 bg-gray-100 px-2 py-1 rounded-full">
                    {{ summary.period }}
                </span>
            </div>
            <div>
                <p class="text-3xl font-bold text-gray-900 mb-1">
                    <span class="text-[0.6em] font-medium text-gray-500 mr-1">AED</span>{{ Number(summary.total_payroll_mtd).toLocaleString() }}
                </p>
                <p class="text-sm font-medium text-gray-500">Approved Payroll</p>
            </div>
        </div>
    </div>

    <!-- Quick Links Section -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4 border-b border-gray-100 pb-3">Financial Reports</h2>
            <ul class="space-y-4">
                <li>
                    <router-link to="/payroll/admin" class="flex items-center text-sm font-medium text-gray-700 hover:text-[var(--color-brand-primary)] group transition-colors">
                        <div class="p-2 bg-gray-50 rounded group-hover:bg-blue-50 mr-3 transition-colors">
                            <svg class="h-5 w-5 text-gray-400 group-hover:text-[var(--color-brand-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </div>
                        <div>
                            WPS Salary Information File (SIF)
                            <p class="text-xs font-normal text-gray-500 mt-0.5">Export Ministry of Human Resources compliant CSV</p>
                        </div>
                    </router-link>
                </li>
                 <li>
                    <router-link to="/timesheets" class="flex items-center text-sm font-medium text-gray-700 hover:text-[var(--color-brand-primary)] group transition-colors">
                        <div class="p-2 bg-gray-50 rounded group-hover:bg-blue-50 mr-3 transition-colors">
                            <svg class="h-5 w-5 text-gray-400 group-hover:text-[var(--color-brand-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                            Monthly Timesheet Variance
                            <p class="text-xs font-normal text-gray-500 mt-0.5">Review overtime and lateness metrics</p>
                        </div>
                    </router-link>
                </li>
            </ul>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4 border-b border-gray-100 pb-3">Operational Reports</h2>
            <ul class="space-y-4">
                 <li>
                    <router-link to="/compliance" class="flex items-center text-sm font-medium text-gray-700 hover:text-[var(--color-brand-primary)] group transition-colors">
                        <div class="p-2 bg-gray-50 rounded group-hover:bg-blue-50 mr-3 transition-colors">
                            <svg class="h-5 w-5 text-gray-400 group-hover:text-[var(--color-brand-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        </div>
                        <div>
                            Emiratisation (Tawteen) Status
                            <p class="text-xs font-normal text-gray-500 mt-0.5">Track UAE National quotas across departments</p>
                        </div>
                    </router-link>
                </li>
                 <li>
                    <router-link to="/analytics" class="flex items-center text-sm font-medium text-gray-700 hover:text-[var(--color-brand-primary)] group transition-colors">
                        <div class="p-2 bg-gray-50 rounded group-hover:bg-blue-50 mr-3 transition-colors">
                            <svg class="h-5 w-5 text-gray-400 group-hover:text-[var(--color-brand-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path></svg>
                        </div>
                        <div>
                            Turnover & Attrition Analysis
                            <p class="text-xs font-normal text-gray-500 mt-0.5">Analyze retention rates and tenure</p>
                        </div>
                    </router-link>
                </li>
            </ul>
        </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const summary = ref(null);
const loading = ref(true);

const fetchSummary = async () => {
    loading.value = true;
    try {
        const res = await axios.get('/reports/summary');
        summary.value = res?.data?.data || null;
    } catch (error) {
        console.error("Error fetching report summary:", error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchSummary();
});
</script>

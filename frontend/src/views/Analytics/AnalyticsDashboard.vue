<template>
  <div>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">HR Analytics Dashboard</h1>
        <button @click="fetchData" class="bg-gray-100 border border-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 transition-colors text-sm font-medium flex items-center">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
            Refresh Data
        </button>
    </div>

    <div v-if="loadingDashboard" class="flex justify-center p-12">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[var(--color-brand-primary)]"></div>
    </div>

    <div v-else>
        <!-- KPI Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Headcount -->
            <div class="bg-white rounded-lg p-6 shadow-sm border border-gray-100 flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Employees</p>
                    <p class="text-2xl font-bold text-gray-900">{{ dashboardData?.headcount?.total || 0 }}</p>
                </div>
            </div>

            <!-- Performance -->
            <div class="bg-white rounded-lg p-6 shadow-sm border border-gray-100 flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Avg Apprasial Score</p>
                    <p class="text-2xl font-bold text-gray-900">{{ dashboardData?.performance?.average_company_score || 'N/A' }} <span class="text-sm font-normal text-gray-500">/ 5</span></p>
                </div>
            </div>

            <!-- Leaves -->
            <div class="bg-white rounded-lg p-6 shadow-sm border border-gray-100 flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">On Leave Today</p>
                    <p class="text-2xl font-bold text-gray-900">{{ dashboardData?.leaves?.employees_on_leave_today || 0 }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ dashboardData?.leaves?.pending_requests || 0 }} Requests Pending</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Department Breakdown -->
            <div class="lg:col-span-1 bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                    <h2 class="text-lg font-semibold text-gray-900">Department Breakdown</h2>
                </div>
                <div class="p-6">
                    <ul class="space-y-4">
                        <li v-for="(count, dept) in dashboardData?.headcount?.by_department || {}" :key="dept" class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">{{ dept || 'Unassigned' }}</span>
                            <span class="text-sm font-bold text-gray-900 bg-gray-100 px-2 py-1 rounded w-10 text-center">{{ count }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Attrition Risk List -->
            <div class="lg:col-span-2 bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-gray-900">Attrition Risk Report</h2>
                    <span class="text-xs text-red-600 bg-red-100 px-2 py-1 rounded font-medium">Attention Required</span>
                </div>
                <div class="overflow-x-auto p-0">
                    <div v-if="loadingRisk" class="p-6 text-center text-gray-500">Calculating risk profiles...</div>
                    <table v-else class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                <th scope="col" class="px-6 py-3 text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Risk Level</th>
                                <th scope="col" class="px-6 py-3 text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Risk Factors</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-if="attritionRisk.length === 0">
                                <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">No high-risk employees identified.</td>
                            </tr>
                            <tr v-else v-for="risk in attritionRisk.filter(r => r.risk_score > 0)" :key="risk.employee_id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ risk.name }}</div>
                                    <div class="text-xs text-gray-500">{{ risk.department }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="getRiskBadge(risk.risk_level)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full border">
                                        {{ risk.risk_level }} ({{ risk.risk_score }})
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <ul class="list-disc list-inside text-xs text-gray-500">
                                        <li v-for="(reason, idx) in risk.reasons" :key="idx">{{ reason }}</li>
                                    </ul>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const dashboardData = ref(null);
const attritionRisk = ref([]);
const loadingDashboard = ref(true);
const loadingRisk = ref(true);

const fetchData = async () => {
    loadingDashboard.value = true;
    loadingRisk.value = true;
    
    try {
        const dashRes = await axios.get('/analytics/dashboard');
        dashboardData.value = dashRes?.data?.data || null;
    } catch (error) {
        console.error("Error fetching dashboard:", error);
    } finally {
        loadingDashboard.value = false;
    }

    try {
        const riskRes = await axios.get('/analytics/attrition');
        attritionRisk.value = riskRes?.data?.data || [];
    } catch (error) {
        console.error("Error fetching attrition risk:", error);
    } finally {
        loadingRisk.value = false;
    }
};

const getRiskBadge = (level) => {
    if (level === 'High') return 'bg-red-100 text-red-800 border-red-200';
    if (level === 'Medium') return 'bg-yellow-100 text-yellow-800 border-yellow-200';
    return 'bg-green-100 text-green-800 border-green-200';
};

onMounted(() => {
    fetchData();
});
</script>

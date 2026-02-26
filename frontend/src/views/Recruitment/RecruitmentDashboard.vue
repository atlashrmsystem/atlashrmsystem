<template>
  <div>
    <h1 class="text-2xl font-semibold text-gray-900 mb-6">Recruitment Dashboard</h1>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
      <!-- KPI Cards -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 flex items-center">
        <div class="p-3 rounded-full bg-blue-50 text-blue-600 mr-4 rtl:ml-4 rtl:mr-0">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
          </svg>
        </div>
        <div>
          <p class="text-sm font-medium text-gray-500">Open Positions</p>
          <p class="text-2xl font-bold text-gray-900">{{ dashboardStats.open_positions }}</p>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 flex items-center">
        <div class="p-3 rounded-full bg-indigo-50 text-indigo-600 mr-4 rtl:ml-4 rtl:mr-0">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
          </svg>
        </div>
        <div>
          <p class="text-sm font-medium text-gray-500">Active Candidates</p>
          <p class="text-2xl font-bold text-gray-900">{{ dashboardStats.active_candidates }}</p>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 flex items-center">
        <div class="p-3 rounded-full bg-green-50 text-green-600 mr-4 rtl:ml-4 rtl:mr-0">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
        </div>
        <div>
          <p class="text-sm font-medium text-gray-500">Offers Sent</p>
          <p class="text-2xl font-bold text-gray-900">{{ dashboardStats.offers_sent }}</p>
        </div>
      </div>
    </div>

    <!-- Active Job Postings -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 mb-8">
      <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
        <h2 class="text-lg font-semibold text-gray-900">Recent Job Postings</h2>
        <router-link to="/recruitment/jobs" class="text-sm font-medium text-[var(--color-brand-primary)] hover:text-blue-800">View All</router-link>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full whitespace-nowrap">
          <thead>
            <tr class="bg-gray-50 border-b border-gray-100 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              <th class="px-6 py-3">Job Title</th>
              <th class="px-6 py-3">Department</th>
              <th class="px-6 py-3">Status</th>
              <th class="px-6 py-3">Closing Date</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 text-sm">
            <tr v-for="job in recentJobs" :key="job.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 font-medium text-gray-900">{{ job.title }}</td>
              <td class="px-6 py-4 text-gray-500">{{ job.department?.name || 'N/A' }}</td>
              <td class="px-6 py-4">
                <span :class="getStatusClass(job.status)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                  {{ job.status }}
                </span>
              </td>
              <td class="px-6 py-4 text-gray-500">{{ formatDate(job.closes_at) }}</td>
            </tr>
            <tr v-if="recentJobs.length === 0">
              <td colspan="4" class="px-6 py-8 text-center text-gray-500">No active job postings found.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const dashboardStats = ref({
  open_positions: 0,
  active_candidates: 0,
  offers_sent: 0
});

const recentJobs = ref([]);

const fetchDashboardData = async () => {
  // In a real application, create an endpoint to aggregate these.
  // For now, doing simple separate requests based on the API built.
  try {
    const jobsRes = await axios.get('/job-postings', {
      params: { status: 'published' }
    });
    const jobsData = jobsRes.data;
    recentJobs.value = (jobsData?.data?.data || []).slice(0, 5); // Just top 5
    dashboardStats.value.open_positions = jobsData?.data?.total || 0;

    const candidatesRes = await axios.get('/candidates');
    const candidatesData = candidatesRes.data;
    // Assume active if not rejected/hired
    dashboardStats.value.active_candidates = (candidatesData?.data?.data || []).filter(
      c => c.status !== 'rejected' && c.status !== 'hired'
    ).length;

  } catch (error) {
    console.error("Failed to load recruitment dashboard data", error);
  }
};

onMounted(() => {
  fetchDashboardData();
});

const getStatusClass = (status) => {
  switch (status) {
    case 'published': return 'bg-green-100 text-green-800';
    case 'closed': return 'bg-gray-100 text-gray-800';
    case 'draft': return 'bg-yellow-100 text-yellow-800';
    default: return 'bg-gray-100 text-gray-800';
  }
};

const formatDate = (dateString) => {
  if (!dateString) return '-';
  const options = { year: 'numeric', month: 'short', day: 'numeric' };
  return new Date(dateString).toLocaleDateString(undefined, options);
};
</script>

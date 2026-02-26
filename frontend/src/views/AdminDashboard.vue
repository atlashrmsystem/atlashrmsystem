<template>
  <div>
    <h1 class="text-2xl font-semibold text-gray-900 mb-6">HR Overview Dashboard</h1>

    <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
      <!-- Total Employees -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 flex flex-col justify-between">
        <div class="flex justify-between items-start">
            <div class="text-sm font-medium text-gray-500">Total Employees</div>
            <div class="p-2 rounded-md bg-blue-50 text-blue-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                </svg>
            </div>
        </div>
        <div class="mt-4">
            <p class="text-3xl font-bold text-gray-900">{{ stats.totalEmployees || 0 }}</p>
        </div>
      </div>

      <!-- Active Contracts -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 flex flex-col justify-between">
        <div class="flex justify-between items-start">
            <div class="text-sm font-medium text-gray-500">Active Contracts</div>
            <div class="p-2 rounded-md bg-green-50 text-green-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
                </svg>
            </div>
        </div>
        <div class="mt-4">
            <p class="text-3xl font-bold text-gray-900">{{ stats.activeContracts || 0 }}</p>
            <p class="text-xs text-gray-500 mt-1">Requiring action: 0</p>
        </div>
      </div>

      <!-- Open Job Postings -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 flex flex-col justify-between">
        <div class="flex justify-between items-start">
            <div class="text-sm font-medium text-gray-500">Open Jobs</div>
            <div class="p-2 rounded-md bg-purple-50 text-purple-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M6 6V5a3 3 0 013-3h2a3 3 0 013 3v1h2a2 2 0 012 2v3.57A22.952 22.952 0 0110 13a22.95 22.95 0 01-8-1.43V8a2 2 0 012-2h2zm2-1a1 1 0 011-1h2a1 1 0 011 1v1H8V5zm1 5a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z" clip-rule="evenodd" />
                    <path d="M2 13.692V16a2 2 0 002 2h12a2 2 0 002-2v-2.308A24.974 24.974 0 0110 15c-2.796 0-5.487-.46-8-1.308z" />
                </svg>
            </div>
        </div>
        <div class="mt-4">
            <p class="text-3xl font-bold text-gray-900">{{ stats.openJobs || 0 }}</p>
            <router-link to="/recruitment/jobs" class="text-xs text-[var(--color-brand-primary)] hover:underline mt-1 block">View all</router-link>
        </div>
      </div>

      <!-- Pending Leaves -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 flex flex-col justify-between">
        <div class="flex justify-between items-start">
            <div class="text-sm font-medium text-gray-500">Pending Leaves</div>
            <div class="p-2 rounded-md bg-yellow-50 text-yellow-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                </svg>
            </div>
        </div>
        <div class="mt-4">
            <p class="text-3xl font-bold text-gray-900">{{ stats.pendingLeaves || 0 }}</p>
            <router-link to="/leaves/approvals" class="text-xs text-[var(--color-brand-primary)] hover:underline mt-1 block">Review leaves</router-link>
        </div>
      </div>

      <!-- Expiring Soon -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 flex flex-col justify-between">
        <div class="flex justify-between items-start">
            <div class="text-sm font-medium text-gray-500">Expiring Soon</div>
            <div class="p-2 rounded-md bg-red-50 text-red-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.72-1.36 3.485 0l6.516 11.59c.75 1.334-.213 2.99-1.742 2.99H3.483c-1.53 0-2.492-1.656-1.743-2.99l6.517-11.59zM11 14a1 1 0 10-2 0 1 1 0 002 0zm-1-7a1 1 0 00-1 1v3a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            </div>
        </div>
        <div class="mt-4">
            <p class="text-3xl font-bold text-gray-900">{{ stats.expiringSoon || 0 }}</p>
            <router-link to="/employees" class="text-xs text-[var(--color-brand-primary)] hover:underline mt-1 block">View employees</router-link>
        </div>
      </div>
    </div>

    <!-- Main Content Area -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Recent Employees / Activity -->
      <div class="lg:col-span-2 bg-white flex flex-col rounded-lg shadow-sm border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-900">Recent Employees</h2>
            <router-link to="/employees" class="text-sm text-[var(--color-brand-primary)] hover:underline">View Directory</router-link>
        </div>
        <div class="p-0 overflow-x-auto flex-1">
            <table class="w-full whitespace-nowrap">
                <thead class="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-3">Name</th>
                        <th class="px-6 py-3">Job Title</th>
                        <th class="px-6 py-3">Department</th>
                        <th class="px-6 py-3">Joining Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="emp in recentEmployees" :key="emp.id" class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <router-link :to="`/employees/${emp.id}`" class="flex items-center group">
                                <div class="h-8 w-8 rounded-full bg-blue-100 text-blue-600 overflow-hidden flex items-center justify-center font-bold text-xs mr-3 rtl:ml-3 rtl:mr-0 group-hover:bg-blue-200 transition-colors border border-white shadow-sm">
                                    <img v-if="emp.photo_url" :src="emp.photo_url" class="h-full w-full object-cover" />
                                    <span v-else>{{ emp.full_name.charAt(0) }}</span>
                                </div>
                                <div class="font-medium text-gray-900 group-hover:text-[var(--color-brand-primary)] transition-colors">{{ emp.full_name }}</div>
                            </router-link>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ emp.job_title }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ emp.department }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ formatDate(emp.joining_date) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-900">Quick Actions</h2>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                <router-link to="/employees/create" class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors group">
                    <div class="h-10 w-10 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center group-hover:bg-blue-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-4 rtl:mr-4 rtl:ml-0">
                        <p class="text-sm font-medium text-gray-900">Onboard Employee</p>
                        <p class="text-xs text-gray-500">Add a new team member</p>
                    </div>
                </router-link>
                
                <router-link to="/leaves/approvals" class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors group">
                    <div class="h-10 w-10 rounded-full bg-yellow-50 text-yellow-600 flex items-center justify-center group-hover:bg-yellow-101">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-4 rtl:mr-4 rtl:ml-0">
                        <p class="text-sm font-medium text-gray-900">Review Leaves</p>
                        <p class="text-xs text-gray-500">Process pending requests</p>
                    </div>
                </router-link>
            </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

const loading = ref(true);
const stats = ref({
    totalEmployees: 0,
    activeContracts: 0,
    openJobs: 0,
    pendingLeaves: 0,
    expiringSoon: 0,
});
const recentEmployees = ref([]);

const fetchData = async () => {
    loading.value = true;
    try {
        const [empRes, jobsRes, leavesRes] = await Promise.all([
            axios.get('/employees?per_page=5'),
            axios.get('/job-postings?status=published'),
            axios.get('/leaves?status=pending')
        ]);

        // empRes is an axios response, so data is in empRes.data
        const empData = empRes.data;
        stats.value.totalEmployees = empData.total || 0;
        stats.value.activeContracts = empData.active_contracts || 0;
        stats.value.expiringSoon = empData.expiring_soon_contracts || 0;
        recentEmployees.value = empData.data || [];

        const jobsData = jobsRes.data;
        stats.value.openJobs = jobsData.total || jobsData.data?.total || 0;

        const leavesData = leavesRes.data;
        stats.value.pendingLeaves = Array.isArray(leavesData) ? leavesData.length : (leavesData.data?.length || 0);

    } catch (error) {
        console.error("Dashboard fetch error:", error);
    } finally {
        loading.value = false;
    }
};

const formatDate = (dateString) => {
  if (!dateString) return '-';
  return new Date(dateString).toLocaleDateString();
};

onMounted(fetchData);
</script>

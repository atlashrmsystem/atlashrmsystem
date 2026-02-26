<template>
  <div>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">Compliance & Legal</h1>
    </div>

    <!-- Section 1: Emiratisation -->
    <div class="mb-8">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Emiratisation (Tawteen) Quotas</h2>
        
        <div v-if="loadingEmiratisation" class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 flex justify-center">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[var(--color-brand-primary)]"></div>
        </div>
        
        <div v-else-if="emiratisationStats" class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Overall Card -->
            <div class="lg:col-span-1 bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">Company Overall</h3>
                <div class="flex items-end mb-2">
                    <span class="text-4xl font-bold" :class="emiratisationStats.overall_percentage >= emiratisationStats.overall_target ? 'text-green-600' : 'text-red-500'">
                        {{ emiratisationStats.overall_percentage }}%
                    </span>
                    <span class="text-sm text-gray-500 ml-2 pb-1">/ {{ emiratisationStats.overall_target }}% Target</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
                    <div class="h-2 rounded-full" 
                        :class="emiratisationStats.overall_percentage >= emiratisationStats.overall_target ? 'bg-green-500' : 'bg-red-500'" 
                        :style="'width: ' + Math.min(emiratisationStats.overall_percentage, 100) + '%'">
                    </div>
                </div>
                <p class="text-sm text-gray-600">
                    {{ emiratisationStats.total_nationals }} UAE Nationals out of {{ emiratisationStats.total_headcount }} employees.
                </p>
            </div>
            
            <!-- Department Breakdown -->
            <div class="lg:col-span-3 bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Department Breakdown</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div v-for="(stat, dept) in emiratisationStats.departments" :key="dept" class="border border-gray-100 rounded p-4">
                        <div class="flex justify-between items-center mb-2">
                            <span class="font-medium text-gray-900">{{ dept || 'Unassigned' }}</span>
                            <span class="text-sm font-bold" :class="stat.percentage >= emiratisationStats.overall_target ? 'text-green-600' : 'text-red-500'">
                                {{ stat.percentage }}%
                            </span>
                        </div>
                         <div class="w-full bg-gray-200 rounded-full h-1.5 mb-2">
                            <div class="h-1.5 rounded-full" 
                                :class="stat.percentage >= emiratisationStats.overall_target ? 'bg-green-500' : 'bg-red-500'" 
                                :style="'width: ' + Math.min(stat.percentage, 100) + '%'">
                            </div>
                        </div>
                        <p class="text-xs text-gray-500">{{ stat.nationals }} of {{ stat.total }} employees</p>
                    </div>
                </div>
                <div v-if="Object.keys(emiratisationStats.departments).length === 0" class="text-sm text-gray-500 py-4">
                    No department data available.
                </div>
            </div>
        </div>
    </div>

    <!-- Section 2: Expirations -->
    <div>
         <h2 class="text-lg font-medium text-gray-900 mb-4">Upcoming Document Expirations (90 Days)</h2>
         <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                            <th scope="col" class="px-6 py-3 text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Document Type</th>
                            <th scope="col" class="px-6 py-3 text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Expiry Date</th>
                            <th scope="col" class="px-6 py-3 text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Time Remaining</th>
                            <th scope="col" class="px-6 py-3 text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-if="loadingExpirations">
                            <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">Loading documents...</td>
                        </tr>
                        <tr v-else-if="expiringDocs.length === 0">
                            <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                All employee documents are up to date!
                            </td>
                        </tr>
                        <tr v-else v-for="(doc, idx) in expiringDocs" :key="idx" class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ doc.name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ doc.document }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ new Date(doc.expiry_date).toLocaleDateString() }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm flex items-center">
                                <span v-if="doc.days_remaining > 0" class="font-medium" :class="doc.urgency === 'critical' ? 'text-red-600' : 'text-yellow-600'">
                                    {{ doc.days_remaining }} days
                                </span>
                                <span v-else class="font-bold text-red-700">Expired</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span v-if="doc.urgency === 'expired'" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 border border-red-200">Expired</span>
                                <span v-else-if="doc.urgency === 'critical'" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-50 text-red-700 border border-red-100">Critical < 30d</span>
                                <span v-else class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800 border border-yellow-200">Warning < 90d</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const emiratisationStats = ref(null);
const expiringDocs = ref([]);
const loadingEmiratisation = ref(true);
const loadingExpirations = ref(true);

const fetchEmiratisation = async () => {
    loadingEmiratisation.value = true;
    try {
        const res = await axios.get('/compliance/emiratisation-stats');
        emiratisationStats.value = res?.data?.data || null;
    } catch (error) {
        console.error("Error fetching Emiratisation stats:", error);
    } finally {
        loadingEmiratisation.value = false;
    }
};

const fetchExpirations = async () => {
    loadingExpirations.value = true;
    try {
        const res = await axios.get('/compliance/expiring-documents');
        expiringDocs.value = res?.data?.data || [];
    } catch (error) {
        console.error("Error fetching expirations:", error);
    } finally {
        loadingExpirations.value = false;
    }
};

onMounted(() => {
    fetchEmiratisation();
    fetchExpirations();
});
</script>

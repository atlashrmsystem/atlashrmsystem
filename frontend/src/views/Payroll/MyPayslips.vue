<template>
  <div>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">My Payslips</h1>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Summary Card -->
        <div class="lg:col-span-1 bg-[var(--color-brand-primary)] rounded-lg shadow-sm text-white overflow-hidden flex flex-col justify-center p-8 min-h-[250px] relative">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-white opacity-10 rounded-full blur-2xl"></div>
            <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-32 h-32 bg-blue-900 opacity-20 rounded-full blur-2xl"></div>
            
            <div class="relative z-10">
                <p class="text-blue-100 mb-1 font-medium">Latest Net Salary</p>
                <div v-if="loading" class="animate-pulse h-10 w-32 bg-blue-800 rounded"></div>
                <h2 v-else-if="latestPayslip" class="text-4xl font-bold mb-4">
                    AED {{ Number(latestPayslip.net_salary).toLocaleString(undefined, {minimumFractionDigits: 2}) }}
                </h2>
                <h2 v-else class="text-2xl font-bold mb-4">No Data</h2>
                
                <p v-if="latestPayslip" class="text-sm text-blue-100 bg-black bg-opacity-20 inline-block px-3 py-1 rounded-full">
                    For {{ getMonthName(latestPayslip.month) }} {{ latestPayslip.year }}
                </p>
            </div>
        </div>

        <!-- History List -->
        <div class="lg:col-span-2 bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-900">Historical Payslips</h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Period</th>
                            <th scope="col" class="px-6 py-3 text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Basic</th>
                            <th scope="col" class="px-6 py-3 text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Net Amount</th>
                            <th scope="col" class="px-6 py-3 text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-if="loading">
                            <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">Loading payslips...</td>
                        </tr>
                        <tr v-else-if="payslips.length === 0">
                            <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">No payslips available yet.</td>
                        </tr>
                        <tr v-else v-for="slip in payslips" :key="slip.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ getMonthName(slip.month) }} {{ slip.year }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ Number(slip.basic_salary).toLocaleString() }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                                AED {{ Number(slip.net_salary).toLocaleString(undefined, {minimumFractionDigits: 2}) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="getStatusBadge(slip.status)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full border">
                                    {{ slip.status.charAt(0).toUpperCase() + slip.status.slice(1) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button @click="viewDetails(slip)" class="text-[var(--color-brand-primary)] hover:text-blue-900 flex items-center justify-end w-full">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    View
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Details Modal -->
    <div v-if="selectedSlip" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl overflow-hidden flex flex-col max-h-[90vh]">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                <h3 class="text-lg font-medium text-gray-900">Payslip Details - {{ getMonthName(selectedSlip.month) }} {{ selectedSlip.year }}</h3>
                <button @click="selectedSlip = null" class="text-gray-400 hover:text-gray-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <div class="p-6 overflow-y-auto">
                <div class="grid grid-cols-2 gap-8">
                    <!-- Earnings -->
                    <div>
                        <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4 border-b pb-2">Earnings</h4>
                        <div class="space-y-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Basic Salary</span>
                                <span class="font-medium text-gray-900">{{ Number(selectedSlip.basic_salary).toLocaleString(undefined, {minimumFractionDigits: 2}) }}</span>
                            </div>
                            <div v-for="(val, key) in selectedSlip.allowances" :key="'allow_'+key" class="flex justify-between text-sm">
                                <span class="text-gray-600 capitalize">Allowance: {{ key }}</span>
                                <span class="font-medium text-green-600">+{{ Number(val).toLocaleString(undefined, {minimumFractionDigits: 2}) }}</span>
                            </div>
                            <div v-if="selectedSlip.overtime_pay > 0" class="flex justify-between text-sm">
                                <span class="text-gray-600">Overtime Pay</span>
                                <span class="font-medium text-green-600">+{{ Number(selectedSlip.overtime_pay).toLocaleString(undefined, {minimumFractionDigits: 2}) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Deductions -->
                    <div>
                        <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4 border-b pb-2">Deductions</h4>
                        <div class="space-y-3">
                            <div v-if="!selectedSlip.deductions || Object.keys(selectedSlip.deductions).length === 0" class="text-sm text-gray-400 italic">
                                No deductions.
                            </div>
                            <div v-else v-for="(val, key) in selectedSlip.deductions" :key="'deduct_'+key" class="flex justify-between text-sm">
                                <span class="text-gray-600 capitalize">{{ key }}</span>
                                <span class="font-medium text-red-600">-{{ Number(val).toLocaleString(undefined, {minimumFractionDigits: 2}) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-gray-200">
                    <div class="flex justify-between items-center bg-gray-50 p-4 rounded-lg">
                        <span class="text-lg font-semibold text-gray-900">Net Salary</span>
                        <span class="text-2xl font-bold text-[var(--color-brand-primary)]">AED {{ Number(selectedSlip.net_salary).toLocaleString(undefined, {minimumFractionDigits: 2}) }}</span>
                    </div>
                </div>
            </div>
            
            <div class="p-4 border-t border-gray-100 bg-gray-50 text-right">
                <button @click="selectedSlip = null" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-50 transition-colors text-sm font-medium">Close</button>
            </div>
        </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

const payslips = ref([]);
const loading = ref(true);
const selectedSlip = ref(null);

const latestPayslip = computed(() => {
    if (payslips.value.length === 0) return null;
    // Assuming API returns them ordered by newest first, usually valid but let's just grab index 0.
    return payslips.value[0];
});

const fetchPayslips = async () => {
    loading.value = true;
    try {
        const authUser = JSON.parse(sessionStorage.getItem('auth_user') || '{}');
        const empId = authUser?.employee_id;

        if (!empId) {
            payslips.value = [];
            return;
        }

        const res = await axios.get('/payrolls', {
            params: { employee_id: empId }
        });
        const data = res?.data?.data || [];

        // Sort by year desc, month desc
        payslips.value = data.sort((a,b) => {
            if (a.year !== b.year) return b.year - a.year;
            return b.month - a.month;
        });
    } catch (error) {
        console.error("Error fetching payslips:", error);
    } finally {
        loading.value = false;
    }
};

const viewDetails = (slip) => {
    selectedSlip.value = slip;
};

const getMonthName = (monthNumber) => {
    const date = new Date(2000, monthNumber - 1, 1);
    return date.toLocaleString('default', { month: 'long' });
};

const getStatusBadge = (status) => {
    if (status === 'draft') return 'bg-yellow-100 text-yellow-800 border-yellow-200';
    if (status === 'approved') return 'bg-green-100 text-green-800 border-green-200';
    if (status === 'paid') return 'bg-blue-100 text-blue-800 border-blue-200';
    return 'bg-gray-100 text-gray-800 border-gray-200';
};

onMounted(() => {
    fetchPayslips();
});
</script>

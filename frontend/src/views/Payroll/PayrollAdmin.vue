<template>
  <div>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">Payroll Administration</h1>
        <div class="flex space-x-3 rtl:space-x-reverse">
            <button @click="showGenerateModal = true" class="bg-[var(--color-brand-primary)] text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors text-sm font-medium flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                Generate Payroll
            </button>
            <button @click="exportWps" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors text-sm font-medium flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                Export WPS (SIF)
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100 mb-6 flex space-x-4 rtl:space-x-reverse">
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Period</label>
            <input type="month" v-model="filterPeriod" @change="fetchPayrolls" class="border border-gray-300 rounded-md shadow-sm py-2 px-3 text-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div class="flex items-end">
             <button @click="fetchPayrolls" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 transition-colors text-sm font-medium">
                Filter
            </button>
        </div>
    </div>

    <!-- Payroll List -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-900">Monthly Payroll Records</h2>
            <span v-if="payrolls.length > 0" class="text-xs text-gray-500">Showing {{ payrolls.length }} records</span>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                        <th scope="col" class="px-6 py-3 text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Period</th>
                        <th scope="col" class="px-6 py-3 text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Basic</th>
                        <th scope="col" class="px-6 py-3 text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Allowances</th>
                        <th scope="col" class="px-6 py-3 text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Overtime</th>
                        <th scope="col" class="px-6 py-3 text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Deductions</th>
                        <th scope="col" class="px-6 py-3 text-left rtl:text-right text-xs font-medium text-gray-900 uppercase tracking-wider">Net Salary</th>
                        <th scope="col" class="px-6 py-3 text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-if="loading">
                        <td colspan="9" class="px-6 py-8 text-center text-sm text-gray-500">Loading payroll data...</td>
                    </tr>
                    <tr v-else-if="payrolls.length === 0">
                        <td colspan="9" class="px-6 py-8 text-center text-sm text-gray-500">
                            No payroll records found for this period. Click "Generate Payroll" to calculate them.
                        </td>
                    </tr>
                    <tr v-else v-for="pay in payrolls" :key="pay.id" class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ pay.employee?.full_name || 'Unknown' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ pay.month }}/{{ pay.year }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ Number(pay.basic_salary).toLocaleString(undefined, {minimumFractionDigits: 2}) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">
                            +{{ sumObject(pay.allowances) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">
                            +{{ Number(pay.overtime_pay).toLocaleString(undefined, {minimumFractionDigits: 2}) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">
                            -{{ sumObject(pay.deductions) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                            AED {{ Number(pay.net_salary).toLocaleString(undefined, {minimumFractionDigits: 2}) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span :class="getStatusBadge(pay.status)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full border">
                                {{ pay.status.charAt(0).toUpperCase() + pay.status.slice(1) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button v-if="pay.status === 'draft'" @click="approvePayroll(pay)" class="text-[var(--color-brand-primary)] hover:text-blue-900">Approve</button>
                            <span v-else class="text-gray-300">Approved</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Generate Modal -->
    <div v-if="showGenerateModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-md w-full shadow-xl">
            <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Generate Monthly Payroll</h3>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Target Period</label>
                <input type="month" v-model="generatePeriod" class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 text-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                <p class="text-xs text-gray-500 mt-2">This will fetch the approved timesheets for all employees and calculate their net salary including basic pay, allowances, overtime, and lateness deductions.</p>
            </div>
            
            <div class="flex justify-end space-x-3 rtl:space-x-reverse mt-6">
                <button @click="showGenerateModal = false" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 transition-colors text-sm font-medium">Cancel</button>
                <button @click="generatePayroll" :disabled="generating" class="bg-[var(--color-brand-primary)] text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors text-sm font-medium flex items-center disabled:opacity-50">
                    <span v-if="generating" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></span>
                    Generate Payroll
                </button>
            </div>
        </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const payrolls = ref([]);
const loading = ref(true);
const showGenerateModal = ref(false);
const generating = ref(false);

const filterPeriod = ref(new Date().toISOString().slice(0, 7));
const generatePeriod = ref(new Date().toISOString().slice(0, 7));

const parsePeriod = (period) => {
    const [yearText, monthText] = (period || '').split('-');
    const year = Number.parseInt(yearText, 10);
    const month = Number.parseInt(monthText, 10);

    if (!Number.isInteger(year) || !Number.isInteger(month)) {
        return null;
    }

    return { year, month };
};

const sumObject = (obj) => {
    if (!obj) return '0.00';
    try {
        const sum = Object.values(obj).reduce((a, b) => a + Number(b), 0);
        return sum.toLocaleString(undefined, {minimumFractionDigits: 2});
    } catch (e) {
        return '0.00';
    }
};

const fetchPayrolls = async () => {
    loading.value = true;
    try {
        const parsed = parsePeriod(filterPeriod.value);
        const params = parsed ? parsed : {};
        const res = await axios.get('/payrolls', { params });
        payrolls.value = res?.data?.data || [];
    } catch (error) {
        console.error("Error fetching payrolls:", error);
    } finally {
        loading.value = false;
    }
};

const generatePayroll = async () => {
    generating.value = true;
    try {
        const parsed = parsePeriod(generatePeriod.value);
        if (!parsed) {
            alert('Please select a valid period first.');
            return;
        }

        await axios.post('/payrolls/generate', parsed);

        alert('Payroll generated successfully!');
        showGenerateModal.value = false;
        filterPeriod.value = generatePeriod.value;
        fetchPayrolls();
    } catch (error) {
        console.error("Generation error:", error);
        alert(error?.response?.data?.message || 'Failed to generate payrolls');
    } finally {
        generating.value = false;
    }
};

const approvePayroll = async (payroll) => {
    try {
        await axios.put(`/payrolls/${payroll.id}`, { status: 'approved' });
        fetchPayrolls();
    } catch (error) {
        console.error("Approval error:", error);
    }
};

const exportWps = async () => {
    const parsed = parsePeriod(filterPeriod.value);
    if (!parsed) {
        alert("Please select a valid period first.");
        return;
    }

    try {
        const response = await axios.get('/payrolls/wps-export', {
            params: parsed,
            responseType: 'blob',
        });

        const blob = new Blob([response.data], { type: 'text/csv;charset=utf-8;' });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `WPS_Export_${parsed.year}_${String(parsed.month).padStart(2, '0')}.csv`);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
    } catch (error) {
        console.error("WPS export error:", error);
        alert(error?.response?.data?.message || 'Failed to export WPS file');
    }
};

const getStatusBadge = (status) => {
    if (status === 'draft') return 'bg-yellow-100 text-yellow-800 border-yellow-200';
    if (status === 'approved') return 'bg-green-100 text-green-800 border-green-200';
    if (status === 'paid') return 'bg-blue-100 text-blue-800 border-blue-200';
    return 'bg-gray-100 text-gray-800 border-gray-200';
};

onMounted(() => {
    fetchPayrolls();
});
</script>

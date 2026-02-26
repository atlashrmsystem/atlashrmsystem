<template>
  <div>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">Timesheets</h1>
        <button @click="showGenerateModal = true" class="bg-[var(--color-brand-primary)] text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors text-sm font-medium flex items-center">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            Generate Timesheets
        </button>
    </div>

    <!-- Filters -->
    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100 mb-6 flex space-x-4 rtl:space-x-reverse">
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Period</label>
            <input type="month" v-model="filterPeriod" @change="fetchTimesheets" class="border border-gray-300 rounded-md shadow-sm py-2 px-3 text-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div class="flex items-end">
             <button @click="fetchTimesheets" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 transition-colors text-sm font-medium">
                Filter
            </button>
        </div>
    </div>

    <!-- Daily Attendance Summary -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Supposed On Duty</p>
            <p class="mt-2 text-2xl font-bold text-gray-900">{{ dailySummary.supposed_on_duty }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Present Today</p>
            <p class="mt-2 text-2xl font-bold text-green-600">{{ dailySummary.present_today }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Currently On Duty</p>
            <p class="mt-2 text-2xl font-bold text-blue-600">{{ dailySummary.currently_on_duty }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Absent Today</p>
            <p class="mt-2 text-2xl font-bold text-red-600">{{ dailySummary.absent_today }}</p>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-900">
                Daily Attendance ({{ dailySummary.date || 'Today' }})
            </h2>
            <button @click="fetchDailySummary" class="text-gray-500 hover:text-gray-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                        <th class="px-6 py-3 text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                        <th class="px-6 py-3 text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Clock In</th>
                        <th class="px-6 py-3 text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Clock Out</th>
                        <th class="px-6 py-3 text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Duty Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-if="loadingDaily">
                        <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">Loading daily attendance...</td>
                    </tr>
                    <tr v-else-if="dailyRows.length === 0">
                        <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">No employees found.</td>
                    </tr>
                    <tr v-else v-for="row in dailyRows" :key="row.employee_id" class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ row.full_name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ row.department || '-' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatTime(row.clock_in_time) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatTime(row.clock_out_time) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span
                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full border"
                                :class="row.is_on_duty ? 'bg-blue-100 text-blue-800 border-blue-200' : (row.status === 'absent' ? 'bg-red-100 text-red-800 border-red-200' : 'bg-green-100 text-green-800 border-green-200')"
                            >
                                {{ row.is_on_duty ? 'On Duty' : (row.status === 'absent' ? 'Absent' : 'Off Duty') }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Timesheets List -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-900">Monthly Timesheets</h2>
            <span v-if="timesheets.length > 0" class="text-xs text-gray-500">Showing {{ timesheets.length }} records</span>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                        <th scope="col" class="px-6 py-3 text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Period</th>
                        <th scope="col" class="px-6 py-3 text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Hours</th>
                        <th scope="col" class="px-6 py-3 text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Overtime</th>
                        <th scope="col" class="px-6 py-3 text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Lateness (HH:MM)</th>
                        <th scope="col" class="px-6 py-3 text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-if="loading">
                        <td colspan="6" class="px-6 py-8 text-center">
                            <div class="flex justify-center flex-col items-center">
                                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mb-2"></div>
                                <span class="text-sm text-gray-500">Loading timesheets...</span>
                            </div>
                        </td>
                    </tr>
                    <tr v-else-if="timesheets.length === 0">
                        <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">
                            No timesheets found for this period. Click "Generate Timesheets" to create them.
                        </td>
                    </tr>
                    <tr v-else v-for="sheet in timesheets" :key="sheet.id" class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="h-8 w-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-xs mr-3 rtl:ml-3 rtl:mr-0">
                                    {{ sheet.employee?.full_name?.charAt(0) || 'U' }}
                                </div>
                                <div class="text-sm font-medium text-gray-900">{{ sheet.employee?.full_name || 'Unknown' }}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ sheet.month }}/{{ sheet.year }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                            {{ Number(sheet.total_hours || 0).toFixed(2) }}h
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span :class="{'text-green-600 font-medium': Number(sheet.overtime_hours || 0) > 0, 'text-gray-500': Number(sheet.overtime_hours || 0) === 0}" class="text-sm">
                                {{ Number(sheet.overtime_hours || 0).toFixed(2) }}h
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                             <span :class="{'text-red-600 font-medium': Number(sheet.lateness_minutes || 0) > 0, 'text-gray-500': Number(sheet.lateness_minutes || 0) === 0}" class="text-sm">
                                {{ formatMinutesAsHours(Number(sheet.lateness_minutes || 0)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span :class="getStatusBadge(sheet.status)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full border">
                                {{ sheet.status }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Generate Modal -->
    <div v-if="showGenerateModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-md w-full shadow-xl">
            <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Generate Monthly Timesheets</h3>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Target Period</label>
                <input type="month" v-model="generatePeriod" class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 text-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                <p class="text-xs text-gray-500 mt-2">This will calculate total hours, overtime, and lateness penalties for all employees for the selected month.</p>
            </div>
            
            <div class="flex justify-end space-x-3 rtl:space-x-reverse mt-6">
                <button @click="showGenerateModal = false" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 transition-colors text-sm font-medium">Cancel</button>
                <button @click="generateTimesheets" :disabled="generating" class="bg-[var(--color-brand-primary)] text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors text-sm font-medium flex items-center disabled:opacity-50">
                    <span v-if="generating" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></span>
                    Generate Data
                </button>
            </div>
        </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const timesheets = ref([]);
const loading = ref(true);
const showGenerateModal = ref(false);
const generating = ref(false);
const loadingDaily = ref(false);
const dailySummary = ref({
    date: '',
    supposed_on_duty: 0,
    present_today: 0,
    currently_on_duty: 0,
    absent_today: 0,
});
const dailyRows = ref([]);

// Default to current month
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

const fetchTimesheets = async () => {
    loading.value = true;
    try {
        const parsed = parsePeriod(filterPeriod.value);
        const response = await axios.get('/timesheets', {
            params: parsed ? parsed : {},
        });
        timesheets.value = response.data.data;
    } catch (error) {
        console.error("Error fetching timesheets:", error);
    } finally {
        loading.value = false;
    }
};

const fetchDailySummary = async () => {
    loadingDaily.value = true;
    try {
        const response = await axios.get('/attendance/summary/today');
        const data = response.data.data || {};

        dailySummary.value = {
            date: data.date || '',
            supposed_on_duty: data.supposed_on_duty || 0,
            present_today: data.present_today || 0,
            currently_on_duty: data.currently_on_duty || 0,
            absent_today: data.absent_today || 0,
        };
        dailyRows.value = data.rows || [];
    } catch (error) {
        console.error("Error fetching daily attendance summary:", error);
        dailyRows.value = [];
    } finally {
        loadingDaily.value = false;
    }
};

const generateTimesheets = async () => {
    generating.value = true;
    try {
        const parsed = parsePeriod(generatePeriod.value);
        if (!parsed) {
            alert('Please select a valid period first.');
            return;
        }

        const response = await axios.post('/timesheets/generate', parsed);

        const generatedCount = response.data.generated_count;
        const successMessage = generatedCount
            ? `Timesheets generated for ${generatedCount} employees.`
            : 'Timesheet generated successfully!';
        alert(successMessage);
        showGenerateModal.value = false;
        filterPeriod.value = generatePeriod.value;
        fetchTimesheets();
        fetchDailySummary();
    } catch (error) {
        console.error("Generation error:", error);
        alert(error.response?.data?.message || 'An error occurred while generating timesheets.');
    } finally {
        generating.value = false;
    }
};

const getStatusBadge = (status) => {
    if (status === 'draft') return 'bg-gray-100 text-gray-800 border-gray-200';
    if (status === 'approved') return 'bg-green-100 text-green-800 border-green-200';
    return 'bg-blue-100 text-blue-800 border-blue-200';
};

const formatTime = (timeString) => {
    if (!timeString) return '-';
    const date = new Date(timeString);
    if (Number.isNaN(date.getTime())) return '-';
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

const formatMinutesAsHours = (minutes) => {
    const safeMinutes = Number.isFinite(minutes) ? Math.max(0, Math.floor(minutes)) : 0;
    const hours = Math.floor(safeMinutes / 60).toString().padStart(2, '0');
    const mins = (safeMinutes % 60).toString().padStart(2, '0');
    return `${hours}:${mins}`;
};

onMounted(() => {
    fetchTimesheets();
    fetchDailySummary();
});
</script>

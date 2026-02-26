<template>
  <div>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">{{ isViewingOthers ? 'Employee Attendance' : 'My Attendance' }}</h1>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Clock In/Out Box - Only show if viewing own attendance -->
        <div v-if="!isViewingOthers" class="lg:col-span-1 bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-900">Today's Status</h2>
            </div>
            <div class="p-6 flex flex-col items-center justify-center min-h-[250px]">
                <div v-if="loadingStatus" class="animate-spin rounded-full h-8 w-8 border-b-2 border-[var(--color-brand-primary)]"></div>
                <div v-else-if="!attendanceStatus" class="text-center">
                    <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-400">
                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <p class="text-gray-500 mb-6">You haven't clocked in today.</p>
                    <button @click="clockIn" :disabled="actionLoading" class="bg-[var(--color-brand-primary)] text-white px-8 py-3 rounded-md hover:bg-blue-700 transition-colors font-medium w-full flex justify-center items-center disabled:opacity-50">
                        <span v-if="actionLoading" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></span>
                        Clock In Now
                    </button>
                </div>
                <div v-else class="text-center w-full">
                    <div v-if="attendanceStatus.clock_out" class="mb-4">
                        <div class="w-24 h-24 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900">Shift Completed</h3>
                        <p class="text-sm text-gray-500 mt-1">Clocked in: {{ formatTime(attendanceStatus.clock_in_time) }}</p>
                        <p class="text-sm text-gray-500">Clocked out: {{ formatTime(attendanceStatus.clock_out_time) }}</p>
                        <div class="mt-4 p-3 bg-gray-50 rounded text-sm text-gray-700 font-medium">
                            Total Hours: {{ attendanceStatus.total_hours || 'Calculating...' }}
                        </div>
                    </div>
                    <div v-else class="mb-4">
                        <div class="w-24 h-24 bg-blue-100 text-[var(--color-brand-primary)] rounded-full flex items-center justify-center mx-auto mb-4 animate-pulse">
                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900">Currently Clocked In</h3>
                        <p class="text-sm text-gray-500 mt-1">Since {{ formatTime(attendanceStatus.clock_in_time) }}</p>
                        
                        <div class="mt-6">
                            <button @click="clockOut" :disabled="actionLoading" class="bg-red-600 text-white px-8 py-3 rounded-md hover:bg-red-700 transition-colors font-medium w-full flex justify-center items-center disabled:opacity-50">
                                <span v-if="actionLoading" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></span>
                                Clock Out
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance History -->
        <div :class="isViewingOthers ? 'lg:col-span-3' : 'lg:col-span-2'" class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-900">Attendance History</h2>
                <button @click="fetchHistory" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                </button>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th scope="col" class="px-6 py-3 text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Clock In</th>
                            <th scope="col" class="px-6 py-3 text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Clock Out</th>
                            <th scope="col" class="px-6 py-3 text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Hours</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-if="loadingHistory">
                            <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">Loading history...</td>
                        </tr>
                        <tr v-else-if="history.length === 0">
                            <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">No attendance records found.</td>
                        </tr>
                        <tr v-else v-for="record in history" :key="record.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ formatDate(record.date) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <span v-if="record.clock_in_time" class="text-green-600 font-medium">{{ formatTime(record.clock_in_time) }}</span>
                                <span v-else class="text-gray-400">-</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <span v-if="record.clock_out_time" class="text-red-600 font-medium">{{ formatTime(record.clock_out_time) }}</span>
                                <span v-else class="text-gray-400">-</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-medium">
                                {{ record.total_hours || '...' }}
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
import { ref, onMounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const attendanceStatus = ref(null);
const history = ref([]);
const loadingStatus = ref(true);
const loadingHistory = ref(true);
const actionLoading = ref(false);

const isViewingOthers = computed(() => !!route.query.employee_id);

const checkTodayStatus = async () => {
    if (isViewingOthers.value) {
        loadingStatus.value = false;
        return;
    }
    
    loadingStatus.value = true;
    try {
        const response = await axios.get('/attendance/today');
        attendanceStatus.value = response.data.data;
    } catch (error) {
        console.error("Error fetching today's status:", error);
        attendanceStatus.value = null;
    } finally {
        loadingStatus.value = false;
    }
};

const fetchHistory = async () => {
    loadingHistory.value = true;
    try {
        const params = {};
        if (route.query.employee_id) {
            params.employee_id = route.query.employee_id;
        }
        
        const response = await axios.get('/attendance', { params });
        // Handle both paginated and non-paginated responses
        if (response.data.data && Array.isArray(response.data.data.data)) {
            history.value = response.data.data.data;
        } else if (Array.isArray(response.data.data)) {
            history.value = response.data.data;
        } else {
            history.value = [];
        }
    } catch (error) {
        console.error("Error fetching history:", error);
        history.value = [];
    } finally {
        loadingHistory.value = false;
    }
};

const clockIn = async () => {
    actionLoading.value = true;
    try {
        const response = await axios.post('/attendance/clock-in');
        if (response.data.status === 'success') {
            await checkTodayStatus();
            await fetchHistory();
        }
    } catch (error) {
        console.error("Clock in error:", error);
        alert(error.response?.data?.message || 'Failed to clock in');
    } finally {
        actionLoading.value = false;
    }
};

const clockOut = async () => {
    actionLoading.value = true;
    try {
        const response = await axios.post('/attendance/clock-out');
        if (response.data.status === 'success') {
            await checkTodayStatus();
            await fetchHistory();
        }
    } catch (error) {
        console.error("Clock out error:", error);
        alert(error.response?.data?.message || 'Failed to clock out');
    } finally {
        actionLoading.value = false;
    }
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString();
};

const formatTime = (timeString) => {
    if (!timeString) return '';
    const date = new Date(timeString);
    if (!isNaN(date.getTime())) {
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
    return timeString;
};

onMounted(() => {
    checkTodayStatus();
    fetchHistory();
});
</script>

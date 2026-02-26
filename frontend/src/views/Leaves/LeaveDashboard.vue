<template>
  <div class="max-w-6xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-900">My Leaves</h1>
      <button
        @click="showRequestModal = true"
        class="bg-[var(--color-brand-primary)] text-white px-4 py-2 rounded-md hover:bg-[var(--color-brand-hover)] transition-colors shadow-sm"
      >
        + Request Leave
      </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div v-for="balance in balances" :key="balance.id" class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 flex items-center justify-between">
        <div>
          <h3 class="text-gray-500 text-sm font-medium">{{ balance.leave_type.name }} Balance</h3>
          <p class="text-3xl font-bold text-gray-900 mt-2">
            {{ balance.balance_days - balance.used_days }} <span class="text-sm font-normal text-gray-500">days left</span>
          </p>
        </div>
        <div class="h-12 w-12 rounded-full bg-blue-50 flex items-center justify-center text-[var(--color-brand-primary)]">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="text-lg font-medium leading-6 text-gray-900">Leave History</h3>
      </div>
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dates</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Days</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="req in leaveRequests" :key="req.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
              {{ req.type ? req.type.name : 'Unknown' }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ formatDate(req.start_date) }} - {{ formatDate(req.end_date) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ req.total_days }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span
                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                :class="getStatusClass(req.status)"
              >
                {{ req.status }}
              </span>
            </td>
          </tr>

          <tr v-if="leaveRequests.length === 0">
            <td colspan="4" class="px-6 py-10 text-center text-gray-500">
              No leave requests found.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="showRequestModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
      <div class="bg-white rounded-lg p-8 w-full max-w-md shadow-xl">
        <h2 class="text-xl font-bold mb-4">Request Leave</h2>

        <form @submit.prevent="submitLeave">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Leave Type</label>
              <select v-model="form.leave_type_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm border p-2 focus:border-blue-500">
                <option value="1">Annual Leave</option>
                <option value="2">Sick Leave</option>
                <option value="3">Maternity Leave</option>
              </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Start Date</label>
                <input type="date" v-model="form.start_date" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm border p-2 focus:border-blue-500" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">End Date</label>
                <input type="date" v-model="form.end_date" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm border p-2 focus:border-blue-500" />
              </div>
            </div>

            <div v-if="form.leave_type_id == 2">
              <label class="block text-sm font-medium text-gray-700">Medical Certificate</label>
              <input type="file" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Reason / Notes</label>
              <textarea v-model="form.reason" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm border p-2 focus:border-blue-500"></textarea>
            </div>
          </div>

          <div class="mt-6 flex justify-end space-x-3">
            <button type="button" @click="showRequestModal = false" class="px-4 py-2 border rounded-md text-gray-600 hover:bg-gray-50">Cancel</button>
            <button type="submit" class="px-4 py-2 bg-[var(--color-brand-primary)] text-white rounded-md hover:bg-blue-700">Submit Request</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const balances = ref([]);
const leaveRequests = ref([]);
const showRequestModal = ref(false);
const authUser = JSON.parse(sessionStorage.getItem('auth_user') || '{}');
const currentEmployeeId = ref(authUser?.employee_id || null);

const form = ref({
  leave_type_id: '',
  start_date: '',
  end_date: '',
  reason: '',
});

const fetchData = async () => {
  try {
    if (!currentEmployeeId.value) {
      balances.value = [];
      leaveRequests.value = [];
      return;
    }

    const params = { employee_id: currentEmployeeId.value };
    const [balRes, reqRes] = await Promise.all([
      axios.get('/leaves/balances', { params }),
      axios.get('/leaves', { params }),
    ]);
    balances.value = balRes?.data || [];
    leaveRequests.value = reqRes?.data?.data || [];
  } catch (err) {
    console.error('Failed to load leave data', err);
  }
};

onMounted(() => {
  fetchData();
});

const submitLeave = async () => {
  try {
    if (!currentEmployeeId.value) {
      alert('No employee profile linked to this account.');
      return;
    }

    await axios.post('/leaves/request', {
      ...form.value,
      employee_id: currentEmployeeId.value,
    });
    showRequestModal.value = false;
    alert('Leave request submitted successfully');
    fetchData();
  } catch (err) {
    alert(err.response?.data?.error || 'Validation failed. Check dates or balances.');
  }
};

const formatDate = (dateString) => {
  if (!dateString) return '';
  return new Date(dateString).toLocaleDateString();
};

const getStatusClass = (status) => {
  switch ((status || '').toLowerCase()) {
    case 'approved':
      return 'bg-green-100 text-green-800';
    case 'rejected':
      return 'bg-red-100 text-red-800';
    default:
      return 'bg-yellow-100 text-yellow-800';
  }
};
</script>

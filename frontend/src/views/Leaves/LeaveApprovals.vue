<template>
  <div class="max-w-6xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-900">{{ requestKind === 'leave' ? 'Leave Approvals' : 'Salary Certificate Approvals' }}</h1>
      <div class="flex gap-2">
        <button 
          v-for="status in ['all', 'pending', 'approved', 'rejected']" 
          :key="status"
          @click="currentFilter = status; fetchRequests()"
          :class="[
            'px-4 py-2 text-sm font-medium rounded-md capitalize',
            currentFilter === status 
              ? 'bg-[var(--color-brand-primary)] text-white' 
              : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'
          ]"
        >
          {{ status }}
        </button>
      </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50 text-xs text-gray-500 uppercase font-medium">
          <tr v-if="requestKind === 'leave'">
            <th class="px-6 py-3 text-left">Employee</th>
            <th class="px-6 py-3 text-left">Type</th>
            <th class="px-6 py-3 text-left">Dates</th>
            <th class="px-6 py-3 text-left">Duration</th>
            <th class="px-6 py-3 text-left">Status</th>
            <th class="px-6 py-3 text-right">Actions</th>
          </tr>
          <tr v-else>
            <th class="px-6 py-3 text-left">Employee</th>
            <th class="px-6 py-3 text-left">Requested At</th>
            <th class="px-6 py-3 text-left">Status</th>
            <th class="px-6 py-3 text-left">Notes</th>
            <th class="px-6 py-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-100">
          <tr v-for="req in requests" :key="`${requestKind}-${req.id}`" class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="flex items-center">
                <div class="h-8 w-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-xs mr-3">
                  {{ req.employee?.full_name?.charAt(0) || 'E' }}
                </div>
                <div>
                  <div class="text-sm font-medium text-gray-900">{{ req.employee?.full_name || 'Unknown' }}</div>
                  <div class="text-xs text-gray-500">ID: {{ req.employee_id }}</div>
                </div>
              </div>
            </td>
            <td v-if="requestKind === 'leave'" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ req.type ? req.type.name : 'Unknown' }}
            </td>
            <td v-if="requestKind === 'leave'" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ formatDate(req.start_date) }} - {{ formatDate(req.end_date) }}
            </td>
            <td v-if="requestKind === 'leave'" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ req.total_days }} days
            </td>
            <td v-if="requestKind === 'salary-certificate'" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ formatDateTime(req.requested_at) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span :class="getStatusBadgeClass(req.status)" class="px-2 py-1 text-xs font-semibold rounded-full uppercase">
                {{ req.status }}
              </span>
            </td>
            <td v-if="requestKind === 'salary-certificate'" class="px-6 py-4 text-sm text-gray-500 max-w-[280px] truncate">
              {{ req.notes || '-' }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <div class="flex justify-end gap-2">
                <button v-if="requestKind === 'leave'" @click="viewDetails(req)" class="text-[var(--color-brand-primary)] hover:text-blue-900 bg-blue-50 px-3 py-1 rounded-md transition-colors">Details</button>
                <div v-if="requestKind === 'leave' && req.status === 'pending'" class="flex gap-2">
                  <button @click="processLeave(req.id, 'approve')" class="text-green-600 hover:text-green-900 bg-green-50 px-3 py-1 rounded-md transition-colors">Approve</button>
                  <button @click="processLeave(req.id, 'reject')" class="text-red-600 hover:text-red-900 bg-red-50 px-3 py-1 rounded-md transition-colors">Reject</button>
                </div>
                <div v-if="requestKind === 'salary-certificate' && req.status === 'pending'" class="flex gap-2">
                  <button @click="processCertificate(req.id, 'approve')" class="text-green-600 hover:text-green-900 bg-green-50 px-3 py-1 rounded-md transition-colors">Approve</button>
                  <button @click="processCertificate(req.id, 'reject')" class="text-red-600 hover:text-red-900 bg-red-50 px-3 py-1 rounded-md transition-colors">Reject</button>
                </div>
              </div>
            </td>
          </tr>
          
          <tr v-if="requests.length === 0">
            <td :colspan="requestKind === 'leave' ? 6 : 5" class="px-6 py-10 text-center text-gray-500">
              No requests found.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Leave Detail Modal -->
    <div v-if="selectedRequest && requestKind === 'leave'" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
          <h3 class="text-lg font-bold text-gray-900">Leave Request Details</h3>
          <button @click="selectedRequest = null" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 p-1 rounded-full transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
          </button>
        </div>
        
        <div class="p-6 space-y-6">
          <!-- Employee Info -->
          <div class="flex items-center p-4 bg-blue-50 rounded-lg">
            <div class="h-12 w-12 rounded-full bg-blue-200 text-[var(--color-brand-primary)] flex items-center justify-center font-bold text-xl mr-4 uppercase">
              {{ selectedRequest.employee?.full_name?.charAt(0) || 'E' }}
            </div>
            <div>
              <div class="text-lg font-bold text-gray-900">{{ selectedRequest.employee?.full_name }}</div>
              <div class="text-sm text-gray-600">Employee ID: {{ selectedRequest.employee_id }}</div>
            </div>
            <div class="ml-auto text-right">
              <span :class="getStatusBadgeClass(selectedRequest.status)" class="px-3 py-1 text-xs font-bold rounded-full uppercase">
                {{ selectedRequest.status }}
              </span>
            </div>
          </div>

          <!-- Request Details -->
          <div class="grid grid-cols-2 gap-6">
            <div>
              <label class="text-xs font-bold text-gray-500 uppercase tracking-wider">Leave Type</label>
              <div class="mt-1 text-sm font-medium text-gray-900">{{ selectedRequest.type?.name }}</div>
            </div>
            <div>
              <label class="text-xs font-bold text-gray-500 uppercase tracking-wider">Duration</label>
              <div class="mt-1 text-sm font-medium text-gray-900">{{ selectedRequest.total_days }} Days</div>
            </div>
            <div>
              <label class="text-xs font-bold text-gray-500 uppercase tracking-wider">Start Date</label>
              <div class="mt-1 text-sm font-medium text-gray-900">{{ formatDate(selectedRequest.start_date) }}</div>
            </div>
            <div>
              <label class="text-xs font-bold text-gray-500 uppercase tracking-wider">End Date</label>
              <div class="mt-1 text-sm font-medium text-gray-900">{{ formatDate(selectedRequest.end_date) }}</div>
            </div>
          </div>

          <!-- Reason -->
          <div>
            <label class="text-xs font-bold text-gray-500 uppercase tracking-wider">Reason for Leave</label>
            <div class="mt-2 p-3 bg-gray-50 rounded text-sm text-gray-700 whitespace-pre-wrap italic">
              "{{ selectedRequest.reason || 'No reason provided.' }}"
            </div>
          </div>

          <!-- Attachment -->
          <div v-if="selectedRequest.attachment_path">
            <label class="text-xs font-bold text-gray-500 uppercase tracking-wider">Attachment</label>
            <div class="mt-2 text-sm">
                <a :href="`${backendAssetBase}/storage/${selectedRequest.attachment_path}`" target="_blank" class="flex items-center text-[var(--color-brand-primary)] hover:underline">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                    View Attachment / Medical Certificate
                </a>
            </div>
          </div>

          <!-- Leave Balances -->
          <div v-if="balances.length > 0" class="border-t pt-4">
            <label class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3 block">Employee Balances (Current Year)</label>
            <div class="grid grid-cols-2 gap-4">
                <div v-for="bal in balances" :key="bal.id" class="p-3 border rounded-md" :class="bal.leave_type_id === selectedRequest.leave_type_id ? 'border-blue-300 bg-blue-50' : 'bg-white'">
                    <div class="text-xs font-semibold text-gray-600 truncate">{{ bal.leave_type?.name }}</div>
                    <div class="flex justify-between items-baseline mt-1">
                        <span class="text-lg font-bold text-gray-900">{{ bal.total_days - bal.used_days }}</span>
                        <span class="text-xs text-gray-500">Left of {{ bal.total_days }}</span>
                    </div>
                </div>
            </div>
          </div>
          <div v-else-if="loadingBalances" class="flex items-center justify-center p-4">
              <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-[var(--color-brand-primary)] mr-2"></div>
              <span class="text-xs text-gray-500">Checking leave balances...</span>
          </div>
        </div>

        <div class="px-6 py-4 bg-gray-50 border-t flex justify-end gap-3">
          <button @click="selectedRequest = null" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">Close</button>
          <div v-if="selectedRequest.status === 'pending'" class="flex gap-3">
            <button @click="processLeave(selectedRequest.id, 'reject')" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700">Reject Request</button>
            <button @click="processLeave(selectedRequest.id, 'approve')" class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700">Approve Request</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const requests = ref([]);
const currentFilter = ref('all');
const requestKind = ref('leave');
const selectedRequest = ref(null);
const balances = ref([]);
const loadingBalances = ref(false);
const backendAssetBase = (axios.defaults.baseURL || '').replace(/\/api\/?$/, '');

const resolveRequestKind = (value) => {
  return value === 'salary-certificate' ? 'salary-certificate' : 'leave';
};

const fetchRequests = async () => {
  try {
    let url = requestKind.value === 'leave' ? '/leaves' : '/salary-certificate-requests';
    const params = {};

    if (currentFilter.value !== 'all') {
      params.status = currentFilter.value;
    }

    const res = await axios.get(url, { params });
    const payload = res.data;
    requests.value = Array.isArray(payload?.data) ? payload.data : (Array.isArray(payload) ? payload : []);
  } catch (err) {
    console.error('Failed fetching requests', err);
  }
};

const viewDetails = async (req) => {
    selectedRequest.value = req;
    balances.value = [];
    loadingBalances.value = true;
    try {
        const res = await axios.get('/leaves/balances', {
            params: { employee_id: req.employee_id }
        });
        balances.value = res.data;
    } catch (err) {
        console.error('Failed to fetch balances', err);
    } finally {
        loadingBalances.value = false;
    }
}

onMounted(() => {
  requestKind.value = resolveRequestKind(route.query.kind);
  fetchRequests();
});

watch(
  () => route.query.kind,
  (value) => {
    const normalized = resolveRequestKind(value);
    if (requestKind.value !== normalized) {
      requestKind.value = normalized;
      fetchRequests();
    }
  },
);

const processLeave = async (id, action) => {
  try {
    const comment = prompt(`Enter optional comment for ${action}:`);
    if (comment === null) return; // User cancelled
    
    await axios.patch(`/leaves/${id}/${action}`, { comment });
    alert(`Leave ${action}d successfully`);
    selectedRequest.value = null; // Close modal if open
    fetchRequests();
  } catch (err) {
    alert(`Failed to ${action} leave. ${err.response?.data?.error || ''}`);
  }
};

const processCertificate = async (id, action) => {
  try {
    const notes = prompt(`Enter optional notes for ${action}:`) ?? '';
    await axios.put(`/salary-certificate-requests/${id}/${action}`, { notes });
    alert(`Salary certificate ${action}d successfully`);
    fetchRequests();
  } catch (err) {
    alert(`Failed to ${action} salary certificate. ${err.response?.data?.message || ''}`);
  }
};

const getStatusBadgeClass = (status) => {
  switch (status.toLowerCase()) {
    case 'approved': return 'bg-green-100 text-green-800';
    case 'rejected': return 'bg-red-100 text-red-800';
    default: return 'bg-yellow-100 text-yellow-800';
  }
};

const formatDate = (dateString) => {
  if (!dateString) return '';
  return new Date(dateString).toLocaleDateString();
};

const formatDateTime = (dateString) => {
  if (!dateString) return '-';
  return new Date(dateString).toLocaleString();
};
</script>

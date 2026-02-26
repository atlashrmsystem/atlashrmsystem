<template>
  <div class="max-w-7xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-semibold text-gray-900">Audit Logs</h1>
      <button @click="fetchLogs" class="px-4 py-2 bg-[var(--color-brand-primary)] text-white rounded-md text-sm font-semibold hover:bg-[var(--color-brand-hover)]">
        Refresh
      </button>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-4 flex flex-wrap gap-3">
      <input
        v-model="filters.action"
        placeholder="Filter by action (e.g. user.updated)"
        class="px-3 py-2 border rounded-md text-sm w-64"
      />
      <input
        v-model.number="filters.actor_id"
        type="number"
        min="1"
        placeholder="Actor ID"
        class="px-3 py-2 border rounded-md text-sm w-36"
      />
      <button @click="fetchLogs(1)" class="px-4 py-2 border rounded-md text-sm">Apply</button>
      <button @click="clearFilters" class="px-4 py-2 border rounded-md text-sm">Clear</button>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Time</th>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Action</th>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Actor</th>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Target</th>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Meta</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white">
          <tr v-if="loading">
            <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">Loading audit logs...</td>
          </tr>
          <tr v-else-if="logs.length === 0">
            <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">No audit records found.</td>
          </tr>
          <tr v-else v-for="log in logs" :key="log.id" class="hover:bg-gray-50 align-top">
            <td class="px-6 py-4 text-xs text-gray-600 whitespace-nowrap">{{ formatDate(log.created_at) }}</td>
            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ log.action }}</td>
            <td class="px-6 py-4 text-sm text-gray-600">
              <div>ID: {{ log.actor_id || '-' }}</div>
              <div class="text-xs text-gray-500">{{ log.actor?.email || log.actor?.name || '-' }}</div>
            </td>
            <td class="px-6 py-4 text-sm text-gray-600">
              <div>{{ log.target_type || '-' }}</div>
              <div class="text-xs text-gray-500">ID: {{ log.target_id || '-' }}</div>
            </td>
            <td class="px-6 py-4 text-xs text-gray-600 max-w-xl break-words">{{ stringify(log.meta) }}</td>
          </tr>
        </tbody>
      </table>
      <div v-if="pagination.total > pagination.per_page" class="px-6 py-4 border-t border-gray-100 flex items-center justify-between text-sm text-gray-600">
        <span>Showing {{ pagination.from }} - {{ pagination.to }} of {{ pagination.total }}</span>
        <div class="space-x-2">
          <button :disabled="!pagination.prev_page_url" @click="fetchLogs(pagination.current_page - 1)" class="px-3 py-1 border rounded disabled:opacity-50">Prev</button>
          <button :disabled="!pagination.next_page_url" @click="fetchLogs(pagination.current_page + 1)" class="px-3 py-1 border rounded disabled:opacity-50">Next</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import axios from 'axios';

const loading = ref(false);
const logs = ref([]);
const filters = reactive({
  action: '',
  actor_id: null,
});
const pagination = reactive({
  current_page: 1,
  from: 0,
  to: 0,
  total: 0,
  per_page: 50,
  next_page_url: null,
  prev_page_url: null,
});

const fetchLogs = async (page = 1) => {
  loading.value = true;
  try {
    const params = { page };
    if (filters.action) params.action = filters.action;
    if (filters.actor_id) params.actor_id = filters.actor_id;

    const resp = await axios.get('/admin/audit-logs', { params });
    logs.value = resp.data.data || [];
    pagination.current_page = resp.data.current_page;
    pagination.from = resp.data.from || 0;
    pagination.to = resp.data.to || 0;
    pagination.total = resp.data.total || 0;
    pagination.per_page = resp.data.per_page || 50;
    pagination.next_page_url = resp.data.next_page_url;
    pagination.prev_page_url = resp.data.prev_page_url;
  } catch (err) {
    console.error(err);
    alert(err.response?.data?.message || 'Failed to load audit logs');
  } finally {
    loading.value = false;
  }
};

const clearFilters = () => {
  filters.action = '';
  filters.actor_id = null;
  fetchLogs(1);
};

const formatDate = (value) => {
  if (!value) return '-';
  return new Date(value).toLocaleString();
};

const stringify = (value) => {
  if (!value) return '-';
  try {
    return JSON.stringify(value);
  } catch (e) {
    return String(value);
  }
};

onMounted(fetchLogs);
</script>


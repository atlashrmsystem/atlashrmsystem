<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-semibold text-gray-900">Candidates</h1>
      <button class="bg-[var(--color-brand-primary)] hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition-colors flex items-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 rtl:ml-2 rtl:mr-0" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
        </svg>
        Add Candidate
      </button>
    </div>

    <!-- Filters container -->
    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100 mb-6 flex flex-col md:flex-row gap-4">
      <div class="w-full md:w-1/2">
          <label class="block text-sm font-medium text-gray-700 mb-1">Search Candidates</label>
          <input 
              type="text" 
              v-model="filters.search" 
              @input="debounceSearch"
              placeholder="Search by name or email..." 
              class="w-full px-4 py-2 rounded-md border border-gray-300 focus:outline-none focus:ring-1 focus:ring-[var(--color-brand-primary)] text-sm"
          />
      </div>
      <div class="w-full md:w-1/4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
          <select v-model="filters.status" @change="fetchCandidates" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-[var(--color-brand-primary)] focus:border-[var(--color-brand-primary)] px-4 py-2">
              <option value="">All Statuses</option>
              <option value="new">New</option>
              <option value="screened">Screened</option>
              <option value="interviewed">Interviewed</option>
              <option value="offered">Offered</option>
              <option value="hired">Hired</option>
              <option value="rejected">Rejected</option>
          </select>
      </div>
    </div>

    <!-- Candidates List -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full whitespace-nowrap">
          <thead>
            <tr class="bg-gray-50 border-b border-gray-100 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              <th class="px-6 py-3">Candidate Info</th>
              <th class="px-6 py-3">Applied Data</th>
              <th class="px-6 py-3">Status</th>
              <th class="px-6 py-3">Date Added</th>
              <th class="px-6 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 text-sm">
            <tr v-for="candidate in candidates" :key="candidate.id" class="hover:bg-gray-50 transition-colors">
              <td class="px-6 py-4">
                  <div class="font-medium text-gray-900">{{ candidate.first_name }} {{ candidate.last_name }}</div>
                  <div class="text-gray-500 text-xs mt-1">{{ candidate.email }}</div>
                  <div class="text-gray-500 text-xs">{{ candidate.phone || 'No phone' }}</div>
              </td>
              <td class="px-6 py-4">
                  <div class="text-gray-900">{{ candidate.current_position || 'N/A' }}</div>
                  <div class="text-gray-500 text-xs mt-1">at {{ candidate.current_company || 'N/A' }}</div>
              </td>
              <td class="px-6 py-4">
                <span :class="getStatusClass(candidate.status)" class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full">
                  {{ formatStatus(candidate.status) }}
                </span>
              </td>
              <td class="px-6 py-4 text-gray-500">{{ formatDate(candidate.created_at) }}</td>
              <td class="px-6 py-4 text-right font-medium">
                <button class="text-[var(--color-brand-primary)] hover:text-blue-900">View Profile</button>
              </td>
            </tr>
            <tr v-if="candidates.length === 0 && !loading">
              <td colspan="5" class="px-6 py-8 text-center text-gray-500">No candidates found matching your criteria.</td>
            </tr>
            <tr v-if="loading">
              <td colspan="5" class="px-6 py-8 text-center text-gray-500">Loading candidates...</td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <!-- Pagination -->
      <div v-if="pagination.total > pagination.perPage" class="bg-white px-4 py-3 border-t border-gray-200 flex items-center justify-between sm:px-6">
        <div class="flex-1 flex justify-between sm:hidden">
          <button @click="changePage(pagination.current - 1)" :disabled="pagination.current === 1" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Previous</button>
          <button @click="changePage(pagination.current + 1)" :disabled="pagination.current === pagination.lastPage" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Next</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const candidates = ref([]);
const loading = ref(true);

const pagination = ref({
    current: 1,
    lastPage: 1,
    total: 0,
    perPage: 15
});

const filters = ref({
    status: '',
    search: ''
});

let searchTimeout;
const debounceSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        fetchCandidates(1);
    }, 500);
};

const fetchCandidates = async (page = 1) => {
    loading.value = true;
    try {
        const response = await axios.get('/candidates', {
            params: {
                page,
                ...(filters.value.status ? { status: filters.value.status } : {}),
                ...(filters.value.search ? { search: filters.value.search } : {}),
            }
        });

        const result = response.data;
        candidates.value = result?.data?.data || [];
        pagination.value = {
            current: result?.data?.current_page || 1,
            lastPage: result?.data?.last_page || 1,
            total: result?.data?.total || 0,
            perPage: result?.data?.per_page || 15
        };
    } catch (error) {
        console.error("Failed to fetch candidates", error);
    } finally {
        loading.value = false;
    }
};

const changePage = (page) => {
    if (page > 0 && page <= pagination.value.lastPage) {
        fetchCandidates(page);
    }
};

onMounted(() => {
    fetchCandidates();
});

const getStatusClass = (status) => {
  switch (status) {
    case 'new': return 'bg-blue-100 text-blue-800';
    case 'screened': return 'bg-indigo-100 text-indigo-800';
    case 'interviewed': return 'bg-purple-100 text-purple-800';
    case 'offered': return 'bg-yellow-100 text-yellow-800';
    case 'hired': return 'bg-green-100 text-green-800';
    case 'rejected': return 'bg-red-100 text-red-800';
    default: return 'bg-gray-100 text-gray-800';
  }
};

const formatStatus = (status) => {
    if (!status) return '';
    return status.charAt(0).toUpperCase() + status.slice(1);
}

const formatDate = (dateString) => {
  if (!dateString) return '-';
  const options = { year: 'numeric', month: 'short', day: 'numeric' };
  return new Date(dateString).toLocaleDateString(undefined, options);
};
</script>

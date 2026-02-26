<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-semibold text-gray-900">Job Postings</h1>
      <button @click="showModal = true" class="bg-[var(--color-brand-primary)] hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition-colors flex items-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 rtl:ml-2 rtl:mr-0" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
        </svg>
        New Job Posting
      </button>
    </div>

    <!-- Filters placeholder -->
    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100 mb-6 flex gap-4">
      <div class="w-1/3">
          <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
          <select v-model="filters.status" @change="fetchJobs" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-[var(--color-brand-primary)] focus:border-[var(--color-brand-primary)]">
              <option value="">All Statuses</option>
              <option value="draft">Draft</option>
              <option value="published">Published</option>
              <option value="closed">Closed</option>
          </select>
      </div>
      <!-- Add department filter similarly when department API is ready -->
    </div>

    <!-- Job Postings List -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full whitespace-nowrap">
          <thead>
            <tr class="bg-gray-50 border-b border-gray-100 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              <th class="px-6 py-3">Title</th>
              <th class="px-6 py-3">Department</th>
              <th class="px-6 py-3">Location</th>
              <th class="px-6 py-3">Status</th>
              <th class="px-6 py-3">Closes At</th>
              <th class="px-6 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 text-sm">
            <tr v-for="job in jobs" :key="job.id" class="hover:bg-gray-50 transition-colors">
              <td class="px-6 py-4 font-medium text-gray-900">{{ job.title }}</td>
              <td class="px-6 py-4 text-gray-500">{{ job.department?.name || 'N/A' }}</td>
              <td class="px-6 py-4 text-gray-500">{{ job.location || 'Remote/Any' }}</td>
              <td class="px-6 py-4">
                <span :class="getStatusClass(job.status)" class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full">
                  {{ job.status }}
                </span>
              </td>
              <td class="px-6 py-4 text-gray-500">{{ formatDate(job.closes_at) }}</td>
              <td class="px-6 py-4 text-right font-medium">
                <button class="text-[var(--color-brand-primary)] hover:text-blue-900 mr-3 rtl:ml-3 rtl:mr-0">Edit</button>
                <button v-if="job.status !== 'closed'" @click="closeJob(job.id)" class="text-red-600 hover:text-red-900">Close</button>
              </td>
            </tr>
            <tr v-if="jobs.length === 0 && !loading">
              <td colspan="6" class="px-6 py-8 text-center text-gray-500">No job postings found.</td>
            </tr>
            <tr v-if="loading">
              <td colspan="6" class="px-6 py-8 text-center text-gray-500">Loading...</td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <!-- Pagination (simplified) -->
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

const jobs = ref([]);
const loading = ref(true);
const showModal = ref(false);

const pagination = ref({
    current: 1,
    lastPage: 1,
    total: 0,
    perPage: 15
});

const filters = ref({
    status: ''
});

const fetchJobs = async (page = 1) => {
    loading.value = true;
    try {
        const response = await axios.get('/job-postings', {
            params: {
                page,
                ...(filters.value.status ? { status: filters.value.status } : {}),
            },
        });

        const result = response.data;
        jobs.value = result?.data?.data || [];
        pagination.value = {
            current: result?.data?.current_page || 1,
            lastPage: result?.data?.last_page || 1,
            total: result?.data?.total || 0,
            perPage: result?.data?.per_page || 15
        };
    } catch (error) {
        console.error("Failed to fetch jobs", error);
    } finally {
        loading.value = false;
    }
};

const closeJob = async (id) => {
    if(!confirm('Are you sure you want to close this job posting?')) return;
    
    try {
        await axios.delete(`/job-postings/${id}`);
        fetchJobs(pagination.value.current);
    } catch (error) {
        console.error("Failed to close job", error);
    }
}

const changePage = (page) => {
    if (page > 0 && page <= pagination.value.lastPage) {
        fetchJobs(page);
    }
};

onMounted(() => {
    fetchJobs();
});

const getStatusClass = (status) => {
  switch (status) {
    case 'published': return 'bg-green-100 text-green-800';
    case 'closed': return 'bg-gray-100 text-gray-800';
    case 'draft': return 'bg-yellow-100 text-yellow-800';
    default: return 'bg-gray-100 text-gray-800';
  }
};

const formatDate = (dateString) => {
  if (!dateString) return '-';
  const options = { year: 'numeric', month: 'short', day: 'numeric' };
  return new Date(dateString).toLocaleDateString(undefined, options);
};
</script>

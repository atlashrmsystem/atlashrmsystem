<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Employees</h1>
      <router-link 
        to="/employees/create" 
        class="bg-[var(--color-brand-primary)] text-white px-4 py-2 rounded-md hover:bg-[var(--color-brand-hover)] transition-colors"
      >
        + Add Employee
      </router-link>
    </div>

    <!-- Stats Row (KPIs) -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
      <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
        <h3 class="text-gray-500 text-sm font-medium">Total Employees</h3>
        <p class="text-3xl font-bold text-gray-900 mt-2">{{ totalEmployees }}</p>
      </div>
      <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
        <h3 class="text-gray-500 text-sm font-medium">Active Contracts</h3>
        <p class="text-3xl font-bold text-[var(--color-brand-success)] mt-2">{{ activeContracts }}</p>
      </div>
      <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 border-l-4 border-l-[var(--color-brand-danger)]">
        <h3 class="text-gray-500 text-sm font-medium">Expiring Soon</h3>
        <p class="text-3xl font-bold text-[var(--color-brand-danger)] mt-2">{{ expiringSoonContracts }}</p>
      </div>
      <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 border-l-4 border-l-[var(--color-brand-warning)]">
        <h3 class="text-gray-500 text-sm font-medium">Pending Leave</h3>
        <p class="text-3xl font-bold text-[var(--color-brand-warning)] mt-2">--</p>
      </div>
    </div>

    <!-- Data Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contract End Date</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="emp in employees" :key="emp.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="flex items-center">
                <div class="h-10 w-10 rounded-full bg-[var(--color-brand-bg)] overflow-hidden flex items-center justify-center text-[var(--color-brand-primary)] font-bold border border-gray-100">
                  <img v-if="emp.photo_url" :src="emp.photo_url" class="h-full w-full object-cover" />
                  <span v-else>{{ emp.full_name.charAt(0) }}</span>
                </div>
                <router-link :to="`/employees/${emp.id}`" class="ml-4 hover:underline">
                  <div class="text-sm font-medium text-gray-900">{{ emp.full_name }}</div>
                  <div class="text-sm text-gray-500">{{ emp.job_title }}</div>
                </router-link>
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ emp.department }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ getContractEndDate(emp) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span 
                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                :class="getStatusClass(emp)"
              >
                {{ getStatusText(emp) }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <router-link :to="`/employees/${emp.id}/edit`" class="text-[var(--color-brand-primary)] hover:text-[var(--color-brand-hover)] mr-3">Edit</router-link>
              <button @click="generateContract(emp.id)" class="text-[var(--color-brand-success)] hover:text-green-700 mr-3">Contract</button>
              <button
                @click="deleteEmployee(emp)"
                :disabled="deletingEmployeeId === emp.id"
                class="text-[var(--color-brand-danger)] hover:text-red-700 disabled:opacity-50"
              >
                {{ deletingEmployeeId === emp.id ? 'Deleting...' : 'Delete' }}
              </button>
            </td>
          </tr>
          
          <tr v-if="employees.length === 0 && !loading">
            <td colspan="5" class="px-6 py-10 text-center text-gray-500">
              No employees found.
            </td>
          </tr>
          <tr v-if="loading">
             <td colspan="5" class="px-6 py-10 text-center text-gray-500">
              Loading...
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="lastPage > 1" class="mt-4 flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6 rounded-lg">
      <div class="flex flex-1 justify-between sm:hidden">
        <button @click="fetchEmployees(currentPage - 1)" :disabled="currentPage === 1" class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50">Previous</button>
        <button @click="fetchEmployees(currentPage + 1)" :disabled="currentPage === lastPage" class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50">Next</button>
      </div>
      <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
        <div>
          <p class="text-sm text-gray-700">
            Showing
            <span class="font-medium">{{ (currentPage - 1) * 15 + 1 }}</span>
            to
            <span class="font-medium">{{ Math.min(currentPage * 15, totalEmployees) }}</span>
            of
            <span class="font-medium">{{ totalEmployees }}</span>
            results
          </p>
        </div>
        <div>
          <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
            <button @click="fetchEmployees(currentPage - 1)" :disabled="currentPage === 1" class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50">
              <span class="sr-only">Previous</span>
              <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" /></svg>
            </button>
            <button 
              v-for="page in lastPage" 
              :key="page" 
              @click="fetchEmployees(page)"
              class="relative inline-flex items-center px-4 py-2 text-sm font-semibold focus:z-20 focus:outline-offset-0"
              :class="page === currentPage ? 'z-10 bg-[var(--color-brand-primary)] text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-brand-primary)]' : 'text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50'"
            >
              {{ page }}
            </button>
            <button @click="fetchEmployees(currentPage + 1)" :disabled="currentPage === lastPage" class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50">
              <span class="sr-only">Next</span>
              <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" /></svg>
            </button>
          </nav>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const employees = ref([]);
const totalEmployees = ref(0);
const activeContracts = ref(0);
const expiringSoonContracts = ref(0);
const loading = ref(true);
const deletingEmployeeId = ref(null);
const currentPage = ref(1);
const lastPage = ref(1);

const fetchEmployees = async (page = 1) => {
  try {
    loading.value = true;
    const response = await axios.get(`/employees?page=${page}`);
    employees.value = response.data.data;
    totalEmployees.value = response.data.total;
    activeContracts.value = response.data.active_contracts || 0;
    expiringSoonContracts.value = response.data.expiring_soon_contracts || 0;
    currentPage.value = response.data.current_page;
    lastPage.value = response.data.last_page;
  } catch (error) {
    console.error('Failed fetching employees:', error);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchEmployees();
});

const getContractEndDate = (emp) => {
  if (emp.contracts && emp.contracts.length > 0) {
    return emp.contracts[0].end_date;
  }
  return 'No Contract';
};

const getStatusText = (emp) => {
  const normalizedStatus = String(emp.status || 'active').toLowerCase();
  if (normalizedStatus === 'inactive') return 'Inactive';
  return emp.contracts && emp.contracts.length > 0 ? 'Active' : 'Pending Contract';
};

const getStatusClass = (emp) => {
  const normalizedStatus = String(emp.status || 'active').toLowerCase();
  if (normalizedStatus === 'inactive') {
    return 'bg-red-100 text-red-700';
  }
  if (emp.contracts && emp.contracts.length > 0) {
    return 'bg-green-100 text-[var(--color-brand-success)]';
  }
  return 'bg-yellow-100 text-[var(--color-brand-warning)]';
};

const generateContract = async (empId) => {
  // Mock generating contract logic: Start today, end 2 years from today
  const today = new Date();
  const endDate = new Date();
  endDate.setFullYear(today.getFullYear() + 2);
  
  try {
    await axios.post('/contracts', {
      employee_id: empId,
      start_date: today.toISOString().split('T')[0],
      end_date: endDate.toISOString().split('T')[0]
    });
    alert('Contract generated and PDF created successfully!');
    fetchEmployees();
  } catch (err) {
    alert('Failed to generate contract: ' + (err.response?.data?.error || err.message));
  }
};

const deleteEmployee = async (employee) => {
  const confirmed = window.confirm(`Delete employee "${employee.full_name}"? This cannot be undone.`);
  if (!confirmed) return;

  deletingEmployeeId.value = employee.id;
  try {
    await axios.delete(`/employees/${employee.id}`);
    await fetchEmployees(currentPage.value);
    alert('Employee deleted successfully.');
  } catch (err) {
    const message = err?.response?.data?.message || err?.response?.data?.error || err.message || 'Failed to delete employee.';
    alert(`Failed to delete employee: ${message}`);
  } finally {
    deletingEmployeeId.value = null;
  }
};
</script>

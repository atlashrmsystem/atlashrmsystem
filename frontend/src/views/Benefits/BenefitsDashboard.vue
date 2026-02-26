<template>
  <div>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">Benefits Administration</h1>
        <button @click="showEnrollModal = true" class="bg-[var(--color-brand-primary)] text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors text-sm font-medium">
            + Enroll Employee
        </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Active Benefit Types -->
        <div class="lg:col-span-1 bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-900">Available Benefits</h2>
            </div>
            <div class="p-0">
                <div v-if="loadingTypes" class="p-6 text-center text-gray-500">Loading benefits...</div>
                <div v-else-if="benefitTypes.length === 0" class="p-6 text-center text-gray-500 text-sm">
                    No active benefits configured.
                </div>
                <ul v-else class="divide-y divide-gray-100">
                    <li v-for="benefit in benefitTypes" :key="benefit.id" class="p-6 hover:bg-gray-50 transition-colors">
                        <div class="flex items-start">
                            <div class="h-10 w-10 flex-shrink-0 bg-blue-100 text-blue-600 rounded-md flex items-center justify-center mr-4 rtl:ml-4 rtl:mr-0">
                                <svg v-if="benefit.type === 'health_insurance'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                <svg v-else-if="benefit.type === 'flight_ticket'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-medium text-gray-900">{{ benefit.name }}</h3>
                                <p class="text-xs text-gray-500 mt-1">{{ benefit.description }}</p>
                                <div v-if="benefit.eligibility_rules && Object.keys(benefit.eligibility_rules).length > 0" class="mt-2 text-[10px] bg-gray-100 text-gray-600 px-2 py-1 rounded inline-block">
                                    Rule: {{ formatRules(benefit.eligibility_rules) }}
                                </div>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Recent Enrollments -->
        <div class="lg:col-span-2 bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-900">Recent Enrollments</h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                            <th scope="col" class="px-6 py-3 text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Benefit</th>
                            <th scope="col" class="px-6 py-3 text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Coverage Period</th>
                            <th scope="col" class="px-6 py-3 text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-if="loadingEnrollments">
                            <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">Loading enrollments...</td>
                        </tr>
                        <tr v-else-if="enrollments.length === 0">
                            <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">No recent enrollments found.</td>
                        </tr>
                        <tr v-else v-for="enrollment in enrollments" :key="enrollment.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="h-8 w-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-xs mr-3 rtl:ml-3 rtl:mr-0">
                                        {{ enrollment.employee.full_name.charAt(0) }}
                                    </div>
                                    <div class="text-sm font-medium text-gray-900">{{ enrollment.employee.full_name }}</div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-900">{{ enrollment.benefit_type.name }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ formatDate(enrollment.start_date) }} - {{ enrollment.end_date ? formatDate(enrollment.end_date) : 'Ongoing' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="getStatusBadge(enrollment.status)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full border">
                                    {{ enrollment.status.charAt(0).toUpperCase() + enrollment.status.slice(1) }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Enroll Modal -->
    <div v-if="showEnrollModal" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl text-left">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-gray-900">Enroll Employee</h3>
                <button @click="showEnrollModal = false" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 p-1 rounded-full transition-colors">&times;</button>
            </div>
            
            <form @submit.prevent="saveEnrollment" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Employee</label>
                    <div class="relative">
                        <input v-model="employeeSearch" @input="searchEmployees" type="text" placeholder="Search by name..." class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                        <div v-if="searchResults.length > 0" class="absolute z-10 w-full mt-1 bg-white border rounded-lg shadow-lg max-h-48 overflow-y-auto">
                            <div v-for="emp in searchResults" :key="emp.id" @click="selectEmployee(emp)" class="px-4 py-2 hover:bg-blue-50 cursor-pointer text-sm border-b last:border-0">
                                <span class="font-bold">{{ emp.full_name }}</span>
                                <span class="text-xs text-gray-500 ml-2">({{ emp.department }})</span>
                            </div>
                        </div>
                    </div>
                    <div v-if="selectedEmployee" class="mt-2 p-2 bg-blue-50 rounded-lg flex items-center justify-between">
                        <span class="text-sm font-bold text-blue-700">{{ selectedEmployee.full_name }}</span>
                        <button @click="selectedEmployee = null; employeeSearch = ''" class="text-blue-500 hover:text-blue-700 text-xs">Clear</button>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Benefit Type</label>
                    <select v-model="enrollmentForm.benefit_type_id" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm appearance-none bg-white">
                        <option value="">Select Benefit...</option>
                        <option v-for="type in benefitTypes" :key="type.id" :value="type.id">{{ type.name }}</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Start Date</label>
                        <input v-model="enrollmentForm.start_date" type="date" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">End Date (Optional)</label>
                        <input v-model="enrollmentForm.end_date" type="date" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-8">
                    <button type="button" @click="showEnrollModal = false" class="px-5 py-2 text-sm font-bold text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200">Cancel</button>
                    <button type="submit" :disabled="saving || !selectedEmployee" class="px-5 py-2 text-sm font-bold text-white bg-[var(--color-brand-primary)] rounded-lg hover:bg-blue-700 disabled:opacity-50">
                        {{ saving ? 'Enrolling...' : 'Confirm Enrollment' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const benefitTypes = ref([]);
const enrollments = ref([]);
const loadingTypes = ref(true);
const loadingEnrollments = ref(true);
const showEnrollModal = ref(false);
const saving = ref(false);

const employeeSearch = ref('');
const searchResults = ref([]);
const selectedEmployee = ref(null);

const enrollmentForm = ref({
    employee_id: null,
    benefit_type_id: '',
    start_date: new Date().toISOString().split('T')[0],
    end_date: ''
});

const fetchData = async () => {
    try {
        const [typesRes, enrollRes] = await Promise.all([
            axios.get('/benefit-types/active'),
            axios.get('/benefit-enrollments')
        ]);
        benefitTypes.value = typesRes.data.data;
        enrollments.value = enrollRes.data.data;
    } catch (error) {
        console.error("Error fetching benefits data:", error);
    } finally {
        loadingTypes.value = false;
        loadingEnrollments.value = false;
    }
};

const searchEmployees = async () => {
    if (employeeSearch.value.length < 2) {
        searchResults.value = [];
        return;
    }
    try {
        // Simplified search, normally we'd have a search endpoint
        const res = await axios.get('/employees');
        searchResults.value = res.data.data.filter(emp => 
            emp.full_name.toLowerCase().includes(employeeSearch.value.toLowerCase())
        );
    } catch (error) {
        console.error("Employee search failed:", error);
    }
};

const selectEmployee = (emp) => {
    selectedEmployee.value = emp;
    enrollmentForm.value.employee_id = emp.id;
    searchResults.value = [];
    employeeSearch.value = emp.full_name;
};

const saveEnrollment = async () => {
    saving.value = true;
    try {
        await axios.post('/benefit-enrollments', enrollmentForm.value);
        showEnrollModal.value = false;
        // Reset form
        enrollmentForm.value = {
            employee_id: null,
            benefit_type_id: '',
            start_date: new Date().toISOString().split('T')[0],
            end_date: ''
        };
        selectedEmployee.value = null;
        employeeSearch.value = '';
        await fetchData();
    } catch (error) {
        alert('Enrollment failed: ' + (error.response?.data?.message || error.message));
    } finally {
        saving.value = false;
    }
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString();
};

const formatRules = (rules) => {
    if (rules.requires_contract_type) {
        return `Requires Contract: ${rules.requires_contract_type}`;
    }
    return 'Custom Rules Apply';
};

const getStatusBadge = (status) => {
    const badges = {
        'active': 'bg-green-50 text-green-700 border-green-100',
        'cancelled': 'bg-rose-50 text-rose-700 border-rose-100',
        'expired': 'bg-slate-100 text-slate-700 border-slate-200'
    };
    return badges[status] || badges['active'];
};

onMounted(() => {
    fetchData();
});
</script>

<template>
  <div class="max-w-7xl mx-auto">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Performance Management</h1>
            <p class="text-gray-500 mt-1">Track your growth, set goals, and review feedback.</p>
        </div>
        <div class="flex gap-3">
            <button v-if="activeCycle" @click="showGoalModal = true" class="inline-flex items-center px-4 py-2 bg-[var(--color-brand-primary)] text-white rounded-lg hover:bg-blue-700 transition-all shadow-sm font-semibold">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                Create New Goal
            </button>
        </div>
    </div>

    <!-- Active Cycle Alert -->
    <div v-if="activeCycle" class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-100 p-5 mb-8 rounded-xl shadow-sm flex items-center justify-between">
        <div class="flex items-center">
            <div class="p-3 bg-blue-100 rounded-lg text-blue-600 mr-4">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-bold text-gray-900 leading-tight">{{ activeCycle.name }}</h3>
                <p class="text-sm text-blue-700">Ends on <span class="font-bold">{{ formatDate(activeCycle.end_date) }}</span></p>
            </div>
        </div>
        <div class="hidden md:block">
            <span class="px-3 py-1 bg-blue-600 text-white text-xs font-bold rounded-full uppercase tracking-wider shadow-sm">Active Cycle</span>
        </div>
    </div>
    
    <div v-else class="bg-amber-50 border border-amber-100 p-6 mb-8 rounded-xl flex items-center">
        <div class="p-3 bg-amber-100 rounded-lg text-amber-600 mr-4">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
        </div>
        <div>
            <h3 class="text-lg font-bold text-amber-800">Review Cycle Inactive</h3>
            <p class="text-sm text-amber-700">No active performance cycle found. Goals and appraisals are currently disabled.</p>
            <button v-if="isAdmin" @click="showCycleModal = true" class="mt-3 px-4 py-2 bg-amber-600 text-white text-xs font-bold rounded-lg hover:bg-amber-700 transition shadow-sm">
                + Create New Performance Cycle
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- My Goals Section -->
        <div class="lg:col-span-2 space-y-6">
            <div class="flex items-center justify-between px-2">
                <h2 class="text-xl font-bold text-gray-900">Professional Goals</h2>
            </div>
            
            <div v-if="loading" class="space-y-4">
                <div v-for="i in 3" :key="i" class="h-32 bg-gray-100 rounded-xl animate-pulse"></div>
            </div>
            
            <div v-else-if="goals.length === 0" class="bg-white rounded-2xl border-2 border-dashed border-gray-200 p-12 text-center">
                <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-400">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900">No goals set yet</h3>
                <p class="text-sm text-gray-500 mt-1 max-w-xs mx-auto">Set clear objectives to track your progress and performance throughout this cycle.</p>
                <button v-if="activeCycle" @click="showGoalModal = true" class="mt-6 text-[var(--color-brand-primary)] font-bold hover:underline">Add your first goal &rarr;</button>
            </div>
            
            <div v-else class="grid grid-cols-1 gap-4">
                <div v-for="goal in goals" :key="goal.id" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 hover:shadow-md transition-all group">
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex-1">
                            <h3 class="text-lg font-bold text-gray-900 group-hover:text-[var(--color-brand-primary)] transition-colors">{{ goal.title }}</h3>
                            <p class="text-sm text-gray-500 mt-1 leading-relaxed">{{ goal.description }}</p>
                        </div>
                        <span :class="getStatusBadge(goal.status)" class="px-3 py-1 rounded-full text-[10px] font-bold border uppercase tracking-widest ml-4">
                            {{ goal.status.replace('_', ' ') }}
                        </span>
                    </div>
                    
                    <div class="mt-6">
                        <div class="flex justify-between items-end mb-2">
                            <span class="text-xs font-bold text-gray-400 uppercase">Current Progress</span>
                            <span class="text-sm font-extrabold text-gray-900">{{ goal.progress || 0 }}%</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2.5 overflow-hidden">
                            <div class="bg-[var(--color-brand-primary)] h-full rounded-full transition-all duration-1000" :style="{ width: (goal.progress || 0) + '%' }"></div>
                        </div>
                    </div>
                    
                    <div class="mt-6 pt-4 border-t border-gray-50 flex items-center justify-between">
                        <div class="flex items-center text-xs text-gray-500 font-medium">
                            <svg class="w-4 h-4 mr-1.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                            Weight: {{ goal.weight }}%
                        </div>
                        <button @click="updateProgress(goal)" class="text-sm font-bold text-[var(--color-brand-primary)] hover:text-blue-700">Update &rarr;</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Appraisals & Feedback Sidebar -->
        <div class="space-y-8">
            <section>
                <div class="flex justify-between items-center px-1 mb-4">
                    <h2 class="text-xl font-bold text-gray-900">Appraisals</h2>
                    <span class="p-1 px-2.5 bg-purple-100 text-purple-700 text-[10px] font-extrabold rounded-full uppercase">{{ appraisals.length }} Pending</span>
                </div>
                
                <div v-if="loading" class="space-y-3">
                    <div v-for="i in 2" :key="i" class="h-24 bg-gray-100 rounded-xl animate-pulse"></div>
                </div>
                
                <div v-else-if="appraisals.length === 0" class="p-8 bg-gray-50 rounded-2xl text-center border-2 border-dashed border-gray-200">
                    <p class="text-sm text-gray-400 font-medium">No reviews are currently required from you.</p>
                </div>
                
                <div v-else class="space-y-3">
                    <div v-for="appraisal in appraisals" :key="appraisal.id" class="p-4 bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-all flex items-center">
                        <div class="h-12 w-12 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center font-bold text-lg mr-4">
                            {{ appraisal.employee?.full_name?.charAt(0) || 'U' }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-sm font-bold text-gray-900 truncate">{{ appraisal.employee?.full_name }}</h3>
                            <p class="text-xs text-gray-500 truncate">{{ appraisal.form?.name }}</p>
                        </div>
                        <button class="ml-3 px-4 py-2 bg-purple-600 text-white text-xs font-bold rounded-lg hover:bg-purple-700 transition shadow-sm">
                            {{ appraisal.status === 'draft' ? 'Start' : 'Review' }}
                        </button>
                    </div>
                </div>
            </section>
            
            <section class="bg-gradient-to-br from-slate-900 to-indigo-900 rounded-2xl p-6 text-white shadow-xl relative overflow-hidden">
                <div class="relative z-10">
                    <h3 class="text-lg font-bold mb-2">Need Help?</h3>
                    <p class="text-sm text-indigo-100 leading-relaxed mb-6">Learn more about our evaluation cycles and how to set SMART goals in the handbook.</p>
                    <button class="w-full py-2.5 bg-white/10 hover:bg-white/20 border border-white/20 rounded-lg text-sm font-bold transition">View Documentation</button>
                </div>
                <div class="absolute -right-4 -bottom-4 opacity-10">
                    <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"></path></svg>
                </div>
            </section>
        </div>
    </div>

    <!-- Goal Creation Modal -->
    <div v-if="showGoalModal" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl text-left">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-gray-900">Create New Goal</h3>
                <button @click="showGoalModal = false" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 p-1 rounded-full transition-colors">&times;</button>
            </div>
            
            <form @submit.prevent="saveGoal" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Goal Title</label>
                    <input v-model="newGoal.title" type="text" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm" placeholder="e.g., Complete Advanced Vue Certification">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Description</label>
                    <textarea v-model="newGoal.description" rows="3" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm" placeholder="Briefly describe the outcome..."></textarea>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Weightage (%)</label>
                    <input v-model.number="newGoal.weight" type="number" min="0" max="100" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                    <p class="text-[10px] text-gray-400 mt-1">Relative importance of this goal compared to others.</p>
                </div>
                
                <div class="flex justify-end gap-3 mt-8">
                    <button type="button" @click="showGoalModal = false" class="px-5 py-2 text-sm font-bold text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200">Cancel</button>
                    <button type="submit" :disabled="saving" class="px-5 py-2 text-sm font-bold text-white bg-[var(--color-brand-primary)] rounded-lg hover:bg-blue-700 disabled:opacity-50">
                        {{ saving ? 'Saving...' : 'Create Goal' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Update Progress Modal -->
    <div v-if="selectedGoalForUpdate" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl text-left">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-gray-900">Update Progress</h3>
                <button @click="selectedGoalForUpdate = null" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 p-1 rounded-full transition-colors">&times;</button>
            </div>
            
            <p class="text-sm font-bold text-gray-900 mb-2">{{ selectedGoalForUpdate.title }}</p>
            
            <form @submit.prevent="saveProgress" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Current Progress ({{ progressUpdate.progress }}%)</label>
                    <input v-model.number="progressUpdate.progress" type="range" min="0" max="100" step="5" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer">
                    <div class="flex justify-between text-[10px] font-bold text-gray-400 mt-1 uppercase">
                        <span>0%</span>
                        <span>50%</span>
                        <span>100%</span>
                    </div>
                </div>
                
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Status</label>
                    <select v-model="progressUpdate.status" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm appearance-none bg-white">
                        <option value="pending">Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="achieved">Achieved / Completed</option>
                        <option value="missed">Missed / Terminated</option>
                    </select>
                </div>
                
                <div class="flex justify-end gap-3 mt-8">
                    <button type="button" @click="selectedGoalForUpdate = null" class="px-5 py-2 text-sm font-bold text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200">Cancel</button>
                    <button type="submit" :disabled="saving" class="px-5 py-2 text-sm font-bold text-white bg-[var(--color-brand-primary)] rounded-lg hover:bg-blue-700 disabled:opacity-50">
                        {{ saving ? 'Updating...' : 'Save Changes' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Cycle Management Modal (Admin Only) -->
    <div v-if="showCycleModal" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl text-left">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-gray-900">Create Performance Cycle</h3>
                <button @click="showCycleModal = false" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 p-1 rounded-full transition-colors">&times;</button>
            </div>
            
            <form @submit.prevent="saveCycle" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Cycle Name</label>
                    <input v-model="newCycle.name" type="text" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm" placeholder="e.g., 2024 Annual Review">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Start Date</label>
                        <input v-model="newCycle.start_date" type="date" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">End Date</label>
                        <input v-model="newCycle.end_date" type="date" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                    </div>
                </div>
                <div class="flex items-center gap-2 mt-2">
                    <input v-model="newCycle.is_active" type="checkbox" id="is_active_check" class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500">
                    <label for="is_active_check" class="text-sm font-medium text-gray-700">Set as active cycle immediately</label>
                </div>
                
                <div class="flex justify-end gap-3 mt-8">
                    <button type="button" @click="showCycleModal = false" class="px-5 py-2 text-sm font-bold text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200">Cancel</button>
                    <button type="submit" :disabled="saving" class="px-5 py-2 text-sm font-bold text-white bg-[var(--color-brand-primary)] rounded-lg hover:bg-blue-700 disabled:opacity-50">
                        {{ saving ? 'Creating...' : 'Create Cycle' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

const authUser = computed(() => JSON.parse(sessionStorage.getItem('auth_user') || '{}'));
const isAdmin = computed(() => {
    const roles = authUser.value?.role_names || authUser.value?.roles?.map(role => role.name) || [];
    const normalizedRoles = roles.map(role => String(role).toLowerCase().trim());
    return normalizedRoles.some(role =>
        ['admin', 'superadmin', 'super-admin', 'super_admin', 'super admin'].includes(role)
    );
});

const activeCycle = ref(null);
const goals = ref([]);
const appraisals = ref([]);
const loading = ref(true);
const saving = ref(false);

const showGoalModal = ref(false);
const showCycleModal = ref(false);
const selectedGoalForUpdate = ref(null);

const newGoal = ref({
    title: '',
    description: '',
    weight: 25
});

const progressUpdate = ref({
    progress: 0,
    status: 'pending'
});

const newCycle = ref({
    name: '',
    start_date: '',
    end_date: '',
    is_active: true
});

const fetchData = async () => {
    loading.value = true;
    try {
        const cycleResponse = await axios.get('/performance-cycles/active');
        activeCycle.value = cycleResponse.data.data;

        if (activeCycle.value) {
            const empId = authUser.value.employee?.id || 1;
            
            const [goalsRes, appraisalsRes] = await Promise.all([
                axios.get('/goals', { params: { performance_cycle_id: activeCycle.value.id, employee_id: empId } }),
                axios.get('/appraisals', { params: { performance_cycle_id: activeCycle.value.id } })
            ]);

            goals.value = goalsRes.data.data;
            appraisals.value = appraisalsRes.data.data;
        }
    } catch (error) {
        console.error("Error fetching performance data:", error);
    } finally {
        loading.value = false;
    }
};

const saveGoal = async () => {
    if (!activeCycle.value) return;
    
    saving.value = true;
    try {
        await axios.post('/goals', {
            ...newGoal.value,
            performance_cycle_id: activeCycle.value.id,
            employee_id: authUser.value.employee?.id || 1
        });
        showGoalModal.value = false;
        newGoal.value = { title: '', description: '', weight: 25 };
        await fetchData();
    } catch (error) {
        alert('Failed to save goal: ' + (error.response?.data?.message || error.message));
    } finally {
        saving.value = false;
    }
};

const updateProgress = (goal) => {
    selectedGoalForUpdate.value = goal;
    progressUpdate.value = {
        progress: goal.progress || 0,
        status: goal.status || 'pending'
    };
};

const saveProgress = async () => {
    if (!selectedGoalForUpdate.value) return;
    
    saving.value = true;
    try {
        await axios.patch(`/goals/${selectedGoalForUpdate.value.id}`, progressUpdate.value);
        selectedGoalForUpdate.value = null;
        await fetchData();
    } catch (error) {
        alert('Failed to update progress: ' + (error.response?.data?.message || error.message));
    } finally {
        saving.value = false;
    }
};

const saveCycle = async () => {
    saving.value = true;
    try {
        await axios.post('/performance-cycles', newCycle.value);
        showCycleModal.value = false;
        newCycle.value = { name: '', start_date: '', end_date: '', is_active: true };
        await fetchData();
    } catch (error) {
        alert('Failed to create cycle: ' + (error.response?.data?.message || error.message));
    } finally {
        saving.value = false;
    }
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' });
};

const getStatusBadge = (status) => {
    const badges = {
        'pending': 'bg-slate-100 text-slate-700 border-slate-200',
        'in_progress': 'bg-blue-50 text-blue-700 border-blue-100',
        'achieved': 'bg-emerald-50 text-emerald-700 border-emerald-100',
        'missed': 'bg-rose-50 text-rose-700 border-rose-100'
    };
    return badges[status] || badges['pending'];
};

onMounted(() => {
    fetchData();
});
</script>

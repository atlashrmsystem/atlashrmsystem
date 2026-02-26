<template>
  <div class="max-w-5xl mx-auto employee-form-shell">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ isEdit ? 'Edit Employee' : 'Add New Employee' }}</h1>
        <p class="text-sm text-gray-500 mt-1">{{ isEdit ? 'Update profile, compliance, and job details.' : 'Create a complete employee profile.' }}</p>
      </div>
      <router-link 
        to="/employees" 
        class="inline-flex items-center gap-1 text-sm px-3 py-2 rounded-md border border-gray-200 text-gray-600 hover:text-gray-800 hover:bg-gray-50 font-medium transition-colors"
      >
        &larr; Back to List
      </router-link>
    </div>

    <div class="bg-white shadow-sm border border-gray-200 rounded-xl p-8 employee-form-card">
      <form @submit.prevent="submitForm" class="space-y-6">
        <!-- Photo Upload Section -->
        <div class="flex flex-col items-center justify-center border-2 border-dashed border-gray-200 rounded-xl p-5 bg-gradient-to-b from-slate-50 to-white mb-8">
            <div class="h-32 w-32 rounded-full bg-gray-200 overflow-hidden mb-4 border-2 border-white shadow-sm flex items-center justify-center">
              <img v-if="photoPreview" :src="photoPreview" class="h-full w-full object-cover" />
              <img v-else-if="form.photo_url" :src="form.photo_url" class="h-full w-full object-cover" />
              <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
              </svg>
            </div>
            <label class="cursor-pointer bg-white px-3 py-1.5 border border-gray-300 rounded text-xs font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
              {{ photoFile ? 'Change Photo' : 'Upload Photo' }}
              <input type="file" class="hidden" @change="onFileChange" accept="image/*" />
            </label>
            <p class="text-[10px] text-gray-400 mt-2 text-center">JPG, PNG (Max 2MB)</p>
        </div>

          <!-- Main Form Fields -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <div>
            <label class="block text-sm font-medium text-gray-700">Employee PIN</label>
            <input type="text" v-model="form.employee_pin" placeholder="Auto-generated if empty" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[var(--color-brand-primary)] focus:ring-[var(--color-brand-primary)] sm:text-sm border p-2" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Status</label>
            <select v-model="form.status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[var(--color-brand-primary)] focus:ring-[var(--color-brand-primary)] sm:text-sm border p-2">
              <option value="active">ACTIVE</option>
              <option value="inactive">INACTIVE</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Full Name</label>
            <input type="text" v-model="form.full_name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[var(--color-brand-primary)] focus:ring-[var(--color-brand-primary)] sm:text-sm border p-2" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Email Address</label>
            <input type="email" v-model="form.email" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[var(--color-brand-primary)] focus:ring-[var(--color-brand-primary)] sm:text-sm border p-2" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Phone</label>
            <input type="text" v-model="form.phone" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[var(--color-brand-primary)] focus:ring-[var(--color-brand-primary)] sm:text-sm border p-2" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Gender</label>
            <select v-model="form.gender" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[var(--color-brand-primary)] focus:ring-[var(--color-brand-primary)] sm:text-sm border p-2">
              <option value="">Select</option>
              <option value="male">Male</option>
              <option value="female">Female</option>
              <option value="other">Other</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Date Of Birth</label>
            <input type="date" v-model="form.date_of_birth" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[var(--color-brand-primary)] focus:ring-[var(--color-brand-primary)] sm:text-sm border p-2" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Emirates ID</label>
            <input type="text" v-model="form.emirates_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[var(--color-brand-primary)] focus:ring-[var(--color-brand-primary)] sm:text-sm border p-2" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Nationality</label>
            <input type="text" v-model="form.nationality" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[var(--color-brand-primary)] focus:ring-[var(--color-brand-primary)] sm:text-sm border p-2" />
          </div>

          <!-- Documents -->
          <div>
            <label class="block text-sm font-medium text-gray-700">Passport Number</label>
            <input type="text" v-model="form.passport_number" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[var(--color-brand-primary)] focus:ring-[var(--color-brand-primary)] sm:text-sm border p-2" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Passport Issue Date</label>
            <input type="date" v-model="form.passport_issue_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[var(--color-brand-primary)] focus:ring-[var(--color-brand-primary)] sm:text-sm border p-2" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Passport Expiry</label>
            <input type="date" v-model="form.passport_expiry" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[var(--color-brand-primary)] focus:ring-[var(--color-brand-primary)] sm:text-sm border p-2" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Visa Status</label>
            <select v-model="form.visa_status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[var(--color-brand-primary)] focus:ring-[var(--color-brand-primary)] sm:text-sm border p-2">
              <option value="Active">Active</option>
              <option value="Processing">Processing</option>
              <option value="Expired">Expired</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Visa Issue Date</label>
            <input type="date" v-model="form.visa_issue_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[var(--color-brand-primary)] focus:ring-[var(--color-brand-primary)] sm:text-sm border p-2" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Visa Expiry</label>
            <input type="date" v-model="form.visa_expiry" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[var(--color-brand-primary)] focus:ring-[var(--color-brand-primary)] sm:text-sm border p-2" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Insurance Start Date</label>
            <input type="date" v-model="form.insurance_start_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[var(--color-brand-primary)] focus:ring-[var(--color-brand-primary)] sm:text-sm border p-2" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Insurance End Date</label>
            <input type="date" v-model="form.insurance_end_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[var(--color-brand-primary)] focus:ring-[var(--color-brand-primary)] sm:text-sm border p-2" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">EID Issue Date</label>
            <input type="date" v-model="form.emirates_id_issue_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[var(--color-brand-primary)] focus:ring-[var(--color-brand-primary)] sm:text-sm border p-2" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">EID Expiry Date</label>
            <input type="date" v-model="form.emirates_id_expiry_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[var(--color-brand-primary)] focus:ring-[var(--color-brand-primary)] sm:text-sm border p-2" />
          </div>

          <!-- Job Details -->
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700">Department</label>
            <select v-model="form.department" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[var(--color-brand-primary)] focus:ring-[var(--color-brand-primary)] sm:text-sm border p-2">
              <option value="">Select Department</option>
              <option v-for="option in departmentOptions" :key="option" :value="option">{{ option }}</option>
            </select>
          </div>

          <div v-if="showBrandField">
            <label class="block text-sm font-medium text-gray-700">Brand</label>
            <select v-model="form.brand_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[var(--color-brand-primary)] focus:ring-[var(--color-brand-primary)] sm:text-sm border p-2">
              <option value="">Select Brand</option>
              <option v-for="brand in brands" :key="brand.id" :value="brand.id">{{ brand.name }}</option>
            </select>
          </div>

          <div v-if="showStoreField">
            <label class="block text-sm font-medium text-gray-700">Store</label>
            <select
              v-model="form.store_id"
              :disabled="!form.brand_id"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[var(--color-brand-primary)] focus:ring-[var(--color-brand-primary)] sm:text-sm border p-2 disabled:bg-gray-100"
            >
              <option value="">{{ form.brand_id ? 'No Store' : 'Select Brand First' }}</option>
              <option v-for="store in filteredStores" :key="store.id" :value="store.id">{{ store.name }}</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Position</label>
            <input
              type="text"
              v-model="form.job_title"
              list="position-options"
              required
              placeholder="Search or type position"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[var(--color-brand-primary)] focus:ring-[var(--color-brand-primary)] sm:text-sm border p-2"
            />
            <datalist id="position-options">
              <option v-for="position in filteredPositionOptions" :key="position" :value="position" />
            </datalist>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Joining Date</label>
            <input type="date" v-model="form.joining_date" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[var(--color-brand-primary)] focus:ring-[var(--color-brand-primary)] sm:text-sm border p-2" />
          </div>

          <div class="md:col-span-2 border-t border-gray-100 pt-4 mt-2">
            <h3 class="text-sm font-semibold text-gray-900">Address</h3>
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700">Permanent Address</label>
            <input type="text" v-model="form.permanent_address" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[var(--color-brand-primary)] focus:ring-[var(--color-brand-primary)] sm:text-sm border p-2" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Permanent City</label>
            <input type="text" v-model="form.permanent_city" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[var(--color-brand-primary)] focus:ring-[var(--color-brand-primary)] sm:text-sm border p-2" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Permanent Country</label>
            <input type="text" v-model="form.permanent_country" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[var(--color-brand-primary)] focus:ring-[var(--color-brand-primary)] sm:text-sm border p-2" />
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700">Present Address</label>
            <input type="text" v-model="form.present_address" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[var(--color-brand-primary)] focus:ring-[var(--color-brand-primary)] sm:text-sm border p-2" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Present City</label>
            <input type="text" v-model="form.present_city" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[var(--color-brand-primary)] focus:ring-[var(--color-brand-primary)] sm:text-sm border p-2" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Present Country</label>
            <input type="text" v-model="form.present_country" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[var(--color-brand-primary)] focus:ring-[var(--color-brand-primary)] sm:text-sm border p-2" />
          </div>

          <div class="md:col-span-2 border-t border-gray-100 pt-4 mt-2">
            <h3 class="text-sm font-semibold text-gray-900">Social Profiles</h3>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">LinkedIn URL</label>
            <input type="url" v-model="form.linkedin_url" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[var(--color-brand-primary)] focus:ring-[var(--color-brand-primary)] sm:text-sm border p-2" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Facebook URL</label>
            <input type="url" v-model="form.facebook_url" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[var(--color-brand-primary)] focus:ring-[var(--color-brand-primary)] sm:text-sm border p-2" />
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700">X URL</label>
            <input type="url" v-model="form.x_url" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[var(--color-brand-primary)] focus:ring-[var(--color-brand-primary)] sm:text-sm border p-2" />
          </div>
        </div>

      <!-- App Access (Only on Create) -->
      <div v-if="!isEdit" class="mt-8 pt-6 border-t border-gray-100">
          <div class="flex items-center mb-4">
              <input type="checkbox" v-model="form.create_account" id="create_account" class="h-4 w-4 text-[var(--color-brand-primary)] focus:ring-[var(--color-brand-primary)] border-gray-300 rounded" />
              <label for="create_account" class="ml-2 block text-sm font-medium text-gray-900">Create Mobile App Account</label>
          </div>
          
          <div v-if="form.create_account" class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-gray-50 p-6 rounded-lg border border-gray-100">
              <div>
                 <label class="block text-sm font-medium text-gray-700">Login Email (Auto-filled)</label>
                 <input type="text" :value="form.email" disabled class="mt-1 block w-full bg-gray-100 rounded-md border-gray-300 shadow-sm sm:text-sm border p-2 text-gray-500" />
              </div>
              <div></div>
              <div>
                  <label class="block text-sm font-medium text-gray-700">Set Password</label>
                  <input type="password" v-model="form.password" :required="form.create_account" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[var(--color-brand-primary)] focus:ring-[var(--color-brand-primary)] sm:text-sm border p-2" />
              </div>
              <div>
                  <label class="block text-sm font-medium text-gray-700">Confirm Password</label>
                  <input type="password" v-model="form.password_confirmation" :required="form.create_account" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[var(--color-brand-primary)] focus:ring-[var(--color-brand-primary)] sm:text-sm border p-2" />
              </div>
              <div>
                  <label class="block text-sm font-medium text-gray-700">Mobile App Role</label>
                  <select v-model="form.mobile_role" :required="form.create_account" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[var(--color-brand-primary)] focus:ring-[var(--color-brand-primary)] sm:text-sm border p-2">
                    <option value="staff">Staff</option>
                    <option value="supervisor">Supervisor</option>
                    <option value="manager">Manager</option>
                    <option value="sales-team">Sales Team</option>
                  </select>
              </div>
          </div>
      </div>

        <div class="flex justify-end mt-8 border-t border-gray-200 pt-6">
          <router-link to="/employees" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--color-brand-primary)] mr-3">
            Cancel
          </router-link>
          <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-[var(--color-brand-primary)] hover:bg-[var(--color-brand-hover)] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--color-brand-primary)]">
            {{ isEdit ? 'Update Employee' : 'Save Employee' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const router = useRouter();

const isEdit = ref(false);
const photoFile = ref(null);
const photoPreview = ref(null);
const brands = ref([]);
const stores = ref([]);
const assignmentRules = ref([]);
const positionOptions = ref([]);
const positionOptionsByDepartment = ref({});

const form = ref({
  employee_pin: '',
  full_name: '',
  email: '',
  phone: '',
  gender: '',
  status: 'active',
  date_of_birth: '',
  nationality: '',
  emirates_id: '',
  passport_number: '',
  passport_issue_date: '',
  passport_expiry: '',
  visa_status: 'Active',
  visa_issue_date: '',
  visa_expiry: '',
  insurance_start_date: '',
  insurance_end_date: '',
  emirates_id_issue_date: '',
  emirates_id_expiry_date: '',
  department: '',
  brand_id: '',
  store_id: '',
  job_title: '',
  joining_date: '',
  permanent_address: '',
  permanent_city: '',
  permanent_country: '',
  present_address: '',
  present_city: '',
  present_country: '',
  linkedin_url: '',
  facebook_url: '',
  x_url: '',
  photo_url: null,
  create_account: false,
  password: '',
  password_confirmation: '',
  mobile_role: 'staff'
});

const filteredStores = computed(() => {
  if (!form.value.brand_id) return [];
  return stores.value.filter((store) => Number(store.brand_id) === Number(form.value.brand_id));
});

const assignmentPolicy = computed(() => {
  const selectedDepartment = String(form.value.department || '').trim().toLowerCase();
  const matched = assignmentRules.value.find(
    (item) => String(item?.name || '').trim().toLowerCase() === selectedDepartment
  );

  return matched?.policy || {
    show_brand: false,
    show_store: false,
    requires_brand: false,
    requires_store: false,
    note: 'Brand and store are not required for this department.',
  };
});

const departmentOptions = computed(() => {
  const options = assignmentRules.value
    .map((item) => item?.name)
    .filter(Boolean);
  if (form.value.department && !options.includes(form.value.department)) {
    options.push(form.value.department);
  }
  return options.length
    ? options
    : ['Operations', 'Food & Beverage', 'Digital Marketing', 'Finance', 'HR', 'Warehouse'];
});

const normalizeDepartment = (department) => {
  const normalized = String(department || '').toLowerCase().trim().replaceAll('&', ' and ');
  return normalized.replace(/[^a-z0-9]+/g, ' ').trim();
};

const normalizeDepartmentMap = (mapObject) => {
  const result = {};
  Object.entries(mapObject || {}).forEach(([key, values]) => {
    const normalizedKey = normalizeDepartment(key);
    if (!normalizedKey) return;
    result[normalizedKey] = Array.isArray(values) ? values : [];
  });
  return result;
};

const showBrandField = computed(() => assignmentPolicy.value.show_brand);
const showStoreField = computed(() => assignmentPolicy.value.show_store);

const filteredPositionOptions = computed(() => {
  const departmentKey = normalizeDepartment(form.value.department);
  const scoped = positionOptionsByDepartment.value[departmentKey];
  const base = Array.isArray(scoped) && scoped.length ? scoped : positionOptions.value;

  if (form.value.job_title && !base.includes(form.value.job_title)) {
    return [...base, form.value.job_title];
  }
  return base;
});

watch(
  () => form.value.brand_id,
  (next, prev) => {
    if (next === prev) return;
    if (!next) {
      form.value.store_id = '';
      return;
    }
    if (!filteredStores.value.some((store) => Number(store.id) === Number(form.value.store_id))) {
      form.value.store_id = '';
    }
  }
);

watch(
  showBrandField,
  (enabled) => {
    if (!enabled) {
      form.value.brand_id = '';
      form.value.store_id = '';
    }
  },
  { immediate: true }
);

watch(
  showStoreField,
  (enabled) => {
    if (!enabled) {
      form.value.store_id = '';
    }
  },
  { immediate: true }
);

onMounted(async () => {
  try {
    const [storesResp, brandsResp, rulesResp] = await Promise.all([
      axios.get('/stores'),
      axios.get('/brands'),
      axios.get('/employees/assignment-rules'),
    ]);
    stores.value = Array.isArray(storesResp.data?.data) ? storesResp.data.data : (Array.isArray(storesResp.data) ? storesResp.data : []);
    brands.value = Array.isArray(brandsResp.data?.data) ? brandsResp.data.data : (Array.isArray(brandsResp.data) ? brandsResp.data : []);
    assignmentRules.value = Array.isArray(rulesResp.data?.departments) ? rulesResp.data.departments : [];
    positionOptions.value = Array.isArray(rulesResp.data?.positions) && rulesResp.data.positions.length
      ? rulesResp.data.positions
      : ['Staff', 'Supervisor', 'Manager', 'Sales Team'];
    positionOptionsByDepartment.value = normalizeDepartmentMap(rulesResp.data?.positions_by_department);
  } catch (e) {
    console.error('Failed to load brands/stores:', e);
    stores.value = [];
    brands.value = [];
    assignmentRules.value = [];
    positionOptions.value = ['Staff', 'Supervisor', 'Manager', 'Sales Team'];
    positionOptionsByDepartment.value = {};
  }

  if (route.params.id) {
    isEdit.value = true;
    try {
      const resp = await axios.get(`/employees/${route.params.id}`);
      const data = resp.data || {};
      const toDateInput = (value) => {
        if (!value) return '';
        if (typeof value === 'string' && value.length >= 10) return value.slice(0, 10);
        const d = new Date(value);
        return Number.isNaN(d.getTime()) ? '' : d.toISOString().slice(0, 10);
      };

      const { basic_salary, ...rest } = data || {};
      form.value = {
        ...form.value,
        ...rest,
        brand_id: data.store?.brand_id ?? data.brand_id ?? '',
        store_id: data.store_id ?? '',
        date_of_birth: toDateInput(data.date_of_birth),
        passport_issue_date: toDateInput(data.passport_issue_date),
        passport_expiry: toDateInput(data.passport_expiry),
        visa_issue_date: toDateInput(data.visa_issue_date),
        visa_expiry: toDateInput(data.visa_expiry),
        insurance_start_date: toDateInput(data.insurance_start_date),
        insurance_end_date: toDateInput(data.insurance_end_date),
        emirates_id_issue_date: toDateInput(data.emirates_id_issue_date),
        emirates_id_expiry_date: toDateInput(data.emirates_id_expiry_date),
        joining_date: toDateInput(data.joining_date),
      };
    } catch (e) {
      console.error(e);
    }
  }
});

const onFileChange = (e) => {
  const file = e.target.files[0];
  if (!file) return;

  const maxPhotoSizeBytes = 2 * 1024 * 1024; // 2MB (matches backend validation max:2048 KB)
  if (!file.type.startsWith('image/')) {
    alert('Please select a valid image file.');
    e.target.value = '';
    return;
  }

  if (file.size > maxPhotoSizeBytes) {
    alert('Image is too large. Please upload an image up to 2MB.');
    e.target.value = '';
    return;
  }

  photoFile.value = file;
  photoPreview.value = URL.createObjectURL(file);
};

const submitForm = async () => {
  try {
    if (!showBrandField.value) {
      form.value.brand_id = '';
      form.value.store_id = '';
    }
    if (!showStoreField.value) {
      form.value.store_id = '';
    }

    const formData = new FormData();
    const nullableStringFields = new Set([
      'date_of_birth',
      'passport_issue_date',
      'passport_expiry',
      'visa_issue_date',
      'visa_expiry',
      'insurance_start_date',
      'insurance_end_date',
      'emirates_id_issue_date',
      'emirates_id_expiry_date',
      'joining_date',
      'manager_id',
      'brand_id',
      'store_id',
      'phone',
      'nationality',
      'permanent_address',
      'permanent_city',
      'permanent_country',
      'present_address',
      'present_city',
      'present_country',
      'linkedin_url',
      'facebook_url',
      'x_url',
      'password',
      'password_confirmation',
    ]);
    // Append all form fields
    Object.keys(form.value).forEach(key => {
        const blacklist = [
            'photo_url', 'roles', 'role_names', 'manager', 
            'contracts', 'attendance_records', 'store', 'allowances',
            'user', 'educations', 'experiences', 'relatives'
        ];
        const accountOnlyFields = ['create_account', 'password', 'password_confirmation', 'mobile_role'];

        const value = form.value[key];
        if (isEdit.value && accountOnlyFields.includes(key)) {
            return;
        }
        if (nullableStringFields.has(key) && (value === '' || value === undefined)) {
            return;
        }

        if (value !== null && !blacklist.includes(key)) {
            formData.append(key, form.value[key]);
        }
    });

    if (photoFile.value) {
        formData.append('photo', photoFile.value);
    }

    if (isEdit.value) {
      // Laravel PUT with FormData needs _method spoofing
      formData.append('_method', 'PUT');
      await axios.post(`/employees/${route.params.id}`, formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
      });
    } else {
      await axios.post('/employees', formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
      });
    }
    router.push('/employees');
  } catch (err) {
    console.error('Save failed:', err.response?.data || err);
    if (err.response?.status === 413) {
      alert('Upload failed: image payload is too large for server limits. Use an image <= 2MB.');
      return;
    }
    const backendMessage = err.response?.data?.message;
    const backendErrors = err.response?.data?.errors;
    const detail = backendMessage || (backendErrors ? JSON.stringify(backendErrors) : err.message);
    alert('Validation failed: ' + detail);
  }
};
</script>

<style scoped>
.employee-form-shell .employee-form-card {
  background-image: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
}

.employee-form-shell label {
  font-size: 0.72rem;
  letter-spacing: 0.03em;
  text-transform: uppercase;
  color: #4b5563;
}

.employee-form-shell input,
.employee-form-shell select,
.employee-form-shell textarea {
  border-color: #d1d5db;
  background: #ffffff;
}

.employee-form-shell input:focus,
.employee-form-shell select:focus,
.employee-form-shell textarea:focus {
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.14);
}
</style>

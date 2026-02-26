<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-gray-900">Brand Management</h1>
      <p class="text-sm text-gray-500">Brand -> Area -> Stores with manager, supervisor, and assigned employees.</p>
    </div>

    <div class="bg-white rounded-lg border border-gray-100 shadow-sm p-4">
      <div v-if="brands.length" class="flex flex-wrap gap-2">
        <button
          v-for="brand in brands"
          :key="brand.id"
          @click="setActiveBrand(brand.id)"
          :class="[
            'rounded-md px-3 py-2 text-sm font-medium transition-colors',
            activeBrandId === brand.id
              ? 'bg-[var(--color-brand-primary)] text-white'
              : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
          ]"
        >
          {{ brand.name }}
        </button>
      </div>
      <p v-else class="text-sm text-gray-500">No brands found.</p>
    </div>

    <div v-if="activeBrand" class="bg-white rounded-lg border border-gray-100 shadow-sm p-5 space-y-4">
      <div class="rounded-md bg-blue-50 border border-blue-100 p-3">
        <p class="text-xs uppercase tracking-wide text-blue-700 font-semibold">Manager</p>
        <p class="text-base font-semibold text-blue-900">
          {{ activeBrand.manager?.name || 'No manager assigned' }}
        </p>
        <p v-if="activeBrand.manager?.email" class="text-sm text-blue-700">{{ activeBrand.manager.email }}</p>
      </div>

      <div v-if="activeBrand.areas?.length" class="space-y-4">
        <div class="flex flex-wrap gap-2">
          <button
            v-for="area in activeBrand.areas"
            :key="area.id"
            @click="setActiveArea(activeBrand.id, area.id)"
            :class="[
              'rounded-md px-3 py-2 text-sm font-medium transition-colors',
              activeAreaIdByBrand[activeBrand.id] === area.id
                ? 'bg-[var(--color-brand-primary)] text-white'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            ]"
          >
            {{ area.name }}
          </button>
        </div>

        <div v-if="activeArea" class="space-y-3">
          <div v-if="supportsAreaManagers" class="rounded-md bg-gray-50 border border-gray-200 p-3">
            <p class="text-xs uppercase tracking-wide text-gray-600 font-semibold">Area Manager</p>
            <p class="text-base font-semibold text-gray-900">{{ activeArea.manager?.name || 'No manager assigned' }}</p>
            <p v-if="activeArea.manager?.email" class="text-sm text-gray-600">{{ activeArea.manager.email }}</p>
          </div>

          <div class="overflow-x-auto border border-gray-100 rounded-lg">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                <tr>
                  <th class="px-4 py-3 text-left">Store Name</th>
                  <th class="px-4 py-3 text-left">Store Supervisor Name</th>
                  <th class="px-4 py-3 text-left">Employee Assigned</th>
                  <th class="px-4 py-3 text-left">Action</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="store in activeArea.stores || []" :key="store.id">
                  <td class="px-4 py-3 text-sm text-gray-900">{{ store.name }}</td>
                  <td class="px-4 py-3 text-sm text-gray-700">
                    {{ store.supervisor_names?.length ? store.supervisor_names.join(', ') : '-' }}
                  </td>
                  <td class="px-4 py-3 text-sm text-gray-700">
                    <span v-if="store.employee_count">{{ store.employee_count }}</span>
                    <span v-else>0</span>
                    <span v-if="store.employee_names?.length" class="text-xs text-gray-500">
                      ({{ store.employee_names.join(', ') }})
                    </span>
                  </td>
                  <td class="px-4 py-3 text-sm">
                    <button
                      class="rounded-md bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-200"
                      @click="viewStoreStaff(store)"
                    >
                      View Staff
                    </button>
                  </td>
                </tr>
                <tr v-if="!(activeArea.stores || []).length">
                  <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">No stores in this area.</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-if="selectedStore" class="rounded-md border border-gray-200 p-4">
            <div class="mb-3 flex items-center justify-between gap-3">
              <div>
                <p class="text-xs uppercase tracking-wide text-gray-600 font-semibold">Store Staff</p>
                <p class="text-sm font-semibold text-gray-900">{{ selectedStore.name }}</p>
              </div>
              <button
                class="rounded-md bg-gray-100 px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-200"
                @click="selectedStore = null"
              >
                Close
              </button>
            </div>

            <p v-if="staffLoading" class="text-sm text-gray-500">Loading staff...</p>
            <p v-else-if="staffError" class="text-sm text-red-600">{{ staffError }}</p>
            <div v-else class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                  <tr>
                    <th class="px-3 py-2 text-left">Name</th>
                    <th class="px-3 py-2 text-left">Email</th>
                    <th class="px-3 py-2 text-left">Roles</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                  <tr
                    v-for="member in selectedStoreStaff"
                    :key="`${member.user_id || 'e'}-${member.employee_id || 'na'}-${member.email || member.name}`"
                  >
                    <td class="px-3 py-2 text-sm text-gray-900">{{ member.name || '-' }}</td>
                    <td class="px-3 py-2 text-sm text-gray-700">{{ member.email || '-' }}</td>
                    <td class="px-3 py-2 text-sm text-gray-700">{{ formatRoles(member) }}</td>
                  </tr>
                  <tr v-if="!selectedStoreStaff.length">
                    <td colspan="3" class="px-3 py-4 text-center text-sm text-gray-500">No staff assigned.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      <p v-else class="text-sm text-gray-500">No areas found for this brand.</p>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import axios from 'axios';

const brands = ref([]);
const activeBrandId = ref(null);
const activeAreaIdByBrand = ref({});
const selectedStore = ref(null);
const staffLoading = ref(false);
const staffError = ref('');
const storeStaffById = ref({});

const normalizeCollection = (payload) => {
  if (Array.isArray(payload)) return payload;
  if (Array.isArray(payload?.data)) return payload.data;
  return [];
};

const activeBrand = computed(() => {
  return brands.value.find((brand) => brand.id === activeBrandId.value) || null;
});

const activeArea = computed(() => {
  if (!activeBrand.value) return null;
  const selectedAreaId = activeAreaIdByBrand.value[activeBrand.value.id];
  return activeBrand.value.areas?.find((area) => area.id === selectedAreaId) || activeBrand.value.areas?.[0] || null;
});

const supportsAreaManagers = computed(() => {
  const name = String(activeBrand.value?.name || '').trim().toLowerCase();
  return name === 'milestones coffee';
});

const setActiveArea = (brandId, areaId) => {
  activeAreaIdByBrand.value[brandId] = areaId;
  selectedStore.value = null;
};

const setActiveBrand = (brandId) => {
  activeBrandId.value = brandId;
  selectedStore.value = null;
  staffError.value = '';
};

const selectedStoreStaff = computed(() => {
  if (!selectedStore.value) return [];
  return storeStaffById.value[selectedStore.value.id] || [];
});

const formatRoles = (member) => {
  const roles = Array.isArray(member?.roles) ? member.roles : [];
  if (!roles.length) return '-';
  return roles.map((role) => String(role)).join(', ');
};

const viewStoreStaff = async (store) => {
  if (!activeBrand.value) return;

  selectedStore.value = store;
  staffError.value = '';

  if (storeStaffById.value[store.id]) {
    return;
  }

  staffLoading.value = true;
  try {
    const { data } = await axios.get(`/brands/${activeBrand.value.id}/stores/${store.id}/staff`);
    storeStaffById.value = {
      ...storeStaffById.value,
      [store.id]: Array.isArray(data?.staff) ? data.staff : [],
    };
  } catch (error) {
    staffError.value = error?.response?.data?.message || 'Failed to load store staff.';
    storeStaffById.value = {
      ...storeStaffById.value,
      [store.id]: [],
    };
  } finally {
    staffLoading.value = false;
  }
};

const loadBrands = async () => {
  const { data } = await axios.get('/brands/management');
  brands.value = normalizeCollection(data);

  if (brands.value.length && !activeBrandId.value) {
    activeBrandId.value = brands.value[0].id;
  }

  for (const brand of brands.value) {
    if (!activeAreaIdByBrand.value[brand.id] && brand.areas?.length) {
      activeAreaIdByBrand.value[brand.id] = brand.areas[0].id;
    }
  }
};

onMounted(async () => {
  await loadBrands();
});
</script>

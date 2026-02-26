<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-gray-900">Brand Management</h1>
      <p class="text-sm text-gray-500">Simple flow: Brands -> Areas -> Stores.</p>
    </div>

    <div class="bg-white rounded-lg border border-gray-100 shadow-sm p-3">
      <div class="flex flex-wrap gap-2">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          type="button"
          @click="activeTab = tab.key"
          :class="[
            'rounded-md px-3 py-2 text-sm font-medium transition-colors',
            activeTab === tab.key
              ? 'bg-[var(--color-brand-primary)] text-white'
              : 'bg-gray-100 text-gray-700 hover:bg-gray-200',
          ]"
        >
          {{ tab.label }}
        </button>
      </div>
    </div>

    <div v-if="activeTab !== 'brands'" class="bg-white rounded-lg border border-gray-100 shadow-sm p-4">
      <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">Selected Brand</label>
      <select
        v-model.number="selectedBrandId"
        class="w-full md:w-96 rounded-md border border-gray-300 px-3 py-2 text-sm"
        @change="ensureSelectedArea"
      >
        <option v-for="brand in brands" :key="brand.id" :value="brand.id">
          {{ brand.name }}
        </option>
      </select>
    </div>

    <div v-if="activeTab === 'brands'" class="space-y-4">
      <form @submit.prevent="saveBrand" class="bg-white rounded-lg border border-gray-100 shadow-sm p-5 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
          <input
            v-model="brandForm.name"
            type="text"
            placeholder="Brand name"
            class="rounded-md border border-gray-300 px-3 py-2 text-sm"
            required
          />

          <select v-model="brandForm.manager_user_id" class="rounded-md border border-gray-300 px-3 py-2 text-sm">
            <option value="">No Manager</option>
            <option v-for="manager in managers" :key="manager.id" :value="String(manager.id)">
              {{ manager.name }} ({{ manager.email }})
            </option>
          </select>

          <div class="flex items-center gap-2">
            <button type="submit" class="rounded-md bg-[var(--color-brand-primary)] px-4 py-2 text-sm text-white hover:bg-[var(--color-brand-hover)]">
              {{ brandForm.id ? 'Update Brand' : 'Create Brand' }}
            </button>
            <button
              v-if="brandForm.id"
              type="button"
              @click="resetBrandForm"
              class="rounded-md border border-gray-300 px-4 py-2 text-sm hover:bg-gray-50"
            >
              Cancel
            </button>
          </div>
        </div>
      </form>

      <div class="bg-white rounded-lg border border-gray-100 shadow-sm overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50 text-xs uppercase text-gray-500">
            <tr>
              <th class="px-4 py-3 text-left">Brand</th>
              <th class="px-4 py-3 text-left">Manager</th>
              <th class="px-4 py-3 text-left">Areas</th>
              <th class="px-4 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="brand in brands" :key="brand.id">
              <td class="px-4 py-3 text-sm text-gray-900">{{ brand.name }}</td>
              <td class="px-4 py-3 text-sm text-gray-700">
                {{ brand.manager ? `${brand.manager.name} (${brand.manager.email})` : '-' }}
              </td>
              <td class="px-4 py-3 text-sm text-gray-700">{{ (brand.areas || []).length }}</td>
              <td class="px-4 py-3 text-right">
                <div class="inline-flex gap-2">
                  <button type="button" @click="manageBrand(brand)" class="rounded-md border border-gray-300 px-3 py-1 text-xs hover:bg-gray-50">Manage</button>
                  <button type="button" @click="editBrand(brand)" class="rounded-md border border-gray-300 px-3 py-1 text-xs hover:bg-gray-50">Edit</button>
                  <button type="button" @click="deleteBrand(brand)" class="rounded-md border border-red-300 px-3 py-1 text-xs text-red-600 hover:bg-red-50">Delete</button>
                </div>
              </td>
            </tr>
            <tr v-if="brands.length === 0">
              <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">No brands found.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="activeTab === 'areas'" class="space-y-4">
      <div v-if="!selectedBrand" class="bg-white rounded-lg border border-gray-100 shadow-sm p-5 text-sm text-gray-500">
        No brand found. Create a brand first.
      </div>

      <template v-else>
        <div
          v-if="!supportsAreaManagers"
          class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"
        >
          Area managers are enabled only for Milestones Coffee. For this brand, access is controlled by brand manager assignment.
        </div>

        <form @submit.prevent="addArea" class="bg-white rounded-lg border border-gray-100 shadow-sm p-5 space-y-4">
          <p class="text-sm font-semibold text-gray-900">Create Area in {{ selectedBrand.name }}</p>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <input
              v-model="areaCreateForm.name"
              type="text"
              placeholder="Area name"
              class="rounded-md border border-gray-300 px-3 py-2 text-sm"
              required
            />

            <select
              v-if="supportsAreaManagers"
              v-model="areaCreateForm.manager_user_id"
              class="rounded-md border border-gray-300 px-3 py-2 text-sm"
            >
              <option value="">No Manager</option>
              <option v-for="manager in managers" :key="manager.id" :value="String(manager.id)">
                {{ manager.name }} ({{ manager.email }})
              </option>
            </select>
            <div
              v-else
              class="rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700"
            >
              Uses brand manager: {{ selectedBrand.manager?.name || 'No manager assigned' }}
            </div>

            <div>
              <button type="submit" class="rounded-md bg-[var(--color-brand-primary)] px-4 py-2 text-sm text-white hover:bg-[var(--color-brand-hover)]">
                Add Area
              </button>
            </div>
          </div>
        </form>

        <div class="bg-white rounded-lg border border-gray-100 shadow-sm overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500">
              <tr>
                <th class="px-4 py-3 text-left">Area Name</th>
                <th class="px-4 py-3 text-left">Area Manager</th>
                <th class="px-4 py-3 text-left">Stores</th>
                <th class="px-4 py-3 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="area in selectedBrand.areas || []" :key="area.id">
                <td class="px-4 py-3">
                  <input
                    v-model="areaEdits[area.id].name"
                    type="text"
                    class="w-full rounded border border-gray-300 px-2 py-1 text-sm"
                  />
                </td>
                <td class="px-4 py-3">
                  <select
                    v-if="supportsAreaManagers"
                    v-model="areaEdits[area.id].manager_user_id"
                    class="w-full rounded border border-gray-300 px-2 py-1 text-sm"
                  >
                    <option value="">No Manager</option>
                    <option v-for="manager in managers" :key="manager.id" :value="String(manager.id)">
                      {{ manager.name }}
                    </option>
                  </select>
                  <span v-else class="text-sm text-gray-700">
                    {{ selectedBrand.manager ? `${selectedBrand.manager.name} (Brand Manager)` : 'Brand Manager Not Assigned' }}
                  </span>
                </td>
                <td class="px-4 py-3 text-sm text-gray-700">{{ (area.stores || []).length }}</td>
                <td class="px-4 py-3 text-right">
                  <div class="inline-flex gap-2">
                    <button type="button" @click="openStoresForArea(area)" class="rounded-md border border-gray-300 px-3 py-1 text-xs hover:bg-gray-50">Open Stores</button>
                    <button type="button" @click="saveArea(area)" class="rounded-md border border-gray-300 px-3 py-1 text-xs hover:bg-gray-50">Save</button>
                    <button type="button" @click="deleteArea(area)" class="rounded-md border border-red-300 px-3 py-1 text-xs text-red-600 hover:bg-red-50">Delete</button>
                  </div>
                </td>
              </tr>
              <tr v-if="!(selectedBrand.areas || []).length">
                <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">No areas found for this brand.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>
    </div>

    <div v-if="activeTab === 'stores'" class="space-y-4">
      <div v-if="!selectedBrand" class="bg-white rounded-lg border border-gray-100 shadow-sm p-5 text-sm text-gray-500">
        No brand found. Create a brand first.
      </div>

      <template v-else>
        <div class="bg-white rounded-lg border border-gray-100 shadow-sm p-4">
          <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">Selected Area</label>
          <select
            v-if="supportsAreaManagers"
            v-model.number="selectedAreaIdByBrand[selectedBrand.id]"
            class="w-full md:w-96 rounded-md border border-gray-300 px-3 py-2 text-sm"
          >
            <option v-for="area in selectedBrand.areas || []" :key="area.id" :value="area.id">
              {{ area.name }}
            </option>
          </select>
          <div
            v-else
            class="w-full md:w-96 rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700"
          >
            Area auto-assigned to default (Main/Unassigned) for this brand.
          </div>
        </div>

        <div v-if="supportsAreaManagers && !selectedArea" class="bg-white rounded-lg border border-gray-100 shadow-sm p-5 text-sm text-gray-500">
          No area selected. Create an area first.
        </div>

        <template v-if="supportsAreaManagers ? Boolean(selectedArea) : true">
          <form @submit.prevent="addStore" class="bg-white rounded-lg border border-gray-100 shadow-sm p-5 space-y-4">
            <p class="text-sm font-semibold text-gray-900">
              {{ supportsAreaManagers ? `Add Store to ${selectedArea.name}` : `Add Store to ${selectedBrand.name}` }}
            </p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
              <input
                v-model="storeCreateForm.name"
                type="text"
                placeholder="Store name"
                class="rounded-md border border-gray-300 px-3 py-2 text-sm"
                required
              />
              <input
                v-model="storeCreateForm.address"
                type="text"
                placeholder="Address (optional)"
                class="rounded-md border border-gray-300 px-3 py-2 text-sm"
              />
              <div>
                <button type="submit" class="rounded-md bg-[var(--color-brand-primary)] px-4 py-2 text-sm text-white hover:bg-[var(--color-brand-hover)]">
                  Add Store
                </button>
              </div>
            </div>
          </form>

          <div class="bg-white rounded-lg border border-gray-100 shadow-sm overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                <tr>
                  <th class="px-4 py-3 text-left">Store Name</th>
                  <th class="px-4 py-3 text-left">Address</th>
                  <th v-if="supportsAreaManagers" class="px-4 py-3 text-left">Move To Area</th>
                  <th class="px-4 py-3 text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="store in storesInScope" :key="store.id">
                  <td class="px-4 py-3">
                    <input
                      v-model="storeEdits[store.id].name"
                      type="text"
                      class="w-full rounded border border-gray-300 px-2 py-1 text-sm"
                    />
                  </td>
                  <td class="px-4 py-3">
                    <input
                      v-model="storeEdits[store.id].address"
                      type="text"
                      class="w-full rounded border border-gray-300 px-2 py-1 text-sm"
                    />
                  </td>
                  <td v-if="supportsAreaManagers" class="px-4 py-3">
                    <div class="flex items-center gap-2">
                      <select
                        v-model.number="storeMoveTargetAreaId[store.id]"
                        class="w-full rounded border border-gray-300 px-2 py-1 text-sm"
                      >
                        <option v-for="area in selectedBrand.areas || []" :key="`move-${store.id}-${area.id}`" :value="area.id">
                          {{ area.name }}
                        </option>
                      </select>
                      <button
                        type="button"
                        @click="moveStore(store)"
                        :disabled="storeMoveTargetAreaId[store.id] === selectedArea.id"
                        class="rounded-md border border-gray-300 px-3 py-1 text-xs hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                      >
                        Move
                      </button>
                    </div>
                  </td>
                  <td class="px-4 py-3 text-right">
                    <div class="inline-flex gap-2">
                      <button type="button" @click="saveStore(store)" class="rounded-md border border-gray-300 px-3 py-1 text-xs hover:bg-gray-50">Save</button>
                      <button type="button" @click="deleteStore(store)" class="rounded-md border border-red-300 px-3 py-1 text-xs text-red-600 hover:bg-red-50">Delete</button>
                    </div>
                  </td>
                </tr>
                <tr v-if="!storesInScope.length">
                  <td :colspan="supportsAreaManagers ? 4 : 3" class="px-4 py-8 text-center text-sm text-gray-500">
                    {{ supportsAreaManagers ? 'No stores in this area.' : 'No stores for this brand.' }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>
      </template>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import axios from 'axios';

const tabs = [
  { key: 'brands', label: 'Brands' },
  { key: 'areas', label: 'Areas' },
  { key: 'stores', label: 'Stores' },
];

const activeTab = ref('brands');
const brands = ref([]);
const managers = ref([]);
const selectedBrandId = ref(null);
const selectedAreaIdByBrand = ref({});
const areaEdits = ref({});
const storeEdits = ref({});
const storeMoveTargetAreaId = ref({});

const brandForm = ref({
  id: null,
  name: '',
  manager_user_id: '',
});

const areaCreateForm = ref({
  name: '',
  manager_user_id: '',
});

const storeCreateForm = ref({
  name: '',
  address: '',
});

const normalizeCollection = (payload) => {
  if (Array.isArray(payload)) return payload;
  if (Array.isArray(payload?.data)) return payload.data;
  return [];
};

const selectedBrand = computed(() => {
  return brands.value.find((brand) => brand.id === selectedBrandId.value) || null;
});

const selectedArea = computed(() => {
  if (!selectedBrand.value) return null;
  const areaId = selectedAreaIdByBrand.value[selectedBrand.value.id];
  return (selectedBrand.value.areas || []).find((area) => area.id === areaId) || (selectedBrand.value.areas || [])[0] || null;
});

const supportsAreaManagers = computed(() => {
  const brandName = String(selectedBrand.value?.name || '').trim().toLowerCase();
  return brandName === 'milestones coffee';
});

const storesInScope = computed(() => {
  if (!selectedBrand.value) return [];
  if (supportsAreaManagers.value) {
    return selectedArea.value?.stores || [];
  }

  const flat = (selectedBrand.value.areas || []).flatMap((area) => area.stores || []);
  const uniqueById = new Map();
  for (const store of flat) {
    uniqueById.set(store.id, store);
  }
  return Array.from(uniqueById.values());
});

const resetBrandForm = () => {
  brandForm.value = {
    id: null,
    name: '',
    manager_user_id: '',
  };
};

const ensureSelectedArea = () => {
  if (!selectedBrand.value) return;
  const currentAreaId = selectedAreaIdByBrand.value[selectedBrand.value.id];
  const exists = (selectedBrand.value.areas || []).some((area) => area.id === currentAreaId);
  if (!exists) {
    selectedAreaIdByBrand.value[selectedBrand.value.id] = selectedBrand.value.areas?.[0]?.id || null;
  }
};

const loadBrands = async () => {
  const { data } = await axios.get('/brands');
  brands.value = normalizeCollection(data);

  if (!brands.value.length) {
    selectedBrandId.value = null;
    areaEdits.value = {};
    storeEdits.value = {};
    return;
  }

  if (!selectedBrandId.value || !brands.value.some((brand) => brand.id === selectedBrandId.value)) {
    selectedBrandId.value = brands.value[0].id;
  }

  const nextAreaEdits = {};
  const nextStoreEdits = {};
  const nextStoreMoveTargets = {};

  for (const brand of brands.value) {
    const currentAreaId = selectedAreaIdByBrand.value[brand.id];
    const hasCurrentArea = (brand.areas || []).some((area) => area.id === currentAreaId);
    if (!hasCurrentArea) {
      selectedAreaIdByBrand.value[brand.id] = brand.areas?.[0]?.id || null;
    }

    for (const area of brand.areas || []) {
      nextAreaEdits[area.id] = {
        name: area.name,
        manager_user_id: area.manager_user_id ? String(area.manager_user_id) : '',
      };

      for (const store of area.stores || []) {
        nextStoreEdits[store.id] = {
          name: store.name,
          address: store.address || '',
        };
        nextStoreMoveTargets[store.id] = area.id;
      }
    }
  }

  areaEdits.value = nextAreaEdits;
  storeEdits.value = nextStoreEdits;
  storeMoveTargetAreaId.value = nextStoreMoveTargets;
  ensureSelectedArea();
};

const loadManagers = async () => {
  const { data } = await axios.get('/brands/managers');
  managers.value = normalizeCollection(data);
};

const manageBrand = (brand) => {
  selectedBrandId.value = brand.id;
  ensureSelectedArea();
  activeTab.value = 'areas';
};

const editBrand = (brand) => {
  brandForm.value = {
    id: brand.id,
    name: brand.name,
    manager_user_id: brand.manager_user_id ? String(brand.manager_user_id) : '',
  };
};

const saveBrand = async () => {
  const payload = {
    name: brandForm.value.name,
    manager_user_id: brandForm.value.manager_user_id ? Number(brandForm.value.manager_user_id) : null,
  };

  if (brandForm.value.id) {
    await axios.put(`/brands/${brandForm.value.id}`, payload);
  } else {
    await axios.post('/brands', payload);
  }

  await loadBrands();
  resetBrandForm();
};

const deleteBrand = async (brand) => {
  if (!window.confirm(`Delete brand "${brand.name}"?`)) return;

  await axios.delete(`/brands/${brand.id}`);
  await loadBrands();
  if (brandForm.value.id === brand.id) {
    resetBrandForm();
  }
};

const addArea = async () => {
  if (!selectedBrand.value) return;
  const name = areaCreateForm.value.name.trim();
  if (!name) return;

  await axios.post(`/brands/${selectedBrand.value.id}/areas`, {
    name,
    manager_user_id: supportsAreaManagers.value && areaCreateForm.value.manager_user_id
      ? Number(areaCreateForm.value.manager_user_id)
      : null,
  });

  areaCreateForm.value = { name: '', manager_user_id: '' };
  await loadBrands();
};

const saveArea = async (area) => {
  if (!selectedBrand.value) return;
  const editState = areaEdits.value[area.id] || {};

  await axios.put(`/brands/${selectedBrand.value.id}/areas/${area.id}`, {
    name: (editState.name || '').trim() || area.name,
    manager_user_id: supportsAreaManagers.value && editState.manager_user_id
      ? Number(editState.manager_user_id)
      : null,
  });

  await loadBrands();
};

const deleteArea = async (area) => {
  if (!selectedBrand.value) return;
  if (!window.confirm(`Delete ${area.name} from ${selectedBrand.value.name}?`)) return;

  await axios.delete(`/brands/${selectedBrand.value.id}/areas/${area.id}`);
  await loadBrands();
};

const openStoresForArea = (area) => {
  if (!selectedBrand.value) return;
  selectedAreaIdByBrand.value[selectedBrand.value.id] = area.id;
  activeTab.value = 'stores';
};

const addStore = async () => {
  if (!selectedBrand.value) return;
  if (supportsAreaManagers.value && !selectedArea.value) return;
  const name = storeCreateForm.value.name.trim();
  if (!name) return;

  try {
    await axios.post(`/brands/${selectedBrand.value.id}/stores`, {
      name,
      address: storeCreateForm.value.address.trim() || null,
      brand_area_id: supportsAreaManagers.value ? selectedArea.value.id : null,
    });

    storeCreateForm.value = { name: '', address: '' };
    await loadBrands();
  } catch (error) {
    const message = error?.response?.data?.message || 'Failed to add store.';
    window.alert(message);
  }
};

const saveStore = async (store) => {
  if (!selectedBrand.value) return;
  const currentAreaId = Number(store.brand_area_id || selectedArea.value?.id || 0);
  if (!currentAreaId) return;
  const editState = storeEdits.value[store.id] || {};

  try {
    await axios.put(`/brands/${selectedBrand.value.id}/areas/${currentAreaId}/stores/${store.id}`, {
      name: (editState.name || '').trim() || store.name,
      address: (editState.address || '').trim() || null,
    });
    await loadBrands();
  } catch (error) {
    const message = error?.response?.data?.message || 'Failed to save store.';
    window.alert(message);
  }
};

const moveStore = async (store) => {
  if (!selectedBrand.value || !selectedArea.value) return;

  const targetAreaId = Number(storeMoveTargetAreaId.value[store.id]);
  const currentAreaId = Number(store.brand_area_id || selectedArea.value.id);
  if (!targetAreaId || targetAreaId === currentAreaId) return;

  const editState = storeEdits.value[store.id] || {};

  try {
    await axios.put(`/brands/${selectedBrand.value.id}/areas/${currentAreaId}/stores/${store.id}`, {
      name: (editState.name || '').trim() || store.name,
      address: (editState.address || '').trim() || null,
      brand_area_id: targetAreaId,
    });

    await loadBrands();
  } catch (error) {
    const message = error?.response?.data?.message || 'Failed to move store.';
    window.alert(message);
  }
};

const deleteStore = async (store) => {
  if (!selectedBrand.value) return;
  const currentAreaId = Number(store.brand_area_id || selectedArea.value?.id || 0);
  if (!currentAreaId) return;
  if (!window.confirm(`Delete store "${store.name}"?`)) return;

  try {
    await axios.delete(`/brands/${selectedBrand.value.id}/areas/${currentAreaId}/stores/${store.id}`);
    await loadBrands();
  } catch (error) {
    const message = error?.response?.data?.message || 'Failed to delete store.';
    window.alert(message);
  }
};

onMounted(async () => {
  await Promise.all([loadBrands(), loadManagers()]);
});
</script>

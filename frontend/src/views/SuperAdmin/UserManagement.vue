<template>
  <div class="max-w-7xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-semibold text-gray-900">User Management</h1>
      <button @click="openCreate" class="px-4 py-2 bg-[var(--color-brand-primary)] text-white rounded-md text-sm font-semibold hover:bg-[var(--color-brand-hover)]">
        + New User
      </button>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Email</th>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Roles</th>
            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white">
          <tr v-if="loading">
            <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">Loading users...</td>
          </tr>
          <tr v-else-if="users.length === 0">
            <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">No users found.</td>
          </tr>
          <tr v-else v-for="user in users" :key="user.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ user.name }}</td>
            <td class="px-6 py-4 text-sm text-gray-600">{{ user.email }}</td>
            <td class="px-6 py-4 text-sm text-gray-600">
              <div class="flex flex-wrap gap-1">
                <span v-for="role in (user.role_names || [])" :key="`${user.id}-${role}`" class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 text-xs font-semibold">
                  {{ role }}
                </span>
              </div>
            </td>
            <td class="px-6 py-4 text-right text-sm">
              <button @click="openEdit(user)" class="text-[var(--color-brand-primary)] hover:underline mr-4">Edit</button>
              <button @click="removeUser(user)" class="text-red-600 hover:underline">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
      <div v-if="pagination.total > pagination.per_page" class="px-6 py-4 border-t border-gray-100 flex items-center justify-between text-sm text-gray-600">
        <span>Showing {{ pagination.from }} - {{ pagination.to }} of {{ pagination.total }}</span>
        <div class="space-x-2">
          <button :disabled="!pagination.prev_page_url" @click="fetchUsers(pagination.current_page - 1)" class="px-3 py-1 border rounded disabled:opacity-50">Prev</button>
          <button :disabled="!pagination.next_page_url" @click="fetchUsers(pagination.current_page + 1)" class="px-3 py-1 border rounded disabled:opacity-50">Next</button>
        </div>
      </div>
    </div>

    <div v-if="showModal" class="fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-lg shadow-xl max-w-xl w-full">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
          <h2 class="text-lg font-semibold text-gray-900">{{ isEdit ? 'Edit User' : 'Create User' }}</h2>
          <button @click="closeModal" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <form @submit.prevent="saveUser" class="p-6 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
            <input v-model="form.name" required class="w-full px-3 py-2 border rounded-md focus:ring-1 focus:ring-[var(--color-brand-primary)]" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input v-model="form.email" type="email" required class="w-full px-3 py-2 border rounded-md focus:ring-1 focus:ring-[var(--color-brand-primary)]" />
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Password {{ isEdit ? '(optional)' : '' }}</label>
              <input v-model="form.password" type="password" :required="!isEdit" class="w-full px-3 py-2 border rounded-md focus:ring-1 focus:ring-[var(--color-brand-primary)]" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
              <input v-model="form.password_confirmation" type="password" :required="!isEdit || !!form.password" class="w-full px-3 py-2 border rounded-md focus:ring-1 focus:ring-[var(--color-brand-primary)]" />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Roles</label>
            <div class="grid grid-cols-2 gap-2">
              <label v-for="role in availableRoles" :key="role" class="flex items-center space-x-2 text-sm text-gray-700">
                <input type="checkbox" :value="role" v-model="form.roles" class="rounded border-gray-300 text-[var(--color-brand-primary)]" />
                <span>{{ role }}</span>
              </label>
            </div>
          </div>
          <div class="pt-2 flex justify-end gap-3">
            <button type="button" @click="closeModal" class="px-4 py-2 border rounded-md text-sm">Cancel</button>
            <button type="submit" :disabled="saving" class="px-4 py-2 bg-[var(--color-brand-primary)] text-white rounded-md text-sm font-semibold disabled:opacity-50">
              {{ saving ? 'Saving...' : (isEdit ? 'Save Changes' : 'Create User') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import axios from 'axios';

const loading = ref(false);
const saving = ref(false);
const showModal = ref(false);
const isEdit = ref(false);
const editingId = ref(null);
const users = ref([]);
const pagination = reactive({
  current_page: 1,
  from: 0,
  to: 0,
  total: 0,
  per_page: 20,
  next_page_url: null,
  prev_page_url: null,
});

const availableRoles = ['super-admin', 'admin', 'manager', 'supervisor', 'sales-team', 'staff', 'employee'];

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  roles: [],
});

const resetForm = () => {
  form.name = '';
  form.email = '';
  form.password = '';
  form.password_confirmation = '';
  form.roles = [];
};

const fetchUsers = async (page = 1) => {
  loading.value = true;
  try {
    const resp = await axios.get('/admin/users', { params: { page } });
    users.value = resp.data.data || [];
    pagination.current_page = resp.data.current_page;
    pagination.from = resp.data.from || 0;
    pagination.to = resp.data.to || 0;
    pagination.total = resp.data.total || 0;
    pagination.per_page = resp.data.per_page || 20;
    pagination.next_page_url = resp.data.next_page_url;
    pagination.prev_page_url = resp.data.prev_page_url;
  } catch (err) {
    console.error(err);
    alert(err.response?.data?.message || 'Failed to load users');
  } finally {
    loading.value = false;
  }
};

const openCreate = () => {
  isEdit.value = false;
  editingId.value = null;
  resetForm();
  showModal.value = true;
};

const openEdit = (user) => {
  isEdit.value = true;
  editingId.value = user.id;
  form.name = user.name || '';
  form.email = user.email || '';
  form.password = '';
  form.password_confirmation = '';
  form.roles = [...(user.role_names || [])];
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
};

const saveUser = async () => {
  if (form.roles.length === 0) {
    alert('Please assign at least one role.');
    return;
  }

  const payload = {
    name: form.name,
    email: form.email,
    roles: form.roles,
  };

  if (form.password) {
    payload.password = form.password;
    payload.password_confirmation = form.password_confirmation;
  } else if (!isEdit.value) {
    payload.password = '';
    payload.password_confirmation = '';
  }

  saving.value = true;
  try {
    if (isEdit.value) {
      await axios.put(`/admin/users/${editingId.value}`, payload);
    } else {
      if (!form.password) {
        alert('Password is required for new users.');
        return;
      }
      await axios.post('/admin/users', payload);
    }
    closeModal();
    await fetchUsers(pagination.current_page);
  } catch (err) {
    console.error(err);
    alert(err.response?.data?.message || 'Failed to save user');
  } finally {
    saving.value = false;
  }
};

const removeUser = async (user) => {
  if (!confirm(`Delete user ${user.email}?`)) return;
  try {
    await axios.delete(`/admin/users/${user.id}`);
    await fetchUsers(pagination.current_page);
  } catch (err) {
    console.error(err);
    alert(err.response?.data?.message || 'Failed to delete user');
  }
};

onMounted(fetchUsers);
</script>

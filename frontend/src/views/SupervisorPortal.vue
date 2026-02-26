<template>
  <div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-2xl font-semibold text-gray-900">Supervisor Portal</h1>
        <p class="text-sm text-gray-500">Leave approvals, sales input, schedules, store info, and staff list.</p>
      </div>
      <div class="flex items-center gap-2">
        <label class="text-sm font-medium text-gray-700">Store</label>
        <select
          v-model.number="selectedStoreId"
          @change="refreshStoreScopedData"
          class="rounded-md border border-gray-300 px-3 py-2 text-sm"
        >
          <option v-for="store in stores" :key="store.id" :value="store.id">
            {{ store.name }}
          </option>
        </select>
      </div>
    </div>

    <div class="border-b border-gray-200">
      <nav class="-mb-px flex flex-wrap gap-2">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          @click="setTab(tab.key)"
          :class="[
            'px-4 py-2 text-sm font-medium rounded-t-md border-b-2',
            activeTab === tab.key
              ? 'border-[var(--color-brand-primary)] text-[var(--color-brand-primary)] bg-blue-50'
              : 'border-transparent text-gray-600 hover:text-gray-900 hover:bg-gray-50'
          ]"
        >
          {{ tab.label }}
        </button>
      </nav>
    </div>

    <section v-if="activeTab === 'overview'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-5 lg:col-span-2">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Store Location</h2>
        <div v-if="selectedStore" class="space-y-2 text-sm text-gray-700">
          <p><span class="font-medium">Name:</span> {{ selectedStore.name }}</p>
          <p><span class="font-medium">Address:</span> {{ selectedStore.address || '-' }}</p>
          <p>
            <span class="font-medium">Coordinates:</span>
            {{ selectedStore.latitude ?? '-' }}, {{ selectedStore.longitude ?? '-' }}
          </p>
          <a
            v-if="selectedStore.latitude && selectedStore.longitude"
            :href="`https://maps.google.com/?q=${selectedStore.latitude},${selectedStore.longitude}`"
            target="_blank"
            rel="noopener"
            class="inline-flex text-[var(--color-brand-primary)] hover:underline"
          >
            Open in Maps
          </a>
        </div>
        <p v-else class="text-sm text-gray-500">No store assigned.</p>
      </div>

      <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-5 space-y-4">
        <div>
          <p class="text-xs uppercase tracking-wide text-gray-500">Pending Leave Requests</p>
          <p class="text-2xl font-semibold text-gray-900 mt-1">{{ pendingLeaves.length }}</p>
        </div>
        <div>
          <p class="text-xs uppercase tracking-wide text-gray-500">Staff in Selected Store</p>
          <p class="text-2xl font-semibold text-gray-900 mt-1">{{ staff.length }}</p>
        </div>
        <div>
          <p class="text-xs uppercase tracking-wide text-gray-500">Schedules Loaded</p>
          <p class="text-2xl font-semibold text-gray-900 mt-1">{{ schedules.length }}</p>
        </div>
      </div>
    </section>

    <section v-if="activeTab === 'leave'" class="space-y-4">
      <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-900">Leave Approval</h2>
        <button
          @click="fetchPendingLeaves"
          class="px-3 py-2 text-sm rounded-md border border-gray-300 bg-white hover:bg-gray-50"
        >
          Refresh
        </button>
      </div>
      <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50 text-xs uppercase text-gray-500">
            <tr>
              <th class="px-4 py-3 text-left">Employee</th>
              <th class="px-4 py-3 text-left">Type</th>
              <th class="px-4 py-3 text-left">Dates</th>
              <th class="px-4 py-3 text-left">Stage</th>
              <th class="px-4 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="leave in pendingLeaves" :key="leave.id">
              <td class="px-4 py-3 text-sm text-gray-900">{{ leave.employee?.full_name || `#${leave.employee_id}` }}</td>
              <td class="px-4 py-3 text-sm text-gray-700">{{ leave.leave_type?.name || '-' }}</td>
              <td class="px-4 py-3 text-sm text-gray-700">{{ leave.start_date }} to {{ leave.end_date }}</td>
              <td class="px-4 py-3 text-sm text-gray-700">{{ leave.workflow_status }}</td>
              <td class="px-4 py-3 text-right">
                <div class="inline-flex gap-2">
                  <button
                    @click="approveLeave(leave)"
                    class="px-3 py-1 text-xs rounded-md bg-green-100 text-green-700 hover:bg-green-200"
                  >
                    Approve
                  </button>
                  <button
                    @click="rejectLeave(leave)"
                    class="px-3 py-1 text-xs rounded-md bg-red-100 text-red-700 hover:bg-red-200"
                  >
                    Reject
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="pendingLeaves.length === 0">
              <td colspan="5" class="px-4 py-8 text-sm text-center text-gray-500">No pending leave requests.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <section v-if="activeTab === 'schedule'" class="space-y-4">
      <h2 class="text-lg font-semibold text-gray-900">Schedule Creation</h2>
      <form @submit.prevent="createSchedule" class="bg-white rounded-lg shadow-sm border border-gray-100 p-5 grid grid-cols-1 md:grid-cols-5 gap-3">
        <select v-model.number="scheduleForm.employee_id" required class="rounded-md border border-gray-300 px-3 py-2 text-sm">
          <option disabled value="">Select Staff</option>
          <option v-for="member in staff" :key="member.id" :value="member.id">{{ member.full_name }}</option>
        </select>

        <select v-model.number="scheduleForm.shift_id" required class="rounded-md border border-gray-300 px-3 py-2 text-sm">
          <option disabled value="">Select Shift</option>
          <option v-for="shift in shifts" :key="shift.id" :value="shift.id">{{ shift.name }} ({{ shift.start_time }} - {{ shift.end_time }})</option>
        </select>

        <input v-model="scheduleForm.date" type="date" required class="rounded-md border border-gray-300 px-3 py-2 text-sm" />

        <label class="flex items-center gap-2 text-sm text-gray-700 rounded-md border border-gray-300 px-3 py-2">
          <input v-model="scheduleForm.is_closing_shift" type="checkbox" />
          Closing Shift
        </label>

        <button type="submit" class="rounded-md bg-[var(--color-brand-primary)] px-4 py-2 text-sm text-white hover:bg-[var(--color-brand-hover)]">
          Save Schedule
        </button>
      </form>

      <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50 text-xs uppercase text-gray-500">
            <tr>
              <th class="px-4 py-3 text-left">Date</th>
              <th class="px-4 py-3 text-left">Staff</th>
              <th class="px-4 py-3 text-left">Shift</th>
              <th class="px-4 py-3 text-left">Closing</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="item in schedules" :key="item.id">
              <td class="px-4 py-3 text-sm text-gray-700">{{ item.date }}</td>
              <td class="px-4 py-3 text-sm text-gray-700">{{ item.employee?.full_name || '-' }}</td>
              <td class="px-4 py-3 text-sm text-gray-700">{{ item.shift?.name || '-' }}</td>
              <td class="px-4 py-3 text-sm text-gray-700">{{ item.is_closing_shift ? 'Yes' : 'No' }}</td>
            </tr>
            <tr v-if="schedules.length === 0">
              <td colspan="4" class="px-4 py-8 text-sm text-center text-gray-500">No schedules found.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <section v-if="activeTab === 'sales'" class="space-y-4">
      <h2 class="text-lg font-semibold text-gray-900">Sales Input</h2>
      <form @submit.prevent="submitSales" class="bg-white rounded-lg shadow-sm border border-gray-100 p-5 grid grid-cols-1 md:grid-cols-4 gap-3">
        <input v-model="salesForm.date" type="date" required class="rounded-md border border-gray-300 px-3 py-2 text-sm" />
        <input v-model.number="salesForm.amount" type="number" step="0.01" min="0" required placeholder="Amount" class="rounded-md border border-gray-300 px-3 py-2 text-sm" />
        <button type="submit" class="rounded-md bg-[var(--color-brand-primary)] px-4 py-2 text-sm text-white hover:bg-[var(--color-brand-hover)]">Save Sales</button>
        <button type="button" @click="fetchSales" class="rounded-md border border-gray-300 px-4 py-2 text-sm hover:bg-gray-50">Refresh</button>
      </form>

      <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-5">
        <p class="text-sm text-gray-500">Sales Total (Loaded Range)</p>
        <p class="text-2xl font-semibold text-gray-900 mt-1">{{ salesSummary }}</p>
      </div>

      <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50 text-xs uppercase text-gray-500">
            <tr>
              <th class="px-4 py-3 text-left">Date</th>
              <th class="px-4 py-3 text-left">Amount</th>
              <th class="px-4 py-3 text-left">By</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="entry in salesEntries" :key="entry.id">
              <td class="px-4 py-3 text-sm text-gray-700">{{ entry.date }}</td>
              <td class="px-4 py-3 text-sm text-gray-700">{{ Number(entry.amount || 0).toFixed(2) }}</td>
              <td class="px-4 py-3 text-sm text-gray-700">#{{ entry.employee_id }}</td>
            </tr>
            <tr v-if="salesEntries.length === 0">
              <td colspan="3" class="px-4 py-8 text-sm text-center text-gray-500">No sales entries.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <section v-if="activeTab === 'staff'" class="space-y-4">
      <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-900">Staff List</h2>
        <button @click="fetchStaff" class="px-3 py-2 text-sm rounded-md border border-gray-300 bg-white hover:bg-gray-50">Refresh</button>
      </div>
      <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50 text-xs uppercase text-gray-500">
            <tr>
              <th class="px-4 py-3 text-left">PIN</th>
              <th class="px-4 py-3 text-left">Name</th>
              <th class="px-4 py-3 text-left">Email</th>
              <th class="px-4 py-3 text-left">Phone</th>
              <th class="px-4 py-3 text-left">Job</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="member in staff" :key="member.id">
              <td class="px-4 py-3 text-sm text-gray-700">{{ member.employee_pin || '-' }}</td>
              <td class="px-4 py-3 text-sm text-gray-700">{{ member.full_name }}</td>
              <td class="px-4 py-3 text-sm text-gray-700">{{ member.email }}</td>
              <td class="px-4 py-3 text-sm text-gray-700">{{ member.phone || '-' }}</td>
              <td class="px-4 py-3 text-sm text-gray-700">{{ member.job_title || '-' }}</td>
            </tr>
            <tr v-if="staff.length === 0">
              <td colspan="5" class="px-4 py-8 text-sm text-center text-gray-500">No staff found for selected store.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()
const route = useRoute()

const tabs = [
  { key: 'overview', label: 'Overview' },
  { key: 'leave', label: 'Leave Approval' },
  { key: 'schedule', label: 'Schedule Creation' },
  { key: 'sales', label: 'Sales Input' },
  { key: 'staff', label: 'Staff List' },
]

const activeTab = ref((route.query.tab || 'overview').toString())

watch(
  () => route.query.tab,
  (value) => {
    if (!value) return
    activeTab.value = value.toString()
  }
)

const authUser = computed(() => {
  const raw = sessionStorage.getItem('auth_user')
  if (!raw) return null
  try {
    return JSON.parse(raw)
  } catch {
    return null
  }
})

const roleNames = computed(() => {
  const roles = authUser.value?.role_names || authUser.value?.roles?.map((r) => r.name) || []
  return roles.map((r) => String(r).toLowerCase().trim())
})

const isSupervisor = computed(() => roleNames.value.includes('supervisor') || roleNames.value.includes('shift-supervisor'))
const isManager = computed(() => roleNames.value.includes('manager'))

const stores = ref([])
const selectedStoreId = ref(null)

const staff = ref([])
const pendingLeaves = ref([])
const shifts = ref([])
const schedules = ref([])
const salesEntries = ref([])

const scheduleForm = ref({
  employee_id: '',
  shift_id: '',
  date: new Date().toISOString().slice(0, 10),
  is_closing_shift: false,
})

const salesForm = ref({
  date: new Date().toISOString().slice(0, 10),
  amount: null,
})

const selectedStore = computed(() => stores.value.find((s) => Number(s.id) === Number(selectedStoreId.value)) || null)
const salesSummary = computed(() => {
  const total = salesEntries.value.reduce((sum, entry) => sum + Number(entry.amount || 0), 0)
  return total.toFixed(2)
})

const setTab = (tab) => {
  activeTab.value = tab
  router.replace({
    path: '/supervisor',
    query: {
      ...route.query,
      tab,
    },
  })
}

const unwrapCollection = (response) => {
  const payload = response?.data
  if (Array.isArray(payload)) return payload
  if (Array.isArray(payload?.data)) return payload.data
  if (Array.isArray(payload?.data?.data)) return payload.data.data
  return []
}

const fetchStores = async () => {
  const response = await axios.get('/stores')
  stores.value = unwrapCollection(response)
  if (!selectedStoreId.value && stores.value.length > 0) {
    selectedStoreId.value = Number(stores.value[0].id)
  }
}

const fetchStaff = async () => {
  if (!selectedStoreId.value) return
  const response = await axios.get('/staff', {
    params: { store_id: selectedStoreId.value },
  })
  staff.value = unwrapCollection(response)
}

const fetchPendingLeaves = async () => {
  if (!selectedStoreId.value) return
  let workflowStatus = 'pending_supervisor'
  if (isManager.value && !isSupervisor.value) {
    workflowStatus = 'pending_manager'
  }

  const response = await axios.get('/leave-requests', {
    params: {
      store_id: selectedStoreId.value,
      workflow_status: workflowStatus,
    },
  })
  pendingLeaves.value = unwrapCollection(response)
}

const fetchShifts = async () => {
  if (!selectedStoreId.value) return
  const response = await axios.get('/shifts', {
    params: { store_id: selectedStoreId.value },
  })
  shifts.value = unwrapCollection(response)
}

const fetchSchedules = async () => {
  if (!selectedStoreId.value) return
  const response = await axios.get('/schedules', {
    params: {
      store_id: selectedStoreId.value,
      date_from: new Date().toISOString().slice(0, 10),
      date_to: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().slice(0, 10),
    },
  })
  schedules.value = unwrapCollection(response)
}

const fetchSales = async () => {
  if (!selectedStoreId.value) return
  const dateFrom = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString().slice(0, 10)
  const dateTo = new Date().toISOString().slice(0, 10)

  const response = await axios.get('/sales', {
    params: {
      store_id: selectedStoreId.value,
      date_from: dateFrom,
      date_to: dateTo,
    },
  })
  salesEntries.value = unwrapCollection(response)
}

const approveLeave = async (leave) => {
  const comment = window.prompt('Approval comment (optional):', '')
  if (comment === null) return

  await axios.put(`/leave-requests/${leave.id}/approve`, { comment })
  await fetchPendingLeaves()
}

const rejectLeave = async (leave) => {
  const reason = window.prompt('Rejection reason:')
  if (!reason) return

  await axios.put(`/leave-requests/${leave.id}/reject`, { reason })
  await fetchPendingLeaves()
}

const createSchedule = async () => {
  if (!selectedStoreId.value) return

  await axios.post('/schedules', {
    store_id: selectedStoreId.value,
    employee_id: scheduleForm.value.employee_id,
    shift_id: scheduleForm.value.shift_id,
    date: scheduleForm.value.date,
    is_closing_shift: scheduleForm.value.is_closing_shift,
  })

  scheduleForm.value.employee_id = ''
  scheduleForm.value.shift_id = ''
  scheduleForm.value.is_closing_shift = false

  await fetchSchedules()
}

const submitSales = async () => {
  if (!selectedStoreId.value) return

  await axios.post('/sales', {
    store_id: selectedStoreId.value,
    date: salesForm.value.date,
    amount: salesForm.value.amount,
  })

  salesForm.value.amount = null
  await fetchSales()
}

const refreshStoreScopedData = async () => {
  await Promise.all([
    fetchStaff(),
    fetchPendingLeaves(),
    fetchShifts(),
    fetchSchedules(),
    fetchSales(),
  ])
}

onMounted(async () => {
  await fetchStores()
  await refreshStoreScopedData()
})
</script>

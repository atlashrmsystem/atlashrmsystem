<template>
  <div v-if="loading" class="flex justify-center items-center h-64">
    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-[var(--color-brand-primary)]"></div>
  </div>

  <div v-else-if="employee">
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
      <div class="flex items-center">
        <div class="h-16 w-16 rounded-full bg-blue-100 text-blue-600 overflow-hidden flex items-center justify-center text-2xl font-bold mr-4 rtl:ml-4 rtl:mr-0 border-2 border-white shadow-sm">
          <img v-if="employee.photo_url" :src="employee.photo_url" class="h-full w-full object-cover" />
          <span v-else>{{ employee.full_name?.[0] || 'U' }}</span>
        </div>
        <div>
          <h1 class="text-3xl font-bold text-gray-900">{{ employee.full_name }}</h1>
          <p class="text-gray-500">{{ employee.job_title || '-' }} • {{ employee.department || '-' }}</p>
        </div>
      </div>
      <div class="flex gap-3">
        <router-link :to="`/employees/${employee.id}/edit`" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
          </svg>
          Edit Profile
        </router-link>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
      <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
        <p class="text-sm font-medium text-gray-500">Employee PIN</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">{{ employee.employee_pin || '-' }}</p>
      </div>
      <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
        <p class="text-sm font-medium text-gray-500">Status</p>
        <p class="text-2xl font-bold mt-1" :class="employee.status === 'inactive' ? 'text-red-600' : 'text-green-600'">
          {{ (employee.status || 'active').toUpperCase() }}
        </p>
      </div>
      <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
        <p class="text-sm font-medium text-gray-500">Contract End Date</p>
        <p class="text-lg font-bold text-gray-900 mt-1">{{ formatDate(contractEndDate) }}</p>
      </div>
      <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
        <p class="text-sm font-medium text-gray-500">Working Hours (Month)</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">{{ totalWorkingHours }}h</p>
      </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-100 mb-6">
      <div class="border-b border-gray-100 px-4 sm:px-6 py-3 overflow-x-auto">
        <div class="flex min-w-max gap-2">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            @click="activeTab = tab.key"
            class="px-3 py-2 text-sm font-semibold rounded-md transition-colors"
            :class="activeTab === tab.key ? 'bg-[var(--color-brand-primary)] text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
          >
            {{ tab.label }}
          </button>
        </div>
      </div>

      <div class="p-6">
        <div v-if="activeTab === 'personal'" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <InfoRow label="Employee PIN" :value="employee.employee_pin" />
            <InfoRow label="User Type" :value="userType" />
            <InfoRow label="Full Name" :value="employee.full_name" />
            <InfoRow label="Gender" :value="employee.gender" />
            <InfoRow label="Status" :value="(employee.status || 'active').toUpperCase()" />
            <InfoRow label="Date Of Birth" :value="formatDate(employee.date_of_birth)" />
            <InfoRow label="EID Number" :value="employee.emirates_id" />
            <InfoRow label="EID Issue Date" :value="formatDate(employee.emirates_id_issue_date)" />
            <div class="space-y-1">
              <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">EID Expiry Date</p>
              <p class="text-sm font-semibold" :class="getExpiryTextClass(employee.emirates_id_expiry_date)">
                {{ formatDate(employee.emirates_id_expiry_date) }}
                <span v-if="expiryLabel(employee.emirates_id_expiry_date)" class="ml-1 text-xs font-medium">
                  ({{ expiryLabel(employee.emirates_id_expiry_date) }})
                </span>
              </p>
            </div>
            <InfoRow label="Passport Number" :value="employee.passport_number" />
            <InfoRow label="Passport Issue Date" :value="formatDate(employee.passport_issue_date)" />
            <div class="space-y-1">
              <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Passport Expiry Date</p>
              <p class="text-sm font-semibold" :class="getExpiryTextClass(employee.passport_expiry)">
                {{ formatDate(employee.passport_expiry) }}
                <span v-if="expiryLabel(employee.passport_expiry)" class="ml-1 text-xs font-medium">
                  ({{ expiryLabel(employee.passport_expiry) }})
                </span>
              </p>
            </div>
            <InfoRow label="Visa Issue Date" :value="formatDate(employee.visa_issue_date)" />
            <div class="space-y-1">
              <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Visa Expiry Date</p>
              <p class="text-sm font-semibold" :class="getExpiryTextClass(employee.visa_expiry)">
                {{ formatDate(employee.visa_expiry) }}
                <span v-if="expiryLabel(employee.visa_expiry)" class="ml-1 text-xs font-medium">
                  ({{ expiryLabel(employee.visa_expiry) }})
                </span>
              </p>
            </div>
            <InfoRow label="Insurance Start Date" :value="formatDate(employee.insurance_start_date)" />
            <div class="space-y-1">
              <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Insurance End Date</p>
              <p class="text-sm font-semibold" :class="getExpiryTextClass(employee.insurance_end_date)">
                {{ formatDate(employee.insurance_end_date) }}
                <span v-if="expiryLabel(employee.insurance_end_date)" class="ml-1 text-xs font-medium">
                  ({{ expiryLabel(employee.insurance_end_date) }})
                </span>
              </p>
            </div>
            <InfoRow label="Contact Number" :value="employee.phone" />
            <InfoRow label="Department" :value="employee.department" />
            <InfoRow label="Designation" :value="employee.job_title" />
            <InfoRow label="Date Of Joining" :value="formatDate(employee.joining_date)" />
            <InfoRow label="Contract End Date" :value="formatDate(contractEndDate)" />
            <InfoRow label="Email" :value="employee.email" />
            <InfoRow label="Nationality" :value="employee.nationality" />
          </div>

          <div class="border-t border-gray-100 pt-4">
            <p class="text-xs text-gray-500 uppercase font-bold tracking-wider mb-2">Social Profiles</p>
            <div class="flex flex-wrap gap-3">
              <a v-if="employee.linkedin_url" :href="safeUrl(employee.linkedin_url)" target="_blank" class="px-3 py-1.5 text-sm rounded bg-blue-50 text-blue-700 hover:bg-blue-100">LinkedIn</a>
              <a v-if="employee.facebook_url" :href="safeUrl(employee.facebook_url)" target="_blank" class="px-3 py-1.5 text-sm rounded bg-indigo-50 text-indigo-700 hover:bg-indigo-100">Facebook</a>
              <a v-if="employee.x_url" :href="safeUrl(employee.x_url)" target="_blank" class="px-3 py-1.5 text-sm rounded bg-slate-100 text-slate-700 hover:bg-slate-200">X</a>
              <p v-if="!employee.linkedin_url && !employee.facebook_url && !employee.x_url" class="text-sm text-gray-500">No social profiles added.</p>
            </div>
          </div>
        </div>

        <div v-else-if="activeTab === 'address'" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div class="rounded-lg border border-gray-200 p-4">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Permanent Contact Information</h3>
            <InfoRow label="Address" :value="employee.permanent_address" />
            <InfoRow label="City" :value="employee.permanent_city" />
            <InfoRow label="Country" :value="employee.permanent_country" />
          </div>
          <div class="rounded-lg border border-gray-200 p-4">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Present Contact Information</h3>
            <InfoRow label="Address" :value="employee.present_address" />
            <InfoRow label="City" :value="employee.present_city" />
            <InfoRow label="Country" :value="employee.present_country" />
          </div>
        </div>

        <div v-else-if="activeTab === 'education'" class="space-y-6">
          <div class="rounded-lg border border-gray-200 p-4">
            <h3 class="text-sm font-semibold text-gray-900 mb-4">{{ editingEducationId ? 'Edit Education' : 'Add Education' }}</h3>
            <form @submit.prevent="saveEducation" class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm text-gray-700 mb-1">Degree Name</label>
                <input v-model="educationForm.degree_name" required class="w-full px-3 py-2 border border-gray-300 rounded-md" />
              </div>
              <div>
                <label class="block text-sm text-gray-700 mb-1">Institute Name</label>
                <input v-model="educationForm.institute_name" required class="w-full px-3 py-2 border border-gray-300 rounded-md" />
              </div>
              <div>
                <label class="block text-sm text-gray-700 mb-1">Result</label>
                <input v-model="educationForm.result" class="w-full px-3 py-2 border border-gray-300 rounded-md" />
              </div>
              <div>
                <label class="block text-sm text-gray-700 mb-1">Passing Year</label>
                <input v-model.number="educationForm.passing_year" type="number" min="1950" max="2100" required class="w-full px-3 py-2 border border-gray-300 rounded-md" />
              </div>
              <div class="md:col-span-2 flex gap-3">
                <button type="submit" :disabled="savingEducation" class="px-4 py-2 bg-[var(--color-brand-primary)] text-white rounded-md text-sm font-semibold disabled:opacity-50">
                  {{ savingEducation ? 'Saving...' : (editingEducationId ? 'Update Education' : 'Add Education') }}
                </button>
                <button v-if="editingEducationId" type="button" @click="resetEducationForm" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-semibold text-gray-700">
                  Cancel
                </button>
              </div>
            </form>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left whitespace-nowrap">
              <thead class="bg-gray-50 text-xs text-gray-500 uppercase font-medium">
                <tr>
                  <th class="px-4 py-3">ID</th>
                  <th class="px-4 py-3">Certificate Name</th>
                  <th class="px-4 py-3">Institute</th>
                  <th class="px-4 py-3">Result</th>
                  <th class="px-4 py-3">Year</th>
                  <th class="px-4 py-3">Action</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="item in educations" :key="item.id">
                  <td class="px-4 py-3 text-sm text-gray-700">#{{ item.id }}</td>
                  <td class="px-4 py-3 text-sm text-gray-900">{{ item.degree_name }}</td>
                  <td class="px-4 py-3 text-sm text-gray-700">{{ item.institute_name }}</td>
                  <td class="px-4 py-3 text-sm text-gray-700">{{ item.result || '-' }}</td>
                  <td class="px-4 py-3 text-sm text-gray-700">{{ item.passing_year }}</td>
                  <td class="px-4 py-3 text-sm">
                    <button @click="editEducation(item)" class="text-[var(--color-brand-primary)] hover:underline mr-3">Edit</button>
                    <button @click="deleteEducation(item.id)" class="text-red-600 hover:underline">Delete</button>
                  </td>
                </tr>
                <tr v-if="!educations.length">
                  <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">No education records found.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div v-else-if="activeTab === 'experience'" class="space-y-6">
          <div class="rounded-lg border border-gray-200 p-4">
            <h3 class="text-sm font-semibold text-gray-900 mb-4">{{ editingExperienceId ? 'Edit Experience' : 'Add Experience' }}</h3>
            <form @submit.prevent="saveExperience" class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm text-gray-700 mb-1">Company Name</label>
                <input v-model="experienceForm.company_name" required class="w-full px-3 py-2 border border-gray-300 rounded-md" />
              </div>
              <div>
                <label class="block text-sm text-gray-700 mb-1">Position</label>
                <input v-model="experienceForm.position" required class="w-full px-3 py-2 border border-gray-300 rounded-md" />
              </div>
              <div class="md:col-span-2">
                <label class="block text-sm text-gray-700 mb-1">Address (Duty)</label>
                <textarea v-model="experienceForm.duty_address" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md"></textarea>
              </div>
              <div class="md:col-span-2">
                <label class="block text-sm text-gray-700 mb-1">Working Duration</label>
                <input v-model="experienceForm.working_duration" required placeholder="e.g. Jan 2021 - Dec 2024" class="w-full px-3 py-2 border border-gray-300 rounded-md" />
              </div>
              <div class="md:col-span-2 flex gap-3">
                <button type="submit" :disabled="savingExperience" class="px-4 py-2 bg-[var(--color-brand-primary)] text-white rounded-md text-sm font-semibold disabled:opacity-50">
                  {{ savingExperience ? 'Saving...' : (editingExperienceId ? 'Update Experience' : 'Add Experience') }}
                </button>
                <button v-if="editingExperienceId" type="button" @click="resetExperienceForm" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-semibold text-gray-700">
                  Cancel
                </button>
              </div>
            </form>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left whitespace-nowrap">
              <thead class="bg-gray-50 text-xs text-gray-500 uppercase font-medium">
                <tr>
                  <th class="px-4 py-3">ID</th>
                  <th class="px-4 py-3">Company Name</th>
                  <th class="px-4 py-3">Position</th>
                  <th class="px-4 py-3">Work Duration</th>
                  <th class="px-4 py-3">Action</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="item in experiences" :key="item.id">
                  <td class="px-4 py-3 text-sm text-gray-700">#{{ item.id }}</td>
                  <td class="px-4 py-3 text-sm text-gray-900">{{ item.company_name }}</td>
                  <td class="px-4 py-3 text-sm text-gray-700">{{ item.position }}</td>
                  <td class="px-4 py-3 text-sm text-gray-700">{{ item.working_duration }}</td>
                  <td class="px-4 py-3 text-sm">
                    <button @click="editExperience(item)" class="text-[var(--color-brand-primary)] hover:underline mr-3">Edit</button>
                    <button @click="deleteExperience(item.id)" class="text-red-600 hover:underline">Delete</button>
                  </td>
                </tr>
                <tr v-if="!experiences.length">
                  <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">No experience records found.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div v-else-if="activeTab === 'relatives'" class="space-y-6">
          <div class="rounded-lg border border-gray-200 p-4">
            <h3 class="text-sm font-semibold text-gray-900 mb-4">{{ editingRelativeId ? 'Edit Relative' : 'Add Relative' }}</h3>
            <form @submit.prevent="saveRelative" class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm text-gray-700 mb-1">Name</label>
                <input v-model="relativeForm.name" required class="w-full px-3 py-2 border border-gray-300 rounded-md" />
              </div>
              <div>
                <label class="block text-sm text-gray-700 mb-1">Relationship</label>
                <input v-model="relativeForm.relationship" required class="w-full px-3 py-2 border border-gray-300 rounded-md" />
              </div>
              <div>
                <label class="block text-sm text-gray-700 mb-1">Phone Number</label>
                <input v-model="relativeForm.phone" class="w-full px-3 py-2 border border-gray-300 rounded-md" />
              </div>
              <div>
                <label class="block text-sm text-gray-700 mb-1">Address</label>
                <input v-model="relativeForm.address" class="w-full px-3 py-2 border border-gray-300 rounded-md" />
              </div>
              <div class="md:col-span-2 flex gap-3">
                <button type="submit" :disabled="savingRelative" class="px-4 py-2 bg-[var(--color-brand-primary)] text-white rounded-md text-sm font-semibold disabled:opacity-50">
                  {{ savingRelative ? 'Saving...' : (editingRelativeId ? 'Update Relative' : 'Add Relative') }}
                </button>
                <button v-if="editingRelativeId" type="button" @click="resetRelativeForm" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-semibold text-gray-700">
                  Cancel
                </button>
              </div>
            </form>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left whitespace-nowrap">
              <thead class="bg-gray-50 text-xs text-gray-500 uppercase font-medium">
                <tr>
                  <th class="px-4 py-3">ID</th>
                  <th class="px-4 py-3">Name</th>
                  <th class="px-4 py-3">Relationship</th>
                  <th class="px-4 py-3">Phone Number</th>
                  <th class="px-4 py-3">Address</th>
                  <th class="px-4 py-3">Action</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="item in relatives" :key="item.id">
                  <td class="px-4 py-3 text-sm text-gray-700">#{{ item.id }}</td>
                  <td class="px-4 py-3 text-sm text-gray-900">{{ item.name }}</td>
                  <td class="px-4 py-3 text-sm text-gray-700">{{ item.relationship }}</td>
                  <td class="px-4 py-3 text-sm text-gray-700">{{ item.phone || '-' }}</td>
                  <td class="px-4 py-3 text-sm text-gray-700">{{ item.address || '-' }}</td>
                  <td class="px-4 py-3 text-sm">
                    <button @click="editRelative(item)" class="text-[var(--color-brand-primary)] hover:underline mr-3">Edit</button>
                    <button @click="deleteRelative(item.id)" class="text-red-600 hover:underline">Delete</button>
                  </td>
                </tr>
                <tr v-if="!relatives.length">
                  <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">No relative records found.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div v-else-if="activeTab === 'bank'" class="max-w-4xl">
          <div class="rounded-lg border border-gray-200 p-5">
            <h3 class="text-sm font-semibold text-gray-900 mb-4">Bank Account Information</h3>
            <form @submit.prevent="saveBankAccount" class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm text-gray-700 mb-1">Bank Holder Name</label>
                <input v-model="bankAccountForm.bank_holder_name" required class="w-full px-3 py-2 border border-gray-300 rounded-md" />
              </div>
              <div>
                <label class="block text-sm text-gray-700 mb-1">Bank Name</label>
                <input v-model="bankAccountForm.bank_name" required class="w-full px-3 py-2 border border-gray-300 rounded-md" />
              </div>
              <div>
                <label class="block text-sm text-gray-700 mb-1">Branch Name</label>
                <input v-model="bankAccountForm.branch_name" class="w-full px-3 py-2 border border-gray-300 rounded-md" />
              </div>
              <div>
                <label class="block text-sm text-gray-700 mb-1">IBAN Number</label>
                <input v-model="bankAccountForm.iban_number" placeholder="AE..." class="w-full px-3 py-2 border border-gray-300 rounded-md" />
              </div>
              <div>
                <label class="block text-sm text-gray-700 mb-1">Bank Account Number</label>
                <input v-model="bankAccountForm.account_number" required class="w-full px-3 py-2 border border-gray-300 rounded-md" />
              </div>
              <div>
                <label class="block text-sm text-gray-700 mb-1">Bank Account Type</label>
                <input v-model="bankAccountForm.account_type" placeholder="Savings / Current" class="w-full px-3 py-2 border border-gray-300 rounded-md" />
              </div>
              <div class="md:col-span-2 flex gap-3">
                <button type="submit" :disabled="savingBankAccount" class="px-4 py-2 bg-[var(--color-brand-primary)] text-white rounded-md text-sm font-semibold disabled:opacity-50">
                  {{ savingBankAccount ? 'Saving...' : 'Save Bank Account' }}
                </button>
                <button type="button" @click="deleteBankAccount" class="px-4 py-2 border border-red-300 text-red-600 rounded-md text-sm font-semibold hover:bg-red-50">
                  Delete
                </button>
              </div>
            </form>
          </div>
        </div>

        <div v-else-if="activeTab === 'documents'" class="space-y-6">
          <div class="rounded-lg border border-gray-200 p-4">
            <h3 class="text-sm font-semibold text-gray-900 mb-4">{{ editingDocumentId ? 'Edit Document' : 'Add Document' }}</h3>
            <form @submit.prevent="saveDocument" class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm text-gray-700 mb-1">File Title</label>
                <input v-model="documentForm.title" required class="w-full px-3 py-2 border border-gray-300 rounded-md" />
              </div>
              <div>
                <label class="block text-sm text-gray-700 mb-1">{{ editingDocumentId ? 'Replace File (Optional)' : 'File Attachment' }}</label>
                <input type="file" @change="handleDocumentFileChange" :required="!editingDocumentId" class="w-full px-3 py-2 border border-gray-300 rounded-md" />
              </div>
              <div class="md:col-span-2 flex gap-3">
                <button type="submit" :disabled="savingDocument" class="px-4 py-2 bg-[var(--color-brand-primary)] text-white rounded-md text-sm font-semibold disabled:opacity-50">
                  {{ savingDocument ? 'Saving...' : (editingDocumentId ? 'Update Document' : 'Add Document') }}
                </button>
                <button v-if="editingDocumentId" type="button" @click="resetDocumentForm" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-semibold text-gray-700">
                  Cancel
                </button>
              </div>
            </form>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left whitespace-nowrap">
              <thead class="bg-gray-50 text-xs text-gray-500 uppercase font-medium">
                <tr>
                  <th class="px-4 py-3">ID</th>
                  <th class="px-4 py-3">File Title</th>
                  <th class="px-4 py-3">File</th>
                  <th class="px-4 py-3">Action</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="item in documents" :key="item.id">
                  <td class="px-4 py-3 text-sm text-gray-700">#{{ item.id }}</td>
                  <td class="px-4 py-3 text-sm text-gray-900">{{ item.title }}</td>
                  <td class="px-4 py-3 text-sm text-gray-700">
                    <a v-if="item.file_url" :href="item.file_url" target="_blank" class="text-[var(--color-brand-primary)] hover:underline">Download</a>
                    <span v-else>-</span>
                  </td>
                  <td class="px-4 py-3 text-sm">
                    <button @click="editDocument(item)" class="text-[var(--color-brand-primary)] hover:underline mr-3">Edit</button>
                    <button @click="deleteDocument(item.id)" class="text-red-600 hover:underline">Delete</button>
                  </td>
                </tr>
                <tr v-if="!documents.length">
                  <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">No documents found.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div v-else-if="activeTab === 'salary'" class="max-w-5xl space-y-6">
          <div class="rounded-lg border border-gray-200 p-5">
            <h3 class="text-sm font-semibold text-gray-900 mb-4">Salary Structure</h3>
            <div class="mb-4 rounded-md border border-blue-200 bg-blue-50 px-3 py-2 text-sm text-blue-800">
              <p class="font-semibold">{{ salarySourceTitle }}</p>
              <p class="text-xs mt-1">Payroll uses Salary Structure when available. Otherwise it uses Basic Salary from the employee profile.</p>
            </div>
            <form @submit.prevent="saveSalaryStructure" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <div>
                <label class="block text-sm text-gray-700 mb-1">Salary Type</label>
                <select v-model="salaryForm.salary_type" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                  <option value="monthly">Monthly</option>
                  <option value="daily">Daily</option>
                  <option value="hourly">Hourly</option>
                </select>
              </div>
              <div>
                <label class="block text-sm text-gray-700 mb-1">Total Salary</label>
                <input type="number" step="0.01" min="0" v-model="salaryForm.total_salary" class="w-full px-3 py-2 border border-gray-300 rounded-md" />
              </div>
              <div>
                <label class="block text-sm text-gray-700 mb-1">Effective From</label>
                <input type="date" v-model="salaryForm.effective_from" class="w-full px-3 py-2 border border-gray-300 rounded-md" />
              </div>

              <div>
                <label class="block text-sm text-gray-700 mb-1">Basic</label>
                <input type="number" step="0.01" min="0" v-model="salaryForm.basic" required class="w-full px-3 py-2 border border-gray-300 rounded-md" />
              </div>
              <div>
                <label class="block text-sm text-gray-700 mb-1">House Rent</label>
                <input type="number" step="0.01" min="0" v-model="salaryForm.house_rent" class="w-full px-3 py-2 border border-gray-300 rounded-md" />
              </div>
              <div>
                <label class="block text-sm text-gray-700 mb-1">Medical</label>
                <input type="number" step="0.01" min="0" v-model="salaryForm.medical" class="w-full px-3 py-2 border border-gray-300 rounded-md" />
              </div>
              <div>
                <label class="block text-sm text-gray-700 mb-1">Conveyance</label>
                <input type="number" step="0.01" min="0" v-model="salaryForm.conveyance" class="w-full px-3 py-2 border border-gray-300 rounded-md" />
              </div>

              <div>
                <label class="block text-sm text-gray-700 mb-1">Penalty Deduction</label>
                <input type="number" step="0.01" min="0" v-model="salaryForm.deduction_penalty" class="w-full px-3 py-2 border border-gray-300 rounded-md" />
              </div>
              <div>
                <label class="block text-sm text-gray-700 mb-1">Other Deductions</label>
                <input type="number" step="0.01" min="0" v-model="salaryForm.deduction_others" class="w-full px-3 py-2 border border-gray-300 rounded-md" />
              </div>
              <div>
                <label class="block text-sm text-gray-700 mb-1">Advance Payment</label>
                <input type="number" step="0.01" min="0" v-model="salaryForm.advance_payment" class="w-full px-3 py-2 border border-gray-300 rounded-md" />
              </div>

              <div class="lg:col-span-3 bg-gray-50 border border-gray-200 rounded p-3 text-sm text-gray-700">
                Gross preview: <strong>AED {{ salaryGrossPreview.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</strong>
                <span class="mx-2">|</span>
                Net before overtime/lateness: <strong>AED {{ salaryNetPreview.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</strong>
              </div>

              <div class="lg:col-span-3 flex gap-3">
                <button type="submit" :disabled="savingSalaryStructure" class="px-4 py-2 bg-[var(--color-brand-primary)] text-white rounded-md text-sm font-semibold disabled:opacity-50">
                  {{ savingSalaryStructure ? 'Saving...' : 'Save Salary Structure' }}
                </button>
                <button type="button" @click="deleteSalaryStructure" class="px-4 py-2 border border-red-300 text-red-600 rounded-md text-sm font-semibold hover:bg-red-50">
                  Delete
                </button>
              </div>
            </form>
          </div>
        </div>

        <div v-else-if="activeTab === 'attendance'" class="overflow-x-auto">
          <table class="w-full text-left whitespace-nowrap">
            <thead class="bg-gray-50 text-xs text-gray-500 uppercase font-medium">
              <tr>
                <th class="px-4 py-3">Date</th>
                <th class="px-4 py-3">Clock In</th>
                <th class="px-4 py-3">Clock Out</th>
                <th class="px-4 py-3">Location/Store</th>
                <th class="px-4 py-3">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="record in (employee.attendance_records || [])" :key="record.id" class="hover:bg-gray-50">
                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ formatDate(record.date) }}</td>
                <td class="px-4 py-3 text-sm text-gray-600 font-mono">{{ formatTime(record.clock_in_time) }}</td>
                <td class="px-4 py-3 text-sm text-gray-600 font-mono">{{ formatTime(record.clock_out_time) }}</td>
                <td class="px-4 py-3 text-sm text-gray-600">{{ record.store?.name || '-' }}</td>
                <td class="px-4 py-3">
                  <span :class="getStatusClass(record.status)" class="px-2 py-1 text-xs font-semibold rounded-full uppercase">
                    {{ record.status }}
                  </span>
                </td>
              </tr>
              <tr v-if="!(employee.attendance_records || []).length">
                <td colspan="5" class="px-4 py-10 text-center text-gray-500">No attendance records found.</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else-if="activeTab === 'access'" class="max-w-xl">
          <div class="bg-gray-50 border border-gray-200 rounded-lg p-5">
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-base font-semibold text-gray-900">App Access</h3>
              <span v-if="employee.user" class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded bg-green-100 text-green-700">Active</span>
              <span v-else class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded bg-gray-100 text-gray-400">No Account</span>
            </div>

            <div v-if="employee.user" class="space-y-3">
              <InfoRow label="Login Email" :value="employee.user.email" />
              <InfoRow label="Role" :value="displayMobileRole(employee.user)" />
              <button @click="openPasswordModal(true)" class="text-sm text-[var(--color-brand-primary)] hover:underline font-medium">Edit Access</button>
            </div>
            <div v-else>
              <p class="text-sm text-gray-600 mb-4">This employee does not have a mobile app account yet.</p>
              <button @click="openPasswordModal(false)" class="w-full py-2 px-4 bg-gray-900 text-white rounded-md text-sm font-semibold hover:bg-gray-800 transition-colors">
                Create App Account
              </button>
            </div>
          </div>
        </div>

        <div v-else-if="activeTab === 'assignment'" class="max-w-xl">
          <div class="bg-gray-50 border border-gray-200 rounded-lg p-5">
            <h3 class="text-base font-semibold text-gray-900 mb-4">Department & Assignment</h3>
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                <select
                  v-model="assignmentForm.department"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-1 focus:ring-[var(--color-brand-primary)] focus:border-[var(--color-brand-primary)]"
                >
                  <option v-for="option in departmentOptions" :key="option" :value="option">{{ option }}</option>
                </select>
              </div>

              <div v-if="assignmentPolicy.show_brand">
                <label class="block text-sm font-medium text-gray-700 mb-1">Brand</label>
                <select v-if="!isManagerAssignmentRole" v-model="assignmentForm.brand_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-1 focus:ring-[var(--color-brand-primary)] focus:border-[var(--color-brand-primary)]">
                  <option :value="null">Select Brand</option>
                  <option v-for="brand in brands" :key="brand.id" :value="brand.id">{{ brand.name }}</option>
                </select>
                <div v-else class="border border-gray-200 rounded-md max-h-40 overflow-auto bg-white">
                  <label
                    v-for="brand in brands"
                    :key="`mgr-brand-${brand.id}`"
                    class="flex items-center gap-2 px-3 py-2 border-b last:border-b-0 border-gray-100 hover:bg-gray-50"
                  >
                    <input
                      type="checkbox"
                      :value="brand.id"
                      v-model="assignmentForm.brand_ids"
                      class="rounded border-gray-300 text-[var(--color-brand-primary)] focus:ring-[var(--color-brand-primary)]"
                    />
                    <span class="text-sm text-gray-700">{{ brand.name }}</span>
                  </label>
                </div>
                <p v-if="isManagerAssignmentRole" class="mt-1 text-xs text-gray-500">Managers can be assigned to multiple brands.</p>
                <p v-if="assignmentPolicy.requires_brand" class="mt-1 text-xs text-amber-600">Brand is required for this department.</p>
              </div>

              <div v-if="assignmentPolicy.show_store && !isManagerAssignmentRole">
                <label class="block text-sm font-medium text-gray-700 mb-1">Assigned Store</label>
                <select
                  v-model="assignmentForm.store_id"
                  :disabled="!assignmentForm.brand_id"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-1 focus:ring-[var(--color-brand-primary)] focus:border-[var(--color-brand-primary)] disabled:bg-gray-100"
                >
                  <option :value="null">{{ assignmentForm.brand_id ? 'No Store' : 'Select Brand First' }}</option>
                  <option v-for="option in assignmentStoreOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                </select>
                <p v-if="showAreaFallbackHint" class="mt-1 text-xs text-gray-500">
                  No branches found for this brand. Selecting area will auto-create branch on save.
                </p>
                <p v-if="assignmentPolicy.requires_store" class="mt-1 text-xs text-amber-600">Store is required for this department.</p>
              </div>

              <p v-if="assignmentPolicy.note" class="text-xs text-gray-500">{{ assignmentPolicy.note }}</p>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Position</label>
                <input
                  v-model="assignmentForm.position"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-1 focus:ring-[var(--color-brand-primary)] focus:border-[var(--color-brand-primary)]"
                  placeholder="e.g. Area Manager"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mobile App Role</label>
                <select
                  v-model="assignmentForm.mobile_role"
                  :disabled="!employee.user"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-1 focus:ring-[var(--color-brand-primary)] focus:border-[var(--color-brand-primary)] disabled:bg-gray-100"
                >
                  <option value="staff">Staff</option>
                  <option value="supervisor">Supervisor</option>
                  <option value="manager">Manager</option>
                  <option value="sales-team">Sales Team</option>
                </select>
                <p v-if="!employee.user" class="mt-1 text-xs text-gray-500">Create app account first to assign mobile role.</p>
              </div>

              <button
                @click="saveAssignments"
                :disabled="savingAssignments"
                class="w-full py-2 px-4 bg-[var(--color-brand-primary)] text-white rounded-md text-sm font-semibold disabled:opacity-50"
              >
                {{ savingAssignments ? 'Saving...' : 'Save Assignment' }}
              </button>
            </div>
          </div>
        </div>

      </div>
    </div>

    <div v-if="showAccountModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-2xl max-w-md w-full overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
          <h3 class="font-bold text-gray-900">{{ isReset ? 'Edit Mobile Access' : 'Create Mobile Account' }}</h3>
          <button @click="showAccountModal = false" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <form @submit.prevent="handlePasswordSubmit" class="p-6 space-y-4">
          <p v-if="!isReset" class="text-sm text-gray-600">
            Account will be created for <strong>{{ employee.full_name }}</strong> using email <strong>{{ employee.email }}</strong>.
          </p>
          <p v-else class="text-sm text-gray-600">
            Update login email or reset password for <strong>{{ employee.full_name }}</strong>.
          </p>
          <div v-if="isReset">
            <label class="block text-sm font-medium text-gray-700 mb-1">Login Email</label>
            <input type="email" v-model="accountForm.email" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-1 focus:ring-[var(--color-brand-primary)] focus:border-[var(--color-brand-primary)]" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Mobile App Role</label>
            <select v-model="accountForm.mobile_role" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-1 focus:ring-[var(--color-brand-primary)] focus:border-[var(--color-brand-primary)]">
              <option value="staff">Staff</option>
              <option value="supervisor">Supervisor</option>
              <option value="manager">Manager</option>
              <option value="sales-team">Sales Team</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ isReset ? 'New Password' : 'Password' }}</label>
            <input type="password" v-model="accountForm.password" :required="!isReset" minlength="8" placeholder="Min 8 characters (Leave empty to keep current)" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-1 focus:ring-[var(--color-brand-primary)] focus:border-[var(--color-brand-primary)]" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
            <input type="password" v-model="accountForm.password_confirmation" :required="!!accountForm.password && !isReset" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-1 focus:ring-[var(--color-brand-primary)] focus:border-[var(--color-brand-primary)]" />
          </div>

          <div class="flex gap-3 pt-2">
            <button type="button" @click="showAccountModal = false" class="flex-1 py-2 px-4 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
              Cancel
            </button>
            <button type="submit" :disabled="creatingAccount" class="flex-1 py-2 px-4 bg-[var(--color-brand-primary)] text-white rounded-md text-sm font-bold disabled:opacity-50">
              {{ creatingAccount ? 'Processing...' : (isReset ? 'Save Changes' : 'Create Account') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div v-else class="text-center py-20">
    <h2 class="text-2xl font-bold text-gray-400">Employee not found</h2>
    <router-link to="/employees" class="text-blue-600 hover:underline mt-4 block">Back to Directory</router-link>
  </div>
</template>

<script setup>
import { computed, defineComponent, h, onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const employee = ref(null);
const loading = ref(true);
const activeTab = ref('personal');

const tabs = [
  { key: 'personal', label: 'Personal Info' },
  { key: 'address', label: 'Address' },
  { key: 'education', label: 'Education' },
  { key: 'experience', label: 'Experience' },
  { key: 'relatives', label: 'Relatives' },
  { key: 'bank', label: 'Bank Account' },
  { key: 'documents', label: 'Documents' },
  { key: 'salary', label: 'Salary' },
  { key: 'attendance', label: 'Attendance' },
  { key: 'assignment', label: 'Department & Assignment' },
  { key: 'access', label: 'App Access' },
];

const loadedTabs = ref({
  education: false,
  experience: false,
  relatives: false,
  bank: false,
  documents: false,
  salary: false,
});

const showAccountModal = ref(false);
const creatingAccount = ref(false);
const isReset = ref(false);
const savingAssignments = ref(false);
const stores = ref([]);
const brands = ref([]);
const storesByBrandData = ref([]);
const assignmentRules = ref([]);

const accountForm = ref({
  email: '',
  password: '',
  password_confirmation: '',
  mobile_role: 'staff',
});

const assignmentForm = ref({
  department: '',
  brand_id: null,
  brand_ids: [],
  store_id: null,
  position: '',
  mobile_role: 'staff',
});

const educations = ref([]);
const experiences = ref([]);
const relatives = ref([]);
const documents = ref([]);

const savingEducation = ref(false);
const savingExperience = ref(false);
const savingRelative = ref(false);
const savingBankAccount = ref(false);
const savingDocument = ref(false);
const savingSalaryStructure = ref(false);

const editingEducationId = ref(null);
const editingExperienceId = ref(null);
const editingRelativeId = ref(null);
const editingDocumentId = ref(null);

const educationForm = ref({
  degree_name: '',
  institute_name: '',
  result: '',
  passing_year: '',
});

const experienceForm = ref({
  company_name: '',
  position: '',
  duty_address: '',
  working_duration: '',
});

const relativeForm = ref({
  name: '',
  relationship: '',
  phone: '',
  address: '',
});

const bankAccountForm = ref({
  bank_holder_name: '',
  bank_name: '',
  branch_name: '',
  iban_number: '',
  account_number: '',
  account_type: '',
});

const documentForm = ref({
  title: '',
  file: null,
});

const salaryForm = ref({
  salary_type: 'monthly',
  total_salary: '',
  basic: '',
  house_rent: '',
  medical: '',
  conveyance: '',
  deduction_penalty: '',
  deduction_others: '',
  advance_payment: '',
  effective_from: '',
});

const InfoRow = defineComponent({
  name: 'InfoRow',
  props: {
    label: { type: String, required: true },
    value: { type: [String, Number], default: '-' },
  },
  setup(props) {
    return () =>
      h('div', { class: 'space-y-1' }, [
        h('p', { class: 'text-xs text-gray-500 uppercase font-bold tracking-wider' }, props.label),
        h('p', { class: 'text-sm text-gray-900' }, props.value || '-'),
      ]);
  },
});

const getUserMobileRole = (user) => {
  const roles = user?.role_names || user?.roles?.map((r) => r.name) || [];
  const normalized = roles.map((r) => String(r).toLowerCase().trim());
  if (normalized.includes('sales-team')) return 'sales-team';
  if (normalized.includes('manager')) return 'manager';
  if (normalized.includes('shift-supervisor')) return 'supervisor';
  if (normalized.includes('supervisor')) return 'supervisor';
  return 'staff';
};

const displayMobileRole = (user) => {
  const role = getUserMobileRole(user);
  if (role === 'sales-team') return 'Sales Team';
  return role.charAt(0).toUpperCase() + role.slice(1);
};

const userType = computed(() => {
  const user = employee.value?.user;
  if (!user) return 'Employee';

  const roles = user?.role_names || user?.roles?.map((r) => r.name) || [];
  const normalized = roles.map((r) => String(r).toLowerCase().trim());

  if (normalized.some((r) => ['superadmin', 'super-admin', 'super_admin', 'super admin'].includes(r))) {
    return 'Super Admin';
  }
  if (normalized.includes('admin')) {
    return 'HR';
  }
  return 'Employee';
});

const contractEndDate = computed(() => {
  const contracts = employee.value?.contracts || [];
  if (!contracts.length) return null;
  const sorted = [...contracts].sort((a, b) => new Date(b.end_date) - new Date(a.end_date));
  return sorted[0]?.end_date || null;
});

const totalWorkingHours = computed(() => {
  if (!employee.value?.attendance_records) return 0;

  let totalMinutes = 0;
  employee.value.attendance_records.forEach((rec) => {
    if (rec.clock_in_time && rec.clock_out_time) {
      const start = new Date(rec.clock_in_time);
      const end = new Date(rec.clock_out_time);
      totalMinutes += (end - start) / (1000 * 60);
    }
  });
  return Math.round(totalMinutes / 60);
});

const departmentOptions = computed(() => {
  const options = assignmentRules.value.map((item) => item?.name).filter(Boolean);
  if (employee.value?.department && !options.includes(employee.value.department)) {
    options.push(employee.value.department);
  }
  return options.length ? options : ['Operations', 'Food & Beverage', 'Digital Marketing', 'Finance', 'HR', 'Warehouse'];
});

const assignmentPolicy = computed(() => {
  const selectedDepartment = String(assignmentForm.value.department || '').trim().toLowerCase();
  const matched = assignmentRules.value.find(
    (item) => String(item?.name || '').trim().toLowerCase() === selectedDepartment
  );

  return matched?.policy || {
    show_brand: true,
    show_store: true,
    requires_brand: false,
    requires_store: false,
    note: 'Brand and store are optional for this department.',
  };
});

const isManagerAssignmentRole = computed(() => assignmentForm.value.mobile_role === 'manager');

const filteredStores = computed(() => {
  if (!assignmentForm.value.brand_id) return [];
  const source = storesByBrandData.value.length ? storesByBrandData.value : stores.value;
  return source.filter((store) => Number(store.brand_id) === Number(assignmentForm.value.brand_id));
});

const assignmentStoreOptions = computed(() => {
  if (!assignmentForm.value.brand_id) return [];

  if (filteredStores.value.length) {
    return filteredStores.value.map((store) => ({
      value: String(store.id),
      label: store.name,
      type: 'store',
    }));
  }

  const brand = brands.value.find((item) => Number(item.id) === Number(assignmentForm.value.brand_id));
  const areas = brand?.areas || [];
  return areas.map((area) => ({
    value: `area:${brand.id}:${area.id}`,
    label: area.name,
    type: 'area',
  }));
});

const showAreaFallbackHint = computed(() => {
  return Boolean(assignmentForm.value.brand_id) && filteredStores.value.length === 0 && assignmentStoreOptions.value.length > 0;
});

const parseMoney = (value) => {
  const num = Number(value);
  return Number.isFinite(num) ? num : 0;
};

const salaryGrossPreview = computed(() => (
  parseMoney(salaryForm.value.basic)
  + parseMoney(salaryForm.value.house_rent)
  + parseMoney(salaryForm.value.medical)
  + parseMoney(salaryForm.value.conveyance)
));

const salaryNetPreview = computed(() => (
  salaryGrossPreview.value
  - parseMoney(salaryForm.value.deduction_penalty)
  - parseMoney(salaryForm.value.deduction_others)
  - parseMoney(salaryForm.value.advance_payment)
));

const salarySourceTitle = computed(() => {
  if (employee.value?.salary_structure) {
    return 'Source: Salary Structure';
  }
  const base = parseMoney(employee.value?.basic_salary);
  return `Source: Employee Basic Salary (AED ${base.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })})`;
});

const resetBankAccountForm = () => {
  bankAccountForm.value = {
    bank_holder_name: '',
    bank_name: '',
    branch_name: '',
    iban_number: '',
    account_number: '',
    account_type: '',
  };
};

const applyBankAccountForm = (payload) => {
  bankAccountForm.value = {
    bank_holder_name: payload?.bank_holder_name || '',
    bank_name: payload?.bank_name || '',
    branch_name: payload?.branch_name || '',
    iban_number: payload?.iban_number || '',
    account_number: payload?.account_number || '',
    account_type: payload?.account_type || '',
  };
};

const resetDocumentForm = () => {
  editingDocumentId.value = null;
  documentForm.value = {
    title: '',
    file: null,
  };
};

const applySalaryForm = (payload) => {
  const fallbackBasic = employee.value?.basic_salary ?? '';
  salaryForm.value = {
    salary_type: payload?.salary_type || 'monthly',
    total_salary: payload?.total_salary ?? '',
    basic: payload?.basic ?? fallbackBasic,
    house_rent: payload?.house_rent ?? '',
    medical: payload?.medical ?? '',
    conveyance: payload?.conveyance ?? '',
    deduction_penalty: payload?.deduction_penalty ?? '',
    deduction_others: payload?.deduction_others ?? '',
    advance_payment: payload?.advance_payment ?? '',
    effective_from: payload?.effective_from || '',
  };
};

const safeUrl = (url) => {
  if (!url) return '';
  return /^https?:\/\//i.test(url) ? url : `https://${url}`;
};

const parseDateValue = (value) => {
  if (!value) return null;
  if (typeof value === 'string') {
    const normalized = value.slice(0, 10);
    const parsed = new Date(`${normalized}T00:00:00`);
    return Number.isNaN(parsed.getTime()) ? null : parsed;
  }
  const parsed = new Date(value);
  return Number.isNaN(parsed.getTime()) ? null : parsed;
};

const getDaysUntil = (date) => {
  const target = parseDateValue(date);
  if (!target) return null;

  const today = new Date();
  today.setHours(0, 0, 0, 0);

  return Math.ceil((target.getTime() - today.getTime()) / (1000 * 60 * 60 * 24));
};

const expiryLabel = (date) => {
  const days = getDaysUntil(date);
  if (days === null) return '';
  if (days < 0) return `${Math.abs(days)} days overdue`;
  if (days === 0) return 'Expires today';
  return `${days} days remaining`;
};

const getExpiryTextClass = (date) => {
  const days = getDaysUntil(date);
  if (days === null) return 'text-gray-900';
  if (days <= 60) return 'text-red-600';
  if (days <= 90) return 'text-orange-500';
  return 'text-gray-900';
};

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' });
};

const formatTime = (time) => {
  if (!time) return '--:--';
  return new Date(time).toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit', hour12: true });
};

const getStatusClass = (status) => {
  switch (status) {
    case 'present':
      return 'bg-green-100 text-green-700';
    case 'late':
      return 'bg-yellow-100 text-yellow-700';
    case 'absent':
      return 'bg-red-100 text-red-700';
    default:
      return 'bg-gray-100 text-gray-700';
  }
};

const fetchDetails = async () => {
  try {
    loading.value = true;
    const response = await axios.get(`/employees/${route.params.id}`);
    employee.value = response.data;

    educations.value = employee.value?.educations || [];
    experiences.value = employee.value?.experiences || [];
    relatives.value = employee.value?.relatives || [];
    documents.value = employee.value?.documents || [];
    applyBankAccountForm(employee.value?.bank_account || null);
    applySalaryForm(employee.value?.salary_structure || null);

    loadedTabs.value.education = true;
    loadedTabs.value.experience = true;
    loadedTabs.value.relatives = true;
    loadedTabs.value.bank = true;
    loadedTabs.value.documents = true;
    loadedTabs.value.salary = true;

    if (employee.value?.user?.email) {
      accountForm.value.email = employee.value.user.email;
      accountForm.value.mobile_role = getUserMobileRole(employee.value.user);
      assignmentForm.value.mobile_role = getUserMobileRole(employee.value.user);
    } else {
      accountForm.value.email = '';
      accountForm.value.mobile_role = 'staff';
      assignmentForm.value.mobile_role = 'staff';
    }

    assignmentForm.value.store_id = employee.value?.store_id ? String(employee.value.store_id) : null;
    assignmentForm.value.brand_id = employee.value?.store?.brand_id ?? null;
    assignmentForm.value.brand_ids = brands.value
      .filter((brand) => Number(brand.manager_user_id) === Number(employee.value?.user?.id))
      .map((brand) => Number(brand.id));
    assignmentForm.value.position = employee.value?.job_title || '';
    assignmentForm.value.department = employee.value?.department || '';
  } catch (err) {
    console.error('Error fetching employee details:', err);
  } finally {
    loading.value = false;
  }
};

const fetchStores = async () => {
  try {
    const { data } = await axios.get('/stores');
    stores.value = Array.isArray(data?.data) ? data.data : (Array.isArray(data) ? data : []);
  } catch (err) {
    console.error('Error fetching stores:', err);
    stores.value = [];
  }
};

const fetchBrands = async () => {
  try {
    const { data } = await axios.get('/brands');
    const brandRows = Array.isArray(data?.data) ? data.data : (Array.isArray(data) ? data : []);
    brands.value = brandRows;
    if (employee.value?.user?.id) {
      assignmentForm.value.brand_ids = brandRows
        .filter((brand) => Number(brand.manager_user_id) === Number(employee.value.user.id))
        .map((brand) => Number(brand.id));
    }

    const flattened = [];
    const seen = new Set();
    for (const brand of brandRows) {
      for (const store of brand?.stores || []) {
        if (!store?.id || seen.has(store.id)) continue;
        seen.add(store.id);
        flattened.push({
          id: store.id,
          name: store.name,
          brand_id: brand.id,
        });
      }

      for (const area of brand?.areas || []) {
        for (const store of area?.stores || []) {
          if (!store?.id || seen.has(store.id)) continue;
          seen.add(store.id);
          flattened.push({
            id: store.id,
            name: store.name,
            brand_id: brand.id,
          });
        }
      }
    }
    storesByBrandData.value = flattened;
  } catch (err) {
    console.error('Error fetching brands:', err);
    brands.value = [];
    storesByBrandData.value = [];
  }
};

const fetchAssignmentRules = async () => {
  try {
    const { data } = await axios.get('/employees/assignment-rules');
    assignmentRules.value = Array.isArray(data?.departments) ? data.departments : [];
  } catch (err) {
    console.error('Error fetching assignment rules:', err);
    assignmentRules.value = [];
  }
};

const fetchEducations = async () => {
  try {
    const response = await axios.get(`/employees/${route.params.id}/educations`);
    educations.value = response.data.data || [];
    loadedTabs.value.education = true;
  } catch (err) {
    console.error('Failed to fetch educations:', err);
  }
};

const fetchExperiences = async () => {
  try {
    const response = await axios.get(`/employees/${route.params.id}/experiences`);
    experiences.value = response.data.data || [];
    loadedTabs.value.experience = true;
  } catch (err) {
    console.error('Failed to fetch experiences:', err);
  }
};

const fetchRelatives = async () => {
  try {
    const response = await axios.get(`/employees/${route.params.id}/relatives`);
    relatives.value = response.data.data || [];
    loadedTabs.value.relatives = true;
  } catch (err) {
    console.error('Failed to fetch relatives:', err);
  }
};

const fetchBankAccount = async () => {
  try {
    const response = await axios.get(`/employees/${route.params.id}/bank-account`);
    applyBankAccountForm(response.data.data || null);
    loadedTabs.value.bank = true;
  } catch (err) {
    console.error('Failed to fetch bank account:', err);
  }
};

const fetchDocuments = async () => {
  try {
    const response = await axios.get(`/employees/${route.params.id}/documents`);
    documents.value = response.data.data || [];
    loadedTabs.value.documents = true;
  } catch (err) {
    console.error('Failed to fetch documents:', err);
  }
};

const fetchSalaryStructure = async () => {
  try {
    const response = await axios.get(`/employees/${route.params.id}/salary-structure`);
    applySalaryForm(response.data.data || null);
    loadedTabs.value.salary = true;
  } catch (err) {
    console.error('Failed to fetch salary structure:', err);
  }
};

const resetEducationForm = () => {
  editingEducationId.value = null;
  educationForm.value = {
    degree_name: '',
    institute_name: '',
    result: '',
    passing_year: '',
  };
};

const editEducation = (education) => {
  editingEducationId.value = education.id;
  educationForm.value = {
    degree_name: education.degree_name || '',
    institute_name: education.institute_name || '',
    result: education.result || '',
    passing_year: education.passing_year || '',
  };
};

const saveEducation = async () => {
  if (!educationForm.value.degree_name || !educationForm.value.institute_name || !educationForm.value.passing_year) {
    alert('Please complete all required education fields.');
    return;
  }

  try {
    savingEducation.value = true;
    const payload = {
      ...educationForm.value,
      passing_year: Number(educationForm.value.passing_year),
    };

    if (editingEducationId.value) {
      await axios.put(`/employees/${route.params.id}/educations/${editingEducationId.value}`, payload);
    } else {
      await axios.post(`/employees/${route.params.id}/educations`, payload);
    }

    await fetchEducations();
    resetEducationForm();
  } catch (err) {
    console.error('Failed to save education:', err);
    alert(err.response?.data?.message || 'Failed to save education.');
  } finally {
    savingEducation.value = false;
  }
};

const deleteEducation = async (educationId) => {
  if (!confirm('Delete this education record?')) return;

  try {
    await axios.delete(`/employees/${route.params.id}/educations/${educationId}`);
    await fetchEducations();
    if (editingEducationId.value === educationId) {
      resetEducationForm();
    }
  } catch (err) {
    console.error('Failed to delete education:', err);
    alert(err.response?.data?.message || 'Failed to delete education.');
  }
};

const resetExperienceForm = () => {
  editingExperienceId.value = null;
  experienceForm.value = {
    company_name: '',
    position: '',
    duty_address: '',
    working_duration: '',
  };
};

const editExperience = (experience) => {
  editingExperienceId.value = experience.id;
  experienceForm.value = {
    company_name: experience.company_name || '',
    position: experience.position || '',
    duty_address: experience.duty_address || '',
    working_duration: experience.working_duration || '',
  };
};

const saveExperience = async () => {
  if (!experienceForm.value.company_name || !experienceForm.value.position || !experienceForm.value.working_duration) {
    alert('Please complete all required experience fields.');
    return;
  }

  try {
    savingExperience.value = true;

    if (editingExperienceId.value) {
      await axios.put(`/employees/${route.params.id}/experiences/${editingExperienceId.value}`, experienceForm.value);
    } else {
      await axios.post(`/employees/${route.params.id}/experiences`, experienceForm.value);
    }

    await fetchExperiences();
    resetExperienceForm();
  } catch (err) {
    console.error('Failed to save experience:', err);
    alert(err.response?.data?.message || 'Failed to save experience.');
  } finally {
    savingExperience.value = false;
  }
};

const deleteExperience = async (experienceId) => {
  if (!confirm('Delete this experience record?')) return;

  try {
    await axios.delete(`/employees/${route.params.id}/experiences/${experienceId}`);
    await fetchExperiences();
    if (editingExperienceId.value === experienceId) {
      resetExperienceForm();
    }
  } catch (err) {
    console.error('Failed to delete experience:', err);
    alert(err.response?.data?.message || 'Failed to delete experience.');
  }
};

const resetRelativeForm = () => {
  editingRelativeId.value = null;
  relativeForm.value = {
    name: '',
    relationship: '',
    phone: '',
    address: '',
  };
};

const editRelative = (relative) => {
  editingRelativeId.value = relative.id;
  relativeForm.value = {
    name: relative.name || '',
    relationship: relative.relationship || '',
    phone: relative.phone || '',
    address: relative.address || '',
  };
};

const saveRelative = async () => {
  if (!relativeForm.value.name || !relativeForm.value.relationship) {
    alert('Please complete required relative fields.');
    return;
  }

  try {
    savingRelative.value = true;

    if (editingRelativeId.value) {
      await axios.put(`/employees/${route.params.id}/relatives/${editingRelativeId.value}`, relativeForm.value);
    } else {
      await axios.post(`/employees/${route.params.id}/relatives`, relativeForm.value);
    }

    await fetchRelatives();
    resetRelativeForm();
  } catch (err) {
    console.error('Failed to save relative:', err);
    alert(err.response?.data?.message || 'Failed to save relative.');
  } finally {
    savingRelative.value = false;
  }
};

const deleteRelative = async (relativeId) => {
  if (!confirm('Delete this relative record?')) return;

  try {
    await axios.delete(`/employees/${route.params.id}/relatives/${relativeId}`);
    await fetchRelatives();
    if (editingRelativeId.value === relativeId) {
      resetRelativeForm();
    }
  } catch (err) {
    console.error('Failed to delete relative:', err);
    alert(err.response?.data?.message || 'Failed to delete relative.');
  }
};

const saveBankAccount = async () => {
  if (!bankAccountForm.value.bank_holder_name || !bankAccountForm.value.bank_name || !bankAccountForm.value.account_number) {
    alert('Please complete required bank account fields.');
    return;
  }

  try {
    savingBankAccount.value = true;
    await axios.put(`/employees/${route.params.id}/bank-account`, bankAccountForm.value);
    await fetchBankAccount();
    alert('Bank account saved successfully.');
  } catch (err) {
    console.error('Failed to save bank account:', err);
    alert(err.response?.data?.message || 'Failed to save bank account.');
  } finally {
    savingBankAccount.value = false;
  }
};

const deleteBankAccount = async () => {
  if (!confirm('Delete this bank account?')) return;

  try {
    await axios.delete(`/employees/${route.params.id}/bank-account`);
    resetBankAccountForm();
    alert('Bank account deleted successfully.');
  } catch (err) {
    console.error('Failed to delete bank account:', err);
    alert(err.response?.data?.message || 'Failed to delete bank account.');
  }
};

const handleDocumentFileChange = (event) => {
  documentForm.value.file = event.target.files?.[0] || null;
};

const editDocument = (item) => {
  editingDocumentId.value = item.id;
  documentForm.value = {
    title: item.title || '',
    file: null,
  };
};

const saveDocument = async () => {
  if (!documentForm.value.title) {
    alert('Please enter document title.');
    return;
  }

  if (!editingDocumentId.value && !documentForm.value.file) {
    alert('Please attach a file.');
    return;
  }

  try {
    savingDocument.value = true;
    const payload = new FormData();
    payload.append('title', documentForm.value.title);
    if (documentForm.value.file) {
      payload.append('file', documentForm.value.file);
    }

    if (editingDocumentId.value) {
      payload.append('_method', 'PUT');
      await axios.post(`/employees/${route.params.id}/documents/${editingDocumentId.value}`, payload);
    } else {
      await axios.post(`/employees/${route.params.id}/documents`, payload);
    }

    await fetchDocuments();
    resetDocumentForm();
  } catch (err) {
    console.error('Failed to save document:', err);
    alert(err.response?.data?.message || 'Failed to save document.');
  } finally {
    savingDocument.value = false;
  }
};

const deleteDocument = async (documentId) => {
  if (!confirm('Delete this document?')) return;

  try {
    await axios.delete(`/employees/${route.params.id}/documents/${documentId}`);
    await fetchDocuments();
    if (editingDocumentId.value === documentId) {
      resetDocumentForm();
    }
  } catch (err) {
    console.error('Failed to delete document:', err);
    alert(err.response?.data?.message || 'Failed to delete document.');
  }
};

const saveSalaryStructure = async () => {
  if (!salaryForm.value.salary_type || salaryForm.value.basic === '') {
    alert('Please complete required salary fields.');
    return;
  }

  try {
    savingSalaryStructure.value = true;
    const payload = {
      salary_type: salaryForm.value.salary_type,
      total_salary: salaryForm.value.total_salary === '' ? null : parseMoney(salaryForm.value.total_salary),
      basic: parseMoney(salaryForm.value.basic),
      house_rent: parseMoney(salaryForm.value.house_rent),
      medical: parseMoney(salaryForm.value.medical),
      conveyance: parseMoney(salaryForm.value.conveyance),
      deduction_penalty: parseMoney(salaryForm.value.deduction_penalty),
      deduction_others: parseMoney(salaryForm.value.deduction_others),
      advance_payment: parseMoney(salaryForm.value.advance_payment),
      effective_from: salaryForm.value.effective_from || null,
    };

    await axios.put(`/employees/${route.params.id}/salary-structure`, payload);
    await fetchSalaryStructure();
    alert('Salary structure saved successfully.');
  } catch (err) {
    console.error('Failed to save salary structure:', err);
    alert(err.response?.data?.message || 'Failed to save salary structure.');
  } finally {
    savingSalaryStructure.value = false;
  }
};

const deleteSalaryStructure = async () => {
  if (!confirm('Delete this salary structure?')) return;

  try {
    await axios.delete(`/employees/${route.params.id}/salary-structure`);
    applySalaryForm(null);
    alert('Salary structure deleted successfully.');
  } catch (err) {
    console.error('Failed to delete salary structure:', err);
    alert(err.response?.data?.message || 'Failed to delete salary structure.');
  }
};

const openPasswordModal = (reset = false) => {
  isReset.value = reset;
  if (reset && employee.value && employee.value.user) {
    accountForm.value.email = employee.value.user.email;
    accountForm.value.mobile_role = getUserMobileRole(employee.value.user);
  } else {
    accountForm.value.email = '';
    accountForm.value.mobile_role = 'staff';
  }
  accountForm.value.password = '';
  accountForm.value.password_confirmation = '';
  showAccountModal.value = true;
};

const handlePasswordSubmit = async () => {
  if (accountForm.value.password !== accountForm.value.password_confirmation) {
    alert('Passwords do not match');
    return;
  }

  try {
    creatingAccount.value = true;
    const endpoint = isReset.value ? 'reset-credentials' : 'create-account';
    await axios.post(`/employees/${route.params.id}/${endpoint}`, accountForm.value);
    showAccountModal.value = false;
    accountForm.value = { email: '', password: '', password_confirmation: '', mobile_role: 'staff' };
    await fetchDetails();
    alert(isReset.value ? 'Mobile access updated successfully!' : 'Mobile account created successfully!');
  } catch (err) {
    console.error('Operation failed:', err);
    alert(err.response?.data?.message || 'Operation failed');
  } finally {
    creatingAccount.value = false;
  }
};

const saveAssignments = async () => {
  try {
    savingAssignments.value = true;
    const managerRole = isManagerAssignmentRole.value;
    let resolvedStoreId = managerRole ? null : (assignmentPolicy.value.show_store ? assignmentForm.value.store_id : null);

    if (!assignmentForm.value.department) {
      throw new Error('Department is required.');
    }

    if (managerRole && assignmentPolicy.value.show_brand && assignmentForm.value.brand_ids.length === 0) {
      throw new Error('At least one brand is required for manager role.');
    }

    if (!managerRole && assignmentPolicy.value.requires_brand && !assignmentForm.value.brand_id) {
      throw new Error('Brand is required for this department.');
    }

    if (!managerRole && assignmentPolicy.value.requires_store && !resolvedStoreId) {
      throw new Error('Store is required for this department.');
    }

    if (resolvedStoreId && String(resolvedStoreId).startsWith('area:')) {
      const [, brandIdRaw, areaIdRaw] = String(resolvedStoreId).split(':');
      const brandId = Number(brandIdRaw);
      const areaId = Number(areaIdRaw);
      const brand = brands.value.find((item) => Number(item.id) === brandId);
      const area = brand?.areas?.find((item) => Number(item.id) === areaId);

      if (!brand || !area) {
        throw new Error('Selected area is invalid.');
      }

      const createResponse = await axios.post(`/brands/${brand.id}/areas/${area.id}/stores`, {
        name: area.name,
      });
      const createdStore = createResponse?.data?.data || createResponse?.data;
      resolvedStoreId = createdStore?.id ? String(createdStore.id) : null;

      await Promise.all([fetchBrands(), fetchStores()]);
    }

    const payload = {
      department: assignmentForm.value.department,
      job_title: assignmentForm.value.position || null,
      brand_id: managerRole
        ? null
        : (assignmentPolicy.value.show_brand && assignmentForm.value.brand_id ? Number(assignmentForm.value.brand_id) : null),
      store_id: managerRole ? null : (resolvedStoreId ? Number(resolvedStoreId) : null),
    };

    if (employee.value?.user) {
      payload.mobile_role = assignmentForm.value.mobile_role || 'staff';
    }

    await axios.patch(`/employees/${route.params.id}`, payload);

    if (employee.value?.user?.id) {
      const managerUserId = Number(employee.value.user.id);
      if (managerRole && assignmentPolicy.value.show_brand) {
        const selectedBrandIds = new Set(assignmentForm.value.brand_ids.map((id) => Number(id)));
        const updates = brands.value
          .filter((brand) => {
            const currentlyAssigned = Number(brand.manager_user_id) === managerUserId;
            const shouldAssign = selectedBrandIds.has(Number(brand.id));
            return currentlyAssigned !== shouldAssign;
          })
          .map((brand) => {
            const shouldAssign = selectedBrandIds.has(Number(brand.id));
            return axios.put(`/brands/${brand.id}`, {
              name: brand.name,
              manager_user_id: shouldAssign ? managerUserId : null,
            });
          });
        if (updates.length) {
          await Promise.all(updates);
        }
      } else {
        const assignedToUser = brands.value.filter((brand) => Number(brand.manager_user_id) === managerUserId);
        if (assignedToUser.length) {
          await Promise.all(
            assignedToUser.map((brand) => axios.put(`/brands/${brand.id}`, {
              name: brand.name,
              manager_user_id: null,
            }))
          );
        }
      }
    }

    await fetchDetails();
    await fetchBrands();
    alert('Assignments updated successfully.');
  } catch (err) {
    console.error('Failed to update assignments:', err);
    alert(err.response?.data?.message || 'Failed to update assignments.');
  } finally {
    savingAssignments.value = false;
  }
};

watch(
  () => assignmentForm.value.brand_id,
  (nextBrandId, prevBrandId) => {
    if (isManagerAssignmentRole.value) {
      assignmentForm.value.store_id = null;
      return;
    }
    if (nextBrandId === prevBrandId) return;
    if (!assignmentPolicy.value.show_store) {
      assignmentForm.value.store_id = null;
      return;
    }

    // Prevent wiping store_id during initial load when stores aren't fetched yet
    if (stores.value.length === 0 && storesByBrandData.value.length === 0) return;

    const selectedValue = assignmentForm.value.store_id;
    if (!selectedValue) return;

    if (String(selectedValue).startsWith('area:')) {
      const [, brandIdRaw] = String(selectedValue).split(':');
      if (Number(brandIdRaw) !== Number(nextBrandId)) {
        assignmentForm.value.store_id = null;
      }
      return;
    }

    const source = storesByBrandData.value.length ? storesByBrandData.value : stores.value;
    const selectedStore = source.find((store) => Number(store.id) === Number(selectedValue));
    if (!selectedStore || Number(selectedStore.brand_id) !== Number(nextBrandId)) {
      assignmentForm.value.store_id = null;
    }
  }
);

watch(
  () => assignmentForm.value.department,
  () => {
    if (!assignmentPolicy.value.show_brand) {
      assignmentForm.value.brand_id = null;
      assignmentForm.value.store_id = null;
      return;
    }

    if (!assignmentPolicy.value.show_store) {
      assignmentForm.value.store_id = null;
    }
  }
);

watch(
  () => assignmentForm.value.mobile_role,
  (role) => {
    if (role === 'manager') {
      assignmentForm.value.store_id = null;
      assignmentForm.value.brand_id = null;
      return;
    }
    assignmentForm.value.brand_ids = [];
  }
);

watch(activeTab, async (tab) => {
  if (tab === 'education' && !loadedTabs.value.education) {
    await fetchEducations();
  }
  if (tab === 'experience' && !loadedTabs.value.experience) {
    await fetchExperiences();
  }
  if (tab === 'relatives' && !loadedTabs.value.relatives) {
    await fetchRelatives();
  }
  if (tab === 'bank' && !loadedTabs.value.bank) {
    await fetchBankAccount();
  }
  if (tab === 'documents' && !loadedTabs.value.documents) {
    await fetchDocuments();
  }
  if (tab === 'salary' && !loadedTabs.value.salary) {
    await fetchSalaryStructure();
  }
});

onMounted(async () => {
  await Promise.all([fetchDetails(), fetchStores(), fetchBrands(), fetchAssignmentRules()]);
});
</script>

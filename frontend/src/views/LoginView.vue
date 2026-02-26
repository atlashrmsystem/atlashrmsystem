<template>
  <div class="min-h-screen flex items-center justify-center bg-[var(--color-brand-bg)] px-4 sm:px-6 lg:px-8 font-sans">
    <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-lg shadow-sm border border-gray-100">
      
      <!-- Language Switcher -->
      <div class="flex justify-end">
        <button 
          @click="toggleLanguage" 
          class="text-sm font-medium text-gray-500 hover:text-[var(--color-brand-primary)] transition-colors"
        >
          {{ currentLocale === 'en' ? 'العربية' : 'English' }}
        </button>
      </div>

      <div class="text-center">
        <!-- Placeholder for Atlas Logo -->
        <div class="mx-auto h-12 w-12 bg-[var(--color-brand-primary)] text-white rounded-full flex items-center justify-center text-xl font-bold mb-4">
          A
        </div>
        <h2 class="mt-2 text-3xl font-extrabold text-gray-900">
          {{ $t('login.title') }}
        </h2>
      </div>
      
      <form class="mt-8 space-y-6" @submit.prevent="handleLogin">
        <div class="rounded-md space-y-4">
          <div>
            <label for="email" class="block text-sm font-medium text-gray-700">
              {{ $t('login.email') }}
            </label>
            <input 
              id="email" 
              v-model="email" 
              name="email" 
              type="email" 
              required 
              class="appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[var(--color-brand-primary)] focus:border-transparent sm:text-sm mt-1 bg-white" 
            />
          </div>
          <div>
            <label for="password" class="block text-sm font-medium text-gray-700">
              {{ $t('login.password') }}
            </label>
            <input 
              id="password" 
              v-model="password" 
              name="password" 
              type="password" 
              required 
              class="appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[var(--color-brand-primary)] focus:border-transparent sm:text-sm mt-1 bg-white" 
            />
          </div>
        </div>

        <div v-if="errorMessage" class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
          {{ errorMessage }}
        </div>

        <div>
          <button 
            type="submit" 
            :disabled="isSubmitting || retryAfterSeconds > 0"
            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-[var(--color-brand-primary)] hover:bg-[var(--color-brand-hover)] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--color-brand-primary)] transition-colors cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed"
          >
            {{ retryAfterSeconds > 0 ? `Try again in ${retryAfterSeconds}s` : $t('login.button') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onUnmounted } from 'vue';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import { setDocumentDirection } from '@/i18n';
import { useRouter } from 'vue-router';

const { t, locale } = useI18n();
const router = useRouter();
const email = ref('');
const password = ref('');
const errorMessage = ref('');
const isSubmitting = ref(false);
const retryAfterSeconds = ref(0);
let retryTimer = null;

const currentLocale = computed(() => locale.value);

const toggleLanguage = () => {
  const newLocale = locale.value === 'en' ? 'ar' : 'en';
  locale.value = newLocale;
  setDocumentDirection(newLocale);
};

const startRetryCountdown = (seconds) => {
  const safeSeconds = Number.isFinite(seconds) && seconds > 0 ? Math.floor(seconds) : 0;
  retryAfterSeconds.value = safeSeconds;

  if (retryTimer) {
    clearInterval(retryTimer);
    retryTimer = null;
  }

  if (safeSeconds <= 0) {
    return;
  }

  retryTimer = setInterval(() => {
    if (retryAfterSeconds.value <= 1) {
      clearInterval(retryTimer);
      retryTimer = null;
      retryAfterSeconds.value = 0;
      return;
    }
    retryAfterSeconds.value -= 1;
  }, 1000);
};

const handleLogin = async () => {
  errorMessage.value = '';
  isSubmitting.value = true;
  try {
    // API token login does not require Sanctum CSRF cookie route.
    const response = await axios.post('/login', {
      email: email.value,
      password: password.value
    });
    
    console.log('Login success:', response.data);
    
    // Save token if needed, then redirect to Dashboard
    if (response.data.access_token) {
        sessionStorage.setItem('auth_token', response.data.access_token);
        // Persist permissions from login payload as a fallback for role detection.
        sessionStorage.setItem('auth_permissions', JSON.stringify(response.data.permissions || []));

        let resolvedUser = response.data.user || null;
        const permissionList = Array.isArray(response.data.permissions) ? response.data.permissions : [];
        try {
          // Fetch canonical authenticated user with roles relation.
          const meResp = await axios.get('/user');
          if (meResp?.data) {
            resolvedUser = meResp.data;
          }
        } catch (meError) {
          console.warn('Failed to fetch /user after login, falling back to login payload user.', meError);
        }

        sessionStorage.setItem('auth_user', JSON.stringify(resolvedUser || {}));
        const roles = resolvedUser?.role_names || resolvedUser?.roles?.map(r => r.name) || [];
        const normalizedRoles = roles.map(r => String(r).toLowerCase().trim());
        const isSuperAdmin = normalizedRoles.some(r => ['superadmin', 'super-admin', 'super_admin', 'super admin'].includes(r));
        const isAdmin = normalizedRoles.includes('admin');
        if (isAdmin || isSuperAdmin) {
          router.push('/');
          return;
        }
    }
    
    router.push('/');
  } catch (error) {
    const status = error?.response?.status;
    const apiMessage = error?.response?.data?.message || 'Login failed';

    if (status === 429) {
      const retryAfterHeader = Number(error?.response?.headers?.['retry-after']);
      startRetryCountdown(retryAfterHeader);
      errorMessage.value = apiMessage;
    } else {
      errorMessage.value = apiMessage;
    }

    console.error('Login failed:', apiMessage);
  } finally {
    isSubmitting.value = false;
  }
};

onUnmounted(() => {
  if (retryTimer) {
    clearInterval(retryTimer);
  }
});
</script>

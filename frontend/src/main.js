import './assets/tailwind.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import axios from 'axios'

import App from './App.vue'
import router from './router'
import i18n, { setDocumentDirection } from './i18n'

// Enforce fresh login on every app load.
sessionStorage.removeItem('auth_token');
sessionStorage.removeItem('auth_user');
sessionStorage.removeItem('auth_permissions');
localStorage.removeItem('auth_token');
localStorage.removeItem('auth_user');
localStorage.removeItem('auth_permissions');

// Global Axios configuration
const normalizeApiBaseUrl = (value) =>
  (value || '').trim().replace(/\/+$/, '');

const envApiBaseUrl = normalizeApiBaseUrl(import.meta.env.VITE_API_BASE_URL);
const isLocalHost = ['localhost', '127.0.0.1'].includes(window.location.hostname);
const fallbackApiBaseUrl = isLocalHost
  ? `${window.location.protocol}//${window.location.hostname}:8000/api`
  : `${window.location.origin}/api`;

axios.defaults.baseURL = envApiBaseUrl || fallbackApiBaseUrl;
axios.defaults.withCredentials = false;
axios.defaults.headers.common.Accept = 'application/json';

// Add a request interceptor to attach the Bearer token.
axios.interceptors.request.use(config => {
  const token = sessionStorage.getItem('auth_token');
  config.headers = config.headers || {};
  config.headers.Accept = 'application/json';

  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

axios.interceptors.response.use(
  response => response,
  error => {
    if (error?.response?.status === 401) {
      sessionStorage.removeItem('auth_token');
      sessionStorage.removeItem('auth_user');
      sessionStorage.removeItem('auth_permissions');
      localStorage.removeItem('auth_token');
      localStorage.removeItem('auth_user');
      localStorage.removeItem('auth_permissions');

      if (router.currentRoute.value.path !== '/login') {
        router.push('/login');
      }
    }

    return Promise.reject(error);
  }
);

// Global Fetch interceptor to catch native fetch() calls and append the token
const originalFetch = window.fetch;
window.fetch = async (...args) => {
  let [resource, config] = args;
  if (typeof resource === 'string' && resource.includes('/api/')) {
    const token = sessionStorage.getItem('auth_token');
    if (token) {
      config = config || {};
      config.headers = {
        ...config.headers,
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      };
    }
  }
  return originalFetch(resource, config);
};

setDocumentDirection(i18n.global.locale.value)

const app = createApp(App)

app.use(createPinia())
app.use(router)
app.use(i18n)

app.mount('#app')

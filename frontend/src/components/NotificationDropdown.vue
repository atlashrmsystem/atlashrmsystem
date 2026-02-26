<template>
  <div class="relative">
    <button @click="toggleMenu" class="relative p-2 text-gray-400 hover:text-gray-500 rounded-full focus:outline-none focus:ring-2 focus:ring-[var(--color-brand-primary)]">
      <span class="sr-only">View notifications</span>
      <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
      </svg>
      <!-- Unread Badge -->
      <span v-if="unreadCount > 0" class="absolute top-1 right-1 block h-4 w-4 rounded-full bg-red-600 text-white text-[10px] font-bold flex items-center justify-center border-2 border-white ring-2 ring-white">
        {{ unreadCount > 9 ? '9+' : unreadCount }}
      </span>
    </button>

    <!-- Dropdown Menu -->
    <div v-if="isOpen" class="origin-top-right absolute right-0 mt-2 w-80 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50 overflow-hidden">
      <div class="px-4 py-3 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
        <h3 class="text-sm font-semibold text-gray-900">Notifications</h3>
        <button v-if="unreadCount > 0" @click="markAllAsRead" class="text-xs text-[var(--color-brand-primary)] hover:underline font-medium">
          Mark all as read
        </button>
      </div>
      
      <div class="max-h-96 overflow-y-auto">
        <div v-if="loading" class="p-4 text-center text-sm text-gray-500">
          <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-[var(--color-brand-primary)] mx-auto mb-2"></div>
          Loading...
        </div>
        
        <div v-else-if="notifications.length === 0" class="p-8 text-center flex flex-col items-center">
            <svg class="w-10 h-10 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
            <p class="text-sm text-gray-500">No recent notifications</p>
        </div>

        <ul v-else class="divide-y divide-gray-100">
            <li v-for="notif in notifications" :key="notif.id" 
                @click="markAsRead(notif.id)"
                class="px-4 py-3 hover:bg-gray-50 cursor-pointer transition flex items-start"
                :class="{'bg-blue-50/30': !notif.read_at}">
                
                <div class="flex-shrink-0 mr-3">
                    <span class="inline-flex items-center justify-center h-8 w-8 rounded-full" 
                        :class="getIconColors(notif.type)">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                    </span>
                </div>
                
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate" :class="{'font-bold': !notif.read_at}">
                        {{ notif.data.title || 'Notification' }}
                    </p>
                    <p class="text-xs text-gray-500 line-clamp-2 mt-0.5">
                        {{ notif.data.message || 'You have a new alert.' }}
                    </p>
                    <p class="text-[10px] text-gray-400 mt-1 uppercase tracking-wider">
                        {{ formatTimeAgo(notif.created_at) }}
                    </p>
                </div>
                
                <div v-if="!notif.read_at" class="flex-shrink-0 ml-2 mt-1.5">
                    <div class="h-2 w-2 rounded-full bg-[var(--color-brand-primary)]"></div>
                </div>
            </li>
        </ul>
      </div>
      
      <div class="px-4 py-2 border-t border-gray-100 bg-gray-50 text-center">
        <a href="#" class="text-xs font-medium text-gray-500 hover:text-gray-700">View all notifications</a>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

const isOpen = ref(false);
const notifications = ref([]);
const unreadCount = ref(0);
const loading = ref(true);

const toggleMenu = () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value && notifications.value.length === 0) {
        fetchNotifications();
    }
};

const closeMenu = (e) => {
    if (!e.target.closest('.relative')) {
        isOpen.value = false;
    }
};

const fetchNotifications = async () => {
    loading.value = true;
    try {
        const { data } = await axios.get('/notifications');
        notifications.value = data.data || [];
        unreadCount.value = data.unread_count || 0;
    } catch (error) {
        console.error("Error fetching notifications:", error);
    } finally {
        loading.value = false;
    }
};

const markAsRead = async (id) => {
    const notif = notifications.value.find(n => n.id === id);
    if (!notif || notif.read_at) return;

    try {
        await axios.post(`/notifications/${id}/read`);
        
        notif.read_at = new Date().toISOString();
        unreadCount.value = Math.max(0, unreadCount.value - 1);
    } catch (error) {
        console.error("Error marking read:", error);
    }
};

const markAllAsRead = async () => {
    try {
        await axios.post('/notifications/mark-all-read');
        
        notifications.value.forEach(n => n.read_at = n.read_at || new Date().toISOString());
        unreadCount.value = 0;
    } catch (error) {
        console.error("Error marking all read:", error);
    }
};

// Generic Icon Helper
// Instead of rendering JSX, we will just use a single SVG in the template
// So we no longer need the getIcon function, we can just render the SVG directly in the template.

const getIconColors = (type) => {
    if (type?.includes('Leave')) return 'bg-orange-100 text-orange-600';
    if (type?.includes('Document')) return 'bg-red-100 text-red-600';
    if (type?.includes('Payroll')) return 'bg-green-100 text-green-600';
    return 'bg-blue-100 text-blue-600';
};

const formatTimeAgo = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMins / 60);
    const diffDays = Math.floor(diffHours / 24);

    if (diffDays > 0) return `${diffDays}d ago`;
    if (diffHours > 0) return `${diffHours}h ago`;
    if (diffMins > 0) return `${diffMins}m ago`;
    return 'Just now';
};

onMounted(() => {
    fetchNotifications();
    document.addEventListener('click', closeMenu);
});

onUnmounted(() => {
    document.removeEventListener('click', closeMenu);
});
</script>

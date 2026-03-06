<template>
  <div class="flex h-screen bg-zinc-950 text-zinc-100 overflow-hidden">
    <!-- Sidebar -->
    <aside
        :class="[
        'flex flex-col border-r border-zinc-800 bg-zinc-900 transition-all duration-300 shrink-0',
        sidebarOpen ? 'w-64' : 'w-16',
      ]"
    >
      <!-- Logo -->
      <div class="flex items-center gap-3 px-4 py-5 border-b border-zinc-800 h-16">
        <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center shrink-0">
          <Package class="w-4 h-4 text-white" />
        </div>
        <span v-if="sidebarOpen" class="font-bold text-sm tracking-wide text-white truncate">
          InvenSys
        </span>
      </div>

      <!-- Nav -->
      <nav class="flex-1 py-4 px-2 space-y-1 overflow-y-auto">
        <RouterLink
            v-for="item in navItems"
            :key="item.to"
            :to="item.to"
            class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors"
            :class="
            isActive(item.to)
              ? 'bg-indigo-600 text-white'
              : 'text-zinc-400 hover:bg-zinc-800 hover:text-zinc-100'
          "
        >
          <component :is="item.icon" class="w-4 h-4 shrink-0" />
          <span v-if="sidebarOpen" class="truncate">{{ item.label }}</span>
        </RouterLink>
      </nav>

      <!-- User info -->
      <div class="border-t border-zinc-800 p-3">
        <div class="flex items-center gap-3">
          <div
              class="w-8 h-8 rounded-full bg-indigo-700 flex items-center justify-center text-xs font-bold text-white shrink-0"
          >
            {{ userInitials }}
          </div>
          <div v-if="sidebarOpen" class="flex-1 min-w-0">
            <p class="text-xs font-semibold text-zinc-200 truncate">{{ auth.user?.name }}</p>
            <span :class="roleBadgeClass" class="text-[10px] px-1.5 py-0.5 rounded-full font-semibold capitalize">
              {{ auth.user?.role }}
            </span>
          </div>
          <button
              v-if="sidebarOpen"
              @click="handleLogout"
              class="p-1.5 rounded-md text-zinc-500 hover:text-red-400 hover:bg-zinc-800 transition-colors"
              title="Logout"
          >
            <LogOut class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>
    </aside>

    <!-- Main -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
      <!-- Topbar -->
      <header class="h-16 border-b border-zinc-800 bg-zinc-900 flex items-center gap-4 px-6 shrink-0">
        <button
            @click="sidebarOpen = !sidebarOpen"
            class="p-2 rounded-lg text-zinc-400 hover:bg-zinc-800 hover:text-zinc-100 transition-colors"
        >
          <Menu class="w-4 h-4" />
        </button>
        <div class="flex-1">
          <h1 class="text-sm font-semibold text-zinc-100">{{ currentPageTitle }}</h1>
          <p class="text-xs text-zinc-500">Inventory Management System</p>
        </div>

        <!-- Role indicator -->
        <div class="flex items-center gap-2 px-3 py-1.5 bg-zinc-800 rounded-lg border border-zinc-700">
          <div :class="roleIndicatorColor" class="w-2 h-2 rounded-full"></div>
          <span class="text-xs text-zinc-400 capitalize">{{ auth.user?.role }}</span>
        </div>
      </header>

      <!-- Page -->
      <main class="flex-1 overflow-y-auto p-6">
        <RouterView />
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router'
import {
  LayoutDashboard, Package, Tag, Warehouse,
  BoxesIcon, Users, UserCheck, ShoppingCart, Menu, LogOut,
} from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()
const sidebarOpen = ref(true)

const navItems = [
  { to: '/',             label: 'Dashboard',  icon: LayoutDashboard },
  { to: '/products',     label: 'Produk',     icon: Package },
  { to: '/categories',   label: 'Kategori',   icon: Tag },
  { to: '/warehouses',   label: 'Gudang',     icon: Warehouse },
  { to: '/inventory',    label: 'Inventaris', icon: BoxesIcon },
  { to: '/customers',    label: 'Customer',   icon: Users },
  { to: '/employees',    label: 'Karyawan',   icon: UserCheck },
  { to: '/transactions', label: 'Transaksi',  icon: ShoppingCart },
]

const pageTitles: Record<string, string> = {
  '/':             'Dashboard',
  '/products':     'Manajemen Produk',
  '/categories':   'Manajemen Kategori',
  '/warehouses':   'Manajemen Gudang',
  '/inventory':    'Manajemen Inventaris',
  '/customers':    'Manajemen Customer',
  '/employees':    'Manajemen Karyawan',
  '/transactions': 'Manajemen Transaksi',
}

const currentPageTitle = computed(() => pageTitles[route.path] ?? 'Inventory System')
const isActive = (path: string) =>
    path === '/' ? route.path === '/' : route.path.startsWith(path)

const userInitials = computed(() =>
    (auth.user?.name ?? '').split(' ').map((n) => n[0]).join('').toUpperCase().slice(0, 2)
)

const roleBadgeClass = computed(() => {
  const map: Record<string, string> = {
    admin:   'bg-red-950 text-red-400',
    manager: 'bg-indigo-950 text-indigo-400',
    staff:   'bg-emerald-950 text-emerald-400',
    viewer:  'bg-zinc-700 text-zinc-400',
  }
  return map[auth.user?.role ?? ''] ?? ''
})

const roleIndicatorColor = computed(() => {
  const map: Record<string, string> = {
    admin:   'bg-red-400',
    manager: 'bg-indigo-400',
    staff:   'bg-emerald-400',
    viewer:  'bg-zinc-500',
  }
  return map[auth.user?.role ?? ''] ?? 'bg-zinc-500'
})

async function handleLogout() {
  await auth.logout()
  router.push('/login')
}
</script>
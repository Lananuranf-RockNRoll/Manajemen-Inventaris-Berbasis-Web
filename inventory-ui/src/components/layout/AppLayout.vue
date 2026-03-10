<template>
  <div class="flex h-screen bg-zinc-950 text-zinc-100 overflow-hidden">

    <!-- ── Idle Logout Warning Modal ── -->
    <Transition name="fade">
      <div v-if="showWarn" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm">
        <div class="bg-zinc-900 border border-zinc-700 rounded-2xl p-8 w-full max-w-sm mx-4 text-center shadow-2xl">
          <!-- Icon countdown ring -->
          <div class="relative w-20 h-20 mx-auto mb-5">
            <svg class="w-20 h-20 -rotate-90" viewBox="0 0 80 80">
              <circle cx="40" cy="40" r="34" fill="none" stroke="#3f3f46" stroke-width="6"/>
              <circle cx="40" cy="40" r="34" fill="none" stroke="#f59e0b" stroke-width="6"
                stroke-dasharray="213.6"
                :stroke-dashoffset="213.6 - (213.6 * countdown / 30)"
                class="transition-all duration-1000"/>
            </svg>
            <span class="absolute inset-0 flex items-center justify-center text-2xl font-bold text-amber-400">
              {{ countdown }}
            </span>
          </div>
          <h3 class="text-base font-bold text-zinc-100 mb-2">Sesi Hampir Berakhir</h3>
          <p class="text-sm text-zinc-400 mb-6 leading-relaxed">
            Anda tidak aktif selama beberapa saat.<br>
            Otomatis logout dalam <span class="text-amber-400 font-semibold">{{ countdown }} detik</span>.
          </p>
          <button @click="stayActive"
            class="w-full py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-sm transition-colors">
            Tetap Masuk
          </button>
        </div>
      </div>
    </Transition>

    <!-- Mobile overlay -->
    <Transition name="fade">
      <div v-if="mobileOpen" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-20 lg:hidden" @click="mobileOpen = false" />
    </Transition>

    <!-- Sidebar -->
    <aside :class="[
      'fixed lg:relative z-30 flex flex-col border-r border-zinc-800 bg-zinc-900 transition-all duration-300 shrink-0 h-full',
      sidebarOpen ? 'lg:w-64' : 'lg:w-16',
      mobileOpen ? 'translate-x-0 w-72' : '-translate-x-full lg:translate-x-0',
    ]">
      <!-- Logo -->
      <div class="flex items-center gap-3 px-4 py-5 border-b border-zinc-800 h-16 shrink-0">
        <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center shrink-0">
          <Package class="w-4 h-4 text-white" />
        </div>
        <span v-if="sidebarOpen || mobileOpen" class="font-bold text-sm tracking-wide text-white truncate">InvenSys</span>
        <button v-if="mobileOpen" @click="mobileOpen = false"
          class="ml-auto p-1 rounded-md text-zinc-400 hover:text-zinc-100 lg:hidden">
          <X class="w-4 h-4" />
        </button>
      </div>

      <!-- Nav -->
      <nav class="flex-1 py-4 px-2 space-y-1 overflow-y-auto" role="navigation">
        <RouterLink v-for="item in visibleNavItems" :key="item.to" :to="item.to" @click="mobileOpen = false"
          class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors"
          :class="isActive(item.to)
            ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-900/40'
            : 'text-zinc-400 hover:bg-zinc-800 hover:text-zinc-100'"
          :title="!sidebarOpen && !mobileOpen ? item.label : undefined">
          <component :is="item.icon" class="w-4 h-4 shrink-0" />
          <span v-if="sidebarOpen || mobileOpen" class="truncate">{{ item.label }}</span>
        </RouterLink>
      </nav>

      <!-- User info footer -->
      <div class="border-t border-zinc-800 p-3 shrink-0">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-full bg-indigo-700 flex items-center justify-center text-xs font-bold text-white shrink-0 select-none" :title="auth.user?.name">
            {{ userInitials }}
          </div>
          <div v-if="sidebarOpen || mobileOpen" class="flex-1 min-w-0">
            <p class="text-xs font-semibold text-zinc-200 truncate">{{ auth.user?.name }}</p>
            <span :class="roleBadgeClass" class="text-[10px] px-1.5 py-0.5 rounded-full font-semibold capitalize">
              {{ auth.user?.role }}
            </span>
          </div>
          <button v-if="sidebarOpen || mobileOpen" @click="handleLogout"
            class="p-1.5 rounded-md text-zinc-500 hover:text-red-400 hover:bg-zinc-800 transition-colors shrink-0"
            title="Logout">
            <LogOut class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>
    </aside>

    <!-- Main content area -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
      <!-- Topbar -->
      <header class="h-16 border-b border-zinc-800 bg-zinc-900 flex items-center gap-3 px-4 lg:px-6 shrink-0">
        <button @click="mobileOpen = !mobileOpen"
          class="p-2 rounded-lg text-zinc-400 hover:bg-zinc-800 hover:text-zinc-100 transition-colors lg:hidden">
          <Menu class="w-4 h-4" />
        </button>
        <button @click="sidebarOpen = !sidebarOpen"
          class="p-2 rounded-lg text-zinc-400 hover:bg-zinc-800 hover:text-zinc-100 transition-colors hidden lg:flex">
          <Menu class="w-4 h-4" />
        </button>
        <div class="flex-1 min-w-0">
          <h1 class="text-sm font-semibold text-zinc-100 truncate">{{ currentPageTitle }}</h1>
          <p class="text-xs text-zinc-500 hidden sm:block">Inventory Management System</p>
        </div>
        <!-- Role indicator -->
        <div class="flex items-center gap-2 px-2.5 py-1.5 bg-zinc-800 rounded-lg border border-zinc-700 shrink-0">
          <div :class="roleIndicatorColor" class="w-2 h-2 rounded-full shrink-0" />
          <span class="text-xs text-zinc-400 capitalize hidden sm:block">{{ auth.user?.role }}</span>
        </div>
      </header>

      <!-- Page content -->
      <main class="flex-1 overflow-y-auto p-4 lg:p-6" id="main-content">
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
  BoxesIcon, Users, UserCheck, ShoppingCart, Menu, LogOut, X, UserCog,
} from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import { useIdleLogout } from '@/composables/useIdleLogout'

const auth        = useAuthStore()
const route       = useRoute()
const router      = useRouter()
const sidebarOpen = ref(true)
const mobileOpen  = ref(false)

// ── Idle logout (3 menit) ────────────────────────────────────────────────────
const { showWarn, countdown, stayActive } = useIdleLogout()

// ── Navigation ───────────────────────────────────────────────────────────────
const allNavItems = [
  { to: '/',             label: 'Dashboard',      icon: LayoutDashboard, roles: ['admin', 'manager', 'staff', 'viewer'] },
  { to: '/products',     label: 'Produk',         icon: Package,         roles: ['admin', 'manager', 'staff', 'viewer'] },
  { to: '/categories',   label: 'Kategori',       icon: Tag,             roles: ['admin', 'manager', 'staff', 'viewer'] },
  { to: '/warehouses',   label: 'Gudang',         icon: Warehouse,       roles: ['admin', 'manager', 'staff', 'viewer'] },
  { to: '/inventory',    label: 'Inventaris',     icon: BoxesIcon,       roles: ['admin', 'manager', 'staff', 'viewer'] },
  { to: '/customers',    label: 'Customer',       icon: Users,           roles: ['admin', 'manager', 'staff', 'viewer'] },
  { to: '/employees',    label: 'Karyawan',       icon: UserCheck,       roles: ['admin', 'manager', 'staff', 'viewer'] },
  { to: '/transactions', label: 'Transaksi',      icon: ShoppingCart,    roles: ['admin', 'manager', 'staff', 'viewer'] },
  { to: '/users',        label: 'Manajemen User', icon: UserCog,         roles: ['admin'] },
]

const visibleNavItems = computed(() =>
  allNavItems.filter(item => item.roles.includes(auth.user?.role ?? ''))
)

const pageTitles: Record<string, string> = {
  '/':             'Dashboard Analytics',
  '/products':     'Manajemen Produk',
  '/categories':   'Manajemen Kategori',
  '/warehouses':   'Manajemen Gudang',
  '/inventory':    'Manajemen Inventaris',
  '/customers':    'Manajemen Customer',
  '/employees':    'Manajemen Karyawan',
  '/transactions': 'Manajemen Transaksi',
  '/users':        'Manajemen User',
}

const currentPageTitle = computed(() => pageTitles[route.path] ?? 'Inventory System')
const isActive = (path: string): boolean => path === '/' ? route.path === '/' : route.path.startsWith(path)

const userInitials = computed(() =>
  (auth.user?.name ?? '').split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase()
)

const roleBadgeClass = computed((): string => ({
  admin:   'bg-red-950 text-red-400',
  manager: 'bg-indigo-950 text-indigo-400',
  staff:   'bg-emerald-950 text-emerald-400',
  viewer:  'bg-zinc-700 text-zinc-400',
}[auth.user?.role ?? ''] ?? ''))

const roleIndicatorColor = computed((): string => ({
  admin:   'bg-red-400',
  manager: 'bg-indigo-400',
  staff:   'bg-emerald-400',
  viewer:  'bg-zinc-500',
}[auth.user?.role ?? ''] ?? 'bg-zinc-500'))

async function handleLogout(): Promise<void> {
  await auth.logout()
  router.push('/login')
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>

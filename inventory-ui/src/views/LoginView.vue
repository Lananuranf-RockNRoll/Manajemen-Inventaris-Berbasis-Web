<template>
  <div class="min-h-screen bg-zinc-950 flex items-center justify-center p-4">
    <div class="w-full max-w-sm">
      <!-- Logo -->
      <div class="text-center mb-8">
        <div class="w-12 h-12 rounded-2xl bg-indigo-600 flex items-center justify-center mx-auto mb-4">
          <Package class="w-6 h-6 text-white" />
        </div>
        <h1 class="text-2xl font-bold text-white">InvenSys</h1>
        <p class="text-zinc-500 text-sm mt-1">Inventory Management System</p>
      </div>

      <!-- Card -->
      <div class="bg-zinc-900 border border-zinc-800 rounded-2xl p-6">
        <h2 class="text-lg font-semibold text-zinc-100 mb-6">Masuk ke akun Anda</h2>

        <form @submit.prevent="handleLogin" class="space-y-4">
          <div class="space-y-1.5">
            <label class="label">Email</label>
            <input
                v-model="form.email"
                type="email"
                placeholder="email@inventory.test"
                required
                class="input-field w-full"
            />
          </div>

          <div class="space-y-1.5">
            <label class="label">Password</label>
            <input
                v-model="form.password"
                type="password"
                placeholder="••••••••"
                required
                class="input-field w-full"
            />
          </div>

          <p v-if="error" class="text-red-400 text-xs bg-red-950/50 border border-red-900 rounded-lg px-3 py-2">
            {{ error }}
          </p>

          <button
              type="submit"
              :disabled="loading"
              class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-semibold rounded-lg transition-colors"
          >
            <span v-if="loading">Memproses...</span>
            <span v-else>Masuk</span>
          </button>
        </form>

        <!-- Demo Accounts -->
        <div class="mt-6 pt-5 border-t border-zinc-800">
          <p class="text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-3">
            Akun Demo — Klik untuk isi otomatis
          </p>
          <div class="space-y-2">
            <button
                v-for="acc in demoAccounts"
                :key="acc.email"
                @click="fillDemo(acc)"
                class="w-full text-left px-3 py-2.5 rounded-xl bg-zinc-800/50 hover:bg-zinc-800 border border-zinc-700/50 hover:border-zinc-600 transition-all group"
            >
              <div class="flex items-center justify-between">
                <div>
                  <div class="flex items-center gap-2 mb-0.5">
                    <span class="text-xs font-bold text-zinc-200">{{ acc.label }}</span>
                    <span :class="roleClass(acc.role)" class="text-[10px] px-1.5 py-0.5 rounded-full font-semibold">
                      {{ acc.role }}
                    </span>
                  </div>
                  <span class="text-xs text-zinc-500">{{ acc.email }}</span>
                </div>
                <div class="text-right">
                  <p class="text-[10px] text-zinc-600">password</p>
                  <p class="text-xs font-mono text-zinc-400">password</p>
                </div>
              </div>
              <!-- Permission badges -->
              <div class="flex gap-1 mt-2 flex-wrap">
                <span
                    v-for="perm in acc.permissions"
                    :key="perm"
                    class="text-[9px] px-1.5 py-0.5 rounded bg-zinc-700 text-zinc-400"
                >
                  {{ perm }}
                </span>
              </div>
            </button>
          </div>
        </div>
      </div>

      <p class="text-center text-xs text-zinc-600 mt-4">
        Inventory Management System v1.0
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { Package } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

const form = ref({ email: '', password: 'password' })
const loading = ref(false)
const error = ref('')

const demoAccounts = [
  {
    label: 'Administrator',
    role: 'admin',
    email: 'admin@inventory.test',
    password: 'password',
    permissions: ['Lihat', 'Tambah', 'Edit', 'Hapus', 'Transfer', 'Laporan'],
  },
  {
    label: 'Manager Gudang',
    role: 'manager',
    email: 'manager@inventory.test',
    password: 'password',
    permissions: ['Lihat', 'Tambah', 'Edit', 'Transfer', 'Laporan'],
  },
  {
    label: 'Staff Gudang',
    role: 'staff',
    email: 'staff@inventory.test',
    password: 'password',
    permissions: ['Lihat', 'Tambah Transaksi'],
  },
  {
    label: 'Viewer',
    role: 'viewer',
    email: 'viewer@inventory.test',
    password: 'password',
    permissions: ['Lihat saja'],
  },
]

function roleClass(role: string) {
  const map: Record<string, string> = {
    admin:   'bg-red-950 text-red-400 border border-red-900',
    manager: 'bg-indigo-950 text-indigo-400 border border-indigo-900',
    staff:   'bg-emerald-950 text-emerald-400 border border-emerald-900',
    viewer:  'bg-zinc-700 text-zinc-400 border border-zinc-600',
  }
  return map[role] ?? ''
}

function fillDemo(acc: { email: string; password: string }) {
  form.value.email = acc.email
  form.value.password = acc.password
}

async function handleLogin() {
  loading.value = true
  error.value = ''
  try {
    await auth.login(form.value.email, form.value.password)
    router.push('/')
  } catch (e: any) {
    error.value = e.response?.data?.message ?? 'Login gagal. Periksa email dan password.'
  } finally {
    loading.value = false
  }
}
</script>
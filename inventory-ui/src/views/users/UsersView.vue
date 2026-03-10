<template>
  <div class="space-y-5">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-lg font-bold text-zinc-100">Manajemen User</h2>
        <p class="text-xs text-zinc-500">{{ meta?.total ?? 0 }} user terdaftar</p>
      </div>
      <button @click="openCreate" class="btn-primary">
        <Plus class="w-4 h-4" /> Tambah User
      </button>
    </div>

    <!-- Filters -->
    <div class="flex gap-3 flex-wrap">
      <input v-model="search" @input="debouncedFetch" placeholder="Cari nama / email..." class="input-field w-64" />
      <select v-model="roleFilter" @change="fetchUsers(1)" class="input-field w-36">
        <option value="">Semua Role</option>
        <option value="manager">Manager</option>
        <option value="staff">Staff</option>
        <option value="viewer">Viewer</option>
      </select>
    </div>

    <!-- Table -->
    <div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden">
      <div v-if="loading" class="py-16 text-center text-zinc-500 text-sm">Memuat data...</div>
      <table v-else class="w-full">
        <thead>
          <tr class="border-b border-zinc-800">
            <th class="th">Nama</th>
            <th class="th">Email</th>
            <th class="th text-center">Role</th>
            <th class="th text-center">Status</th>
            <th class="th text-right">Dibuat</th>
            <th class="th text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="u in users" :key="u.id"
            class="border-b border-zinc-800/50 hover:bg-zinc-800/30 transition-colors"
            :class="{ 'opacity-50': !u.is_active }">
            <td class="td font-semibold text-zinc-200">
              {{ u.name }}
              <span v-if="u.id === authStore.user?.id" class="ml-1 text-[10px] text-indigo-400 font-normal">(kamu)</span>
            </td>
            <td class="td text-sm text-zinc-400">{{ u.email }}</td>
            <td class="td text-center">
              <span :class="roleClass(u.role)" class="px-2 py-0.5 rounded text-[11px] font-semibold uppercase">
                {{ u.role }}
              </span>
            </td>
            <td class="td text-center">
              <button @click="handleToggleActive(u)" :disabled="u.id === authStore.user?.id"
                :class="u.is_active ? 'badge-green cursor-pointer hover:opacity-75' : 'badge-zinc cursor-pointer hover:opacity-75'"
                class="transition-opacity" :title="u.is_active ? 'Klik untuk nonaktifkan' : 'Klik untuk aktifkan'">
                {{ u.is_active ? 'Aktif' : 'Nonaktif' }}
              </button>
            </td>
            <td class="td text-right text-xs text-zinc-500">
              {{ new Date(u.created_at).toLocaleDateString('id-ID') }}
            </td>
            <td class="td text-center">
              <div class="flex items-center justify-center gap-2">
                <button @click="openEdit(u)" class="btn-icon text-zinc-400 hover:text-indigo-400"
                  :disabled="u.id === authStore.user?.id" title="Edit">
                  <Pencil class="w-3.5 h-3.5" />
                </button>
                <button @click="confirmDelete(u)" class="btn-icon text-zinc-400 hover:text-red-400"
                  :disabled="u.id === authStore.user?.id" title="Hapus">
                  <Trash2 class="w-3.5 h-3.5" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="meta" class="flex items-center justify-between text-xs text-zinc-500">
      <span>Menampilkan {{ meta.from }}–{{ meta.to }} dari {{ meta.total }}</span>
      <div class="flex gap-1">
        <button v-for="page in visiblePages" :key="page" @click="fetchUsers(page)"
          :class="['px-3 py-1.5 rounded-lg transition-colors',
            page === meta.current_page
              ? 'bg-indigo-600 text-white font-semibold'
              : 'bg-zinc-800 text-zinc-400 hover:bg-zinc-700']">
          {{ page }}
        </button>
      </div>
    </div>

    <!-- Info RBAC -->
    <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-4 text-xs text-zinc-500 space-y-2">
      <p class="text-zinc-300 font-semibold text-sm">📋 Hak Akses per Role</p>
      <div class="grid grid-cols-4 gap-3 mt-2">
        <div v-for="r in roleDescriptions" :key="r.role" class="space-y-1">
          <span :class="roleClass(r.role)" class="px-2 py-0.5 rounded text-[11px] font-semibold uppercase">{{ r.role }}</span>
          <ul class="text-zinc-500 space-y-0.5 mt-1">
            <li v-for="perm in r.perms" :key="perm">{{ perm }}</li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Create / Edit Modal -->
    <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
      <div class="modal-box">
        <h3 class="text-base font-bold text-zinc-100 mb-5">
          {{ editingUser ? 'Edit User' : 'Tambah User' }}
        </h3>
        <form @submit.prevent="handleSubmit" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2 space-y-1.5">
              <label class="label">Nama</label>
              <input v-model="form.name" type="text" required class="input-field w-full" />
            </div>
            <div class="space-y-1.5">
              <label class="label">Email</label>
              <input v-model="form.email" type="email" required class="input-field w-full" />
            </div>
            <div class="space-y-1.5">
              <label class="label">Role</label>
              <select v-model="form.role" required class="input-field w-full">
                <option value="manager">Manager</option>
                <option value="staff">Staff</option>
                <option value="viewer">Viewer</option>
              </select>
            </div>
            <div class="col-span-2 space-y-1.5">
              <label class="label">
                Password
                <span v-if="editingUser" class="text-zinc-500 font-normal">(kosongkan jika tidak diubah)</span>
              </label>
              <input v-model="form.password" type="password" :required="!editingUser"
                :placeholder="editingUser ? 'Biarkan kosong jika tidak berubah' : 'Min. 8 karakter'"
                minlength="8" class="input-field w-full" />
            </div>
            <div class="col-span-2 flex items-center gap-2">
              <input v-model="form.is_active" type="checkbox" id="is-active" class="rounded" />
              <label for="is-active" class="text-sm text-zinc-300">Aktif</label>
            </div>
          </div>

          <p v-if="formError" class="text-red-400 text-xs bg-red-400/10 p-2 rounded">{{ formError }}</p>
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="showModal = false" class="btn-secondary">Batal</button>
            <button type="submit" :disabled="submitting" class="btn-primary">
              {{ submitting ? 'Menyimpan...' : 'Simpan' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Delete Confirm Modal -->
    <div v-if="showDeleteModal" class="modal-overlay" @click.self="showDeleteModal = false">
      <div class="modal-box max-w-sm">
        <h3 class="text-base font-bold text-zinc-100 mb-2">Hapus User?</h3>
        <p class="text-sm text-zinc-400 mb-1">
          User <strong class="text-zinc-200">{{ deletingUser?.name }}</strong> akan dihapus.
        </p>
        <p class="text-xs text-zinc-500 mb-6">User yang dihapus tidak dapat login. Data transaksi tetap tersimpan.</p>
        <div class="flex justify-end gap-3">
          <button @click="showDeleteModal = false" class="btn-secondary">Batal</button>
          <button @click="handleDelete" :disabled="submitting" class="btn-danger">
            {{ submitting ? 'Menghapus...' : 'Hapus' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Plus, Pencil, Trash2 } from 'lucide-vue-next'
import { usersApi } from '@/api/users'
import { useAuthStore } from '@/stores/auth'
import type { User, PaginationMeta } from '@/types'

const authStore = useAuthStore()
const users = ref<User[]>([])
const meta = ref<PaginationMeta | null>(null)
const loading = ref(true)
const search = ref('')
const roleFilter = ref('')
const showModal = ref(false)
const showDeleteModal = ref(false)
const editingUser = ref<User | null>(null)
const deletingUser = ref<User | null>(null)
const submitting = ref(false)
const formError = ref('')

const form = ref({ name: '', email: '', password: '', role: 'staff', is_active: true })

const visiblePages = computed(() => {
  if (!meta.value) return []
  const { current_page, last_page } = meta.value
  const pages: number[] = []
  for (let i = Math.max(1, current_page - 2); i <= Math.min(last_page, current_page + 2); i++) pages.push(i)
  return pages
})

const roleDescriptions = [
  {
    role: 'admin',
    perms: ['Semua akses', 'Kelola user', 'Hapus data', 'Reset kredit customer']
  },
  {
    role: 'manager',
    perms: ['Dashboard & laporan', 'Kelola produk & gudang', 'Atur kredit customer', 'Kelola karyawan']
  },
  {
    role: 'staff',
    perms: ['Buat & proses transaksi', 'Kelola inventaris', 'Transfer stok', 'Tambah customer']
  },
  {
    role: 'viewer',
    perms: ['Lihat dashboard', 'Lihat semua data', 'Export laporan', 'Tidak bisa edit']
  }
]

let debounceTimer: ReturnType<typeof setTimeout>
function debouncedFetch() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => fetchUsers(1), 400)
}

function roleClass(role: string) {
  if (role === 'admin') return 'text-purple-400 bg-purple-400/10'
  if (role === 'manager') return 'text-blue-400 bg-blue-400/10'
  if (role === 'staff') return 'text-emerald-400 bg-emerald-400/10'
  return 'text-zinc-400 bg-zinc-400/10'
}

async function fetchUsers(page = 1) {
  loading.value = true
  try {
    const res = await usersApi.list({
      page,
      search: search.value || undefined,
      role: roleFilter.value || undefined,
      per_page: 15
    })
    users.value = res.data.data
    meta.value = res.data.meta
  } finally {
    loading.value = false
  }
}

function openCreate() {
  editingUser.value = null
  form.value = { name: '', email: '', password: '', role: 'staff', is_active: true }
  formError.value = ''
  showModal.value = true
}

function openEdit(u: User) {
  if (u.id === authStore.user?.id) return
  editingUser.value = u
  form.value = { name: u.name, email: u.email, password: '', role: u.role, is_active: u.is_active }
  formError.value = ''
  showModal.value = true
}

function confirmDelete(u: User) {
  if (u.id === authStore.user?.id) return
  deletingUser.value = u
  showDeleteModal.value = true
}

async function handleSubmit() {
  submitting.value = true
  formError.value = ''
  try {
    const payload: any = { ...form.value }
    // Jangan kirim password kosong saat edit
    if (editingUser.value && !payload.password) delete payload.password

    if (editingUser.value) {
      await usersApi.update(editingUser.value.id, payload)
    } else {
      await usersApi.create(payload)
    }
    showModal.value = false
    fetchUsers(meta.value?.current_page ?? 1)
  } catch (e: any) {
    const errors = e.response?.data?.errors
    if (errors) {
      formError.value = Object.values(errors).flat().join(', ')
    } else {
      formError.value = e.response?.data?.message ?? 'Gagal menyimpan'
    }
  } finally {
    submitting.value = false
  }
}

async function handleDelete() {
  if (!deletingUser.value) return
  submitting.value = true
  try {
    await usersApi.destroy(deletingUser.value.id)
    showDeleteModal.value = false
    fetchUsers(meta.value?.current_page ?? 1)
  } finally {
    submitting.value = false
  }
}

async function handleToggleActive(u: User) {
  if (u.id === authStore.user?.id) return
  try {
    await usersApi.toggleActive(u.id)
    fetchUsers(meta.value?.current_page ?? 1)
  } catch (e: any) {
    alert(e.response?.data?.message ?? 'Gagal mengubah status')
  }
}

onMounted(fetchUsers)
</script>

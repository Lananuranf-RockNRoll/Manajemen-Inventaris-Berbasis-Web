<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-lg font-bold text-zinc-100">Customer</h2>
        <p class="text-xs text-zinc-500">{{ meta?.total ?? 0 }} customer</p>
      </div>
      <button v-if="auth.canCreate" @click="openCreate" class="btn-primary">
        <Plus class="w-4 h-4" /> Tambah Customer
      </button>
    </div>

    <div class="flex gap-3">
      <input v-model="search" @input="debouncedFetch" placeholder="Cari nama / email..." class="input-field w-64" />
      <select v-model="statusFilter" @change="fetchCustomers(1)" class="input-field w-40">
        <option value="">Semua Status</option>
        <option value="active">Aktif</option>
        <option value="inactive">Nonaktif</option>
        <option value="blacklisted">Blacklist</option>
      </select>
    </div>

    <div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden">
      <div v-if="loading" class="py-16 text-center text-zinc-500 text-sm">Memuat data...</div>
      <table v-else class="w-full">
        <thead>
          <tr class="border-b border-zinc-800">
            <th class="th">Nama</th>
            <th class="th">Email</th>
            <th class="th">Telepon</th>
            <th class="th text-right">Credit Limit</th>
            <th class="th text-right">Tersedia</th>
            <th class="th text-center">Status</th>
            <th v-if="auth.canEdit || auth.canDelete" class="th text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="cust in customers" :key="cust.id"
            class="border-b border-zinc-800/50 hover:bg-zinc-800/30 transition-colors">
            <td class="td font-semibold text-zinc-200">{{ cust.name }}</td>
            <td class="td text-sm text-zinc-400">{{ cust.email ?? '—' }}</td>
            <td class="td text-sm text-zinc-400">{{ cust.phone ?? '—' }}</td>
            <td class="td text-right text-sm text-zinc-300">Rp {{ formatPrice(cust.credit_limit) }}</td>
            <td class="td text-right text-sm font-semibold" :class="Number(cust.credit_available) > 0 ? 'text-emerald-400' : 'text-red-400'">
              Rp {{ formatPrice(cust.credit_available) }}
            </td>
            <td class="td text-center">
              <span :class="statusClass(cust.status)">{{ cust.status }}</span>
            </td>
            <td v-if="auth.canEdit || auth.canDelete" class="td text-center">
              <div class="flex items-center justify-center gap-2">
                <button v-if="auth.canEdit" @click="openEdit(cust)" class="btn-icon text-zinc-400 hover:text-indigo-400">
                  <Pencil class="w-3.5 h-3.5" />
                </button>
                <button v-if="auth.canDelete" @click="confirmDelete(cust)" class="btn-icon text-zinc-400 hover:text-red-400">
                  <Trash2 class="w-3.5 h-3.5" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
      <div v-if="auth.isViewer" class="px-4 py-2 bg-zinc-800/30 border-t border-zinc-800 text-xs text-zinc-500">
        ℹ️ Anda login sebagai <strong>viewer</strong> — hanya dapat melihat data.
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="meta" class="flex items-center justify-between text-xs text-zinc-500">
      <span>Menampilkan {{ meta.from }}–{{ meta.to }} dari {{ meta.total }}</span>
      <div class="flex gap-1">
        <button v-for="page in visiblePages" :key="page" @click="fetchCustomers(page)"
          :class="['px-3 py-1.5 rounded-lg transition-colors', page === meta.current_page ? 'bg-indigo-600 text-white font-semibold' : 'bg-zinc-800 text-zinc-400 hover:bg-zinc-700']">
          {{ page }}
        </button>
      </div>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
      <div class="modal-box">
        <h3 class="text-base font-bold text-zinc-100 mb-5">{{ editingCust ? 'Edit Customer' : 'Tambah Customer' }}</h3>
        <form @submit.prevent="handleSubmit" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2 space-y-1.5">
              <label class="label">Nama</label>
              <input v-model="form.name" type="text" required class="input-field w-full" />
            </div>
            <div class="space-y-1.5">
              <label class="label">Email</label>
              <input v-model="form.email" type="email" class="input-field w-full" />
            </div>
            <div class="space-y-1.5">
              <label class="label">Telepon</label>
              <input v-model="form.phone" type="text" class="input-field w-full" />
            </div>
            <div class="space-y-1.5">
              <label class="label">Credit Limit</label>
              <input v-model="form.credit_limit" type="number" step="0.01" min="0" class="input-field w-full" />
            </div>
            <div class="space-y-1.5">
              <label class="label">Status</label>
              <select v-model="form.status" class="input-field w-full">
                <option value="active">Aktif</option>
                <option value="inactive">Nonaktif</option>
                <option value="blacklisted">Blacklist</option>
              </select>
            </div>
            <div class="col-span-2 space-y-1.5">
              <label class="label">Alamat</label>
              <textarea v-model="form.address" rows="2" class="input-field w-full resize-none"></textarea>
            </div>
          </div>
          <p v-if="formError" class="text-red-400 text-xs">{{ formError }}</p>
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="showModal = false" class="btn-secondary">Batal</button>
            <button type="submit" :disabled="submitting" class="btn-primary">
              {{ submitting ? 'Menyimpan...' : 'Simpan' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Delete Modal -->
    <div v-if="showDeleteModal" class="modal-overlay" @click.self="showDeleteModal = false">
      <div class="modal-box max-w-sm">
        <h3 class="text-base font-bold text-zinc-100 mb-2">Hapus Customer?</h3>
        <p class="text-sm text-zinc-400 mb-6">
          Customer <strong class="text-zinc-200">{{ deletingCust?.name }}</strong> akan dihapus.
        </p>
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
import { customersApi } from '@/api/customers'
import { useAuthStore } from '@/stores/auth'
import type { Customer, PaginationMeta } from '@/types'

const auth = useAuthStore()
const customers = ref<Customer[]>([])
const meta = ref<PaginationMeta | null>(null)
const loading = ref(true)
const search = ref('')
const statusFilter = ref('')
const showModal = ref(false)
const showDeleteModal = ref(false)
const editingCust = ref<Customer | null>(null)
const deletingCust = ref<Customer | null>(null)
const submitting = ref(false)
const formError = ref('')

const form = ref({ name: '', email: '', phone: '', address: '', credit_limit: '', status: 'active' })

const visiblePages = computed(() => {
  if (!meta.value) return []
  const { current_page, last_page } = meta.value
  const pages: number[] = []
  for (let i = Math.max(1, current_page - 2); i <= Math.min(last_page, current_page + 2); i++) pages.push(i)
  return pages
})

let debounceTimer: ReturnType<typeof setTimeout>
function debouncedFetch() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => fetchCustomers(1), 400)
}

function statusClass(status: string) {
  if (status === 'active') return 'badge-green'
  if (status === 'blacklisted') return 'badge-red'
  return 'badge-zinc'
}

function formatPrice(val: string | number) {
  return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Number(val))
}

async function fetchCustomers(page = 1) {
  loading.value = true
  try {
    const res = await customersApi.list({ page, search: search.value || undefined, status: statusFilter.value || undefined, per_page: 15 })
    customers.value = res.data.data
    meta.value = res.data.meta
  } finally {
    loading.value = false
  }
}

function openCreate() {
  if (!auth.canCreate) return
  editingCust.value = null
  form.value = { name: '', email: '', phone: '', address: '', credit_limit: '', status: 'active' }
  formError.value = ''
  showModal.value = true
}

function openEdit(cust: Customer) {
  if (!auth.canEdit) return
  editingCust.value = cust
  form.value = { name: cust.name, email: cust.email ?? '', phone: cust.phone ?? '', address: cust.address ?? '', credit_limit: cust.credit_limit, status: cust.status }
  formError.value = ''
  showModal.value = true
}

function confirmDelete(cust: Customer) {
  if (!auth.canDelete) return
  deletingCust.value = cust
  showDeleteModal.value = true
}

async function handleSubmit() {
  submitting.value = true
  formError.value = ''
  try {
    if (editingCust.value) {
      await customersApi.update(editingCust.value.id, form.value)
    } else {
      await customersApi.create(form.value)
    }
    showModal.value = false
    fetchCustomers(meta.value?.current_page ?? 1)
  } catch (e: any) {
    formError.value = e.response?.data?.message ?? 'Gagal menyimpan'
  } finally {
    submitting.value = false
  }
}

async function handleDelete() {
  if (!deletingCust.value) return
  submitting.value = true
  try {
    await customersApi.destroy(deletingCust.value.id)
    showDeleteModal.value = false
    fetchCustomers(meta.value?.current_page ?? 1)
  } finally {
    submitting.value = false
  }
}

onMounted(fetchCustomers)
</script>

<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-lg font-bold text-zinc-100">Customer</h2>
        <p class="text-xs text-zinc-500">{{ meta?.total ?? 0 }} customer</p>
      </div>
      <button v-if="auth.canCreateCustomer" @click="openCreate" class="btn-primary">
        <Plus class="w-4 h-4" /> Tambah Customer
      </button>
    </div>

    <div class="flex gap-3 flex-wrap">
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
      <table v-else class="w-full text-sm">
        <thead>
          <tr class="border-b border-zinc-800">
            <th class="th">Nama</th>
            <th class="th">Email</th>
            <th class="th hidden md:table-cell">Telepon</th>
            <th class="th text-right">Credit Limit</th>
            <th class="th text-right">Terpakai</th>
            <th class="th text-right">Tersedia</th>
            <th class="th text-center">Status</th>
            <th class="th text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="cust in customers" :key="cust.id"
            class="border-b border-zinc-800/50 hover:bg-zinc-800/30 transition-colors">
            <td class="td font-semibold text-zinc-200">{{ cust.name }}</td>
            <td class="td text-zinc-400">{{ cust.email ?? '—' }}</td>
            <td class="td text-zinc-400 hidden md:table-cell">{{ cust.phone ?? '—' }}</td>
            <td class="td text-right text-zinc-300">${{ fmtUSD(cust.credit_limit) }}</td>
            <td class="td text-right" :class="Number(cust.credit_used) > 0 ? 'text-amber-400' : 'text-zinc-500'">
              ${{ fmtUSD(cust.credit_used) }}
            </td>
            <td class="td text-right font-semibold"
              :class="Number(cust.credit_available) > 0 ? 'text-emerald-400' : 'text-red-400'">
              ${{ fmtUSD(cust.credit_available) }}
            </td>
            <td class="td text-center">
              <span :class="statusClass(cust.status)">{{ cust.status }}</span>
            </td>
            <td class="td text-center">
              <div class="flex items-center justify-center gap-1.5">
                <button v-if="auth.canManageCredit" @click="openCreditModal(cust)"
                  class="btn-icon text-zinc-400 hover:text-amber-400" title="Atur Credit">
                  <DollarSign class="w-3.5 h-3.5" />
                </button>
                <button v-if="auth.canEditCustomer" @click="openEdit(cust)"
                  class="btn-icon text-zinc-400 hover:text-indigo-400" title="Edit">
                  <Pencil class="w-3.5 h-3.5" />
                </button>
                <button v-if="auth.canDeleteCustomer" @click="confirmDelete(cust)"
                  class="btn-icon text-zinc-400 hover:text-red-400" title="Hapus">
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
          :class="['px-3 py-1.5 rounded-lg transition-colors',
            page === meta.current_page ? 'bg-indigo-600 text-white font-semibold' : 'bg-zinc-800 text-zinc-400 hover:bg-zinc-700']">
          {{ page }}
        </button>
      </div>
    </div>

    <!-- Create / Edit Modal -->
    <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
      <div class="modal-box">
        <h3 class="text-base font-bold text-zinc-100 mb-5">
          {{ editingCust ? 'Edit Customer' : 'Tambah Customer' }}
        </h3>
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
              <label class="label">Credit Limit (USD)</label>
              <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-500 text-sm">$</span>
                <input v-model="form.credit_limit" type="number" step="0.01" min="0"
                  class="input-field w-full pl-7" placeholder="300.00" />
              </div>
              <p class="text-xs text-zinc-600">Default: $300.00</p>
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

    <!-- Credit Management Modal -->
    <div v-if="showCreditModal" class="modal-overlay" @click.self="showCreditModal = false">
      <div class="modal-box max-w-sm">
        <h3 class="text-base font-bold text-zinc-100 mb-1">Atur Credit</h3>
        <p class="text-sm text-zinc-400 mb-4"><strong class="text-zinc-200">{{ creditCust?.name }}</strong></p>
        <div class="bg-zinc-800 rounded-lg p-3 mb-4 space-y-1.5 text-sm">
          <div class="flex justify-between">
            <span class="text-zinc-400">Limit saat ini</span>
            <span class="text-zinc-100 font-semibold">${{ fmtUSD(creditCust?.credit_limit) }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-zinc-400">Terpakai</span>
            <span class="text-amber-400">${{ fmtUSD(creditCust?.credit_used) }}</span>
          </div>
          <div class="flex justify-between border-t border-zinc-700 pt-1.5">
            <span class="text-zinc-400">Tersedia</span>
            <span class="font-bold" :class="Number(creditCust?.credit_available) > 0 ? 'text-emerald-400' : 'text-red-400'">
              ${{ fmtUSD(creditCust?.credit_available) }}
            </span>
          </div>
        </div>
        <form @submit.prevent="handleCreditAdjust" class="space-y-4">
          <div class="space-y-1.5">
            <label class="label">Aksi</label>
            <select v-model="creditForm.action" class="input-field w-full">
              <option value="add">Tambah Credit Limit</option>
              <option value="subtract">Kurangi Credit Limit</option>
              <option value="set">Set Credit Limit ke Nilai Baru</option>
            </select>
          </div>
          <div class="space-y-1.5">
            <label class="label">{{ creditForm.action === 'set' ? 'Nilai Baru (USD)' : 'Jumlah (USD)' }}</label>
            <div class="relative">
              <span class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-500 text-sm">$</span>
              <input v-model="creditForm.amount" type="number" step="0.01" min="0" required class="input-field w-full pl-7" />
            </div>
          </div>
          <div v-if="auth.isAdmin" class="pt-1 border-t border-zinc-800">
            <button type="button" @click="handleResetCreditUsed"
              class="text-xs text-amber-400 hover:text-amber-300 transition-colors">
              ⚠️ Reset Credit Terpakai ke $0 (koreksi manual)
            </button>
          </div>
          <p v-if="creditError" class="text-red-400 text-xs bg-red-400/10 p-2 rounded">{{ creditError }}</p>
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="showCreditModal = false" class="btn-secondary">Batal</button>
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
import { Plus, Pencil, Trash2, DollarSign } from 'lucide-vue-next'
import { customersApi } from '@/api/customers'
import { useAuthStore } from '@/stores/auth'
import type { Customer, PaginationMeta } from '@/types'

const auth = useAuthStore()
const customers     = ref<Customer[]>([])
const meta          = ref<PaginationMeta | null>(null)
const loading       = ref(true)
const search        = ref('')
const statusFilter  = ref('')
const showModal     = ref(false)
const showDeleteModal  = ref(false)
const showCreditModal  = ref(false)
const editingCust   = ref<Customer | null>(null)
const deletingCust  = ref<Customer | null>(null)
const creditCust    = ref<Customer | null>(null)
const submitting    = ref(false)
const formError     = ref('')
const creditError   = ref('')

const form       = ref({ name: '', email: '', phone: '', address: '', credit_limit: '300', status: 'active' })
const creditForm = ref({ action: 'add' as 'add' | 'subtract' | 'set', amount: '' })

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

function fmtUSD(val: string | number | undefined | null) {
  if (val == null) return '0.00'
  return Number(val).toFixed(2)
}

async function fetchCustomers(page = 1) {
  loading.value = true
  try {
    const res = await customersApi.list({ page, search: search.value || undefined, status: statusFilter.value || undefined, per_page: 15 })
    customers.value = res.data.data
    meta.value = res.data.meta
  } finally { loading.value = false }
}

function openCreate() {
  editingCust.value = null
  form.value = { name: '', email: '', phone: '', address: '', credit_limit: '300', status: 'active' }
  formError.value = ''
  showModal.value = true
}

function openEdit(cust: Customer) {
  editingCust.value = cust
  form.value = { name: cust.name, email: cust.email ?? '', phone: cust.phone ?? '', address: cust.address ?? '', credit_limit: cust.credit_limit, status: cust.status }
  formError.value = ''
  showModal.value = true
}

function openCreditModal(cust: Customer) {
  creditCust.value = cust
  creditForm.value = { action: 'add', amount: '' }
  creditError.value = ''
  showCreditModal.value = true
}

function confirmDelete(cust: Customer) {
  deletingCust.value = cust
  showDeleteModal.value = true
}

async function handleSubmit() {
  submitting.value = true; formError.value = ''
  try {
    if (editingCust.value) { await customersApi.update(editingCust.value.id, form.value) }
    else { await customersApi.create(form.value) }
    showModal.value = false
    fetchCustomers(meta.value?.current_page ?? 1)
  } catch (e: any) {
    formError.value = e.response?.data?.message ?? 'Gagal menyimpan'
  } finally { submitting.value = false }
}

async function handleCreditAdjust() {
  if (!creditCust.value) return
  submitting.value = true; creditError.value = ''
  try {
    await customersApi.adjustCredit(creditCust.value.id, creditForm.value.action, Number(creditForm.value.amount))
    showCreditModal.value = false
    fetchCustomers(meta.value?.current_page ?? 1)
  } catch (e: any) {
    creditError.value = e.response?.data?.message ?? 'Gagal mengatur credit'
  } finally { submitting.value = false }
}

async function handleResetCreditUsed() {
  if (!creditCust.value || !confirm('Reset credit terpakai ke $0?')) return
  submitting.value = true
  try {
    await customersApi.resetCreditUsed(creditCust.value.id)
    showCreditModal.value = false
    fetchCustomers(meta.value?.current_page ?? 1)
  } catch (e: any) {
    creditError.value = e.response?.data?.message ?? 'Gagal reset credit'
  } finally { submitting.value = false }
}

async function handleDelete() {
  if (!deletingCust.value) return
  submitting.value = true
  try {
    await customersApi.destroy(deletingCust.value.id)
    showDeleteModal.value = false
    fetchCustomers(meta.value?.current_page ?? 1)
  } finally { submitting.value = false }
}

onMounted(fetchCustomers)
</script>

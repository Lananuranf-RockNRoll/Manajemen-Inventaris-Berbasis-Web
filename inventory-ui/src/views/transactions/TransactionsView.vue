<template>
  <div class="space-y-5">

    <!-- Header -->
    <div class="page-header">
      <div>
        <h2 class="page-title">Transaksi</h2>
        <p class="page-subtitle">{{ meta?.total ?? 0 }} transaksi</p>
      </div>
      <div class="flex flex-wrap items-center gap-2 self-start sm:self-auto">
        <button @click="exportExcel" :disabled="!!exporting" class="btn-export-excel">
          <FileSpreadsheet class="w-3.5 h-3.5" />
          <span>{{ exporting === 'excel' ? '...' : 'Excel' }}</span>
        </button>
        <button @click="exportPdf" :disabled="!!exporting" class="btn-export-pdf">
          <FileText class="w-3.5 h-3.5" />
          <span>{{ exporting === 'pdf' ? '...' : 'PDF' }}</span>
        </button>
        <button v-if="auth.canCreate" @click="openCreate" class="btn-primary">
          <Plus class="w-4 h-4" />
          <span class="hidden sm:inline">Buat Order</span>
          <span class="sm:hidden">Order</span>
        </button>
      </div>
    </div>

    <!-- Filters -->
    <div class="filter-row">
      <select v-model="statusFilter" @change="fetchTransactions(1)" class="input-field w-full sm:w-40">
        <option value="">Semua Status</option>
        <option value="pending">Pending</option>
        <option value="processing">Processing</option>
        <option value="shipped">Shipped</option>
        <option value="delivered">Delivered</option>
        <option value="canceled">Canceled</option>
      </select>
      <input v-model="fromDate" @change="fetchTransactions(1)" type="date" class="input-field w-full sm:w-auto" />
      <input v-model="toDate"   @change="fetchTransactions(1)" type="date" class="input-field w-full sm:w-auto" />
    </div>

    <!-- Table -->
    <div class="card overflow-hidden">
      <div v-if="loading" class="py-16 text-center text-zinc-500 text-sm">Memuat data...</div>
      <template v-else>
        <!-- Desktop -->
        <div class="table-scroll hidden sm:block">
          <table class="w-full">
            <thead>
              <tr class="border-b border-zinc-800">
                <th class="th">No. Order</th>
                <th class="th">Customer</th>
                <th class="th">Gudang</th>
                <th class="th">Tanggal</th>
                <th class="th text-right">Total</th>
                <th class="th text-center">Status</th>
                <th class="th text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="trx in transactions"
                :key="trx.id"
                class="border-b border-zinc-800/50 hover:bg-zinc-800/30 transition-colors"
              >
                <td class="td font-mono text-xs text-indigo-400 whitespace-nowrap">{{ trx.order_number }}</td>
                <td class="td text-sm text-zinc-300">{{ trx.customer?.name }}</td>
                <td class="td text-sm text-zinc-400">{{ trx.warehouse?.name }}</td>
                <td class="td text-sm text-zinc-400 whitespace-nowrap">{{ trx.order_date }}</td>
                <td class="td text-right text-sm font-bold text-zinc-200 whitespace-nowrap tabular-nums">
                  Rp {{ formatPrice(trx.total_amount) }}
                </td>
                <td class="td text-center">
                  <span :class="statusClass(trx.status)">{{ trx.status }}</span>
                </td>
                <td class="td text-center">
                  <div class="flex items-center justify-center gap-2">
                    <button @click="openDetail(trx)" class="btn-icon text-zinc-400 hover:text-indigo-400" title="Detail">
                      <Eye class="w-3.5 h-3.5" />
                    </button>
                    <button
                      v-if="auth.canEdit && ['pending', 'processing', 'shipped'].includes(trx.status)"
                      @click="openUpdateStatus(trx)"
                      class="btn-icon text-zinc-400 hover:text-emerald-400"
                      title="Update Status"
                    >
                      <RefreshCw class="w-3.5 h-3.5" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Mobile cards -->
        <div class="sm:hidden divide-y divide-zinc-800">
          <div
            v-for="trx in transactions"
            :key="trx.id"
            class="p-4 hover:bg-zinc-800/30 transition-colors"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2 flex-wrap mb-1">
                  <span class="font-mono text-xs text-indigo-400">{{ trx.order_number }}</span>
                  <span :class="statusClass(trx.status)">{{ trx.status }}</span>
                </div>
                <p class="text-sm font-semibold text-zinc-200 truncate">{{ trx.customer?.name }}</p>
                <p class="text-xs text-zinc-500">{{ trx.warehouse?.name }} · {{ trx.order_date }}</p>
                <p class="text-sm font-bold text-zinc-100 mt-1 tabular-nums">Rp {{ formatPrice(trx.total_amount) }}</p>
              </div>
              <div class="flex gap-1 shrink-0">
                <button @click="openDetail(trx)" class="btn-icon text-zinc-400 hover:text-indigo-400">
                  <Eye class="w-4 h-4" />
                </button>
                <button
                  v-if="auth.canEdit && ['pending', 'processing', 'shipped'].includes(trx.status)"
                  @click="openUpdateStatus(trx)"
                  class="btn-icon text-zinc-400 hover:text-emerald-400"
                >
                  <RefreshCw class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
          <div v-if="transactions.length === 0" class="py-12 text-center text-zinc-500 text-sm">
            Tidak ada transaksi ditemukan.
          </div>
        </div>

        <div v-if="auth.isViewer" class="px-4 py-2.5 bg-zinc-800/30 border-t border-zinc-800 text-xs text-zinc-500">
          ℹ️ Anda login sebagai <strong>viewer</strong> — hanya dapat melihat data.
        </div>
      </template>
    </div>

    <!-- Pagination -->
    <div v-if="meta && meta.last_page > 1" class="flex items-center justify-between text-xs text-zinc-500">
      <span class="hidden sm:block">Menampilkan {{ meta.from }}–{{ meta.to }} dari {{ meta.total }}</span>
      <span class="sm:hidden">{{ meta.current_page }} / {{ meta.last_page }}</span>
      <div class="flex gap-1">
        <button
          v-for="page in visiblePages"
          :key="page"
          @click="fetchTransactions(page)"
          :class="[
            'px-3 py-1.5 rounded-lg transition-colors text-xs font-medium',
            page === meta.current_page ? 'bg-indigo-600 text-white' : 'bg-zinc-800 text-zinc-400 hover:bg-zinc-700',
          ]"
        >
          {{ page }}
        </button>
      </div>
    </div>

    <!-- Create Order Modal -->
    <Teleport to="body">
      <div v-if="showCreateModal" class="modal-overlay" @click.self="showCreateModal = false">
        <div class="modal-box sm:max-w-2xl">
          <h3 class="text-base font-bold text-zinc-100 mb-5">Buat Order Baru</h3>
          <form @submit.prevent="handleCreate" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="space-y-1.5">
                <label class="label">Customer *</label>
                <select v-model="createForm.customer_id" required class="input-field w-full">
                  <option value="">Pilih Customer</option>
                  <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
              </div>
              <div class="space-y-1.5">
                <label class="label">Gudang *</label>
                <select v-model="createForm.warehouse_id" required class="input-field w-full">
                  <option value="">Pilih Gudang</option>
                  <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                </select>
              </div>
            </div>

            <!-- Items -->
            <div>
              <div class="flex items-center justify-between mb-2">
                <label class="label !mb-0">Item Produk *</label>
                <button type="button" @click="addItem" class="text-xs text-indigo-400 hover:text-indigo-300">
                  + Tambah Item
                </button>
              </div>
              <div class="space-y-2">
                <div v-for="(item, i) in createForm.items" :key="i" class="flex gap-2 items-center">
                  <select v-model="item.product_id" required class="input-field flex-1 min-w-0">
                    <option value="">Produk</option>
                    <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
                  </select>
                  <input v-model.number="item.quantity" type="number" min="1" placeholder="Qty" required class="input-field w-16 sm:w-20 shrink-0" />
                  <input v-model.number="item.unit_price" type="number" step="0.01" placeholder="Harga" required class="input-field w-24 sm:w-32 shrink-0" />
                  <button
                    type="button"
                    @click="removeItem(i)"
                    :disabled="createForm.items.length <= 1"
                    class="text-red-400 hover:text-red-300 p-1 disabled:opacity-30 shrink-0"
                  >
                    <X class="w-3.5 h-3.5" />
                  </button>
                </div>
              </div>
            </div>

            <div class="space-y-1.5">
              <label class="label">Catatan</label>
              <textarea v-model="createForm.notes" rows="2" class="input-field w-full resize-none" />
            </div>
            <p v-if="formError" class="text-red-400 text-xs bg-red-950/40 border border-red-900 rounded-lg px-3 py-2">
              {{ formError }}
            </p>
            <div class="flex justify-end gap-3 pt-2">
              <button type="button" @click="showCreateModal = false" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="submitting" class="btn-primary">
                {{ submitting ? 'Memproses...' : 'Buat Order' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Detail Modal -->
    <Teleport to="body">
      <div v-if="showDetailModal && detailTrx" class="modal-overlay" @click.self="showDetailModal = false">
        <div class="modal-box sm:max-w-2xl">
          <div class="flex items-start justify-between gap-3 mb-5">
            <div>
              <h3 class="text-base font-bold text-zinc-100">Detail Order</h3>
              <p class="text-xs font-mono text-indigo-400 mt-0.5">{{ detailTrx.order_number }}</p>
            </div>
            <span :class="statusClass(detailTrx.status)">{{ detailTrx.status }}</span>
          </div>
          <div class="grid grid-cols-2 gap-3 mb-5">
            <div>
              <p class="text-xs text-zinc-500">Customer</p>
              <p class="text-sm font-semibold text-zinc-200 mt-0.5">{{ detailTrx.customer?.name }}</p>
            </div>
            <div>
              <p class="text-xs text-zinc-500">Gudang</p>
              <p class="text-sm font-semibold text-zinc-200 mt-0.5">{{ detailTrx.warehouse?.name }}</p>
            </div>
            <div>
              <p class="text-xs text-zinc-500">Tanggal Order</p>
              <p class="text-sm text-zinc-300 mt-0.5">{{ detailTrx.order_date }}</p>
            </div>
            <div>
              <p class="text-xs text-zinc-500">Total</p>
              <p class="text-sm font-bold text-indigo-400 mt-0.5 tabular-nums">Rp {{ formatPrice(detailTrx.total_amount) }}</p>
            </div>
          </div>

          <div v-if="detailTrx.items?.length" class="bg-zinc-800/50 rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead>
                  <tr class="border-b border-zinc-700">
                    <th class="th text-xs">Produk</th>
                    <th class="th text-xs text-right">Qty</th>
                    <th class="th text-xs text-right">Harga</th>
                    <th class="th text-xs text-right">Subtotal</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in detailTrx.items" :key="item.id" class="border-b border-zinc-700/50">
                    <td class="td text-xs text-zinc-300">{{ item.product?.name }}</td>
                    <td class="td text-xs text-right text-zinc-400 tabular-nums">{{ item.quantity }}</td>
                    <td class="td text-xs text-right text-zinc-400 tabular-nums whitespace-nowrap">Rp {{ formatPrice(item.unit_price) }}</td>
                    <td class="td text-xs text-right font-semibold text-zinc-200 tabular-nums whitespace-nowrap">Rp {{ formatPrice(item.subtotal) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="flex justify-end mt-4">
            <button @click="showDetailModal = false" class="btn-secondary">Tutup</button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Update Status Modal -->
    <Teleport to="body">
      <div v-if="showStatusModal && updatingTrx" class="modal-overlay" @click.self="showStatusModal = false">
        <div class="modal-box sm:max-w-sm">
          <h3 class="text-base font-bold text-zinc-100 mb-1">Update Status</h3>
          <p class="text-xs text-zinc-500 mb-5 font-mono">{{ updatingTrx.order_number }}</p>
          <div class="space-y-2 mb-5">
            <button
              v-for="s in nextStatuses(updatingTrx.status)"
              :key="s.value"
              @click="handleUpdateStatus(s.value)"
              :disabled="submitting"
              :class="`w-full py-2.5 px-4 rounded-lg text-sm font-semibold transition-colors border ${s.class} disabled:opacity-50`"
            >
              {{ s.label }}
            </button>
          </div>
          <button @click="showStatusModal = false" class="btn-secondary w-full justify-center">Batal</button>
        </div>
      </div>
    </Teleport>

  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Plus, Eye, RefreshCw, X, FileSpreadsheet, FileText } from 'lucide-vue-next'
import { transactionsApi } from '@/api/transactions'
import { customersApi }    from '@/api/customers'
import { warehousesApi }   from '@/api/warehouses'
import { productsApi }     from '@/api/products'
import { reportsApi, downloadBlob } from '@/api/reports'
import { useAuthStore }    from '@/stores/auth'
import type { Transaction, Customer, Warehouse, Product, PaginationMeta } from '@/types'

const auth = useAuthStore()

const transactions = ref<Transaction[]>([])
const customers    = ref<Customer[]>([])
const warehouses   = ref<Warehouse[]>([])
const products     = ref<Product[]>([])
const meta         = ref<PaginationMeta | null>(null)
const loading      = ref(true)
const exporting    = ref<'excel' | 'pdf' | null>(null)
const statusFilter = ref('')
const fromDate     = ref('')
const toDate       = ref('')
const showCreateModal = ref(false)
const showDetailModal = ref(false)
const showStatusModal = ref(false)
const detailTrx    = ref<Transaction | null>(null)
const updatingTrx  = ref<Transaction | null>(null)
const submitting   = ref(false)
const formError    = ref('')

const defaultCreateForm = () => ({
  customer_id: '' as number | '',
  warehouse_id: '' as number | '',
  notes: '',
  items: [{ product_id: '' as number | '', quantity: 1, unit_price: 0 }],
})
const createForm = ref(defaultCreateForm())

const visiblePages = computed((): number[] => {
  if (!meta.value) return []
  const { current_page, last_page } = meta.value
  const pages: number[] = []
  for (let i = Math.max(1, current_page - 2); i <= Math.min(last_page, current_page + 2); i++) pages.push(i)
  return pages
})

function statusClass(status: string): string {
  const map: Record<string, string> = {
    pending:    'badge-amber',
    processing: 'badge-blue',
    shipped:    'badge-indigo',
    delivered:  'badge-green',
    canceled:   'badge-red',
  }
  return map[status] ?? 'badge-zinc'
}

function nextStatuses(current: string): { value: string; label: string; class: string }[] {
  const map: Record<string, { value: string; label: string; class: string }[]> = {
    pending: [
      { value: 'processing', label: '→ Proses',   class: 'bg-blue-950 text-blue-300 border-blue-800 hover:bg-blue-900' },
      { value: 'canceled',   label: '✕ Batalkan', class: 'bg-red-950 text-red-300 border-red-800 hover:bg-red-900' },
    ],
    processing: [
      { value: 'shipped',  label: '→ Kirim',     class: 'bg-indigo-950 text-indigo-300 border-indigo-800 hover:bg-indigo-900' },
      { value: 'canceled', label: '✕ Batalkan',  class: 'bg-red-950 text-red-300 border-red-800 hover:bg-red-900' },
    ],
    shipped: [
      { value: 'delivered', label: '✓ Selesai',  class: 'bg-emerald-950 text-emerald-300 border-emerald-800 hover:bg-emerald-900' },
      { value: 'canceled',  label: '✕ Batalkan', class: 'bg-red-950 text-red-300 border-red-800 hover:bg-red-900' },
    ],
  }
  return map[current] ?? []
}

function formatPrice(val: string | number): string {
  return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Number(val))
}

async function fetchTransactions(page = 1): Promise<void> {
  loading.value = true
  try {
    const res = await transactionsApi.list({
      page,
      status: statusFilter.value || undefined,
      from:   fromDate.value || undefined,
      to:     toDate.value   || undefined,
      per_page: 20,
    })
    transactions.value = res.data.data
    meta.value         = res.data.meta
  } finally {
    loading.value = false
  }
}

async function exportExcel(): Promise<void> {
  exporting.value = 'excel'
  try {
    const res = await reportsApi.salesExcel({ from: fromDate.value || undefined, to: toDate.value || undefined, status: statusFilter.value || undefined })
    downloadBlob(res.data, `laporan-penjualan-${new Date().toISOString().slice(0, 10)}.xlsx`)
  } catch { alert('Gagal export Excel') }
  finally { exporting.value = null }
}

async function exportPdf(): Promise<void> {
  exporting.value = 'pdf'
  try {
    const res = await reportsApi.salesPdf({ from: fromDate.value || undefined, to: toDate.value || undefined, status: statusFilter.value || undefined })
    downloadBlob(res.data, `laporan-penjualan-${new Date().toISOString().slice(0, 10)}.pdf`)
  } catch { alert('Gagal export PDF') }
  finally { exporting.value = null }
}

function openCreate(): void {
  createForm.value = defaultCreateForm()
  formError.value  = ''
  showCreateModal.value = true
}

async function openDetail(trx: Transaction): Promise<void> {
  try {
    const res = await transactionsApi.show(trx.id)
    detailTrx.value = res.data.data
    showDetailModal.value = true
  } catch { alert('Gagal memuat detail transaksi') }
}

function openUpdateStatus(trx: Transaction): void {
  updatingTrx.value = trx
  showStatusModal.value = true
}

function addItem(): void {
  createForm.value.items.push({ product_id: '', quantity: 1, unit_price: 0 })
}

function removeItem(i: number): void {
  if (createForm.value.items.length <= 1) return
  createForm.value.items.splice(i, 1)
}

async function handleCreate(): Promise<void> {
  submitting.value = true
  formError.value  = ''
  try {
    await transactionsApi.create(createForm.value)
    showCreateModal.value = false
    await fetchTransactions(1)
  } catch (e: any) {
    const errors = e.response?.data?.errors
    formError.value = errors
      ? (Object.values(errors) as string[][]).flat().join(', ')
      : (e.response?.data?.message ?? 'Gagal membuat order')
  } finally {
    submitting.value = false
  }
}

async function handleUpdateStatus(status: string): Promise<void> {
  if (!updatingTrx.value) return
  submitting.value = true
  try {
    await transactionsApi.updateStatus(updatingTrx.value.id, status)
    showStatusModal.value = false
    await fetchTransactions(meta.value?.current_page ?? 1)
  } catch (e: any) {
    alert(e.response?.data?.message ?? 'Gagal update status')
  } finally {
    submitting.value = false
  }
}

onMounted(async (): Promise<void> => {
  const [, custRes, whRes, prodRes] = await Promise.all([
    fetchTransactions(),
    customersApi.list({ per_page: 200 }),
    warehousesApi.list({ per_page: 100 }),
    productsApi.list({ per_page: 200, active: 1 }),
  ])
  customers.value  = custRes.data.data
  warehouses.value = whRes.data.data
  products.value   = prodRes.data.data
})
</script>

<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-lg font-bold text-zinc-100">Inventaris</h2>
        <p class="text-xs text-zinc-500">{{ meta?.total ?? 0 }} item stok</p>
      </div>
      <button v-if="auth.canTransfer" @click="openTransfer" class="btn-primary">
        <ArrowLeftRight class="w-4 h-4" /> Transfer Stok
      </button>
    </div>

    <div class="flex gap-3 flex-wrap">
      <select v-model="warehouseFilter" @change="fetchInventory(1)" class="input-field w-52">
        <option value="">Semua Gudang</option>
        <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
      </select>
      <label class="flex items-center gap-2 text-sm text-zinc-400 cursor-pointer">
        <input v-model="lowStockOnly" @change="fetchInventory(1)" type="checkbox" class="w-4 h-4 accent-amber-500" />
        Stok Rendah Saja
      </label>
    </div>

    <div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden">
      <div v-if="loading" class="py-16 text-center text-zinc-500 text-sm">Memuat data...</div>
      <table v-else class="w-full">
        <thead>
          <tr class="border-b border-zinc-800">
            <th class="th">Produk</th>
            <th class="th">Gudang</th>
            <th class="th text-right">Di Tangan</th>
            <th class="th text-right">Tersedia</th>
            <th class="th text-right">Min Stok</th>
            <th class="th text-center">Status</th>
            <th v-if="auth.canEdit" class="th text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="inv in inventory" :key="inv.id"
            class="border-b border-zinc-800/50 hover:bg-zinc-800/30 transition-colors">
            <td class="td">
              <p class="text-sm font-medium text-zinc-200">{{ inv.product?.name }}</p>
              <p class="text-xs font-mono text-indigo-400">{{ inv.product?.sku }}</p>
            </td>
            <td class="td text-sm text-zinc-400">{{ inv.warehouse?.name }}</td>
            <td class="td text-right text-sm font-bold text-zinc-200">{{ inv.qty_on_hand }}</td>
            <td class="td text-right text-sm font-bold" :class="inv.is_low_stock ? 'text-amber-400' : 'text-emerald-400'">
              {{ inv.qty_available }}
            </td>
            <td class="td text-right text-sm text-zinc-500">{{ inv.min_stock }}</td>
            <td class="td text-center">
              <span v-if="inv.is_low_stock" class="badge-amber">Stok Rendah</span>
              <span v-else class="badge-green">Normal</span>
            </td>
            <td v-if="auth.canEdit" class="td text-center">
              <button @click="openUpdate(inv)" class="btn-icon text-zinc-400 hover:text-indigo-400">
                <Pencil class="w-3.5 h-3.5" />
              </button>
            </td>
          </tr>
        </tbody>
      </table>
      <div v-if="auth.isViewer || auth.isStaff && !auth.isManager" class="px-4 py-2 bg-zinc-800/30 border-t border-zinc-800 text-xs text-zinc-500">
        ℹ️ Anda hanya dapat melihat data inventaris.
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="meta" class="flex items-center justify-between text-xs text-zinc-500">
      <span>Menampilkan {{ meta.from }}–{{ meta.to }} dari {{ meta.total }}</span>
      <div class="flex gap-1">
        <button v-for="page in visiblePages" :key="page" @click="fetchInventory(page)"
          :class="['px-3 py-1.5 rounded-lg transition-colors', page === meta.current_page ? 'bg-indigo-600 text-white font-semibold' : 'bg-zinc-800 text-zinc-400 hover:bg-zinc-700']">
          {{ page }}
        </button>
      </div>
    </div>

    <!-- Update Modal -->
    <div v-if="showUpdateModal" class="modal-overlay" @click.self="showUpdateModal = false">
      <div class="modal-box max-w-sm">
        <h3 class="text-base font-bold text-zinc-100 mb-1">Update Stok</h3>
        <p class="text-xs text-zinc-500 mb-5">{{ updatingInv?.product?.name }}</p>
        <form @submit.prevent="handleUpdate" class="space-y-4">
          <div class="space-y-1.5">
            <label class="label">Qty Di Tangan</label>
            <input v-model="updateForm.qty_on_hand" type="number" min="0" class="input-field w-full" />
          </div>
          <div class="space-y-1.5">
            <label class="label">Min Stok</label>
            <input v-model="updateForm.min_stock" type="number" min="0" class="input-field w-full" />
          </div>
          <div class="space-y-1.5">
            <label class="label">Max Stok</label>
            <input v-model="updateForm.max_stock" type="number" min="1" class="input-field w-full" />
          </div>
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="showUpdateModal = false" class="btn-secondary">Batal</button>
            <button type="submit" :disabled="submitting" class="btn-primary">
              {{ submitting ? 'Menyimpan...' : 'Simpan' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Transfer Modal -->
    <div v-if="showTransferModal" class="modal-overlay" @click.self="showTransferModal = false">
      <div class="modal-box">
        <h3 class="text-base font-bold text-zinc-100 mb-5">Transfer Stok</h3>
        <form @submit.prevent="handleTransfer" class="space-y-4">
          <div class="space-y-1.5">
            <label class="label">Produk</label>
            <select v-model="transferForm.product_id" required class="input-field w-full">
              <option value="">Pilih Produk</option>
              <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
            </select>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <label class="label">Dari Gudang</label>
              <select v-model="transferForm.from_warehouse_id" required class="input-field w-full">
                <option value="">Pilih Gudang</option>
                <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
              </select>
            </div>
            <div class="space-y-1.5">
              <label class="label">Ke Gudang</label>
              <select v-model="transferForm.to_warehouse_id" required class="input-field w-full">
                <option value="">Pilih Gudang</option>
                <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
              </select>
            </div>
          </div>
          <div class="space-y-1.5">
            <label class="label">Jumlah</label>
            <input v-model="transferForm.quantity" type="number" min="1" required class="input-field w-full" />
          </div>
          <p v-if="formError" class="text-red-400 text-xs">{{ formError }}</p>
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="showTransferModal = false" class="btn-secondary">Batal</button>
            <button type="submit" :disabled="submitting" class="btn-primary">
              {{ submitting ? 'Memproses...' : 'Transfer' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { ArrowLeftRight, Pencil } from 'lucide-vue-next'
import { inventoryApi } from '@/api/inventory'
import { warehousesApi } from '@/api/warehouses'
import { productsApi } from '@/api/products'
import { useAuthStore } from '@/stores/auth'
import type { Inventory, Warehouse, Product, PaginationMeta } from '@/types'

const auth = useAuthStore()
const inventory = ref<Inventory[]>([])
const warehouses = ref<Warehouse[]>([])
const products = ref<Product[]>([])
const meta = ref<PaginationMeta | null>(null)
const loading = ref(true)
const warehouseFilter = ref('')
const lowStockOnly = ref(false)
const showUpdateModal = ref(false)
const showTransferModal = ref(false)
const updatingInv = ref<Inventory | null>(null)
const submitting = ref(false)
const formError = ref('')

const updateForm = ref({ qty_on_hand: 0, min_stock: 10, max_stock: 1000 })
const transferForm = ref({ product_id: '', from_warehouse_id: '', to_warehouse_id: '', quantity: 1 })

const visiblePages = computed(() => {
  if (!meta.value) return []
  const { current_page, last_page } = meta.value
  const pages: number[] = []
  for (let i = Math.max(1, current_page - 2); i <= Math.min(last_page, current_page + 2); i++) pages.push(i)
  return pages
})

async function fetchInventory(page = 1) {
  loading.value = true
  try {
    const res = await inventoryApi.list({ page, warehouse_id: warehouseFilter.value || undefined, low_stock: lowStockOnly.value ? true : undefined, per_page: 20 })
    inventory.value = res.data.data
    meta.value = res.data.meta
  } finally {
    loading.value = false
  }
}

function openUpdate(inv: Inventory) {
  if (!auth.canEdit) return
  updatingInv.value = inv
  updateForm.value = { qty_on_hand: inv.qty_on_hand, min_stock: inv.min_stock, max_stock: inv.max_stock }
  showUpdateModal.value = true
}

function openTransfer() {
  if (!auth.canTransfer) return
  transferForm.value = { product_id: '', from_warehouse_id: '', to_warehouse_id: '', quantity: 1 }
  formError.value = ''
  showTransferModal.value = true
}

async function handleUpdate() {
  if (!updatingInv.value) return
  submitting.value = true
  try {
    await inventoryApi.update(updatingInv.value.id, updateForm.value)
    showUpdateModal.value = false
    fetchInventory(meta.value?.current_page ?? 1)
  } finally {
    submitting.value = false
  }
}

async function handleTransfer() {
  submitting.value = true
  formError.value = ''
  try {
    await inventoryApi.transfer(transferForm.value)
    showTransferModal.value = false
    fetchInventory(meta.value?.current_page ?? 1)
  } catch (e: any) {
    formError.value = e.response?.data?.message ?? 'Gagal transfer'
  } finally {
    submitting.value = false
  }
}

onMounted(async () => {
  const [, whRes, prodRes] = await Promise.all([fetchInventory(), warehousesApi.list({ per_page: 100 }), productsApi.list({ per_page: 100, active: 1 })])
  warehouses.value = whRes.data.data
  products.value = prodRes.data.data
})
</script>

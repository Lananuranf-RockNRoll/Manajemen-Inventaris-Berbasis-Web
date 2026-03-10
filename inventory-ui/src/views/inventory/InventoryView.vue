<template>
  <div class="space-y-5">

    <!-- Header -->
    <div class="page-header">
      <div>
        <h2 class="page-title">Inventaris</h2>
        <p class="page-subtitle">{{ meta?.total ?? 0 }} item stok</p>
      </div>
      <div class="flex flex-wrap items-center gap-2 self-start sm:self-auto">
        <button @click="exportExcel" :disabled="exporting" class="btn-export-excel">
          <FileSpreadsheet class="w-3.5 h-3.5" />
          <span>{{ exporting ? 'Loading...' : 'Excel' }}</span>
        </button>
        <button v-if="auth.canTransfer" @click="openTransfer" class="btn-primary">
          <ArrowLeftRight class="w-4 h-4" />
          <span class="hidden sm:inline">Transfer Stok</span>
          <span class="sm:hidden">Transfer</span>
        </button>
      </div>
    </div>

    <!-- Filters -->
    <div class="filter-row">
      <select v-model="warehouseFilter" @change="fetchInventory(1)" class="input-field w-full sm:w-52">
        <option value="">Semua Gudang</option>
        <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
      </select>
      <label class="flex items-center gap-2 text-sm text-zinc-400 cursor-pointer select-none">
        <input v-model="lowStockOnly" @change="fetchInventory(1)" type="checkbox" class="w-4 h-4 accent-amber-500" />
        Stok Rendah Saja
      </label>
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
              <tr
                v-for="inv in inventory"
                :key="inv.id"
                class="border-b border-zinc-800/50 hover:bg-zinc-800/30 transition-colors"
              >
                <td class="td">
                  <p class="text-sm font-medium text-zinc-200">{{ inv.product?.name }}</p>
                  <p class="text-xs font-mono text-indigo-400">{{ inv.product?.sku }}</p>
                </td>
                <td class="td text-sm text-zinc-400">{{ inv.warehouse?.name }}</td>
                <td class="td text-right text-sm font-bold text-zinc-200 tabular-nums">{{ inv.qty_on_hand }}</td>
                <td
                  class="td text-right text-sm font-bold tabular-nums"
                  :class="inv.is_low_stock ? 'text-amber-400' : 'text-emerald-400'"
                >
                  {{ inv.qty_available }}
                </td>
                <td class="td text-right text-sm text-zinc-500 tabular-nums">{{ inv.min_stock }}</td>
                <td class="td text-center">
                  <span v-if="inv.is_low_stock" class="badge-amber">Stok Rendah</span>
                  <span v-else class="badge-green">Normal</span>
                </td>
                <td v-if="auth.canEdit" class="td text-center">
                  <button @click="openUpdate(inv)" class="btn-icon text-zinc-400 hover:text-indigo-400" title="Update stok">
                    <Pencil class="w-3.5 h-3.5" />
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Mobile cards -->
        <div class="sm:hidden divide-y divide-zinc-800">
          <div
            v-for="inv in inventory"
            :key="inv.id"
            class="p-4 hover:bg-zinc-800/30 transition-colors"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-zinc-200 truncate">{{ inv.product?.name }}</p>
                <p class="text-xs font-mono text-indigo-400">{{ inv.product?.sku }}</p>
                <p class="text-xs text-zinc-500 mt-0.5">{{ inv.warehouse?.name }}</p>
                <div class="flex items-center gap-3 mt-2 flex-wrap">
                  <span class="text-xs text-zinc-400">
                    Stok: <strong :class="inv.is_low_stock ? 'text-amber-400' : 'text-emerald-400'">{{ inv.qty_available }}</strong>
                    <span class="text-zinc-600"> / {{ inv.qty_on_hand }}</span>
                  </span>
                  <span v-if="inv.is_low_stock" class="badge-amber text-[10px]">Stok Rendah</span>
                  <span v-else class="badge-green text-[10px]">Normal</span>
                </div>
              </div>
              <button
                v-if="auth.canEdit"
                @click="openUpdate(inv)"
                class="btn-icon text-zinc-400 hover:text-indigo-400 shrink-0"
              >
                <Pencil class="w-4 h-4" />
              </button>
            </div>
          </div>
          <div v-if="inventory.length === 0" class="py-12 text-center text-zinc-500 text-sm">
            Tidak ada data inventaris.
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
          @click="fetchInventory(page)"
          :class="[
            'px-3 py-1.5 rounded-lg transition-colors text-xs font-medium',
            page === meta.current_page
              ? 'bg-indigo-600 text-white'
              : 'bg-zinc-800 text-zinc-400 hover:bg-zinc-700',
          ]"
        >
          {{ page }}
        </button>
      </div>
    </div>

    <!-- Update Stok Modal -->
    <Teleport to="body">
      <div v-if="showUpdateModal" class="modal-overlay" @click.self="showUpdateModal = false">
        <div class="modal-box sm:max-w-sm">
          <h3 class="text-base font-bold text-zinc-100 mb-1">Update Stok</h3>
          <p class="text-xs text-zinc-500 mb-5">{{ updatingInv?.product?.name }}</p>
          <form @submit.prevent="handleUpdate" class="space-y-4">
            <div class="space-y-1.5">
              <label class="label">Qty Di Tangan</label>
              <input v-model.number="updateForm.qty_on_hand" type="number" min="0" class="input-field w-full" />
            </div>
            <div class="space-y-1.5">
              <label class="label">Min Stok</label>
              <input v-model.number="updateForm.min_stock" type="number" min="0" class="input-field w-full" />
            </div>
            <div class="space-y-1.5">
              <label class="label">Max Stok</label>
              <input v-model.number="updateForm.max_stock" type="number" min="1" class="input-field w-full" />
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
    </Teleport>

    <!-- Transfer Modal -->
    <Teleport to="body">
      <div v-if="showTransferModal" class="modal-overlay" @click.self="showTransferModal = false">
        <div class="modal-box">
          <h3 class="text-base font-bold text-zinc-100 mb-5">Transfer Stok</h3>
          <form @submit.prevent="handleTransfer" class="space-y-4">
            <div class="space-y-1.5">
              <label class="label">Produk *</label>
              <select v-model="transferForm.product_id" required class="input-field w-full">
                <option value="">Pilih Produk</option>
                <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
              </select>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="space-y-1.5">
                <label class="label">Dari Gudang *</label>
                <select v-model="transferForm.from_warehouse_id" required class="input-field w-full">
                  <option value="">Pilih Gudang</option>
                  <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                </select>
              </div>
              <div class="space-y-1.5">
                <label class="label">Ke Gudang *</label>
                <select v-model="transferForm.to_warehouse_id" required class="input-field w-full">
                  <option value="">Pilih Gudang</option>
                  <option
                    v-for="w in warehouses"
                    :key="w.id"
                    :value="w.id"
                    :disabled="w.id === transferForm.from_warehouse_id"
                  >
                    {{ w.name }}
                  </option>
                </select>
              </div>
            </div>
            <div class="space-y-1.5">
              <label class="label">Jumlah *</label>
              <input v-model.number="transferForm.quantity" type="number" min="1" required class="input-field w-full" />
            </div>
            <p v-if="formError" class="text-red-400 text-xs bg-red-950/40 border border-red-900 rounded-lg px-3 py-2">
              {{ formError }}
            </p>
            <div class="flex justify-end gap-3 pt-2">
              <button type="button" @click="showTransferModal = false" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="submitting" class="btn-primary">
                {{ submitting ? 'Memproses...' : 'Transfer' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { ArrowLeftRight, Pencil, FileSpreadsheet } from 'lucide-vue-next'
import { inventoryApi } from '@/api/inventory'
import { warehousesApi } from '@/api/warehouses'
import { productsApi } from '@/api/products'
import { reportsApi, downloadBlob } from '@/api/reports'
import { useAuthStore } from '@/stores/auth'
import type { Inventory, Warehouse, Product, PaginationMeta } from '@/types'

const auth = useAuthStore()

const inventory         = ref<Inventory[]>([])
const warehouses        = ref<Warehouse[]>([])
const products          = ref<Product[]>([])
const meta              = ref<PaginationMeta | null>(null)
const loading           = ref(true)
const exporting         = ref(false)
const warehouseFilter   = ref<number | ''>('')
const lowStockOnly      = ref(false)
const showUpdateModal   = ref(false)
const showTransferModal = ref(false)
const updatingInv       = ref<Inventory | null>(null)
const submitting        = ref(false)
const formError         = ref('')

const updateForm   = ref({ qty_on_hand: 0, min_stock: 10, max_stock: 1000 })
const transferForm = ref({ product_id: '' as number | '', from_warehouse_id: '' as number | '', to_warehouse_id: '' as number | '', quantity: 1 })

const visiblePages = computed((): number[] => {
  if (!meta.value) return []
  const { current_page, last_page } = meta.value
  const pages: number[] = []
  for (let i = Math.max(1, current_page - 2); i <= Math.min(last_page, current_page + 2); i++) pages.push(i)
  return pages
})

async function fetchInventory(page = 1): Promise<void> {
  loading.value = true
  try {
    const res = await inventoryApi.list({
      page,
      warehouse_id: warehouseFilter.value || undefined,
      low_stock: lowStockOnly.value ? true : undefined,
      per_page: 20,
    })
    inventory.value = res.data.data
    meta.value = res.data.meta
  } finally {
    loading.value = false
  }
}

async function exportExcel(): Promise<void> {
  exporting.value = true
  try {
    const res = await reportsApi.inventoryExcel(warehouseFilter.value ? { warehouse_id: Number(warehouseFilter.value) } : undefined)
    downloadBlob(res.data, `laporan-inventaris-${new Date().toISOString().slice(0, 10)}.xlsx`)
  } catch { alert('Gagal export Excel') }
  finally { exporting.value = false }
}

function openUpdate(inv: Inventory): void {
  updatingInv.value  = inv
  updateForm.value   = { qty_on_hand: inv.qty_on_hand, min_stock: inv.min_stock, max_stock: inv.max_stock }
  showUpdateModal.value = true
}

function openTransfer(): void {
  transferForm.value = { product_id: '', from_warehouse_id: '', to_warehouse_id: '', quantity: 1 }
  formError.value = ''
  showTransferModal.value = true
}

async function handleUpdate(): Promise<void> {
  if (!updatingInv.value) return
  submitting.value = true
  try {
    await inventoryApi.update(updatingInv.value.id, updateForm.value)
    showUpdateModal.value = false
    await fetchInventory(meta.value?.current_page ?? 1)
  } catch (e: any) {
    alert(e.response?.data?.message ?? 'Gagal update stok')
  } finally {
    submitting.value = false
  }
}

async function handleTransfer(): Promise<void> {
  submitting.value = true
  formError.value = ''
  try {
    await inventoryApi.transfer(transferForm.value)
    showTransferModal.value = false
    await fetchInventory(meta.value?.current_page ?? 1)
  } catch (e: any) {
    formError.value = e.response?.data?.message ?? 'Gagal transfer stok'
  } finally {
    submitting.value = false
  }
}

onMounted(async (): Promise<void> => {
  const [, whRes, prodRes] = await Promise.all([
    fetchInventory(),
    warehousesApi.list({ per_page: 100 }),
    productsApi.list({ per_page: 100, active: 1 }),
  ])
  warehouses.value = whRes.data.data
  products.value   = prodRes.data.data
})
</script>

<template>
  <div class="space-y-5">
    <!-- Header -->
    <div class="page-header">
      <div>
        <h2 class="page-title">Produk</h2>
        <p class="page-subtitle">{{ meta?.total ?? 0 }} produk terdaftar</p>
      </div>
      <button v-if="auth.canCreate" @click="openCreate" class="btn-primary self-start sm:self-auto">
        <Plus class="w-4 h-4" />
        <span>Tambah Produk</span>
      </button>
    </div>

    <!-- Filters -->
    <div class="filter-row">
      <input v-model="search" @input="debouncedFetch" placeholder="Cari nama / SKU..." class="input-field w-full sm:w-64" />
      <select v-model="categoryFilter" @change="fetchProducts(1)" class="input-field w-full sm:w-48">
        <option value="">Semua Kategori</option>
        <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
      </select>
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
                <th class="th">SKU</th>
                <th class="th">Nama Produk</th>
                <th class="th">Kategori</th>
                <th class="th text-right">Harga Modal</th>
                <th class="th text-right">Harga Jual</th>
                <th class="th text-right">Margin</th>
                <th class="th text-center">Status</th>
                <th v-if="auth.canEdit || auth.canDelete" class="th text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="product in products" :key="product.id"
                class="border-b border-zinc-800/50 hover:bg-zinc-800/30 transition-colors">
                <td class="td"><span class="font-mono text-xs text-indigo-400">{{ product.sku }}</span></td>
                <td class="td">
                  <p class="text-sm font-medium text-zinc-200 max-w-xs truncate">{{ product.name }}</p>
                  <p v-if="product.description" class="text-xs text-zinc-500 truncate max-w-xs">{{ product.description }}</p>
                </td>
                <td class="td"><span class="badge-indigo">{{ product.category?.name }}</span></td>
                <td class="td text-right text-sm text-zinc-300">{{ fmtUSD(product.standard_cost) }}</td>
                <td class="td text-right text-sm font-semibold text-zinc-100">{{ fmtUSD(product.list_price) }}</td>
                <td class="td text-right"><span class="text-xs font-medium text-emerald-400">+{{ product.profit_percentage }}%</span></td>
                <td class="td text-center">
                  <span :class="product.is_active ? 'badge-green' : 'badge-red'">{{ product.is_active ? 'Aktif' : 'Nonaktif' }}</span>
                </td>
                <td v-if="auth.canEdit || auth.canDelete" class="td text-center">
                  <div class="flex items-center justify-center gap-2">
                    <button v-if="auth.canEdit" @click="openEdit(product)" class="btn-icon text-zinc-400 hover:text-indigo-400" title="Edit"><Pencil class="w-3.5 h-3.5" /></button>
                    <button v-if="auth.canDelete" @click="confirmDelete(product)" class="btn-icon text-zinc-400 hover:text-red-400" title="Hapus"><Trash2 class="w-3.5 h-3.5" /></button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Mobile -->
        <div class="sm:hidden divide-y divide-zinc-800">
          <div v-for="product in products" :key="product.id" class="p-4 hover:bg-zinc-800/30 transition-colors">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2 flex-wrap">
                  <span class="font-mono text-xs text-indigo-400">{{ product.sku }}</span>
                  <span :class="product.is_active ? 'badge-green' : 'badge-red'">{{ product.is_active ? 'Aktif' : 'Nonaktif' }}</span>
                </div>
                <p class="text-sm font-semibold text-zinc-200 mt-1 truncate">{{ product.name }}</p>
                <div class="flex items-center gap-3 mt-2 flex-wrap">
                  <span class="badge-indigo text-[10px]">{{ product.category?.name }}</span>
                  <span class="text-xs text-zinc-400">Jual: <strong class="text-zinc-200">{{ fmtUSD(product.list_price) }}</strong></span>
                  <span class="text-xs text-emerald-400 font-medium">+{{ product.profit_percentage }}%</span>
                </div>
              </div>
              <div v-if="auth.canEdit || auth.canDelete" class="flex gap-1 shrink-0">
                <button v-if="auth.canEdit" @click="openEdit(product)" class="btn-icon text-zinc-400 hover:text-indigo-400"><Pencil class="w-4 h-4" /></button>
                <button v-if="auth.canDelete" @click="confirmDelete(product)" class="btn-icon text-zinc-400 hover:text-red-400"><Trash2 class="w-4 h-4" /></button>
              </div>
            </div>
          </div>
          <div v-if="products.length === 0" class="py-12 text-center text-zinc-500 text-sm">Tidak ada produk ditemukan.</div>
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
        <button v-for="page in visiblePages" :key="page" @click="fetchProducts(page)"
          :class="['px-3 py-1.5 rounded-lg transition-colors text-xs font-medium', page === meta.current_page ? 'bg-indigo-600 text-white' : 'bg-zinc-800 text-zinc-400 hover:bg-zinc-700']">
          {{ page }}
        </button>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <Teleport to="body">
      <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
        <div class="modal-box sm:max-w-lg">
          <h3 class="text-base font-bold text-zinc-100 mb-5">{{ editingProduct ? 'Edit Produk' : 'Tambah Produk' }}</h3>
          <form @submit.prevent="handleSubmit" class="space-y-4">
            <div class="space-y-1.5">
              <label class="label">Nama Produk *</label>
              <input v-model="form.name" type="text" required class="input-field w-full" />
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="space-y-1.5">
                <label class="label">SKU *</label>
                <input v-model="form.sku" type="text" required class="input-field w-full" :disabled="!!editingProduct" />
              </div>
              <div class="space-y-1.5">
                <label class="label">Kategori *</label>
                <select v-model="form.category_id" required class="input-field w-full">
                  <option value="">Pilih Kategori</option>
                  <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
              </div>
              <div class="space-y-1.5">
                <label class="label">Harga Modal (USD) *</label>
                <div class="relative">
                  <span class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-500 text-sm">$</span>
                  <input v-model="form.standard_cost" type="number" step="0.01" min="0" required class="input-field w-full pl-7" />
                </div>
              </div>
              <div class="space-y-1.5">
                <label class="label">Harga Jual (USD) *</label>
                <div class="relative">
                  <span class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-500 text-sm">$</span>
                  <input v-model="form.list_price" type="number" step="0.01" min="0" required class="input-field w-full pl-7" />
                </div>
              </div>
            </div>
            <div class="space-y-1.5">
              <label class="label">Deskripsi</label>
              <textarea v-model="form.description" rows="2" class="input-field w-full resize-none" />
            </div>
            <label class="flex items-center gap-2 cursor-pointer">
              <input v-model="form.is_active" type="checkbox" class="w-4 h-4 accent-indigo-600" />
              <span class="text-sm text-zinc-300">Produk Aktif</span>
            </label>
            <p v-if="formError" class="text-red-400 text-xs bg-red-950/40 border border-red-900 rounded-lg px-3 py-2">{{ formError }}</p>
            <div class="flex justify-end gap-3 pt-2">
              <button type="button" @click="showModal = false" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="submitting" class="btn-primary">{{ submitting ? 'Menyimpan...' : 'Simpan' }}</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Delete Modal -->
    <Teleport to="body">
      <div v-if="showDeleteModal" class="modal-overlay" @click.self="showDeleteModal = false">
        <div class="modal-box sm:max-w-sm">
          <h3 class="text-base font-bold text-zinc-100 mb-2">Hapus Produk?</h3>
          <p class="text-sm text-zinc-400 mb-6">Produk <strong class="text-zinc-200">{{ deletingProduct?.name }}</strong> akan dihapus.</p>
          <div class="flex justify-end gap-3">
            <button @click="showDeleteModal = false" class="btn-secondary">Batal</button>
            <button @click="handleDelete" :disabled="submitting" class="btn-danger">{{ submitting ? 'Menghapus...' : 'Hapus' }}</button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Plus, Pencil, Trash2 } from 'lucide-vue-next'
import { productsApi } from '@/api/products'
import { categoriesApi } from '@/api/categories'
import { useAuthStore } from '@/stores/auth'
import type { Product, Category, PaginationMeta } from '@/types'

const auth = useAuthStore()
const products       = ref<Product[]>([])
const categories     = ref<Category[]>([])
const meta           = ref<PaginationMeta | null>(null)
const loading        = ref(true)
const search         = ref('')
const categoryFilter = ref<number | ''>('')
const showModal      = ref(false)
const showDeleteModal = ref(false)
const editingProduct  = ref<Product | null>(null)
const deletingProduct = ref<Product | null>(null)
const submitting     = ref(false)
const formError      = ref('')

const defaultForm = () => ({ name: '', sku: '', category_id: '' as number | '', standard_cost: '', list_price: '', description: '', is_active: true })
const form = ref(defaultForm())

const visiblePages = computed((): number[] => {
  if (!meta.value) return []
  const { current_page, last_page } = meta.value
  const pages: number[] = []
  for (let i = Math.max(1, current_page - 2); i <= Math.min(last_page, current_page + 2); i++) pages.push(i)
  return pages
})

function fmtUSD(val: string | number): string {
  return '$' + Number(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

let debounceTimer: ReturnType<typeof setTimeout>
function debouncedFetch(): void {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => fetchProducts(1), 400)
}

async function fetchProducts(page = 1): Promise<void> {
  loading.value = true
  try {
    const res = await productsApi.list({ page, search: search.value || undefined, category_id: categoryFilter.value || undefined, per_page: 15 })
    products.value = res.data.data
    meta.value = res.data.meta
  } finally { loading.value = false }
}

async function fetchCategories(): Promise<void> {
  const res = await categoriesApi.list({ per_page: 100 })
  categories.value = res.data.data
}

function openCreate(): void { editingProduct.value = null; form.value = defaultForm(); formError.value = ''; showModal.value = true }
function openEdit(product: Product): void {
  editingProduct.value = product
  form.value = { name: product.name, sku: product.sku, category_id: product.category?.id ?? '', standard_cost: product.standard_cost, list_price: product.list_price, description: product.description ?? '', is_active: product.is_active }
  formError.value = ''; showModal.value = true
}
function confirmDelete(product: Product): void { deletingProduct.value = product; showDeleteModal.value = true }

async function handleSubmit(): Promise<void> {
  submitting.value = true; formError.value = ''
  try {
    if (editingProduct.value) { await productsApi.update(editingProduct.value.id, form.value) }
    else { await productsApi.create(form.value) }
    showModal.value = false
    await fetchProducts(meta.value?.current_page ?? 1)
  } catch (e: any) {
    const errors = e.response?.data?.errors
    formError.value = errors ? (Object.values(errors) as string[][]).flat().join(', ') : (e.response?.data?.message ?? 'Gagal menyimpan')
  } finally { submitting.value = false }
}

async function handleDelete(): Promise<void> {
  if (!deletingProduct.value) return
  submitting.value = true
  try { await productsApi.destroy(deletingProduct.value.id); showDeleteModal.value = false; await fetchProducts(meta.value?.current_page ?? 1) }
  catch (e: any) { alert(e.response?.data?.message ?? 'Gagal menghapus produk') }
  finally { submitting.value = false }
}

onMounted(() => { fetchProducts(); fetchCategories() })
</script>

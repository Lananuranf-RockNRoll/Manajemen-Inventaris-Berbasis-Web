<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-lg font-bold text-zinc-100">Produk</h2>
        <p class="text-xs text-zinc-500">{{ meta?.total ?? 0 }} produk terdaftar</p>
      </div>
      <button v-if="auth.canCreate" @click="openCreate" class="btn-primary">
        <Plus class="w-4 h-4" /> Tambah Produk
      </button>
    </div>

    <div class="flex gap-3 flex-wrap">
      <input v-model="search" @input="debouncedFetch" placeholder="Cari nama / SKU..." class="input-field w-64" />
      <select v-model="categoryFilter" @change="fetchProducts(1)" class="input-field w-48">
        <option value="">Semua Kategori</option>
        <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
      </select>
    </div>

    <div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden">
      <div v-if="loading" class="py-16 text-center text-zinc-500 text-sm">Memuat data...</div>
      <table v-else class="w-full">
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
            <p class="text-xs text-zinc-500 truncate max-w-xs">{{ product.description }}</p>
          </td>
          <td class="td"><span class="badge-indigo">{{ product.category?.name }}</span></td>
          <td class="td text-right text-sm text-zinc-300">Rp {{ formatPrice(product.standard_cost) }}</td>
          <td class="td text-right text-sm font-semibold text-zinc-100">Rp {{ formatPrice(product.list_price) }}</td>
          <td class="td text-right"><span class="text-xs font-medium text-emerald-400">+{{ product.profit_percentage }}%</span></td>
          <td class="td text-center">
              <span :class="product.is_active ? 'badge-green' : 'badge-red'">
                {{ product.is_active ? 'Aktif' : 'Nonaktif' }}
              </span>
          </td>
          <td v-if="auth.canEdit || auth.canDelete" class="td text-center">
            <div class="flex items-center justify-center gap-2">
              <button v-if="auth.canEdit" @click="openEdit(product)" class="btn-icon text-zinc-400 hover:text-indigo-400">
                <Pencil class="w-3.5 h-3.5" />
              </button>
              <button v-if="auth.canDelete" @click="confirmDelete(product)" class="btn-icon text-zinc-400 hover:text-red-400">
                <Trash2 class="w-3.5 h-3.5" />
              </button>
            </div>
          </td>
        </tr>
        </tbody>
      </table>

      <!-- Viewer notice -->
      <div v-if="auth.isViewer" class="px-4 py-2 bg-zinc-800/30 border-t border-zinc-800 text-xs text-zinc-500">
        ℹ️ Anda login sebagai <strong>viewer</strong> — hanya dapat melihat data.
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="meta" class="flex items-center justify-between text-xs text-zinc-500">
      <span>Menampilkan {{ meta.from }}–{{ meta.to }} dari {{ meta.total }}</span>
      <div class="flex gap-1">
        <button v-for="page in visiblePages" :key="page" @click="fetchProducts(page)"
                :class="['px-3 py-1.5 rounded-lg transition-colors', page === meta.current_page ? 'bg-indigo-600 text-white font-semibold' : 'bg-zinc-800 text-zinc-400 hover:bg-zinc-700']">
          {{ page }}
        </button>
      </div>
    </div>

    <!-- Modal Create/Edit -->
    <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
      <div class="modal-box">
        <h3 class="text-base font-bold text-zinc-100 mb-5">
          {{ editingProduct ? 'Edit Produk' : 'Tambah Produk' }}
        </h3>
        <form @submit.prevent="handleSubmit" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2 space-y-1.5">
              <label class="label">Nama Produk</label>
              <input v-model="form.name" type="text" required class="input-field w-full" />
            </div>
            <div class="space-y-1.5">
              <label class="label">SKU</label>
              <input v-model="form.sku" type="text" required class="input-field w-full" />
            </div>
            <div class="space-y-1.5">
              <label class="label">Kategori</label>
              <select v-model="form.category_id" required class="input-field w-full">
                <option value="">Pilih Kategori</option>
                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
            </div>
            <div class="space-y-1.5">
              <label class="label">Harga Modal</label>
              <input v-model="form.standard_cost" type="number" step="0.01" required class="input-field w-full" />
            </div>
            <div class="space-y-1.5">
              <label class="label">Harga Jual</label>
              <input v-model="form.list_price" type="number" step="0.01" required class="input-field w-full" />
            </div>
            <div class="col-span-2 space-y-1.5">
              <label class="label">Deskripsi</label>
              <textarea v-model="form.description" rows="2" class="input-field w-full resize-none"></textarea>
            </div>
            <div class="col-span-2 flex items-center gap-2">
              <input v-model="form.is_active" type="checkbox" id="is_active" class="w-4 h-4 accent-indigo-600" />
              <label for="is_active" class="text-sm text-zinc-300">Produk Aktif</label>
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
        <h3 class="text-base font-bold text-zinc-100 mb-2">Hapus Produk?</h3>
        <p class="text-sm text-zinc-400 mb-6">
          Produk <strong class="text-zinc-200">{{ deletingProduct?.name }}</strong> akan dihapus secara permanen.
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
import { productsApi } from '@/api/products'
import { categoriesApi } from '@/api/categories'
import { useAuthStore } from '@/stores/auth'
import type { Product, Category, PaginationMeta } from '@/types'

const auth = useAuthStore()
const products = ref<Product[]>([])
const categories = ref<Category[]>([])
const meta = ref<PaginationMeta | null>(null)
const loading = ref(true)
const search = ref('')
const categoryFilter = ref('')
const showModal = ref(false)
const showDeleteModal = ref(false)
const editingProduct = ref<Product | null>(null)
const deletingProduct = ref<Product | null>(null)
const submitting = ref(false)
const formError = ref('')

const form = ref({
  name: '', sku: '', category_id: '', standard_cost: '',
  list_price: '', description: '', is_active: true,
})

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
  debounceTimer = setTimeout(() => fetchProducts(1), 400)
}

async function fetchProducts(page = 1) {
  loading.value = true
  try {
    const res = await productsApi.list({
      page,
      search: search.value || undefined,
      category_id: categoryFilter.value || undefined,
      per_page: 15,
    })
    products.value = res.data.data
    meta.value = res.data.meta
  } finally {
    loading.value = false
  }
}

async function fetchCategories() {
  const res = await categoriesApi.list({ per_page: 100 })
  categories.value = res.data.data
}

function openCreate() {
  if (!auth.canCreate) return
  editingProduct.value = null
  form.value = { name: '', sku: '', category_id: '', standard_cost: '', list_price: '', description: '', is_active: true }
  formError.value = ''
  showModal.value = true
}

function openEdit(product: Product) {
  if (!auth.canEdit) return
  editingProduct.value = product
  form.value = {
    name: product.name, sku: product.sku,
    category_id: String(product.category?.id ?? ''),
    standard_cost: product.standard_cost,
    list_price: product.list_price,
    description: product.description ?? '',
    is_active: product.is_active,
  }
  formError.value = ''
  showModal.value = true
}

function confirmDelete(product: Product) {
  if (!auth.canDelete) return
  deletingProduct.value = product
  showDeleteModal.value = true
}

async function handleSubmit() {
  submitting.value = true
  formError.value = ''
  try {
    if (editingProduct.value) {
      await productsApi.update(editingProduct.value.id, form.value)
    } else {
      await productsApi.create(form.value)
    }
    showModal.value = false
    fetchProducts(meta.value?.current_page ?? 1)
  } catch (e: any) {
    const errors = e.response?.data?.errors
    formError.value = errors
        ? Object.values(errors).flat().join(', ')
        : e.response?.data?.message ?? 'Gagal menyimpan'
  } finally {
    submitting.value = false
  }
}

async function handleDelete() {
  if (!deletingProduct.value) return
  submitting.value = true
  try {
    await productsApi.destroy(deletingProduct.value.id)
    showDeleteModal.value = false
    fetchProducts(meta.value?.current_page ?? 1)
  } finally {
    submitting.value = false
  }
}

function formatPrice(val: string | number) {
  return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Number(val))
}

onMounted(() => {
  fetchProducts()
  fetchCategories()
})
</script>
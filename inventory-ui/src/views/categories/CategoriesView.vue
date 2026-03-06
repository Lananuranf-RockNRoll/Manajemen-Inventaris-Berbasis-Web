<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-lg font-bold text-zinc-100">Kategori</h2>
        <p class="text-xs text-zinc-500">{{ meta?.total ?? 0 }} kategori</p>
      </div>
      <button v-if="auth.canCreate" @click="openCreate" class="btn-primary">
        <Plus class="w-4 h-4" /> Tambah Kategori
      </button>
    </div>

    <div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden">
      <div v-if="loading" class="py-16 text-center text-zinc-500 text-sm">Memuat data...</div>
      <table v-else class="w-full">
        <thead>
          <tr class="border-b border-zinc-800">
            <th class="th">Nama</th>
            <th class="th">Slug</th>
            <th class="th">Deskripsi</th>
            <th class="th text-center">Status</th>
            <th v-if="auth.canEdit || auth.canDelete" class="th text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="cat in categories" :key="cat.id"
            class="border-b border-zinc-800/50 hover:bg-zinc-800/30 transition-colors">
            <td class="td font-semibold text-zinc-200">{{ cat.name }}</td>
            <td class="td font-mono text-xs text-indigo-400">{{ cat.slug }}</td>
            <td class="td text-sm text-zinc-400">{{ cat.description ?? '—' }}</td>
            <td class="td text-center">
              <span :class="cat.is_active ? 'badge-green' : 'badge-red'">
                {{ cat.is_active ? 'Aktif' : 'Nonaktif' }}
              </span>
            </td>
            <td v-if="auth.canEdit || auth.canDelete" class="td text-center">
              <div class="flex items-center justify-center gap-2">
                <button v-if="auth.canEdit" @click="openEdit(cat)" class="btn-icon text-zinc-400 hover:text-indigo-400">
                  <Pencil class="w-3.5 h-3.5" />
                </button>
                <button v-if="auth.canDelete" @click="confirmDelete(cat)" class="btn-icon text-zinc-400 hover:text-red-400">
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

    <!-- Modal -->
    <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
      <div class="modal-box">
        <h3 class="text-base font-bold text-zinc-100 mb-5">{{ editingCat ? 'Edit Kategori' : 'Tambah Kategori' }}</h3>
        <form @submit.prevent="handleSubmit" class="space-y-4">
          <div class="space-y-1.5">
            <label class="label">Nama Kategori</label>
            <input v-model="form.name" type="text" required class="input-field w-full" />
          </div>
          <div class="space-y-1.5">
            <label class="label">Deskripsi</label>
            <textarea v-model="form.description" rows="2" class="input-field w-full resize-none"></textarea>
          </div>
          <div class="flex items-center gap-2">
            <input v-model="form.is_active" type="checkbox" id="cat_active" class="w-4 h-4 accent-indigo-600" />
            <label for="cat_active" class="text-sm text-zinc-300">Aktif</label>
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
        <h3 class="text-base font-bold text-zinc-100 mb-2">Hapus Kategori?</h3>
        <p class="text-sm text-zinc-400 mb-6">
          Kategori <strong class="text-zinc-200">{{ deletingCat?.name }}</strong> akan dihapus.
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
import { ref, onMounted } from 'vue'
import { Plus, Pencil, Trash2 } from 'lucide-vue-next'
import { categoriesApi } from '@/api/categories'
import { useAuthStore } from '@/stores/auth'
import type { Category, PaginationMeta } from '@/types'

const auth = useAuthStore()
const categories = ref<Category[]>([])
const meta = ref<PaginationMeta | null>(null)
const loading = ref(true)
const showModal = ref(false)
const showDeleteModal = ref(false)
const editingCat = ref<Category | null>(null)
const deletingCat = ref<Category | null>(null)
const submitting = ref(false)
const formError = ref('')
const form = ref({ name: '', description: '', is_active: true })

async function fetchCategories() {
  loading.value = true
  try {
    const res = await categoriesApi.list({ per_page: 50 })
    categories.value = res.data.data
    meta.value = res.data.meta
  } finally {
    loading.value = false
  }
}

function openCreate() {
  if (!auth.canCreate) return
  editingCat.value = null
  form.value = { name: '', description: '', is_active: true }
  formError.value = ''
  showModal.value = true
}

function openEdit(cat: Category) {
  if (!auth.canEdit) return
  editingCat.value = cat
  form.value = { name: cat.name, description: cat.description ?? '', is_active: cat.is_active }
  formError.value = ''
  showModal.value = true
}

function confirmDelete(cat: Category) {
  if (!auth.canDelete) return
  deletingCat.value = cat
  showDeleteModal.value = true
}

async function handleSubmit() {
  submitting.value = true
  formError.value = ''
  try {
    if (editingCat.value) {
      await categoriesApi.update(editingCat.value.id, form.value)
    } else {
      await categoriesApi.create(form.value)
    }
    showModal.value = false
    fetchCategories()
  } catch (e: any) {
    formError.value = e.response?.data?.message ?? 'Gagal menyimpan'
  } finally {
    submitting.value = false
  }
}

async function handleDelete() {
  if (!deletingCat.value) return
  submitting.value = true
  try {
    await categoriesApi.destroy(deletingCat.value.id)
    showDeleteModal.value = false
    fetchCategories()
  } finally {
    submitting.value = false
  }
}

onMounted(fetchCategories)
</script>

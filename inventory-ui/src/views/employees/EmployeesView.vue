<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-lg font-bold text-zinc-100">Karyawan</h2>
        <p class="text-xs text-zinc-500">{{ meta?.total ?? 0 }} karyawan</p>
      </div>
      <button v-if="auth.canCreate" @click="openCreate" class="btn-primary">
        <Plus class="w-4 h-4" /> Tambah Karyawan
      </button>
    </div>

    <div class="flex gap-3">
      <input v-model="search" @input="debouncedFetch" placeholder="Cari nama / email..." class="input-field w-64" />
      <select v-model="warehouseFilter" @change="fetchEmployees(1)" class="input-field w-48">
        <option value="">Semua Gudang</option>
        <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
      </select>
    </div>

    <div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden">
      <div v-if="loading" class="py-16 text-center text-zinc-500 text-sm">Memuat data...</div>
      <table v-else class="w-full">
        <thead>
          <tr class="border-b border-zinc-800">
            <th class="th">Nama</th>
            <th class="th">Jabatan</th>
            <th class="th">Gudang</th>
            <th class="th">Tanggal Masuk</th>
            <th class="th text-center">Status</th>
            <th v-if="auth.canEdit || auth.canDelete" class="th text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="emp in employees" :key="emp.id"
            class="border-b border-zinc-800/50 hover:bg-zinc-800/30 transition-colors">
            <td class="td">
              <p class="text-sm font-semibold text-zinc-200">{{ emp.name }}</p>
              <p class="text-xs text-zinc-500">{{ emp.email }}</p>
            </td>
            <td class="td text-sm text-zinc-400">{{ emp.job_title ?? '—' }}</td>
            <td class="td text-sm text-zinc-400">{{ emp.warehouse?.name ?? '—' }}</td>
            <td class="td text-sm text-zinc-400">{{ emp.hire_date ?? '—' }}</td>
            <td class="td text-center">
              <span :class="emp.is_active ? 'badge-green' : 'badge-red'">
                {{ emp.is_active ? 'Aktif' : 'Nonaktif' }}
              </span>
            </td>
            <td v-if="auth.canEdit || auth.canDelete" class="td text-center">
              <div class="flex items-center justify-center gap-2">
                <button v-if="auth.canEdit" @click="openEdit(emp)" class="btn-icon text-zinc-400 hover:text-indigo-400">
                  <Pencil class="w-3.5 h-3.5" />
                </button>
                <button v-if="auth.canDelete" @click="confirmDelete(emp)" class="btn-icon text-zinc-400 hover:text-red-400">
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
        <button v-for="page in visiblePages" :key="page" @click="fetchEmployees(page)"
          :class="['px-3 py-1.5 rounded-lg transition-colors', page === meta.current_page ? 'bg-indigo-600 text-white font-semibold' : 'bg-zinc-800 text-zinc-400 hover:bg-zinc-700']">
          {{ page }}
        </button>
      </div>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
      <div class="modal-box">
        <h3 class="text-base font-bold text-zinc-100 mb-5">{{ editingEmp ? 'Edit Karyawan' : 'Tambah Karyawan' }}</h3>
        <form @submit.prevent="handleSubmit" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2 space-y-1.5">
              <label class="label">Nama Lengkap</label>
              <input v-model="form.name" type="text" required class="input-field w-full" />
            </div>
            <div class="space-y-1.5">
              <label class="label">Email</label>
              <input v-model="form.email" type="email" required class="input-field w-full" />
            </div>
            <div class="space-y-1.5">
              <label class="label">Telepon</label>
              <input v-model="form.phone" type="text" class="input-field w-full" />
            </div>
            <div class="space-y-1.5">
              <label class="label">Jabatan</label>
              <input v-model="form.job_title" type="text" class="input-field w-full" />
            </div>
            <div class="space-y-1.5">
              <label class="label">Departemen</label>
              <input v-model="form.department" type="text" class="input-field w-full" />
            </div>
            <div class="space-y-1.5">
              <label class="label">Gudang</label>
              <select v-model="form.warehouse_id" class="input-field w-full">
                <option value="">Tanpa Gudang</option>
                <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
              </select>
            </div>
            <div class="space-y-1.5">
              <label class="label">Tanggal Masuk</label>
              <input v-model="form.hire_date" type="date" class="input-field w-full" />
            </div>
            <div class="col-span-2 flex items-center gap-2">
              <input v-model="form.is_active" type="checkbox" id="emp_active" class="w-4 h-4 accent-indigo-600" />
              <label for="emp_active" class="text-sm text-zinc-300">Karyawan Aktif</label>
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
        <h3 class="text-base font-bold text-zinc-100 mb-2">Hapus Karyawan?</h3>
        <p class="text-sm text-zinc-400 mb-6">
          Karyawan <strong class="text-zinc-200">{{ deletingEmp?.name }}</strong> akan dihapus.
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
import { employeesApi } from '@/api/employees'
import { warehousesApi } from '@/api/warehouses'
import { useAuthStore } from '@/stores/auth'
import type { Employee, Warehouse, PaginationMeta } from '@/types'

const auth = useAuthStore()
const employees = ref<Employee[]>([])
const warehouses = ref<Warehouse[]>([])
const meta = ref<PaginationMeta | null>(null)
const loading = ref(true)
const search = ref('')
const warehouseFilter = ref('')
const showModal = ref(false)
const showDeleteModal = ref(false)
const editingEmp = ref<Employee | null>(null)
const deletingEmp = ref<Employee | null>(null)
const submitting = ref(false)
const formError = ref('')

const form = ref({ name: '', email: '', phone: '', job_title: '', department: '', warehouse_id: '', hire_date: '', is_active: true })

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
  debounceTimer = setTimeout(() => fetchEmployees(1), 400)
}

async function fetchEmployees(page = 1) {
  loading.value = true
  try {
    const res = await employeesApi.list({ page, search: search.value || undefined, warehouse_id: warehouseFilter.value || undefined, per_page: 15 })
    employees.value = res.data.data
    meta.value = res.data.meta
  } finally {
    loading.value = false
  }
}

function openCreate() {
  if (!auth.canCreate) return
  editingEmp.value = null
  form.value = { name: '', email: '', phone: '', job_title: '', department: '', warehouse_id: '', hire_date: '', is_active: true }
  formError.value = ''
  showModal.value = true
}

function openEdit(emp: Employee) {
  if (!auth.canEdit) return
  editingEmp.value = emp
  form.value = { name: emp.name, email: emp.email, phone: emp.phone ?? '', job_title: emp.job_title ?? '', department: emp.department ?? '', warehouse_id: String(emp.warehouse_id ?? ''), hire_date: emp.hire_date ?? '', is_active: emp.is_active }
  formError.value = ''
  showModal.value = true
}

function confirmDelete(emp: Employee) {
  if (!auth.canDelete) return
  deletingEmp.value = emp
  showDeleteModal.value = true
}

async function handleSubmit() {
  submitting.value = true
  formError.value = ''
  try {
    if (editingEmp.value) {
      await employeesApi.update(editingEmp.value.id, form.value)
    } else {
      await employeesApi.create(form.value)
    }
    showModal.value = false
    fetchEmployees(meta.value?.current_page ?? 1)
  } catch (e: any) {
    formError.value = e.response?.data?.message ?? 'Gagal menyimpan'
  } finally {
    submitting.value = false
  }
}

async function handleDelete() {
  if (!deletingEmp.value) return
  submitting.value = true
  try {
    await employeesApi.destroy(deletingEmp.value.id)
    showDeleteModal.value = false
    fetchEmployees(meta.value?.current_page ?? 1)
  } finally {
    submitting.value = false
  }
}

onMounted(async () => {
  const [, whRes] = await Promise.all([fetchEmployees(), warehousesApi.list({ per_page: 100 })])
  warehouses.value = whRes.data.data
})
</script>

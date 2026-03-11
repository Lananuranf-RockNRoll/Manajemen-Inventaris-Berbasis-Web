<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-lg font-bold text-zinc-100">Gudang</h2>
        <p class="text-xs text-zinc-500">{{ meta?.total ?? 0 }} gudang</p>
      </div>
      <button v-if="auth.canCreateWarehouse" @click="openCreate" class="btn-primary">
        <Plus class="w-4 h-4" /> Tambah Gudang
      </button>
    </div>

    <div v-if="loading" class="py-16 text-center text-zinc-500 text-sm">Memuat data...</div>
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="wh in warehouses" :key="wh.id"
        class="bg-zinc-900 border border-zinc-800 rounded-xl p-5 hover:border-zinc-700 transition-colors">
        <div class="flex items-start justify-between mb-3">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg bg-indigo-950 flex items-center justify-center">
              <WarehouseIcon class="w-4 h-4 text-indigo-400" />
            </div>
            <div>
              <p class="text-sm font-bold text-zinc-100">{{ wh.name }}</p>
              <p class="text-xs text-zinc-500">{{ wh.city }}, {{ wh.country }}</p>
            </div>
          </div>
          <span :class="wh.is_active ? 'badge-green' : 'badge-red'">
            {{ wh.is_active ? 'Aktif' : 'Nonaktif' }}
          </span>
        </div>
        <div class="space-y-1 mb-4">
          <div class="flex items-center gap-2 text-xs text-zinc-500">
            <MapPin class="w-3 h-3" />
            <span>{{ wh.address ?? '—' }}</span>
          </div>
          <div class="flex items-center gap-2 text-xs text-zinc-500">
            <Globe class="w-3 h-3" />
            <span>{{ wh.region ?? '—' }}</span>
          </div>
        </div>
        <div v-if="auth.canEditWarehouse || auth.canDeleteWarehouse" class="flex gap-2 pt-3 border-t border-zinc-800">
          <button v-if="auth.canEditWarehouse" @click="openEdit(wh)" class="btn-secondary flex-1 text-xs py-1.5">
            <Pencil class="w-3 h-3" /> Edit
          </button>
          <button v-if="auth.canDeleteWarehouse" @click="confirmDelete(wh)" class="btn-danger flex-1 text-xs py-1.5">
            <Trash2 class="w-3 h-3" /> Hapus
          </button>
        </div>
        <div v-else class="pt-3 border-t border-zinc-800">
          <p class="text-xs text-zinc-600 text-center">Hanya lihat</p>
        </div>
      </div>
    </div>

    <div v-if="auth.isViewer" class="text-xs text-zinc-500 text-center py-2">
      ℹ️ Anda login sebagai <strong>viewer</strong> — hanya dapat melihat data.
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
      <div class="modal-box">
        <h3 class="text-base font-bold text-zinc-100 mb-5">{{ editingWh ? 'Edit Gudang' : 'Tambah Gudang' }}</h3>
        <form @submit.prevent="handleSubmit" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2 space-y-1.5">
              <label class="label">Nama Gudang</label>
              <input v-model="form.name" type="text" required class="input-field w-full" />
            </div>
            <div class="space-y-1.5"><label class="label">Region</label><input v-model="form.region" type="text" class="input-field w-full" /></div>
            <div class="space-y-1.5"><label class="label">Negara</label><input v-model="form.country" type="text" class="input-field w-full" /></div>
            <div class="space-y-1.5"><label class="label">Provinsi/State</label><input v-model="form.state" type="text" class="input-field w-full" /></div>
            <div class="space-y-1.5"><label class="label">Kota</label><input v-model="form.city" type="text" class="input-field w-full" /></div>
            <div class="space-y-1.5"><label class="label">Kode Pos</label><input v-model="form.postal_code" type="text" class="input-field w-full" /></div>
            <div class="space-y-1.5"><label class="label">Telepon</label><input v-model="form.phone" type="text" class="input-field w-full" /></div>
            <div class="col-span-2 space-y-1.5">
              <label class="label">Alamat Lengkap</label>
              <textarea v-model="form.address" rows="2" class="input-field w-full resize-none"></textarea>
            </div>
            <div class="col-span-2 flex items-center gap-2">
              <input v-model="form.is_active" type="checkbox" id="wh_active" class="w-4 h-4 accent-indigo-600" />
              <label for="wh_active" class="text-sm text-zinc-300">Gudang Aktif</label>
            </div>
          </div>
          <p v-if="formError" class="text-red-400 text-xs">{{ formError }}</p>
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="showModal = false" class="btn-secondary">Batal</button>
            <button type="submit" :disabled="submitting" class="btn-primary">{{ submitting ? 'Menyimpan...' : 'Simpan' }}</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Delete Modal -->
    <div v-if="showDeleteModal" class="modal-overlay" @click.self="showDeleteModal = false">
      <div class="modal-box max-w-sm">
        <h3 class="text-base font-bold text-zinc-100 mb-2">Hapus Gudang?</h3>
        <p class="text-sm text-zinc-400 mb-6">Gudang <strong class="text-zinc-200">{{ deletingWh?.name }}</strong> akan dihapus.</p>
        <div class="flex justify-end gap-3">
          <button @click="showDeleteModal = false" class="btn-secondary">Batal</button>
          <button @click="handleDelete" :disabled="submitting" class="btn-danger">{{ submitting ? 'Menghapus...' : 'Hapus' }}</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Plus, Pencil, Trash2, Warehouse as WarehouseIcon, MapPin, Globe } from 'lucide-vue-next'
import { warehousesApi } from '@/api/warehouses'
import { useAuthStore } from '@/stores/auth'
import type { Warehouse as WarehouseType, PaginationMeta } from '@/types'

const auth = useAuthStore()
const warehouses = ref<WarehouseType[]>([])
const meta = ref<PaginationMeta | null>(null)
const loading = ref(true)
const showModal = ref(false)
const showDeleteModal = ref(false)
const editingWh = ref<WarehouseType | null>(null)
const deletingWh = ref<WarehouseType | null>(null)
const submitting = ref(false)
const formError = ref('')
const form = ref({ name: '', region: '', country: '', state: '', city: '', postal_code: '', address: '', phone: '', is_active: true })

async function fetchWarehouses() {
  loading.value = true
  try {
    const res = await warehousesApi.list({ per_page: 50 })
    warehouses.value = res.data.data
    meta.value = res.data.meta
  } finally { loading.value = false }
}

function openCreate() {
  editingWh.value = null
  form.value = { name: '', region: '', country: '', state: '', city: '', postal_code: '', address: '', phone: '', is_active: true }
  formError.value = ''
  showModal.value = true
}

function openEdit(wh: WarehouseType) {
  editingWh.value = wh
  form.value = { name: wh.name, region: wh.region ?? '', country: wh.country ?? '', state: wh.state ?? '', city: wh.city ?? '', postal_code: wh.postal_code ?? '', address: wh.address ?? '', phone: wh.phone ?? '', is_active: wh.is_active }
  formError.value = ''
  showModal.value = true
}

function confirmDelete(wh: WarehouseType) {
  deletingWh.value = wh
  showDeleteModal.value = true
}

async function handleSubmit() {
  submitting.value = true
  formError.value = ''
  try {
    if (editingWh.value) { await warehousesApi.update(editingWh.value.id, form.value) }
    else { await warehousesApi.create(form.value) }
    showModal.value = false
    fetchWarehouses()
  } catch (e: any) {
    formError.value = e.response?.data?.message ?? 'Gagal menyimpan'
  } finally { submitting.value = false }
}

async function handleDelete() {
  if (!deletingWh.value) return
  submitting.value = true
  try {
    await warehousesApi.destroy(deletingWh.value.id)
    showDeleteModal.value = false
    fetchWarehouses()
  } finally { submitting.value = false }
}

onMounted(fetchWarehouses)
</script>

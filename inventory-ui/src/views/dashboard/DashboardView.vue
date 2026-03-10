<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { TrendingUp, ShoppingCart, Package, AlertTriangle, Users, FileText } from 'lucide-vue-next'
import { dashboardApi } from '@/api/dashboard'
import { reportsApi, downloadBlob } from '@/api/reports'
import type { DashboardSummary } from '@/types'

const summary       = ref<DashboardSummary | null>(null)
const topProducts   = ref<any[]>([])
const lowStockItems = ref<any[]>([])
const loading       = ref(true)
const loadingTop    = ref(true)
const exporting     = ref(false)

function fmtUSD(num: number | string): string {
  return '$' + Number(num).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function fmtInt(num: number | string): string {
  return Number(num).toLocaleString('en-US', { maximumFractionDigits: 0 })
}

const kpiCards = computed(() => {
  if (!summary.value) return []
  return [
    {
      label: 'Total Revenue',
      value: fmtUSD(summary.value.total_revenue),
      sub: `Bulan ini: ${fmtUSD(summary.value.revenue_this_month)}`,
      icon: TrendingUp,
      bgColor: 'bg-indigo-950',
      iconColor: 'text-indigo-400',
    },
    {
      label: 'Total Order',
      value: String(summary.value.total_orders),
      sub: `${summary.value.pending_orders} pending`,
      icon: ShoppingCart,
      bgColor: 'bg-emerald-950',
      iconColor: 'text-emerald-400',
    },
    {
      label: 'Total Produk',
      value: String(summary.value.total_products),
      sub: `${summary.value.low_stock_alerts} stok rendah`,
      icon: Package,
      bgColor: 'bg-amber-950',
      iconColor: 'text-amber-400',
    },
    {
      label: 'Total Customer',
      value: String(summary.value.total_customers),
      sub: `${summary.value.total_warehouses} gudang aktif`,
      icon: Users,
      bgColor: 'bg-blue-950',
      iconColor: 'text-blue-400',
    },
  ]
})

const orderStatus = computed(() => {
  if (!summary.value) return []
  return [
    { label: 'Pending',   value: summary.value.pending_orders,  color: 'bg-amber-400' },
    { label: 'Shipped',   value: summary.value.shipped_orders,  color: 'bg-indigo-400' },
    { label: 'Delivered', value: summary.value.total_orders - summary.value.pending_orders - summary.value.shipped_orders - summary.value.canceled_orders, color: 'bg-emerald-400' },
    { label: 'Canceled',  value: summary.value.canceled_orders, color: 'bg-red-400' },
  ]
})

async function exportPdf(): Promise<void> {
  exporting.value = true
  try {
    const res = await reportsApi.dashboardPdf()
    downloadBlob(res.data, `dashboard-${new Date().toISOString().slice(0, 10)}.pdf`)
  } catch {
    alert('Gagal export PDF dashboard')
  } finally {
    exporting.value = false
  }
}

onMounted(async (): Promise<void> => {
  try {
    const [summaryRes, topRes, lowRes] = await Promise.all([
      dashboardApi.summary(),
      dashboardApi.topProducts(),
      dashboardApi.lowStock(),
    ])
    summary.value       = summaryRes.data.data
    topProducts.value   = topRes.data.data
    lowStockItems.value = lowRes.data.data
  } catch {
    // Non-fatal
  } finally {
    loading.value    = false
    loadingTop.value = false
  }
})
</script>

<template>
  <div class="space-y-5">

    <div class="page-header">
      <div>
        <h2 class="page-title">Dashboard</h2>
        <p class="page-subtitle">Ringkasan kinerja inventaris</p>
      </div>
      <button @click="exportPdf" :disabled="exporting" class="btn-export-pdf self-start sm:self-auto">
        <FileText class="w-3.5 h-3.5" />
        <span>{{ exporting ? 'Menyiapkan...' : 'Print PDF' }}</span>
      </button>
    </div>

    <!-- KPI Cards -->
    <div v-if="loading" class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
      <div v-for="i in 4" :key="i" class="card p-4 animate-pulse">
        <div class="h-3 bg-zinc-800 rounded w-2/3 mb-3" />
        <div class="h-7 bg-zinc-800 rounded w-1/2" />
      </div>
    </div>
    <div v-else class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
      <div v-for="card in kpiCards" :key="card.label" class="card p-4">
        <div class="flex items-center justify-between mb-3">
          <span class="text-xs font-medium text-zinc-500 leading-tight">{{ card.label }}</span>
          <div :class="`p-2 rounded-lg ${card.bgColor} shrink-0`">
            <component :is="card.icon" :class="`w-3.5 h-3.5 ${card.iconColor}`" />
          </div>
        </div>
        <p class="text-xl sm:text-2xl font-bold text-zinc-100 truncate">{{ card.value }}</p>
        <p class="text-xs text-zinc-500 mt-1 truncate">{{ card.sub }}</p>
      </div>
    </div>

    <!-- Second row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <div class="card p-5">
        <h3 class="text-sm font-semibold text-zinc-300 mb-4">Status Order</h3>
        <div v-if="loading" class="space-y-3">
          <div v-for="i in 3" :key="i" class="h-5 bg-zinc-800 rounded animate-pulse" />
        </div>
        <div v-else class="space-y-3">
          <div v-for="s in orderStatus" :key="s.label" class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div :class="`w-2 h-2 rounded-full shrink-0 ${s.color}`" />
              <span class="text-xs text-zinc-400">{{ s.label }}</span>
            </div>
            <span class="text-sm font-bold text-zinc-200 tabular-nums">{{ s.value }}</span>
          </div>
        </div>
      </div>

      <div class="lg:col-span-2 card p-5">
        <h3 class="text-sm font-semibold text-zinc-300 mb-4">Top 5 Produk</h3>
        <div v-if="loadingTop" class="space-y-3">
          <div v-for="i in 5" :key="i" class="h-10 bg-zinc-800 rounded animate-pulse" />
        </div>
        <div v-else-if="topProducts.length === 0" class="text-center py-6 text-zinc-500 text-sm">
          Belum ada data transaksi.
        </div>
        <div v-else class="space-y-1.5">
          <div v-for="(p, i) in topProducts.slice(0, 5)" :key="p.id"
            class="flex items-center gap-3 p-2 rounded-lg hover:bg-zinc-800/50 transition-colors">
            <span class="w-5 text-xs font-bold text-zinc-600 shrink-0 tabular-nums">{{ i + 1 }}</span>
            <div class="flex-1 min-w-0">
              <p class="text-xs font-medium text-zinc-200 truncate">{{ p.name }}</p>
              <p class="text-xs text-zinc-500 truncate">{{ p.category_name }}</p>
            </div>
            <div class="text-right shrink-0">
              <p class="text-xs font-bold text-indigo-400">{{ fmtUSD(p.total_revenue) }}</p>
              <p class="text-xs text-zinc-500">{{ fmtInt(p.total_qty) }} unit</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Low stock alert -->
    <div v-if="!loading && lowStockItems.length > 0" class="card border-amber-900/50 p-5">
      <div class="flex items-center gap-2 mb-4">
        <AlertTriangle class="w-4 h-4 text-amber-400 shrink-0" />
        <h3 class="text-sm font-semibold text-amber-400">
          Peringatan Stok Rendah ({{ lowStockItems.length }})
        </h3>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
        <div v-for="item in lowStockItems.slice(0, 6)" :key="item.inventory_id"
          class="flex items-center gap-3 p-3 bg-zinc-800/50 rounded-lg border border-zinc-700">
          <div class="flex-1 min-w-0">
            <p class="text-xs font-medium text-zinc-200 truncate">{{ item.product_name }}</p>
            <p class="text-xs text-zinc-500 truncate">{{ item.warehouse_name }}</p>
          </div>
          <div class="text-right shrink-0">
            <p class="text-sm font-bold text-amber-400 tabular-nums">{{ item.qty_available }}</p>
            <p class="text-xs text-zinc-600">min {{ item.min_stock }}</p>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

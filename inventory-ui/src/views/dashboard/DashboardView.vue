<template>
  <div class="space-y-6">
    <!-- KPI Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div
        v-for="card in kpiCards"
        :key="card.label"
        class="bg-zinc-900 border border-zinc-800 rounded-xl p-4"
      >
        <div class="flex items-center justify-between mb-3">
          <span class="text-xs font-medium text-zinc-500">{{ card.label }}</span>
          <div :class="`p-2 rounded-lg ${card.bgColor}`">
            <component :is="card.icon" :class="`w-3.5 h-3.5 ${card.iconColor}`" />
          </div>
        </div>
        <p class="text-2xl font-bold text-zinc-100">{{ card.value }}</p>
        <p class="text-xs text-zinc-500 mt-1">{{ card.sub }}</p>
      </div>
    </div>

    <!-- Second row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <!-- Orders status -->
      <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5">
        <h3 class="text-sm font-semibold text-zinc-300 mb-4">Status Order</h3>
        <div class="space-y-3">
          <div v-for="s in orderStatus" :key="s.label" class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div :class="`w-2 h-2 rounded-full ${s.color}`"></div>
              <span class="text-xs text-zinc-400">{{ s.label }}</span>
            </div>
            <span class="text-sm font-bold text-zinc-200">{{ s.value }}</span>
          </div>
        </div>
      </div>

      <!-- Top products -->
      <div class="lg:col-span-2 bg-zinc-900 border border-zinc-800 rounded-xl p-5">
        <h3 class="text-sm font-semibold text-zinc-300 mb-4">Top 5 Produk</h3>
        <div v-if="loadingTop" class="text-center py-6 text-zinc-500 text-sm">Memuat...</div>
        <div v-else class="space-y-2">
          <div
            v-for="(p, i) in topProducts.slice(0, 5)"
            :key="p.id"
            class="flex items-center gap-3 p-2 rounded-lg hover:bg-zinc-800/50 transition-colors"
          >
            <span class="w-5 text-xs font-bold text-zinc-600">{{ i + 1 }}</span>
            <div class="flex-1 min-w-0">
              <p class="text-xs font-medium text-zinc-200 truncate">{{ p.name }}</p>
              <p class="text-xs text-zinc-500">{{ p.category_name }}</p>
            </div>
            <div class="text-right shrink-0">
              <p class="text-xs font-bold text-indigo-400">Rp {{ formatNumber(p.total_revenue) }}</p>
              <p class="text-xs text-zinc-500">{{ p.total_qty }} unit</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Low stock alert -->
    <div v-if="lowStockItems.length > 0" class="bg-zinc-900 border border-amber-900/50 rounded-xl p-5">
      <div class="flex items-center gap-2 mb-4">
        <AlertTriangle class="w-4 h-4 text-amber-400" />
        <h3 class="text-sm font-semibold text-amber-400">Peringatan Stok Rendah ({{ lowStockItems.length }})</h3>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
        <div
          v-for="item in lowStockItems.slice(0, 6)"
          :key="item.inventory_id"
          class="flex items-center gap-3 p-3 bg-zinc-800/50 rounded-lg border border-zinc-700"
        >
          <div class="flex-1 min-w-0">
            <p class="text-xs font-medium text-zinc-200 truncate">{{ item.product_name }}</p>
            <p class="text-xs text-zinc-500">{{ item.warehouse_name }}</p>
          </div>
          <div class="text-right shrink-0">
            <p class="text-sm font-bold text-amber-400">{{ item.qty_available }}</p>
            <p class="text-xs text-zinc-600">/ min {{ item.min_stock }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import {
  TrendingUp,
  ShoppingCart,
  Package,
  AlertTriangle,
  Users,
  Warehouse,
} from 'lucide-vue-next'
import { dashboardApi } from '@/api/dashboard'
import type { DashboardSummary } from '@/types'

const summary = ref<DashboardSummary | null>(null)
const topProducts = ref<any[]>([])
const lowStockItems = ref<any[]>([])
const loadingTop = ref(true)

const kpiCards = computed(() => {
  if (!summary.value) return []
  return [
    {
      label: 'Total Revenue',
      value: `Rp ${formatNumber(summary.value.total_revenue)}`,
      sub: `Bulan ini: Rp ${formatNumber(summary.value.revenue_this_month)}`,
      icon: TrendingUp,
      bgColor: 'bg-indigo-950',
      iconColor: 'text-indigo-400',
    },
    {
      label: 'Total Order',
      value: summary.value.total_orders,
      sub: `${summary.value.pending_orders} pending`,
      icon: ShoppingCart,
      bgColor: 'bg-emerald-950',
      iconColor: 'text-emerald-400',
    },
    {
      label: 'Total Produk',
      value: summary.value.total_products,
      sub: `${summary.value.low_stock_alerts} stok rendah`,
      icon: Package,
      bgColor: 'bg-amber-950',
      iconColor: 'text-amber-400',
    },
    {
      label: 'Total Customer',
      value: summary.value.total_customers,
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
    { label: 'Pending', value: summary.value.pending_orders, color: 'bg-amber-400' },
    { label: 'Shipped', value: summary.value.shipped_orders, color: 'bg-indigo-400' },
    { label: 'Canceled', value: summary.value.canceled_orders, color: 'bg-red-400' },
  ]
})

function formatNumber(num: number) {
  return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(num)
}

onMounted(async () => {
  try {
    const [summaryRes, topRes, lowRes] = await Promise.all([
      dashboardApi.summary(),
      dashboardApi.topProducts(),
      dashboardApi.lowStock(),
    ])
    summary.value = summaryRes.data.data
    topProducts.value = topRes.data.data
    lowStockItems.value = lowRes.data.data
  } finally {
    loadingTop.value = false
  }
})
</script>

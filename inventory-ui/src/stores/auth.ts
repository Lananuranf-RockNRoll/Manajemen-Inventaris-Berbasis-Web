import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi } from '@/api/auth'
import type { User } from '@/types'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(
    localStorage.getItem('user') ? JSON.parse(localStorage.getItem('user')!) : null
  )
  const token = ref<string | null>(localStorage.getItem('token'))
  const permissions = ref<string[]>(
    localStorage.getItem('permissions') ? JSON.parse(localStorage.getItem('permissions')!) : []
  )

  // ── Auth state ────────────────────────────────────────────────────────────
  const isAuthenticated = computed(() => !!token.value)

  // ── Role helpers ──────────────────────────────────────────────────────────
  const isAdmin   = computed(() => user.value?.role === 'admin')
  const isManager = computed(() => user.value?.role === 'manager')
  const isStaff   = computed(() => user.value?.role === 'staff')
  const isViewer  = computed(() => user.value?.role === 'viewer')

  // ── Permission checker ────────────────────────────────────────────────────
  /**
   * Cek apakah user punya permission tertentu.
   * Gunakan ini untuk menyembunyikan/menampilkan tombol di UI.
   *
   * Contoh:
   *   v-if="auth.can('product.create')"
   *   v-if="auth.can('transaction.delete')"
   */
  function can(permission: string): boolean {
    return permissions.value.includes(permission)
  }

  /**
   * Cek apakah user punya setidaknya satu dari beberapa permission.
   */
  function canAny(perms: string[]): boolean {
    return perms.some(p => permissions.value.includes(p))
  }

  // ── Computed permission flags (shorthand untuk template) ──────────────────
  // Products
  const canViewProduct   = computed(() => can('product.view'))
  const canCreateProduct = computed(() => can('product.create'))
  const canEditProduct   = computed(() => can('product.update'))
  const canDeleteProduct = computed(() => can('product.delete'))

  // Categories
  const canViewCategory   = computed(() => can('category.view'))
  const canCreateCategory = computed(() => can('category.create'))
  const canEditCategory   = computed(() => can('category.update'))
  const canDeleteCategory = computed(() => can('category.delete'))

  // Warehouses
  const canViewWarehouse   = computed(() => can('warehouse.view'))
  const canCreateWarehouse = computed(() => can('warehouse.create'))
  const canEditWarehouse   = computed(() => can('warehouse.update'))
  const canDeleteWarehouse = computed(() => can('warehouse.delete'))

  // Inventory
  const canViewInventory     = computed(() => can('inventory.view'))
  const canEditInventory     = computed(() => can('inventory.update'))
  const canTransferInventory = computed(() => can('inventory.transfer'))

  // Customers
  const canViewCustomer   = computed(() => can('customer.view'))
  const canCreateCustomer = computed(() => can('customer.create'))
  const canEditCustomer   = computed(() => can('customer.update'))
  const canDeleteCustomer = computed(() => can('customer.delete'))
  const canManageCredit   = computed(() => can('customer.credit'))

  // Employees
  const canViewEmployee   = computed(() => can('employee.view'))
  const canCreateEmployee = computed(() => can('employee.create'))
  const canEditEmployee   = computed(() => can('employee.update'))
  const canDeleteEmployee = computed(() => can('employee.delete'))

  // Transactions
  const canViewTransaction         = computed(() => can('transaction.view'))
  const canCreateTransaction       = computed(() => can('transaction.create'))
  const canEditTransaction         = computed(() => can('transaction.update'))
  const canUpdateTransactionStatus = computed(() => can('transaction.update_status'))
  const canDeleteTransaction       = computed(() => can('transaction.delete'))

  // Users & Reports
  const canManageUsers = computed(() => can('user.manage'))
  const canViewReports = computed(() => can('report.view'))

  // ── Actions ───────────────────────────────────────────────────────────────
  async function login(email: string, password: string) {
    const res = await authApi.login(email, password)
    const { user: userData, token: tokenData, permissions: perms } = res.data.data

    user.value        = userData
    token.value       = tokenData
    permissions.value = perms ?? []

    localStorage.setItem('token',       tokenData)
    localStorage.setItem('user',        JSON.stringify(userData))
    localStorage.setItem('permissions', JSON.stringify(perms ?? []))

    return userData
  }

  async function logout() {
    try { await authApi.logout() } finally {
      user.value        = null
      token.value       = null
      permissions.value = []

      localStorage.removeItem('token')
      localStorage.removeItem('user')
      localStorage.removeItem('permissions')
    }
  }

  return {
    // State
    user, token, permissions,

    // Auth
    isAuthenticated, isAdmin, isManager, isStaff, isViewer,

    // Permission checkers
    can, canAny,

    // Permission flags — products
    canViewProduct, canCreateProduct, canEditProduct, canDeleteProduct,

    // Permission flags — categories
    canViewCategory, canCreateCategory, canEditCategory, canDeleteCategory,

    // Permission flags — warehouses
    canViewWarehouse, canCreateWarehouse, canEditWarehouse, canDeleteWarehouse,

    // Permission flags — inventory
    canViewInventory, canEditInventory, canTransferInventory,

    // Permission flags — customers
    canViewCustomer, canCreateCustomer, canEditCustomer, canDeleteCustomer, canManageCredit,

    // Permission flags — employees
    canViewEmployee, canCreateEmployee, canEditEmployee, canDeleteEmployee,

    // Permission flags — transactions
    canViewTransaction, canCreateTransaction, canEditTransaction,
    canUpdateTransactionStatus, canDeleteTransaction,

    // Permission flags — admin
    canManageUsers, canViewReports,

    // Actions
    login, logout,
  }
})

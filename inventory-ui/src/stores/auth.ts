import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi } from '@/api/auth'
import type { User } from '@/types'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(
      localStorage.getItem('user') ? JSON.parse(localStorage.getItem('user')!) : null
  )
  const token = ref<string | null>(localStorage.getItem('token'))

  const isAuthenticated = computed(() => !!token.value)
  const isAdmin   = computed(() => user.value?.role === 'admin')
  const isManager = computed(() => ['admin', 'manager'].includes(user.value?.role ?? ''))
  const isStaff   = computed(() => ['admin', 'manager', 'staff'].includes(user.value?.role ?? ''))
  const isViewer  = computed(() => user.value?.role === 'viewer')

  const canCreate   = computed(() => isStaff.value)
  const canEdit     = computed(() => isManager.value)
  const canDelete   = computed(() => isAdmin.value)
  const canTransfer = computed(() => isManager.value)

  async function login(email: string, password: string) {
    const res = await authApi.login(email, password)
    const { user: userData, token: tokenData } = res.data.data
    user.value = userData
    token.value = tokenData
    localStorage.setItem('token', tokenData)
    localStorage.setItem('user', JSON.stringify(userData))
    return userData
  }

  async function logout() {
    try { await authApi.logout() }
    finally {
      user.value = null
      token.value = null
      localStorage.removeItem('token')
      localStorage.removeItem('user')
    }
  }

  return { user, token, isAuthenticated, isAdmin, isManager, isStaff, isViewer, canCreate, canEdit, canDelete, canTransfer, login, logout }
})
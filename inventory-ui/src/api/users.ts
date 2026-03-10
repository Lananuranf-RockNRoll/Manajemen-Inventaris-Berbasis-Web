import api from './index'
import type { User } from '@/types'

export const usersApi = {
  list: (params?: { page?: number; search?: string; role?: string; per_page?: number }) =>
    api.get<{ data: User[]; meta: any }>('/users', { params }),

  get: (id: number) =>
    api.get<{ data: User }>(`/users/${id}`),

  create: (data: { name: string; email: string; password: string; role: string; is_active?: boolean }) =>
    api.post<{ message: string; data: User }>('/users', data),

  update: (id: number, data: Partial<{ name: string; email: string; password: string; role: string; is_active: boolean }>) =>
    api.put<{ message: string; data: User }>(`/users/${id}`, data),

  destroy: (id: number) =>
    api.delete<{ message: string }>(`/users/${id}`),

  toggleActive: (id: number) =>
    api.patch<{ message: string; data: User }>(`/users/${id}/toggle-active`),
}

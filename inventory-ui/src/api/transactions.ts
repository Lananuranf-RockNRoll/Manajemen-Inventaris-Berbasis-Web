import api from './index'

export const transactionsApi = {
  list: (params?: Record<string, any>) =>
    api.get('/transactions', { params }),

  show: (id: number) =>
    api.get(`/transactions/${id}`),

  create: (data: Record<string, any>) =>
    api.post('/transactions', data),

  update: (id: number, data: Record<string, any>) =>
    api.put(`/transactions/${id}`, data),

  updateStatus: (id: number, status: string) =>
    api.patch(`/transactions/${id}/status`, { status }),

  destroy: (id: number) =>
    api.delete(`/transactions/${id}`),
}

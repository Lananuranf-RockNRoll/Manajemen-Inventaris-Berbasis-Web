import api from './index.ts'

export const inventoryApi = {
  list: (params?: Record<string, any>) =>
    api.get('/inventory', { params }),

  show: (id: number) =>
    api.get(`/inventory/${id}`),

  update: (id: number, data: Record<string, any>) =>
    api.put(`/inventory/${id}`, data),

  transfer: (data: Record<string, any>) =>
    api.post('/inventory/transfer', data),

  lowStock: (params?: Record<string, any>) =>
    api.get('/inventory/alerts/low-stock', { params }),
}

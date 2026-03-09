import api from './index'

export const warehousesApi = {
  list: (params?: Record<string, any>) =>
    api.get('/warehouses', { params }),

  show: (id: number) =>
    api.get(`/warehouses/${id}`),

  create: (data: Record<string, any>) =>
    api.post('/warehouses', data),

  update: (id: number, data: Record<string, any>) =>
    api.put(`/warehouses/${id}`, data),

  destroy: (id: number) =>
    api.delete(`/warehouses/${id}`),
}

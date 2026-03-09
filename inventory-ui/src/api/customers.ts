import api from './index'

export const customersApi = {
  list: (params?: Record<string, any>) =>
    api.get('/customers', { params }),

  show: (id: number) =>
    api.get(`/customers/${id}`),

  create: (data: Record<string, any>) =>
    api.post('/customers', data),

  update: (id: number, data: Record<string, any>) =>
    api.put(`/customers/${id}`, data),

  destroy: (id: number) =>
    api.delete(`/customers/${id}`),
}

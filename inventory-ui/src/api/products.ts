import api from './index'

export const productsApi = {
  list: (params?: Record<string, any>) =>
    api.get('/products', { params }),

  show: (id: number) =>
    api.get(`/products/${id}`),

  create: (data: Record<string, any>) =>
    api.post('/products', data),

  update: (id: number, data: Record<string, any>) =>
    api.put(`/products/${id}`, data),

  destroy: (id: number) =>
    api.delete(`/products/${id}`),
}

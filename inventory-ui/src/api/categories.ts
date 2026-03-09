import api from './index'

export const categoriesApi = {
  list: (params?: Record<string, any>) =>
    api.get('/categories', { params }),

  show: (id: number) =>
    api.get(`/categories/${id}`),

  create: (data: Record<string, any>) =>
    api.post('/categories', data),

  update: (id: number, data: Record<string, any>) =>
    api.put(`/categories/${id}`, data),

  destroy: (id: number) =>
    api.delete(`/categories/${id}`),
}

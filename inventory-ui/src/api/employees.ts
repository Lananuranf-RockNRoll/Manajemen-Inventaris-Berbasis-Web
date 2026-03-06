import api from './index.ts'

export const employeesApi = {
  list: (params?: Record<string, any>) =>
    api.get('/employees', { params }),

  show: (id: number) =>
    api.get(`/employees/${id}`),

  create: (data: Record<string, any>) =>
    api.post('/employees', data),

  update: (id: number, data: Record<string, any>) =>
    api.put(`/employees/${id}`, data),

  destroy: (id: number) =>
    api.delete(`/employees/${id}`),
}

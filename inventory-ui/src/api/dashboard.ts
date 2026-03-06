import api from './index.ts'

export const dashboardApi = {
  summary: () =>
    api.get('/dashboard/summary'),

  topProducts: () =>
    api.get('/dashboard/top-products'),

  lowStock: () =>
    api.get('/dashboard/low-stock'),
}

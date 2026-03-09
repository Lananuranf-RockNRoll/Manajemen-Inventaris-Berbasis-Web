import api from './index'

export const dashboardApi = {
  summary: () =>
    api.get('/dashboard/summary'),

  topProducts: () =>
    api.get('/dashboard/top-products'),

  lowStock: () =>
    api.get('/dashboard/low-stock'),
}

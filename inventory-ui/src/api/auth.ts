import api from './index'

export const authApi = {
  login: (email: string, password: string) =>
    api.post('/auth/login', { email, password }),

  logout: () =>
    api.post('/auth/logout'),

  me: () =>
    api.get('/auth/me'),
}

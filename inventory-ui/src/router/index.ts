import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/LoginView.vue'),
      meta: { guest: true },
    },
    {
      path: '/',
      component: () => import('@/components/layout/AppLayout.vue'),
      meta: { requiresAuth: true },
      children: [
        {
          path: '',
          name: 'dashboard',
          component: () => import('@/views/dashboard/DashboardView.vue'),
        },
        {
          path: 'products',
          name: 'products',
          component: () => import('@/views/products/ProductsView.vue'),
        },
        {
          path: 'categories',
          name: 'categories',
          component: () => import('@/views/categories/CategoriesView.vue'),
        },
        {
          path: 'warehouses',
          name: 'warehouses',
          component: () => import('@/views/warehouses/WarehousesView.vue'),
        },
        {
          path: 'inventory',
          name: 'inventory',
          component: () => import('@/views/inventory/InventoryView.vue'),
        },
        {
          path: 'customers',
          name: 'customers',
          component: () => import('@/views/customers/CustomersView.vue'),
        },
        {
          path: 'employees',
          name: 'employees',
          component: () => import('@/views/employees/EmployeesView.vue'),
        },
        {
          path: 'transactions',
          name: 'transactions',
          component: () => import('@/views/transactions/TransactionsView.vue'),
        },
      ],
    },
  ],
})

router.beforeEach((to, _from, next) => {
  const auth = useAuthStore()

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    next('/login')
  } else if (to.meta.guest && auth.isAuthenticated) {
    next('/')
  } else {
    next()
  }
})

export default router

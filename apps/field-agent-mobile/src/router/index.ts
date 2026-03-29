import { createRouter, createWebHistory } from 'vue-router'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      name: 'FieldHome',
      component: () => import('@/pages/FieldHome.vue')
    },
    {
      path: '/product-lookup',
      name: 'ProductLookup',
      component: () => import('@/domains/inventory/views/ProductLookup.vue')
    },
    {
      path: '/incidents/report',
      name: 'ReportIncident',
      component: () => import('@/domains/incidents/views/ReportIncident.vue')
    },
    {
      path: '/movements/register',
      name: 'RegisterMovement',
      component: () => import('@/domains/movements/views/ExecuteMovement.vue')
    }
  ]
})

export default router

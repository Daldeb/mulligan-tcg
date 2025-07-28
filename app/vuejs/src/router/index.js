// router/index.js - Version simplifiée pour commencer
import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'

const routes = [
  {
    path: '/',
    name: 'home',
    component: HomeView,
    meta: {
      title: 'Accueil - MULLIGAN TCG'
    }
  },
  // Temporairement commentées jusqu'à création des vues
  /*
  {
    path: '/discussions',
    name: 'discussions',
    component: () => import('../views/DiscussionsView.vue'),
    meta: {
      title: 'Discussions - MULLIGAN TCG'
    }
  },
  */
  // Route 404
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: HomeView, // Temporairement redirige vers home
    meta: {
      title: 'Page non trouvée - MULLIGAN TCG'
    }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) {
      return savedPosition
    } else {
      return { top: 0 }
    }
  }
})

export default router
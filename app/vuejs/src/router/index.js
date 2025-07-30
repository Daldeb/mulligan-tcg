// router/index.js - Version avec Profile
import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import { useAuthStore } from '../stores/auth'

const routes = [
  {
    path: '/',
    name: 'home',
    component: HomeView,
    meta: {
      title: 'Accueil - MULLIGAN TCG'
    }
  },
  {
    path: '/profile',
    name: 'profile',
    component: () => import('../views/ProfileView.vue'),
    meta: {
      title: 'Mon Profil - MULLIGAN TCG',
      requiresAuth: true
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
  {
    path: '/decks',
    name: 'decks',
    component: () => import('../views/DecksView.vue'),
    meta: {
      title: 'Decks - MULLIGAN TCG'
    }
  },
  {
    path: '/classements',
    name: 'classements',
    component: () => import('../views/ClassementsView.vue'),
    meta: {
      title: 'Classements - MULLIGAN TCG'
    }
  },
  {
    path: '/boutiques',
    name: 'boutiques',
    component: () => import('../views/BoutiquesView.vue'),
    meta: {
      title: 'Boutiques - MULLIGAN TCG'
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

// Guard pour les routes qui nécessitent une authentification
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()
  
  // Vérifier si la route nécessite une authentification
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    // Rediriger vers l'accueil et déclencher l'ouverture de la modal de connexion
    // Tu peux aussi stocker la route de destination pour rediriger après login
    next({ name: 'home' })
  } else {
    next()
  }
})

export default router
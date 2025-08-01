// router/index.js - Version avec Profile et guard async
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
  {
    path: '/decks',
    name: 'decks',
    component: () => import('../views/DecksView.vue'),
    meta: {
      title: 'Decks - MULLIGAN TCG'
    }
  },
  {
    path: '/decks/create',
    name: 'decks-create',
    component: () => import('../views/DecksEditor.vue'),
    meta: {
      title: 'Créer un deck - MULLIGAN TCG',
      requiresAuth: true
    }
  },
  {
    path: '/decks/edit/:id',
    name: 'decks-edit',
    component: () => import('../views/DecksEditor.vue'),
    meta: {
      title: 'Modifier un deck - MULLIGAN TCG',
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

// Guard async pour les routes qui nécessitent une authentification
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()
  
  // Si la route nécessite une auth
  if (to.meta.requiresAuth) {
    // Si pas de token du tout, rediriger
    if (!authStore.token) {
      next({ name: 'home' })
      return
    }
    
    // Si token mais pas d'user, attendre la vérification
    if (authStore.token && !authStore.user) {
      try {
        await authStore.checkAuthStatus()
      } catch (error) {
        console.error('Erreur vérification auth:', error)
        next({ name: 'home' })
        return
      }
    }
    
    // Vérifier à nouveau après checkAuthStatus
    if (!authStore.isAuthenticated) {
      next({ name: 'home' })
      return
    }
  }
  
  next()
})

export default router
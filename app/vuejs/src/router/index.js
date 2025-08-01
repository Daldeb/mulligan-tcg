// router/index.js - Version avec routes decks améliorées
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
  // Route pour créer un deck (supprimée - création direct via modale)
  // {
  //   path: '/decks/create',
  //   name: 'decks-create',
  //   component: () => import('../views/DecksEditor.vue'),
  //   meta: {
  //     title: 'Créer un deck - MULLIGAN TCG',
  //     requiresAuth: true
  //   }
  // },
  
  // Nouvelle route pour éditer un deck avec slug propre
  {
    path: '/mes-decks/:gameSlug/:formatSlug/:deckSlug',
    name: 'deck-editor',
    component: () => import('../views/DecksEditor.vue'),
    meta: {
      title: 'Éditeur de deck - MULLIGAN TCG',
      requiresAuth: true
    },
    props: route => ({
      gameSlug: route.params.gameSlug,
      formatSlug: route.params.formatSlug,
      deckSlug: route.params.deckSlug
    })
  },
  
  // Route alternative pour édition par ID (backward compatibility)
  {
    path: '/decks/edit/:id',
    name: 'decks-edit',
    component: () => import('../views/DecksEditor.vue'),
    meta: {
      title: 'Modifier un deck - MULLIGAN TCG',
      requiresAuth: true
    }
  },
  
  {
    path: '/forums',
    name: 'ForumsView',
    component: () => import('../views/ForumsView.vue'),
    meta: {
      title: 'Forums - MULLIGAN TCG',
      requiresAuth: true
    }
  },
  {
    path: '/forums/:slug',
    name: 'ForumPostsView',
    component: () => import('../views/ForumPostsView.vue'),
    meta: {
      title: 'Forum - MULLIGAN TCG',
      requiresAuth: true
    }
  },
  {
    path: '/posts/:id',
    name: 'PostView',
    component: () => import('../views/PostView.vue'),
    meta: {
      title: 'Sujet - MULLIGAN TCG',
      requiresAuth: true
    }
  },
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
// router/index.js - Version corrigée sans accents dans les paths
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
    path: '/carte-des-boutiques',
    name: 'shop-map',
    component: () => import('../views/ShopMapView.vue'),
    meta: {
      title: 'Carte des boutiques TCG - MULLIGAN TCG'
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
    component: () => import('../views/DecksView.vue'),
    redirect: '/decks/communaute',
    children: [
      {
        path: 'communaute',
        name: 'community-decks',
        component: () => import('../components/decks/CommunityDecksView.vue'),
        meta: {
          title: 'Decks Communautaires - MULLIGAN TCG'
        }
      },
      {
        path: 'metagame',
        name: 'metagame-decks',
        component: () => import('../components/decks/MetagameDecksView.vue'),
        meta: {
          title: 'Metagame - MULLIGAN TCG'
        }
      }
    ]
  },
  {
    path: '/edition/:gameSlug/:formatSlug/:deckSlug',
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
    path: '/mes-decks',
    name: 'MyDecks',
    component: () => import('../views/MyDecksView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/forums/:forumSlug/posts/:id',
    name: 'PostView',
    component: () => import('../views/PostView.vue'),
    meta: {
      title: 'Sujet - MULLIGAN TCG',
      requiresAuth: true
    }
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: HomeView,
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

  if (to.meta.requiresAuth) {
    if (!authStore.token) {
      next({ name: 'home' })
      return
    }

    if (authStore.token && !authStore.user) {
      try {
        await authStore.checkAuthStatus()
      } catch (error) {
        console.error('Erreur vérification auth:', error)
        next({ name: 'home' })
        return
      }
    }

    if (!authStore.isAuthenticated) {
      next({ name: 'home' })
      return
    }
  }

  next()
})

export default router

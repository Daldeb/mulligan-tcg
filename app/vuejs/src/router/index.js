// router/index.js - Version complète avec protection d'authentification et correction réhydratation
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
    redirect: '/decks/metagame',
    children: [
      {
        path: 'communaute',
        name: 'community-decks',
        component: () => import('../views/CommunityDecksView.vue'),
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
    meta: { 
      title: 'Mes Decks - MULLIGAN TCG',
      requiresAuth: true 
    }
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
  // ============= ROUTES ÉVÉNEMENTS SIMPLIFIÉES =============
  {
    path: '/evenements',
    name: 'evenements',
    component: () => import('../views/EventsView.vue'),
    meta: {
      title: 'Événements - MULLIGAN TCG'
    }
  },
  {
    path: '/evenements/creer',
    name: 'creer-evenement',
    component: () => import('../views/CreateEventView.vue'),
    meta: {
      title: 'Créer un événement - MULLIGAN TCG',
      requiresAuth: true,
      requiresRole: ['ROLE_ORGANIZER', 'ROLE_SHOP', 'ROLE_ADMIN']
    }
  },
  {
    path: '/auth-required',
    name: 'auth-required',
    component: () => import('../views/AuthRequiredView.vue'),
    meta: {
      title: 'Connexion requise - MULLIGAN TCG'
    }
  },
  {
    path: '/evenements/creer-tournoi',
    name: 'creer-tournoi',
    component: () => import('../views/CreateTournamentView.vue'),
    meta: {
      title: 'Créer un tournoi - MULLIGAN TCG',
      requiresAuth: true,
      requiresRole: ['ROLE_ORGANIZER', 'ROLE_SHOP', 'ROLE_ADMIN']
    }
  },
  {
    path: '/evenements/:id',
    name: 'evenement-detail',
    component: () => import('../views/EventDetailView.vue'),
    meta: {
      title: 'Événement - MULLIGAN TCG'
    },
    props: route => ({
      eventId: parseInt(route.params.id),
      fromRoute: route.meta.from || 'evenements' // Pour les breadcrumbs
    })
  },
  {
    path: '/mes-evenements',
    name: 'mes-evenements',
    component: () => import('../views/MyEventsView.vue'),
    meta: {
      title: 'Mes événements - MULLIGAN TCG',
      requiresAuth: true,
    }
  },
  
  // ============= ROUTES FOOTER MANQUANTES =============
  {
    path: '/conditions',
    name: 'conditions',
    component: () => import('../views/StaticPages/ConditionsView.vue'),
    meta: {
      title: 'Conditions d\'utilisation - MULLIGAN TCG'
    }
  },
  {
    path: '/rgpd',
    name: 'rgpd',
    component: () => import('../views/StaticPages/RgpdView.vue'),
    meta: {
      title: 'Protection des données - MULLIGAN TCG'
    }
  },
  {
    path: '/about',
    name: 'about',
    component: () => import('../views/StaticPages/AboutView.vue'),
    meta: {
      title: 'À propos - MULLIGAN TCG'
    }
  },
  {
    path: '/aide',
    name: 'aide',
    component: () => import('../views/StaticPages/HelpView.vue'),
    meta: {
      title: 'Aide & Support - MULLIGAN TCG'
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
  
  // Routes protégées avec leurs configs pour la redirection vers auth-required
  const protectedRoutes = {
    'ForumsView': 'forums',
    'ForumPostsView': 'forums', 
    'PostView': 'forums',
    'MyDecks': 'myDecks',
    'mes-evenements': 'myEvents',
    'creer-evenement': 'events',
    'creer-tournoi': 'events',
    'profile': 'profile',
    'decks-edit': 'myDecks',
    'community-decks': 'decks',
    'metagame-decks': 'decks'
  }
  
  // Ajouter info de provenance pour les breadcrumbs
  if (to.name === 'evenement-detail' && from.name) {
    to.meta.from = from.name
  }
  
  // ========== CORRECTION: Gestion de la réhydratation ==========
  // Si un token existe mais pas d'utilisateur, attendre la vérification
  if (authStore.token && !authStore.user) {
    try {
      await authStore.checkAuthStatus()
    } catch (error) {
      console.error('Erreur vérification auth:', error)
      // Le token est invalide, logout géré par checkAuthStatus
    }
  }
  
  // Vérification d'authentification pour routes protégées (redirection vers auth-required)
  const configKey = protectedRoutes[to.name]
  if (configKey && !authStore.isAuthenticated) {
    // Rediriger vers page d'auth avec config appropriée
    next({ 
      name: 'auth-required', 
      query: { 
        config: configKey, 
        redirect: to.fullPath 
      } 
    })
    return
  }
  
  // Logique d'authentification existante pour les routes avec requiresAuth
  if (to.meta.requiresAuth) {
    if (!authStore.token) {
      next({ name: 'home' })
      return
    }
    
    if (!authStore.isAuthenticated) {
      next({ name: 'home' })
      return
    }
    
    // Vérification des rôles
    if (to.meta.requiresRole) {
      const userRoles = authStore.user?.roles || []
      const requiredRoles = to.meta.requiresRole
      const hasRequiredRole = requiredRoles.some(role => userRoles.includes(role))
      
      if (!hasRequiredRole) {
        console.warn('⚠️ Accès refusé - Rôle insuffisant:', {
          userRoles,
          requiredRoles,
          route: to.name
        })
        next({ name: 'home' })
        return
      }
    }
  }
  
  next()
})

export default router
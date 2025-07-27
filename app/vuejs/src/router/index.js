import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '@/views/HomeView.vue'
import ComingSoonView from '@/views/ComingSoonView.vue'

const routes = [
  // Page d'accueil
  {
    path: '/',
    name: 'Home',
    component: HomeView,
    meta: {
      title: 'Accueil - MULLIGAN TCG',
      description: 'Votre hub TCG ultime pour Hearthstone et plus'
    }
  },

  // Toutes les autres sections pointent vers ComingSoon pour l'instant
  {
    path: '/discussions',
    name: 'Discussions',
    component: ComingSoonView,
    meta: {
      title: 'Discussions - MULLIGAN TCG',
      description: 'Forum communautaire et discussions TCG'
    }
  },
  
  {
    path: '/decks',
    name: 'Decks',
    component: ComingSoonView,
    meta: {
      title: 'Decks - MULLIGAN TCG',
      description: 'Collection de decks et deck builder'
    }
  },
  
  {
    path: '/rankings',
    name: 'Rankings',
    component: ComingSoonView,
    meta: {
      title: 'Classements - MULLIGAN TCG',
      description: 'Classements joueurs et tournois'
    }
  },
  
  {
    path: '/shops',
    name: 'Shops',
    component: ComingSoonView,
    meta: {
      title: 'Boutiques - MULLIGAN TCG',
      description: 'Marketplace et boutiques partenaires'
    }
  },
  
  // Pages utilisateur
  {
    path: '/profile',
    name: 'Profile',
    component: ComingSoonView,
    meta: {
      title: 'Profil - MULLIGAN TCG',
      requiresAuth: true
    }
  },
  
  {
    path: '/favorites',
    name: 'Favorites',
    component: ComingSoonView,
    meta: {
      title: 'Favoris - MULLIGAN TCG',
      requiresAuth: true
    }
  },
  
  {
    path: '/settings',
    name: 'Settings',
    component: ComingSoonView,
    meta: {
      title: 'Paramètres - MULLIGAN TCG',
      requiresAuth: true
    }
  },

  {
    path: '/my-decks',
    name: 'MyDecks', 
    component: ComingSoonView,
    meta: {
      title: 'Mes Decks - MULLIGAN TCG',
      requiresAuth: true
    }
  },
  
  {
    path: '/my-tournaments',
    name: 'MyTournaments',
    component: ComingSoonView, 
    meta: {
      title: 'Mes Tournois - MULLIGAN TCG',
      requiresAuth: true
    }
  },
  
  {
    path: '/admin',
    name: 'Admin',
    component: ComingSoonView,
    meta: {
      title: 'Administration - MULLIGAN TCG',
      requiresAuth: true,
      requiresAdmin: true
    }
  },
  
  {
    path: '/messages',
    name: 'Messages',
    component: ComingSoonView,
    meta: {
      title: 'Messages - MULLIGAN TCG',
      requiresAuth: true
    }
  },
  
  // Redirection pour toutes les routes non trouvées
  {
    path: '/:pathMatch(.*)*',
    redirect: '/'
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) {
      return savedPosition
    } else if (to.hash) {
      return { el: to.hash, behavior: 'smooth' }
    } else {
      return { top: 0, behavior: 'smooth' }
    }
  }
})

// Guards de navigation
router.beforeEach(async (to, from, next) => {
  // Mise à jour du titre de la page
  if (to.meta.title) {
    document.title = to.meta.title
  }
  
  // Mise à jour des meta descriptions
  if (to.meta.description) {
    const metaDescription = document.querySelector('meta[name="description"]')
    if (metaDescription) {
      metaDescription.setAttribute('content', to.meta.description)
    }
  }
  
  // Vérification d'authentification
  if (to.meta?.requiresAuth) {
    const { useAuthStore } = await import('@/stores/auth')
    const authStore = useAuthStore()
    
    if (!authStore.isAuthenticated) {
      // Pas connecté, rediriger vers home
      next('/')
      return
    }
    
    if (to.meta?.requiresAdmin && !authStore.isAdmin) {
      // Pas admin, rediriger vers home
      next('/')
      return
    }
  }
  
  next()
})

router.afterEach((to, from) => {
  console.log(`Navigation: ${from.path} → ${to.path}`)
})

export default router
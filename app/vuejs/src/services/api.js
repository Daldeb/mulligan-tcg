// services/api.js - Configuration Axios pour les appels API
import axios from 'axios'

// Configuration de base
const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL,  // URL de votre backend Symfony
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
})

// Intercepteur pour les requêtes
api.interceptors.request.use(
  (config) => {
    // Ajouter le token d'authentification si disponible
    const token = localStorage.getItem('auth_token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    
    console.log(`🚀 API Request: ${config.method?.toUpperCase()} ${config.url}`)
    return config
  },
  (error) => {
    console.error('❌ Request Error:', error)
    return Promise.reject(error)
  }
)

// Intercepteur pour les réponses
api.interceptors.response.use(
  (response) => {
    console.log(`✅ API Response: ${response.status} ${response.config.url}`)
    return response
  },
  (error) => {
    console.error(`❌ API Error: ${error.response?.status} ${error.config?.url}`, error.response?.data)
    
    // Gestion globale des erreurs
    if (error.response?.status === 401) {
      // Token expiré ou invalide - rediriger vers login
      localStorage.removeItem('auth_token')
      delete api.defaults.headers.common['Authorization']
      
      // Optionnel : redirection automatique ou événement global
      window.dispatchEvent(new CustomEvent('auth:logout'))
    }
    
    return Promise.reject(error)
  }
)

// 🆕 MÉTHODES NOTIFICATIONS
// Ajout des méthodes spécifiques aux notifications

/**
 * API Notifications - Méthodes pour gérer les notifications utilisateur
 */
api.notifications = {
  // Header notifications (4 non lues max)
  getHeader: () => api.get('/api/notifications/header'),
  
  // Compteur notifications non lues
  getUnreadCount: () => api.get('/api/notifications/unread-count'),
  
  // Notifications récentes pour ProfileView (paginées)
  getRecent: (page = 1, limit = 6) => 
    api.get(`/api/notifications/recent?page=${page}&limit=${limit}`),
  
  // Toutes les notifications (pagination complète)
  getAll: (page = 1, limit = 10) => 
    api.get(`/api/notifications?page=${page}&limit=${limit}`),
  
  // Actions sur les notifications
  markAsRead: (id) => api.post(`/api/notifications/${id}/read`),
  markAllAsRead: () => api.post('/api/notifications/mark-all-read'),
  delete: (id) => api.delete(`/api/notifications/${id}`),
  
  // Polling pour temps réel
  poll: (since = null) => {
    const params = since ? `?since=${since}` : ''
    return api.get(`/api/notifications/poll${params}`)
  },
  
  // Méthodes avancées
  getStats: () => api.get('/api/notifications/stats'),
  getByType: (type, limit = 10) => 
    api.get(`/api/notifications/type/${type}?limit=${limit}`)
}

/**
 * API Shops - Méthodes pour gérer les boutiques
 */
api.shops = {
  // Liste toutes les boutiques avec filtres optionnels
  getAll: (filters = {}) => {
    const params = new URLSearchParams(
      Object.entries(filters).filter(([_, value]) => value !== null && value !== '')
    ).toString()
    return api.get(`/api/shops${params ? '?' + params : ''}`)
  },
  
  // Données optimisées pour la carte
  getMapData: () => api.get('/api/shops/map'),
  
  // Détails d'une boutique par ID
  getById: (id) => api.get(`/api/shops/${id}`),
  
  // Détails d'une boutique par slug
  getBySlug: (slug) => api.get(`/api/shops/slug/${slug}`),
  
  // Boutiques à proximité
  getNearby: (lat, lng, radius = 50) => 
    api.get(`/api/shops/nearby?lat=${lat}&lng=${lng}&radius=${radius}`),
  
  // Boutiques populaires (pour HomeView)
  getPopular: (limit = 5) => 
    api.get(`/api/shops/popular?limit=${limit}`),
  
  // Recherche de boutiques
  search: (query) => 
    api.get(`/api/shops/search?q=${encodeURIComponent(query)}`),
  
  // Statistiques des boutiques
  getStats: () => api.get('/api/shops/stats'),
  
  // Liste des départements avec compteurs
  getDepartments: () => api.get('/api/shops/departments'),
  
  // Méthodes avec filtres spécifiques
  getByType: (type) => api.get(`/api/shops?type=${type}`),
  getByDepartment: (department) => api.get(`/api/shops?department=${department}`),
  getByGame: (gameId) => api.get(`/api/shops?game=${gameId}`),
  getByService: (service) => api.get(`/api/shops?service=${service}`)
}

export default api
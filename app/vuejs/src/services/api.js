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

export default api
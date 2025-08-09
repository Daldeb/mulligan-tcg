// services/api.js - Configuration Axios pour les appels API
import axios from 'axios'

// Configuration de base
const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL,  // URL de votre backend Symfony
  timeout: 10000,
  headers: {
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

/**
 * API Events - Méthodes pour gérer les événements et tournois
 */
api.events = {
  // Liste avec filtres et pagination
  getAll: (params = {}) => api.get('/api/events', { params }),
  
  // Détails d'un événement
  getById: (id) => api.get(`/api/events/${id}`),
  
  // Création/modification (ROLE_ORGANIZER/ROLE_SHOP uniquement)
  create: (eventData) => api.post('/api/events', eventData),
  update: (id, eventData) => api.put(`/api/events/${id}`, eventData),
  delete: (id) => api.delete(`/api/events/${id}`),
  
  // Soumission pour validation admin
  submitForReview: (id) => api.post(`/api/events/${id}/submit-for-review`),
  
  // Inscriptions utilisateur
  register: (id, data = {}) => api.post(`/api/events/${id}/register`, data),
  unregister: (id) => api.delete(`/api/events/${id}/register`),
  
  // Mes inscriptions
  getMyRegistrations: (status = null) => {
    const params = status ? { status } : {}
    return api.get('/api/my-registrations', { params })
  },
  getUpcomingRegistrations: () => api.get('/api/my-upcoming-events'),
  
  // Mes événements créés (organisateur)
  getMyEvents: () => api.get('/api/events/my-events'),
  
  // Participants d'un événement (organisateur)
  getParticipants: (id, params = {}) => api.get(`/api/events/${id}/participants`, { params }),
  getPendingCheckIn: (id) => api.get(`/api/events/${id}/pending-check-in`),
  getRegistrationStats: (id) => api.get(`/api/events/${id}/registration-stats`),
  
  // Check-in (organisateur)
  checkInUser: (eventId, userId) => api.post(`/api/events/${eventId}/check-in/${userId}`),
  selfCheckIn: (id) => api.post(`/api/events/${id}/self-check-in`),
  
  // Soumission decklist (tournois)
  submitDecklist: (id, data) => api.post(`/api/events/${id}/submit-decklist`, data),
  
  // Filtres et recherche
  getUpcoming: (limit = 10) => api.get('/api/events/upcoming', { params: { limit } }),
  getPopular: (limit = 10) => api.get('/api/events/popular', { params: { limit } }),
  getByGame: (gameId) => api.get(`/api/events/by-game/${gameId}`),
  search: (query, limit = 20) => api.get('/api/events/search', { params: { q: query, limit } })
}

/**
 * API Tournaments - Méthodes spécifiques aux tournois
 */
api.tournaments = {
  // Liste des tournois avec filtres spécifiques
  getAll: (params = {}) => api.get('/api/tournaments', { params }),
  getById: (id) => api.get(`/api/tournaments/${id}`),
  
  // Gestion tournoi (organisateur)
  start: (id) => api.post(`/api/tournaments/${id}/start`),
  
  // Rounds et pairings
  getRounds: (id) => api.get(`/api/tournaments/${id}/rounds`),
  getRoundDetail: (id, roundId) => api.get(`/api/tournaments/${id}/rounds/${roundId}`),
  generatePairings: (id, roundId) => api.post(`/api/tournaments/${id}/rounds/${roundId}/generate-pairings`),
  startRound: (id, roundId) => api.post(`/api/tournaments/${id}/rounds/${roundId}/start`),
  finishRound: (id, roundId) => api.post(`/api/tournaments/${id}/rounds/${roundId}/finish`),
  
  // Classements
  getStandings: (id) => api.get(`/api/tournaments/${id}/standings`),
  
  // Statistiques
  getStats: (id) => api.get(`/api/tournaments/${id}/stats`),
  
  // Filtres spéciaux
  getByFormat: (formatId) => api.get(`/api/tournaments/by-format/${formatId}`),
  getOpenForRegistration: () => api.get('/api/tournaments/open-registration'),
  getInProgress: () => api.get('/api/tournaments/in-progress')
}

/**
 * API Tournament Management - Interface organisateur
 */
api.tournamentManagement = {
  // Dashboard organisateur
  getDashboard: (id) => api.get(`/api/tournament-management/${id}/dashboard`),
  
  // Gestion des matchs
  startMatch: (matchId) => api.post(`/api/tournament-management/matches/${matchId}/start`),
  submitResult: (matchId, data) => api.post(`/api/tournament-management/matches/${matchId}/result`, data),
  updateResult: (matchId, data) => api.put(`/api/tournament-management/matches/${matchId}/result`, data),
  disqualifyPlayer: (matchId, data) => api.post(`/api/tournament-management/matches/${matchId}/disqualify`, data),
  
  // Contrôle tournoi
  pauseTournament: (id, data) => api.post(`/api/tournament-management/${id}/pause`, data),
  resumeTournament: (id) => api.post(`/api/tournament-management/${id}/resume`),
  
  // Monitoring
  monitor: (id) => api.get(`/api/tournament-management/${id}/monitor`),
  getUrgentActions: (id) => api.get(`/api/tournament-management/${id}/urgent-actions`),
  
  // Actions d'urgence (admin)
  forceFinishRound: (id, roundId, data) => api.post(`/api/tournament-management/${id}/rounds/${roundId}/force-finish`, data)
}

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

/**
 * API Games - Méthodes pour récupérer les jeux et formats
 */
api.games = {
  // Liste des jeux
  getAll: () => api.get('/api/games'),
  getById: (id) => api.get(`/api/games/${id}`),
  
  // Formats de jeu
  getFormats: (gameId = null) => {
    const params = gameId ? { game_id: gameId } : {}
    return api.get('/api/game-formats', { params })
  },
  getFormatById: (id) => api.get(`/api/game-formats/${id}`)
}

/**
 * API Users - Méthodes pour la recherche et profils d'utilisateurs
 */
api.users = {
  // Recherche d'utilisateurs
  search: (query, limit = 5) => api.get('/api/users/search', { params: { q: query, limit } }),
  
  // Suggestions d'utilisateurs actifs
  getSuggestions: () => api.get('/api/users/suggestions'),
  
  // Profil public d'un utilisateur
  getPublicProfile: (userId) => api.get(`/api/users/${userId}/public`)
}

export default api
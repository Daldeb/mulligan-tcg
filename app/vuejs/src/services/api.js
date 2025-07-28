// services/api.js - Configuration Axios pour les appels API
import axios from 'axios'

// Configuration de base
const api = axios.create({
  baseURL: 'http://localhost:8000', // URL de votre backend Symfony
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

export default api
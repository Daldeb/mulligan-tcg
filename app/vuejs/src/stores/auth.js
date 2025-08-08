// stores/auth.js - Store Pinia pour l'authentification
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../services/api'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(localStorage.getItem('auth_token'))
  const isLoading = ref(false)

  const isAuthenticated = computed(() => !!token.value && !!user.value)
  
  // Computed pour les jeux sélectionnés
  const selectedGames = computed(() => user.value?.selectedGames || [])
  
  // Computed pour vérifier si l'utilisateur a des jeux sélectionnés
  const hasSelectedGames = computed(() => selectedGames.value.length > 0)

  const followedEvents = computed(() => user.value?.followedEvents || [])

  const login = async (email, password) => {
    isLoading.value = true

    try {
      const response = await api.post('/api/login', { email, password })

      if (response.data.token) {
        token.value = response.data.token
        localStorage.setItem('auth_token', response.data.token)
        api.defaults.headers.common['Authorization'] = `Bearer ${response.data.token}`

        try {
          const profileResponse = await api.get('/api/profile')
          user.value = {
            ...profileResponse.data,
            followedEvents: profileResponse.data.followedEvents || [] 
          }
        } catch (err) {
          console.warn('⚠️ Impossible de récupérer le profil après login.')
          user.value = null
        }

        return { success: true }
      } else {
        return { success: false, errors: ['Identifiants invalides'] }
      }
    } catch (error) {
      console.error('Erreur de connexion:', error)

      const msg = error.response?.data?.message || error.response?.data?.error

      if (error.response?.status === 401) {
        return { success: false, errors: ['Email ou mot de passe incorrect'] }
      } else if (msg) {
        return { success: false, errors: [msg] }
      } else {
        return { success: false, errors: ['Erreur serveur. Veuillez réessayer.'] }
      }
    } finally {
      isLoading.value = false
    }
  }

  const register = async (userData) => {
    isLoading.value = true

    try {
      const response = await api.post('/api/register', userData)

      if (response.data.token) {
        token.value = response.data.token
        localStorage.setItem('auth_token', response.data.token)
        api.defaults.headers.common['Authorization'] = `Bearer ${response.data.token}`
        try {
          const profileResponse = await api.get('/api/profile')
          user.value = {
            ...profileResponse.data,
            followedEvents: profileResponse.data.followedEvents || [] 
          }
        } catch (err) {
          console.warn('⚠️ Impossible de récupérer le profil après inscription.')
          user.value = null
        }

        return { success: true }
      } else {
        return { success: false, errors: ['Erreur lors de l\'inscription'] }
      }
    } catch (error) {
      console.error('Erreur d\'inscription:', error)

      const msg = error.response?.data?.message || error.response?.data?.error

      if (error.response?.status === 409) {
        return { success: false, errors: ['Email ou nom d\'utilisateur déjà utilisé'] }
      } else if (error.response?.status === 400 && msg) {
        return { success: false, errors: [msg] }
      } else {
        return { success: false, errors: ['Erreur d\'inscription. Veuillez réessayer.'] }
      }
    } finally {
      isLoading.value = false
    }
  }

  const logout = () => {
    user.value = null
    token.value = null
    localStorage.removeItem('auth_token')
    delete api.defaults.headers.common['Authorization']
  }

  const checkAuthStatus = async () => {
    if (!token.value) return

    try {
      api.defaults.headers.common['Authorization'] = `Bearer ${token.value}`
      const response = await api.get('/api/profile')
      user.value = {
        ...response.data,
        followedEvents: response.data.followedEvents || []
      }
    } catch (error) {
      console.error('Token invalide:', error)
      logout()
    }
  }

  const updateProfile = async (profileData) => {
    try {
      const response = await api.put('/api/profile', profileData)
      user.value = { ...user.value, ...response.data }
      return { success: true }
    } catch (error) {
      console.error('Erreur de mise à jour du profil:', error)
      return {
        success: false,
        error: error.response?.data?.message || 'Erreur de mise à jour'
      }
    }
  }

  // Nouvelle méthode pour mettre à jour les jeux sélectionnés
  const updateSelectedGames = async (gameIds) => {
    try {
      const response = await api.put('/api/profile', { 
        selectedGames: gameIds 
      })
      
      if (user.value) {
        user.value.selectedGames = gameIds
      }
      
      return { success: true }
    } catch (error) {
      console.error('Erreur mise à jour jeux:', error)
      return { 
        success: false, 
        error: error.response?.data?.message || 'Erreur de mise à jour des jeux' 
      }
    }
  }

  // Méthodes utilitaires pour les jeux sélectionnés
  const hasGameSelected = (gameId) => {
    return selectedGames.value.includes(gameId)
  }

  const toggleGame = async (gameId) => {
    if (!user.value) return { success: false, error: 'Utilisateur non connecté' }

    const currentGames = [...selectedGames.value]
    const index = currentGames.indexOf(gameId)
    
    if (index > -1) {
      // Retirer le jeu
      currentGames.splice(index, 1)
    } else {
      // Ajouter le jeu
      currentGames.push(gameId)
    }

    return await updateSelectedGames(currentGames)
  }

  return {
    user,
    token,
    isLoading,
    isAuthenticated,
    selectedGames,
    hasSelectedGames,
    followedEvents,
    login,
    register,
    logout,
    checkAuthStatus,
    updateProfile,
    updateSelectedGames,
    hasGameSelected,
    toggleGame
  }
})
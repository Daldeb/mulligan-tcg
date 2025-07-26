// stores/auth.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

const API_BASE = 'http://51.178.27.41:8080/api'

export const useAuthStore = defineStore('auth', () => {
  // State
  const user = ref(null)
  const token = ref(localStorage.getItem('token'))
  const isLoading = ref(false)
  const registrationStep = ref(null) // 'pending-verification', 'verified', null

  // Getters
  const isAuthenticated = computed(() => !!token.value && !!user.value?.isVerified)
  const isRegistered = computed(() => !!user.value)
  const userRoles = computed(() => user.value?.roles || [])
  const isAdmin = computed(() => userRoles.value.includes('ROLE_ADMIN'))
  const needsVerification = computed(() => user.value && !user.value.isVerified)

  // Configuration Axios par défaut
  if (token.value) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`
  }

  // Actions
  const register = async (userData) => {
    isLoading.value = true
    try {
      const response = await axios.post(`${API_BASE}/register`, userData)
      
      // Stocker les infos utilisateur temporaires (non vérifiées)
      user.value = response.data.user
      registrationStep.value = 'pending-verification'
      
      return { 
        success: true, 
        message: response.data.message,
        needsVerification: true
      }
    } catch (error) {
      console.error('Erreur d\'inscription:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Erreur lors de l\'inscription' 
      }
    } finally {
      isLoading.value = false
    }
  }

  const login = async (credentials) => {
    isLoading.value = true
    try {
      const response = await axios.post(`${API_BASE}/login`, credentials)
      
      const { token: newToken } = response.data
      token.value = newToken
      localStorage.setItem('token', newToken)
      axios.defaults.headers.common['Authorization'] = `Bearer ${newToken}`
      
      // Récupérer les infos utilisateur
      await fetchUser()
      
      // Vérifier si l'utilisateur doit vérifier son email
      if (user.value && !user.value.isVerified) {
        return { 
          success: false, 
          message: 'Veuillez vérifier votre email avant de vous connecter.',
          needsVerification: true
        }
      }
      
      return { success: true }
    } catch (error) {
      console.error('Erreur de connexion:', error)
      
      // Si l'erreur indique un compte non vérifié
      if (error.response?.status === 403) {
        return { 
          success: false, 
          message: 'Compte non vérifié. Vérifiez votre email.',
          needsVerification: true
        }
      }
      
      return { 
        success: false, 
        message: error.response?.data?.message || 'Erreur de connexion' 
      }
    } finally {
      isLoading.value = false
    }
  }

  const fetchUser = async () => {
    if (!token.value) return
    
    try {
      const response = await axios.get(`${API_BASE}/me`)
      user.value = response.data
      
      // Effacer l'état de registration si l'utilisateur est vérifié
      if (user.value.isVerified) {
        registrationStep.value = null
      }
    } catch (error) {
      console.error('Erreur récupération utilisateur:', error)
      // Si le token est invalide, déconnecter l'utilisateur
      if (error.response?.status === 401) {
        logout()
      }
    }
  }

  const verifyEmail = async (token) => {
    isLoading.value = true
    try {
      const response = await axios.get(`${API_BASE}/verify-email/${token}`)
      
      // Mettre à jour l'état de vérification
      if (user.value) {
        user.value.isVerified = true
        registrationStep.value = 'verified'
      }
      
      return { 
        success: true, 
        message: response.data.message 
      }
    } catch (error) {
      console.error('Erreur vérification email:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Erreur lors de la vérification' 
      }
    } finally {
      isLoading.value = false
    }
  }

  const resendVerification = async (email) => {
    isLoading.value = true
    try {
      const response = await axios.post(`${API_BASE}/resend-verification`, { email })
      
      return { 
        success: true, 
        message: response.data.message 
      }
    } catch (error) {
      console.error('Erreur renvoi vérification:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Erreur lors du renvoi' 
      }
    } finally {
      isLoading.value = false
    }
  }

  const forgotPassword = async (email) => {
    isLoading.value = true
    try {
      const response = await axios.post(`${API_BASE}/forgot-password`, { email })
      
      return { 
        success: true, 
        message: response.data.message 
      }
    } catch (error) {
      console.error('Erreur mot de passe oublié:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Erreur lors de la demande' 
      }
    } finally {
      isLoading.value = false
    }
  }

  const resetPassword = async (token, newPassword) => {
    isLoading.value = true
    try {
      const response = await axios.post(`${API_BASE}/reset-password/${token}`, { 
        password: newPassword 
      })
      
      return { 
        success: true, 
        message: response.data.message 
      }
    } catch (error) {
      console.error('Erreur réinitialisation:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Erreur lors de la réinitialisation' 
      }
    } finally {
      isLoading.value = false
    }
  }

  const updateProfile = async (profileData) => {
    isLoading.value = true
    try {
      const response = await axios.put(`${API_BASE}/profile`, profileData)
      
      // Mettre à jour les données utilisateur
      user.value = { ...user.value, ...response.data.user }
      
      return { 
        success: true, 
        message: response.data.message 
      }
    } catch (error) {
      console.error('Erreur mise à jour profil:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Erreur lors de la mise à jour' 
      }
    } finally {
      isLoading.value = false
    }
  }

  const logout = () => {
    user.value = null
    token.value = null
    registrationStep.value = null
    localStorage.removeItem('token')
    delete axios.defaults.headers.common['Authorization']
  }

  const clearRegistrationState = () => {
    registrationStep.value = null
    if (user.value && !user.value.isVerified) {
      user.value = null
    }
  }

  // Initialisation - récupérer l'utilisateur si token présent
  const initialize = async () => {
    if (token.value) {
      await fetchUser()
    }
  }

  return {
    // State
    user,
    token,
    isLoading,
    registrationStep,
    
    // Getters
    isAuthenticated,
    isRegistered,
    userRoles,
    isAdmin,
    needsVerification,
    
    // Actions
    register,
    login,
    logout,
    fetchUser,
    verifyEmail,
    resendVerification,
    forgotPassword,
    resetPassword,
    updateProfile,
    clearRegistrationState,
    initialize
  }
})
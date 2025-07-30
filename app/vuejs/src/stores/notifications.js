import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../services/api'

export const useNotificationStore = defineStore('notifications', () => {
  // État
  const headerNotifications = ref([])
  const recentNotifications = ref([])
  const unreadCount = ref(0)
  const isLoading = ref(false)
  const lastPollTime = ref(null)
  const pollInterval = ref(null)

  // Pagination pour ProfileView
  const recentPagination = ref({
    page: 1,
    limit: 6,
    total: 0,
    pages: 0,
    hasMore: false
  })

  // Computed
  const hasUnread = computed(() => unreadCount.value > 0)
  const displayBadge = computed(() => unreadCount.value > 0)
  const badgeText = computed(() => {
    if (unreadCount.value > 99) return '99+'
    return unreadCount.value.toString()
  })

  // Actions

  /**
   * Charge les notifications pour l'header (4 non lues max)
   */
  const loadHeaderNotifications = async () => {
    try {
      const response = await api.get('/api/notifications/header')
      headerNotifications.value = response.data.notifications
      unreadCount.value = response.data.unreadCount
      
      console.log('🔔 Header notifications loaded:', response.data)
      return response.data
    } catch (error) {
      console.error('❌ Erreur chargement notifications header:', error)
      throw error
    }
  }

  /**
   * Charge les notifications récentes pour ProfileView
   */
  const loadRecentNotifications = async (page = 1, reset = false) => {
    if (isLoading.value && !reset) return

    isLoading.value = true
    try {
      const response = await api.get(`/api/notifications/recent?page=${page}&limit=${recentPagination.value.limit}`)
      
      if (reset || page === 1) {
        recentNotifications.value = response.data.notifications
      } else {
        // Ajouter à la liste existante (pagination "voir plus")
        recentNotifications.value.push(...response.data.notifications)
      }
      
      recentPagination.value = response.data.pagination
      
      console.log('📋 Recent notifications loaded:', response.data)
      return response.data
    } catch (error) {
      console.error('❌ Erreur chargement notifications récentes:', error)
      throw error
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Charge plus de notifications récentes (pagination)
   */
  const loadMoreRecent = async () => {
    if (!recentPagination.value.hasMore || isLoading.value) return
    
    const nextPage = recentPagination.value.page + 1
    await loadRecentNotifications(nextPage, false)
  }

  /**
   * Récupère uniquement le compteur non lu
   */
  const loadUnreadCount = async () => {
    try {
      const response = await api.get('/api/notifications/unread-count')
      unreadCount.value = response.data.unreadCount
      return response.data
    } catch (error) {
      console.error('❌ Erreur chargement compteur:', error)
      throw error
    }
  }

  /**
   * Marque une notification comme lue
   */
  const markAsRead = async (notificationId) => {
    try {
      const response = await api.post(`/api/notifications/${notificationId}/read`)
      
      // Mettre à jour le compteur
      unreadCount.value = response.data.newUnreadCount
      
      // Retirer la notification du header (si elle y était)
      headerNotifications.value = headerNotifications.value.filter(n => n.id !== notificationId)
      
      // Marquer comme lue dans les notifications récentes
      const recentIndex = recentNotifications.value.findIndex(n => n.id === notificationId)
      if (recentIndex !== -1) {
        recentNotifications.value[recentIndex].isRead = true
      }
      
      console.log('✅ Notification marquée comme lue:', notificationId)
      return response.data
    } catch (error) {
      console.error('❌ Erreur marquage lecture:', error)
      throw error
    }
  }

  /**
   * Marque toutes les notifications comme lues
   */
  const markAllAsRead = async () => {
    try {
      const response = await api.post('/api/notifications/mark-all-read')
      
      // Réinitialiser les états
      unreadCount.value = 0
      headerNotifications.value = []
      
      // Marquer toutes les notifications récentes comme lues
      recentNotifications.value.forEach(notification => {
        notification.isRead = true
      })
      
      console.log('✅ Toutes les notifications marquées comme lues')
      return response.data
    } catch (error) {
      console.error('❌ Erreur marquage global:', error)
      throw error
    }
  }

  /**
   * Polling pour les mises à jour temps réel
   */
  const pollNotifications = async () => {
    try {
      const since = lastPollTime.value ? Math.floor(lastPollTime.value.getTime() / 1000) : null
      const params = since ? `?since=${since}` : ''
      
      const response = await api.get(`/api/notifications/poll${params}`)
      
      // Mettre à jour uniquement si il y a des changements
      if (response.data.hasChanges) {
        headerNotifications.value = response.data.headerNotifications
        unreadCount.value = response.data.unreadCount
      }
      
      lastPollTime.value = new Date()
      
      // Log discret (pas à chaque fois)
      if (response.data.unreadCount !== unreadCount.value) {
        console.log('🔄 Polling: nouvelles notifications détectées')
      }
      
      return response.data
    } catch (error) {
      console.error('❌ Erreur polling:', error)
      // Ne pas relancer d'erreur pour le polling
    }
  }

  /**
   * Démarre le polling automatique
   */
  const startPolling = (intervalMs = 30000) => {
    if (pollInterval.value) {
      clearInterval(pollInterval.value)
    }
    
    // Polling initial
    pollNotifications()
    
    // Polling récurrent
    pollInterval.value = setInterval(pollNotifications, intervalMs)
    
    console.log(`🔄 Polling démarré (${intervalMs}ms)`)
  }

  /**
   * Arrête le polling
   */
  const stopPolling = () => {
    if (pollInterval.value) {
      clearInterval(pollInterval.value)
      pollInterval.value = null
      console.log('⏹️ Polling arrêté')
    }
  }

  /**
   * Initialise le store (à appeler au login)
   */
  const initialize = async () => {
    try {
      await loadHeaderNotifications()
      startPolling()
    } catch (error) {
      console.error('❌ Erreur initialisation notifications:', error)
    }
  }

  /**
   * Nettoie le store (à appeler au logout)
   */
  const cleanup = () => {
    stopPolling()
    headerNotifications.value = []
    recentNotifications.value = []
    unreadCount.value = 0
    lastPollTime.value = null
    recentPagination.value = {
      page: 1,
      limit: 6,
      total: 0,
      pages: 0,
      hasMore: false
    }
  }

  /**
   * Rafraîchit toutes les données
   */
  const refresh = async () => {
    await Promise.all([
      loadHeaderNotifications(),
      loadRecentNotifications(1, true)
    ])
  }

  /**
   * Gestion intelligente du polling selon l'activité
   */
  const updatePollingInterval = () => {
    const isActive = document.visibilityState === 'visible'
    const hasUnreadNotifs = unreadCount.value > 0
    
    let interval = 300000 // 5 minutes par défaut (inactif)
    
    if (isActive && hasUnreadNotifs) {
      interval = 15000 // 15 secondes si actif avec notifications
    } else if (isActive) {
      interval = 60000 // 1 minute si actif sans notifications
    }
    
    if (pollInterval.value) {
      stopPolling()
      startPolling(interval)
    }
  }

  // Écouter les changements de visibilité
  if (typeof document !== 'undefined') {
    document.addEventListener('visibilitychange', updatePollingInterval)
  }

  return {
    // État
    headerNotifications,
    recentNotifications,
    unreadCount,
    isLoading,
    recentPagination,
    
    // Computed
    hasUnread,
    displayBadge,
    badgeText,
    
    // Actions
    loadHeaderNotifications,
    loadRecentNotifications,
    loadMoreRecent,
    loadUnreadCount,
    markAsRead,
    markAllAsRead,
    pollNotifications,
    startPolling,
    stopPolling,
    initialize,
    cleanup,
    refresh,
    updatePollingInterval
  }
})
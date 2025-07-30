import { computed } from 'vue'
import { useNotificationStore } from '../stores/notifications'
import { useToast } from 'primevue/usetoast'
import { useRouter } from 'vue-router'

/**
 * Composable pour la gestion des notifications
 * Centralise la logique et les actions communes
 */
export function useNotifications() {
  const notificationStore = useNotificationStore()
  const toast = useToast()
  const router = useRouter()

  // État réactif depuis le store
  const notifications = computed(() => notificationStore.headerNotifications)
  const recentNotifications = computed(() => notificationStore.recentNotifications)
  const unreadCount = computed(() => notificationStore.unreadCount)
  const hasUnread = computed(() => notificationStore.hasUnread)
  const displayBadge = computed(() => notificationStore.displayBadge)
  const badgeText = computed(() => notificationStore.badgeText)
  const isLoading = computed(() => notificationStore.isLoading)
  const pagination = computed(() => notificationStore.recentPagination)

  /**
   * Charge les notifications pour l'header
   */
  const loadHeaderNotifications = async () => {
    try {
      return await notificationStore.loadHeaderNotifications()
    } catch (error) {
      toast.add({
        severity: 'error',
        summary: 'Erreur',
        detail: 'Impossible de charger les notifications',
        life: 3000
      })
      throw error
    }
  }

  /**
   * Charge les notifications récentes pour ProfileView
   */
  const loadRecentNotifications = async (page = 1, reset = false) => {
    try {
      return await notificationStore.loadRecentNotifications(page, reset)
    } catch (error) {
      toast.add({
        severity: 'error',
        summary: 'Erreur',
        detail: 'Impossible de charger l\'activité récente',
        life: 3000
      })
      throw error
    }
  }

  /**
   * Charge plus de notifications (pagination)
   */
  const loadMore = async () => {
    if (!pagination.value.hasMore || isLoading.value) return
    
    try {
      await notificationStore.loadMoreRecent()
    } catch (error) {
      toast.add({
        severity: 'error',
        summary: 'Erreur',
        detail: 'Impossible de charger plus de notifications',
        life: 3000
      })
    }
  }

  /**
   * Marque une notification comme lue et navigue si nécessaire
   */
  const handleNotificationClick = async (notification) => {
    try {
      // Marquer comme lue
      if (!notification.isRead) {
        await notificationStore.markAsRead(notification.id)
      }

      // Naviguer vers l'action URL si elle existe
      if (notification.actionUrl) {
        router.push(notification.actionUrl)
      }

      // Toast de feedback optionnel
      if (notification.actionLabel) {
        toast.add({
          severity: 'info',
          summary: notification.title,
          detail: notification.actionLabel,
          life: 2000
        })
      }
    } catch (error) {
      toast.add({
        severity: 'error',
        summary: 'Erreur',
        detail: 'Impossible de traiter la notification',
        life: 3000
      })
    }
  }

  /**
   * Marque toutes les notifications comme lues
   */
  const markAllAsRead = async () => {
    try {
      await notificationStore.markAllAsRead()
      toast.add({
        severity: 'success',
        summary: 'Notifications',
        detail: 'Toutes les notifications ont été marquées comme lues',
        life: 3000
      })
    } catch (error) {
      toast.add({
        severity: 'error',
        summary: 'Erreur',
        detail: 'Impossible de marquer les notifications comme lues',
        life: 3000
      })
    }
  }

  /**
   * Initialise les notifications (à appeler au login/refresh)
   */
  const initialize = async () => {
    try {
      await notificationStore.initialize()
    } catch (error) {
      console.error('Erreur initialisation notifications:', error)
      // Pas de toast ici car c'est en arrière-plan
    }
  }

  /**
   * Nettoie les notifications (à appeler au logout)
   */
  const cleanup = () => {
    notificationStore.cleanup()
  }

  /**
   * Rafraîchit toutes les données
   */
  const refresh = async () => {
    try {
      await notificationStore.refresh()
    } catch (error) {
      toast.add({
        severity: 'error',
        summary: 'Erreur',
        detail: 'Impossible de rafraîchir les notifications',
        life: 3000
      })
    }
  }

  /**
   * Formate le temps d'affichage d'une notification
   */
  const formatTimeAgo = (timeAgo) => {
    return timeAgo || 'à l\'instant'
  }

  /**
   * Retourne l'icône appropriée pour un type de notification
   */
  const getNotificationIcon = (type) => {
    const icons = {
      'role_approved': '🎉',
      'role_rejected': '❌',
      'event_created': '📅',
      'reply_received': '💬',
      'message_received': '📧'
    }
    return icons[type] || '🔔'
  }

  /**
   * Retourne la classe CSS appropriée pour un type de notification
   */
  const getNotificationClass = (notification) => {
    const classes = ['notification-item']
    
    if (!notification.isRead) {
      classes.push('notification-unread')
    }
    
    classes.push(`notification-${notification.type}`)
    
    return classes.join(' ')
  }

  /**
   * Utilitaire pour tronquer le texte long
   */
  const truncateMessage = (message, maxLength = 80) => {
    if (!message || message.length <= maxLength) return message
    return message.substring(0, maxLength) + '...'
  }

  return {
    // État
    notifications,
    recentNotifications,
    unreadCount,
    hasUnread,
    displayBadge,
    badgeText,
    isLoading,
    pagination,

    // Actions
    loadHeaderNotifications,
    loadRecentNotifications,
    loadMore,
    handleNotificationClick,
    markAllAsRead,
    initialize,
    cleanup,
    refresh,

    // Utilitaires
    formatTimeAgo,
    getNotificationIcon,
    getNotificationClass,
    truncateMessage
  }
}
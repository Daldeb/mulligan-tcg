// stores/events.js - Store Pinia pour la gestion des événements
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'
import { useAuthStore } from './auth'
import { useGameFilterStore } from './gameFilter'

export const useEventStore = defineStore('events', () => {
  // ============= ÉTAT =============
  
  // Listes d'événements
  const events = ref([])
  const myEvents = ref([])
  const myRegistrations = ref([])
  const upcomingEvents = ref([])
  const popularEvents = ref([])
  
  // Événement actuel (détail)
  const currentEvent = ref(null)
  
  // États de chargement
  const isLoading = ref(false)
  const isLoadingDetail = ref(false)
  const isCreating = ref(false)
  const isUpdating = ref(false)
  
  // Pagination
  const pagination = ref({
    page: 1,
    limit: 10,
    total: 0,
    pages: 0
  })
  
  // Filtres
  const filters = ref({
    event_type: null,
    game_id: null,
    status: null,
    is_online: null,
    start_date: null,
    end_date: null,
    order_by: 'start_date',
    order_direction: 'ASC'
  })
  
  // Cache pour éviter les appels redondants
  const cache = ref({
    lastFetch: null,
    ttl: 5 * 60 * 1000 // 5 minutes
  })

  // ============= COMPUTED =============
  
  const authStore = useAuthStore()
  const gameFilterStore = useGameFilterStore()
  
  // PERMISSIONS CORRIGÉES - Seuls organisateurs et boutiques
  const canCreateEvent = computed(() => {
    if (!authStore.isAuthenticated || !authStore.user) return false
    
    const userRoles = authStore.user.roles || []
    
    // Seuls organisateurs, boutiques et admins peuvent créer des événements
    return userRoles.includes('ROLE_ORGANIZER') ||
           userRoles.includes('ROLE_SHOP') ||
           userRoles.includes('ROLE_ADMIN')
  })
  
  const canManageEvents = computed(() => {
    return canCreateEvent.value
  })
  
  // Événements filtrés par jeux sélectionnés
  const filteredEvents = computed(() => {
    if (!gameFilterStore.selectedGames.length) return events.value
    
    return events.value.filter(event => {
      if (!event.games || !event.games.length) return true
      return event.games.some(game => gameFilterStore.selectedGames.includes(game.id))
    })
  })
  
  // Statistiques
  const eventsStats = computed(() => ({
    total: events.value.length,
    upcoming: events.value.filter(e => new Date(e.start_date) > new Date()).length,
    myEventsCount: myEvents.value.length,
    myRegistrationsCount: myRegistrations.value.length
  }))
  
  // Cache validité
  const isCacheValid = computed(() => {
    if (!cache.value.lastFetch) return false
    return Date.now() - cache.value.lastFetch < cache.value.ttl
  })

  // ============= ACTIONS - LISTE =============
  
  /**
   * Charge la liste des événements avec filtres
   */
  const loadEvents = async (params = {}, forceRefresh = false) => {
    if (isLoading.value) return
    
    // Utiliser le cache si valide et pas de force refresh
    if (!forceRefresh && isCacheValid.value && !Object.keys(params).length) {
      return { events: events.value, pagination: pagination.value }
    }
    
    isLoading.value = true
    
    try {
      // Fusionner filtres actuels avec nouveaux params
      const queryParams = {
        ...filters.value,
        ...params,
        page: params.page || pagination.value.page,
        limit: params.limit || pagination.value.limit
      }
      
      // Nettoyer les params null/undefined
      Object.keys(queryParams).forEach(key => {
        if (queryParams[key] === null || queryParams[key] === undefined || queryParams[key] === '') {
          delete queryParams[key]
        }
      })
      
      const response = await api.events.getAll(queryParams)
      
      if (response.data.events) {
        events.value = response.data.events
        pagination.value = response.data.pagination || pagination.value
        
        // Mettre à jour le cache
        cache.value.lastFetch = Date.now()
        
        console.log('📅 Événements chargés:', events.value.length)
        return response.data
      }
      
      throw new Error('Format de réponse invalide')
      
    } catch (error) {
      console.error('❌ Erreur chargement événements:', error)
      
      // En cas d'erreur, garder les données existantes
      const errorMsg = error.response?.data?.error || 'Erreur lors du chargement des événements'
      
      throw new Error(errorMsg)
    } finally {
      isLoading.value = false
    }
  }
  
  /**
   * Charge les événements à venir
   */
  const loadUpcomingEvents = async (limit = 10) => {
    try {
      const response = await api.events.getUpcoming(limit)
      upcomingEvents.value = response.data.events || []
      return response.data
    } catch (error) {
      console.error('❌ Erreur événements à venir:', error)
      throw error
    }
  }
  
  /**
   * Charge les événements populaires
   */
  const loadPopularEvents = async (limit = 10) => {
    try {
      const response = await api.events.getPopular(limit)
      popularEvents.value = response.data.events || []
      return response.data
    } catch (error) {
      console.error('❌ Erreur événements populaires:', error)
      throw error
    }
  }

  // ============= ACTIONS - DÉTAIL =============
  
  /**
   * Charge les détails d'un événement
   */
  const loadEventDetail = async (id) => {
    if (isLoadingDetail.value) return
    
    isLoadingDetail.value = true
    
    try {
      const response = await api.events.getById(id)
      currentEvent.value = response.data.event
      
      console.log('📋 Détail événement chargé:', currentEvent.value.title)
      return currentEvent.value
    } catch (error) {
      console.error('❌ Erreur détail événement:', error)
      currentEvent.value = null
      
      const errorMsg = error.response?.data?.error || 'Événement non trouvé'
      throw new Error(errorMsg)
    } finally {
      isLoadingDetail.value = false
    }
  }
  
  /**
   * Nettoie l'événement actuel
   */
  const clearCurrentEvent = () => {
    currentEvent.value = null
  }

  // ============= ACTIONS - CRUD =============
  
  /**
   * Crée un nouvel événement
   */
  const createEvent = async (eventData) => {
    if (!canCreateEvent.value) {
      throw new Error('Permissions insuffisantes pour créer un événement')
    }
    
    isCreating.value = true
    
    try {
      const response = await api.events.create(eventData)
      
      if (response.data.event) {
        // Ajouter à la liste locale
        events.value.unshift(response.data.event)
        
        // Invalider le cache
        cache.value.lastFetch = null
        
        console.log('✅ Événement créé:', response.data.event.title)
        return response.data.event
      }
      
      throw new Error('Erreur lors de la création')
    } catch (error) {
      console.error('❌ Erreur création événement:', error)
      
      if (error.response?.data?.errors) {
        // Erreurs de validation
        throw new Error(error.response.data.errors.map(e => e.message).join(', '))
      }
      
      const errorMsg = error.response?.data?.error || 'Erreur lors de la création de l\'événement'
      throw new Error(errorMsg)
    } finally {
      isCreating.value = false
    }
  }
  
  /**
   * Met à jour un événement
   */
  const updateEvent = async (id, eventData) => {
    if (!canManageEvents.value) {
      throw new Error('Permissions insuffisantes pour modifier cet événement')
    }
    
    isUpdating.value = true
    
    try {
      const response = await api.events.update(id, eventData)
      
      if (response.data.event) {
        // Mettre à jour dans la liste
        const index = events.value.findIndex(e => e.id === id)
        if (index !== -1) {
          events.value[index] = response.data.event
        }
        
        // Mettre à jour l'événement actuel si c'est le même
        if (currentEvent.value?.id === id) {
          currentEvent.value = response.data.event
        }
        
        // Invalider le cache
        cache.value.lastFetch = null
        
        console.log('✅ Événement mis à jour:', response.data.event.title)
        return response.data.event
      }
      
      throw new Error('Erreur lors de la mise à jour')
    } catch (error) {
      console.error('❌ Erreur mise à jour événement:', error)
      
      if (error.response?.data?.errors) {
        throw new Error(error.response.data.errors.map(e => e.message).join(', '))
      }
      
      const errorMsg = error.response?.data?.error || 'Erreur lors de la mise à jour de l\'événement'
      throw new Error(errorMsg)
    } finally {
      isUpdating.value = false
    }
  }
  
  /**
   * Supprime un événement
   */
  const deleteEvent = async (id) => {
    if (!canManageEvents.value) {
      throw new Error('Permissions insuffisantes pour supprimer cet événement')
    }
    
    try {
      await api.events.delete(id)
      
      // Retirer de la liste locale
      events.value = events.value.filter(e => e.id !== id)
      myEvents.value = myEvents.value.filter(e => e.id !== id)
      
      // Nettoyer l'événement actuel si c'est le même
      if (currentEvent.value?.id === id) {
        currentEvent.value = null
      }
      
      // Invalider le cache
      cache.value.lastFetch = null
      
      console.log('✅ Événement supprimé:', id)
      return { success: true }
    } catch (error) {
      console.error('❌ Erreur suppression événement:', error)
      
      const errorMsg = error.response?.data?.error || 'Erreur lors de la suppression de l\'événement'
      throw new Error(errorMsg)
    }
  }
/**
 * Upload une image pour un événement
 */
const uploadEventImage = async (eventId, file) => {
  if (!canManageEvents.value) {
    throw new Error('Permissions insuffisantes pour modifier cet événement')
  }

  try {
    // Créer FormData avec le fichier
    const formData = new FormData()
    formData.append('image', file)
    
    // LOGS DE DEBUG (laisse-les)
    console.log('🔍 Upload - File:', file.name, 'Size:', file.size)
    for (let [key, value] of formData.entries()) {
      console.log('🔍 FormData entry:', key, value)
    }
    
    // ATTENTION ICI : PAS DE HEADERS
    const response = await api.post(`/api/events/${eventId}/image`, formData)
    console.log('✅ Image uploadée:', response.data)
    return response.data
  } catch (error) {
    console.error('❌ Erreur upload image événement:', error)
    throw new Error(error.response?.data?.error || 'Erreur lors de l\'upload de l\'image')
  }
}

  /**
   * Supprime l'image d'un événement
   */
  const deleteEventImage = async (eventId) => {
    if (!canManageEvents.value) {
      throw new Error('Permissions insuffisantes pour modifier cet événement')
    }
    
    try {
      await api.delete(`/events/${eventId}/image`)
      
      // Mettre à jour dans les listes locales
      const updateImage = (eventsList) => {
        const index = eventsList.findIndex(e => e.id === eventId)
        if (index !== -1) {
          eventsList[index].image = null
        }
      }
      
      updateImage(events.value)
      updateImage(myEvents.value)
      
      // Mettre à jour l'événement actuel si c'est le même
      if (currentEvent.value?.id === eventId) {
        currentEvent.value.image = null
      }
      
      console.log('✅ Image supprimée pour événement:', eventId)
      return { success: true }
    } catch (error) {
      console.error('❌ Erreur suppression image événement:', error)
      
      const errorMsg = error.response?.data?.error || 'Erreur lors de la suppression de l\'image'
      throw new Error(errorMsg)
    }
  }
  
  /**
   * Soumet un événement pour validation admin
   */
  const submitForReview = async (id) => {
    try {
      const response = await api.events.submitForReview(id)
      
      // Mettre à jour le statut dans les listes
      const updateStatus = (eventsList) => {
        const index = eventsList.findIndex(e => e.id === id)
        if (index !== -1) {
          eventsList[index].status = 'PENDING_REVIEW'
        }
      }
      
      updateStatus(events.value)
      updateStatus(myEvents.value)
      
      if (currentEvent.value?.id === id) {
        currentEvent.value.status = 'PENDING_REVIEW'
      }
      
      console.log('✅ Événement soumis pour validation:', id)
      return response.data
    } catch (error) {
      console.error('❌ Erreur soumission validation:', error)
      
      const errorMsg = error.response?.data?.error || 'Erreur lors de la soumission pour validation'
      throw new Error(errorMsg)
    }
  }

  // ============= ACTIONS - MES ÉVÉNEMENTS =============
  
  /**
   * Charge les événements créés par l'utilisateur connecté
   */
  const loadMyEvents = async () => {
    if (!authStore.isAuthenticated) return
    
    try {
      const response = await api.events.getMyEvents()
      
      myEvents.value = [
        ...(response.data.created_events || []),
        ...(response.data.shop_events || [])
      ]
      
      console.log('📋 Mes événements chargés:', myEvents.value.length)
      return response.data
    } catch (error) {
      console.error('❌ Erreur mes événements:', error)
      myEvents.value = []
      throw error
    }
  }
  
  /**
   * Charge mes inscriptions aux événements
   */
  const loadMyRegistrations = async (status = null) => {
    if (!authStore.isAuthenticated) return
    
    try {
      const response = await api.events.getMyRegistrations(status)
      myRegistrations.value = response.data.registrations || []
      
      console.log('📋 Mes inscriptions chargées:', myRegistrations.value.length)
      return response.data
    } catch (error) {
      console.error('❌ Erreur mes inscriptions:', error)
      myRegistrations.value = []
      throw error
    }
  }

  // ============= ACTIONS - INSCRIPTIONS =============
  
  /**
   * S'inscrire à un événement
   */
  const registerToEvent = async (eventId, data = {}) => {
    if (!authStore.isAuthenticated) {
      throw new Error('Vous devez être connecté pour vous inscrire')
    }
    
    try {
      const response = await api.events.register(eventId, data)
      
      // Mettre à jour le compteur de participants dans les listes
      const updateParticipants = (eventsList) => {
        const index = eventsList.findIndex(e => e.id === eventId)
        if (index !== -1) {
          eventsList[index].current_participants++
          eventsList[index].can_register = eventsList[index].current_participants < eventsList[index].max_participants
        }
      }
      
      updateParticipants(events.value)
      updateParticipants(upcomingEvents.value)
      updateParticipants(popularEvents.value)
      
      if (currentEvent.value?.id === eventId) {
        currentEvent.value.current_participants++
        currentEvent.value.can_register = currentEvent.value.current_participants < currentEvent.value.max_participants
      }
      
      console.log('✅ Inscription réussie à l\'événement:', eventId)
      return response.data.registration
    } catch (error) {
      console.error('❌ Erreur inscription événement:', error)
      
      const errorMsg = error.response?.data?.error || 'Erreur lors de l\'inscription à l\'événement'
      throw new Error(errorMsg)
    }
  }
  
  /**
   * Se désinscrire d'un événement
   */
  const unregisterFromEvent = async (eventId) => {
    if (!authStore.isAuthenticated) {
      throw new Error('Vous devez être connecté pour vous désinscrire')
    }
    
    try {
      await api.events.unregister(eventId)
      
      // Mettre à jour le compteur de participants dans les listes
      const updateParticipants = (eventsList) => {
        const index = eventsList.findIndex(e => e.id === eventId)
        if (index !== -1) {
          eventsList[index].current_participants = Math.max(0, eventsList[index].current_participants - 1)
          eventsList[index].can_register = true
        }
      }
      
      updateParticipants(events.value)
      updateParticipants(upcomingEvents.value)
      updateParticipants(popularEvents.value)
      
      if (currentEvent.value?.id === eventId) {
        currentEvent.value.current_participants = Math.max(0, currentEvent.value.current_participants - 1)
        currentEvent.value.can_register = true
      }
      
      // Retirer de mes inscriptions
      myRegistrations.value = myRegistrations.value.filter(r => r.event.id !== eventId)
      
      console.log('✅ Désinscription réussie de l\'événement:', eventId)
      return { success: true }
    } catch (error) {
      console.error('❌ Erreur désinscription événement:', error)
      
      const errorMsg = error.response?.data?.error || 'Erreur lors de la désinscription de l\'événement'
      throw new Error(errorMsg)
    }
  }

  // ============= ACTIONS - FILTRES =============
  
  /**
   * Met à jour les filtres et recharge les événements
   */
  const updateFilters = async (newFilters) => {
    filters.value = { ...filters.value, ...newFilters }
    pagination.value.page = 1 // Reset pagination
    
    await loadEvents({ ...filters.value, page: 1 }, true)
  }
  
  /**
   * Réinitialise tous les filtres
   */
  const resetFilters = async () => {
    filters.value = {
      event_type: null,
      game_id: null,
      status: null,
      is_online: null,
      start_date: null,
      end_date: null,
      order_by: 'start_date',
      order_direction: 'ASC'
    }
    pagination.value.page = 1
    
    await loadEvents(filters.value, true)
  }
  
  /**
   * Recherche d'événements
   */
  const searchEvents = async (query, limit = 20) => {
    if (!query.trim()) {
      return await loadEvents({}, true)
    }
    
    try {
      const response = await api.events.search(query, limit)
      events.value = response.data.events || []
      
      // Pas de pagination pour la recherche
      pagination.value = {
        page: 1,
        limit: response.data.total || events.value.length,
        total: response.data.total || events.value.length,
        pages: 1
      }
      
      console.log('🔍 Recherche événements:', query, '→', events.value.length, 'résultats')
      return response.data
    } catch (error) {
      console.error('❌ Erreur recherche événements:', error)
      throw error
    }
  }
  

  // ============= ACTIONS - UTILITAIRES =============
  
  /**
   * Rafraîchit toutes les données
   */
  const refresh = async () => {
    cache.value.lastFetch = null
    
    const promises = [loadEvents({}, true)]
    
    if (authStore.isAuthenticated && canManageEvents.value) {
      promises.push(loadMyEvents())
      promises.push(loadMyRegistrations())
    }
    
    await Promise.all(promises)
  }
  
  /**
   * Nettoie le store (au logout)
   */
  const cleanup = () => {
    events.value = []
    myEvents.value = []
    myRegistrations.value = []
    upcomingEvents.value = []
    popularEvents.value = []
    currentEvent.value = null
    
    pagination.value = {
      page: 1,
      limit: 10,
      total: 0,
      pages: 0
    }
    
    filters.value = {
      event_type: null,
      game_id: null,
      status: null,
      is_online: null,
      start_date: null,
      end_date: null,
      order_by: 'start_date',
      order_direction: 'ASC'
    }
    
    cache.value = {
      lastFetch: null,
      ttl: 5 * 60 * 1000
    }
    
    isLoading.value = false
    isLoadingDetail.value = false
    isCreating.value = false
    isUpdating.value = false
  }

  /**
 * Charge les événements en attente de validation (admin)
 */
const loadPendingEvents = async (params = {}) => {
  if (!authStore.user?.roles?.includes('ROLE_ADMIN')) {
    throw new Error('Accès admin requis')
  }
  
  try {
    const response = await api.get('/api/admin/events/pending-review', { params })
    return response.data
  } catch (error) {
    console.error('❌ Erreur chargement événements admin:', error)
    throw error
  }
}

/**
 * Approuve un événement (admin)
 */
const approveEvent = async (eventId, comment = null) => {
  if (!authStore.user?.roles?.includes('ROLE_ADMIN')) {
    throw new Error('Accès admin requis')
  }
  
  try {
    const response = await api.post(`/api/admin/events/${eventId}/approve`, {
      comment
    })
    
    // Invalider le cache
    cache.value.lastFetch = null
    
    console.log('✅ Événement approuvé:', eventId)
    return response.data
  } catch (error) {
    console.error('❌ Erreur approbation événement:', error)
    throw error
  }
}

/**
 * Rejette un événement (admin)
 */
const rejectEvent = async (eventId, reason) => {
  if (!authStore.user?.roles?.includes('ROLE_ADMIN')) {
    throw new Error('Accès admin requis')
  }
  
  if (!reason?.trim()) {
    throw new Error('Motif de rejet requis')
  }
  
  try {
    const response = await api.post(`/api/admin/events/${eventId}/reject`, {
      reason: reason.trim()
    })
    
    // Invalider le cache
    cache.value.lastFetch = null
    
    console.log('✅ Événement rejeté:', eventId)
    return response.data
  } catch (error) {
    console.error('❌ Erreur rejet événement:', error)
    throw error
  }
}

/**
 * Supprime définitivement un événement (admin)
 */
const deleteEventAdmin = async (eventId, reason) => {
  if (!authStore.user?.roles?.includes('ROLE_ADMIN')) {
    throw new Error('Accès admin requis')
  }
  
  if (!reason?.trim()) {
    throw new Error('Motif de suppression requis')
  }
  
  try {
    const response = await api.delete(`/api/admin/events/${eventId}`, {
      data: { reason: reason.trim() }
    })
    
    // Retirer de toutes les listes locales
    events.value = events.value.filter(e => e.id !== eventId)
    myEvents.value = myEvents.value.filter(e => e.id !== eventId)
    
    if (currentEvent.value?.id === eventId) {
      currentEvent.value = null
    }
    
    // Invalider le cache
    cache.value.lastFetch = null
    
    console.log('✅ Événement supprimé définitivement:', eventId)
    return response.data
  } catch (error) {
    console.error('❌ Erreur suppression admin événement:', error)
    throw error
  }
}

  // ============= RETURN =============
  
  return {
    // État
    events,
    myEvents,
    myRegistrations,
    upcomingEvents,
    popularEvents,
    currentEvent,
    isLoading,
    isLoadingDetail,
    isCreating,
    isUpdating,
    pagination,
    filters,
    
    // Computed
    canCreateEvent,
    canManageEvents,
    filteredEvents,
    eventsStats,
    isCacheValid,
    
    // Actions - Liste
    loadEvents,
    loadUpcomingEvents,
    loadPopularEvents,
    
    // Actions - Détail
    loadEventDetail,
    clearCurrentEvent,
    
    // Actions - CRUD
    createEvent,
    updateEvent,
    deleteEvent,
    uploadEventImage,
    deleteEventImage,
    submitForReview,
    
    // Actions - Mes événements
    loadMyEvents,
    loadMyRegistrations,
    
    // Actions - Inscriptions
    registerToEvent,
    unregisterFromEvent,
    
    // Actions - Filtres
    updateFilters,
    resetFilters,
    searchEvents,
    
    // Actions - Utilitaires
    refresh,
    loadPendingEvents,
    approveEvent,
    rejectEvent,
    deleteEventAdmin,
    cleanup
  }
})
import { ref, computed, watch } from 'vue'
import api from '../services/api'

// Simple debounce function
const debounce = (func, wait) => {
  let timeout
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout)
      func(...args)
    }
    clearTimeout(timeout)
    timeout = setTimeout(later, wait)
  }
}

export function useUserSearch() {
  const searchQuery = ref('')
  const searchResults = ref([])
  const isSearching = ref(false)
  const showResults = ref(false)
  const error = ref('')

  // Debounced search function
  const debouncedSearch = debounce(async (query) => {
    if (!query || query.length < 2) {
      searchResults.value = []
      showResults.value = false
      isSearching.value = false
      return
    }

    isSearching.value = true
    error.value = ''

    try {
      const response = await api.users.search(query, 5)
      searchResults.value = response.data.users || []
      showResults.value = true
    } catch (err) {
      console.error('Erreur recherche utilisateurs:', err)
      error.value = 'Erreur lors de la recherche'
      searchResults.value = []
    } finally {
      isSearching.value = false
    }
  }, 300)

  // Watcher pour déclencher la recherche
  watch(searchQuery, (newQuery) => {
    debouncedSearch(newQuery.trim())
  })

  // Computed pour savoir si on a des résultats
  const hasResults = computed(() => searchResults.value.length > 0)

  // Computed pour l'état de chargement
  const showSpinner = computed(() => isSearching.value && searchQuery.value.length >= 2)

  // Méthodes
  const clearSearch = () => {
    searchQuery.value = ''
    searchResults.value = []
    showResults.value = false
    error.value = ''
  }

  const hideResults = () => {
    showResults.value = false
  }

  const selectUser = (user) => {
    clearSearch()
    return user
  }

  // Formater les rôles pour l'affichage
  const formatUserRole = (roles) => {
    if (roles.includes('ROLE_ADMIN')) return 'Admin'
    if (roles.includes('ROLE_SHOP')) return 'Boutique'
    if (roles.includes('ROLE_ORGANIZER')) return 'Organisateur'
    return 'Joueur'
  }

  // Générer l'URL de l'avatar
  const getAvatarUrl = (user) => {
    if (user.avatar) {
      return `${import.meta.env.VITE_BACKEND_URL}/uploads/${user.avatar}`
    }
    return null
  }

  // Obtenir les initiales pour l'avatar fallback
  const getUserInitials = (user) => {
    return user.pseudo?.charAt(0).toUpperCase() || 'U'
  }

  return {
    // Reactive state
    searchQuery,
    searchResults,
    isSearching,
    showResults,
    error,
    
    // Computed
    hasResults,
    showSpinner,
    
    // Methods
    clearSearch,
    hideResults,
    selectUser,
    formatUserRole,
    getAvatarUrl,
    getUserInitials
  }
}
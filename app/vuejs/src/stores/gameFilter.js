import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useAuthStore } from './auth'
import api from '@/services/api'

export const useGameFilterStore = defineStore('gameFilter', () => {
  const availableGames = ref([])
  const selectedGames = ref([])
  const isLoading = ref(false)

  // Chargement des jeux (et injection d'image selon slug)
  async function loadGames() {
    isLoading.value = true
    try {
      const response = await api.get('/api/games')
      
      // CORRECTION: Utiliser response.data.data au lieu de response.data.games
      const gamesData = response.data.success ? response.data.data : []
      
      availableGames.value = gamesData.map(game => {
        const imageMap = {
          magic: new URL('@/assets/images/tcg/Magic.webp', import.meta.url).href,
          pokemon: new URL('@/assets/images/tcg/Pokemon.webp', import.meta.url).href,
          hearthstone: new URL('@/assets/images/tcg/Hearthstone.webp', import.meta.url).href,
        }

        return {
          ...game,
          image: imageMap[game.slug] || null
        }
      })
    } catch (error) {
      console.error('Erreur lors du chargement des jeux:', error)
      availableGames.value = []
    } finally {
      isLoading.value = false
    }
  }

  // Chargement depuis API ou localStorage
  async function loadSelectedGames() {
    const authStore = useAuthStore()

    if (authStore.isAuthenticated) {
      try {
        const res = await api.get('/api/profile')
        selectedGames.value = res.data.selectedGames || []
      } catch (e) {
        console.warn('Erreur lors du chargement des jeux sélectionnés via l\'API', e)
        selectedGames.value = []
      }
    } else {
      const raw = localStorage.getItem('selectedGames')
      try {
        selectedGames.value = raw ? JSON.parse(raw) : []
      } catch {
        selectedGames.value = []
      }
    }
  }

  // Sauvegarde côté API + localStorage
  async function saveSelectedGames() {
    const authStore = useAuthStore()

    if (authStore.isAuthenticated) {
      try {
        await api.put('/api/profile/selected-games', {
          selectedGames: selectedGames.value
        })
      } catch (e) {
        console.warn('Échec de la sauvegarde des préférences côté serveur', e)
      }
    }

    localStorage.setItem('selectedGames', JSON.stringify(selectedGames.value))
  }

  // Actions de sélection
  function toggleGame(id) {
    if (selectedGames.value.includes(id)) {
      selectedGames.value = selectedGames.value.filter(g => g !== id)
    } else {
      selectedGames.value.push(id)
    }
    saveSelectedGames()
  }

  function replaceSelection(ids) {
    selectedGames.value = [...ids]
    saveSelectedGames()
  }

  function clearSelection() {
    selectedGames.value = []
    saveSelectedGames()
  }

  const filteredGameIds = computed(() => {
    return selectedGames.value.length > 0
      ? selectedGames.value
      : availableGames.value.map(g => g.id)
  })

  const isReady = computed(() => {
    return availableGames.value.length > 0
  })

  return {
    availableGames,
    selectedGames,
    filteredGameIds,
    isLoading,
    loadGames,
    loadSelectedGames,
    saveSelectedGames,
    toggleGame,
    replaceSelection,
    clearSelection,
    isReady
  }
})
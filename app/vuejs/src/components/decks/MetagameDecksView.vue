<template>
  <div class="metagame-decks-page">
    <div class="container">
      
      <!-- État de chargement -->
      <div v-if="isLoading" class="loading-state">
        <Card class="gaming-card loading-card">
          <template #content>
            <div class="loading-content">
              <div class="emerald-spinner"></div>
              <p>Chargement du metagame...</p>
              <small class="text-secondary">Récupération des derniers decks performants</small>
            </div>
          </template>
        </Card>
      </div>

      <!-- Sections par jeu -->
      <div class="games-sections" v-if="!isLoading && hasData">
        
        <!-- Section Hearthstone -->
        <div v-if="metagameData.hearthstone?.length" class="game-section hearthstone-section slide-in-up">
          
          <div class="game-header">
            <div class="game-title-area">
              <div class="game-badge hearthstone">
                <i class="game-icon">🃏</i>
                <span class="game-name">Hearthstone Metagame</span>
              </div>
              <div class="game-stats">
                <span class="deck-count">{{ filteredDecksByGame('hearthstone').length }} decks</span>
                <div class="last-updated">
                  <i class="pi pi-clock"></i>
                  <span>Mis à jour: {{ formatLastUpdate(lastUpdated) }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Filtres Hearthstone -->
          <div class="game-filters hearthstone-filters">
            <div class="format-toggle">
              <Button
                :label="'Standard (' + countDecksByFormat('hearthstone', 'standard') + ')'"
                :class="['format-toggle-btn', { 'active': activeFormats.hearthstone === 'standard' }]"
                @click="setActiveFormat('hearthstone', 'standard')"
                outlined
              />
              <Button
                :label="'Wild (' + countDecksByFormat('hearthstone', 'wild') + ')'"
                :class="['format-toggle-btn', { 'active': activeFormats.hearthstone === 'wild' }]"
                @click="setActiveFormat('hearthstone', 'wild')"
                outlined
              />
            </div>
            
            <div class="filters-row">
              <div class="filter-search-wrapper">
                <InputText 
                  v-model="gameFilters.hearthstone.search"
                  placeholder="Rechercher un deck Hearthstone..."
                  class="filter-search-input hearthstone-search"
                />
                <i class="pi pi-search search-icon"></i>
              </div>
              
              <Dropdown
                v-model="gameFilters.hearthstone.sortBy"
                :options="sortOptions"
                option-label="label"
                option-value="value"
                class="filter-dropdown sort-dropdown hearthstone-sort"
              />
              <Button
                icon="pi pi-filter-slash"
                class="clear-filters-btn hearthstone-clear"
                @click="resetGameFilters('hearthstone')"
                v-tooltip="'Réinitialiser les filtres'"
                text
                size="small"
              />
            </div>
          </div>
          
          <!-- Grille Hearthstone -->
          <div class="decks-grid hearthstone-grid">
            <SimpleDeckImage 
              v-for="(deck, index) in filteredDecksByGame('hearthstone')" 
              :key="deck.id"
              :deck="deck"
              :index="index"
              @open-gallery="openGallery('hearthstone', $event)"
            />
          </div>
          
          <!-- Pagination Hearthstone -->
          <div class="pagination-controls" v-if="hasMoreDecks('hearthstone')">
            <Button
              :label="`Afficher 4 decks de plus (${getRemainingCount('hearthstone')} restants)`"
              icon="pi pi-chevron-down"
              class="load-more-btn hearthstone-btn"
              @click="loadMore('hearthstone')"
              outlined
            />
          </div>

          <div class="pagination-controls" v-if="canCollapse('hearthstone')">
            <Button
              label="Réduire à 4 decks"
              icon="pi pi-chevron-up"
              class="collapse-btn"
              @click="collapseTo('hearthstone')"
              text
              size="small"
            />
          </div>
        </div>

        <!-- Section Magic -->
        <div v-if="metagameData.magic?.length" class="game-section magic-section slide-in-up">
          
          <div class="game-header">
            <div class="game-title-area">
              <div class="game-badge magic">
                <i class="game-icon">🎴</i>
                <span class="game-name">Magic: The Gathering Metagame</span>
              </div>
              <div class="game-stats">
                <span class="deck-count">{{ filteredDecksByGame('magic').length }} decks</span>
                <div class="last-updated">
                  <i class="pi pi-clock"></i>
                  <span>Mis à jour: {{ formatLastUpdate(lastUpdated) }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Filtres Magic -->
          <div class="game-filters magic-filters">
            <div class="format-toggle">
              <Button
                :label="'Standard (' + countDecksByFormat('magic', 'standard') + ')'"
                :class="['format-toggle-btn', { 'active': activeFormats.magic === 'standard' }]"
                @click="setActiveFormat('magic', 'standard')"
                outlined
              />
              <Button
                :label="'Commander (' + countDecksByFormat('magic', 'commander') + ')'"
                :class="['format-toggle-btn', { 'active': activeFormats.magic === 'commander' }]"
                @click="setActiveFormat('magic', 'commander')"
                outlined
              />
            </div>
            
            <div class="filters-row">
              <div class="filter-search-wrapper">
                <InputText 
                  v-model="gameFilters.magic.search"
                  placeholder="Rechercher un deck Magic..."
                  class="filter-search-input magic-search"
                />
                <i class="pi pi-search search-icon"></i>
              </div>
              
              <Dropdown
                v-model="gameFilters.magic.sortBy"
                :options="sortOptions"
                option-label="label"
                option-value="value"
                class="filter-dropdown sort-dropdown magic-sort"
              />
              <Button
                icon="pi pi-filter-slash"
                class="clear-filters-btn magic-clear"
                @click="resetGameFilters('magic')"
                v-tooltip="'Réinitialiser les filtres'"
                text
                size="small"
              />
            </div>
          </div>
          
          <!-- Grille Magic -->
          <div class="decks-grid magic-grid">
            <SimpleDeckImage 
              v-for="(deck, index) in filteredDecksByGame('magic')" 
              :key="deck.id"
              :deck="deck"
              :index="index"
              @open-gallery="openGallery('magic', $event)"
            />
          </div>
          
          <!-- Pagination Magic -->
          <div class="pagination-controls" v-if="hasMoreDecks('magic')">
            <Button
              :label="`Afficher 4 decks de plus (${getRemainingCount('magic')} restants)`"
              icon="pi pi-chevron-down"
              class="load-more-btn magic-btn"
              @click="loadMore('magic')"
              outlined
            />
          </div>
          
          <div class="pagination-controls" v-if="canCollapse('magic')">
            <Button
              label="Réduire à 4 decks"
              icon="pi pi-chevron-up"
              class="collapse-btn"
              @click="collapseTo('magic')"
              text
              size="small"
            />
          </div>
        </div>

        <!-- Section Pokemon -->
        <div v-if="metagameData.pokemon?.length" class="game-section pokemon-section slide-in-up">
          
          <div class="game-header">
            <div class="game-title-area">
              <div class="game-badge pokemon">
                <i class="game-icon">⚡</i>
                <span class="game-name">Pokemon TCG Metagame</span>
              </div>
              <div class="game-stats">
                <span class="deck-count">{{ filteredDecksByGame('pokemon').length }} decks</span>
                <div class="last-updated">
                  <i class="pi pi-clock"></i>
                  <span>Mis à jour: {{ formatLastUpdate(lastUpdated) }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Filtres Pokemon -->
          <div class="game-filters pokemon-filters">
            <div class="filter-search-wrapper">
              <InputText 
                v-model="gameFilters.pokemon.search"
                placeholder="Rechercher un deck Pokemon..."
                class="filter-search-input pokemon-search"
              />
              <i class="pi pi-search search-icon"></i>
            </div>
            
            <div class="filters-row">
              <Dropdown
                v-model="gameFilters.pokemon.sortBy"
                :options="sortOptions"
                option-label="label"
                option-value="value"
                class="filter-dropdown sort-dropdown pokemon-sort"
              />
              <Button
                icon="pi pi-filter-slash"
                class="clear-filters-btn pokemon-clear"
                @click="resetGameFilters('pokemon')"
                v-tooltip="'Réinitialiser les filtres'"
                text
                size="small"
              />
            </div>
          </div>
          
          <!-- Grille Pokemon -->
          <div class="decks-grid pokemon-grid">
            <SimpleDeckImage 
              v-for="(deck, index) in filteredDecksByGame('pokemon')" 
              :key="deck.id"
              :deck="deck"
              :index="index"
              @open-gallery="openGallery('pokemon', $event)"
            />
          </div>
          
          <!-- Pagination Pokemon -->
          <div class="pagination-controls" v-if="hasMoreDecks('pokemon')">
            <Button
              :label="`Afficher 6 decks de plus (${getRemainingCount('pokemon')} restants)`"
              icon="pi pi-chevron-down"
              class="load-more-btn pokemon-btn"
              @click="loadMore('pokemon')"
              outlined
            />
          </div>
          
          <div class="pagination-controls" v-if="canCollapse('pokemon')">
            <Button
              label="Réduire à 6 decks"
              icon="pi pi-chevron-up"
              class="collapse-btn"
              @click="collapseTo('pokemon')"
              text
              size="small"
            />
          </div>
        </div>

      </div>

      <!-- État vide -->
      <div v-if="!isLoading && !hasData" class="empty-state">
        <Card class="gaming-card empty-card">
          <template #content>
            <div class="empty-content">
              <i class="pi pi-chart-line empty-icon"></i>
              <h3 class="empty-title">Aucun deck metagame disponible</h3>
              <p class="empty-description">
                Les decks du metagame sont en cours de scraping depuis les sites spécialisés.
                <br>
                <strong>Revenez bientôt pour découvrir les derniers decks performants !</strong>
              </p>
              <div class="empty-actions">
                <Button
                  label="Actualiser"
                  icon="pi pi-refresh"
                  class="emerald-button primary"
                  @click="loadMetagameData"
                />
              </div>
            </div>
          </template>
        </Card>
      </div>

      <!-- État d'erreur -->
      <div v-if="error && !isLoading" class="error-state">
        <Card class="gaming-card error-card">
          <template #content>
            <div class="error-content">
              <i class="pi pi-exclamation-triangle error-icon"></i>
              <h3 class="error-title">Erreur de chargement</h3>
              <p class="error-description">
                {{ error }}
              </p>
              <div class="error-actions">
                <Button
                  label="Réessayer"
                  icon="pi pi-refresh"
                  class="emerald-button primary"
                  @click="loadMetagameData"
                />
              </div>
            </div>
          </template>
        </Card>
      </div>

    </div>
    
    <!-- Modale galerie -->
    <DeckGalleryModal
      v-model:visible="galleryVisible"
      :decks="currentGalleryDecks"
      :initial-index="galleryIndex"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import SimpleDeckImage from './SimpleDeckImage.vue'
import DeckGalleryModal from './DeckGalleryModal.vue'
import api from '../../services/api'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Dropdown from 'primevue/dropdown'
import InputText from 'primevue/inputtext'

// État des données
const metagameData = ref({
  hearthstone: [],
  magic: [],
  pokemon: []
})
const isLoading = ref(true)
const error = ref(null)
const lastUpdated = ref(null)

// État de la galerie
const galleryVisible = ref(false)
const galleryIndex = ref(0)
const currentGalleryDecks = ref([])

// État des formats actifs
const activeFormats = ref({
  hearthstone: 'standard',
  magic: 'standard', 
  pokemon: 'standard'
})

// État de la pagination
const pagination = ref({
  hearthstone: { current: 0, pageSize: 4 }, 
  magic: { current: 0, pageSize: 4 },
  pokemon: { current: 0, pageSize: 6 }
})

// Filtres
const gameFilters = ref({
  hearthstone: {
    search: '',
    format: 'all',
    sortBy: 'winrate'
  },
  magic: {
    search: '',
    format: 'all',
    sortBy: 'recent'
  },
  pokemon: {
    search: '',
    sortBy: 'rank'
  }
})

// Options de filtres
const sortOptions = [
  { label: 'Winrate', value: 'winrate' },
  { label: 'Plus récents', value: 'recent' },
  { label: 'Alphabétique', value: 'alphabetical' },
  { label: 'Nombre de games', value: 'games' },
  { label: 'Rang', value: 'rank' }
]

// Computed
const hasData = computed(() => {
  return metagameData.value.hearthstone?.length > 0 ||
         metagameData.value.magic?.length > 0 ||
         metagameData.value.pokemon?.length > 0
})

const filteredDecksByGame = (game) => {
  const gameDecks = metagameData.value[game] || []
  const filters = gameFilters.value[game]
  const activeFormat = activeFormats.value[game]
  
  if (!filters) return gameDecks
  
  let filtered = [...gameDecks]
  
  // Filtrer les decks sans image
  filtered = filtered.filter(deck => {
    return deck.imagePath && 
           deck.imagePath.trim() !== '' && 
           !deck.imagePath.includes('error') &&
           !deck.imagePath.includes('404')
  })
  
  // Filtrage par format actif
  if (activeFormat) {
    filtered = filtered.filter(deck => deck.format === activeFormat)
  }
  
  // Filtrage par recherche
  if (filters.search?.trim()) {
    const searchQuery = filters.search.toLowerCase().trim()
    filtered = filtered.filter(deck => 
      deck.title?.toLowerCase().includes(searchQuery) ||
      deck.metadata?.player?.toLowerCase().includes(searchQuery) ||
      deck.metadata?.class?.toLowerCase().includes(searchQuery) ||
      deck.metadata?.archetype?.toLowerCase().includes(searchQuery)
    )
  }
  
  // Tri
  const sorted = sortDecks(filtered, filters.sortBy, game)
  
  // Pagination
  const pageInfo = pagination.value[game]
  const endIndex = (pageInfo.current + 1) * pageInfo.pageSize
  
  return sorted.slice(0, endIndex)
}

const sortDecks = (decks, sortBy, game) => {
  switch (sortBy) {
    case 'winrate':
      return decks.sort((a, b) => {
        const wrA = a.metadata?.winrate || 0
        const wrB = b.metadata?.winrate || 0
        return wrB - wrA
      })
    case 'games':
      return decks.sort((a, b) => {
        const gamesA = a.metadata?.games || 0
        const gamesB = b.metadata?.games || 0
        return gamesB - gamesA
      })
    case 'rank':
      return decks.sort((a, b) => {
        const rankA = parseFloat(a.metadata?.rank) || 999
        const rankB = parseFloat(b.metadata?.rank) || 999
        return rankA - rankB
      })
    case 'recent':
      return decks.sort((a, b) => {
        return new Date(b.lastUpdated) - new Date(a.lastUpdated)
      })
    case 'alphabetical':
      return decks.sort((a, b) => a.title.localeCompare(b.title))
    default:
      return decks
  }
}

// Méthodes
const loadMetagameData = async () => {
  try {
    isLoading.value = true
    error.value = null
    
    console.log('[📡 Requête API] /api/decks/metagame...')
    const response = await api.get('/api/decks/metagame')
    console.log('[✅ Réponse API reçue]', response.data)

    if (response.data.success) {
      metagameData.value = response.data.data
      lastUpdated.value = response.data.lastUpdated
      
      const totalDecks = Object.values(response.data.data).reduce((sum, decks) => sum + decks.length, 0)
      console.log(`[📦 Metagame chargé] ${totalDecks} decks au total`)
    } else {
      throw new Error('Réponse API invalide')
    }
  } catch (err) {
    console.error('[❌ Erreur chargement metagame]:', err)
    error.value = 'Impossible de charger les decks metagame. Vérifiez votre connexion.'
    metagameData.value = { hearthstone: [], magic: [], pokemon: [] }
  } finally {
    isLoading.value = false
  }
}

const resetGameFilters = (gameSlug) => {
  if (gameFilters.value[gameSlug]) {
    const defaultSorts = {
      hearthstone: 'winrate',
      magic: 'recent',
      pokemon: 'rank'
    }
    
    gameFilters.value[gameSlug] = {
      search: '',
      format: 'all',
      sortBy: defaultSorts[gameSlug] || 'recent'
    }
  }
}

const openGallery = (game, index) => {
  currentGalleryDecks.value = filteredDecksByGame(game)
  galleryIndex.value = index
  galleryVisible.value = true
}

const setActiveFormat = (game, format) => {
  activeFormats.value[game] = format
}

const countDecksByFormat = (game, format) => {
  const gameDecks = metagameData.value[game] || []
  return gameDecks.filter(deck => deck.format === format).length
}

const loadMore = (game) => {
  pagination.value[game].current++
}

const collapseTo = (game) => {
  pagination.value[game].current = 0
}

const hasMoreDecks = (game) => {
  const allValidDecks = getAllFilteredDecks(game)
  const pageInfo = pagination.value[game]
  const currentTotal = (pageInfo.current + 1) * pageInfo.pageSize
  return allValidDecks.length > currentTotal
}

const canCollapse = (game) => {
  return pagination.value[game].current > 0
}

const getRemainingCount = (game) => {
  const allValidDecks = getAllFilteredDecks(game)
  const pageInfo = pagination.value[game]
  const currentTotal = (pageInfo.current + 1) * pageInfo.pageSize
  return Math.max(0, allValidDecks.length - currentTotal)
}

const getAllFilteredDecks = (game) => {
  const gameDecks = metagameData.value[game] || []
  const filters = gameFilters.value[game]
  const activeFormat = activeFormats.value[game]
  
  if (!filters) return gameDecks.filter(deck => deck.imagePath && deck.imagePath.trim() !== '')
  
  let filtered = [...gameDecks]
  
  // Filtrer les decks sans image
  filtered = filtered.filter(deck => {
    return deck.imagePath && 
           deck.imagePath.trim() !== '' && 
           !deck.imagePath.includes('error') &&
           !deck.imagePath.includes('404')
  })
  
  if (activeFormat) {
    filtered = filtered.filter(deck => deck.format === activeFormat)
  }
  
  if (filters.search?.trim()) {
    const searchQuery = filters.search.toLowerCase().trim()
    filtered = filtered.filter(deck => 
      deck.title?.toLowerCase().includes(searchQuery) ||
      deck.metadata?.player?.toLowerCase().includes(searchQuery) ||
      deck.metadata?.class?.toLowerCase().includes(searchQuery) ||
      deck.metadata?.archetype?.toLowerCase().includes(searchQuery)
    )
  }
  
  return sortDecks(filtered, filters.sortBy, game)
}

const formatLastUpdate = (dateString) => {
  if (!dateString) return 'Inconnue'
  
  const date = new Date(dateString)
  const now = new Date()
  const diffMs = now - date
  const diffMins = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMs / 3600000)
  const diffDays = Math.floor(diffMs / 86400000)
  
  if (diffMins < 60) {
    return `Il y a ${diffMins} min`
  } else if (diffHours < 24) {
    return `Il y a ${diffHours}h`
  } else if (diffDays < 7) {
    return `Il y a ${diffDays} jour${diffDays > 1 ? 's' : ''}`
  } else {
    return date.toLocaleDateString('fr-FR', { 
      day: 'numeric', 
      month: 'short'
    })
  }
}

onMounted(() => {
  loadMetagameData()
})
</script>

<style scoped>
/* === METAGAME DECKS PAGE === */

.metagame-decks-page {
  min-height: calc(100vh - 140px);
  background: var(--surface-gradient);
  padding: 2rem 0;
}

.container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 2rem;
}

/* Games sections */
.games-sections {
  display: flex;
  flex-direction: column;
  gap: 3rem;
}

.game-section {
  background: white;
  border-radius: var(--border-radius-large);
  border: 1px solid var(--surface-200);
  box-shadow: var(--shadow-small);
  overflow: hidden;
  position: relative;
  transition: all var(--transition-medium);
}

.game-section:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-medium);
}

.game-section::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
}

/* Couleurs BEAUCOUP plus foncées */
.hearthstone-section::before {
  background: linear-gradient(90deg, #8b0000, #690000, #4a0000);
}

.magic-section::before {
  background: linear-gradient(90deg, #1f0033, #13001f, #0a0011);
}

.pokemon-section::before {
  background: linear-gradient(90deg, #cc4400, #a53700, #7a2800);
}

/* Game headers */
.game-header {
  padding: 1.5rem 2rem;
  background: var(--surface-50);
  border-bottom: 1px solid var(--surface-200);
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 2rem;
}

.game-title-area {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

.game-badge {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1.25rem;
  border-radius: 25px;
  font-weight: 600;
  font-size: 1.1rem;
}

.game-badge.hearthstone {
  background: linear-gradient(135deg, rgba(139, 0, 0, 0.25), rgba(105, 0, 0, 0.2));
  color: #8b0000;
  border: 2px solid rgba(139, 0, 0, 0.6);
}

.game-badge.magic {
  background: linear-gradient(135deg, rgba(31, 0, 51, 0.25), rgba(19, 0, 31, 0.2));
  color: #1f0033;
  border: 2px solid rgba(31, 0, 51, 0.6);
}

.game-badge.pokemon {
  background: linear-gradient(135deg, rgba(204, 68, 0, 0.25), rgba(165, 55, 0, 0.2));
  color: #cc4400;
  border: 2px solid rgba(204, 68, 0, 0.6);
}

.game-icon {
  font-size: 1.5rem;
}

.game-stats {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.5rem;
  color: var(--text-secondary);
  font-size: 0.9rem;
}

.deck-count {
  font-weight: 600;
  color: var(--text-primary);
  font-size: 1rem;
}

.last-updated {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.85rem;
}

.last-updated i {
  font-size: 0.8rem;
}

/* Filtres */
.game-filters {
  padding: 1.5rem 2rem;
  background: white;
  border-bottom: 1px solid var(--surface-200);
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  animation: fadeInScale 0.3s ease-out;
}

.format-toggle {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}

:deep(.format-toggle-btn) {
  background: white !important;
  color: var(--text-secondary) !important;
  border: 2px solid var(--surface-300) !important;
  font-weight: 600 !important;
  transition: all var(--transition-fast) !important;
  border-radius: 8px !important;
  padding: 0.75rem 1.25rem !important;
}

:deep(.format-toggle-btn:hover) {
  color: var(--text-primary) !important;
  border-color: var(--primary) !important;
  background: rgba(38, 166, 154, 0.05) !important;
}

:deep(.format-toggle-btn.active) {
  background: var(--primary) !important;
  color: white !important;
  border-color: var(--primary) !important;
  box-shadow: 0 2px 8px rgba(38, 166, 154, 0.3) !important;
}

.filter-search-wrapper {
  position: relative;
  max-width: 400px;
  width: 100%;
}

:deep(.filter-search-input) {
  width: 100% !important;
  padding-left: 3rem !important;
  border: 2px solid var(--surface-300) !important;
  border-radius: 25px !important;
  background: white !important;
  transition: all var(--transition-fast) !important;
  font-size: 0.9rem !important;
  height: 44px !important;
}

:deep(.filter-search-input:focus) {
  border-color: var(--primary) !important;
  box-shadow: 0 0 0 3px rgba(38, 166, 154, 0.1) !important;
  outline: none !important;
}

.search-icon {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-secondary);
  pointer-events: none;
  font-size: 1.1rem;
}

.filters-row {
  display: flex;
  gap: 1rem;
  align-items: center;
  flex-wrap: wrap;
}

:deep(.filter-dropdown) {
  min-width: 140px !important;
  border: 2px solid var(--surface-300) !important;
  border-radius: 8px !important;
  font-size: 0.85rem !important;
  background: white !important;
  transition: all var(--transition-fast) !important;
  height: 40px !important;
}

:deep(.filter-dropdown:hover) {
  border-color: var(--primary) !important;
  box-shadow: 0 0 0 2px rgba(38, 166, 154, 0.1) !important;
  transform: translateY(-1px) !important;
}

:deep(.clear-filters-btn) {
  background: none !important;
  border: 2px solid var(--surface-300) !important;
  color: var(--text-secondary) !important;
  width: 40px !important;
  height: 40px !important;
  border-radius: 50% !important;
  flex-shrink: 0 !important;
  transition: all var(--transition-fast) !important;
  opacity: 0.7;
}

:deep(.clear-filters-btn:hover) {
  border-color: var(--accent) !important;
  color: var(--accent) !important;
  background: rgba(255, 87, 34, 0.1) !important;
  transform: scale(1.05) !important;
  opacity: 1;
}

/* Couleurs thématiques par jeu */
.hearthstone-filters {
  border-left: 6px solid #8b0000;
  background: linear-gradient(135deg, rgba(139, 0, 0, 0.12), rgba(105, 0, 0, 0.08));
  border-radius: 0 16px 16px 0;
}

.magic-filters {
  border-left: 6px solid #1f0033;
  background: linear-gradient(135deg, rgba(31, 0, 51, 0.12), rgba(19, 0, 31, 0.08));
  border-radius: 0 16px 16px 0;
}

.pokemon-filters {
  border-left: 6px solid #cc4400;
  background: linear-gradient(135deg, rgba(204, 68, 0, 0.12), rgba(165, 55, 0, 0.08));
  border-radius: 0 16px 16px 0;
}

/* Grilles spécialisées */
.decks-grid {
  padding: 2rem;
  display: grid;
  gap: 1rem;
}

/* Grilles avec fondus TRÈS FONCÉS */
.hearthstone-grid {
  grid-template-columns: repeat(4, 1fr);
  grid-auto-rows: min-content;
  align-items: start;
  gap: 2rem;
  max-width: 100%;
  margin: 0;
  padding: 2rem;
  background: linear-gradient(135deg, rgba(139, 0, 0, 0.25), rgba(105, 0, 0, 0.18));
  border-radius: 16px;
  position: relative;
  overflow: hidden;
}

.hearthstone-grid::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: radial-gradient(circle at top left, rgba(139, 0, 0, 0.35), transparent 60%);
  pointer-events: none;
  z-index: 1;
}

.magic-grid {
  grid-template-columns: repeat(4, 1fr);
  grid-auto-rows: min-content;
  align-items: start;
  gap: 2rem;
  max-width: 100%;
  margin: 0;
  padding: 2rem;
  background: linear-gradient(135deg, rgba(31, 0, 51, 0.25), rgba(19, 0, 31, 0.18));
  border-radius: 16px;
  position: relative;
  overflow: hidden;
}

.magic-grid::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: radial-gradient(circle at top left, rgba(31, 0, 51, 0.35), transparent 60%);
  pointer-events: none;
  z-index: 1;
}

.pokemon-grid {
  grid-template-columns: repeat(3, 1fr);
  grid-auto-rows: min-content;
  align-items: start;
  gap: 2rem;
  max-width: 100%;
  margin: 0;
  padding: 2rem;
  background: linear-gradient(135deg, rgba(204, 68, 0, 0.25), rgba(165, 55, 0, 0.18));
  border-radius: 16px;
  position: relative;
  overflow: hidden;
}

.pokemon-grid::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: radial-gradient(circle at bottom right, rgba(120, 40, 0, 0.4), transparent 70%);
  pointer-events: none;
  z-index: 1;
}

/* S'assurer que les images sont visibles */
.decks-grid :deep(.simple-deck-image) {
  position: relative;
  z-index: 2;
}

/* Pagination controls */
.pagination-controls {
  padding: 1rem 2rem;
  display: flex;
  justify-content: center;
  border-top: 1px solid var(--surface-200);
}

:deep(.load-more-btn) {
  padding: 1rem 2rem !important;
  font-size: 0.9rem !important;
  font-weight: 600 !important;
  border-radius: 8px !important;
  transition: all var(--transition-medium) !important;
}

/* Boutons avec couleurs plus foncées */
:deep(.load-more-btn.hearthstone-btn) {
  color: #8b0000 !important;
  border-color: #8b0000 !important;
}

:deep(.load-more-btn.hearthstone-btn:hover) {
  background: #8b0000 !important;
  color: white !important;
}

:deep(.load-more-btn.magic-btn) {
  color: #1f0033 !important;
  border-color: #1f0033 !important;
}

:deep(.load-more-btn.magic-btn:hover) {
  background: #1f0033 !important;
  color: white !important;
}

:deep(.load-more-btn.pokemon-btn) {
  color: #cc4400 !important;
  border-color: #cc4400 !important;
}

:deep(.load-more-btn.pokemon-btn:hover) {
  background: #cc4400 !important;
  color: white !important;
}

:deep(.collapse-btn) {
  color: var(--text-secondary) !important;
  font-size: 0.85rem !important;
}

:deep(.collapse-btn:hover) {
  color: var(--text-primary) !important;
}

/* États spéciaux */
.loading-state,
.empty-state,
.error-state {
  display: flex;
  justify-content: center;
  margin: 3rem 0;
}

.loading-card,
.empty-card,
.error-card {
  max-width: 600px;
  width: 100%;
}

.loading-content,
.empty-content,
.error-content {
  padding: 3rem 2rem;
  text-align: center;
}

.emerald-spinner {
  width: 40px;
  height: 40px;
  margin: 0 auto 1rem;
  border: 3px solid var(--surface-200);
  border-top: 3px solid var(--primary);
  border-radius: 50%;
  animation: emeraldSpin 1s linear infinite;
}

.empty-icon,
.error-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
  opacity: 0.7;
}

.empty-icon {
  color: var(--primary);
}

.error-icon {
  color: var(--accent);
}

.empty-title,
.error-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0 0 0.5rem 0;
}

.empty-description,
.error-description {
  color: var(--text-secondary);
  margin: 0 0 2rem 0;
  line-height: 1.5;
}

.empty-actions,
.error-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
}

/* Animations */
.slide-in-up {
  animation: slideInUp 0.6s ease-out;
}

@keyframes slideInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes fadeInScale {
  from {
    opacity: 0;
    transform: scale(0.95);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

@keyframes emeraldSpin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 1024px) {
  .container {
    padding: 0 1rem;
  }
  
  .decks-grid {
    padding: 1.5rem;
    gap: 1rem;
  }
  
  .hearthstone-grid,
  .magic-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .pokemon-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .game-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .game-stats {
    align-items: flex-start;
  }
}

@media (max-width: 768px) {
  .metagame-decks-page {
    padding: 1rem 0;
  }
  
  .game-header {
    padding: 1rem 1.5rem;
  }
  
  .game-filters {
    padding: 1rem 1.5rem;
  }
  
  .filters-row {
    flex-direction: column;
    align-items: stretch;
    gap: 0.75rem;
  }
  
  :deep(.filter-dropdown) {
    min-width: auto !important;
    width: 100% !important;
  }
  
  .decks-grid,
  .hearthstone-grid,
  .magic-grid,
  .pokemon-grid {
    grid-template-columns: 1fr;
    padding: 1rem;
  }
}

@media (max-width: 480px) {
  .game-badge {
    font-size: 1rem;
    padding: 0.5rem 1rem;
  }
  
  .game-icon {
    font-size: 1.25rem;
  }
}
</style>
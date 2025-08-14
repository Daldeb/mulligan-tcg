<template>
  <div class="metagame-decks-page">
    <div class="container">
      
      <!-- État de chargement initial -->
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

      <!-- Sections par jeu avec scroll infini -->
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
                <span class="deck-count">{{ getVisibleDecksCount('hearthstone') }}/{{ getTotalDecksCount('hearthstone') }} decks</span>
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
                  @input="onSearchChange('hearthstone')"
                />
                <i class="pi pi-search search-icon"></i>
              </div>
              
              <Dropdown
                v-model="gameFilters.hearthstone.sortBy"
                :options="sortOptions"
                option-label="label"
                option-value="value"
                class="filter-dropdown sort-dropdown hearthstone-sort"
                @change="onSortChange('hearthstone')"
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
          
          <!-- Grille Hearthstone avec scroll infini -->
          <div class="infinite-scroll-container">
            <div class="decks-grid hearthstone-grid" ref="hearthstoneGrid">
              <SimpleDeckImage 
                v-for="(deck, index) in visibleDecks.hearthstone" 
                :key="deck.id"
                :deck="deck"
                :index="index"
                @open-gallery="openGallery('hearthstone', $event)"
                @image-loaded="onImageLoaded('hearthstone')"
              />
            </div>
            
            <!-- Indicateur de chargement pour scroll infini -->
            <div v-if="loadingMore.hearthstone" class="loading-more">
              <div class="loading-more-spinner"></div>
              <span>Chargement des decks suivants...</span>
            </div>
            
            <!-- Sentinelle pour détecter le scroll -->
            <div 
              ref="hearthstoneSentinel" 
              class="scroll-sentinel"
              v-if="hasMoreDecks('hearthstone')"
            ></div>
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
                <span class="deck-count">{{ getVisibleDecksCount('magic') }}/{{ getTotalDecksCount('magic') }} decks</span>
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
                  @input="onSearchChange('magic')"
                />
                <i class="pi pi-search search-icon"></i>
              </div>
              
              <Dropdown
                v-model="gameFilters.magic.sortBy"
                :options="sortOptions"
                option-label="label"
                option-value="value"
                class="filter-dropdown sort-dropdown magic-sort"
                @change="onSortChange('magic')"
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
          
          <!-- Grille Magic avec scroll infini -->
          <div class="infinite-scroll-container">
            <div class="decks-grid magic-grid" ref="magicGrid">
              <SimpleDeckImage 
                v-for="(deck, index) in visibleDecks.magic" 
                :key="deck.id"
                :deck="deck"
                :index="index"
                @open-gallery="openGallery('magic', $event)"
                @image-loaded="onImageLoaded('magic')"
              />
            </div>
            
            <div v-if="loadingMore.magic" class="loading-more">
              <div class="loading-more-spinner"></div>
              <span>Chargement des decks suivants...</span>
            </div>
            
            <div 
              ref="magicSentinel" 
              class="scroll-sentinel"
              v-if="hasMoreDecks('magic')"
            ></div>
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
                <span class="deck-count">{{ getVisibleDecksCount('pokemon') }}/{{ getTotalDecksCount('pokemon') }} decks</span>
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
                @input="onSearchChange('pokemon')"
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
                @change="onSortChange('pokemon')"
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
          
          <!-- Grille Pokemon avec scroll infini -->
          <div class="infinite-scroll-container">
            <div class="decks-grid pokemon-grid" ref="pokemonGrid">
              <SimpleDeckImage 
                v-for="(deck, index) in visibleDecks.pokemon" 
                :key="deck.id"
                :deck="deck"
                :index="index"
                @open-gallery="openGallery('pokemon', $event)"
                @image-loaded="onImageLoaded('pokemon')"
              />
            </div>
            
            <div v-if="loadingMore.pokemon" class="loading-more">
              <div class="loading-more-spinner"></div>
              <span>Chargement des decks suivants...</span>
            </div>
            
            <div 
              ref="pokemonSentinel" 
              class="scroll-sentinel"
              v-if="hasMoreDecks('pokemon')"
            ></div>
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
              <p class="error-description">{{ error }}</p>
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
    
    <!-- Modale galerie (inchangée) -->
    <DeckGalleryModal
      v-model:visible="galleryVisible"
      :decks="currentGalleryDecks"
      :initial-index="galleryIndex"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
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

// Decks filtrés et triés (complets)
const filteredDecks = ref({
  hearthstone: [],
  magic: [],
  pokemon: []
})

// Decks visibles (avec scroll infini)
const visibleDecks = ref({
  hearthstone: [],
  magic: [],
  pokemon: []
})

// Configuration du scroll infini
const INITIAL_LOAD = 12 // Nombre initial de decks
const LOAD_MORE_COUNT = 8 // Nombre de decks à charger à chaque scroll

// État de pagination par jeu
const decksPagination = ref({
  hearthstone: { loaded: 0 },
  magic: { loaded: 0 },
  pokemon: { loaded: 0 }
})

const loadingMore = ref({
  hearthstone: false,
  magic: false,
  pokemon: false
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

// Filtres
const gameFilters = ref({
  hearthstone: {
    search: '',
    sortBy: 'winrate'
  },
  magic: {
    search: '',
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

// Refs pour les sentinelles d'observation
const hearthstoneSentinel = ref(null)
const magicSentinel = ref(null)
const pokemonSentinel = ref(null)
const observers = new Map()

// Computed
const hasData = computed(() => {
  return metagameData.value.hearthstone?.length > 0 ||
         metagameData.value.magic?.length > 0 ||
         metagameData.value.pokemon?.length > 0
})

// Méthodes principales
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
      
      // Appliquer les filtres et tri initiaux
      for (const game of ['hearthstone', 'magic', 'pokemon']) {
        applyFiltersAndSort(game)
        loadInitialDecks(game)
      }
      
      // Configurer les observers après le prochain tick
      await nextTick()
      setupIntersectionObservers()
      
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

const applyFiltersAndSort = (game) => {
  const gameDecks = metagameData.value[game] || []
  const filters = gameFilters.value[game]
  const activeFormat = activeFormats.value[game]
  
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
  if (filters?.search?.trim()) {
    const searchQuery = filters.search.toLowerCase().trim()
    filtered = filtered.filter(deck => 
      deck.title?.toLowerCase().includes(searchQuery) ||
      deck.metadata?.player?.toLowerCase().includes(searchQuery) ||
      deck.metadata?.class?.toLowerCase().includes(searchQuery) ||
      deck.metadata?.archetype?.toLowerCase().includes(searchQuery)
    )
  }
  
  // Tri
  const sorted = sortDecks(filtered, filters?.sortBy, game)
  
  filteredDecks.value[game] = sorted
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

const loadInitialDecks = (game) => {
  const filtered = filteredDecks.value[game]
  const initialCount = Math.min(INITIAL_LOAD, filtered.length)
  
  visibleDecks.value[game] = filtered.slice(0, initialCount)
  decksPagination.value[game].loaded = initialCount
}

const loadMoreDecks = async (game) => {
  if (loadingMore.value[game]) return
  
  loadingMore.value[game] = true
  
  // Simulation d'un délai pour l'UX
  await new Promise(resolve => setTimeout(resolve, 300))
  
  const filtered = filteredDecks.value[game]
  const currentLoaded = decksPagination.value[game].loaded
  const nextLoad = Math.min(currentLoaded + LOAD_MORE_COUNT, filtered.length)
  
  if (nextLoad > currentLoaded) {
    const newDecks = filtered.slice(currentLoaded, nextLoad)
    visibleDecks.value[game] = [...visibleDecks.value[game], ...newDecks]
    decksPagination.value[game].loaded = nextLoad
  }
  
  loadingMore.value[game] = false
}

// Configuration des Intersection Observers
const setupIntersectionObservers = () => {
  const sentinels = [
    { ref: hearthstoneSentinel, game: 'hearthstone' },
    { ref: magicSentinel, game: 'magic' },
    { ref: pokemonSentinel, game: 'pokemon' }
  ]
  
  sentinels.forEach(({ ref, game }) => {
    if (ref.value) {
      const observer = new IntersectionObserver(
        (entries) => {
          entries.forEach(entry => {
            if (entry.isIntersecting && hasMoreDecks(game)) {
              loadMoreDecks(game)
            }
          })
        },
        {
          rootMargin: '100px' // Charger 100px avant d'atteindre la sentinelle
        }
      )
      
      observer.observe(ref.value)
      observers.set(game, observer)
    }
  })
}

// Gestionnaires d'événements
const onSearchChange = (game) => {
  applyFiltersAndSort(game)
  loadInitialDecks(game)
}

const onSortChange = (game) => {
  applyFiltersAndSort(game)
  loadInitialDecks(game)
}

const setActiveFormat = (game, format) => {
  activeFormats.value[game] = format
  applyFiltersAndSort(game)
  loadInitialDecks(game)
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
      sortBy: defaultSorts[gameSlug] || 'recent'
    }
    
    applyFiltersAndSort(gameSlug)
    loadInitialDecks(gameSlug)
  }
}

const openGallery = (game, index) => {
  currentGalleryDecks.value = visibleDecks.value[game]
  galleryIndex.value = index
  galleryVisible.value = true
}

const onImageLoaded = (game) => {
  // Callback pour optimisations futures (masonry layout, etc.)
}

// Utilitaires
const countDecksByFormat = (game, format) => {
  const gameDecks = metagameData.value[game] || []
  return gameDecks.filter(deck => deck.format === format).length
}

const hasMoreDecks = (game) => {
  const filtered = filteredDecks.value[game]
  const loaded = decksPagination.value[game].loaded
  return loaded < filtered.length
}

const getTotalDecksCount = (game) => {
  return filteredDecks.value[game]?.length || 0
}

const getVisibleDecksCount = (game) => {
  return visibleDecks.value[game]?.length || 0
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

// Lifecycle
onMounted(() => {
  loadMetagameData()
})

onUnmounted(() => {
  // Nettoyer les observers
  observers.forEach(observer => {
    observer.disconnect()
  })
  observers.clear()
})
</script>

<style scoped>
/* === SCROLL INFINI === */

.infinite-scroll-container {
  position: relative;
}

.loading-more {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  padding: 2rem;
  color: var(--text-secondary);
  font-size: 0.9rem;
}

.loading-more-spinner {
  width: 20px;
  height: 20px;
  border: 2px solid var(--surface-300);
  border-top: 2px solid var(--primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

.scroll-sentinel {
  height: 20px;
  margin: 1rem 0;
  background: transparent;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* === GRILLES OPTIMISÉES === */

.decks-grid {
  padding: 2rem;
  display: grid;
  gap: 1.5rem;
  transition: all var(--transition-medium);
}

.hearthstone-grid {
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  background: linear-gradient(135deg, rgba(139, 0, 0, 0.15), rgba(105, 0, 0, 0.1));
  border-radius: 16px;
}

.magic-grid {
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  background: linear-gradient(135deg, rgba(31, 0, 51, 0.15), rgba(19, 0, 31, 0.1));
  border-radius: 16px;
}

.pokemon-grid {
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  background: linear-gradient(135deg, rgba(204, 68, 0, 0.15), rgba(165, 55, 0, 0.1));
  border-radius: 16px;
}

/* === STYLES EXISTANTS OPTIMISÉS === */

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

/* === FILTRES === */

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

/* === ÉTATS SPÉCIAUX === */

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

/* === ANIMATIONS === */

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

/* === RESPONSIVE === */

@media (max-width: 1024px) {
  .container {
    padding: 0 1rem;
  }
  
  .decks-grid {
    padding: 1.5rem;
    gap: 1rem;
  }
  
  .hearthstone-grid {
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  }
  
  .magic-grid {
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  }
  
  .pokemon-grid {
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
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
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    padding: 1rem;
    gap: 0.75rem;
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
  
  .decks-grid,
  .hearthstone-grid,
  .magic-grid,
  .pokemon-grid {
    grid-template-columns: 1fr;
  }
}

/* === PERFORMANCE === */

.deck-image {
  will-change: transform;
}

.simple-deck-image {
  contain: layout style paint;
}

/* === SMOOTH SCROLLING === */

@media (prefers-reduced-motion: no-preference) {
  html {
    scroll-behavior: smooth;
  }
}

/* === FOCUS MANAGEMENT === */

.format-toggle-btn:focus,
.filter-search-input:focus,
.filter-dropdown:focus,
.clear-filters-btn:focus {
  outline: 2px solid var(--primary);
  outline-offset: 2px;
}
</style>
<template>
  <div class="community-decks-page">
    <div class="container">
      
      <!-- État de chargement -->
      <div v-if="isLoading" class="loading-state">
        <Card class="gaming-card loading-card">
          <template #content>
            <div class="loading-content">
              <div class="emerald-spinner"></div>
              <p>Chargement des decks communautaires...</p>
            </div>
          </template>
        </Card>
      </div>

      <!-- Sections par jeu avec filtres intelligents -->
      <div class="games-sections" v-if="!isLoading && allDecks.length > 0">
        
        <!-- Section Hearthstone -->
        <div v-if="filteredDecksByGame('hearthstone').length > 0 || allDecks.some(d => d?.game?.slug === 'hearthstone')" 
             class="game-section hearthstone-section slide-in-up">
          
          <div class="game-header">
            <div class="game-title-area">
              <div class="game-badge hearthstone">
                <i class="game-icon">🃏</i>
                <span class="game-name">Hearthstone</span>
              </div>
              <div class="game-stats">
                <span class="deck-count">{{ filteredDecksByGame('hearthstone').length }} decks</span>
                <span v-if="gameFilters.hearthstone.sortBy === 'smart'" class="sort-indicator">
                  <i class="pi pi-sparkles"></i>
                  Score intelligent
                </span>
              </div>
            </div>
          </div>
          
          <!-- ✅ FILTRES HEARTHSTONE AMÉLIORÉS -->
          <div class="game-filters hearthstone-filters">
            
            <!-- Barre de recherche globale -->
            <div class="filter-search-wrapper">
              <InputText 
                v-model="gameFilters.hearthstone.search"
                placeholder="Rechercher un deck Hearthstone..."
                class="filter-search-input hearthstone-search"
              />
              <i class="pi pi-search search-icon"></i>
            </div>
            
            <!-- Ligne de filtres -->
            <div class="filters-row">
              <Dropdown
                v-model="gameFilters.hearthstone.class"
                :options="hearthstoneClasses"
                option-label="label"
                option-value="value"
                placeholder="Classe"
                class="filter-dropdown hearthstone-dropdown"
              />
              <Dropdown
                v-model="gameFilters.hearthstone.format"
                :options="hearthstoneFormats"
                option-label="label"
                option-value="value"
                placeholder="Format"
                class="filter-dropdown hearthstone-dropdown"
              />
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
          
          <div class="decks-grid">
            <HearthstoneCompactDeck 
              v-for="deck in filteredDecksByGame('hearthstone')" 
              :key="`community-hs-${deck.id}`"
              :deck="deck"
              context="community"
              :current-user="authStore.user"
              @like="toggleLike"
            />
          </div>
        </div>

        <!-- Section Magic -->
        <div v-if="filteredDecksByGame('magic').length > 0 || allDecks.some(d => d?.game?.slug === 'magic')" 
             class="game-section magic-section slide-in-up">
          
          <div class="game-header">
            <div class="game-title-area">
              <div class="game-badge magic">
                <i class="game-icon">🎴</i>
                <span class="game-name">Magic: The Gathering</span>
              </div>
              <div class="game-stats">
                <span class="deck-count">{{ filteredDecksByGame('magic').length }} decks</span>
                <span v-if="gameFilters.magic.sortBy === 'smart'" class="sort-indicator">
                  <i class="pi pi-sparkles"></i>
                  Score intelligent
                </span>
              </div>
            </div>
          </div>
          
          <!-- ✅ FILTRES MAGIC AMÉLIORÉS -->
          <div class="game-filters magic-filters">
            
            <!-- Barre de recherche globale -->
            <div class="filter-search-wrapper">
              <InputText 
                v-model="gameFilters.magic.search"
                placeholder="Rechercher un deck Magic..."
                class="filter-search-input magic-search"
              />
              <i class="pi pi-search search-icon"></i>
            </div>
            
            <!-- Ligne de filtres -->
            <div class="filters-row">
              
              <!-- ✅ CHECKBOXES COULEURS MAGIC REDESIGNÉES -->
              <div class="magic-colors-filter">
                <label class="filter-group-label">Couleurs :</label>
                <div class="magic-colors-grid">
                  <div 
                    v-for="color in magicColors" 
                    :key="color.value"
                    class="magic-color-checkbox"
                    :class="{ 'selected': gameFilters.magic.selectedColors.includes(color.value) }"
                    :style="{ 
                      backgroundColor: gameFilters.magic.selectedColors.includes(color.value) ? color.color : 'transparent',
                      color: gameFilters.magic.selectedColors.includes(color.value) ? color.textColor : '#6b7280',
                      borderColor: color.color
                    }"
                    @click="toggleMagicColor(color.value)"
                  >
                    <i class="pi pi-check" v-if="gameFilters.magic.selectedColors.includes(color.value)"></i>
                    <span>{{ color.label }}</span>
                  </div>
                </div>
              </div>
              
              <Dropdown
                v-model="gameFilters.magic.format"
                :options="magicFormats"
                option-label="label"
                option-value="value"
                placeholder="Format"
                class="filter-dropdown magic-dropdown"
              />
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
          
          <div class="decks-grid">
            <MagicCompactDeck 
              v-for="deck in filteredDecksByGame('magic')" 
              :key="`community-magic-${deck.id}`"
              :deck="deck"
              context="community"
              :current-user="authStore.user"
              @like="toggleLike"
            />
          </div>
        </div>

        <!-- Section Pokemon -->
        <div v-if="filteredDecksByGame('pokemon').length > 0 || allDecks.some(d => d?.game?.slug === 'pokemon')" 
             class="game-section pokemon-section slide-in-up">
          
          <div class="game-header">
            <div class="game-title-area">
              <div class="game-badge pokemon">
                <i class="game-icon">⚡</i>
                <span class="game-name">Pokemon TCG</span>
              </div>
              <div class="game-stats">
                <span class="deck-count">{{ filteredDecksByGame('pokemon').length }} decks</span>
                <span v-if="gameFilters.pokemon.sortBy === 'smart'" class="sort-indicator">
                  <i class="pi pi-sparkles"></i>
                  Score intelligent
                </span>
              </div>
            </div>
          </div>
          
          <!-- ✅ FILTRES POKEMON AMÉLIORÉS -->
          <div class="game-filters pokemon-filters">
            
            <!-- Barre de recherche globale -->
            <div class="filter-search-wrapper">
              <InputText 
                v-model="gameFilters.pokemon.search"
                placeholder="Rechercher un deck Pokemon..."
                class="filter-search-input pokemon-search"
              />
              <i class="pi pi-search search-icon"></i>
            </div>
            
            <!-- Ligne de filtres -->
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
          
          <div class="decks-grid">
            <Card 
              v-for="deck in filteredDecksByGame('pokemon')" 
              :key="`community-pkmn-${deck.id}`"
              class="deck-card gaming-card hover-lift"
            >
              <template #content>
                <div class="deck-content">
                  <div class="deck-header-info">
                    <h3 class="deck-name">{{ deck.title }}</h3>
                    <div class="deck-author">Par {{ deck.author }}</div>
                  </div>
                  <div class="deck-meta">
                    <span class="format-badge pokemon">{{ deck.format.name }}</span>
                  </div>
                  <div class="deck-stats-info">
                    <span class="likes">{{ deck.likesCount || 0 }} ❤️</span>
                    <span class="cards">{{ deck.totalCards || 0 }}/60 cartes</span>
                  </div>
                </div>
              </template>
            </Card>
          </div>
        </div>

      </div>

      <!-- État vide -->
      <div v-if="!isLoading && allDecks.length === 0" class="empty-state">
        <Card class="gaming-card empty-card">
          <template #content>
            <div class="empty-content">
              <i class="pi pi-clone empty-icon"></i>
              <h3 class="empty-title">Aucun deck communautaire</h3>
              <p class="empty-description">
                Les decks partagés par la communauté apparaîtront ici !
              </p>
            </div>
          </template>
        </Card>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useGameFilterStore } from '../../stores/gameFilter'
import { useAuthStore } from '../../stores/auth'
import HearthstoneCompactDeck from '../../components/decks/HearthstoneCompactDeck.vue'
import MagicCompactDeck from '../../components/decks/MagicCompactDeck.vue'
import api from '../../services/api'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Dropdown from 'primevue/dropdown'
import InputText from 'primevue/inputtext'

const allDecks = ref([])
const isLoading = ref(true)

const gameFilterStore = useGameFilterStore()
const { selectedGames } = storeToRefs(gameFilterStore)
const authStore = useAuthStore()

// ✅ FILTRES AMÉLIORÉS avec recherche
const gameFilters = ref({
  hearthstone: {
    search: '',
    class: 'all',
    format: 'all',
    archetype: 'all',
    sortBy: 'smart'
  },
  magic: {
    search: '',
    selectedColors: [],
    format: 'all',
    archetype: 'all',
    sortBy: 'smart'
  },
  pokemon: {
    search: '',
    archetype: 'all',
    sortBy: 'smart'
  }
})

// Options de filtres Hearthstone
const hearthstoneClasses = [
  { label: 'Toutes les classes', value: 'all' },
  { label: 'Mage', value: 'mage' },
  { label: 'Chasseur', value: 'hunter' },
  { label: 'Paladin', value: 'paladin' },
  { label: 'Guerrier', value: 'warrior' },
  { label: 'Prêtre', value: 'priest' },
  { label: 'Démoniste', value: 'warlock' },
  { label: 'Chaman', value: 'shaman' },
  { label: 'Voleur', value: 'rogue' },
  { label: 'Druide', value: 'druid' },
  { label: 'Chasseur de démons', value: 'demonhunter' },
  { label: 'Chevalier de la mort', value: 'deathknight' }
]

const hearthstoneFormats = [
  { label: 'Tous les formats', value: 'all' },
  { label: 'Standard', value: 'standard' },
  { label: 'Wild', value: 'wild' }
]

// Options de filtres Magic
const magicColors = [
  { label: 'Blanc', value: 'W', color: '#FFFBD5', textColor: '#8B4513' },
  { label: 'Bleu', value: 'U', color: '#0E68AB', textColor: '#FFFFFF' },
  { label: 'Noir', value: 'B', color: '#150B00', textColor: '#FFFFFF' },
  { label: 'Rouge', value: 'R', color: '#D3202A', textColor: '#FFFFFF' },
  { label: 'Vert', value: 'G', color: '#00733E', textColor: '#FFFFFF' },
  { label: 'Incolore', value: '', color: '#CCCCCC', textColor: '#333333' }
]

const magicFormats = [
  { label: 'Tous les formats', value: 'all' },
  { label: 'Standard', value: 'standard' },
  { label: 'Commander', value: 'commander' },
  { label: 'Modern', value: 'modern' },
  { label: 'Legacy', value: 'legacy' }
]

// Options communes
const archetypes = [
  { label: 'Tous les archetypes', value: 'all' },
  { label: 'Aggro', value: 'Aggro' },
  { label: 'Control', value: 'Control' },
  { label: 'Midrange', value: 'Midrange' },
  { label: 'Combo', value: 'Combo' },
  { label: 'Tempo', value: 'Tempo' },
  { label: 'Big', value: 'Big' },
  { label: 'Zoo', value: 'Zoo' },
  { label: 'Burn', value: 'Burn' }
]

const sortOptions = [
  { label: 'Score intelligent', value: 'smart' },
  { label: 'Plus récents', value: 'recent' },
  { label: 'Plus populaires', value: 'popular' },
  { label: 'Alphabétique', value: 'alphabetical' }
]

// Algorithme de scoring intelligent
const calculateDeckScore = (deck) => {
  const now = new Date()
  const lastUpdate = new Date(deck.updatedAt || deck.createdAt)
  const daysSinceUpdate = (now - lastUpdate) / (1000 * 60 * 60 * 24)
  
  const freshnessScore = Math.max(0, 100 - (daysSinceUpdate * 1.5))
  const likesScore = (deck.likesCount || 0) * 8
  const recentBonus = daysSinceUpdate < 7 ? 25 : 0
  const popularBonus = (deck.likesCount || 0) >= 5 ? 20 : 0
  const validBonus = deck.validDeck ? 15 : 0
  
  return freshnessScore * 0.4 + likesScore * 0.5 + recentBonus + popularBonus + validBonus
}

const sortDecksByScore = (decks, sortBy) => {
  switch (sortBy) {
    case 'recent':
      return [...decks].sort((a, b) => new Date(b.updatedAt || b.createdAt) - new Date(a.updatedAt || a.createdAt))
    case 'popular':
      return [...decks].sort((a, b) => (b.likesCount || 0) - (a.likesCount || 0))
    case 'alphabetical':
      return [...decks].sort((a, b) => a.title.localeCompare(b.title))
    case 'smart':
    default:
      return [...decks].sort((a, b) => calculateDeckScore(b) - calculateDeckScore(a))
  }
}

// ✅ LOGIQUE DE FILTRAGE AMÉLIORÉE avec recherche
const filteredDecksByGame = (slug) => {
  const gameDecks = allDecks.value.filter(d => d?.game?.slug === slug)
  const filters = gameFilters.value[slug]
  
  if (!filters) return sortDecksByScore(gameDecks, 'smart')
  
  let filtered = gameDecks
  
  // ✅ FILTRAGE PAR RECHERCHE TEXTUELLE
  if (filters.search?.trim()) {
    const searchQuery = filters.search.toLowerCase().trim()
    filtered = filtered.filter(deck => 
      deck.title?.toLowerCase().includes(searchQuery) ||
      deck.description?.toLowerCase().includes(searchQuery) ||
      deck.author?.toLowerCase().includes(searchQuery) ||
      deck.archetype?.toLowerCase().includes(searchQuery)
    )
  }
  
  // Filtres spécifiques Hearthstone
  if (slug === 'hearthstone') {
    if (filters.class !== 'all') {
      filtered = filtered.filter(deck => deck.hearthstoneClass === filters.class)
    }
    if (filters.format !== 'all') {
      filtered = filtered.filter(deck => deck.format?.slug === filters.format)
    }
    if (filters.archetype !== 'all') {
      filtered = filtered.filter(deck => deck.archetype === filters.archetype)
    }
  }
  
  // Filtres spécifiques Magic
  if (slug === 'magic') {
    if (filters.selectedColors.length > 0) {
      filtered = filtered.filter(deck => {
        const deckColors = deck.colorIdentity || []
        return filters.selectedColors.some(selectedColor => 
          selectedColor === '' ? deckColors.length === 0 : deckColors.includes(selectedColor)
        )
      })
    }
    if (filters.format !== 'all') {
      filtered = filtered.filter(deck => deck.format?.slug === filters.format)
    }
    if (filters.archetype !== 'all') {
      filtered = filtered.filter(deck => deck.archetype === filters.archetype)
    }
  }
  
  // Filtres spécifiques Pokemon
  if (slug === 'pokemon') {
    if (filters.archetype !== 'all') {
      filtered = filtered.filter(deck => deck.archetype === filters.archetype)
    }
  }
  
  return sortDecksByScore(filtered, filters.sortBy)
}

// ✅ RESET AMÉLIORÉ avec recherche
const resetGameFilters = (gameSlug) => {
  if (gameFilters.value[gameSlug]) {
    gameFilters.value[gameSlug] = {
      search: '',
      class: 'all',
      format: 'all',
      archetype: 'all',
      selectedColors: [],
      sortBy: 'smart'
    }
  }
}

const toggleMagicColor = (colorValue) => {
  const colors = gameFilters.value.magic.selectedColors
  const index = colors.indexOf(colorValue)
  
  if (index > -1) {
    colors.splice(index, 1)
  } else {
    colors.push(colorValue)
  }
}

// Chargement des données
onMounted(async () => {
  try {
    isLoading.value = true
    console.log('[📡 Requête API] /api/decks/community...')
    const response = await api.get('/api/decks/community')
    console.log('[✅ Réponse API reçue]', response.data)

    const decks = Array.isArray(response.data.data)
      ? response.data.data
      : Array.isArray(response.data)
      ? response.data
      : []

    allDecks.value = decks
    console.log(`[📦 Decks chargés] ${decks.length} decks`)
  } catch (error) {
    console.error('[❌ Erreur chargement decks communauté]:', error)
    allDecks.value = []
  } finally {
    isLoading.value = false
  }
})

const toggleLike = (data) => {
  console.log('[👍 Like] Deck ID:', data.deck?.id)
}
</script>

<style scoped>
/* === COMMUNITY DECKS PAGE AVEC FILTRES EMERALD === */

.community-decks-page {
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

.hearthstone-section::before {
  background: linear-gradient(90deg, #ff5722, #ff9800, #f57c00);
}

.magic-section::before {
  background: linear-gradient(90deg, #673ab7, #9c27b0, #4527a0);
}

.pokemon-section::before {
  background: linear-gradient(90deg, #ffc107, #ff9800, #ff5722);
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
  background: linear-gradient(135deg, rgba(255, 87, 34, 0.15), rgba(255, 152, 0, 0.1));
  color: #ff5722;
  border: 2px solid rgba(255, 87, 34, 0.3);
}

.game-badge.magic {
  background: linear-gradient(135deg, rgba(103, 58, 183, 0.15), rgba(69, 39, 160, 0.1));
  color: #673ab7;
  border: 2px solid rgba(103, 58, 183, 0.4);
}

.game-badge.pokemon {
  background: linear-gradient(135deg, rgba(255, 193, 7, 0.15), rgba(255, 87, 34, 0.1));
  color: #ffc107;
  border: 2px solid rgba(255, 193, 7, 0.4);
}

.game-icon {
  font-size: 1.5rem;
}

.game-stats {
  display: flex;
  align-items: center;
  gap: 1rem;
  color: var(--text-secondary);
  font-size: 0.9rem;
}

.deck-count {
  font-weight: 600;
  color: var(--text-primary);
}

.sort-indicator {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.85rem;
  font-weight: 600;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  backdrop-filter: blur(10px);
  border: 1px solid;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.hearthstone-section .sort-indicator {
  color: #ff5722;
  background: linear-gradient(135deg, rgba(255, 87, 34, 0.15), rgba(255, 152, 0, 0.1));
  border-color: rgba(255, 87, 34, 0.3);
}

.magic-section .sort-indicator {
  color: #673ab7;
  background: linear-gradient(135deg, rgba(103, 58, 183, 0.15), rgba(156, 39, 176, 0.1));
  border-color: rgba(103, 58, 183, 0.3);
}

.pokemon-section .sort-indicator {
  color: #ffc107;
  background: linear-gradient(135deg, rgba(255, 193, 7, 0.15), rgba(255, 152, 0, 0.1));
  border-color: rgba(255, 193, 7, 0.3);
}

.sort-indicator i {
  font-size: 0.8rem;
  animation: sparkle 2s ease-in-out infinite;
}

@keyframes sparkle {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.7; transform: scale(1.1); }
}

/* ✅ FILTRES AMÉLIORÉS CALQUÉS SUR DECK EDITOR */

.game-filters {
  padding: 1.5rem 2rem;
  background: white;
  border-bottom: 1px solid var(--surface-200);
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  animation: fadeInScale 0.3s ease-out;
}

/* ✅ SEARCH WRAPPER LIKE DECK EDITOR */
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

/* ✅ FILTERS ROW LIKE DECK EDITOR */
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

:deep(.filter-dropdown.p-focus) {
  border-color: var(--primary) !important;
  box-shadow: 0 0 0 3px rgba(38, 166, 154, 0.2) !important;
}

/* ✅ CLEAR FILTERS BUTTON LIKE DECK EDITOR */
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

/* ✅ COULEURS THÉMATIQUES PAR JEU COMME DECK EDITOR */

/* Hearthstone - Thème Orange/Feu */
.hearthstone-filters {
  border-left: 6px solid #ff5722;
  background: linear-gradient(135deg, rgba(255, 87, 34, 0.04), rgba(255, 152, 0, 0.02));
  border-radius: 0 16px 16px 0;
}

:deep(.hearthstone-search:focus) {
  border-color: #ff5722 !important;
  box-shadow: 0 0 0 3px rgba(255, 87, 34, 0.15) !important;
}

:deep(.hearthstone-dropdown) {
  border-color: #ff5722 !important;
}

:deep(.hearthstone-dropdown:hover) {
  border-color: #d84315 !important;
  box-shadow: 0 0 0 3px rgba(255, 87, 34, 0.15) !important;
}

:deep(.hearthstone-dropdown.p-focus) {
  border-color: #d84315 !important;
  box-shadow: 0 0 0 4px rgba(255, 87, 34, 0.25) !important;
}

:deep(.hearthstone-sort) {
  border-color: #ff5722 !important;
  background: linear-gradient(135deg, rgba(255, 87, 34, 0.1), rgba(255, 152, 0, 0.05)) !important;
  color: #d84315 !important;
  font-weight: 600 !important;
}

:deep(.hearthstone-clear) {
  color: #ff5722 !important;
}

:deep(.hearthstone-clear:hover) {
  background: linear-gradient(135deg, rgba(255, 87, 34, 0.15), rgba(255, 152, 0, 0.1)) !important;
  color: #d84315 !important;
  border-color: #ff5722 !important;
}

/* Magic - Thème Violet/Noir */
.magic-filters {
  border-left: 6px solid #673ab7;
  background: linear-gradient(135deg, rgba(103, 58, 183, 0.04), rgba(156, 39, 176, 0.02));
  border-radius: 0 16px 16px 0;
}

:deep(.magic-search:focus) {
  border-color: #673ab7 !important;
  box-shadow: 0 0 0 3px rgba(103, 58, 183, 0.15) !important;
}

:deep(.magic-dropdown) {
  border-color: #673ab7 !important;
}

:deep(.magic-dropdown:hover) {
  border-color: #4527a0 !important;
  box-shadow: 0 0 0 3px rgba(103, 58, 183, 0.15) !important;
}

:deep(.magic-dropdown.p-focus) {
  border-color: #4527a0 !important;
  box-shadow: 0 0 0 4px rgba(103, 58, 183, 0.25) !important;
}

:deep(.magic-sort) {
  border-color: #673ab7 !important;
  background: linear-gradient(135deg, rgba(103, 58, 183, 0.1), rgba(156, 39, 176, 0.05)) !important;
  color: #4527a0 !important;
  font-weight: 600 !important;
}

:deep(.magic-clear) {
  color: #673ab7 !important;
}

:deep(.magic-clear:hover) {
  background: linear-gradient(135deg, rgba(103, 58, 183, 0.15), rgba(156, 39, 176, 0.1)) !important;
  color: #4527a0 !important;
  border-color: #673ab7 !important;
}

/* Pokemon - Thème Jaune/Rouge */
.pokemon-filters {
  border-left: 6px solid #ffc107;
  background: linear-gradient(135deg, rgba(255, 193, 7, 0.04), rgba(255, 152, 0, 0.02));
  border-radius: 0 16px 16px 0;
}

:deep(.pokemon-search:focus) {
  border-color: #ffc107 !important;
  box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.15) !important;
}

:deep(.pokemon-dropdown) {
  border-color: #ffc107 !important;
}

:deep(.pokemon-dropdown:hover) {
  border-color: #ff8f00 !important;
  box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.15) !important;
}

:deep(.pokemon-dropdown.p-focus) {
  border-color: #ff8f00 !important;
  box-shadow: 0 0 0 4px rgba(255, 193, 7, 0.25) !important;
}

:deep(.pokemon-sort) {
  border-color: #ffc107 !important;
  background: linear-gradient(135deg, rgba(255, 193, 7, 0.1), rgba(255, 152, 0, 0.05)) !important;
  color: #ff8f00 !important;
  font-weight: 600 !important;
}

:deep(.pokemon-clear) {
  color: #ffc107 !important;
}

:deep(.pokemon-clear:hover) {
  background: linear-gradient(135deg, rgba(255, 193, 7, 0.15), rgba(255, 152, 0, 0.1)) !important;
  color: #ff8f00 !important;
  border-color: #ffc107 !important;
}

/* ✅ CHECKBOXES COULEURS MAGIC REDESIGNÉES COMME DECK EDITOR */
.magic-colors-filter {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin-right: 1rem;
}

.filter-group-label {
  font-size: 0.9rem;
  font-weight: 700;
  color: #673ab7;
  text-transform: uppercase;
  letter-spacing: 1px;
  margin: 0;
}

.magic-colors-grid {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.magic-color-checkbox {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  border: 2px solid;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  font-size: 0.9rem;
  font-weight: 600;
  user-select: none;
  min-width: 90px;
  justify-content: center;
  position: relative;
  overflow: hidden;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.magic-color-checkbox::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
  transition: left 0.5s ease;
}

.magic-color-checkbox:hover::before {
  left: 100%;
}

.magic-color-checkbox:hover {
  transform: translateY(-3px) scale(1.05);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
}

.magic-color-checkbox.selected {
  transform: translateY(-2px) scale(1.02);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.25);
  position: relative;
}

.magic-color-checkbox.selected::after {
  content: '';
  position: absolute;
  top: -2px;
  left: -2px;
  right: -2px;
  bottom: -2px;
  background: linear-gradient(45deg, currentColor, transparent, currentColor);
  border-radius: 14px;
  z-index: -1;
  opacity: 0.3;
  animation: glow 2s ease-in-out infinite alternate;
}

@keyframes glow {
  0% { opacity: 0.3; }
  100% { opacity: 0.6; }
}

.magic-color-checkbox i {
  font-size: 0.8rem;
  animation: checkPop 0.3s ease-out;
}

@keyframes checkPop {
  0% { transform: scale(0); opacity: 0; }
  50% { transform: scale(1.3); }
  100% { transform: scale(1); opacity: 1; }
}

/* ✅ DROPDOWN PANEL AMÉLIORATIONS COMME DECK EDITOR */
:deep(.p-dropdown-panel) {
  border-radius: 8px !important;
  box-shadow: var(--shadow-large) !important;
  border: 1px solid var(--surface-200) !important;
  animation: dropdownSlide 0.2s ease-out !important;
}

@keyframes dropdownSlide {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

:deep(.p-dropdown-item) {
  transition: all var(--transition-fast) !important;
  border-radius: 4px !important;
  margin: 2px !important;
}

:deep(.p-dropdown-item:hover) {
  background: rgba(38, 166, 154, 0.1) !important;
  color: var(--primary) !important;
}

/* ✅ INDICATEURS VISUELS POUR FILTRES ACTIFS */
:deep(.filter-dropdown:not([aria-expanded="false"])) {
  border-color: var(--primary) !important;
  background: rgba(38, 166, 154, 0.1) !important;
}

.filters-row:hover .clear-filters-btn {
  opacity: 1;
  transform: scale(1.05);
}

/* Decks grid */
.decks-grid {
  padding: 2rem;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
  gap: 1.5rem;
}

/* États spéciaux */
.loading-state,
.empty-state {
  display: flex;
  justify-content: center;
  margin: 3rem 0;
}

.loading-card,
.empty-card {
  max-width: 600px;
  width: 100%;
}

.loading-content,
.empty-content {
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

@keyframes emeraldSpin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.empty-icon {
  font-size: 4rem;
  color: var(--text-secondary);
  margin-bottom: 1rem;
  opacity: 0.7;
}

.empty-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0 0 0.5rem 0;
}

.empty-description {
  color: var(--text-secondary);
  margin: 0;
  line-height: 1.5;
}

/* Pokemon deck cards (temporaire) */
.deck-content {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  height: 100%;
}

.deck-header-info {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
}

.deck-name {
  font-size: 1.2rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0;
  flex: 1;
  line-height: 1.3;
}

.deck-author {
  font-size: 0.85rem;
  color: var(--text-secondary);
  font-style: italic;
}

.deck-meta {
  display: flex;
  gap: 0.75rem;
  align-items: center;
  flex-wrap: wrap;
}

.format-badge {
  padding: 0.25rem 0.75rem;
  background: rgba(38, 166, 154, 0.1);
  color: var(--primary);
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
}

.format-badge.pokemon {
  background: rgba(255, 193, 7, 0.1);
  color: #ff6f00;
}

.deck-stats-info {
  display: flex;
  gap: 1rem;
  align-items: center;
  font-size: 0.85rem;
  color: var(--text-secondary);
}

.deck-stats-info span {
  display: flex;
  align-items: center;
  gap: 0.25rem;
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

/* ✅ RESPONSIVE AMÉLIORÉ */
@media (max-width: 1024px) {
  .container {
    padding: 0 1rem;
  }
  
  .decks-grid {
    padding: 1.5rem;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1rem;
  }
  
  .game-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .game-stats {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
  
  .game-filters {
    padding: 1.25rem 1.5rem;
  }
  
  .filters-row {
    gap: 0.75rem;
  }
  
  :deep(.filter-dropdown) {
    min-width: 120px !important;
  }
  
  .magic-colors-grid {
    justify-content: center;
  }
}

@media (max-width: 768px) {
  .community-decks-page {
    padding: 1rem 0;
  }
  
  .game-header {
    padding: 1rem 1.5rem;
  }
  
  .game-filters {
    padding: 1rem 1.5rem;
  }
  
  .filter-search-wrapper {
    max-width: 100%;
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
  
  .magic-colors-filter {
    margin-right: 0;
    width: 100%;
  }
  
  .magic-colors-grid {
    justify-content: center;
    gap: 0.5rem;
  }
  
  .magic-color-checkbox {
    min-width: 70px;
    font-size: 0.8rem;
    padding: 0.6rem 0.8rem;
  }
  
  .filter-group-label {
    text-align: center;
  }
  
  .decks-grid {
    grid-template-columns: 1fr;
    padding: 1rem;
  }
  
  .sort-indicator {
    display: none;
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
  
  .game-filters {
    padding: 1rem;
  }
  
  :deep(.filter-search-input) {
    height: 40px !important;
    padding-left: 2.5rem !important;
  }
  
  .search-icon {
    left: 0.75rem;
    font-size: 1rem;
  }
  
  :deep(.clear-filters-btn) {
    width: 36px !important;
    height: 36px !important;
  }
}
</style>
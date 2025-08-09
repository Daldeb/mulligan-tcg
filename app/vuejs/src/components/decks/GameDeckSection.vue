<template>
  <div 
    v-if="hasDecksOrData" 
    :class="[
      'game-section', 
      `${gameSlug}-section`, 
      'slide-in-up'
    ]"
  >
    
    <!-- Header avec stats thématisées -->
    <div class="game-header">
      <div class="game-title-area">
        <div :class="['game-badge', gameSlug]">
          <i class="game-icon">{{ gameConfig.icon }}</i>
          <span class="game-name">{{ gameConfig.name }}</span>
        </div>
        
        <!-- Stats adaptées au contexte -->
        <div v-if="context === 'my-decks'" class="game-stats-integrated">
          <div class="stat-item likes">
            <i class="pi pi-heart"></i>
            <span class="stat-value">{{ gameStats.totalLikes }}</span>
          </div>
          <div class="stat-item public">
            <i class="pi pi-globe"></i>
            <span class="stat-value">{{ gameStats.publicCount }}</span>
          </div>
          <div class="stat-item private">
            <i class="pi pi-lock"></i>
            <span class="stat-value">{{ gameStats.privateCount }}</span>
          </div>
          <div class="stat-item total">
            <span class="stat-label">{{ filteredDecks.length }} decks</span>
          </div>
        </div>
        
        <!-- Stats pour communauté -->
        <div v-else class="game-stats">
          <span class="deck-count">{{ filteredDecks.length }} decks</span>
          <span v-if="filters.sortBy === 'smart'" class="sort-indicator">
            <i class="pi pi-sparkles"></i>
            Score intelligent
          </span>
        </div>
      </div>
    </div>

    <!-- Filtres par jeu -->
    <div :class="['game-filters-panel', `${gameSlug}-filters-panel`]">
      
      <!-- Recherche commune -->
      <div class="filter-search-wrapper">
        <InputText 
          v-model="filters.search"
          :placeholder="`Rechercher un deck ${gameConfig.name}...`"
          :class="['filter-search-input', `${gameSlug}-search`]"
        />
        <i class="pi pi-search search-icon"></i>
      </div>
      
      <!-- Filtres spécifiques Hearthstone -->
      <div v-if="gameSlug === 'hearthstone'" class="filters-main-row">
        
<!-- Slider coût poussière REFACTORISÉ -->
<div class="dust-cost-filter-group">
  <label class="filter-group-label">
    Coût en poussière : 
    <span class="dust-range-display">
      {{ formatDustCost(filters.dustCost.min) }} - {{ formatDustCost(filters.dustCost.max) }}
    </span>
  </label>
  
  <div class="dual-range-container">
    <!-- Piste visuelle de fond -->
    <div class="range-track-bg"></div>
    
    <!-- Piste de sélection active -->
    <div 
      class="range-track-active" 
      :style="rangeTrackStyle"
    ></div>
    
    <!-- Input range minimum -->
    <input
      type="range"
      :min="DUST_MIN"
      :max="DUST_MAX"
      :step="DUST_STEP"
      v-model.number="filters.dustCost.min"
      class="range-input range-min"
      @input="handleMinChange"
    />
    
    <!-- Input range maximum -->
    <input
      type="range"
      :min="DUST_MIN"
      :max="DUST_MAX"
      :step="DUST_STEP"
      v-model.number="filters.dustCost.max"
      class="range-input range-max"
      @input="handleMaxChange"
    />
    
    <!-- Thumbs visuels personnalisés -->
    <div 
      class="range-thumb range-thumb-min" 
      :style="minThumbStyle"
    >
      <div class="thumb-tooltip">{{ formatDustCost(filters.dustCost.min) }}</div>
    </div>
    
    <div 
      class="range-thumb range-thumb-max" 
      :style="maxThumbStyle"
    >
      <div class="thumb-tooltip">{{ formatDustCost(filters.dustCost.max) }}</div>
    </div>
  </div>
</div>
                
        <!-- Toggle Standard/Wild -->
        <div class="format-filter-group">
          <label class="filter-group-label">Format :</label>
          <div class="format-toggle-buttons">
            <button 
              class="format-toggle-btn"
              :class="{ 'active': filters.format === 'all' }"
              @click="filters.format = 'all'"
            >
              <i class="pi pi-globe"></i>
              <span>Tous</span>
            </button>
            <button 
              class="format-toggle-btn standard"
              :class="{ 'active': filters.format === 'standard' }"
              @click="filters.format = 'standard'"
            >
              <i class="pi pi-star"></i>
              <span>Standard</span>
            </button>
            <button 
              class="format-toggle-btn wild"
              :class="{ 'active': filters.format === 'wild' }"
              @click="filters.format = 'wild'"
            >
              <i class="pi pi-sun"></i>
              <span>Wild</span>
            </button>
          </div>
        </div>
        
        <!-- Actions -->
        <div class="filters-actions-group">
          <Dropdown
            v-model="filters.sortBy"
            :options="sortOptions"
            option-label="label"
            option-value="value"
            class="filter-sort-dropdown"
          />
          <Button
            icon="pi pi-filter-slash"
            class="reset-filters-btn"
            @click="resetFilters"
            v-tooltip="'Réinitialiser les filtres'"
            text
            size="small"
          />
        </div>
      </div>
      
      <!-- Classes Hearthstone avec images -->
      <div v-if="gameSlug === 'hearthstone'" class="classes-inline-row">
        <div 
          v-for="hsClass in hearthstoneClassesFilter" 
          :key="hsClass.value"
          class="class-checkbox-inline"
          :class="{ 'selected': filters.selectedClasses?.includes(hsClass.value) }"
          @click="toggleHearthstoneClass(hsClass.value)"
        >
          <img 
            :src="hsClass.icon" 
            :alt="hsClass.name"
            class="class-checkbox-icon-inline"
          />
          <span class="class-checkbox-name-inline">{{ hsClass.name }}</span>
          <div class="class-checkbox-indicator-inline" v-if="filters.selectedClasses?.includes(hsClass.value)">
            <i class="pi pi-check"></i>
          </div>
        </div>
      </div>

      <!-- Filtres spécifiques Magic -->
      <div v-if="gameSlug === 'magic'" class="filters-main-row">
        
        <!-- Couleurs Magic -->
        <div class="magic-colors-filter">
          <label class="filter-group-label">Couleurs :</label>
          <div class="magic-colors-grid">
            <div 
              v-for="color in magicColors" 
              :key="color.value"
              class="magic-color-checkbox"
              :class="{ 'selected': filters.selectedColors?.includes(color.value) }"
              :style="{ 
                backgroundColor: filters.selectedColors?.includes(color.value) ? color.color : 'transparent',
                color: filters.selectedColors?.includes(color.value) ? color.textColor : '#6b7280',
                borderColor: color.color
              }"
              @click="toggleMagicColor(color.value)"
            >
              <i class="pi pi-check" v-if="filters.selectedColors?.includes(color.value)"></i>
              <span>{{ color.label }}</span>
            </div>
          </div>
        </div>
        
        <!-- Format Magic -->
        <div class="format-filter-group">
          <label class="filter-group-label">Format :</label>
          <Dropdown
            v-model="filters.format"
            :options="magicFormats"
            option-label="label"
            option-value="value"
            placeholder="Tous les formats"
            class="filter-dropdown magic-dropdown"
          />
        </div>
        
        <!-- Actions Magic -->
        <div class="filters-actions-group">
          <Dropdown
            v-model="filters.sortBy"
            :options="sortOptions"
            option-label="label"
            option-value="value"
            class="filter-sort-dropdown"
          />
          <Button
            icon="pi pi-filter-slash"
            class="reset-filters-btn"
            @click="resetFilters"
            v-tooltip="'Réinitialiser les filtres'"
            text
            size="small"
          />
        </div>
      </div>

      <!-- Filtres spécifiques Pokemon -->
      <div v-if="gameSlug === 'pokemon'" class="filters-main-row">
        <div class="filters-actions-group">
          <Dropdown
            v-model="filters.sortBy"
            :options="sortOptions"
            option-label="label"
            option-value="value"
            class="filter-sort-dropdown"
          />
          <Button
            icon="pi pi-filter-slash"
            class="reset-filters-btn"
            @click="resetFilters"
            v-tooltip="'Réinitialiser les filtres'"
            text
            size="small"
          />
        </div>
      </div>
      
    </div>

    <!-- Grid de decks -->
    <div class="decks-grid">
      
      <!-- Wrapper pour chaque deck avec créateur (si community) -->
      <template v-for="deck in filteredDecks" :key="`${context}-${gameSlug}-${deck.id}`">
        
        <!-- Section créateur (uniquement pour community) -->
        <div v-if="context === 'community'" class="deck-with-creator">
          <div class="deck-creator-header">
            <div class="creator-info">
              <div 
                class="creator-avatar"
                :class="{ 'clickable-avatar': canNavigateToProfile(deck.authorId || deck.user?.id) }"
                @click="canNavigateToProfile(deck.authorId || deck.user?.id) && goToProfile(deck.authorId || deck.user?.id, deck.author || deck.user?.pseudo)"
                :title="canNavigateToProfile(deck.authorId || deck.user?.id) ? getProfileTooltip(deck.author || deck.user?.pseudo) : ''"
              >
                <img 
                  v-if="deck.authorAvatar || deck.user?.avatar"
                  :src="`${backendUrl}/uploads/${deck.authorAvatar || deck.user?.avatar}`"
                  class="creator-avatar-image"
                  alt="Avatar"
                  @error="handleImageError"
                />
                <span v-else class="creator-avatar-fallback">
                  {{ (deck.author || deck.user?.pseudo || 'U')?.charAt(0).toUpperCase() }}
                </span>
              </div>
              
              <div class="creator-details">
                <span 
                  class="creator-name"
                  :class="{ 'clickable-name': canNavigateToProfile(deck.authorId || deck.user?.id) }"
                  @click="canNavigateToProfile(deck.authorId || deck.user?.id) && goToProfile(deck.authorId || deck.user?.id, deck.author || deck.user?.pseudo)"
                  :title="canNavigateToProfile(deck.authorId || deck.user?.id) ? getProfileTooltip(deck.author || deck.user?.pseudo) : ''"
                >
                  {{ deck.author || deck.user?.pseudo || 'Deck importé' }}
                </span>
                <span v-if="deck.user" class="creator-label">Créateur du deck</span>
                <span v-else class="creator-label imported">Deck importé</span>
              </div>
            </div>
            
            <div v-if="deck.publishedAt || deck.createdAt" class="deck-publish-date">
              <i class="pi pi-calendar"></i>
              <span>{{ formatDeckDate(deck.publishedAt || deck.createdAt) }}</span>
            </div>
          </div>
          
          <!-- Le composant deck -->
          <component 
            :is="deckComponentName"
            :deck="deck"
            :context="context"
            :current-user="currentUser"
            @edit="$emit('edit', deck)"
            @delete="$emit('delete', deck)"
            @like="$emit('like', deck)"
            @copy="$emit('copy', deck)"
            @copyDeckcode="$emit('copyDeckcode', deck)"
          />
        </div>
        
        <!-- Deck simple (pour my-decks) -->
        <component 
          v-else
          :is="deckComponentName"
          :deck="deck"
          :context="context"
          :current-user="currentUser"
          @edit="$emit('edit', deck)"
          @delete="$emit('delete', deck)"
          @like="$emit('like', deck)"
          @copy="$emit('copy', deck)"
          @copyDeckcode="$emit('copyDeckcode', deck)"
        />
        
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import HearthstoneCompactDeck from './HearthstoneCompactDeck.vue'
import MagicCompactDeck from './MagicCompactDeck.vue'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Dropdown from 'primevue/dropdown'
import InputText from 'primevue/inputtext'
import { useProfileNavigation } from '@/composables/useProfileNavigation'


const backendUrl = computed(() => import.meta.env.VITE_BACKEND_URL)
const { goToProfile, canNavigateToProfile, getProfileTooltip } = useProfileNavigation()

const formatDeckDate = (dateString) => {
  const date = new Date(dateString)
  const now = new Date()
  const diffTime = Math.abs(now - date)
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  
  if (diffDays === 1) return 'Hier'
  if (diffDays < 7) return `Il y a ${diffDays} jours`
  if (diffDays < 30) return `Il y a ${Math.ceil(diffDays / 7)} semaines`
  
  return date.toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: date.getFullYear() !== now.getFullYear() ? 'numeric' : undefined
  })
}

const handleImageError = (event) => {
  // Gérer les erreurs d'image
  event.target.style.display = 'none'
}

const props = defineProps({
  gameSlug: {
    type: String,
    required: true
  },
  decks: {
    type: Array,
    default: () => []
  },
  context: {
    type: String,
    required: true // 'my-decks' | 'community'
  },
  currentUser: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['edit', 'delete', 'like', 'copy', 'copyDeckcode'])

const DUST_MIN = 0
const DUST_MAX = 10000
const DUST_STEP = 200

// Configuration par jeu
const gameConfigs = {
  hearthstone: {
    name: 'Hearthstone',
    icon: '🃏',
    component: 'HearthstoneCompactDeck'
  },
  magic: {
    name: 'Magic: The Gathering', 
    icon: '🎴',
    component: 'MagicCompactDeck'
  },
  pokemon: {
    name: 'Pokemon TCG',
    icon: '⚡',
    component: 'Card'
  }
}

// State des filtres
const filters = ref({
  search: '',
  sortBy: 'smart',
  // Hearthstone
  selectedClasses: [],
  dustCost: { min: 0, max: 10000 },
  format: 'all',
  // Magic
  selectedColors: [],
  // Pokemon (pas de filtres spéciaux)
})

// Data statique
const hearthstoneClassesFilter = [
  { name: 'Mage', value: 'mage', icon: '/src/assets/images/icons/Alt-Heroes_Mage_Jaina.png.avif' },
  { name: 'Chasseur', value: 'hunter', icon: '/src/assets/images/icons/Alt-Heroes_Hunter_Rexxar.png.avif' },
  { name: 'Paladin', value: 'paladin', icon: '/src/assets/images/icons/Alt-Heroes_Paladin_Uther.png.avif' },
  { name: 'Guerrier', value: 'warrior', icon: '/src/assets/images/icons/Alt-Heroes_Warrior_Garrosh.png.avif' },
  { name: 'Prêtre', value: 'priest', icon: '/src/assets/images/icons/Alt-Heroes_Priest_Anduin.png.avif' },
  { name: 'Démoniste', value: 'warlock', icon: '/src/assets/images/icons/Alt-Heroes_Warlock_Guldan.png.avif' },
  { name: 'Chaman', value: 'shaman', icon: '/src/assets/images/icons/Alt-Heroes_Shaman_Thrall.png.avif' },
  { name: 'Voleur', value: 'rogue', icon: '/src/assets/images/icons/Alt-Heroes_Rogue_Valeera.png.avif' },
  { name: 'Druide', value: 'druid', icon: '/src/assets/images/icons/Alt-Heroes_Druid_Malfurion.png.avif' },
  { name: 'Chasseur de démons', value: 'demonhunter', icon: '/src/assets/images/icons/Alt-Heroes_Demon-Hunter_Illidan.png.avif' },
  { name: 'Chevalier de la mort', value: 'deathknight', icon: '/src/assets/images/icons/hearthstone-lich-king.webp' }
]

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

const sortOptions = [
  { label: 'Score intelligent', value: 'smart' },
  { label: 'Plus récents', value: 'recent' },
  { label: 'Plus populaires', value: 'popular' },
  { label: 'Alphabétique', value: 'alphabetical' }
]

// Computed
const gameConfig = computed(() => gameConfigs[props.gameSlug])

const hasDecksOrData = computed(() => {
  return props.decks.length > 0 || 
    (props.context === 'community' && props.decks.some(d => d?.game?.slug === props.gameSlug))
})

const deckComponentName = computed(() => {
  if (props.gameSlug === 'pokemon') {
    return Card
  }
  return gameConfig.value?.component === 'HearthstoneCompactDeck' 
    ? HearthstoneCompactDeck 
    : MagicCompactDeck
})

const filteredDecks = computed(() => {
  let decks = [...props.decks]
  
  // Filtre par recherche
  if (filters.value.search?.trim()) {
    const query = filters.value.search.toLowerCase()
    decks = decks.filter(deck => 
      deck.title?.toLowerCase().includes(query) ||
      deck.name?.toLowerCase().includes(query) ||
      deck.description?.toLowerCase().includes(query) ||
      deck.author?.toLowerCase().includes(query) ||
      deck.archetype?.toLowerCase().includes(query)
    )
  }
  
  // Filtres Hearthstone
  if (props.gameSlug === 'hearthstone') {
    if (filters.value.selectedClasses?.length > 0) {
      decks = decks.filter(deck => 
        filters.value.selectedClasses.includes(deck.hearthstoneClass)
      )
    }
    if (filters.value.format !== 'all') {
      decks = decks.filter(deck => deck.format?.slug === filters.value.format)
    }
    decks = decks.filter(deck => {
      const dustCost = calculateDeckDustCost(deck)
      return dustCost >= filters.value.dustCost.min && 
             dustCost <= filters.value.dustCost.max
    })
  }
  
  // Filtres Magic
  if (props.gameSlug === 'magic') {
    if (filters.value.selectedColors?.length > 0) {
      decks = decks.filter(deck => {
        const deckColors = deck.colorIdentity || []
        return filters.value.selectedColors.some(selectedColor => 
          selectedColor === '' ? deckColors.length === 0 : deckColors.includes(selectedColor)
        )
      })
    }
    if (filters.value.format !== 'all') {
      decks = decks.filter(deck => deck.format?.slug === filters.value.format)
    }
  }
  
  return sortDecks(decks, filters.value.sortBy)
})

const gameStats = computed(() => {
  if (props.context !== 'my-decks') return {}
  
  const totalLikes = props.decks.reduce((sum, deck) => sum + (deck.likesCount || deck.likes || 0), 0)
  const publicCount = props.decks.filter(deck => deck.isPublic).length
  const privateCount = props.decks.filter(deck => !deck.isPublic).length
  
  return { totalLikes, publicCount, privateCount }
})
// Computed pour le nouveau slider
const rangeTrackStyle = computed(() => {
  const min = filters.value.dustCost.min
  const max = filters.value.dustCost.max
  const minPercent = ((min - DUST_MIN) / (DUST_MAX - DUST_MIN)) * 100
  const maxPercent = ((max - DUST_MIN) / (DUST_MAX - DUST_MIN)) * 100
  
  return {
    left: `${minPercent}%`,
    width: `${maxPercent - minPercent}%`
  }
})

const minThumbStyle = computed(() => {
  const percent = ((filters.value.dustCost.min - DUST_MIN) / (DUST_MAX - DUST_MIN)) * 100
  return {
    left: `calc(${percent}% - 12px)`
  }
})

const maxThumbStyle = computed(() => {
  const percent = ((filters.value.dustCost.max - DUST_MIN) / (DUST_MAX - DUST_MIN)) * 100
  return {
    left: `calc(${percent}% - 12px)`
  }
})
// Méthodes
const calculateDeckDustCost = (deck) => {
  if (!deck.cards || deck.cards.length === 0) return 0
  
  const dustCosts = {
    'common': 40,
    'rare': 100, 
    'epic': 400,
    'legendary': 1600
  }
  
  return deck.cards.reduce((sum, cardEntry) => {
    const rarity = cardEntry.card.rarity?.toLowerCase() || 'common'
    const cardCost = dustCosts[rarity] || 40
    return sum + (cardCost * cardEntry.quantity)
  }, 0)
}

const calculateDeckScore = (deck) => {
  const now = new Date()
  const lastUpdate = new Date(deck.updatedAt || deck.createdAt)
  const daysSinceUpdate = (now - lastUpdate) / (1000 * 60 * 60 * 24)
  
  const freshnessScore = Math.max(0, 100 - (daysSinceUpdate * 1.5))
  const likesScore = (deck.likesCount || deck.likes || 0) * 8
  const recentBonus = daysSinceUpdate < 7 ? 25 : 0
  const popularBonus = (deck.likesCount || deck.likes || 0) >= 5 ? 20 : 0
  const validBonus = deck.validDeck ? 15 : 0
  
  return freshnessScore * 0.4 + likesScore * 0.5 + recentBonus + popularBonus + validBonus
}

const sortDecks = (decks, sortBy) => {
  switch (sortBy) {
    case 'recent':
      return [...decks].sort((a, b) => new Date(b.updatedAt || b.createdAt) - new Date(a.updatedAt || a.createdAt))
    case 'popular':
      return [...decks].sort((a, b) => (b.likesCount || b.likes || 0) - (a.likesCount || a.likes || 0))
    case 'alphabetical':
      return [...decks].sort((a, b) => (a.title || a.name || '').localeCompare(b.title || b.name || ''))
    case 'smart':
    default:
      return [...decks].sort((a, b) => calculateDeckScore(b) - calculateDeckScore(a))
  }
}

const toggleHearthstoneClass = (classValue) => {
  if (!filters.value.selectedClasses) {
    filters.value.selectedClasses = []
  }
  
  const classes = filters.value.selectedClasses
  const index = classes.indexOf(classValue)
  
  if (index > -1) {
    classes.splice(index, 1)
  } else {
    classes.push(classValue)
  }
}

const toggleMagicColor = (colorValue) => {
  if (!filters.value.selectedColors) {
    filters.value.selectedColors = []
  }
  
  const colors = filters.value.selectedColors
  const index = colors.indexOf(colorValue)
  
  if (index > -1) {
    colors.splice(index, 1)
  } else {
    colors.push(colorValue)
  }
}
// Nouvelles méthodes pour le slider
const handleMinChange = () => {
  if (filters.value.dustCost.min > filters.value.dustCost.max) {
    filters.value.dustCost.min = filters.value.dustCost.max
  }
}

const handleMaxChange = () => {
  if (filters.value.dustCost.max < filters.value.dustCost.min) {
    filters.value.dustCost.max = filters.value.dustCost.min
  }
}

const formatDustCost = (value) => {
  if (value >= 10000) return '10000+'
  return value.toLocaleString()
}


const resetFilters = () => {
  filters.value = {
    search: '',
    sortBy: 'smart',
    selectedClasses: [],
    dustCost: { min: 0, max: 10000 },
    format: 'all',
    selectedColors: []
  }
}
</script>

<style scoped>
/* === GAME DECK SECTION - DESIGN UNIFIÉ === */

.game-section {
  background: white;
  border-radius: var(--border-radius-large);
  border: 1px solid var(--surface-200);
  box-shadow: var(--shadow-small);
  overflow: hidden;
  position: relative;
  transition: all var(--transition-medium);
  margin-bottom: 3rem;
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

/* Thématisation par jeu */
.hearthstone-section::before {
  background: linear-gradient(90deg, #d97706, #f59e0b, #b45309);
}

.magic-section::before {
  background: linear-gradient(90deg, #7c3aed, #8b5cf6, #a855f7);
}

.pokemon-section::before {
  background: linear-gradient(90deg, #ffc107, #ff9800, #ff5722);
}

/* Header unifié */
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
  justify-content: space-between;
  width: 100%;
  gap: 2rem;
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
  background: rgba(217, 119, 6, 0.1);
  color: #d97706;
  border: 2px solid rgba(217, 119, 6, 0.3);
}

.game-badge.magic {
  background: rgba(124, 58, 237, 0.1);
  color: #7c3aed;
  border: 2px solid rgba(124, 58, 237, 0.3);
}

.game-badge.pokemon {
  background: rgba(255, 193, 7, 0.1);
  color: #ff6f00;
  border: 2px solid rgba(255, 193, 7, 0.3);
}

.game-icon {
  font-size: 1.5rem;
}

/* Stats pour MyDecks */
.game-stats-integrated {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.75rem;
  background: white;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 600;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  border: 1px solid var(--surface-200);
  transition: all var(--transition-fast);
}

.stat-item:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.stat-item.likes {
  color: #e11d48;
  border-color: rgba(225, 29, 72, 0.2);
}

.stat-item.public {
  color: var(--primary);
  border-color: rgba(38, 166, 154, 0.2);
}

.stat-item.private {
  color: #6b7280;
  border-color: rgba(107, 114, 128, 0.2);
}

.stat-item.total {
  color: var(--text-primary);
  border-color: var(--surface-300);
  background: var(--surface-100);
  font-style: italic;
}

.stat-value {
  font-weight: 700;
  font-size: 0.9rem;
  min-width: 20px;
  text-align: center;
  color: var(--text-primary) !important;
}

.stat-label {
  font-weight: 500;
  font-size: 0.85rem;
  white-space: nowrap;
}

/* Stats pour Community */
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

/* === FILTRES PAR JEU === */

.game-filters-panel {
  padding: 2rem;
  border-bottom: 1px solid var(--surface-200);
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  animation: fadeInScale 0.3s ease-out;
}

/* Thématisation des panneaux de filtres */
.hearthstone-filters-panel {
  background: linear-gradient(135deg, rgba(217, 119, 6, 0.04), rgba(255, 152, 0, 0.02));
  border-left: 6px solid #d97706;
}

.magic-filters-panel {
  background: linear-gradient(135deg, rgba(124, 58, 237, 0.04), rgba(139, 92, 246, 0.02));
  border-left: 6px solid #7c3aed;
}

.pokemon-filters-panel {
  background: linear-gradient(135deg, rgba(255, 193, 7, 0.04), rgba(255, 152, 0, 0.02));
  border-left: 6px solid #ffc107;
}

/* Recherche commune */
.filter-search-wrapper {
  position: relative;
  max-width: 400px;
  width: 100%;
}

:deep(.filter-search-input) {
  width: 100% !important;
  padding: 0.875rem 1rem 0.875rem 3rem !important;
  border: 2px solid !important;
  border-radius: 25px !important;
  background: white !important;
  font-size: 0.9rem !important;
  transition: all var(--transition-fast) !important;
}

:deep(.hearthstone-search) {
  border-color: #d97706 !important;
}

:deep(.hearthstone-search:focus) {
  border-color: #b45309 !important;
  box-shadow: 0 0 0 3px rgba(217, 119, 6, 0.15) !important;
}

:deep(.magic-search) {
  border-color: #7c3aed !important;
}

:deep(.magic-search:focus) {
  border-color: #5b21b6 !important;
  box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.15) !important;
}

:deep(.pokemon-search) {
  border-color: #ffc107 !important;
}

:deep(.pokemon-search:focus) {
  border-color: #f59e0b !important;
  box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.15) !important;
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

/* Layout principal des filtres */
.filters-main-row {
  display: grid;
  grid-template-columns: 1fr auto auto;
  column-gap: 2rem;
  row-gap: 1rem;
  align-items: flex-start;
}

.filter-group-label {
  display: block;
  height: 18px;
  line-height: 18px;
  margin-bottom: 8px;
  font-size: 0.9rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1px;
}

/* === FILTRES HEARTHSTONE === */

/* Slider coût poussière simplifié */
.dust-cost-filter-group {
  min-width: 280px;
  flex: 1;
}

/* === SLIDER DUAL-RANGE HEARTHSTONE REFACTORISÉ === */

.dust-cost-filter-group {
  min-width: 320px;
  flex: 1;
}

.dual-range-container {
  position: relative;
  height: 60px;
  margin: 1rem 0;
  padding: 1rem 0;
}

/* Piste de fond */
.range-track-bg {
  position: absolute;
  top: 24px;
  left: 0;
  right: 0;
  height: 8px;
  background: #e5e7eb;
  border-radius: 4px;
  box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
}

/* Piste active (sélection) */
.range-track-active {
  position: absolute;
  top: 24px;
  height: 8px;
  background: linear-gradient(90deg, #d97706, #f59e0b);
  border-radius: 4px;
  box-shadow: 0 2px 6px rgba(217, 119, 6, 0.3);
  transition: all 0.2s ease;
  z-index: 1;
}

/* Inputs range invisibles */
.range-input {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: transparent;
  pointer-events: none;
  opacity: 0;
  cursor: pointer;
  -webkit-appearance: none;
  appearance: none;
}

.range-input::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none;
  width: 24px;
  height: 24px;
  background: transparent;
  cursor: pointer;
  pointer-events: auto;
}

.range-input::-moz-range-thumb {
  width: 24px;
  height: 24px;
  background: transparent;
  border: none;
  cursor: pointer;
  pointer-events: auto;
}

.range-min { z-index: 2; }
.range-max { z-index: 3; }

/* Thumbs visuels personnalisés */
.range-thumb {
  position: absolute;
  top: 16px;
  width: 24px;
  height: 24px;
  background: #d97706;
  border: 3px solid white;
  border-radius: 50%;
  box-shadow: 0 3px 12px rgba(217, 119, 6, 0.4);
  cursor: pointer;
  transition: all 0.2s ease;
  z-index: 4;
  pointer-events: none;
}

.range-thumb:hover {
  transform: scale(1.1);
  box-shadow: 0 4px 16px rgba(217, 119, 6, 0.5);
}

.range-thumb.range-thumb-max {
  background: #f59e0b;
  z-index: 5;
}

/* Tooltips des thumbs */
.thumb-tooltip {
  position: absolute;
  bottom: 35px;
  left: 50%;
  transform: translateX(-50%);
  background: #1f2937;
  color: white;
  padding: 0.375rem 0.75rem;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 600;
  white-space: nowrap;
  opacity: 0;
  transition: opacity 0.2s ease;
  pointer-events: none;
  z-index: 10;
}

.thumb-tooltip::after {
  content: '';
  position: absolute;
  top: 100%;
  left: 50%;
  transform: translateX(-50%);
  border: 4px solid transparent;
  border-top-color: #1f2937;
}

.range-thumb:hover .thumb-tooltip {
  opacity: 1;
}

/* Responsive */
@media (max-width: 768px) {
  .dust-cost-filter-group {
    min-width: 280px;
  }
  
  .dual-range-container {
    height: 50px;
    padding: 0.75rem 0;
  }
  
  .dust-inputs-row {
    gap: 0.75rem;
    margin-top: 0.5rem;
  }
}

@media (max-width: 480px) {
  .dust-inputs-row {
    flex-direction: column;
    gap: 0.5rem;
  }
}

.dust-range-display {
  font-weight: 600;
  color: #f59e0b;
  background: rgba(245, 158, 11, 0.1);
  padding: 0.25rem 0.5rem;
  border-radius: 8px;
  font-size: 0.8rem;
}

/* Toggle format Standard/Wild */
.format-filter-group {
  min-width: 200px;
}

.format-toggle-buttons {
  display: flex;
  background: white;
  border-radius: 12px;
  padding: 0.25rem;
  border: 2px solid var(--surface-300);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.format-toggle-btn {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  background: transparent;
  border: none;
  border-radius: 8px;
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--text-secondary);
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.format-toggle-btn:hover {
  background: rgba(217, 119, 6, 0.1);
  color: #d97706;
  transform: translateY(-1px);
}

.format-toggle-btn.active {
  background: linear-gradient(135deg, #d97706, #f59e0b);
  color: white;
  box-shadow: 0 4px 12px rgba(217, 119, 6, 0.3);
  transform: translateY(-2px);
}

.format-toggle-btn.standard.active {
  background: linear-gradient(135deg, #3b82f6, #1e40af);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.format-toggle-btn.wild.active {
  background: linear-gradient(135deg, #f59e0b, #d97706);
  box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
}

/* Classes Hearthstone avec images */
.classes-inline-row {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
  justify-content: center;
  padding-top: 0.75rem;
  border-top: 1px solid rgba(217, 119, 6, 0.2);
  margin-top: 0.75rem;
}

.class-checkbox-inline {
  position: relative;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border-radius: 10px;
  overflow: hidden;
  border: 2px solid transparent;
  background: white;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.75rem;
  min-width: 100px;
}

.class-checkbox-inline:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(217, 119, 6, 0.2);
  border-color: #d97706;
}

.class-checkbox-inline.selected {
  border-color: #d97706;
  background: linear-gradient(135deg, rgba(217, 119, 6, 0.15), rgba(255, 152, 0, 0.08));
  transform: translateY(-1px);
  box-shadow: 0 6px 16px rgba(217, 119, 6, 0.25);
}

.class-checkbox-icon-inline {
  width: 32px;
  height: 32px;
  object-fit: contain;
  transition: all var(--transition-fast);
  filter: drop-shadow(0 1px 3px rgba(0, 0, 0, 0.2));
  flex-shrink: 0;
}

.class-checkbox-inline:hover .class-checkbox-icon-inline {
  transform: scale(1.1);
}

.class-checkbox-name-inline {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--text-primary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  white-space: nowrap;
}

.class-checkbox-indicator-inline {
  position: absolute;
  top: 0.25rem;
  right: 0.25rem;
  width: 16px;
  height: 16px;
  background: #d97706;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 0.6rem;
  animation: checkPop 0.3s ease-out;
  box-shadow: 0 2px 4px rgba(217, 119, 6, 0.4);
}

@keyframes checkPop {
  0% { transform: scale(0); opacity: 0; }
  50% { transform: scale(1.3); }
  100% { transform: scale(1); opacity: 1; }
}

/* === FILTRES MAGIC === */

.magic-colors-filter {
  min-width: 320px;
  flex: 1;
}

.magic-colors-grid {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
  justify-content: flex-start;
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
  font-size: 0.85rem;
  font-weight: 600;
  user-select: none;
  min-width: 85px;
  justify-content: center;
  position: relative;
  overflow: hidden;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  background: white;
}

.magic-color-checkbox::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
  transition: left 0.6s ease;
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
  animation: magicGlow 2s ease-in-out infinite alternate;
}

@keyframes magicGlow {
  0% { opacity: 0.3; }
  100% { opacity: 0.6; }
}

.magic-color-checkbox i {
  font-size: 0.75rem;
  animation: checkPop 0.3s ease-out;
}

/* Actions filtres */
.filters-actions-group {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 0.75rem;
  padding-top: 26px;
}

:deep(.filter-sort-dropdown) {
  min-width: 160px !important;
  border: 2px solid !important;
  border-radius: 8px !important;
  background: white !important;
  font-size: 0.85rem !important;
  height: 42px !important;
  transition: all var(--transition-fast) !important;
}

/* Thématisation des dropdowns */
.hearthstone-filters-panel :deep(.filter-sort-dropdown) {
  border-color: #d97706 !important;
}

.hearthstone-filters-panel :deep(.filter-sort-dropdown:hover) {
  border-color: #b45309 !important;
  box-shadow: 0 0 0 3px rgba(217, 119, 6, 0.1) !important;
}

.magic-filters-panel :deep(.filter-sort-dropdown) {
  border-color: #7c3aed !important;
}

.magic-filters-panel :deep(.filter-sort-dropdown:hover) {
  border-color: #5b21b6 !important;
  box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1) !important;
}

.pokemon-filters-panel :deep(.filter-sort-dropdown) {
  border-color: #ffc107 !important;
}

.pokemon-filters-panel :deep(.filter-sort-dropdown:hover) {
  border-color: #f59e0b !important;
  box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.1) !important;
}

:deep(.reset-filters-btn) {
  width: 42px !important;
  height: 42px !important;
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  background: none !important;
  border: 2px solid var(--surface-300) !important;
  color: var(--text-secondary) !important;
  border-radius: 50% !important;
  transition: all var(--transition-fast) !important;
  opacity: 0.7;
}

:deep(.reset-filters-btn:hover) {
  background: #ef4444 !important;
  color: white !important;
  transform: scale(1.05) rotate(90deg) !important;
  opacity: 1;
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3) !important;
  border-color: #ef4444 !important;
}

/* Grid de decks */
.decks-grid {
  padding: 2rem;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
  gap: 1.5rem;
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

/* Responsive */
@media (max-width: 1024px) {
  .game-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .game-title-area {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .game-stats-integrated {
    align-self: stretch;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.75rem;
  }
  
  .game-filters-panel {
    padding: 1.5rem;
  }
  
  .filters-main-row {
    flex-direction: column;
    gap: 1.5rem;
  }
  
  .classes-inline-row {
    gap: 0.5rem;
  }
  
  .class-checkbox-inline {
    min-width: 90px;
    padding: 0.4rem 0.6rem;
  }
  
  .class-checkbox-icon-inline {
    width: 28px;
    height: 28px;
  }
  
  .magic-colors-grid {
    gap: 0.5rem;
  }
  
  .magic-color-checkbox {
    min-width: 75px;
    padding: 0.6rem 0.8rem;
    font-size: 0.8rem;
  }
  
  .decks-grid {
    padding: 1.5rem;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1rem;
  }
}

@media (max-width: 768px) {
  .game-header {
    padding: 1rem 1.5rem;
  }
  
  .game-filters-panel {
    padding: 1rem 1.5rem;
    gap: 1rem;
  }
  
  .filter-search-wrapper {
    max-width: 100%;
  }
  
  .stat-item {
    flex: 1;
    min-width: 60px;
    justify-content: center;
    padding: 0.5rem 0.25rem;
  }
  
  .classes-inline-row {
    justify-content: flex-start;
    gap: 0.4rem;
  }
  
  .class-checkbox-inline {
    min-width: 80px;
    padding: 0.35rem 0.5rem;
  }
  
  .class-checkbox-icon-inline {
    width: 24px;
    height: 24px;
  }
  
  .class-checkbox-name-inline {
    font-size: 0.65rem;
  }
  
  .magic-colors-grid {
    justify-content: center;
    gap: 0.4rem;
  }
  
  .magic-color-checkbox {
    min-width: 70px;
    padding: 0.5rem 0.7rem;
    font-size: 0.75rem;
  }
  
  .format-toggle-buttons {
    flex-direction: column;
  }
  
  .format-toggle-btn {
    padding: 0.6rem;
    font-size: 0.8rem;
  }
  
  .decks-grid {
    grid-template-columns: 1fr;
    padding: 1rem;
  }
}

@media (max-width: 480px) {
  .game-filters-panel {
    padding: 1rem;
  }
  
  .stat-item {
    justify-content: center;
    text-align: center;
  }
  
  .classes-inline-row {
    gap: 0.25rem;
  }
  
  .class-checkbox-inline {
    min-width: 70px;
    padding: 0.3rem 0.4rem;
  }
  
  .class-checkbox-icon-inline {
    width: 20px;
    height: 20px;
  }
  
  .class-checkbox-name-inline {
    font-size: 0.6rem;
  }
  
  .magic-colors-grid {
    gap: 0.3rem;
  }
  
  .magic-color-checkbox {
    min-width: 60px;
    padding: 0.4rem 0.6rem;
    font-size: 0.7rem;
  }
  
  .dust-range-display {
    font-size: 0.7rem;
  }
}

.deck-with-creator {
  display: flex;
  flex-direction: column;
  gap: 0;
  border-radius: var(--border-radius-large);
  overflow: hidden;
  box-shadow: var(--shadow-medium);
  transition: all var(--transition-medium);
  background: white;
  border: 1px solid var(--surface-200);
}

.deck-with-creator:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-large);
}

.deck-creator-header {
  background: linear-gradient(135deg, var(--surface-50), var(--surface-100));
  border-bottom: 1px solid var(--surface-200);
  padding: 1rem 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
}

.creator-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex: 1;
}

.creator-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  position: relative;
  flex-shrink: 0;
  transition: all var(--transition-fast);
}

.creator-avatar-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 50%;
}

.creator-avatar-fallback {
  background: var(--primary-light);
  color: white;
  font-weight: 700;
  font-size: 1.1rem;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
}

.creator-details {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  flex: 1;
}

.creator-name {
  font-weight: 600;
  color: var(--text-primary);
  font-size: 0.95rem;
  line-height: 1.2;
  transition: color var(--transition-fast);
}

.creator-label {
  font-size: 0.75rem;
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-weight: 500;
}

.creator-label.imported {
  color: #8b7355;
  font-style: italic;
}

.deck-publish-date {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--text-secondary);
  font-size: 0.8rem;
  font-weight: 500;
  white-space: nowrap;
  flex-shrink: 0;
}

.deck-publish-date .pi {
  font-size: 0.75rem;
  color: var(--primary);
}

/* Styles cliquables pour navigation profil */
.clickable-avatar {
  cursor: pointer;
  transition: all var(--transition-fast);
  border-radius: 50%;
  position: relative;
}

.clickable-avatar:hover {
  transform: scale(1.05);
  box-shadow: 0 0 0 3px rgba(38, 166, 154, 0.3);
}

.clickable-name {
  cursor: pointer;
  transition: color var(--transition-fast);
  text-decoration: none;
  border-radius: var(--border-radius-small);
  padding: 0.125rem 0.25rem;
  margin: -0.125rem -0.25rem;
}

.clickable-name:hover {
  color: var(--primary) !important;
  background: rgba(38, 166, 154, 0.1);
}

.clickable-avatar:hover::after {
  content: '';
  position: absolute;
  top: -2px;
  left: -2px;
  right: -2px;
  bottom: -2px;
  border: 2px solid var(--primary);
  border-radius: 50%;
  opacity: 0.6;
}

/* Override de la grid pour les decks avec créateurs */
.game-section .decks-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
  gap: 2rem;
  padding: 2rem;
}

/* Responsive pour créateurs */
@media (max-width: 768px) {
  .deck-creator-header {
    padding: 0.75rem 1rem;
    flex-direction: column;
    align-items: flex-start;
    gap: 0.75rem;
  }
  
  .creator-info {
    gap: 0.5rem;
  }
  
  .creator-avatar {
    width: 32px;
    height: 32px;
  }
  
  .creator-avatar-fallback {
    font-size: 0.9rem;
  }
  
  .creator-name {
    font-size: 0.9rem;
  }
  
  .creator-label {
    font-size: 0.7rem;
  }
  
  .deck-publish-date {
    font-size: 0.75rem;
    align-self: flex-end;
  }
  
  .clickable-avatar:hover {
    transform: scale(1.02);
  }
  
  .clickable-avatar:hover::after {
    display: none;
  }
  
  .game-section .decks-grid {
    grid-template-columns: 1fr;
    gap: 1.5rem;
    padding: 1rem;
  }
}

@media (max-width: 480px) {
  .deck-creator-header {
    padding: 0.5rem 0.75rem;
  }
  
  .creator-details {
    gap: 0.125rem;
  }
  
  .deck-publish-date {
    margin-top: 0.5rem;
    align-self: flex-start;
  }
}
</style>
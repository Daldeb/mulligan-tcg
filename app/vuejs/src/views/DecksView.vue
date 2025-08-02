<template>
  <div class="decks-page">
    <div class="container">
      
      <!-- Header avec actions -->
      <div class="page-header slide-in-down">
        <div class="header-content">
          <div class="header-left">
            <h1 class="page-title">
              <i class="pi pi-clone"></i>
              Decks Communautaires
            </h1>
            <p class="page-subtitle">
              Découvrez les meilleurs decks de la communauté
            </p>
          </div>
          <div class="header-actions">
            <Button 
              label="Créer un deck"
              icon="pi pi-plus"
              class="emerald-button primary"
              @click="showCreateModal = true"
            />
          </div>
        </div>
      </div>

      <!-- Recherche globale -->
      <div class="global-search slide-in-up">
        <Card class="gaming-card search-card">
          <template #content>
            <div class="search-content">
              <div class="search-wrapper">
                <InputText 
                  v-model="globalSearch"
                  placeholder="Rechercher un deck dans tous les jeux..."
                  class="global-search-input"
                />
                <i class="pi pi-search search-icon"></i>
              </div>
              <div class="search-stats">
                <span class="stats-text">{{ totalDecksCount }} decks disponibles</span>
              </div>
            </div>
          </template>
        </Card>
      </div>

      <!-- Sections par jeu -->
      <div class="games-sections">
        
        <!-- Section Hearthstone -->
        <div 
          v-if="shouldShowGame('hearthstone')" 
          class="game-section hearthstone-section slide-in-up"
        >
          <div class="game-header">
            <div class="game-title-area">
              <div class="game-badge hearthstone">
                <i class="game-icon">🃏</i>
                <span class="game-name">Hearthstone</span>
              </div>
              <div class="game-stats">
                <span class="deck-count">{{ getGameDeckCount('hearthstone') }} decks</span>
                <span class="separator">•</span>
                <span class="formats">Standard • Wild</span>
              </div>
            </div>
            <div class="game-filters">
              <Button 
                :label="hearthstoneFilters.format === 'all' ? 'Tous' : hearthstoneFilters.format"
                icon="pi pi-filter"
                class="format-filter-btn"
                @click="toggleHearthstoneFormat"
              />
              <Button 
                icon="pi pi-sort-alt"
                class="sort-btn"
                @click="toggleHearthstoneSort"
                v-tooltip="'Trier par popularité'"
              />
            </div>
          </div>
          
          <div class="decks-grid">
            <!-- Temporairement remplacé par des Card basiques -->
            <Card 
              v-for="deck in getFilteredHearthstoneDecks" 
              :key="`hs-${deck.id}`"
              class="deck-card gaming-card hover-lift"
              @click="viewDeck(deck)"
            >
              <template #content>
                <div class="deck-content">
                  <h3 class="deck-name">{{ deck.name }}</h3>
                  <p class="deck-author">Par {{ deck.author }}</p>
                  <div class="deck-meta">
                    <span class="format-badge">{{ deck.format }}</span>
                    <span class="likes">{{ deck.likes }} ❤️</span>
                  </div>
                </div>
              </template>
            </Card>
          </div>
          
          <div v-if="getFilteredHearthstoneDecks.length === 0" class="empty-game-section">
            <i class="pi pi-clone empty-icon"></i>
            <p>Aucun deck Hearthstone trouvé</p>
          </div>
        </div>

        <!-- Section Magic -->
        <div 
          v-if="shouldShowGame('magic')" 
          class="game-section magic-section slide-in-up"
        >
          <div class="game-header">
            <div class="game-title-area">
              <div class="game-badge magic">
                <i class="game-icon">🎴</i>
                <span class="game-name">Magic: The Gathering</span>
              </div>
              <div class="game-stats">
                <span class="deck-count">{{ getGameDeckCount('magic') }} decks</span>
                <span class="separator">•</span>
                <span class="formats">Standard • Modern • Legacy</span>
              </div>
            </div>
            <div class="game-filters">
              <Button 
                :label="magicFilters.format === 'all' ? 'Tous' : magicFilters.format"
                icon="pi pi-filter"
                class="format-filter-btn"
                @click="toggleMagicFormat"
              />
              <Button 
                icon="pi pi-sort-alt"
                class="sort-btn"
                @click="toggleMagicSort"
                v-tooltip="'Trier par popularité'"
              />
            </div>
          </div>
          
          <div class="decks-grid">
            <!-- Temporairement remplacé par des Card basiques -->
            <Card 
              v-for="deck in getFilteredMagicDecks" 
              :key="`mtg-${deck.id}`"
              class="deck-card gaming-card hover-lift"
              @click="viewDeck(deck)"
            >
              <template #content>
                <div class="deck-content">
                  <h3 class="deck-name">{{ deck.name }}</h3>
                  <p class="deck-author">Par {{ deck.author }}</p>
                  <div class="deck-meta">
                    <span class="format-badge">{{ deck.format }}</span>
                    <span class="likes">{{ deck.likes }} ❤️</span>
                  </div>
                </div>
              </template>
            </Card>
          </div>
          
          <div v-if="getFilteredMagicDecks.length === 0" class="empty-game-section">
            <i class="pi pi-clone empty-icon"></i>
            <p>Aucun deck Magic trouvé</p>
          </div>
        </div>

        <!-- Section Pokemon -->
        <div 
          v-if="shouldShowGame('pokemon')" 
          class="game-section pokemon-section slide-in-up"
        >
          <div class="game-header">
            <div class="game-title-area">
              <div class="game-badge pokemon">
                <i class="game-icon">⚡</i>
                <span class="game-name">Pokemon TCG</span>
              </div>
              <div class="game-stats">
                <span class="deck-count">{{ getGameDeckCount('pokemon') }} decks</span>
                <span class="separator">•</span>
                <span class="formats">Standard • Expanded</span>
              </div>
            </div>
            <div class="game-filters">
              <Button 
                :label="pokemonFilters.format === 'all' ? 'Tous' : pokemonFilters.format"
                icon="pi pi-filter"
                class="format-filter-btn"
                @click="togglePokemonFormat"
              />
              <Button 
                icon="pi pi-sort-alt"
                class="sort-btn"
                @click="togglePokemonSort"
                v-tooltip="'Trier par popularité'"
              />
            </div>
          </div>
          
          <div class="decks-grid">
            <!-- Temporairement remplacé par des Card basiques -->
            <Card 
              v-for="deck in getFilteredPokemonDecks" 
              :key="`pkmn-${deck.id}`"
              class="deck-card gaming-card hover-lift"
              @click="viewDeck(deck)"
            >
              <template #content>
                <div class="deck-content">
                  <h3 class="deck-name">{{ deck.name }}</h3>
                  <p class="deck-author">Par {{ deck.author }}</p>
                  <div class="deck-meta">
                    <span class="format-badge">{{ deck.format }}</span>
                    <span class="likes">{{ deck.likes }} ❤️</span>
                  </div>
                </div>
              </template>
            </Card>
          </div>
          
          <div v-if="getFilteredPokemonDecks.length === 0" class="empty-game-section">
            <i class="pi pi-clone empty-icon"></i>
            <p>Aucun deck Pokemon trouvé</p>
          </div>
        </div>

      </div>

      <!-- État de chargement global -->
      <div v-if="isLoading" class="loading-state">
        <Card class="gaming-card loading-card">
          <template #content>
            <div class="loading-content">
              <div class="emerald-spinner"></div>
              <p>Chargement des decks...</p>
            </div>
          </template>
        </Card>
      </div>

      <!-- État vide global -->
      <div v-if="!isLoading && totalDecksCount === 0" class="empty-state">
        <Card class="gaming-card empty-card">
          <template #content>
            <div class="empty-content">
              <i class="pi pi-clone empty-icon"></i>
              <h3 class="empty-title">Aucun deck disponible</h3>
              <p class="empty-description">
                Soyez le premier à partager un deck avec la communauté !
              </p>
              <Button 
                label="Créer le premier deck"
                icon="pi pi-plus"
                class="emerald-btn"
                @click="createNewDeck"
              />
            </div>
          </template>
        </Card>
      </div>

    </div>

    <!-- Modale de création de deck - Version Emerald Design -->
    <Dialog 
      v-model:visible="showCreateModal"
      modal 
      :closable="true"
      :style="{ width: '100%', maxWidth: '540px' }"
      :breakpoints="{ '960px': '85vw', '640px': '95vw' }"
      class="emerald-modal"
    >
      <template #header>
        <div class="modal-header-content">
          <i class="pi pi-plus header-icon"></i>
          <span class="header-title">Créer un nouveau deck</span>
        </div>
      </template>

      <div class="modal-body">
        <form @submit.prevent="createDeck" class="emerald-form">
          
          <!-- Titre du deck -->
          <div class="field-group">
            <label for="deck-title" class="field-label">Titre du deck *</label>
            <InputText
              id="deck-title"
              v-model="deckData.title"
              placeholder="Ex: Deck Aggro Chasseur"
              class="emerald-input"
              :class="{ 'error': errors.title }"
              @input="errors.title = ''"
            />
            <small v-if="errors.title" class="field-error">{{ errors.title }}</small>
          </div>

          <!-- Sélection du jeu -->
          <div class="field-group">
            <label for="deck-game" class="field-label">Jeu *</label>
            <Dropdown
              id="deck-game"
              v-model="deckData.selectedGame"
              :options="availableGames"
              option-label="name"
              option-value="id"
              placeholder="Sélectionner un jeu"
              class="emerald-dropdown"
              :class="{ 'error': errors.game }"
              @change="onGameChange"
            >
              <template #option="{ option }">
                <div class="game-option">
                  <div 
                    class="game-color-badge"
                    :style="{ backgroundColor: option.primaryColor }"
                  ></div>
                  <span class="game-name">{{ option.name }}</span>
                </div>
              </template>
              <template #value="{ value }">
                <div v-if="value" class="selected-game">
                  <div 
                    class="game-color-badge"
                    :style="{ backgroundColor: getSelectedGameColor(value) }"
                  ></div>
                  <span>{{ getSelectedGameName(value) }}</span>
                </div>
                <span v-else class="placeholder-text">Sélectionner un jeu</span>
              </template>
            </Dropdown>
            <small v-if="errors.game" class="field-error">{{ errors.game }}</small>
          </div>

          <!-- Sélection du format -->
          <div class="field-group">
            <label for="deck-format" class="field-label">Format *</label>
            <Dropdown
              id="deck-format"
              v-model="deckData.selectedFormat"
              :options="availableFormats"
              option-label="name"
              option-value="id"
              placeholder="Sélectionner un format"
              class="emerald-dropdown"
              :class="{ 'error': errors.format }"
              :disabled="!deckData.selectedGame"
            >
              <template #option="{ option }">
                <div class="format-option">
                  <div class="format-main">
                    <span class="format-name">{{ option.name }}</span>
                  </div>
                  <div v-if="option.description" class="format-description">
                    {{ option.description }}
                  </div>
                </div>
              </template>
            </Dropdown>
            <small v-if="errors.format" class="field-error">{{ errors.format }}</small>
            <small v-else-if="!deckData.selectedGame" class="field-hint">
              Sélectionnez d'abord un jeu pour voir les formats disponibles
            </small>
          </div>

          <!-- Description optionnelle -->
          <div class="field-group">
            <label for="deck-description" class="field-label">Description (optionnelle)</label>
            <Textarea
              id="deck-description"
              v-model="deckData.description"
              placeholder="Décrivez votre stratégie, les combos clés..."
              rows="3"
              class="emerald-textarea"
            />
          </div>

          <!-- Archetype optionnel -->
          <div class="field-group">
            <label for="deck-archetype" class="field-label">Archetype (optionnel)</label>
            <Dropdown
              id="deck-archetype"
              v-model="deckData.archetype"
              :options="getArchetypesForGame()"
              placeholder="Ex: Aggro, Control, Combo..."
              class="emerald-dropdown"
              :disabled="!deckData.selectedGame"
              editable
            />
            <small v-if="!deckData.selectedGame" class="field-hint">
              Les archetypes dépendent du jeu sélectionné
            </small>
          </div>

        </form>

        <!-- Actions de la modale -->
        <div class="modal-actions">
          <Button
            label="Annuler"
            icon="pi pi-times"
            class="emerald-outline-btn cancel"
            @click="showCreateModal = false"
          />
          <Button
            label="Créer le deck"
            icon="pi pi-check"
            class="emerald-button primary"
            @click="createDeck"
            :loading="isCreating"
            :disabled="!isFormValid"
          />
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useGameFilterStore } from '../stores/gameFilter'
import { useToast } from 'primevue/usetoast'
import { storeToRefs } from 'pinia'
import api from '../services/api'
import Dialog from 'primevue/dialog'
import Dropdown from 'primevue/dropdown' 
import Textarea from 'primevue/textarea'
// import DeckCard from '../components/decks/DeckCard.vue' // Temporairement commenté

// Stores et composables
const router = useRouter()
const authStore = useAuthStore()
const gameFilterStore = useGameFilterStore()
const toast = useToast()

// Récupération du state gameFilter
const { selectedGames } = storeToRefs(gameFilterStore)

// State principal
const allDecks = ref([])
const isLoading = ref(true)
const globalSearch = ref('')

// State modale (après tes variables existantes)
const showCreateModal = ref(false)
const isCreating = ref(false)
const availableGames = ref([])
const availableFormats = ref([])

const deckData = ref({
  title: '',
  description: '',
  selectedGame: null,
  selectedFormat: null,
  archetype: ''
})

const errors = ref({
  title: '',
  game: '',
  format: ''
})

// Filtres par jeu (état local à la vue)
const hearthstoneFilters = ref({
  format: 'all', // all, standard, wild
  sort: 'popularity' // popularity, recent, winrate
})

const magicFilters = ref({
  format: 'all', // all, standard, modern, legacy
  sort: 'popularity'
})

const pokemonFilters = ref({
  format: 'all', // all, standard, expanded
  sort: 'popularity'
})

// Computed - Décider quels jeux afficher
const shouldShowGame = (game) => {
  // Si aucun filtre global ou si le jeu est sélectionné
  return selectedGames.value.length === 0 || selectedGames.value.includes(getGameId(game))
}

const getGameId = (gameName) => {
  const gameIds = {
    'hearthstone': 1, // À adapter selon tes IDs en BDD
    'magic': 2,
    'pokemon': 3
  }
  return gameIds[gameName]
}

// Computed - Filtrage des decks par jeu
const getFilteredHearthstoneDecks = computed(() => {
  let decks = allDecks.value.filter(deck => deck.game === 'hearthstone')
  
  // Recherche globale
  if (globalSearch.value) {
    const query = globalSearch.value.toLowerCase()
    decks = decks.filter(deck => 
      deck.name.toLowerCase().includes(query) ||
      deck.description?.toLowerCase().includes(query) ||
      deck.author?.toLowerCase().includes(query)
    )
  }
  
  // Filtre format
  if (hearthstoneFilters.value.format !== 'all') {
    decks = decks.filter(deck => deck.format === hearthstoneFilters.value.format)
  }
  
  // Tri
  return sortDecks(decks, hearthstoneFilters.value.sort)
})

const getFilteredMagicDecks = computed(() => {
  let decks = allDecks.value.filter(deck => deck.game === 'magic')
  
  if (globalSearch.value) {
    const query = globalSearch.value.toLowerCase()
    decks = decks.filter(deck => 
      deck.name.toLowerCase().includes(query) ||
      deck.description?.toLowerCase().includes(query) ||
      deck.author?.toLowerCase().includes(query)
    )
  }
  
  if (magicFilters.value.format !== 'all') {
    decks = decks.filter(deck => deck.format === magicFilters.value.format)
  }
  
  return sortDecks(decks, magicFilters.value.sort)
})

const getFilteredPokemonDecks = computed(() => {
  let decks = allDecks.value.filter(deck => deck.game === 'pokemon')
  
  if (globalSearch.value) {
    const query = globalSearch.value.toLowerCase()
    decks = decks.filter(deck => 
      deck.name.toLowerCase().includes(query) ||
      deck.description?.toLowerCase().includes(query) ||
      deck.author?.toLowerCase().includes(query)
    )
  }
  
  if (pokemonFilters.value.format !== 'all') {
    decks = decks.filter(deck => deck.format === pokemonFilters.value.format)
  }
  
  return sortDecks(decks, pokemonFilters.value.sort)
})

const totalDecksCount = computed(() => {
  let total = 0
  if (shouldShowGame('hearthstone')) total += getFilteredHearthstoneDecks.value.length
  if (shouldShowGame('magic')) total += getFilteredMagicDecks.value.length
  if (shouldShowGame('pokemon')) total += getFilteredPokemonDecks.value.length
  return total
})

// Computed pour la modale
const isFormValid = computed(() => {
  return deckData.value.title.trim().length >= 3 &&
         deckData.value.selectedGame &&
         deckData.value.selectedFormat
})

// Archetypes par jeu (pour la modale)
const archetypes = {
  hearthstone: ['Aggro', 'Midrange', 'Control', 'Combo', 'Tempo', 'Big', 'Zoo', 'Burn', 'Mill'],
  pokemon: ['Aggro', 'Control', 'Combo', 'Toolbox', 'Stall', 'Beatdown', 'Engine', 'Disruption'],
  magic: ['Aggro', 'Control', 'Midrange', 'Combo', 'Ramp', 'Tribal', 'Voltron', 'Stax', 'Storm']
}

// Méthodes utilitaires
const getGameDeckCount = (game) => {
  return allDecks.value.filter(deck => deck.game === game).length
}

const sortDecks = (decks, sortType) => {
  switch (sortType) {
    case 'recent':
      return [...decks].sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt))
    case 'winrate':
      return [...decks].sort((a, b) => (b.winRate || 0) - (a.winRate || 0))
    case 'popularity':
    default:
      return [...decks].sort((a, b) => (b.likes || 0) - (a.likes || 0))
  }
}

// Méthodes utilitaires pour la modale
const getSelectedGameName = (gameId) => {
  const game = availableGames.value.find(g => g.id === gameId)
  return game ? game.name : ''
}

const getSelectedGameColor = (gameId) => {
  const game = availableGames.value.find(g => g.id === gameId)
  return game ? game.primaryColor : '#26a69a'
}

// Actions filtres
const toggleHearthstoneFormat = () => {
  const formats = ['all', 'standard', 'wild']
  const currentIndex = formats.indexOf(hearthstoneFilters.value.format)
  hearthstoneFilters.value.format = formats[(currentIndex + 1) % formats.length]
}

const toggleMagicFormat = () => {
  const formats = ['all', 'standard', 'modern', 'legacy']
  const currentIndex = formats.indexOf(magicFilters.value.format)
  magicFilters.value.format = formats[(currentIndex + 1) % formats.length]
}

const togglePokemonFormat = () => {
  const formats = ['all', 'standard', 'expanded']
  const currentIndex = formats.indexOf(pokemonFilters.value.format)
  pokemonFilters.value.format = formats[(currentIndex + 1) % formats.length]
}

const toggleHearthstoneSort = () => {
  const sorts = ['popularity', 'recent', 'winrate']
  const currentIndex = sorts.indexOf(hearthstoneFilters.value.sort)
  hearthstoneFilters.value.sort = sorts[(currentIndex + 1) % sorts.length]
}

const toggleMagicSort = () => {
  const sorts = ['popularity', 'recent', 'winrate']
  const currentIndex = sorts.indexOf(magicFilters.value.sort)
  magicFilters.value.sort = sorts[(currentIndex + 1) % sorts.length]
}

const togglePokemonSort = () => {
  const sorts = ['popularity', 'recent', 'winrate']
  const currentIndex = sorts.indexOf(pokemonFilters.value.sort)
  pokemonFilters.value.sort = sorts[(currentIndex + 1) % sorts.length]
}

// Actions principales
const loadCommunityDecks = async () => {
  try {
    isLoading.value = true
    const response = await api.get('/api/decks/community')
    allDecks.value = response.data
  } catch (error) {
    console.error('Erreur chargement decks communautaires:', error)
    // Charger données de test
    loadMockDecks()
  } finally {
    isLoading.value = false
  }
}

const createNewDeck = () => {
  // Ouvrir la modale au lieu de naviguer
  showCreateModal.value = true
}

const viewDeck = (deck) => {
  // Navigation vers la vue détaillée du deck
  router.push(`/decks/${deck.id}`)
}

const editDeck = (deck) => {
  // Vérifier si l'utilisateur peut éditer (propriétaire)
  if (deck.authorId === authStore.user?.id) {
    router.push(`/decks/edit/${deck.id}`)
  } else {
    toast.add({
      severity: 'warn',
      summary: 'Non autorisé',
      detail: 'Vous ne pouvez modifier que vos propres decks',
      life: 3000
    })
  }
}

const deleteDeck = async (deck) => {
  // Vérifier permissions
  if (deck.authorId !== authStore.user?.id) {
    toast.add({
      severity: 'warn',
      summary: 'Non autorisé',
      detail: 'Vous ne pouvez supprimer que vos propres decks',
      life: 3000
    })
    return
  }

  // Confirmation et suppression
  if (confirm(`Supprimer le deck "${deck.name}" ?`)) {
    try {
      await api.delete(`/api/decks/${deck.id}`)
      allDecks.value = allDecks.value.filter(d => d.id !== deck.id)
      toast.add({
        severity: 'success',
        summary: 'Deck supprimé',
        detail: `"${deck.name}" a été supprimé`,
        life: 3000
      })
    } catch (error) {
      toast.add({
        severity: 'error',
        summary: 'Erreur',
        detail: 'Impossible de supprimer le deck',
        life: 3000
      })
    }
  }
}

// Methods pour la modale
const loadGames = async () => {
  try {
    const response = await api.get('/api/games')
    if (response.data.success) {
      availableGames.value = response.data.data
      
      // Pré-sélectionner si user a 1 seul jeu préféré
      if (selectedGames.value?.length === 1) {
        deckData.value.selectedGame = selectedGames.value[0]
        await loadFormatsForGame(selectedGames.value[0])
      }
    }
  } catch (error) {
    console.error('Erreur chargement jeux:', error)
  }
}

const loadFormatsForGame = async (gameId) => {
  if (!gameId) {
    availableFormats.value = []
    return
  }
  
  try {
    const response = await api.get(`/api/games/${gameId}/formats`)
    if (response.data.success) {
      availableFormats.value = response.data.data
    }
  } catch (error) {
    console.error('Erreur chargement formats:', error)
    availableFormats.value = []
  }
}

const onGameChange = () => {
  deckData.value.selectedFormat = null
  deckData.value.archetype = ''
  errors.value.game = ''
  
  if (deckData.value.selectedGame) {
    loadFormatsForGame(deckData.value.selectedGame)
  }
}

const getArchetypesForGame = () => {
  if (!deckData.value.selectedGame) return []
  const game = availableGames.value.find(g => g.id === deckData.value.selectedGame)
  return game ? archetypes[game.slug] || [] : []
}

const validateForm = () => {
  errors.value = { title: '', game: '', format: '' }
  let isValid = true

  if (!deckData.value.title.trim()) {
    errors.value.title = 'Le titre est requis'
    isValid = false
  } else if (deckData.value.title.trim().length < 3) {
    errors.value.title = 'Le titre doit faire au moins 3 caractères'
    isValid = false
  }

  if (!deckData.value.selectedGame) {
    errors.value.game = 'Veuillez sélectionner un jeu'
    isValid = false
  }

  if (!deckData.value.selectedFormat) {
    errors.value.format = 'Veuillez sélectionner un format'
    isValid = false
  }

  return isValid
}

const createDeck = async () => {
  if (!validateForm()) return

  isCreating.value = true

  try {
    // Préparer les données pour l'API
    const deckPayload = {
      title: deckData.value.title.trim(),
      gameId: deckData.value.selectedGame,
      formatId: deckData.value.selectedFormat,
      description: deckData.value.description?.trim() || null,
      archetype: deckData.value.archetype?.trim() || null
    }

    console.log('🎯 Création deck avec payload:', deckPayload)

    // Appel API pour créer le deck en BDD
    const response = await api.post('/api/decks', deckPayload)

    if (response.data.success) {
      const deckInfo = response.data.data
      
      console.log('✅ Deck créé:', deckInfo)

      // Fermer la modale
      showCreateModal.value = false
      
      // Reset du formulaire
      deckData.value = {
        title: '',
        description: '',
        selectedGame: null,
        selectedFormat: null,
        archetype: ''
      }
      
      // Notification de succès
      toast.add({
        severity: 'success',
        summary: 'Deck créé !',
        detail: `"${deckInfo.title}" est prêt à être édité`,
        life: 3000
      })

      // Redirection vers l'éditeur avec URL propre
      const editUrl = `/edition/${deckInfo.gameSlug}/${deckInfo.formatSlug}/${deckInfo.slug}`
      console.log('🚀 Redirection vers:', editUrl)
      
      await router.push(editUrl)

    } else {
      // Gestion des erreurs retournées par l'API
      const errorMessage = response.data.message || 'Erreur lors de la création'
      
      toast.add({
        severity: 'error',
        summary: 'Erreur de création',
        detail: errorMessage,
        life: 4000
      })

      // Afficher les erreurs de validation si présentes
      if (response.data.errors) {
        response.data.errors.forEach(err => {
          console.error('Erreur validation:', err)
        })
      }
    }

  } catch (error) {
    console.error('💥 Erreur création deck:', error)
    
    // Gestion d'erreurs détaillée
    let errorMessage = 'Erreur lors de la création du deck'
    
    if (error.response?.status === 400) {
      errorMessage = error.response.data?.message || 'Données invalides'
    } else if (error.response?.status === 401) {
      errorMessage = 'Vous devez être connecté pour créer un deck'
    } else if (error.response?.status === 403) {
      errorMessage = 'Vous n\'avez pas les permissions nécessaires'
    } else if (error.response?.status >= 500) {
      errorMessage = 'Erreur serveur, veuillez réessayer'
    }

    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: errorMessage,
      life: 4000
    })
  } finally {
    isCreating.value = false
  }
}

const copyDeck = (deck) => {
  // Copier le deck vers l'éditeur
  router.push(`/decks/create?copy=${deck.id}`)
}

// Données de test
const loadMockDecks = () => {
  allDecks.value = [
    {
      id: 1,
      name: 'Aggro Mage Burn',
      description: 'Deck agressif focalisé sur les dégâts directs et la rapidité',
      game: 'hearthstone',
      format: 'standard',
      author: 'ProPlayer42',
      authorId: 123,
      cardCount: 30,
      winRate: 73,
      likes: 156,
      views: 1247,
      createdAt: '2024-07-28T10:30:00Z',
      updatedAt: '2024-07-30T15:45:00Z'
    },
    {
      id: 2,
      name: 'Control Warrior Classic',
      description: 'Contrôle pur avec removal et late game puissant',
      game: 'hearthstone',
      format: 'wild',
      author: 'LegendMaster',
      authorId: 456,
      cardCount: 30,
      winRate: 68,
      likes: 234,
      views: 892,
      createdAt: '2024-07-25T14:20:00Z',
      updatedAt: '2024-07-29T09:15:00Z'
    },
    {
      id: 3,
      name: 'Pikachu Lightning Rush',
      description: 'Deck Pokemon électrique ultra-rapide avec Pikachu VMAX',
      game: 'pokemon',
      format: 'standard',
      author: 'PokeMaster',
      authorId: 789,
      cardCount: 60,
      winRate: 65,
      likes: 89,
      views: 432,
      createdAt: '2024-07-26T16:45:00Z',
      updatedAt: '2024-07-28T11:30:00Z'
    },
    {
      id: 4,
      name: 'Mono Blue Control',
      description: 'Contrôle Magic mono-bleu avec counter spells',
      game: 'magic',
      format: 'standard',
      author: 'BlueWizard',
      authorId: 321,
      cardCount: 60,
      winRate: 71,
      likes: 178,
      views: 654,
      createdAt: '2024-07-24T12:00:00Z',
      updatedAt: '2024-07-27T18:20:00Z'
    },
    {
      id: 5,
      name: 'Miracle Rogue Wild',
      description: 'Combo deck classique avec Gadgetzan Auctioneer',
      game: 'hearthstone',
      format: 'wild',
      author: 'ComboKing',
      authorId: 654,
      cardCount: 30,
      winRate: 62,
      likes: 267,
      views: 1156,
      createdAt: '2024-07-22T08:15:00Z',
      updatedAt: '2024-07-26T20:45:00Z'
    },
    {
      id: 6,
      name: 'Charizard Fire Deck',
      description: 'Deck Pokemon feu avec Charizard GX et support',
      game: 'pokemon',
      format: 'expanded',
      author: 'FireTrainer',
      authorId: 987,
      cardCount: 60,
      winRate: 58,
      likes: 134,
      views: 723,
      createdAt: '2024-07-21T11:30:00Z',
      updatedAt: '2024-07-25T14:10:00Z'
    }
  ]
  isLoading.value = false
}

// Lifecycle
onMounted(async () => {
  console.log('🃏 DecksView montée - Filtres globaux:', selectedGames.value)
  await loadCommunityDecks()
  await loadGames()
})

// Watcher pour réagir aux changements de filtre global
watch(selectedGames, (newGames) => {
  console.log('🔄 Filtres globaux changés:', newGames)
  // Ici on pourrait recharger les decks si nécessaire
}, { deep: true })
</script>

<style scoped>
/* === DECKS COMMUNAUTAIRES EMERALD GAMING === */

.decks-page {
  min-height: calc(100vh - 140px);
  background: var(--surface-gradient);
  padding: 2rem 0;
}

.container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 2rem;
}

/* Page header */
.page-header {
  margin-bottom: 2rem;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 2rem;
}

.header-left {
  flex: 1;
}

.page-title {
  font-size: 2.5rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0 0 0.5rem 0;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.page-title i {
  color: var(--primary);
}

.page-subtitle {
  font-size: 1.1rem;
  color: var(--text-secondary);
  margin: 0;
}

.header-actions {
  flex-shrink: 0;
}

/* Global search */
.global-search {
  margin-bottom: 2rem;
}

.search-card {
  border: none;
  box-shadow: var(--shadow-small);
}

.search-content {
  padding: 1.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 2rem;
}

.search-wrapper {
  position: relative;
  flex: 1;
  max-width: 500px;
}

:deep(.global-search-input) {
  width: 100% !important;
  padding: 1rem 1rem 1rem 3.5rem !important;
  border: 2px solid var(--surface-300) !important;
  border-radius: 30px !important;
  font-size: 1rem !important;
  background: var(--surface-100) !important;
}

:deep(.global-search-input:focus) {
  border-color: var(--primary) !important;
  background: white !important;
  box-shadow: 0 0 0 3px rgba(38, 166, 154, 0.1) !important;
}

.search-icon {
  position: absolute;
  left: 1.25rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-secondary);
  font-size: 1.1rem;
  pointer-events: none;
}

.search-stats {
  color: var(--text-secondary);
  font-size: 0.9rem;
}

/* Game sections */
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
  background: linear-gradient(90deg, var(--primary), var(--primary-dark));
}

.magic-section::before {
  background: linear-gradient(90deg, #8b4513, #5d2f02);
}

.pokemon-section::before {
  background: linear-gradient(90deg, #ffc107, #ff6f00);
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
  background: rgba(38, 166, 154, 0.1);
  color: var(--primary);
  border: 2px solid rgba(38, 166, 154, 0.3);
}

.game-badge.magic {
  background: rgba(139, 69, 19, 0.1);
  color: #8b4513;
  border: 2px solid rgba(139, 69, 19, 0.3);
}

.game-badge.pokemon {
  background: rgba(255, 193, 7, 0.1);
  color: #ff6f00;
  border: 2px solid rgba(255, 193, 7, 0.3);
}

.game-icon {
  font-size: 1.5rem;
}

.game-stats {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  color: var(--text-secondary);
  font-size: 0.9rem;
}

.deck-count {
  font-weight: 600;
  color: var(--text-primary);
}

.separator {
  color: var(--surface-400);
}

.game-filters {
  display: flex;
  gap: 0.75rem;
}

:deep(.format-filter-btn),
:deep(.sort-btn) {
  background: white !important;
  border: 2px solid var(--surface-300) !important;
  color: var(--text-secondary) !important;
  padding: 0.5rem 1rem !important;
  border-radius: 20px !important;
  font-size: 0.85rem !important;
  transition: all var(--transition-fast) !important;
}

:deep(.format-filter-btn:hover),
:deep(.sort-btn:hover) {
  border-color: var(--primary) !important;
  color: var(--primary) !important;
  background: rgba(38, 166, 154, 0.1) !important;
}

/* Decks grid */
.decks-grid {
  padding: 2rem;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 1.5rem;
}

/* Empty states */
.empty-game-section {
  padding: 3rem 2rem;
  text-align: center;
  color: var(--text-secondary);
}

.empty-game-section .empty-icon {
  font-size: 2.5rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

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
  margin: 0 0 2rem 0;
  line-height: 1.5;
}

/* Deck cards basiques temporaires */
.deck-content {
  padding: 1.5rem;
  text-align: center;
}

.deck-name {
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0 0 0.5rem 0;
}

.deck-author {
  font-size: 0.85rem;
  color: var(--text-secondary);
  margin: 0 0 1rem 0;
}

.deck-meta {
  display: flex;
  justify-content: center;
  gap: 1rem;
  align-items: center;
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

.likes {
  font-size: 0.8rem;
  color: var(--text-secondary);
}

/* === MODAL EMERALD DECK CREATION === */

/* Header modal */
:deep(.emerald-modal .p-dialog) {
  border-radius: var(--border-radius-large) !important;
  box-shadow: var(--shadow-large) !important;
  border: 1px solid var(--surface-200) !important;
  overflow: hidden !important;
}

:deep(.emerald-modal .p-dialog-header) {
  background: var(--emerald-gradient) !important;
  color: var(--text-inverse) !important;
  padding: 1.5rem 2rem !important;
  border-bottom: none !important;
}

:deep(.emerald-modal .p-dialog-content) {
  padding: 0 !important;
  background: var(--surface) !important;
}

.modal-header-content {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  width: 100%;
}

.header-icon {
  font-size: 1.5rem;
  opacity: 0.9;
}

.header-title {
  font-size: 1.5rem;
  font-weight: 700;
  letter-spacing: 0.5px;
}

/* Body modal */
.modal-body {
  padding: 2rem;
}

/* Formulaire Emerald */
.emerald-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.field-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.field-label {
  font-weight: 500;
  color: var(--text-primary);
  font-size: 0.9rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* Inputs Emerald */
:deep(.emerald-input) {
  width: 100% !important;
  padding: 0.875rem 1rem !important;
  border: 2px solid var(--surface-300) !important;
  border-radius: var(--border-radius) !important;
  background: var(--surface) !important;
  color: var(--text-primary) !important;
  font-size: 0.95rem !important;
  transition: all var(--transition-fast) !important;
}

:deep(.emerald-input:focus) {
  border-color: var(--primary) !important;
  box-shadow: 0 0 0 3px rgba(38, 166, 154, 0.1) !important;
  background: white !important;
}

:deep(.emerald-input.error) {
  border-color: var(--accent) !important;
  box-shadow: 0 0 0 3px rgba(255, 87, 34, 0.1) !important;
}

:deep(.emerald-input::placeholder) {
  color: var(--text-secondary) !important;
  opacity: 0.7 !important;
}

/* Textarea Emerald */
:deep(.emerald-textarea) {
  width: 100% !important;
  padding: 0.875rem 1rem !important;
  border: 2px solid var(--surface-300) !important;
  border-radius: var(--border-radius) !important;
  background: var(--surface) !important;
  color: var(--text-primary) !important;
  font-size: 0.95rem !important;
  transition: all var(--transition-fast) !important;
  resize: vertical !important;
  min-height: 80px !important;
}

:deep(.emerald-textarea:focus) {
  border-color: var(--primary) !important;
  box-shadow: 0 0 0 3px rgba(38, 166, 154, 0.1) !important;
  background: white !important;
}

:deep(.emerald-textarea::placeholder) {
  color: var(--text-secondary) !important;
  opacity: 0.7 !important;
}

/* Dropdowns Emerald */
:deep(.emerald-dropdown) {
  width: 100% !important;
  border: 2px solid var(--surface-300) !important;
  border-radius: var(--border-radius) !important;
  background: var(--surface) !important;
  transition: all var(--transition-fast) !important;
}

:deep(.emerald-dropdown:not(.p-disabled):hover) {
  border-color: var(--primary-light) !important;
}

:deep(.emerald-dropdown.p-focus) {
  border-color: var(--primary) !important;
  box-shadow: 0 0 0 3px rgba(38, 166, 154, 0.1) !important;
  background: white !important;
}

:deep(.emerald-dropdown.error) {
  border-color: var(--accent) !important;
  box-shadow: 0 0 0 3px rgba(255, 87, 34, 0.1) !important;
}

:deep(.emerald-dropdown .p-dropdown-label) {
  padding: 0.875rem 1rem !important;
  color: var(--text-primary) !important;
  font-size: 0.95rem !important;
}

:deep(.emerald-dropdown .p-dropdown-label.p-placeholder) {
  color: var(--text-secondary) !important;
  opacity: 0.7 !important;
}

/* Options des dropdowns */
.game-option,
.selected-game {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem 0;
}

.game-color-badge {
  width: 16px;
  height: 16px;
  border-radius: 50%;
  flex-shrink: 0;
}

.game-name {
  font-weight: 500;
}

.placeholder-text {
  color: var(--text-secondary);
  opacity: 0.7;
}

.format-option {
  padding: 0.5rem 0;
}

.format-main {
  font-weight: 500;
  color: var(--text-primary);
}

.format-description {
  font-size: 0.8rem;
  color: var(--text-secondary);
  margin-top: 0.25rem;
  line-height: 1.3;
}

/* Messages d'erreur et d'aide */
.field-error {
  color: var(--accent);
  font-size: 0.8rem;
  font-weight: 500;
  margin-top: 0.25rem;
}

.field-hint {
  color: var(--text-secondary);
  font-size: 0.8rem;
  margin-top: 0.25rem;
  font-style: italic;
}

/* Actions de la modale */
.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding: 1.5rem 0 0 0;
  border-top: 1px solid var(--surface-200);
}

:deep(.modal-actions .p-button) {
  padding: 0.75rem 1.5rem !important;
  font-weight: 600 !important;
  border-radius: var(--border-radius) !important;
  transition: all var(--transition-fast) !important;
}

/* Responsive */
@media (max-width: 1024px) {
  .container {
    padding: 0 1rem;
  }
  
  .header-content {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .search-content {
    flex-direction: column;
    gap: 1rem;
  }
  
  .game-header {
    padding: 1rem 1.5rem;
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .game-title-area {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.75rem;
  }
  
  .decks-grid {
    padding: 1.5rem;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1rem;
  }
}

@media (max-width: 768px) {
  .decks-page {
    padding: 1rem 0;
  }
  
  .page-title {
    font-size: 2rem;
  }
  
  .game-badge {
    padding: 0.5rem 1rem;
    font-size: 1rem;
  }
  
  .game-icon {
    font-size: 1.25rem;
  }
  
  .decks-grid {
    grid-template-columns: 1fr;
    padding: 1rem;
  }
  
  .search-wrapper {
    max-width: none;
  }
  
  .game-filters {
    width: 100%;
    justify-content: space-between;
  }
}

@media (max-width: 640px) {
  .header-content {
    text-align: center;
  }
  
  .game-stats {
    flex-direction: column;
    gap: 0.25rem;
    text-align: center;
  }
  
  .separator {
    display: none;
  }
  
  .game-filters {
    flex-direction: column;
    gap: 0.5rem;
  }
  
  :deep(.format-filter-btn),
  :deep(.sort-btn) {
    width: 100% !important;
    justify-content: center !important;
  }

  /* Modal responsive */
  .modal-body {
    padding: 1.5rem;
  }
  
  :deep(.emerald-modal .p-dialog-header) {
    padding: 1rem 1.5rem !important;
  }
  
  .header-title {
    font-size: 1.25rem;
  }
  
  .emerald-form {
    gap: 1.25rem;
  }
  
  .modal-actions {
    flex-direction: column;
    gap: 0.75rem;
  }
  
  :deep(.modal-actions .p-button) {
    width: 100% !important;
    justify-content: center !important;
  }
}

/* Animations personnalisées */
.game-section {
  animation: slideInUp 0.6s ease-out;
}

.hearthstone-section {
  animation-delay: 0.1s;
}

.magic-section {
  animation-delay: 0.2s;
}

.pokemon-section {
  animation-delay: 0.3s;
}

/* Transitions fluides entre états */
.decks-grid {
  transition: all var(--transition-medium);
}

.game-section:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-medium);
}

/* Animation d'entrée modale */
:deep(.p-dialog-enter-active) {
  animation: emeraldModalEnter 0.4s ease-out;
}

@keyframes emeraldModalEnter {
  from {
    opacity: 0;
    transform: scale(0.9) translateY(-20px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}
</style>
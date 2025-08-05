<template>
  <div class="my-decks-page">
    <div class="container">
      
      <!-- Header avec actions -->
      <div class="page-header slide-in-down">
        <div class="header-content">
          <div class="header-left">
            <h1 class="page-title">
              <i class="pi pi-user"></i>
              Mes Decks
            </h1>
            <p class="page-subtitle">
              Gérez et organisez vos decks personnels
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

      <!-- Statistiques utilisateur -->
      <div class="user-stats slide-in-up">
        <Card class="gaming-card stats-card">
          <template #content>
            <div class="stats-content">
              <div class="stat-item">
                <div class="stat-value">{{ userDecks.length }}</div>
                <div class="stat-label">Decks créés</div>
              </div>
              <div class="stat-item">
                <div class="stat-value">{{ publicDecksCount }}</div>
                <div class="stat-label">Decks publics</div>
              </div>
              <div class="stat-item">
                <div class="stat-value">{{ totalLikes }}</div>
                <div class="stat-label">Likes reçus</div>
              </div>
              <div class="stat-item">
                <div class="stat-value">{{ averageViews }}</div>
                <div class="stat-label">Vues moyennes</div>
              </div>
            </div>
          </template>
        </Card>
      </div>

      <!-- Filtres et recherche -->
      <div class="deck-filters slide-in-up">
        <Card class="gaming-card filters-card">
          <template #content>
            <div class="filters-content">
              <div class="search-wrapper">
                <InputText 
                  v-model="searchQuery"
                  placeholder="Rechercher dans mes decks..."
                  class="search-input"
                />
                <i class="pi pi-search search-icon"></i>
              </div>
              <div class="filter-buttons">
                <Button 
                  :label="visibilityFilter === 'all' ? 'Tous' : visibilityFilter === 'public' ? 'Publics' : 'Privés'"
                  icon="pi pi-filter"
                  class="filter-btn"
                  @click="toggleVisibilityFilter"
                />
                <Button 
                  :label="sortBy === 'recent' ? 'Récents' : sortBy === 'likes' ? 'Populaires' : 'Alphabétique'"
                  icon="pi pi-sort-alt"
                  class="sort-btn"
                  @click="toggleSort"
                />
              </div>
            </div>
          </template>
        </Card>
      </div>

      <!-- Sections par jeu -->
      <div class="games-sections" v-if="!isLoading && userDecks.length > 0">
        
        <!-- Section Hearthstone -->
        <div v-if="getGameDecks('hearthstone').length > 0" class="game-section hearthstone-section slide-in-up">
          <div class="game-header">
            <div class="game-title-area">
              <div class="game-badge hearthstone">
                <i class="game-icon">🃏</i>
                <span class="game-name">Hearthstone</span>
              </div>
              <div class="game-stats">
                <span class="deck-count">{{ getGameDecks('hearthstone').length }} decks</span>
              </div>
            </div>
          </div>
          
          <div class="decks-grid">
          <HearthstoneCompactDeck 
            v-for="deck in filteredDecksByGame('hearthstone')" 
            :key="`my-hs-${deck.id}`"
            :deck="deck"
            context="my-decks"
            :current-user="authStore.user"
            @edit="editDeck"
            @delete="deleteDeck"
            @copyDeckcode="copyDeckcode"
          />
          </div>
        </div>

        <!-- Section Magic -->
        <div v-if="getGameDecks('magic').length > 0" class="game-section magic-section slide-in-up">
          <div class="game-header">
            <div class="game-title-area">
              <div class="game-badge magic">
                <i class="game-icon">🎴</i>
                <span class="game-name">Magic: The Gathering</span>
              </div>
              <div class="game-stats">
                <span class="deck-count">{{ getGameDecks('magic').length }} decks</span>
              </div>
            </div>
          </div>
          
          <div class="decks-grid">
          <MagicCompactDeck 
            v-for="deck in filteredDecksByGame('magic')" 
            :key="`my-magic-${deck.id}`"
            :deck="deck"
            context="my-decks"
            :current-user="authStore.user"
            @edit="editDeck"
            @delete="deleteDeck"
            @copyDeckcode="copyDeckcode"
          />
          </div>
        </div>

        <!-- Section Pokemon -->
        <div v-if="getGameDecks('pokemon').length > 0" class="game-section pokemon-section slide-in-up">
          <div class="game-header">
            <div class="game-title-area">
              <div class="game-badge pokemon">
                <i class="game-icon">⚡</i>
                <span class="game-name">Pokemon TCG</span>
              </div>
              <div class="game-stats">
                <span class="deck-count">{{ getGameDecks('pokemon').length }} decks</span>
              </div>
            </div>
          </div>
          
          <div class="decks-grid">
            <Card 
              v-for="deck in getGameDecks('pokemon')" 
              :key="`my-pkmn-${deck.id}`"
              class="deck-card gaming-card hover-lift"
            >
              <template #content>
                <div class="deck-content">
                  <div class="deck-header-info">
                    <h3 class="deck-name">{{ deck.name }}</h3>
                    <div class="deck-status">
                      <i :class="deck.isPublic ? 'pi pi-globe' : 'pi pi-lock'" 
                         :style="{ color: deck.isPublic ? 'var(--primary)' : 'var(--text-secondary)' }"
                         :title="deck.isPublic ? 'Public' : 'Privé'"></i>
                    </div>
                  </div>
                  <div class="deck-meta">
                    <span class="format-badge pokemon">{{ deck.format }}</span>
                  </div>
                  <div class="deck-stats-info">
                    <span class="likes">{{ deck.likes || 0 }} ❤️</span>
                    <span class="views">{{ deck.views || 0 }} 👁️</span>
                    <span class="cards">{{ deck.cardCount || 0 }}/60 cartes</span>
                  </div>
                  <div class="deck-actions">
                    <Button 
                      icon="pi pi-pencil"
                      class="edit-btn"
                      @click="editDeck(deck)"
                      v-tooltip="'Éditer'"
                      size="small"
                    />
                    <Button 
                      icon="pi pi-copy"
                      class="copy-btn"
                      @click="duplicateDeck(deck)"
                      v-tooltip="'Dupliquer'"
                      size="small"
                    />
                    <Button 
                      icon="pi pi-trash"
                      class="delete-btn"
                      @click="deleteDeck(deck)"
                      v-tooltip="'Supprimer'"
                      size="small"
                    />
                  </div>
                </div>
              </template>
            </Card>
          </div>
        </div>

      </div>

      <!-- État de chargement -->
      <div v-if="isLoading" class="loading-state">
        <Card class="gaming-card loading-card">
          <template #content>
            <div class="loading-content">
              <div class="emerald-spinner"></div>
              <p>Chargement de vos decks...</p>
            </div>
          </template>
        </Card>
      </div>

      <!-- État vide -->
      <div v-if="!isLoading && userDecks.length === 0" class="empty-state">
        <Card class="gaming-card empty-card">
          <template #content>
            <div class="empty-content">
              <i class="pi pi-clone empty-icon"></i>
              <h3 class="empty-title">Aucun deck créé</h3>
              <p class="empty-description">
                Commencez à créer vos premiers decks pour les voir apparaître ici !
              </p>
              <Button 
                label="Créer mon premier deck"
                icon="pi pi-plus"
                class="emerald-button primary"
                @click="showCreateModal = true"
              />
            </div>
          </template>
        </Card>
      </div>

    </div>

    <!-- Modale de création de deck -->
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

          <div class="field-group" v-if="deckData.selectedGame && getSelectedGameSlug(deckData.selectedGame) === 'hearthstone'">
            <label for="deck-class" class="field-label">Classe Hearthstone *</label>
            <Dropdown
              id="deck-class"
              v-model="deckData.hearthstoneClass"
              :options="hearthstoneClasses"
              option-label="name"
              option-value="value"
              placeholder="Sélectionner une classe"
              class="emerald-dropdown"
              :class="{ 'error': errors.hearthstoneClass }"
            >
              <template #option="{ option }">
                <div class="class-option">
                  <span class="class-name">{{ option.name }}</span>
                </div>
              </template>
            </Dropdown>
            <small v-if="errors.hearthstoneClass" class="field-error">{{ errors.hearthstoneClass }}</small>
          </div>

        <div class="field-group" v-if="deckData.selectedGame">
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
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useToast } from 'primevue/usetoast'
import api from '../services/api'
import Dialog from 'primevue/dialog'
import Dropdown from 'primevue/dropdown' 
import Textarea from 'primevue/textarea'
import HearthstoneCompactDeck from '../components/decks/HearthstoneCompactDeck.vue'
import MagicCompactDeck from '../components/decks/MagicCompactDeck.vue'

// Stores et composables
const router = useRouter()
const authStore = useAuthStore()
const toast = useToast()

// State principal
const userDecks = ref([])
const isLoading = ref(true)
const searchQuery = ref('')
const visibilityFilter = ref('all') // all, public, private
const sortBy = ref('recent') // recent, likes, name

// State modale
const showCreateModal = ref(false)
const isCreating = ref(false)
const availableGames = ref([])
const availableFormats = ref([])

const deckData = ref({
  title: '',
  description: '',
  selectedGame: null,
  selectedFormat: null,
  hearthstoneClass: null,
  archetype: ''
})

const errors = ref({
  title: '',
  game: '',
  format: '',
  hearthstoneClass: ''
})

// Classes Hearthstone pour l'affichage
const hearthstoneClasses = ref([
  { name: 'Mage', value: 'mage' },
  { name: 'Chasseur', value: 'hunter' },
  { name: 'Paladin', value: 'paladin' },
  { name: 'Guerrier', value: 'warrior' },
  { name: 'Prêtre', value: 'priest' },
  { name: 'Démoniste', value: 'warlock' },
  { name: 'Chaman', value: 'shaman' },
  { name: 'Voleur', value: 'rogue' },
  { name: 'Druide', value: 'druid' },
  { name: 'Chasseur de démons', value: 'demonhunter' },
  { name: 'Chevalier de la mort', value: 'deathknight' }
])

const archetypes = {
  hearthstone: ['Aggro', 'Midrange', 'Control', 'Combo', 'Tempo', 'Big', 'Zoo', 'Burn', 'Mill'],
  pokemon: ['Aggro', 'Control', 'Combo', 'Toolbox', 'Stall', 'Beatdown', 'Engine', 'Disruption'],
  magic: ['Aggro', 'Control', 'Midrange', 'Combo', 'Ramp', 'Tribal', 'Voltron', 'Stax', 'Storm']
}

// Computed
const filteredDecks = computed(() => {
  if (!Array.isArray(userDecks.value)) {
    console.warn('userDecks.value n\'est pas un tableau:', userDecks.value)
    return []
  }
  
  let decks = [...userDecks.value]
  
  // Filtre par recherche
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    decks = decks.filter(deck => 
      deck.name && deck.name.toLowerCase().includes(query) ||
      deck.description && deck.description.toLowerCase().includes(query)
    )
  }
  
  // Filtre par visibilité
  if (visibilityFilter.value !== 'all') {
    decks = decks.filter(deck => 
      visibilityFilter.value === 'public' ? deck.isPublic : !deck.isPublic
    )
  }
  
  // Tri
  switch (sortBy.value) {
    case 'likes':
      return decks.sort((a, b) => (b.likes || 0) - (a.likes || 0))
    case 'name':
      return decks.sort((a, b) => (a.name || '').localeCompare(b.name || ''))
    case 'recent':
    default:
      return decks.sort((a, b) => new Date(b.updatedAt || 0) - new Date(a.updatedAt || 0))
  }
})

const publicDecksCount = computed(() => 
  Array.isArray(userDecks.value) ? userDecks.value.filter(deck => deck.isPublic).length : 0
)

const totalLikes = computed(() => 
  Array.isArray(userDecks.value) ? userDecks.value.reduce((sum, deck) => sum + (deck.likes || 0), 0) : 0
)

const averageViews = computed(() => {
  if (!Array.isArray(userDecks.value) || userDecks.value.length === 0) return 0
  const totalViews = userDecks.value.reduce((sum, deck) => sum + (deck.views || 0), 0)
  return Math.round(totalViews / userDecks.value.length)
})

const isFormValid = computed(() => {
  const baseValid = deckData.value.title.trim().length >= 3 &&
                    deckData.value.selectedGame &&
                    deckData.value.selectedFormat
  
  // Si Hearthstone, classe requise
  if (deckData.value.selectedGame && getSelectedGameSlug(deckData.value.selectedGame) === 'hearthstone') {
    return baseValid && deckData.value.hearthstoneClass
  }
  
  return baseValid
})

// Méthodes
const loadUserDecks = async () => {
  try {
    isLoading.value = true
    const response = await api.get('/api/decks/my-decks')
    
    console.log('🔍 Réponse API complète:', response.data)
    
    // Gérer les différents formats de réponse
    if (response.data.success && Array.isArray(response.data.data)) {
      userDecks.value = response.data.data
      console.log('✅ Decks chargés depuis response.data.data:', userDecks.value.length)
    } else if (Array.isArray(response.data)) {
      userDecks.value = response.data
      console.log('✅ Decks chargés depuis response.data:', userDecks.value.length)
    } else {
      console.warn('❌ Format réponse inattendu:', response.data)
      userDecks.value = []
    }
    
  } catch (error) {
    console.error('💥 Erreur chargement decks:', error)
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Impossible de charger vos decks',
      life: 3000
    })
    userDecks.value = []
  } finally {
    isLoading.value = false
  }
}

const loadGames = async () => {
  try {
    const response = await api.get('/api/games')
    if (response.data.success) {
      availableGames.value = response.data.data
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

const getGameDecks = (game) => {
  return filteredDecks.value.filter(deck => {
    // Gérer les deux formats : deck.game.slug ou deck.game directement
    const gameSlug = typeof deck.game === 'object' ? deck.game.slug : deck.game
    return gameSlug === game
  })
}

const filteredDecksByGame = (gameSlug) => {
  return filteredDecks.value.filter(deck => {
    // Gérer les deux formats : deck.game.slug ou deck.game directement
    const deckGameSlug = typeof deck.game === 'object' ? deck.game.slug : deck.game
    return deckGameSlug === gameSlug
  })
}

const getClassDisplayName = (classValue) => {
  const classObj = hearthstoneClasses.value.find(c => c.value === classValue)
  return classObj ? classObj.name : classValue
}

const editDeck = (deck) => {
  router.push(`/edition/${deck.game.slug}/${deck.format.slug}/${deck.slug}`)
}

const duplicateDeck = async (deck) => {
  try {
    const response = await api.post(`/api/decks/${deck.id}/duplicate`)
    if (response.data.success) {
      toast.add({
        severity: 'success',
        summary: 'Deck dupliqué',
        detail: `"${deck.name}" a été dupliqué`,
        life: 3000
      })
      await loadUserDecks() // Recharger la liste
    }
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Impossible de dupliquer le deck',
      life: 3000
    })
  }
}

const deleteDeck = async (deck) => {
  if (!confirm(`Supprimer définitivement "${deck.name}" ?`)) return
  
  try {
    await api.delete(`/api/decks/${deck.id}`)
    userDecks.value = userDecks.value.filter(d => d.id !== deck.id)
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

const toggleVisibilityFilter = () => {
  const filters = ['all', 'public', 'private']
  const currentIndex = filters.indexOf(visibilityFilter.value)
  visibilityFilter.value = filters[(currentIndex + 1) % filters.length]
}

const toggleSort = () => {
  const sorts = ['recent', 'likes', 'name']
  const currentIndex = sorts.indexOf(sortBy.value)
  sortBy.value = sorts[(currentIndex + 1) % sorts.length]
}

// Méthodes modale
const getSelectedGameName = (gameId) => {
  const game = availableGames.value.find(g => g.id === gameId)
  return game ? game.name : ''
}

const getSelectedGameColor = (gameId) => {
  const game = availableGames.value.find(g => g.id === gameId)
  return game ? game.primaryColor : '#26a69a'
}

const getSelectedGameSlug = (gameId) => {
  const game = availableGames.value.find(g => g.id === gameId)
  return game ? game.slug : ''
}

const getArchetypesForGame = () => {
  if (!deckData.value.selectedGame) return []
  const game = availableGames.value.find(g => g.id === deckData.value.selectedGame)
  return game ? archetypes[game.slug] || [] : []
}

const onGameChange = () => {
  deckData.value.selectedFormat = null
  deckData.value.hearthstoneClass = null 
  deckData.value.archetype = ''
  errors.value.game = ''
  errors.value.hearthstoneClass = ''
  
  if (deckData.value.selectedGame) {
    loadFormatsForGame(deckData.value.selectedGame)
  }
}

const validateForm = () => {
  errors.value = { title: '', game: '', format: '', hearthstoneClass: '' }
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

  if (deckData.value.selectedGame && getSelectedGameSlug(deckData.value.selectedGame) === 'hearthstone') {
    if (!deckData.value.hearthstoneClass) {
      errors.value.hearthstoneClass = 'Veuillez sélectionner une classe'
      isValid = false
    }
  }

  return isValid
}

const copyDeckcode = (deck) => {
  toast.add({
    severity: 'info',
    summary: 'Deckcode',
    detail: 'Fonctionnalité bientôt disponible...',
    life: 2000
  })
}

const createDeck = async () => {
  if (!validateForm()) return

  isCreating.value = true

  try {
    const deckPayload = {
      title: deckData.value.title.trim(),
      gameId: deckData.value.selectedGame,
      formatId: deckData.value.selectedFormat,
      description: deckData.value.description?.trim() || null,
      hearthstoneClass: deckData.value.hearthstoneClass,
      archetype: deckData.value.archetype?.trim() || null
    }

    const response = await api.post('/api/decks', deckPayload)

    if (response.data.success) {
      const deckInfo = response.data.data
      
      showCreateModal.value = false
      
      // Reset du formulaire
      deckData.value = {
        title: '',
        description: '',
        selectedGame: null,
        selectedFormat: null,
        hearthstoneClass: null,
        archetype: ''
      }
      
      toast.add({
        severity: 'success',
        summary: 'Deck créé !',
        detail: `"${deckInfo.title}" est prêt à être édité`,
        life: 3000
      })

      // Redirection vers l'éditeur
      const editUrl = `/edition/${deckInfo.gameSlug}/${deckInfo.formatSlug}/${deckInfo.slug}`
      await router.push(editUrl)

    } else {
      toast.add({
        severity: 'error',
        summary: 'Erreur de création',
        detail: response.data.message || 'Erreur lors de la création',
        life: 4000
      })
    }

  } catch (error) {
    console.error('💥 Erreur création deck:', error)
    
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

// Lifecycle
onMounted(async () => {
  await loadUserDecks()
  await loadGames()
})
</script>

<style scoped>
/* === MY DECKS PAGE EMERALD GAMING === */

.my-decks-page {
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

/* User stats */
.user-stats {
  margin-bottom: 2rem;
}

.stats-card {
  border: none;
  box-shadow: var(--shadow-small);
}

.stats-content {
  padding: 2rem;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 2rem;
}

.stat-item {
  text-align: center;
  padding: 1rem;
  border-radius: var(--border-radius);
  background: var(--surface-50);
  border: 1px solid var(--surface-200);
}

.stat-value {
  font-size: 2rem;
  font-weight: 700;
  color: var(--primary);
  margin-bottom: 0.5rem;
}

.stat-label {
  font-size: 0.9rem;
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-weight: 500;
}

/* Deck filters */
.deck-filters {
  margin-bottom: 2rem;
}

.filters-card {
  border: none;
  box-shadow: var(--shadow-small);
}

.filters-content {
  padding: 1.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 2rem;
}

.search-wrapper {
  position: relative;
  flex: 1;
  max-width: 400px;
}

:deep(.search-input) {
  width: 100% !important;
  padding: 0.875rem 1rem 0.875rem 3rem !important;
  border: 2px solid var(--surface-300) !important;
  border-radius: 25px !important;
  font-size: 1rem !important;
  background: var(--surface-100) !important;
}

:deep(.search-input:focus) {
  border-color: var(--primary) !important;
  background: white !important;
  box-shadow: 0 0 0 3px rgba(38, 166, 154, 0.1) !important;
}

.search-icon {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-secondary);
  font-size: 1rem;
  pointer-events: none;
}

.filter-buttons {
  display: flex;
  gap: 1rem;
}

:deep(.filter-btn),
:deep(.sort-btn) {
  background: white !important;
  border: 2px solid var(--surface-300) !important;
  color: var(--text-secondary) !important;
  padding: 0.75rem 1.25rem !important;
  border-radius: 20px !important;
  font-size: 0.9rem !important;
  font-weight: 500 !important;
  transition: all var(--transition-fast) !important;
}

:deep(.filter-btn:hover),
:deep(.sort-btn:hover) {
  border-color: var(--primary) !important;
  color: var(--primary) !important;
  background: rgba(38, 166, 154, 0.1) !important;
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

/* Decks grid */
.decks-grid {
  padding: 2rem;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
  gap: 1.5rem;
}

/* Deck cards */
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

.deck-status {
  flex-shrink: 0;
}

.deck-status i {
  font-size: 1.1rem;
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

.format-badge.magic {
  background: rgba(139, 69, 19, 0.1);
  color: #8b4513;
}

.format-badge.pokemon {
  background: rgba(255, 193, 7, 0.1);
  color: #ff6f00;
}

.class-badge {
  padding: 0.25rem 0.75rem;
  background: var(--surface-200);
  color: var(--text-primary);
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 500;
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

.deck-actions {
  display: flex;
  gap: 0.5rem;
  margin-top: auto;
  padding-top: 1rem;
  border-top: 1px solid var(--surface-200);
}

:deep(.deck-actions .p-button) {
  flex: 1 !important;
  padding: 0.5rem !important;
  border-radius: 6px !important;
  font-size: 0.85rem !important;
}

:deep(.edit-btn) {
  background: var(--primary) !important;
  border-color: var(--primary) !important;
  color: white !important;
}

:deep(.edit-btn:hover) {
  background: var(--primary-dark) !important;
  border-color: var(--primary-dark) !important;
}

:deep(.copy-btn) {
  background: white !important;
  border: 2px solid var(--surface-300) !important;
  color: var(--text-secondary) !important;
}

:deep(.copy-btn:hover) {
  border-color: var(--primary) !important;
  color: var(--primary) !important;
  background: rgba(38, 166, 154, 0.1) !important;
}

:deep(.delete-btn) {
  background: white !important;
  border: 2px solid rgba(255, 87, 34, 0.3) !important;
  color: var(--accent) !important;
}

:deep(.delete-btn:hover) {
  background: var(--accent) !important;
  border-color: var(--accent) !important;
  color: white !important;
}

/* Loading and empty states */
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
  animation: spin 1s linear infinite;
}

@keyframes spin {
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
  margin: 0 0 2rem 0;
  line-height: 1.5;
}

/* Modal styles (same as DecksView) */
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

.modal-body {
  padding: 2rem;
}

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

:deep(.emerald-dropdown) {
  width: 100% !important;
  border: 2px solid var(--surface-300) !important;
  border-radius: var(--border-radius) !important;
  background: var(--surface) !important;
  transition: all var(--transition-fast) !important;
}

:deep(.emerald-dropdown.p-focus) {
  border-color: var(--primary) !important;
  box-shadow: 0 0 0 3px rgba(38, 166, 154, 0.1) !important;
  background: white !important;
}

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
}

.field-error {
  color: var(--accent);
  font-size: 0.8rem;
  font-weight: 500;
}

.field-hint {
  color: var(--text-secondary);
  font-size: 0.8rem;
  font-style: italic;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding: 1.5rem 0 0 0;
  border-top: 1px solid var(--surface-200);
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
  
  .stats-content {
    padding: 1.5rem;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
  }
  
  .filters-content {
    flex-direction: column;
    gap: 1rem;
  }
  
  .search-wrapper {
    max-width: none;
  }
  
  .decks-grid {
    padding: 1.5rem;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1rem;
  }
}

@media (max-width: 768px) {
  .my-decks-page {
    padding: 1rem 0;
  }
  
  .page-title {
    font-size: 2rem;
  }
  
  .stats-content {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
  
  .game-header {
    padding: 1rem 1.5rem;
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .decks-grid {
    grid-template-columns: 1fr;
    padding: 1rem;
  }
  
  .filter-buttons {
    width: 100%;
    justify-content: space-between;
  }
}

@media (max-width: 640px) {
  .deck-actions {
    flex-direction: column;
    gap: 0.75rem;
  }
  
  :deep(.deck-actions .p-button) {
    width: 100% !important;
  }
  
  .modal-body {
    padding: 1.5rem;
  }
  
  .modal-actions {
    flex-direction: column;
    gap: 0.75rem;
  }
  
  :deep(.modal-actions .p-button) {
    width: 100% !important;
  }
}

/* Animations */
.slide-in-down {
  animation: slideInDown 0.6s ease-out;
}

.slide-in-up {
  animation: slideInUp 0.6s ease-out;
}

@keyframes slideInDown {
  from {
    opacity: 0;
    transform: translateY(-30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
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

.hover-lift {
  transition: all var(--transition-fast);
}

.hover-lift:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-medium);
}
</style>
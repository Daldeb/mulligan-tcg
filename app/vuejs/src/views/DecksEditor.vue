<template>
  <div class="decks-editor">
    <div class="container-fluid">
      
        <!-- Header row avec decklist -->
        <div class="editor-header-row">
          <!-- Header de l'éditeur (gauche) -->
          <div class="editor-header">
            <div class="header-left">
              <Button 
                icon="pi pi-arrow-left"
                class="back-btn"
                @click="goBack"
                v-tooltip="'Retour aux decks'"
              />
              <div class="editor-title-area">
                <h1 class="editor-title">
                  {{ isEditing ? 'Modifier le deck' : 'Créer un deck' }}
                </h1>
                <div class="deck-info-quick" v-if="currentDeck.name">
                  <span class="deck-name-display">{{ currentDeck.name }}</span>
                  <span class="separator">•</span>
                  <span class="game-display">{{ gameDisplayName }}</span>
                  <span class="separator">•</span>
                  <span class="format-display">{{ currentDeck.format?.toUpperCase() }}</span>
                </div>
              </div>
            </div>
            <div class="header-actions">
              <Button 
                label="Sauvegarder"
                icon="pi pi-save"
                class="save-btn emerald-btn"
                @click="saveDeck"
                :loading="isSaving"
                :disabled="!canSave"
              />
            </div>
          </div>
        </div>

        <!-- Layout principal split -->
        <div class="editor-layout">
        
        <!-- Panneau de gauche : Library de cartes -->
        <div class="library-panel">
          <div class="library-header">
            <h3 class="library-title">
              <i class="pi pi-th-large"></i>
              Bibliothèque de cartes
            </h3>
            <div class="library-stats">
              <span class="available-cards">{{ filteredCards.length }} cartes disponibles</span>
            </div>
          </div>

          <!-- Filtres de la library -->
          <div class="library-filters">
            <div class="search-wrapper">
              <InputText 
                v-model="cardSearch"
                placeholder="Rechercher une carte..."
                class="card-search-input"
              />
              <i class="pi pi-search search-icon"></i>
            </div>
            
            <div class="filter-row">
              <Dropdown 
                v-model="filters.cost"
                :options="costOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="Coût"
                class="cost-filter"
              />
              
              <Dropdown 
                v-model="filters.rarity"
                :options="rarityOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="Rareté"
                class="rarity-filter"
              />
              
              <Dropdown 
                v-model="filters.cardType"
                :options="typeOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="Type"
                class="type-filter"
              />
              
              <Button 
                icon="pi pi-filter-slash"
                class="clear-filters-btn"
                @click="clearCardFilters"
                v-tooltip="'Effacer les filtres'"
              />
            </div>
          </div>

          <!-- Grid des cartes -->
          <div class="cards-library">
            <div v-if="isLoadingCards" class="library-loading">
              <div class="emerald-spinner"></div>
              <p>Chargement des cartes {{ gameDisplayName }}...</p>
            </div>
            
            <div v-else class="cards-grid">
              <CardItem 
                v-for="card in paginatedCards"
                :key="card.id"
                :card="card"
                :quantity="getCardQuantity(card.id)"
                :max-quantity="getMaxQuantity(card)"
                :can-add="canAddCard(card)"
                @add="addCardToDeck"
                @remove="removeCardFromDeck"
                @preview="showCardPreview"
              />
            </div>

            <!-- Pagination -->
            <div v-if="totalPages > 1" class="library-pagination">
              <Paginator 
                v-model:first="currentPage"
                :rows="cardsPerPage"
                :totalRecords="filteredCards.length"
                class="custom-paginator"
              />
            </div>
          </div>
        </div>

        <!-- Panneau deck : Decklist en cours -->
<div class="deck-panel">
  <!-- Header du deck -->
  <div class="deck-header">
    <h3 class="deck-title">Mon Deck</h3>
    <div class="deck-stats">
      <div class="stat-item">
        <span class="stat-label">Total</span>
        <span class="stat-value">{{ totalCardsInDeck }}/{{ maxCardsForGame }}</span>
      </div>
      <div class="stat-item">
        <span class="stat-label">Coût moyen</span>
        <span class="stat-value">{{ averageCost }}</span>
      </div>
      <div class="stat-item">
        <span class="stat-label">Cartes uniques</span>
        <span class="stat-value">{{ deckCards.length }}</span>
      </div>
    </div>
  </div>

  <!-- Contenu du deck -->
  <div class="deck-content">
    <div v-if="deckCards.length === 0" class="empty-deck">
      <i class="pi pi-clone empty-icon"></i>
      <p>Votre deck apparaîtra ici</p>
      <small>Cliquez sur les cartes pour les ajouter</small>
    </div>
    
    <div v-else class="deck-list">
      <div 
        v-for="entry in sortedDeckCards" 
        :key="entry.card.id"
        class="deck-card-entry"
        @click="removeCardFromDeck(entry)"
      >
        <div class="card-cost">{{ entry.card.cost || 0 }}</div>
        <div class="card-info">
          <div class="card-name">{{ entry.card.name }}</div>
        </div>
        <div class="card-quantity">{{ entry.quantity }}</div>
      </div>
    </div>
  </div>
</div>


      </div>

    </div>

    <!-- Modal paramètres du deck -->
    <Dialog 
      v-model:visible="showSettings"
      modal
      header="Paramètres du deck"
      class="settings-modal"
      :style="{ width: '500px' }"
    >
      <div class="settings-form">
        
        <!-- Nom du deck -->
        <div class="form-group">
          <label for="deckName" class="form-label">Nom du deck *</label>
          <InputText 
            id="deckName"
            v-model="currentDeck.name"
            placeholder="Ex: Aggro Mage"
            class="form-input"
            :class="{ 'p-invalid': errors.name }"
          />
          <small v-if="errors.name" class="error-message">{{ errors.name }}</small>
        </div>

        <!-- Description -->
        <div class="form-group">
          <label for="deckDescription" class="form-label">Description</label>
          <Textarea 
            id="deckDescription"
            v-model="currentDeck.description"
            placeholder="Décrivez votre stratégie..."
            rows="3"
            class="form-input"
          />
        </div>

        <!-- Jeu et format -->
        <div class="form-row">
          <div class="form-group">
            <label for="deckGame" class="form-label">Jeu *</label>
            <Dropdown 
              id="deckGame"
              v-model="currentDeck.game"
              :options="gameOptions"
              optionLabel="name"
              optionValue="value"
              placeholder="Choisir un jeu"
              class="form-input"
              :class="{ 'p-invalid': errors.game }"
              @change="onGameChange"
            />
            <small v-if="errors.game" class="error-message">{{ errors.game }}</small>
          </div>
          
          <div class="form-group">
            <label for="deckFormat" class="form-label">Format</label>
            <Dropdown 
              id="deckFormat"
              v-model="currentDeck.format"
              :options="getFormatsForGame(currentDeck.game)"
              optionLabel="name"
              optionValue="value"
              placeholder="Standard"
              class="form-input"
              @change="onFormatChange"
            />
          </div>
        </div>

        <!-- Visibilité -->
        <div class="form-group">
          <label class="form-label">Visibilité</label>
          <div class="visibility-options">
            <div class="visibility-option">
              <RadioButton 
                v-model="currentDeck.visibility"
                inputId="private"
                value="private"
              />
              <label for="private" class="visibility-label">
                <i class="pi pi-lock"></i>
                <span>Privé</span>
                <small>Visible uniquement par vous</small>
              </label>
            </div>
            <div class="visibility-option">
              <RadioButton 
                v-model="currentDeck.visibility"
                inputId="public"
                value="public"
              />
              <label for="public" class="visibility-label">
                <i class="pi pi-globe"></i>
                <span>Public</span>
                <small>Visible par la communauté</small>
              </label>
            </div>
          </div>
        </div>

      </div>

      <template #footer>
        <div class="modal-actions">
          <Button 
            label="Annuler"
            icon="pi pi-times"
            class="emerald-outline-btn cancel"
            @click="showSettings = false"
          />
          <Button 
            label="Appliquer"
            icon="pi pi-check"
            class="emerald-button primary"
            @click="applySettings"
          />
        </div>
      </template>
    </Dialog>

    <!-- Modal import deckcode -->
    <Dialog 
      v-model:visible="showImportDeckcode"
      modal
      header="Importer un deckcode"
      class="import-modal"
      :style="{ width: '450px' }"
    >
      <div class="import-form">
        <div class="form-group">
          <label for="deckcode" class="form-label">Deckcode</label>
          <Textarea 
            id="deckcode"
            v-model="importDeckcode"
            placeholder="Collez votre deckcode ici..."
            rows="4"
            class="form-input"
          />
          <small class="form-hint">
            Formats supportés: Hearthstone, Magic Arena, Pokemon Live
          </small>
        </div>
      </div>

      <template #footer>
        <div class="modal-actions">
          <Button 
            label="Annuler"
            icon="pi pi-times"
            class="emerald-outline-btn cancel"
            @click="showImportDeckcode = false"
          />
          <Button 
            label="Importer"
            icon="pi pi-download"
            class="emerald-button primary"
            @click="importFromDeckcode"
            :loading="isImporting"
          />
        </div>
      </template>
    </Dialog>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useGameFilterStore } from '../stores/gameFilter'
import { useToast } from 'primevue/usetoast'
import api from '../services/api'
import CardItem from '../components/decks/CardItem.vue'
import Dropdown from 'primevue/dropdown'
import Paginator from 'primevue/paginator' 
import RadioButton from 'primevue/radiobutton'

// Stores et composables
const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const gameFilterStore = useGameFilterStore()
const toast = useToast()

// State principal
const currentDeck = ref({
  id: null,
  name: '',
  description: '',
  game: 'hearthstone',
  format: 'standard',
  visibility: 'private'
})

// Props depuis l'URL
const props = defineProps({
  gameSlug: String,
  formatSlug: String, 
  deckSlug: String
})

const deckCards = ref([]) // [{ card: CardObject, quantity: number }]
const availableCards = ref([])
const isLoading = ref(false)
const isLoadingCards = ref(false)
const isSaving = ref(false)
const isImporting = ref(false)

// State UI
const showSettings = ref(false)
const showImportDeckcode = ref(false)
const importDeckcode = ref('')
const errors = ref({})

// Filtres de la library
const cardSearch = ref('')
const filters = ref({
  cost: null,
  rarity: null,
  cardType: null,
  cardClass: null
})

// Pagination
const currentPage = ref(0)
const cardsPerPage = ref(30)

// Computed
const isEditing = computed(() => !!route.params.id)

const gameDisplayName = computed(() => {
  const names = {
    'hearthstone': 'Hearthstone',
    'magic': 'Magic: The Gathering',
    'pokemon': 'Pokemon TCG'
  }
  return names[currentDeck.value.game] || 'Jeu inconnu'
})

const maxCardsForGame = computed(() => {
  const limits = {
    'hearthstone': 30,
    'magic': 60,
    'pokemon': 60
  }
  return limits[currentDeck.value.game] || 30
})

const totalCardsInDeck = computed(() => {
  return deckCards.value.reduce((total, entry) => total + entry.quantity, 0)
})

const averageCost = computed(() => {
  if (deckCards.value.length === 0) return '0.0'
  const totalCost = deckCards.value.reduce((sum, entry) => 
    sum + (entry.card.cost || 0) * entry.quantity, 0
  )
  return (totalCost / totalCardsInDeck.value).toFixed(1)
})

const formatBadgeClass = computed(() => {
  if (currentDeck.value.format === 'wild') return 'format-wild'
  if (currentDeck.value.format === 'standard') return 'format-standard'
  return 'format-default'
})

const canSave = computed(() => {
  return currentDeck.value.name?.trim() && 
         currentDeck.value.game && 
         deckCards.value.length > 0
})

// Filtrage des cartes
const filteredCards = computed(() => {
  let cards = availableCards.value

  // Filtre par recherche
  if (cardSearch.value) {
    const query = cardSearch.value.toLowerCase()
    cards = cards.filter(card => 
      card.name.toLowerCase().includes(query) ||
      card.text?.toLowerCase().includes(query)
    )
  }

  // Filtre par coût
  if (filters.value.cost !== null) {
    if (filters.value.cost === '7+') {
      cards = cards.filter(card => (card.cost || 0) >= 7)
    } else {
      cards = cards.filter(card => (card.cost || 0) === filters.value.cost)
    }
  }

  // Filtre par rareté
  if (filters.value.rarity) {
    cards = cards.filter(card => 
      card.rarity?.toLowerCase() === filters.value.rarity
    )
  }

  // Filtre par type
  if (filters.value.cardType) {
    cards = cards.filter(card => 
      card.cardType?.toLowerCase() === filters.value.cardType
    )
  }

  // Filtre par format (Standard/Wild)
  if (currentDeck.value.format === 'standard') {
    cards = cards.filter(card => card.isStandardLegal)
  }

  return cards
})

const paginatedCards = computed(() => {
  const start = currentPage.value
  const end = start + cardsPerPage.value
  return filteredCards.value.slice(start, end)
})

const totalPages = computed(() => {
  return Math.ceil(filteredCards.value.length / cardsPerPage.value)
})

// Options pour les filtres
const gameOptions = ref([
  { name: 'Hearthstone', value: 'hearthstone' },
  { name: 'Magic: The Gathering', value: 'magic' },
  { name: 'Pokemon TCG', value: 'pokemon' }
])

const costOptions = computed(() => {
  if (currentDeck.value.game === 'hearthstone') {
    return [
      { label: 'Tous les coûts', value: null },
      { label: '0', value: 0 },
      { label: '1', value: 1 },
      { label: '2', value: 2 },
      { label: '3', value: 3 },
      { label: '4', value: 4 },
      { label: '5', value: 5 },
      { label: '6', value: 6 },
      { label: '7+', value: '7+' }
    ]
  }
  return [{ label: 'Tous les coûts', value: null }]
})

const rarityOptions = computed(() => {
  if (currentDeck.value.game === 'hearthstone') {
    return [
      { label: 'Toutes les raretés', value: null },
      { label: 'Commune', value: 'common' },
      { label: 'Rare', value: 'rare' },
      { label: 'Épique', value: 'epic' },
      { label: 'Légendaire', value: 'legendary' }
    ]
  }
  return [{ label: 'Toutes les raretés', value: null }]
})

const typeOptions = computed(() => {
  if (currentDeck.value.game === 'hearthstone') {
    return [
      { label: 'Tous les types', value: null },
      { label: 'Serviteur', value: 'minion' },
      { label: 'Sort', value: 'spell' },
      { label: 'Arme', value: 'weapon' }
    ]
  }
  return [{ label: 'Tous les types', value: null }]
})

const getFormatsForGame = (game) => {
  const formats = {
    hearthstone: [
      { name: 'Standard', value: 'standard' },
      { name: 'Wild', value: 'wild' }
    ],
    magic: [
      { name: 'Standard', value: 'standard' },
      { name: 'Modern', value: 'modern' },
      { name: 'Legacy', value: 'legacy' }
    ],
    pokemon: [
      { name: 'Standard', value: 'standard' },
      { name: 'Expanded', value: 'expanded' }
    ]
  }
  return formats[game] || [{ name: 'Standard', value: 'standard' }]
}

// Méthodes de gestion des cartes
const getCardQuantity = (cardId) => {
  const entry = deckCards.value.find(entry => entry.card.id === cardId)
  return entry ? entry.quantity : 0
}

const getMaxQuantity = (card) => {
  if (currentDeck.value.game === 'hearthstone') {
    return card.rarity?.toLowerCase() === 'legendary' ? 1 : 2
  }
  if (currentDeck.value.game === 'pokemon') {
    return 4
  }
  if (currentDeck.value.game === 'magic') {
    // Terrains de base = illimité, autres = 4
    return card.cardType?.toLowerCase().includes('basic land') ? 99 : 4
  }
  return 2
}

const canAddCard = (card) => {
  const currentQuantity = getCardQuantity(card.id)
  const maxQuantity = getMaxQuantity(card)
  return currentQuantity < maxQuantity && totalCardsInDeck.value < maxCardsForGame.value
}

const addCardToDeck = (card) => {
  if (!canAddCard(card)) return

  const existingEntry = deckCards.value.find(entry => entry.card.id === card.id)
  
  if (existingEntry) {
    existingEntry.quantity++
  } else {
    deckCards.value.push({
      card: card,
      quantity: 1
    })
  }

  toast.add({
    severity: 'success',
    summary: 'Carte ajoutée',
    detail: `${card.name} ajoutée au deck`,
    life: 2000
  })
}

const removeCardFromDeck = (cardOrEntry) => {
  const cardId = cardOrEntry.card?.id || cardOrEntry.id
  const entryIndex = deckCards.value.findIndex(entry => entry.card.id === cardId)
  
  if (entryIndex !== -1) {
    const entry = deckCards.value[entryIndex]
    
    if (entry.quantity > 1) {
      entry.quantity--
    } else {
      deckCards.value.splice(entryIndex, 1)
    }

    toast.add({
      severity: 'info',
      summary: 'Carte retirée',
      detail: `${entry.card.name} retirée du deck`,
      life: 2000
    })
  }
}

const loadDeckBySlug = async (gameSlug, formatSlug, deckSlug) => {
  try {
    isLoading.value = true
    console.log(`🔍 Chargement deck: ${gameSlug}/${formatSlug}/${deckSlug}`)
    
    const response = await api.get(`/api/decks/by-slug/${gameSlug}/${formatSlug}/${deckSlug}`)
    
    if (response.data.success) {
      const deck = response.data.data
      
      currentDeck.value = {
        id: deck.id,
        name: deck.title,
        description: deck.description,
        game: deck.game.slug,
        format: deck.format.slug,
        visibility: deck.isPublic ? 'public' : 'private'
      }
      
      // Charger les cartes du deck si présentes
      deckCards.value = deck.cards || []
      
      console.log('✅ Deck chargé:', currentDeck.value)
    }
  } catch (error) {
    console.error('💥 Erreur chargement deck:', error)
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Impossible de charger le deck',
      life: 3000
    })
    goBack()
  } finally {
    isLoading.value = false
  }
}

const loadCards = async () => {
  try {
    isLoadingCards.value = true
    
    const gameSlug = currentDeck.value.game
    const format = currentDeck.value.format // ← Récupérer le format du deck
    
    console.log(`🃏 Chargement cartes pour: ${gameSlug} (${format})`)
    
    const response = await api.get(`/api/cards/${gameSlug}?format=${format}&limit=10000`) // ← Passer le format
    availableCards.value = response.data
    
    console.log(`✅ ${availableCards.value.length} cartes ${gameSlug} chargées`)
    console.log('🔍 Structure première carte:', availableCards.value[0])
    console.log('🔍 Total dans availableCards:', availableCards.value.length)
    console.log('🔍 Filtered cards length:', filteredCards.value.length)
    console.log('🔍 isStandardLegal:', availableCards.value[0].isStandardLegal)  } catch (error) {
    console.error('Erreur chargement cartes:', error)
    loadMockCards()
  } finally {
    isLoadingCards.value = false
  }
}

const loadMockCards = () => {
  // Données de test Hearthstone
  if (currentDeck.value.game === 'hearthstone') {
    availableCards.value = [
      {
        id: 1,
        name: 'Fireball',
        cost: 4,
        rarity: 'common',
        cardType: 'spell',
        cardClass: 'mage',
        imageUrl: 'hearthstone/cards/CS2_029.png',
        text: 'Deal 6 damage.',
        isStandardLegal: true
      },
      {
        id: 2,
        name: 'Leeroy Jenkins',
        cost: 5,
        rarity: 'legendary',
        cardType: 'minion',
        cardClass: 'neutral',
        imageUrl: 'hearthstone/cards/EX1_116.png',
        text: 'Charge. Battlecry: Summon two 1/1 Whelps for your opponent.',
        isStandardLegal: false
      }
      // ... plus de cartes
    ]
  }
}

const saveDeck = async () => {
  if (!validateDeck()) return

  try {
    isSaving.value = true
    
    const deckData = {
      name: currentDeck.value.name.trim(),
      description: currentDeck.value.description?.trim(),
      game: currentDeck.value.game,
      format: currentDeck.value.format,
      visibility: currentDeck.value.visibility,
      cards: deckCards.value.map(entry => ({
        cardId: entry.card.id,
        quantity: entry.quantity
      }))
    }

    if (isEditing.value) {
      await api.put(`/api/decks/${route.params.id}`, deckData)
      toast.add({
        severity: 'success',
        summary: 'Deck modifié',
        detail: 'Votre deck a été modifié avec succès',
        life: 3000
      })
    } else {
      const response = await api.post('/api/decks', deckData)
      currentDeck.value.id = response.data.id
      toast.add({
        severity: 'success',
        summary: 'Deck créé',
        detail: 'Votre deck a été créé avec succès',
        life: 3000
      })
    }

  } catch (error) {
    console.error('Erreur sauvegarde:', error)
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: error.response?.data?.message || 'Erreur lors de la sauvegarde',
      life: 3000
    })
  } finally {
    isSaving.value = false
  }
}

const validateDeck = () => {
  errors.value = {}
  
  if (!currentDeck.value.name?.trim()) {
    errors.value.name = 'Le nom du deck est requis'
  }
  
  if (!currentDeck.value.game) {
    errors.value.game = 'Veuillez choisir un jeu'
  }
  
  if (deckCards.value.length === 0) {
    toast.add({
      severity: 'warn',
      summary: 'Deck vide',
      detail: 'Ajoutez au moins une carte à votre deck',
      life: 3000
    })
    return false
  }
  
  return Object.keys(errors.value).length === 0
}

const goBack = () => {
  router.push('/decks')
}

const clearDeck = () => {
  if (confirm('Vider entièrement le deck ?')) {
    deckCards.value = []
    toast.add({
      severity: 'info',
      summary: 'Deck vidé',
      detail: 'Toutes les cartes ont été retirées',
      life: 2000
    })
  }
}

const clearCardFilters = () => {
  cardSearch.value = ''
  filters.value = {
    cost: null,
    rarity: null,
    cardType: null,
    cardClass: null
  }
  currentPage.value = 0
}

const onGameChange = () => {
  // Reset format et cartes quand on change de jeu
  const formats = getFormatsForGame(currentDeck.value.game)
  currentDeck.value.format = formats[0]?.value || 'standard'
  
  // Vider le deck et recharger les cartes
  deckCards.value = []
  clearCardFilters()
  loadCards()
}

const onFormatChange = () => {
  // Filtrer les cartes selon le nouveau format
  currentPage.value = 0
}

const sortedDeckCards = computed(() => {
  return [...deckCards.value].sort((a, b) => {
    const costDiff = (a.card.cost || 0) - (b.card.cost || 0)
    if (costDiff !== 0) return costDiff
    return a.card.name.localeCompare(b.card.name)
  })
})

const applySettings = () => {
  showSettings.value = false
  // Les changements sont déjà appliqués via v-model
}

const exportDeckcode = () => {
  toast.add({
    severity: 'info',
    summary: 'Export deckcode',
    detail: 'Fonctionnalité en développement...',
    life: 3000
  })
}

const importFromDeckcode = () => {
  toast.add({
    severity: 'info',
    summary: 'Import deckcode',
    detail: 'Fonctionnalité en développement...',
    life: 3000
  })
  showImportDeckcode.value = false
}

const showCardPreview = (card) => {
  // Logique de preview (sera gérée par CardItem)
  console.log('Preview card:', card.name)
}

// Lifecycle
onMounted(async () => {
  // Mode édition avec slugs depuis l'URL
  if (props.gameSlug && props.formatSlug && props.deckSlug) {
    await loadDeckBySlug(props.gameSlug, props.formatSlug, props.deckSlug)
  } else if (route.params.id) {
    // Fallback mode édition par ID
    await loadExistingDeck(route.params.id)
  } else {
    // Mode création pur (si besoin)
    const preferredGames = gameFilterStore.selectedGames
    if (preferredGames.length > 0) {
      const gameMapping = { 1: 'hearthstone', 2: 'magic', 3: 'pokemon' }
      currentDeck.value.game = gameMapping[preferredGames[0]] || 'hearthstone'
    }
  }
  
  await loadCards()
})

const loadExistingDeck = async (deckId) => {
  try {
    isLoading.value = true
    const response = await api.get(`/api/decks/${deckId}`)
    const deck = response.data
    
    currentDeck.value = {
      id: deck.id,
      name: deck.name,
      description: deck.description,
      game: deck.game,
      format: deck.format,
      visibility: deck.visibility
    }
    
    deckCards.value = deck.cards || []
  } catch (error) {
    console.error('Erreur chargement deck:', error)
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Impossible de charger le deck',
      life: 3000
    })
    goBack()
  } finally {
    isLoading.value = false
  }
}

const copyExistingDeck = async (deckId) => {
  try {
    const response = await api.get(`/api/decks/${deckId}`)
    const deck = response.data
    
    currentDeck.value = {
      id: null, // Nouveau deck
      name: `Copie de ${deck.name}`,
      description: deck.description,
      game: deck.game,
      format: deck.format,
      visibility: 'private'
    }
    
    deckCards.value = [...(deck.cards || [])]
  } catch (error) {
    console.error('Erreur copie deck:', error)
  }
}

// Watcher pour recharger les cartes si le jeu change
watch(() => currentDeck.value.game, (newGame) => {
  if (newGame) {
    loadCards()
  }
})
</script>

<style scoped>
/* === DECKS EDITOR EMERALD GAMING === */

.decks-editor {
  min-height: 100vh;
  background: var(--surface-gradient);
  padding-top: 0;
}

.container-fluid {
  max-width: 100%;
  padding: 0;
}

/* Header de l'éditeur */
.editor-header {
  padding: 1rem 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 2rem;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 1rem;
}

:deep(.back-btn) {
  background: none !important;
  border: 2px solid var(--surface-300) !important;
  color: var(--text-secondary) !important;
  width: 44px !important;
  height: 44px !important;
  border-radius: 50% !important;
}

:deep(.back-btn:hover) {
  border-color: var(--primary) !important;
  color: var(--primary) !important;
  background: rgba(38, 166, 154, 0.1) !important;
}

.editor-title-area {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.editor-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0;
}

.deck-info-quick {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.85rem;
  color: var(--text-secondary);
}

.deck-name-display {
  font-weight: 600;
  color: var(--primary);
}

.separator {
  color: var(--surface-400);
}

.header-actions {
  display: flex;
  gap: 1rem;
}

:deep(.settings-btn) {
  background: none !important;
  border: 2px solid var(--surface-300) !important;
  color: var(--text-secondary) !important;
  padding: 0.75rem 1.25rem !important;
  border-radius: 25px !important;
}

:deep(.settings-btn:hover) {
  border-color: var(--primary) !important;
  color: var(--primary) !important;
  background: rgba(38, 166, 154, 0.1) !important;
}

/* Header row pour la cohérence */
.editor-header-row {
  background: white;
  border-bottom: 1px solid var(--surface-200);
  position: sticky;
  top: 0;
  z-index: 50;
  box-shadow: var(--shadow-small);
}

/* Layout principal - RETOUR AUX 2 COLONNES */
.editor-layout {
  display: grid;
  grid-template-columns: 1fr 350px;
  min-height: calc(100vh - 80px);
  background: white;
}

/* Panneau library - COLONNE DE GAUCHE */
.library-panel {
  background: #f8fafc;
  border-right: 1px solid #e2e8f0;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.library-header {
  background: white;
  padding: 1.5rem 2rem;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.library-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.library-title i {
  color: var(--primary);
}

.library-stats {
  color: #6b7280;
  font-size: 0.9rem;
}

.available-cards {
  font-weight: 600;
  color: #10b981;
}

/* Filtres library */
.library-filters {
  background: white;
  padding: 1rem 2rem;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.search-wrapper {
  position: relative;
}

:deep(.card-search-input) {
  width: 100% !important;
  padding-left: 3rem !important;
  border: 2px solid var(--surface-300) !important;
  border-radius: 25px !important;
}

:deep(.card-search-input:focus) {
  border-color: var(--primary) !important;
  box-shadow: 0 0 0 3px rgba(38, 166, 154, 0.1) !important;
}

.search-icon {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-secondary);
  pointer-events: none;
}

.filter-row {
  display: flex;
  gap: 0.75rem;
  align-items: center;
}

:deep(.cost-filter),
:deep(.rarity-filter),
:deep(.type-filter) {
  flex: 1;
  min-width: 120px;
}

:deep(.clear-filters-btn) {
  background: none !important;
  border: 2px solid var(--surface-300) !important;
  color: var(--text-secondary) !important;
  width: 40px !important;
  height: 40px !important;
  border-radius: 50% !important;
  flex-shrink: 0;
}

:deep(.clear-filters-btn:hover) {
  border-color: var(--accent) !important;
  color: var(--accent) !important;
  background: rgba(255, 87, 34, 0.1) !important;
}

/* Cards library - PLUS LARGE MAINTENANT */
.cards-library {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  background: #f8fafc;
}

.library-loading {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  color: #64748b;
  background: #f8fafc;
}

.emerald-spinner {
  width: 40px;
  height: 40px;
  margin-bottom: 1rem;
  border: 3px solid #e2e8f0;
  border-top: 3px solid #10b981;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* GRILLE ADAPTÉE - 5 COLONNES */
.cards-grid {
  padding: 1.5rem;
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  grid-template-rows: repeat(6, minmax(220px, auto));
  gap: 1rem;
}

.cards-grid::-webkit-scrollbar {
  width: 8px;
}

.cards-grid::-webkit-scrollbar-track {
  background: transparent;
}

.cards-grid::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}

.cards-grid::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* Animation d'entrée pour les cartes */
.cards-grid > * {
  animation: fadeInCard 0.3s ease-out;
}

@keyframes fadeInCard {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Pagination */
.library-pagination {
  padding: 1rem 2rem;
  background: white;
  border-top: 1px solid var(--surface-200);
}

:deep(.custom-paginator) {
  justify-content: center;
}

/* Modals */
.settings-form,
.import-form {
  padding: 1.5rem 0;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.form-label {
  display: block;
  font-weight: 500;
  color: var(--text-primary);
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
}

:deep(.form-input) {
  width: 100% !important;
}

.error-message {
  color: var(--accent);
  font-size: 0.8rem;
  margin-top: 0.25rem;
}

.form-hint {
  color: var(--text-secondary);
  font-size: 0.8rem;
  margin-top: 0.25rem;
}

/* Visibilité options */
.visibility-options {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.visibility-option {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 1rem;
  border: 2px solid var(--surface-200);
  border-radius: var(--border-radius);
  cursor: pointer;
  transition: all var(--transition-fast);
}

.visibility-option:hover {
  border-color: var(--primary);
  background: rgba(38, 166, 154, 0.05);
}

.visibility-label {
  flex: 1;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.visibility-label > span {
  font-weight: 500;
  color: var(--text-primary);
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.visibility-label small {
  color: var(--text-secondary);
  font-size: 0.8rem;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding: 1rem 1.5rem;
  background: var(--surface-100);
  border-top: 1px solid var(--surface-200);
}

/* === NOUVEAU PANNEAU DECKLIST === */
.deck-panel {
  background: #1a202c;
  display: flex;
  flex-direction: column;
  position: sticky;
  top: 215px; /* ← CORRECTION : coller sous le AppHeader au lieu de top: 0 */
  height: calc(100vh - 210px); /* ← AJUSTER la hauteur en conséquence */
  overflow-y: auto;
  z-index: 20;
}

.deck-header {
  background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
  padding: 1.5rem 1.25rem;
  border-bottom: 2px solid #4a5568;
  flex-shrink: 0;
}

.deck-title {
  color: #f7fafc;
  font-size: 1.2rem;
  font-weight: 700;
  margin: 0 0 0.75rem 0;
  text-align: center;
}

.deck-stats {
  display: flex;
  justify-content: space-between;
  font-size: 0.8rem;
  color: #cbd5e0;
}

.stat-item {
  text-align: center;
}

.stat-label {
  display: block;
  color: #a0aec0;
  margin-bottom: 0.25rem;
  text-transform: uppercase;
  font-weight: 500;
}

.stat-value {
  display: block;
  color: #f7fafc;
  font-weight: 600;
  font-size: 0.9rem;
}

.deck-content {
  flex: 1;
  padding: 1rem;
  overflow-y: auto;
}

.deck-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.deck-card-entry {
  display: flex;
  align-items: center;
  padding: 0.75rem;
  background: rgba(45, 55, 72, 0.6);
  border-radius: 8px;
  border: 1px solid #4a5568;
  cursor: pointer;
  transition: all 0.2s ease;
}

.deck-card-entry:hover {
  background: rgba(45, 55, 72, 0.8);
  border-color: #68d391;
  transform: translateX(4px);
}

.card-cost {
  width: 24px;
  height: 24px;
  background: linear-gradient(135deg, #4299e1 0%, #2b6cb0 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 700;
  font-size: 0.75rem;
  margin-right: 0.75rem;
  flex-shrink: 0;
  border: 2px solid #1e3a8a;
}

.card-info {
  flex: 1;
  min-width: 0;
}

.card-name {
  color: #f7fafc;
  font-weight: 600;
  font-size: 0.85rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.card-quantity {
  color: #68d391;
  font-weight: 700;
  font-size: 0.9rem;
  margin-left: 0.5rem;
  flex-shrink: 0;
}

.empty-deck {
  text-align: center;
  padding: 3rem 1rem;
  color: #a0aec0;
}

.empty-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

.deck-actions {
  background: rgba(26, 32, 44, 0.9);
  padding: 1rem;
  border-top: 1px solid #4a5568;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  flex-shrink: 0;
}

.deck-action-btn {
  width: 100%;
  padding: 0.75rem;
  border: 2px solid #4a5568;
  background: transparent;
  color: #cbd5e0;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 0.85rem;
  font-weight: 500;
}

.deck-action-btn:hover {
  border-color: #68d391;
  color: #68d391;
  background: rgba(104, 211, 145, 0.1);
}

.deck-action-btn.danger {
  border-color: #e53e3e;
  color: #e53e3e;
}

.deck-action-btn.danger:hover {
  background: #e53e3e;
  color: white;
}

/* Responsive pour decklist */
@media (max-width: 1024px) {
  .editor-layout {
    grid-template-columns: 1fr;
    grid-template-rows: auto 1fr;
  }
  
  .deck-panel {
    position: relative;
    top: auto;
    height: 300px;
    order: -1;
    border-right: none;
    border-bottom: 1px solid #4a5568;
  }
  
  .cards-grid {
    grid-template-columns: repeat(4, 1fr);
  }
}

@media (max-width: 768px) {
  .editor-header {
    padding: 1rem;
    flex-direction: column;
    gap: 1rem;
  }
  
  .header-left {
    width: 100%;
    justify-content: flex-start;
  }
  
  .header-actions {
    width: 100%;
    justify-content: flex-end;
  }
  
  .library-header,
  .library-filters {
    padding: 1rem;
  }
  
  .filter-row {
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .cards-grid {
    grid-template-columns: repeat(3, 1fr); /* 3 colonnes */
    padding: 0.75rem;
    gap: 0.5rem;
  }
  
  .form-row {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 480px) {
  .cards-grid {
    grid-template-columns: repeat(2, 1fr); /* 2 colonnes */
    padding: 0.5rem;
  }
  
  .editor-title {
    font-size: 1.25rem;
  }
  
  .deck-info-quick {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.25rem;
  }
  
  .separator {
    display: none;
  }
}

/* Animations */
.library-panel {
  animation: slideInLeft 0.4s ease-out;
}

@keyframes slideInLeft {
  from {
    opacity: 0;
    transform: translateX(-30px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}
</style>
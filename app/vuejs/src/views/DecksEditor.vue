<template>
  <div class="decks-editor">
    <div class="container-fluid">
      
      <!-- Header de l'éditeur -->
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
            label="Paramètres"
            icon="pi pi-cog"
            class="settings-btn"
            @click="showSettings = true"
          />
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

        <!-- Panneau de droite : Deck en cours -->
        <div class="deck-panel">
          <div class="deck-header">
            <h3 class="deck-title">
              <i class="pi pi-clone"></i>
              Deck en cours
            </h3>
            <div class="deck-stats">
              <div class="stat-item">
                <span class="stat-value">{{ totalCardsInDeck }}</span>
                <span class="stat-label">/{{ maxCardsForGame }} cartes</span>
              </div>
              <div class="stat-item">
                <span class="stat-value">{{ averageCost }}</span>
                <span class="stat-label">coût moyen</span>
              </div>
            </div>
          </div>

          <!-- Affichage du deck selon le jeu -->
          <div class="deck-display">
            <HearthstoneDeckList 
              v-if="currentDeck.game === 'hearthstone'"
              :deck="currentDeck"
              :cards="deckCards"
              :is-editable="true"
              @card-click="removeCardFromDeck"
            />
            
            <div v-else-if="currentDeck.game === 'magic'" class="magic-decklist">
              <!-- À implémenter : affichage Magic style -->
              <p>Affichage Magic en développement...</p>
            </div>
            
            <div v-else-if="currentDeck.game === 'pokemon'" class="pokemon-decklist">
              <!-- À implémenter : affichage Pokemon style -->
              <p>Affichage Pokemon en développement...</p>
            </div>

            <div v-else class="empty-deck">
              <i class="pi pi-clone empty-icon"></i>
              <p>Votre deck apparaîtra ici</p>
            </div>
          </div>

          <!-- Actions rapides du deck -->
          <div class="deck-actions">
            <Button 
              label="Vider le deck"
              icon="pi pi-trash"
              class="clear-deck-btn"
              @click="clearDeck"
              :disabled="deckCards.length === 0"
            />
            <Button 
              label="Import deckcode"
              icon="pi pi-download"
              class="import-btn"
              @click="showImportDeckcode = true"
            />
            <Button 
              label="Export deckcode"
              icon="pi pi-upload"
              class="export-btn"
              @click="exportDeckcode"
              :disabled="deckCards.length === 0"
            />
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
import HearthstoneDeckList from '../components/decks/HearthstoneDeckList.vue'
import CardItem from '../components/decks/CardItem.vue'

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
const cardsPerPage = ref(48)

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

// Actions principales
const loadCards = async () => {
  try {
    isLoadingCards.value = true
    const response = await api.get(`/api/cards/${currentDeck.value.game}`)
    availableCards.value = response.data
  } catch (error) {
    console.error('Erreur chargement cartes:', error)
    // Charger données de test
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
  // Déterminer le mode (création/édition/copie)
  if (route.params.id) {
    // Mode édition
    await loadExistingDeck(route.params.id)
  } else if (route.query.copy) {
    // Mode copie
    await copyExistingDeck(route.query.copy)
  } else {
    // Mode création - utiliser préférences de jeu si disponibles
    const preferredGames = gameFilterStore.selectedGames
    if (preferredGames.length > 0) {
      // Mapper les IDs vers les noms de jeux
      const gameMapping = { 1: 'hearthstone', 2: 'magic', 3: 'pokemon' }
      currentDeck.value.game = gameMapping[preferredGames[0]] || 'hearthstone'
    }
  }
  
  // Charger les cartes du jeu sélectionné
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
  background: white;
  border-bottom: 1px solid var(--surface-200);
  padding: 1rem 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 2rem;
  position: sticky;
  top: 0;
  z-index: 50;
  box-shadow: var(--shadow-small);
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

/* Layout principal */
.editor-layout {
  display: grid;
  grid-template-columns: 1fr 400px;
  min-height: calc(100vh - 80px);
}

/* Panneau library */
.library-panel {
  background: var(--surface-100);
  border-right: 1px solid var(--surface-200);
  display: flex;
  flex-direction: column;
}

.library-header {
  background: white;
  padding: 1.5rem 2rem;
  border-bottom: 1px solid var(--surface-200);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.library-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.library-title i {
  color: var(--primary);
}

.library-stats {
  color: var(--text-secondary);
  font-size: 0.85rem;
}

.available-cards {
  font-weight: 500;
}

/* Filtres library */
.library-filters {
  background: white;
  padding: 1rem 2rem;
  border-bottom: 1px solid var(--surface-200);
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

/* Cards library */
.cards-library {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.library-loading {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  color: var(--text-secondary);
}

.emerald-spinner {
  width: 40px;
  height: 40px;
  margin-bottom: 1rem;
}

.cards-grid {
  flex: 1;
  padding: 1rem;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 1rem;
  overflow-y: auto;
  max-height: calc(100vh - 320px);
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

/* Panneau deck */
.deck-panel {
  background: #1a1a1a;
  display: flex;
  flex-direction: column;
}

.deck-header {
  background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
  padding: 1.5rem;
  border-bottom: 2px solid #4a5568;
}

.deck-title {
  color: #f7fafc;
  font-size: 1.25rem;
  font-weight: 600;
  margin: 0 0 1rem 0;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.deck-title i {
  color: var(--primary);
}

.deck-stats {
  display: flex;
  gap: 2rem;
}

.stat-item {
  display: flex;
  align-items: baseline;
  gap: 0.25rem;
}

.stat-value {
  color: #f7fafc;
  font-size: 1.5rem;
  font-weight: 700;
}

.stat-label {
  color: #a0aec0;
  font-size: 0.8rem;
}

/* Affichage du deck */
.deck-display {
  flex: 1;
  overflow: hidden;
}

.empty-deck {
  padding: 3rem 2rem;
  text-align: center;
  color: #a0aec0;
}

.empty-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

/* Actions du deck */
.deck-actions {
  background: rgba(26, 32, 44, 0.8);
  padding: 1rem;
  border-top: 1px solid #4a5568;
  display: flex;
  gap: 0.75rem;
}

:deep(.clear-deck-btn) {
  flex: 1;
  background: none !important;
  border: 2px solid #ef4444 !important;
  color: #ef4444 !important;
  font-size: 0.8rem !important;
  padding: 0.5rem !important;
}

:deep(.clear-deck-btn:hover) {
  background: #ef4444 !important;
  color: white !important;
}

:deep(.import-btn),
:deep(.export-btn) {
  flex: 1;
  background: none !important;
  border: 2px solid #4a5568 !important;
  color: #a0aec0 !important;
  font-size: 0.8rem !important;
  padding: 0.5rem !important;
}

:deep(.import-btn:hover),
:deep(.export-btn:hover) {
  border-color: var(--primary) !important;
  color: var(--primary) !important;
  background: rgba(38, 166, 154, 0.1) !important;
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

/* Responsive */
@media (max-width: 1024px) {
  .editor-layout {
    grid-template-columns: 1fr;
    grid-template-rows: 1fr auto;
  }
  
  .deck-panel {
    order: -1;
    max-height: 300px;
  }
  
  .library-panel {
    border-right: none;
    border-top: 1px solid var(--surface-200);
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
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    padding: 0.75rem;
    gap: 0.75rem;
  }
  
  .form-row {
    grid-template-columns: 1fr;
  }
  
  .deck-stats {
    justify-content: center;
    gap: 1rem;
  }
  
  .deck-actions {
    flex-direction: column;
    gap: 0.5rem;
  }
}

@media (max-width: 640px) {
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
  
  .cards-grid {
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
  }
}

/* Animations */
.library-panel {
  animation: slideInLeft 0.4s ease-out;
}

.deck-panel {
  animation: slideInRight 0.4s ease-out;
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

@keyframes slideInRight {
  from {
    opacity: 0;
    transform: translateX(30px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}
</style>
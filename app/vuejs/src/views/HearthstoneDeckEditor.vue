<template>
  <div class="hearthstone-deck-editor">
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
                    {{ currentDeck.id ? 'Éditer le deck' : 'Créer un deck' }}
                </h1>
                <div class="deck-info-quick" v-if="currentDeck.name">
                <span class="deck-name-display">{{ currentDeck.name }}</span>
                <span class="separator">•</span>
                <span class="class-display">{{ getClassDisplayName(currentDeck.hearthstoneClass) }}</span>
                <span class="separator">•</span>
                <span class="format-display">{{ currentDeck.format?.toUpperCase() }}</span>
                </div>
              </div>
            </div>
            <div class="header-actions">
              <!-- NOUVEAUX BOUTONS DECK -->
              <Button 
                icon="pi pi-copy"
                class="deck-action-btn copy-btn"
                @click="copyDeckcode"
                v-tooltip="'Copier le deckcode'"
                :disabled="deckCards.length === 0"
              />
              <div class="visibility-toggle">
                <label class="toggle-label"></label>
                <div class="toggle-container" :class="{ 'disabled': !isDeckValid }">
                  <button 
                    class="toggle-btn"
                    :class="{ 
                      'public': currentDeck.isPublic, 
                      'disabled': !isDeckValid,
                      'success': justToggled 
                    }"
                    @click="toggleVisibility"
                    :disabled="!isDeckValid"
                    :title="!isDeckValid ? 'Le deck doit être valide pour être rendu public' : ''"
                  >
                    <div class="toggle-slider">
                      <div class="toggle-indicator"></div>
                    </div>
                    <div class="toggle-labels">
                      <span class="label-private" :class="{ 'active': !currentDeck.isPublic }">
                        <i class="pi pi-lock"></i> Privé
                      </span>
                      <span class="label-public" :class="{ 'active': currentDeck.isPublic }">
                        <i class="pi pi-globe"></i> Public
                      </span>
                    </div>
                  </button>
                </div>
              </div>
              <Button 
                label="Sauvegarder"
                icon="pi pi-save"
                class="deck-action-btn save-btn emerald-button primary"
                @click="saveDeck"
                :loading="isSaving"
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
    <div class="deck-title-editable">
        <input 
        v-model="currentDeck.name" 
        class="deck-name-input-panel"
        placeholder="Nom du deck"
        @blur="saveDeckMetadata"
        />
        <Dropdown 
        v-model="currentDeck.hearthstoneClass"
        :options="hearthstoneClasses"
        optionLabel="name"
        optionValue="value"
        class="class-selector-panel"
        @change="onClassChange"
        />
    </div>
    <div class="deck-stats">
      <div class="stat-item">
        <span class="stat-label">Total</span>
        <span class="stat-value">{{ totalCardsInDeck }}/{{ maxCardsForGame }}</span>
      </div>
      <div class="stat-item">
        <span class="stat-label">Coût total</span>
        <span class="stat-value">{{ totalDustCost }}</span>
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

    <!-- Ajoutez cette modale dans le template -->
    <Dialog 
      v-model:visible="showSaveConfirmation"
      modal
      header="Confirmer la sauvegarde"
      class="emerald-modal save-confirmation-modal"
      :style="{ width: '100%', maxWidth: '500px' }"
    >
      <div class="save-confirmation-content">
        
        <!-- Statut du deck -->
        <div class="deck-status-section">
          <div class="status-header">
            <i :class="isDeckValid ? 'pi pi-check-circle' : 'pi pi-exclamation-triangle'"
              :style="{ color: isDeckValid ? 'var(--primary)' : 'var(--accent)' }">
            </i>
            <h3 class="status-title">
              {{ isDeckValid ? 'Deck valide' : 'Deck invalide' }}
            </h3>
          </div>
          
          <div v-if="!isDeckValid" class="validation-errors">
            <ul class="error-list">
              <li v-for="error in getValidationErrors()" :key="error" class="error-item">
                {{ error }}
              </li>
            </ul>
          </div>
          
          <div v-else class="validation-success">
            <p>Votre deck respecte toutes les règles de construction Hearthstone.</p>
          </div>
        </div>

        <!-- Statut de visibilité -->
        <div class="visibility-status-section">
          <div class="visibility-info">
            <i :class="currentDeck.isPublic ? 'pi pi-globe' : 'pi pi-lock'"
              :style="{ color: currentDeck.isPublic ? 'var(--primary)' : 'var(--text-secondary)' }">
            </i>
            <span class="visibility-text">
              {{ currentDeck.isPublic ? 'Deck public' : 'Deck privé' }}
            </span>
          </div>
          <p class="visibility-description">
            {{ currentDeck.isPublic 
              ? 'Ce deck sera visible par tous les utilisateurs de la communauté.' 
              : 'Ce deck ne sera visible que par vous dans votre collection personnelle.' 
            }}
          </p>
        </div>

      </div>

      <template #footer>
        <div class="modal-actions">
          <Button
            label="Annuler"
            icon="pi pi-times"
            class="emerald-outline-btn cancel"
            @click="showSaveConfirmation = false"
          />
          <Button
            label="Confirmer la sauvegarde"
            icon="pi pi-check"
            class="emerald-button primary"
            @click="confirmSave"
            :loading="isSaving"
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
  visibility: 'private',
  hearthstoneClass: 'mage'
})

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
const isPublishing = ref(false)

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
const isEditing = computed(() => {
  // ✅ On est toujours en mode édition si on a un deck ID
  return !!currentDeck.value.id
})
const gameDisplayName = computed(() => {
  const names = {
    'hearthstone': 'Hearthstone',
    'magic': 'Magic: The Gathering',
    'pokemon': 'Pokemon TCG'
  }
  return names[currentDeck.value.game] || 'Jeu inconnu'
})

const totalDustCost = computed(() => {
  if (currentDeck.value.game !== 'hearthstone') return '0'
  
  const dustCosts = {
    'common': 40,
    'rare': 100, 
    'epic': 400,
    'legendary': 1600
  }
  
  return deckCards.value.reduce((total, entry) => {
    const rarity = entry.card.rarity?.toLowerCase() || 'common'
    const cardCost = dustCosts[rarity] || 40
    return total + (cardCost * entry.quantity)
  }, 0).toLocaleString()
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

const canPublish = computed(() => {
  return currentDeck.value.name?.trim() && 
         currentDeck.value.game && 
         deckCards.value.length > 0 &&
         totalCardsInDeck.value === maxCardsForGame.value
})

// Filtrage des cartes
const filteredCards = computed(() => {
  let cards = availableCards.value

    // 🆕 FILTRAGE PAR CLASSE HEARTHSTONE
    if (currentDeck.value.game === 'hearthstone' && currentDeck.value.hearthstoneClass) {
    cards = cards.filter(card => 
        card.cardClass === currentDeck.value.hearthstoneClass || 
        card.cardClass === 'neutral'  // Cartes neutres
    )
    }

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

const getClassDisplayName = (classValue) => {
  const classObj = hearthstoneClasses.value.find(c => c.value === classValue)
  return classObj ? classObj.name : classValue
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
  
  // Vérification limite totale du deck
  if (totalCardsInDeck.value >= maxCardsForGame.value) {
    return false
  }
  
  // Vérification limite par carte
  if (currentQuantity >= maxQuantity) {
    return false
  }
  
  return true
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
        visibility: deck.isPublic ? 'public' : 'private',
        hearthstoneClass: deck.hearthstoneClass || 'mage'
      }
      
      // Charger les cartes du deck si présentes
      deckCards.value = deck.cards || []
      
      console.log('✅ Deck chargé avec ID:', currentDeck.value.id)
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

const saveDeck = () => {
  if (!validateDeck()) return
  showSaveConfirmation.value = true
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

const isDeckValid = computed(() => {
  if (deckCards.value.length === 0) return false
  if (totalCardsInDeck.value !== 30) return false
  
  // Vérifier les limites par carte
  for (const entry of deckCards.value) {
    const card = entry.card
    const quantity = entry.quantity
    
    if (card.rarity?.toLowerCase() === 'legendary' && quantity > 1) {
      return false
    }
    if (card.rarity?.toLowerCase() !== 'legendary' && quantity > 2) {
      return false
    }
    
    // Vérifier format
    if (currentDeck.value.format === 'standard' && !card.isStandardLegal) {
      return false
    }
  }
  
  return true
})

const justToggled = ref(false)

const toggleVisibility = () => {
  if (!isDeckValid.value) {
    toast.add({
      severity: 'warn',
      summary: 'Deck invalide',
      detail: 'Ce deck ne peut pas être rendu public car il ne respecte pas les règles de construction',
      life: 4000
    })
    return
  }
  
  currentDeck.value.isPublic = !currentDeck.value.isPublic
  
  // Animation de succès
  justToggled.value = true
  setTimeout(() => {
    justToggled.value = false
  }, 600)
  
  toast.add({
    severity: 'success',
    summary: currentDeck.value.isPublic ? 'Deck rendu public' : 'Deck rendu privé',
    detail: currentDeck.value.isPublic ? 'Visible par la communauté' : 'Visible uniquement par vous',
    life: 2000
  })
}

const getValidationErrors = () => {
  const errors = []
  
  if (totalCardsInDeck.value !== 30) {
    errors.push(`Nombre de cartes incorrect: ${totalCardsInDeck.value}/30`)
  }
  
  for (const entry of deckCards.value) {
    const card = entry.card
    const quantity = entry.quantity
    
    if (card.rarity?.toLowerCase() === 'legendary' && quantity > 1) {
      errors.push(`Trop d'exemplaires d'une carte légendaire: ${card.name}`)
    }
    if (card.rarity?.toLowerCase() !== 'legendary' && quantity > 2) {
      errors.push(`Trop d'exemplaires d'une carte: ${card.name} (max 2)`)
    }
    
    if (currentDeck.value.format === 'standard' && !card.isStandardLegal) {
      errors.push(`Carte non légale en Standard: ${card.name}`)
    }
  }
  
  return errors
}

const onClassChange = () => {
  // Vérifier s'il y a des cartes incompatibles dans le deck
  const incompatibleCards = deckCards.value.filter(entry => {
    const card = entry.card
    return card.cardClass !== currentDeck.value.hearthstoneClass && 
           card.cardClass !== 'NEUTRAL'  // ← CORRECTION
  })

  if (incompatibleCards.length > 0) {
    console.log(`${incompatibleCards.length} cartes incompatibles détectées`)
    // TODO: Ajouter dialog de confirmation plus tard
  }

  // Sauvegarder les métadonnées
  saveDeckMetadata()
}

const saveDeckMetadata = async () => {
  if (!currentDeck.value.id) return

  try {
    await api.put(`/api/decks/${currentDeck.value.id}/metadata`, {
      title: currentDeck.value.name,
      hearthstoneClass: currentDeck.value.hearthstoneClass
    })
  } catch (error) {
    console.error('Erreur sauvegarde métadonnées:', error)
  }
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

const publishDeck = async () => {
  if (!validateDeck()) return

  try {
    isPublishing.value = true
    
    const deckData = {
      name: currentDeck.value.name.trim(),
      description: currentDeck.value.description?.trim(),
      game: currentDeck.value.game,
      format: currentDeck.value.format,
      visibility: currentDeck.value.visibility,
      isPublic: true, // ← RENDU PUBLIC
      cards: deckCards.value.map(entry => ({
        cardId: entry.card.id,
        quantity: entry.quantity
      }))
    }

    if (isEditing.value) {
      await api.put(`/api/decks/${route.params.id}`, deckData)
    } else {
      const response = await api.post('/api/decks', deckData)
      currentDeck.value.id = response.data.id
    }

    toast.add({
      severity: 'success',
      summary: 'Deck publié',
      detail: 'Votre deck est maintenant visible par la communauté',
      life: 3000
    })

  } catch (error) {
    console.error('Erreur publication:', error)
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: error.response?.data?.message || 'Erreur lors de la publication',
      life: 3000
    })
  } finally {
    isPublishing.value = false
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

const copyDeckcode = () => {
  toast.add({
    severity: 'info',
    summary: 'Deckcode copié',
    detail: 'Fonctionnalité bientôt disponible...',
    life: 2000
  })
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

const showSaveConfirmation = ref(false)

const confirmSave = async () => {
  if (!validateDeck()) return

  try {
    isSaving.value = true
    
    const deckData = {
      title: currentDeck.value.name.trim(),
      description: currentDeck.value.description?.trim(),
      hearthstoneClass: currentDeck.value.hearthstoneClass,
      isPublic: currentDeck.value.isPublic, // ✅ AJOUT : Sauvegarder le statut public
      cards: deckCards.value.map(entry => ({
        cardId: entry.card.id,
        quantity: entry.quantity
      }))
    }

    if (currentDeck.value.id) {
      await api.put(`/api/decks/${currentDeck.value.id}`, deckData)
      
      showSaveConfirmation.value = false
      
      toast.add({
        severity: 'success',
        summary: 'Deck sauvegardé',
        detail: `"${currentDeck.value.name}" a été mis à jour avec succès`,
        life: 3000
      })
      
      // ✅ REDIRECTION vers Mes Decks
      router.push('/mes-decks')
      
    } else {
      console.error('❌ Aucun ID de deck - impossible de sauvegarder')
      toast.add({
        severity: 'error',
        summary: 'Erreur',
        detail: 'Impossible de sauvegarder : deck non initialisé',
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

.hearthstone-deck-editor{
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

.deck-name-input {
  background: transparent;
  border: none;
  color: var(--primary);
  font-weight: 600;
  font-size: 0.85rem;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  transition: all 0.2s ease;
  min-width: 150px;
}

.deck-name-input:focus {
  outline: none;
  background: rgba(38, 166, 154, 0.1);
  border: 1px solid var(--primary);
}

:deep(.class-selector) {
  min-width: 140px;
}

:deep(.class-selector .p-dropdown-label) {
  font-weight: 500;
  color: var(--text-primary);
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

/* CSS pour les nouveaux boutons d'action deck */
.header-actions {
  display: flex;
  gap: 0.75rem;
  align-items: center;
}

:deep(.deck-action-btn) {
  border-radius: 8px !important;
  font-weight: 500 !important;
  transition: all var(--transition-fast) !important;
  min-height: 40px !important;
}

:deep(.copy-btn) {
  background: none !important;
  border: 2px solid var(--surface-300) !important;
  color: var(--text-secondary) !important;
  width: 44px !important;
  padding: 0 !important;
}

:deep(.copy-btn:hover) {
  border-color: var(--primary) !important;
  color: var(--primary) !important;
  background: rgba(38, 166, 154, 0.1) !important;
}

:deep(.publish-btn) {
  background: #3b82f6 !important;
  border: 2px solid #3b82f6 !important;
  color: white !important;
  padding: 0.5rem 1rem !important;
}

:deep(.publish-btn:hover) {
  background: #2563eb !important;
  border-color: #2563eb !important;
  transform: translateY(-1px) !important;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3) !important;
}

:deep(.save-btn) {
  padding: 0.5rem 1rem !important;
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
  top: 260px; /* ← CORRECTION : coller sous le AppHeader au lieu de top: 0 */
  height: calc(100vh - 260px); /* ← AJUSTER la hauteur en conséquence */
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

.deck-title-editable {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin-bottom: 0.75rem;
}

.deck-name-input-panel {
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid #4a5568;
  color: #f7fafc;
  font-weight: 700;
  font-size: 1.2rem;
  padding: 0.5rem 0.75rem;
  border-radius: 6px;
  transition: all 0.2s ease;
  text-align: center;
}

.deck-name-input-panel:focus {
  outline: none;
  border-color: #68d391;
  background: rgba(104, 211, 145, 0.1);
}

:deep(.class-selector-panel) {
  width: 100%;
}

:deep(.class-selector-panel .p-dropdown) {
  background: rgba(255, 255, 255, 0.1) !important;
  border: 1px solid #4a5568 !important;
  color: #f7fafc !important;
}

:deep(.class-selector-panel .p-dropdown:hover) {
  border-color: #68d391 !important;
}

/* === SAVE CONFIRMATION MODAL === */

:deep(.save-confirmation-modal .p-dialog) {
  border-radius: var(--border-radius-large) !important;
  overflow: hidden !important;
}

:deep(.save-confirmation-modal .p-dialog-header) {
  background: var(--emerald-gradient) !important;
  color: white !important;
  padding: 1.5rem !important;
  border-bottom: none !important;
}

:deep(.save-confirmation-modal .p-dialog-content) {
  padding: 0 !important;
}

.save-confirmation-content {
  padding: 2rem;
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.deck-status-section {
  background: var(--surface-50);
  border-radius: var(--border-radius);
  padding: 1.5rem;
  border-left: 4px solid var(--primary);
}

.status-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.status-header i {
  font-size: 1.5rem;
}

.status-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0;
}

.validation-errors {
  margin-top: 1rem;
}

.error-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.error-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--accent);
  font-size: 0.9rem;
  font-weight: 500;
}

.error-item::before {
  content: '•';
  color: var(--accent);
  font-weight: bold;
}

.validation-success {
  color: var(--primary);
  font-weight: 500;
}

.validation-success p {
  margin: 0;
}

.visibility-status-section {
  background: var(--surface-100);
  border-radius: var(--border-radius);
  padding: 1.5rem;
  border-left: 4px solid var(--secondary);
}

.visibility-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 0.75rem;
}

.visibility-info i {
  font-size: 1.25rem;
}

.visibility-text {
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--text-primary);
}

.visibility-description {
  color: var(--text-secondary);
  font-size: 0.9rem;
  line-height: 1.5;
  margin: 0;
}

/* Actions de la modale */
:deep(.save-confirmation-modal .p-dialog-footer) {
  background: var(--surface-50) !important;
  padding: 1.5rem !important;
  border-top: 1px solid var(--surface-200) !important;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  width: 100%;
}

/* Responsive */
@media (max-width: 640px) {
  .save-confirmation-content {
    padding: 1.5rem;
    gap: 1.5rem;
  }
  
  .deck-status-section,
  .visibility-status-section {
    padding: 1rem;
  }
  
  .modal-actions {
    flex-direction: column;
    gap: 0.75rem;
  }
  
  :deep(.modal-actions .p-button) {
    width: 100% !important;
  }
}
</style>
<template>
  <div class="magic-deck-editor">
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
                <span class="colors-display">{{ getColorsDisplayName(currentDeck.colorIdentity) }}</span>
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
              Bibliothèque de cartes Magic
            </h3>
            <div class="library-stats">
              <span class="available-cards">{{ filteredCards.length }} cartes disponibles</span>
            </div>
          </div>

          <!-- Filtres de la library Magic -->
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
                v-model="filters.cmc"
                :options="cmcOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="CMC"
                class="cmc-filter"
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
              
              <Dropdown 
                v-model="filters.colors"
                :options="colorOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="Couleurs"
                class="color-filter"
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
              <p>Chargement des cartes Magic...</p>
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

        <!-- Panneau deck : Decklist Magic -->
        <div class="deck-panel">
          <!-- Header du deck -->
          <div class="deck-header">
            <div class="deck-title-editable">
              <input 
                v-model="currentDeck.name" 
                class="deck-name-input-panel"
                placeholder="Nom du deck Magic"
                @blur="saveDeckMetadata"
              />
              <div class="color-identity-selector">
                <div class="color-identity-display">
                  <span class="identity-label">Identité de couleur:</span>
                  <div class="mana-symbols">
                    <div 
                      v-for="color in ['W', 'U', 'B', 'R', 'G']"
                      :key="color"
                      class="mana-symbol"
                      :class="{ 'selected': isColorSelected(color) }"
                      @click="toggleColor(color)"
                    >
                      {{ color }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="deck-stats">
              <div class="stat-item">
                <span class="stat-label">Total</span>
                <span class="stat-value">{{ totalCardsInDeck }}/{{ maxCardsForFormat }}</span>
              </div>
              <div class="stat-item">
                <span class="stat-label">CMC Moyen</span>
                <span class="stat-value">{{ averageCMC }}</span>
              </div>
              <div v-if="currentDeck.format === 'commander'" class="stat-item commander-stat">
                <span class="stat-label">Commandant</span>
                <span class="stat-value" :class="{ 'missing': !hasCommander }">
                  {{ hasCommander ? '✓' : '✗' }}
                </span>
              </div>
            </div>
          </div>

          <!-- Contenu du deck -->
          <div class="deck-content">
            <div v-if="deckCards.length === 0" class="empty-deck">
              <i class="pi pi-clone empty-icon"></i>
              <p>Votre deck Magic apparaîtra ici</p>
              <small>Cliquez sur les cartes pour les ajouter</small>
            </div>
            
            <div v-else class="deck-list">
              
              <!-- Section Commandant (Commander uniquement) -->
              <div v-if="currentDeck.format === 'commander'" class="commander-section">
                <div class="section-header">
                  <h4 class="section-title">
                    <i class="pi pi-crown"></i>
                    Commandant
                  </h4>
                </div>
                <div class="commander-slot">
                  <div v-if="commanderCard" class="commander-card" @click="removeCardFromDeck(commanderCard)">
                    <div class="card-cost">{{ commanderCard.card.cmc || 0 }}</div>
                    <div class="card-info">
                      <div class="card-name legendary">{{ commanderCard.card.name }}</div>
                      <div class="card-type">{{ commanderCard.card.typeLine }}</div>
                    </div>
                    <div class="commander-indicator">⚔️</div>
                  </div>
                  <div v-else class="commander-placeholder">
                    <i class="pi pi-plus"></i>
                    <span>Sélectionner un commandant</span>
                  </div>
                </div>
              </div>

              <!-- Sections par type de carte -->
              <div 
                v-for="section in deckSections" 
                :key="section.type"
                class="deck-section"
                v-show="section.cards.length > 0"
              >
                <div class="section-header">
                  <h4 class="section-title">
                    <i :class="section.icon"></i>
                    {{ section.name }} ({{ section.cards.length }})
                  </h4>
                </div>
                <div class="section-cards">
                  <div 
                    v-for="entry in section.cards" 
                    :key="entry.card.id"
                    class="deck-card-entry"
                    @click="removeCardFromDeck(entry)"
                  >
                    <div class="card-cost">{{ entry.card.cmc || 0 }}</div>
                    <div class="card-info">
                      <div class="card-name" :class="{ 'legendary': entry.card.isLegendary }">
                        {{ entry.card.name }}
                      </div>
                    </div>
                    <div class="card-quantity" v-if="currentDeck.format !== 'commander'">
                      x{{ entry.quantity }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>

    </div>

    <!-- Modal confirmation sauvegarde -->
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
            <p>Votre deck respecte toutes les règles de construction Magic.</p>
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

// Stores et composables
const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const gameFilterStore = useGameFilterStore()
const toast = useToast()

// Props depuis l'URL
const props = defineProps({
  gameSlug: String,
  formatSlug: String, 
  deckSlug: String
})

// State principal
const currentDeck = ref({
  id: null,
  name: '',
  description: '',
  game: 'magic',
  format: 'standard',
  visibility: 'private',
  colorIdentity: [] // Couleurs Magic : ['W', 'U', 'B', 'R', 'G']
})

const deckCards = ref([]) // [{ card: CardObject, quantity: number }]
const availableCards = ref([])
const isLoading = ref(false)
const isLoadingCards = ref(false)
const isSaving = ref(false)

// State UI
const showSaveConfirmation = ref(false)

// Filtres de la library Magic
const cardSearch = ref('')
const filters = ref({
  cmc: null,        // Converted Mana Cost
  rarity: null,     // common, uncommon, rare, mythic
  cardType: null,   // creature, instant, sorcery, etc.
  colors: null      // Couleurs : W, U, B, R, G, C (colorless)
})

// Pagination
const currentPage = ref(0)
const cardsPerPage = ref(30)

// Computed
const isEditing = computed(() => !!currentDeck.value.id)

const gameDisplayName = computed(() => 'Magic: The Gathering')

const maxCardsForFormat = computed(() => {
  return currentDeck.value.format === 'commander' ? 100 : 60
})

const totalCardsInDeck = computed(() => {
  return deckCards.value.reduce((total, entry) => total + entry.quantity, 0)
})

const averageCMC = computed(() => {
  if (deckCards.value.length === 0) return '0.0'
  const totalCMC = deckCards.value.reduce((sum, entry) => 
    sum + (entry.card.cmc || 0) * entry.quantity, 0
  )
  return (totalCMC / totalCardsInDeck.value).toFixed(1)
})

const hasCommander = computed(() => {
  return currentDeck.value.format === 'commander' && commanderCard.value !== null
})

const commanderCard = computed(() => {
  return deckCards.value.find(entry => entry.card.canBeCommander) || null
})

// Filtrage des cartes Magic
const filteredCards = computed(() => {
  let result = availableCards.value

  // Filtrage par nom
  if (cardSearch.value.trim()) {
    const searchTerm = cardSearch.value.toLowerCase()
    result = result.filter(card => 
      card.name.toLowerCase().includes(searchTerm)
    )
  }

  // Filtrage par CMC (Converted Mana Cost)
  if (filters.value.cmc !== null) {
    if (filters.value.cmc === '7+') {
      result = result.filter(card => (card.cmc || 0) >= 7)
    } else {
      result = result.filter(card => (card.cmc || 0) === filters.value.cmc)
    }
  }

  // Filtrage par rareté
  if (filters.value.rarity) {
    result = result.filter(card => 
      card.rarity?.toLowerCase() === filters.value.rarity.toLowerCase()
    )
  }

  // Filtrage par type de carte
  if (filters.value.cardType) {
    result = result.filter(card => 
      card.cardType?.toLowerCase() === filters.value.cardType.toLowerCase()
    )
  }

  // ❌ SUPPRIMÉ: Filtrage par couleur car Magic n'a pas de restriction de couleurs
  // Les joueurs peuvent ajouter n'importe quelle couleur à leur deck

  // Filtrage par format (légalité)
  if (currentDeck.value.format === 'standard') {
    result = result.filter(card => card.isStandardLegal)
  } else if (currentDeck.value.format === 'commander') {
    result = result.filter(card => card.isCommanderLegal)
  }

  return result
})

const paginatedCards = computed(() => {
  const start = currentPage.value
  const end = start + cardsPerPage.value
  return filteredCards.value.slice(start, end)
})

const totalPages = computed(() => {
  return Math.ceil(filteredCards.value.length / cardsPerPage.value)
})

// Options pour les filtres Magic
const cmcOptions = computed(() => [
  { label: 'Tous les CMC', value: null },
  { label: '0', value: 0 },
  { label: '1', value: 1 },
  { label: '2', value: 2 },
  { label: '3', value: 3 },
  { label: '4', value: 4 },
  { label: '5', value: 5 },
  { label: '6', value: 6 },
  { label: '7+', value: '7+' }
])

const rarityOptions = computed(() => [
  { label: 'Toutes les raretés', value: null },
  { label: 'Commune', value: 'common' },
  { label: 'Peu commune', value: 'uncommon' },
  { label: 'Rare', value: 'rare' },
  { label: 'Mythique', value: 'mythic' }
])

const typeOptions = computed(() => [
  { label: 'Tous les types', value: null },
  { label: 'Créature', value: 'creature' },
  { label: 'Instantané', value: 'instant' },
  { label: 'Rituel', value: 'sorcery' },
  { label: 'Enchantement', value: 'enchantment' },
  { label: 'Artefact', value: 'artifact' },
  { label: 'Planeswalker', value: 'planeswalker' },
  { label: 'Terrain', value: 'land' }
])

const colorOptions = computed(() => [
  { label: 'Toutes les couleurs', value: null },
  { label: 'Blanc (W)', value: 'W' },
  { label: 'Bleu (U)', value: 'U' },
  { label: 'Noir (B)', value: 'B' },
  { label: 'Rouge (R)', value: 'R' },
  { label: 'Vert (G)', value: 'G' },
  { label: 'Incolore', value: 'colorless' }
])

// Organisation de la decklist par sections Magic
const deckSections = computed(() => {
  const sections = [
    { 
      type: 'planeswalker', 
      name: 'Planeswalkers', 
      icon: 'pi pi-star',
      cards: getCardsByType('planeswalker') 
    },
    { 
      type: 'creature', 
      name: 'Créatures', 
      icon: 'pi pi-users',
      cards: getCardsByType('creature') 
    },
    { 
      type: 'instant', 
      name: 'Instantanés', 
      icon: 'pi pi-bolt',
      cards: getCardsByType('instant') 
    },
    { 
      type: 'sorcery', 
      name: 'Rituels', 
      icon: 'pi pi-book',
      cards: getCardsByType('sorcery') 
    },
    { 
      type: 'enchantment', 
      name: 'Enchantements', 
      icon: 'pi pi-sparkles',
      cards: getCardsByType('enchantment') 
    },
    { 
      type: 'artifact', 
      name: 'Artefacts', 
      icon: 'pi pi-cog',
      cards: getCardsByType('artifact') 
    },
    { 
      type: 'land', 
      name: 'Terrains', 
      icon: 'pi pi-map',
      cards: getCardsByType('land') 
    }
  ]
  
  // Filtrer les sections vides et trier par CMC
  return sections.map(section => ({
    ...section,
    cards: section.cards.sort((a, b) => {
      const cmcDiff = (a.card.cmc || 0) - (b.card.cmc || 0)
      if (cmcDiff !== 0) return cmcDiff
      return a.card.name.localeCompare(b.card.name)
    })
  }))
})

// Validation Magic
const isDeckValid = computed(() => {
  if (deckCards.value.length === 0) return false
  
  if (currentDeck.value.format === 'commander') {
    return isValidCommanderDeck.value
  } else {
    return isValidStandardDeck.value
  }
})

const isValidStandardDeck = computed(() => {
  // Standard : minimum 60 cartes, max 4 exemplaires
  if (totalCardsInDeck.value < 60) return false
  
  for (const entry of deckCards.value) {
    if (entry.quantity > 4) return false
    if (currentDeck.value.format === 'standard' && !entry.card.isStandardLegal) return false
  }
  
  return true
})

const isValidCommanderDeck = computed(() => {
  // Commander : exactement 100 cartes, max 1 exemplaire, 1 commandant
  if (totalCardsInDeck.value !== 100) return false
  if (!hasCommander.value) return false
  
  for (const entry of deckCards.value) {
    if (!entry.card.canBeCommander && entry.quantity > 1) return false
    if (!entry.card.isCommanderLegal) return false
  }
  
  return true
})

const justToggled = ref(false)

// Méthodes utilitaires
const getCardsByType = (type) => {
    console.log(`🔍 DIAGNOSTIC - Recherche type "${type}" dans:`, deckCards.value.map(entry => ({
    name: entry.card.name,
    cardType: entry.card.cardType,
    typeLine: entry.card.typeLine
  })))
  
  if (currentDeck.value.format === 'commander') {
    // En Commander : exclure le commandant des sections normales
    return deckCards.value.filter(entry => 
      entry.card.cardType === type && !entry.card.canBeCommander
    )
  } else {
    // En Standard : toutes les cartes du type, même les légendaires
    return deckCards.value.filter(entry => 
      entry.card.cardType === type
    )
  }
}

const getCardQuantity = (cardId) => {
  const entry = deckCards.value.find(entry => entry.card.id === cardId)
  return entry ? entry.quantity : 0
}

const getMaxQuantity = (card) => {
  if (currentDeck.value.format === 'commander') {
    return card.canBeCommander ? 1 : 1 // Commander : 1 de chaque
  } else {
    return 4 // Standard : max 4 exemplaires
  }
}

const canAddCard = (card) => {
  const currentQuantity = getCardQuantity(card.id)
  const maxQuantity = getMaxQuantity(card)
  
  // Vérification limite totale du deck
  if (totalCardsInDeck.value >= maxCardsForFormat.value) {
    return false
  }
  
  // Vérification limite par carte
  if (currentQuantity >= maxQuantity) {
    return false
  }
  
  // En Commander, vérifier qu'on n'a qu'un seul commandant
  if (currentDeck.value.format === 'commander' && card.canBeCommander && hasCommander.value) {
    return false
  }
  
  return true
}

const addCardToDeck = (card) => {
  const existingEntry = deckCards.value.find(entry => entry.card.id === card.id)
  const maxQuantity = getMaxQuantity(card)
  
  if (existingEntry) {
    if (existingEntry.quantity < maxQuantity) {
      existingEntry.quantity++
      updateDeckColorIdentity() 
    } else {
      toast.add({
        severity: 'warn',
        summary: 'Limite atteinte',
        detail: `Maximum ${maxQuantity} exemplaires de ${card.name}`,
        life: 2000
      })
      return
    }
  } else {
    deckCards.value.push({
      card: card,
      quantity: 1
    })
    updateDeckColorIdentity()
  }
}

const removeCardFromDeck = (entry) => {
  if (entry.quantity > 1) {
    entry.quantity--
  } else {
    const index = deckCards.value.findIndex(e => e.card.id === entry.card.id)
    if (index > -1) {
      deckCards.value.splice(index, 1)
    }
  }
  
  updateDeckColorIdentity() 
}

const updateDeckColorIdentity = () => {
  const allColors = new Set()
  
  deckCards.value.forEach(entry => {
    if (entry.card.colorIdentity && Array.isArray(entry.card.colorIdentity)) {
      entry.card.colorIdentity.forEach(color => allColors.add(color))
    }
  })
  
  const colorOrder = ['W', 'U', 'B', 'R', 'G']
  currentDeck.value.colorIdentity = colorOrder.filter(color => allColors.has(color))
  
  console.log('🎨 Couleurs deck mises à jour:', currentDeck.value.colorIdentity)
}

const toggleColor = (color) => {
  const colors = [...currentDeck.value.colorIdentity]
  const index = colors.indexOf(color)
  
  if (index > -1) {
    colors.splice(index, 1)
  } else {
    colors.push(color)
  }
  
  currentDeck.value.colorIdentity = colors.sort()
}

const getColorsDisplayName = (colorIdentity) => {
  if (!colorIdentity || colorIdentity.length === 0) return 'Incolore'
  return colorIdentity.join('')
}

const isColorSelected = (color) => {
  return currentDeck.value.colorIdentity.includes(color)
}

// Chargement et sauvegarde
const loadDeckBySlug = async (gameSlug, formatSlug, deckSlug) => {
  try {
    isLoading.value = true
    console.log(`🔍 Chargement deck Magic: ${gameSlug}/${formatSlug}/${deckSlug}`)
    
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
        isPublic: deck.isPublic, 
        colorIdentity: deck.colorIdentity || [] 
      }
      
      deckCards.value = deck.cards ? deck.cards.map(cardEntry => ({
        card: cardEntry.card,
        quantity: cardEntry.quantity
      })) : []
      console.log('🔍 DIAGNOSTIC - Cartes deck rechargées:', deckCards.value)
      console.log('🔍 DIAGNOSTIC - Structure première carte deck:', deckCards.value[0]?.card)
      console.log('🔍 DIAGNOSTIC - CardType première carte deck:', deckCards.value[0]?.card?.cardType)
      console.log('✅ Deck Magic chargé avec ID:', currentDeck.value.id)
      console.log('🃏 Cartes chargées:', deckCards.value.length)
    }
  } catch (error) {
    console.error('💥 Erreur chargement deck Magic:', error)
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
    const format = currentDeck.value.format
    
    console.log(`🃏 Chargement cartes Magic pour: ${gameSlug} (${format})`)
    
    const response = await api.get(`/api/cards/${gameSlug}?format=${format}&limit=10000`)
    availableCards.value = response.data

    console.log('🔍 DIAGNOSTIC - Structure carte API:', availableCards.value[0])
    console.log('🔍 DIAGNOSTIC - CardType première carte:', availableCards.value[0]?.cardType)
    console.log('🔍 DIAGNOSTIC - TypeLine première carte:', availableCards.value[0]?.typeLine)
    
    console.log(`✅ ${availableCards.value.length} cartes Magic chargées`)
  } catch (error) {
    console.error('Erreur chargement cartes Magic:', error)
    loadMockMagicCards()
  } finally {
    isLoadingCards.value = false
  }
}

const loadMockMagicCards = () => {
  // Données de test Magic
  availableCards.value = [
    {
      id: 1,
      name: 'Lightning Bolt',
      manaCost: '{R}',
      cmc: 1,
      rarity: 'common',
      cardType: 'instant',
      colors: ['R'],
      colorIdentity: ['R'],
      typeLine: 'Instant',
      text: 'Lightning Bolt deals 3 damage to any target.',
      imageUrl: 'magic/cards/lightning_bolt.jpg',
      isStandardLegal: true,
      isCommanderLegal: true,
      canBeCommander: false
    },
    {
      id: 2,
      name: 'Sol Ring',
      manaCost: '{1}',
      cmc: 1,
      rarity: 'uncommon',
      cardType: 'artifact',
      colors: [],
      colorIdentity: [],
      typeLine: 'Artifact',
      text: '{T}: Add {C}{C}.',
      imageUrl: 'magic/cards/sol_ring.jpg',
      isStandardLegal: false,
      isCommanderLegal: true,
      canBeCommander: false
    }
  ]
}

const saveDeck = () => {
  if (!validateDeck()) return
  showSaveConfirmation.value = true
}

const validateDeck = () => {
  if (!currentDeck.value.name?.trim()) {
    toast.add({
      severity: 'warn',
      summary: 'Nom requis',
      detail: 'Le deck doit avoir un nom',
      life: 3000
    })
    return false
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
  
  return true
}

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
  
  if (currentDeck.value.format === 'commander') {
    if (totalCardsInDeck.value !== 100) {
      errors.push(`Nombre de cartes incorrect: ${totalCardsInDeck.value}/100`)
    }
    
    if (!hasCommander.value) {
      errors.push('Un commandant légendaire est requis')
    }
    
    // Vérifier doublons (sauf terrains de base)
    for (const entry of deckCards.value) {
      if (!entry.card.canBeCommander && entry.quantity > 1 && !entry.card.typeLine?.includes('Basic Land')) {
        errors.push(`Trop d'exemplaires: ${entry.card.name} (max 1 en Commander)`)
      }
    }
  } else {
    // Standard
    if (totalCardsInDeck.value < 60) {
      errors.push(`Pas assez de cartes: ${totalCardsInDeck.value}/60 minimum`)
    }
    
    for (const entry of deckCards.value) {
      if (entry.quantity > 4) {
        errors.push(`Trop d'exemplaires: ${entry.card.name} (max 4)`)
      }
      
      if (currentDeck.value.format === 'standard' && !entry.card.isStandardLegal) {
        errors.push(`Carte non légale en Standard: ${entry.card.name}`)
      }
    }
  }
  
  return errors
}

const saveDeckMetadata = async () => {
  if (!currentDeck.value.id) return

  try {
    await api.put(`/api/decks/${currentDeck.value.id}/metadata`, {
      title: currentDeck.value.name,
      colorIdentity: currentDeck.value.colorIdentity
    })
  } catch (error) {
    console.error('Erreur sauvegarde métadonnées:', error)
  }
}

const confirmSave = async () => {
  if (!validateDeck()) return

  try {
    isSaving.value = true
    
    const deckData = {
      title: currentDeck.value.name.trim(),
      description: currentDeck.value.description?.trim(),
      // ✅ CORRECTION: Envoyer colorIdentity au lieu de hearthstoneClass
      colorIdentity: currentDeck.value.colorIdentity,
      isPublic: currentDeck.value.isPublic,
      cards: deckCards.value.map(entry => ({
        cardId: entry.card.id,
        quantity: entry.quantity
      }))
    }

    console.log('🔍 Données Magic envoyées à l\'API:', {
      deckData,
      deckCards: deckCards.value,
      totalCards: deckCards.value.length,
      deckId: currentDeck.value.id
    })

    if (currentDeck.value.id) {
      console.log('🔍 DIAGNOSTIC - isPublic envoyé:', deckData.isPublic)
      console.log('🔍 DIAGNOSTIC - currentDeck.isPublic:', currentDeck.value.isPublic)
      const response = await api.put(`/api/decks/${currentDeck.value.id}`, deckData)
      
      console.log('✅ Réponse API sauvegarde Magic:', response.data)
      
      showSaveConfirmation.value = false
      
      toast.add({
        severity: 'success',
        summary: 'Deck sauvegardé',
        detail: `"${currentDeck.value.name}" a été mis à jour avec succès`,
        life: 3000
      })
      
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
    console.error('Erreur sauvegarde Magic:', error)
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

const goBack = () => {
  router.push('/decks')
}

const clearCardFilters = () => {
  cardSearch.value = ''
  filters.value = {
    cmc: null,
    rarity: null,
    cardType: null,
    colors: null
  }
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

const showCardPreview = (card) => {
  console.log('Preview Magic card:', card.name)
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
    // Mode création : Pré-configurer pour Magic
    currentDeck.value.game = 'magic'
    currentDeck.value.format = 'standard'
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
      visibility: deck.visibility,
      colorIdentity: deck.colorIdentity || []
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

// Watcher pour recharger les cartes si le format change
watch(() => currentDeck.value.format, (newFormat) => {
  if (newFormat) {
    loadCards()
  }
})
</script>

<style scoped>
/* === MAGIC DECK EDITOR - STYLES IDENTIQUES À HEARTHSTONE === */

.magic-deck-editor {
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

.colors-display {
  font-weight: 600;
  color: #8b4513;
  font-family: 'Courier New', monospace;
}

.separator {
  color: var(--surface-400);
}

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

:deep(.save-btn) {
  padding: 0.5rem 1rem !important;
}

/* Toggle visibility */
.visibility-toggle {
  display: flex;
  align-items: center;
}

.toggle-container {
  position: relative;
}

.toggle-btn {
  background: none;
  border: none;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  transition: all var(--transition-fast);
}

.toggle-btn.disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.toggle-slider {
  width: 50px;
  height: 24px;
  background: var(--surface-300);
  border-radius: 12px;
  position: relative;
  transition: all var(--transition-fast);
}

.toggle-btn.public .toggle-slider {
  background: var(--primary);
}

.toggle-indicator {
  width: 20px;
  height: 20px;
  background: white;
  border-radius: 50%;
  position: absolute;
  top: 2px;
  left: 2px;
  transition: all var(--transition-fast);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.toggle-btn.public .toggle-indicator {
  transform: translateX(26px);
}

.toggle-labels {
  display: flex;
  gap: 1rem;
  font-size: 0.75rem;
}

.label-private,
.label-public {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  color: var(--text-secondary);
  transition: all var(--transition-fast);
}

.label-private.active,
.label-public.active {
  color: var(--primary);
  font-weight: 600;
}

/* Header row */
.editor-header-row {
  background: white;
  border-bottom: 1px solid var(--surface-200);
  position: sticky;
  top: 0;
  z-index: 50;
  box-shadow: var(--shadow-small);
}

/* Layout principal - 2 colonnes */
.editor-layout {
  display: grid;
  grid-template-columns: 1fr 350px;
  min-height: calc(100vh - 80px);
  background: white;
}

/* Panneau library - colonne gauche */
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
  color: #8b4513;
}

.library-stats {
  color: #6b7280;
  font-size: 0.9rem;
}

.available-cards {
  font-weight: 600;
  color: #8b4513;
}

/* Filtres library Magic */
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
  border-color: #8b4513 !important;
  box-shadow: 0 0 0 3px rgba(139, 69, 19, 0.1) !important;
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

:deep(.cmc-filter),
:deep(.rarity-filter),
:deep(.type-filter),
:deep(.color-filter) {
  flex: 1;
  min-width: 100px;
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
  border-top: 3px solid #8b4513;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Grille cartes Magic - 5 colonnes */
.cards-grid {
  padding: 1.5rem;
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  grid-template-rows: repeat(6, minmax(220px, auto));
  gap: 1rem;
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

/* === DECK PANEL MAGIC === */
.deck-panel {
  background: #2d1b0e; /* Couleur Magic terre/brun */
  display: flex;
  flex-direction: column;
  position: sticky;
  top: 260px;
  height: calc(100vh - 260px);
  overflow-y: auto;
  z-index: 20;
}

.deck-header {
  background: linear-gradient(135deg, #4a2c17 0%, #2d1b0e 100%);
  padding: 1.5rem 1.25rem;
  border-bottom: 2px solid #6b4423;
  flex-shrink: 0;
}

.deck-title-editable {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin-bottom: 0.75rem;
}

.deck-name-input-panel {
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid #6b4423;
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
  border-color: #d97706;
  background: rgba(217, 119, 6, 0.1);
}

/* Sélecteur d'identité de couleur Magic */
.color-identity-selector {
  width: 100%;
}

.color-identity-display {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.identity-label {
  color: #cbd5e0;
  font-size: 0.8rem;
  text-transform: uppercase;
  font-weight: 500;
  text-align: center;
}

.mana-symbols {
  display: flex;
  justify-content: center;
  gap: 0.5rem;
}

.mana-symbol {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.2s ease;
  border: 2px solid #6b4423;
  background: rgba(255, 255, 255, 0.1);
  color: #cbd5e0;
}

.mana-symbol:hover {
  transform: scale(1.1);
  border-color: #d97706;
}

.mana-symbol.selected {
  border-color: #d97706;
  background: #d97706;
  color: white;
  box-shadow: 0 0 10px rgba(217, 119, 6, 0.5);
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

.commander-stat {
  border-left: 1px solid #6b4423;
  padding-left: 1rem;
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

.stat-value.missing {
  color: #ef4444;
}

.deck-content {
  flex: 1;
  padding: 1rem;
  overflow-y: auto;
}

/* Section Commandant */
.commander-section {
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #6b4423;
}

.section-header {
  margin-bottom: 0.75rem;
}

.section-title {
  color: #f7fafc;
  font-size: 0.9rem;
  font-weight: 600;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.commander-slot {
  background: rgba(217, 119, 6, 0.1);
  border: 2px dashed #d97706;
  border-radius: 8px;
  padding: 1rem;
}

.commander-card {
  display: flex;
  align-items: center;
  padding: 0.75rem;
  background: rgba(217, 119, 6, 0.2);
  border-radius: 8px;
  border: 1px solid #d97706;
  cursor: pointer;
  transition: all 0.2s ease;
}

.commander-card:hover {
  background: rgba(217, 119, 6, 0.3);
  transform: translateX(4px);
}

.commander-placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  color: #a0aec0;
  font-style: italic;
  padding: 1rem;
}

.commander-indicator {
  font-size: 1.2rem;
  margin-left: 0.5rem;
}

/* Sections par type */
.deck-section {
  margin-bottom: 1rem;
}

.section-cards {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.deck-card-entry {
  display: flex;
  align-items: center;
  padding: 0.5rem 0.75rem;
  background: rgba(75, 44, 23, 0.4);
  border-radius: 6px;
  border: 1px solid #6b4423;
  cursor: pointer;
  transition: all 0.2s ease;
}

.deck-card-entry:hover {
  background: rgba(75, 44, 23, 0.6);
  border-color: #d97706;
  transform: translateX(4px);
}

.card-cost {
  width: 20px;
  height: 20px;
  background: linear-gradient(135deg, #8b4513 0%, #5d2f02 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 700;
  font-size: 0.7rem;
  margin-right: 0.75rem;
  flex-shrink: 0;
  border: 1px solid #5d2f02;
}

.card-info {
  flex: 1;
  min-width: 0;
}

.card-name {
  color: #f7fafc;
  font-weight: 500;
  font-size: 0.8rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.card-name.legendary {
  color: #fbbf24;
  font-weight: 600;
}

.card-type {
  color: #a0aec0;
  font-size: 0.7rem;
  margin-top: 0.125rem;
}

.card-quantity {
  color: #d97706;
  font-weight: 700;
  font-size: 0.8rem;
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

/* Modal de sauvegarde */
:deep(.save-confirmation-modal .p-dialog) {
  border-radius: var(--border-radius-large) !important;
  overflow: hidden !important;
}

:deep(.save-confirmation-modal .p-dialog-header) {
  background: linear-gradient(135deg, #8b4513 0%, #5d2f02 100%) !important;
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
  border-left: 4px solid #8b4513;
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
  color: #8b4513;
  font-weight: 500;
}

.validation-success p {
  margin: 0;
}

.visibility-status-section {
  background: var(--surface-100);
  border-radius: var(--border-radius);
  padding: 1.5rem;
  border-left: 4px solid #8b4513;
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

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding: 1.5rem 0 0 0;
  border-top: 1px solid var(--surface-200);
}

/* Responsive */
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
    border-bottom: 1px solid #6b4423;
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
    grid-template-columns: repeat(3, 1fr);
    padding: 0.75rem;
    gap: 0.5rem;
  }
}

@media (max-width: 480px) {
  .cards-grid {
    grid-template-columns: repeat(2, 1fr);
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
  
  .mana-symbols {
    flex-wrap: wrap;
  }
  
  .mana-symbol {
    width: 24px;
    height: 24px;
    font-size: 0.8rem;
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

.deck-panel {
  animation: slideInRight 0.4s ease-out;
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

/* Animation d'entrée pour les sections */
.deck-section {
  animation: fadeInSection 0.3s ease-out;
}

@keyframes fadeInSection {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.commander-section {
  animation: fadeInCommander 0.5s ease-out;
}

@keyframes fadeInCommander {
  from {
    opacity: 0;
    transform: scale(0.95);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

/* Transitions fluides */
.toggle-btn.success {
  animation: successPulse 0.6s ease-out;
}

@keyframes successPulse {
  0% { transform: scale(1); }
  50% { transform: scale(1.1); }
  100% { transform: scale(1); }
}

/* Hover effects pour les cartes */
.deck-card-entry,
.commander-card {
  position: relative;
  overflow: hidden;
}

.deck-card-entry::before,
.commander-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(217, 119, 6, 0.2), transparent);
  transition: left 0.5s ease;
}

.deck-card-entry:hover::before,
.commander-card:hover::before {
  left: 100%;
}

/* Effets spéciaux pour les cartes légendaires */
.card-name.legendary {
  position: relative;
  text-shadow: 0 0 3px rgba(251, 191, 36, 0.5);
}

.card-name.legendary::after {
  content: '★';
  position: absolute;
  right: -12px;
  top: 50%;
  transform: translateY(-50%);
  color: #fbbf24;
  font-size: 0.7rem;
  animation: twinkle 2s infinite;
}

@keyframes twinkle {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

/* Effets de particules pour la sélection des couleurs */
.mana-symbol.selected {
  position: relative;
}

.mana-symbol.selected::after {
  content: '';
  position: absolute;
  top: -2px;
  left: -2px;
  right: -2px;
  bottom: -2px;
  border-radius: 50%;
  background: conic-gradient(from 0deg, #d97706, #fbbf24, #d97706);
  z-index: -1;
  animation: rotate 3s linear infinite;
}

@keyframes rotate {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Scroll personnalisé pour le deck panel */
.deck-content::-webkit-scrollbar {
  width: 6px;
}

.deck-content::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 3px;
}

.deck-content::-webkit-scrollbar-thumb {
  background: #d97706;
  border-radius: 3px;
}

.deck-content::-webkit-scrollbar-thumb:hover {
  background: #fbbf24;
}

/* Transitions de couleur pour le thème Magic */
* {
  transition: color 0.2s ease, background-color 0.2s ease, border-color 0.2s ease;
}

/* Focus states pour l'accessibilité */
.mana-symbol:focus,
.deck-card-entry:focus,
.commander-card:focus {
  outline: 2px solid #d97706;
  outline-offset: 2px;
}

/* États de chargement */
.cards-grid.loading {
  opacity: 0.7;
  pointer-events: none;
}

.cards-grid.loading::after {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 40px;
  height: 40px;
  border: 3px solid rgba(139, 69, 19, 0.3);
  border-top: 3px solid #8b4513;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}
</style>
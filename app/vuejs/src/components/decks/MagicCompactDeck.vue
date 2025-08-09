<template>
  <Card class="magic-deck-card gaming-card hover-lift" :class="{ 'expanded': isExpanded }">
    <template #content>
      <div class="deck-card-content">
        
        <!-- Header du deck -->
        <div class="deck-header">
          
          <!-- Titre avec jetons de couleurs -->
          <div class="deck-title-section">
            <div class="deck-title-line">
              <div class="deck-title-content">
                <h3 class="deck-name">{{ deck.title }}</h3>
                <div class="color-name-display">
                  {{ getColorIdentityName(deck.colorIdentity) }}
                </div>
              </div>
              
              <div class="deck-visibility">
                <i :class="deck.isPublic ? 'pi pi-globe' : 'pi pi-lock'" 
                   :style="{ color: deck.isPublic ? 'var(--primary)' : 'var(--text-secondary)' }"
                   :title="deck.isPublic ? 'Public' : 'Privé'">
                </i>
              </div>
            </div>
            
            <!-- Jetons de couleurs sous le titre -->
            <div class="color-identity-line">
              <div v-if="deck.colorIdentity && deck.colorIdentity.length > 0" class="color-tokens-container">
                <div 
                  v-for="color in getColorTokens(deck.colorIdentity)"
                  :key="color.symbol"
                  class="color-token"
                  :class="`color-${color.symbol.toLowerCase()}`"
                  :style="{ 
                    backgroundColor: color.color,
                    color: color.textColor,
                    borderColor: color.borderColor
                  }"
                  :title="color.name"
                >
                  {{ color.symbol }}
                </div>
              </div>
              
              <div v-else class="colorless-indicator">
                <div class="colorless-token">
                  <i class="pi pi-circle"></i>
                </div>
                <span class="colorless-text">Incolore</span>
              </div>
            </div>
            
            <!-- Métadonnées du deck -->
            <div class="deck-meta-line">
              <span class="format-badge" :class="formatBadgeClass">
                {{ deck.format.name }}
              </span>
              <span class="separator">•</span>
              <span class="cards-count" :class="{ 'complete': isComplete }">
                {{ deck.totalCards }}/{{ getMaxCards(deck.format.slug) }} cartes
              </span>
              <span v-if="isComplete" class="complete-badge">COMPLET</span>
            </div>
            
            <!-- Statistiques du deck -->
            <div class="deck-stats-line">
              <span class="cmc-avg">
                <i class="pi pi-circle-fill mana-icon"></i>
                CMC moy: {{ deck.averageCost?.toFixed(1) || '0.0' }}
              </span>
              <span class="separator">•</span>
              <span class="archetype" v-if="deck.archetype">
                {{ deck.archetype }}
              </span>
              <span v-else class="no-archetype">Aucun archetype</span>
              
            </div>
          </div>
        </div>

        <!-- Zone cartes expandable -->
        <div class="cards-section" v-if="isExpanded && deck.cards && deck.cards.length > 0">
          <div class="cards-header">
            <h4 class="cards-title">
              <i class="pi pi-list"></i>
              Liste des cartes ({{ deck.cards.length }})
            </h4>
          </div>
          
          <!-- Sections par type Magic -->
          <div class="magic-cards-list">
            <div 
              v-for="section in deckSections" 
              :key="section.type"
              class="magic-card-section"
              v-show="section.cards.length > 0"
            >
              <div class="section-header">
                <span class="section-name">
                  <i :class="section.icon"></i>
                  {{ section.name }} ({{ section.cards.length }})
                </span>
              </div>
              
              <div class="section-cards">
                  <div 
                    v-for="cardEntry in section.cards" 
                    :key="cardEntry.card.id"
                    class="magic-card-entry"
                    @mouseenter="(e) => handleCardHover(cardEntry, e)"
                    @mousemove="updateMousePosition"
                    @mouseleave="handleCardLeave"
                  >
                  <div class="card-cmc-badge">{{ cardEntry.card.cmc || 0 }}</div>
                  <div class="card-info-magic">
                    <span class="card-name" :class="{ 'legendary': cardEntry.card.isLegendary }">
                      {{ cardEntry.card.name }}
                    </span>
                    <span class="card-type-magic">{{ cardEntry.card.typeLine }}</span>
                  </div>
                  <div class="card-quantity" v-if="deck.format.slug !== 'commander'">
                    x{{ cardEntry.quantity }}
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <div v-if="!deck.cards || deck.cards.length === 0" class="empty-cards">
            <i class="pi pi-info-circle"></i>
            <span>Aucune carte ajoutée</span>
          </div>
        </div>

        <!-- Actions du deck Magic -->
        <div class="magic-deck-actions">
          <Button 
            :label="isExpanded ? 'Masquer' : 'Voir les cartes'"
            :icon="isExpanded ? 'pi pi-chevron-up' : 'pi pi-chevron-down'"
            class="toggle-cards-btn"
            @click="toggleExpand"
            :disabled="!deck.cards || deck.cards.length === 0"
          />
          
          <div class="action-buttons">
            <!-- Bouton like (seulement en mode community) -->
            <Button 
              v-if="showLike"
              :icon="isLiked ? 'pi pi-heart-fill' : 'pi pi-heart'"
              :class="['action-btn', 'like-btn', { 'liked': isLiked, 'loading': isLikeLoading }]"
              @click.stop.prevent="toggleLike"
              :label="likesCount.toString()"
              :disabled="isLikeLoading"
              v-tooltip="isLiked ? 'Ne plus aimer' : 'Aimer ce deck'"
              size="small"
            />
            
            <!-- Bouton edit (seulement si propriétaire et context my-decks) -->
            <Button 
              v-if="canEdit"
              icon="pi pi-pencil"
              class="action-btn edit-btn"
              @click="$emit('edit', deck)"
              v-tooltip="'Éditer le deck'"
              size="small"
            />
            
            <!-- Bouton copy (toujours présent) -->
            <Button 
              icon="pi pi-copy"
              class="action-btn copy-btn"
              @click="copyDeckcode"
              v-tooltip="'Copier le deckcode'"
              size="small"
            />
            
            <!-- Bouton delete (seulement si propriétaire et context my-decks) -->
            <Button 
              v-if="canDelete"
              icon="pi pi-trash"
              class="action-btn delete-btn"
              @click="$emit('delete', deck)"
              v-tooltip="'Supprimer le deck'"
              size="small"
            />
          </div>
        </div>

      </div>
      
      <!-- Preview simple d'image qui suit le curseur -->
      <div 
        v-if="hoveredCard" 
        class="card-image-preview"
        :style="{ 
          left: mousePosition.x + 20 + 'px', 
          top: mousePosition.y - 200 + 'px' 
        }"
      >
        <img 
          :src="hoveredCard.imageUrl" 
          :alt="hoveredCard.name"
          class="card-image-only"
          @error="handleImageError"
        />
      </div>
    </template>
  </Card>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import { useToast } from 'primevue/usetoast'
import Card from 'primevue/card'
import Button from 'primevue/button'
import api from '../../services/api'

// Props
const props = defineProps({
  deck: {
    type: Object,
    required: true
  },
  context: {
    type: String,
    default: 'community',
    validator: value => ['community', 'my-decks'].includes(value)
  },
  currentUser: {
    type: Object,
    default: null
  }
})

const hoveredCard = ref(null)
const mousePosition = ref({ x: 0, y: 0 })

// Méthodes pour hover avec position souris
const handleCardHover = (cardEntry, event) => {
  if (cardEntry && cardEntry.card) {
    hoveredCard.value = cardEntry.card
    updateMousePosition(event)
  }
}

const handleCardLeave = () => {
  hoveredCard.value = null
}

const updateMousePosition = (event) => {
  mousePosition.value = {
    x: event.clientX,
    y: event.clientY
  }
}

const handleImageError = (event) => {
  event.target.style.display = 'none'
}

// Emits
const emit = defineEmits(['edit', 'delete', 'copyDeckcode'])

// Composables
const toast = useToast()

// State réactif
const isExpanded = ref(false)
const isLiked = ref(false)
const likesCount = ref(0)

// Synchroniser avec les props
watch(() => props.deck, (newDeck) => {
  if (newDeck) {
    isLiked.value = newDeck.isLiked || false
    likesCount.value = newDeck.likesCount || 0
  }
}, { immediate: true })

// Computed - Permissions
const canEdit = computed(() => {
  if (props.context === 'my-decks') {
    return true
  }
  return props.currentUser && props.deck.user?.id === props.currentUser.id
})

const canDelete = computed(() => {
  if (props.context === 'my-decks') {
    return true
  }
  return props.currentUser && props.deck.user?.id === props.currentUser.id
})

const showLike = computed(() => {
  return props.context === 'community'
})

// Computed - Deck info
const isComplete = computed(() => {
  const maxCards = getMaxCards(props.deck.format.slug)
  return props.deck.totalCards === maxCards
})

const formatBadgeClass = computed(() => {
  return props.deck.format.slug.toLowerCase()
})

// Organisation des cartes par sections Magic
const deckSections = computed(() => {
  if (!props.deck.cards || props.deck.cards.length === 0) return []
  
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
  
  return sections.map(section => ({
    ...section,
    cards: section.cards.sort((a, b) => {
      const cmcDiff = (a.card.cmc || 0) - (b.card.cmc || 0)
      if (cmcDiff !== 0) return cmcDiff
      return a.card.name.localeCompare(b.card.name)
    })
  })).filter(section => section.cards.length > 0)
})

// Méthodes pour les couleurs Magic
const getColorTokens = (colorIdentity) => {
  if (!colorIdentity || colorIdentity.length === 0) return []
  
  const colorMap = {
    'W': { 
      symbol: 'W', 
      color: '#FFFBD5', 
      textColor: '#8B4513', 
      borderColor: '#D4AF37',
      name: 'Blanc'
    },
    'U': { 
      symbol: 'U', 
      color: '#0E68AB', 
      textColor: '#FFFFFF', 
      borderColor: '#1E3A8A',
      name: 'Bleu'
    },
    'B': { 
      symbol: 'B', 
      color: '#150B00', 
      textColor: '#FFFFFF', 
      borderColor: '#374151',
      name: 'Noir'
    },
    'R': { 
      symbol: 'R', 
      color: '#D3202A', 
      textColor: '#FFFFFF', 
      borderColor: '#B91C1C',
      name: 'Rouge'
    },
    'G': { 
      symbol: 'G', 
      color: '#00733E', 
      textColor: '#FFFFFF', 
      borderColor: '#059669',
      name: 'Vert'
    }
  }
  
  return colorIdentity.map(color => colorMap[color]).filter(Boolean)
}

const getColorIdentityName = (colorIdentity) => {
  if (!colorIdentity || colorIdentity.length === 0) {
    return 'Incolore'
  }
  
  if (colorIdentity.length === 1) {
    const colorNames = {
      'W': 'Blanc',
      'U': 'Bleu', 
      'B': 'Noir',
      'R': 'Rouge',
      'G': 'Vert'
    }
    return colorNames[colorIdentity[0]] || 'Inconnu'
  }
  
  if (colorIdentity.length === 2) {
    return 'Bicolore'
  }
  
  if (colorIdentity.length === 3) {
    return 'Tricolore'
  }
  
  if (colorIdentity.length >= 4) {
    return 'Multicolore'
  }
  
  return colorIdentity.join('')
}

// Méthodes utilitaires
const getMaxCards = (formatSlug) => {
  return formatSlug === 'commander' ? 100 : 60
}

const getCardsByType = (type) => {
  if (!props.deck.cards) return []
  
  return props.deck.cards.filter(entry => 
    entry.card.cardType === type
  )
}

// Actions
const toggleExpand = () => {
  isExpanded.value = !isExpanded.value
}

// Dans le script setup, ajouter un état de loading
const isLikeLoading = ref(false)

// Remplacer la fonction toggleLike existante par :
const toggleLike = async () => {
  // Protection contre les clics multiples
  if (isLikeLoading.value) {
    console.log('Like en cours, clic ignoré')
    return
  }

  if (!props.currentUser) {
    toast.add({
      severity: 'warn',
      summary: 'Connexion requise',
      detail: 'Veuillez vous connecter pour aimer les decks',
      life: 3000
    })
    return
  }

  // Bloquer les clics pendant la requête
  isLikeLoading.value = true

  try {
    // console.log('Envoi requête like pour deck:', props.deck.id)
    const response = await api.post(`/api/decks/${props.deck.id}/like`)
    
    if (response.data.success) {
      // console.log('Réponse serveur:', response.data)
      
      // Mise à jour immédiate de l'état local
      const newIsLiked = response.data.isLiked
      const newLikesCount = response.data.likesCount
      
      // Vérification que l'état a vraiment changé
      if (isLiked.value !== newIsLiked) {
        isLiked.value = newIsLiked
        likesCount.value = newLikesCount
        
        // console.log(`Like mis à jour: ${newIsLiked ? 'Liké' : 'Déliké'}, count: ${newLikesCount}`)
        
        toast.add({
          severity: 'success',
          summary: response.data.message,
          life: 2000
        })
        
      } else {
        console.warn('État like inchangé côté serveur')
      }
    }
  } catch (error) {
    // console.error('Erreur lors du like:', error)
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Impossible de modifier le like',
      life: 3000
    })
  } finally {
    // Débloquer les clics après un délai minimal
    setTimeout(() => {
      isLikeLoading.value = false
    }, 500)
  }
}

const copyDeckcode = () => {
  emit('copyDeckcode', props.deck)
  toast.add({
    severity: 'info',
    summary: 'Deckcode copié',
    detail: 'Fonctionnalité bientôt disponible pour Magic...',
    life: 2000
  })
}
</script>

<style scoped>
/* === MAGIC COMPACT DECK - DESIGN BLANC AVEC JETONS WUBRG === */

.magic-deck-card {
  background: white;
  border: 1px solid var(--surface-200);
  border-radius: var(--border-radius-large);
  transition: all var(--transition-medium);
  overflow: hidden;
  position: relative;
}

.magic-deck-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #7c3aed, #8b5cf6, #a855f7);
}

.magic-deck-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-large);
  border-color: #7c3aed;
}

.magic-deck-card.expanded {
  box-shadow: var(--shadow-medium);
}

.deck-card-content {
  padding: 1.5rem;
  color: var(--text-primary);
}

/* === HEADER DU DECK === */
.deck-header {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.deck-title-line {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  margin-bottom: 0.75rem;
}

.deck-title-content {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.deck-name {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0;
  line-height: 1.3;
}

.color-name-display {
  font-size: 0.9rem;
  color: var(--text-secondary);
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.deck-visibility {
  flex-shrink: 0;
}

.deck-visibility i {
  font-size: 1.1rem;
}

.deck-title-line {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 0.5rem;
}

/* === LIGNE DES JETONS DE COULEURS === */
.color-identity-line {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}

.color-tokens-container {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.color-token {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.8rem;
  font-family: 'Courier New', monospace;
  border: 2px solid;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
  transition: all var(--transition-fast);
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
}

.color-token:hover {
  transform: scale(1.15);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.25);
}

.colorless-indicator {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.colorless-token {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
  border: 2px solid #9ca3af;
  color: #6b7280;
  font-size: 1rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  transition: all var(--transition-fast);
}

.colorless-token:hover {
  transform: scale(1.15);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.colorless-text {
  color: var(--text-secondary);
  font-style: italic;
  font-weight: 500;
  font-size: 0.9rem;
}

/* === MÉTADONNÉES === */
.deck-meta-line {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
  font-size: 0.9rem;
}

.format-badge {
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.format-badge.standard {
  background: rgba(59, 130, 246, 0.1);
  color: #3b82f6;
  border: 1px solid rgba(59, 130, 246, 0.3);
}

.format-badge.commander {
  background: rgba(220, 38, 38, 0.1);
  color: #dc2626;
  border: 1px solid rgba(220, 38, 38, 0.3);
}

.format-badge.modern {
  background: rgba(5, 150, 105, 0.1);
  color: #059669;
  border: 1px solid rgba(5, 150, 105, 0.3);
}

.format-badge.legacy {
  background: rgba(124, 58, 237, 0.1);
  color: #7c3aed;
  border: 1px solid rgba(124, 58, 237, 0.3);
}

.cards-count {
  color: var(--text-secondary);
  font-weight: 600;
}

.cards-count.complete {
  color: #7c3aed;
  font-weight: 700;
}

.complete-badge {
  background: #7c3aed;
  color: white;
  padding: 0.125rem 0.375rem;
  border-radius: 3px;
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 0.5px;
}

.separator {
  color: var(--surface-400);
}

/* === STATISTIQUES === */
.deck-stats-line {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.85rem;
  color: var(--text-secondary);
  flex-wrap: wrap;
}

.cmc-avg {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-weight: 600;
}

.mana-icon {
  color: var(--primary);
  font-size: 0.7rem;
}

.archetype {
  color: var(--primary);
  font-weight: 600;
  font-style: italic;
}

.no-archetype {
  color: var(--text-secondary);
  font-style: italic;
}

/* === SECTION CARTES === */
.cards-section {
  background: var(--surface-50);
  border-radius: var(--border-radius);
  padding: 1rem;
  margin: 1rem 0;
  border: 1px solid var(--surface-200);
  animation: expandIn 0.3s ease-out;
  max-height: 350px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.cards-header {
  margin-bottom: 0.75rem;
}

.cards-title {
  color: var(--text-primary);
  font-size: 1rem;
  font-weight: 600;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.cards-title i {
  color: var(--primary);
}

.magic-cards-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  overflow-y: auto;
  flex: 1;
  max-height: 280px;
  padding-right: 0.5rem;
}

.magic-card-section {
  background: white;
  border-radius: 6px;
  padding: 0.75rem;
  border: 1px solid var(--surface-200);
}

.magic-cards-list::-webkit-scrollbar {
  width: 6px;
}

.magic-cards-list::-webkit-scrollbar-track {
  background: var(--surface-200);
  border-radius: 3px;
}

.magic-cards-list::-webkit-scrollbar-thumb {
  background: var(--primary);
  border-radius: 3px;
}

.magic-cards-list::-webkit-scrollbar-thumb:hover {
  background: var(--primary-dark);
}

.section-header {
  margin-bottom: 0.5rem;
}

.section-name {
  color: var(--primary);
  font-size: 0.9rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.section-cards {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.magic-card-entry {
  display: flex;
  align-items: center;
  padding: 0.375rem 0.5rem;
  background: var(--surface-50);
  border-radius: 4px;
  border: 1px solid var(--surface-200);
  transition: all var(--transition-fast);
}

.magic-card-entry:hover {
  background: rgba(38, 166, 154, 0.1) !important;
  border-color: var(--primary) !important;
  transform: translateX(4px);
  cursor: pointer;
  transition: all 0.2s ease;
}

.card-cmc-badge {
  width: 18px;
  height: 18px;
  background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 700;
  font-size: 0.7rem;
  margin-right: 0.5rem;
  flex-shrink: 0;
  border: 1px solid var(--primary-dark);
}

.card-info-magic {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
}

.card-name {
  color: var(--text-primary);
  font-weight: 500;
  font-size: 0.8rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.card-name.legendary {
  color: #f59e0b;
  font-weight: 600;
  text-shadow: 0 0 2px rgba(245, 158, 11, 0.3);
}

.card-type-magic {
  color: var(--text-secondary);
  font-size: 0.7rem;
  font-style: italic;
}

.card-quantity {
  color: var(--primary);
  font-weight: 700;
  font-size: 0.8rem;
  margin-left: 0.5rem;
  flex-shrink: 0;
}

.empty-cards {
  text-align: center;
  padding: 2rem;
  color: var(--text-secondary);
}

.empty-cards i {
  font-size: 1.5rem;
  margin-bottom: 0.5rem;
  opacity: 0.7;
}

/* === ACTIONS DU DECK === */
.magic-deck-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  padding-top: 1rem;
  border-top: 1px solid var(--surface-200);
}

:deep(.toggle-cards-btn) {
  background: var(--surface-100) !important;
  border: 2px solid var(--surface-300) !important;
  color: var(--text-primary) !important;
  padding: 0.5rem 1rem !important;
  border-radius: 8px !important;
  font-weight: 500 !important;
  font-size: 0.85rem !important;
  transition: all var(--transition-fast) !important;
}

:deep(.toggle-cards-btn:hover) {
  border-color: var(--primary) !important;
  color: var(--primary) !important;
  background: rgba(38, 166, 154, 0.1) !important;
}

:deep(.toggle-cards-btn:disabled) {
  opacity: 0.5 !important;
  cursor: not-allowed !important;
}

.action-buttons {
  display: flex;
  gap: 0.5rem;
}

:deep(.action-btn) {
  width: 36px !important;
  height: 36px !important;
  padding: 0 !important;
  border-radius: 6px !important;
  font-size: 0.85rem !important;
}

/* === BOUTON LIKE CORRIGÉ === */
:deep(.like-btn) {
  background: white !important;
  border: 2px solid var(--surface-300) !important;
  color: var(--text-secondary) !important;
  min-width: 60px !important;
  padding: 0.5rem !important;
}

:deep(.like-btn .p-button-content) {
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  gap: 0.375rem !important;
  width: 100% !important;
}

:deep(.like-btn .p-button-icon) {
  margin: 0 !important;
  order: 1 !important;
}

:deep(.like-btn .p-button-label) {
  margin: 0 !important;
  order: 2 !important;
  font-weight: 600 !important;
}

:deep(.like-btn:hover) {
  border-color: #e11d48 !important;
  color: #e11d48 !important;
  background: rgba(225, 29, 72, 0.1) !important;
}

:deep(.like-btn.liked) {
  background: #e11d48 !important;
  border-color: #e11d48 !important;
  color: white !important;
}

:deep(.like-btn.liked:hover) {
  background: #be185d !important;
  border-color: #be185d !important;
  color: white !important;
}

:deep(.like-btn .pi-heart-fill) {
  animation: heartBeat 0.3s ease-in-out;
}

@keyframes heartBeat {
  0% { transform: scale(1); }
  50% { transform: scale(1.2); }
  100% { transform: scale(1); }
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

/* === PREVIEW IMAGE SIMPLE === */
.card-image-preview {
  position: fixed;
  z-index: 9999;
  pointer-events: none;
  transition: opacity 0.2s ease-out;
}

.card-image-only {
  width: 180px;
  height: auto;
  border-radius: 8px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
  border: 2px solid var(--primary);
  background: white;
  object-fit: contain;
}

/* === ANIMATIONS === */
@keyframes expandIn {
  from {
    opacity: 0;
    max-height: 0;
    padding-top: 0;
    padding-bottom: 0;
  }
  to {
    opacity: 1;
    max-height: 500px;
    padding-top: 1rem;
    padding-bottom: 1rem;
  }
}

/* === RESPONSIVE === */
@media (max-width: 768px) {
  .deck-card-content {
    padding: 1rem;
  }
  
  .deck-title-line {
    align-items: center;
    gap: 0.75rem;
  }
  
  .color-tokens-container {
    flex-direction: row;
    flex-wrap: wrap;
    gap: 0.25rem;
  }
  
  .color-token {
    width: 28px;
    height: 28px;
    font-size: 0.8rem;
  }
  
  .colorless-token {
    width: 32px;
    height: 32px;
    font-size: 1rem;
  }
  
  .deck-meta-line,
  .deck-stats-line {
    font-size: 0.8rem;
  }
  
  .magic-deck-actions {
    flex-direction: column;
    gap: 0.75rem;
  }
  
  .action-buttons {
    width: 100%;
    justify-content: center;
  }
  
  :deep(.toggle-cards-btn) {
    width: 100% !important;
    justify-content: center !important;
  }
  
  /* Masquer preview sur mobile */
  .card-image-preview {
    display: none !important;
  }
}

@media (max-width: 480px) {
  .color-token {
    width: 24px;
    height: 24px;
    font-size: 0.7rem;
  }
  
  .colorless-token {
    width: 28px;
    height: 28px;
    font-size: 0.9rem;
  }
  
  .deck-name {
    font-size: 1.1rem;
  }
}
</style>
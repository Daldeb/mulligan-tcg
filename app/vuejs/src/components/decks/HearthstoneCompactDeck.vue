<template>
  <Card class="hearthstone-deck-card gaming-card hover-lift" :class="{ 'expanded': isExpanded }">
    <template #content>
      <div class="deck-card-content">
        
        <!-- Header du deck -->
        <div class="deck-header">
          
          <!-- Titre avec icône de classe -->
          <div class="deck-title-section">
            <div class="deck-title-line">
              <div class="class-icon">
                <img 
                  :src="getClassIcon(deck.hearthstoneClass)" 
                  :alt="getClassDisplayName(deck.hearthstoneClass)"
                  class="class-icon-image"
                />
              </div>
              
              <div class="deck-title-content">
                <h3 class="deck-name">{{ deck.title }}</h3>
                <div class="class-name-display">
                  {{ getClassDisplayName(deck.hearthstoneClass) }}
                </div>
              </div>
              
              <div class="deck-visibility">
                <i :class="deck.isPublic ? 'pi pi-globe' : 'pi pi-lock'" 
                   :style="{ color: deck.isPublic ? 'var(--primary)' : 'var(--text-secondary)' }"
                   :title="deck.isPublic ? 'Public' : 'Privé'">
                </i>
              </div>
            </div>
            
            <!-- Métadonnées du deck -->
            <div class="deck-meta-line">
              <span class="format-badge" :class="formatBadgeClass">
                {{ deck.format.name }}
              </span>
              <span class="separator">•</span>
              <span class="cards-count" :class="{ 'complete': isComplete }">
                {{ deck.totalCards }}/30 cartes
              </span>
              <span v-if="isComplete" class="complete-badge">COMPLET</span>
            </div>
            
            <!-- Statistiques du deck -->
            <div class="deck-stats-line">
              <span class="dust-cost">
                <i class="pi pi-circle-fill dust-icon"></i>
                {{ dustCost }} poussière
              </span>
              <span class="separator">•</span>
              <span class="avg-cost">
                Coût moy: {{ deck.averageCost?.toFixed(1) || '0.0' }}
              </span>
            
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
          
          <div class="cards-list">
              <div 
                v-for="cardEntry in sortedCards" 
                :key="cardEntry.card.id"
                class="card-entry"
                @mouseenter="(e) => handleCardHover(cardEntry, e)"
                @mousemove="updateMousePosition"
                @mouseleave="handleCardLeave"
              >
              <div class="card-cost-badge">{{ cardEntry.card.cost || 0 }}</div>
              <div class="card-info">
                <span class="card-name">{{ cardEntry.card.name }}</span>
                <span class="card-rarity" :class="`rarity-${cardEntry.card.rarity?.toLowerCase()}`">
                  {{ getRarityIcon(cardEntry.card.rarity) }}
                </span>
              </div>
              <div class="card-quantity">x{{ cardEntry.quantity }}</div>
            </div>
          </div>
          
          <div v-if="!deck.cards || deck.cards.length === 0" class="empty-cards">
            <i class="pi pi-info-circle"></i>
            <span>Aucune carte ajoutée</span>
          </div>
        </div>

        <!-- Actions du deck -->
        <div class="deck-actions">
          <Button 
            :label="isExpanded ? 'Masquer' : 'Voir les cartes'"
            :icon="isExpanded ? 'pi pi-chevron-up' : 'pi pi-chevron-down'"
            class="expand-btn"
            @click="toggleExpanded"
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
              @click="$emit('copyDeckcode', deck)"
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

const hoveredCard = ref(null)
const mousePosition = ref({ x: 0, y: 0 })

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

// Méthodes pour hover
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

// Events
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
const isComplete = computed(() => props.deck.totalCards === 30)

const formatBadgeClass = computed(() => {
  if (props.deck.format?.slug === 'wild') return 'format-wild'
  if (props.deck.format?.slug === 'standard') return 'format-standard'
  return 'format-default'
})

const dustCost = computed(() => {
  if (!props.deck.cards || props.deck.cards.length === 0) return '0'
  
  const dustCosts = {
    'common': 40,
    'rare': 100, 
    'epic': 400,
    'legendary': 1600
  }
  
  const total = props.deck.cards.reduce((sum, cardEntry) => {
    const rarity = cardEntry.card.rarity?.toLowerCase() || 'common'
    const cardCost = dustCosts[rarity] || 40
    return sum + (cardCost * cardEntry.quantity)
  }, 0)
  
  return total.toLocaleString()
})

const sortedCards = computed(() => {
  if (!props.deck.cards) return []
  
  return [...props.deck.cards].sort((a, b) => {
    const costDiff = (a.card.cost || 0) - (b.card.cost || 0)
    if (costDiff !== 0) return costDiff
    return a.card.name.localeCompare(b.card.name)
  })
})

// Methods
const toggleExpanded = () => {
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
    console.log('Envoi requête like pour deck:', props.deck.id)
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
        // console.warn('État like inchangé côté serveur')
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

const getClassIcon = (hearthstoneClass) => {
  const iconMap = {
    'mage': '/src/assets/images/icons/Alt-Heroes_Mage_Jaina.png.avif',
    'hunter': '/src/assets/images/icons/Alt-Heroes_Hunter_Rexxar.png.avif',
    'paladin': '/src/assets/images/icons/Alt-Heroes_Paladin_Uther.png.avif',
    'warrior': '/src/assets/images/icons/Alt-Heroes_Warrior_Garrosh.png.avif',
    'priest': '/src/assets/images/icons/Alt-Heroes_Priest_Anduin.png.avif',
    'warlock': '/src/assets/images/icons/Alt-Heroes_Warlock_Guldan.png.avif',
    'shaman': '/src/assets/images/icons/Alt-Heroes_Shaman_Thrall.png.avif',
    'rogue': '/src/assets/images/icons/Alt-Heroes_Rogue_Valeera.png.avif',
    'druid': '/src/assets/images/icons/Alt-Heroes_Druid_Malfurion.png.avif',
    'demonhunter': '/src/assets/images/icons/Alt-Heroes_Demon-Hunter_Illidan.png.avif',
    'deathknight': '/src/assets/images/icons/hearthstone-lich-king.webp'
  }
  
  return iconMap[hearthstoneClass] || iconMap['mage']
}

const getClassDisplayName = (classValue) => {
  const classes = {
    'mage': 'Mage',
    'hunter': 'Chasseur',
    'paladin': 'Paladin',
    'warrior': 'Guerrier',
    'priest': 'Prêtre',
    'warlock': 'Démoniste',
    'shaman': 'Chaman',
    'rogue': 'Voleur',
    'druid': 'Druide',
    'demonhunter': 'Chasseur de démons',
    'deathknight': 'Chevalier de la mort'
  }
  return classes[classValue] || classValue
}

const getRarityIcon = (rarity) => {
  const icons = {
    'common': '⚪',
    'rare': '🔵',
    'epic': '🟣',
    'legendary': '🟠'
  }
  return icons[rarity?.toLowerCase()] || '⚪'
}
</script>

<style scoped>
/* === HEARTHSTONE COMPACT DECK CARD === */

.hearthstone-deck-card {
  border: 1px solid var(--surface-200);
  border-radius: var(--border-radius-large);
  overflow: hidden;
  transition: all var(--transition-fast);
  background: white;
  position: relative;
}

.hearthstone-deck-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #d97706, #f59e0b, #d97706);
}

.hearthstone-deck-card.expanded {
  box-shadow: var(--shadow-medium);
}

.hearthstone-deck-card.hover-lift:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-medium);
}

.deck-card-content {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
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

.class-name-display {
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

/* === ICÔNE DE CLASSE === */
.class-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.class-icon-image {
  width: 78px;
  height: 78px;
  object-fit: contain;
  transition: all var(--transition-fast);
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
}

.class-icon:hover .class-icon-image {
  transform: scale(1.15);
  filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.3));
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
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
}

.format-badge.format-standard {
  background: rgba(217, 119, 6, 0.1);
  color: #d97706;
  border: 1px solid rgba(217, 119, 6, 0.3);
}

.format-badge.format-wild {
  background: rgba(245, 158, 11, 0.1);
  color: #f59e0b;
  border: 1px solid rgba(245, 158, 11, 0.3);
}

.format-badge.format-default {
  background: var(--surface-200);
  color: var(--text-secondary);
}

.cards-count {
  font-weight: 500;
  color: var(--text-secondary);
}

.cards-count.complete {
  color: #d97706;
  font-weight: 600;
}

.complete-badge {
  padding: 0.25rem 0.5rem;
  background: #d97706;
  color: white;
  border-radius: 8px;
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
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

.dust-cost {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-weight: 500;
}

.dust-icon {
  font-size: 0.6rem;
  color: #f59e0b;
}

.avg-cost {
  font-weight: 500;
}

/* === SECTION CARTES === */
.cards-section {
  border: 1px solid var(--surface-200);
  border-radius: var(--border-radius);
  background: var(--surface-50);
  overflow: hidden;
  animation: expandCard 0.3s ease-out;
}

@keyframes expandCard {
  from {
    opacity: 0;
    max-height: 0;
  }
  to {
    opacity: 1;
    max-height: 400px;
  }
}

.cards-header {
  padding: 1rem;
  background: var(--surface-100);
  border-bottom: 1px solid var(--surface-200);
}

.cards-title {
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.cards-list {
  max-height: 250px;
  overflow-y: auto;
  padding: 0.5rem;
}

.card-entry {
  display: flex;
  align-items: center;
  padding: 0.5rem;
  border-radius: 6px;
  transition: all var(--transition-fast);
  gap: 0.75rem;
}

.card-entry:hover {
  background: rgba(217, 119, 6, 0.1) !important;
  transform: translateX(4px);
  cursor: pointer;
  transition: all 0.2s ease;
}

.card-cost-badge {
  width: 24px;
  height: 24px;
  background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 700;
  font-size: 0.75rem;
  flex-shrink: 0;
  border: 2px solid #1e3a8a;
}

.card-info {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  min-width: 0;
}

.card-name {
  font-weight: 500;
  color: var(--text-primary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  font-size: 0.85rem;
}

.card-rarity {
  font-size: 0.7rem;
  flex-shrink: 0;
}

.card-quantity {
  color: #d97706;
  font-weight: 600;
  font-size: 0.85rem;
  flex-shrink: 0;
}

.empty-cards {
  padding: 2rem;
  text-align: center;
  color: var(--text-secondary);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  font-size: 0.9rem;
}

/* === ACTIONS DU DECK === */
.deck-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  padding-top: 1rem;
  border-top: 1px solid var(--surface-200);
}

:deep(.expand-btn) {
  background: var(--surface-100) !important;
  border: 2px solid var(--surface-300) !important;
  color: var(--text-primary) !important;
  padding: 0.5rem 1rem !important;
  border-radius: 8px !important;
  font-weight: 500 !important;
  font-size: 0.85rem !important;
  transition: all var(--transition-fast) !important;
}

:deep(.expand-btn:hover) {
  border-color: #d97706 !important;
  color: #d97706 !important;
  background: rgba(217, 119, 6, 0.1) !important;
}

:deep(.expand-btn:disabled) {
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
  background: #d97706 !important;
  border-color: #d97706 !important;
  color: white !important;
}

:deep(.edit-btn:hover) {
  background: #b45309 !important;
  border-color: #b45309 !important;
}

:deep(.copy-btn) {
  background: white !important;
  border: 2px solid var(--surface-300) !important;
  color: var(--text-secondary) !important;
}

:deep(.copy-btn:hover) {
  border-color: #d97706 !important;
  color: #d97706 !important;
  background: rgba(217, 119, 6, 0.1) !important;
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
  border: 2px solid #d97706;
  background: white;
  object-fit: contain;
}

/* === SCROLLBAR === */
.cards-list::-webkit-scrollbar {
  width: 6px;
}

.cards-list::-webkit-scrollbar-track {
  background: var(--surface-200);
  border-radius: 3px;
}

.cards-list::-webkit-scrollbar-thumb {
  background: #d97706;
  border-radius: 3px;
}

.cards-list::-webkit-scrollbar-thumb:hover {
  background: #b45309;
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
  
  .class-icon-image {
    width: 60px;
    height: 60px;
  }
  
  .deck-meta-line {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.25rem;
  }
  
  .deck-actions {
    flex-direction: column;
    gap: 0.75rem;
  }
  
  .action-buttons {
    width: 100%;
    justify-content: center;
  }
  
  :deep(.expand-btn) {
    width: 100% !important;
    justify-content: center !important;
  }
  
  /* Masquer preview sur mobile */
  .card-image-preview {
    display: none !important;
  }
}

@media (max-width: 480px) {
  .class-icon-image {
    width: 50px;
    height: 50px;
  }
  
  .deck-name {
    font-size: 1.1rem;
  }
  
  .cards-list {
    max-height: 200px;
  }
}
</style>
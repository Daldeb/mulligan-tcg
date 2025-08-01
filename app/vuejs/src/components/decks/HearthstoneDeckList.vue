<template>
  <div class="hearthstone-decklist">
    
    <!-- Header du deck -->
    <div class="deck-header">
      <h3 class="deck-title">{{ deck.name }}</h3>
      <div class="deck-meta">
        <div class="meta-item">
          <span class="meta-label">Format:</span>
          <span class="meta-value" :class="`format-${deck.format}`">{{ deck.format.toUpperCase() }}</span>
        </div>
        <div class="meta-item">
          <span class="meta-label">Classe:</span>
          <span class="meta-value" :class="`class-${deck.heroClass?.toLowerCase()}`">{{ deck.heroClass || 'Neutre' }}</span>
        </div>
        <div class="meta-item">
          <span class="meta-label">Coût:</span>
          <span class="meta-value">{{ totalManaCost }}</span>
        </div>
      </div>
    </div>

    <!-- Liste des cartes -->
    <div class="cards-list">
      <div 
        v-for="cardEntry in sortedCards" 
        :key="cardEntry.card.id"
        class="card-entry"
        :class="`rarity-${cardEntry.card.rarity?.toLowerCase()}`"
        @click="$emit('card-click', cardEntry)"
        @mouseenter="showCardPreview(cardEntry.card)"
        @mouseleave="hideCardPreview"
      >
        
        <!-- Coût de mana -->
        <div class="mana-cost">
          <div class="mana-crystal" :class="`cost-${cardEntry.card.cost || 0}`">
            {{ cardEntry.card.cost || 0 }}
          </div>
        </div>

        <!-- Image de la carte (miniature) -->
        <div class="card-image-mini">
          <img 
            v-if="cardEntry.card.imageUrl"
            :src="getCardImageUrl(cardEntry.card.imageUrl)"
            :alt="cardEntry.card.name"
            class="mini-image"
            @error="handleImageError"
          />
          <div v-else class="image-placeholder">
            <i class="pi pi-image"></i>
          </div>
        </div>

        <!-- Nom de la carte avec gradient -->
        <div class="card-name-area" :class="`rarity-bg-${cardEntry.card.rarity?.toLowerCase()}`">
          <span class="card-name">{{ cardEntry.card.name }}</span>
          <div class="rarity-gradient"></div>
        </div>

        <!-- Quantité -->
        <div class="card-quantity">
          <span class="quantity-number">{{ cardEntry.quantity }}</span>
        </div>

      </div>

      <!-- Résumé en bas -->
      <div class="deck-summary">
        <div class="summary-item">
          <span class="summary-label">Total:</span>
          <span class="summary-value">{{ totalCards }}/30 cartes</span>
        </div>
        <div class="summary-item">
          <span class="summary-label">Coût moyen:</span>
          <span class="summary-value">{{ averageManaCost }}</span>
        </div>
        <div class="summary-item">
          <span class="summary-label">Légendaires:</span>
          <span class="summary-value">{{ legendaryCount }}</span>
        </div>
      </div>
    </div>

    <!-- Card preview hover (grande image) -->
    <div 
      v-if="hoveredCard && showPreview"
      class="card-preview-overlay"
      :style="previewPosition"
    >
      <div class="card-preview-container">
        <img 
          :src="getCardImageUrl(hoveredCard.imageUrl)"
          :alt="hoveredCard.name"
          class="preview-image"
        />
        <div class="preview-info">
          <h4 class="preview-name">{{ hoveredCard.name }}</h4>
          <p class="preview-text" v-if="hoveredCard.text">{{ hoveredCard.text }}</p>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  deck: {
    type: Object,
    required: true
  },
  cards: {
    type: Array,
    default: () => []
  },
  isEditable: {
    type: Boolean,
    default: false
  }
})

defineEmits(['card-click', 'edit', 'delete', 'copy'])

// State
const hoveredCard = ref(null)
const showPreview = ref(false)
const previewPosition = ref({ top: '0px', left: '0px' })
const mouseX = ref(0)
const mouseY = ref(0)

// Computed - Organisation des cartes
const sortedCards = computed(() => {
  return [...props.cards]
    .sort((a, b) => {
      // Tri par coût de mana puis par nom
      const costDiff = (a.card.cost || 0) - (b.card.cost || 0)
      if (costDiff !== 0) return costDiff
      return a.card.name.localeCompare(b.card.name)
    })
})

const totalCards = computed(() => {
  return props.cards.reduce((total, entry) => total + entry.quantity, 0)
})

const totalManaCost = computed(() => {
  return props.cards.reduce((total, entry) => 
    total + ((entry.card.cost || 0) * entry.quantity), 0
  )
})

const averageManaCost = computed(() => {
  if (totalCards.value === 0) return '0.0'
  return (totalManaCost.value / totalCards.value).toFixed(1)
})

const legendaryCount = computed(() => {
  return props.cards.filter(entry => 
    entry.card.rarity?.toLowerCase() === 'legendary'
  ).length
})

// Cartes clés pour l'aperçu (les plus chères ou légendaires)
const keyCards = computed(() => {
  return [...props.cards]
    .sort((a, b) => {
      // Priorité aux légendaires, puis coût élevé
      const aWeight = (a.card.rarity === 'LEGENDARY' ? 1000 : 0) + (a.card.cost || 0)
      const bWeight = (b.card.rarity === 'LEGENDARY' ? 1000 : 0) + (b.card.cost || 0)
      return bWeight - aWeight
    })
    .map(entry => entry.card)
})

// Méthodes
const getCardImageUrl = (imageUrl) => {
  const backendUrl = import.meta.env.VITE_BACKEND_URL
  return `${backendUrl}/uploads/${imageUrl}`
}

const handleImageError = (event) => {
  event.target.style.display = 'none'
  event.target.nextElementSibling?.style?.setProperty('display', 'flex')
}

const showCardPreview = (card) => {
  hoveredCard.value = card
  showPreview.value = true
  updatePreviewPosition()
}

const hideCardPreview = () => {
  showPreview.value = false
  hoveredCard.value = null
}

const updatePreviewPosition = () => {
  // Positionner la preview à côté de la souris
  const offset = 20
  previewPosition.value = {
    top: `${mouseY.value + offset}px`,
    left: `${mouseX.value + offset}px`
  }
}

const handleMouseMove = (event) => {
  mouseX.value = event.clientX
  mouseY.value = event.clientY
  if (showPreview.value) {
    updatePreviewPosition()
  }
}

// Lifecycle
onMounted(() => {
  document.addEventListener('mousemove', handleMouseMove)
})

onUnmounted(() => {
  document.removeEventListener('mousemove', handleMouseMove)
})
</script>

<style scoped>
/* === HEARTHSTONE DECKLIST STYLE === */

.hearthstone-decklist {
  background: #1a1a1a;
  border-radius: var(--border-radius-large);
  overflow: hidden;
  position: relative;
  min-width: 300px;
  max-width: 350px;
}

/* Header du deck */
.deck-header {
  background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
  padding: 1rem 1.25rem;
  border-bottom: 2px solid #4a5568;
}

.deck-title {
  color: #f7fafc;
  font-size: 1.1rem;
  font-weight: 700;
  margin: 0 0 0.75rem 0;
  text-align: center;
}

.deck-meta {
  display: flex;
  justify-content: space-between;
  font-size: 0.75rem;
}

.meta-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
}

.meta-label {
  color: #a0aec0;
  text-transform: uppercase;
  font-weight: 500;
}

.meta-value {
  color: #f7fafc;
  font-weight: 600;
}

.meta-value.format-standard {
  color: #48bb78;
}

.meta-value.format-wild {
  color: #ed8936;
}

/* Couleurs de classe Hearthstone */
.meta-value.class-mage { color: #69ccf0; }
.meta-value.class-hunter { color: #abd473; }
.meta-value.class-paladin { color: #f58cba; }
.meta-value.class-warrior { color: #c79c6e; }
.meta-value.class-priest { color: #ffffff; }
.meta-value.class-warlock { color: #9482c9; }
.meta-value.class-shaman { color: #0070de; }
.meta-value.class-rogue { color: #fff569; }
.meta-value.class-druid { color: #ff7d0a; }

/* Liste des cartes */
.cards-list {
  background: #2d3748;
  padding: 0.5rem 0;
  max-height: 500px;
  overflow-y: auto;
}

/* Scrollbar custom */
.cards-list::-webkit-scrollbar {
  width: 6px;
}

.cards-list::-webkit-scrollbar-track {
  background: #1a202c;
}

.cards-list::-webkit-scrollbar-thumb {
  background: #4a5568;
  border-radius: 3px;
}

/* Entrée de carte */
.card-entry {
  display: flex;
  align-items: center;
  padding: 0.25rem 0.75rem;
  cursor: pointer;
  transition: all 0.2s ease;
  border-left: 3px solid transparent;
  position: relative;
}

.card-entry:hover {
  background: rgba(74, 85, 104, 0.3);
  border-left-color: var(--primary);
}

/* Couleurs de rareté */
.card-entry.rarity-common:hover {
  border-left-color: #9ca3af;
}

.card-entry.rarity-rare:hover {
  border-left-color: #3b82f6;
}

.card-entry.rarity-epic:hover {
  border-left-color: #a855f7;
}

.card-entry.rarity-legendary:hover {
  border-left-color: #f59e0b;
}

/* Coût de mana */
.mana-cost {
  width: 24px;
  height: 24px;
  margin-right: 0.5rem;
  flex-shrink: 0;
}

.mana-crystal {
  width: 100%;
  height: 100%;
  background: radial-gradient(circle, #4299e1 0%, #2b6cb0 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 700;
  font-size: 0.75rem;
  border: 2px solid #1e3a8a;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.8);
}

.cost-0 { background: radial-gradient(circle, #6b7280 0%, #374151 100%); }
.cost-1 { background: radial-gradient(circle, #4299e1 0%, #2563eb 100%); }
.cost-2 { background: radial-gradient(circle, #4299e1 0%, #2563eb 100%); }
.cost-3 { background: radial-gradient(circle, #4299e1 0%, #2563eb 100%); }
.cost-4 { background: radial-gradient(circle, #4299e1 0%, #2563eb 100%); }
.cost-5 { background: radial-gradient(circle, #4299e1 0%, #2563eb 100%); }
.cost-6 { background: radial-gradient(circle, #4299e1 0%, #2563eb 100%); }
.cost-7 { background: radial-gradient(circle, #7c3aed 0%, #5b21b6 100%); }
.cost-8 { background: radial-gradient(circle, #7c3aed 0%, #5b21b6 100%); }
.cost-9 { background: radial-gradient(circle, #7c3aed 0%, #5b21b6 100%); }
.cost-10 { background: radial-gradient(circle, #dc2626 0%, #991b1b 100%); }

/* Image miniature */
.card-image-mini {
  width: 40px;
  height: 56px;
  margin-right: 0.75rem;
  flex-shrink: 0;
  border-radius: 4px;
  overflow: hidden;
  border: 1px solid #4a5568;
}

.mini-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.image-placeholder {
  width: 100%;
  height: 100%;
  background: #4a5568;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #a0aec0;
  font-size: 1rem;
}

/* Zone nom avec gradient de rareté */
.card-name-area {
  flex: 1;
  position: relative;
  padding: 0.5rem 0.75rem;
  border-radius: 4px;
  overflow: hidden;
}

.card-name {
  color: #f7fafc;
  font-weight: 600;
  font-size: 0.85rem;
  position: relative;
  z-index: 2;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.8);
}

.rarity-gradient {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 1;
  opacity: 0.8;
}

/* Gradients de rareté */
.rarity-bg-common .rarity-gradient {
  background: linear-gradient(90deg, rgba(156, 163, 175, 0.4) 0%, rgba(0, 0, 0, 0.9) 100%);
}

.rarity-bg-rare .rarity-gradient {
  background: linear-gradient(90deg, rgba(59, 130, 246, 0.4) 0%, rgba(0, 0, 0, 0.9) 100%);
}

.rarity-bg-epic .rarity-gradient {
  background: linear-gradient(90deg, rgba(168, 85, 247, 0.4) 0%, rgba(0, 0, 0, 0.9) 100%);
}

.rarity-bg-legendary .rarity-gradient {
  background: linear-gradient(90deg, rgba(245, 158, 11, 0.4) 0%, rgba(0, 0, 0, 0.9) 100%);
}

/* Quantité */
.card-quantity {
  width: 24px;
  text-align: center;
  flex-shrink: 0;
  margin-left: 0.5rem;
}

.quantity-number {
  color: #f7fafc;
  font-weight: 700;
  font-size: 0.9rem;
  background: rgba(74, 85, 104, 0.6);
  padding: 0.25rem 0.5rem;
  border-radius: 12px;
  border: 1px solid #4a5568;
}

/* Résumé du deck */
.deck-summary {
  background: rgba(26, 32, 44, 0.8);
  padding: 0.75rem 1rem;
  border-top: 1px solid #4a5568;
  display: flex;
  justify-content: space-between;
  font-size: 0.75rem;
}

.summary-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
}

.summary-label {
  color: #a0aec0;
  text-transform: uppercase;
  font-weight: 500;
}

.summary-value {
  color: #f7fafc;
  font-weight: 600;
}

/* Card preview overlay */
.card-preview-overlay {
  position: fixed;
  z-index: 1000;
  pointer-events: none;
  transition: opacity 0.2s ease;
}

.card-preview-container {
  background: rgba(26, 32, 44, 0.95);
  border: 2px solid var(--primary);
  border-radius: var(--border-radius);
  padding: 1rem;
  backdrop-filter: blur(10px);
  box-shadow: var(--shadow-large);
  max-width: 300px;
}

.preview-image {
  width: 200px;
  height: auto;
  border-radius: 8px;
  margin-bottom: 0.75rem;
}

.preview-name {
  color: #f7fafc;
  font-size: 1rem;
  font-weight: 600;
  margin: 0 0 0.5rem 0;
}

.preview-text {
  color: #cbd5e0;
  font-size: 0.85rem;
  line-height: 1.4;
  margin: 0;
}

/* Responsive */
@media (max-width: 768px) {
  .hearthstone-decklist {
    max-width: none;
    width: 100%;
  }
  
  .deck-meta {
    flex-direction: column;
    gap: 0.5rem;
    align-items: center;
  }
  
  .meta-item {
    flex-direction: row;
    gap: 0.5rem;
  }
  
  .card-entry {
    padding: 0.5rem;
  }
  
  .card-name {
    font-size: 0.8rem;
  }
  
  .preview-image {
    width: 150px;
  }
  
  .card-preview-container {
    max-width: 250px;
  }
}

/* Animation d'entrée */
.card-entry {
  animation: slideInFromLeft 0.3s ease-out;
}

.card-entry:nth-child(odd) {
  animation-delay: 0.05s;
}

.card-entry:nth-child(even) {
  animation-delay: 0.1s;
}

@keyframes slideInFromLeft {
  from {
    opacity: 0;
    transform: translateX(-20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

/* États spéciaux */
.card-entry.selected {
  background: rgba(38, 166, 154, 0.2);
  border-left-color: var(--primary);
}

.card-entry.disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.card-entry.disabled:hover {
  background: transparent;
  border-left-color: transparent;
}
</style>
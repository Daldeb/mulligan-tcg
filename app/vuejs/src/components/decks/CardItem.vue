<template>
  <div 
    class="card-item"
    :class="[
      `rarity-${card.rarity?.toLowerCase()}`,
      `game-${card.cardClass?.toLowerCase()}`,
      { 'in-deck': quantity > 0, 'max-reached': quantity >= maxQuantity }
    ]"
    @mouseenter="startHoverTimer"
    @mouseleave="cancelHoverTimer"
  >
    
    <!-- Image de la carte -->
    <div class="card-image-container">
      <img 
        v-if="card.imageUrl"
        :src="getCardImageUrl(card.imageUrl)"
        :alt="card.name"
        class="card-image"
        @error="handleImageError"
        @load="handleImageLoad"
      />
      <div v-else class="image-placeholder">
        <i class="pi pi-image"></i>
      </div>
      
      <!-- Overlay avec coût -->
      <div class="cost-overlay">
        <div class="mana-crystal" :class="`cost-${card.cost || 0}`">
          {{ card.cost || 0 }}
        </div>
      </div>

      <!-- Badge de rareté -->
      <div class="rarity-badge" :class="`rarity-${card.rarity?.toLowerCase()}`">
        {{ rarityIcon }}
      </div>

      <!-- Quantity indicator si dans le deck -->
      <div v-if="quantity > 0" class="quantity-indicator">
        <span class="quantity-text">{{ quantity }}</span>
      </div>
    </div>

    <!-- Informations de la carte -->
    <div class="card-info">
      <h4 class="card-name" :class="`rarity-text-${card.rarity?.toLowerCase()}`">
        {{ card.name }}
      </h4>
      
      <div class="card-meta">
        <div class="card-stats" v-if="showStats">
          <span v-if="card.attack !== null" class="attack">{{ card.attack }}</span>
          <span v-if="card.attack !== null && card.health !== null" class="separator">/</span>
          <span v-if="card.health !== null" class="health">{{ card.health }}</span>
        </div>
        
        <div class="card-type">
          {{ cardTypeDisplay }}
        </div>
      </div>

      <!-- Description courte -->
      <p v-if="card.text" class="card-description">
        {{ truncatedText }}
      </p>
    </div>

    <!-- Actions -->
    <div class="card-actions">
      <Button 
        icon="pi pi-minus"
        class="quantity-btn remove-btn"
        @click.stop="$emit('remove', card)"
        :disabled="quantity === 0"
        v-tooltip="'Retirer du deck'"
      />
      
      <span class="quantity-display">{{ quantity }}/{{ maxQuantity }}</span>
      
      <Button 
        icon="pi pi-plus"
        class="quantity-btn add-btn"
        @click.stop="$emit('add', card)"
        :disabled="!canAdd"
        v-tooltip="canAdd ? 'Ajouter au deck' : 'Limite atteinte'"
      />
    </div>

    <!-- Hover preview timer -->
    <div v-if="showHoverPreview" class="hover-preview">
      <img 
        :src="getCardImageUrl(card.imageUrl)"
        :alt="card.name"
        class="preview-image"
      />
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onUnmounted } from 'vue'

const props = defineProps({
  card: {
    type: Object,
    required: true
  },
  quantity: {
    type: Number,
    default: 0
  },
  maxQuantity: {
    type: Number,
    default: 2
  },
  canAdd: {
    type: Boolean,
    default: true
  }
})

defineEmits(['add', 'remove', 'preview'])

// State
const showHoverPreview = ref(false)
const hoverTimer = ref(null)
const imageLoaded = ref(false)

// Computed
const canAdd = computed(() => {
  return props.canAdd && props.quantity < props.maxQuantity
})

const rarityIcon = computed(() => {
  const icons = {
    'common': '⚪',
    'rare': '🔵',
    'epic': '🟣',
    'legendary': '🟠'
  }
  return icons[props.card.rarity?.toLowerCase()] || '⚪'
})

const cardTypeDisplay = computed(() => {
  const types = {
    'minion': 'Serviteur',
    'spell': 'Sort',
    'weapon': 'Arme',
    'hero': 'Héros'
  }
  return types[props.card.cardType?.toLowerCase()] || props.card.cardType
})

const showStats = computed(() => {
  // Afficher stats pour les serviteurs et armes
  return ['minion', 'weapon'].includes(props.card.cardType?.toLowerCase())
})

const truncatedText = computed(() => {
  if (!props.card.text) return ''
  return props.card.text.length > 60 
    ? props.card.text.substring(0, 57) + '...'
    : props.card.text
})

// Méthodes
const getCardImageUrl = (imageUrl) => {
  const backendUrl = import.meta.env.VITE_BACKEND_URL
  return `${backendUrl}/uploads/${imageUrl}`
}

const handleImageError = (event) => {
  event.target.style.display = 'none'
  const placeholder = event.target.parentElement.querySelector('.image-placeholder')
  if (placeholder) {
    placeholder.style.display = 'flex'
  }
}

const handleImageLoad = () => {
  imageLoaded.value = true
}

const startHoverTimer = () => {
  hoverTimer.value = setTimeout(() => {
    showHoverPreview.value = true
  }, 800) // Délai avant affichage preview
}

const cancelHoverTimer = () => {
  if (hoverTimer.value) {
    clearTimeout(hoverTimer.value)
    hoverTimer.value = null
  }
  showHoverPreview.value = false
}

// Cleanup
onUnmounted(() => {
  cancelHoverTimer()
})
</script>

<style scoped>
/* === CARD ITEM COMPONENT === */

.card-item {
  background: white;
  border-radius: var(--border-radius);
  border: 2px solid var(--surface-200);
  overflow: hidden;
  transition: all var(--transition-medium);
  cursor: pointer;
  position: relative;
  display: flex;
  flex-direction: column;
}

.card-item:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-medium);
  border-color: var(--primary);
}

/* États spéciaux */
.card-item.in-deck {
  border-color: var(--primary);
  background: rgba(38, 166, 154, 0.05);
}

.card-item.max-reached {
  opacity: 0.7;
}

.card-item.max-reached:hover {
  transform: translateY(-2px);
  cursor: not-allowed;
}

/* Bordures de rareté */
.rarity-common { border-left: 4px solid #9ca3af; }
.rarity-rare { border-left: 4px solid #3b82f6; }
.rarity-epic { border-left: 4px solid #a855f7; }
.rarity-legendary { border-left: 4px solid #f59e0b; }

/* Image container */
.card-image-container {
  position: relative;
  aspect-ratio: 5/7;
  overflow: hidden;
}

.card-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform var(--transition-medium);
}

.card-item:hover .card-image {
  transform: scale(1.05);
}

.image-placeholder {
  width: 100%;
  height: 100%;
  background: var(--surface-200);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text-secondary);
  font-size: 1.5rem;
}

/* Overlays */
.cost-overlay {
  position: absolute;
  top: 8px;
  left: 8px;
}

.mana-crystal {
  width: 28px;
  height: 28px;
  background: radial-gradient(circle, #4299e1 0%, #2b6cb0 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 700;
  font-size: 0.8rem;
  border: 2px solid #1e3a8a;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.8);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

/* Couleurs de mana par coût */
.cost-0 { background: radial-gradient(circle, #6b7280 0%, #374151 100%); border-color: #4b5563; }
.cost-1, .cost-2, .cost-3, .cost-4, .cost-5, .cost-6 { 
  background: radial-gradient(circle, #4299e1 0%, #2563eb 100%); 
  border-color: #1e3a8a; 
}
.cost-7, .cost-8, .cost-9 { 
  background: radial-gradient(circle, #7c3aed 0%, #5b21b6 100%); 
  border-color: #4c1d95; 
}
.cost-10 { 
  background: radial-gradient(circle, #dc2626 0%, #991b1b 100%); 
  border-color: #7f1d1d; 
}

.rarity-badge {
  position: absolute;
  top: 8px;
  right: 8px;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.7rem;
  background: rgba(0, 0, 0, 0.7);
  backdrop-filter: blur(4px);
}

.quantity-indicator {
  position: absolute;
  bottom: 8px;
  right: 8px;
  background: var(--primary);
  color: white;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.8rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

/* Informations de la carte */
.card-info {
  padding: 0.75rem;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.card-name {
  font-size: 0.9rem;
  font-weight: 600;
  margin: 0;
  line-height: 1.2;
  color: var(--text-primary);
}

/* Couleurs de nom par rareté */
.rarity-text-common { color: #6b7280; }
.rarity-text-rare { color: #3b82f6; }
.rarity-text-epic { color: #a855f7; }
.rarity-text-legendary { color: #f59e0b; }

.card-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.75rem;
  color: var(--text-secondary);
}

.card-stats {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-weight: 600;
}

.attack {
  color: #ef4444;
}

.health {
  color: #22c55e;
}

.separator {
  color: var(--text-secondary);
}

.card-type {
  font-size: 0.7rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-weight: 500;
}

.card-description {
  font-size: 0.75rem;
  color: var(--text-secondary);
  line-height: 1.3;
  margin: 0;
  flex: 1;
}

/* Actions */
.card-actions {
  background: var(--surface-100);
  padding: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  border-top: 1px solid var(--surface-200);
}

:deep(.quantity-btn) {
  width: 28px !important;
  height: 28px !important;
  border-radius: 50% !important;
  padding: 0 !important;
  border: 2px solid var(--surface-300) !important;
  background: white !important;
  color: var(--text-secondary) !important;
  font-size: 0.8rem !important;
  transition: all var(--transition-fast) !important;
}

:deep(.add-btn:not(:disabled):hover) {
  border-color: var(--primary) !important;
  color: var(--primary) !important;
  background: rgba(38, 166, 154, 0.1) !important;
  transform: scale(1.1) !important;
}

:deep(.remove-btn:not(:disabled):hover) {
  border-color: #ef4444 !important;
  color: #ef4444 !important;
  background: rgba(239, 68, 68, 0.1) !important;
  transform: scale(1.1) !important;
}

:deep(.quantity-btn:disabled) {
  opacity: 0.3 !important;
  cursor: not-allowed !important;
}

.quantity-display {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--text-primary);
  min-width: 30px;
  text-align: center;
}

/* Hover preview */
.hover-preview {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  z-index: 1000;
  pointer-events: none;
  animation: fadeInScale 0.2s ease-out;
}

.preview-image {
  width: 250px;
  height: auto;
  border-radius: var(--border-radius);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
  border: 3px solid var(--primary);
}

/* Classes de jeu Hearthstone */
.game-mage .card-name { color: #69ccf0; }
.game-hunter .card-name { color: #abd473; }
.game-paladin .card-name { color: #f58cba; }
.game-warrior .card-name { color: #c79c6e; }
.game-priest .card-name { color: #ffffff; }
.game-warlock .card-name { color: #9482c9; }
.game-shaman .card-name { color: #0070de; }
.game-rogue .card-name { color: #fff569; }
.game-druid .card-name { color: #ff7d0a; }
.game-neutral .card-name { color: #9ca3af; }

/* Responsive */
@media (max-width: 768px) {
  .card-info {
    padding: 0.5rem;
  }
  
  .card-name {
    font-size: 0.8rem;
  }
  
  .card-description {
    font-size: 0.7rem;
  }
  
  .card-actions {
    padding: 0.375rem;
  }
  
  :deep(.quantity-btn) {
    width: 24px !important;
    height: 24px !important;
    font-size: 0.7rem !important;
  }
  
  .quantity-display {
    font-size: 0.7rem;
  }
  
  .preview-image {
    width: 200px;
  }
}

/* Animations */
.card-item {
  animation: fadeInScale 0.3s ease-out;
}

.quantity-indicator {
  animation: bounceIn 0.4s ease-out;
}

@keyframes bounceIn {
  0% {
    transform: scale(0);
  }
  50% {
    transform: scale(1.2);
  }
  100% {
    transform: scale(1);
  }
}

/* États de filtre */
.card-item.filtered-out {
  opacity: 0.3;
  transform: scale(0.95);
}

/* Loading state */
.card-image {
  opacity: 0;
  transition: opacity 0.3s ease;
}

.card-image.loaded {
  opacity: 1;
}

/* Focus states */
.card-item:focus-within {
  outline: 3px solid rgba(38, 166, 154, 0.3);
  outline-offset: 2px;
}

/* Accessibility */
@media (prefers-reduced-motion: reduce) {
  .card-item,
  .card-image,
  .quantity-btn {
    transition: none !important;
    animation: none !important;
  }
  
  .card-item:hover {
    transform: none !important;
  }
}
</style>
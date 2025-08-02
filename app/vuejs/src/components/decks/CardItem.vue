<template>
  <div 
    class="card-item-clean"
    :class="{ 'in-deck': quantity > 0, 'max-reached': quantity >= maxQuantity }"
    @mouseenter="startHoverTimer"
    @mouseleave="cancelHoverTimer"
  >
    
    <!-- Image de la carte (fullsize) -->
    <div class="card-image-wrapper">
      <img 
        v-if="card.imageUrl"
        :src="getCardImageUrl(card.imageUrl)"
        :alt="card.name"
        class="card-image-full"
        @error="handleImageError"
        @load="handleImageLoad"
      />
      <div v-else class="image-placeholder-clean">
        <i class="pi pi-image"></i>
        <span>{{ card.name }}</span>
      </div>

      <!-- Quantity indicator si dans le deck -->
      <div v-if="quantity > 0" class="quantity-badge">
        {{ quantity }}
      </div>

      <!-- Actions overlay (apparaît au hover) -->
      <div class="card-actions-overlay">
        <button 
          class="action-btn remove-btn"
          @click.stop="$emit('remove', card)"
          :disabled="quantity === 0"
          title="Retirer du deck"
        >
          <i class="pi pi-minus"></i>
        </button>
        
        <button 
          class="action-btn add-btn"
          @click.stop="$emit('add', card)"
          :disabled="!canAdd"
          :title="canAdd ? 'Ajouter au deck' : 'Limite atteinte'"
        >
          <i class="pi pi-plus"></i>
        </button>
      </div>
    </div>

    <!-- Hover preview (optionnel) -->
    <div v-if="showHoverPreview" class="hover-preview-clean">
      <img 
        :src="getCardImageUrl(card.imageUrl)"
        :alt="card.name"
        class="preview-image-large"
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

// Méthodes
const getCardImageUrl = (imageUrl) => {
  const backendUrl = import.meta.env.VITE_BACKEND_URL
  return `${backendUrl}/uploads/${imageUrl}`
}

const handleImageError = (event) => {
  event.target.style.display = 'none'
  const placeholder = event.target.parentElement.querySelector('.image-placeholder-clean')
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
  }, 1000) // Délai plus long pour preview
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
/* === CARD ITEM ULTRA-CLEAN === */

.card-item-clean {
  position: relative;
  cursor: pointer;
  transition: all 0.2s ease;
  border-radius: 8px;
  overflow: hidden;
}

.card-item-clean:hover {
  transform: translateY(-4px) scale(1.02);
  z-index: 10;
}

/* Container de l'image */
.card-image-wrapper {
  position: relative;
  width: 100%;
  aspect-ratio: 0.714; /* Ratio cartes Hearthstone (5:7) */
  overflow: hidden;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

/* Image fullsize */
.card-image-full {
  width: 100%;
  height: 100%;
  object-fit: contain; /* ← Garde l'aspect ratio, image complète */
  object-position: center; /* ← Centre l'image */
  background: transparent;
  transition: transform 0.2s ease;
}

.card-item-clean:hover .card-image-full {
  transform: scale(1.05);
}

/* Placeholder si pas d'image */
.image-placeholder-clean {
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, #e2e8f0, #cbd5e1);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #64748b;
  font-size: 0.8rem;
  text-align: center;
  gap: 0.5rem;
  padding: 1rem;
}

.image-placeholder-clean i {
  font-size: 1.5rem;
}

/* Badge quantité (toujours visible si > 0) */
.quantity-badge {
  position: absolute;
  top: 8px;
  right: 8px;
  background: #10b981;
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
  z-index: 5;
}

/* Overlay actions (apparaît au hover) */
.card-actions-overlay {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  background: linear-gradient(to top, rgba(0, 0, 0, 0.8), transparent);
  display: flex;
  justify-content: center;
  align-items: flex-end;
  gap: 0.75rem;
  padding: 1rem;
  opacity: 0;
  transform: translateY(100%);
  transition: all 0.3s ease;
}

.card-item-clean:hover .card-actions-overlay {
  opacity: 1;
  transform: translateY(0);
}

/* Boutons d'action */
.action-btn {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: 2px solid rgba(255, 255, 255, 0.9);
  background: rgba(255, 255, 255, 0.95);
  color: #374151;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 0.9rem;
  font-weight: 600;
  backdrop-filter: blur(4px);
}

.action-btn:hover {
  transform: scale(1.1);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.add-btn:hover {
  background: #10b981;
  color: white;
  border-color: #10b981;
}

.remove-btn:hover {
  background: #ef4444;
  color: white;
  border-color: #ef4444;
}

.action-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
}

.action-btn:disabled:hover {
  background: rgba(255, 255, 255, 0.95);
  color: #374151;
  border-color: rgba(255, 255, 255, 0.9);
  transform: none;
}

/* États spéciaux */
.card-item-clean.in-deck {
  box-shadow: 0 0 0 2px #10b981;
}

.card-item-clean.max-reached {
  opacity: 0.7;
}

.card-item-clean.max-reached:hover {
  transform: translateY(-2px) scale(1.01);
}

/* Hover preview */
.hover-preview-clean {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  z-index: 1000;
  pointer-events: none;
  animation: fadeInPreview 0.3s ease-out;
}

.preview-image-large {
  width: 300px;
  height: auto;
  border-radius: 12px;
  box-shadow: 0 12px 48px rgba(0, 0, 0, 0.6);
  border: 3px solid #10b981;
}

@keyframes fadeInPreview {
  from {
    opacity: 0;
    transform: translate(-50%, -50%) scale(0.8);
  }
  to {
    opacity: 1;
    transform: translate(-50%, -50%) scale(1);
  }
}

/* Responsive */
@media (max-width: 768px) {
  .card-actions-overlay {
    padding: 0.75rem;
    gap: 0.5rem;
  }
  
  .action-btn {
    width: 32px;
    height: 32px;
    font-size: 0.8rem;
  }
  
  .quantity-badge {
    width: 20px;
    height: 20px;
    font-size: 0.7rem;
  }
  
  .preview-image-large {
    width: 250px;
  }
}
</style>
<template>
  <div 
    class="card-item-simple"
    :class="{ 'in-deck': quantity > 0, 'max-reached': quantity >= maxQuantity }"
    @click="handleCardClick"
  >
    
    <!-- Image de la carte (fullsize) -->
    <div class="card-image-wrapper">
      <img 
        v-if="card.imageUrl"
        :src="getCardImageUrl(card.imageUrl)"
        :alt="card.name"
        class="card-image-full"
        @error="handleImageError"
      />
      <div v-else class="image-placeholder-simple">
        <i class="pi pi-image"></i>
        <span>{{ card.name }}</span>
      </div>

      <!-- Badge quantité si dans le deck -->
      <div v-if="quantity > 0" class="quantity-badge">
        {{ quantity }}
      </div>
    </div>

  </div>
</template>

<script setup>
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

const emit = defineEmits(['add', 'remove'])

// Méthodes
const getCardImageUrl = (imageUrl) => {
  const backendUrl = import.meta.env.VITE_BACKEND_URL
  return `${backendUrl}/uploads/${imageUrl}`
}

const handleImageError = (event) => {
  event.target.style.display = 'none'
  const placeholder = event.target.parentElement.querySelector('.image-placeholder-simple')
  if (placeholder) {
    placeholder.style.display = 'flex'
  }
}

const handleCardClick = () => {
  if (props.canAdd && props.quantity < props.maxQuantity) {
    emit('add', props.card)
  }
}
</script>

<style scoped>
/* === CARD ITEM ULTRA-SIMPLE === */

.card-item-simple {
  position: relative;
  cursor: pointer;
  transition: all 0.2s ease;
  border-radius: 8px;
  overflow: hidden;
}

.card-item-simple:hover {
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
  transition: box-shadow 0.2s ease;
}

.card-item-simple:hover .card-image-wrapper {
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4);
}

/* Image fullsize - SANS ZOOM */
.card-image-full {
  width: 100%;
  height: 100%;
  object-fit: contain;
  object-position: center;
  background: transparent;
}

/* Placeholder si pas d'image */
.image-placeholder-simple {
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

.image-placeholder-simple i {
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

/* États spéciaux */
.card-item-simple.in-deck {
  box-shadow: 0 0 0 2px #10b981;
}

.card-item-simple.in-deck .card-image-wrapper {
  box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
}

.card-item-simple.in-deck:hover .card-image-wrapper {
  box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
}

.card-item-simple.max-reached {
  opacity: 0.6;
  cursor: not-allowed;
}

.card-item-simple.max-reached:hover {
  transform: none;
}

/* Feedback visuel au clic */
.card-item-simple:active {
  transform: translateY(-2px) scale(0.98);
}

.card-item-simple.max-reached:active {
  transform: none;
}

/* Responsive */
@media (max-width: 768px) {
  .quantity-badge {
    width: 20px;
    height: 20px;
    font-size: 0.7rem;
    top: 6px;
    right: 6px;
  }
  
  .card-item-simple:hover {
    transform: translateY(-2px) scale(1.01);
  }
}
</style>
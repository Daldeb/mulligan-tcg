<template>
  <Dialog 
    v-model:visible="isVisible" 
    modal 
    :closable="false"
    :showHeader="false"
    :style="{ width: '90vw', maxWidth: '1200px', height: '80vh' }"
    class="deck-gallery-modal"
    :contentStyle="{ padding: '0', overflow: 'hidden' }"
  >
    <div class="gallery-container" v-if="currentDeck">
      <!-- Header simple -->
      <div class="gallery-header">
        <h3 class="deck-title">{{ currentDeck.title }}</h3>
        <div class="header-controls">
          <span class="deck-counter">{{ currentIndex + 1 }} / {{ decks.length }}</span>
          <Button 
            icon="pi pi-times" 
            class="close-btn"
            @click="closeModal"
            text
            rounded
          />
        </div>
      </div>

      <!-- Contenu avec bandes floutées -->
      <div class="gallery-content">
        <!-- Navigation précédent -->
        <Button
          v-if="hasPrevious"
          icon="pi pi-chevron-left"
          class="nav-btn nav-prev"
          @click="previousDeck"
          rounded
        />

        <!-- Viewport avec bandes -->
        <div class="image-viewport">
          <!-- Bande floutée gauche -->
          <div 
            class="blur-band blur-left"
            :style="{ 
              backgroundImage: `url(${currentDeck.imagePath})`,
              backgroundPosition: 'left center',
              backgroundSize: 'cover'
            }"
          ></div>
          
          <!-- Image principale -->
          <div class="main-image-container">
            <img
              :src="currentDeck.imagePath"
              :alt="currentDeck.title"
              class="main-image"
            />
          </div>
          
          <!-- Bande floutée droite -->
          <div 
            class="blur-band blur-right"
            :style="{ 
              backgroundImage: `url(${currentDeck.imagePath})`,
              backgroundPosition: 'right center',
              backgroundSize: 'cover'
            }"
          ></div>
        </div>

        <!-- Navigation suivant -->
        <Button
          v-if="hasNext"
          icon="pi pi-chevron-right"
          class="nav-btn nav-next"
          @click="nextDeck"
          rounded
        />
      </div>

      <!-- Footer avec métadonnées -->
      <div class="gallery-footer" v-if="currentDeck.metadata">
        <div class="metadata-info">
          <!-- Hearthstone -->
          <div v-if="currentDeck.game === 'hearthstone'" class="game-meta">
            <span v-if="currentDeck.metadata.winrate" class="meta-tag winrate">
              {{ currentDeck.metadata.winrate }}% WR
            </span>
            <span v-if="currentDeck.metadata.games" class="meta-tag games">
              {{ formatNumber(currentDeck.metadata.games) }} games
            </span>
            <span v-if="currentDeck.metadata.class" class="meta-tag class-tag">
              {{ currentDeck.metadata.class }}
            </span>
          </div>
          
          <!-- Pokemon -->
          <div v-if="currentDeck.game === 'pokemon'" class="game-meta">
            <span v-if="currentDeck.metadata.player" class="meta-tag player">
              {{ currentDeck.metadata.player }}
            </span>
            <span v-if="currentDeck.metadata.rank" class="meta-tag rank">
              #{{ currentDeck.metadata.rank }}
            </span>
          </div>
          
          <!-- Magic -->
          <div v-if="currentDeck.game === 'magic'" class="game-meta">
            <span v-if="currentDeck.metadata.archetype" class="meta-tag archetype">
              {{ currentDeck.metadata.archetype }}
            </span>
          </div>
        </div>
        
        <Button
          v-if="currentDeck.url"
          label="Voir la source"
          icon="pi pi-external-link"
          class="source-btn"
          @click="openDeckUrl"
          outlined
          size="small"
        />
      </div>
    </div>
  </Dialog>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'

const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  },
  decks: {
    type: Array,
    required: true
  },
  initialIndex: {
    type: Number,
    default: 0
  }
})

const emit = defineEmits(['update:visible'])

// État simple
const currentIndex = ref(0)

const isVisible = computed({
  get: () => props.visible,
  set: (value) => emit('update:visible', value)
})

const currentDeck = computed(() => props.decks[currentIndex.value])
const hasPrevious = computed(() => currentIndex.value > 0)
const hasNext = computed(() => currentIndex.value < props.decks.length - 1)

// Watchers
watch(() => props.initialIndex, (newIndex) => {
  currentIndex.value = newIndex
}, { immediate: true })

watch(isVisible, (visible) => {
  if (visible) {
    currentIndex.value = props.initialIndex
  }
})

// Méthodes
const previousDeck = () => {
  if (hasPrevious.value) {
    currentIndex.value--
  }
}

const nextDeck = () => {
  if (hasNext.value) {
    currentIndex.value++
  }
}

const closeModal = () => {
  isVisible.value = false
}

const openDeckUrl = () => {
  if (currentDeck.value?.url) {
    window.open(currentDeck.value.url, '_blank', 'noopener,noreferrer')
  }
}

const formatNumber = (num) => {
  if (!num) return '0'
  if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M'
  if (num >= 1000) return (num / 1000).toFixed(1) + 'K'
  return num.toString()
}

// Navigation clavier
const handleKeydown = (event) => {
  if (!isVisible.value) return
  
  switch (event.key) {
    case 'ArrowLeft':
      event.preventDefault()
      previousDeck()
      break
    case 'ArrowRight':
      event.preventDefault()
      nextDeck()
      break
    case 'Escape':
      event.preventDefault()
      closeModal()
      break
  }
}

onMounted(() => {
  document.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
})
</script>

<style scoped>
/* Modal style Reddit */
:deep(.deck-gallery-modal .p-dialog-mask) {
  backdrop-filter: blur(10px);
  background: rgba(0, 0, 0, 0.85);
}

:deep(.deck-gallery-modal .p-dialog) {
  background: #1a1a1b;
  border: none;
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
  border-radius: 16px;
  overflow: hidden;
}

.gallery-container {
  width: 100%;
  height: 80vh;
  display: flex;
  flex-direction: column;
  background: #1a1a1b;
  color: white;
}

/* Header */
.gallery-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem;
  background: rgba(0, 0, 0, 0.4);
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.deck-title {
  font-size: 1.1rem;
  font-weight: 600;
  margin: 0;
  color: white;
}

.header-controls {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.deck-counter {
  padding: 0.25rem 0.75rem;
  background: rgba(255, 255, 255, 0.15);
  border-radius: 12px;
  font-size: 0.85rem;
  font-weight: 500;
}

:deep(.close-btn) {
  color: white !important;
  background: rgba(255, 255, 255, 0.1) !important;
  width: 36px !important;
  height: 36px !important;
}

:deep(.close-btn:hover) {
  background: rgba(255, 255, 255, 0.2) !important;
}

/* Contenu principal */
.gallery-content {
  flex: 1;
  display: flex;
  align-items: center;
  position: relative;
}

.image-viewport {
  flex: 1;
  height: 100%;
  display: flex;
  align-items: center;
  position: relative;
  overflow: hidden;
}

/* Bandes floutées plus visibles */
.blur-band {
  width: 150px;
  height: 100%;
  background-repeat: no-repeat;
  filter: blur(10px) brightness(0.8);
  opacity: 0.9;
  flex-shrink: 0;
}

.blur-left {
  transform: scaleX(-1);
}

/* Container image principale */
.main-image-container {
  flex: 1;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.3);
  padding: 2rem;
}

.main-image {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
  border-radius: 8px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
}

/* Navigation */
:deep(.nav-btn) {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background: rgba(0, 0, 0, 0.7) !important;
  color: white !important;
  width: 50px !important;
  height: 50px !important;
  font-size: 1.25rem !important;
  z-index: 10;
  backdrop-filter: blur(10px) !important;
}

:deep(.nav-btn:hover) {
  background: rgba(0, 0, 0, 0.9) !important;
  transform: translateY(-50%) scale(1.1) !important;
}

:deep(.nav-prev) {
  left: 20px;
}

:deep(.nav-next) {
  right: 20px;
}

/* Footer */
.gallery-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem;
  background: rgba(0, 0, 0, 0.3);
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.metadata-info {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.game-meta {
  display: flex;
  gap: 0.75rem;
  align-items: center;
}

.meta-tag {
  padding: 0.25rem 0.75rem;
  border-radius: 16px;
  font-size: 0.8rem;
  font-weight: 500;
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
}

.meta-tag.winrate {
  background: rgba(76, 175, 80, 0.2);
  color: #4ade80;
}

.meta-tag.games {
  background: rgba(255, 193, 7, 0.2);
  color: #fbbf24;
}

.meta-tag.class-tag,
.meta-tag.player {
  background: rgba(33, 150, 243, 0.2);
  color: #60a5fa;
}

.meta-tag.rank {
  background: rgba(255, 152, 0, 0.2);
  color: #fbbf24;
}

.meta-tag.archetype {
  background: rgba(156, 39, 176, 0.2);
  color: #a78bfa;
}

:deep(.source-btn) {
  border-color: rgba(255, 255, 255, 0.3) !important;
  color: white !important;
  background: rgba(255, 255, 255, 0.1) !important;
}

:deep(.source-btn:hover) {
  background: rgba(255, 255, 255, 0.2) !important;
  border-color: rgba(255, 255, 255, 0.5) !important;
}

/* Responsive */
@media (max-width: 768px) {
  :deep(.deck-gallery-modal .p-dialog) {
    width: 95vw !important;
    height: 90vh !important;
  }
  
  .blur-band {
    width: 80px;
  }
  
  .gallery-footer {
    flex-direction: column;
    gap: 1rem;
    align-items: flex-start;
  }
  
  :deep(.nav-prev) {
    left: 10px;
  }
  
  :deep(.nav-next) {
    right: 10px;
  }
}

@media (max-width: 480px) {
  .blur-band {
    width: 60px;
  }
  
  .gallery-header,
  .gallery-footer {
    padding: 1rem;
  }
  
  .main-image-container {
    padding: 1rem;
  }
}
</style>
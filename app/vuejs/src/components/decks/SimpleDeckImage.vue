<template>
  <div 
    class="simple-deck-image" 
    :class="deck.game"
    :data-index="index"
    :data-game="deck.game"
    ref="deckElement"
    @click="openGallery"
    v-if="!imageError"
  >
    <img
      :src="deck.imagePath"
      :alt="deck.title"
      class="deck-img"
      @load="onImageLoad"
      @error="onImageError"
      ref="imageElement"
    />
    
    <!-- Loading simple -->
    <div v-if="isLoading" class="simple-loading">
      <div class="loading-dot"></div>
    </div>
  </div>
</template>

<script setup>
import { ref, nextTick } from 'vue'

const props = defineProps({
  deck: {
    type: Object,
    required: true
  },
  index: {
    type: Number,
    required: true
  }
})

const imageError = ref(false)

const onImageError = () => {
  isLoading.value = false
  imageError.value = true // Masquer le composant si erreur
//   console.error('Erreur image:', props.deck.imagePath)
}

const emit = defineEmits(['openGallery', 'imageLoaded'])

const isLoading = ref(true)
const deckElement = ref(null)
const imageElement = ref(null)

const onImageLoad = async () => {
  isLoading.value = false
  
  // Attendre le prochain tick pour que le DOM soit mis à jour
  await nextTick()
  
  // Émettre l'événement pour recalculer les positions
  emit('imageLoaded', {
    index: props.index,
    element: deckElement.value,
    height: imageElement.value?.offsetHeight || 0
  })
}

const openGallery = () => {
  emit('openGallery', props.index)
}
</script>

<style scoped>
.simple-deck-image {
  position: relative;
  cursor: pointer;
  border-radius: 8px;
  overflow: hidden;
  transition: transform 0.2s ease;
  background: transparent;
  width: 100%;
}

.simple-deck-image:hover {
  transform: scale(1.02);
  z-index: 2;
}

.deck-img {
  width: 100%;
  height: auto;
  display: block;
  transition: opacity 0.3s ease;
}

.simple-loading {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  z-index: 1;
}

.loading-dot {
  width: 20px;
  height: 20px;
  background: var(--primary);
  border-radius: 50%;
  animation: pulse 1.5s ease-in-out infinite;
}

@keyframes pulse {
  0% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.5; transform: scale(0.8); }
  100% { opacity: 1; transform: scale(1); }
}

/* États par jeu - Optimisations spécifiques */
.simple-deck-image.hearthstone {
  /* Format portrait étroit - optimisé pour les listes de cartes */
  min-height: 200px;
}

.simple-deck-image.magic {
  /* Format paysage large - images uniformes */
  min-height: 300px;
}

.simple-deck-image.pokemon {
  /* Format paysage moyen - variabilité modérée */
  min-height: 250px;
}
</style>
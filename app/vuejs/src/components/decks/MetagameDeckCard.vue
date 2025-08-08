<template>
  <div 
    class="metagame-deck-card" 
    :class="[`${deck.game}-deck`, { 'loading': isImageLoading }]"
  >
    <div class="deck-image-container" @click="openGallery">
      <!-- Image du deck -->
      <img 
        :src="deck.imagePath" 
        :alt="deck.title"
        class="deck-image"
        @load="onImageLoad"
        @error="onImageError"
      />
      
      <!-- Overlay de loading -->
      <div v-if="isImageLoading" class="image-loading-overlay">
        <div class="loading-spinner"></div>
      </div>
      
      <!-- Overlay zoom -->
      <div class="zoom-overlay">
        <i class="pi pi-search-plus zoom-icon"></i>
        <span class="zoom-text">Cliquer pour agrandir</span>
      </div>
      
      <!-- Badge de jeu -->
      <div class="game-badge" :class="deck.game">
        <i :class="gameIcons[deck.game]"></i>
        <span>{{ gameLabels[deck.game] }}</span>
      </div>
      
      <!-- Badge de format -->
      <div class="format-badge" :class="deck.format">
        {{ formatLabels[deck.format] || deck.format }}
      </div>
      
      
      <!-- Métadonnées au hover -->
      <div class="metadata-overlay">
        <div class="deck-title">
          {{ deck.title }}
        </div>
        
        <!-- Métadonnées Hearthstone -->
        <div v-if="deck.game === 'hearthstone'" class="game-metadata hearthstone-meta">
          <div v-if="deck.metadata.winrate" class="meta-item winrate">
            <i class="pi pi-chart-line"></i>
            <span>{{ deck.metadata.winrate }}% WR</span>
          </div>
          <div v-if="deck.metadata.games" class="meta-item games">
            <i class="pi pi-users"></i>
            <span>{{ formatNumber(deck.metadata.games) }} games</span>
          </div>
          <div v-if="deck.metadata.class" class="meta-item class">
            <i class="pi pi-user"></i>
            <span>{{ deck.metadata.class }}</span>
          </div>
        </div>
        
        <!-- Métadonnées Magic -->
        <div v-if="deck.game === 'magic'" class="game-metadata magic-meta">
          <div v-if="deck.metadata.colors?.length" class="meta-item colors">
            <div class="color-icons">
              <span 
                v-for="color in deck.metadata.colors" 
                :key="color"
                class="color-symbol"
                :class="`color-${color.toLowerCase()}`"
              >
                {{ color }}
              </span>
            </div>
          </div>
          <div v-if="deck.metadata.archetype" class="meta-item archetype">
            <i class="pi pi-tag"></i>
            <span>{{ deck.metadata.archetype }}</span>
          </div>
        </div>
        
        <!-- Métadonnées Pokemon -->
        <div v-if="deck.game === 'pokemon'" class="game-metadata pokemon-meta">
          <div v-if="deck.metadata.player" class="meta-item player">
            <i class="pi pi-user"></i>
            <span>{{ deck.metadata.player }}</span>
          </div>
          <div v-if="deck.metadata.rank" class="meta-item rank">
            <i class="pi pi-trophy"></i>
            <span>{{ deck.metadata.rank }}</span>
          </div>
          <div v-if="deck.metadata.tournament" class="meta-item tournament">
            <i class="pi pi-calendar"></i>
            <span>{{ truncateText(deck.metadata.tournament, 20) }}</span>
          </div>
        </div>
      </div>
      
      <!-- Actions externes -->
      <div class="deck-actions">
        <Button 
          icon="pi pi-external-link" 
          class="action-btn view-deck"
          size="small"
          rounded
          v-tooltip="'Voir sur le site source'"
          @click.stop="openDeckUrl"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import Button from 'primevue/button'

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

const emit = defineEmits(['openGallery'])

const isImageLoading = ref(true)
const imageError = ref(false)

// Configuration des jeux
const gameIcons = {
  hearthstone: 'pi pi-heart-fill',
  magic: 'pi pi-star-fill',
  pokemon: 'pi pi-bolt'
}

const gameLabels = {
  hearthstone: 'Hearthstone',
  magic: 'Magic',
  pokemon: 'Pokemon'
}

const formatLabels = {
  standard: 'Standard',
  wild: 'Wild',
  commander: 'Commander',
  historic: 'Historic'
}

// Gestion des images
const onImageLoad = () => {
  isImageLoading.value = false
  imageError.value = false
}

const onImageError = () => {
  isImageLoading.value = false
  imageError.value = true
  console.error('Erreur chargement image:', props.deck.imagePath)
}

// Actions
const openGallery = () => {
  emit('openGallery', props.index)
}

const openDeckUrl = () => {
  if (props.deck.url) {
    window.open(props.deck.url, '_blank', 'noopener,noreferrer')
  }
}

// Utilitaires
const formatNumber = (num) => {
  if (!num) return '0'
  if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M'
  if (num >= 1000) return (num / 1000).toFixed(1) + 'K'
  return num.toString()
}

const truncateText = (text, maxLength) => {
  if (!text || text.length <= maxLength) return text
  return text.substring(0, maxLength) + '...'
}
</script>

<style scoped>
/* === METAGAME DECK CARD ADAPTATIF === */

.metagame-deck-card {
  position: relative;
  border-radius: var(--border-radius-large);
  overflow: hidden;
  cursor: pointer;
  transition: all var(--transition-medium);
  background: white;
  border: 2px solid var(--surface-200);
  box-shadow: var(--shadow-small);
}

.metagame-deck-card:hover {
  transform: translateY(-4px) scale(1.02);
  box-shadow: var(--shadow-large);
  border-color: var(--primary);
}

.metagame-deck-card.loading {
  opacity: 0.8;
}

/* === CONTENEUR IMAGE ADAPTATIF === */

.deck-image-container {
  position: relative;
  width: 100%;
  overflow: hidden;
  border-radius: var(--border-radius);
  cursor: pointer;
}

/* Dimensions adaptées aux VRAIS screenshots */
.hearthstone-deck .deck-image-container {
  aspect-ratio: 9 / 16; /* Format portrait pour liste Hearthstone */
}

.magic-deck .deck-image-container {
  aspect-ratio: 16 / 10; /* Format paysage large pour deck Magic complet */
}

.pokemon-deck .deck-image-container {
  aspect-ratio: 16 / 9; /* Format paysage pour decklist Pokemon */
}

.deck-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: all var(--transition-medium);
}

.metagame-deck-card:hover .deck-image {
  transform: scale(1.05);
}

/* === OVERLAY DE LOADING === */

.image-loading-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: var(--surface-100);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2;
}

.loading-spinner {
  width: 32px;
  height: 32px;
  border: 3px solid var(--surface-300);
  border-top: 3px solid var(--primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* === BADGES === */

.game-badge {
  position: absolute;
  top: 12px;
  left: 12px;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.75rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.3);
  z-index: 3;
}

.game-badge.hearthstone {
  background: linear-gradient(135deg, rgba(255, 87, 34, 0.9), rgba(255, 152, 0, 0.8));
  color: white;
}

.game-badge.magic {
  background: linear-gradient(135deg, rgba(103, 58, 183, 0.9), rgba(156, 39, 176, 0.8));
  color: white;
}

.game-badge.pokemon {
  background: linear-gradient(135deg, rgba(255, 193, 7, 0.9), rgba(255, 152, 0, 0.8));
  color: white;
}

.format-badge {
  position: absolute;
  top: 12px;
  right: 12px;
  padding: 0.375rem 0.75rem;
  background: rgba(0, 0, 0, 0.7);
  color: white;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  backdrop-filter: blur(5px);
  z-index: 3;
}

/* === OVERLAY ZOOM === */

.zoom-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: white;
  opacity: 0;
  transition: all var(--transition-medium);
  z-index: 2;
}

.metagame-deck-card:hover .zoom-overlay {
  opacity: 1;
}

.zoom-icon {
  font-size: 2rem;
  margin-bottom: 0.5rem;
}

.zoom-text {
  font-size: 0.9rem;
  font-weight: 600;
  text-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
}

/* === OVERLAY MÉTADONNÉES === */

.metadata-overlay {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  background: linear-gradient(transparent, rgba(0, 0, 0, 0.9));
  padding: 2rem 1rem 1rem;
  color: white;
  opacity: 0;
  transition: all var(--transition-medium);
  z-index: 3;
}

.metagame-deck-card:hover .metadata-overlay {
  opacity: 1;
}

.deck-title {
  font-size: 1rem;
  font-weight: 700;
  margin-bottom: 0.75rem;
  line-height: 1.2;
  text-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
}

/* === MÉTADONNÉES PAR JEU === */

.game-metadata {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.85rem;
  font-weight: 500;
}

.meta-item i {
  font-size: 0.8rem;
  opacity: 0.8;
}

/* Métadonnées Hearthstone */
.hearthstone-meta .winrate {
  color: #4ade80;
}

.hearthstone-meta .games {
  color: #fbbf24;
}

.hearthstone-meta .class {
  color: #f87171;
}

/* Métadonnées Magic - Symboles de couleurs */
.color-icons {
  display: flex;
  gap: 0.25rem;
}

.color-symbol {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.7rem;
  font-weight: 700;
  color: white;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
}

.color-symbol.color-w { background: linear-gradient(135deg, #FFFBD5, #F0F0F0); color: #8B4513; }
.color-symbol.color-u { background: linear-gradient(135deg, #0E68AB, #4FC3F7); }
.color-symbol.color-b { background: linear-gradient(135deg, #150B00, #424242); }
.color-symbol.color-r { background: linear-gradient(135deg, #D3202A, #FF5722); }
.color-symbol.color-g { background: linear-gradient(135deg, #00733E, #4CAF50); }

/* Métadonnées Pokemon */
.pokemon-meta .player {
  color: #60a5fa;
}

.pokemon-meta .rank {
  color: #fbbf24;
}

.pokemon-meta .tournament {
  color: #a78bfa;
}

/* === ACTIONS === */

.deck-actions {
  position: absolute;
  bottom: 12px;
  right: 12px;
  opacity: 0;
  transition: all var(--transition-medium);
  z-index: 4;
}

.metagame-deck-card:hover .deck-actions {
  opacity: 1;
}

:deep(.action-btn) {
  background: rgba(38, 166, 154, 0.9) !important;
  border: 1px solid rgba(255, 255, 255, 0.3) !important;
  color: white !important;
  backdrop-filter: blur(10px) !important;
  width: 36px !important;
  height: 36px !important;
}

:deep(.action-btn:hover) {
  background: var(--primary) !important;
  transform: scale(1.1) !important;
  box-shadow: 0 4px 12px rgba(38, 166, 154, 0.4) !important;
}

/* === RESPONSIVE === */

@media (max-width: 768px) {
  .metadata-overlay {
    opacity: 1;
    background: linear-gradient(transparent, rgba(0, 0, 0, 0.9));
  }
  
  .deck-actions {
    opacity: 1;
  }
  
  .game-badge {
    top: 8px;
    left: 8px;
    font-size: 0.7rem;
    padding: 0.375rem 0.625rem;
  }
  
  .format-badge {
    top: 8px;
    right: 8px;
    font-size: 0.7rem;
    padding: 0.25rem 0.625rem;
  }
}

@media (max-width: 480px) {
  .deck-title {
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
  }
  
  .meta-item {
    font-size: 0.8rem;
  }
  
  .game-metadata {
    gap: 0.375rem;
  }
}

/* === ANIMATIONS === */

.metagame-deck-card {
  animation: fadeInScale 0.6s ease-out;
}

@keyframes fadeInScale {
  from {
    opacity: 0;
    transform: scale(0.9);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

/* === ÉTATS D'ERREUR === */

.metagame-deck-card.error .deck-image {
  background: var(--surface-200);
  display: flex;
  align-items: center;
  justify-content: center;
}

.metagame-deck-card.error .deck-image::before {
  content: '🎴';
  font-size: 3rem;
  opacity: 0.5;
}
</style>
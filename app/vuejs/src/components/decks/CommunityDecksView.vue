<script setup>
import { ref, computed, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useGameFilterStore } from '../../stores/gameFilter'
import HearthstoneCompactDeck from '../../components/decks/HearthstoneCompactDeck.vue'
import MagicCompactDeck from '../../components/decks/MagicCompactDeck.vue'
import api from '../../services/api'
import Card from 'primevue/card'

const allDecks = ref([])
const isLoading = ref(true)

const gameFilterStore = useGameFilterStore()
const { selectedGames } = storeToRefs(gameFilterStore)

onMounted(async () => {
  try {
    console.log('[📡 Requête API] /api/decks/community...')
    const response = await api.get('/api/decks/community')
    console.log('[✅ Réponse API reçue]', response.data)

    const decks = Array.isArray(response.data.data)
      ? response.data.data
      : Array.isArray(response.data)
      ? response.data
      : []

    allDecks.value = decks
    console.log(`[📦 Decks chargés] ${decks.length} decks`)
  } catch (error) {
    console.error('[❌ Erreur chargement decks communauté]:', error)
    allDecks.value = []
  } finally {
    isLoading.value = false
  }
})

const games = [
  {
    slug: 'hearthstone',
    name: 'Hearthstone',
    component: HearthstoneCompactDeck
  },
  {
    slug: 'magic',
    name: 'Magic: The Gathering',
    component: MagicCompactDeck
  },
  {
    slug: 'pokemon',
    name: 'Pokémon TCG',
    component: {
      props: ['deck'],
      template: `
        <div class='gaming-card placeholder-deck'>
          <div class='p-4'>
            <strong>{{ deck.title }}</strong>
            <p>Affichage Pokémon en cours de développement.</p>
          </div>
        </div>
      `
    }
  }
]

const visibleGames = computed(() => {
  return selectedGames.value.length > 0
    ? games.filter(g => selectedGames.value.includes(g.slug))
    : games
})

const freshnessScore = (updatedAt) => {
  if (!updatedAt) return 0
  const daysAgo = (new Date() - new Date(updatedAt)) / (1000 * 3600 * 24)
  return Math.max(0, 30 - daysAgo)
}

const computeScore = (deck) => {
  const likes = deck.likes || 0
  return likes + freshnessScore(deck.updatedAt)
}

const filteredDecksByGame = (slug) => {
  return [...(allDecks.value || [])]
    .filter(d => d?.game?.slug === slug)
    .sort((a, b) => computeScore(b) - computeScore(a))
}

const toggleLike = (deck) => {
  console.log('[👍 Like] Deck ID:', deck.id)
}
</script>

<template>
  <div class="community-decks-page">
    <div class="container">
      
      <!-- État de chargement -->
      <div v-if="isLoading" class="loading-state">
        <Card class="gaming-card loading-card">
          <template #content>
            <div class="loading-content">
              <div class="emerald-spinner"></div>
              <p>Chargement des decks communautaires...</p>
            </div>
          </template>
        </Card>
      </div>

      <!-- Sections par jeu -->
      <div class="games-sections" v-if="!isLoading && allDecks.length > 0">
        
        <!-- Section Hearthstone -->
        <div v-if="filteredDecksByGame('hearthstone').length > 0" class="game-section hearthstone-section slide-in-up">
          <div class="game-header">
            <div class="game-title-area">
              <div class="game-badge hearthstone">
                <i class="game-icon">🃏</i>
                <span class="game-name">Hearthstone</span>
              </div>
              <div class="game-stats">
                <span class="deck-count">{{ filteredDecksByGame('hearthstone').length }} decks</span>
              </div>
            </div>
          </div>
          
          <div class="decks-grid">
            <HearthstoneCompactDeck 
              v-for="deck in filteredDecksByGame('hearthstone')" 
              :key="`community-hs-${deck.id}`"
              :deck="deck"
              @like="toggleLike"
            />
          </div>
        </div>

        <!-- Section Magic -->
        <div v-if="filteredDecksByGame('magic').length > 0" class="game-section magic-section slide-in-up">
          <div class="game-header">
            <div class="game-title-area">
              <div class="game-badge magic">
                <i class="game-icon">🎴</i>
                <span class="game-name">Magic: The Gathering</span>
              </div>
              <div class="game-stats">
                <span class="deck-count">{{ filteredDecksByGame('magic').length }} decks</span>
              </div>
            </div>
          </div>
          
          <div class="decks-grid">
            <MagicCompactDeck 
              v-for="deck in filteredDecksByGame('magic')" 
              :key="`community-magic-${deck.id}`"
              :deck="deck"
              @like="toggleLike"
            />
          </div>
        </div>

        <!-- Section Pokemon -->
        <div v-if="filteredDecksByGame('pokemon').length > 0" class="game-section pokemon-section slide-in-up">
          <div class="game-header">
            <div class="game-title-area">
              <div class="game-badge pokemon">
                <i class="game-icon">⚡</i>
                <span class="game-name">Pokemon TCG</span>
              </div>
              <div class="game-stats">
                <span class="deck-count">{{ filteredDecksByGame('pokemon').length }} decks</span>
              </div>
            </div>
          </div>
          
          <div class="decks-grid">
            <Card 
              v-for="deck in filteredDecksByGame('pokemon')" 
              :key="`community-pkmn-${deck.id}`"
              class="deck-card gaming-card hover-lift"
            >
              <template #content>
                <div class="deck-content">
                  <div class="deck-header-info">
                    <h3 class="deck-name">{{ deck.title }}</h3>
                    <div class="deck-author">Par {{ deck.author }}</div>
                  </div>
                  <div class="deck-meta">
                    <span class="format-badge pokemon">{{ deck.format.name }}</span>
                  </div>
                  <div class="deck-stats-info">
                    <span class="likes">{{ deck.likes || 0 }} ❤️</span>
                    <span class="cards">{{ deck.totalCards || 0 }}/60 cartes</span>
                  </div>
                </div>
              </template>
            </Card>
          </div>
        </div>

      </div>

      <!-- État vide -->
      <div v-if="!isLoading && allDecks.length === 0" class="empty-state">
        <Card class="gaming-card empty-card">
          <template #content>
            <div class="empty-content">
              <i class="pi pi-clone empty-icon"></i>
              <h3 class="empty-title">Aucun deck communautaire</h3>
              <p class="empty-description">
                Les decks partagés par la communauté apparaîtront ici !
              </p>
            </div>
          </template>
        </Card>
      </div>

    </div>
  </div>
</template>

<style scoped>
/* Reprendre exactement les styles de MyDecksView.vue */
.community-decks-page {
  min-height: calc(100vh - 140px);
  background: var(--surface-gradient);
  padding: 2rem 0;
}

.container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 2rem;
}

/* Games sections */
.games-sections {
  display: flex;
  flex-direction: column;
  gap: 3rem;
}

.game-section {
  background: white;
  border-radius: var(--border-radius-large);
  border: 1px solid var(--surface-200);
  box-shadow: var(--shadow-small);
  overflow: hidden;
  position: relative;
}

.game-section::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
}

.hearthstone-section::before {
  background: linear-gradient(90deg, var(--primary), var(--primary-dark));
}

.magic-section::before {
  background: linear-gradient(90deg, #8b4513, #5d2f02);
}

.pokemon-section::before {
  background: linear-gradient(90deg, #ffc107, #ff6f00);
}

/* Game headers */
.game-header {
  padding: 1.5rem 2rem;
  background: var(--surface-50);
  border-bottom: 1px solid var(--surface-200);
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 2rem;
}

.game-title-area {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

.game-badge {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1.25rem;
  border-radius: 25px;
  font-weight: 600;
  font-size: 1.1rem;
}

.game-badge.hearthstone {
  background: rgba(38, 166, 154, 0.1);
  color: var(--primary);
  border: 2px solid rgba(38, 166, 154, 0.3);
}

.game-badge.magic {
  background: rgba(139, 69, 19, 0.1);
  color: #8b4513;
  border: 2px solid rgba(139, 69, 19, 0.3);
}

.game-badge.pokemon {
  background: rgba(255, 193, 7, 0.1);
  color: #ff6f00;
  border: 2px solid rgba(255, 193, 7, 0.3);
}

.game-icon {
  font-size: 1.5rem;
}

.game-stats {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  color: var(--text-secondary);
  font-size: 0.9rem;
}

.deck-count {
  font-weight: 600;
  color: var(--text-primary);
}

/* Decks grid */
.decks-grid {
  padding: 2rem;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
  gap: 1.5rem;
}

/* États spéciaux */
.loading-state,
.empty-state {
  display: flex;
  justify-content: center;
  margin: 3rem 0;
}

.loading-card,
.empty-card {
  max-width: 600px;
  width: 100%;
}

.loading-content,
.empty-content {
  padding: 3rem 2rem;
  text-align: center;
}

.emerald-spinner {
  width: 40px;
  height: 40px;
  margin: 0 auto 1rem;
  border: 3px solid var(--surface-200);
  border-top: 3px solid var(--primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.empty-icon {
  font-size: 4rem;
  color: var(--text-secondary);
  margin-bottom: 1rem;
  opacity: 0.7;
}

.empty-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0 0 0.5rem 0;
}

.empty-description {
  color: var(--text-secondary);
  margin: 0;
  line-height: 1.5;
}

/* Animations */
.slide-in-up {
  animation: slideInUp 0.6s ease-out;
}

@keyframes slideInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Responsive */
@media (max-width: 1024px) {
  .container {
    padding: 0 1rem;
  }
  
  .decks-grid {
    padding: 1.5rem;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1rem;
  }
}

@media (max-width: 768px) {
  .community-decks-page {
    padding: 1rem 0;
  }
  
  .game-header {
    padding: 1rem 1.5rem;
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .decks-grid {
    grid-template-columns: 1fr;
    padding: 1rem;
  }
}
</style>
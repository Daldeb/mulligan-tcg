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

      <!-- Header avec filtres globaux -->
      <div v-if="!isLoading" class="page-header slide-in-down">
        <div class="header-content">
          <div class="header-left">
            <h1 class="page-title">
              <i class="pi pi-users"></i>
              Decks Communautaires
            </h1>
            <p class="page-subtitle">
              Découvrez les créations de la communauté TCG
            </p>
          </div>
          <div class="header-stats">
            <div class="stat-card">
              <i class="pi pi-clone stat-icon"></i>
              <div class="stat-info">
                <span class="stat-value">{{ totalDecks }}</span>
                <span class="stat-label">Decks</span>
              </div>
            </div>
            <div class="stat-card">
              <i class="pi pi-users stat-icon"></i>
              <div class="stat-info">
                <span class="stat-value">{{ uniqueAuthors }}</span>
                <span class="stat-label">Auteurs</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Filtres et recherche globaux -->
      <div v-if="!isLoading" class="community-filters slide-in-up">
        <Card class="gaming-card filters-card">
          <template #content>
            <div class="filters-content">
              <div class="search-wrapper">
                <InputText 
                  v-model="globalSearch"
                  placeholder="Rechercher dans les decks communautaires..."
                  class="search-input"
                />
                <i class="pi pi-search search-icon"></i>
              </div>
              <div class="filter-buttons">
                <Button 
                  :label="timeFilter === 'all' ? 'Toute période' : timeFilter === 'week' ? 'Cette semaine' : timeFilter === 'month' ? 'Ce mois' : 'Aujourd\'hui'"
                  icon="pi pi-calendar"
                  class="filter-btn"
                  @click="toggleTimeFilter"
                />
                <Button 
                  :label="globalSort === 'smart' ? 'Score intelligent' : globalSort === 'recent' ? 'Récents' : globalSort === 'popular' ? 'Populaires' : 'Alphabétique'"
                  icon="pi pi-sort-alt"
                  class="sort-btn"
                  @click="toggleSort"
                />
              </div>
            </div>
          </template>
        </Card>
      </div>

      <!-- Sections par jeu avec GameDeckSection -->
      <div class="games-sections" v-if="!isLoading && communityDecks.length > 0">
        
        <!-- Section Hearthstone -->
        <GameDeckSection 
          v-if="getGameDecks('hearthstone').length > 0"
          game-slug="hearthstone"
          :decks="getGameDecks('hearthstone')"
          context="community"
          :current-user="authStore.user"
          @like="likeDeck"
          @copy="copyDeckcode"
        />

        <!-- Section Magic -->
        <GameDeckSection 
          v-if="getGameDecks('magic').length > 0"
          game-slug="magic"
          :decks="getGameDecks('magic')"
          context="community"
          :current-user="authStore.user"
          @like="likeDeck"
          @copy="copyDeckcode"
        />

        <!-- Section Pokemon -->
        <GameDeckSection 
          v-if="getGameDecks('pokemon').length > 0"
          game-slug="pokemon"
          :decks="getGameDecks('pokemon')"
          context="community"
          :current-user="authStore.user"
          @like="likeDeck"
          @copy="copyDeckcode"
        />

      </div>

      <!-- État vide -->
      <div v-if="!isLoading && communityDecks.length === 0" class="empty-state">
        <Card class="gaming-card empty-card">
          <template #content>
            <div class="empty-content">
              <i class="pi pi-users empty-icon"></i>
              <h3 class="empty-title">Aucun deck trouvé</h3>
              <p class="empty-description">
                Aucun deck ne correspond à vos critères de recherche.
                <br>
                Essayez de modifier vos filtres ou créez le premier deck !
              </p>
              <div class="empty-actions">
                <Button 
                  label="Réinitialiser les filtres"
                  icon="pi pi-filter-slash"
                  class="emerald-outline-btn"
                  @click="resetAllFilters"
                />
                <RouterLink to="/mes-decks">
                  <Button 
                    label="Créer un deck"
                    icon="pi pi-plus"
                    class="emerald-button primary create-deck"
                  />
                </RouterLink>
              </div>
            </div>
          </template>
        </Card>
      </div>

      <!-- Pagination -->
      <div v-if="!isLoading && communityDecks.length > 0 && totalPages > 1" class="pagination-wrapper">
        <Paginator
          v-model:first="currentPage"
          :rows="pageSize"
          :total-records="filteredTotal"
          :rows-per-page-options="[12, 24, 48]"
          class="emerald-paginator"
          template="PrevPageLink PageLinks NextPageLink RowsPerPageDropdown CurrentPageReport"
          current-page-report-template="Affichage de {first} à {last} sur {totalRecords} decks"
        />
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useToast } from 'primevue/usetoast'
import api from '../services/api'
import Paginator from 'primevue/paginator'
import GameDeckSection from '../components/decks/GameDeckSection.vue'

// Stores et composables
const authStore = useAuthStore()
const toast = useToast()

// State principal
const communityDecks = ref([])
const isLoading = ref(true)
const globalSearch = ref('')
const timeFilter = ref('all') // all, today, week, month
const validFilter = ref('all') // all, valid, invalid
const globalSort = ref('smart') // smart, recent, popular, alphabetical

// Pagination
const currentPage = ref(0)
const pageSize = ref(24)
const filteredTotal = ref(0)

// Computed
const filteredDecks = computed(() => {
  if (!Array.isArray(communityDecks.value)) {
    console.warn('communityDecks.value n\'est pas un tableau:', communityDecks.value)
    return []
  }
  
  let decks = [...communityDecks.value]
  
  // Filtre par recherche globale
  if (globalSearch.value?.trim()) {
    const query = globalSearch.value.toLowerCase()
    decks = decks.filter(deck => 
      deck.title?.toLowerCase().includes(query) ||
      deck.name?.toLowerCase().includes(query) ||
      deck.description?.toLowerCase().includes(query) ||
      deck.author?.toLowerCase().includes(query) ||
      deck.archetype?.toLowerCase().includes(query)
    )
  }
  
  // Filtre par période
  if (timeFilter.value !== 'all') {
    const now = new Date()
    const cutoffTime = new Date()
    
    switch (timeFilter.value) {
      case 'today':
        cutoffTime.setHours(0, 0, 0, 0)
        break
      case 'week':
        cutoffTime.setDate(now.getDate() - 7)
        break
      case 'month':
        cutoffTime.setMonth(now.getMonth() - 1)
        break
    }
    
    decks = decks.filter(deck => {
      const deckDate = new Date(deck.updatedAt || deck.createdAt)
      return deckDate >= cutoffTime
    })
  }
  
  // Filtre par validité
  if (validFilter.value !== 'all') {
    decks = decks.filter(deck => 
      validFilter.value === 'valid' ? deck.validDeck : !deck.validDeck
    )
  }
  
  // Tri global
  return sortDecks(decks, globalSort.value)
})

const totalDecks = computed(() => communityDecks.value.length)

const totalLikes = computed(() => 
  communityDecks.value.reduce((sum, deck) => sum + (deck.likesCount || deck.likes || 0), 0)
)

const uniqueAuthors = computed(() => {
  const authors = new Set()
  communityDecks.value.forEach(deck => {
    if (deck.author || deck.user?.username) {
      authors.add(deck.author || deck.user.username)
    }
  })
  return authors.size
})

const totalPages = computed(() => Math.ceil(filteredTotal.value / pageSize.value))

// Méthodes
const loadCommunityDecks = async () => {
  try {
    isLoading.value = true
    const response = await api.get('/api/decks/community', {
      params: {
        page: Math.floor(currentPage.value / pageSize.value) + 1,
        limit: pageSize.value,
        search: globalSearch.value,
        timeFilter: timeFilter.value,
        validFilter: validFilter.value,
        sort: globalSort.value
      }
    })
    
    console.log('🔍 Réponse API communauté:', response.data)
    
    if (response.data.success) {
      communityDecks.value = response.data.data || []
      filteredTotal.value = response.data.total || 0
      console.log('✅ Decks communautaires chargés:', communityDecks.value.length)
    } else if (Array.isArray(response.data)) {
      communityDecks.value = response.data
      filteredTotal.value = response.data.length
      console.log('✅ Decks communautaires depuis array:', communityDecks.value.length)
    } else {
      console.warn('❌ Format réponse inattendu:', response.data)
      communityDecks.value = []
      filteredTotal.value = 0
    }
    
  } catch (error) {
    console.error('💥 Erreur chargement decks communautaires:', error)
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Impossible de charger les decks communautaires',
      life: 3000
    })
    communityDecks.value = []
    filteredTotal.value = 0
  } finally {
    isLoading.value = false
  }
}

const getGameDecks = (game) => {
  return filteredDecks.value.filter(deck => {
    // Gérer les deux formats : deck.game.slug ou deck.game directement
    const gameSlug = typeof deck.game === 'object' ? deck.game.slug : deck.game
    return gameSlug === game
  })
}

const calculateDeckScore = (deck) => {
  const now = new Date()
  const lastUpdate = new Date(deck.updatedAt || deck.createdAt)
  const daysSinceUpdate = (now - lastUpdate) / (1000 * 60 * 60 * 24)
  
  // Score de fraîcheur (diminue avec le temps)
  const freshnessScore = Math.max(0, 100 - (daysSinceUpdate * 1.5))
  
  // Score de popularité (likes)
  const likesScore = (deck.likesCount || deck.likes || 0) * 8
  
  // Bonus pour les decks récents (moins de 7 jours)
  const recentBonus = daysSinceUpdate < 7 ? 25 : 0
  
  // Bonus pour les decks populaires (5+ likes)
  const popularBonus = (deck.likesCount || deck.likes || 0) >= 5 ? 20 : 0
  
  // Bonus pour les decks valides
  const validBonus = deck.validDeck ? 15 : 0
  
  // Score final pondéré
  return freshnessScore * 0.4 + likesScore * 0.5 + recentBonus + popularBonus + validBonus
}

const sortDecks = (decks, sortBy) => {
  switch (sortBy) {
    case 'recent':
      return [...decks].sort((a, b) => new Date(b.updatedAt || b.createdAt) - new Date(a.updatedAt || a.createdAt))
    case 'popular':
      return [...decks].sort((a, b) => (b.likesCount || b.likes || 0) - (a.likesCount || a.likes || 0))
    case 'alphabetical':
      return [...decks].sort((a, b) => (a.title || a.name || '').localeCompare(b.title || b.name || ''))
    case 'smart':
    default:
      return [...decks].sort((a, b) => calculateDeckScore(b) - calculateDeckScore(a))
  }
}

const likeDeck = async (deck) => {
  if (!authStore.isAuthenticated) {
    toast.add({
      severity: 'warn',
      summary: 'Connexion requise',
      detail: 'Vous devez être connecté pour liker un deck',
      life: 3000
    })
    return
  }

  try {
    const response = await api.post(`/api/decks/${deck.id}/like`)
    
    if (response.data.success) {
      // Mettre à jour le deck localement
      const deckIndex = communityDecks.value.findIndex(d => d.id === deck.id)
      if (deckIndex !== -1) {
        const updatedDeck = { ...communityDecks.value[deckIndex] }
        updatedDeck.likesCount = response.data.likesCount
        updatedDeck.isLikedByUser = response.data.isLiked
        communityDecks.value[deckIndex] = updatedDeck
      }
      
      toast.add({
        severity: 'success',
        summary: response.data.isLiked ? 'Deck liké !' : 'Like retiré',
        detail: response.data.isLiked ? 
          `Vous avez liké "${deck.title || deck.name}"` : 
          `Vous avez retiré votre like de "${deck.title || deck.name}"`,
        life: 2000
      })
    }
  } catch (error) {
    console.error('💥 Erreur like deck:', error)
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Impossible de liker ce deck',
      life: 3000
    })
  }
}

const copyDeckcode = (deck) => {
  // TODO: Implémenter la copie de deckcode selon le jeu
  toast.add({
    severity: 'info',
    summary: 'Deckcode',
    detail: 'Fonctionnalité bientôt disponible...',
    life: 2000
  })
}

const toggleTimeFilter = () => {
  const filters = ['all', 'today', 'week', 'month']
  const currentIndex = filters.indexOf(timeFilter.value)
  timeFilter.value = filters[(currentIndex + 1) % filters.length]
  loadCommunityDecks()
}

const toggleValidFilter = () => {
  const filters = ['all', 'valid', 'invalid']
  const currentIndex = filters.indexOf(validFilter.value)
  validFilter.value = filters[(currentIndex + 1) % filters.length]
  loadCommunityDecks()
}

const toggleSort = () => {
  const sorts = ['smart', 'recent', 'popular', 'alphabetical']
  const currentIndex = sorts.indexOf(globalSort.value)
  globalSort.value = sorts[(currentIndex + 1) % sorts.length]
  loadCommunityDecks()
}

const resetAllFilters = () => {
  globalSearch.value = ''
  timeFilter.value = 'all'
  validFilter.value = 'all'
  globalSort.value = 'smart'
  currentPage.value = 0
  loadCommunityDecks()
}

// Watchers pour reload automatique
import { watch } from 'vue'

watch([globalSearch, currentPage, pageSize], () => {
  loadCommunityDecks()
}, { debounce: 300 })

// Lifecycle
onMounted(async () => {
  await loadCommunityDecks()
})
</script>

<style scoped>
/* === COMMUNITY DECKS PAGE EMERALD GAMING === */

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

/* Page header avec stats */
.page-header {
  margin-bottom: 2rem;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 2rem;
}

.header-left {
  flex: 1;
}

.page-title {
  font-size: 2.5rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0 0 0.5rem 0;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.page-title i {
  color: var(--primary);
}

.page-subtitle {
  font-size: 1.1rem;
  color: var(--text-secondary);
  margin: 0;
}

.header-stats {
  display: flex;
  gap: 1.5rem;
}

.stat-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem 1.5rem;
  background: white;
  border-radius: var(--border-radius-large);
  box-shadow: var(--shadow-small);
  border: 1px solid var(--surface-200);
  transition: all var(--transition-fast);
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-medium);
}

.stat-icon {
  font-size: 2rem;
  color: var(--primary);
  padding: 0.75rem;
  background: rgba(38, 166, 154, 0.1);
  border-radius: 50%;
}

.stat-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--text-primary);
  line-height: 1;
}

.stat-label {
  font-size: 0.85rem;
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-weight: 500;
}

/* Community filters */
.community-filters {
  margin-bottom: 2rem;
}

.filters-card {
  border: none;
  box-shadow: var(--shadow-small);
}

.filters-content {
  padding: 1.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 2rem;
}

.search-wrapper {
  position: relative;
  flex: 1;
  max-width: 500px;
}

:deep(.search-input) {
  width: 100% !important;
  padding: 0.875rem 1rem 0.875rem 3rem !important;
  border: 2px solid var(--surface-300) !important;
  border-radius: 25px !important;
  font-size: 1rem !important;
  background: var(--surface-100) !important;
}

:deep(.search-input:focus) {
  border-color: var(--primary) !important;
  background: white !important;
  box-shadow: 0 0 0 3px rgba(38, 166, 154, 0.1) !important;
}

.search-icon {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-secondary);
  font-size: 1rem;
  pointer-events: none;
}

.filter-buttons {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

:deep(.filter-btn),
:deep(.sort-btn) {
  background: white !important;
  border: 2px solid var(--surface-300) !important;
  color: var(--text-secondary) !important;
  padding: 0.75rem 1.25rem !important;
  border-radius: 20px !important;
  font-size: 0.9rem !important;
  font-weight: 500 !important;
  transition: all var(--transition-fast) !important;
  white-space: nowrap !important;
}

:deep(.filter-btn:hover),
:deep(.sort-btn:hover) {
  border-color: var(--primary) !important;
  color: var(--primary) !important;
  background: rgba(38, 166, 154, 0.1) !important;
  transform: translateY(-1px) !important;
}

/* Game sections */
.games-sections {
  display: flex;
  flex-direction: column;
  gap: 3rem;
}

/* Loading state */
.loading-state {
  display: flex;
  justify-content: center;
  margin: 3rem 0;
}

.loading-card {
  max-width: 600px;
  width: 100%;
}

.loading-content {
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

/* Empty state */
.empty-state {
  display: flex;
  justify-content: center;
  margin: 3rem 0;
}

.empty-card {
  max-width: 700px;
  width: 100%;
}

.empty-content {
  padding: 3rem 2rem;
  text-align: center;
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
  margin: 0 0 2rem 0;
  line-height: 1.5;
}

.empty-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
  flex-wrap: wrap;
}

/* Pagination */
.pagination-wrapper {
  margin-top: 3rem;
  display: flex;
  justify-content: center;
}

:deep(.emerald-paginator) {
  background: white !important;
  border: 1px solid var(--surface-200) !important;
  border-radius: var(--border-radius-large) !important;
  box-shadow: var(--shadow-small) !important;
  padding: 1rem !important;
}

:deep(.emerald-paginator .p-paginator-page.p-highlight) {
  background: var(--primary) !important;
  border-color: var(--primary) !important;
  color: white !important;
}

:deep(.emerald-paginator .p-paginator-page:not(.p-highlight):hover) {
  background: rgba(38, 166, 154, 0.1) !important;
  border-color: var(--primary) !important;
  color: var(--primary) !important;
}

:deep(.emerald-paginator .p-dropdown) {
  border: 2px solid var(--surface-300) !important;
  border-radius: var(--border-radius) !important;
}

:deep(.emerald-paginator .p-dropdown:hover) {
  border-color: var(--primary) !important;
}

/* Responsive */
@media (max-width: 1200px) {
  .header-stats {
    gap: 1rem;
  }
  
  .stat-card {
    padding: 0.75rem 1rem;
  }
  
  .stat-icon {
    font-size: 1.5rem;
    padding: 0.5rem;
  }
  
  .stat-value {
    font-size: 1.25rem;
  }
}

@media (max-width: 1024px) {
  .container {
    padding: 0 1rem;
  }
  
  .header-content {
    flex-direction: column;
    align-items: flex-start;
    gap: 1.5rem;
  }
  
  .header-stats {
    align-self: stretch;
    justify-content: space-between;
  }
  
  .filters-content {
    flex-direction: column;
    gap: 1.5rem;
  }
  
  .search-wrapper {
    max-width: none;
  }
  
  .filter-buttons {
    justify-content: center;
    width: 100%;
  }
}

@media (max-width: 768px) {
  .community-decks-page {
    padding: 1rem 0;
  }
  
  .page-title {
    font-size: 2rem;
  }
  
  .header-stats {
    flex-direction: column;
    gap: 0.75rem;
  }
  
  .stat-card {
    justify-content: center;
    text-align: center;
  }
  
  .filter-buttons {
    flex-direction: column;
    gap: 0.75rem;
  }
  
  :deep(.filter-btn),
  :deep(.sort-btn) {
    width: 100% !important;
    justify-content: center !important;
  }
}

@media (max-width: 640px) {
  .empty-actions {
    flex-direction: column;
    align-items: center;
  }
  
  :deep(.empty-actions .p-button) {
    width: 100% !important;
    max-width: 300px !important;
  }
  
  .header-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
  }
  
  .stat-card:last-child {
    grid-column: 1 / -1;
    max-width: 200px;
    margin: 0 auto;
  }
}

/* Animations */
.slide-in-down {
  animation: slideInDown 0.6s ease-out;
}

.slide-in-up {
  animation: slideInUp 0.6s ease-out;
}

@keyframes slideInDown {
  from {
    opacity: 0;
    transform: translateY(-30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
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

/* Effets de hover subtils */
.stat-card {
  position: relative;
  overflow: hidden;
}

.stat-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(38, 166, 154, 0.1), transparent);
  transition: left 0.6s ease;
}

.stat-card:hover::before {
  left: 100%;
}

/* Loading skeleton pour les futures améliorations */
.skeleton {
  background: linear-gradient(90deg, var(--surface-200) 25%, var(--surface-100) 50%, var(--surface-200) 75%);
  background-size: 200% 100%;
  animation: loading 1.5s infinite;
  border-radius: var(--border-radius);
}

@keyframes loading {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}
</style>
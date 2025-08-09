<template>
  <div class="events-page">
    <div class="container">
      
      <!-- Header avec titre et bouton créer -->
      <div class="page-header">
        <div class="header-content">
          <div class="header-info">
            <h1 class="page-title">
              <i class="pi pi-calendar title-icon"></i>
              Événements TCG
            </h1>
            <p class="page-description">
              Découvrez tous les événements TCG près de chez vous
            </p>
          </div>
          
          <!-- Bouton créer événement (conditionnel) -->
          <div v-if="eventStore.canCreateEvent" class="header-actions">
            <Button 
              label="Créer un événement"
              icon="pi pi-plus"
              iconPos="left"
              class="emerald-button primary create-deck"
              @click="toggleCreateEventMenu"
              aria-haspopup="true"
              aria-controls="create_event_menu"
            />
            <Menu 
              ref="createEventMenu"
              id="create_event_menu"
              :model="createEventMenuItems"
              :popup="true"
            />
          </div>
        </div>
        
        <!-- Stats rapides -->
        <div class="quick-stats">
          <div class="stat-item">
            <span class="stat-value">{{ eventStore.eventsStats.total }}</span>
            <span class="stat-label">Événements</span>
          </div>
          <div class="stat-item">
            <span class="stat-value">{{ eventStore.eventsStats.upcoming }}</span>
            <span class="stat-label">À venir</span>
          </div>
          <div v-if="authStore.isAuthenticated" class="stat-item">
            <span class="stat-value">{{ eventStore.eventsStats.myRegistrationsCount }}</span>
            <span class="stat-label">Mes inscriptions</span>
          </div>
        </div>
      </div>

      <!-- Filtres -->
      <Card class="filters-card">
        <template #content>
          <div class="filters-section">
            <div class="filters-row">
              
              <!-- Recherche -->
              <div class="filter-group search-group">
                <label class="filter-label">Recherche</label>
                <div class="search-wrapper">
                  <InputText 
                    v-model="searchQuery"
                    placeholder="Rechercher un événement..."
                    class="search-input"
                    @input="handleSearchInput"
                  />
                  <i class="pi pi-search search-icon"></i>
                </div>
              </div>

              <!-- Type d'événement -->
              <div class="filter-group">
                <label class="filter-label">Type</label>
                <Dropdown
                  v-model="filters.event_type"
                  :options="eventTypeOptions"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="Tous les types"
                  class="filter-dropdown"
                  @change="handleFilterChange"
                />
              </div>

              <!-- Jeu -->
              <div class="filter-group">
                <label class="filter-label">Jeu</label>
                <Dropdown
                  v-model="filters.game_id"
                  :options="gameOptions"
                  optionLabel="name"
                  optionValue="id"
                  placeholder="Tous les jeux"
                  class="filter-dropdown"
                  @change="handleFilterChange"
                />
              </div>

              <!-- Format (en ligne/physique) -->
              <div class="filter-group">
                <label class="filter-label">Format</label>
                <Dropdown
                  v-model="filters.is_online"
                  :options="formatOptions"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="Tous formats"  
                  class="filter-dropdown"
                  @change="handleFilterChange"
                />
              </div>

            </div>

            <div class="filters-row">
              
              <!-- Date de début -->
              <div class="filter-group">
                <label class="filter-label">À partir du</label>
                <Calendar
                  v-model="filters.start_date"
                  placeholder="Date de début"
                  :showIcon="true"
                  class="filter-calendar"
                  @date-select="handleFilterChange"
                />
              </div>

              <!-- Date de fin -->
              <div class="filter-group">
                <label class="filter-label">Jusqu'au</label>
                <Calendar
                  v-model="filters.end_date"
                  placeholder="Date de fin"
                  :showIcon="true"
                  class="filter-calendar"
                  @date-select="handleFilterChange"
                />
              </div>

              <!-- Tri -->
              <div class="filter-group">
                <label class="filter-label">Trier par</label>
                <Dropdown
                  v-model="filters.order_by"
                  :options="sortOptions"
                  optionLabel="label"
                  optionValue="value"
                  class="filter-dropdown"
                  @change="handleFilterChange"
                />
              </div>

              <!-- Actions filtres -->
              <div class="filter-actions">
                <Button 
                  label="Réinitialiser"
                  icon="pi pi-refresh"
                  class="reset-filters-btn"
                  outlined
                  @click="resetAllFilters"
                />
              </div>
            </div>
          </div>
        </template>
      </Card>

      <!-- Résultats -->
      <div class="results-section">
        
        <!-- Header résultats -->
        <div class="results-header">
          <div class="results-info">
            <span class="results-count">
              {{ eventStore.pagination.total }} résultat{{ eventStore.pagination.total > 1 ? 's' : '' }}
            </span>
            <span v-if="hasActiveFilters" class="active-filters-indicator">
              (filtré{{ hasActiveFilters > 1 ? 's' : '' }})
            </span>
          </div>
        </div>

        <!-- Loading état -->
        <div v-if="eventStore.isLoading" class="loading-section">
          <div class="loading-grid">
            <div v-for="n in 6" :key="n" class="event-card-skeleton loading-skeleton"></div>
          </div>
        </div>

        <!-- Liste des événements -->
        <div v-else-if="eventStore.filteredEvents.length > 0" class="events-grid">
          <EventCard 
            v-for="event in eventStore.filteredEvents"
            :key="event.id"
            :event="event"
            :show-follow-button="true"
            @click="goToEventDetail(event.id)"
            @register="handleRegister"
            @unregister="handleUnregister"
            @follow-changed="handleFollowChanged"
          />
        </div>

        <!-- État vide -->
        <div v-else class="empty-state">
          <Card class="empty-card">
            <template #content>
              <div class="empty-content">
                <i class="pi pi-calendar-times empty-icon"></i>
                <h3 class="empty-title">Aucun événement trouvé</h3>
                <p class="empty-message">
                  <span v-if="hasActiveFilters">
                    Aucun événement ne correspond à vos critères de recherche.
                  </span>
                  <span v-else>
                    Aucun événement n'est disponible pour le moment.
                  </span>
                </p>
                <div class="empty-actions">
                  <Button 
                    v-if="hasActiveFilters"
                    label="Réinitialiser les filtres"
                    icon="pi pi-refresh"
                    class="emerald-outline-btn"
                    @click="resetAllFilters"
                  />
                  <Button 
                    v-if="eventStore.canCreateEvent"
                    label="Créer le premier événement"
                    icon="pi pi-plus"
                    class="emerald-button primary"
                    @click="goToCreateEventType('GENERIQUE')"
                  />
                </div>
              </div>
            </template>
          </Card>
        </div>

        <!-- Pagination -->
        <div v-if="eventStore.pagination.pages > 1" class="pagination-section">
          <Paginator
            v-model:first="paginationFirst"
            :rows="eventStore.pagination.limit"
            :totalRecords="eventStore.pagination.total"
            :rowsPerPageOptions="[10, 20, 50]"
            template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
            @page="handlePageChange"
          />
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { useEventStore } from '@/stores/events'
import { useAuthStore } from '@/stores/auth'
import { useGameFilterStore } from '@/stores/gameFilter'
import EventCard from '@/components/events/EventCard.vue'
import SplitButton from 'primevue/splitbutton'
import Menu from 'primevue/menu'
import Dropdown from 'primevue/dropdown'
import Calendar from 'primevue/calendar'
import Paginator from 'primevue/paginator'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import Card from 'primevue/card'

// ============= SETUP =============
const createEventMenu = ref()

const router = useRouter()
const eventStore = useEventStore()
const authStore = useAuthStore()
const gameFilterStore = useGameFilterStore()

// ============= STATE =============

// Recherche avec debounce
const searchQuery = ref('')
let searchTimeout = null

// Filtres locaux (synchro avec store)
const filters = ref({
  event_type: null,
  game_id: null,
  is_online: null,
  start_date: null,
  end_date: null,
  order_by: 'start_date'
})

// Pagination
const paginationFirst = ref(0)

// ============= OPTIONS =============

const eventTypeOptions = ref([
  { label: 'Tournoi', value: 'TOURNOI', icon: 'pi-trophy' },
  { label: 'Avant-première', value: 'AVANT_PREMIERE', icon: 'pi-star' },
  { label: 'Rencontre', value: 'RENCONTRE', icon: 'pi-users' },
  { label: 'Événement générique', value: 'GENERIQUE', icon: 'pi-calendar' }
])

const formatOptions = ref([
  { label: 'En ligne', value: true },
  { label: 'En présentiel', value: false }
])

const sortOptions = ref([
  { label: 'Date (croissant)', value: 'start_date' },
  { label: 'Date (décroissant)', value: '-start_date' },
  { label: 'Titre', value: 'title' },
  { label: 'Participants', value: 'current_participants' },
  { label: 'Créé récemment', value: 'created_at' }
])

// ============= COMPUTED =============

const gameOptions = computed(() => {
  return [
    { id: null, name: 'Tous les jeux' },
    ...gameFilterStore.availableGames
  ]
})

const hasActiveFilters = computed(() => {
  return Object.values(filters.value).some(value => value !== null && value !== '') ||
         searchQuery.value.trim() !== ''
})

// ============= WATCHERS =============

// Synchroniser les filtres avec le store
watch(filters, (newFilters) => {
  const cleanFilters = {}
  Object.entries(newFilters).forEach(([key, value]) => {
    if (value !== null && value !== '') {
      cleanFilters[key] = value
    }
  })
  eventStore.filters = { ...eventStore.filters, ...cleanFilters }
}, { deep: true })

// ============= METHODS =============

// ============= MENU OPTIONS =============

const createEventMenuItems = ref([
  {
    label: 'Événement générique',
    icon: 'pi pi-calendar',
    command: () => goToCreateEventType('GENERIQUE')
  },
  {
    label: 'Rencontre',
    icon: 'pi pi-users',
    command: () => goToCreateEventType('RENCONTRE')
  },
  {
    label: 'Avant-première',
    icon: 'pi pi-star',
    command: () => goToCreateEventType('AVANT_PREMIERE')
  },
  {
    separator: true
  },
  {
    label: 'Tournoi',
    icon: 'pi pi-trophy',
    command: () => goToCreateTournament()
  }
])

// ============= NAVIGATION METHODS (à ajouter) =============

/**
 * Navigation création - Action par défaut (clic principal)
 */
const toggleCreateEventMenu = (event) => {
  createEventMenu.value.toggle(event)
}

/**
 * Navigation création - Événements classiques
 */
const goToCreateEventType = (eventType) => {
  router.push({ 
    name: 'creer-evenement',
    query: { type: eventType }
  })
}

/**
 * Navigation création - Tournois
 */
const goToCreateTournament = () => {
  router.push({ name: 'creer-tournoi' })
}

const goToEventDetail = (eventId) => {
  router.push({ name: 'evenement-detail', params: { id: eventId } })
}

/**
 * Gestion changement de suivi
 */
const handleFollowChanged = async (data) => {
  console.log('Suivi modifié:', data.event.title, 'Suivi:', data.isFollowing)
  // Optionnel: recharger les événements suivis si on a une section dédiée
}

/**
 * Gestion recherche avec debounce
 */
const handleSearchInput = () => {
  if (searchTimeout) {
    clearTimeout(searchTimeout)
  }
  
  searchTimeout = setTimeout(async () => {
    if (searchQuery.value.trim()) {
      await eventStore.searchEvents(searchQuery.value.trim())
    } else {
      await handleFilterChange()
    }
  }, 500)
}

/**
 * Gestion des filtres
 */
const handleFilterChange = async () => {
  paginationFirst.value = 0 // Reset pagination
  await eventStore.updateFilters({
    ...filters.value,
    page: 1
  })
}

const resetAllFilters = async () => {
  searchQuery.value = ''
  filters.value = {
    event_type: null,
    game_id: null,
    is_online: null,
    start_date: null,
    end_date: null,
    order_by: 'start_date'
  }
  paginationFirst.value = 0
  
  await eventStore.resetFilters()
}

/**
 * Gestion pagination
 */
const handlePageChange = async (event) => {
  const newPage = Math.floor(event.first / event.rows) + 1
  paginationFirst.value = event.first
  
  await eventStore.updateFilters({
    ...filters.value,
    page: newPage,
    limit: event.rows
  })
  
  // Scroll vers le haut
  await nextTick()
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

/**
 * Gestion inscriptions
 */
const handleRegister = async (eventId) => {
  if (!authStore.isAuthenticated) {
    // Afficher modal de connexion ou rediriger
    return
  }
  
  try {
    await eventStore.registerToEvent(eventId)
    // Toast success sera géré par un composable plus tard
  } catch (error) {
    console.error('Erreur inscription:', error)
    // Toast error
  }
}

const handleUnregister = async (eventId) => {
  try {
    await eventStore.unregisterFromEvent(eventId)
    // Toast success
  } catch (error) {
    console.error('Erreur désinscription:', error)
    // Toast error
  }
}

/**
 * Chargement initial
 */
const loadInitialData = async () => {
  try {
    // Charger les jeux si pas encore fait
    if (!gameFilterStore.isReady) {
      await gameFilterStore.loadGames()
    }
    
    // Charger les événements avec filtres par défaut
    await eventStore.loadEvents({
      order_by: 'start_date',
      order_direction: 'ASC',
      page: 1,
      limit: 10
    })
    
    // Charger mes inscriptions si connecté
    if (authStore.isAuthenticated) {
      await eventStore.loadMyRegistrations()
    }
    
  } catch (error) {
    console.error('❌ Erreur chargement initial:', error)
  }
}

// ============= LIFECYCLE =============

onMounted(async () => {
  console.log('📅 EventsView montée')
  await loadInitialData()
})

onUnmounted(() => {
  if (searchTimeout) {
    clearTimeout(searchTimeout)
  }
})
</script>

<style scoped>
/* === EVENTS PAGE === */

.events-page {
  min-height: calc(100vh - var(--header-height));
  background: var(--surface-gradient);
  padding: 2rem 0;
}

.container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 2rem;
}

/* === PAGE HEADER === */

.page-header {
  margin-bottom: 2rem;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1.5rem;
}

.header-info {
  flex: 1;
}

.page-title {
  display: flex;
  align-items: center;
  gap: 1rem;
  font-size: 2.5rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0 0 0.5rem 0;
}

.title-icon {
  font-size: 2rem;
  color: var(--primary);
  background: rgba(38, 166, 154, 0.1);
  width: 60px;
  height: 60px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.page-description {
  font-size: 1.1rem;
  color: var(--text-secondary);
  margin: 0;
  max-width: 500px;
}

.header-actions {
  display: flex;
  gap: 1rem;
}

:deep(.create-event-btn) {
  background: var(--primary) !important;
  border: none !important;
  color: white !important;
  font-weight: 600 !important;
  padding: 0.875rem 1.5rem !important;
  border-radius: var(--border-radius) !important;
  transition: all var(--transition-fast) !important;
}

:deep(.create-event-btn:hover) {
  background: var(--primary-dark) !important;
  transform: translateY(-1px) !important;
  box-shadow: 0 4px 12px rgba(38, 166, 154, 0.3) !important;
}

/* Stats rapides */
.quick-stats {
  display: flex;
  gap: 2rem;
  padding: 1rem 0;
  border-top: 1px solid var(--surface-200);
}

.stat-item {
  text-align: center;
}

.stat-value {
  display: block;
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--primary);
  line-height: 1;
}

.stat-label {
  display: block;
  font-size: 0.875rem;
  color: var(--text-secondary);
  margin-top: 0.25rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* === FILTRES === */

.filters-card {
  margin-bottom: 2rem;
  border-radius: var(--border-radius-large) !important;
  box-shadow: var(--shadow-medium) !important;
}

.filters-section {
  padding: 0.5rem;
}

.filters-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
  margin-bottom: 1rem;
}

.filters-row:last-child {
  margin-bottom: 0;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.filter-group.search-group {
  grid-column: 1 / -1;
  max-width: 400px;
}

.filter-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 0.25rem;
}

.search-wrapper {
  position: relative;
}

:deep(.search-input) {
  width: 100% !important;
  padding: 0.75rem 1rem 0.75rem 2.5rem !important;
  border: 2px solid var(--surface-300) !important;
  border-radius: var(--border-radius) !important;
  background: white !important;
  transition: all var(--transition-fast) !important;
}

:deep(.search-input:focus) {
  border-color: var(--primary) !important;
  box-shadow: 0 0 0 3px rgba(38, 166, 154, 0.1) !important;
}

.search-icon {
  position: absolute;
  left: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-secondary);
  pointer-events: none;
}

:deep(.filter-dropdown) {
  border: 2px solid var(--surface-300) !important;
  border-radius: var(--border-radius) !important;
  transition: all var(--transition-fast) !important;
}

:deep(.filter-dropdown:not(.p-disabled).p-focus) {
  border-color: var(--primary) !important;
  box-shadow: 0 0 0 3px rgba(38, 166, 154, 0.1) !important;
}

:deep(.filter-calendar) {
  border: 2px solid var(--surface-300) !important;
  border-radius: var(--border-radius) !important;
}

:deep(.filter-calendar:not(.p-disabled).p-focus) {
  border-color: var(--primary) !important;
  box-shadow: 0 0 0 3px rgba(38, 166, 154, 0.1) !important;
}

.filter-actions {
  display: flex;
  align-items: flex-end;
  gap: 1rem;
}

:deep(.reset-filters-btn) {
  padding: 0.75rem 1rem !important;
  border: 2px solid var(--surface-400) !important;
  color: var(--text-secondary) !important;
  border-radius: var(--border-radius) !important;
}

:deep(.reset-filters-btn:hover) {
  border-color: var(--primary) !important;
  color: var(--primary) !important;
  background: rgba(38, 166, 154, 0.1) !important;
}

/* === RÉSULTATS === */

.results-section {
  min-height: 400px;
}

.results-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid var(--surface-200);
}

.results-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.results-count {
  font-size: 1rem;
  font-weight: 600;
  color: var(--text-primary);
}

.active-filters-indicator {
  font-size: 0.875rem;
  color: var(--primary);
  font-style: italic;
}

/* === GRILLE ÉVÉNEMENTS === */

.events-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

/* === LOADING === */

.loading-section {
  margin-bottom: 2rem;
}

.loading-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
  gap: 1.5rem;
}

.event-card-skeleton {
  height: 280px;
  border-radius: var(--border-radius-large);
}

/* === ÉTAT VIDE === */

.empty-state {
  display: flex;
  justify-content: center;
  padding: 3rem 0;
}

.empty-card {
  max-width: 500px;
  width: 100%;
  text-align: center;
}

.empty-content {
  padding: 2rem;
}

.empty-icon {
  font-size: 4rem;
  color: var(--surface-400);
  margin-bottom: 1.5rem;
}

.empty-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0 0 1rem 0;
}

.empty-message {
  font-size: 1rem;
  color: var(--text-secondary);
  line-height: 1.6;
  margin: 0 0 2rem 0;
}

.empty-actions {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  align-items: center;
}

/* === PAGINATION === */

.pagination-section {
  display: flex;
  justify-content: center;
  padding: 2rem 0;
}

:deep(.p-paginator) {
  background: white !important;
  border: 1px solid var(--surface-200) !important;
  border-radius: var(--border-radius) !important;
  padding: 1rem !important;
}

:deep(.p-paginator .p-paginator-page.p-highlight) {
  background: var(--primary) !important;
  border-color: var(--primary) !important;
  color: white !important;
}

/* === RESPONSIVE === */

@media (max-width: 1024px) {
  .events-grid {
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1rem;
  }
  
  .loading-grid {
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  }
}

@media (max-width: 768px) {
  .container {
    padding: 0 1rem;
  }
  
  .events-page {
    padding: 1rem 0;
  }
  
  .page-title {
    font-size: 2rem;
  }
  
  .title-icon {
    width: 50px;
    height: 50px;
    font-size: 1.5rem;
  }
  
  .header-content {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
  
  .quick-stats {
    justify-content: space-around;
    gap: 1rem;
  }
  
  .events-grid {
    grid-template-columns: 1fr;
  }
  
  .loading-grid {
    grid-template-columns: 1fr;
  }
  
  .filters-row {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
  
  .filter-group.search-group {
    grid-column: 1;
  }
}

@media (max-width: 640px) {
  .quick-stats {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
  }
  
  .stat-item {
    padding: 0.5rem;
    background: var(--surface-100);
    border-radius: var(--border-radius);
  }
}
</style>
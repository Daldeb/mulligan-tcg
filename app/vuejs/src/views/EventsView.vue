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
              Découvrez tous les événements TCG près de chez vous, Avant-première, Rencontres, Tournois et autres
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
        
        <!-- Stats rapides avec bouton filtres -->
        <div class="stats-and-filters">
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
          
          <!-- Bouton filtres avancés -->
          <div class="advanced-filters">
            <button 
              class="filter-toggle"
              :class="{ active: showAdvancedFilters }"
              @click="toggleAdvancedFilters"
            >
              <i class="pi pi-filter"></i>
              <span>Filtres</span>
              <i class="pi pi-chevron-down" :class="{ 'rotated': showAdvancedFilters }"></i>
              <span v-if="activeFiltersCount > 0" class="filters-badge">{{ activeFiltersCount }}</span>
            </button>
          </div>
        </div>
        
        <!-- Panneau filtres étendu -->
        <div v-if="showAdvancedFilters" class="filters-panel-extended">
          <div class="filters-content">
            
            <!-- Ligne 1: Recherche + Type + Jeu -->
            <div class="filters-line-1">
              <div class="search-field">
                <label>Recherche</label>
                <div class="input-wrapper">
                  <InputText 
                    v-model="searchQuery"
                    placeholder="Rechercher un événement..."
                    @input="handleSearchInput"
                  />
                  <i class="pi pi-search"></i>
                </div>
              </div>

              <div class="type-field">
                <label>Type</label>
                <Dropdown
                  v-model="filters.event_type"
                  :options="eventTypeOptions"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="Tous les types"
                  @change="handleFilterChange"
                />
              </div>

              <div class="game-field">
                <label>Jeu</label>
                <Dropdown
                  v-model="filters.game_id"
                  :options="gameOptions"
                  optionLabel="name"
                  optionValue="id"
                  placeholder="Tous les jeux"
                  @change="handleFilterChange"
                />
              </div>
            </div>

            <!-- Ligne 2: Format + Dates + Tri -->
            <div class="filters-line-2">
              <div class="format-field">
                <label>Format</label>
                <Dropdown
                  v-model="filters.is_online"
                  :options="formatOptions"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="Tous formats"  
                  @change="handleFilterChange"
                />
              </div>

              <div class="start-date-field">
                <label>À partir du</label>
                <Calendar
                  v-model="filters.start_date"
                  placeholder="Date de début"
                  :showIcon="true"
                  @date-select="handleFilterChange"
                />
              </div>

              <div class="end-date-field">
                <label>Jusqu'au</label>
                <Calendar
                  v-model="filters.end_date"
                  placeholder="Date de fin"
                  :showIcon="true"
                  @date-select="handleFilterChange"
                />
              </div>

              <div class="sort-field">
                <label>Trier par</label>
                <Dropdown
                  v-model="filters.order_by"
                  :options="sortOptions"
                  optionLabel="label"
                  optionValue="value"
                  @change="handleFilterChange"
                />
              </div>
            </div>

            <!-- Ligne 3: Actions -->
            <div class="filters-line-3">
              <div class="actions-wrapper">
                <Button 
                  label="Réinitialiser"
                  icon="pi pi-refresh"
                  outlined
                  @click="resetAllFilters"
                />
                <Button 
                  label="Appliquer"
                  icon="pi pi-check"
                  @click="applyFiltersAndClose"
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Carte des événements à proximité -->
      <div class="map-section">
        <EventMap 
          :events="eventsWithCoordinates"
          :filters="filters"
          @event-selected="handleMapEventSelection"
          @location-changed="handleUserLocationChange"
        />
      </div>

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
          
          <!-- Filtres actifs visibles -->
          <div v-if="activeFiltersCount > 0" class="active-filters-display">
            <div class="active-filter-tag" v-for="filter in displayedActiveFilters" :key="filter.key">
              <span class="filter-name">{{ filter.label }}</span>
              <button class="remove-filter" @click="removeFilter(filter.key)">
                <i class="pi pi-times"></i>
              </button>
            </div>
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
import EventMap from '@/components/events/EventMap.vue'
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
const filtersPanel = ref()

const router = useRouter()
const eventStore = useEventStore()
const authStore = useAuthStore()
const gameFilterStore = useGameFilterStore()

// ============= STATE =============

// Recherche avec debounce
const searchQuery = ref('')
let searchTimeout = null

// État panneau filtres
const showAdvancedFilters = ref(false)

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

const activeFiltersCount = computed(() => {
  let count = 0
  if (searchQuery.value.trim()) count++
  Object.values(filters.value).forEach(value => {
    if (value !== null && value !== '' && value !== 'start_date') count++
  })
  return count
})

const displayedActiveFilters = computed(() => {
  const active = []
  
  if (searchQuery.value.trim()) {
    active.push({
      key: 'search',
      label: `"${searchQuery.value}"`
    })
  }
  
  if (filters.value.event_type) {
    const type = eventTypeOptions.value.find(t => t.value === filters.value.event_type)
    active.push({
      key: 'event_type',
      label: type?.label || filters.value.event_type
    })
  }
  
  if (filters.value.game_id) {
    const game = gameOptions.value.find(g => g.id === filters.value.game_id)
    active.push({
      key: 'game_id',
      label: game?.name || 'Jeu sélectionné'
    })
  }
  
  if (filters.value.is_online !== null) {
    active.push({
      key: 'is_online',
      label: filters.value.is_online ? 'En ligne' : 'En présentiel'
    })
  }
  
  if (filters.value.start_date) {
    active.push({
      key: 'start_date',
      label: `Depuis ${new Date(filters.value.start_date).toLocaleDateString()}`
    })
  }
  
  if (filters.value.end_date) {
    active.push({
      key: 'end_date',
      label: `Jusqu'au ${new Date(filters.value.end_date).toLocaleDateString()}`
    })
  }
  
  return active
})

// Événements avec coordonnées pour la carte
const eventsWithCoordinates = computed(() => {
  return eventStore.filteredEvents.filter(event => 
    event.address?.latitude && 
    event.address?.longitude &&
    !event.is_online
  )
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

// ============= FILTRES METHODS =============

/**
 * Toggle panneau filtres
 */
const toggleAdvancedFilters = (event) => {
  event.stopPropagation()
  showAdvancedFilters.value = !showAdvancedFilters.value
  console.log('Toggle filtres:', showAdvancedFilters.value)
}

/**
 * Fermer panneau filtres (pas nécessaire avec le nouveau layout)
 */
const closeFiltersPanel = () => {
  showAdvancedFilters.value = false
}

/**
 * Appliquer filtres et fermer
 */
const applyFiltersAndClose = async () => {
  await handleFilterChange()
  showAdvancedFilters.value = false
}

/**
 * Supprimer un filtre spécifique
 */
const removeFilter = async (filterKey) => {
  if (filterKey === 'search') {
    searchQuery.value = ''
  } else {
    filters.value[filterKey] = null
  }
  await handleFilterChange()
}

// ============= NAVIGATION METHODS =============

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
}

// ============= MÉTHODES CARTE =============

/**
 * Gestion sélection d'événement depuis la carte
 */
const handleMapEventSelection = (eventData) => {
  if (eventData.id) {
    goToEventDetail(eventData.id)
  } else if (eventData) {
    goToEventDetail(eventData.id)
  }
}

/**
 * Gestion changement position utilisateur
 */
const handleUserLocationChange = (location) => {
  console.log('📍 Position utilisateur mise à jour:', location)
}

// ============= GESTION FILTRES ET RECHERCHE =============

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
  paginationFirst.value = 0
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
  showAdvancedFilters.value = false
  
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
  
  await nextTick()
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

/**
 * Gestion inscriptions
 */
const handleRegister = async (eventId) => {
  if (!authStore.isAuthenticated) {
    return
  }
  
  try {
    await eventStore.registerToEvent(eventId)
  } catch (error) {
    console.error('Erreur inscription:', error)
  }
}

const handleUnregister = async (eventId) => {
  try {
    await eventStore.unregisterFromEvent(eventId)
  } catch (error) {
    console.error('Erreur désinscription:', error)
  }
}

/**
 * Chargement initial
 */
const loadInitialData = async () => {
  try {
    if (!gameFilterStore.isReady) {
      await gameFilterStore.loadGames()
    }
    
    await eventStore.loadEvents({
      order_by: 'start_date',
      order_direction: 'ASC',
      page: 1,
      limit: 10
    })
    
    if (authStore.isAuthenticated) {
      await eventStore.loadMyRegistrations()
    }
    
  } catch (error) {
    console.error('❌ Erreur chargement initial:', error)
  }
}

// ============= DIRECTIVE CLICK OUTSIDE =============

const vClickOutside = {
  mounted(el, binding) {
    el.clickOutsideEvent = function(event) {
      if (!(el === event.target || el.contains(event.target))) {
        binding.value(event)
      }
    }
    document.addEventListener('click', el.clickOutsideEvent)
  },
  unmounted(el) {
    document.removeEventListener('click', el.clickOutsideEvent)
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

/* === STATS ET FILTRES === */

.stats-and-filters {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 0;
  border-top: 1px solid var(--surface-200);
}

.quick-stats {
  display: flex;
  gap: 2rem;
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

/* === BOUTON FILTRES === */

.advanced-filters {
  position: relative;
}

.filter-toggle {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.875rem 1.25rem;
  background: white;
  border: 2px solid var(--surface-300);
  border-radius: var(--border-radius);
  color: var(--text-secondary);
  font-weight: 600;
  cursor: pointer;
  transition: all var(--transition-medium);
  white-space: nowrap;
  position: relative;
  font-size: 0.95rem;
}

.filter-toggle:hover {
  background: var(--surface-100);
  color: var(--text-primary);
  border-color: var(--primary);
  transform: translateY(-1px);
}

.filter-toggle.active {
  background: var(--primary);
  border-color: var(--primary);
  color: white;
  box-shadow: 0 4px 12px rgba(38, 166, 154, 0.3);
}

.filter-toggle .pi-chevron-down {
  transition: transform var(--transition-medium);
  font-size: 0.8rem;
}

.filter-toggle .pi-chevron-down.rotated {
  transform: rotate(180deg);
}

.filters-badge {
  position: absolute;
  top: -6px;
  right: -6px;
  background: var(--accent);
  color: white;
  border-radius: 50%;
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.7rem;
  font-weight: 700;
  border: 2px solid white;
}

/* === NOUVEAU SYSTÈME DE FILTRES === */

.filters-panel-extended {
  width: 100%;
  background: white;
  border: 1px solid var(--surface-200);
  border-radius: var(--border-radius);
  box-shadow: var(--shadow-small);
  margin-top: 1rem;
  animation: slideInDown 0.3s ease-out;
}

.filters-content {
  padding: 1.25rem;
}

/* === LIGNE 1 === */
.filters-line-1 {
  display: grid;
  grid-template-columns: 3fr 1fr 1fr;
  gap: 1rem;
  margin-bottom: 1rem;
  align-items: end;
}

.search-field {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.search-field label {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--text-primary);
}

.input-wrapper {
  position: relative;
}

.input-wrapper :deep(.p-inputtext) {
  width: 100%;
  padding-left: 2.5rem;
  height: 40px;
  border: 1px solid var(--surface-300);
  border-radius: var(--border-radius);
}

.input-wrapper i {
  position: absolute;
  left: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-secondary);
  font-size: 0.875rem;
}

.type-field,
.game-field {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.type-field label,
.game-field label {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--text-primary);
}

.type-field :deep(.p-dropdown),
.game-field :deep(.p-dropdown) {
  height: 40px;
  border: 1px solid var(--surface-300);
  border-radius: var(--border-radius);
}

/* === LIGNE 2 === */
.filters-line-2 {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr 1fr;
  gap: 1rem;
  margin-bottom: 1rem;
  align-items: end;
  padding-top: 1rem;
  border-top: 1px solid var(--surface-200);
}

.format-field,
.start-date-field,
.end-date-field,
.sort-field {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.format-field label,
.start-date-field label,
.end-date-field label,
.sort-field label {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--text-primary);
}

.format-field :deep(.p-dropdown),
.sort-field :deep(.p-dropdown) {
  height: 40px;
  border: 1px solid var(--surface-300);
  border-radius: var(--border-radius);
}

.start-date-field :deep(.p-calendar),
.end-date-field :deep(.p-calendar) {
  height: 40px;
}

.start-date-field :deep(.p-inputtext),
.end-date-field :deep(.p-inputtext) {
  height: 40px;
  border: 1px solid var(--surface-300);
  border-radius: var(--border-radius);
}

/* === LIGNE 3 === */
.filters-line-3 {
  display: flex;
  justify-content: flex-end;
  padding-top: 1rem;
  border-top: 1px solid var(--surface-200);
}

.actions-wrapper {
  display: flex;
  gap: 0.75rem;
}

.actions-wrapper :deep(.p-button) {
  height: 40px;
  padding: 0 1.25rem;
  border-radius: var(--border-radius);
  font-size: 0.875rem;
  font-weight: 500;
}

.actions-wrapper :deep(.p-button[outlined]) {
  background: transparent;
  border: 1px solid var(--surface-400);
  color: var(--text-secondary);
}

.actions-wrapper :deep(.p-button[outlined]:hover) {
  border-color: var(--primary);
  color: var(--primary);
  background: rgba(38, 166, 154, 0.1);
}

.actions-wrapper :deep(.p-button:not([outlined])) {
  background: var(--primary);
  border: 1px solid var(--primary);
  color: white;
}

.actions-wrapper :deep(.p-button:not([outlined]):hover) {
  background: var(--primary-dark);
  border-color: var(--primary-dark);
}

/* === SECTION CARTE === */
.map-section {
  margin-bottom: 2rem;
}

/* === RÉSULTATS === */

.results-section {
  min-height: 400px;
}

.results-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid var(--surface-200);
  flex-wrap: wrap;
  gap: 1rem;
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

/* === FILTRES ACTIFS VISIBLES === */

.active-filters-display {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  max-width: 60%;
}

.active-filter-tag {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.375rem 0.75rem;
  background: rgba(38, 166, 154, 0.1);
  border: 1px solid rgba(38, 166, 154, 0.3);
  border-radius: 20px;
  color: var(--primary);
  font-size: 0.8rem;
  font-weight: 500;
}

.filter-name {
  max-width: 120px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.remove-filter {
  background: none;
  border: none;
  color: var(--primary);
  cursor: pointer;
  padding: 0;
  margin: 0;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all var(--transition-fast);
}

.remove-filter:hover {
  background: var(--primary);
  color: white;
}

.remove-filter .pi {
  font-size: 0.7rem;
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
  
  .filters-line-1 {
    grid-template-columns: 2fr 1fr 1fr;
  }
  
  .filters-line-2 {
    grid-template-columns: 1fr 1fr;
    grid-template-rows: auto auto;
  }
  
  .format-field {
    grid-column: 1;
  }
  
  .start-date-field {
    grid-column: 2;
  }
  
  .end-date-field {
    grid-column: 1;
    grid-row: 2;
  }
  
  .sort-field {
    grid-column: 2;
    grid-row: 2;
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
  
  .stats-and-filters {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
  
  .quick-stats {
    justify-content: space-around;
    gap: 1rem;
  }
  
  .advanced-filters {
    align-self: flex-end;
  }
  
  .filters-panel-extended {
    padding: 1rem;
  }
  
  .filters-content {
    padding: 1rem;
  }
  
  .filters-line-1 {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
  
  .filters-line-2 {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
  
  .events-grid {
    grid-template-columns: 1fr;
  }
  
  .loading-grid {
    grid-template-columns: 1fr;
  }
  
  .results-header {
    flex-direction: column;
    align-items: stretch;
  }
  
  .active-filters-display {
    max-width: 100%;
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
  
  .filter-toggle {
    padding: 0.75rem 1rem;
    font-size: 0.9rem;
  }
  
  .active-filter-tag {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
  }
  
  .filter-name {
    max-width: 80px;
  }
}

/* === ANIMATIONS === */

@keyframes slideInDown {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.active-filter-tag {
  animation: filterTagAppear 0.3s ease-out;
}

@keyframes filterTagAppear {
  from {
    opacity: 0;
    transform: scale(0.8) translateY(-10px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

.filters-badge {
  animation: badgeBounce 0.4s ease-out;
}

@keyframes badgeBounce {
  0% { transform: scale(0); }
  60% { transform: scale(1.2); }
  100% { transform: scale(1); }
}

/* === ÉTATS DE CHARGEMENT === */

.filter-toggle.loading {
  pointer-events: none;
  opacity: 0.7;
}

.filter-toggle.loading::after {
  content: '';
  position: absolute;
  right: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  width: 16px;
  height: 16px;
  border: 2px solid transparent;
  border-top: 2px solid var(--primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: translateY(-50%) rotate(0deg); }
  100% { transform: translateY(-50%) rotate(360deg); }
}

/* === TRANSITIONS DOUCES === */

.stats-and-filters {
  transition: all var(--transition-medium);
}

.active-filters-display {
  transition: all var(--transition-medium);
}

/* === DARK MODE SUPPORT (préparation future) === */

@media (prefers-color-scheme: dark) {
  .filter-toggle {
    background: #2a2a2a;
    border-color: #4a4a4a;
    color: #e0e0e0;
  }
  
  .filter-toggle:hover {
    background: #3a3a3a;
    border-color: var(--primary);
  }
  
  .filters-panel-extended {
    background: #2a2a2a;
    border-color: #4a4a4a;
  }
  
  .input-wrapper :deep(.p-inputtext),
  .type-field :deep(.p-dropdown),
  .game-field :deep(.p-dropdown),
  .format-field :deep(.p-dropdown),
  .sort-field :deep(.p-dropdown),
  .start-date-field :deep(.p-inputtext),
  .end-date-field :deep(.p-inputtext) {
    background: #2a2a2a;
    border-color: #4a4a4a;
    color: #e0e0e0;
  }
  
  .active-filter-tag {
    background: rgba(38, 166, 154, 0.2);
    border-color: rgba(38, 166, 154, 0.4);
  }
}

/* === PRINT STYLES === */

@media print {
  .filter-toggle,
  .filters-panel-extended,
  .active-filters-display {
    display: none !important;
  }
  
  .stats-and-filters {
    border: none !important;
    padding: 0 !important;
  }
}
</style>
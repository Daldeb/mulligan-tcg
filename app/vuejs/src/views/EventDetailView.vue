<template>
  <div class="event-detail-page">
    <div class="container">
      
      <!-- Breadcrumb -->
      <div class="page-header">
        <div class="breadcrumb">
          <router-link :to="getBreadcrumbRoute()" class="breadcrumb-link">
            <i class="pi pi-calendar"></i>
            {{ getBreadcrumbLabel() }}
          </router-link>
          <i class="pi pi-chevron-right breadcrumb-separator"></i>
          <span class="breadcrumb-current">
            {{ event?.title || 'Détail événement' }}
          </span>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="isLoading" class="loading-section">
        <Card class="loading-card">
          <template #content>
            <div class="loading-content">
              <i class="pi pi-spin pi-spinner loading-icon"></i>
              <h3>Chargement de l'événement...</h3>
            </div>
          </template>
        </Card>
      </div>

      <!-- Error -->
      <div v-else-if="error" class="error-section">
        <Card class="error-card">
          <template #content>
            <div class="error-content">
              <i class="pi pi-exclamation-triangle error-icon"></i>
              <h3>Erreur</h3>
              <p>{{ error }}</p>
              <Button 
                label="Retour aux événements"
                icon="pi pi-arrow-left"
                class="emerald-outline-btn"
                @click="goBack"
              />
            </div>
          </template>
        </Card>
      </div>

      <!-- Event Detail -->
      <div v-else-if="event" class="event-detail-content">
        
        <!-- Header Section -->
        <Card class="event-header-card">
          <template #content>
            <div class="event-header">
              
              <!-- Image + Info principale -->
              <div class="event-main-info">
                <div v-if="event.image" class="event-image">
                  <img :src="getImageUrl(event.image)" :alt="event.title" />
                </div>
                
                <div class="event-info">
                  <div class="event-title-section">
                    <h1 class="event-title">{{ event.title }}</h1>
                    <div class="event-badges">
                      <span class="event-type-badge" :class="getTypeBadgeClass(event.event_type)">
                        {{ getTypeLabel(event.event_type) }}
                      </span>
                      <span v-if="event.status" class="event-status-badge" :class="getStatusBadgeClass(event.status)">
                        {{ getStatusLabel(event.status) }}
                      </span>
                      <span v-if="event.is_online" class="online-badge">
                        <i class="pi pi-globe"></i>
                        En ligne
                      </span>
                    </div>
                  </div>
                  
                  <!-- Dates et participants -->
                  <div class="event-meta">
                    <div class="meta-item">
                      <i class="pi pi-calendar"></i>
                      <div>
                        <strong>{{ formatDate(event.start_date) }}</strong>
                        <span v-if="event.end_date"> - {{ formatDate(event.end_date) }}</span>
                      </div>
                    </div>
                    
                    <div class="meta-item">
                      <i class="pi pi-users"></i>
                      <div>
                        <strong>{{ event.current_participants || 0 }} / {{ event.max_participants || '∞' }} </strong>
                        <span> participants</span>
                      </div>
                    </div>
                    
                    <div v-if="event.registration_deadline" class="meta-item">
                      <i class="pi pi-clock"></i>
                      <div>
                        <strong>Limite d'inscription : </strong>
                        <span>{{ formatDate(event.registration_deadline) }}</span>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Actions principales -->
                  <div class="event-actions">
                    <Button
                      v-if="canRegister"
                      label="S'inscrire"
                      icon="pi pi-user-plus"
                      class="register-btn"
                      :disabled="!event.can_register || event.is_full"
                      :loading="isRegistering"
                      @click="handleRegister"
                    />
                    <Button
                      v-if="isRegistered"
                      label="Se désinscrire"
                      icon="pi pi-user-minus"
                      class="unregister-btn"
                      :loading="isUnregistering"
                      @click="handleUnregister"
                    />
                    <Button
                      v-if="canEdit"
                      label="Modifier"
                      icon="pi pi-pencil"
                      class="emerald-outline-btn"
                      @click="editEvent"
                    />
                    <Button
                      v-if="canDelete"
                      label="Supprimer"
                      icon="pi pi-trash"
                      severity="danger"
                      outlined
                      @click="deleteEvent"
                    />
                  </div>
                </div>
              </div>
            </div>
          </template>
        </Card>

        <!-- Description -->
        <Card v-if="event.description" class="event-description-card">
          <template #title>
            <i class="pi pi-info-circle"></i>
            Description
          </template>
          <template #content>
            <div class="event-description" v-html="formatDescription(event.description)"></div>
          </template>
        </Card>

        <!-- Détails -->
        <div class="event-details-grid">
          
          <!-- Localisation -->
          <Card class="detail-card">
            <template #title>
              <i class="pi pi-map-marker"></i>
              Localisation
            </template>
            <template #content>
              <div v-if="event.is_online" class="location-online">
                <i class="pi pi-globe"></i>
                <span>Événement en ligne</span>
                <a v-if="event.stream_url" :href="event.stream_url" target="_blank" class="stream-link">
                  <i class="pi pi-external-link"></i>
                  Rejoindre
                </a>
              </div>
              <div v-else-if="event.address" class="location-physical">
                <i class="pi pi-map-marker"></i>
                <div class="address">
                  <div class="address-line">{{ event.address.street_address }}</div>
                  <div class="address-line">{{ event.address.postal_code }} {{ event.address.city }}</div>
                  <div class="address-line">{{ event.address.country }}</div>
                </div>
              </div>
              <div v-else class="location-unknown">
                <i class="pi pi-question-circle"></i>
                <span>Localisation non précisée</span>
              </div>
            </template>
          </Card>

          <!-- Jeux -->
          <Card v-if="event.games && event.games.length" class="detail-card">
            <template #title>
              <i class="pi pi-clone"></i>
              Jeu(x) concerné(s)
            </template>
            <template #content>
              <div class="games-list">
                <div v-for="game in event.games" :key="game.id" class="game-item">
                  <img v-if="game.logo" :src="game.logo" :alt="game.name" class="game-logo" />
                  <span class="game-name">{{ game.name }}</span>
                </div>
              </div>
            </template>
          </Card>

          <!-- Organisateur -->
          <Card class="detail-card">
            <template #title>
              <i class="pi pi-user"></i>
              Organisateur
            </template>
            <template #content>
              <div class="organizer-info">
                <i :class="event.organizer_type === 'SHOP' ? 'pi pi-shop' : 'pi pi-user'"></i>
                <span>{{ event.organizer_name || 'Organisateur' }}</span>
              </div>
            </template>
          </Card>

        </div>

        <!-- Règles -->
        <Card v-if="event.rules" class="event-rules-card">
          <template #title>
            <i class="pi pi-book"></i>
            Règles spécifiques
          </template>
          <template #content>
            <div class="event-rules" v-html="formatDescription(event.rules)"></div>
          </template>
        </Card>

        <!-- Prix/Récompenses -->
        <Card v-if="event.prizes" class="event-prizes-card">
          <template #title>
            <i class="pi pi-trophy"></i>
            Prix et récompenses
          </template>
          <template #content>
            <div class="event-prizes" v-html="formatDescription(event.prizes)"></div>
          </template>
        </Card>

      </div>
    </div>

    <!-- Confirmation dialogs -->
    <ConfirmDialog></ConfirmDialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useEventStore } from '@/stores/events'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'

// Components
import Card from 'primevue/card'
import Button from 'primevue/button'
import ConfirmDialog from 'primevue/confirmdialog'

// Props
const props = defineProps({
  eventId: {
    type: Number,
    required: true
  },
  fromRoute: {
    type: String,
    default: 'evenements'
  }
})

// Stores & Router
const eventStore = useEventStore()
const authStore = useAuthStore()
const router = useRouter()
const route = useRoute()
const toast = useToast()
const confirm = useConfirm()

// State
const isLoading = ref(true)
const error = ref(null)
const isRegistering = ref(false)
const isUnregistering = ref(false)

// Computed
const event = computed(() => eventStore.currentEvent)

const canEdit = computed(() => {
  if (!authStore.isAuthenticated || !event.value) return false
  
  const userRoles = authStore.user?.roles || []
  return userRoles.includes('ROLE_ADMIN') ||
         event.value.created_by_id === authStore.user?.id ||
         (event.value.organizer_type === 'SHOP' && 
          authStore.user?.shop?.id === event.value.organizer_id)
})

const canDelete = computed(() => canEdit.value)

const canRegister = computed(() => {
  if (!authStore.isAuthenticated || !event.value) return false
  
  if (event.value.created_by_id === authStore.user?.id) return false
  
  if (canEdit.value) return false
  
  return !isRegistered.value && 
         event.value?.can_register &&
         event.value?.status === 'APPROVED'
})

const isRegistered = computed(() => {
  // TODO: Implémenter la logique de vérification d'inscription
  return false
})

// Methods
const loadEvent = async () => {
  if (!props.eventId) {
    error.value = 'ID d\'événement manquant'
    isLoading.value = false
    return
  }

  isLoading.value = true
  error.value = null

  try {
    await eventStore.loadEventDetail(props.eventId)
    
    if (!eventStore.currentEvent) {
      error.value = 'Événement non trouvé'
    }
  } catch (err) {
    console.error('❌ Erreur chargement événement:', err)
    error.value = err.message || 'Erreur lors du chargement de l\'événement'
  } finally {
    isLoading.value = false
  }
}

const handleRegister = async () => {
  if (!canRegister.value) return

  isRegistering.value = true
  try {
    await eventStore.registerToEvent(props.eventId)
    toast.add({
      severity: 'success',
      summary: 'Inscription réussie',
      detail: 'Vous êtes maintenant inscrit à cet événement',
      life: 3000
    })
  } catch (err) {
    console.error('❌ Erreur inscription:', err)
    toast.add({
      severity: 'error',
      summary: 'Erreur d\'inscription',
      detail: err.message || 'Une erreur est survenue',
      life: 5000
    })
  } finally {
    isRegistering.value = false
  }
}

const handleUnregister = async () => {
  isUnregistering.value = true
  try {
    await eventStore.unregisterFromEvent(props.eventId)
    toast.add({
      severity: 'success',
      summary: 'Désinscription réussie',
      detail: 'Vous n\'êtes plus inscrit à cet événement',
      life: 3000
    })
  } catch (err) {
    console.error('❌ Erreur désinscription:', err)
    toast.add({
      severity: 'error',
      summary: 'Erreur de désinscription',
      detail: err.message || 'Une erreur est survenue',
      life: 5000
    })
  } finally {
    isUnregistering.value = false
  }
}

const editEvent = () => {
  router.push({ name: 'creer-evenement', query: { id: props.eventId } })
}

const deleteEvent = () => {
  confirm.require({
    message: `Voulez-vous vraiment supprimer "${event.value?.title}" ?`,
    header: 'Supprimer l\'événement',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Oui, supprimer',
    rejectLabel: 'Annuler',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await eventStore.deleteEvent(props.eventId)
        toast.add({
          severity: 'success',
          summary: 'Événement supprimé',
          detail: 'L\'événement a été supprimé avec succès',
          life: 3000
        })
        goBack()
      } catch (err) {
        console.error('❌ Erreur suppression:', err)
        toast.add({
          severity: 'error',
          summary: 'Erreur de suppression',
          detail: err.message || 'Une erreur est survenue',
          life: 5000
        })
      }
    }
  })
}

const goBack = () => {
  const fromRoute = route.meta?.from || props.fromRoute
  router.push({ name: fromRoute })
}

const getBreadcrumbRoute = () => {
  const fromRoute = route.meta?.from || props.fromRoute
  return `/${fromRoute}`
}

const getBreadcrumbLabel = () => {
  const fromRoute = route.meta?.from || props.fromRoute
  return fromRoute === 'mes-evenements' ? 'Mes événements' : 'Événements'
}

// Utils
const getImageUrl = (imagePath) => {
  if (!imagePath) return null
  if (imagePath.startsWith('events/')) {
    return `http://localhost:8000/uploads/${imagePath}`
  }
  if (imagePath.startsWith('http')) return imagePath
  return `http://localhost:8000/uploads/${imagePath}`
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleString('fr-FR', {
    dateStyle: 'full',
    timeStyle: 'short'
  })
}

const formatDescription = (text) => {
  if (!text) return ''
  return text.replace(/\n/g, '<br>')
}

const getTypeLabel = (type) => {
  const labels = {
    'GENERIQUE': 'Événement',
    'RENCONTRE': 'Rencontre',
    'AVANT_PREMIERE': 'Avant-première',
    'TOURNOI': 'Tournoi'
  }
  return labels[type] || type
}

const getStatusLabel = (status) => {
  const labels = {
    'DRAFT': 'Brouillon',
    'PENDING_REVIEW': 'En attente',
    'APPROVED': 'Validé',
    'REJECTED': 'Refusé',
    'CANCELLED': 'Annulé',
    'IN_PROGRESS': 'En cours',
    'FINISHED': 'Terminé'
  }
  return labels[status] || status
}

const getTypeBadgeClass = (type) => {
  return `badge-${type?.toLowerCase() || 'generique'}`
}

const getStatusBadgeClass = (status) => {
  const classes = {
    'DRAFT': 'badge-draft',
    'PENDING_REVIEW': 'badge-pending',
    'APPROVED': 'badge-approved',
    'REJECTED': 'badge-rejected',
    'CANCELLED': 'badge-cancelled',
    'IN_PROGRESS': 'badge-progress',
    'FINISHED': 'badge-finished'
  }
  return classes[status] || 'badge-default'
}

// Lifecycle
onMounted(() => {
  loadEvent()
})

watch(() => props.eventId, (newId) => {
  if (newId) {
    eventStore.clearCurrentEvent()
    loadEvent()
  }
})

// Cleanup on unmount
onUnmounted(() => {
  eventStore.clearCurrentEvent()
})
</script>

<style scoped>
.event-detail-page {
  min-height: calc(100vh - var(--header-height));
  background: var(--surface-gradient);
  padding: 2rem 0;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 2rem;
}

/* === BREADCRUMB === */
.page-header {
  margin-bottom: 2rem;
}

.breadcrumb {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
}

.breadcrumb-link {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--primary);
  text-decoration: none;
  transition: color var(--transition-fast);
}

.breadcrumb-link:hover {
  color: var(--primary-dark);
}

.breadcrumb-separator {
  color: var(--text-secondary);
  font-size: 0.75rem;
}

.breadcrumb-current {
  color: var(--text-secondary);
}

/* === LOADING & ERROR === */
.loading-section,
.error-section {
  display: flex;
  justify-content: center;
  padding: 3rem 0;
}

.loading-card,
.error-card {
  max-width: 400px;
  text-align: center;
}

.loading-content,
.error-content {
  padding: 2rem;
}

.loading-icon,
.error-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.loading-icon {
  color: var(--primary);
}

.error-icon {
  color: #ef4444;
}

/* === EVENT DETAIL === */
.event-detail-content {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.event-header-card {
  border-radius: var(--border-radius-large) !important;
  box-shadow: var(--shadow-large) !important;
}

.event-header {
  padding: 1rem;
}

.event-main-info {
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 2rem;
  align-items: start;
}

.event-image {
  width: 300px;
  height: 200px;
  border-radius: var(--border-radius);
  overflow: hidden;
  background: var(--surface-200);
}

.event-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.event-info {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.event-title-section {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.event-title {
  font-size: 2rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0;
}

.event-badges {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.event-type-badge,
.event-status-badge,
.online-badge {
  padding: 0.5rem 1rem;
  border-radius: var(--border-radius);
  font-size: 0.875rem;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.badge-generique { background: rgba(99, 102, 241, 0.1); color: #6366f1; }
.badge-rencontre { background: rgba(34, 197, 94, 0.1); color: #22c55e; }
.badge-avant_premiere { background: rgba(251, 146, 60, 0.1); color: #fb923c; }
.badge-tournoi { background: rgba(239, 68, 68, 0.1); color: #ef4444; }

.badge-approved { background: rgba(34, 197, 94, 0.1); color: #22c55e; }
.badge-pending { background: rgba(251, 146, 60, 0.1); color: #fb923c; }
.badge-draft { background: rgba(156, 163, 175, 0.1); color: #9ca3af; }
.badge-rejected { background: rgba(239, 68, 68, 0.1); color: #ef4444; }

.online-badge {
  background: rgba(59, 130, 246, 0.1);
  color: #3b82f6;
}

.event-meta {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  font-size: 0.875rem;
}

.meta-item i {
  color: var(--primary);
  font-size: 1.125rem;
  width: 20px;
}

.event-actions {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

:deep(.register-btn) {
  background: var(--primary) !important;
  border: none !important;
  color: white !important;
}

:deep(.unregister-btn) {
  background: #ef4444 !important;
  border: none !important;
  color: white !important;
}

/* === DETAIL CARDS === */
.event-details-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 2rem;
}

.detail-card {
  border-radius: var(--border-radius-large) !important;
  box-shadow: var(--shadow-medium) !important;
}

:deep(.detail-card .p-card-title) {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  color: var(--text-primary);
}

.location-online,
.location-physical,
.location-unknown,
.organizer-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.address {
  display: flex;
  flex-direction: column;
}

.address-line {
  line-height: 1.4;
}

.stream-link {
  color: var(--primary);
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.games-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.game-item {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.game-logo {
  width: 40px;
  height: 40px;
  object-fit: contain;
}

/* === RESPONSIVE === */
@media (max-width: 768px) {
  .container {
    padding: 0 1rem;
  }

  .event-main-info {
    grid-template-columns: 1fr;
    text-align: center;
  }

  .event-image {
    justify-self: center;
  }

  .event-title {
    font-size: 1.5rem;
  }

  .event-details-grid {
    grid-template-columns: 1fr;
  }

  .event-actions {
    justify-content: center;
  }
}
</style>
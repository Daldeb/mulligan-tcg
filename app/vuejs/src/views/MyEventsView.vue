<template>
  <div class="my-events-page">
    <div class="container">
      
      <!-- Header principal avec stats -->
      <div class="page-header">
        <div class="header-content">
          <div class="header-info">
            <h1 class="page-title">
              <i class="pi pi-user title-icon"></i>
              Mes événements
            </h1>
            <p class="page-description">
              Gérez vos événements créés, participations et favoris
            </p>
          </div>
          
          <!-- Bouton créer événement -->
          <div v-if="canCreateEvent" class="header-actions">
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
        
        <!-- Stats rapides globales -->
        <div class="quick-stats">
          <div class="stat-item">
            <span class="stat-value">{{ myEvents.length }}</span>
            <span class="stat-label">Mes créations</span>
          </div>
          <div class="stat-item">
            <span class="stat-value">{{ participatingEvents.length }}</span>
            <span class="stat-label">Participations</span>
          </div>
          <div class="stat-item">
            <span class="stat-value">{{ followedEvents.length }}</span>
            <span class="stat-label">Suivis</span>
          </div>
          <div v-if="isAdmin" class="stat-item admin-stat">
            <span class="stat-value">{{ pendingEvents.length }}</span>
            <span class="stat-label">En attente admin</span>
            <div v-if="adminNotificationCount > 0" class="stat-notification-dot"></div>
          </div>
        </div>
      </div>

      <!-- Section Administration (si admin) -->
      <Card v-if="isAdmin" class="admin-section-card" :class="{ 'has-notifications': adminNotificationCount > 0 }">
        <template #header>
          <div class="admin-header" @click="toggleAdminSection">
            <div class="admin-title">
              <i class="pi pi-shield admin-icon"></i>
              <span>Administration des événements</span>
              <div v-if="adminNotificationCount > 0" class="admin-notification-badge">
                {{ adminNotificationCount }}
              </div>
            </div>
            <i class="pi toggle-icon" :class="adminSectionOpen ? 'pi-chevron-up' : 'pi-chevron-down'"></i>
          </div>
        </template>
        
        <template #content>
          <div v-show="adminSectionOpen" class="admin-content">
            
            <!-- Loading admin -->
            <div v-if="adminLoading" class="admin-loading">
              <div class="loading-grid">
                <div v-for="n in 3" :key="n" class="admin-card-skeleton loading-skeleton"></div>
              </div>
            </div>

            <!-- Événements en attente -->
            <div v-else-if="pendingEvents.length > 0" class="pending-events">
              <div class="section-header">
                <h3 class="section-title">
                  <i class="pi pi-clock"></i>
                  Événements en attente de validation
                </h3>
                <div class="section-count">{{ pendingEvents.length }}</div>
              </div>
              
              <div class="admin-events-grid">
                <div
                  v-for="event in pendingEvents"
                  :key="`admin-${event.id}`"
                  class="admin-event-card gaming-card"
                >
                  <div class="admin-event-header">
                    <div class="event-title-section">
                      <h4 class="event-title">{{ event.title }}</h4>
                      <div class="event-type-badge" :class="`type-${event.event_type.toLowerCase()}`">
                        {{ getEventTypeLabel(event.event_type) }}
                      </div>
                    </div>
                    <div class="event-status-badge pending">
                      <i class="pi pi-clock"></i>
                      <span>En attente</span>
                    </div>
                  </div>
                  
                  <div class="admin-event-info">
                    <div class="info-item">
                      <i class="pi pi-user"></i>
                      <span>{{ event.created_by?.pseudo || 'Utilisateur' }}</span>
                    </div>
                    <div class="info-item">
                      <i class="pi pi-calendar"></i>
                      <span>{{ formatDate(event.start_date) }}</span>
                    </div>
                    <div class="info-item">
                      <i class="pi pi-users"></i>
                      <span>{{ event.current_participants }}/{{ event.max_participants || '∞' }}</span>
                    </div>
                  </div>
                  
                  <div class="admin-event-actions">
                    <Button
                      icon="pi pi-eye"
                      label="Voir"
                      size="small"
                      outlined
                      class="action-btn view-btn"
                      @click="viewEventDetail(event)"
                    />
                    <Button
                      icon="pi pi-check"
                      label="Approuver"
                      size="small"
                      severity="success"
                      class="action-btn approve-btn"
                      @click="approveEvent(event)"
                    />
                    <Button
                      icon="pi pi-times"
                      label="Refuser"
                      size="small"
                      severity="danger"
                      class="action-btn reject-btn"
                      @click="rejectEvent(event)"
                    />
                    <Button
                      icon="pi pi-ban"
                      label="Annuler"
                      size="small"
                      severity="warning"
                      outlined
                      class="action-btn cancel-btn"
                      @click="cancelEvent(event)"
                    />
                  </div>
                </div>
              </div>
            </div>

            <!-- Aucun événement en attente -->
            <div v-else class="no-pending-events">
              <div class="empty-state-mini">
                <i class="pi pi-check-circle empty-icon"></i>
                <p class="empty-message">Aucun événement en attente de validation</p>
              </div>
            </div>
          </div>
        </template>
      </Card>

      <!-- Section Mes événements avec tabs -->
      <Card class="events-section-card">
        <template #header>
          <div class="section-header-with-tabs">
            <div class="section-title-area">
              <h2 class="section-title">
                <i class="pi pi-calendar"></i>
                Mes événements
              </h2>
            </div>
            
            <!-- Tabs navigation AMÉLIORÉS - FORMAT HORIZONTAL -->
            <div class="section-tabs enhanced-tabs">
              <button 
                class="tab-button enhanced"
                :class="{ active: activeTab === 'created' }"
                @click="activeTab = 'created'"
                data-tab="created"
              >
                <div class="tab-content-left">
                  <i class="pi pi-plus-circle"></i>
                  <span class="tab-text">Créés</span>
                </div>
                <div class="tab-count">{{ myEvents.length }}</div>
              </button>
              
              <button 
                class="tab-button enhanced"
                :class="{ active: activeTab === 'participating' }"
                @click="activeTab = 'participating'"
                data-tab="participating"
              >
                <div class="tab-content-left">
                  <i class="pi pi-ticket"></i>
                  <span class="tab-text">Participé</span>
                </div>
                <div class="tab-count">{{ participatingEvents.length }}</div>
              </button>
              
              <button 
                class="tab-button enhanced"
                :class="{ active: activeTab === 'followed' }"
                @click="activeTab = 'followed'"
                data-tab="followed"
              >
                <div class="tab-content-left">
                  <i class="pi pi-heart"></i>
                  <span class="tab-text">Suivis</span>
                </div>
                <div class="tab-count">{{ followedEvents.length }}</div>
              </button>
            </div>
          </div>
        </template>
        
        <template #content>
          <!-- Tab Content: Événements créés -->
          <div v-if="activeTab === 'created'" class="tab-content">
            <!-- Loading -->
            <div v-if="loading" class="loading-section">
              <div class="loading-grid">
                <div v-for="n in 4" :key="n" class="event-card-skeleton loading-skeleton"></div>
              </div>
            </div>

            <!-- Événements créés -->
            <div v-else-if="myEvents.length > 0" class="events-grid">
              <EventCard
                v-for="event in myEvents"
                :key="event.id"
                :event="event"
                :show-actions="true"
                :show-status="true"
                class="my-event-card"
                @edit="onEditEvent"
                @delete="confirmDelete(event)"
                @click="viewEventDetail(event)"
              />
            </div>

            <!-- État vide créés -->
            <div v-else class="empty-state">
              <div class="empty-content">
                <i class="pi pi-calendar-plus empty-icon"></i>
                <h3 class="empty-title">Aucun événement créé</h3>
                <p class="empty-message">
                  Vous n'avez pas encore créé d'événement. Commencez dès maintenant !
                </p>
                <p>Si vous ne voyez pas le bouton pour créer un événement vous devez aller sur votre profil et demander à obtenir le rôle boutique ou organisateur</p>
                <div class="empty-actions">
                  <Button 
                    v-if="canCreateEvent"
                    label="Créer mon premier événement"
                    icon="pi pi-plus"
                    class="emerald-button primary create-deck"
                    @click="onCreateEvent"
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- Tab Content: Mes participations -->
          <div v-if="activeTab === 'participating'" class="tab-content">
            <!-- Loading -->
            <div v-if="loading" class="loading-section">
              <div class="loading-grid">
                <div v-for="n in 4" :key="n" class="event-card-skeleton loading-skeleton"></div>
              </div>
            </div>

            <!-- Mes participations -->
            <div v-else-if="participatingEvents.length > 0" class="events-grid">
              <EventCard
                v-for="event in participatingEvents"
                :key="`participating-${event.id}`"
                :event="event"
                :show-follow-button="false"
                class="participation-card"
                @registration-changed="handleRegistrationChange"
                @click="viewEventDetail(event)"
              />
            </div>

            <!-- État vide participations -->
            <div v-else class="empty-state">
              <div class="empty-content">
                <i class="pi pi-ticket empty-icon"></i>
                <h3 class="empty-title">Aucune participation</h3>
                <p class="empty-message">
                  Vous ne participez encore à aucun événement. Découvrez les événements disponibles et inscrivez-vous !
                </p>
                <div class="empty-actions">
                  <Button 
                    label="Découvrir les événements"
                    icon="pi pi-search"
                    class="emerald-button primary"
                    @click="goToEvents"
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- Tab Content: Événements suivis -->
          <div v-if="activeTab === 'followed'" class="tab-content">
            <!-- Événements suivis -->
            <div v-if="followedEvents.length > 0" class="events-grid">
              <EventCard
                v-for="event in followedEvents"
                :key="`followed-${event.id}`"
                :event="event"
                :show-follow-button="true"
                class="followed-card"
                @follow-changed="loadFollowedEvents"
                @registration-changed="handleRegistrationChange"
                @click="viewEventDetail(event)"
              />
            </div>

            <!-- État vide suivis -->
            <div v-else class="empty-state">
              <div class="empty-content">
                <i class="pi pi-heart-fill empty-icon"></i>
                <h3 class="empty-title">Aucun événement suivi</h3>
                <p class="empty-message">
                  Vous ne suivez encore aucun événement. Découvrez les événements disponibles et ajoutez-les à vos favoris !
                </p>
                <div class="empty-actions">
                  <Button 
                    label="Découvrir les événements"
                    icon="pi pi-search"
                    class="emerald-button primary"
                    @click="goToEvents"
                  />
                </div>
              </div>
            </div>
          </div>
        </template>
      </Card>

      <!-- État vide global (modifié) -->
      <div v-if="!loading && !hasAnyEvents && activeTab !== 'participating'" class="global-empty-state">
        <Card class="empty-card">
          <template #content>
            <div class="empty-content">
              <i class="pi pi-calendar-times empty-icon"></i>
              <h3 class="empty-title">Aucune activité</h3>
              <p class="empty-message">
                Vous n'avez encore aucun événement. Créez votre premier événement ou participez à ceux existants !
              </p>
              <div class="empty-actions">
                <Button 
                  label="Découvrir les événements"
                  icon="pi pi-search"
                  class="emerald-outline-btn"
                  @click="goToEvents"
                />
                <Button 
                  v-if="canCreateEvent"
                  label="Créer un événement"
                  icon="pi pi-plus"
                  class="emerald-button primary"
                  @click="onCreateEvent"
                />
              </div>
            </div>
          </template>
        </Card>
      </div>

    </div>

    <!-- Dialogs Admin (inchangés) -->
    <Dialog
      v-model:visible="adminActionDialog"
      modal
      :header="adminActionTitle"
      :style="{ width: '500px' }"
    >
      <div class="admin-action-content">
        <p class="mb-4">{{ adminActionMessage }}</p>
        
        <div v-if="adminAction === 'reject' || adminAction === 'delete' || adminAction === 'cancel'" class="form-group">
          <label for="adminReason" class="form-label">
            Motif (obligatoire{{ adminAction === 'cancel' ? ' - min. 30 caractères' : '' }}) :
          </label>
          <Textarea
            id="adminReason"
            v-model="adminActionReason"
            :placeholder="adminAction === 'cancel' 
              ? 'Expliquez pourquoi cet événement doit être annulé...' 
              : 'Expliquez la raison du refus/suppression...'"
            rows="4"
            class="w-full"
            :class="{ 'p-invalid': !adminActionReason.trim() || (adminAction === 'cancel' && adminActionReason.trim().length < 30) }"
          />
          <small v-if="!adminActionReason.trim()" class="text-red-500">
            Un motif est requis pour cette action
          </small>
          <small v-else-if="adminAction === 'cancel' && adminActionReason.trim().length < 30" class="text-red-500">
            Le motif d'annulation doit faire au moins 30 caractères ({{ adminActionReason.trim().length }}/30)
          </small>
        </div>

        <div v-else-if="adminAction === 'approve'" class="form-group">
          <label for="adminComment" class="form-label">Commentaire (optionnel) :</label>
          <Textarea
            id="adminComment"
            v-model="adminActionReason"
            placeholder="Commentaire sur l'approbation..."
            rows="2"
            class="w-full"
          />
        </div>
      </div>

      <template #footer>
        <Button
          label="Annuler"
          icon="pi pi-times"
          outlined
          @click="adminActionDialog = false"
        />
        <Button
          :label="adminActionConfirmLabel"
          :icon="adminActionIcon"
          :severity="adminActionSeverity"
          :disabled="(adminAction === 'reject' || adminAction === 'delete') && !adminActionReason.trim() || 
                    (adminAction === 'cancel' && adminActionReason.trim().length < 30)"
          @click="confirmAdminAction"
        />
      </template>
    </Dialog>

    <!-- Boîte de confirmation -->
    <ConfirmDialog></ConfirmDialog>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useEventStore } from '@/stores/events'
import { useAuthStore } from '@/stores/auth'
import { useNotifications } from '@/composables/useNotifications'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import { useRouter } from 'vue-router'
import api from '@/services/api'

import EventCard from '@/components/events/EventCard.vue'
import Menu from 'primevue/menu'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Dialog from 'primevue/dialog'
import Textarea from 'primevue/textarea'
import ConfirmDialog from 'primevue/confirmdialog'

const eventStore = useEventStore()
const authStore = useAuthStore()
const { addNotification } = useNotifications()
const toast = useToast()
const confirm = useConfirm()
const router = useRouter()

// State (identique à l'original + nouveau tab state)
const loading = ref(true)
const createEventMenu = ref()
const activeTab = ref('created') // Nouvel état pour gérer les tabs

// Admin section
const adminSectionOpen = ref(false)
const adminLoading = ref(false)
const pendingEvents = ref([])
const followedEvents = ref([])
const participatingEvents = ref([])

// Admin actions (identique)
const adminActionDialog = ref(false)
const adminAction = ref('')
const adminActionEvent = ref(null)
const adminActionReason = ref('')

// Computed (identique + nouveaux)
const canCreateEvent = computed(() => authStore.canCreateEvent)
const isAdmin = computed(() => authStore.user?.roles?.includes('ROLE_ADMIN'))
const myEvents = computed(() => eventStore.myEvents)
const adminNotificationCount = computed(() => pendingEvents.value.length)

const hasAnyEvents = computed(() => 
  myEvents.value.length > 0 || 
  participatingEvents.value.length > 0
)

const getEventTypeLabel = (type) => {
  const labels = {
    'TOURNOI': 'Tournoi',
    'AVANT_PREMIERE': 'Avant-première',
    'RENCONTRE': 'Rencontre',
    'GENERIQUE': 'Générique'
  }
  return labels[type] || type
}

// Menu création (identique à EventsView)
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

// Computed pour les dialogs admin (identiques)
const adminActionTitle = computed(() => {
  switch (adminAction.value) {
    case 'approve': return 'Approuver l\'événement'
    case 'reject': return 'Refuser l\'événement'
    case 'delete': return 'Supprimer l\'événement'
    case 'cancel': return 'Annuler l\'événement'
    default: return 'Action admin'
  }
})

const adminActionMessage = computed(() => {
  if (!adminActionEvent.value) return ''
  const title = adminActionEvent.value.title
  
  switch (adminAction.value) {
    case 'approve': 
      return `Approuver "${title}" ? L'événement deviendra visible publiquement et les inscriptions s'ouvriront.`
    case 'reject': 
      return `Refuser "${title}" ? Le créateur pourra le modifier et le re-soumettre.`
    case 'delete': 
      return `Supprimer définitivement "${title}" ? Cette action est irréversible.`
    case 'cancel': 
      return `Annuler "${title}" ? L'événement sera annulé et tous les participants seront notifiés.`
    default: return ''
  }
})

const adminActionConfirmLabel = computed(() => {
  switch (adminAction.value) {
    case 'approve': return 'Approuver'
    case 'reject': return 'Refuser'
    case 'delete': return 'Supprimer définitivement'
    case 'cancel': return 'Annuler l\'événement'
    default: return 'Confirmer'
  }
})

const adminActionIcon = computed(() => {
  switch (adminAction.value) {
    case 'approve': return 'pi pi-check'
    case 'reject': return 'pi pi-times'
    case 'delete': return 'pi pi-trash'
    case 'cancel': return 'pi pi-ban' 
    default: return 'pi pi-check'
  }
})

const adminActionSeverity = computed(() => {
  switch (adminAction.value) {
    case 'approve': return 'success'
    case 'reject': return 'warning'
    case 'delete': return 'danger'
    case 'cancel': return 'warning'
    default: return undefined
  }
})

// Methods (identiques à l'original + nouvelles pour navigation)
const toggleCreateEventMenu = (event) => {
  createEventMenu.value.toggle(event)
}

const goToCreateEventType = (eventType) => {
  router.push({ 
    name: 'creer-evenement',
    query: { type: eventType }
  })
}

const goToCreateTournament = () => {
  router.push({ name: 'creer-tournoi' })
}

const goToEvents = () => {
  router.push({ name: 'evenements' })
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleString('fr-FR', {
    dateStyle: 'short',
    timeStyle: 'short'
  })
}

// NOUVELLE MÉTHODE pour gérer les changements d'inscription
const handleRegistrationChange = async (data) => {
  console.log('🔄 Registration changed:', data)
  
  // Recharger les participations pour mettre à jour les données
  await loadParticipatingEvents()
  
  // Si l'utilisateur s'est désinscrit d'un événement suivi, 
  // recharger aussi les événements suivis
  if (!data.isRegistered) {
    await loadFollowedEvents()
  }
}

const loadMyEvents = async () => {
  loading.value = true
  try {
    if (authStore.isAuthenticated) {
      console.log('🔄 Chargement des événements utilisateur...')
      await eventStore.loadMyEvents()
      console.log('✅ Événements chargés:', eventStore.myEvents.length)
    }
  } catch (error) {
    console.error('❌ Erreur chargement mes événements:', error)
    toast.add({ 
      severity: 'error', 
      summary: 'Erreur', 
      detail: 'Impossible de charger vos événements', 
      life: 3000 
    })
  } finally {
    loading.value = false
  }
}

const loadPendingEvents = async () => {
  if (!isAdmin.value) return
  
  adminLoading.value = true
  try {
    const response = await eventStore.loadPendingEvents({ limit: 20 })
    pendingEvents.value = response.events || []
    console.log('📋 Événements en attente chargés:', pendingEvents.value.length)
  } catch (error) {
    console.error('❌ Erreur chargement événements admin:', error)
  } finally {
    adminLoading.value = false
  }
}

const loadFollowedEvents = async () => {
  try {
    const response = await api.get('/api/events/user/followed') 
    followedEvents.value = response.data.events || []
    console.log('💖 Événements suivis chargés:', followedEvents.value.length)
  } catch (error) {
    console.error('❌ Erreur chargement événements suivis:', error)
  }
}

const loadParticipatingEvents = async () => {
  try {
    console.log('🔄 Chargement des participations...')
    
    const response = await api.get('/api/my-registrations')
    console.log('📋 Réponse complète API inscriptions:', response.data)
    
    if (response.data.registrations && response.data.registrations.length > 0) {
      const activeRegistrations = response.data.registrations.filter(registration => 
        ['REGISTERED', 'CONFIRMED'].includes(registration.status)
      )
      
      console.log('🔍 Inscriptions actives:', activeRegistrations)
      
      participatingEvents.value = activeRegistrations.map(registration => {
        console.log('🔍 Event dans registration:', {
          id: registration.event?.id,
          title: registration.event?.title,
          image: registration.event?.image,
          created_by: registration.event?.created_by,
          games: registration.event?.games,
          participants: registration.event?.participants
        })
        
        return {
          ...registration.event,
          created_by: registration.event.created_by || {},
          participants: registration.event.participants || [],
          games: registration.event.games || [],
          my_registration: {
            id: registration.id,
            status: registration.status,
            registered_at: registration.registered_at,
            checked_in: registration.checked_in
          }
        }
      })
      
      console.log('🎫 Événements finaux:', participatingEvents.value)
    } else {
      participatingEvents.value = []
    }
    
  } catch (error) {
    console.error('❌ Erreur chargement participations:', error)
    participatingEvents.value = []
  }
}

const toggleAdminSection = async () => {
  adminSectionOpen.value = !adminSectionOpen.value
  
  if (adminSectionOpen.value && !pendingEvents.value.length) {
    await loadPendingEvents()
  }
}

// Admin actions (identiques)
const viewEventDetail = (event) => {
  router.push({ name: 'evenement-detail', params: { id: event.id } })
}

const approveEvent = (event) => {
  adminActionEvent.value = event
  adminAction.value = 'approve'
  adminActionReason.value = ''
  adminActionDialog.value = true
}

const rejectEvent = (event) => {
  adminActionEvent.value = event
  adminAction.value = 'reject'
  adminActionReason.value = ''
  adminActionDialog.value = true
}

const cancelEvent = (event) => {
  adminActionEvent.value = event
  adminAction.value = 'cancel'
  adminActionReason.value = ''
  adminActionDialog.value = true
}

const confirmAdminAction = async () => {
  if (!adminActionEvent.value) return
  
  const eventId = adminActionEvent.value.id
  const reason = adminActionReason.value.trim()
  
  if ((adminAction.value === 'reject' || adminAction.value === 'delete' || adminAction.value === 'cancel') && !reason) {
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: error.message || 'Erreur lors de l\'action',
      life: 4000
    })
  }
}

function confirmDelete(event) {
  confirm.require({
    message: `Voulez-vous vraiment supprimer "${event.title}" ?`,
    header: 'Supprimer événement',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Oui, supprimer',
    rejectLabel: 'Annuler',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await eventStore.deleteEvent(event.id)
        toast.add({ 
          severity: 'success', 
          summary: 'Supprimé', 
          detail: 'Événement supprimé avec succès', 
          life: 2000 
        })
      } catch (err) {
        console.error('❌ Erreur suppression:', err)
        toast.add({ 
          severity: 'error', 
          summary: 'Erreur', 
          detail: err.message || 'Erreur lors de la suppression', 
          life: 3000 
        })
      }
    }
  })
}

const onCreateEvent = () => {
  router.push({ name: 'creer-evenement' })
}

const onEditEvent = (event) => {
  console.log('📝 Édition événement:', event.id, event.title)
  router.push({ name: 'creer-evenement', query: { id: event.id } })
}

// Lifecycle
onMounted(async () => {
  console.log('🚀 MyEventsView montée - User authentifié:', authStore.isAuthenticated)
  if (authStore.isAuthenticated) {
    await Promise.all([
      loadMyEvents(),
      loadFollowedEvents(),
      loadParticipatingEvents()
    ])
    
    if (isAdmin.value) {
      await loadPendingEvents()
    }
  } else {
    loading.value = false
  }
})

watch(
  () => authStore.isAuthenticated,
  async (isAuth) => {
    console.log('🔄 Auth changed:', isAuth)
    if (isAuth) {
      await loadMyEvents()
      if (isAdmin.value) {
        await loadPendingEvents()
      }
    } else {
      eventStore.myEvents = []
      pendingEvents.value = []
      loading.value = false
    }
  },
  { immediate: false }
)
</script>

<style scoped>
/* === MY EVENTS PAGE === */

.my-events-page {
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

/* Stats rapides */
.quick-stats {
  display: flex;
  gap: 2rem;
  padding: 1rem 0;
  border-top: 1px solid var(--surface-200);
}

.stat-item {
  text-align: center;
  position: relative;
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

.stat-item.admin-stat .stat-value {
  color: #f59e0b;
}

.stat-notification-dot {
  position: absolute;
  top: -4px;
  right: -4px;
  width: 12px;
  height: 12px;
  background: #ef4444;
  border-radius: 50%;
  animation: pulse 2s infinite;
}

/* === SECTION CARDS === */

.admin-section-card,
.events-section-card {
  margin-bottom: 2rem;
  border-radius: var(--border-radius-large) !important;
  box-shadow: var(--shadow-medium) !important;
  border: 1px solid var(--surface-200) !important;
  overflow: hidden !important;
}

.admin-section-card {
  border-left: 4px solid var(--primary) !important;
  background: linear-gradient(135deg, rgba(38, 166, 154, 0.02), rgba(38, 166, 154, 0.01)) !important;
}

.admin-section-card.has-notifications {
  border-left-color: #f59e0b !important;
  background: linear-gradient(135deg, rgba(245, 158, 11, 0.05), rgba(245, 158, 11, 0.02)) !important;
  animation: adminPulse 3s ease-in-out infinite;
}

@keyframes adminPulse {
  0%, 100% { box-shadow: var(--shadow-medium); }
  50% { box-shadow: 0 8px 32px rgba(245, 158, 11, 0.2); }
}

.participating-section {
  border-left: 4px solid #3b82f6 !important;
}

.followed-section {
  border-left: 4px solid #ef4444 !important;
}

/* === ENHANCED TABS STYLES - FORMAT HORIZONTAL === */

/* Conteneur des tabs amélioré */
.section-tabs.enhanced-tabs {
  display: flex;
  background: var(--surface-100);
  border-radius: 16px;
  padding: 0.5rem;
  gap: 0.5rem;
  box-shadow: inset 0 2px 8px rgba(0, 0, 0, 0.1);
  border: 1px solid var(--surface-200);
  min-width: 500px;
}

/* Boutons de tabs améliorés - FORMAT HORIZONTAL */
.tab-button.enhanced {
  flex: 1;
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 1rem 1.5rem;
  background: white;
  border: 2px solid var(--surface-200);
  border-radius: 12px;
  color: var(--text-secondary);
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
  min-height: 70px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.tab-button.enhanced:hover:not(.active) {
  background: var(--surface-50);
  border-color: var(--primary);
  color: var(--text-primary);
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(38, 166, 154, 0.15);
}

.tab-button.enhanced.active {
  background: var(--emerald-gradient);
  border-color: var(--primary);
  color: white;
  transform: translateY(-4px) scale(1.02);
  box-shadow: 0 8px 24px rgba(38, 166, 154, 0.3);
}

.tab-button.enhanced.active::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
  animation: tabShimmer 2s infinite;
}

@keyframes tabShimmer {
  0% { left: -100%; }
  100% { left: 100%; }
}

/* Section gauche : Icône + Texte */
.tab-button.enhanced .tab-content-left {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex: 1;
}

/* Icônes des tabs */
.tab-button.enhanced i {
  font-size: 1.25rem;
  transition: all var(--transition-fast);
}

.tab-button.enhanced.active i {
  transform: scale(1.1);
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
}

/* Texte des tabs */
.tab-text {
  font-size: 0.875rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  line-height: 1;
  white-space: nowrap;
}

/* Compteurs améliorés - À DROITE */
.tab-button.enhanced .tab-count {
  background: var(--surface-300);
  color: var(--text-secondary);
  border-radius: 20px;
  padding: 0.375rem 0.75rem;
  font-size: 0.75rem;
  font-weight: 700;
  min-width: 2rem;
  text-align: center;
  border: 2px solid transparent;
  transition: all var(--transition-fast);
  flex-shrink: 0;
}

.tab-button.enhanced.active .tab-count {
  background: rgba(255, 255, 255, 0.9);
  color: var(--primary);
  border-color: rgba(255, 255, 255, 0.3);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.tab-button.enhanced:hover:not(.active) .tab-count {
  background: rgba(38, 166, 154, 0.1);
  color: var(--primary);
  border-color: rgba(38, 166, 154, 0.2);
}

/* Variantes par type de tab */
.tab-button.enhanced[data-tab="created"]:hover {
  border-color: #22c55e;
}

.tab-button.enhanced[data-tab="created"].active {
  background: linear-gradient(135deg, #22c55e, #16a34a);
}

.tab-button.enhanced[data-tab="participating"]:hover {
  border-color: #3b82f6;
}

.tab-button.enhanced[data-tab="participating"].active {
  background: linear-gradient(135deg, #3b82f6, #2563eb);
}

.tab-button.enhanced[data-tab="followed"]:hover {
  border-color: #ef4444;
}

.tab-button.enhanced[data-tab="followed"].active {
  background: linear-gradient(135deg, #ef4444, #dc2626);
}

/* Animation d'entrée pour les tabs */
.enhanced-tabs {
  animation: slideInDown 0.6s ease-out;
}

.tab-button.enhanced {
  animation: fadeInScale 0.4s ease-out;
}

.tab-button.enhanced:nth-child(1) { animation-delay: 0s; }
.tab-button.enhanced:nth-child(2) { animation-delay: 0.1s; }
.tab-button.enhanced:nth-child(3) { animation-delay: 0.2s; }

/* === SECTION HEADERS AVEC TABS === */

.section-header-with-tabs {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.5rem;
  background: var(--surface-100);
  border-bottom: 1px solid var(--surface-200);
  flex-wrap: wrap;
  gap: 1rem;
}

.section-title-area {
  flex: 0 0 auto;
  min-width: 200px;
}

.section-header-with-tabs .section-tabs {
  flex: 0 0 auto;
}

/* Tab content */
.tab-content {
  min-height: 200px;
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.5rem;
  background: var(--surface-100);
  border-bottom: 1px solid var(--surface-200);
}

.section-title {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0;
}

.section-count {
  background: var(--primary);
  color: white;
  border-radius: 20px;
  padding: 0.375rem 0.75rem;
  font-size: 0.875rem;
  font-weight: 600;
  min-width: 2rem;
  text-align: center;
}

/* === ADMIN SECTION === */

.admin-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  cursor: pointer;
  padding: 1.5rem;
  transition: all var(--transition-fast);
  background: var(--surface-100);
}

.admin-header:hover {
  background: rgba(38, 166, 154, 0.05);
}

.admin-title {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-weight: 600;
  color: var(--text-primary);
  font-size: 1.25rem;
}

.admin-icon {
  color: var(--primary);
  font-size: 1.25rem;
}

.admin-notification-badge {
  background: #ef4444;
  color: white;
  border-radius: 50%;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 700;
  animation: bounceIn 0.6s ease;
}

.toggle-icon {
  color: var(--text-secondary);
  transition: transform var(--transition-fast);
}

.admin-content {
  padding: 1.5rem;
}

/* === ADMIN EVENTS GRID === */

.admin-events-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(450px, 1fr));
  gap: 1.5rem;
}

.admin-event-card {
  background: white;
  border: 1px solid var(--surface-200);
  border-radius: var(--border-radius-large);
  padding: 1.5rem;
  transition: all var(--transition-medium);
  position: relative;
  overflow: hidden;
}

.admin-event-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #f59e0b, #d97706);
}

.admin-event-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-large);
}

.admin-event-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 1rem;
}

.event-title-section {
  flex: 1;
}

.event-title {
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0 0 0.5rem 0;
  line-height: 1.3;
}

.event-type-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.25rem 0.5rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.event-type-badge.type-tournoi {
  background: rgba(245, 158, 11, 0.1);
  color: #d97706;
}

.event-type-badge.type-avant_premiere {
  background: rgba(139, 69, 19, 0.1);
  color: #8b4513;
}

.event-type-badge.type-rencontre {
  background: rgba(59, 130, 246, 0.1);
  color: #2563eb;
}

.event-type-badge.type-generique {
  background: rgba(107, 114, 128, 0.1);
  color: #4b5563;
}

.event-status-badge {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.375rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  white-space: nowrap;
}

.event-status-badge.pending {
  background: rgba(245, 158, 11, 0.1);
  color: #d97706;
  border: 1px solid rgba(245, 158, 11, 0.3);
}

.admin-event-info {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  margin-bottom: 1rem;
}

.info-item {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  font-size: 0.875rem;
  color: var(--text-secondary);
}

.info-item i {
  color: var(--primary);
}

.admin-event-actions {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.action-btn {
  border-radius: var(--border-radius) !important;
  font-weight: 500 !important;
  transition: all var(--transition-fast) !important;
}

.action-btn:hover {
  transform: translateY(-1px) !important;
}

/* === EVENTS GRID SIMPLIFIÉ === */

.events-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
  gap: 1.5rem;
  padding: 1.5rem;
}

.my-event-card,
.participation-card,
.followed-card {
  transition: all var(--transition-medium) !important;
}

.my-event-card:hover,
.participation-card:hover,
.followed-card:hover {
  transform: translateY(-6px) scale(1.02) !important;
}

/* === AMÉLIORATION DES CARDS DE PARTICIPATION === */

.participation-card {
  border-left: 4px solid #3b82f6 !important;
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.02), rgba(59, 130, 246, 0.01)) !important;
}

.participation-card:hover {
  box-shadow: 0 8px 32px rgba(59, 130, 246, 0.15) !important;
}

/* Badge spécial pour les participations */
.participation-card .event-status-badge {
  background: rgba(59, 130, 246, 0.1) !important;
  color: #2563eb !important;
  border-color: rgba(59, 130, 246, 0.3) !important;
}

/* Animation d'apparition des cards */
.events-grid .participation-card {
  animation: slideInUp 0.6s ease-out;
}

.events-grid .participation-card:nth-child(1) { animation-delay: 0s; }
.events-grid .participation-card:nth-child(2) { animation-delay: 0.1s; }
.events-grid .participation-card:nth-child(3) { animation-delay: 0.2s; }
.events-grid .participation-card:nth-child(4) { animation-delay: 0.3s; }

/* === LOADING STATES === */

.loading-section,
.admin-loading {
  padding: 1.5rem;
}

.loading-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
  gap: 1.5rem;
}

.event-card-skeleton,
.admin-card-skeleton {
  height: 280px;
  border-radius: var(--border-radius-large);
}

.admin-card-skeleton {
  height: 200px;
}

/* === EMPTY STATES === */

.empty-state,
.empty-state-mini,
.global-empty-state {
  display: flex;
  justify-content: center;
  padding: 3rem 1.5rem;
}

.empty-state-mini {
  padding: 2rem;
  text-align: center;
}

.empty-card {
  max-width: 600px;
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

.empty-state-mini .empty-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.empty-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0 0 1rem 0;
}

.empty-state-mini .empty-title {
  font-size: 1.25rem;
  margin: 0 0 0.5rem 0;
}

.empty-message {
  font-size: 1rem;
  color: var(--text-secondary);
  line-height: 1.6;
  margin: 0 0 2rem 0;
}

.empty-state-mini .empty-message {
  font-size: 0.875rem;
  margin: 0;
}

.empty-actions {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  align-items: center;
}

/* === ADMIN DIALOGS === */

.admin-action-content {
  padding: 0.5rem 0;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-label {
  display: block;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 0.5rem;
}

/* === RESPONSIVE POUR FORMAT HORIZONTAL === */

@media (max-width: 1024px) {
  .events-grid {
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1rem;
  }
  
  .admin-events-grid {
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
  }
  
  .loading-grid {
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  }
  
  .section-tabs.enhanced-tabs {
    min-width: 450px;
  }
  
  .tab-button.enhanced {
    padding: 0.875rem 1.25rem;
    min-height: 65px;
    gap: 0.5rem;
  }
  
  .tab-button.enhanced i {
    font-size: 1.125rem;
  }
  
  .tab-text {
    font-size: 0.8rem;
  }
}

@media (max-width: 768px) {
  .container {
    padding: 0 1rem;
  }
  
  .my-events-page {
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
    flex-wrap: wrap;
  }
  
  .stat-item {
    min-width: 80px;
  }
  
  .events-grid {
    grid-template-columns: 1fr;
    padding: 1rem;
  }
  
  .admin-events-grid {
    grid-template-columns: 1fr;
  }
  
  .loading-grid {
    grid-template-columns: 1fr;
  }
  
  .section-header-with-tabs {
    flex-direction: column;
    align-items: stretch;
    gap: 1.5rem;
  }
  
  .section-title-area {
    min-width: auto;
  }
  
  .section-tabs.enhanced-tabs {
    width: 100%;
    min-width: auto;
    padding: 0.375rem;
    gap: 0.375rem;
    flex-direction: column;
  }
  
  .tab-button.enhanced {
    min-height: 60px;
    padding: 0.75rem 1rem;
    justify-content: space-between;
  }
  
  .tab-button.enhanced i {
    font-size: 1.125rem;
  }
  
  .tab-text {
    font-size: 0.75rem;
  }
  
  .tab-count {
    font-size: 0.7rem !important;
    padding: 0.25rem 0.5rem !important;
  }
  
  .section-title {
    font-size: 1.25rem;
  }
  
  .admin-event-actions {
    justify-content: space-between;
  }
  
  .admin-event-info {
    flex-direction: column;
    gap: 0.5rem;
  }
}

@media (max-width: 640px) {
  .quick-stats {
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
  }
  
  .stat-item {
    padding: 0.75rem;
    background: var(--surface-100);
    border-radius: var(--border-radius);
  }
  
  .admin-notification-badge {
    display: none;
  }
  
  .empty-actions {
    flex-direction: column;
    width: 100%;
  }
  
  .empty-actions .emerald-button,
  .empty-actions .emerald-outline-btn {
    width: 100%;
  }
  
  /* Mobile : garder le format horizontal mais plus compact */
  .section-tabs.enhanced-tabs {
    flex-direction: row;
    gap: 0.25rem;
    padding: 0.25rem;
  }
  
  .tab-button.enhanced {
    flex-direction: column;
    justify-content: center;
    gap: 0.375rem;
    min-height: 65px;
    padding: 0.5rem 0.75rem;
  }
  
  .tab-content-left {
    flex-direction: column !important;
    gap: 0.25rem !important;
  }
  
  .tab-text {
    font-size: 0.7rem;
    text-align: center;
  }
  
  .tab-count {
    position: absolute;
    top: 8px;
    right: 8px;
    min-width: 18px;
    height: 18px;
    padding: 0 !important;
    font-size: 0.65rem !important;
    display: flex;
    align-items: center;
    justify-content: center;
  }
}

/* Focus accessibilité pour les tabs */
.tab-button.enhanced:focus-visible {
  outline: 3px solid rgba(38, 166, 154, 0.5);
  outline-offset: 2px;
}

.tab-button.enhanced:focus:not(:focus-visible) {
  outline: none;
}

/* === ANIMATIONS === */

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

@keyframes bounceIn {
  0% { transform: scale(0); }
  50% { transform: scale(1.2); }
  100% { transform: scale(1); }
}

.slide-in-up {
  animation: slideInUp 0.6s ease-out;
}

.fade-in-scale {
  animation: fadeInScale 0.4s ease-out;
}

/* Transition pour l'ouverture/fermeture admin */
.admin-content {
  transition: all var(--transition-medium);
}
</style>
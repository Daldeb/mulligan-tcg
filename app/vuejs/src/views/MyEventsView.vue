<template>
  <div class="container py-6">
    <h2 class="text-2xl font-bold mb-6 flex items-center gap-2">
      <i class="pi pi-calendar" /> Mes événements
      <Button 
        v-if="canCreateEvent"
        class="emerald-btn ml-auto"
        icon="pi pi-plus"
        label="Créer un événement"
        @click="onCreateEvent"
      />
    </h2>

    <!-- Section Administration (pour admins uniquement) -->
    <Card v-if="isAdmin" class="admin-section mb-6" :class="{ 'has-notifications': adminNotificationCount > 0 }">
      <template #header>
        <div class="admin-header" @click="toggleAdminSection">
          <div class="admin-title">
            <i class="pi pi-shield admin-icon"></i>
            <span>Administration des événements</span>
            <!-- Cloche de notification -->
            <div v-if="adminNotificationCount > 0" class="notification-bell">
              <i class="pi pi-bell notification-icon"></i>
              <span class="notification-count">{{ adminNotificationCount }}</span>
            </div>
          </div>
          <i class="pi" :class="adminSectionOpen ? 'pi-chevron-up' : 'pi-chevron-down'"></i>
        </div>
      </template>
      
      <template #content>
        <div v-show="adminSectionOpen" class="admin-content">
          
          <!-- Loading admin events -->
          <div v-if="adminLoading" class="admin-loading">
            <div class="loading-spinner">
              <i class="pi pi-spin pi-spinner"></i>
              <span>Chargement des événements en attente...</span>
            </div>
          </div>

          <!-- Événements en attente de validation -->
          <div v-else-if="pendingEvents.length > 0" class="pending-events">
            <h4 class="admin-subtitle">
              <i class="pi pi-clock text-orange-500"></i>
              {{ pendingEvents.length }} événement{{ pendingEvents.length > 1 ? 's' : '' }} en attente de validation
            </h4>
            
            <div class="admin-events-grid">
              <div
                v-for="event in pendingEvents"
                :key="`admin-${event.id}`"
                class="admin-event-card"
              >
                <div class="admin-event-header">
                  <h5 class="admin-event-title">{{ event.title }}</h5>
                  <div class="admin-event-status">
                    <span class="status-badge pending-review">
                      <i class="pi pi-clock"></i>
                      En attente
                    </span>
                  </div>
                </div>
                
                <div class="admin-event-info">
                  <p class="admin-event-creator">
                    <i class="pi pi-user"></i>
                    Créé par {{ event.created_by?.pseudo || 'Utilisateur' }}
                  </p>
                  <p class="admin-event-date">
                    <i class="pi pi-calendar"></i>
                    {{ formatDate(event.start_date) }}
                  </p>
                </div>
                
                <div class="admin-event-actions">
                  <Button
                    icon="pi pi-eye"
                    label="Voir"
                    size="small"
                    outlined
                    @click="viewEventDetail(event)"
                  />
                  <Button
                    icon="pi pi-check"
                    label="Approuver"
                    size="small"
                    severity="success"
                    @click="approveEvent(event)"
                  />
                  <Button
                    icon="pi pi-times"
                    label="Refuser"
                    size="small"
                    severity="danger"
                    @click="rejectEvent(event)"
                  />
                  <Button
                    icon="pi pi-trash"
                    label="Supprimer"
                    size="small"
                    severity="danger"
                    outlined
                    @click="deleteEventAdmin(event)"
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- Aucun événement en attente -->
          <div v-else class="no-pending-events">
            <i class="pi pi-check-circle text-green-500 text-4xl mb-3"></i>
            <p class="text-secondary">Aucun événement en attente de validation</p>
          </div>
        </div>
      </template>
    </Card>

    <!-- Loading State -->
    <div v-if="loading" class="events-loading-grid">
      <div v-for="i in 2" :key="i" class="event-skeleton"></div>
    </div>

    <!-- Aucune donnée -->
    <div v-else-if="!myEvents.length" class="text-center text-secondary my-12">
      <i class="pi pi-info-circle mr-2"></i>
      Aucun événement créé pour le moment.
    </div>

    <!-- Liste événements -->
    <div v-else class="grid md:grid-cols-2 xl:grid-cols-3 gap-6">
      <div
        v-for="event in myEvents"
        :key="event.id"
        class="event-wrapper"
        :class="{ 'pending-review': event.status === 'PENDING_REVIEW' }"
      >
        <!-- Badge brillant pour événements en attente -->
        <div v-if="event.status === 'PENDING_REVIEW'" class="pending-badge">
          <div class="shine-effect"></div>
          <i class="pi pi-clock badge-icon"></i>
          <span class="badge-text">En attente de validation</span>
        </div>
        
        <!-- Badge pour événements refusés -->
        <div v-else-if="event.status === 'REJECTED'" class="rejected-badge">
          <i class="pi pi-times-circle badge-icon"></i>
          <span class="badge-text">Refusé - Cliquer pour modifier</span>
        </div>
        
        <!-- Badge pour événements approuvés -->
        <div v-else-if="event.status === 'APPROVED'" class="approved-badge">
          <i class="pi pi-check-circle badge-icon"></i>
          <span class="badge-text">Approuvé et public</span>
        </div>

        <EventCard
          :event="event"
          :show-actions="true"
          @edit="onEditEvent"
          @delete="confirmDelete(event)"
        />
      </div>
    </div>

    <!-- Événements suivis -->
    <div v-if="followedEvents.length > 0" class="followed-events-section mt-8">
      <h3 class="section-title">
        <i class="pi pi-heart text-red-500"></i>
        Événements suivis ({{ followedEvents.length }})
      </h3>
      
      <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-6">
        <EventCard
          v-for="event in followedEvents"
          :key="`followed-${event.id}`"
          :event="event"
          :show-follow-button="true"
          @follow-changed="loadFollowedEvents"
        />
      </div>
    </div>

    <!-- Mes participations -->
    <div v-if="participatingEvents.length > 0" class="participating-events-section mt-8">
      <h3 class="section-title">
        <i class="pi pi-users text-blue-500"></i>
        Mes participations ({{ participatingEvents.length }})
      </h3>
      
      <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-6">
        <EventCard
          v-for="event in participatingEvents"
          :key="`participating-${event.id}`"
          :event="event"
        />
      </div>
    </div>

    <!-- Dialogs Admin -->
    <Dialog
      v-model:visible="adminActionDialog"
      modal
      :header="adminActionTitle"
      :style="{ width: '500px' }"
    >
      <div class="admin-action-content">
        <p class="mb-4">{{ adminActionMessage }}</p>
        
        <div v-if="adminAction === 'reject' || adminAction === 'delete'" class="form-group">
          <label for="adminReason" class="form-label">Motif (obligatoire) :</label>
          <Textarea
            id="adminReason"
            v-model="adminActionReason"
            placeholder="Expliquez la raison du refus/suppression..."
            rows="4"
            class="w-full"
            :class="{ 'p-invalid': !adminActionReason.trim() }"
          />
          <small v-if="!adminActionReason.trim()" class="text-red-500">
            Un motif est requis pour cette action
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
          :disabled="(adminAction === 'reject' || adminAction === 'delete') && !adminActionReason.trim()"
          @click="confirmAdminAction"
        />
      </template>
    </Dialog>

    <!-- Boîte de confirmation PrimeVue -->
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
import CreateEventView from '@/views/CreateEventView.vue'

const eventStore = useEventStore()
const authStore = useAuthStore()
const { addNotification } = useNotifications()
const toast = useToast()
const confirm = useConfirm()
const router = useRouter()

const loading = ref(true)
const editingEvent = ref(null)

// Admin section
const adminSectionOpen = ref(false)
const adminLoading = ref(false)
const pendingEvents = ref([])
const followedEvents = ref([])
const participatingEvents = ref([])

// Admin actions
const adminActionDialog = ref(false)
const adminAction = ref('')
const adminActionEvent = ref(null)
const adminActionReason = ref('')

// --- Computed ---
const canCreateEvent = computed(() => authStore.canCreateEvent)
const isAdmin = computed(() => authStore.user?.roles?.includes('ROLE_ADMIN'))
const myEvents = computed(() => eventStore.myEvents)

const adminNotificationCount = computed(() => pendingEvents.value.length)

const adminActionTitle = computed(() => {
  switch (adminAction.value) {
    case 'approve': return 'Approuver l\'événement'
    case 'reject': return 'Refuser l\'événement'
    case 'delete': return 'Supprimer l\'événement'
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
    default: return ''
  }
})

const adminActionConfirmLabel = computed(() => {
  switch (adminAction.value) {
    case 'approve': return 'Approuver'
    case 'reject': return 'Refuser'
    case 'delete': return 'Supprimer définitivement'
    default: return 'Confirmer'
  }
})

const adminActionIcon = computed(() => {
  switch (adminAction.value) {
    case 'approve': return 'pi pi-check'
    case 'reject': return 'pi pi-times'
    case 'delete': return 'pi pi-trash'
    default: return 'pi pi-check'
  }
})

const adminActionSeverity = computed(() => {
  switch (adminAction.value) {
    case 'approve': return 'success'
    case 'reject': return 'warning'
    case 'delete': return 'danger'
    default: return undefined
  }
})

// --- Methods ---

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
    const response = await api.get('/api/admin/events/pending-review', {
      params: { limit: 20 }
    })
    pendingEvents.value = response.data.events || []
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
    const response = await api.get('/api/events/user/participating')
    participatingEvents.value = response.data.events || []
    console.log('🎫 Participations chargées:', participatingEvents.value.length)
  } catch (error) {
    console.error('❌ Erreur chargement participations:', error)
  }
}

const toggleAdminSection = async () => {
  adminSectionOpen.value = !adminSectionOpen.value
  
  // Charger les événements en attente si on ouvre la section
  if (adminSectionOpen.value && !pendingEvents.value.length) {
    await loadPendingEvents()
  }
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleString('fr-FR', {
    dateStyle: 'short',
    timeStyle: 'short'
  })
}

// Admin actions
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

const deleteEventAdmin = (event) => {
  adminActionEvent.value = event
  adminAction.value = 'delete'
  adminActionReason.value = ''
  adminActionDialog.value = true
}

const confirmAdminAction = async () => {
  if (!adminActionEvent.value) return
  
  const eventId = adminActionEvent.value.id
  const reason = adminActionReason.value.trim()
  
  // Validation
  if ((adminAction.value === 'reject' || adminAction.value === 'delete') && !reason) {
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Un motif est requis pour cette action',
      life: 3000
    })
    return
  }
  
  try {
    let response
    let successMessage = ''
    
    switch (adminAction.value) {
      case 'approve':
        response = await api.post(`/api/admin/events/${eventId}/approve`, {
          comment: reason || null
        })
        successMessage = 'Événement approuvé avec succès'
        break
        
      case 'reject':
        response = await api.post(`/api/admin/events/${eventId}/reject`, {
          reason: reason
        })
        successMessage = 'Événement refusé. Le créateur a été notifié.'
        break
        
      case 'delete':
        response = await api.delete(`/api/admin/events/${eventId}`, {
          data: { reason: reason }
        })
        successMessage = 'Événement supprimé définitivement'
        break
    }
    
    // Succès
    toast.add({
      severity: 'success',
      summary: 'Action réalisée',
      detail: successMessage,
      life: 3000
    })
    
    // Ajouter notification au créateur (sauf pour delete car plus d'événement)
    if (adminAction.value !== 'delete') {
      const notificationType = adminAction.value === 'approve' ? 'EVENT_APPROVED' : 'EVENT_REJECTED'
      addNotification(adminActionEvent.value.created_by.id, {
        type: notificationType,
        title: `Événement ${adminAction.value === 'approve' ? 'approuvé' : 'refusé'}`,
        message: adminAction.value === 'approve' 
          ? `Votre événement "${adminActionEvent.value.title}" a été approuvé et est maintenant visible.`
          : `Votre événement "${adminActionEvent.value.title}" a été refusé. Motif: ${reason}`,
        related_id: eventId,
        related_type: 'event'
      })
    } else {
      // Pour suppression, notification différente
      addNotification(adminActionEvent.value.created_by.id, {
        type: 'EVENT_DELETED',
        title: 'Événement supprimé',
        message: `Votre événement "${adminActionEvent.value.title}" a été supprimé. Motif: ${reason}`,
        related_id: null,
        related_type: 'event'
      })
    }
    
    // Fermer dialog et recharger
    adminActionDialog.value = false
    await loadPendingEvents()
    
  } catch (error) {
    console.error('❌ Erreur action admin:', error)
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: error.response?.data?.error || 'Erreur lors de l\'action',
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

onMounted(async () => {
  console.log('🚀 MyEventsView montée - User authentifié:', authStore.isAuthenticated)
  if (authStore.isAuthenticated) {
    await Promise.all([
      loadMyEvents(),
      loadFollowedEvents(),
      loadParticipatingEvents()
    ])
    
    // Charger les événements admin si admin
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
/* Section Admin */
.admin-section {
  border-left: 4px solid var(--primary);
  background: linear-gradient(135deg, rgba(38, 166, 154, 0.05), rgba(38, 166, 154, 0.02));
  transition: all var(--transition-medium);
}

.admin-section.has-notifications {
  border-left-color: #f59e0b;
  background: linear-gradient(135deg, rgba(245, 158, 11, 0.08), rgba(245, 158, 11, 0.02));
  box-shadow: 0 0 20px rgba(245, 158, 11, 0.1);
}

.admin-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  cursor: pointer;
  padding: 1rem;
  transition: all var(--transition-fast);
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
}

.admin-icon {
  color: var(--primary);
  font-size: 1.25rem;
}

/* Cloche de notification */
.notification-bell {
  position: relative;
  animation: bellShake 2s ease-in-out infinite;
}

.notification-icon {
  color: #f59e0b;
  font-size: 1.1rem;
}

.notification-count {
  position: absolute;
  top: -8px;
  right: -8px;
  background: #ef4444;
  color: white;
  border-radius: 50%;
  width: 18px;
  height: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.7rem;
  font-weight: 600;
}

@keyframes bellShake {
  0%, 50%, 100% { transform: rotate(0deg); }
  10%, 30% { transform: rotate(-10deg); }
  20%, 40% { transform: rotate(10deg); }
}

/* Admin content */
.admin-content {
  padding: 0 1rem 1rem;
}

.admin-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  padding: 2rem;
  color: var(--text-secondary);
}

.admin-subtitle {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 1.5rem;
}

.admin-events-grid {
  display: grid;
  gap: 1rem;
}

.admin-event-card {
  background: white;
  border: 1px solid var(--surface-200);
  border-radius: var(--border-radius-large);
  padding: 1.25rem;
  transition: all var(--transition-medium);
}

.admin-event-card:hover {
  box-shadow: var(--shadow-medium);
  transform: translateY(-2px);
}

.admin-event-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 1rem;
}

.admin-event-title {
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0;
}

.status-badge.pending-review {
  background: rgba(245, 158, 11, 0.1);
  color: #d97706;
  border: 1px solid rgba(245, 158, 11, 0.3);
  padding: 0.375rem 0.75rem;
  border-radius: 50px;
  font-size: 0.75rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.375rem;
}

.admin-event-info {
  display: flex;
  gap: 1.5rem;
  margin-bottom: 1rem;
  font-size: 0.875rem;
  color: var(--text-secondary);
}

.admin-event-info p {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  margin: 0;
}

.admin-event-actions {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.no-pending-events {
  text-align: center;
  padding: 3rem;
  color: var(--text-secondary);
}

/* Badges brillants pour les événements */
.event-wrapper {
  position: relative;
}

.pending-badge,
.rejected-badge,
.approved-badge {
  position: absolute;
  top: -8px;
  left: -8px;
  right: -8px;
  z-index: 10;
  padding: 0.5rem 1rem;
  border-radius: var(--border-radius);
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.pending-badge {
  background: linear-gradient(135deg, #f59e0b, #fbbf24);
  color: white;
  position: relative;
  overflow: hidden;
}

.rejected-badge {
  background: linear-gradient(135deg, #ef4444, #f87171);
  color: white;
}

.approved-badge {
  background: linear-gradient(135deg, #22c55e, #4ade80);
  color: white;
}

/* Effet brillant pour pending */
.shine-effect {
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
  animation: shine 2s infinite;
}

@keyframes shine {
  0% { left: -100%; }
  100% { left: 100%; }
}

.badge-icon {
  font-size: 0.875rem;
}

.badge-text {
  font-weight: 600;
}

/* Event wrapper pending */
.event-wrapper.pending-review {
  margin-top: 1rem;
}

/* Loading grid */
.events-loading-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
  gap: 1.5rem;
}

.event-skeleton {
  height: 280px;
  background: linear-gradient(90deg, var(--surface-200) 25%, var(--surface-100) 50%, var(--surface-200) 75%);
  background-size: 200% 100%;
  animation: loading 1.5s infinite;
  border-radius: var(--border-radius-large);
}

@keyframes loading {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

/* Admin dialog */
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

/* Responsive */
@media (max-width: 768px) {
  .admin-events-grid {
    grid-template-columns: 1fr;
  }
  
  .admin-event-actions {
    justify-content: space-between;
  }
  
  .admin-event-info {
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .notification-bell {
    display: none;
  }
}

/* Nouvelles sections */
.section-title {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 1.5rem;
  padding-bottom: 0.75rem;
  border-bottom: 2px solid var(--surface-200);
}

.followed-events-section,
.participating-events-section {
  background: var(--surface-50);
  border-radius: var(--border-radius-large);
  padding: 2rem;
  border: 1px solid var(--surface-200);
}
</style>
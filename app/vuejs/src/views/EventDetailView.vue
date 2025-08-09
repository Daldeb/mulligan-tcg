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
        
        <!-- Structure en grille : contenu principal + sidebar carte -->
        <div class="event-detail-grid">
          
          <!-- Contenu principal (gauche) -->
          <div class="main-content">
            
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
                          v-if="canPost"
                          label="Soumettre pour validation"
                          icon="pi pi-send"
                          class="post-btn emerald-button primary create-deck"
                          :loading="isPosting"
                          @click="submitForReview"
                        />
                        <Button
                          v-if="canEdit"
                          label="Modifier"
                          icon="pi pi-pencil"
                          class="emerald-outline-btn"
                          @click="editEvent"
                        />
                        <Button
                          v-if="canEdit && (event.status === 'DRAFT' || event.status === 'PENDING_REVIEW' || event.status === 'APPROVED')"
                          label="Annuler l'événement"
                          icon="pi pi-ban"
                          severity="warning"
                          outlined
                          :loading="isCancelling"
                          @click="cancelEventOwner"
                        />
                        <Button
                          v-if="canCancelAsAdmin"
                          label="Annuler (Admin)"
                          icon="pi pi-ban"
                          severity="danger"
                          outlined
                          :loading="isCancellingAdmin"
                          @click="cancelEventAdmin"
                        />
                      </div>
                    </div>
                  </div>
                </div>
              </template>
            </Card>

              <!-- Description avec créateur -->
              <Card v-if="event.description || event.created_by" class="event-description-card">
                <template #title>
                  <div class="description-header">
                    <div class="title-section">
                      <i class="pi pi-info-circle"></i>
                      Description
                    </div>
                    
                    <!-- Section créateur déplacée dans le header -->
                    <div v-if="event.created_by" class="creator-header-section">
                      <div class="creator-info" @click="goToCreatorProfile">
                        <div class="creator-details">
                          <span 
                            class="creator-name"
                            :class="{ 'clickable-name': canNavigateToProfile(creatorUserId) }"
                            :title="canNavigateToProfile(creatorUserId) ? getProfileTooltip(creatorDisplayName) : ''"
                          >
                            {{ creatorDisplayName }}
                          </span>
                          <div class="creator-type">
                            <i :class="creatorTypeIcon" class="type-icon"></i>
                            <span class="type-label">{{ creatorTypeLabel }}</span>
                          </div>
                        </div>
                        <div class="creator-avatar" :class="{ 'clickable-avatar': canNavigateToProfile(creatorUserId) }">
                          <img 
                            v-if="creatorAvatar" 
                            :src="creatorAvatar" 
                            :alt="creatorDisplayName"
                            class="avatar-image"
                            @error="(e) => e.target.style.display = 'none'"
                          />
                          <div v-else class="avatar-placeholder">
                            <i :class="creatorIcon" class="avatar-icon"></i>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </template>
                <template #content>
                  <!-- Description seulement -->
                  <div v-if="event.description" class="event-description" v-html="formatDescription(event.description)"></div>
                  <div v-else class="no-description">
                    <i class="pi pi-info-circle"></i>
                    <span>Aucune description fournie</span>
                  </div>
                </template>
              </Card>

              <!-- Participants -->
              <Card v-if="event.current_participants > 0" class="participants-card">
                <template #title>
                  <div class="participants-header" @click="participantsExpanded = !participantsExpanded">
                    <div class="title-section">
                      <i class="pi pi-users"></i>
                      Participants
                      <span class="participants-count">({{ event.current_participants }})</span>
                    </div>
                    <i :class="participantsExpanded ? 'pi pi-chevron-up' : 'pi pi-chevron-down'" class="toggle-icon"></i>
                  </div>
                </template>
                <template #content>
                  <Transition name="collapse">
                    <div v-show="participantsExpanded" class="participants-content">
                      <div class="participants-loading" v-if="isLoadingParticipants">
                        <i class="pi pi-spin pi-spinner"></i>
                        <span>Chargement des participants...</span>
                      </div>
                      
                      <div v-else-if="sortedParticipants.length" class="participants-list">
                        <!-- Organisateurs/Admins d'abord -->
                        <div v-if="privilegedParticipants.length" class="participants-group">
                          <h4 class="group-title">Organisateurs & Admins</h4>
                          <div class="participants-grid">
                            <div 
                              v-for="participant in privilegedParticipants" 
                              :key="participant.id"
                              class="participant-item privileged"
                              :class="{ 'clickable': canViewProfile }"
                              @click="handleParticipantClick(participant)"
                            >
                              <div class="participant-avatar">
                                <img 
                                  v-if="participant.user.avatar" 
                                  :src="getImageUrl(participant.user.avatar)" 
                                  :alt="participant.user.pseudo"
                                  class="avatar-image"
                                />
                                <div v-else class="avatar-placeholder">
                                  <i class="pi pi-user"></i>
                                </div>
                              </div>
                              <div class="participant-info">
                                <span 
                                  class="participant-name" 
                                  :class="{ 'name-blurred': !canViewProfile }"
                                >
                                  {{ participant.user.pseudo }}
                                </span>
                                <div class="participant-role">
                                  <i :class="getRoleIcon(participant.user.roles)" class="role-icon"></i>
                                  <span class="role-label">{{ getRoleLabel(participant.user.roles) }}</span>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>

                        <!-- Utilisateurs normaux -->
                        <div v-if="regularParticipants.length" class="participants-group">
                          <h4 class="group-title">Participants</h4>
                          <div class="participants-grid">
                            <div 
                              v-for="participant in regularParticipants" 
                              :key="participant.id"
                              class="participant-item"
                              :class="{ 'clickable': canViewProfile }"
                              @click="handleParticipantClick(participant)"
                            >
                              <div class="participant-avatar">
                                <img 
                                  v-if="participant.user.avatar" 
                                  :src="getImageUrl(participant.user.avatar)" 
                                  :alt="participant.user.pseudo"
                                  class="avatar-image"
                                />
                                <div v-else class="avatar-placeholder">
                                  <i class="pi pi-user"></i>
                                </div>
                              </div>
                              <div class="participant-info">
                                <span 
                                  class="participant-name" 
                                  :class="{ 'name-blurred': !canViewProfile }"
                                >
                                  {{ participant.user.pseudo }}
                                </span>
                                <div class="participant-role">
                                  <i class="pi pi-user role-icon"></i>
                                  <span class="role-label">Participant</span>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>

                      <div v-else class="no-participants">
                        <i class="pi pi-users"></i>
                        <span>Aucun participant pour le moment</span>
                      </div>
                    </div>
                  </Transition>
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
          
          <!-- Sidebar carte (droite) -->
          <aside v-if="event.address && !event.is_online && event.address.latitude && event.address.longitude" class="event-sidebar">
            <EventLocationMap 
              :address="event.address"
              :event-title="event.title"
            />
          </aside>
          
        </div>
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
import EventLocationMap from '@/components/events/EventLocationMap.vue'
import { useProfileNavigation } from '@/composables/useProfileNavigation'
const { goToProfile, canNavigateToProfile, getProfileTooltip } = useProfileNavigation()

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
const isCancelling = ref(false)
const isCancellingAdmin = ref(false)

// State
const isLoading = ref(true)
const error = ref(null)
const isRegistering = ref(false)
const isUnregistering = ref(false)
const isPosting = ref(false)

// Computed
const event = computed(() => eventStore.currentEvent)

// State pour participants (ajoute après les autres ref)
const participantsExpanded = ref(false)
const isLoadingParticipants = ref(false)
const participants = ref([])

// Computed pour le tri et les permissions
const canViewProfile = computed(() => {
  return authStore.isAuthenticated && 
         authStore.user?.id === event.value?.created_by_id
})

const sortedParticipants = computed(() => {
  // Ici on utilisera la vraie liste plus tard
  // Pour l'instant on simule avec une liste vide
  return participants.value
})

const privilegedParticipants = computed(() => {
  return sortedParticipants.value
    .filter(p => hasPrivilegedRole(p.user.roles))
    .sort((a, b) => a.user.pseudo.localeCompare(b.user.pseudo))
})

const regularParticipants = computed(() => {
  return sortedParticipants.value
    .filter(p => !hasPrivilegedRole(p.user.roles))
    .sort((a, b) => a.user.pseudo.localeCompare(b.user.pseudo))
})

// Méthodes utilitaires
const hasPrivilegedRole = (roles) => {
  return roles?.some(role => 
    ['ROLE_ADMIN', 'ROLE_SHOP', 'ROLE_ORGANIZER'].includes(role)
  )
}

const getRoleIcon = (roles) => {
  if (roles?.includes('ROLE_ADMIN')) return 'pi pi-shield'
  if (roles?.includes('ROLE_SHOP')) return 'pi pi-shop'
  if (roles?.includes('ROLE_ORGANIZER')) return 'pi pi-star'
  return 'pi pi-user'
}

const getRoleLabel = (roles) => {
  if (roles?.includes('ROLE_ADMIN')) return 'Admin'
  if (roles?.includes('ROLE_SHOP')) return 'Boutique'
  if (roles?.includes('ROLE_ORGANIZER')) return 'Organisateur'
  return 'Participant'
}

const handleParticipantClick = (participant) => {
  if (!canViewProfile.value) return
  
  // Logique navigation profil (à implémenter)
  console.log('Clic sur participant:', participant.user.pseudo)
}
// Computed pour le créateur (copié d'EventCard)
// Remplace TOUTES les occurrences de props.event par event.value
const creatorDisplayName = computed(() => {
  if (event.value?.organizer_type === 'SHOP' && 
      event.value?.created_by?.shop?.name && 
      event.value?.created_by?.shop?.isActive) {
    return event.value.created_by.shop.name
  }
  
  return event.value?.created_by?.pseudo || 
         event.value?.organizer_name || 
         'Organisateur'
})

const creatorAvatar = computed(() => {
  if (event.value?.organizer_type === 'SHOP' && 
      event.value?.created_by?.shop?.logo && 
      event.value?.created_by?.shop?.isActive) {
    return getImageUrl(event.value.created_by.shop.logo)
  }
  
  if (event.value?.created_by?.avatar) {
    return getImageUrl(event.value.created_by.avatar)
  }
  
  return null
})

const creatorTypeLabel = computed(() => {
  if (event.value?.organizer_type === 'SHOP') {
    return 'Boutique'
  }
  return 'Créateur'
})

const creatorTypeIcon = computed(() => {
  if (event.value?.organizer_type === 'SHOP') {
    return 'pi pi-shop'
  }
  return 'pi pi-user'
})

const creatorUserId = computed(() => {
  return event.value?.created_by?.id || event.value?.created_by_id
})

const creatorIcon = computed(() => {
  if (event.value?.organizer_type === 'SHOP') {
    return 'pi pi-shop'
  }
  return 'pi pi-user'
})

const goToCreatorProfile = (event) => {
  event?.stopPropagation()
  
  if (!creatorUserId.value) return
  
  if (canNavigateToProfile(creatorUserId.value)) {
    goToProfile(creatorUserId.value, creatorDisplayName.value)
  }
}

const canEdit = computed(() => {
  if (!authStore.isAuthenticated || !event.value) return false
  
  const userRoles = authStore.user?.roles || []
  const isAdmin = userRoles.includes('ROLE_ADMIN')
  const isCreator = event.value.created_by_id === authStore.user?.id
  const isShopOwner = event.value.organizer_type === 'SHOP' && 
                     authStore.user?.shop?.id === event.value.organizer_id

  return isCreator || isShopOwner
})

const canPost = computed(() => 
  event.value?.status === 'DRAFT' && canEdit.value
)

const canRegister = computed(() => {
  if (!authStore.isAuthenticated || !event.value) return false
  
  if (event.value.created_by_id === authStore.user?.id) return false
  
  if (canEdit.value) return false
  
  return !isRegistered.value && 
         event.value?.can_register &&
         event.value?.status === 'APPROVED'
})

const canCancelAsAdmin = computed(() => {
  if (!authStore.isAuthenticated || !event.value) return false
  
  const userRoles = authStore.user?.roles || []
  const isAdmin = userRoles.includes('ROLE_ADMIN')
  const isNotCreator = event.value.created_by_id !== authStore.user?.id
  
  // Admin peut annuler les événements des autres (pas les siens)
  return isAdmin && isNotCreator && !event.value.status.includes('FINISHED')
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
    
    // DEBUG - ajoute ces logs temporairement
    console.log('🔍 Event complet:', eventStore.currentEvent)
    console.log('🔍 Event address:', eventStore.currentEvent?.address)
    console.log('🔍 Coords:', eventStore.currentEvent?.address?.latitude, eventStore.currentEvent?.address?.longitude)
    console.log('🔍 Address complète:', JSON.stringify(eventStore.currentEvent?.address, null, 2))

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

const cancelEventOwner = () => {
  confirm.require({
    message: `Voulez-vous vraiment annuler "${event.value?.title}" ?`,
    header: 'Annuler l\'événement',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Oui, annuler',
    rejectLabel: 'Non',
    acceptClass: 'p-button-warning',
    accept: async () => {
      // Demander le motif
      const reason = prompt('Motif d\'annulation (minimum 30 caractères) :')
      
      if (!reason || reason.trim().length < 30) {
        toast.add({
          severity: 'error',
          summary: 'Motif requis',
          detail: 'Le motif d\'annulation doit faire au moins 30 caractères',
          life: 3000
        })
        return
      }
      
      isCancelling.value = true
      try {
        // UTILISER LE STORE au lieu de l'appel API direct
        await eventStore.cancelEvent(props.eventId, reason.trim())
        
        toast.add({
          severity: 'success',
          summary: 'Événement annulé',
          detail: 'Votre événement a été annulé avec succès',
          life: 3000
        })
        
        // Recharger pour voir le nouveau statut
        await loadEvent()
        
      } catch (err) {
        console.error('❌ Erreur annulation:', err)
        toast.add({
          severity: 'error',
          summary: 'Erreur d\'annulation',
          detail: err.message || 'Une erreur est survenue',
          life: 5000
        })
      } finally {
        isCancelling.value = false
      }
    }
  })
}

const cancelEventAdmin = () => {
  confirm.require({
    message: `Voulez-vous vraiment annuler "${event.value?.title}" en tant qu'administrateur ?`,
    header: 'Annulation administrative',
    icon: 'pi pi-shield',
    acceptLabel: 'Oui, annuler',
    rejectLabel: 'Non',
    acceptClass: 'p-button-danger',
    accept: async () => {
      // Demander le motif
      const reason = prompt('Motif d\'annulation administrative (minimum 30 caractères) :')
      
      if (!reason || reason.trim().length < 30) {
        toast.add({
          severity: 'error',
          summary: 'Motif requis',
          detail: 'Le motif d\'annulation doit faire au moins 30 caractères',
          life: 3000
        })
        return
      }
      
      isCancellingAdmin.value = true
      try {
        await eventStore.cancelEventAdmin(props.eventId, reason.trim())
        
        toast.add({
          severity: 'success',
          summary: 'Événement annulé',
          detail: 'L\'événement a été annulé par l\'administration',
          life: 3000
        })
        
        // Recharger pour voir le nouveau statut
        await loadEvent()
        
      } catch (err) {
        console.error('❌ Erreur annulation admin:', err)
        toast.add({
          severity: 'error',
          summary: 'Erreur d\'annulation',
          detail: err.message || 'Une erreur est survenue',
          life: 5000
        })
      } finally {
        isCancellingAdmin.value = false
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

const submitForReview = async () => {
  if (!canPost.value) return

  isPosting.value = true
  try {
    await eventStore.submitForReview(props.eventId)
    
    toast.add({
      severity: 'success',
      summary: 'Événement soumis',
      detail: 'Votre événement a été soumis pour validation par un administrateur',
      life: 4000
    })
    
    // Recharger l'événement pour voir le nouveau statut
    await loadEvent()
    
  } catch (err) {
    console.error('❌ Erreur soumission événement:', err)
    toast.add({
      severity: 'error',
      summary: 'Erreur de soumission',
      detail: err.message || 'Une erreur est survenue lors de la soumission',
      life: 5000
    })
  } finally {
    isPosting.value = false
  }
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

.event-detail-grid {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 2rem;
  align-items: start;
}

.event-sidebar {
  width: 320px;
  position: sticky;
  top: 180px;
}

.main-content {
  display: flex;
  flex-direction: column;
  gap: 2rem; /* Espacement uniforme entre toutes les sections */
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
@media (max-width: 1024px) {
  .event-detail-grid {
    grid-template-columns: 1fr;
  }
  
  .event-sidebar {
    position: static;
    order: -1; /* Carte en haut sur mobile */
  }
}

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

/* Section créateur dans description */
.event-creator-section {
  display: flex;
  align-items: center;
  justify-content: flex-start;
  padding: 1rem;
  background: var(--surface-gradient);
  border-radius: var(--border-radius);
  margin-bottom: 1.5rem;
  border: 1px solid var(--surface-200);
}

.creator-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  cursor: pointer;
  transition: all var(--transition-fast);
  border-radius: var(--border-radius);
  padding: 0.375rem;
  margin: -0.375rem;
}

.creator-info:hover {
  background: rgba(38, 166, 154, 0.1);
  transform: translateX(2px);
}

.creator-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  overflow: hidden;
  background: var(--surface-200);
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid var(--surface-300);
  flex-shrink: 0;
}

.avatar-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--surface-gradient);
}

.avatar-icon {
  font-size: 1.25rem;
  color: var(--text-secondary);
}

.creator-details {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.creator-name {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--text-primary);
  line-height: 1.2;
}

.creator-type {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.75rem;
  color: var(--text-secondary);
}

.type-icon {
  font-size: 0.7rem;
}

.type-label {
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-weight: 500;
}

.clickable-avatar {
  cursor: pointer;
  transition: all var(--transition-fast);
}

.clickable-avatar:hover {
  transform: scale(1.05);
  box-shadow: 0 0 0 3px rgba(38, 166, 154, 0.3);
}

.clickable-name {
  cursor: pointer;
  transition: color var(--transition-fast);
  border-radius: var(--border-radius-small);
  padding: 0.125rem 0.25rem;
  margin: -0.125rem -0.25rem;
}

.clickable-name:hover {
  color: var(--primary) !important;
  background: rgba(38, 166, 154, 0.1);
}

.no-description {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--text-secondary);
  font-style: italic;
}
/* Header de la description avec créateur */
.description-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  gap: 1rem;
}

.title-section {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  color: var(--text-primary);
}

.creator-header-section {
  flex-shrink: 0;
}

.creator-header-section .creator-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  cursor: pointer;
  transition: all var(--transition-fast);
  border-radius: var(--border-radius);
  padding: 0.375rem;
  margin: -0.375rem;
}

.creator-header-section .creator-info:hover {
  background: rgba(38, 166, 154, 0.1);
}

.creator-header-section .creator-details {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  text-align: right;
}

.creator-header-section .creator-name {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--text-primary);
  line-height: 1.2;
}

.creator-header-section .creator-type {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.75rem;
  color: var(--text-secondary);
  justify-content: flex-end;
}

.creator-header-section .creator-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  overflow: hidden;
  background: var(--surface-200);
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid var(--surface-300);
  flex-shrink: 0;
}

/* Responsive pour le header */
@media (max-width: 768px) {
  .description-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.75rem;
  }
  
  .creator-header-section .creator-details {
    text-align: left;
  }
  
  .creator-header-section .creator-type {
    justify-content: flex-start;
  }
}

/* === PARTICIPANTS SECTION === */
.participants-card {
  border-radius: var(--border-radius-large) !important;
  box-shadow: var(--shadow-medium) !important;
}

.participants-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  cursor: pointer;
  transition: all var(--transition-fast);
  padding: 0.25rem;
  margin: -0.25rem;
  border-radius: var(--border-radius);
}

.participants-header:hover {
  background: rgba(38, 166, 154, 0.05);
}

.participants-header .title-section {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  color: var(--text-primary);
}

.participants-count {
  font-size: 0.875rem;
  color: var(--text-secondary);
  font-weight: 500;
}

.toggle-icon {
  color: var(--text-secondary);
  transition: transform var(--transition-fast);
}

.participants-content {
  padding-top: 1rem;
}

.participants-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 2rem;
  color: var(--text-secondary);
}

.participants-group {
  margin-bottom: 2rem;
}

.participants-group:last-child {
  margin-bottom: 0;
}

.group-title {
  font-size: 1rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0 0 1rem 0;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid var(--surface-200);
}

.participants-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 1rem;
}

.participant-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  background: var(--surface-100);
  border: 1px solid var(--surface-200);
  border-radius: var(--border-radius);
  transition: all var(--transition-fast);
}

.participant-item.privileged {
  background: linear-gradient(135deg, rgba(38, 166, 154, 0.05), rgba(38, 166, 154, 0.02));
  border-color: rgba(38, 166, 154, 0.2);
}

.participant-item.clickable {
  cursor: pointer;
}

.participant-item.clickable:hover {
  background: var(--surface-200);
  transform: translateY(-1px);
  box-shadow: var(--shadow-small);
}

.participant-item.privileged.clickable:hover {
  background: linear-gradient(135deg, rgba(38, 166, 154, 0.1), rgba(38, 166, 154, 0.05));
  box-shadow: 0 2px 8px rgba(38, 166, 154, 0.2);
}

.participant-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  overflow: hidden;
  background: var(--surface-200);
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid var(--surface-300);
  flex-shrink: 0;
}

.participant-item.privileged .participant-avatar {
  border-color: var(--primary);
}

.avatar-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--surface-gradient);
  color: var(--text-secondary);
}

.participant-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  flex: 1;
}

.participant-name {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--text-primary);
  line-height: 1.2;
  transition: all var(--transition-fast);
}

.participant-name.name-blurred {
  filter: blur(4px);
  pointer-events: none;
  user-select: none;
}

.participant-role {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.75rem;
  color: var(--text-secondary);
}

.role-icon {
  font-size: 0.7rem;
}

.role-label {
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-weight: 500;
}

.no-participants {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 2rem;
  color: var(--text-secondary);
  font-style: italic;
}

/* Animation collapse */
.collapse-enter-active,
.collapse-leave-active {
  transition: all 0.3s ease;
}

.collapse-enter-from,
.collapse-leave-to {
  opacity: 0;
  max-height: 0;
  transform: translateY(-10px);
}

.collapse-enter-to,
.collapse-leave-from {
  opacity: 1;
  max-height: 500px;
  transform: translateY(0);
}

/* Responsive */
@media (max-width: 768px) {
  .participants-grid {
    grid-template-columns: 1fr;
  }
  
  .participant-item {
    padding: 0.5rem;
  }
  
  .participant-avatar {
    width: 36px;
    height: 36px;
  }
}
</style>
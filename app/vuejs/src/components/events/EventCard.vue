<template>
  <div
    class="event-card gaming-card hover-lift"
    :class="[
      `event-type-${event.event_type?.toLowerCase() || 'generique'}`,
      gameClass,
      {
        'event-full': eventIsFull,
        'event-past': isPast,
        'event-online': event.is_online,
        'event-featured': isFeatured
      }
    ]"
    @click="goToDetail"
    v-ripple
  >
<div class="event-creator-section">
  <div class="creator-info" @click="goToCreatorProfile">
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
  </div>
  
  <!-- Status badge déplacé ici -->
  <div v-if="showStatus" class="event-status-badge" :class="statusBadgeClass">
    <i :class="statusIcon" class="status-icon"></i>
    <span class="status-text">{{ statusLabel }}</span>
  </div>
</div>

    <!-- Image avec overlay et badges -->
    <div class="event-image-section">
      <div v-if="event.image" class="event-image-container">
        <img :src="getImageUrl(event.image)" :alt="event.title" class="event-image" />
        <div class="image-overlay"></div>
      </div>
      <div v-else class="event-image-placeholder">
        <i :class="eventTypeIcon" class="placeholder-icon"></i>
      </div>
      
      <!-- Badge "En ligne" -->
      <div v-if="event.is_online" class="online-badge">
        <i class="pi pi-globe"></i>
        <span>Evenement en virtuel</span>
      </div>
      
      <!-- Badge "Complet" -->
      <div v-if="eventIsFull" class="full-badge">
        <i class="pi pi-ban"></i>
        <span>Complet</span>
      </div>
    </div>

    <!-- Contenu principal -->
    <div class="event-content">
      <!-- Header avec titre et jeu -->
      <div class="event-header">
        <div class="event-title-section">
          <h3 class="event-title">{{ event.title }}</h3>
          <div v-if="event.games && event.games.length" class="event-game-tag" :class="gameClass">
            <i :class="gameIcon" class="game-icon"></i>
            <span class="game-name">{{ event.games[0]?.name }}</span>
          </div>
        </div>
      </div>

      <!-- Informations principales -->
      <div class="event-info">
        <div class="info-row">
          <div class="info-item">
            <i class="pi pi-calendar info-icon"></i>
            <span class="info-text">{{ formatDate(event.start_date) }}</span>
          </div>
          <div class="info-item">
            <i class="pi pi-users info-icon"></i>
            <span class="info-text">
              {{ event.current_participants || 0 }}/{{ event.max_participants || '∞' }}
            </span>
          </div>
        </div>
        
        <div v-if="event.entry_fee" class="info-row">
          <div class="info-item">
            <i class="pi pi-euro info-icon"></i>
            <span class="info-text">{{ event.entry_fee }}€</span>
          </div>
          <div v-if="!event.is_online && event.address" class="info-item location-item">
            <i class="pi pi-map-marker info-icon"></i>
            <span class="info-text">{{ event.address?.city || 'Lieu à définir' }}</span>
          </div>
        </div>
      </div>

      <!-- Description -->
      <div v-if="event.description" class="event-description">
        {{ truncateText(event.description, 120) }}
      </div>

      <!-- Barre de progression participants -->
      <div v-if="event.max_participants" class="participants-progress">
        <div class="progress-bar">
          <div 
            class="progress-fill" 
            :style="{ width: participantsPercentage + '%' }"
            :class="{ 'progress-full': eventIsFull }"
          ></div>
        </div>
        <span class="progress-text">
          {{ event.current_participants }}/{{ event.max_participants }} inscrits
        </span>
      </div>

      <!-- Countdown (nouveau) -->
      <div v-if="showCountdown" class="event-countdown">
        <div class="countdown-container" :class="countdownType">
          <i :class="countdownIcon" class="countdown-icon"></i>
          <div class="countdown-content">
            <span class="countdown-label">{{ countdownLabel }}</span>
            <div class="countdown-timer">
              <div v-if="timeRemaining.days > 0" class="time-unit">
                <span class="time-value">{{ timeRemaining.days }}</span>
                <span class="time-label">j</span>
              </div>
              <div v-if="timeRemaining.hours > 0 || timeRemaining.days > 0" class="time-unit">
                <span class="time-value">{{ String(timeRemaining.hours).padStart(2, '0') }}</span>
                <span class="time-label">h</span>
              </div>
              <div class="time-unit">
                <span class="time-value">{{ String(timeRemaining.minutes).padStart(2, '0') }}</span>
                <span class="time-label">m</span>
              </div>
              <div v-if="timeRemaining.days === 0 && timeRemaining.hours === 0" class="time-unit">
                <span class="time-value">{{ String(timeRemaining.seconds).padStart(2, '0') }}</span>
                <span class="time-label">s</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Actions footer -->
    <div class="event-actions">
      <!-- Bouton Suivre (premier pour la visibilité) -->
      <Button
        v-if="canFollow"
        :icon="isFollowing ? 'pi pi-heart-fill' : 'pi pi-heart'"
        :label="isFollowing ? 'Suivi' : 'Suivre'"
        :class="['follow-btn', { 'following': isFollowing }]"
        @click.stop="toggleFollow"
        size="small"
        outlined
      />

      <!-- Boutons principaux -->
      <Button
        v-if="canRegister"
        label="S'inscrire"
        icon="pi pi-user-plus"
        class="event-register-btn"
        :disabled="eventIsFull || isPast"
        @click.stop="register"
        size="small"
      />
      
      <Button
        v-if="isRegistered"
        label="Inscrit"
        icon="pi pi-check"
        class="event-registered-btn"
        @click.stop="unregister"
        size="small"
        severity="success"
      />

      <!-- Actions propriétaire/admin -->
      <div v-if="canEdit" class="owner-actions">
        <Button
          v-if="canPost"
          label="Poster"
          icon="pi pi-send"
          class="post-btn emerald-button primary create-deck"
          :loading="isPosting"
          @click.stop="submitForReview"
          size="small"
        />
        <Button
          v-if="canEdit"
          icon="pi pi-pencil"
          class="edit-btn"
          @click.stop="editEvent"
          size="small"
          outlined
          severity="secondary"
          v-tooltip="'Modifier'"
        />
        <Button
          icon="pi pi-trash"
          class="delete-btn"
          @click.stop="deleteEvent"
          size="small"
          outlined
          severity="danger"
        />
      </div>
    </div>

    <!-- Indicateur de statut en cours (pour événements en live) -->
    <div v-if="isLive" class="live-indicator">
      <div class="live-dot"></div>
      <span class="live-text">EN COURS</span>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'
import Button from 'primevue/button'
import { useProfileNavigation } from '@/composables/useProfileNavigation'


// Ajouter après les autres imports
const { goToProfile, canNavigateToProfile, getProfileTooltip } = useProfileNavigation()

// Ajouter temporairement dans les computed pour debug
const creatorDisplayName = computed(() => {
  console.log('🔍 Event data:', {
    organizerType: props.event.organizer_type,
    createdBy: props.event.created_by,
    organizerName: props.event.organizer_name
  })
  
  // Si l'organisateur est une boutique ET que le créateur a une boutique active
  if (props.event.organizer_type === 'SHOP' && 
      props.event.created_by?.shop?.name && 
      props.event.created_by?.shop?.isActive) {
    return props.event.created_by.shop.name
  }
  
  // Sinon, utiliser le pseudo du créateur
  return props.event.created_by?.pseudo || 
         props.event.organizer_name || 
         'Organisateur'
})

const creatorAvatar = computed(() => {
  // Si organisateur boutique et boutique active avec logo
  if (props.event.organizer_type === 'SHOP' && 
      props.event.created_by?.shop?.logo && 
      props.event.created_by?.shop?.isActive) {
    return getImageUrl(props.event.created_by.shop.logo)
  }
  
  // Sinon, utiliser l'avatar du créateur
  if (props.event.created_by?.avatar) {
    return getImageUrl(props.event.created_by.avatar)
  }
  
  return null
})

const creatorTypeLabel = computed(() => {
  if (props.event.organizer_type === 'SHOP') {
    return 'Boutique'
  }
  return 'Créateur'
})

const creatorTypeIcon = computed(() => {
  if (props.event.organizer_type === 'SHOP') {
    return 'pi pi-shop'
  }
  return 'pi pi-user'
})

const creatorUserId = computed(() => {
  return props.event.created_by?.id || props.event.created_by_id
})

const goToCreatorProfile = (event) => {
  event?.stopPropagation()
  
  if (!creatorUserId.value) return
  
  if (canNavigateToProfile(creatorUserId.value)) {
    goToProfile(creatorUserId.value, creatorDisplayName.value)
  }
}

// Props
const props = defineProps({
  event: { type: Object, required: true },
  showFollowButton: { type: Boolean, default: false },
  showStatus: { type: Boolean, default: false },
  onEdit: { type: Function },
  onDelete: { type: Function },
  onRegister: { type: Function },
  onUnregister: { type: Function }
})

const emit = defineEmits(['followChanged', 'statusChanged'])
const router = useRouter()
const authStore = useAuthStore()
const isPosting = ref(false)

// ============= COUNTDOWN SYSTEM =============

const currentTime = ref(new Date())
let countdownInterval = null

const updateCountdown = () => {
  currentTime.value = new Date()
}

onMounted(() => {
  // Mettre à jour toutes les secondes
  countdownInterval = setInterval(updateCountdown, 1000)
})

onUnmounted(() => {
  if (countdownInterval) {
    clearInterval(countdownInterval)
  }
})

const showCountdown = computed(() => {
  return props.event.start_date && !isPast.value && 
         (props.event.status === 'APPROVED' || props.event.status === 'VALIDATED')
})

const countdownType = computed(() => {
  if (isLive.value) return 'countdown-ending'
  return 'countdown-starting'
})

const countdownLabel = computed(() => {
  if (isLive.value) return 'Se termine dans'
  return 'Commence dans'
})

const countdownIcon = computed(() => {
  if (isLive.value) return 'pi pi-clock text-red-500'
  return 'pi pi-clock text-blue-500'
})

const targetDate = computed(() => {
  if (isLive.value && props.event.end_date) {
    return new Date(props.event.end_date)
  }
  return new Date(props.event.start_date)
})

const timeRemaining = computed(() => {
  const now = currentTime.value
  const target = targetDate.value
  const diff = target.getTime() - now.getTime()
  
  if (diff <= 0) {
    return { days: 0, hours: 0, minutes: 0, seconds: 0 }
  }
  
  const days = Math.floor(diff / (1000 * 60 * 60 * 24))
  const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))
  const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60))
  const seconds = Math.floor((diff % (1000 * 60)) / 1000)
  
  return { days, hours, minutes, seconds }
})

// ============= COMPUTED STATES (existants) =============

const canEdit = computed(() => {
  const result = authStore.user?.id === props.event.created_by_id
    || (authStore.user?.roles || []).includes('ROLE_ADMIN')
  
  console.log('🔍 CanEdit debug:', {
    userId: authStore.user?.id,
    eventCreatedBy: props.event.created_by_id,
    userRoles: authStore.user?.roles,
    result: result
  })
  
  return result
})

const creatorIcon = computed(() => {
  if (props.event.organizer_type === 'SHOP') {
    return 'pi pi-shop'
  }
  return 'pi pi-user'
})

const canRegister = computed(() =>
  !isRegistered.value && 
  !eventIsFull.value && 
  !isPast.value && 
  (props.event.status === 'APPROVED' || props.event.status === 'VALIDATED') &&
  !canEdit.value &&
  authStore.isAuthenticated
)

const isRegistered = computed(() =>
  props.event.participants?.some(p => p.user?.id === authStore.user?.id)
)

const eventIsFull = computed(() =>
  props.event.max_participants && 
  props.event.current_participants >= props.event.max_participants
)

const isPast = computed(() => {
  if (!props.event.start_date) return false
  return new Date(props.event.start_date) < new Date()
})

const isLive = computed(() => {
  if (!props.event.start_date) return false
  const now = new Date()
  const start = new Date(props.event.start_date)
  const end = props.event.end_date ? new Date(props.event.end_date) : new Date(start.getTime() + 4 * 60 * 60 * 1000)
  return start <= now && now <= end
})

const canPost = computed(() => 
  props.event.status === 'DRAFT' && canEdit.value
)

async function submitForReview(e) {
  e?.stopPropagation()
  
  isPosting.value = true
  try {
    await api.post(`/api/events/${props.event.id}/submit-for-review`)
    
    // Mettre à jour le statut localement
    props.event.status = 'PENDING_REVIEW'
    
    // Success feedback
    console.log('✅ Événement soumis pour validation:', props.event.id)
    
    // Optionnel: émettre un événement pour informer le parent
    emit('statusChanged', { 
      event: props.event, 
      newStatus: 'PENDING_REVIEW' 
    })
    
  } catch (error) {
    console.error('❌ Erreur soumission événement:', error)
  } finally {
    isPosting.value = false
  }
}

const isFeatured = computed(() => 
  props.event.featured || props.event.event_type === 'TOURNOI'
)

const participantsPercentage = computed(() => {
  if (!props.event.max_participants) return 0
  return Math.min(100, (props.event.current_participants / props.event.max_participants) * 100)
})

// ============= GAME & TYPE STYLING =============

const gameClass = computed(() => {
  if (!props.event.games || !props.event.games.length) return ''
  const name = props.event.games[0].name?.toLowerCase() || ''
  if (name.includes('magic')) return 'gaming-magic'
  if (name.includes('hearthstone')) return 'gaming-hearthstone'
  if (name.includes('pokemon')) return 'gaming-pokemon'
  return 'gaming-generic'
})

const gameIcon = computed(() => {
  if (!props.event.games || !props.event.games.length) return 'pi pi-tag'
  const name = props.event.games[0].name?.toLowerCase() || ''
  if (name.includes('magic')) return 'pi pi-star-fill'
  if (name.includes('hearthstone')) return 'pi pi-bolt'
  if (name.includes('pokemon')) return 'pi pi-circle-fill'
  return 'pi pi-tag'
})

const eventTypeIcon = computed(() => {
  switch (props.event.event_type) {
    case 'TOURNOI': return 'pi pi-trophy'
    case 'AVANT_PREMIERE': return 'pi pi-star'
    case 'RENCONTRE': return 'pi pi-users'
    default: return 'pi pi-calendar'
  }
})

// ============= STATUS MANAGEMENT =============

const statusLabel = computed(() => {
  if (!props.showStatus) return ''
  
  switch (props.event.status) {
    case 'DRAFT': return 'Brouillon'
    case 'PENDING_REVIEW': return 'En validation'
    case 'APPROVED': return 'Publié'
    case 'VALIDATED': return 'Validé'
    case 'REJECTED': return 'Refusé'
    case 'CANCELLED': return 'Annulé'
    case 'IN_PROGRESS': return 'En cours'
    case 'FINISHED': return 'Terminé'
    default: return props.event.status
  }
})

const statusIcon = computed(() => {
  switch (props.event.status) {
    case 'DRAFT': return 'pi pi-file-edit'
    case 'PENDING_REVIEW': return 'pi pi-clock'
    case 'APPROVED':
    case 'VALIDATED': return 'pi pi-check-circle'
    case 'REJECTED': return 'pi pi-times-circle'
    case 'CANCELLED': return 'pi pi-ban'
    case 'IN_PROGRESS': return 'pi pi-play-circle'
    case 'FINISHED': return 'pi pi-flag-fill'
    default: return 'pi pi-info-circle'
  }
})

const statusBadgeClass = computed(() => {
  if (!props.showStatus) return 'status-hidden'
  
  switch (props.event.status) {
    case 'DRAFT': return 'status-draft'
    case 'PENDING_REVIEW': return 'status-pending'
    case 'APPROVED':
    case 'VALIDATED': return 'status-approved'
    case 'REJECTED': return 'status-rejected'
    case 'CANCELLED': return 'status-cancelled'
    case 'IN_PROGRESS': return 'status-live'
    case 'FINISHED': return 'status-finished'
    default: return 'status-default'
  }
})

// ============= FOLLOW SYSTEM =============

const isFollowing = computed(() => {
  if (!authStore.user || !props.showFollowButton) return false
  const followedEvents = authStore.user.followedEvents || []
  return followedEvents.includes(props.event.id)
})

const canFollow = computed(() => {
  return props.showFollowButton && 
         authStore.isAuthenticated && 
         !canEdit.value && 
         !isRegistered.value &&
         (props.event.status === 'APPROVED' || props.event.status === 'VALIDATED')
})

async function toggleFollow(e) {
  e?.stopPropagation()
  
  if (!authStore.isAuthenticated) return
  
  try {
    const endpoint = isFollowing.value ? 'unfollow' : 'follow'
    await api.post(`/api/events/${props.event.id}/${endpoint}`)
    
    const followedEvents = authStore.user.followedEvents || []
    
    if (isFollowing.value) {
      authStore.user.followedEvents = followedEvents.filter(id => id !== props.event.id)
    } else {
      authStore.user.followedEvents = [...followedEvents, props.event.id]
    }
    
    emit('followChanged', { 
      event: props.event, 
      isFollowing: !isFollowing.value 
    })
    
  } catch (error) {
    console.error('Erreur toggle follow:', error)
  }
}

// ============= UTILITY FUNCTIONS =============

const getImageUrl = (imagePath) => {
  if (!imagePath) return null
  
  if (imagePath.startsWith('events/') || imagePath.startsWith('avatars/') || imagePath.startsWith('shops/')) {
    return `http://localhost:8000/uploads/${imagePath}`
  }
  
  if (imagePath.startsWith('http')) {
    return imagePath
  }
  
  return `http://localhost:8000/uploads/${imagePath}`
}

function formatDate(date) {
  if (!date) return ''
  const d = new Date(date)
  const now = new Date()
  const diffTime = d.getTime() - now.getTime()
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  
  if (diffDays === 0) return 'Aujourd\'hui'
  if (diffDays === 1) return 'Demain'
  if (diffDays > 1 && diffDays <= 7) return `Dans ${diffDays} jours`
  
  return d.toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function truncateText(text, maxLength) {
  if (!text || text.length <= maxLength) return text
  return text.substring(0, maxLength) + '...'
}

// ============= EVENT HANDLERS =============

function goToDetail() {
  router.push({ name: 'evenement-detail', params: { id: props.event.id } })
}

function editEvent(e) {
  e?.stopPropagation()
  router.push({ name: 'creer-evenement', query: { id: props.event.id } })
}

function deleteEvent(e) {
  e?.stopPropagation()
  props.onDelete && props.onDelete(props.event)
}

function register(e) {
  e?.stopPropagation()
  props.onRegister && props.onRegister(props.event)
}

function unregister(e) {
  e?.stopPropagation()
  props.onUnregister && props.onUnregister(props.event)
}
</script>

<style scoped>
/* ============= BASE CARD STYLING ============= */

.event-card {
  position: relative;
  background: white;
  border-radius: var(--border-radius-large);
  box-shadow: var(--shadow-small);
  border: 1px solid var(--surface-200);
  overflow: hidden;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  height: 100%;
  min-height: 440px; /* Augmenté de 420px à 440px */
}

.event-card:hover {
  box-shadow: var(--shadow-large);
  transform: translateY(-8px) scale(1.02);
}

/* Bordures colorées par type */
.event-card.event-type-tournoi::before {
  background: linear-gradient(90deg, #f59e0b, #d97706, #92400e);
}

.event-card.event-type-avant_premiere::before {
  background: linear-gradient(90deg, #8b4513, #a0522d, #654321);
}

.event-card.event-type-rencontre::before {
  background: linear-gradient(90deg, #3b82f6, #2563eb, #1d4ed8);
}

.event-card.event-type-generique::before {
  background: linear-gradient(90deg, var(--primary), var(--primary-dark), #004d40);
}

/* ============= CREATOR SECTION (NOUVELLE) ============= */

.event-creator-section {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.25rem;
  background: var(--surface-gradient);
  border-bottom: 1px solid var(--surface-200);
  min-height: 60px; /* Assurer une hauteur minimum */
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

/* ============= IMAGE SECTION ============= */

.event-image-section {
  position: relative;
  height: 130px; /* Réduit de 140px à 130px pour compenser */
  background: var(--surface-gradient);
}

.event-image-container {
  width: 100%;
  height: 100%;
  position: relative;
  overflow: hidden;
}

.event-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform var(--transition-medium);
}

.event-card:hover .event-image {
  transform: scale(1.05);
}

.image-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(to bottom, transparent 0%, rgba(0, 0, 0, 0.1) 100%);
}

.event-image-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--surface-gradient);
}

.placeholder-icon {
  font-size: 3rem;
  color: var(--text-secondary);
  opacity: 0.5;
}

/* ============= STATUS BADGES ============= */

.event-status-badge {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.375rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.3);
  flex-shrink: 0;
}

.status-hidden {
  display: none;
}

.status-draft {
  background: rgba(107, 114, 128, 0.9);
  color: white;
}

.status-pending {
  background: rgba(245, 158, 11, 0.9);
  color: white;
  animation: pulse 2s infinite;
}

.status-approved {
  background: rgba(34, 197, 94, 0.9);
  color: white;
}

.status-rejected {
  background: rgba(239, 68, 68, 0.9);
  color: white;
}

.status-cancelled {
  background: rgba(156, 163, 175, 0.9);
  color: white;
}

.status-live {
  background: rgba(239, 68, 68, 0.9);
  color: white;
  animation: pulse 1.5s infinite;
}

.status-finished {
  background: rgba(75, 85, 99, 0.9);
  color: white;
}

.online-badge {
  position: absolute;
  top: 12px;
  left: 12px;
  display: flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.25rem 0.5rem;
  background: rgba(16, 185, 129, 0.9);
  color: white;
  border-radius: 12px;
  font-size: 0.7rem;
  font-weight: 600;
  backdrop-filter: blur(10px);
}

.full-badge {
  position: absolute;
  bottom: 12px;
  left: 12px;
  display: flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.25rem 0.5rem;
  background: rgba(239, 68, 68, 0.9);
  color: white;
  border-radius: 12px;
  font-size: 0.7rem;
  font-weight: 600;
  backdrop-filter: blur(10px);
}

/* ============= CONTENT SECTION ============= */

.event-content {
  flex: 1;
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.event-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
}

.event-title-section {
  flex: 1;
}

.event-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--text-primary);
  line-height: 1.3;
  margin: 0 0 0.75rem 0;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.event-game-tag {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.375rem 0.75rem;
  background: var(--surface-200);
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  border: 1px solid var(--surface-300);
  transition: all var(--transition-fast);
}

.event-game-tag.gaming-magic {
  background: rgba(107, 70, 193, 0.1);
  color: #6b46c1;
  border-color: rgba(107, 70, 193, 0.3);
}

.event-game-tag.gaming-hearthstone {
  background: rgba(255, 99, 71, 0.1);
  color: #ff6347;
  border-color: rgba(255, 99, 71, 0.3);
}

.event-game-tag.gaming-pokemon {
  background: rgba(220, 38, 38, 0.1);
  color: #dc2626;
  border-color: rgba(220, 38, 38, 0.3);
}

.game-icon {
  font-size: 0.875rem;
}

/* ============= INFO SECTION ============= */

.event-info {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.info-row {
  display: flex;
  gap: 1.5rem;
  flex-wrap: wrap;
}

.info-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  color: var(--text-secondary);
}

.info-icon {
  font-size: 1rem;
  color: var(--primary);
  flex-shrink: 0;
}

.info-text {
  font-weight: 500;
}

.location-item .info-text {
  max-width: 120px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* ============= DESCRIPTION ============= */

.event-description {
  font-size: 0.875rem;
  color: var(--text-secondary);
  line-height: 1.5;
  flex: 1;
}

/* ============= PROGRESS BAR ============= */

.participants-progress {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.progress-bar {
  width: 100%;
  height: 6px;
  background: var(--surface-200);
  border-radius: 3px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, var(--primary), var(--primary-light));
  border-radius: 3px;
  transition: width var(--transition-medium);
}

.progress-fill.progress-full {
  background: linear-gradient(90deg, #ef4444, #f87171);
}

.progress-text {
  font-size: 0.75rem;
  color: var(--text-secondary);
  text-align: center;
  font-weight: 500;
}

/* ============= COUNTDOWN SECTION (NOUVELLE) ============= */

.event-countdown {
  margin-top: 0.5rem;
}

.countdown-container {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.875rem;
  background: var(--surface-gradient);
  border: 1px solid var(--surface-200);
  border-radius: var(--border-radius);
  transition: all var(--transition-fast);
}

.countdown-container.countdown-starting {
  border-color: #3b82f6;
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.05), rgba(59, 130, 246, 0.02));
}

.countdown-container.countdown-ending {
  border-color: #ef4444;
  background: linear-gradient(135deg, rgba(239, 68, 68, 0.05), rgba(239, 68, 68, 0.02));
  animation: countdownPulse 2s ease-in-out infinite;
}

@keyframes countdownPulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.02); }
}

.countdown-icon {
  font-size: 1.25rem;
  flex-shrink: 0;
}

.countdown-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.countdown-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.countdown-timer {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.time-unit {
  display: flex;
  flex-direction: column;
  align-items: center;
  min-width: 32px;
}

.time-value {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--text-primary);
  line-height: 1;
  font-variant-numeric: tabular-nums;
}

.time-label {
  font-size: 0.7rem;
  color: var(--text-secondary);
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-top: 0.125rem;
}

.countdown-ending .time-value {
  color: #ef4444;
}

.countdown-ending .countdown-label {
  color: #ef4444;
}

/* ============= ACTIONS ============= */

.event-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem 1.25rem;
  border-top: 1px solid var(--surface-200);
  background: var(--surface-gradient);
}

.follow-btn {
  border: 2px solid #ef4444 !important;
  color: #ef4444 !important;
  background: transparent !important;
  transition: all var(--transition-fast) !important;
}

.follow-btn:hover {
  transform: translateY(-1px) !important;
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3) !important;
}

.follow-btn.following {
  background: #ef4444 !important;
  color: white !important;
}

.follow-btn.following:hover {
  background: #dc2626 !important;
  border-color: #dc2626 !important;
}

.event-register-btn {
  background: var(--primary) !important;
  border-color: var(--primary) !important;
  color: white !important;
  font-weight: 600 !important;
  flex: 1;
}

.event-register-btn:hover:not(:disabled) {
  background: var(--primary-dark) !important;
  border-color: var(--primary-dark) !important;
  transform: translateY(-1px) !important;
  box-shadow: 0 4px 12px rgba(38, 166, 154, 0.4) !important;
}

.event-registered-btn {
  flex: 1;
  background: #22c55e !important;
  border-color: #22c55e !important;
}

.event-registered-btn:hover {
  background: #16a34a !important;
  border-color: #16a34a !important;
  transform: translateY(-1px) !important;
}

.owner-actions {
  display: flex;
  gap: 0.5rem;
}

.edit-btn, .delete-btn {
  min-width: 40px;
}

/* ============= LIVE INDICATOR ============= */

.live-indicator {
  position: absolute;
  bottom: 12px;
  right: 12px;
  display: flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.375rem 0.75rem;
  background: rgba(239, 68, 68, 0.95);
  color: white;
  border-radius: 20px;
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  backdrop-filter: blur(10px);
  animation: pulse 2s infinite;
}

.live-dot {
  width: 6px;
  height: 6px;
  background: white;
  border-radius: 50%;
  animation: pulse 1s infinite;
}

/* ============= SPECIAL STATES ============= */

.event-card.event-featured {
  border: 2px solid var(--primary);
  box-shadow: 0 0 20px rgba(38, 166, 154, 0.2);
}

.event-card.event-past {
  opacity: 0.8;
  filter: grayscale(10%);
}

.event-card.event-full .progress-fill {
  background: linear-gradient(90deg, #ef4444, #f87171);
}

/* ============= ANIMATIONS ============= */

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.7; }
}

.event-card {
  animation: fadeInScale 0.6s ease-out;
}

/* ============= RESPONSIVE ============= */

@media (max-width: 768px) {
  .event-card {
    min-height: 400px;
  }
  
  .event-creator-section {
    padding: 0.875rem 1rem;
  }
  
  .creator-avatar {
    width: 36px;
    height: 36px;
  }
  
  .creator-name {
    font-size: 0.8rem;
  }
  
  .event-image-section {
    height: 120px;
  }
  
  .event-content {
    padding: 1rem;
    gap: 0.75rem;
  }
  
  .event-title {
    font-size: 1.125rem;
  }
  
  .info-row {
    gap: 1rem;
  }
  
  .countdown-container {
    padding: 0.75rem;
  }
  
  .countdown-timer {
    gap: 0.375rem;
  }
  
  .time-unit {
    min-width: 28px;
  }
  
  .time-value {
    font-size: 1.125rem;
  }
  
  .event-actions {
    padding: 0.875rem 1rem;
    gap: 0.5rem;
    flex-wrap: wrap;
  }
  
  .follow-btn, .owner-actions {
    order: 2;
  }
  
  .event-register-btn, .event-registered-btn {
    order: 1;
    flex: 1;
  }
}

@media (max-width: 480px) {
  .event-creator-section {
    padding: 0.75rem;
  }
  
  .creator-details {
    gap: 0.125rem;
  }
  
  .creator-name {
    font-size: 0.75rem;
  }
  
  .creator-type {
    font-size: 0.7rem;
  }
  
  .countdown-timer {
    justify-content: center;
  }
  
  .time-unit {
    min-width: 24px;
  }
  
  .time-value {
    font-size: 1rem;
  }
  
  .time-label {
    font-size: 0.65rem;
  }
  
  .event-actions {
    flex-direction: column;
    gap: 0.75rem;
  }
  
  .follow-btn, .event-register-btn, .event-registered-btn {
    width: 100%;
  }
  
  .owner-actions {
    align-self: stretch;
    justify-content: space-between;
  }
}

/* ============= THEMES PAR JEU ============= */

.event-card.gaming-magic .countdown-container.countdown-starting {
  border-color: #6b46c1;
  background: linear-gradient(135deg, rgba(107, 70, 193, 0.05), rgba(107, 70, 193, 0.02));
}

.event-card.gaming-hearthstone .countdown-container.countdown-starting {
  border-color: #ff6347;
  background: linear-gradient(135deg, rgba(255, 99, 71, 0.05), rgba(255, 99, 71, 0.02));
}

.event-card.gaming-pokemon .countdown-container.countdown-starting {
  border-color: #dc2626;
  background: linear-gradient(135deg, rgba(220, 38, 38, 0.05), rgba(220, 38, 38, 0.02));
}

/* Styles cliquables pour navigation profil */
.clickable-avatar {
  cursor: pointer;
  transition: all var(--transition-fast);
  border-radius: 50%;
  position: relative;
}

.clickable-avatar:hover {
  transform: scale(1.05);
  box-shadow: 0 0 0 3px rgba(38, 166, 154, 0.3);
}

.clickable-name {
  cursor: pointer;
  transition: color var(--transition-fast);
  text-decoration: none;
  border-radius: var(--border-radius-small);
  padding: 0.125rem 0.25rem;
  margin: -0.125rem -0.25rem;
}

.clickable-name:hover {
  color: var(--primary) !important;
  background: rgba(38, 166, 154, 0.1);
}

.clickable-avatar:hover::after {
  content: '';
  position: absolute;
  top: -2px;
  left: -2px;
  right: -2px;
  bottom: -2px;
  border: 2px solid var(--primary);
  border-radius: 50%;
  opacity: 0.6;
}

/* Responsive pour les avatars cliquables */
@media (max-width: 768px) {
  .clickable-avatar:hover {
    transform: scale(1.02);
  }
  
  .clickable-avatar:hover::after {
    display: none;
  }
}
</style>
<template>
  <div
    class="event-card"
    :class="[
      `event-type-${event.event_type?.toLowerCase() || 'generique'}`,
      { 'event-full': eventIsFull, 'event-past': isPast, 'event-online': event.is_online },
      gameClass
    ]"
    @click="goToDetail"
    v-ripple
  >
    <!-- ✅ CORRECTION: Image Event avec URL complète -->
    <div v-if="event.image" class="event-image-container">
      <img :src="getImageUrl(event.image)" :alt="event.title" class="event-image" />
    </div>

    <div class="p-card-body flex flex-col gap-3">
      <!-- Header Title & Badges -->
      <div class="flex items-center gap-3 mb-1">
        <span class="text-lg font-bold text-primary">{{ event.title }}</span>
        <span v-if="event.status" class="event-badge"
          :class="statusBadgeClass(event.status)">
          {{ statusLabel(event.status) }}
        </span>
        <span v-if="event.is_online" class="event-badge badge-online">En ligne</span>
      </div>

      <!-- Info lignes -->
      <div class="flex flex-wrap gap-4 text-sm text-secondary">
        <div>
          <i class="pi pi-calendar"></i>
          {{ formatDate(event.start_date) }}
        </div>
        <div>
          <i class="pi pi-users"></i>
          {{ event.current_participants || 0 }} /
          {{ event.max_participants || '-' }} inscrits
        </div>
        <div v-if="event.games && event.games.length">
          <span class="event-game-tag" :class="gameClass">
            <i :class="gameIcon"></i>
            {{ event.games[0]?.name }}
          </span>
        </div>
      </div>

      <!-- Description -->
      <div class="text-secondary text-sm line-clamp-3 mb-2">
        {{ event.description }}
      </div>

      <!-- Participants Dropdown (à implémenter quand tu charger as event.participants) -->
      <!--
      <Dropdown
        v-if="event.participants && event.participants.length"
        :options="event.participants"
        optionLabel="user.pseudo"
        placeholder="Liste inscrits"
        class="ml-2"
        style="min-width:150px;"
      >
        <template #option="slotProps">
          <div class="flex items-center gap-2">
            <img v-if="slotProps.option.user.avatar" :src="slotProps.option.user.avatar" alt="" class="p-avatar p-avatar-sm" style="width:28px;height:28px;border-radius:50%;" />
            <span>{{ slotProps.option.user.pseudo }}</span>
          </div>
        </template>
      </Dropdown>
      -->

      <!-- Actions -->
      <div class="flex items-center gap-3 mt-3">
        <Button
          v-if="canEdit"
          label="Éditer"
          icon="pi pi-pencil"
          class="emerald-btn"
          @click.stop="editEvent"
          size="small"
        />
        <Button
          v-if="canEdit"
          label="Supprimer"
          icon="pi pi-trash"
          class="emerald-outline-btn cancel"
          @click.stop="deleteEvent"
          size="small"
        />
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
          label="Me désinscrire"
          icon="pi pi-user-minus"
          class="event-unregister-btn"
          @click.stop="unregister"
          size="small"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import Button from 'primevue/button'
// import Dropdown from 'primevue/dropdown' // À décommenter quand participants sont chargés

// Props
const props = defineProps({
  event: { type: Object, required: true },
  onEdit: { type: Function },
  onDelete: { type: Function },
  onRegister: { type: Function },
  onUnregister: { type: Function }
})

const router = useRouter()
const authStore = useAuthStore()

// Calculs status & droits
const canEdit = computed(() =>
  authStore.user?.id === props.event.created_by_id
  || (authStore.user?.roles || []).includes('ROLE_ADMIN')
)
const canRegister = computed(() =>
  !isRegistered.value && !eventIsFull.value && !isPast.value && props.event.status === 'VALIDATED'
)
const isRegistered = computed(() =>
  props.event.participants?.some(
    p => p.user.id === authStore.user?.id
  )
)
const eventIsFull = computed(() =>
  props.event.current_participants >= props.event.max_participants
)
const isPast = computed(() => {
  const now = new Date()
  return props.event.start_date && new Date(props.event.start_date) < now
})

// GESTION DU JEU PRINCIPAL
const gameClass = computed(() => {
  if (!props.event.games || !props.event.games.length) return ''
  const name = props.event.games[0].name?.toLowerCase() || ''
  if (name.includes('magic')) return 'gaming-magic'
  if (name.includes('hearthstone')) return 'gaming-hearthstone'
  if (name.includes('pokemon')) return 'gaming-pokemon'
  return ''
})
const gameIcon = computed(() => {
  if (!props.event.games || !props.event.games.length) return ''
  const name = props.event.games[0].name?.toLowerCase() || ''
  if (name.includes('magic')) return 'pi pi-star'
  if (name.includes('hearthstone')) return 'pi pi-bolt'
  if (name.includes('pokemon')) return 'pi pi-cog'
  return 'pi pi-tag'
})

// ✅ AJOUT: Méthode pour construire l'URL complète de l'image
const getImageUrl = (imagePath) => {
  if (!imagePath) return null
  
  // Si l'image commence par 'events/', construire l'URL complète
  if (imagePath.startsWith('events/')) {
    return `http://localhost:8000/uploads/${imagePath}`
  }
  
  // Si c'est déjà une URL complète, la retourner
  if (imagePath.startsWith('http')) {
    return imagePath
  }
  
  // Par défaut, considérer que c'est un chemin relatif dans uploads
  return `http://localhost:8000/uploads/${imagePath}`
}

// Utils
function formatDate(date) {
  if (!date) return ''
  return new Date(date).toLocaleString('fr-FR', {
    dateStyle: 'short',
    timeStyle: 'short'
  })
}

function statusLabel(status) {
  switch (status) {
    case 'DRAFT': return 'Brouillon'
    case 'PENDING_REVIEW': return 'À valider'
    case 'VALIDATED': return 'Validé'
    case 'REJECTED': return 'Rejeté'
    default: return status
  }
}

function statusBadgeClass(status) {
  switch (status) {
    case 'DRAFT': return 'badge-generique'
    case 'PENDING_REVIEW': return 'badge-rencontre'
    case 'VALIDATED': return 'badge-tournoi'
    case 'REJECTED': return 'badge-full'
    default: return 'badge-generique'
  }
}

// Navigation/detail
function goToDetail() {
  router.push({ name: 'evenement-detail', params: { id: props.event.id } })
}

function editEvent(e) {
  e?.stopPropagation()
  props.onEdit && props.onEdit(props.event)
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
.event-image-container {
  width: 100%;
  height: 180px;
  overflow: hidden;
  border-radius: var(--border-radius-large) var(--border-radius-large) 0 0;
  background: var(--surface-200);
  display: flex;
  align-items: center;
  justify-content: center;
}

.event-image {
  max-width: 100%;
  max-height: 100%;
  object-fit: cover;
}

.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
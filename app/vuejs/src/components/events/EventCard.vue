<template>
  <Card 
    class="event-card"
    :class="[
      `event-type-${event.event_type?.toLowerCase()}`,
      { 'event-full': event.is_full },
      { 'event-online': event.is_online },
      { 'event-past': isPastEvent }
    ]"
  >
    <template #content>
      <div class="event-card-content" @click="$emit('click')">
        
        <!-- Header avec type et statut -->
        <div class="event-header">
          <div class="event-type-badge">
            <i :class="eventTypeIcon" class="type-icon"></i>
            <span class="type-label">{{ eventTypeLabel }}</span>
          </div>
          
          <div class="event-badges">
            <!-- Badge en ligne -->
            <span v-if="event.is_online" class="badge badge-online">
              <i class="pi pi-globe"></i>
              En ligne
            </span>
            
            <!-- Badge complet -->
            <span v-if="event.is_full" class="badge badge-full">
              <i class="pi pi-ban"></i>
              Complet
            </span>
            
            <!-- Badge passé -->
            <span v-if="isPastEvent" class="badge badge-past">
              <i class="pi pi-history"></i>
              Terminé
            </span>
          </div>
        </div>

        <!-- Titre et description -->
        <div class="event-content">
          <h3 class="event-title">{{ event.title }}</h3>
          <p v-if="event.description" class="event-description">
            {{ truncateText(event.description, 120) }}
          </p>
        </div>

        <!-- Informations principales -->
        <div class="event-info">
          
          <!-- Date et heure -->
          <div class="info-item date-info">
            <i class="pi pi-calendar info-icon"></i>
            <div class="info-content">
              <span class="info-label">{{ formatEventDate(event.start_date) }}</span>
              <span v-if="event.end_date" class="info-sublabel">
                Jusqu'au {{ formatEventDate(event.end_date) }}
              </span>
            </div>
          </div>

          <!-- Lieu -->
          <div class="info-item location-info">
            <i :class="event.is_online ? 'pi pi-globe' : 'pi pi-map-marker'" class="info-icon"></i>
            <div class="info-content">
              <span class="info-label">
                <span v-if="event.is_online">En ligne</span>
                <span v-else-if="event.address">
                  {{ event.address.city }}, {{ event.address.postal_code }}
                </span>
                <span v-else>Lieu à préciser</span>
              </span>
            </div>
          </div>

          <!-- Organisateur -->
          <div class="info-item organizer-info">
            <i class="pi pi-user info-icon"></i>
            <div class="info-content">
              <span class="info-label">{{ event.organizer_name }}</span>
              <span class="info-sublabel">{{ organizerTypeLabel }}</span>
            </div>
          </div>

          <!-- Jeux associés -->
          <div v-if="event.games && event.games.length" class="info-item games-info">
            <i class="pi pi-clone info-icon"></i>
            <div class="info-content">
              <div class="games-list">
                <span 
                  v-for="(game, index) in event.games.slice(0, 2)" 
                  :key="game.id"
                  class="game-tag"
                >
                  {{ game.name }}
                </span>
                <span v-if="event.games.length > 2" class="games-more">
                  +{{ event.games.length - 2 }} autre{{ event.games.length - 2 > 1 ? 's' : '' }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Participants et prix -->
        <div class="event-stats">
          <div class="stat-item participants-stat">
            <i class="pi pi-users stat-icon"></i>
            <span class="stat-value">
              {{ event.current_participants }}{{ event.max_participants ? `/${event.max_participants}` : '' }}
            </span>
            <span class="stat-label">participants</span>
          </div>
          
          <div v-if="event.tournament && event.tournament.prize_pool" class="stat-item prize-stat">
            <i class="pi pi-trophy stat-icon"></i>
            <span class="stat-value">{{ event.tournament.prize_pool }}€</span>
            <span class="stat-label">prix</span>
          </div>
          
          <div v-if="event.entry_fee" class="stat-item fee-stat">
            <i class="pi pi-money-bill stat-icon"></i>
            <span class="stat-value">{{ event.entry_fee }}€</span>
            <span class="stat-label">entrée</span>
          </div>
        </div>

        <!-- Actions -->
        <div class="event-actions" @click.stop>
          
          <!-- Inscription/Désinscription -->
          <template v-if="!isPastEvent">
            <Button 
              v-if="canRegister"
              label="S'inscrire"
              icon="pi pi-calendar-plus"
              class="emerald-button primary register-btn"
              :disabled="event.is_full || !event.can_register"
              @click="$emit('register', event.id)"
            />
            
            <Button 
              v-else-if="isUserRegistered"
              label="Se désinscrire"
              icon="pi pi-calendar-minus"
              class="emerald-outline-btn cancel unregister-btn"
              @click="$emit('unregister', event.id)"
            />
            
            <Button 
              v-else-if="!authStore.isAuthenticated"
              label="Connexion requise"
              icon="pi pi-sign-in"
              class="auth-required-btn"
              outlined
              disabled
            />
          </template>
          
          <!-- Voir détails -->
          <Button 
            label="Voir détails"
            icon="pi pi-eye"
            class="details-btn"
            outlined
            @click="$emit('click')"
          />
        </div>
      </div>
    </template>
  </Card>
</template>

<script setup>
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

// ============= PROPS =============

const props = defineProps({
  event: {
    type: Object,
    required: true
  }
})

// ============= EMITS =============

defineEmits(['click', 'register', 'unregister'])

// ============= SETUP =============

const authStore = useAuthStore()

// ============= COMPUTED =============

const eventTypeIcon = computed(() => {
  switch (props.event.event_type) {
    case 'TOURNOI':
      return 'pi pi-trophy'
    case 'AVANT_PREMIERE':
      return 'pi pi-star'
    case 'RENCONTRE':
      return 'pi pi-users'
    case 'GENERIQUE':
    default:
      return 'pi pi-calendar'
  }
})

const eventTypeLabel = computed(() => {
  switch (props.event.event_type) {
    case 'TOURNOI':
      return 'Tournoi'
    case 'AVANT_PREMIERE':
      return 'Avant-première'
    case 'RENCONTRE':
      return 'Rencontre'
    case 'GENERIQUE':
    default:
      return 'Événement'
  }
})

const organizerTypeLabel = computed(() => {
  switch (props.event.organizer_type) {
    case 'SHOP':
      return 'Boutique'
    case 'USER':
    default:
      return 'Organisateur'
  }
})

const isPastEvent = computed(() => {
  if (!props.event.start_date) return false
  return new Date(props.event.start_date) < new Date()
})

const canRegister = computed(() => {
  return !isPastEvent.value && 
         authStore.isAuthenticated && 
         props.event.can_register && 
         !props.event.is_full &&
         !isUserRegistered.value
})

const isUserRegistered = computed(() => {
  // Cette logique sera implémentée quand on aura les données d'inscription
  // Pour l'instant, on retourne false
  return false
})

// ============= METHODS =============

const truncateText = (text, maxLength) => {
  if (!text) return ''
  if (text.length <= maxLength) return text
  return text.substring(0, maxLength).trim() + '...'
}

const formatEventDate = (dateString) => {
  if (!dateString) return ''
  
  const date = new Date(dateString)
  const now = new Date()
  
  // Options de formatage
  const dateOptions = {
    weekday: 'short',
    day: 'numeric',
    month: 'short',
    year: date.getFullYear() !== now.getFullYear() ? 'numeric' : undefined
  }
  
  const timeOptions = {
    hour: '2-digit',
    minute: '2-digit'
  }
  
  const formattedDate = date.toLocaleDateString('fr-FR', dateOptions)
  const formattedTime = date.toLocaleTimeString('fr-FR', timeOptions)
  
  return `${formattedDate} à ${formattedTime}`
}
</script>

<style scoped>
/* === EVENT CARD === */

.event-card {
  border-radius: var(--border-radius-large) !important;
  box-shadow: var(--shadow-medium) !important;
  border: 1px solid var(--surface-200) !important;
  transition: all var(--transition-medium) !important;
  overflow: hidden !important;
  height: 100%;
  cursor: pointer;
}

.event-card:hover {
  transform: translateY(-4px) !important;
  box-shadow: var(--shadow-large) !important;
}

.event-card-content {
  padding: 1.5rem;
  height: 100%;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

/* === HEADER === */

.event-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 0.5rem;
}

.event-type-badge {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.375rem 0.75rem;
  border-radius: 20px;
  font-size: 0.875rem;
  font-weight: 600;
}

.type-icon {
  font-size: 1rem;
}

/* Types d'événements - couleurs spécifiques */
.event-type-tournoi .event-type-badge {
  background: rgba(245, 158, 11, 0.1);
  color: #d97706;
  border: 1px solid rgba(245, 158, 11, 0.2);
}

.event-type-avant_premiere .event-type-badge {
  background: rgba(139, 69, 19, 0.1);
  color: #8b4513;
  border: 1px solid rgba(139, 69, 19, 0.2);
}

.event-type-rencontre .event-type-badge {
  background: rgba(59, 130, 246, 0.1);
  color: #2563eb;
  border: 1px solid rgba(59, 130, 246, 0.2);
}

.event-type-generique .event-type-badge {
  background: rgba(107, 114, 128, 0.1);
  color: #4b5563;
  border: 1px solid rgba(107, 114, 128, 0.2);
}

.event-badges {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  align-items: flex-end;
}

.badge {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.25rem 0.5rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.badge-online {
  background: rgba(34, 197, 94, 0.1);
  color: #16a34a;
  border: 1px solid rgba(34, 197, 94, 0.2);
}

.badge-full {
  background: rgba(239, 68, 68, 0.1);
  color: #dc2626;
  border: 1px solid rgba(239, 68, 68, 0.2);
}

.badge-past {
  background: rgba(107, 114, 128, 0.1);
  color: #6b7280;
  border: 1px solid rgba(107, 114, 128, 0.2);
}

/* === CONTENU === */

.event-content {
  flex: 1;
}

.event-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0 0 0.5rem 0;
  line-height: 1.3;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.event-description {
  font-size: 0.9rem;
  color: var(--text-secondary);
  line-height: 1.5;
  margin: 0;
}

/* === INFORMATIONS === */

.event-info {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin: 1rem 0;
}

.info-item {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
}

.info-icon {
  font-size: 1rem;
  color: var(--primary);
  margin-top: 0.125rem;
  flex-shrink: 0;
}

.info-content {
  flex: 1;
  min-width: 0;
}

.info-label {
  display: block;
  font-size: 0.9rem;
  font-weight: 500;
  color: var(--text-primary);
  line-height: 1.3;
}

.info-sublabel {
  display: block;
  font-size: 0.8rem;
  color: var(--text-secondary);
  margin-top: 0.125rem;
}

.games-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.375rem;
}

.game-tag {
  background: var(--surface-200);
  color: var(--text-primary);
  padding: 0.25rem 0.5rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 500;
}

.games-more {
  color: var(--text-secondary);
  font-size: 0.75rem;
  font-style: italic;
  padding: 0.25rem 0;
}

/* === STATISTIQUES === */

.event-stats {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 0;
  border-top: 1px solid var(--surface-200);
  border-bottom: 1px solid var(--surface-200);
}

.stat-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
}

.stat-icon {
  font-size: 1.125rem;
  color: var(--primary);
}

.stat-value {
  font-size: 0.875rem;
  font-weight: 700;
  color: var(--text-primary);
  line-height: 1;
}

.stat-label {
  font-size: 0.75rem;
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* === ACTIONS === */

.event-actions {
  display: flex;
  gap: 0.75rem;
  margin-top: auto;
}

:deep(.register-btn) {
  flex: 1;
  background: var(--primary) !important;
  border: none !important;
  color: white !important;
  font-weight: 600 !important;
  padding: 0.75rem 1rem !important;
  border-radius: var(--border-radius) !important;
  transition: all var(--transition-fast) !important;
}

:deep(.register-btn:hover:not(:disabled)) {
  background: var(--primary-dark) !important;
  transform: translateY(-1px) !important;
  box-shadow: 0 4px 12px rgba(38, 166, 154, 0.3) !important;
}

:deep(.unregister-btn) {
  flex: 1;
  background: transparent !important;
  border: 2px solid #ef4444 !important;
  color: #ef4444 !important;
  font-weight: 500 !important;
  padding: 0.75rem 1rem !important;
  border-radius: var(--border-radius) !important;
  transition: all var(--transition-fast) !important;
}

:deep(.unregister-btn:hover) {
  background: #ef4444 !important;
  color: white !important;
  transform: translateY(-1px) !important;
}

:deep(.details-btn) {
  background: transparent !important;
  border: 2px solid var(--surface-400) !important;
  color: var(--text-secondary) !important;
  font-weight: 500 !important;
  padding: 0.75rem 1rem !important;
  border-radius: var(--border-radius) !important;
  transition: all var(--transition-fast) !important;
  min-width: 120px;
}

:deep(.details-btn:hover) {
  border-color: var(--primary) !important;
  color: var(--primary) !important;
  background: rgba(38, 166, 154, 0.1) !important;
}

:deep(.auth-required-btn) {
  flex: 1;
  border: 2px solid var(--surface-300) !important;
  color: var(--text-secondary) !important;
  cursor: not-allowed !important;
  opacity: 0.6 !important;
}

/* === ÉTATS SPÉCIAUX === */

.event-full {
  opacity: 0.8;
}

.event-past {
  opacity: 0.7;
}

.event-past .event-title {
  color: var(--text-secondary);
}

/* === RESPONSIVE === */

@media (max-width: 768px) {
  .event-card-content {
    padding: 1rem;
  }
  
  .event-header {
    flex-direction: column;
    gap: 0.75rem;
    align-items: flex-start;
  }
  
  .event-badges {
    flex-direction: row;
    align-items: flex-start;
  }
  
  .event-stats {
    flex-wrap: wrap;
    gap: 1rem;
    justify-content: center;
  }
  
  .event-actions {
    flex-direction: column;
  }
  
  :deep(.details-btn) {
    order: -1;
  }
}

@media (max-width: 480px) {
  .event-title {
    font-size: 1.125rem;
  }
  
  .info-item {
    gap: 0.5rem;
  }
  
  .event-stats {
    padding: 0.75rem 0;
  }
  
  .stat-item {
    min-width: 0;
  }
  
  .stat-value {
    font-size: 0.8rem;
  }
  
  .stat-label {
    font-size: 0.7rem;
  }
}
</style>
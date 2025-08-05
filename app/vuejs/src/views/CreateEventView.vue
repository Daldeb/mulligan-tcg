<template>
  <div class="create-event-page">
    <div class="container">
      
      <!-- Header avec breadcrumb -->
      <div class="page-header">
        <div class="breadcrumb">
          <router-link to="/evenements" class="breadcrumb-link">
            <i class="pi pi-calendar"></i>
            Événements
          </router-link>
          <i class="pi pi-chevron-right breadcrumb-separator"></i>
          <span class="breadcrumb-current">
            {{ isEditMode ? 'Modifier' : 'Créer' }} {{ eventTypeLabel }}
          </span>
        </div>
        
        <h1 class="page-title">
          <i :class="eventTypeIcon" class="title-icon"></i>
          {{ isEditMode ? 'Modifier' : 'Créer un' }} {{ eventTypeLabel }}
        </h1>
        
        <p class="page-description">
          {{ getTypeDescription() }}
        </p>
      </div>

      <!-- Formulaire -->
      <form @submit.prevent="handleSubmit" class="event-form">
        
        <!-- Section 1: Informations de base -->
        <Card class="form-section">
          <template #title>
            <i class="pi pi-info-circle section-icon"></i>
            Informations générales
          </template>
          <template #content>
            <div class="form-grid">
              
              <!-- Titre -->
              <div class="form-group full-width">
                <label for="title" class="form-label required">
                  Titre de l'événement
                </label>
                <InputText
                  id="title"
                  v-model="formData.title"
                  placeholder="Donnez un nom à votre événement..."
                  class="form-input"
                  :class="{ 'p-invalid': errors.title }"
                  :disabled="isEditMode"
                />
                <small v-if="errors.title" class="form-error">
                  {{ errors.title }}
                </small>
              </div>

              <!-- Description -->
              <div class="form-group full-width">
                <label for="description" class="form-label">
                  Description
                </label>
                <Textarea
                  id="description"
                  v-model="formData.description"
                  placeholder="Décrivez votre événement en détail..."
                  class="form-textarea"
                  :class="{ 'p-invalid': errors.description }"
                  rows="4"
                  :maxlength="2000"
                />
                <small class="form-help">
                  {{ formData.description?.length || 0 }}/2000 caractères
                </small>
                <small v-if="errors.description" class="form-error">
                  {{ errors.description }}
                </small>
              </div>

            </div>
          </template>
        </Card>

        <!-- Section 2: Planning -->
        <Card class="form-section">
          <template #title>
            <i class="pi pi-calendar section-icon"></i>
            Planning
          </template>
          <template #content>
            <div class="form-grid">

              <!-- Date de début -->
              <div class="form-group">
                <label for="start_date" class="form-label required">
                  Date et heure de début
                </label>
                <Calendar
                  id="start_date"
                  v-model="formData.start_date"
                  showTime
                  hourFormat="24"
                  :showIcon="true"
                  placeholder="Sélectionnez la date de début"
                  class="form-calendar"
                  :class="{ 'p-invalid': errors.start_date }"
                  :minDate="minDate"
                />
                <small v-if="errors.start_date" class="form-error">
                  {{ errors.start_date }}
                </small>
              </div>

              <!-- Date de fin -->
              <div class="form-group">
                <label for="end_date" class="form-label">
                  Date et heure de fin (optionnelle)
                </label>
                <Calendar
                  id="end_date"
                  v-model="formData.end_date"
                  showTime
                  hourFormat="24"
                  :showIcon="true"
                  placeholder="Sélectionnez la date de fin"
                  class="form-calendar"
                  :class="{ 'p-invalid': errors.end_date }"
                  :minDate="formData.start_date || minDate"
                />
                <small class="form-help">
                  Si non renseignée, l'événement se termine dans la journée
                </small>
                <small v-if="errors.end_date" class="form-error">
                  {{ errors.end_date }}
                </small>
              </div>

              <!-- Date limite d'inscription -->
              <div class="form-group">
                <label for="registration_deadline" class="form-label">
                  Date limite d'inscription (optionnelle)
                </label>
                <Calendar
                  id="registration_deadline"
                  v-model="formData.registration_deadline"
                  showTime
                  hourFormat="24"
                  :showIcon="true"
                  placeholder="Limite pour les inscriptions"
                  class="form-calendar"
                  :class="{ 'p-invalid': errors.registration_deadline }"
                  :minDate="new Date()"
                  :maxDate="formData.start_date"
                />
                <small class="form-help">
                  Les inscriptions se fermeront automatiquement à cette date
                </small>
                <small v-if="errors.registration_deadline" class="form-error">
                  {{ errors.registration_deadline }}
                </small>
              </div>

            </div>
          </template>
        </Card>

        <!-- Section 3: Participants -->
        <Card class="form-section">
          <template #title>
            <i class="pi pi-users section-icon"></i>
            Participants
          </template>
          <template #content>
            <div class="form-grid">
              
              <!-- Limite de participants -->
              <div class="form-group">
                <div class="form-checkbox-group">
                  <Checkbox
                    id="unlimited_participants"
                    v-model="unlimitedParticipants"
                    :binary="true"
                    @change="handleUnlimitedParticipantsChange"
                  />
                  <label for="unlimited_participants" class="checkbox-label">
                    Nombre de participants illimité
                  </label>
                </div>
              </div>

              <!-- Nombre max si limité -->
              <div v-if="!unlimitedParticipants" class="form-group">
                <label for="max_participants" class="form-label required">
                  Nombre maximum de participants
                </label>
                <InputNumber
                  id="max_participants"
                  v-model="formData.max_participants"
                  :min="1"
                  :max="1000"
                  placeholder="Ex: 50"
                  class="form-input"
                  :class="{ 'p-invalid': errors.max_participants }"
                />
                <small v-if="errors.max_participants" class="form-error">
                  {{ errors.max_participants }}
                </small>
              </div>

            </div>
          </template>
        </Card>

        <!-- Section 4: Localisation -->
        <Card class="form-section">
          <template #title>
            <i class="pi pi-map-marker section-icon"></i>
            Localisation
          </template>
          <template #content>
            <div class="form-grid">
              
              <!-- Type de localisation -->
              <div class="form-group full-width">
                <label class="form-label required">Format de l'événement</label>
                <div class="location-toggle">
                  <div class="toggle-options">
                    <div 
                      class="toggle-option"
                      :class="{ active: !formData.is_online }"
                      @click="setLocationType(false)"
                    >
                      <i class="pi pi-map-marker"></i>
                      <span>Présentiel</span>
                      <small>Événement physique avec une adresse</small>
                    </div>
                    <div 
                      class="toggle-option"
                      :class="{ active: formData.is_online }"
                      @click="setLocationType(true)"
                    >
                      <i class="pi pi-globe"></i>
                      <span>Virtuel</span>
                      <small>Événement en ligne avec un lien</small>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Adresse (si présentiel) -->
              <div v-if="!formData.is_online" class="form-group full-width">
                <label for="address" class="form-label required">
                  Adresse de l'événement
                </label>
                <AddressAutocomplete
                  v-model="formData.address"
                  placeholder="Rechercher une adresse..."
                  class="form-address"
                  :class="{ 'p-invalid': errors.address }"
                  @update:modelValue="handleAddressChange"
                />
                <small v-if="errors.address" class="form-error">
                  {{ errors.address }}
                </small>
              </div>

              <!-- URL Stream (si virtuel) -->
              <div v-if="formData.is_online" class="form-group full-width">
                <label for="stream_url" class="form-label">
                  Lien de l'événement (optionnel)
                </label>
                <InputText
                  id="stream_url"
                  v-model="formData.stream_url"
                  placeholder="https://twitch.tv/... ou https://discord.gg/..."
                  class="form-input"
                  :class="{ 'p-invalid': errors.stream_url }"
                />
                <small class="form-help">
                  Lien Twitch, Discord, YouTube, ou autre plateforme
                </small>
                <small v-if="errors.stream_url" class="form-error">
                  {{ errors.stream_url }}
                </small>
              </div>

            </div>
          </template>
        </Card>

        <!-- Section 5: Jeu (pour TOURNOI et AVANT_PREMIERE) -->
        <Card v-if="requiresGame" class="form-section">
          <template #title>
            <i class="pi pi-clone section-icon"></i>
            Jeu associé
          </template>
          <template #content>
            <div class="form-grid">
              
              <!-- Sélection du jeu -->
              <div class="form-group">
                <label for="game" class="form-label required">
                  Jeu concerné
                </label>
                <Dropdown
                  id="game"
                  v-model="formData.selected_game"
                  :options="availableGames"
                  optionLabel="name"
                  optionValue="id"
                  placeholder="Sélectionnez un jeu"
                  class="form-dropdown"
                  :class="{ 'p-invalid': errors.selected_game }"
                  @change="handleGameChange"
                />
                <small v-if="errors.selected_game" class="form-error">
                  {{ errors.selected_game }}
                </small>
              </div>

            </div>
          </template>
        </Card>

        <!-- Section 6: Règles (pour TOURNOI et AVANT_PREMIERE) -->
        <Card v-if="requiresRules" class="form-section">
          <template #title>
            <i class="pi pi-book section-icon"></i>
            Règles et informations
          </template>
          <template #content>
            <div class="form-grid">
              
              <!-- Règles -->
              <div class="form-group full-width">
                <label for="rules" class="form-label">
                  Règles spécifiques
                </label>
                <Textarea
                  id="rules"
                  v-model="formData.rules"
                  placeholder="Décrivez les règles particulières de votre événement..."
                  class="form-textarea"
                  rows="4"
                  :maxlength="1000"
                />
                <small class="form-help">
                  {{ formData.rules?.length || 0 }}/1000 caractères
                </small>
                <small v-if="errors.rules" class="form-error">
                  {{ errors.rules }}
                </small>
              </div>

            </div>
          </template>
        </Card>

        <!-- Actions -->
        <div class="form-actions">
          <div class="actions-left">
            <Button
              label="Annuler"
              icon="pi pi-times"
              class="cancel-btn"
              outlined
              @click="handleCancel"
            />
          </div>
          
          <div class="actions-right">
            <Button
              label="Sauvegarder en brouillon"
              icon="pi pi-save"
              class="draft-btn"
              outlined
              @click="handleSaveDraft"
              :loading="isSubmitting"
              :disabled="!canSave"
            />
            <Button
              :label="isEditMode ? 'Soumettre les modifications' : 'Créer l\'événement'"
              icon="pi pi-check"
              class="submit-btn"
              @click="handleSubmit"
              :loading="isSubmitting"
              :disabled="!canSubmit"
            />
          </div>
        </div>

      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useEventStore } from '@/stores/events'
import { useGameFilterStore } from '@/stores/gameFilter'
import { useAuthStore } from '@/stores/auth'
import AddressAutocomplete from '@/components/form/AddressAutocomplete.vue'

// Imports PrimeVue
import Calendar from 'primevue/calendar'
import Checkbox from 'primevue/checkbox'
import InputNumber from 'primevue/inputnumber'
import Dropdown from 'primevue/dropdown'

// ============= SETUP =============

const router = useRouter()
const route = useRoute()
const eventStore = useEventStore()
const gameFilterStore = useGameFilterStore()  
const authStore = useAuthStore()

// ============= STATE =============

const isSubmitting = ref(false)
const errors = ref({})
const unlimitedParticipants = ref(true)

// Données du formulaire
const formData = ref({
  title: '',
  description: '',
  event_type: '',
  start_date: null,
  end_date: null,
  registration_deadline: null,
  max_participants: null,
  is_online: false,
  address: null,
  stream_url: '',
  selected_game: null,
  rules: ''
})

// ============= COMPUTED =============

const eventType = computed(() => {
  return route.query.type || formData.value.event_type || 'GENERIQUE'
})

const isEditMode = computed(() => {
  return !!route.query.id
})

const eventTypeLabel = computed(() => {
  switch (eventType.value) {
    case 'GENERIQUE': return 'événement générique'
    case 'RENCONTRE': return 'rencontre'
    case 'AVANT_PREMIERE': return 'avant-première'
    default: return 'événement'
  }
})

const eventTypeIcon = computed(() => {
  switch (eventType.value) {
    case 'GENERIQUE': return 'pi pi-calendar'
    case 'RENCONTRE': return 'pi pi-users'
    case 'AVANT_PREMIERE': return 'pi pi-star'
    default: return 'pi pi-calendar'
  }
})

const requiresGame = computed(() => {
  return ['TOURNOI', 'AVANT_PREMIERE'].includes(eventType.value)
})

const requiresRules = computed(() => {
  // Règles disponibles pour TOUS les types d'événements
  return true
})

const minDate = computed(() => {
  return new Date()
})

const availableGames = computed(() => {
  return gameFilterStore.availableGames || []
})

const canSave = computed(() => {
  return formData.value.title?.trim().length > 0
})

const canSubmit = computed(() => {
  return validateForm()
})

// ============= METHODS =============

const getTypeDescription = () => {
  switch (eventType.value) {
    case 'GENERIQUE':
      return 'Créez un événement personnalisé qui ne rentre pas dans les autres catégories'
    case 'RENCONTRE':
      return 'Organisez une rencontre sociale entre joueurs (Discord, meetup, etc.)'
    case 'AVANT_PREMIERE':
      return 'Présentez en avant-première un nouveau set ou contenu exclusif'
    default:
      return 'Créez votre événement personnalisé'
  }
}

const setLocationType = (isOnline) => {
  formData.value.is_online = isOnline
  if (isOnline) {
    formData.value.address = null
  } else {
    formData.value.stream_url = ''
  }
  clearFieldError('address')
  clearFieldError('stream_url')
}

const handleUnlimitedParticipantsChange = () => {
  if (unlimitedParticipants.value) {
    formData.value.max_participants = null
  }
  clearFieldError('max_participants')
}

const handleAddressChange = (address) => {
  formData.value.address = address
  clearFieldError('address')
}

const handleGameChange = () => {
  clearFieldError('selected_game')
}

const validateForm = () => {
  errors.value = {}
  
  // Titre obligatoire
  if (!formData.value.title?.trim()) {
    errors.value.title = 'Le titre est requis'
  }
  
  // Date de début obligatoire
  if (!formData.value.start_date) {
    errors.value.start_date = 'La date de début est requise'
  }
  
  // Date de fin après date de début
  if (formData.value.end_date && formData.value.start_date) {
    if (formData.value.end_date <= formData.value.start_date) {
      errors.value.end_date = 'La date de fin doit être après la date de début'
    }
  }
  
  // Adresse obligatoire si présentiel
  if (!formData.value.is_online && !formData.value.address) {
    errors.value.address = 'L\'adresse est requise pour un événement présentiel'
  }
  
  // Participants max si limité
  if (!unlimitedParticipants.value && !formData.value.max_participants) {
    errors.value.max_participants = 'Le nombre maximum de participants est requis'
  }
  
  // Jeu obligatoire pour certains types
  if (requiresGame.value && !formData.value.selected_game) {
    errors.value.selected_game = 'Le jeu est requis pour ce type d\'événement'
  }
  
  // URL valide si fournie
  if (formData.value.stream_url && !isValidUrl(formData.value.stream_url)) {
    errors.value.stream_url = 'L\'URL n\'est pas valide'
  }
  
  return Object.keys(errors.value).length === 0
}

const isValidUrl = (string) => {
  try {
    new URL(string)
    return true
  } catch (_) {
    return false
  }
}

const clearFieldError = (field) => {
  if (errors.value[field]) {
    delete errors.value[field]
  }
}

const handleCancel = () => {
  router.push({ name: 'evenements' })
}

const handleSaveDraft = async () => {
  if (!canSave.value) return
  
  isSubmitting.value = true
  try {
    const eventData = prepareEventData('DRAFT')
    
    if (isEditMode.value) {
      await eventStore.updateEvent(route.query.id, eventData)
    } else {
      await eventStore.createEvent(eventData)
    }
    
    // TODO: Toast success
    router.push({ name: 'mes-evenements' })
    
  } catch (error) {
    console.error('Erreur sauvegarde brouillon:', error)
    // TODO: Toast error
  } finally {
    isSubmitting.value = false
  }
}

const handleSubmit = async () => {
  if (!validateForm()) return
  
  isSubmitting.value = true
  try {
    const eventData = prepareEventData('PENDING_REVIEW')
    
    if (isEditMode.value) {
      await eventStore.updateEvent(route.query.id, eventData)
    } else {
      await eventStore.createEvent(eventData)
    }
    
    // TODO: Toast success
    router.push({ name: 'mes-evenements' })
    
  } catch (error) {
    console.error('Erreur soumission événement:', error)
    // TODO: Toast error
  } finally {
    isSubmitting.value = false
  }
}

const prepareEventData = (status) => {
  const data = {
    title: formData.value.title.trim(),
    description: formData.value.description?.trim() || null,
    event_type: eventType.value,
    start_date: formData.value.start_date?.toISOString(),
    end_date: formData.value.end_date?.toISOString() || null,
    registration_deadline: formData.value.registration_deadline?.toISOString() || null,
    max_participants: unlimitedParticipants.value ? null : formData.value.max_participants,
    is_online: formData.value.is_online,
    rules: formData.value.rules?.trim() || null,
    status: status
  }
  
  // Organisateur (sera géré côté serveur)
  if (authStore.user?.canActAsShop) {
    data.organizer_type = 'SHOP'
  } else {
    data.organizer_type = 'USER'
  }
  
  // Adresse ou URL selon le type
  if (formData.value.is_online) {
    data.stream_url = formData.value.stream_url?.trim() || null
  } else {
    data.address_id = formData.value.address?.id || null
  }
  
  // Jeux associés
  if (requiresGame.value && formData.value.selected_game) {
    data.game_ids = [formData.value.selected_game]
  }
  
  return data
}

const loadEventData = async () => {
  if (!isEditMode.value) return
  
  try {
    const event = await eventStore.getEvent(route.query.id)
    
    // Pré-remplir le formulaire
    formData.value = {
      title: event.title,
      description: event.description || '',
      event_type: event.event_type,
      start_date: event.start_date ? new Date(event.start_date) : null,
      end_date: event.end_date ? new Date(event.end_date) : null,
      registration_deadline: event.registration_deadline ? new Date(event.registration_deadline) : null,
      max_participants: event.max_participants,
      is_online: event.is_online,
      address: event.address || null,
      stream_url: event.stream_url || '',
      selected_game: event.games?.[0]?.id || null,
      rules: event.rules || ''
    }
    
    unlimitedParticipants.value = !event.max_participants
    
  } catch (error) {
    console.error('Erreur chargement événement:', error)
    router.push({ name: 'evenements' })
  }
}

// ============= LIFECYCLE =============

onMounted(async () => {
  // Définir le type d'événement
  formData.value.event_type = eventType.value
  
  // Charger les jeux si nécessaire
  if (requiresGame.value && !gameFilterStore.isReady) {
    await gameFilterStore.loadGames()
  }
  
  // Charger les données en mode édition
  if (isEditMode.value) {
    await loadEventData()
  }
})

// Watcher pour réinitialiser les erreurs lors de la saisie
watch(formData, () => {
  // Clear errors au fur et à mesure de la saisie
}, { deep: true })
</script>

<style scoped>
/* === CREATE EVENT PAGE === */

.create-event-page {
  min-height: calc(100vh - var(--header-height));
  background: var(--surface-gradient);
  padding: 2rem 0;
}

.container {
  max-width: 800px;
  margin: 0 auto;
  padding: 0 2rem;
}

/* === PAGE HEADER === */

.page-header {
  margin-bottom: 2rem;
}

.breadcrumb {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1rem;
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

.page-title {
  display: flex;
  align-items: center;
  gap: 1rem;
  font-size: 2rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0 0 0.5rem 0;
}

.title-icon {
  font-size: 1.5rem;
  color: var(--primary);
  background: rgba(38, 166, 154, 0.1);
  width: 50px;
  height: 50px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.page-description {
  font-size: 1rem;
  color: var(--text-secondary);
  margin: 0;
  line-height: 1.5;
}

/* === FORM === */

.event-form {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.form-section {
  border-radius: var(--border-radius-large) !important;
  box-shadow: var(--shadow-medium) !important;
}

:deep(.form-section .p-card-title) {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 1rem;
}

.section-icon {
  color: var(--primary);
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group.full-width {
  grid-column: 1 / -1;
}

.form-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--text-primary);
}

.form-label.required::after {
  content: ' *';
  color: #ef4444;
}

.form-input,
.form-textarea,
.form-dropdown,
.form-calendar {
  width: 100% !important;
}

:deep(.form-input),
:deep(.form-textarea),
:deep(.form-dropdown),
:deep(.form-calendar) {
  border: 2px solid var(--surface-300) !important;
  border-radius: var(--border-radius) !important;
  transition: all var(--transition-fast) !important;
}

:deep(.form-input:focus),
:deep(.form-textarea:focus),
:deep(.form-dropdown:not(.p-disabled).p-focus),
:deep(.form-calendar:not(.p-disabled).p-focus) {
  border-color: var(--primary) !important;
  box-shadow: 0 0 0 3px rgba(38, 166, 154, 0.1) !important;
}

:deep(.p-invalid) {
  border-color: #ef4444 !important;
}

.form-help {
  font-size: 0.75rem;
  color: var(--text-secondary);
  margin-top: 0.25rem;
}

.form-error {
  font-size: 0.75rem;
  color: #ef4444 !important;
  margin-top: 0.25rem;
}

.form-checkbox-group {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.checkbox-label {
  font-size: 0.875rem;
  color: var(--text-primary);
  cursor: pointer;
}

/* === LOCATION TOGGLE === */

.location-toggle {
  margin-top: 0.5rem;
}

.toggle-options {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.toggle-option {
  padding: 1.5rem;
  border: 2px solid var(--surface-300);
  border-radius: var(--border-radius);
  cursor: pointer;
  transition: all var(--transition-fast);
  text-align: center;
  background: white;
}

.toggle-option:hover {
  border-color: var(--primary);
  background: rgba(38, 166, 154, 0.05);
}

.toggle-option.active {
  border-color: var(--primary);
  background: rgba(38, 166, 154, 0.1);
}

.toggle-option i {
  font-size: 1.5rem;
  color: var(--primary);
  margin-bottom: 0.5rem;
  display: block;
}

.toggle-option span {
  font-size: 1rem;
  font-weight: 600;
  color: var(--text-primary);
  display: block;
  margin-bottom: 0.25rem;
}

.toggle-option small {
  font-size: 0.75rem;
  color: var(--text-secondary);
  display: block;
}

/* === FORM ACTIONS === */

.form-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 2rem 0;
  border-top: 1px solid var(--surface-200);
  margin-top: 1rem;
}

.actions-left,
.actions-right {
  display: flex;
  gap: 1rem;
}

:deep(.cancel-btn) {
  padding: 0.75rem 1.5rem !important;
  border: 2px solid var(--surface-400) !important;
  color: var(--text-secondary) !important;
  border-radius: var(--border-radius) !important;
}

:deep(.cancel-btn:hover) {
  border-color: #ef4444 !important;
  color: #ef4444 !important;
  background: rgba(239, 68, 68, 0.1) !important;
}

:deep(.draft-btn) {
  padding: 0.75rem 1.5rem !important;
  border: 2px solid var(--primary) !important;
  color: var(--primary) !important;
  border-radius: var(--border-radius) !important;
}

:deep(.draft-btn:hover:not(:disabled)) {
  background: rgba(38, 166, 154, 0.1) !important;
}

:deep(.submit-btn) {
  padding: 0.75rem 1.5rem !important;
  background: var(--primary) !important;
  border: none !important;
  color: white !important;
  font-weight: 600 !important;
  border-radius: var(--border-radius) !important;
  transition: all var(--transition-fast) !important;
}

:deep(.submit-btn:hover:not(:disabled)) {
  background: var(--primary-dark) !important;
  transform: translateY(-1px) !important;
  box-shadow: 0 4px 12px rgba(38, 166, 154, 0.3) !important;
}

:deep(.submit-btn:disabled),
:deep(.draft-btn:disabled) {
  opacity: 0.6 !important;
  cursor: not-allowed !important;
}

/* === RESPONSIVE === */

@media (max-width: 768px) {
  .container {
    padding: 0 1rem;
  }
  
  .create-event-page {
    padding: 1rem 0;
  }
  
  .page-title {
    font-size: 1.5rem;
  }
  
  .title-icon {
    width: 40px;
    height: 40px;
    font-size: 1.25rem;
  }
  
  .form-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
  
  .toggle-options {
    grid-template-columns: 1fr;
  }
  
  .toggle-option {
    padding: 1rem;
  }
  
  .form-actions {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
  
  .actions-left,
  .actions-right {
    justify-content: center;
  }
  
  .actions-right {
    flex-direction: column;
  }
}

@media (max-width: 480px) {
  .breadcrumb {
    font-size: 0.8rem;
  }
  
  .page-title {
    font-size: 1.25rem;
    gap: 0.75rem;
  }
  
  .form-section {
    margin: 0 -0.5rem;
  }
  
  :deep(.form-section .p-card-content) {
    padding: 1rem !important;
  }
  
  .toggle-option {
    padding: 0.75rem;
  }
  
  .toggle-option i {
    font-size: 1.25rem;
  }
}
</style>
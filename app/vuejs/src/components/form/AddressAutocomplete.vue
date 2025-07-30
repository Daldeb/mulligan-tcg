<template>
  <div class="address-autocomplete">
    <!-- Mode compact : Un seul champ avec autocomplétion -->
    <div v-if="mode === 'compact'" class="compact-mode">
      <div class="field-group">
        <label :for="fieldId" class="field-label">
          {{ label }}
          <span v-if="required" class="required">*</span>
        </label>
        
        <div class="autocomplete-container">
          <InputText
            :id="fieldId"
            v-model="searchQuery"
            :placeholder="placeholder"
            class="emerald-input autocomplete-input"
            :class="{ 'error': !!errors.general }"
            @input="handleSearchInput"
            @focus="showSuggestions = true"
            @blur="handleBlur"
            :disabled="isLoading"
          />
          
          <div v-if="isSearching" class="loading-indicator">
            <i class="pi pi-spin pi-spinner"></i>
          </div>
          
          <!-- Liste des suggestions -->
          <div 
            v-if="showSuggestions && suggestions.length > 0" 
            class="suggestions-dropdown"
          >
            <div
              v-for="(suggestion, index) in suggestions"
              :key="index"
              class="suggestion-item"
              :class="{ 'highlighted': highlightedIndex === index }"
              @mousedown.prevent="selectSuggestion(suggestion)"
              @mouseenter="highlightedIndex = index"
            >
              <div class="suggestion-main">{{ suggestion.label }}</div>
              <div class="suggestion-context">{{ suggestion.context }}</div>
              <div class="suggestion-score">
                Score: {{ Math.round(suggestion.score * 100) }}%
              </div>
            </div>
          </div>
          
          <!-- Message si pas de résultats -->
          <div 
            v-if="showSuggestions && searchQuery.length >= 3 && suggestions.length === 0 && !isSearching"
            class="no-results"
          >
            Aucune adresse trouvée
          </div>
        </div>
        
        <small v-if="errors.general" class="field-error">{{ errors.general }}</small>
        <small v-else-if="!hideHelp" class="field-help">
          Saisissez au moins 3 caractères pour rechercher une adresse
        </small>
      </div>
    </div>

    <!-- Mode détaillé : Champs séparés avec autocomplétion intelligente -->
    <div v-else class="detailed-mode">
      <div class="form-grid">
        <!-- Champ adresse avec autocomplétion -->
        <div class="field-group full-width">
          <label :for="`${fieldId}_street`" class="field-label">
            Adresse
            <span v-if="required" class="required">*</span>
          </label>
          
          <div class="autocomplete-container">
            <InputText
              :id="`${fieldId}_street`"
              v-model="formData.streetAddress"
              placeholder="123 Rue de la Paix..."
              class="emerald-input autocomplete-input"
              :class="{ 'error': !!errors.streetAddress }"
              @input="handleStreetInput"
              @focus="showSuggestions = true"
              @blur="handleBlur"
              :disabled="isLoading"
            />
            
            <div v-if="isSearching" class="loading-indicator">
              <i class="pi pi-spin pi-spinner"></i>
            </div>
            
            <!-- Suggestions pour adresse détaillée -->
            <div 
              v-if="showSuggestions && suggestions.length > 0" 
              class="suggestions-dropdown"
            >
              <div
                v-for="(suggestion, index) in suggestions"
                :key="index"
                class="suggestion-item"
                :class="{ 'highlighted': highlightedIndex === index }"
                @mousedown.prevent="selectDetailedSuggestion(suggestion)"
                @mouseenter="highlightedIndex = index"
              >
                <div class="suggestion-main">{{ suggestion.streetAddress }}</div>
                <div class="suggestion-secondary">
                  {{ suggestion.postalCode }} {{ suggestion.city }}
                </div>
                <div class="suggestion-context">{{ suggestion.context }}</div>
              </div>
            </div>
          </div>
          
          <small v-if="errors.streetAddress" class="field-error">{{ errors.streetAddress }}</small>
        </div>
      </div>
      
      <div class="form-grid">
        <!-- Code postal -->
        <div class="field-group">
          <label :for="`${fieldId}_postal`" class="field-label">
            Code postal
            <span v-if="required" class="required">*</span>
          </label>
          <InputText
            :id="`${fieldId}_postal`"
            v-model="formData.postalCode"
            placeholder="75001"
            maxlength="5"
            class="emerald-input"
            :class="{ 'error': !!errors.postalCode }"
            @input="handlePostalCodeInput"
            :disabled="isLoading"
          />
          <small v-if="errors.postalCode" class="field-error">{{ errors.postalCode }}</small>
        </div>
        
        <!-- Ville -->
        <div class="field-group">
          <label :for="`${fieldId}_city`" class="field-label">
            Ville
            <span v-if="required" class="required">*</span>
          </label>
          <InputText
            :id="`${fieldId}_city`"
            v-model="formData.city"
            placeholder="Paris"
            class="emerald-input"
            :class="{ 'error': !!errors.city }"
            @input="validateField('city')"
            :disabled="isLoading"
          />
          <small v-if="errors.city" class="field-error">{{ errors.city }}</small>
        </div>
      </div>
      
      <div class="form-grid">
        <!-- Pays -->
        <div class="field-group">
          <label :for="`${fieldId}_country`" class="field-label">Pays</label>
          <InputText
            :id="`${fieldId}_country`"
            v-model="formData.country"
            class="emerald-input"
            readonly
            :disabled="isLoading"
          />
        </div>
        
        <!-- 🆕 Indicateur de statut au lieu du bouton -->
        <div class="field-group">
          <div class="validation-info">
            <div v-if="!hasValue" class="validation-hint">
              <i class="pi pi-info-circle"></i>
              <span>Saisissez une adresse pour la valider automatiquement</span>
            </div>
            <div v-else-if="isValidating" class="validation-loading">
              <i class="pi pi-spin pi-spinner"></i>
              <span>Validation en cours...</span>
            </div>
            <div v-else-if="validationStatus?.type === 'success'" class="validation-success">
              <i class="pi pi-check-circle"></i>
              <span>Adresse validée ✓</span>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Indicateur de validation détaillé -->
      <div v-if="validationStatus" class="validation-status" :class="validationStatus.type">
        <i :class="validationStatus.icon"></i>
        <span>{{ validationStatus.message }}</span>
        <div v-if="validationStatus.coordinates" class="coordinates-info">
          📍 Coordonnées: {{ validationStatus.coordinates }}
        </div>
      </div>
    </div>
    
    <!-- Bouton de suppression si adresse existante -->
    <div v-if="hasValue && allowRemove" class="address-actions">
      <Button
        label="Supprimer l'adresse"
        icon="pi pi-trash"
        class="p-button-danger p-button-outlined remove-btn"
        @click="removeAddress"
        severity="danger"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, nextTick } from 'vue'
import { useToast } from 'primevue/usetoast'
import api from '../../services/api'

// Props
const props = defineProps({
  modelValue: {
    type: Object,
    default: null
  },
  mode: {
    type: String,
    default: 'detailed', // 'compact' ou 'detailed'
    validator: (value) => ['compact', 'detailed'].includes(value)
  },
  label: {
    type: String,
    default: 'Adresse'
  },
  placeholder: {
    type: String,
    default: 'Rechercher une adresse...'
  },
  required: {
    type: Boolean,
    default: false
  },
  fieldId: {
    type: String,
    default: 'address'
  },
  hideHelp: {
    type: Boolean,
    default: false
  },
  allowRemove: {
    type: Boolean,
    default: true
  }
})

// Emits
const emit = defineEmits(['update:modelValue', 'addressValidated', 'addressRemoved', 'validationError'])

// Composables
const toast = useToast()

// State
const searchQuery = ref('')
const formData = reactive({
  streetAddress: '',
  city: '',
  postalCode: '',
  country: 'France'
})

const suggestions = ref([])
const showSuggestions = ref(false)
const highlightedIndex = ref(-1)
const isSearching = ref(false)
const isValidating = ref(false)
const isLoading = ref(false)

const errors = reactive({
  general: '',
  streetAddress: '',
  city: '',
  postalCode: ''
})

const validationStatus = ref(null)
let searchTimeout = null

// Computed
const hasValue = computed(() => {
  if (props.mode === 'compact') {
    return searchQuery.value.length > 0
  }
  return formData.streetAddress || formData.city || formData.postalCode
})

const canValidate = computed(() => {
  return formData.streetAddress && formData.city && formData.postalCode
})

// MÉTHODES (AVANT LES WATCHERS)
const clearErrors = () => {
  Object.keys(errors).forEach(key => errors[key] = '')
}

const resetForm = () => {
  searchQuery.value = ''
  Object.assign(formData, {
    streetAddress: '',
    city: '',
    postalCode: '',
    country: 'France'
  })
  suggestions.value = []
  clearErrors()
  validationStatus.value = null
}

const handleSearchInput = () => {
  const query = searchQuery.value.trim()
  
  if (query.length < 3) {
    suggestions.value = []
    showSuggestions.value = false
    return
  }
  
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    searchAddresses(query)
  }, 300)
}

const handleStreetInput = () => {
  const query = formData.streetAddress.trim()
  
  if (query.length < 3) {
    suggestions.value = []
    showSuggestions.value = false
    return
  }
  
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    searchAddresses(query)
  }, 300)
}

const handlePostalCodeInput = () => {
  // Auto-complétion basée sur code postal si 5 chiffres
  if (formData.postalCode.length === 5 && /^\d{5}$/.test(formData.postalCode)) {
    searchAddresses(formData.postalCode + ' ' + formData.city)
  }
  validateField('postalCode')
}

const handleBlur = () => {
  // Délai pour permettre le clic sur suggestion
  setTimeout(() => {
    showSuggestions.value = false
    highlightedIndex.value = -1
  }, 200)
}

const searchAddresses = async (query) => {
  if (!query || query.length < 3) return
  
  isSearching.value = true
  
  try {
    const response = await api.get('/api/address/search', {
      params: { q: query, limit: 5 }
    })
    
    suggestions.value = response.data.suggestions || []
    showSuggestions.value = suggestions.value.length > 0
    highlightedIndex.value = -1
    
  } catch (error) {
    console.error('Erreur recherche adresses:', error)
    suggestions.value = []
    errors.general = 'Erreur lors de la recherche d\'adresses'
  } finally {
    isSearching.value = false
  }
}

const selectSuggestion = (suggestion) => {
  searchQuery.value = suggestion.label
  suggestions.value = []
  showSuggestions.value = false
  
  // Émettre la suggestion complète
  emit('update:modelValue', {
    streetAddress: suggestion.streetAddress,
    city: suggestion.city,
    postalCode: suggestion.postalCode,
    country: suggestion.country,
    fullAddress: suggestion.label,
    latitude: suggestion.latitude,
    longitude: suggestion.longitude
  })
  
  emit('addressValidated', suggestion)
}

const selectDetailedSuggestion = (suggestion) => {
  Object.assign(formData, {
    streetAddress: suggestion.streetAddress,
    city: suggestion.city,
    postalCode: suggestion.postalCode,
    country: suggestion.country
  })
  
  suggestions.value = []
  showSuggestions.value = false
  
  // 🆕 Auto-validation optionnelle lors de sélection
  nextTick(() => {
    validateAddress()
  })
}

const validateField = (fieldName) => {
  errors[fieldName] = ''
  
  switch (fieldName) {
    case 'streetAddress':
      if (formData.streetAddress.length < 5) {
        errors.streetAddress = 'L\'adresse doit contenir au moins 5 caractères'
      }
      break
    case 'city':
      if (formData.city.length < 2) {
        errors.city = 'Le nom de ville doit contenir au moins 2 caractères'
      }
      break
    case 'postalCode':
      if (!/^\d{5}$/.test(formData.postalCode)) {
        errors.postalCode = 'Le code postal doit contenir exactement 5 chiffres'
      }
      break
  }
}

// 🆕 Méthode publique exposée pour validation depuis le parent
const validateAddress = async () => {
  if (!canValidate.value) {
    if (props.required) {
      throw new Error('Adresse requise mais incomplète')
    }
    return { valid: true, address: null }
  }
  
  // Validation côté client
  validateField('streetAddress')
  validateField('city')
  validateField('postalCode')
  
  if (errors.streetAddress || errors.city || errors.postalCode) {
    throw new Error('Erreurs de validation dans l\'adresse')
  }
  
  isValidating.value = true
  validationStatus.value = null
  
  try {
    const response = await api.post('/api/address/validate', {
      streetAddress: formData.streetAddress,
      city: formData.city,
      postalCode: formData.postalCode,
      country: formData.country
    })
    
    const result = response.data
    
    if (result.valid) {
      const coordinates = result.normalized.latitude && result.normalized.longitude 
        ? `${result.normalized.latitude.toFixed(6)}, ${result.normalized.longitude.toFixed(6)}`
        : null
      
      validationStatus.value = {
        type: 'success',
        icon: 'pi pi-check-circle',
        message: 'Adresse validée avec succès',
        coordinates
      }
      
      // Normaliser les données
      if (result.normalized) {
        Object.assign(formData, {
          streetAddress: result.normalized.streetAddress,
          city: result.normalized.city,
          postalCode: result.normalized.postalCode
        })
      }
      
      // Émettre l'adresse validée
      const validatedAddress = {
        streetAddress: formData.streetAddress,
        city: formData.city,
        postalCode: formData.postalCode,
        country: formData.country,
        fullAddress: `${formData.streetAddress}, ${formData.postalCode} ${formData.city}`,
        latitude: result.normalized?.latitude,
        longitude: result.normalized?.longitude,
        score: result.normalized?.score
      }
      
      emit('update:modelValue', validatedAddress)
      emit('addressValidated', validatedAddress)
      
      return { valid: true, address: validatedAddress }
      
    } else {
      validationStatus.value = {
        type: 'error',
        icon: 'pi pi-times-circle',
        message: 'Adresse non trouvée ou invalide'
      }
      
      // Afficher les erreurs spécifiques
      if (result.errors) {
        Object.assign(errors, result.errors)
      }
      
      emit('validationError', result.errors)
      throw new Error('Adresse invalide')
    }
    
  } catch (error) {
    console.error('Erreur validation adresse:', error)
    validationStatus.value = {
      type: 'error',
      icon: 'pi pi-times-circle',
      message: 'Erreur lors de la validation'
    }
    
    throw error
  } finally {
    isValidating.value = false
  }
}

const removeAddress = () => {
  resetForm()
  emit('update:modelValue', null)
  emit('addressRemoved')
  
  toast.add({
    severity: 'info',
    summary: 'Adresse supprimée',
    detail: 'L\'adresse a été supprimée',
    life: 2000
  })
}

// 🆕 Exposer la méthode validateAddress pour l'usage externe
defineExpose({
  validateAddress
})

// WATCHERS (APRÈS LES MÉTHODES)
watch(() => props.modelValue, (newValue) => {
  if (newValue) {
    if (props.mode === 'compact') {
      searchQuery.value = newValue.fullAddress || newValue.label || ''
    } else {
      Object.assign(formData, {
        streetAddress: newValue.streetAddress || '',
        city: newValue.city || '',
        postalCode: newValue.postalCode || '',
        country: newValue.country || 'France'
      })
    }
  } else {
    resetForm()
  }
}, { immediate: true })

watch(formData, () => {
  clearErrors()
  validationStatus.value = null
}, { deep: true })
</script>

<style scoped>
.address-autocomplete {
  width: 100%;
}

.field-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.field-group.full-width {
  grid-column: 1 / -1;
}

.field-label {
  font-weight: 600;
  color: var(--text-primary);
  font-size: 0.9rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.required {
  color: var(--accent);
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

.autocomplete-container {
  position: relative;
}

.autocomplete-input {
  width: 100% !important;
  padding-right: 3rem !important;
}

.loading-indicator {
  position: absolute;
  right: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--primary);
}

.suggestions-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 2px solid var(--primary);
  border-top: none;
  border-radius: 0 0 var(--border-radius) var(--border-radius);
  box-shadow: var(--shadow-medium);
  z-index: 1000;
  max-height: 300px;
  overflow-y: auto;
}

.suggestion-item {
  padding: 1rem;
  border-bottom: 1px solid var(--surface-200);
  cursor: pointer;
  transition: background-color var(--transition-fast);
}

.suggestion-item:hover,
.suggestion-item.highlighted {
  background: rgba(38, 166, 154, 0.1);
}

.suggestion-item:last-child {
  border-bottom: none;
}

.suggestion-main {
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 0.25rem;
}

.suggestion-secondary {
  font-size: 0.9rem;
  color: var(--text-secondary);
  margin-bottom: 0.25rem;
}

.suggestion-context {
  font-size: 0.8rem;
  color: var(--text-secondary);
  font-style: italic;
}

.suggestion-score {
  font-size: 0.75rem;
  color: var(--primary);
  font-weight: 500;
}

.no-results {
  padding: 1rem;
  text-align: center;
  color: var(--text-secondary);
  font-style: italic;
}

/* 🆕 Styles pour l'indicateur de validation */
.validation-info {
  display: flex;
  align-items: center;
  min-height: 2.5rem;
  margin-top: 1.5rem;
}

.validation-hint,
.validation-loading,
.validation-success,
.validation-ready {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  padding: 0.5rem 0.75rem;
  border-radius: var(--border-radius);
}

.validation-hint {
  color: var(--text-secondary);
  background: rgba(84, 110, 122, 0.1);
}

.validation-loading {
  color: var(--primary);
  background: rgba(38, 166, 154, 0.1);
}

.validation-success {
  color: #16a34a;
  background: rgba(34, 197, 94, 0.1);
}

.validation-ready {
  color: #f59e0b;
  background: rgba(255, 193, 7, 0.1);
}

.validation-status {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem;
  border-radius: var(--border-radius);
  margin-top: 1rem;
}

.validation-status.success {
  background: rgba(34, 197, 94, 0.1);
  color: #16a34a;
  border: 1px solid rgba(34, 197, 94, 0.2);
}

.validation-status.error {
  background: rgba(239, 68, 68, 0.1);
  color: #dc2626;
  border: 1px solid rgba(239, 68, 68, 0.2);
}

.coordinates-info {
  font-size: 0.8rem;
  margin-top: 0.5rem;
  font-family: monospace;
}

.address-actions {
  margin-top: 1.5rem;
  padding-top: 1rem;
  border-top: 1px solid var(--surface-200);
}

.remove-btn {
  width: 100%;
}

.field-error {
  color: var(--accent);
  font-size: 0.8rem;
  font-weight: 500;
}

.field-help {
  color: var(--text-secondary);
  font-size: 0.8rem;
  font-style: italic;
}

/* Responsive */
@media (max-width: 768px) {
  .form-grid {
    grid-template-columns: 1fr;
  }
}
</style>
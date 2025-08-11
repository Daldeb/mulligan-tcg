<template>
  <div class="password-reset-page">
    <div class="container">
      <Card class="gaming-card reset-card">
        <template #content>
          <div class="reset-content">
            <div class="reset-icon-container">
              <i class="pi pi-key reset-icon"></i>
              <div class="reset-icon-glow"></div>
            </div>
            
            <h3 class="reset-title">Réinitialisation du mot de passe</h3>
            
            <!-- État de chargement -->
            <div v-if="isLoading" class="loading-state">
              <i class="pi pi-spin pi-spinner"></i>
              <p>Vérification du token...</p>
            </div>
            
            <!-- Formulaire de reset -->
            <form v-else-if="isValidToken" @submit.prevent="handleReset" class="emerald-form">
              <p class="reset-description">
                Choisissez un nouveau mot de passe pour votre compte.
              </p>
              
              <div class="field-group">
                <label for="new-password" class="field-label">Nouveau mot de passe</label>
                <div class="password-wrapper">
                  <InputText
                    id="new-password"
                    v-model="resetForm.password"
                    :type="showPassword ? 'text' : 'password'"
                    placeholder="Votre nouveau mot de passe"
                    class="emerald-input password-input"
                    :class="{ 'error': !!errors.password }"
                  />
                  <button 
                    type="button"
                    class="password-toggle"
                    @click="showPassword = !showPassword"
                  >
                    <i :class="showPassword ? 'pi pi-eye-slash' : 'pi pi-eye'"></i>
                  </button>
                </div>
                <small v-if="errors.password" class="field-error">{{ errors.password }}</small>
              </div>

              <div class="field-group">
                <label for="confirm-password" class="field-label">Confirmer le mot de passe</label>
                <div class="password-wrapper">
                  <InputText
                    id="confirm-password"
                    v-model="resetForm.confirmPassword"
                    :type="showConfirmPassword ? 'text' : 'password'"
                    placeholder="Confirmez votre mot de passe"
                    class="emerald-input password-input"
                    :class="{ 'error': !!errors.confirmPassword }"
                  />
                  <button 
                    type="button"
                    class="password-toggle"
                    @click="showConfirmPassword = !showConfirmPassword"
                  >
                    <i :class="showConfirmPassword ? 'pi pi-eye-slash' : 'pi pi-eye'"></i>
                  </button>
                </div>
                <small v-if="errors.confirmPassword" class="field-error">{{ errors.confirmPassword }}</small>
              </div>

              <!-- Erreurs globales -->
              <div v-if="globalErrors.length" class="error-message">
                <div class="error-content">
                  <i class="pi pi-exclamation-triangle error-icon"></i>
                  <div class="error-text">
                    <p v-for="(err, index) in globalErrors" :key="index">{{ err }}</p>
                  </div>
                </div>
              </div>

              <!-- Message de succès -->
              <div v-if="successMessage" class="success-message">
                <div class="success-content">
                  <i class="pi pi-check success-icon"></i>
                  <div class="success-text">
                    <p>{{ successMessage }}</p>
                  </div>
                </div>
              </div>

              <div class="reset-actions">
                <Button 
                  type="submit"
                  label="Réinitialiser le mot de passe"
                  icon="pi pi-check"
                  class="emerald-button primary"
                  :loading="isSubmitting"
                  :disabled="!!successMessage"
                />
                <Button 
                  label="Retour à l'accueil"
                  icon="pi pi-home"
                  class="secondary-button"
                  @click="goHome"
                  text
                />
              </div>
            </form>
            
            <!-- État d'erreur token -->
            <div v-else class="error-state">
              <i class="pi pi-times-circle error-big-icon"></i>
              <h4>Token invalide ou expiré</h4>
              <p>Ce lien de réinitialisation n'est plus valide. Veuillez demander un nouveau lien.</p>
              <Button 
                label="Retour à l'accueil"
                icon="pi pi-home"
                class="emerald-button primary"
                @click="goHome"
              />
            </div>
          </div>
        </template>
      </Card>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Card from 'primevue/card'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'

const route = useRoute()
const router = useRouter()
const toast = useToast()

// State
const isLoading = ref(true)
const isValidToken = ref(false)
const isSubmitting = ref(false)
const showPassword = ref(false)
const showConfirmPassword = ref(false)
const successMessage = ref('')

const resetForm = reactive({
  password: '',
  confirmPassword: ''
})

const errors = reactive({
  password: '',
  confirmPassword: ''
})

const globalErrors = ref([])

// Token depuis l'URL
const token = route.query.token

// Validation du formulaire
const validateForm = () => {
  errors.password = ''
  errors.confirmPassword = ''
  globalErrors.value = []
  
  let valid = true
  
  if (!resetForm.password || resetForm.password.length < 6) {
    errors.password = 'Le mot de passe doit contenir au moins 6 caractères'
    valid = false
  }
  
  if (resetForm.password !== resetForm.confirmPassword) {
    errors.confirmPassword = 'Les mots de passe ne correspondent pas'
    valid = false
  }
  
  return valid
}

// Handler pour le reset
const handleReset = async () => {
  if (!validateForm()) return
  
  isSubmitting.value = true
  globalErrors.value = []
  
  try {
    const response = await fetch('/api/password-reset/confirm', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        token: token,
        password: resetForm.password
      })
    })
    
    const data = await response.json()
    
    if (response.ok && data.success) {
      successMessage.value = data.message
      toast.add({ 
        severity: 'success', 
        summary: 'Succès', 
        detail: 'Mot de passe réinitialisé !',
        life: 3000 
      })
      
      // Rediriger vers l'accueil après 3 secondes
      setTimeout(() => {
        router.push('/')
      }, 3000)
    } else {
      globalErrors.value = [data.error || 'Erreur lors de la réinitialisation']
    }
  } catch (error) {
    globalErrors.value = ['Erreur serveur. Veuillez réessayer.']
  } finally {
    isSubmitting.value = false
  }
}

// Navigation
const goHome = () => {
  router.push('/')
}

// Vérifier le token au chargement
onMounted(async () => {
  if (!token) {
    isValidToken.value = false
    isLoading.value = false
    return
  }
  
  // On peut vérifier le token côté frontend en essayant le reset avec un mot de passe vide
  // Ou simplement considérer que s'il y a un token, on affiche le formulaire
  isValidToken.value = true
  isLoading.value = false
})
</script>

<style scoped>
/* === PASSWORD RESET PAGE === */

.password-reset-page {
  min-height: calc(100vh - var(--header-height));
  background: var(--surface-gradient);
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 2rem 0;
  animation: slideInUp 0.8s ease-out;
}

.container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 2rem;
  display: flex;
  justify-content: center;
}

.reset-card {
  max-width: 600px;
  width: 100%;
  position: relative;
  overflow: hidden;
}

.reset-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(135deg, #ff5722 0%, #e64a19 100%);
}

.reset-content {
  padding: 3rem 2rem;
  text-align: center;
  position: relative;
}

.reset-icon-container {
  position: relative;
  display: inline-block;
  margin-bottom: 1.5rem;
}

.reset-icon {
  font-size: 4rem;
  color: #ff5722;
  position: relative;
  z-index: 2;
  animation: iconFloat 3s ease-in-out infinite;
}

.reset-icon-glow {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 80px;
  height: 80px;
  background: radial-gradient(circle, rgba(255, 87, 34, 0.2) 0%, transparent 70%);
  border-radius: 50%;
  animation: glowPulse 2s ease-in-out infinite;
}

@keyframes iconFloat {
  0%, 100% { transform: translateY(0px); }
  50% { transform: translateY(-10px); }
}

@keyframes glowPulse {
  0%, 100% { opacity: 0.5; transform: translate(-50%, -50%) scale(1); }
  50% { opacity: 0.8; transform: translate(-50%, -50%) scale(1.1); }
}

.reset-title {
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0 0 1rem 0;
  background: linear-gradient(135deg, #ff5722 0%, #e64a19 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.reset-description {
  color: var(--text-secondary);
  margin: 0 0 2rem 0;
  line-height: 1.6;
  font-size: 1.1rem;
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  padding: 2rem;
  color: var(--text-secondary);
}

.loading-state i {
  font-size: 2rem;
  color: var(--primary);
}

.error-state {
  text-align: center;
  padding: 2rem;
}

.error-big-icon {
  font-size: 4rem;
  color: var(--accent);
  margin-bottom: 1rem;
}

.error-state h4 {
  color: var(--text-primary);
  margin: 0 0 1rem 0;
  font-size: 1.5rem;
}

.error-state p {
  color: var(--text-secondary);
  margin: 0 0 2rem 0;
  line-height: 1.6;
}

/* Formulaire */
.emerald-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  text-align: left;
}

.field-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.field-label {
  font-weight: 500;
  color: var(--text-primary);
  font-size: 0.9rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

:deep(.emerald-input) {
  width: 100% !important;
  padding: 0.875rem 1rem !important;
  border: 2px solid var(--surface-300) !important;
  border-radius: var(--border-radius) !important;
  background: var(--surface) !important;
  color: var(--text-primary) !important;
  font-size: 0.95rem !important;
  transition: all var(--transition-fast) !important;
}

:deep(.emerald-input:focus) {
  border-color: #ff5722 !important;
  box-shadow: 0 0 0 3px rgba(255, 87, 34, 0.1) !important;
  background: white !important;
}

:deep(.emerald-input.error) {
  border-color: var(--accent) !important;
  box-shadow: 0 0 0 3px rgba(255, 87, 34, 0.1) !important;
}

/* Password fields */
.password-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.password-input {
  padding-right: 3rem !important;
}

.password-toggle {
  position: absolute;
  right: 1rem;
  background: none;
  border: none;
  color: var(--text-secondary);
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 4px;
  transition: color var(--transition-fast);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10;
}

.password-toggle:hover {
  color: #ff5722;
}

/* Messages */
.field-error {
  color: var(--accent);
  font-size: 0.8rem;
  font-weight: 500;
  margin-top: 0.25rem;
}

.error-message {
  background: linear-gradient(135deg, rgba(255, 87, 34, 0.1) 0%, rgba(255, 87, 34, 0.05) 100%);
  border: 1px solid rgba(255, 87, 34, 0.3);
  border-radius: var(--border-radius);
  padding: 1rem;
}

.error-content {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
}

.error-icon {
  color: var(--accent);
  font-size: 1.1rem;
  margin-top: 0.125rem;
  flex-shrink: 0;
}

.error-text p {
  color: var(--accent-dark);
  font-size: 0.875rem;
  margin: 0;
  line-height: 1.4;
  font-weight: 500;
}

.success-message {
  background: linear-gradient(135deg, rgba(76, 175, 80, 0.1) 0%, rgba(76, 175, 80, 0.05) 100%);
  border: 1px solid rgba(76, 175, 80, 0.3);
  border-radius: var(--border-radius);
  padding: 1rem;
}

.success-content {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
}

.success-icon {
  color: #4caf50;
  font-size: 1.1rem;
  margin-top: 0.125rem;
  flex-shrink: 0;
}

.success-text p {
  color: #2e7d32;
  font-size: 0.875rem;
  margin: 0;
  line-height: 1.4;
  font-weight: 500;
}

/* Boutons */
.reset-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
  margin-top: 2rem;
  flex-wrap: wrap;
}

:deep(.emerald-button) {
  padding: 1rem 2rem !important;
  font-size: 1rem !important;
  font-weight: 600 !important;
  border-radius: 12px !important;
  transition: all var(--transition-medium) !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  gap: 0.5rem !important;
}

:deep(.emerald-button.primary) {
  background: linear-gradient(135deg, #ff5722 0%, #e64a19 100%) !important;
  border: none !important;
  color: white !important;
}

:deep(.emerald-button.primary:hover) {
  transform: translateY(-2px) scale(1.02) !important;
  box-shadow: 0 8px 25px rgba(255, 87, 34, 0.3) !important;
}

:deep(.secondary-button) {
  color: var(--text-secondary) !important;
  padding: 1rem 2rem !important;
  font-weight: 500 !important;
  border-radius: 12px !important;
  transition: all var(--transition-medium) !important;
}

:deep(.secondary-button:hover) {
  color: #ff5722 !important;
  background: rgba(255, 87, 34, 0.1) !important;
  transform: translateY(-1px) !important;
}

/* Animations */
@keyframes slideInUp {
  from {
    opacity: 0;
    transform: translateY(40px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Responsive */
@media (max-width: 768px) {
  .password-reset-page {
    padding: 1rem 0;
  }
  
  .container {
    padding: 0 1rem;
  }
  
  .reset-content {
    padding: 2rem 1.5rem;
  }
  
  .reset-title {
    font-size: 1.5rem;
  }
  
  .reset-actions {
    flex-direction: column;
    gap: 0.75rem;
  }
  
  :deep(.emerald-button) {
    width: 100% !important;
  }
}
</style>
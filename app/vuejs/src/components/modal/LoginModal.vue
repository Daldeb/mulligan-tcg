<template>
  <div v-if="isOpen" class="modal-overlay" @click.self="closeModal">
    <div class="modal-container">
      
      <!-- Header simple -->
      <div class="modal-header">
        <h2 class="modal-title">MULLIGAN TCG</h2>
        <button @click="closeModal" class="close-button">
          ×
        </button>
      </div>

      <!-- Contenu -->
      <div class="modal-body">
        
        <!-- Étape : Vérification email -->
        <div v-if="showVerificationStep" class="verification-step">
          <h3>Vérifiez votre email</h3>
          <p>Un email de vérification a été envoyé à <strong>{{ verificationEmail }}</strong></p>
          <p class="subtitle">Cliquez sur le lien dans l'email pour activer votre compte.</p>
          
          <div class="button-group">
            <Button
              label="Renvoyer l'email"
              class="p-button-outlined"
              @click="handleResendVerification"
              :loading="authStore.isLoading"
            />
            <Button
              label="Changer d'email"
              class="p-button-text"
              @click="showVerificationStep = false; currentStep = 'register'"
            />
          </div>
          
          <p class="link-text">
            Déjà vérifié ? 
            <button @click="showVerificationStep = false; currentStep = 'login'" class="text-link">
              Se connecter
            </button>
          </p>
        </div>

        <!-- Étape : Email vérifié -->
        <div v-else-if="showSuccessStep" class="success-step">
          <h3>Email vérifié !</h3>
          <p>Votre compte est maintenant actif. Vous pouvez vous connecter.</p>
          
          <Button
            label="Se connecter"
            class="primary-button"
            @click="showSuccessStep = false; currentStep = 'login'"
          />
        </div>

        <!-- Étape : Mot de passe oublié -->
        <div v-else-if="currentStep === 'forgot-password'" class="forgot-step">
          <h3>Mot de passe oublié</h3>
          <p>Entrez votre email pour recevoir un lien de réinitialisation</p>
          
          <div class="field-group">
            <label>Email</label>
            <InputText
              v-model="forgotEmail"
              type="email"
              placeholder="votre@email.com"
              class="clean-input"
            />
          </div>
          
          <Button
            @click="handleForgotPassword"
            :loading="authStore.isLoading"
            label="Envoyer le lien"
            class="primary-button"
          />
          
          <p class="link-text">
            <button @click="currentStep = 'login'" class="text-link">
              Retour à la connexion
            </button>
          </p>
        </div>

        <!-- Étapes principales : Login/Register -->
        <div v-else class="main-form">
          
          <!-- Tabs -->
          <div class="tabs">
            <button 
              @click="currentStep = 'login'"
              :class="['tab', { 'active': currentStep === 'login' }]"
            >
              Connexion
            </button>
            <button 
              @click="currentStep = 'register'"
              :class="['tab', { 'active': currentStep === 'register' }]"
            >
              Inscription
            </button>
          </div>

          <!-- Formulaire -->
          <div class="form">
            
            <!-- Pseudo (inscription uniquement) -->
            <div v-if="currentStep === 'register'" class="field-group">
              <label>Pseudo</label>
              <InputText
                v-model="formData.pseudo"
                placeholder="Votre pseudo"
                class="clean-input"
              />
            </div>

            <!-- Email -->
            <div class="field-group">
              <label>Email</label>
              <InputText
                v-model="formData.email"
                type="email"
                placeholder="votre@email.com"
                class="clean-input"
              />
            </div>

            <!-- Mot de passe -->
            <div class="field-group">
              <label>Mot de passe</label>
              <div class="custom-password-field">
                <InputText
                  v-model="formData.password"
                  :type="showPassword ? 'text' : 'password'"
                  placeholder="Votre mot de passe"
                  class="clean-input"
                />
                <button 
                  type="button"
                  class="password-toggle-btn"
                  @click="showPassword = !showPassword"
                >
                  <i :class="showPassword ? 'pi pi-eye-slash' : 'pi pi-eye'"></i>
                </button>
              </div>
            </div>

            <!-- Confirmation mot de passe (inscription) -->
            <div v-if="currentStep === 'register'" class="field-group">
              <label>Confirmer le mot de passe</label>
              <div class="custom-password-field">
                <InputText
                  v-model="formData.confirmPassword"
                  :type="showConfirmPassword ? 'text' : 'password'"
                  placeholder="Confirmez votre mot de passe"
                  class="clean-input"
                />
                <button 
                  type="button"
                  class="password-toggle-btn"
                  @click="showConfirmPassword = !showConfirmPassword"
                >
                  <i :class="showConfirmPassword ? 'pi pi-eye-slash' : 'pi pi-eye'"></i>
                </button>
              </div>
            </div>

            <!-- Champs optionnels (inscription) -->
            <div v-if="currentStep === 'register'" class="optional-fields">
              <div class="field-row">
                <div class="field-group">
                  <label>Prénom (optionnel)</label>
                  <InputText
                    v-model="formData.firstName"
                    placeholder="Prénom"
                    class="clean-input"
                  />
                </div>
                <div class="field-group">
                  <label>Nom (optionnel)</label>
                  <InputText
                    v-model="formData.lastName"
                    placeholder="Nom"
                    class="clean-input"
                  />
                </div>
              </div>
            </div>

            <!-- Mot de passe oublié (connexion) -->
            <div v-if="currentStep === 'login'" class="forgot-link-container">
              <button 
                type="button" 
                class="text-link"
                @click="currentStep = 'forgot-password'"
              >
                Mot de passe oublié ?
              </button>
            </div>

            <!-- Bouton principal -->
            <Button
              @click="handleSubmit"
              :loading="authStore.isLoading"
              :label="currentStep === 'login' ? 'Se connecter' : 'S\'inscrire'"
              class="primary-button"
            />
          </div>

          <!-- Basculer entre login/register -->
          <div class="switch-mode">
            <span v-if="currentStep === 'login'">
              Pas encore de compte ?
              <button @click="currentStep = 'register'" class="text-link">
                Inscrivez-vous
              </button>
            </span>
            <span v-else>
              Déjà un compte ?
              <button @click="currentStep = 'login'" class="text-link">
                Connectez-vous
              </button>
            </span>
          </div>
        </div>

      </div>
    </div>

    <Toast />
  </div>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '@/stores/auth' // 👈 Import du store

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  initialMode: {
    type: String,
    default: 'login'
  }
})

const emit = defineEmits(['update:modelValue', 'login-success', 'registration-success'])

const router = useRouter()
const route = useRoute()
const toast = useToast()
const authStore = useAuthStore() // 👈 Utilisation du store

// State
const isOpen = ref(props.modelValue)
const currentStep = ref(props.initialMode)
const showVerificationStep = ref(false)
const showSuccessStep = ref(false)
const verificationEmail = ref('')
const forgotEmail = ref('')
const showPassword = ref(false)
const showConfirmPassword = ref(false)

const formData = reactive({
  email: '',
  password: '',
  confirmPassword: '',
  pseudo: '',
  firstName: '',
  lastName: ''
})

// Methods
const closeModal = () => {
  isOpen.value = false
  emit('update:modelValue', false)
  resetForm()
  resetSteps()
}

const resetForm = () => {
  Object.keys(formData).forEach(key => {
    formData[key] = ''
  })
  forgotEmail.value = ''
}

const resetSteps = () => {
  currentStep.value = 'login'
  showVerificationStep.value = false
  showSuccessStep.value = false
  verificationEmail.value = ''
}

const handleSubmit = async () => {
  if (currentStep.value === 'login') {
    await handleLogin()
  } else if (currentStep.value === 'register') {
    await handleRegister()
  }
}

// 👈 Méthodes utilisant le store Pinia
const handleLogin = async () => {
  if (!formData.email || !formData.password) {
    toast.add({
      severity: 'warn',
      summary: 'Champs requis',
      detail: 'Veuillez remplir tous les champs',
      life: 3000
    })
    return
  }

  try {
    const result = await authStore.login({
      email: formData.email,
      password: formData.password
    })
    
    if (result.success) {
      toast.add({
        severity: 'success',
        summary: 'Connexion réussie',
        detail: 'Bienvenue !',
        life: 3000
      })
      
      emit('login-success', result.user)
      closeModal()
    } else {
      if (result.needsVerification) {
        verificationEmail.value = formData.email
        showVerificationStep.value = true
      } else {
        toast.add({
          severity: 'error',
          summary: 'Erreur de connexion',
          detail: result.message,
          life: 5000
        })
      }
    }
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Une erreur inattendue s\'est produite',
      life: 5000
    })
  }
}

const handleRegister = async () => {
  if (!formData.email || !formData.password || !formData.pseudo) {
    toast.add({
      severity: 'warn',
      summary: 'Champs requis',
      detail: 'Veuillez remplir tous les champs obligatoires',
      life: 3000
    })
    return
  }
  
  if (formData.password !== formData.confirmPassword) {
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Les mots de passe ne correspondent pas',
      life: 3000
    })
    return
  }

  if (formData.password.length < 8) {
    toast.add({
      severity: 'warn',
      summary: 'Mot de passe faible',
      detail: 'Le mot de passe doit faire au moins 8 caractères',
      life: 3000
    })
    return
  }

  try {
    const result = await authStore.register({
      email: formData.email,
      password: formData.password,
      pseudo: formData.pseudo,
      firstName: formData.firstName || null,
      lastName: formData.lastName || null
    })
    
    if (result.success) {
      toast.add({
        severity: 'success',
        summary: 'Inscription réussie',
        detail: result.message,
        life: 5000
      })
      
      verificationEmail.value = formData.email
      showVerificationStep.value = true
      emit('registration-success', result.user)
    } else {
      toast.add({
        severity: 'error',
        summary: 'Erreur d\'inscription',
        detail: result.message,
        life: 5000
      })
    }
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Une erreur inattendue s\'est produite',
      life: 5000
    })
  }
}

const handleResendVerification = async () => {
  try {
    const result = await authStore.resendVerification(verificationEmail.value)
    
    if (result.success) {
      toast.add({
        severity: 'success',
        summary: 'Email renvoyé',
        detail: result.message,
        life: 3000
      })
    } else {
      toast.add({
        severity: 'error',
        summary: 'Erreur',
        detail: result.message,
        life: 3000
      })
    }
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Impossible de renvoyer l\'email',
      life: 3000
    })
  }
}

const handleForgotPassword = async () => {
  if (!forgotEmail.value) {
    toast.add({
      severity: 'warn',
      summary: 'Email requis',
      detail: 'Veuillez saisir votre email',
      life: 3000
    })
    return
  }

  try {
    const result = await authStore.forgotPassword(forgotEmail.value)
    
    if (result.success) {
      toast.add({
        severity: 'success',
        summary: 'Email envoyé',
        detail: result.message,
        life: 5000
      })
      
      currentStep.value = 'login'
      forgotEmail.value = ''
    } else {
      toast.add({
        severity: 'error',
        summary: 'Erreur',
        detail: result.message,
        life: 3000
      })
    }
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Impossible d\'envoyer l\'email',
      life: 3000
    })
  }
}

// Watchers (inchangés)
watch(() => props.modelValue, (newVal) => {
  isOpen.value = newVal
  
  if (newVal) {
    document.body.style.overflow = 'hidden'
  } else {
    document.body.style.overflow = ''
  }
})

watch(() => props.initialMode, (newMode) => {
  currentStep.value = newMode
})

watch(isOpen, (newVal) => {
  if (!newVal) {
    resetForm()
    resetSteps()
    document.body.style.overflow = ''
  }
})
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 2rem;
}

.modal-container {
  width: 100%;
  max-width: 480px;
  max-height: 80vh;
  background: white;
  border-radius: 12px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  animation: modalEnter 0.3s ease-out;
  margin: auto;
  position: relative;
  overflow-y: auto;
}

@keyframes modalEnter {
  from {
    opacity: 0;
    transform: scale(0.9) translateY(-20px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem 2rem;
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  color: white;
}

.modal-title {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 700;
}

.close-button {
  background: none;
  border: none;
  color: white;
  font-size: 2rem;
  cursor: pointer;
  padding: 0;
  width: 2rem;
  height: 2rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  transition: background 0.2s;
}

.close-button:hover {
  background: rgba(255, 255, 255, 0.2);
}

.modal-body {
  padding: 2rem;
}

/* Étapes spéciales */
.verification-step,
.success-step,
.forgot-step {
  text-align: center;
}

.verification-step h3,
.success-step h3,
.forgot-step h3 {
  margin: 0 0 1rem 0;
  color: var(--text-primary);
  font-size: 1.5rem;
}

.verification-step p,
.success-step p,
.forgot-step p {
  color: var(--text-secondary);
  margin-bottom: 1rem;
}

.subtitle {
  font-size: 0.9rem;
  margin-bottom: 2rem !important;
}

.button-group {
  display: flex;
  gap: 1rem;
  justify-content: center;
  margin-bottom: 1.5rem;
}

.link-text {
  color: var(--text-secondary);
  font-size: 0.9rem;
}

/* Formulaire principal */
.tabs {
  display: flex;
  background: var(--surface-200);
  border-radius: 8px;
  padding: 4px;
  margin-bottom: 2rem;
}

.tab {
  flex: 1;
  padding: 0.75rem;
  border: none;
  background: none;
  border-radius: 6px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  color: var(--text-secondary);
}

.tab:hover {
  color: var(--primary);
}

.tab.active {
  background: white;
  color: var(--primary);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.field-group {
  display: flex;
  flex-direction: column;
}

.field-group label {
  font-weight: 500;
  color: var(--text-primary);
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
}

.clean-input {
  width: 100%;
}

:deep(.clean-input .p-inputtext) {
  width: 100%;
  padding: 0.875rem;
  border: 2px solid var(--surface-300);
  border-radius: 8px;
  font-size: 1rem;
  transition: border-color 0.2s;
}

:deep(.clean-input .p-inputtext:focus) {
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(38, 166, 154, 0.1);
}

/* Champ mot de passe custom */
.custom-password-field {
  position: relative;
  display: flex;
  align-items: center;
}

.custom-password-field .clean-input {
  flex: 1;
  padding-right: 3rem;
}

.password-toggle-btn {
  position: absolute;
  right: 0.875rem;
  background: none;
  border: none;
  color: var(--text-secondary);
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 4px;
  transition: color 0.2s;
  z-index: 10;
  display: flex;
  align-items: center;
  justify-content: center;
}

.password-toggle-btn:hover {
  color: var(--primary);
}

.password-toggle-btn i {
  font-size: 1rem;
}

/* Remove old password styles */

.field-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.forgot-link-container {
  text-align: right;
}

.text-link {
  background: none;
  border: none;
  color: var(--primary);
  cursor: pointer;
  font-weight: 500;
  text-decoration: underline;
  font-size: 0.9rem;
}

.text-link:hover {
  color: var(--primary-dark);
}

.primary-button {
  width: 100%;
}

:deep(.primary-button) {
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  border: none;
  padding: 1rem;
  border-radius: 8px;
  font-weight: 600;
  font-size: 1rem;
  transition: all 0.2s;
}

:deep(.primary-button:hover) {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(38, 166, 154, 0.3);
}

.switch-mode {
  margin-top: 2rem;
  text-align: center;
  color: var(--text-secondary);
  font-size: 0.9rem;
}

/* Responsive */
@media (max-width: 640px) {
  .modal-container {
    margin: 1rem;
    max-width: calc(100vw - 2rem);
  }
  
  .modal-body {
    padding: 1.5rem;
  }
  
  .field-row {
    grid-template-columns: 1fr;
  }
  
  .button-group {
    flex-direction: column;
  }
}
</style>
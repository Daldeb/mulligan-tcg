<template>
  <Dialog 
    v-model:visible="localVisible"
    modal 
    :closable="true"
    :style="{ width: '100%', maxWidth: '540px' }"
    :breakpoints="{ '960px': '85vw', '640px': '95vw' }"
    class="emerald-modal"
  >
    <template #header>
      <div class="modal-header-content">
        <i class="pi pi-user header-icon"></i>
        <span class="header-title">MULLIGAN TCG</span>
      </div>
    </template>

    <div class="modal-body">
      <!-- Onglets custom Emerald -->
      <div class="emerald-tabs">
        <button 
          @click="tabIndex = 0"
          :class="['emerald-tab', { 'active': tabIndex === 0 }]"
        >
          Connexion
        </button>
        <button 
          @click="tabIndex = 1"
          :class="['emerald-tab', { 'active': tabIndex === 1 }]"
        >
          Inscription
        </button>
      </div>

      <!-- Formulaire de connexion -->
      <form v-if="tabIndex === 0" @submit.prevent="handleLogin" class="emerald-form">
        <div class="field-group">
          <label for="login-email" class="field-label">Email</label>
          <InputText 
            id="login-email"
            v-model="loginForm.email"
            type="email"
            placeholder="votre@email.com"
            class="emerald-input"
            :class="{ 'error': !!loginErrors.email }"
          />
          <small v-if="loginErrors.email" class="field-error">{{ loginErrors.email }}</small>
        </div>

        <div class="field-group">
          <label for="login-password" class="field-label">Mot de passe</label>
          <div class="password-wrapper">
            <InputText
              id="login-password"
              v-model="loginForm.password"
              :type="showLoginPassword ? 'text' : 'password'"
              placeholder="Votre mot de passe"
              class="emerald-input password-input"
              :class="{ 'error': !!loginErrors.password }"
            />
            <button 
              type="button"
              class="password-toggle"
              @click="showLoginPassword = !showLoginPassword"
            >
              <i :class="showLoginPassword ? 'pi pi-eye-slash' : 'pi pi-eye'"></i>
            </button>
          </div>
          <small v-if="loginErrors.password" class="field-error">{{ loginErrors.password }}</small>
        </div>

        <!-- Erreurs globales login -->
        <div v-if="loginGlobalErrors.length" class="error-message">
          <div class="error-content">
            <i class="pi pi-exclamation-triangle error-icon"></i>
            <div class="error-text">
              <p v-for="(err, index) in loginGlobalErrors" :key="'login-error-' + index">
                {{ err }}
              </p>
            </div>
          </div>
        </div>

        <Button 
          type="submit"
          label="Se connecter"
          icon="pi pi-sign-in"
          class="emerald-button primary"
          :loading="isLoading"
        />
      </form>

      <!-- Formulaire d'inscription -->
      <form v-else @submit.prevent="handleRegister" class="emerald-form">
        <div class="field-group">
          <label for="register-username" class="field-label">Nom d'utilisateur</label>
          <InputText 
            id="register-username"
            v-model="registerForm.username"
            placeholder="Votre pseudo"
            class="emerald-input"
            :class="{ 'error': !!registerErrors.username }"
          />
          <small v-if="registerErrors.username" class="field-error">{{ registerErrors.username }}</small>
        </div>

        <div class="field-group">
          <label for="register-email" class="field-label">Email</label>
          <InputText 
            id="register-email"
            v-model="registerForm.email"
            type="email"
            placeholder="votre@email.com"
            class="emerald-input"
            :class="{ 'error': !!registerErrors.email }"
          />
          <small v-if="registerErrors.email" class="field-error">{{ registerErrors.email }}</small>
        </div>

        <div class="field-group">
          <label for="register-password" class="field-label">Mot de passe</label>
          <div class="password-wrapper">
            <InputText
              id="register-password"
              v-model="registerForm.password"
              :type="showRegisterPassword ? 'text' : 'password'"
              placeholder="Choisissez un mot de passe"
              class="emerald-input password-input"
              :class="{ 'error': !!registerErrors.password }"
            />
            <button 
              type="button"
              class="password-toggle"
              @click="showRegisterPassword = !showRegisterPassword"
            >
              <i :class="showRegisterPassword ? 'pi pi-eye-slash' : 'pi pi-eye'"></i>
            </button>
          </div>
          <small v-if="registerErrors.password" class="field-error">{{ registerErrors.password }}</small>
        </div>

        <div class="field-group">
          <label for="register-confirm-password" class="field-label">Confirmer le mot de passe</label>
          <div class="password-wrapper">
            <InputText
              id="register-confirm-password"
              v-model="registerForm.confirmPassword"
              :type="showConfirmPassword ? 'text' : 'password'"
              placeholder="Confirmez votre mot de passe"
              class="emerald-input password-input"
              :class="{ 'error': !!registerErrors.confirmPassword }"
            />
            <button 
              type="button"
              class="password-toggle"
              @click="showConfirmPassword = !showConfirmPassword"
            >
              <i :class="showConfirmPassword ? 'pi pi-eye-slash' : 'pi pi-eye'"></i>
            </button>
          </div>
          <small v-if="registerErrors.confirmPassword" class="field-error">{{ registerErrors.confirmPassword }}</small>
        </div>

        <!-- Erreurs globales register -->
        <div v-if="registerGlobalErrors.length" class="error-message">
          <div class="error-content">
            <i class="pi pi-exclamation-triangle error-icon"></i>
            <div class="error-text">
              <p v-for="(err, index) in registerGlobalErrors" :key="'register-error-' + index">
                {{ err }}
              </p>
            </div>
          </div>
        </div>

        <Button 
          type="submit"
          label="S'inscrire"
          icon="pi pi-user-plus"
          class="emerald-button primary"
          :loading="isLoading"
        />
      </form>

      <!-- Footer avec switch mode -->
      <div class="modal-footer">
        <span v-if="tabIndex === 0" class="switch-text">
          Pas encore de compte ?
          <button @click="tabIndex = 1" class="link-button">
            Inscrivez-vous
          </button>
        </span>
        <span v-else class="switch-text">
          Déjà un compte ?
          <button @click="tabIndex = 0" class="link-button">
            Connectez-vous
          </button>
        </span>
      </div>
    </div>
  </Dialog>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'
import { useAuthStore } from '../../stores/auth'
import { useToast } from 'primevue/usetoast'
import { useRouter } from 'vue-router'

// Props
const props = defineProps({ visible: Boolean })
const emit = defineEmits(['update:visible', 'login-success'])

// State
const isLoginMode = ref(true)
const tabIndex = ref(0)
const isLoading = ref(false)
const router = useRouter()

// Password visibility
const showLoginPassword = ref(false)
const showRegisterPassword = ref(false)
const showConfirmPassword = ref(false)

const loginForm = reactive({ email: '', password: '' })
const registerForm = reactive({ username: '', email: '', password: '', confirmPassword: '' })

const loginErrors = reactive({ email: '', password: '' })
const registerErrors = reactive({ username: '', email: '', password: '', confirmPassword: '' })

const loginGlobalErrors = ref([])
const registerGlobalErrors = ref([])

const authStore = useAuthStore()
const toast = useToast()

const localVisible = computed({
  get: () => props.visible,
  set: (val) => emit('update:visible', val)
})

watch(tabIndex, (val) => {
  isLoginMode.value = val === 0
})
watch(isLoginMode, (val) => {
  tabIndex.value = val ? 0 : 1
})

const clearErrors = () => {
  Object.keys(loginErrors).forEach(k => loginErrors[k] = '')
  Object.keys(registerErrors).forEach(k => registerErrors[k] = '')
  loginGlobalErrors.value = []
  registerGlobalErrors.value = []
}

const resetForms = () => {
  Object.assign(loginForm, { email: '', password: '' })
  Object.assign(registerForm, { username: '', email: '', password: '', confirmPassword: '' })
  clearErrors()
}

// Validations
const validateLoginForm = () => {
  clearErrors()
  let valid = true
  if (!loginForm.email) {
    loginErrors.email = 'Email requis'
    valid = false
  } else if (!/\S+@\S+\.\S+/.test(loginForm.email)) {
    loginErrors.email = 'Email invalide'
    valid = false
  }
  if (!loginForm.password) {
    loginErrors.password = 'Mot de passe requis'
    valid = false
  }
  return valid
}

const validateRegisterForm = () => {
  clearErrors()
  let valid = true
  if (!registerForm.username || registerForm.username.length < 3) {
    registerErrors.username = 'Nom d\'utilisateur requis (min 3 caractères)'
    valid = false
  }
  if (!registerForm.email || !/\S+@\S+\.\S+/.test(registerForm.email)) {
    registerErrors.email = 'Email valide requis'
    valid = false
  }
  if (!registerForm.password || registerForm.password.length < 6) {
    registerErrors.password = 'Mot de passe requis (min 6 caractères)'
    valid = false
  }
  if (registerForm.password !== registerForm.confirmPassword) {
    registerErrors.confirmPassword = 'Les mots de passe ne correspondent pas'
    valid = false
  }
  return valid
}

// Actions
const handleLogin = async () => {
  if (!validateLoginForm()) return
  isLoading.value = true
  loginGlobalErrors.value = []
  try {
    const res = await authStore.login(loginForm.email, loginForm.password)
    if (res.success) {
      toast.add({ severity: 'success', summary: 'Connexion réussie', detail: `Bienvenue`, life: 3000 })
      emit('login-success')
      resetForms()
      // Redirection vers /profile
      router.push('/profile')
    } else {
      loginGlobalErrors.value = res.errors || ['Erreur de connexion']
    }
  } catch {
    loginGlobalErrors.value = ['Erreur serveur. Veuillez réessayer.']
  } finally {
    isLoading.value = false
  }
}

const handleRegister = async () => {
  if (!validateRegisterForm()) return
  isLoading.value = true
  registerGlobalErrors.value = []
  try {
    const res = await authStore.register({
      pseudo: registerForm.username,
      email: registerForm.email,
      password: registerForm.password
    })
    if (res.success) {
      toast.add({ severity: 'success', summary: 'Inscription réussie', detail: `Bienvenue`, life: 3000 })
      emit('login-success')
      resetForms()
      // Redirection vers /profile
      router.push('/profile')
    } else {
      registerGlobalErrors.value = res.errors || ['Erreur d\'inscription']
    }
  } catch {
    registerGlobalErrors.value = ['Erreur serveur. Veuillez réessayer.']
  } finally {
    isLoading.value = false
  }
}
</script>

<style scoped>
/* === MODAL EMERALD GAMING === */

:deep(.emerald-modal .p-dialog) {
  border-radius: var(--border-radius-large) !important;
  box-shadow: var(--shadow-large) !important;
  border: 1px solid var(--surface-200) !important;
  overflow: hidden !important;
}

:deep(.emerald-modal .p-dialog-header) {
  background: var(--emerald-gradient) !important;
  color: var(--text-inverse) !important;
  padding: 1.5rem 2rem !important;
  border-bottom: none !important;
}

:deep(.emerald-modal .p-dialog-content) {
  padding: 0 !important;
  background: var(--surface) !important;
}

.modal-header-content {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  width: 100%;
}

.header-icon {
  font-size: 1.5rem;
  opacity: 0.9;
}

.header-title {
  font-size: 1.5rem;
  font-weight: 700;
  letter-spacing: 1px;
}

.modal-body {
  padding: 2rem;
}

/* Onglets Emerald */
.emerald-tabs {
  display: flex;
  background: var(--surface-200);
  border-radius: var(--border-radius);
  padding: 4px;
  margin-bottom: 2rem;
}

.emerald-tab {
  flex: 1;
  padding: 0.875rem 1.5rem;
  border: none;
  background: none;
  border-radius: calc(var(--border-radius) - 2px);
  font-weight: 500;
  color: var(--text-secondary);
  cursor: pointer;
  transition: all var(--transition-fast);
  font-size: 0.95rem;
}

.emerald-tab:hover {
  color: var(--primary);
  background: rgba(38, 166, 154, 0.1);
}

.emerald-tab.active {
  background: white;
  color: var(--primary);
  box-shadow: var(--shadow-small);
  font-weight: 600;
}

/* Formulaires */
.emerald-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
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
  border-color: var(--primary) !important;
  box-shadow: 0 0 0 3px rgba(38, 166, 154, 0.1) !important;
  background: white !important;
}

:deep(.emerald-input.error) {
  border-color: var(--accent) !important;
  box-shadow: 0 0 0 3px rgba(255, 87, 34, 0.1) !important;
}

:deep(.emerald-input::placeholder) {
  color: var(--text-secondary) !important;
  opacity: 0.7 !important;
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
  color: var(--primary);
}

.password-toggle i {
  font-size: 1rem;
}

/* Field errors */
.field-error {
  color: var(--accent);
  font-size: 0.8rem;
  font-weight: 500;
  margin-top: 0.25rem;
}

/* Error messages */
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

/* Buttons */
:deep(.emerald-button) {
  width: 100% !important;
  padding: 1rem 1.5rem !important;
  border-radius: var(--border-radius) !important;
  font-weight: 600 !important;
  font-size: 0.95rem !important;
  transition: all var(--transition-fast) !important;
  border: none !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  gap: 0.5rem !important;
}

:deep(.emerald-button.primary) {
  background: var(--emerald-gradient) !important;
  color: var(--text-inverse) !important;
}

:deep(.emerald-button.primary:hover) {
  background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%) !important;
  transform: translateY(-1px) !important;
  box-shadow: 0 8px 25px rgba(38, 166, 154, 0.3) !important;
}

:deep(.emerald-button.primary:active) {
  transform: translateY(0) !important;
}

/* Modal footer */
.modal-footer {
  margin-top: 2rem;
  padding-top: 1.5rem;
  border-top: 1px solid var(--surface-200);
  text-align: center;
}

.switch-text {
  color: var(--text-secondary);
  font-size: 0.9rem;
}

.link-button {
  background: none;
  border: none;
  color: var(--primary);
  cursor: pointer;
  font-weight: 600;
  text-decoration: underline;
  font-size: 0.9rem;
  margin-left: 0.25rem;
  transition: color var(--transition-fast);
}

.link-button:hover {
  color: var(--primary-dark);
}

/* Responsive */
@media (max-width: 640px) {
  .modal-body {
    padding: 1.5rem;
  }
  
  :deep(.emerald-modal .p-dialog-header) {
    padding: 1rem 1.5rem !important;
  }
  
  .header-title {
    font-size: 1.25rem;
  }
  
  .emerald-form {
    gap: 1.25rem;
  }
  
  .emerald-tabs {
    margin-bottom: 1.5rem;
  }
  
  .emerald-tab {
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
  }
}

/* Animation d'entrée */
:deep(.p-dialog-enter-active) {
  animation: emeraldModalEnter 0.4s ease-out;
}

@keyframes emeraldModalEnter {
  from {
    opacity: 0;
    transform: scale(0.9) translateY(-20px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}
</style>
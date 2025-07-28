<template>
  <Dialog 
    v-model:visible="localVisible"
    modal 
    header="Authentification"
    :style="{ width: '100%', maxWidth: '640px' }"
    :breakpoints="{ '960px': '90vw', '640px': '95vw' }"
  >
    <div class="p-4">
      <TabView v-model:activeIndex="tabIndex">
        <!-- Onglet Connexion -->
        <TabPanel header="Connexion">
          <form @submit.prevent="handleLogin" class="p-fluid flex flex-col gap-4">
            <div>
              <label for="login-email" class="block mb-1 font-semibold">Email</label>
              <InputText 
                id="login-email"
                v-model="loginForm.email"
                type="email"
                placeholder="Votre email"
                class="w-full"
                :invalid="!!loginErrors.email"
              />
              <small v-if="loginErrors.email" class="p-error">{{ loginErrors.email }}</small>
            </div>

            <div>
              <label for="login-password" class="block mb-1 font-semibold">Mot de passe</label>
              <Password 
                id="login-password"
                v-model="loginForm.password"
                toggleMask
                placeholder="Votre mot de passe"
                :feedback="false"
                class="w-full"
                :invalid="!!loginErrors.password"
              />
              <small v-if="loginErrors.password" class="p-error">{{ loginErrors.password }}</small>
            </div>

            <!-- Affichage des erreurs globales -->
            <div v-if="loginGlobalErrors.length" class="bg-red-100 border border-red-300 text-red-700 p-3 rounded-md">
              <ul>
                <li v-for="(err, index) in loginGlobalErrors" :key="'login-error-' + index">{{ err }}</li>
              </ul>
            </div>

            <Button 
              type="submit"
              label="Se connecter"
              icon="pi pi-sign-in"
              class="px-6 py-3 mx-auto mt-2"
              :loading="isLoading"
            />
          </form>
        </TabPanel>

        <!-- Onglet Inscription -->
        <TabPanel header="Inscription">
          <form @submit.prevent="handleRegister" class="p-fluid flex flex-col gap-4">
            <div>
              <label for="register-username" class="block mb-1 font-semibold">Nom d'utilisateur</label>
              <InputText 
                id="register-username"
                v-model="registerForm.username"
                placeholder="Votre pseudo"
                class="w-full"
                :invalid="!!registerErrors.username"
              />
              <small v-if="registerErrors.username" class="p-error">{{ registerErrors.username }}</small>
            </div>

            <div>
              <label for="register-email" class="block mb-1 font-semibold">Email</label>
              <InputText 
                id="register-email"
                v-model="registerForm.email"
                type="email"
                placeholder="Votre email"
                class="w-full"
                :invalid="!!registerErrors.email"
              />
              <small v-if="registerErrors.email" class="p-error">{{ registerErrors.email }}</small>
            </div>

            <div>
              <label for="register-password" class="block mb-1 font-semibold">Mot de passe</label>
              <Password 
                id="register-password"
                v-model="registerForm.password"
                placeholder="Mot de passe"
                toggleMask
                class="w-full"
                :invalid="!!registerErrors.password"
              />
              <small v-if="registerErrors.password" class="p-error">{{ registerErrors.password }}</small>
            </div>

            <div>
              <label for="register-confirm-password" class="block mb-1 font-semibold">Confirmer le mot de passe</label>
              <Password 
                id="register-confirm-password"
                v-model="registerForm.confirmPassword"
                placeholder="Confirmation"
                toggleMask
                class="w-full"
                :feedback="false"
                :invalid="!!registerErrors.confirmPassword"
              />
              <small v-if="registerErrors.confirmPassword" class="p-error">{{ registerErrors.confirmPassword }}</small>
            </div>

            <!-- Affichage des erreurs globales -->
            <div v-if="registerGlobalErrors.length" class="bg-red-100 border border-red-300 text-red-700 p-3 rounded-md">
              <ul>
                <li v-for="(err, index) in registerGlobalErrors" :key="'register-error-' + index">{{ err }}</li>
              </ul>
            </div>

            <Button 
              type="submit"
              label="S'inscrire"
              icon="pi pi-user-plus"
              class="px-6 py-3 mx-auto mt-2"
              :loading="isLoading"
            />
          </form>
        </TabPanel>
      </TabView>
    </div>
  </Dialog>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'
import { useAuthStore } from '../../stores/auth'
import { useToast } from 'primevue/usetoast'

// Props
const props = defineProps({ visible: Boolean })
const emit = defineEmits(['update:visible', 'login-success'])

// State
const isLoginMode = ref(true)
const tabIndex = ref(0)
const isLoading = ref(false)

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
:deep(.p-dialog) {
  max-width: 800px !important;
  width: 100% !important;
}
:deep(.p-dialog-content) {
  padding: 2rem !important;
}
:deep(.p-tabview),
:deep(.p-tabview-panel) {
  width: 100%;
}
:deep(form) {
  width: 100%;
  display: flex !important;
  flex-direction: column !important;
  gap: 1rem;
}
:deep(.p-inputtext),
:deep(.p-password),
:deep(.p-button) {
  width: 100%;
}
:deep(.p-tabview-nav) {
  gap: 1rem;
  padding: 0.5rem 1rem;
  justify-content: center;
}
:deep(.p-tabview-nav li) {
  padding: 0.5rem 1rem;
  font-weight: 500;
  border-radius: 6px;
}
:deep(.p-tabview-nav li.p-highlight) {
  background: #e0e7ff;
  color: #3730a3;
}
</style>

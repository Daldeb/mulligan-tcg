import { createApp } from 'vue'
import { createPinia } from 'pinia'
import PrimeVue from 'primevue/config'
import Ripple from 'primevue/ripple';
import ConfirmDialog from 'primevue/confirmdialog'
import ConfirmationService from 'primevue/confirmationservice'

// ✅ Thème CSS PrimeVue
import 'primevue/resources/themes/lara-light-indigo/theme.css'
import 'primevue/resources/primevue.min.css'
import 'primeicons/primeicons.css'

// 🎨 Thème Emerald Gaming (priorité sur PrimeVue)
import './assets/styles/emerald-theme.css'

// Composants PrimeVue essentiels
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Dialog from 'primevue/dialog'
import Card from 'primevue/card'
import Avatar from 'primevue/avatar'
import Menu from 'primevue/menu'
import Menubar from 'primevue/menubar'
import Badge from 'primevue/badge'
import Divider from 'primevue/divider'
import InputGroup from 'primevue/inputgroup'
import InputGroupAddon from 'primevue/inputgroupaddon'
import Password from 'primevue/password'
import Toast from 'primevue/toast'
import ToastService from 'primevue/toastservice'
import TabView from 'primevue/tabview'
import TabPanel from 'primevue/tabpanel'
import Message from 'primevue/message'
import Textarea from 'primevue/textarea'
import Tooltip from 'primevue/tooltip'

import App from './App.vue'
import router from './router'

const app = createApp(App)
const pinia = createPinia()

app.use(PrimeVue)
app.use(ConfirmationService)
app.use(pinia)
app.use(router)
app.use(ToastService)

// ✅ Initialisation du store auth après création de Pinia
const { useAuthStore } = await import('./stores/auth')
const authStore = useAuthStore()

// Si un token existe, recharger les données utilisateur
if (authStore.token) {
  await authStore.checkAuthStatus()
}

// ✅ Initialiser le super filtre après auth
const { useGameFilterStore } = await import('./stores/gameFilter')
const gameFilterStore = useGameFilterStore()

await gameFilterStore.loadGames()
await gameFilterStore.loadSelectedGames()

// Enregistrement des composants
app.component('Button', Button)
app.component('InputText', InputText)
app.component('Dialog', Dialog)
app.component('Card', Card)
app.component('Avatar', Avatar)
app.component('Menu', Menu)
app.component('Menubar', Menubar)
app.component('Badge', Badge)
app.component('Divider', Divider)
app.component('InputGroup', InputGroup)
app.component('InputGroupAddon', InputGroupAddon)
app.component('Password', Password)
app.component('Toast', Toast)
app.component('TabView', TabView)
app.component('TabPanel', TabPanel)
app.component('Message', Message)
app.component('Textarea', Textarea)
app.component('ConfirmDialog', ConfirmDialog)

// Enregistrement des directives 
app.directive('tooltip', Tooltip)
app.directive('ripple', Ripple)

app.mount('#app')
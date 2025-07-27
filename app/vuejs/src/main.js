import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import { createPinia } from 'pinia'

// PrimeVue Core
import PrimeVue from 'primevue/config'
import ToastService from 'primevue/toastservice'
import Tooltip from 'primevue/tooltip'

// PrimeVue Components essentiels
import Button from 'primevue/button'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import Dropdown from 'primevue/dropdown'
import Badge from 'primevue/badge'
import Avatar from 'primevue/avatar'
import Toast from 'primevue/toast'
import Divider from 'primevue/divider'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Carousel from 'primevue/carousel'
import Dialog from 'primevue/dialog'
import Menu from 'primevue/menu'
import Password from 'primevue/password'

// Seulement les CSS qui existent
import 'primeicons/primeicons.css'

// Notre thème personnalisé Emerald Gaming (remplace tous les styles PrimeVue)
import './assets/styles/emerald-theme.css'

const app = createApp(App)

// Configuration PrimeVue
app.use(PrimeVue, {
    ripple: true,
    locale: {
        startsWith: 'Commence par',
        contains: 'Contient',
        notContains: 'Ne contient pas',
        endsWith: 'Finit par',
        equals: 'Égal à',
        notEquals: 'Différent de',
        clear: 'Effacer',
        apply: 'Appliquer',
        accept: 'Oui',
        reject: 'Non',
        choose: 'Choisir',
        upload: 'Télécharger',
        cancel: 'Annuler',
        dayNames: ["Dimanche", "Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi"],
        dayNamesShort: ["Dim", "Lun", "Mar", "Mer", "Jeu", "Ven", "Sam"],
        monthNames: ["Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre"],
        monthNamesShort: ["Jan", "Fév", "Mar", "Avr", "Mai", "Jun", "Jul", "Aoû", "Sep", "Oct", "Nov", "Déc"],
        today: 'Aujourd\'hui',
        weekHeader: 'Sem',
        firstDayOfWeek: 1,
        dateFormat: 'dd/mm/yy',
        weak: 'Faible',
        medium: 'Moyen',
        strong: 'Fort',
        passwordPrompt: 'Saisissez un mot de passe',
        emptyFilterMessage: 'Aucun résultat trouvé',
        emptyMessage: 'Aucune option disponible'
    }
})

app.use(ToastService)
app.directive('tooltip', Tooltip)
app.use(createPinia())
app.use(router)

// Composants globaux
app.component('Button', Button)
app.component('Card', Card)
app.component('InputText', InputText)
app.component('Dropdown', Dropdown)
app.component('Badge', Badge)
app.component('Avatar', Avatar)
app.component('Toast', Toast)
app.component('Divider', Divider)
app.component('DataTable', DataTable)
app.component('Column', Column)
app.component('Carousel', Carousel)
app.component('Dialog', Dialog)
app.component('Menu', Menu)
app.component('Password', Password)

// Guard de navigation pour les routes protégées
router.beforeEach(async (to, from, next) => {
    if (to.meta?.requiresAuth) {
      const { useAuthStore } = await import('./stores/auth')
      const authStore = useAuthStore()
      
      if (!authStore.isAuthenticated) {
        next('/')
        return
      }
      
      if (to.meta?.requiresAdmin && !authStore.isAdmin) {
        next('/')
        return
      }
    }
    
    next()
  })
app.mount('#app')

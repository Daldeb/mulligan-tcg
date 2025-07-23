// frontend/src/main.js
import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import { createPinia } from 'pinia'

// PrimeVue imports
import PrimeVue from 'primevue/config'
import Aura from '@primevue/themes/aura'
import 'primeicons/primeicons.css'

// PrimeVue components
import Button from 'primevue/button'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Toast from 'primevue/toast'
import ToastService from 'primevue/toastservice'
import ProgressSpinner from 'primevue/progressspinner'
import Dialog from 'primevue/dialog'
import Menubar from 'primevue/menubar'
import Badge from 'primevue/badge'

const app = createApp(App)

// PrimeVue configuration
app.use(PrimeVue, {
    theme: {
        preset: Aura,
        options: {
            prefix: 'p',
            darkModeSelector: '.dark-mode',
            cssLayer: false
        }
    }
})

// Services
app.use(ToastService)
app.use(createPinia())
app.use(router)

// Global components
app.component('Button', Button)
app.component('Card', Card)
app.component('InputText', InputText)
app.component('DataTable', DataTable)
app.component('Column', Column)
app.component('Toast', Toast)
app.component('ProgressSpinner', ProgressSpinner)
app.component('Dialog', Dialog)
app.component('Menubar', Menubar)
app.component('Badge', Badge)

app.mount('#app')
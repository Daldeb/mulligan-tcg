<template>
  <header class="bg-white shadow-sm border-b border-gray-200">
    <div class="container mx-auto">
      <!-- Header principal -->
      <div class="flex items-center justify-between py-4">
        <!-- Logo -->
        <div class="flex items-center">
          <router-link to="/" class="text-2xl font-bold text-gray-900">
            MULLIGAN TCG
          </router-link>
          <span class="ml-2 text-sm text-red-500 font-medium">
            Votre hub TCG ultime
          </span>
        </div>

        <!-- Barre de recherche -->
        <div class="flex-1 max-w-md mx-8">
          <InputGroup>
            <InputText 
              v-model="searchQuery"
              placeholder="Recherche d'utilisateurs"
              class="w-full"
            />
            <InputGroupAddon>
              <i class="pi pi-search text-gray-400"></i>
            </InputGroupAddon>
          </InputGroup>
        </div>

        <!-- Actions utilisateur -->
        <div class="flex items-center space-x-4">
          <!-- Messages -->
          <Button 
            icon="pi pi-envelope" 
            severity="secondary" 
            text 
            rounded
            :badge="unreadMessages > 0 ? unreadMessages.toString() : null"
            badge-severity="danger"
            @click="openMessages"
          />

          <!-- Profil / Connexion -->
          <div class="relative">
            <template v-if="authStore.isAuthenticated">
              <Avatar 
                :label="authStore.user?.username?.charAt(0).toUpperCase() ?? 'U'" 
                shape="circle"
                size="normal"
                class="bg-blue-500 text-white cursor-pointer"
                @click="onAvatarClick"
              />
            </template>

            <template v-else>
              <Button 
                icon="pi pi-user" 
                label="Connexion"
                @click="$emit('open-login')"
                size="small"
              />
            </template>

            <!-- Menu utilisateur -->
            <Menu 
              ref="menu"
              :model="userMenuItems"
              popup
            />
          </div>

          <!-- Menu hamburger -->
          <Button 
            icon="pi pi-bars" 
            severity="secondary" 
            text 
            rounded
            @click="toggleMobileMenu"
          />
        </div>
      </div>

      <!-- Section NEWS ET ÉVÉNEMENTS -->
      <div class="bg-gray-100 py-3">
        <div class="text-center">
          <h2 class="text-lg font-medium text-gray-700 tracking-wider">
            NEWS ET ÉVÉNEMENTS
          </h2>
        </div>
      </div>

      <!-- Navigation principale -->
      <nav class="py-4">
        <div class="flex justify-center space-x-8">
          <router-link 
            to="/discussions"
            class="nav-button"
          >
            <Button 
              label="Discussions" 
              icon="pi pi-comments"
              severity="secondary"
              outlined
              rounded
            />
          </router-link>

          <router-link 
            to="/decks"
            class="nav-button"
          >
            <Button 
              label="Decks" 
              icon="pi pi-clone"
              severity="secondary"
              outlined
              rounded
            />
          </router-link>

          <router-link 
            to="/classements"
            class="nav-button"
          >
            <Button 
              label="Classements" 
              icon="pi pi-chart-bar"
              severity="secondary"
              outlined
              rounded
            />
          </router-link>

          <router-link 
            to="/boutiques"
            class="nav-button"
          >
            <Button 
              label="Boutiques" 
              icon="pi pi-shopping-cart"
              severity="secondary"
              outlined
              rounded
            />
          </router-link>
        </div>
      </nav>
    </div>
  </header>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '../../stores/auth'
import { useRouter } from 'vue-router'

onMounted(() => {
  console.log('🔍 Menu ref on mount:', menu.value)
})

// Props et émissions
defineEmits(['open-login'])

// Stores et router
const authStore = useAuthStore()
const router = useRouter()

// State local
const menu = ref(null)
const searchQuery = ref('')
const userMenuVisible = ref(false)
const mobileMenuVisible = ref(false)
const unreadMessages = ref(3) // Exemple

// Menu utilisateur
const userMenuItems = computed(() => [
  {
    label: 'Profil',
    icon: 'pi pi-user',
    command: () => router.push('/profile')
  },
  {
    label: 'Favoris',
    icon: 'pi pi-heart',
    command: () => router.push('/favorites')
  },
  {
    label: 'Préférences',
    icon: 'pi pi-cog',
    command: () => router.push('/preferences')
  },
  {
    separator: true
  },
  {
    label: 'Déconnexion',
    icon: 'pi pi-sign-out',
    command: () => authStore.logout()
  }
])

// Methods
const toggleUserMenu = () => {
  userMenuVisible.value = !userMenuVisible.value
}
//Clic handler sur l'avatar pour acceder au menu
const onAvatarClick = (event) => {
  console.log('🖱 Avatar click event:', event)
  if (menu.value) {
    menu.value.toggle(event)
  } else {
    console.warn('⚠️ Menu ref is not defined')
  }
}

const toggleMobileMenu = () => {
  mobileMenuVisible.value = !mobileMenuVisible.value
}

const openMessages = () => {
  router.push('/messages')
}
</script>

<style scoped>
.nav-button {
  text-decoration: none;
}

.nav-button:hover .p-button {
  transform: translateY(-1px);
  transition: transform 0.2s ease;
}

/* Responsive */
@media (max-width: 768px) {
  .container {
    padding: 0 0.5rem;
  }

  .flex.space-x-8 {
    flex-wrap: wrap;
    gap: 0.5rem;
    justify-content: center;
  }
}
</style>

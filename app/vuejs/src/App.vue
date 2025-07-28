<template>
  <div id="app" class="min-h-screen bg-surface">
    <!-- Header principal -->
    <AppHeader @open-login="openLoginModal" />
    
    <!-- Contenu principal avec router -->
    <main class="flex-1">
      <router-view />
    </main>
    
    <!-- Footer -->
    <AppFooter />
    
    <!-- Modal de connexion/inscription -->
    <LoginModal 
      v-model:visible="isLoginModalVisible" 
      @login-success="handleLoginSuccess"
    />
    
    <!-- Toast pour les notifications -->
    <Toast />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useAuthStore } from './stores/auth'
import AppHeader from './components/layout/AppHeader.vue'
import AppFooter from './components/layout/AppFooter.vue'
import LoginModal from './components/auth/LoginModal.vue'

// State
const isLoginModalVisible = ref(false)

// Store
const authStore = useAuthStore()

// Methods
const openLoginModal = () => {
  isLoginModalVisible.value = true
}

const handleLoginSuccess = (userData) => {
  isLoginModalVisible.value = false
  // Optionnel : afficher un toast de succès
}

// Vérifier si l'utilisateur est déjà connecté au démarrage
authStore.checkAuthStatus()
</script>

<style>
/* Emerald va override ces styles, on garde juste l'essentiel */
#app {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 2rem;
}

@media (max-width: 768px) {
  .container {
    padding: 0 1rem;
  }
}
</style>
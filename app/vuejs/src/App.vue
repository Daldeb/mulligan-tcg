<template>
  <div id="app" class="min-h-screen bg-gray-50">
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
/* Styles globaux Material Design */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: 'Roboto', 'Helvetica Neue', Arial, sans-serif;
  line-height: 1.6;
  color: #333;
}

#app {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

/* Classes utilitaires */
.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 1rem;
}

.section-placeholder {
  background: #e0e0e0;
  border-radius: 8px;
  min-height: 200px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #666;
  font-size: 1.2rem;
  font-weight: 500;
}
</style>
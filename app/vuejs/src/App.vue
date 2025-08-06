<template>
  <div id="app" class="min-h-screen bg-surface">
    <!-- Header principal -->
    <AppHeader 
      @open-login="openLoginModal" 
      :is-game-data-ready="isGameDataReady" 
    />
    
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
    <ConfirmDialog />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useAuthStore } from './stores/auth'
import { useGameFilterStore } from './stores/gameFilter'
import { storeToRefs } from 'pinia'

import AppHeader from './components/layout/AppHeader.vue'
import AppFooter from './components/layout/AppFooter.vue'
import LoginModal from './components/auth/LoginModal.vue'

// Store
const authStore = useAuthStore()

// Game filter
const gameFilterStore = useGameFilterStore()
const { availableGames } = storeToRefs(gameFilterStore)
const isGameDataReady = computed(() => availableGames.value.length > 0)

// Login modal
const isLoginModalVisible = ref(false)
const openLoginModal = () => {
  isLoginModalVisible.value = true
}
const handleLoginSuccess = () => {
  isLoginModalVisible.value = false
}

// Auth au démarrage
authStore.checkAuthStatus()
</script>

<style>
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

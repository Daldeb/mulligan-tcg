<template>
  <div class="decks-page">
    <div class="container">
      
      <!-- Si utilisateur connecté : afficher les toggles normaux -->
      <div v-if="authStore.isAuthenticated" class="deck-toggle-container">
        <div class="toggle-wrapper glass-effect p-3 border-round flex gap-2 align-items-center justify-content-center">
          <RouterLink
            to="/decks/communaute"
            class="p-button emerald-btn"
            :class="{ 'p-button-outlined': currentRoute !== 'community-decks', 'p-button-raised': currentRoute === 'community-decks' }"
          >
            Decks Communautaires
          </RouterLink>
          <RouterLink
            to="/decks/metagame"
            class="p-button emerald-btn"
            :class="{ 'p-button-outlined': currentRoute !== 'metagame-decks', 'p-button-raised': currentRoute === 'metagame-decks' }"
          >
            Metagame
          </RouterLink>
        </div>
      </div>

      <!-- Si utilisateur NON connecté : message de connexion -->
      <div v-if="!authStore.isAuthenticated" class="auth-required-state">
        <Card class="gaming-card auth-card">
          <template #content>
            <div class="auth-content">
              <i class="pi pi-lock auth-icon"></i>
              <h3 class="auth-title">Veuillez vous connecter</h3>
              <p class="auth-description">
                Pour accéder aux decks de la communauté et du metagame, veuillez vous authentifier.
              </p>
              <div class="auth-actions">
                <Button 
                  label="Se connecter"
                  icon="pi pi-sign-in"
                  class="emerald-button primary"
                  @click="showLoginModal = true"
                />
              </div>
            </div>
          </template>
        </Card>
      </div>

      <!-- Vue enfant selon la route (seulement si connecté) -->
      <div v-if="authStore.isAuthenticated" class="mt-4">
        <RouterView />
      </div>
      
    </div>

    <!-- Modale de connexion/inscription -->
    <LoginModal 
      v-model:visible="showLoginModal"
      @login-success="onLoginSuccess"
    />
  </div>
</template>

<script setup>
import { useRoute } from 'vue-router'
import { computed, watchEffect } from 'vue'
import { useAuthStore } from '../stores/auth'
import LoginModal from '../components/auth/LoginModal.vue'

const route = useRoute()
const authStore = useAuthStore() 
const currentRoute = computed(() => route.name)

const showLoginModal = ref(false) 

const onLoginSuccess = () => { 
  showLoginModal.value = false
}

watchEffect(() => {
  console.log('[🔍 Route actuelle]', currentRoute.value)
})
</script>

<style scoped>
.deck-toggle-container {
  display: flex;
  justify-content: center;
  margin-bottom: 2rem;
  margin-top: 1rem;
}

.toggle-wrapper {
  background-color: var(--surface-100);
  box-shadow: var(--shadow-small);
  border: 1px solid var(--surface-200);
  border-radius: var(--border-radius-large);
  max-width: 600px;
  width: 100%;
}

/* Auth required state */
.auth-required-state {
  display: flex;
  justify-content: center;
  margin: 3rem 0;
}

.auth-card {
  max-width: 600px;
  width: 100%;
}

.auth-content {
  padding: 3rem 2rem;
  text-align: center;
}

.auth-icon {
  font-size: 4rem;
  color: var(--text-secondary);
  margin-bottom: 1rem;
  opacity: 0.7;
}

.auth-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0 0 0.5rem 0;
}

.auth-description {
  color: var(--text-secondary);
  margin: 0 0 2rem 0;
  line-height: 1.5;
}

.auth-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
}

@media (max-width: 640px) {
  .auth-actions {
    flex-direction: column;
    gap: 0.75rem;
  }
  
  .auth-actions .p-button {
    width: 100%;
  }
}
</style>

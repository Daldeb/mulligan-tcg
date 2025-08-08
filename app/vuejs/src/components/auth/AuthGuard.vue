<template>
  <div class="auth-guard-wrapper">
    <!-- Si utilisateur connecté : afficher le contenu -->
    <div v-if="authStore.isAuthenticated" class="protected-content">
      <slot />
    </div>

    <!-- Si utilisateur NON connecté : interface d'authentification -->
    <div v-else class="auth-required-state">
      <div class="container">
        <Card class="gaming-card auth-card">
          <template #content>
            <div class="auth-content">
              <div class="auth-icon-container">
                <i :class="config.icon" class="auth-icon"></i>
                <div class="auth-icon-glow"></div>
              </div>
              <h3 class="auth-title">{{ config.title }}</h3>
              <p class="auth-description" v-html="config.description"></p>
              
              <div class="auth-actions">
                <Button
                  label="Se connecter"
                  icon="pi pi-sign-in"
                  class="emerald-button primary auth-login-btn"
                  @click="showLoginModal = true"
                />
              </div>
              
              <!-- Preview des fonctionnalités -->
              <div v-if="config.features?.length" class="auth-preview">
                <div 
                  v-for="feature in config.features" 
                  :key="feature.icon"
                  class="preview-section"
                >
                  <i :class="feature.icon"></i>
                  <span>{{ feature.label }}</span>
                </div>
              </div>
            </div>
          </template>
        </Card>
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
import { ref } from 'vue'
import { useAuthStore } from '../../stores/auth'
import LoginModal from './LoginModal.vue'
import Card from 'primevue/card'
import Button from 'primevue/button'

const props = defineProps({
  config: {
    type: Object,
    required: true,
    validator: (config) => {
      return config.title && config.description && config.icon
    }
  }
})

const authStore = useAuthStore()
const showLoginModal = ref(false)

const onLoginSuccess = () => {
  showLoginModal.value = false
}
</script>

<style scoped>
.auth-guard-wrapper {
  min-height: calc(100vh - var(--header-height));
  background: var(--surface-gradient);
}

.protected-content {
  min-height: inherit;
}

/* Auth required state */
.auth-required-state {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: calc(100vh - var(--header-height));
  padding: 2rem 0;
  animation: slideInUp 0.8s ease-out;
}

.container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 2rem;
  display: flex;
  justify-content: center;
}

.auth-card {
  max-width: 700px;
  width: 100%;
  position: relative;
  overflow: hidden;
}

.auth-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: var(--emerald-gradient);
}

.auth-content {
  padding: 3rem 2rem;
  text-align: center;
  position: relative;
}

.auth-icon-container {
  position: relative;
  display: inline-block;
  margin-bottom: 1.5rem;
}

.auth-icon {
  font-size: 4rem;
  color: var(--primary);
  position: relative;
  z-index: 2;
  animation: iconFloat 3s ease-in-out infinite;
}

.auth-icon-glow {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 80px;
  height: 80px;
  background: radial-gradient(circle, rgba(38, 166, 154, 0.2) 0%, transparent 70%);
  border-radius: 50%;
  animation: glowPulse 2s ease-in-out infinite;
}

@keyframes iconFloat {
  0%, 100% { transform: translateY(0px); }
  50% { transform: translateY(-10px); }
}

@keyframes glowPulse {
  0%, 100% { opacity: 0.5; transform: translate(-50%, -50%) scale(1); }
  50% { opacity: 0.8; transform: translate(-50%, -50%) scale(1.1); }
}

.auth-title {
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0 0 1rem 0;
  background: var(--emerald-gradient);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.auth-description {
  color: var(--text-secondary);
  margin: 0 0 2rem 0;
  line-height: 1.6;
  font-size: 1.1rem;
}

.auth-description :deep(strong) {
  color: var(--primary);
  font-weight: 600;
}

.auth-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
  margin-bottom: 2rem;
}

.auth-login-btn {
  padding: 1rem 2rem !important;
  font-size: 1.1rem !important;
  font-weight: 600 !important;
  border-radius: 12px !important;
  text-transform: uppercase !important;
  letter-spacing: 0.5px !important;
  transition: all var(--transition-medium) !important;
}

.auth-login-btn:hover {
  transform: translateY(-2px) scale(1.02) !important;
  box-shadow: 0 8px 25px rgba(38, 166, 154, 0.3) !important;
}

/* Preview des fonctionnalités */
.auth-preview {
  display: flex;
  gap: 2rem;
  justify-content: center;
  margin-top: 2rem;
  padding-top: 2rem;
  border-top: 1px solid var(--surface-200);
  flex-wrap: wrap;
}

.preview-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem;
  border-radius: 12px;
  background: var(--surface-100);
  color: var(--text-secondary);
  font-size: 0.9rem;
  font-weight: 500;
  opacity: 0.7;
  transition: all var(--transition-medium);
  min-width: 120px;
}

.preview-section:hover {
  opacity: 1;
  transform: translateY(-2px);
  background: rgba(38, 166, 154, 0.1);
  color: var(--primary);
}

.preview-section i {
  font-size: 1.5rem;
}

/* Animations */
@keyframes slideInUp {
  from {
    opacity: 0;
    transform: translateY(40px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Responsive */
@media (max-width: 768px) {
  .auth-required-state {
    padding: 1rem 0;
  }
  
  .container {
    padding: 0 1rem;
  }
  
  .auth-content {
    padding: 2rem 1.5rem;
  }
  
  .auth-title {
    font-size: 1.5rem;
  }
  
  .auth-description {
    font-size: 1rem;
  }
  
  .auth-preview {
    flex-direction: column;
    gap: 1rem;
  }
  
  .preview-section {
    flex-direction: row;
    justify-content: center;
    min-width: auto;
  }
}

@media (max-width: 480px) {
  .auth-actions {
    flex-direction: column;
    gap: 0.75rem;
  }
  
  .auth-login-btn {
    width: 100%;
  }
  
  .auth-icon {
    font-size: 3rem;
  }
  
  .auth-icon-glow {
    width: 60px;
    height: 60px;
  }
}
</style>
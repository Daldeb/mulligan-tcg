<template>
  <header class="header-main">
    <div class="container">
      <!-- Header principal -->
      <div class="header-top">
        <!-- Logo -->
        <div class="logo-section">
          <router-link to="/" class="logo-link">
            MULLIGAN TCG
          </router-link>
          <span class="tagline">
            Votre hub TCG ultime
          </span>
        </div>

        <!-- Barre de recherche -->
        <div class="search-section">
          <div class="search-wrapper">
            <InputText 
              v-model="searchQuery"
              placeholder="Recherche d'utilisateurs"
              class="search-input"
            />
            <i class="pi pi-search search-icon"></i>
          </div>
        </div>

        <!-- Actions utilisateur -->
        <div class="user-actions">
          <!-- Messages -->
          <div class="action-item">
            <Button 
              icon="pi pi-envelope" 
              class="action-button"
              :class="{ 'has-badge': unreadMessages > 0 }"
              @click="openMessages"
            />
            <span v-if="unreadMessages > 0" class="notification-badge">
              {{ unreadMessages }}
            </span>
          </div>

          <!-- Profil / Connexion -->
          <template v-if="authStore.isAuthenticated">
            <!-- Section profil avec avatar + pseudo -->
            <div class="action-item profile-section" @click="goToProfile">
              <!-- Avatar avec photo -->
              <div class="avatar-container">
                <img 
                  v-if="authStore.user?.avatar"
                  :src="`${backendUrl}/uploads/${authStore.user.avatar}`"
                  class="user-avatar-image"
                  alt="Avatar"
                  @error="showFallbackAvatar = true"
                />
                <Avatar 
                  v-else
                  :label="authStore.user?.pseudo?.charAt(0).toUpperCase() ?? 'U'" 
                  shape="circle"
                  size="normal"
                  class="user-avatar-fallback"
                />
              </div>
              
              <!-- Pseudo -->
              <span class="user-pseudo">{{ authStore.user?.pseudo ?? 'Utilisateur' }}</span>
            </div>
            
            <!-- Bouton déconnexion -->
            <div class="action-item">
              <Button 
                icon="pi pi-sign-out"
                class="logout-button"
                @click="handleLogout"
                v-tooltip="'Se déconnecter'"
              />
            </div>
          </template>

          <template v-else>
            <div class="action-item">
              <Button 
                icon="pi pi-user" 
                label="Connexion"
                class="login-button"
                @click="$emit('open-login')"
              />
            </div>
          </template>
        </div>
      </div>

      <!-- Section NEWS ET ÉVÉNEMENTS -->
      <div class="news-section">
        <h2 class="news-title">NEWS ET EVENEMENTS</h2>
      </div>

      <!-- Navigation principale -->
      <nav class="main-nav">
        <div class="nav-buttons">
          <router-link 
            to="/discussions"
            class="nav-item"
            active-class="nav-active"
          >
            <Button 
              label="Discussions" 
              icon="pi pi-comments"
              class="nav-button"
            />
          </router-link>

          <router-link 
            to="/decks"
            class="nav-item"
            active-class="nav-active"
          >
            <Button 
              label="Decks" 
              icon="pi pi-clone"
              class="nav-button"
            />
          </router-link>

          <router-link 
            to="/classements"
            class="nav-item"
            active-class="nav-active"
          >
            <Button 
              label="Classements" 
              icon="pi pi-chart-bar"
              class="nav-button"
            />
          </router-link>

          <router-link 
            to="/boutiques"
            class="nav-item"
            active-class="nav-active"
          >
            <Button 
              label="Boutiques" 
              icon="pi pi-shopping-cart"
              class="nav-button"
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
})

// Props et émissions
defineEmits(['open-login'])

// Stores et router
const authStore = useAuthStore()
const router = useRouter()

// State local
const searchQuery = ref('')
const mobileMenuVisible = ref(false)
const unreadMessages = ref(3) // Exemple
const showFallbackAvatar = ref(false)

// Computed
const backendUrl = computed(() => import.meta.env.VITE_BACKEND_URL)

// Methods
const toggleMobileMenu = () => {
  mobileMenuVisible.value = !mobileMenuVisible.value
}

const openMessages = () => {
  router.push('/messages')
}

const goToProfile = () => {
  router.push('/profile')
}

const handleLogout = () => {
  authStore.logout()
  // Redirection vers la page d'accueil
  router.push('/')
}
</script>

<style scoped>
/* === HEADER EMERALD GAMING === */

.header-main {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 100;
  background: white;
  box-shadow: var(--shadow-medium);
  border-bottom: 1px solid var(--surface-200);
}

.container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 2rem;
}

/* Header top section */
.header-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 0;
  min-height: 70px;
}

/* Logo section */
.logo-section {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.logo-link {
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--text-primary);
  text-decoration: none;
  transition: color var(--transition-fast);
}

.logo-link:hover {
  color: var(--primary);
}

.tagline {
  font-size: 0.875rem;
  color: var(--accent);
  font-weight: 500;
  padding: 0.25rem 0.75rem;
  background: rgba(255, 87, 34, 0.1);
  border-radius: 20px;
  border: 1px solid rgba(255, 87, 34, 0.2);
}

/* Search section */
.search-section {
  flex: 1;
  max-width: 400px;
  margin: 0 2rem;
}

.search-wrapper {
  position: relative;
  width: 100%;
}

:deep(.search-input) {
  width: 100% !important;
  padding: 0.75rem 1rem 0.75rem 3rem !important;
  border: 2px solid var(--surface-300) !important;
  border-radius: 25px !important;
  background: var(--surface-100) !important;
  transition: all var(--transition-fast) !important;
  font-size: 0.9rem !important;
}

:deep(.search-input:focus) {
  border-color: var(--primary) !important;
  background: white !important;
  box-shadow: 0 0 0 3px rgba(38, 166, 154, 0.1) !important;
}

.search-icon {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-secondary);
  font-size: 1rem;
  pointer-events: none;
}

/* User actions */
.user-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.action-item {
  position: relative;
  display: flex;
  align-items: center;
}

/* 🆕 Section profil avec avatar + pseudo */
.profile-section {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem 1rem;
  border-radius: 25px;
  border: 2px solid var(--surface-300);
  background: var(--surface-100);
  cursor: pointer;
  transition: all var(--transition-fast);
}

.profile-section:hover {
  border-color: var(--primary);
  background: rgba(38, 166, 154, 0.1);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(38, 166, 154, 0.2);
}

.avatar-container {
  position: relative;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  overflow: hidden;
  border: 2px solid white;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.user-avatar-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 50%;
}

:deep(.user-avatar-fallback) {
  background: var(--primary) !important;
  color: white !important;
  width: 36px !important;
  height: 36px !important;
  font-size: 1rem !important;
}

.user-pseudo {
  font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--text-primary);
  letter-spacing: 0.3px;
  white-space: nowrap;
  text-overflow: ellipsis;
  overflow: hidden;
  max-width: 120px;
}

/* Bouton déconnexion */
:deep(.logout-button) {
  background: none !important;
  border: 2px solid rgba(255, 87, 34, 0.3) !important;
  color: var(--accent) !important;
  width: 44px !important;
  height: 44px !important;
  border-radius: 50% !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  transition: all var(--transition-fast) !important;
}

:deep(.logout-button:hover) {
  border-color: var(--accent) !important;
  color: white !important;
  background: var(--accent) !important;
  transform: scale(1.05) !important;
}

:deep(.action-button) {
  background: none !important;
  border: 2px solid var(--surface-300) !important;
  color: var(--text-secondary) !important;
  width: 44px !important;
  height: 44px !important;
  border-radius: 50% !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  transition: all var(--transition-fast) !important;
}

:deep(.action-button:hover) {
  border-color: var(--primary) !important;
  color: var(--primary) !important;
  background: rgba(38, 166, 154, 0.1) !important;
}

.notification-badge {
  position: absolute;
  top: -4px;
  right: -4px;
  background: var(--accent);
  color: white;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 2px 6px;
  border-radius: 10px;
  min-width: 18px;
  text-align: center;
  line-height: 1.2;
}

:deep(.login-button) {
  background: var(--primary) !important;
  border: none !important;
  color: white !important;
  padding: 0.75rem 1.5rem !important;
  border-radius: 25px !important;
  font-weight: 500 !important;
  transition: all var(--transition-fast) !important;
}

:deep(.login-button:hover) {
  background: var(--primary-dark) !important;
  transform: translateY(-1px) !important;
  box-shadow: 0 4px 12px rgba(38, 166, 154, 0.3) !important;
}

:deep(.mobile-menu-button) {
  background: none !important;
  border: 2px solid var(--surface-300) !important;
  color: var(--text-secondary) !important;
  width: 44px !important;
  height: 44px !important;
  border-radius: 8px !important;
  transition: all var(--transition-fast) !important;
}

:deep(.mobile-menu-button:hover) {
  border-color: var(--primary) !important;
  color: var(--primary) !important;
  background: rgba(38, 166, 154, 0.1) !important;
}

/* News section */
.news-section {
  background: var(--surface-100);
  border-top: 1px solid var(--surface-200);
  border-bottom: 1px solid var(--surface-200);
  padding: 0.75rem 0;
  text-align: center;
}

.news-title {
  font-size: 1rem;
  font-weight: 600;
  color: var(--text-secondary);
  letter-spacing: 2px;
  text-transform: uppercase;
  margin: 0;
}

/* Main navigation */
.main-nav {
  padding: 1rem 0;
  background: white;
}

.nav-buttons {
  display: flex;
  justify-content: center;
  gap: 1.5rem;
  flex-wrap: wrap;
}

.nav-item {
  text-decoration: none;
  transition: all var(--transition-fast);
}

:deep(.nav-button) {
  background: none !important;
  border: 2px solid var(--surface-300) !important;
  color: var(--text-secondary) !important;
  padding: 0.75rem 1.5rem !important;
  border-radius: 25px !important;
  font-weight: 500 !important;
  transition: all var(--transition-fast) !important;
  display: flex !important;
  align-items: center !important;
  gap: 0.5rem !important;
}

:deep(.nav-button:hover) {
  border-color: var(--primary) !important;
  color: var(--primary) !important;
  background: rgba(38, 166, 154, 0.1) !important;
  transform: translateY(-2px) !important;
}

.nav-active :deep(.nav-button) {
  background: var(--primary) !important;
  border-color: var(--primary) !important;
  color: white !important;
  box-shadow: 0 4px 12px rgba(38, 166, 154, 0.3) !important;
}

/* Responsive */
@media (max-width: 1024px) {
  .container {
    padding: 0 1rem;
  }
  
  .search-section {
    margin: 0 1rem;
    max-width: 300px;
  }
  
  .user-pseudo {
    max-width: 100px;
  }
}

@media (max-width: 768px) {
  .header-top {
    padding: 0.75rem 0;
    min-height: 60px;
  }
  
  .logo-link {
    font-size: 1.5rem;
  }
  
  .tagline {
    display: none;
  }
  
  .search-section {
    display: none;
  }
  
  .user-actions {
    gap: 0.75rem;
  }
  
  .profile-section {
    padding: 0.25rem 0.75rem;
  }
  
  .user-pseudo {
    font-size: 0.8rem;
    max-width: 80px;
  }
  
  .nav-buttons {
    gap: 0.75rem;
    justify-content: center;
  }
  
  :deep(.nav-button) {
    padding: 0.5rem 1rem !important;
    font-size: 0.875rem !important;
  }
}

@media (max-width: 640px) {
  .nav-buttons {
    gap: 0.5rem;
  }
  
  .user-pseudo {
    display: none;
  }
  
  .profile-section {
    padding: 0.5rem;
    border-radius: 50%;
    min-width: 44px;
    width: 44px;
    height: 44px;
    justify-content: center;
  }
  
  :deep(.nav-button .p-button-label) {
    display: none;
  }
  
  :deep(.nav-button) {
    width: 44px !important;
    height: 44px !important;
    padding: 0 !important;
    border-radius: 50% !important;
  }
}
</style>
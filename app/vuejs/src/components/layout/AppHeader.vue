<template>
  <header class="app-header">
    <div class="header-container">
      <!-- Logo Section -->
      <div class="logo-section">
        <router-link to="/" class="logo-link">
          <h1 class="logo-title">
            <span class="logo-main">MULLIGAN</span>
            <span class="logo-sub">TCG</span>
          </h1>
          <p class="logo-tagline">Votre hub TCG ultime</p>
        </router-link>
      </div>

      <!-- Search Section -->
      <div class="search-section">
        <div class="search-container">
          <i class="pi pi-search search-icon"></i>
          <InputText
            v-model="searchQuery"
            placeholder="Recherche d'utilisateurs"
            class="search-input"
            @focus="onSearchFocus"
            @blur="onSearchBlur"
            @input="onSearchInput"
          />
          
          <!-- Search Suggestions -->
          <div 
            v-if="showSuggestions && searchSuggestions.length" 
            class="search-suggestions fade-in-scale"
          >
            <div class="suggestions-header">
              <span class="text-sm text-secondary">Suggestions</span>
            </div>
            <div 
              v-for="suggestion in searchSuggestions" 
              :key="suggestion.id"
              class="suggestion-item"
              @click="selectSuggestion(suggestion)"
            >
              <Avatar 
                :image="suggestion.avatar" 
                size="small" 
                class="mr-3" 
              />
              <div class="suggestion-info">
                <span class="suggestion-name">{{ suggestion.name }}</span>
                <span class="suggestion-type">{{ suggestion.type }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- User Actions Section -->
      <div class="user-section">
        
        <!-- Si utilisateur NON connecté -->
        <div v-if="!authStore.isAuthenticated" class="auth-buttons">
          <Button
            label="Connexion"
            class="p-button-text login-btn"
            @click="openLoginModal('login')"
            icon="pi pi-sign-in"
          />
          <Button
            label="Inscription"
            class="p-button-outlined register-btn"
            @click="openLoginModal('register')"
            icon="pi pi-user-plus"
          />
        </div>

        <!-- Si utilisateur connecté -->
        <div v-else class="authenticated-section">
          <!-- Messages Button -->
          <div class="header-action">
            <Button
              icon="pi pi-envelope"
              class="header-action-btn"
              :badge="unreadMessages > 0 ? unreadMessages.toString() : null"
              badge-class="p-badge-danger"
              outlined
              rounded
              v-tooltip.bottom="'Messages'"
              @click="openMessages"
            />
          </div>

          <!-- Notifications Button -->
          <div class="header-action">
            <Button
              icon="pi pi-bell"
              class="header-action-btn"
              :badge="unreadNotifications > 0 ? unreadNotifications.toString() : null"
              badge-class="p-badge-info"
              outlined
              rounded
              v-tooltip.bottom="'Notifications'"
              @click="openNotifications"
            />
          </div>

          <!-- Profile Dropdown -->
          <div class="profile-dropdown" ref="profileDropdown">
            <Button
              class="profile-trigger-btn"
              @click="toggleProfileMenu"
              v-tooltip.bottom="'Profil'"
            >
              <Avatar 
                :label="userInitials"
                size="normal"
                class="profile-avatar-small"
              />
              <span class="profile-name">{{ authStore.user?.pseudo }}</span>
              <i class="pi pi-angle-down profile-arrow" :class="{ 'rotated': profileMenuVisible }"></i>
            </Button>
            
            <!-- Profile Menu -->
            <div 
              v-if="profileMenuVisible" 
              class="profile-menu fade-in-scale"
              @click.stop
            >
              <div class="profile-menu-header emerald-gradient">
                <Avatar 
                  :label="userInitials"
                  size="large" 
                  class="profile-avatar"
                />
                <div class="profile-info">
                  <h4 class="profile-name">{{ authStore.user?.fullName || authStore.user?.pseudo }}</h4>
                  <span class="profile-email">{{ authStore.user?.email }}</span>
                  <div class="profile-badges">
                    <Badge
                      v-for="role in authStore.userRoles"
                      :key="role"
                      :value="formatRole(role)"
                      :severity="getRoleSeverity(role)"
                      class="role-badge"
                    />
                    <Badge
                      v-if="authStore.user?.isVerified"
                      value="Vérifié"
                      severity="success"
                      class="verified-badge"
                    />
                  </div>
                </div>
              </div>
              
              <div class="profile-menu-items">
                <div 
                  class="menu-item"
                  @click="navigateTo('/profile')"
                >
                  <i class="pi pi-user"></i>
                  <span>Mon Profil</span>
                </div>
                <div 
                  class="menu-item"
                  @click="navigateTo('/my-decks')"
                >
                  <i class="pi pi-clone"></i>
                  <span>Mes Decks</span>
                </div>
                <div 
                  class="menu-item"
                  @click="navigateTo('/my-tournaments')"
                >
                  <i class="pi pi-trophy"></i>
                  <span>Mes Tournois</span>
                </div>
                
                <!-- Section admin (si admin) -->
                <div v-if="authStore.isAdmin">
                  <Divider />
                  <div 
                    class="menu-item admin-item"
                    @click="navigateTo('/admin')"
                  >
                    <i class="pi pi-shield"></i>
                    <span>Administration</span>
                  </div>
                </div>
                
                <Divider />
                
                <div 
                  class="menu-item"
                  @click="navigateTo('/settings')"
                >
                  <i class="pi pi-cog"></i>
                  <span>Préférences</span>
                </div>
                
                <div 
                  class="menu-item logout"
                  @click="handleLogout"
                >
                  <i class="pi pi-sign-out"></i>
                  <span>Déconnexion</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Mobile Menu Button -->
        <Button
          icon="pi pi-bars"
          class="mobile-menu-btn"
          outlined
          rounded
          @click="toggleMobileMenu"
          v-tooltip.bottom="'Menu'"
        />
      </div>
    </div>

    <!-- Navigation Tabs -->
    <NavigationTabs />

    <!-- Modal de connexion/inscription -->
    <LoginModal 
      v-model="showLoginModal" 
      :initial-mode="loginModalMode"
      @login-success="handleLoginSuccess"
      @registration-success="handleRegistrationSuccess"
    />
  </header>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '@/stores/auth'
import NavigationTabs from './NavigationTabs.vue'
import LoginModal from '@/components/modal/LoginModal.vue'

const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()

// Reactive state
const searchQuery = ref('')
const searchFocused = ref(false)
const profileMenuVisible = ref(false)
const showSuggestions = ref(false)
const showLoginModal = ref(false)
const loginModalMode = ref('login')
const unreadMessages = ref(3)
const unreadNotifications = ref(2)

// Mock search suggestions
const allSuggestions = ref([
  { 
    id: 1, 
    name: 'Alice Gaming', 
    type: 'Joueur Pro',
    avatar: 'https://i.pravatar.cc/32?img=2' 
  },
  { 
    id: 2, 
    name: 'Bob TCG Master', 
    type: 'Streameur',
    avatar: 'https://i.pravatar.cc/32?img=3' 
  },
  { 
    id: 3, 
    name: 'Charlie Deck Builder', 
    type: 'Créateur de decks',
    avatar: 'https://i.pravatar.cc/32?img=4' 
  },
  { 
    id: 4, 
    name: 'Diana Cardmaster', 
    type: 'Collectionneuse',
    avatar: 'https://i.pravatar.cc/32?img=5' 
  }
])

// Computed
const searchSuggestions = computed(() => {
  if (!searchQuery.value || searchQuery.value.length < 2) {
    return []
  }
  
  return allSuggestions.value.filter(suggestion =>
    suggestion.name.toLowerCase().includes(searchQuery.value.toLowerCase())
  ).slice(0, 4)
})

const userInitials = computed(() => {
  if (authStore.user?.firstName && authStore.user?.lastName) {
    return `${authStore.user.firstName[0]}${authStore.user.lastName[0]}`.toUpperCase()
  }
  if (authStore.user?.pseudo) {
    return authStore.user.pseudo.substring(0, 2).toUpperCase()
  }
  return 'U'
})

// Authentication methods
const openLoginModal = (mode = 'login') => {
  loginModalMode.value = mode
  showLoginModal.value = true
}

const handleLoginSuccess = () => {
  showLoginModal.value = false
  profileMenuVisible.value = false
  
  toast.add({
    severity: 'success',
    summary: 'Bienvenue !',
    detail: `Content de vous revoir, ${authStore.user?.pseudo} !`,
    life: 3000
  })
}

const handleRegistrationSuccess = () => {
  showLoginModal.value = false
  
  toast.add({
    severity: 'success',
    summary: 'Inscription réussie',
    detail: 'Vérifiez votre email pour activer votre compte',
    life: 5000
  })
}

const handleLogout = () => {
  profileMenuVisible.value = false
  
  const userName = authStore.user?.pseudo
  authStore.logout()
  
  toast.add({
    severity: 'info',
    summary: 'Déconnexion',
    detail: `À bientôt, ${userName} !`,
    life: 3000
  })
  
  router.push('/')
}

// Utility methods
const formatRole = (role) => {
  const roleMap = {
    'ROLE_USER': 'Utilisateur',
    'ROLE_ADMIN': 'Admin',
    'ROLE_MODERATOR': 'Modérateur',
    'ROLE_ORGANIZER': 'Organisateur'
  }
  return roleMap[role] || role
}

const getRoleSeverity = (role) => {
  const severityMap = {
    'ROLE_USER': 'secondary',
    'ROLE_ADMIN': 'danger',
    'ROLE_MODERATOR': 'warning',
    'ROLE_ORGANIZER': 'info'
  }
  return severityMap[role] || 'secondary'
}

// Search methods
const onSearchFocus = () => {
  searchFocused.value = true
  if (searchQuery.value.length >= 2) {
    showSuggestions.value = true
  }
}

const onSearchBlur = () => {
  setTimeout(() => {
    searchFocused.value = false
    showSuggestions.value = false
  }, 200)
}

const onSearchInput = () => {
  if (searchQuery.value.length >= 2) {
    showSuggestions.value = true
  } else {
    showSuggestions.value = false
  }
}

const selectSuggestion = (suggestion) => {
  searchQuery.value = suggestion.name
  showSuggestions.value = false
  
  toast.add({
    severity: 'info',
    summary: 'Navigation',
    detail: `Redirection vers le profil de ${suggestion.name}`,
    life: 2000
  })
}

// Navigation methods
const toggleProfileMenu = () => {
  profileMenuVisible.value = !profileMenuVisible.value
}

const toggleMobileMenu = () => {
  toast.add({
    severity: 'info',
    summary: 'Menu mobile',
    detail: 'Fonctionnalité à implémenter',
    life: 2000
  })
}

const openMessages = () => {
  toast.add({
    severity: 'info',
    summary: 'Messages',
    detail: `Vous avez ${unreadMessages.value} messages non lus`,
    life: 3000
  })
}

const openNotifications = () => {
  toast.add({
    severity: 'info',
    summary: 'Notifications',
    detail: `Vous avez ${unreadNotifications.value} notifications`,
    life: 3000
  })
}

const navigateTo = (route) => {
  profileMenuVisible.value = false
  
  toast.add({
    severity: 'success',
    summary: 'Navigation',
    detail: `Redirection vers ${route}`,
    life: 2000
  })
  
  router.push(route)
}

// Close menus when clicking outside
const handleClickOutside = (event) => {
  if (!event.target.closest('.profile-dropdown')) {
    profileMenuVisible.value = false
  }
  if (!event.target.closest('.search-container')) {
    showSuggestions.value = false
  }
}

// Lifecycle
onMounted(async () => {
  document.addEventListener('click', handleClickOutside)
  
  // Initialiser le store d'authentification
  await authStore.initialize()
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
.app-header {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1000;
  background: rgba(250, 250, 250, 0.95);
  backdrop-filter: blur(15px);
  border-bottom: 1px solid var(--surface-200);
  box-shadow: var(--shadow-small);
  animation: slideInDown 0.6s ease-out;
}

.header-container {
  display: grid;
  grid-template-columns: 1fr 2fr 1fr;
  align-items: center;
  padding: 0.75rem 2rem;
  max-width: 1400px;
  margin: 0 auto;
  gap: 2rem;
}

/* Logo Section */
.logo-section {
  display: flex;
  align-items: center;
}

.logo-link {
  text-decoration: none;
  color: inherit;
  transition: transform var(--transition-fast);
}

.logo-link:hover {
  transform: scale(1.02);
}

.logo-title {
  margin: 0;
  display: flex;
  align-items: baseline;
  gap: 0.5rem;
  margin-bottom: 2px;
}

.logo-main {
  font-size: 1.8rem;
  font-weight: 800;
  color: var(--text-primary);
  letter-spacing: -0.5px;
}

.logo-sub {
  font-size: 1.2rem;
  font-weight: 600;
  background: linear-gradient(135deg, var(--primary), var(--primary-light));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.logo-tagline {
  margin: 0;
  font-size: 0.75rem;
  color: var(--text-secondary);
  font-weight: 500;
}

/* Search Section */
.search-section {
  display: flex;
  justify-content: center;
}

.search-container {
  position: relative;
  width: 100%;
  max-width: 400px;
}

.search-icon {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-secondary);
  z-index: 1;
  transition: color var(--transition-fast);
}

.search-input {
  width: 100% !important;
  padding: 0.875rem 1rem 0.875rem 3rem !important;
  border: 2px solid var(--surface-300) !important;
  border-radius: 50px !important;
  background: var(--surface) !important;
  transition: all var(--transition-medium) !important;
  font-size: 0.9rem !important;
}

.search-input:focus {
  border-color: var(--primary) !important;
  box-shadow: 0 0 0 3px rgba(38, 166, 154, 0.1) !important;
  transform: scale(1.02);
}

.search-suggestions {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: var(--surface);
  border: 1px solid var(--surface-200);
  border-radius: var(--border-radius-large);
  box-shadow: var(--shadow-large);
  margin-top: 0.5rem;
  overflow: hidden;
  z-index: 10;
}

.suggestions-header {
  padding: 0.75rem 1rem 0.5rem;
  background: var(--surface-100);
  border-bottom: 1px solid var(--surface-200);
}

.suggestion-item {
  display: flex;
  align-items: center;
  padding: 0.75rem 1rem;
  cursor: pointer;
  transition: background var(--transition-fast);
}

.suggestion-item:hover {
  background: var(--surface-100);
  transform: translateX(4px);
}

.suggestion-info {
  display: flex;
  flex-direction: column;
}

.suggestion-name {
  font-weight: 600;
  color: var(--text-primary);
}

.suggestion-type {
  font-size: 0.8rem;
  color: var(--text-secondary);
}

/* User Section */
.user-section {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.75rem;
}

/* Auth Buttons (non connecté) */
.auth-buttons {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.login-btn {
  font-weight: 500 !important;
  color: var(--primary) !important;
}

.login-btn:hover {
  background: rgba(20, 184, 166, 0.1) !important;
}

.register-btn {
  border-color: var(--primary) !important;
  color: var(--primary) !important;
  font-weight: 500 !important;
}

.register-btn:hover {
  background: var(--primary) !important;
  color: white !important;
}

/* Authenticated Section */
.authenticated-section {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.header-action {
  position: relative;
}

.header-action-btn {
  transition: all var(--transition-medium) !important;
  border-color: var(--surface-300) !important;
}

.header-action-btn:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-small);
  border-color: var(--primary) !important;
}

/* Profile Dropdown */
.profile-dropdown {
  position: relative;
}

.profile-trigger-btn {
  display: flex !important;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem 1rem !important;
  border: 2px solid var(--surface-300) !important;
  border-radius: 50px !important;
  background: var(--surface) !important;
  transition: all var(--transition-medium) !important;
  font-weight: 500;
  color: var(--text-primary) !important;
}

.profile-trigger-btn:hover {
  border-color: var(--primary) !important;
  background: rgba(20, 184, 166, 0.05) !important;
  transform: translateY(-1px);
}

.profile-avatar-small {
  background: linear-gradient(135deg, var(--primary), var(--primary-light));
  color: white;
  font-weight: bold;
}

.profile-name {
  font-size: 0.9rem;
  max-width: 100px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.profile-arrow {
  transition: transform var(--transition-fast);
  font-size: 0.8rem;
}

.profile-arrow.rotated {
  transform: rotate(180deg);
}

.profile-menu {
  position: absolute;
  top: 100%;
  right: 0;
  width: 320px;
  background: var(--surface);
  border: 1px solid var(--surface-200);
  border-radius: var(--border-radius-large);
  box-shadow: var(--shadow-large);
  margin-top: 0.5rem;
  overflow: hidden;
  z-index: 20;
}

.profile-menu-header {
  padding: 1.5rem;
  color: var(--text-inverse);
  display: flex;
  align-items: center;
  gap: 1rem;
}

.profile-avatar {
  border: 2px solid rgba(255, 255, 255, 0.3);
  background: linear-gradient(135deg, var(--primary), var(--primary-light));
  color: white;
  font-weight: bold;
}

.profile-info {
  flex: 1;
}

.profile-info .profile-name {
  margin: 0;
  font-weight: 600;
  font-size: 1.1rem;
  max-width: none;
}

.profile-email {
  font-size: 0.85rem;
  opacity: 0.9;
  display: block;
  margin: 0.25rem 0 0.5rem 0;
}

.profile-badges {
  display: flex;
  gap: 0.25rem;
  flex-wrap: wrap;
}

.role-badge,
.verified-badge {
  font-size: 0.7rem;
}

.profile-menu-items {
  padding: 0.5rem 0;
}

.menu-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1.5rem;
  cursor: pointer;
  transition: all var(--transition-fast);
  font-weight: 500;
}

.menu-item:hover {
  background: var(--surface-100);
  transform: translateX(4px);
}

.menu-item i {
  width: 16px;
  color: var(--text-secondary);
}

.menu-item.admin-item {
  color: var(--accent);
}

.menu-item.admin-item i {
  color: var(--accent);
}

.menu-item.admin-item:hover {
  background: rgba(255, 87, 34, 0.1);
}

.menu-item.logout {
  color: var(--accent);
}

.menu-item.logout:hover {
  background: rgba(255, 87, 34, 0.1);
}

.menu-item.logout i {
  color: var(--accent);
}

.mobile-menu-btn {
  display: none;
  border-color: var(--surface-300) !important;
}

.mobile-menu-btn:hover {
  border-color: var(--primary) !important;
}

/* Animations */
.fade-in-scale {
  animation: fadeInScale 0.2s ease-out;
}

@keyframes fadeInScale {
  from {
    opacity: 0;
    transform: scale(0.95) translateY(-10px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

/* Responsive Design */
@media (max-width: 1024px) {
  .header-container {
    grid-template-columns: auto 1fr auto;
    gap: 1rem;
  }
  
  .search-container {
    max-width: 300px;
  }
  
  .profile-name {
    display: none;
  }
}

@media (max-width: 768px) {
  .header-container {
    padding: 0.75rem 1rem;
  }
  
  .logo-main {
    font-size: 1.5rem;
  }
  
  .logo-sub {
    font-size: 1rem;
  }
  
  .search-container {
    max-width: 250px;
  }
  
  .mobile-menu-btn {
    display: flex;
  }
  
  .profile-menu {
    width: 300px;
  }
  
  .auth-buttons {
    gap: 0.5rem;
  }
  
  .login-btn span,
  .register-btn span {
    display: none;
  }
}

@media (max-width: 640px) {
  .header-container {
    grid-template-columns: auto 1fr auto;
    gap: 0.5rem;
  }
  
  .user-section {
    gap: 0.5rem;
  }
  
  .search-container {
    max-width: none;
  }
  
  .authenticated-section .header-action:first-child {
    display: none;
  }
  
  .profile-menu {
    width: 280px;
    right: -1rem;
  }
}
</style>
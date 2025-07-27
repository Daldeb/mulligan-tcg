<template>
  <header class="app-header">
    <div class="header-container">
      <!-- Logo Section -->
      <div class="logo-section">
        <router-link to="/" class="logo-link">
          <div class="logo-content">
            <h1 class="logo-title">
              <span class="logo-main">MULLIGAN</span>
              <span class="logo-sub">TCG</span>
            </h1>
            <p class="logo-tagline">Votre hub TCG ultime</p>
          </div>
        </router-link>
      </div>

      <!-- Search Section -->
      <div class="search-section">
        <div class="search-container">
          <i class="pi pi-search search-icon"></i>
          <InputText
            v-model="searchQuery"
            placeholder="Rechercher des utilisateurs, decks, tournois..."
            class="search-input"
            @focus="onSearchFocus"
            @blur="onSearchBlur"
            @input="onSearchInput"
          />
          
          <!-- Search Suggestions -->
          <div 
            v-if="showSuggestions && searchSuggestions.length" 
            class="search-suggestions"
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
                :label="suggestion.initials"
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
            class="login-btn"
            @click="openLoginModal('login')"
            icon="pi pi-sign-in"
            outlined
          />
          <Button
            label="Inscription"
            class="register-btn"
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
              class="action-btn"
              :badge="unreadMessages > 0 ? unreadMessages.toString() : null"
              badge-class="p-badge-danger"
              outlined
              rounded
              @click="navigateTo('/messages')"
            />
          </div>

          <!-- Notifications Button -->
          <div class="header-action">
            <Button
              icon="pi pi-bell"
              class="action-btn"
              :badge="unreadNotifications > 0 ? unreadNotifications.toString() : null"
              badge-class="p-badge-info"
              outlined
              rounded
              @click="openNotifications"
            />
          </div>

          <!-- Profile Dropdown -->
          <div class="profile-dropdown" ref="profileDropdown">
            <Button
              class="profile-trigger"
              @click="toggleProfileMenu"
            >
              <Avatar 
                :label="userInitials"
                :image="authStore.user?.avatar"
                size="normal"
                class="profile-avatar"
              />
              <div class="profile-text">
                <span class="profile-name">{{ displayName }}</span>
                <div class="profile-roles">
                  <Badge
                    v-for="role in userRoles"
                    :key="role"
                    :value="formatRole(role)"
                    :severity="getRoleSeverity(role)"
                    class="role-badge"
                  />
                </div>
              </div>
              <i class="pi pi-angle-down profile-arrow" :class="{ 'rotated': profileMenuVisible }"></i>
            </Button>
            
            <!-- Profile Menu -->
            <div 
              v-if="profileMenuVisible" 
              class="profile-menu"
              @click.stop
            >
              <div class="profile-menu-header">
                <Avatar 
                  :label="userInitials"
                  :image="authStore.user?.avatar"
                  size="large" 
                  class="profile-avatar-large"
                />
                <div class="profile-info">
                  <h4 class="profile-full-name">{{ fullName }}</h4>
                  <span class="profile-email">{{ authStore.user?.email }}</span>
                  <div class="profile-badge-container">
                    <Badge
                      v-for="role in userRoles"
                      :key="role"
                      :value="formatRole(role)"
                      :severity="getRoleSeverity(role)"
                      class="role-badge-large"
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
                
                <div 
                  class="menu-item"
                  @click="navigateTo('/favorites')"
                >
                  <i class="pi pi-heart"></i>
                  <span>Favoris</span>
                </div>
                
                <!-- Section admin (si admin) -->
                <template v-if="authStore.isAdmin">
                  <Divider class="menu-divider" />
                  <div 
                    class="menu-item admin-item"
                    @click="navigateTo('/admin')"
                  >
                    <i class="pi pi-shield"></i>
                    <span>Administration</span>
                  </div>
                </template>
                
                <Divider class="menu-divider" />
                
                <div 
                  class="menu-item"
                  @click="navigateTo('/settings')"
                >
                  <i class="pi pi-cog"></i>
                  <span>Paramètres</span>
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
import { useAuthStore } from '@/stores/auth'
import NavigationTabs from './NavigationTabs.vue'
import LoginModal from '@/components/modal/LoginModal.vue'

const router = useRouter()
const authStore = useAuthStore()

// Reactive state
const searchQuery = ref('')
const searchFocused = ref(false)
const profileMenuVisible = ref(false)
const showSuggestions = ref(false)
const showLoginModal = ref(false)
const loginModalMode = ref('login')
const unreadMessages = ref(0) // Sera connecté à une vraie API plus tard
const unreadNotifications = ref(0) // Sera connecté à une vraie API plus tard

// Mock search suggestions - sera remplacé par une vraie API
const allSuggestions = ref([
  { 
    id: 1, 
    name: 'Alice Gaming', 
    type: 'Joueur Pro',
    initials: 'AG',
    avatar: null
  },
  { 
    id: 2, 
    name: 'Bob TCG Master', 
    type: 'Streameur',
    initials: 'BT',
    avatar: null
  },
  { 
    id: 3, 
    name: 'Charlie Deck Builder', 
    type: 'Créateur de decks',
    initials: 'CD',
    avatar: null
  }
])

// Computed properties
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

const displayName = computed(() => {
  return authStore.user?.pseudo || 'Utilisateur'
})

const fullName = computed(() => {
  if (authStore.user?.firstName && authStore.user?.lastName) {
    return `${authStore.user.firstName} ${authStore.user.lastName}`
  }
  return authStore.user?.pseudo || 'Utilisateur'
})

const userRoles = computed(() => {
  return authStore.userRoles.filter(role => role !== 'ROLE_USER') // On n'affiche pas le rôle USER de base
})

// Utility functions (comme dans ton Header original)
const formatRole = (role) => {
  const roleMap = {
    'ROLE_USER': 'Utilisateur',
    'ROLE_ADMIN': 'Admin',
    'ROLE_BOUTIQUE': 'Boutique',
    'ROLE_ORGANIZER': 'Organisateur'
  }
  return roleMap[role] || role
}

const getRoleSeverity = (role) => {
  const severityMap = {
    'ROLE_USER': 'secondary',
    'ROLE_ADMIN': 'danger',
    'ROLE_BOUTIQUE': 'warning',
    'ROLE_ORGANIZER': 'info'
  }
  return severityMap[role] || 'secondary'
}

// Authentication methods
const openLoginModal = (mode = 'login') => {
  loginModalMode.value = mode
  showLoginModal.value = true
}

const handleLoginSuccess = () => {
  showLoginModal.value = false
  profileMenuVisible.value = false
}

const handleRegistrationSuccess = () => {
  showLoginModal.value = false
}

const handleLogout = () => {
  profileMenuVisible.value = false
  authStore.logout()
  router.push('/')
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
  // Ici on naviguerait vers le profil de l'utilisateur
}

// Navigation methods
const toggleProfileMenu = () => {
  profileMenuVisible.value = !profileMenuVisible.value
}

const toggleMobileMenu = () => {
  // À implémenter
  console.log('Menu mobile')
}

const openNotifications = () => {
  // À implémenter
  console.log('Notifications')
}

const navigateTo = (route) => {
  profileMenuVisible.value = false
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
  background: #fafafa !important; /* Force un fond opaque */
  border-bottom: 1px solid var(--surface-200);
  box-shadow: var(--shadow-medium);
  backdrop-filter: none;
}

.header-container {
  display: grid;
  grid-template-columns: 280px 1fr 320px;
  align-items: center;
  padding: 0.75rem 2rem;
  max-width: 1400px;
  margin: 0 auto;
  gap: 2rem;
  background: #fafafa !important; /* Force un fond opaque aussi */
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

.logo-content {
  display: flex;
  flex-direction: column;
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
  max-width: 500px;
}

.search-icon {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-secondary);
  z-index: 1;
}

.search-input {
  width: 100% !important;
  padding: 0.75rem 1rem 0.75rem 3rem !important;
  border: 1px solid var(--surface-300) !important;
  border-radius: var(--border-radius) !important;
  background: var(--surface-100) !important;
  transition: all var(--transition-medium) !important;
  font-size: 0.9rem !important;
  color: var(--text-primary) !important;
}

.search-input:focus {
  border-color: var(--primary) !important;
  box-shadow: 0 0 0 2px rgba(38, 166, 154, 0.1) !important;
  background: var(--surface) !important;
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
  transition: all var(--transition-fast);
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
  gap: 1rem;
}

/* Auth Buttons - Style uniforme */
.auth-buttons {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.login-btn,
.register-btn {
  font-weight: 500 !important;
  color: var(--text-primary) !important;
  border-color: var(--surface-300) !important;
  background: var(--surface) !important;
  box-shadow: none !important;
  transition: all var(--transition-medium) !important;
}

.login-btn:hover,
.register-btn:hover {
  background: var(--surface-100) !important;
  border-color: var(--primary) !important;
  color: var(--primary) !important;
  box-shadow: none !important;
  transform: none !important;
}

.login-btn:focus,
.register-btn:focus {
  background: var(--surface) !important;
  border-color: var(--surface-300) !important;
  color: var(--text-primary) !important;
  box-shadow: 0 0 0 2px rgba(38, 166, 154, 0.2) !important;
  outline: none !important;
}

.login-btn:active,
.register-btn:active {
  background: var(--surface-100) !important;
  border-color: var(--primary) !important;
  color: var(--primary) !important;
  box-shadow: none !important;
  transform: none !important;
}

/* Authenticated Section */
.authenticated-section {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.header-action {
  position: relative;
}

.action-btn {
  transition: all var(--transition-medium) !important;
  border-color: var(--surface-300) !important;
  background: var(--surface) !important;
}

.action-btn:hover {
  border-color: var(--primary) !important;
  background: var(--surface-100) !important;
}

/* Profile Dropdown */
.profile-dropdown {
  position: relative;
}

.profile-trigger {
  display: flex !important;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem 1rem !important;
  border: 1px solid var(--surface-300) !important;
  border-radius: var(--border-radius) !important;
  background: var(--surface) !important;
  transition: all var(--transition-medium) !important;
  color: var(--text-primary) !important;
  min-width: 200px;
}

.profile-trigger:hover {
  border-color: var(--primary) !important;
  background: var(--surface-100) !important;
}

.profile-avatar {
  background: linear-gradient(135deg, var(--primary), var(--primary-light));
  color: white;
  font-weight: bold;
  flex-shrink: 0;
}

.profile-text {
  flex: 1;
  text-align: left;
  min-width: 0;
}

.profile-name {
  display: block;
  font-weight: 600;
  font-size: 0.9rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.profile-roles {
  display: flex;
  gap: 0.25rem;
  margin-top: 0.25rem;
  flex-wrap: wrap;
}

.role-badge {
  font-size: 0.65rem;
}

.profile-arrow {
  transition: transform var(--transition-fast);
  font-size: 0.8rem;
  flex-shrink: 0;
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
  animation: fadeInScale 0.2s ease-out;
}

.profile-menu-header {
  padding: 1.5rem;
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  color: white;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.profile-avatar-large {
  border: 2px solid rgba(255, 255, 255, 0.3);
  background: linear-gradient(135deg, var(--primary-light), var(--primary));
  color: white;
  font-weight: bold;
  flex-shrink: 0;
}

.profile-info {
  flex: 1;
  min-width: 0;
}

.profile-full-name {
  margin: 0 0 0.25rem 0;
  font-weight: 600;
  font-size: 1.1rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.profile-email {
  font-size: 0.85rem;
  opacity: 0.9;
  display: block;
  margin-bottom: 0.5rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.profile-badge-container {
  display: flex;
  gap: 0.25rem;
  flex-wrap: wrap;
}

.role-badge-large,
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
  flex-shrink: 0;
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

.menu-divider {
  margin: 0.5rem 0;
}

.mobile-menu-btn {
  display: none;
  border-color: var(--surface-300) !important;
  background: var(--surface) !important;
}

.mobile-menu-btn:hover {
  border-color: var(--primary) !important;
  background: var(--surface-100) !important;
}

/* Responsive Design */
@media (max-width: 1200px) {
  .header-container {
    grid-template-columns: 260px 1fr 280px;
    gap: 1.5rem;
    padding: 0.75rem 1.5rem;
  }
  
  .search-container {
    max-width: 400px;
  }
}

@media (max-width: 1024px) {
  .header-container {
    grid-template-columns: 240px 1fr 260px;
    gap: 1rem;
    padding: 0.75rem 1rem;
  }
  
  .search-container {
    max-width: 350px;
  }
  
  .profile-trigger {
    min-width: 180px;
  }
  
  .logo-main {
    font-size: 1.6rem;
  }
  
  .logo-sub {
    font-size: 1.1rem;
  }
}

@media (max-width: 768px) {
  .header-container {
    grid-template-columns: auto 1fr auto;
    padding: 0.75rem 1rem;
    gap: 1rem;
  }
  
  .logo-main {
    font-size: 1.4rem;
  }
  
  .logo-sub {
    font-size: 1rem;
  }
  
  .logo-tagline {
    font-size: 0.7rem;
  }
  
  .search-container {
    max-width: 300px;
  }
  
  .mobile-menu-btn {
    display: flex;
  }
  
  .profile-menu {
    width: 300px;
    right: -1rem;
  }
  
  .profile-trigger {
    min-width: 160px;
  }
  
  .authenticated-section {
    gap: 0.75rem;
  }
  
  .auth-buttons {
    gap: 0.5rem;
  }
  
  .auth-buttons .p-button {
    padding: 0.6rem 1rem !important;
    font-size: 0.85rem !important;
  }
}

@media (max-width: 640px) {
  .header-container {
    grid-template-columns: auto 1fr auto;
    gap: 0.75rem;
    padding: 0.5rem 1rem;
  }
  
  .user-section {
    gap: 0.5rem;
  }
  
  .search-container {
    max-width: 250px;
  }
  
  .search-input {
    padding: 0.65rem 0.75rem 0.65rem 2.5rem !important;
    font-size: 0.85rem !important;
  }
  
  .search-icon {
    left: 0.75rem;
  }
  
  .authenticated-section .header-action:first-child {
    display: none; /* Cache les messages sur mobile */
  }
  
  .profile-menu {
    width: 280px;
    right: -1rem;
  }
  
  .profile-trigger {
    min-width: 140px;
    padding: 0.5rem 0.75rem !important;
  }
  
  .profile-name {
    font-size: 0.8rem;
  }
  
  .auth-buttons {
    gap: 0.5rem;
  }
  
  .auth-buttons .p-button {
    padding: 0.5rem 0.75rem !important;
    font-size: 0.8rem !important;
    min-width: 80px;
  }
  
  .logo-main {
    font-size: 1.3rem;
  }
  
  .logo-sub {
    font-size: 0.9rem;
  }
  
  .logo-tagline {
    font-size: 0.65rem;
  }
}

@media (max-width: 480px) {
  .header-container {
    padding: 0.5rem 0.75rem;
    gap: 0.5rem;
  }
  
  .search-container {
    max-width: 200px;
  }
  
  .search-input {
    padding: 0.6rem 0.6rem 0.6rem 2.2rem !important;
    font-size: 0.8rem !important;
  }
  
  .search-icon {
    left: 0.6rem;
    font-size: 0.85rem;
  }
  
  .logo-main {
    font-size: 1.2rem;
  }
  
  .logo-sub {
    font-size: 0.85rem;
  }
  
  .logo-tagline {
    font-size: 0.6rem;
  }
  
  .profile-trigger {
    min-width: 120px;
    padding: 0.4rem 0.6rem !important;
  }
  
  .profile-text {
    display: none; /* Cache le texte sur très petit écran */
  }
  
  .auth-buttons .p-button {
    padding: 0.45rem 0.6rem !important;
    font-size: 0.75rem !important;
    min-width: 70px;
  }
  
  .action-btn {
    padding: 0.4rem !important;
  }
  
  .mobile-menu-btn {
    padding: 0.4rem !important;
  }
}

@media (max-width: 380px) {
  .header-container {
    padding: 0.4rem 0.5rem;
  }
  
  .search-container {
    max-width: 160px;
  }
  
  .auth-buttons .p-button span {
    display: none; /* Cache le texte des boutons sur très petit écran */
  }
  
  .auth-buttons .p-button {
    min-width: 40px;
    padding: 0.4rem !important;
  }
  
  .profile-trigger {
    min-width: 100px;
  }
  
  .logo-tagline {
    display: none; /* Cache le tagline sur très petit écran */
  }
}

/* Animation pour le menu profil */
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

/* Amélioration de l'accessibilité */
@media (prefers-reduced-motion: reduce) {
  * {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}

/* Support du mode sombre */
@media (prefers-color-scheme: dark) {
  .app-header {
    background: var(--surface-dark);
    border-bottom-color: var(--surface-400);
  }
  
  .header-container {
    background: var(--surface-dark);
  }
  
  .search-input {
    background: var(--surface-dark) !important;
    border-color: var(--surface-400) !important;
    color: var(--text-primary-dark) !important;
  }
  
  .search-input:focus {
    background: var(--surface-300) !important;
  }
  
  .profile-trigger {
    background: var(--surface-dark) !important;
    border-color: var(--surface-400) !important;
    color: var(--text-primary-dark) !important;
  }
  
  .action-btn {
    background: var(--surface-dark) !important;
    border-color: var(--surface-400) !important;
  }
  
  .mobile-menu-btn {
    background: var(--surface-dark) !important;
    border-color: var(--surface-400) !important;
  }
}
</style>
<template>
  <header class="header-main">
    <div class="container">
      <!-- Header principal -->
      <div class="header-top">
        <!-- Logo -->
        <div class="logo-section">
          <router-link to="/" class="logo-link">
            <img src="/favicon.png" alt="Mulligan TCG" class="logo-favicon" />
            MULLIGAN TCG
          </router-link>
          <span class="tagline">
            Votre hub TCG ultime
          </span>
        </div>

        <!-- Barre de recherche -->
<!-- Barre de recherche d'utilisateurs -->
        <div class="search-section">
          <div class="search-wrapper">
            <InputText 
              v-model="userSearchQuery"
              placeholder="Rechercher un utilisateur..."
              class="search-input"
              @focus="onSearchFocus"
              @blur="onSearchBlur"
            />
            <i class="pi pi-search search-icon"></i>
            
            <!-- Spinner de recherche -->
            <div v-if="showSearchSpinner" class="search-spinner">
              <i class="pi pi-spin pi-spinner"></i>
            </div>
            
            <!-- Dropdown des résultats -->
            <div 
              v-if="showSearchResults" 
              class="search-results-dropdown"
              @mousedown.prevent
            >
              <!-- Loading state -->
              <div v-if="isUserSearching" class="search-loading">
                <i class="pi pi-spin pi-spinner"></i>
                <span>Recherche en cours...</span>
              </div>
              
              <!-- Résultats -->
              <div v-else-if="hasSearchResults" class="search-results-list">
                <div 
                  v-for="user in userSearchResults" 
                  :key="user.id"
                  class="search-result-item"
                  @click="handleUserSelect(user)"
                >
                  <div class="user-result-avatar">
                    <img 
                      v-if="getUserAvatarUrl(user)"
                      :src="getUserAvatarUrl(user)"
                      class="result-avatar-image"
                      alt="Avatar"
                    />
                    <span v-else class="result-avatar-fallback">
                      {{ getUserInitials(user) }}
                    </span>
                  </div>
                  <div class="user-result-info">
                    <div class="user-result-name">{{ user.pseudo }}</div>
                    <div class="user-result-role">{{ formatUserRole(user.roles) }}</div>
                  </div>
                  <div class="user-result-arrow">
                    <i class="pi pi-chevron-right"></i>
                  </div>
                </div>
              </div>
              
              <!-- Aucun résultat -->
              <div v-else-if="userSearchQuery.length >= 2" class="search-no-results">
                <i class="pi pi-search"></i>
                <span>Aucun utilisateur trouvé</span>
              </div>
              
              <!-- Message d'aide -->
              <div v-else class="search-help">
                <span>Tapez au moins 2 caractères pour rechercher</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Actions utilisateur -->
        <div class="user-actions">
          <!-- 🆕 NOTIFICATIONS -->
          <template v-if="authStore.isAuthenticated">
            <div class="action-item">
              <Button 
                icon="pi pi-bell" 
                class="action-button notifications-button"
                :class="{ 'has-notifications': hasUnread }"
                @click="toggleNotifications"
                v-tooltip.bottom="'Notifications'"
              />
              <span v-if="displayBadge" class="notification-badge">
                {{ badgeText }}
              </span>
              
              <!-- Dropdown notifications -->
              <div 
                v-if="showNotifications" 
                class="notifications-dropdown"
                v-click-outside="closeNotifications"
              >
                <div class="notifications-header">
                  <h4>Notifications</h4>
                  <div class="notifications-actions">
                    <Button 
                      v-if="hasUnread"
                      label="Tout marquer lu"
                      icon="pi pi-check"
                      class="mark-all-btn"
                      size="small"
                      @click="handleMarkAllAsRead"
                    />
                  </div>
                </div>
                
                <div class="notifications-content">
                  <!-- Liste des notifications -->
                  <div v-if="notifications.length > 0" class="notifications-list">
                    <div 
                      v-for="notification in notifications" 
                      :key="notification.id"
                      class="notification-item"
                      :class="{ 'notification-unread': !notification.isRead }"
                      @click="handleNotificationClick(notification)"
                    >
                      <div class="notification-icon">
                        {{ notification.icon || '🔔' }}
                      </div>
                      <div class="notification-content">
                        <div class="notification-title">{{ notification.title }}</div>
                        <div class="notification-message">{{ truncateMessage(notification.message, 60) }}</div>
                        <div class="notification-time">{{ notification.timeAgo }}</div>
                      </div>
                    </div>
                  </div>
                  
                  <!-- État vide -->
                  <div v-else class="notifications-empty">
                    <i class="pi pi-bell-slash empty-icon"></i>
                    <p>Aucune notification</p>
                  </div>
                </div>
                
                <!-- Footer avec lien vers toutes les notifications -->
                <div class="notifications-footer">
                  <Button 
                    label="Voir toutes les notifications"
                    icon="pi pi-external-link"
                    class="view-all-btn"
                    @click="goToNotifications"
                    text
                  />
                </div>
              </div>
            </div>
          </template>

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
                v-tooltip.bottom="'Déconnexion'"
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
      
      <!-- Navigation principale -->
      <nav class="main-nav">
        <div class="nav-content">
          
          <!-- Blocs TCG -->
          <div v-if="props.isGameDataReady" class="game-sections">
            <div
              v-for="game in availableGames"
              :key="game.id"
              class="game-section"
              :class="{ 'selected': isSelected(game.id) }"
              @click="gameFilter.toggleGame(game.id)"
              :style="{ '--game-bg': `url(${game.image})` }"
            >
              <div class="game-name">
                {{ game.name }}
              </div>
            </div>
          </div>


          <!-- Boutons navigation -->
          <div class="nav-buttons">
            <button @click="handleForumsClick" :class="['nav-item', { 'nav-active': $route.path.startsWith('/forums') }]">
              <Button label="Discussions" icon="pi pi-comments" class="nav-button" />
            </button>
            <button @click="handleDecksClick" :class="['nav-item', { 'nav-active': $route.path.startsWith('/decks') }]">
              <Button label="Decks" icon="pi pi-clone" class="nav-button" />
            </button>
            
            <button @click="handleMyDecksClick" :class="['nav-item', { 'nav-active': $route.path === '/mes-decks' }]">
              <Button label="Mes Decks" icon="pi pi-user" class="nav-button" />
            </button>
            <button @click="handleEventsClick" :class="['nav-item', { 'nav-active': $route.path.startsWith('/evenements') && $route.path !== '/mes-evenements' }]">
              <Button label="Evenements" icon="pi pi-calendar" class="nav-button" />
            </button>
            <button @click="handleMyEventsClick" :class="['nav-item', { 'nav-active': $route.path === '/mes-evenements' }]">
              <Button label="Mes Evenements" icon="pi pi-calendar-plus" class="nav-button" />
            </button>
          </div>

        </div>
      </nav>
    </div>
  </header>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAuthStore } from '../../stores/auth'
import { useNotifications } from '../../composables/useNotifications'
import { useProfileNavigation } from '../../composables/useProfileNavigation'
import { useUserSearch } from '../../composables/useUserSearch'
import { useRouter } from 'vue-router'
import { useGameFilterStore } from '../../stores/gameFilter'
import { storeToRefs } from 'pinia'


const props = defineProps({
  isGameDataReady: Boolean
})

// Props et émissions
defineEmits(['open-login'])

// Stores et router
const authStore = useAuthStore()
const router = useRouter()

const { goToProfile } = useProfileNavigation()
const {
  searchQuery: userSearchQuery,
  searchResults: userSearchResults,
  isSearching: isUserSearching,
  showResults: showSearchResults,
  hasResults: hasSearchResults,
  showSpinner: showSearchSpinner,
  clearSearch,
  hideResults,
  formatUserRole,
  getAvatarUrl: getUserAvatarUrl,
  getUserInitials
} = useUserSearch()

//game choice filters
const gameFilter = useGameFilterStore()
const { selectedGames, availableGames } = storeToRefs(gameFilter)

const isSelected = (id) => selectedGames.value.includes(id)


const handleDecksClick = () => {
  if (!authStore.isAuthenticated) {
    router.push('/auth-required?config=decks&redirect=/decks')
  } else {
    router.push('/decks')
  }
}

const handleForumsClick = () => {
  if (!authStore.isAuthenticated) {
    router.push('/auth-required?config=forums&redirect=/forums')
  } else {
    router.push('/forums')
  }
}

const handleMyDecksClick = () => {
  if (!authStore.isAuthenticated) {
    router.push('/auth-required?config=myDecks&redirect=/mes-decks')
  } else {
    router.push('/mes-decks')
  }
}

const handleEventsClick = () => {
  if (!authStore.isAuthenticated) {
    router.push('/auth-required?config=events&redirect=/evenements')
  } else {
    router.push('/evenements')
  }
}

const handleMyEventsClick = () => {
  if (!authStore.isAuthenticated) {
    router.push('/auth-required?config=myEvents&redirect=/mes-evenements')
  } else {
    router.push('/mes-evenements')
  }
}

// 🆕 Composable notifications
const {
  notifications,
  unreadCount,
  hasUnread,
  displayBadge,
  badgeText,
  handleNotificationClick,
  markAllAsRead,
  initialize: initializeNotifications,
  cleanup: cleanupNotifications,
  truncateMessage
} = useNotifications()

// State local
const unreadMessages = ref(3) // Exemple
const showFallbackAvatar = ref(false)
const showNotifications = ref(false)
const searchFocused = ref(false)

// Gestion focus/blur pour la recherche
const onSearchFocus = () => {
  searchFocused.value = true
  if (userSearchQuery.value.length >= 2) {
    showSearchResults.value = true
  }
}

const onSearchBlur = () => {
  // Délai pour permettre le clic sur les résultats
  setTimeout(() => {
    searchFocused.value = false
    hideResults()
  }, 200)
}

// Gestion sélection utilisateur
const handleUserSelect = (user) => {
  goToProfile(user.id, user.pseudo)
  clearSearch()
}

// Computed
const backendUrl = computed(() => import.meta.env.VITE_BACKEND_URL)

// 🆕 Méthodes notifications
const toggleNotifications = () => {
  console.log('Toggle notifications clicked, current state:', showNotifications.value)
  showNotifications.value = !showNotifications.value
  console.log('New state:', showNotifications.value)
}

const closeNotifications = () => {
  showNotifications.value = false
}

const handleMarkAllAsRead = async () => {
  await markAllAsRead()
  showNotifications.value = false
}

const goToNotifications = () => {
  showNotifications.value = false
  router.push('/profile') 
}

// Methods existantes
const openMessages = () => {
  router.push('/messages')
}

const handleLogout = () => {
  // 🆕 Cleanup notifications avant logout
  cleanupNotifications()
  authStore.logout()
  router.push('/')
}

// 🆕 Directive click outside
const vClickOutside = {
  mounted(el, binding) {
    el.clickOutsideEvent = (event) => {
      // Récupérer l'élément bouton notifications
      const notificationButton = el.parentElement.querySelector('.notifications-button')
      
      // Vérifier si le clic est sur le dropdown, le bouton, ou leurs enfants
      const isClickOnDropdown = el === event.target || el.contains(event.target)
      const isClickOnButton = notificationButton && (notificationButton === event.target || notificationButton.contains(event.target))
      
      if (!isClickOnDropdown && !isClickOnButton) {
        binding.value()
      }
    }
    document.addEventListener('click', el.clickOutsideEvent)
  },
  unmounted(el) {
    document.removeEventListener('click', el.clickOutsideEvent)
  }
}

// Lifecycle
onMounted(async () => {
  // 🆕 Initialiser notifications si utilisateur connecté
  if (authStore.isAuthenticated) {
    await initializeNotifications()
  }
})

onUnmounted(() => {
  // 🆕 Cleanup au démontage du composant
  cleanupNotifications()
})

// 🆕 Watcher pour initialiser les notifications au login
// (sera géré par le store auth plus tard)
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
  display: flex;
  align-items: center;
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--text-primary);
  text-decoration: none;
  transition: color var(--transition-fast);
}

.logo-link:hover {
  color: var(--primary);
}

.logo-link:hover .logo-favicon {
  transform: scale(1.05);
}

.logo-favicon {
  width: 48px;
  height: 48px;
  margin-right: 0.75rem;
  border-radius: 8px;
  transition: transform var(--transition-fast);
  flex-shrink: 0;
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
/* Search section avec dropdown */
.search-section {
  flex: 1;
  max-width: 400px;
  margin: 0 2rem;
  position: relative;
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
  z-index: 2;
}

.search-spinner {
  position: absolute;
  right: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--primary);
  z-index: 2;
}

/* Dropdown des résultats de recherche */
.search-results-dropdown {
  position: absolute;
  top: calc(100% + 8px);
  left: 0;
  right: 0;
  background: white;
  border-radius: var(--border-radius-large);
  box-shadow: var(--shadow-large);
  border: 1px solid var(--surface-200);
  z-index: 1000;
  max-height: 320px;
  overflow-y: auto;
  animation: slideDown 0.2s ease-out;
}

.search-loading,
.search-no-results,
.search-help {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 1.5rem;
  color: var(--text-secondary);
  font-size: 0.875rem;
  text-align: center;
}

.search-results-list {
  padding: 0.5rem 0;
}

.search-result-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.875rem 1.25rem;
  cursor: pointer;
  transition: background var(--transition-fast);
  border-bottom: 1px solid var(--surface-100);
}

.search-result-item:hover {
  background: var(--surface-50);
}

.search-result-item:last-child {
  border-bottom: none;
}

.user-result-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  overflow: hidden;
  flex-shrink: 0;
  position: relative;
}

.result-avatar-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.result-avatar-fallback {
  width: 100%;
  height: 100%;
  background: var(--primary);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 1rem;
}

.user-result-info {
  flex: 1;
  min-width: 0;
}

.user-result-name {
  font-weight: 600;
  color: var(--text-primary);
  font-size: 0.9rem;
  line-height: 1.2;
  margin-bottom: 0.25rem;
}

.user-result-role {
  color: var(--text-secondary);
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-weight: 500;
}

.user-result-arrow {
  color: var(--text-secondary);
  font-size: 0.875rem;
  opacity: 0.6;
  transition: all var(--transition-fast);
}

.search-result-item:hover .user-result-arrow {
  opacity: 1;
  transform: translateX(2px);
  color: var(--primary);
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

/* 🆕 NOTIFICATIONS DROPDOWN */
.notifications-dropdown {
  position: absolute;
  top: calc(100% + 10px);
  right: 0;
  width: 380px;
  background: white;
  border-radius: var(--border-radius-large);
  box-shadow: var(--shadow-large);
  border: 1px solid var(--surface-200);
  z-index: 1000;
  animation: slideDown 0.2s ease-out;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.notifications-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid var(--surface-200);
  background: var(--surface-50);
  border-radius: var(--border-radius-large) var(--border-radius-large) 0 0;
}

.notifications-header h4 {
  margin: 0;
  font-size: 1rem;
  font-weight: 600;
  color: var(--text-primary);
}

:deep(.mark-all-btn) {
  background: none !important;
  border: 1px solid var(--primary) !important;
  color: var(--primary) !important;
  padding: 0.25rem 0.75rem !important;
  font-size: 0.8rem !important;
  border-radius: 15px !important;
}

:deep(.mark-all-btn:hover) {
  background: var(--primary) !important;
  color: white !important;
}

.notifications-content {
  max-height: 320px;
  overflow-y: auto;
}

.notifications-list {
  display: flex;
  flex-direction: column;
}

.notification-item {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid var(--surface-100);
  cursor: pointer;
  transition: background var(--transition-fast);
}

.notification-item:hover {
  background: var(--surface-50);
}

.notification-item:last-child {
  border-bottom: none;
}

.notification-unread {
  background: rgba(38, 166, 154, 0.05);
  border-left: 3px solid var(--primary);
}

.notification-icon {
  font-size: 1.5rem;
  flex-shrink: 0;
  margin-top: 0.25rem;
}

.notification-content {
  flex: 1;
  min-width: 0;
}

.notification-title {
  font-weight: 600;
  color: var(--text-primary);
  font-size: 0.9rem;
  margin-bottom: 0.25rem;
}

.notification-message {
  color: var(--text-secondary);
  font-size: 0.85rem;
  line-height: 1.4;
  margin-bottom: 0.5rem;
}

.notification-time {
  color: var(--text-secondary);
  font-size: 0.75rem;
  font-style: italic;
}

.notifications-empty {
  text-align: center;
  padding: 2rem 1.5rem;
  color: var(--text-secondary);
}

.empty-icon {
  font-size: 2rem;
  margin-bottom: 0.5rem;
  color: var(--surface-400);
}

.notifications-footer {
  padding: 1rem 1.5rem;
  border-top: 1px solid var(--surface-200);
  background: var(--surface-50);
  border-radius: 0 0 var(--border-radius-large) var(--border-radius-large);
}

:deep(.view-all-btn) {
  width: 100% !important;
  justify-content: center !important;
  color: var(--primary) !important;
  font-weight: 500 !important;
  padding: 0.5rem !important;
}

:deep(.view-all-btn:hover) {
  background: rgba(38, 166, 154, 0.1) !important;
}

/* 🆕 Bouton notifications avec badge */
:deep(.notifications-button) {
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

:deep(.notifications-button:hover) {
  border-color: var(--primary) !important;
  color: var(--primary) !important;
  background: rgba(38, 166, 154, 0.1) !important;
}

:deep(.notifications-button.has-notifications) {
  border-color: var(--primary) !important;
  color: var(--primary) !important;
  background: rgba(38, 166, 154, 0.1) !important;
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0% { box-shadow: 0 0 0 0 rgba(38, 166, 154, 0.4); }
  70% { box-shadow: 0 0 0 8px rgba(38, 166, 154, 0); }
  100% { box-shadow: 0 0 0 0 rgba(38, 166, 154, 0); }
}

/* Section profil avec avatar + pseudo */
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
  z-index: 10;
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
  padding: 0.5rem 0;
  background: white;
}

/* Navigation buttons avec authentification */
.nav-buttons {
  display: flex;
  justify-content: center;
  gap: 1.5rem;
  flex-wrap: wrap;
}

.nav-item {
  background: none;
  border: none;
  text-decoration: none;
  transition: all var(--transition-fast);
  cursor: pointer;
  border-radius: 25px;
  overflow: hidden;
  padding: 0;
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
  
  .notifications-dropdown {
    width: 320px;
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
  
  .logo-favicon {
    width: 40px;
    height: 40px;
    margin-right: 0.5rem;
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
  
  .notifications-dropdown {
    width: 300px;
    right: -50px;
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
  
  .notifications-dropdown {
    width: 280px;
    right: -100px;
  }
  
  .logo-favicon {
    width: 32px;
    height: 32px;
    margin-right: 0.5rem;
  }
}

.nav-content {
  display: flex;
  align-items: stretch;
  gap: 1rem;
  row-gap: 0rem; /* for wrap layout */
}

.game-sections {
  display: flex;
  gap: 1rem;
  flex-shrink: 0;
}

.game-section {
  width: 140px;
  height: 72px;
  background: #333;
  border-radius: 12px;
  cursor: pointer;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  filter: grayscale(100%);
  transition: filter 0.2s ease, box-shadow 0.2s ease;
}

.game-section.selected {
  filter: none;
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.2);
  transform: translateY(-3px) scale(1.02);
  z-index: 5;
}

.game-name {
  color: white;
  font-weight: 700;
  font-size: 0.85rem;
  text-align: center;
  z-index: 2;
  padding: 0 0.5rem;
}

/* placeholder for future image background */
.game-section::before {
  content: "";
  position: absolute;
  inset: 0;
  background-image: var(--game-bg);
  background-size: cover;
  background-position: center;
  opacity: 0.7;
  z-index: 1;
}

.nav-buttons {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
  justify-content: center;
  flex-grow: 1;
}

.game-section:hover {
  transform: translateY(-4px) scale(1.03);
  box-shadow: 0 6px 18px rgba(0, 0, 0, 0.25);
  z-index: 5;
}
</style>
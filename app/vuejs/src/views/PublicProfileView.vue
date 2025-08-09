<template>
  <div class="public-profile-page">
    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
      <div class="emerald-spinner"></div>
      <p>Chargement du profil...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-container">
      <i class="pi pi-exclamation-triangle error-icon"></i>
      <h3>Profil introuvable</h3>
      <p>{{ error }}</p>
      <RouterLink to="/forums" class="p-button emerald-button primary">
        <i class="pi pi-arrow-left"></i>
        Retour aux forums
      </RouterLink>
    </div>

    <!-- Profile Content -->
    <div v-else class="profile-container">
      <div class="profile-grid">
        
        <!-- Section principale -->
        <div class="main-profile">
          
          <!-- Header profil public -->
          <section class="profile-header slide-in-down">
            <Card class="gaming-card profile-header-card">
              <template #content>
                <div class="profile-header-content">
                  <!-- Avatar et info de base -->
                  <div class="avatar-section">
                    <div class="avatar-container">
                      <img 
                        v-if="profileData.user.avatar"
                        :src="`${backendUrl}/uploads/${profileData.user.avatar}`"
                        class="profile-avatar avatar-image"
                        alt="Avatar"
                      />
                      <Avatar 
                        v-else
                        :label="profileData.user.pseudo?.charAt(0).toUpperCase() ?? 'U'"
                        size="xlarge"
                        shape="circle"
                        class="profile-avatar"
                      />
                    </div>
                    
                    <div class="basic-info">
                      <div class="username-section">
                        <h1 class="username">{{ profileData.user.pseudo }}</h1>
                        <div class="role-badges">
                          <span :class="['role-badge', getUserRole()]">
                            <i :class="getRoleIcon(getUserRole())"></i>
                            {{ getRoleLabel(getUserRole()) }}
                          </span>
                        </div>
                      </div>
                      
                      <div v-if="profileData.user.bio" class="user-bio">
                        <p>{{ profileData.user.bio }}</p>
                      </div>
                      
                      <div v-if="profileData.user.favoriteClass" class="favorite-class">
                        <span class="class-badge">
                          <i class="pi pi-star"></i>
                          {{ profileData.user.favoriteClass }}
                        </span>
                      </div>
                      
                      <div class="profile-stats">
                        <div class="stat-item">
                          <span class="stat-value">{{ profileData.user.stats?.postsCount || 0 }}</span>
                          <span class="stat-label">Posts</span>
                        </div>
                        <div class="stat-item">
                          <span class="stat-value">{{ profileData.user.stats?.topicsParticipated || 0 }}</span>
                          <span class="stat-label">Discussions</span>
                        </div>
                        <div class="stat-item">
                          <span class="stat-value">{{ profileData.user.stats?.totalLikes || 0 }}</span>
                          <span class="stat-label">Likes reçus</span>
                        </div>
                        <div v-if="profileData.user.stats?.eventsCreated > 0" class="stat-item">
                          <span class="stat-value">{{ profileData.user.stats.eventsCreated }}</span>
                          <span class="stat-label">Événements</span>
                        </div>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Membre depuis -->
                  <div class="join-info">
                    <div class="join-date">
                      <i class="pi pi-calendar"></i>
                      <span>Membre depuis {{ formatJoinDate(profileData.user.createdAt) }}</span>
                    </div>
                  </div>
                </div>
              </template>
            </Card>
          </section>

          <!-- Section Boutique (si applicable) -->
          <section v-if="profileData.shop" class="shop-section slide-in-up">
            <Card class="gaming-card shop-card">
              <template #header>
                <div class="card-header-custom shop-header">
                  <i class="pi pi-shop header-icon"></i>
                  <h3 class="header-title">{{ profileData.shop.name }}</h3>
                </div>
              </template>
              <template #content>
                <div class="shop-content">
                  <div class="shop-info">
                    <div class="shop-logo-section">
                      <img 
                        v-if="profileData.shop.logo"
                        :src="`${backendUrl}/uploads/${profileData.shop.logo}`"
                        class="shop-logo"
                        alt="Logo boutique"
                      />
                      <Avatar 
                        v-else
                        :label="profileData.shop.name?.charAt(0).toUpperCase() ?? 'B'"
                        size="large"
                        shape="circle"
                        class="shop-logo-fallback"
                      />
                    </div>
                    
                    <div class="shop-details">
                      <div v-if="profileData.shop.description" class="shop-description">
                        <p>{{ profileData.shop.description }}</p>
                      </div>
                      
                      <div v-if="profileData.shop.address" class="shop-address">
                        <i class="pi pi-map-marker"></i>
                        <span>{{ profileData.shop.address.fullAddress }}</span>
                      </div>
                      
                      <div v-if="profileData.shop.website" class="shop-website">
                        <a :href="profileData.shop.website" target="_blank" class="website-link">
                          <i class="pi pi-external-link"></i>
                          Visiter le site
                        </a>
                      </div>
                      
                      <div v-if="profileData.shop.services && profileData.shop.services.length" class="shop-services">
                        <h4>Services proposés</h4>
                        <div class="services-list">
                          <span 
                            v-for="service in profileData.shop.services" 
                            :key="service"
                            class="service-tag"
                          >
                            {{ service }}
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </template>
            </Card>
          </section>

          <!-- Section Posts récents -->
          <section v-if="profileData.posts && profileData.posts.length" class="posts-section slide-in-up">
            <Card class="gaming-card posts-card">
              <template #header>
                <div class="card-header-custom posts-header">
                  <i class="pi pi-comments header-icon"></i>
                  <h3 class="header-title">Posts récents</h3>
                </div>
              </template>
              <template #content>
                <div class="posts-list">
                  <div 
                    v-for="post in profileData.posts" 
                    :key="post.id"
                    class="post-item"
                    @click="goToPost(post)"
                  >
                    <div class="post-header">
                      <h4 class="post-title">{{ post.title }}</h4>
                      <span class="post-type-badge" :class="`type-${post.postType}`">
                        <i :class="getPostTypeIcon(post.postType)"></i>
                      </span>
                    </div>
                    
                    <div class="post-meta">
                      <span class="post-forum">{{ post.forum.name }}</span>
                      <span class="post-date">{{ formatPostDate(post.createdAt) }}</span>
                    </div>
                    
                    <div class="post-stats">
                      <span class="post-score">
                        <i class="pi pi-heart"></i>
                        {{ post.score }}
                      </span>
                      <span class="post-comments">
                        <i class="pi pi-comment"></i>
                        {{ post.commentsCount }}
                      </span>
                    </div>
                    
                    <div v-if="post.tags && post.tags.length" class="post-tags">
                      <span v-for="tag in post.tags.slice(0, 3)" :key="tag" class="post-tag">
                        #{{ tag }}
                      </span>
                    </div>
                  </div>
                </div>
              </template>
            </Card>
          </section>

          <!-- Section Événements (si applicable) -->
          <section v-if="profileData.events && profileData.events.length" class="events-section slide-in-up">
            <Card class="gaming-card events-card">
              <template #header>
                <div class="card-header-custom events-header">
                  <i class="pi pi-calendar header-icon"></i>
                  <h3 class="header-title">Événements organisés</h3>
                </div>
              </template>
              <template #content>
                <div class="events-list">
                  <div 
                    v-for="event in profileData.events" 
                    :key="event.id"
                    class="event-item"
                    @click="goToEvent(event)"
                  >
                    <div class="event-header">
                      <h4 class="event-title">{{ event.title }}</h4>
                      <span :class="['event-status', event.status]">
                        {{ getEventStatusLabel(event.status) }}
                      </span>
                    </div>
                    
                    <div class="event-meta">
                      <span class="event-date">
                        <i class="pi pi-calendar"></i>
                        {{ formatEventDate(event.startDate) }}
                      </span>
                      <span class="event-participants">
                        <i class="pi pi-users"></i>
                        {{ event.participantsCount }} participants
                      </span>
                    </div>
                  </div>
                </div>
              </template>
            </Card>
          </section>
        </div>

        <!-- Sidebar -->
        <aside class="profile-sidebar">
          <!-- Widget Contact -->
          <Card class="sidebar-card contact-card slide-in-down">
            <template #header>
              <div class="card-header-custom contact-header">
                <i class="pi pi-user header-icon"></i>
                <h3 class="header-title">Profil</h3>
              </div>
            </template>
            <template #content>
              <div class="contact-info">
                <div class="profile-item">
                  <i class="pi pi-calendar"></i>
                  <span>Membre depuis {{ formatJoinDate(profileData.user.createdAt) }}</span>
                </div>
                
                <div v-if="getUserRole() !== 'user'" class="profile-item">
                  <i :class="getRoleIcon(getUserRole())"></i>
                  <span>{{ getRoleLabel(getUserRole()) }}</span>
                </div>
                
                <div v-if="profileData.user.favoriteClass" class="profile-item">
                  <i class="pi pi-star"></i>
                  <span>{{ profileData.user.favoriteClass }}</span>
                </div>
              </div>
            </template>
          </Card>

          <!-- Widget Statistiques -->
          <Card class="sidebar-card stats-card slide-in-down">
            <template #header>
              <div class="card-header-custom stats-header">
                <i class="pi pi-chart-bar header-icon"></i>
                <h3 class="header-title">Statistiques</h3>
              </div>
            </template>
            <template #content>
              <div class="stats-grid">
                <div class="stat-card">
                  <div class="stat-value">{{ profileData.user.stats?.postsCount || 0 }}</div>
                  <div class="stat-label">Posts créés</div>
                </div>
                <div class="stat-card">
                  <div class="stat-value">{{ profileData.user.stats?.topicsParticipated || 0 }}</div>
                  <div class="stat-label">Discussions</div>
                </div>
                <div class="stat-card">
                  <div class="stat-value">{{ profileData.user.stats?.totalLikes || 0 }}</div>
                  <div class="stat-label">Likes reçus</div>
                </div>
                <div v-if="profileData.user.stats?.eventsCreated > 0" class="stat-card">
                  <div class="stat-value">{{ profileData.user.stats.eventsCreated }}</div>
                  <div class="stat-label">Événements</div>
                </div>
              </div>
            </template>
          </Card>
        </aside>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'

const route = useRoute()
const router = useRouter()
const userId = route.params.userId

const profileData = ref({})
const loading = ref(true)
const error = ref('')

const backendUrl = computed(() => import.meta.env.VITE_BACKEND_URL)

// Méthodes utilitaires
const getUserRole = () => {
  const roles = profileData.value.user?.roles || []
  if (roles.includes('ROLE_ADMIN')) return 'admin'
  if (roles.includes('ROLE_SHOP')) return 'shop'
  if (roles.includes('ROLE_ORGANIZER')) return 'organizer'
  return 'user'
}

const getRoleIcon = (role) => {
  const icons = {
    user: 'pi pi-user',
    organizer: 'pi pi-calendar',
    shop: 'pi pi-shop',
    admin: 'pi pi-shield'
  }
  return icons[role] || 'pi pi-user'
}

const getRoleLabel = (role) => {
  const labels = {
    user: 'Utilisateur',
    organizer: 'Organisateur', 
    shop: 'Boutique',
    admin: 'Administrateur'
  }
  return labels[role] || 'Utilisateur'
}

const formatJoinDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', {
    month: 'long',
    year: 'numeric'
  })
}

const formatPostDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}

const formatEventDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })
}

const getPostTypeIcon = (type) => {
  switch (type) {
    case 'link': return 'pi pi-link'
    case 'image': return 'pi pi-image'
    default: return 'pi pi-align-left'
  }
}

const getEventStatusLabel = (status) => {
  const labels = {
    'upcoming': 'À venir',
    'ongoing': 'En cours',
    'finished': 'Terminé',
    'cancelled': 'Annulé'
  }
  return labels[status] || status
}

// Navigation
const goToPost = (post) => {
  router.push(`/forums/${post.forum.slug}/posts/${post.id}`)
}

const goToEvent = (event) => {
  router.push(`/evenements/${event.id}`)
}

// Chargement des données
const fetchPublicProfile = async () => {
  loading.value = true
  error.value = ''
  
  try {
    const response = await api.get(`/api/users/${userId}/public-profile`)
    profileData.value = response.data
  } catch (err) {
    console.error('Erreur chargement profil public:', err)
    if (err.response?.status === 404) {
      error.value = 'Cet utilisateur n\'existe pas.'
    } else {
      error.value = 'Impossible de charger le profil. Vérifiez votre connexion.'
    }
  } finally {
    loading.value = false
  }
}

onMounted(fetchPublicProfile)
</script>

<style scoped>
.public-profile-page {
  min-height: calc(100vh - var(--header-height));
  background: var(--surface-gradient);
  /* AVANT */
  /* padding: 2rem 0; */
  /* margin-top: var(--header-height); */
  
  /* APRÈS - Correction alignment header */
  margin-top: calc(-1 * var(--header-height));
  padding-top: calc(var(--header-height) + 2rem);
  padding-bottom: 2rem;
}

/* Loading & Error States */
.loading-container,
.error-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  text-align: center;
  color: var(--text-secondary);
}

.error-icon {
  font-size: 3rem;
  color: var(--accent);
  margin-bottom: 1rem;
}

.emerald-spinner {
  width: 48px;
  height: 48px;
  margin-bottom: 1rem;
  border: 3px solid var(--surface-300);
  border-top: 3px solid var(--primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Layout */
.profile-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 2rem;
}

.profile-grid {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 2rem;
  align-items: start;
}

/* Main profile column */
.main-profile {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

/* Profile header */
.profile-header-card {
  position: relative;
}

.profile-header-content {
  padding: 2rem;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 2rem;
}

.avatar-section {
  display: flex;
  gap: 2rem;
  align-items: center;
  flex: 1;
}

.avatar-container {
  position: relative;
}

:deep(.profile-avatar) {
  width: 120px !important;
  height: 120px !important;
  font-size: 3rem !important;
  background: var(--emerald-gradient) !important;
  color: white !important;
  border: 4px solid white !important;
  box-shadow: var(--shadow-medium) !important;
}

.avatar-image {
  width: 120px !important;
  height: 120px !important;
  border-radius: 50% !important;
  object-fit: cover !important;
  border: 4px solid white !important;
  box-shadow: var(--shadow-medium) !important;
  aspect-ratio: 1 / 1 !important;
}

.basic-info {
  flex: 1;
}

.username-section {
  margin-bottom: 1.5rem;
}

.username {
  font-size: 2rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0 0 0.75rem 0;
}

.role-badges {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.role-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-size: 0.875rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.role-badge.user {
  background: rgba(84, 110, 122, 0.1);
  color: var(--secondary);
  border: 1px solid rgba(84, 110, 122, 0.2);
}

.role-badge.organizer {
  background: rgba(38, 166, 154, 0.1);
  color: var(--primary);
  border: 1px solid rgba(38, 166, 154, 0.2);
}

.role-badge.shop {
  background: rgba(255, 87, 34, 0.1);
  color: var(--accent);
  border: 1px solid rgba(255, 87, 34, 0.2);
}

.role-badge.admin {
  background: linear-gradient(135deg, #8b5cf6, #a855f7);
  color: white;
  border: none;
}

.user-bio {
  margin-bottom: 1rem;
}

.user-bio p {
  color: var(--text-secondary);
  line-height: 1.6;
  margin: 0;
  font-style: italic;
}

.favorite-class {
  margin-bottom: 1.5rem;
}

.class-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: rgba(255, 193, 7, 0.1);
  color: #f59e0b;
  border: 1px solid rgba(255, 193, 7, 0.2);
  border-radius: 20px;
  font-size: 0.875rem;
  font-weight: 600;
}

.profile-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
  gap: 1.5rem;
}

.stat-item {
  text-align: center;
}

.stat-value {
  display: block;
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--primary);
  line-height: 1;
}

.stat-label {
  display: block;
  font-size: 0.875rem;
  color: var(--text-secondary);
  margin-top: 0.25rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.join-info {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.5rem;
}

.join-date {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--text-secondary);
  font-size: 0.875rem;
}

/* Shop section */
.shop-card {
  background: linear-gradient(135deg, rgba(255, 87, 34, 0.05), rgba(255, 87, 34, 0.02));
  border: 2px solid rgba(255, 87, 34, 0.1);
}

.shop-header {
  background: linear-gradient(135deg, var(--accent), var(--accent-dark));
}

.shop-content {
  padding: 2rem;
}

.shop-info {
  display: flex;
  gap: 2rem;
  align-items: flex-start;
}

.shop-logo-section {
  flex-shrink: 0;
}

.shop-logo {
  width: 80px;
  height: 80px;
  border-radius: 12px;
  object-fit: cover;
  border: 3px solid white;
  box-shadow: var(--shadow-medium);
}

:deep(.shop-logo-fallback) {
  width: 80px !important;
  height: 80px !important;
  font-size: 2rem !important;
  background: var(--accent-gradient) !important;
  color: white !important;
  border: 3px solid white !important;
  box-shadow: var(--shadow-medium) !important;
  border-radius: 12px !important;
}

.shop-details {
  flex: 1;
}

.shop-description {
  margin-bottom: 1rem;
}

.shop-description p {
  color: var(--text-secondary);
  line-height: 1.6;
  margin: 0;
}

.shop-address,
.shop-website {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
  color: var(--text-secondary);
  font-size: 0.875rem;
}

.website-link {
  color: var(--primary);
  text-decoration: none;
  transition: color var(--transition-fast);
}

.website-link:hover {
  color: var(--primary-dark);
}

.shop-services {
  margin-top: 1.5rem;
}

.shop-services h4 {
  margin: 0 0 1rem 0;
  color: var(--text-primary);
  font-size: 1rem;
  font-weight: 600;
}

.services-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.service-tag {
  padding: 0.25rem 0.75rem;
  background: var(--surface-200);
  color: var(--text-secondary);
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 500;
}

/* Posts section */
.posts-header {
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
}

.posts-list {
  padding: 2rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.post-item {
  background: var(--surface-100);
  border: 1px solid var(--surface-200);
  border-radius: var(--border-radius);
  padding: 1.5rem;
  cursor: pointer;
  transition: all var(--transition-fast);
}

.post-item:hover {
  background: white;
  border-color: var(--primary);
  transform: translateY(-2px);
  box-shadow: var(--shadow-medium);
}

.post-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
  margin-bottom: 1rem;
}

.post-title {
  font-size: 1rem;
  font-weight: 600;
  color: var(--text-primary);
  line-height: 1.3;
  margin: 0;
  flex: 1;
}

.post-type-badge {
  width: 28px;
  height: 28px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.875rem;
  flex-shrink: 0;
}

.post-type-badge.type-text {
  background: rgba(38, 166, 154, 0.1);
  color: var(--primary);
}

.post-type-badge.type-link {
  background: rgba(59, 130, 246, 0.1);
  color: #3b82f6;
}

.post-type-badge.type-image {
  background: rgba(236, 72, 153, 0.1);
  color: #ec4899;
}

.post-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  font-size: 0.875rem;
}

.post-forum {
  color: var(--primary);
  font-weight: 600;
  background: rgba(38, 166, 154, 0.1);
  padding: 0.25rem 0.75rem;
  border-radius: 8px;
}

.post-date {
  color: var(--text-secondary);
}

.post-stats {
  display: flex;
  gap: 1rem;
  margin-bottom: 1rem;
}

.post-score,
.post-comments {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.875rem;
  color: var(--text-secondary);
}

.post-score .pi {
  color: #ef4444;
}

.post-comments .pi {
  color: var(--primary);
}

.post-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.post-tag {
  background: var(--surface-200);
  color: var(--text-secondary);
  padding: 0.25rem 0.5rem;
  border-radius: 8px;
  font-size: 0.75rem;
  font-weight: 500;
}

/* Events section */
.events-header {
  background: linear-gradient(135deg, var(--accent), var(--accent-dark));
}

.events-list {
  padding: 2rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.event-item {
  background: var(--surface-100);
  border: 1px solid var(--surface-200);
  border-radius: var(--border-radius);
  padding: 1.5rem;
  cursor: pointer;
  transition: all var(--transition-fast);
}

.event-item:hover {
  background: white;
  border-color: var(--accent);
  transform: translateY(-2px);
  box-shadow: var(--shadow-medium);
}

.event-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
  margin-bottom: 1rem;
}

.event-title {
  font-size: 1rem;
  font-weight: 600;
  color: var(--text-primary);
  line-height: 1.3;
  margin: 0;
  flex: 1;
}

.event-status {
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.event-status.upcoming {
  background: rgba(59, 130, 246, 0.1);
  color: #3b82f6;
}

.event-status.ongoing {
  background: rgba(34, 197, 94, 0.1);
  color: #22c55e;
}

.event-status.finished {
  background: rgba(107, 114, 128, 0.1);
  color: #6b7280;
}

.event-status.cancelled {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
}

.event-meta {
  display: flex;
  gap: 2rem;
  font-size: 0.875rem;
  color: var(--text-secondary);
}

.event-date,
.event-participants {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

/* Sidebar */
.profile-sidebar {
  position: sticky;
  top: calc(var(--header-height) + 2rem);
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.sidebar-card {
  background: white;
  border-radius: var(--border-radius-large);
  box-shadow: var(--shadow-medium);
  border: 1px solid var(--surface-200);
  overflow: hidden;
}

.card-header-custom {
  padding: 1.5rem;
  color: white;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.contact-header {
  background: linear-gradient(135deg, #8b5cf6, #a855f7);
}

.stats-header {
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
}

.header-icon {
  font-size: 1.25rem;
}

.header-title {
  font-size: 1.1rem;
  font-weight: 600;
  margin: 0;
}

/* Contact info */
.contact-info {
  padding: 1.5rem;
}

.profile-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 0;
  border-bottom: 1px solid var(--surface-200);
  color: var(--text-secondary);
  font-size: 0.875rem;
}

.profile-item:last-child {
  border-bottom: none;
}

.profile-item .pi {
  color: var(--primary);
  width: 16px;
}

/* Stats grid */
.stats-grid {
  padding: 1.5rem;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.stat-card {
  text-align: center;
  padding: 1rem;
  background: var(--surface-100);
  border-radius: var(--border-radius);
  border: 1px solid var(--surface-200);
  transition: all var(--transition-fast);
}

.stat-card:hover {
  background: white;
  border-color: var(--primary);
  transform: translateY(-2px);
}

.stat-card .stat-value {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--primary);
  line-height: 1;
  margin-bottom: 0.25rem;
}

.stat-card .stat-label {
  font-size: 0.75rem;
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* Responsive Design */
@media (max-width: 1024px) {
  .profile-grid {
    grid-template-columns: 1fr;
    gap: 1.5rem;
  }
  
  .profile-sidebar {
    position: static;
    grid-row: 2;
  }
  
  .sidebar-card {
    display: grid;
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .profile-container {
    padding: 0 1rem;
  }
  
  .profile-header-content {
    flex-direction: column;
    gap: 1.5rem;
    padding: 1.5rem;
  }
  
  .avatar-section {
    flex-direction: column;
    text-align: center;
    gap: 1.5rem;
  }
  
  :deep(.profile-avatar),
  .avatar-image {
    width: 100px !important;
    height: 100px !important;
    font-size: 2.5rem !important;
  }
  
  .username {
    font-size: 1.5rem;
  }
  
  .profile-stats {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .shop-info {
    flex-direction: column;
    gap: 1.5rem;
  }
  
  .shop-logo,
  :deep(.shop-logo-fallback) {
    width: 60px !important;
    height: 60px !important;
    font-size: 1.5rem !important;
  }
  
  .post-header,
  .event-header {
    flex-direction: column;
    gap: 0.75rem;
    align-items: flex-start;
  }
  
  .post-meta,
  .event-meta {
    flex-direction: column;
    gap: 0.5rem;
    align-items: flex-start;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 640px) {
  .profile-stats {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
  
  .join-info {
    align-items: center;
  }
  
  .role-badges {
    justify-content: center;
  }
}
</style>
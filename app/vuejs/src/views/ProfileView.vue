<template>
    <div class="profile-view">
      <div class="container">
        
        <!-- Header de profil -->
        <div class="profile-header">
          <Card class="profile-card">
            <template #content>
              <div class="profile-banner">
                <div class="profile-avatar-section">
                  <div class="avatar-container">
                    <Avatar
                      :label="userInitials"
                      size="xlarge"
                      shape="circle"
                      class="profile-avatar"
                    />
                    <Button
                      icon="pi pi-camera"
                      class="p-button-rounded p-button-sm avatar-edit-btn"
                      @click="triggerAvatarUpload"
                      v-tooltip.bottom="'Modifier l\'avatar'"
                    />
                    <input
                      ref="avatarInput"
                      type="file"
                      accept="image/*"
                      style="display: none"
                      @change="handleAvatarUpload"
                    />
                  </div>
                  
                  <div class="profile-info">
                    <h1 class="profile-name">
                      {{ authStore.user?.fullName || authStore.user?.pseudo }}
                    </h1>
                    <p class="profile-pseudo">@{{ authStore.user?.pseudo }}</p>
                    <p class="profile-email">{{ authStore.user?.email }}</p>
                    
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
                    
                    <div class="profile-stats">
                      <div class="stat-item">
                        <span class="stat-number">{{ userStats.decks }}</span>
                        <span class="stat-label">Decks</span>
                      </div>
                      <div class="stat-item">
                        <span class="stat-number">{{ userStats.tournaments }}</span>
                        <span class="stat-label">Tournois</span>
                      </div>
                      <div class="stat-item">
                        <span class="stat-number">{{ userStats.winRate }}%</span>
                        <span class="stat-label">Victoires</span>
                      </div>
                    </div>
                  </div>
                </div>
                
                <div class="profile-actions">
                  <Button
                    label="Modifier le profil"
                    icon="pi pi-pencil"
                    @click="showEditModal = true"
                  />
                  <Button
                    label="Paramètres"
                    icon="pi pi-cog"
                    class="p-button-outlined"
                    @click="$router.push('/settings')"
                  />
                </div>
              </div>
            </template>
          </Card>
        </div>
  
        <!-- Contenu principal -->
        <div class="profile-content">
          <div class="content-grid">
            
            <!-- Colonne principale -->
            <div class="main-column">
              
              <!-- Bio -->
              <Card v-if="authStore.user?.bio" class="bio-card section-card">
                <template #header>
                  <div class="card-header">
                    <h3><i class="pi pi-info-circle mr-3"></i>À propos</h3>
                  </div>
                </template>
                <template #content>
                  <p class="bio-text">{{ authStore.user.bio }}</p>
                </template>
              </Card>
  
              <!-- Activité récente -->
              <Card class="activity-card section-card">
                <template #header>
                  <div class="card-header">
                    <h3><i class="pi pi-clock mr-3"></i>Activité récente</h3>
                    <Button
                      icon="pi pi-refresh"
                      class="p-button-text p-button-rounded"
                      @click="refreshActivity"
                      v-tooltip.bottom="'Actualiser'"
                    />
                  </div>
                </template>
                <template #content>
                  <div class="activity-list">
                    <div
                      v-for="activity in recentActivity"
                      :key="activity.id"
                      class="activity-item hover-lift"
                    >
                      <div class="activity-icon">
                        <i :class="activity.icon" :style="{ color: activity.color }"></i>
                      </div>
                      <div class="activity-content">
                        <p class="activity-text">{{ activity.text }}</p>
                        <span class="activity-time">{{ formatTime(activity.createdAt) }}</span>
                      </div>
                    </div>
                  </div>
                </template>
              </Card>
  
              <!-- Mes decks favoris -->
              <Card class="decks-card section-card">
                <template #header>
                  <div class="card-header">
                    <h3><i class="pi pi-clone mr-3"></i>Mes decks favoris</h3>
                    <Button
                      label="Voir tous"
                      class="p-button-text"
                      @click="$router.push('/my-decks')"
                    />
                  </div>
                </template>
                <template #content>
                  <div class="decks-grid">
                    <div
                      v-for="deck in favoriteDecks"
                      :key="deck.id"
                      class="deck-card hover-lift"
                      @click="viewDeck(deck)"
                    >
                      <div class="deck-image">
                        <i class="pi pi-clone deck-icon"></i>
                      </div>
                      <div class="deck-info">
                        <h4>{{ deck.name }}</h4>
                        <p class="deck-class">{{ deck.class }}</p>
                        <div class="deck-stats">
                          <span class="win-rate">{{ deck.winRate }}%</span>
                          <span class="games">{{ deck.games }} parties</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </template>
              </Card>
  
            </div>
  
            <!-- Sidebar -->
            <div class="sidebar-column">
              
              <!-- Informations rapides -->
              <Card class="info-card section-card">
                <template #header>
                  <div class="card-header">
                    <h3><i class="pi pi-user mr-3"></i>Informations</h3>
                  </div>
                </template>
                <template #content>
                  <div class="info-list">
                    <div class="info-item">
                      <i class="pi pi-calendar info-icon"></i>
                      <div>
                        <span class="info-label">Membre depuis</span>
                        <span class="info-value">{{ formatDate(authStore.user?.createdAt) }}</span>
                      </div>
                    </div>
                    <div class="info-item">
                      <i class="pi pi-clock info-icon"></i>
                      <div>
                        <span class="info-label">Dernière connexion</span>
                        <span class="info-value">{{ formatDate(authStore.user?.lastLoginAt) }}</span>
                      </div>
                    </div>
                    <div v-if="authStore.user?.favoriteClass" class="info-item">
                      <i class="pi pi-star info-icon"></i>
                      <div>
                        <span class="info-label">Classe favorite</span>
                        <span class="info-value">{{ authStore.user.favoriteClass }}</span>
                      </div>
                    </div>
                  </div>
                </template>
              </Card>
  
              <!-- Accomplissements -->
              <Card class="achievements-card section-card">
                <template #header>
                  <div class="card-header">
                    <h3><i class="pi pi-trophy mr-3"></i>Accomplissements</h3>
                  </div>
                </template>
                <template #content>
                  <div class="achievements-list">
                    <div
                      v-for="achievement in userAchievements"
                      :key="achievement.id"
                      class="achievement-item"
                      :class="{ unlocked: achievement.unlocked }"
                    >
                      <div class="achievement-icon">
                        <i :class="achievement.icon"></i>
                      </div>
                      <div class="achievement-info">
                        <h4>{{ achievement.name }}</h4>
                        <p>{{ achievement.description }}</p>
                        <div v-if="achievement.progress" class="achievement-progress">
                          <div class="progress-bar">
                            <div
                              class="progress-fill"
                              :style="{ width: `${achievement.progress}%` }"
                            ></div>
                          </div>
                          <span class="progress-text">{{ achievement.progress }}%</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </template>
              </Card>
  
              <!-- Actions rapides -->
              <Card class="quick-actions-card section-card">
                <template #header>
                  <div class="card-header">
                    <h3><i class="pi pi-bolt mr-3"></i>Actions rapides</h3>
                  </div>
                </template>
                <template #content>
                  <div class="quick-actions">
                    <Button
                      label="Créer un deck"
                      icon="pi pi-plus"
                      class="p-button-outlined w-full mb-3"
                      @click="createDeck"
                    />
                    <Button
                      label="Rejoindre un tournoi"
                      icon="pi pi-trophy"
                      class="p-button-outlined w-full mb-3"
                      @click="joinTournament"
                    />
                    <Button
                      label="Voir mes stats"
                      icon="pi pi-chart-bar"
                      class="p-button-outlined w-full"
                      @click="viewStats"
                    />
                  </div>
                </template>
              </Card>
  
            </div>
          </div>
        </div>
  
      </div>
  
      <!-- Modal d'édition de profil -->
      <EditProfileModal
        v-model="showEditModal"
        @profile-updated="handleProfileUpdate"
      />
  
      <!-- Toast -->
      <Toast />
    </div>
  </template>
  
  <script setup>
  import { ref, computed, onMounted } from 'vue'
  import { useRouter } from 'vue-router'
  import { useToast } from 'primevue/usetoast'
  import { useAuthStore } from '@/stores/auth'
  import EditProfileModal from '@/components/modal/EditProfileModal.vue'
  
  const router = useRouter()
  const toast = useToast()
  const authStore = useAuthStore()
  
  // State
  const showEditModal = ref(false)
  const avatarInput = ref(null)
  
  // Mock data - À remplacer par des données réelles depuis votre API
  const userStats = ref({
    decks: 12,
    tournaments: 8,
    winRate: 67
  })
  
  const recentActivity = ref([
    {
      id: 1,
      text: 'A participé au tournoi "Championship Series #12"',
      icon: 'pi pi-trophy',
      color: '#f59e0b',
      createdAt: '2025-01-26T10:30:00Z'
    },
    {
      id: 2,
      text: 'A créé un nouveau deck "Aggro Warrior v2"',
      icon: 'pi pi-clone',
      color: '#14b8a6',
      createdAt: '2025-01-25T15:45:00Z'
    },
    {
      id: 3,
      text: 'A atteint le rang Diamond',
      icon: 'pi pi-star',
      color: '#8b5cf6',
      createdAt: '2025-01-24T09:15:00Z'
    },
    {
      id: 4,
      text: 'A ajouté un deck aux favoris',
      icon: 'pi pi-heart',
      color: '#ef4444',
      createdAt: '2025-01-23T14:20:00Z'
    }
  ])
  
  const favoriteDecks = ref([
    {
      id: 1,
      name: 'Aggro Warrior',
      class: 'Guerrier',
      winRate: 72,
      games: 158
    },
    {
      id: 2,
      name: 'Control Mage',
      class: 'Mage',
      winRate: 68,
      games: 142
    },
    {
      id: 3,
      name: 'Midrange Hunter',
      class: 'Chasseur',
      winRate: 65,
      games: 127
    }
  ])
  
  const userAchievements = ref([
    {
      id: 1,
      name: 'Premier pas',
      description: 'Créer votre premier deck',
      icon: 'pi pi-check-circle',
      unlocked: true
    },
    {
      id: 2,
      name: 'Vétéran',
      description: 'Jouer 100 parties',
      icon: 'pi pi-star',
      unlocked: true
    },
    {
      id: 3,
      name: 'Champion',
      description: 'Gagner un tournoi',
      icon: 'pi pi-trophy',
      unlocked: false,
      progress: 75
    },
    {
      id: 4,
      name: 'Collectionneur',
      description: 'Posséder 50 decks',
      icon: 'pi pi-clone',
      unlocked: false,
      progress: 24
    }
  ])
  
  // Computed
  const userInitials = computed(() => {
    if (authStore.user?.firstName && authStore.user?.lastName) {
      return `${authStore.user.firstName[0]}${authStore.user.lastName[0]}`.toUpperCase()
    }
    if (authStore.user?.pseudo) {
      return authStore.user.pseudo.substring(0, 2).toUpperCase()
    }
    return 'U'
  })
  
  // Methods
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
  
  const formatDate = (dateString) => {
    if (!dateString) return 'Non disponible'
    
    const date = new Date(dateString)
    return date.toLocaleDateString('fr-FR', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    })
  }
  
  const formatTime = (dateString) => {
    const date = new Date(dateString)
    const now = new Date()
    const diffInHours = Math.floor((now - date) / (1000 * 60 * 60))
    
    if (diffInHours < 1) return 'Il y a moins d\'une heure'
    if (diffInHours < 24) return `Il y a ${diffInHours} heure${diffInHours > 1 ? 's' : ''}`
    
    const diffInDays = Math.floor(diffInHours / 24)
    if (diffInDays < 7) return `Il y a ${diffInDays} jour${diffInDays > 1 ? 's' : ''}`
    
    return formatDate(dateString)
  }
  
  const triggerAvatarUpload = () => {
    avatarInput.value?.click()
  }
  
  const handleAvatarUpload = (event) => {
    const file = event.target.files[0]
    if (file) {
      toast.add({
        severity: 'info',
        summary: 'Upload',
        detail: 'Fonctionnalité d\'upload d\'avatar à implémenter',
        life: 3000
      })
    }
  }
  
  const refreshActivity = () => {
    toast.add({
      severity: 'success',
      summary: 'Actualisation',
      detail: 'Activité mise à jour',
      life: 2000
    })
  }
  
  const viewDeck = (deck) => {
    toast.add({
      severity: 'info',
      summary: 'Deck',
      detail: `Consultation du deck ${deck.name}`,
      life: 2000
    })
  }
  
  const createDeck = () => {
    toast.add({
      severity: 'info',
      summary: 'Création',
      detail: 'Redirection vers le deck builder',
      life: 2000
    })
  }
  
  const joinTournament = () => {
    toast.add({
      severity: 'info',
      summary: 'Tournoi',
      detail: 'Recherche de tournois disponibles',
      life: 2000
    })
  }
  
  const viewStats = () => {
    toast.add({
      severity: 'info',
      summary: 'Statistiques',
      detail: 'Affichage des statistiques détaillées',
      life: 2000
    })
  }
  
  const handleProfileUpdate = () => {
    toast.add({
      severity: 'success',
      summary: 'Profil mis à jour',
      detail: 'Vos informations ont été sauvegardées',
      life: 3000
    })
  }
  
  onMounted(() => {
    // Vérifier si l'utilisateur est connecté
    if (!authStore.isAuthenticated) {
      router.push('/')
      toast.add({
        severity: 'warn',
        summary: 'Accès refusé',
        detail: 'Vous devez être connecté pour accéder à cette page',
        life: 3000
      })
    }
  })
  </script>
  
  <style scoped>
  .profile-view {
    min-height: calc(100vh - 140px);
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    padding: 2rem 0;
  }
  
  .container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 2rem;
  }
  
  .profile-header {
    margin-bottom: 2rem;
  }
  
  .profile-card {
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border: none;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    border-radius: 1rem;
  }
  
  .profile-banner {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 2rem;
  }
  
  .profile-avatar-section {
    display: flex;
    gap: 2rem;
    align-items: center;
  }
  
  .avatar-container {
    position: relative;
  }
  
  .profile-avatar {
    background: linear-gradient(135deg, #14b8a6, #059669);
    color: white;
    font-size: 2rem;
    font-weight: bold;
  }
  
  .avatar-edit-btn {
    position: absolute;
    bottom: 0;
    right: 0;
    background: #374151 !important;
    border: 3px solid white;
  }
  
  .profile-info h1 {
    margin: 0 0 0.5rem 0;
    color: #1e293b;
    font-size: 2rem;
    font-weight: 700;
  }
  
  .profile-pseudo {
    margin: 0 0 0.25rem 0;
    color: #14b8a6;
    font-weight: 600;
    font-size: 1.1rem;
  }
  
  .profile-email {
    margin: 0 0 1rem 0;
    color: #64748b;
  }
  
  .profile-badges {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
  }
  
  .role-badge,
  .verified-badge {
    font-size: 0.8rem;
  }
  
  .profile-stats {
    display: flex;
    gap: 2rem;
  }
  
  .stat-item {
    text-align: center;
  }
  
  .stat-number {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
    color: #14b8a6;
  }
  
  .stat-label {
    font-size: 0.9rem;
    color: #64748b;
  }
  
  .profile-actions {
    display: flex;
    gap: 1rem;
    align-items: flex-start;
  }
  
  .content-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
    align-items: start;
  }
  
  .main-column {
    display: flex;
    flex-direction: column;
    gap: 2rem;
  }
  
  .sidebar-column {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
  }
  
  .section-card {
    border-radius: 1rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    border: none;
  }
  
  .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem 1.5rem 1rem 1.5rem;
    background: linear-gradient(135deg, #f8fafc, #f1f5f9);
    margin: -1.5rem -1.5rem 1rem -1.5rem;
    border-bottom: 1px solid #e2e8f0;
    border-radius: 1rem 1rem 0 0;
  }
  
  .card-header h3 {
    margin: 0;
    color: #1e293b;
    font-size: 1.1rem;
    font-weight: 600;
    display: flex;
    align-items: center;
  }
  
  .bio-text {
    color: #374151;
    line-height: 1.6;
    margin: 0;
    font-size: 1rem;
  }
  
  .activity-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }
  
  .activity-item {
    display: flex;
    gap: 1rem;
    align-items: flex-start;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 0.75rem;
    cursor: pointer;
    transition: all 0.2s;
    border: 1px solid #e2e8f0;
  }
  
  .activity-item:hover {
    background: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  }
  
  .activity-icon {
    width: 2.5rem;
    height: 2.5rem;
    background: #f1f5f9;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }
  
  .activity-content {
    flex: 1;
  }
  
  .activity-text {
    margin: 0 0 0.25rem 0;
    color: #374151;
    font-size: 0.9rem;
    font-weight: 500;
  }
  
  .activity-time {
    color: #9ca3af;
    font-size: 0.8rem;
  }
  
  .decks-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
  }
  
  .deck-card {
    padding: 1.5rem;
    background: #f8fafc;
    border-radius: 0.75rem;
    cursor: pointer;
    transition: all 0.2s;
    text-align: center;
    border: 1px solid #e2e8f0;
  }
  
  .deck-card:hover {
    background: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  }
  
  .deck-image {
    margin-bottom: 1rem;
  }
  
  .deck-icon {
    font-size: 2rem;
    color: #14b8a6;
  }
  
  .deck-info h4 {
    margin: 0 0 0.5rem 0;
    color: #1e293b;
    font-size: 1rem;
    font-weight: 600;
  }
  
  .deck-class {
    color: #64748b;
    margin: 0 0 0.75rem 0;
    font-size: 0.9rem;
  }
  
  .deck-stats {
    display: flex;
    justify-content: space-between;
    font-size: 0.8rem;
  }
  
  .win-rate {
    color: #059669;
    font-weight: 600;
  }
  
  .games {
    color: #64748b;
  }
  
  .info-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }
  
  .info-item {
    display: flex;
    gap: 1rem;
    align-items: center;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 0.75rem;
    border: 1px solid #e2e8f0;
  }
  
  .info-icon {
    color: #14b8a6;
    font-size: 1.1rem;
    flex-shrink: 0;
  }
  
  .info-label {
    display: block;
    font-size: 0.8rem;
    color: #64748b;
    font-weight: 500;
  }
  
  .info-value {
    display: block;
    font-weight: 600;
    color: #374151;
    font-size: 0.9rem;
  }
  
  .achievements-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }
  
  .achievement-item {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    border-radius: 0.75rem;
    transition: all 0.2s;
    border: 1px solid #e2e8f0;
  }
  
  .achievement-item.unlocked {
    background: #ecfdf5;
    border-color: #d1fae5;
  }
  
  .achievement-item:not(.unlocked) {
    background: #f9fafb;
    border-color: #e5e7eb;
    opacity: 0.8;
  }
  
  .achievement-icon {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }
  
  .achievement-item.unlocked .achievement-icon {
    background: #059669;
    color: white;
  }
  
  .achievement-item:not(.unlocked) .achievement-icon {
    background: #e5e7eb;
    color: #9ca3af;
  }
  
  .achievement-info h4 {
    margin: 0 0 0.25rem 0;
    font-size: 0.9rem;
    color: #374151;
    font-weight: 600;
  }
  
  .achievement-info p {
    margin: 0 0 0.5rem 0;
    font-size: 0.8rem;
    color: #64748b;
  }
  
  .achievement-progress {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }
  
  .progress-bar {
    flex: 1;
    height: 0.5rem;
    background: #e5e7eb;
    border-radius: 0.25rem;
    overflow: hidden;
  }
  
  .progress-fill {
    height: 100%;
    background: #14b8a6;
    border-radius: 0.25rem;
    transition: width 0.3s;
  }
  
  .progress-text {
    font-size: 0.7rem;
    color: #64748b;
    font-weight: 600;
  }
  
  .quick-actions {
    display: flex;
    flex-direction: column;
  }
  
  /* Responsive */
  @media (max-width: 1024px) {
    .content-grid {
      grid-template-columns: 1fr;
      gap: 1.5rem;
    }
    
    .profile-banner {
      flex-direction: column;
      text-align: center;
    }
    
    .profile-avatar-section {
      flex-direction: column;
      text-align: center;
    }
  }
  
  @media (max-width: 768px) {
    .profile-view {
      padding: 1rem 0;
    }
    
    .container {
      padding: 0 1rem;
    }
    
    .profile-stats {
      justify-content: center;
      gap: 1.5rem;
    }
    
    .profile-actions {
      flex-direction: column;
      width: 100%;
    }
    
    .decks-grid {
      grid-template-columns: 1fr;
    }
  }
  
  @media (max-width: 640px) {
    .profile-info h1 {
      font-size: 1.5rem;
    }
    
    .profile-stats {
      flex-direction: column;
      gap: 1rem;
    }
    
    .card-header {
      flex-direction: column;
      gap: 1rem;
      text-align: center;
    }
    
    .activity-item {
      flex-direction: column;
      text-align: center;
    }
    
    .info-item {
      flex-direction: column;
      text-align: center;
    }
    
    .achievement-item {
      flex-direction: column;
      text-align: center;
    }
  }
  
  /* Hover effects */
  .hover-lift {
    transition: all 0.2s ease;
  }
  
  .hover-lift:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 15px -3px rgba(0, 0, 0, 0.1);
  }
  
  /* Animations */
  @keyframes fadeInUp {
    from {
      opacity: 0;
      transform: translateY(20px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }
  
  .section-card {
    animation: fadeInUp 0.6s ease-out;
  }
  
  .section-card:nth-child(1) { animation-delay: 0.1s; }
  .section-card:nth-child(2) { animation-delay: 0.2s; }
  .section-card:nth-child(3) { animation-delay: 0.3s; }
  .section-card:nth-child(4) { animation-delay: 0.4s; }
  </style>
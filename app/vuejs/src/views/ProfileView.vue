<template>
  <div class="profile-page">
    <div class="container">
      <div class="profile-grid">
        
        <!-- Colonne principale (2/3) -->
        <div class="main-profile">
          
          <!-- En-tête profil -->
          <section class="profile-header slide-in-down">
            <Card class="gaming-card profile-header-card">
              <template #content>
                <div class="profile-header-content">
                  <!-- Avatar et info de base -->
                  <div class="avatar-section">
                    <div class="avatar-container">
                      <img 
                        v-if="!avatarPreview && user.avatar"
                        :src="`${backendUrl}/uploads/${user.avatar}`"
                        class="profile-avatar avatar-image"
                        alt="Avatar"
                        @error="console.log('Image failed to load:', `${backendUrl}/uploads/${user.avatar}`)"
                        @load="console.log('Image loaded successfully:', `${backendUrl}/uploads/${user.avatar}`)"
                      />
                      <Avatar 
                        v-else-if="!avatarPreview && !user.avatar"
                        :label="user.pseudo?.charAt(0).toUpperCase() ?? 'U'"
                        size="xlarge"
                        shape="circle"
                        class="profile-avatar"
                      />
                      <img 
                        v-else
                        :src="avatarPreview"
                        class="profile-avatar avatar-preview"
                        alt="Prévisualisation avatar"
                      />
                      <button class="avatar-edit-btn" @click="openAvatarEditor" :disabled="isLoading">
                        <i class="pi pi-camera"></i>
                      </button>
                    </div>
                    
                    <div class="basic-info">
                      <div class="username-section">
                        <h1 class="username">{{ user.pseudo }}</h1>
                        <div class="role-badges">
                          <span :class="['role-badge', userRole]">
                            <i :class="getRoleIcon(userRole)"></i>
                            {{ getRoleLabel(userRole) }}
                          </span>
                          <span v-if="user.isVerified" class="verified-badge">
                            <i class="pi pi-verified"></i>
                            Vérifié
                          </span>
                        </div>
                      </div>
                      
                      <div class="profile-stats">
                        <div class="stat-item">
                          <span class="stat-value">{{ user.stats?.topics || 0 }}</span>
                          <span class="stat-label">Topics</span>
                        </div>
                        <div class="stat-item">
                          <span class="stat-value">{{ user.stats?.replies || 0 }}</span>
                          <span class="stat-label">Réponses</span>
                        </div>
                        <div class="stat-item">
                          <span class="stat-value">{{ user.stats?.events || 0 }}</span>
                          <span class="stat-label">Événements</span>
                        </div>
                        <div class="stat-item">
                          <span class="stat-value">{{ user.stats?.reputation || 0 }}</span>
                          <span class="stat-label">Réputation</span>
                        </div>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Actions rapides -->
                  <div class="quick-actions">
                    <Button 
                      icon="pi pi-pencil"
                      label="Modifier profil"
                      class="emerald-outline-btn"
                      @click="editMode = !editMode"
                    />
                    <Button 
                      icon="pi pi-sign-out"
                      class="logout-btn"
                      @click="handleLogout"
                      v-tooltip="'Se déconnecter'"
                    />
                  </div>
                </div>
              </template>
            </Card>
          </section>

          <!-- Section d'édition (si activée) -->
          <section v-if="editMode" class="edit-section slide-in-up">
            <Card class="gaming-card edit-card">
              <template #header>
                <div class="card-header-custom edit-header">
                  <i class="pi pi-user-edit header-icon"></i>
                  <h3 class="header-title">Modifier mon profil</h3>
                </div>
              </template>
              <template #content>
                <form @submit.prevent="saveProfile" class="edit-form">
                  <div class="form-grid">
                    <div class="field-group">
                      <label for="pseudo" class="field-label">Pseudo</label>
                      <InputText 
                        id="pseudo"
                        v-model="editForm.pseudo" 
                        class="emerald-input"
                        :class="{ 'error': !!editErrors.pseudo }"
                      />
                      <small v-if="editErrors.pseudo" class="field-error">{{ editErrors.pseudo }}</small>
                    </div>
                    
                    <div class="field-group">
                      <label for="favoriteClass" class="field-label">Classe favorite</label>
                      <InputText 
                        id="favoriteClass"
                        v-model="editForm.favoriteClass" 
                        placeholder="ex: Mage, Guerrier..."
                        class="emerald-input"
                      />
                    </div>
                  </div>
                  
                  <div class="form-grid">
                    <div class="field-group">
                      <label for="firstName" class="field-label">Prénom</label>
                      <InputText 
                        id="firstName"
                        v-model="editForm.firstName" 
                        class="emerald-input"
                        :class="{ 'error': !!editErrors.firstName }"
                      />
                      <small v-if="editErrors.firstName" class="field-error">{{ editErrors.firstName }}</small>
                    </div>
                    
                    <div class="field-group">
                      <label for="lastName" class="field-label">Nom</label>
                      <InputText 
                        id="lastName"
                        v-model="editForm.lastName" 
                        class="emerald-input"
                        :class="{ 'error': !!editErrors.lastName }"
                      />
                      <small v-if="editErrors.lastName" class="field-error">{{ editErrors.lastName }}</small>
                    </div>
                  </div>
                  
                  <div class="field-group">
                    <label for="bio" class="field-label">Biographie</label>
                    <Textarea 
                      id="bio"
                      v-model="editForm.bio" 
                      rows="4"
                      placeholder="Parlez-nous de vous et de votre passion pour les TCG..."
                      class="emerald-input"
                    />
                  </div>
                  
                  <div class="form-actions">
                    <Button 
                      type="submit"
                      label="Sauvegarder"
                      icon="pi pi-check"
                      class="emerald-btn"
                      :loading="isLoading"
                    />
                    <Button 
                      type="button"
                      label="Annuler"
                      icon="pi pi-times"
                      class="emerald-outline-btn"
                      @click="cancelEdit"
                    />
                  </div>
                </form>
              </template>
            </Card>
          </section>

          <!-- Section changement de rôle -->
          <section class="role-request-section slide-in-up">
            <Card class="gaming-card role-card">
              <template #header>
                <div class="card-header-custom role-header">
                  <i class="pi pi-users header-icon"></i>
                  <h3 class="header-title">Gestion des rôles</h3>
                </div>
              </template>
              <template #content>
                <div class="role-content">
                  <div class="current-role">
                    <h4 class="role-section-title">Rôle actuel</h4>
                    <div class="current-role-display">
                      <span :class="['role-badge', 'large', userRole]">
                        <i :class="getRoleIcon(userRole)"></i>
                        {{ getRoleLabel(userRole) }}
                      </span>
                      <p class="role-description">
                        {{ getRoleDescription(userRole) }}
                      </p>
                    </div>
                  </div>
                  
                  <div v-if="userRole === 'user'" class="role-upgrade">
                    <h4 class="role-section-title">Demander un nouveau rôle</h4>
                    <div class="role-options">
                      
                      <!-- Option Organisateur -->
                      <div class="role-option">
                        <div class="role-option-header">
                          <span class="role-badge organizer">
                            <i class="pi pi-calendar"></i>
                            Organisateur
                          </span>
                          <span class="role-benefits">Organiser des tournois et événements</span>
                        </div>
                        <p class="role-option-description">
                          Créez et gérez des tournois, organisez des événements communautaires, 
                          et animez la scène TCG locale.
                        </p>
                        <Button 
                          label="Demander le rôle"
                          icon="pi pi-calendar"
                          class="emerald-outline-btn role-request-btn"
                          @click="requestRole('organizer')"
                          :disabled="hasRequestPending('organizer')"
                        />
                      </div>
                      
                      <!-- Option Boutique -->
                      <div class="role-option">
                        <div class="role-option-header">
                          <span class="role-badge shop">
                            <i class="pi pi-shop"></i>
                            Boutique
                          </span>
                          <span class="role-benefits">Vendre des produits et organiser des événements</span>
                        </div>
                        <p class="role-option-description">
                          Gérez votre boutique physique, vendez des produits, organisez des tournois 
                          et événements dans votre établissement.
                        </p>
                        <Button 
                          label="Demander le rôle"
                          icon="pi pi-shop"
                          class="emerald-outline-btn role-request-btn"
                          @click="requestRole('shop')"
                          :disabled="hasRequestPending('shop')"
                        />
                      </div>
                    </div>
                  </div>
                  
                  <!-- Demandes en cours -->
                  <div v-if="roleRequests.length > 0" class="pending-requests">
                    <h4 class="role-section-title">Demandes en cours</h4>
                    <div class="request-list">
                      <div 
                        v-for="request in roleRequests" 
                        :key="request.id"
                        class="request-item"
                      >
                        <div class="request-info">
                          <span :class="['role-badge', getRoleClassFromString(request.requestedRole)]">
                            <i :class="getRoleIcon(getRoleClassFromString(request.requestedRole))"></i>
                            {{ getRoleLabel(getRoleClassFromString(request.requestedRole)) }}
                          </span>
                          <span class="request-date">
                            Demandé le {{ formatDate(request.createdAt) }}
                          </span>
                        </div>
                        <span :class="['request-status', request.status]">
                          {{ getStatusLabel(request.status) }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </template>
            </Card>
          </section>
        </div>

        <!-- Sidebar droite (1/3) -->
        <aside class="profile-sidebar">
          
          <!-- Widget Activité récente -->
          <Card class="sidebar-card activity-card slide-in-down">
            <template #header>
              <div class="card-header-custom activity-header">
                <i class="pi pi-history header-icon"></i>
                <h3 class="header-title">Activité récente</h3>
              </div>
            </template>
            <template #content>
              <div class="activity-list">
                <div 
                  v-for="activity in recentActivity" 
                  :key="activity.id"
                  class="activity-item"
                >
                  <div class="activity-icon">
                    <i :class="activity.icon"></i>
                  </div>
                  <div class="activity-content">
                    <p class="activity-text">{{ activity.text }}</p>
                    <span class="activity-time">{{ activity.time }}</span>
                  </div>
                </div>
                
                <div v-if="recentActivity.length === 0" class="empty-activity">
                  <i class="pi pi-inbox empty-icon"></i>
                  <p class="empty-text">Aucune activité récente</p>
                </div>
              </div>
            </template>
          </Card>
          
          <!-- Widget Mes Topics -->
          <Card class="sidebar-card topics-card slide-in-down">
            <template #header>
              <div class="card-header-custom topics-header">
                <i class="pi pi-comments header-icon"></i>
                <h3 class="header-title">Mes topics</h3>
              </div>
            </template>
            <template #content>
              <div class="topics-summary">
                <div class="topic-stats">
                  <div class="topic-stat">
                    <span class="stat-value">{{ user.stats?.topicsCreated || 0 }}</span>
                    <span class="stat-label">Créés</span>
                  </div>
                  <div class="topic-stat">
                    <span class="stat-value">{{ user.stats?.topicsParticipated || 0 }}</span>
                    <span class="stat-label">Participés</span>
                  </div>
                </div>
                
                <Button 
                  label="Voir tous mes topics"
                  icon="pi pi-external-link"
                  class="emerald-outline-btn small"
                  @click="goToMyTopics"
                />
              </div>
            </template>
          </Card>
          
          <!-- Widget Événements (si organisateur/boutique) -->
          <Card 
            v-if="['organizer', 'shop'].includes(userRole)" 
            class="sidebar-card events-card slide-in-down"
          >
            <template #header>
              <div class="card-header-custom events-header">
                <i class="pi pi-calendar header-icon"></i>
                <h3 class="header-title">Mes événements</h3>
              </div>
            </template>
            <template #content>
              <div class="events-summary">
                <div class="event-stats">
                  <div class="event-stat">
                    <span class="stat-value">{{ user.stats?.eventsActive || 0 }}</span>
                    <span class="stat-label">Actifs</span>
                  </div>
                  <div class="event-stat">
                    <span class="stat-value">{{ user.stats?.eventsTotal || 0 }}</span>
                    <span class="stat-label">Total</span>
                  </div>
                </div>
                
                <div class="event-actions">
                  <Button 
                    label="Créer un événement"
                    icon="pi pi-plus"
                    class="emerald-btn small"
                    @click="createEvent"
                  />
                  <Button 
                    label="Gérer mes événements"
                    icon="pi pi-cog"
                    class="emerald-outline-btn small"
                    @click="manageEvents"
                  />
                </div>
              </div>
            </template>
          </Card>
        </aside>
      </div>
    </div>
    
    <!-- Modal de confirmation déconnexion -->
    <Dialog 
      v-model:visible="showLogoutConfirm"
      modal
      header="Confirmation"
      :style="{ width: '400px' }"
      class="emerald-modal"
    >
      <p>Êtes-vous sûr de vouloir vous déconnecter ?</p>
      <template #footer>
        <Button 
          label="Annuler" 
          icon="pi pi-times" 
          class="emerald-outline-btn"
          @click="showLogoutConfirm = false" 
        />
        <Button 
          label="Déconnecter" 
          icon="pi pi-sign-out" 
          class="emerald-btn"
          @click="confirmLogout" 
        />
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import api from '../services/api'

// Stores et router
const authStore = useAuthStore()
const router = useRouter()
const toast = useToast()

// State
const editMode = ref(false)
const isLoading = ref(false)
const showLogoutConfirm = ref(false)
const avatarFile = ref(null)
const avatarPreview = ref(null)

const editForm = reactive({
  pseudo: '',
  firstName: '',
  lastName: '',
  bio: '',
  favoriteClass: ''
})

const editErrors = reactive({
  pseudo: '',
  firstName: '',
  lastName: '',
  bio: ''
})

// Données utilisateur réelles depuis le store
const user = computed(() => authStore.user || {})
const roleRequests = ref([])
const recentActivity = ref([])

// Computed
const isCurrentUser = computed(() => {
  return authStore.user?.id === user.value.id
})

const userRole = computed(() => {
  const roles = user.value.roles || []
  if (roles.includes('ROLE_ADMIN')) return 'admin'
  if (roles.includes('ROLE_SHOP')) return 'shop'
  if (roles.includes('ROLE_ORGANIZER')) return 'organizer'
  return 'user'
})

const backendUrl = computed(() => import.meta.env.VITE_BACKEND_URL)

// Methods
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

const getRoleDescription = (role) => {
  const descriptions = {
    user: 'Participant aux discussions et événements de la communauté.',
    organizer: 'Organisateur d\'événements et de tournois TCG.',
    shop: 'Boutique physique proposant produits et événements.',
    admin: 'Administrateur de la plateforme.'
  }
  return descriptions[role] || 'Rôle utilisateur standard.'
}

const getRoleClassFromString = (roleString) => {
  if (roleString === 'ROLE_ORGANIZER') return 'organizer'
  if (roleString === 'ROLE_SHOP') return 'shop'
  if (roleString === 'ROLE_ADMIN') return 'admin'
  return 'user'
}

const getStatusLabel = (status) => {
  const labels = {
    pending: 'En attente',
    approved: 'Approuvée',
    rejected: 'Refusée'
  }
  return labels[status] || 'Inconnu'
}

const hasRequestPending = (role) => {
  return roleRequests.value.some(req => req.requestedRole === `ROLE_${role.toUpperCase()}` && req.status === 'pending')
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('fr-FR')
}

// Actions
const loadUserProfile = async () => {
  try {
    const response = await api.get('/api/profile')
    // Mettre à jour le store avec les données fraîches
    authStore.user = response.data
    roleRequests.value = response.data.roleRequests || []
  } catch (error) {
    console.error('Erreur lors du chargement du profil:', error)
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de charger le profil', life: 3000 })
  }
}

const openAvatarEditor = () => {
  // Déclencher le sélecteur de fichier
  const input = document.createElement('input')
  input.type = 'file'
  input.accept = 'image/*'
  input.onchange = (e) => {
    const file = e.target.files[0]
    if (file) {
      avatarFile.value = file
      // Prévisualisation
      const reader = new FileReader()
      reader.onload = (e) => {
        avatarPreview.value = e.target.result
      }
      reader.readAsDataURL(file)
      // Upload immédiat
      uploadAvatar()
    }
  }
  input.click()
}

const uploadAvatar = async () => {
  if (!avatarFile.value) return
  
  const formData = new FormData()
  formData.append('avatar', avatarFile.value)
  
  isLoading.value = true
  try {
    const response = await api.post('/api/profile/avatar', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
    
    // Mettre à jour le store
    authStore.user.avatar = response.data.avatar
    avatarPreview.value = null
    avatarFile.value = null
    
    toast.add({ severity: 'success', summary: 'Avatar mis à jour', detail: 'Votre photo de profil a été mise à jour', life: 3000 })
  } catch (error) {
    console.error('Erreur upload avatar:', error)
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de mettre à jour l\'avatar', life: 3000 })
  } finally {
    isLoading.value = false
  }
}

const cancelEdit = () => {
  editMode.value = false
  // Reset form avec les données actuelles
  editForm.pseudo = user.value.pseudo || ''
  editForm.firstName = user.value.firstName || ''
  editForm.lastName = user.value.lastName || ''
  editForm.bio = user.value.bio || ''
  editForm.favoriteClass = user.value.favoriteClass || ''
  // Clear errors
  Object.keys(editErrors).forEach(key => editErrors[key] = '')
}

const saveProfile = async () => {
  // Validation
  Object.keys(editErrors).forEach(key => editErrors[key] = '')
  
  if (!editForm.pseudo || editForm.pseudo.length < 3) {
    editErrors.pseudo = 'Le pseudo doit contenir au moins 3 caractères'
    return
  }
  
  isLoading.value = true
  
  try {
    const response = await api.put('/api/profile/update', {
      pseudo: editForm.pseudo,
      firstName: editForm.firstName,
      lastName: editForm.lastName,
      bio: editForm.bio,
      favoriteClass: editForm.favoriteClass
    })
    
    // Mise à jour du store
    Object.assign(authStore.user, response.data.user)
    
    editMode.value = false
    toast.add({ severity: 'success', summary: 'Profil mis à jour', detail: 'Vos informations ont été sauvegardées', life: 3000 })
  } catch (error) {
    const errorMsg = error.response?.data?.error || 'Impossible de sauvegarder le profil'
    toast.add({ severity: 'error', summary: 'Erreur', detail: errorMsg, life: 3000 })
  } finally {
    isLoading.value = false
  }
}

const requestRole = async (role) => {
  const roleMapping = {
    'organizer': 'ROLE_ORGANIZER',
    'shop': 'ROLE_SHOP'
  }
  
  try {
    const response = await api.post('/api/profile/request-role', {
      role: roleMapping[role],
      message: `Demande de rôle ${getRoleLabel(role)}`
    })
    
    // Recharger les demandes
    await loadUserProfile()
    
    toast.add({ 
      severity: 'success', 
      summary: 'Demande envoyée', 
      detail: `Votre demande de rôle ${getRoleLabel(role)} a été envoyée`, 
      life: 4000 
    })
  } catch (error) {
    const errorMsg = error.response?.data?.error || 'Impossible d\'envoyer la demande'
    toast.add({ severity: 'error', summary: 'Erreur', detail: errorMsg, life: 3000 })
  }
}

const handleLogout = () => {
  showLogoutConfirm.value = true
}

const confirmLogout = () => {
  authStore.logout()
  showLogoutConfirm.value = false
  router.push('/')
  toast.add({ severity: 'success', summary: 'Déconnecté', detail: 'À bientôt !', life: 2000 })
}

const goToMyTopics = () => {
  router.push('/my-topics')
}

const createEvent = () => {
  router.push('/events/create')
}

const manageEvents = () => {
  router.push('/events/manage')
}

// Lifecycle
onMounted(async () => {
  // Charger les données du profil
  await loadUserProfile()
  
  // Initialiser le formulaire d'édition
  editForm.pseudo = user.value.pseudo || ''
  editForm.firstName = user.value.firstName || ''
  editForm.lastName = user.value.lastName || ''
  editForm.bio = user.value.bio || ''
  editForm.favoriteClass = user.value.favoriteClass || ''
  
  console.log('🔍 Profil chargé pour:', user.value.pseudo)
})
</script>

<style scoped>
/* === PROFILE PAGE EMERALD GAMING === */

.profile-page {
  min-height: calc(100vh - 140px);
  background: var(--surface-gradient);
  padding: 2rem 0;
  margin-top: 140px; /* Assure que le contenu commence sous le header */
}

.container {
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

/* Avatar image */
.avatar-image {
  width: 120px !important;
  height: 120px !important;
  border-radius: 50% !important;
  object-fit: cover !important;
  border: 4px solid white !important;
  box-shadow: var(--shadow-medium) !important;
  aspect-ratio: 1 / 1 !important;
}

/* Avatar preview */
.avatar-preview {
  width: 120px !important;
  height: 120px !important;
  border-radius: 50% !important;
  object-fit: cover !important;
  border: 4px solid white !important;
  box-shadow: var(--shadow-medium) !important;
}

.avatar-edit-btn {
  position: absolute;
  bottom: 8px;
  right: 8px;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: var(--primary);
  color: white;
  border: 2px solid white;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all var(--transition-fast);
  box-shadow: var(--shadow-small);
}

.avatar-edit-btn:hover {
  background: var(--primary-dark);
  transform: scale(1.1);
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

.role-badge.large {
  padding: 0.75rem 1.5rem;
  font-size: 1rem;
}

.verified-badge {
  background: linear-gradient(135deg, #10b981, #059669);
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-size: 0.875rem;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.profile-stats {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
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

.quick-actions {
  display: flex;
  gap: 0.75rem;
  align-items: flex-start;
}

.logout-btn {
  width: 44px !important;
  height: 44px !important;
  border-radius: 50% !important;
  background: rgba(255, 87, 34, 0.1) !important;
  border: 2px solid rgba(255, 87, 34, 0.2) !important;
  color: var(--accent) !important;
  transition: all var(--transition-fast) !important;
}

.logout-btn:hover {
  background: var(--accent) !important;
  color: white !important;
  border-color: var(--accent) !important;
}

/* Edit section */
.edit-header {
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
}

.edit-form {
  padding: 2rem;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

.field-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.field-label {
  font-weight: 600;
  color: var(--text-primary);
  font-size: 0.9rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

:deep(.emerald-input) {
  width: 100% !important;
  padding: 0.875rem 1rem !important;
  border: 2px solid var(--surface-300) !important;
  border-radius: var(--border-radius) !important;
  background: var(--surface) !important;
  color: var(--text-primary) !important;
  font-size: 0.95rem !important;
  transition: all var(--transition-fast) !important;
}

:deep(.emerald-input:focus) {
  border-color: var(--primary) !important;
  box-shadow: 0 0 0 3px rgba(38, 166, 154, 0.1) !important;
  background: white !important;
}

:deep(.emerald-input.error) {
  border-color: var(--accent) !important;
  box-shadow: 0 0 0 3px rgba(255, 87, 34, 0.1) !important;
}

.field-error {
  color: var(--accent);
  font-size: 0.8rem;
  font-weight: 500;
}

.form-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  margin-top: 2rem;
  padding-top: 1.5rem;
  border-top: 1px solid var(--surface-200);
}

/* Role section */
.role-header {
  background: linear-gradient(135deg, var(--secondary), var(--secondary-dark));
}

.role-content {
  padding: 2rem;
}

.role-section-title {
  font-size: 1.2rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0 0 1.5rem 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.role-section-title::before {
  content: '';
  width: 4px;
  height: 20px;
  background: var(--primary);
  border-radius: 2px;
}

.current-role {
  margin-bottom: 3rem;
}

.current-role-display {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.role-description {
  color: var(--text-secondary);
  font-size: 0.95rem;
  line-height: 1.5;
  margin: 0;
}

.role-upgrade {
  margin-bottom: 3rem;
}

.role-options {
  display: grid;
  gap: 1.5rem;
}

.role-option {
  padding: 1.5rem;
  border: 2px solid var(--surface-200);
  border-radius: var(--border-radius-large);
  background: var(--surface);
  transition: all var(--transition-medium);
}

.role-option:hover {
  border-color: var(--primary);
  background: white;
  box-shadow: var(--shadow-small);
}

.role-option-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.role-benefits {
  font-size: 0.875rem;
  color: var(--text-secondary);
  font-style: italic;
}

.role-option-description {
  color: var(--text-secondary);
  line-height: 1.5;
  margin: 0 0 1.5rem 0;
}

.role-request-btn {
  width: 100%;
}

.role-request-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Pending requests */
.pending-requests {
  background: rgba(38, 166, 154, 0.05);
  border: 1px solid rgba(38, 166, 154, 0.1);
  border-radius: var(--border-radius);
  padding: 1.5rem;
}

.request-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.request-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background: white;
  border-radius: var(--border-radius);
  border: 1px solid var(--surface-200);
}

.request-info {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.request-date {
  font-size: 0.875rem;
  color: var(--text-secondary);
}

.request-status {
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-size: 0.875rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.request-status.pending {
  background: rgba(255, 193, 7, 0.1);
  color: #f59e0b;
  border: 1px solid rgba(255, 193, 7, 0.2);
}

.request-status.approved {
  background: rgba(34, 197, 94, 0.1);
  color: #16a34a;
  border: 1px solid rgba(34, 197, 94, 0.2);
}

.request-status.rejected {
  background: rgba(239, 68, 68, 0.1);
  color: #dc2626;
  border: 1px solid rgba(239, 68, 68, 0.2);
}

/* Sidebar */
.profile-sidebar {
  position: sticky;
  top: 160px;
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

.activity-header {
  background: linear-gradient(135deg, #8b5cf6, #a855f7);
}

.topics-header {
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
}

.events-header {
  background: linear-gradient(135deg, var(--accent), var(--accent-dark));
}

.header-icon {
  font-size: 1.25rem;
}

.header-title {
  font-size: 1.1rem;
  font-weight: 600;
  margin: 0;
}

/* Activity list */
.activity-list {
  padding: 1.5rem;
  max-height: 300px;
  overflow-y: auto;
}

.activity-item {
  display: flex;
  gap: 1rem;
  padding: 0.75rem 0;
  border-bottom: 1px solid var(--surface-200);
}

.activity-item:last-child {
  border-bottom: none;
}

.activity-icon {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: rgba(139, 92, 246, 0.1);
  color: #8b5cf6;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.875rem;
  flex-shrink: 0;
}

.activity-content {
  flex: 1;
}

.activity-text {
  font-size: 0.875rem;
  color: var(--text-primary);
  margin: 0 0 0.25rem 0;
  line-height: 1.4;
}

.activity-time {
  font-size: 0.75rem;
  color: var(--text-secondary);
}

.empty-activity {
  text-align: center;
  padding: 2rem 0;
}

.empty-icon {
  font-size: 2rem;
  color: var(--text-secondary);
  margin-bottom: 0.75rem;
}

.empty-text {
  color: var(--text-secondary);
  font-size: 0.875rem;
  margin: 0;
}

/* Topics summary */
.topics-summary {
  padding: 1.5rem;
}

.topic-stats {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.topic-stat {
  text-align: center;
  padding: 1rem;
  background: var(--surface-100);
  border-radius: var(--border-radius);
}

.topic-stat .stat-value {
  display: block;
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--primary);
  line-height: 1;
}

.topic-stat .stat-label {
  display: block;
  font-size: 0.75rem;
  color: var(--text-secondary);
  margin-top: 0.25rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* Events summary */
.events-summary {
  padding: 1.5rem;
}

.event-stats {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.event-stat {
  text-align: center;
  padding: 1rem;
  background: rgba(255, 87, 34, 0.05);
  border-radius: var(--border-radius);
  border: 1px solid rgba(255, 87, 34, 0.1);
}

.event-stat .stat-value {
  display: block;
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--accent);
  line-height: 1;
}

.event-stat .stat-label {
  display: block;
  font-size: 0.75rem;
  color: var(--text-secondary);
  margin-top: 0.25rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.event-actions {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

/* Buttons */
:deep(.emerald-btn) {
  background: var(--emerald-gradient) !important;
  border: none !important;
  color: white !important;
  font-weight: 600 !important;
  padding: 0.75rem 1.5rem !important;
  border-radius: var(--border-radius) !important;
  transition: all var(--transition-fast) !important;
}

:deep(.emerald-btn:hover) {
  background: linear-gradient(135deg, var(--primary-dark), var(--primary)) !important;
  transform: translateY(-1px) !important;
  box-shadow: 0 4px 12px rgba(38, 166, 154, 0.3) !important;
}

:deep(.emerald-outline-btn) {
  background: none !important;
  border: 2px solid var(--primary) !important;
  color: var(--primary) !important;
  font-weight: 500 !important;
  padding: 0.75rem 1.5rem !important;
  border-radius: var(--border-radius) !important;
  transition: all var(--transition-fast) !important;
}

:deep(.emerald-outline-btn:hover) {
  background: var(--primary) !important;
  color: white !important;
  transform: translateY(-1px) !important;
}

:deep(.emerald-outline-btn.small) {
  padding: 0.5rem 1rem !important;
  font-size: 0.875rem !important;
}

:deep(.emerald-btn.small) {
  padding: 0.5rem 1rem !important;
  font-size: 0.875rem !important;
}

/* Modal overrides */
:deep(.emerald-modal .p-dialog) {
  border-radius: var(--border-radius-large) !important;
  box-shadow: var(--shadow-large) !important;
  border: 1px solid var(--surface-200) !important;
}

:deep(.emerald-modal .p-dialog-header) {
  background: var(--emerald-gradient) !important;
  color: white !important;
  padding: 1.5rem 2rem !important;
  border-bottom: none !important;
}

:deep(.emerald-modal .p-dialog-content) {
  padding: 2rem !important;
  background: var(--surface) !important;
}

:deep(.emerald-modal .p-dialog-footer) {
  padding: 1.5rem 2rem !important;
  background: var(--surface) !important;
  border-top: 1px solid var(--surface-200) !important;
  display: flex !important;
  justify-content: flex-end !important;
  gap: 1rem !important;
}

/* Responsive */
@media (max-width: 1024px) {
  .profile-grid {
    grid-template-columns: 1fr;
    gap: 1.5rem;
  }
  
  .profile-sidebar {
    position: static;
    grid-row: 2;
  }
  
  .profile-sidebar .sidebar-card {
    display: grid;
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .container {
    padding: 0 1rem;
  }
  
  .profile-page {
    padding: 1rem 0;
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
  
  .profile-stats {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .quick-actions {
    justify-content: center;
  }
  
  .form-grid {
    grid-template-columns: 1fr;
  }
  
  .role-option-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
}

@media (max-width: 640px) {
  :deep(.profile-avatar) {
    width: 80px !important;
    height: 80px !important;
    font-size: 2rem !important;
  }
  
  .username {
    font-size: 1.5rem;
  }
  
  .profile-stats {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
  
  .role-badges {
    justify-content: center;
  }
  
  .form-actions {
    flex-direction: column-reverse;
  }
  
  .event-actions {
    gap: 0.5rem;
  }
}
</style>
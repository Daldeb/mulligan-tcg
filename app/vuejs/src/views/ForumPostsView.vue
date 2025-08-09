<template>
  <div class="forum-page">
    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
      <div class="emerald-spinner"></div>
      <p>Chargement du forum...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-container">
      <i class="pi pi-exclamation-triangle error-icon"></i>
      <h3>Erreur de chargement</h3>
      <p>{{ error }}</p>
      <button @click="fetchForumData" class="p-button emerald-button primary">
        <i class="pi pi-refresh"></i>
        Réessayer
      </button>
    </div>

    <!-- Forum Content -->
    <div v-else class="forum-container">
      <!-- Breadcrumb Navigation -->
      <nav class="forum-breadcrumb">
        <RouterLink to="/forums" class="breadcrumb-link">
          <i class="pi pi-home"></i>
          Forums
        </RouterLink>
        <i class="pi pi-chevron-right breadcrumb-separator"></i>
        <span class="breadcrumb-current">{{ forum.name }}</span>
      </nav>

      <!-- Forum Header avec image d'arrière-plan -->
      <header 
        class="forum-header"
        :style="{ 
          backgroundImage: getForumImageUrl(forum.slug) ? 
            `url(${getForumImageUrl(forum.slug)})` : 
            'none',
          backgroundSize: 'cover',
          backgroundPosition: 'center top'
        }"
      >
        <div class="forum-info">
          <div class="forum-icon">
            <i class="pi pi-comments"></i>
          </div>
          <div class="forum-details">
            <h1 class="forum-title">{{ forum.name }}</h1>
            <p class="forum-description">{{ forum.description }}</p>
            <div class="forum-stats">
              <div class="stat-item">
                <i class="pi pi-file"></i>
                <span>{{ filteredPosts.length }} posts</span>
              </div>
              <div class="stat-item">
                <i class="pi pi-users"></i>
                <span>Communauté active</span>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Create Post Button -->
        <button 
          @click="showCreateModal = true"
          class="p-button emerald-button primary create-post-btn"
        >
          <i class="pi pi-plus"></i>
          Créer un post
        </button>
      </header>

      <!-- Search & Filters Bar -->
      <div class="controls-bar">
        <!-- Search -->
        <div class="search-container">
          <div class="search-input-wrapper">
            <i class="pi pi-search search-icon"></i>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Rechercher dans ce forum..."
              class="search-input"
            />
            <button 
              v-if="searchQuery"
              @click="searchQuery = ''"
              class="clear-search"
            >
              <i class="pi pi-times"></i>
            </button>
          </div>
        </div>

        <!-- Sort Filters -->
        <div class="sort-container">
          <div class="sort-tabs">
            <button 
              v-for="filter in sortFilters" 
              :key="filter.value"
              @click="currentSort = filter.value"
              :class="['sort-tab', { active: currentSort === filter.value }]"
            >
              <i :class="filter.icon"></i>
              {{ filter.label }}
            </button>
          </div>
        </div>

                <!-- Advanced Filters -->
        <div class="advanced-filters">
          <button 
            @click="showAdvancedFilters = !showAdvancedFilters"
            :class="['filter-toggle', { active: showAdvancedFilters || hasActiveAdvancedFilters }]"
          >
            <i class="pi pi-filter"></i>
            Filtres avancés
            <i :class="['pi', showAdvancedFilters ? 'pi-chevron-up' : 'pi-chevron-down']"></i>
          </button>
          
          <div v-if="showAdvancedFilters" class="filters-panel">
            <!-- Time Period Filter -->
            <div class="filter-group">
              <label class="filter-label">Période</label>
              <div class="filter-options">
                <button 
                  v-for="period in timePeriods" 
                  :key="period.value"
                  @click="currentPeriod = period.value"
                  :class="['filter-option', { active: currentPeriod === period.value }]"
                >
                  {{ period.label }}
                </button>
              </div>
            </div>

            <!-- Post Type Filter -->
            <div class="filter-group">
              <label class="filter-label">Type de post</label>
              <div class="filter-options">
                <button 
                  v-for="type in postTypes" 
                  :key="type.value"
                  @click="togglePostType(type.value)"
                  :class="['filter-option', 'checkbox', { active: selectedPostTypes.includes(type.value) }]"
                >
                  <i :class="type.icon"></i>
                  {{ type.label }}
                  <i v-if="selectedPostTypes.includes(type.value)" class="pi pi-check"></i>
                </button>
              </div>
            </div>

            <!-- Popular Tags Filter -->
            <div v-if="popularTags.length" class="filter-group">
              <label class="filter-label">Tags populaires</label>
              <div class="filter-options tags-grid">
                <button 
                  v-for="tag in popularTags" 
                  :key="tag.name"
                  @click="toggleTag(tag.name)"
                  :class="['tag-filter', { active: selectedTags.includes(tag.name) }]"
                >
                  #{{ tag.name }}
                  <span class="tag-count">({{ tag.count }})</span>
                </button>
              </div>
            </div>

            <!-- Filter Actions -->
            <div class="filter-actions">
              <button @click="resetAdvancedFilters" class="reset-filters-btn">
                <i class="pi pi-refresh"></i>
                Réinitialiser
              </button>
              <button @click="showAdvancedFilters = false" class="apply-filters-btn">
                <i class="pi pi-check"></i>
                Appliquer ({{ filteredPosts.length }})
              </button>
            </div>
          </div>
        </div>

        <!-- View Toggle -->
        <div class="view-toggle">
          <button 
            @click="viewMode = 'card'"
            :class="['view-btn', { active: viewMode === 'card' }]"
            title="Vue carte"
          >
            <i class="pi pi-th-large"></i>
          </button>
          <button 
            @click="viewMode = 'compact'"
            :class="['view-btn', { active: viewMode === 'compact' }]"
            title="Vue compacte"
          >
            <i class="pi pi-list"></i>
          </button>
        </div>
      </div>

      <!-- Active Filters Display -->
      <div v-if="searchQuery || currentSort !== 'new'" class="active-filters">
        <div class="filter-tags">
          <span v-if="searchQuery" class="filter-tag">
            <i class="pi pi-search"></i>
            "{{ searchQuery }}"
            <button @click="searchQuery = ''" class="remove-filter">
              <i class="pi pi-times"></i>
            </button>
          </span>
          <span v-if="currentSort !== 'new'" class="filter-tag">
            <i class="pi pi-sort-alt"></i>
            {{ getSortLabel(currentSort) }}
            <button @click="currentSort = 'new'" class="remove-filter">
              <i class="pi pi-times"></i>
            </button>
          </span>
        </div>
        <button @click="clearAllFilters" class="clear-all-btn">
          Effacer tout
        </button>
      </div>

      <!-- Posts List -->
      <main class="posts-section">
        <!-- No Posts State -->
        <div v-if="filteredPosts.length === 0 && !loading" class="no-posts">
          <div v-if="searchQuery" class="no-results">
            <i class="pi pi-search"></i>
            <h3>Aucun résultat trouvé</h3>
            <p>Essayez avec d'autres mots-clés ou explorez les posts récents.</p>
            <button @click="searchQuery = ''" class="p-button p-button-text">
              Effacer la recherche
            </button>
          </div>
          <div v-else class="empty-forum">
            <i class="pi pi-inbox"></i>
            <h3>Aucun post pour l'instant</h3>
            <p>Soyez le premier à démarrer une discussion dans ce forum !</p>
            <button 
              @click="showCreateModal = true"
              class="p-button emerald-button primary"
            >
              <i class="pi pi-plus"></i>
              Créer le premier post
            </button>
          </div>
        </div>

        <!-- Posts Grid/List -->
        <div v-else :class="['posts-container', viewMode]">
          <article 
            v-for="(post, index) in paginatedPosts" 
            :key="post.id"
            :class="['post-card', viewMode]"
            :style="{ animationDelay: `${index * 0.05}s` }"
          >
            <!-- Post Vote Section -->
            <div class="post-votes">
              <button 
                @click="handleUpvote(post)"
                :class="['vote-btn', 'upvote', { active: post.userVote === 'UP' }]" 
                title="Upvote"
              >
                <i class="pi pi-chevron-up"></i>
              </button>
              <span class="vote-score">{{ post.score || 0 }}</span>
              <button 
                @click="handleDownvote(post)"
                :class="['vote-btn', 'downvote', { active: post.userVote === 'DOWN' }]" 
                title="Downvote"
              >
                <i class="pi pi-chevron-down"></i>
              </button>
            </div>

            <!-- Post Content -->
            <div class="post-content">
              <!-- Post Meta -->
              <div class="post-meta">
                <div class="post-type-badge" :class="`type-${post.postType || 'text'}`">
                  <i :class="getPostTypeIcon(post.postType)"></i>
                </div>
                
                  <div class="post-author">
                    <div 
                      class="author-avatar"
                      :class="{ 'clickable-avatar': canNavigateToProfile(post.authorId) }"
                      @click="canNavigateToProfile(post.authorId) && goToProfile(post.authorId, post.author)"
                      :title="canNavigateToProfile(post.authorId) ? getProfileTooltip(post.author) : ''"
                    >
                      <img 
                        v-if="post.authorAvatar"
                        :src="`${backendUrl}/uploads/${post.authorAvatar}`"
                        class="author-avatar-image"
                        alt="Avatar"
                      />
                      <span v-else class="author-avatar-fallback">
                        {{ post.author?.charAt(0).toUpperCase() }}
                      </span>
                    </div>
                    <div class="author-info">
                      <span 
                        class="author-name"
                        :class="{ 'clickable-name': canNavigateToProfile(post.authorId) }"
                        @click="canNavigateToProfile(post.authorId) && goToProfile(post.authorId, post.author)"
                        :title="canNavigateToProfile(post.authorId) ? getProfileTooltip(post.author) : ''"
                      >
                        {{ post.author }}
                      </span>
                      <time class="post-date">{{ formatRelativeDate(post.createdAt) }}</time>
                    </div>
                  </div>

                <!-- Post Tags -->
                <div v-if="post.tags && post.tags.length" class="post-tags">
                  <span v-for="tag in post.tags" :key="tag" class="tag">
                    #{{ tag }}
                  </span>
                </div>
              </div>

              <!-- Post Title & Preview -->
              <div class="post-main">
                <RouterLink 
                  :to="`/forums/${forum.slug}/posts/${post.id}`"
                  class="post-title-link"
                >
                  <h2 class="post-title">{{ post.title }}</h2>
                </RouterLink>

                <!-- Post Preview Content -->
                <div v-if="viewMode === 'card'" class="post-preview">
                  <p v-if="post.content" class="content-preview">
                    {{ truncateContent(post.content, 150) }}
                  </p>
                  
                  <!-- Link Preview -->
                  <div v-if="post.postType === 'link' && post.linkUrl" class="link-preview-mini">
                    <i class="pi pi-link"></i>
                    <span>{{ getDomainFromUrl(post.linkUrl) }}</span>
                  </div>

                  <!-- Attachment Preview -->
                  <div v-if="post.attachments && post.attachments.length" class="attachments-preview">
                    <div class="attachment-count">
                      <i class="pi pi-paperclip"></i>
                      <span>{{ post.attachments.length }} fichier(s)</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Post Actions -->
              <footer class="post-actions">
                <RouterLink 
                  :to="`/forums/${forum.slug}/posts/${post.id}`"
                  class="action-btn comments"
                >
                  <i class="pi pi-comment"></i>
                  <span>{{ post.commentsCount || 0 }} commentaires</span>
                </RouterLink>
                
                <button @click="sharePost(post)" class="action-btn share">
                  <i class="pi pi-share-alt"></i>
                  <span>Partager</span>
                </button>
                
                <button 
                  @click="savePost(post)" 
                  :class="['action-btn', 'save', { active: post.isSaved }]"
                >
                  <i :class="post.isSaved ? 'pi pi-bookmark-fill' : 'pi pi-bookmark'"></i>
                  <span>{{ post.isSaved ? 'Sauvegardé' : 'Sauvegarder' }}</span>
                </button>

                <div class="post-time">
                  {{ formatDate(post.createdAt) }}
                </div>
              </footer>
            </div>
          </article>
        </div>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="pagination-container">
          <div class="pagination">
            <button 
              @click="currentPage--"
              :disabled="currentPage === 1"
              class="page-btn"
            >
              <i class="pi pi-chevron-left"></i>
              Précédent
            </button>
            
            <div class="page-numbers">
              <button 
                v-for="page in visiblePages" 
                :key="page"
                @click="currentPage = page"
                :class="['page-number', { active: page === currentPage }]"
              >
                {{ page }}
              </button>
            </div>
            
            <button 
              @click="currentPage++"
              :disabled="currentPage === totalPages"
              class="page-btn"
            >
              Suivant
              <i class="pi pi-chevron-right"></i>
            </button>
          </div>
          
          <div class="pagination-info">
            Affichage {{ (currentPage - 1) * postsPerPage + 1 }}-{{ Math.min(currentPage * postsPerPage, filteredPosts.length) }} 
            sur {{ filteredPosts.length }} posts
          </div>
        </div>
      </main>
    </div>

    <!-- Create Post Modal -->
    <div v-if="showCreateModal" class="modal-overlay" @click="closeModal">
      <div class="modal-content" @click.stop>
        <header class="modal-header">
          <h2>Créer un nouveau post</h2>
          <button @click="showCreateModal = false" class="modal-close">
            <i class="pi pi-times"></i>
          </button>
        </header>
        <div class="modal-body">
          <PostCreateForm 
            :forumSlug="forum.slug" 
            @post-created="handlePostCreated"
            @cancel="showCreateModal = false"
          />
        </div>
      </div>
    </div>

    <!-- Toast Notification -->
    <div v-if="showToast" class="toast-notification">
      <div class="toast-content">
        <span>{{ toastMessage }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/services/api'
import PostCreateForm from '@/components/forum/PostCreateForm.vue'

// Imports des images d'arrière-plan
import hearthstoneImg from '@/assets/images/forums/hearthstone-bg.jpg'
import magicImg from '@/assets/images/forums/magic-bg.jpg'
import pokemonImg from '@/assets/images/forums/pokemon-bg.jpg'

import { useProfileNavigation } from '@/composables/useProfileNavigation'

const { goToProfile, canNavigateToProfile, getProfileTooltip } = useProfileNavigation()

const backendUrl = computed(() => import.meta.env.VITE_BACKEND_URL)

const route = useRoute()
const slug = route.params.slug

import { useRouter } from 'vue-router'
const router = useRouter()
// Data
const forum = ref({})
const posts = ref([])
const loading = ref(true)
const error = ref('')
const showCreateModal = ref(false)
const showToast = ref(false)
const toastMessage = ref('')

// Search & Filtering
const searchQuery = ref('')
const currentSort = ref('new')
const viewMode = ref('card') // 'card' ou 'compact'
const currentPage = ref(1)
const postsPerPage = 10

// === FILTRES AVANCÉS MANQUANTS ===
const showAdvancedFilters = ref(false)

// Filtres par période
const currentPeriod = ref('all')
const timePeriods = [
  { value: 'all', label: 'Toutes' },
  { value: 'today', label: 'Aujourd\'hui' },
  { value: 'week', label: 'Cette semaine' },
  { value: 'month', label: 'Ce mois' },
  { value: 'year', label: 'Cette année' }
]

// Filtres par type
const selectedPostTypes = ref([])
const postTypes = [
  { value: 'text', label: 'Texte', icon: 'pi pi-align-left' },
  { value: 'link', label: 'Lien', icon: 'pi pi-link' },
  { value: 'image', label: 'Image', icon: 'pi pi-image' }
]

// Filtres par tags et scores
const selectedTags = ref([])

// Tags populaires
const popularTags = computed(() => {
  const tagCounts = {}
  posts.value.forEach(post => {
    if (post.tags) {
      post.tags.forEach(tag => {
        tagCounts[tag] = (tagCounts[tag] || 0) + 1
      })
    }
  })
  return Object.entries(tagCounts)
    .map(([name, count]) => ({ name, count }))
    .sort((a, b) => b.count - a.count)
    .slice(0, 10)
})

// Détection des filtres actifs
const hasActiveAdvancedFilters = computed(() => 
  currentPeriod.value !== 'all' ||
  selectedPostTypes.value.length > 0 ||
  selectedTags.value.length > 0
)

// Fonctions manquantes
const togglePostType = (type) => {
  const index = selectedPostTypes.value.indexOf(type)
  if (index > -1) {
    selectedPostTypes.value.splice(index, 1)
  } else {
    selectedPostTypes.value.push(type)
  }
}

const toggleTag = (tag) => {
  const index = selectedTags.value.indexOf(tag)
  if (index > -1) {
    selectedTags.value.splice(index, 1)
  } else {
    selectedTags.value.push(tag)
  }
}

const resetAdvancedFilters = () => {
  currentPeriod.value = 'all'
  selectedPostTypes.value = []
  selectedTags.value = []
}

// Scroll intelligent - ferme seulement si on scroll vraiment loin
let lastScrollY = 0
const handleScroll = () => {
  if (showAdvancedFilters.value) {
    const currentScrollY = window.scrollY
    
    // Se ferme seulement si on scroll vers le bas ET qu'on a dépassé une distance significative
    if (currentScrollY > lastScrollY && currentScrollY > 600) {
      // On a scrollé vers le bas de plus de 300px depuis le haut de la page
      showAdvancedFilters.value = false
    }
    
    lastScrollY = currentScrollY
  }
}

// Fermer les filtres au clic extérieur
const handleClickOutside = (event) => {
  if (showAdvancedFilters.value && !event.target.closest('.advanced-filters')) {
    showAdvancedFilters.value = false
  }
}

// Sort options
const sortFilters = [
  { value: 'new', label: 'Nouveau', icon: 'pi pi-clock' },
  { value: 'hot', label: 'Tendance', icon: 'pi pi-fire' },
  { value: 'top', label: 'Top', icon: 'pi pi-star' },
  { value: 'comments', label: 'Commentaires', icon: 'pi pi-comment' }
]

// Image functions
const getForumImageUrl = (forumSlug) => {
  const slug = forumSlug.toLowerCase();
  if (slug.includes('hearthstone')) return hearthstoneImg;
  if (slug.includes('magic')) return magicImg;
  if (slug.includes('pokemon')) return pokemonImg;
  return null;
};

// Fonctions de vote
const voteOnPost = async (post, voteType) => {
  try {
    const response = await api.post(`/api/posts/${post.id}/vote`, { type: voteType })
    
    // Mettre à jour le post local
    post.score = response.data.newScore
    post.userVote = response.data.userVote
    
  } catch (error) {
    console.error('Erreur vote:', error)
  }
}

const handleUpvote = (post) => {
  voteOnPost(post, 'UP')
}

const handleDownvote = (post) => {
  voteOnPost(post, 'DOWN')
}

// Fonction de partage
const sharePost = async (post) => {
  const postUrl = `${window.location.origin}/forums/${forum.value.slug}/posts/${post.id}`
  
  try {
    await navigator.clipboard.writeText(postUrl)
    showToastMessage('Lien copié dans le presse-papier ! 📋')
  } catch (error) {
    console.error('Erreur copie:', error)
    // Fallback pour les navigateurs plus anciens
    fallbackCopyTextToClipboard(postUrl)
  }
}

// Fallback pour navigateurs sans clipboard API
const fallbackCopyTextToClipboard = (text) => {
  const textArea = document.createElement('textarea')
  textArea.value = text
  document.body.appendChild(textArea)
  textArea.focus()
  textArea.select()
  try {
    document.execCommand('copy')
    showToastMessage('Lien copié ! 📋')
  } catch (err) {
    console.error('Erreur copie fallback:', err)
    showToastMessage('Erreur lors de la copie ❌')
  }
  document.body.removeChild(textArea)
}

// Fonction pour afficher le toast
const showToastMessage = (message) => {
  toastMessage.value = message
  showToast.value = true
  
  // Masquer automatiquement après 2.5 secondes
  setTimeout(() => {
    showToast.value = false
  }, 2500)
}

// Fonction de sauvegarde
const savePost = async (post) => {
  try {
    const response = await api.post(`/api/posts/${post.id}/save`)
    
    // Mettre à jour le post local
    post.isSaved = response.data.isSaved
    
    // Afficher le message de feedback
    showToastMessage(response.data.message + ' 📌')
    
  } catch (error) {
    console.error('Erreur sauvegarde:', error)
    showToastMessage('Erreur lors de la sauvegarde ❌')
  }
}

// Computed
const filteredPosts = computed(() => {
  let filtered = [...posts.value]

  // Search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(post => 
      post.title.toLowerCase().includes(query) ||
      post.content?.toLowerCase().includes(query) ||
      post.author.toLowerCase().includes(query) ||
      (post.tags && post.tags.some(tag => tag.toLowerCase().includes(query)))
    )
  }

  // === NOUVEAUX FILTRES AVANCÉS ===
  
  // Filtre par période
  if (currentPeriod.value !== 'all') {
    const now = new Date()
    const filterDate = new Date()
    
    switch (currentPeriod.value) {
      case 'today':
        filterDate.setHours(0, 0, 0, 0)
        break
      case 'week':
        filterDate.setDate(now.getDate() - 7)
        break
      case 'month':
        filterDate.setMonth(now.getMonth() - 1)
        break
      case 'year':
        filterDate.setFullYear(now.getFullYear() - 1)
        break
    }
    
    filtered = filtered.filter(post => new Date(post.createdAt) >= filterDate)
  }

  // Filtre par type de post
  if (selectedPostTypes.value.length > 0) {
    filtered = filtered.filter(post => 
      selectedPostTypes.value.includes(post.postType || 'text')
    )
  }

  // Filtre par tags
  if (selectedTags.value.length > 0) {
    filtered = filtered.filter(post => 
      post.tags && post.tags.some(tag => selectedTags.value.includes(tag))
    )
  }

  // Sort filter (inchangé)
  switch (currentSort.value) {
    case 'hot':
      filtered.sort((a, b) => calculateHotScore(b) - calculateHotScore(a))
      break
    case 'top':
      filtered.sort((a, b) => (b.score || 0) - (a.score || 0))
      break
    case 'comments':
      filtered.sort((a, b) => (b.commentsCount || 0) - (a.commentsCount || 0))
      break
    case 'new':
    default:
      filtered.sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt))
      break
  }

  return filtered
})

const totalPages = computed(() => Math.ceil(filteredPosts.value.length / postsPerPage))

const paginatedPosts = computed(() => {
  const start = (currentPage.value - 1) * postsPerPage
  const end = start + postsPerPage
  return filteredPosts.value.slice(start, end)
})

const visiblePages = computed(() => {
  const total = totalPages.value
  const current = currentPage.value
  const pages = []
  
  if (total <= 7) {
    for (let i = 1; i <= total; i++) {
      pages.push(i)
    }
  } else {
    if (current <= 4) {
      pages.push(1, 2, 3, 4, 5, '...', total)
    } else if (current >= total - 3) {
      pages.push(1, '...', total - 4, total - 3, total - 2, total - 1, total)
    } else {
      pages.push(1, '...', current - 1, current, current + 1, '...', total)
    }
  }
  
  return pages.filter(page => page !== '...' || pages.indexOf(page) === pages.lastIndexOf(page))
})

// Methods
const fetchForumData = async () => {
  loading.value = true
  error.value = ''
  
  try {
    const res = await api.get(`/api/forums/${slug}/posts`)
    forum.value = res.data.forum
    posts.value = res.data.posts || []
  } catch (err) {
    console.error('Erreur chargement forum:', err)
    error.value = 'Impossible de charger le forum. Vérifiez votre connexion.'
  } finally {
    loading.value = false
  }
}

const calculateHotScore = (post) => {
  const score = post.score || 0
  const comments = post.commentsCount || 0
  const hoursOld = (Date.now() - new Date(post.createdAt).getTime()) / (1000 * 60 * 60)
  
  // Algorithme simple pour le "hot score"
  return (score + comments * 2) / Math.pow(hoursOld + 2, 1.8)
}

const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatRelativeDate = (dateString) => {
  const date = new Date(dateString)
  const now = new Date()
  const diffTime = Math.abs(now - date)
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  const diffHours = Math.ceil(diffTime / (1000 * 60 * 60))
  const diffMinutes = Math.ceil(diffTime / (1000 * 60))
  
  if (diffMinutes < 60) return `Il y a ${diffMinutes}m`
  if (diffHours < 24) return `Il y a ${diffHours}h`
  if (diffDays === 1) return 'Hier'
  if (diffDays < 7) return `Il y a ${diffDays}j`
  if (diffDays < 30) return `Il y a ${Math.ceil(diffDays / 7)} sem.`
  
  return date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })
}

const getPostTypeIcon = (type) => {
  switch (type) {
    case 'link': return 'pi pi-link'
    case 'image': return 'pi pi-image'
    default: return 'pi pi-align-left'
  }
}

const getSortLabel = (sortValue) => {
  const filter = sortFilters.find(f => f.value === sortValue)
  return filter ? filter.label : sortValue
}

const truncateContent = (content, maxLength) => {
  if (!content) return ''
  if (content.length <= maxLength) return content
  return content.substring(0, maxLength) + '...'
}

const getDomainFromUrl = (url) => {
  try {
    return new URL(url).hostname.replace('www.', '')
  } catch {
    return url
  }
}

const clearAllFilters = () => {
  searchQuery.value = ''
  currentSort.value = 'new'
  currentPage.value = 1
}

const closeModal = () => {
  showCreateModal.value = false
}

const handlePostCreated = () => {
  showCreateModal.value = false
  fetchForumData() // Recharger les posts
}

// Watchers
watch([searchQuery, currentSort], () => {
  currentPage.value = 1 // Reset pagination when filters change
})

onMounted(() => {
  fetchForumData()
  window.addEventListener('scroll', handleScroll)
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
  document.removeEventListener('click', handleClickOutside)
})

</script>

<style scoped>
.forum-page {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem 1rem;
  background: var(--surface);
  min-height: calc(100vh - var(--header-height));
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
}

/* Breadcrumb */
.forum-breadcrumb {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 2rem;
  padding: 1rem;
  background: white;
  border-radius: var(--border-radius);
  box-shadow: var(--shadow-small);
}

.breadcrumb-link {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--primary);
  text-decoration: none;
  font-weight: 500;
  transition: color var(--transition-fast);
}

.breadcrumb-link:hover {
  color: var(--primary-dark);
}

.breadcrumb-separator {
  color: var(--text-secondary);
  font-size: 0.75rem;
}

.breadcrumb-current {
  color: var(--text-secondary);
  font-weight: 500;
}

/* Forum Header avec image d'arrière-plan */
.forum-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 2rem;
  padding: 2rem;
  background: white;
  border-radius: var(--border-radius-large);
  box-shadow: var(--shadow-medium);
  margin-bottom: 2rem;
  position: relative;
  overflow: hidden; /* Important pour l'image de fond */
}

.forum-info {
  display: flex;
  gap: 1rem;
  flex: 1;
  position: relative;
  z-index: 2; /* Au-dessus de l'image */
}

.forum-icon {
  width: 64px;
  height: 64px;
  background: linear-gradient(135deg, var(--primary-light), var(--primary));
  border-radius: var(--border-radius-large);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.5rem;
  flex-shrink: 0;
  box-shadow: var(--shadow-medium); /* Ajout d'ombre pour faire ressortir l'icône */
}

.forum-details {
  flex: 1;
}

.forum-title {
  font-size: 2rem;
  font-weight: 700;
  color: white;
  margin-bottom: 0.5rem;
  line-height: 1.2;
  text-shadow: 2px 2px 4px rgba(0,0,0,0.8), 0 0 8px rgba(0,0,0,0.3);
}

.forum-description {
  color: rgba(255,255,255,0.95);
  margin-bottom: 1rem;
  font-size: 1.125rem;
  line-height: 1.5;
  text-shadow: 1px 1px 3px rgba(0,0,0,0.7), 0 0 6px rgba(0,0,0,0.2);
  font-weight: 500;
}

.forum-stats {
  display: flex;
  gap: 2rem;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: rgba(255,255,255,0.9);
  font-size: 0.875rem;
  font-weight: 600;
  text-shadow: 1px 1px 3px rgba(0,0,0,0.6);
}

.create-post-btn {
  white-space: nowrap;
  height: fit-content;
  font-size: 1rem;
  padding: 0.875rem 1.5rem;
  position: relative;
  z-index: 2; /* Au-dessus de l'image */
  box-shadow: var(--shadow-medium); /* Ajout d'ombre pour faire ressortir le bouton */
}

/* Controls Bar */
.controls-bar {
  display: flex;
  gap: 1rem;
  align-items: center;
  padding: 1.5rem;
  background: white;
  border-radius: var(--border-radius-large);
  box-shadow: var(--shadow-small);
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
}

.search-container {
  flex: 1;
  min-width: 300px;
}

.search-input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.search-icon {
  position: absolute;
  left: 1rem;
  color: var(--text-secondary);
  z-index: 1;
}

.search-input {
  width: 100%;
  padding: 0.75rem 1rem 0.75rem 2.5rem;
  border: 2px solid var(--surface-300);
  border-radius: var(--border-radius);
  font-size: 1rem;
  transition: border-color var(--transition-fast);
}

.search-input:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(38, 166, 154, 0.1);
}

.clear-search {
  position: absolute;
  right: 0.5rem;
  background: none;
  border: none;
  color: var(--text-secondary);
  cursor: pointer;
  padding: 0.5rem;
  border-radius: var(--border-radius-small);
  transition: background var(--transition-fast);
}

.clear-search:hover {
  background: var(--surface-200);
}

.sort-container {
  display: flex;
  gap: 0.5rem;
}

.sort-tabs {
  display: flex;
  background: var(--surface-200);
  border-radius: var(--border-radius);
  padding: 0.25rem;
}

.sort-tab {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: transparent;
  border: none;
  border-radius: var(--border-radius-small);
  color: var(--text-secondary);
  font-weight: 500;
  cursor: pointer;
  transition: all var(--transition-fast);
  white-space: nowrap;
}

.sort-tab:hover {
  background: var(--surface-300);
  color: var(--text-primary);
}

.sort-tab.active {
  background: white;
  color: var(--primary);
  box-shadow: var(--shadow-small);
}

.view-toggle {
  display: flex;
  background: var(--surface-200);
  border-radius: var(--border-radius);
  padding: 0.25rem;
}

.view-btn {
  padding: 0.5rem;
  background: transparent;
  border: none;
  border-radius: var(--border-radius-small);
  color: var(--text-secondary);
  cursor: pointer;
  transition: all var(--transition-fast);
}

.view-btn:hover {
  background: var(--surface-300);
  color: var(--text-primary);
}

.view-btn.active {
  background: white;
  color: var(--primary);
  box-shadow: var(--shadow-small);
}

/* Active Filters */
.active-filters {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background: var(--surface-100);
  border-radius: var(--border-radius);
  margin-bottom: 1rem;
}

.filter-tags {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.filter-tag {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.375rem 0.75rem;
  background: var(--primary);
  color: white;
  border-radius: 15px;
  font-size: 0.875rem;
  font-weight: 500;
}

.remove-filter {
  background: none;
  border: none;
  color: white;
  cursor: pointer;
  padding: 0.125rem;
  border-radius: 50%;
  transition: background var(--transition-fast);
}

.remove-filter:hover {
  background: rgba(255, 255, 255, 0.2);
}

.clear-all-btn {
  background: none;
  border: 1px solid var(--surface-300);
  color: var(--text-secondary);
  padding: 0.5rem 1rem;
  border-radius: var(--border-radius);
  cursor: pointer;
  transition: all var(--transition-fast);
}

.clear-all-btn:hover {
  background: var(--surface-200);
  color: var(--text-primary);
}

/* Posts Section */
.posts-section {
  flex: 1;
}

.no-posts {
  text-align: center;
  padding: 4rem 2rem;
  background: white;
  border-radius: var(--border-radius-large);
  box-shadow: var(--shadow-small);
}

.no-results,
.empty-forum {
  color: var(--text-secondary);
}

.no-results i,
.empty-forum i {
  font-size: 4rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

.no-results h3,
.empty-forum h3 {
  margin: 0 0 0.5rem 0;
  color: var(--text-primary);
  font-size: 1.5rem;
}

.no-results p,
.empty-forum p {
  margin: 0 0 2rem 0;
  font-size: 1.125rem;
}

/* Posts Container */
.posts-container {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.posts-container.compact {
  gap: 0.5rem;
}

/* Post Cards */
.post-card {
  display: flex;
  background: white;
  border-radius: var(--border-radius-large);
  box-shadow: var(--shadow-small);
  border: 1px solid var(--surface-200);
  overflow: hidden;
  transition: all var(--transition-medium);
  animation: slideInUp 0.3s ease-out;
}

.post-card:hover {
  box-shadow: var(--shadow-medium);
  transform: translateY(-2px);
}

.post-card.compact {
  border-radius: var(--border-radius);
}

.post-card.compact:hover {
  transform: translateY(-1px);
}

/* Post Votes */
.post-votes {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
  padding: 1rem 0.75rem;
  background: var(--surface-100);
  border-right: 1px solid var(--surface-200);
  min-width: 60px;
}

.vote-btn {
  width: 32px;
  height: 32px;
  background: transparent;
  border: none;
  border-radius: var(--border-radius-small);
  cursor: pointer;
  transition: all var(--transition-fast);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text-secondary);
}

.vote-btn.upvote:hover {
  background: rgba(34, 197, 94, 0.1);
  color: #22c55e;
}

.vote-btn.downvote:hover {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
}

.vote-btn.active.upvote {
  background: rgba(34, 197, 94, 0.2);
  color: #22c55e;
}

.vote-btn.active.downvote {
  background: rgba(239, 68, 68, 0.2);
  color: #ef4444;
}

.vote-score {
  font-weight: 600;
  font-size: 0.875rem;
  color: var(--text-primary);
  text-align: center;
}

/* Post Content */
.post-content {
  flex: 1;
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.post-card.compact .post-content {
  padding: 1rem;
  gap: 0.5rem;
}

/* Post Meta */
.post-meta {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.post-type-badge {
  width: 32px;
  height: 32px;
  border-radius: var(--border-radius);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 0.875rem;
  flex-shrink: 0;
}

.post-type-badge.type-text {
  background: var(--primary);
}

.post-type-badge.type-link {
  background: #3b82f6;
}

.post-type-badge.type-image {
  background: #ec4899;
}

.post-author {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.author-avatar {
  width: 32px;
  height: 32px;
  background: var(--primary-light);
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 0.875rem;
}

.author-info {
  display: flex;
  flex-direction: column;
}

.author-name {
  font-weight: 600;
  color: var(--text-primary);
  font-size: 0.875rem;
  line-height: 1;
}

.post-date {
  color: var(--text-secondary);
  font-size: 0.75rem;
  line-height: 1;
}

.post-tags {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
  margin-left: auto;
}

.tag {
  padding: 0.25rem 0.5rem;
  background: var(--surface-200);
  color: var(--text-secondary);
  border-radius: 10px;
  font-size: 0.75rem;
  font-weight: 500;
}

/* Post Main Content */
.post-main {
  flex: 1;
}

.post-title-link {
  text-decoration: none;
  color: inherit;
}

.post-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--text-primary);
  line-height: 1.3;
  margin: 0 0 0.75rem 0;
  transition: color var(--transition-fast);
}

.post-card.compact .post-title {
  font-size: 1rem;
  margin-bottom: 0.5rem;
}

.post-title-link:hover .post-title {
  color: var(--primary);
}

.post-preview {
  color: var(--text-secondary);
  line-height: 1.5;
}

.content-preview {
  margin: 0 0 1rem 0;
  font-size: 0.875rem;
}

.link-preview-mini {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.75rem;
  background: var(--surface-100);
  border-radius: var(--border-radius-small);
  font-size: 0.875rem;
  color: var(--text-secondary);
  margin-bottom: 0.75rem;
}

.attachments-preview {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--text-secondary);
  font-size: 0.875rem;
  margin-bottom: 0.75rem;
}

.attachment-count {
  display: flex;
  align-items: center;
  gap: 0.375rem;
}

/* Post Actions */
.post-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding-top: 0.75rem;
  border-top: 1px solid var(--surface-200);
}

.post-card.compact .post-actions {
  padding-top: 0.5rem;
  gap: 0.75rem;
}

.action-btn {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.5rem 0.75rem;
  background: transparent;
  border: none;
  border-radius: var(--border-radius-small);
  color: var(--text-secondary);
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all var(--transition-fast);
  text-decoration: none;
}

.action-btn:hover {
  background: var(--surface-200);
  color: var(--text-primary);
}

.action-btn.comments:hover {
  color: var(--primary);
}

.action-btn.save.active {
  background: rgba(255, 87, 34, 0.1);
  color: var(--accent);
}

.action-btn.save:hover {
  color: var(--accent);
}

.post-time {
  margin-left: auto;
  color: var(--text-secondary);
  font-size: 0.75rem;
}

/* Pagination */
.pagination-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  margin-top: 2rem;
  padding: 2rem;
  background: white;
  border-radius: var(--border-radius-large);
  box-shadow: var(--shadow-small);
}

.pagination {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.page-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  background: transparent;
  border: 1px solid var(--surface-300);
  border-radius: var(--border-radius);
  color: var(--text-secondary);
  font-weight: 500;
  cursor: pointer;
  transition: all var(--transition-fast);
}

.page-btn:hover:not(:disabled) {
  background: var(--surface-200);
  color: var(--text-primary);
}

.page-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.page-numbers {
  display: flex;
  gap: 0.25rem;
  margin: 0 1rem;
}

.page-number {
  width: 40px;
  height: 40px;
  background: transparent;
  border: 1px solid var(--surface-300);
  border-radius: var(--border-radius);
  color: var(--text-secondary);
  font-weight: 500;
  cursor: pointer;
  transition: all var(--transition-fast);
  display: flex;
  align-items: center;
  justify-content: center;
}

.page-number:hover {
  background: var(--surface-200);
  color: var(--text-primary);
}

.page-number.active {
  background: var(--primary);
  border-color: var(--primary);
  color: white;
}

.pagination-info {
  color: var(--text-secondary);
  font-size: 0.875rem;
  text-align: center;
}

/* Modal */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.modal-content {
  background: white;
  border-radius: var(--border-radius-large);
  box-shadow: var(--shadow-large);
  max-width: 800px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem 2rem;
  border-bottom: 1px solid var(--surface-200);
}

.modal-header h2 {
  margin: 0;
  color: var(--text-primary);
  font-size: 1.5rem;
  font-weight: 600;
}

.modal-close {
  background: none;
  border: none;
  color: var(--text-secondary);
  cursor: pointer;
  padding: 0.5rem;
  border-radius: var(--border-radius-small);
  transition: all var(--transition-fast);
}

.modal-close:hover {
  background: var(--surface-200);
  color: var(--text-primary);
}

.modal-body {
  padding: 2rem;
}

/* Toast Notification */
.toast-notification {
  position: fixed;
  top: 2rem;
  right: 2rem;
  z-index: 9999;
  animation: slideInRight 0.3s ease-out, fadeOutUp 0.3s ease-in 2.2s forwards;
}

.toast-content {
  background: var(--primary);
  color: white;
  padding: 0.875rem 1.5rem;
  border-radius: var(--border-radius-large);
  box-shadow: var(--shadow-large);
  font-weight: 500;
  font-size: 0.875rem;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.1);
}

@keyframes slideInRight {
  from {
    opacity: 0;
    transform: translateX(100px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes fadeOutUp {
  from {
    opacity: 1;
    transform: translateY(0);
  }
  to {
    opacity: 0;
    transform: translateY(-20px);
  }
}

/* Responsive Design */
@media (max-width: 1024px) {
  .forum-header {
    flex-direction: column;
    align-items: stretch;
    gap: 1.5rem;
  }
  
  .controls-bar {
    flex-direction: column;
    align-items: stretch;
    gap: 1rem;
  }
  
  .search-container {
    min-width: auto;
  }
  
  .sort-container,
  .view-toggle {
    justify-content: center;
  }
}

@media (max-width: 768px) {
  .forum-page {
    padding: 1rem 0.5rem;
  }
  
  .forum-breadcrumb {
    padding: 0.75rem;
    margin-bottom: 1rem;
  }
  
  .forum-header {
    padding: 1.5rem;
  }
  
  .forum-info {
    flex-direction: column;
    gap: 1rem;
  }
  
  .forum-icon {
    width: 48px;
    height: 48px;
    font-size: 1.25rem;
  }
  
  .forum-title {
    font-size: 1.5rem;
  }
  
  .forum-stats {
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .controls-bar {
    padding: 1rem;
  }
  
  .sort-tabs {
    flex-wrap: wrap;
    justify-content: center;
  }
  
  .post-card {
    flex-direction: column;
  }
  
  .post-votes {
    flex-direction: row;
    justify-content: center;
    padding: 0.75rem;
    border-right: none;
    border-bottom: 1px solid var(--surface-200);
    min-width: auto;
  }
  
  .post-content {
    padding: 1rem;
  }
  
  .post-meta {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.75rem;
  }
  
  .post-tags {
    margin-left: 0;
  }
  
  .post-actions {
    flex-wrap: wrap;
    gap: 0.5rem;
  }
  
  .action-btn {
    font-size: 0.75rem;
    padding: 0.375rem 0.5rem;
  }
  
  .page-numbers {
    margin: 0 0.5rem;
  }
  
  .modal-header,
  .modal-body {
    padding: 1rem;
  }
}

@media (max-width: 480px) {
  .forum-title {
    font-size: 1.25rem;
  }
  
  .forum-description {
    font-size: 1rem;
  }
  
  .sort-tab {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
  }
  
  .post-title {
    font-size: 1rem;
  }
  
  .post-card.compact .post-title {
    font-size: 0.875rem;
  }
  
  .pagination {
    flex-wrap: wrap;
    justify-content: center;
  }
  
  .page-numbers {
    flex-wrap: wrap;
  }
}

.author-avatar-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 50%;
}

.author-avatar-fallback {
  background: var(--primary-light);
  color: white;
  font-weight: 600;
  font-size: 0.875rem;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
}

/* Styles pour les éléments cliquables */
.clickable-avatar {
  cursor: pointer;
  transition: all var(--transition-fast);
  border-radius: 50%;
  position: relative;
}

.clickable-avatar:hover {
  transform: scale(1.05);
  box-shadow: 0 0 0 3px rgba(38, 166, 154, 0.3);
}

.clickable-name {
  cursor: pointer;
  transition: color var(--transition-fast);
  text-decoration: none;
  border-radius: var(--border-radius-small);
  padding: 0.125rem 0.25rem;
  margin: -0.125rem -0.25rem;
}

.clickable-name:hover {
  color: var(--primary) !important;
  background: rgba(38, 166, 154, 0.1);
}

/* Animation subtile pour indiquer l'interactivité */
@keyframes profileHint {
  0% { background: transparent; }
  50% { background: rgba(38, 166, 154, 0.05); }
  100% { background: transparent; }
}

.clickable-avatar:hover::after {
  content: '';
  position: absolute;
  top: -2px;
  left: -2px;
  right: -2px;
  bottom: -2px;
  border: 2px solid var(--primary);
  border-radius: 50%;
  animation: profileHint 0.6s ease-out;
}

/* Tooltip style pour les titres */
.clickable-avatar[title],
.clickable-name[title] {
  position: relative;
}

/* Responsive - réduire les effets sur mobile */
@media (max-width: 768px) {
  .clickable-avatar:hover {
    transform: scale(1.02);
  }
  
  .clickable-avatar:hover::after {
    display: none;
  }
}
</style>
<template>
  <div class="post-page">
    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
      <div class="emerald-spinner"></div>
      <p>Chargement du post...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-container">
      <i class="pi pi-exclamation-triangle error-icon"></i>
      <h3>Erreur de chargement</h3>
      <p>{{ error }}</p>
      <button @click="fetchPost" class="p-button emerald-button primary">
        <i class="pi pi-refresh"></i>
        Réessayer
      </button>
    </div>

    <!-- Post Content -->
    <div v-else class="post-container">
      <!-- Breadcrumb Navigation -->
      <nav class="post-breadcrumb">
        <RouterLink to="/forums" class="breadcrumb-link">
          <i class="pi pi-home"></i>
          Forums
        </RouterLink>
        <i class="pi pi-chevron-right breadcrumb-separator"></i>
        <RouterLink :to="`/forums/${post.forum?.slug}`" class="breadcrumb-link">
          {{ post.forum?.name || 'Forum' }}
        </RouterLink>
        <i class="pi pi-chevron-right breadcrumb-separator"></i>
        <span class="breadcrumb-current">{{ post.title }}</span>
      </nav>

      <!-- Main Post Card -->
      <article class="post-main-card">
        <!-- Post Header -->
        <header class="post-header">
          <div class="post-meta">
            <div class="post-author">
              <div class="author-avatar">
                {{ post.author?.charAt(0).toUpperCase() }}
              </div>
              <div class="author-info">
                <strong class="author-name">{{ post.author }}</strong>
                <time class="post-date">{{ formatDate(post.createdAt) }}</time>
              </div>
            </div>
            
            <div class="post-stats">
              <div class="stat-item">
                <i class="pi pi-heart"></i>
                <span>{{ post.score || 0 }}</span>
              </div>
              <div class="stat-item">
                <i class="pi pi-comment"></i>
                <span>{{ comments.length }}</span>
              </div>
            </div>
          </div>

          <h1 class="post-title">{{ post.title }}</h1>
          
          <!-- Post Type Badge -->
          <div class="post-badges">
            <span :class="['post-type-badge', `type-${post.postType || 'text'}`]">
              <i :class="getPostTypeIcon(post.postType)"></i>
              {{ getPostTypeLabel(post.postType) }}
            </span>
            
            <!-- Tags -->
            <div v-if="post.tags && post.tags.length" class="post-tags">
              <span v-for="tag in post.tags" :key="tag" class="tag">
                #{{ tag }}
              </span>
            </div>
          </div>
        </header>

        <!-- Post Content -->
        <div class="post-content">
          <!-- Link Preview for link posts -->
          <div v-if="post.postType === 'link' && post.linkUrl" class="link-preview">
            <a :href="post.linkUrl" target="_blank" class="link-preview-card">
              <div class="link-info">
                <h4>{{ post.linkPreview?.title || 'Lien externe' }}</h4>
                <p v-if="post.linkPreview?.description">{{ post.linkPreview.description }}</p>
                <span class="link-url">{{ post.linkUrl }}</span>
              </div>
              <i class="pi pi-external-link link-icon"></i>
            </a>
          </div>

          <!-- Main content -->
          <div class="content-body" v-html="renderMarkdown(post.content)"></div>

          <!-- Attachments -->
          <div v-if="post.attachments && post.attachments.length" class="post-attachments">
            <h4 class="attachments-title">
              <i class="pi pi-paperclip"></i>
              Fichiers joints
            </h4>
            <div class="attachments-grid">
              <div v-for="attachment in post.attachments" :key="attachment.filename" class="attachment-item">
                <div v-if="attachment.type === 'image'" class="image-attachment">
                  <img :src="attachment.url" :alt="attachment.originalName" @click="openImageModal(attachment)" />
                  <div class="image-overlay">
                    <i class="pi pi-search-plus"></i>
                  </div>
                </div>
                <div v-else class="file-attachment">
                  <div class="file-icon">
                    <i class="pi pi-file"></i>
                  </div>
                  <div class="file-info">
                    <span class="file-name">{{ attachment.originalName }}</span>
                    <span class="file-size">{{ formatFileSize(attachment.size) }}</span>
                  </div>
                  <a :href="attachment.url" download class="file-download">
                    <i class="pi pi-download"></i>
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Post Actions -->
        <footer class="post-actions">
          <button 
            @click="handleUpvote(post)"
            :class="['action-btn', 'upvote', { active: post.userVote === 'UP' }]" 
            title="Upvote"
          >
            <i class="pi pi-chevron-up"></i>
            Upvote
          </button>
          <button 
            @click="handleDownvote(post)"
            :class="['action-btn', 'downvote', { active: post.userVote === 'DOWN' }]" 
            title="Downvote"
          >
            <i class="pi pi-chevron-down"></i>
            Downvote
          </button>
          <button @click="sharePost(post)" class="action-btn share">
            <i class="pi pi-share-alt"></i>
            Partager
          </button>
          <button 
            @click="savePost(post)" 
            :class="['action-btn', 'save', { active: post.isSaved }]"
          >
            <i :class="post.isSaved ? 'pi pi-bookmark-fill' : 'pi pi-bookmark'"></i>
            {{ post.isSaved ? 'Sauvegardé' : 'Sauvegarder' }}
          </button>
        </footer>
      </article>

      <!-- Comments Section -->
      <section class="comments-section">
        <header class="comments-header">
          <h2 class="comments-title">
            <i class="pi pi-comments"></i>
            Commentaires
            <span class="comments-count">({{ comments.length }})</span>
          </h2>
          
          <div class="comments-sort">
            <select class="sort-select">
              <option value="top">Les plus populaires</option>
              <option value="new">Les plus récents</option>
              <option value="old">Les plus anciens</option>
            </select>
          </div>
        </header>

        <!-- Add Comment Form -->
        <div class="add-comment-form">
          <div class="comment-form-header">
            <div class="form-avatar">
              {{ getCurrentUserInitial() }}
            </div>
            <h3>Ajouter un commentaire</h3>
          </div>
          
          <div class="comment-editor">
            <textarea 
              v-model="newComment" 
              rows="4" 
              class="comment-textarea"
              placeholder="Partagez votre opinion, posez une question, ou ajoutez des informations utiles..."
            ></textarea>
            
            <div class="comment-actions">
              <div class="comment-help">
                <span>Supporte le Markdown</span>
              </div>
              <div class="comment-buttons">
                <button 
                  @click="newComment = ''" 
                  class="btn-cancel"
                  :disabled="!newComment.trim()"
                >
                  Annuler
                </button>
                <button 
                  @click="submitComment" 
                  class="p-button emerald-button primary"
                  :disabled="loadingComment || !newComment.trim()"
                >
                  <i v-if="loadingComment" class="pi pi-spinner pi-spin"></i>
                  <i v-else class="pi pi-send"></i>
                  {{ loadingComment ? 'Publication...' : 'Publier' }}
                </button>
              </div>
            </div>
          </div>
          
          <div v-if="commentError" class="comment-error">
            <i class="pi pi-exclamation-triangle"></i>
            {{ commentError }}
          </div>
        </div>

        <!-- Comments List - HIÉRARCHIQUE -->
        <div class="comments-list">
          <div v-if="commentTree.length === 0" class="no-comments">
            <i class="pi pi-comment"></i>
            <h3>Aucun commentaire pour l'instant</h3>
            <p>Soyez le premier à partager votre opinion !</p>
          </div>

          <!-- Affichage hiérarchique avec CommentThread -->
          <CommentThread
            v-for="comment in commentTree"
            :key="comment.id"
            :comment="comment"
            :is-collapsed="isCommentCollapsed(comment.id)"
            :is-reply-form-open="isReplyFormOpen(comment.id)"
            :reply-content="replyContents[comment.id] || ''"
            :child-collapsed-states="collapsedComments"
            :child-reply-form-states="replyForms"
            :child-reply-contents="replyContents"
            :get-current-user-initial="getCurrentUserInitial"
            @toggle-collapse="toggleCommentCollapse"
            @toggle-reply="toggleReplyForm"
            @submit-reply="submitReply"
            @update-reply-content="updateReplyContent"
            @comment-upvote="handleCommentUpvote"
            @comment-downvote="handleCommentDownvote"
          />
        </div>
      </section>
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
import { ref, onMounted, provide } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'
import CommentThread from '@/components/forum/CommentThread.vue'

const route = useRoute()
const authStore = useAuthStore()
const postId = route.params.id
const forumSlug = route.params.forumSlug

const post = ref({})
const comments = ref([])
const loading = ref(true)
const error = ref('')

const newComment = ref('')
const loadingComment = ref(false)
const commentError = ref('')

// Toast notifications
const showToast = ref(false)
const toastMessage = ref('')

// États pour la hiérarchie des commentaires
const commentTree = ref([])
const collapsedComments = ref(new Set()) // IDs des commentaires pliés
const replyForms = ref(new Set()) // IDs des formulaires de réponse ouverts
const replyContents = ref({}) // Contenu des formulaires de réponse

// Fonction pour transformer les commentaires plats en arbre hiérarchique
const buildCommentTree = (flatComments) => {
  const commentMap = new Map()
  const tree = []
  
  // 1. Créer une map de tous les commentaires avec leurs enfants
  flatComments.forEach(comment => {
    commentMap.set(comment.id, {
      ...comment,
      children: [],
      level: 0 // Niveau de profondeur (0 = racine)
    })
  })
  
  // 2. Organiser la hiérarchie
  flatComments.forEach(comment => {
    const commentWithChildren = commentMap.get(comment.id)
    
    if (comment.parentId) {
      // C'est une réponse - l'ajouter aux enfants du parent
      const parent = commentMap.get(comment.parentId)
      if (parent) {
        commentWithChildren.level = parent.level + 1
        parent.children.push(commentWithChildren)
      }
    } else {
      // C'est un commentaire racine
      tree.push(commentWithChildren)
    }
  })
  
  return tree
}

// Fonction pour mettre à jour l'arbre des commentaires
const updateCommentTree = () => {
  commentTree.value = buildCommentTree(comments.value)
}

// Fonctions pour gérer le collapse/expand
const toggleCommentCollapse = (commentId) => {
  if (collapsedComments.value.has(commentId)) {
    collapsedComments.value.delete(commentId)
  } else {
    collapsedComments.value.add(commentId)
  }
}

const isCommentCollapsed = (commentId) => {
  return collapsedComments.value.has(commentId)
}

// Fonctions pour gérer les formulaires de réponse
const toggleReplyForm = (commentId) => {
  if (replyForms.value.has(commentId)) {
    replyForms.value.delete(commentId)
    delete replyContents.value[commentId]
  } else {
    replyForms.value.add(commentId)
    replyContents.value[commentId] = ''
  }
}

const isReplyFormOpen = (commentId) => {
  return replyForms.value.has(commentId)
}

// Provide des fonctions pour les composants enfants
provide('isCommentCollapsed', isCommentCollapsed)
provide('isReplyFormOpen', isReplyFormOpen)
provide('getReplyContent', (id) => replyContents.value[id] || '')

// Fonction pour mettre à jour le contenu d'une réponse
const updateReplyContent = (commentId, content) => {
  replyContents.value[commentId] = content
}

// Fonction pour soumettre une réponse
const submitReply = async (parentCommentId) => {
  const content = replyContents.value[parentCommentId]
  
  if (!content || !content.trim()) {
    showToastMessage('Le commentaire ne peut pas être vide ❌')
    return
  }

  try {
    await api.post(`/api/comments/${parentCommentId}/comments`, {
      content: content.trim()
    })
    
    // Fermer le formulaire et vider le contenu
    replyForms.value.delete(parentCommentId)
    delete replyContents.value[parentCommentId]
    
    // Recharger les commentaires
    await fetchPost()
    
    showToastMessage('Réponse publiée ! 💬')
  } catch (error) {
    console.error('Erreur ajout réponse:', error)
    showToastMessage('Erreur lors de la publication ❌')
  }
}

// Fonctions de vote
const voteOnPost = async (post, voteType) => {
  try {
    const response = await api.post(`/api/posts/${post.id}/vote`, { type: voteType })
    
    // Mettre à jour le post local
    post.score = response.data.newScore
    post.userVote = response.data.userVote
    
  } catch (error) {
    console.error('Erreur vote:', error)
    showToastMessage('Erreur lors du vote ❌')
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
  const postUrl = `${window.location.origin}/forums/${forumSlug}/posts/${post.id}`
  
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

// Fonctions de vote sur commentaires
const voteOnComment = async (comment, voteType) => {
  try {
    const response = await api.post(`/api/comments/${comment.id}/vote`, { type: voteType })
    
    // Mettre à jour le commentaire local dans l'arbre
    const updateCommentInTree = (tree) => {
      for (const node of tree) {
        if (node.id === comment.id) {
          node.score = response.data.newScore
          node.userVote = response.data.userVote
          
          // Recalculer les upvotes/downvotes
          if (response.data.userVote === 'UP') {
            node.upvotes = Math.max(0, response.data.newScore + (node.downvotes || 0))
          } else if (response.data.userVote === 'DOWN') {
            node.downvotes = Math.max(0, (node.upvotes || 0) - response.data.newScore)
          } else {
            // Vote neutralisé
            node.upvotes = Math.max(0, response.data.newScore)
            node.downvotes = 0
          }
          return true
        }
        if (node.children && updateCommentInTree(node.children)) {
          return true
        }
      }
      return false
    }
    
    updateCommentInTree(commentTree.value)
    
  } catch (error) {
    console.error('Erreur vote commentaire:', error)
    showToastMessage('Erreur lors du vote ❌')
  }
}

const handleCommentUpvote = (comment) => {
  voteOnComment(comment, 'UP')
}

const handleCommentDownvote = (comment) => {
  voteOnComment(comment, 'DOWN')
}

const formatDate = (dateString) => {
  const date = new Date(dateString)
  const now = new Date()
  const diffTime = Math.abs(now - date)
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  
  if (diffDays === 1) return 'Il y a 1 jour'
  if (diffDays < 7) return `Il y a ${diffDays} jours`
  if (diffDays < 30) return `Il y a ${Math.ceil(diffDays / 7)} semaines`
  
  return date.toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: date.getFullYear() !== now.getFullYear() ? 'numeric' : undefined
  })
}

const getPostTypeIcon = (type) => {
  switch (type) {
    case 'link': return 'pi pi-link'
    case 'image': return 'pi pi-image'
    default: return 'pi pi-align-left'
  }
}

const getPostTypeLabel = (type) => {
  switch (type) {
    case 'link': return 'Lien'
    case 'image': return 'Image'
    default: return 'Texte'
  }
}

const getCurrentUserInitial = () => {
  return authStore.user?.pseudo?.charAt(0).toUpperCase() || 'U'
}

const renderMarkdown = (content) => {
  if (!content) return ''
  
  // Simple rendu Markdown
  return content
    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
    .replace(/\*(.*?)\*/g, '<em>$1</em>')
    .replace(/`(.*?)`/g, '<code>$1</code>')
    .replace(/^> (.+)/gm, '<blockquote>$1</blockquote>')
    .replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank" rel="noopener">$1</a>')
    .replace(/\n/g, '<br>')
}

const formatFileSize = (bytes) => {
  if (!bytes) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i]
}

const openImageModal = (attachment) => {
  // Implémenter modal d'image
  window.open(attachment.url, '_blank')
}

const fetchPost = async () => {
  loading.value = true
  error.value = ''
  
  try {
    const res = await api.get(`/api/posts/${postId}`)
    post.value = res.data
    comments.value = res.data.comments || []
    updateCommentTree()
  } catch (err) {
    console.error('Erreur chargement post:', err)
    error.value = 'Impossible de charger le post. Vérifiez votre connexion.'
  } finally {
    loading.value = false
  }
}

const submitComment = async () => {
  if (!newComment.value.trim()) {
    commentError.value = 'Le commentaire ne peut pas être vide'
    return
  }

  loadingComment.value = true
  commentError.value = ''

  try {
    await api.post(`/api/posts/${postId}/comments`, {
      content: newComment.value.trim()
    })
    newComment.value = ''
    await fetchPost() // Recharger les commentaires et rebuild l'arbre
    showToastMessage('Commentaire publié ! 💬')
  } catch (err) {
    console.error('Erreur ajout commentaire:', err)
    commentError.value = 'Impossible d\'ajouter le commentaire. Réessayez.'
    showToastMessage('Erreur lors de la publication ❌')
  } finally {
    loadingComment.value = false
  }
}

onMounted(fetchPost)
</script>

<style scoped>
.post-page {
  max-width: 1000px;
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

/* Breadcrumb */
.post-breadcrumb {
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
  max-width: 300px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* Main Post Card */
.post-main-card {
  background: white;
  border-radius: var(--border-radius-large);
  box-shadow: var(--shadow-medium);
  margin-bottom: 2rem;
  overflow: hidden;
}

.post-header {
  padding: 2rem;
  border-bottom: 1px solid var(--surface-200);
}

.post-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.post-author {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.author-avatar {
  width: 48px;
  height: 48px;
  background: var(--primary-light);
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 1.25rem;
}

.author-info {
  display: flex;
  flex-direction: column;
}

.author-name {
  color: var(--text-primary);
  font-size: 1rem;
}

.post-date {
  color: var(--text-secondary);
  font-size: 0.875rem;
}

.post-stats {
  display: flex;
  gap: 1rem;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--text-secondary);
  font-weight: 500;
}

.post-title {
  font-size: 2rem;
  font-weight: 700;
  color: var(--text-primary);
  line-height: 1.3;
  margin-bottom: 1rem;
}

.post-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  align-items: center;
}

.post-type-badge {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-size: 0.875rem;
  font-weight: 600;
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

.post-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.tag {
  padding: 0.25rem 0.75rem;
  background: var(--surface-200);
  color: var(--text-secondary);
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 500;
}

/* Post Content */
.post-content {
  padding: 2rem;
}

.link-preview {
  margin-bottom: 1.5rem;
}

.link-preview-card {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background: var(--surface-100);
  border: 1px solid var(--surface-300);
  border-radius: var(--border-radius);
  text-decoration: none;
  color: inherit;
  transition: all var(--transition-fast);
}

.link-preview-card:hover {
  background: var(--surface-200);
  transform: translateY(-1px);
}

.link-info h4 {
  margin: 0 0 0.5rem 0;
  color: var(--primary);
}

.link-info p {
  margin: 0 0 0.5rem 0;
  color: var(--text-secondary);
  font-size: 0.875rem;
}

.link-url {
  font-size: 0.75rem;
  color: var(--text-secondary);
  opacity: 0.8;
}

.link-icon {
  color: var(--primary);
  font-size: 1.25rem;
}

.content-body {
  font-size: 1rem;
  line-height: 1.7;
  color: var(--text-primary);
  margin-bottom: 1.5rem;
}

.content-body strong {
  font-weight: 600;
}

.content-body code {
  background: var(--surface-200);
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-family: 'Fira Code', monospace;
  font-size: 0.875rem;
}

.content-body blockquote {
  border-left: 4px solid var(--primary);
  padding-left: 1rem;
  margin: 1rem 0;
  background: var(--surface-100);
  padding: 1rem;
  border-radius: 0 var(--border-radius) var(--border-radius) 0;
}

/* Attachments */
.post-attachments {
  margin-top: 2rem;
  padding-top: 2rem;
  border-top: 1px solid var(--surface-200);
}

.attachments-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1rem;
  color: var(--text-primary);
  font-weight: 600;
}

.attachments-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 1rem;
}

.image-attachment {
  position: relative;
  aspect-ratio: 16/9;
  border-radius: var(--border-radius);
  overflow: hidden;
  cursor: pointer;
}

.image-attachment img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.image-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  opacity: 0;
  transition: opacity var(--transition-fast);
}

.image-attachment:hover .image-overlay {
  opacity: 1;
}

.file-attachment {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem;
  background: var(--surface-100);
  border-radius: var(--border-radius);
}

.file-icon {
  width: 40px;
  height: 40px;
  background: var(--primary-light);
  color: white;
  border-radius: var(--border-radius);
  display: flex;
  align-items: center;
  justify-content: center;
}

.file-info {
  flex: 1;
}

.file-name {
  display: block;
  font-weight: 500;
  color: var(--text-primary);
}

.file-size {
  font-size: 0.875rem;
  color: var(--text-secondary);
}

.file-download {
  padding: 0.5rem;
  color: var(--primary);
  text-decoration: none;
  border-radius: var(--border-radius-small);
  transition: background var(--transition-fast);
}

.file-download:hover {
  background: var(--surface-200);
}

/* Post Actions */
.post-actions {
  display: flex;
  gap: 0.5rem;
  padding: 1rem 2rem;
  background: var(--surface-100);
  border-top: 1px solid var(--surface-200);
}

.action-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  background: transparent;
  border: none;
  border-radius: var(--border-radius);
  color: var(--text-secondary);
  font-weight: 500;
  cursor: pointer;
  transition: all var(--transition-fast);
}

.action-btn:hover {
  background: var(--surface-200);
  color: var(--text-primary);
}

.action-btn.upvote:hover {
  color: #22c55e;
}

.action-btn.downvote:hover {
  color: #ef4444;
}

.action-btn.upvote.active {
  background: rgba(34, 197, 94, 0.2);
  color: #22c55e;
}

.action-btn.downvote.active {
  background: rgba(239, 68, 68, 0.2);
  color: #ef4444;
}

.action-btn.save.active {
  background: rgba(255, 87, 34, 0.1);
  color: var(--accent);
}

.action-btn.save:hover {
  color: var(--accent);
}

/* Comments Section */
.comments-section {
  background: white;
  border-radius: var(--border-radius-large);
  box-shadow: var(--shadow-medium);
  overflow: hidden;
}

.comments-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 2rem;
  background: var(--surface-100);
  border-bottom: 1px solid var(--surface-200);
}

.comments-title {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0;
}

.comments-count {
  color: var(--text-secondary);
  font-weight: 500;
}

.sort-select {
  padding: 0.5rem 1rem;
  border: 1px solid var(--surface-300);
  border-radius: var(--border-radius);
  background: white;
  color: var(--text-primary);
}

/* Add Comment Form */
.add-comment-form {
  padding: 2rem;
  border-bottom: 1px solid var(--surface-200);
}

.comment-form-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.form-avatar {
  width: 40px;
  height: 40px;
  background: var(--primary-light);
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
}

.comment-form-header h3 {
  margin: 0;
  color: var(--text-primary);
  font-weight: 600;
}

.comment-editor {
  margin-left: 52px;
}

.comment-textarea {
  width: 100%;
  padding: 1rem;
  border: 2px solid var(--surface-300);
  border-radius: var(--border-radius);
  font-family: inherit;
  font-size: 1rem;
  resize: vertical;
  min-height: 120px;
  transition: border-color var(--transition-fast);
}

.comment-textarea:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(38, 166, 154, 0.1);
}

.comment-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 1rem;
}

.comment-help {
  font-size: 0.875rem;
  color: var(--text-secondary);
}

.comment-buttons {
  display: flex;
  gap: 0.75rem;
}

.btn-cancel {
  padding: 0.75rem 1.5rem;
  background: transparent;
  border: 1px solid var(--surface-300);
  border-radius: var(--border-radius);
  color: var(--text-secondary);
  font-weight: 500;
  cursor: pointer;
  transition: all var(--transition-fast);
}

.btn-cancel:hover:not(:disabled) {
  background: var(--surface-200);
  color: var(--text-primary);
}

.btn-cancel:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.comment-error {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: 1rem;
  padding: 0.75rem;
  background: rgba(239, 68, 68, 0.1);
  color: #dc2626;
  border-radius: var(--border-radius);
  font-size: 0.875rem;
}

/* Comments List - HIÉRARCHIQUE */
.comments-list {
  padding: 2rem;
  background: var(--surface-50);
}

.no-comments {
  text-align: center;
  padding: 3rem 2rem;
  color: var(--text-secondary);
  background: white;
  border-radius: var(--border-radius);
}

.no-comments i {
  font-size: 3rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

.no-comments h3 {
  margin: 0 0 0.5rem 0;
  color: var(--text-primary);
}

.no-comments p {
  margin: 0;
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
@media (max-width: 768px) {
  .post-page {
    padding: 1rem 0.5rem;
  }
  
  .post-breadcrumb {
    padding: 0.75rem;
    margin-bottom: 1rem;
  }
  
  .breadcrumb-current {
    max-width: 150px;
  }
  
  .post-header {
    padding: 1.5rem;
  }
  
  .post-meta {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .post-title {
    font-size: 1.5rem;
  }
  
  .post-content {
    padding: 1.5rem;
  }
  
  .post-actions {
    padding: 0.75rem 1.5rem;
    flex-wrap: wrap;
  }
  
  .comments-header {
    padding: 1.5rem;
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .add-comment-form {
    padding: 1.5rem;
  }
  
  .comment-editor {
    margin-left: 0;
  }
  
  .comment-form-header {
    margin-bottom: 1rem;
  }
  
  .comment-actions {
    flex-direction: column;
    gap: 1rem;
    align-items: flex-start;
  }
  
  .comment-buttons {
    width: 100%;
    justify-content: flex-end;
  }
  
  .comments-list {
    padding: 1.5rem;
  }
  
  .attachments-grid {
    grid-template-columns: 1fr;
  }
  
  .action-btn {
    font-size: 0.875rem;
    padding: 0.5rem 0.75rem;
  }
}

@media (max-width: 480px) {
  .post-title {
    font-size: 1.25rem;
  }
  
  .post-badges {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>
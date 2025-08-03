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
                <span>{{ post.score }}</span>
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
          <button class="action-btn upvote">
            <i class="pi pi-chevron-up"></i>
            Upvote
          </button>
          <button class="action-btn downvote">
            <i class="pi pi-chevron-down"></i>
            Downvote
          </button>
          <button class="action-btn share">
            <i class="pi pi-share-alt"></i>
            Partager
          </button>
          <button class="action-btn save">
            <i class="pi pi-bookmark"></i>
            Sauvegarder
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

        <!-- Comments List -->
        <div class="comments-list">
          <div v-if="comments.length === 0" class="no-comments">
            <i class="pi pi-comment"></i>
            <h3>Aucun commentaire pour l'instant</h3>
            <p>Soyez le premier à partager votre opinion !</p>
          </div>

          <article 
            v-for="comment in comments" 
            :key="comment.id" 
            class="comment-card"
          >
            <div class="comment-sidebar">
              <div class="comment-avatar">
                {{ comment.author?.charAt(0).toUpperCase() }}
              </div>
              <div class="comment-line"></div>
            </div>
            
            <div class="comment-content">
              <header class="comment-header">
                <div class="comment-meta">
                  <strong class="comment-author">{{ comment.author }}</strong>
                  <time class="comment-date">{{ formatDate(comment.createdAt) }}</time>
                  <span v-if="comment.score !== 0" class="comment-score">
                    {{ comment.score > 0 ? '+' : '' }}{{ comment.score }}
                  </span>
                </div>
              </header>
              
              <div class="comment-body" v-html="renderMarkdown(comment.content)"></div>
              
              <footer class="comment-actions">
                <button class="comment-action upvote">
                  <i class="pi pi-chevron-up"></i>
                  {{ comment.upvotes || 0 }}
                </button>
                <button class="comment-action downvote">
                  <i class="pi pi-chevron-down"></i>
                  {{ comment.downvotes || 0 }}
                </button>
                <button class="comment-action reply">
                  <i class="pi pi-reply"></i>
                  Répondre
                </button>
                <button class="comment-action share">
                  <i class="pi pi-share-alt"></i>
                  Partager
                </button>
              </footer>
            </div>
          </article>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'

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
    await fetchPost() // Recharger les commentaires
  } catch (err) {
    console.error('Erreur ajout commentaire:', err)
    commentError.value = 'Impossible d\'ajouter le commentaire. Réessayez.'
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

/* Comments List */
.comments-list {
  padding: 2rem;
}

.no-comments {
  text-align: center;
  padding: 3rem 2rem;
  color: var(--text-secondary);
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

/* Comment Cards */
.comment-card {
  display: flex;
  gap: 1rem;
  margin-bottom: 1.5rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid var(--surface-200);
}

.comment-card:last-child {
  border-bottom: none;
  margin-bottom: 0;
  padding-bottom: 0;
}

.comment-sidebar {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
}

.comment-avatar {
  width: 40px;
  height: 40px;
  background: var(--secondary-light);
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 1rem;
  flex-shrink: 0;
}

.comment-line {
  width: 2px;
  height: 100%;
  background: var(--surface-300);
  min-height: 60px;
}

.comment-content {
  flex: 1;
  min-width: 0;
}

.comment-header {
  margin-bottom: 0.75rem;
}

.comment-meta {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.comment-author {
  color: var(--text-primary);
  font-weight: 600;
}

.comment-date {
  color: var(--text-secondary);
  font-size: 0.875rem;
}

.comment-score {
  color: var(--primary);
  font-weight: 600;
  font-size: 0.875rem;
}

.comment-body {
  color: var(--text-primary);
  line-height: 1.6;
  margin-bottom: 1rem;
  word-wrap: break-word;
}

.comment-body strong {
  font-weight: 600;
}

.comment-body code {
  background: var(--surface-200);
  padding: 0.125rem 0.375rem;
  border-radius: 3px;
  font-family: 'Fira Code', monospace;
  font-size: 0.875rem;
}

.comment-body blockquote {
  border-left: 3px solid var(--primary);
  padding-left: 0.75rem;
  margin: 0.75rem 0;
  background: var(--surface-100);
  padding: 0.75rem;
  border-radius: 0 var(--border-radius-small) var(--border-radius-small) 0;
}

.comment-actions {
  display: flex;
  gap: 0.5rem;
}

.comment-action {
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
}

.comment-action:hover {
  background: var(--surface-200);
  color: var(--text-primary);
}

.comment-action.upvote:hover {
  color: #22c55e;
}

.comment-action.downvote:hover {
  color: #ef4444;
}

.comment-action.reply:hover {
  color: var(--primary);
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
  
  .comment-card {
    gap: 0.75rem;
  }
  
  .comment-avatar {
    width: 32px;
    height: 32px;
    font-size: 0.875rem;
  }
  
  .comment-actions {
    flex-wrap: wrap;
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
  
  .comment-meta {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.25rem;
  }
  
  .comment-actions {
    gap: 0.25rem;
  }
  
  .comment-action {
    font-size: 0.75rem;
    padding: 0.375rem 0.5rem;
  }
}
</style>
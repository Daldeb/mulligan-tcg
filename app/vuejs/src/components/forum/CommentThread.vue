<template>
  <div class="comment-thread">
    <article 
      :class="['comment-item', { 'collapsed': isCollapsed }]"
      :style="{ marginLeft: `${comment.level * 20}px` }"
    >
      <!-- Ligne de connexion hiérarchique -->
      <div v-if="comment.level > 0" class="comment-connection-line"></div>
      
      <div class="comment-card">
        <div class="comment-sidebar">
          <!-- Bouton collapse/expand -->
          <button 
            v-if="comment.children?.length > 0"
            @click="$emit('toggle-collapse', comment.id)"
            :class="['collapse-btn', { collapsed: isCollapsed }]"
            :title="isCollapsed ? 'Déplier' : 'Plier'"
          >
            <i :class="isCollapsed ? 'pi pi-plus' : 'pi pi-minus'"></i>
          </button>
          
          <!-- Avatar -->
          <div 
            class="comment-avatar"
            :class="{ 'clickable-avatar': canNavigateToProfile(comment.authorId) }"
            @click="canNavigateToProfile(comment.authorId) && goToProfile(comment.authorId, comment.author)"
            :title="canNavigateToProfile(comment.authorId) ? getProfileTooltip(comment.author) : ''"
          >
            <img 
              v-if="comment.authorAvatar"
              :src="`${backendUrl}/uploads/${comment.authorAvatar}`"
              class="comment-avatar-image"
              alt="Avatar"
            />
            <span v-else class="comment-avatar-fallback">
              {{ comment.author?.charAt(0).toUpperCase() }}
            </span>
          </div>
          
          <!-- Ligne verticale pour les enfants -->
          <div 
            v-if="comment.children?.length > 0 && !isCollapsed" 
            class="comment-line"
          ></div>
        </div>
        
        <div class="comment-content">
          <header class="comment-header">
            <div class="comment-meta">
              <strong 
                class="comment-author"
                :class="{ 'clickable-name': canNavigateToProfile(comment.authorId) }"
                @click="canNavigateToProfile(comment.authorId) && goToProfile(comment.authorId, comment.author)"
                :title="canNavigateToProfile(comment.authorId) ? getProfileTooltip(comment.author) : ''"
              >
                {{ comment.author }}
              </strong>
              <time class="comment-date">{{ formatDate(comment.createdAt) }}</time>
              <span v-if="comment.score !== 0" class="comment-score">
                {{ comment.score > 0 ? '+' : '' }}{{ comment.score }}
              </span>
            </div>
          </header>
          
          <!-- Contenu du commentaire (masqué si plié) -->
          <div v-if="!isCollapsed" class="comment-body">
            <div class="comment-text" v-html="renderMarkdown(comment.content)"></div>
            
            <footer class="comment-actions">
              <button 
                @click="$emit('comment-upvote', comment)"
                :class="['comment-action', 'upvote', { active: comment.userVote === 'UP' }]"
              >
                <i class="pi pi-chevron-up"></i>
                {{ comment.upvotes || 0 }}
              </button>
              
              <button 
                @click="$emit('comment-downvote', comment)"
                :class="['comment-action', 'downvote', { active: comment.userVote === 'DOWN' }]"
              >
                <i class="pi pi-chevron-down"></i>
                {{ comment.downvotes || 0 }}
              </button>
              
              <button 
                @click="$emit('toggle-reply', comment.id)"
                class="comment-action reply"
              >
                <i class="pi pi-reply"></i>
                Répondre
              </button>
              
              <button class="comment-action share">
                <i class="pi pi-share-alt"></i>
                Partager
              </button>
              <button 
                v-if="comment.canDelete"
                @click="$emit('delete-comment', comment)"
                class="comment-action delete"
                :title="'Supprimer le commentaire'"
              >
                <i class="pi pi-trash"></i>
                Supprimer
              </button>
            </footer>
            
            <!-- Formulaire de réponse -->
            <div v-if="actualIsReplyFormOpen" class="reply-form">
              <div class="reply-form-header">
                <div class="reply-avatar">
                  <img 
                    v-if="currentUserAvatar"
                    :src="`${backendUrl}/uploads/${currentUserAvatar}`"
                    class="reply-avatar-image"
                    alt="Avatar"
                  />
                  <span v-else class="reply-avatar-fallback">
                    {{ getCurrentUserInitial() }}
                  </span>
                </div>
                <span class="reply-to">Répondre à {{ comment.author }}</span>
              </div>
              
              <div class="reply-editor">
                <textarea 
                  v-model="localContent"
                  rows="3" 
                  class="reply-textarea"
                  placeholder="Écrivez votre réponse..."
                  @input="handleInput"
                ></textarea>
                
                <div class="reply-actions">
                  <button 
                    @click="$emit('toggle-reply', comment.id)"
                    class="btn-cancel"
                  >
                    Annuler
                  </button>
                  <button 
                    @click="$emit('submit-reply', comment.id)"
                    class="p-button emerald-button primary btn-submit"
                    :disabled="!localContent || !localContent.trim()"
                  >
                    <i class="pi pi-send"></i>
                    Répondre
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </article>

    <!-- Commentaires enfants (récursif) - AVEC INJECTION -->
    <div v-if="!isCollapsed && comment.children?.length > 0" class="comment-children">
    <CommentThread
      v-for="child in comment.children"
      :key="child.id"
      :comment="child"
      :is-collapsed="isCommentCollapsed(child.id)"
      :is-reply-form-open="isReplyFormOpenInjected(child.id)"
      :reply-content="getReplyContent(child.id)"
      @toggle-collapse="$emit('toggle-collapse', $event)"
      @toggle-reply="$emit('toggle-reply', $event)"
      @submit-reply="$emit('submit-reply', $event)"
      @update-reply-content="(commentId, content) => $emit('update-reply-content', commentId, content)"
      @comment-upvote="$emit('comment-upvote', $event)"
      @comment-downvote="$emit('comment-downvote', $event)"
      @delete-comment="$emit('delete-comment', $event)"
    />
    </div>
  </div>
</template>

<script setup>
import { defineProps, defineEmits, computed, inject, ref, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
// Ajouter cette ligne aux imports existants
import { useProfileNavigation } from '@/composables/useProfileNavigation'

// Dans le script setup, ajouter :
const { goToProfile, canNavigateToProfile, getProfileTooltip } = useProfileNavigation()

const router = useRouter()

const authStore = useAuthStore() 
const backendUrl = computed(() => import.meta.env.VITE_BACKEND_URL)
const currentUserAvatar = computed(() => authStore.user?.avatar) 

const getCurrentUserInitial = () => { 
  return authStore.user?.pseudo?.charAt(0).toUpperCase() || 'U'
}

const props = defineProps({
  comment: {
    type: Object,
    required: true
  },
  isCollapsed: {
    type: Boolean,
    default: false
  },
  isReplyFormOpen: {
    type: Boolean,
    default: false
  },
  replyContent: {
    type: [String, Array, Object],
    default: ''
  }
})

const emit = defineEmits([
  'toggle-collapse',
  'toggle-reply', 
  'submit-reply',
  'update-reply-content',
  'comment-upvote',
  'comment-downvote',
  'delete-comment'
])

// État local pour le contenu de la réponse
const localContent = ref('')

// Injection des fonctions du parent
const isCommentCollapsed = inject('isCommentCollapsed', () => false)
const isReplyFormOpenInjected = inject('isReplyFormOpen', () => false)
const getReplyContent = inject('getReplyContent', () => '')

// Computed pour utiliser les bonnes valeurs (props ou injection)
const actualIsReplyFormOpen = computed(() => {
  // Si on a une prop directe, on l'utilise, sinon on utilise l'injection
  if (props.isReplyFormOpen !== false) {
    return props.isReplyFormOpen
  }
  return isReplyFormOpenInjected(props.comment.id)
})

// Fonction pour gérer l'input local
const handleInput = (event) => {
  const inputValue = event.target.value
  
  // DEBUG: Voir ce qu'on émet
//   console.log('handleInput - inputValue:', inputValue)
//   console.log('handleInput - commentId:', props.comment.id)
  
  // Met à jour l'état local
  localContent.value = inputValue
  
  // Émet vers le parent avec les bons arguments
  emit('update-reply-content', props.comment.id, inputValue)
}

// Reset localContent quand le formulaire se ferme
watch(actualIsReplyFormOpen, (isOpen) => {
  if (!isOpen) {
    localContent.value = ''
  }
})

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

const renderMarkdown = (content) => {
  if (!content) return ''
  
  return content
    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
    .replace(/\*(.*?)\*/g, '<em>$1</em>')
    .replace(/`(.*?)`/g, '<code>$1</code>')
    .replace(/^> (.+)/gm, '<blockquote>$1</blockquote>')
    .replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank" rel="noopener">$1</a>')
    .replace(/\n/g, '<br>')
}

</script>

<style scoped>
.comment-thread {
  position: relative;
}

.comment-item {
  position: relative;
  margin-bottom: 1rem;
}

.comment-item.collapsed .comment-content {
  opacity: 0.6;
}

/* Ligne de connexion hiérarchique */
.comment-connection-line {
  position: absolute;
  left: -10px;
  top: 20px;
  width: 20px;
  height: 2px;
  background: var(--surface-300);
  z-index: 1;
}

.comment-card {
  display: flex;
  gap: 0.75rem;
  background: white;
  border-radius: var(--border-radius);
  padding: 1rem;
  border: 1px solid var(--surface-200);
  transition: all var(--transition-fast);
}

.comment-card:hover {
  border-color: var(--surface-300);
  box-shadow: var(--shadow-small);
}

.comment-sidebar {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  position: relative;
}

/* Bouton collapse/expand */
.collapse-btn {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: var(--surface-200);
  border: none;
  color: var(--text-secondary);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  transition: all var(--transition-fast);
  z-index: 2;
}

.collapse-btn:hover {
  background: var(--primary);
  color: white;
  transform: scale(1.1);
}

.collapse-btn.collapsed {
  background: var(--accent);
  color: white;
}

.comment-avatar {
  width: 32px;
  height: 32px;
  background: var(--secondary-light);
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 0.875rem;
  flex-shrink: 0;
}

/* Ligne verticale pour connecter aux enfants */
.comment-line {
  width: 2px;
  height: 100%;
  background: var(--surface-300);
  min-height: 40px;
  position: absolute;
  top: 52px;
  left: 50%;
  transform: translateX(-50%);
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
  margin-top: 0.5rem;
}

.comment-text {
  color: var(--text-primary);
  line-height: 1.6;
  margin-bottom: 1rem;
  word-wrap: break-word;
}

.comment-text strong {
  font-weight: 600;
}

.comment-text code {
  background: var(--surface-200);
  padding: 0.125rem 0.375rem;
  border-radius: 3px;
  font-family: 'Fira Code', monospace;
  font-size: 0.875rem;
}

.comment-text blockquote {
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
  align-items: center;
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

.comment-action.upvote.active {
  background: rgba(34, 197, 94, 0.2);
  color: #22c55e;
}

.comment-action.downvote.active {
  background: rgba(239, 68, 68, 0.2);
  color: #ef4444;
}

.comment-action.reply:hover {
  color: var(--primary);
}

/* Formulaire de réponse */
.reply-form {
  margin-top: 1rem;
  padding: 1rem;
  background: var(--surface-100);
  border-radius: var(--border-radius);
  border: 1px solid var(--surface-200);
}

.reply-form-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}

.reply-avatar {
  width: 24px;
  height: 24px;
  background: var(--primary-light);
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 0.75rem;
}

.reply-to {
  color: var(--text-secondary);
  font-size: 0.875rem;
}

.reply-textarea {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid var(--surface-300);
  border-radius: var(--border-radius);
  font-family: inherit;
  font-size: 0.875rem;
  resize: vertical;
  min-height: 80px;
  transition: border-color var(--transition-fast);
}

.reply-textarea:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 2px rgba(38, 166, 154, 0.1);
}

.reply-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  margin-top: 0.75rem;
}

.btn-cancel {
  padding: 0.5rem 1rem;
  background: transparent;
  border: 1px solid var(--surface-300);
  border-radius: var(--border-radius);
  color: var(--text-secondary);
  font-weight: 500;
  cursor: pointer;
  transition: all var(--transition-fast);
}

.btn-cancel:hover {
  background: var(--surface-200);
  color: var(--text-primary);
}

.btn-submit {
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
}

.btn-submit:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Commentaires enfants */
.comment-children {
  margin-top: 0.5rem;
  border-left: 2px solid var(--surface-200);
  padding-left: 1rem;
}

/* Responsive */
@media (max-width: 768px) {
  .comment-item {
    margin-left: 0 !important;
  }
  
  .comment-card {
    padding: 0.75rem;
    gap: 0.5rem;
  }
  
  .comment-avatar {
    width: 28px;
    height: 28px;
    font-size: 0.75rem;
  }
  
  .comment-actions {
    flex-wrap: wrap;
    gap: 0.25rem;
  }
  
  .comment-action {
    font-size: 0.75rem;
    padding: 0.375rem 0.5rem;
  }
  
  .comment-children {
    padding-left: 0.5rem;
  }
}

.comment-avatar-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 50%;
}

.comment-avatar-fallback {
  background: var(--secondary-light);
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

.reply-avatar-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 50%;
}

.reply-avatar-fallback {
  background: var(--primary-light);
  color: white;
  font-weight: 600;
  font-size: 0.75rem;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
}

/* Bouton de suppression commentaire */
.comment-action.delete {
  color: var(--text-secondary);
}

.comment-action.delete:hover {
  color: #ef4444;
  background: rgba(239, 68, 68, 0.1);
}

/* Responsive */
@media (max-width: 768px) {
  .comment-action.delete {
    color: #ef4444;
    opacity: 0.8;
  }
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
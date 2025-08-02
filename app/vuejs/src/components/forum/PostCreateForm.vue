<template>
  <div class="reddit-post-form">
    <!-- Header avec types de posts -->
    <div class="form-header">
      <h2 class="form-title">Créer un nouveau post</h2>
      <div class="post-type-tabs">
        <button 
          v-for="type in postTypes" 
          :key="type.value"
          :class="['post-type-tab', { active: postType === type.value }]"
          @click="setPostType(type.value)"
        >
          <i :class="type.icon"></i>
          {{ type.label }}
        </button>
      </div>
    </div>

    <!-- Formulaire principal -->
    <div class="form-content">
      <!-- Titre -->
      <div class="form-group">
        <label class="form-label">Titre *</label>
        <input
          v-model="title"
          type="text"
          class="form-input"
          placeholder="Un titre accrocheur pour votre post..."
          maxlength="150"
          @input="validateForm"
        />
        <div class="character-count">{{ title.length }}/150</div>
      </div>

      <!-- URL pour les posts de type lien -->
      <div v-if="postType === 'link'" class="form-group">
        <label class="form-label">URL *</label>
        <input
          v-model="linkUrl"
          type="url"
          class="form-input"
          placeholder="https://example.com"
          @blur="fetchLinkPreview"
        />
        
        <!-- Prévisualisation du lien -->
        <div v-if="linkPreview" class="link-preview">
          <div class="link-preview-content">
            <img v-if="linkPreview.image" :src="linkPreview.image" alt="Preview" class="link-preview-image" />
            <div class="link-preview-text">
              <h4>{{ linkPreview.title }}</h4>
              <p>{{ linkPreview.description }}</p>
              <span class="link-preview-url">{{ linkPreview.url }}</span>
            </div>
          </div>
          <button @click="linkPreview = null" class="link-preview-remove">
            <i class="pi pi-times"></i>
          </button>
        </div>
      </div>

      <!-- Zone d'upload d'images -->
      <div v-if="postType === 'image'" class="form-group">
        <label class="form-label">Images</label>
        <div 
          class="image-upload-zone"
          :class="{ 'drag-over': isDragOver }"
          @dragover.prevent="isDragOver = true"
          @dragleave.prevent="isDragOver = false"
          @drop.prevent="handleImageDrop"
          @click="$refs.imageInput.click()"
        >
          <div v-if="!imageFiles.length" class="upload-placeholder">
            <i class="pi pi-cloud-upload upload-icon"></i>
            <p>Glissez vos images ici ou cliquez pour sélectionner</p>
            <p class="upload-hint">JPG, PNG, GIF, WebP - Max 5MB par image</p>
          </div>
          
          <!-- Aperçu des images -->
          <div v-else class="image-preview-grid">
            <div v-for="(file, index) in imageFiles" :key="index" class="image-preview">
              <img :src="file.preview" :alt="file.name" />
              <button @click="removeImage(index)" class="image-remove">
                <i class="pi pi-times"></i>
              </button>
              <div class="image-info">{{ file.name }}</div>
            </div>
            <div class="add-more-images" @click="$refs.imageInput.click()">
              <i class="pi pi-plus"></i>
              <span>Ajouter</span>
            </div>
          </div>
        </div>
        <input
          ref="imageInput"
          type="file"
          multiple
          accept="image/jpeg,image/png,image/gif,image/webp"
          @change="handleImageSelect"
          style="display: none"
        />
      </div>

      <!-- Contenu / Description -->
      <div class="form-group">
        <label class="form-label">
          {{ postType === 'link' ? 'Description (optionnelle)' : 'Contenu *' }}
        </label>
        
        <!-- Toolbar Markdown -->
        <div class="markdown-toolbar">
          <button 
            v-for="tool in markdownTools" 
            :key="tool.name"
            @click="insertMarkdown(tool)"
            class="markdown-tool"
            :title="tool.title"
          >
            <i :class="tool.icon"></i>
          </button>
          
          <div class="toolbar-separator"></div>
          
          <button @click="showPreview = !showPreview" class="preview-toggle" :class="{ active: showPreview }">
            <i class="pi pi-eye"></i>
            Aperçu
          </button>
        </div>

        <!-- Zone d'édition -->
        <div class="content-editor">
          <textarea
            v-if="!showPreview"
            ref="contentTextarea"
            v-model="content"
            class="form-textarea"
            :placeholder="getContentPlaceholder()"
            rows="8"
            @input="validateForm"
          ></textarea>
          
          <!-- Aperçu Markdown -->
          <div v-else class="markdown-preview" v-html="renderedContent"></div>
        </div>
        
        <div class="editor-help">
          <span>Supporte le Markdown</span>
          <span class="character-count">{{ content.length }} caractères</span>
        </div>
      </div>

      <!-- Tags -->
      <div class="form-group">
        <label class="form-label">Tags (optionnels)</label>
        <div class="tags-input-container">
          <div class="tags-list">
            <span v-for="tag in tags" :key="tag" class="tag">
              {{ tag }}
              <button @click="removeTag(tag)" class="tag-remove">
                <i class="pi pi-times"></i>
              </button>
            </span>
          </div>
          <input
            v-model="newTag"
            type="text"
            class="tag-input"
            placeholder="Ajouter un tag..."
            @keydown.enter.prevent="addTag"
            @keydown.comma.prevent="addTag"
          />
        </div>
        <div class="tags-help">Appuyez sur Entrée ou virgule pour ajouter un tag</div>
      </div>

      <!-- Fichiers joints (pour tous types) -->
      <div class="form-group">
        <label class="form-label">Fichiers joints (optionnels)</label>
        <div class="file-upload-zone" @click="$refs.fileInput.click()">
          <i class="pi pi-paperclip"></i>
          <span>Ajouter des fichiers (PDF, DOC, TXT...)</span>
        </div>
        <input
          ref="fileInput"
          type="file"
          multiple
          accept=".pdf,.doc,.docx,.txt"
          @change="handleFileSelect"
          style="display: none"
        />
        
        <!-- Liste des fichiers -->
        <div v-if="attachmentFiles.length" class="attachment-list">
          <div v-for="(file, index) in attachmentFiles" :key="index" class="attachment-item">
            <i class="pi pi-file attachment-icon"></i>
            <span class="attachment-name">{{ file.name }}</span>
            <span class="attachment-size">{{ formatFileSize(file.size) }}</span>
            <button @click="removeAttachment(index)" class="attachment-remove">
              <i class="pi pi-times"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="form-actions">
        <div class="form-status">
          <div v-if="errors.length" class="error-messages">
            <div v-for="error in errors" :key="error" class="error-message">
              <i class="pi pi-exclamation-triangle"></i>
              {{ error }}
            </div>
          </div>
        </div>
        
        <div class="action-buttons">
          <button @click="resetForm" class="btn-secondary" :disabled="loading">
            <i class="pi pi-refresh"></i>
            Réinitialiser
          </button>
          <button @click="saveDraft" class="btn-outline" :disabled="loading || !canSaveDraft">
            <i class="pi pi-save"></i>
            Brouillon
          </button>
          <button @click="submitPost" class="btn-primary" :disabled="loading || !isFormValid">
            <i v-if="loading" class="pi pi-spinner pi-spin"></i>
            <i v-else class="pi pi-send"></i>
            {{ loading ? 'Publication...' : 'Publier' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'

const props = defineProps({
  forumSlug: String
})

const router = useRouter()

// État du formulaire
const postType = ref('text')
const title = ref('')
const content = ref('')
const linkUrl = ref('')
const linkPreview = ref(null)
const tags = ref([])
const newTag = ref('')
const imageFiles = ref([])
const attachmentFiles = ref([])
const showPreview = ref(false)
const isDragOver = ref(false)
const loading = ref(false)
const errors = ref([])

// Types de posts
const postTypes = [
  { value: 'text', label: 'Texte', icon: 'pi pi-align-left' },
  { value: 'link', label: 'Lien', icon: 'pi pi-link' },
  { value: 'image', label: 'Image', icon: 'pi pi-image' }
]

// Outils Markdown
const markdownTools = [
  { name: 'bold', icon: 'pi pi-bold', syntax: '**', title: 'Gras' },
  { name: 'italic', icon: 'pi pi-italic', syntax: '*', title: 'Italique' },
  { name: 'link', icon: 'pi pi-link', syntax: '[texte](url)', title: 'Lien' },
  { name: 'code', icon: 'pi pi-code', syntax: '`', title: 'Code' },
  { name: 'quote', icon: 'pi pi-quote-left', syntax: '> ', title: 'Citation' },
  { name: 'list', icon: 'pi pi-list', syntax: '- ', title: 'Liste' }
]

// Computed
const renderedContent = computed(() => {
  // Simple rendu Markdown basique (tu peux utiliser une lib comme marked.js)
  return content.value
    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
    .replace(/\*(.*?)\*/g, '<em>$1</em>')
    .replace(/`(.*?)`/g, '<code>$1</code>')
    .replace(/^> (.+)/gm, '<blockquote>$1</blockquote>')
    .replace(/\n/g, '<br>')
})

const isFormValid = computed(() => {
  if (!title.value.trim()) return false
  if (postType.value === 'link' && !linkUrl.value.trim()) return false
  if (postType.value === 'text' && !content.value.trim()) return false
  return errors.value.length === 0
})

const canSaveDraft = computed(() => {
  return title.value.trim() || content.value.trim()
})

// Méthodes
const setPostType = (type) => {
  postType.value = type
  validateForm()
}

const getContentPlaceholder = () => {
  switch (postType.value) {
    case 'link': return 'Ajoutez une description ou des commentaires sur ce lien...'
    case 'image': return 'Décrivez vos images, ajoutez du contexte...'
    default: return 'Rédigez votre post en Markdown...\n\n**Gras**, *italique*, `code`, [lien](url)'
  }
}

const insertMarkdown = (tool) => {
  const textarea = $refs.contentTextarea
  if (!textarea) return

  const start = textarea.selectionStart
  const end = textarea.selectionEnd
  const selectedText = content.value.substring(start, end)

  let newText = ''
  switch (tool.name) {
    case 'bold':
    case 'italic':
    case 'code':
      newText = `${tool.syntax}${selectedText || 'texte'}${tool.syntax}`
      break
    case 'link':
      newText = `[${selectedText || 'texte'}](url)`
      break
    case 'quote':
    case 'list':
      newText = tool.syntax + (selectedText || 'texte')
      break
  }

  content.value = content.value.substring(0, start) + newText + content.value.substring(end)
  
  nextTick(() => {
    textarea.focus()
    textarea.setSelectionRange(start + newText.length, start + newText.length)
  })
}

const handleImageDrop = (e) => {
  isDragOver.value = false
  const files = Array.from(e.dataTransfer.files).filter(file => file.type.startsWith('image/'))
  processImageFiles(files)
}

const handleImageSelect = (e) => {
  const files = Array.from(e.target.files)
  processImageFiles(files)
}

const processImageFiles = (files) => {
  files.forEach(file => {
    if (file.size > 5 * 1024 * 1024) {
      errors.value.push(`${file.name} est trop volumineux (max 5MB)`)
      return
    }

    const reader = new FileReader()
    reader.onload = (e) => {
      imageFiles.value.push({
        file,
        name: file.name,
        preview: e.target.result
      })
    }
    reader.readAsDataURL(file)
  })
}

const removeImage = (index) => {
  imageFiles.value.splice(index, 1)
}

const handleFileSelect = (e) => {
  const files = Array.from(e.target.files)
  files.forEach(file => {
    if (file.size > 10 * 1024 * 1024) {
      errors.value.push(`${file.name} est trop volumineux (max 10MB)`)
      return
    }
    attachmentFiles.value.push(file)
  })
}

const removeAttachment = (index) => {
  attachmentFiles.value.splice(index, 1)
}

const addTag = () => {
  const tag = newTag.value.trim().toLowerCase()
  if (tag && !tags.value.includes(tag) && tags.value.length < 5) {
    tags.value.push(tag)
    newTag.value = ''
  }
}

const removeTag = (tag) => {
  const index = tags.value.indexOf(tag)
  if (index > -1) tags.value.splice(index, 1)
}

const fetchLinkPreview = async () => {
  if (!linkUrl.value) return
  
  try {
    // Tu peux implémenter un service de prévisualisation de liens
    // Pour l'instant, on simule
    linkPreview.value = {
      title: 'Titre du lien',
      description: 'Description du contenu...',
      url: linkUrl.value,
      image: null
    }
  } catch (error) {
    console.error('Erreur récupération preview:', error)
  }
}

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i]
}

const validateForm = () => {
  errors.value = []
  
  if (title.value.length > 150) {
    errors.value.push('Le titre ne peut pas dépasser 150 caractères')
  }
  
  if (postType.value === 'link' && linkUrl.value && !isValidUrl(linkUrl.value)) {
    errors.value.push('URL invalide')
  }
}

const isValidUrl = (string) => {
  try {
    new URL(string)
    return true
  } catch {
    return false
  }
}

const resetForm = () => {
  title.value = ''
  content.value = ''
  linkUrl.value = ''
  linkPreview.value = null
  tags.value = []
  newTag.value = ''
  imageFiles.value = []
  attachmentFiles.value = []
  errors.value = []
  postType.value = 'text'
}

const saveDraft = () => {
  // Implémenter la sauvegarde en brouillon
  console.log('Sauvegarde brouillon...')
}

const submitPost = async () => {
  if (!isFormValid.value) return

  loading.value = true
  errors.value = []

  try {
    const formData = new FormData()
    
    // Données de base
    formData.append('title', title.value)
    formData.append('content', content.value)
    formData.append('forumSlug', props.forumSlug)
    formData.append('postType', postType.value)
    
    if (linkUrl.value) {
      formData.append('linkUrl', linkUrl.value)
    }
    
    if (tags.value.length) {
      formData.append('tags', JSON.stringify(tags.value))
    }

    // Images
    imageFiles.value.forEach((imageFile, index) => {
      formData.append(`image_${index}`, imageFile.file)
    })

    // Fichiers joints
    attachmentFiles.value.forEach((file, index) => {
      formData.append(`attachment_${index}`, file)
    })

    const response = await api.post(`/api/forums/${props.forumSlug}/posts`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })

    // Redirection vers le post créé
    router.push(`/posts/${response.data.postId}`)
    
  } catch (error) {
    console.error('Erreur création post:', error)
    errors.value.push(error.response?.data?.error || 'Erreur lors de la création du post')
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.reddit-post-form {
  background: white;
  border-radius: var(--border-radius-large);
  box-shadow: var(--shadow-medium);
  overflow: hidden;
}

.form-header {
  padding: 1.5rem;
  background: var(--surface-100);
  border-bottom: 1px solid var(--surface-200);
}

.form-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0 0 1rem 0;
}

.post-type-tabs {
  display: flex;
  gap: 0.5rem;
}

.post-type-tab {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  background: white;
  border: 2px solid var(--surface-300);
  border-radius: var(--border-radius);
  cursor: pointer;
  transition: all var(--transition-fast);
  font-weight: 500;
  color: var(--text-secondary);
}

.post-type-tab:hover {
  border-color: var(--primary-light);
  color: var(--text-primary);
}

.post-type-tab.active {
  background: var(--primary);
  border-color: var(--primary);
  color: white;
}

.form-content {
  padding: 2rem;
}

.form-group {
  margin-bottom: 2rem;
}

.form-label {
  display: block;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 0.5rem;
}

.form-input {
  width: 100%;
  padding: 0.875rem;
  border: 2px solid var(--surface-300);
  border-radius: var(--border-radius);
  font-size: 1rem;
  transition: border-color var(--transition-fast);
}

.form-input:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(38, 166, 154, 0.1);
}

.character-count {
  font-size: 0.75rem;
  color: var(--text-secondary);
  text-align: right;
  margin-top: 0.25rem;
}

.image-upload-zone {
  border: 2px dashed var(--surface-300);
  border-radius: var(--border-radius);
  padding: 2rem;
  text-align: center;
  cursor: pointer;
  transition: all var(--transition-fast);
}

.image-upload-zone:hover,
.image-upload-zone.drag-over {
  border-color: var(--primary);
  background: rgba(38, 166, 154, 0.05);
}

.upload-placeholder {
  color: var(--text-secondary);
}

.upload-icon {
  font-size: 3rem;
  color: var(--primary);
  margin-bottom: 1rem;
}

.upload-hint {
  font-size: 0.875rem;
  margin-top: 0.5rem;
}

.image-preview-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: 1rem;
}

.image-preview {
  position: relative;
  aspect-ratio: 1;
  border-radius: var(--border-radius);
  overflow: hidden;
  background: var(--surface-200);
}

.image-preview img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.image-remove {
  position: absolute;
  top: 0.5rem;
  right: 0.5rem;
  width: 24px;
  height: 24px;
  background: rgba(0, 0, 0, 0.7);
  color: white;
  border: none;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.image-info {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  background: rgba(0, 0, 0, 0.7);
  color: white;
  padding: 0.25rem;
  font-size: 0.75rem;
  text-overflow: ellipsis;
  overflow: hidden;
  white-space: nowrap;
}

.add-more-images {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  border: 2px dashed var(--surface-300);
  border-radius: var(--border-radius);
  color: var(--text-secondary);
  cursor: pointer;
  transition: all var(--transition-fast);
}

.add-more-images:hover {
  border-color: var(--primary);
  color: var(--primary);
}

.markdown-toolbar {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.5rem;
  background: var(--surface-100);
  border: 2px solid var(--surface-300);
  border-bottom: none;
  border-radius: var(--border-radius) var(--border-radius) 0 0;
}

.markdown-tool {
  width: 32px;
  height: 32px;
  background: transparent;
  border: none;
  border-radius: var(--border-radius-small);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text-secondary);
  transition: all var(--transition-fast);
}

.markdown-tool:hover {
  background: var(--surface-200);
  color: var(--text-primary);
}

.toolbar-separator {
  width: 1px;
  height: 20px;
  background: var(--surface-300);
  margin: 0 0.5rem;
}

.preview-toggle {
  padding: 0.25rem 0.75rem;
  background: transparent;
  border: 1px solid var(--surface-300);
  border-radius: var(--border-radius-small);
  cursor: pointer;
  font-size: 0.875rem;
  color: var(--text-secondary);
  transition: all var(--transition-fast);
}

.preview-toggle:hover,
.preview-toggle.active {
  background: var(--primary);
  border-color: var(--primary);
  color: white;
}

.content-editor {
  position: relative;
}

.form-textarea {
  width: 100%;
  padding: 1rem;
  border: 2px solid var(--surface-300);
  border-top: none;
  border-radius: 0 0 var(--border-radius) var(--border-radius);
  font-size: 1rem;
  font-family: 'Fira Code', monospace;
  resize: vertical;
  min-height: 200px;
}

.form-textarea:focus {
  outline: none;
  border-color: var(--primary);
}

.markdown-preview {
  padding: 1rem;
  border: 2px solid var(--surface-300);
  border-top: none;
  border-radius: 0 0 var(--border-radius) var(--border-radius);
  min-height: 200px;
  background: white;
}

.editor-help {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.75rem;
  color: var(--text-secondary);
  margin-top: 0.5rem;
}

.tags-input-container {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  padding: 0.5rem;
  border: 2px solid var(--surface-300);
  border-radius: var(--border-radius);
  min-height: 50px;
}

.tags-input-container:focus-within {
  border-color: var(--primary);
}

.tags-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.tag {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.25rem 0.5rem;
  background: var(--primary-light);
  color: white;
  border-radius: 12px;
  font-size: 0.875rem;
}

.tag-remove {
  background: none;
  border: none;
  color: white;
  cursor: pointer;
  padding: 0;
  width: 16px;
  height: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.tag-input {
  flex: 1;
  border: none;
  outline: none;
  min-width: 120px;
  font-size: 1rem;
}

.tags-help {
  font-size: 0.75rem;
  color: var(--text-secondary);
  margin-top: 0.25rem;
}

.file-upload-zone {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 1rem;
  border: 2px dashed var(--surface-300);
  border-radius: var(--border-radius);
  cursor: pointer;
  color: var(--text-secondary);
  transition: all var(--transition-fast);
}

.file-upload-zone:hover {
  border-color: var(--primary);
  color: var(--primary);
}

.attachment-list {
  margin-top: 1rem;
}

.attachment-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  background: var(--surface-100);
  border-radius: var(--border-radius);
  margin-bottom: 0.5rem;
}

.attachment-icon {
  color: var(--text-secondary);
}

.attachment-name {
  flex: 1;
  font-weight: 500;
}

.attachment-size {
  font-size: 0.875rem;
  color: var(--text-secondary);
}

.attachment-remove {
  background: none;
  border: none;
  color: var(--text-secondary);
  cursor: pointer;
  padding: 0.25rem;
}

.link-preview {
  position: relative;
  margin-top: 1rem;
  border: 1px solid var(--surface-300);
  border-radius: var(--border-radius);
  overflow: hidden;
}

.link-preview-content {
  display: flex;
  gap: 1rem;
  padding: 1rem;
}

.link-preview-image {
  width: 80px;
  height: 80px;
  object-fit: cover;
  border-radius: var(--border-radius-small);
}

.link-preview-text h4 {
  margin: 0 0 0.5rem 0;
  font-size: 1rem;
  font-weight: 600;
}

.link-preview-text p {
  margin: 0 0 0.5rem 0;
  font-size: 0.875rem;
  color: var(--text-secondary);
  line-height: 1.4;
}

.link-preview-url {
  font-size: 0.75rem;
  color: var(--primary);
}

.link-preview-remove {
  position: absolute;
  top: 0.5rem;
  right: 0.5rem;
  background: rgba(0, 0, 0, 0.7);
  color: white;
  border: none;
  border-radius: 50%;
  width: 24px;
  height: 24px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.form-actions {
  margin-top: 2rem;
  padding-top: 2rem;
  border-top: 1px solid var(--surface-200);
}

.form-status {
  margin-bottom: 1rem;
}

.error-messages {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.error-message {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem;
  background: rgba(239, 68, 68, 0.1);
  color: #dc2626;
  border-radius: var(--border-radius);
  font-size: 0.875rem;
}

.action-buttons {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
}

.btn-secondary,
.btn-outline,
.btn-primary {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.875rem 1.5rem;
  border-radius: var(--border-radius);
  font-weight: 500;
  cursor: pointer;
  transition: all var(--transition-fast);
  border: 2px solid;
  text-decoration: none;
}

.btn-secondary {
  background: var(--surface-200);
  border-color: var(--surface-300);
  color: var(--text-secondary);
}

.btn-secondary:hover:not(:disabled) {
  background: var(--surface-300);
  border-color: var(--surface-400);
  color: var(--text-primary);
}

.btn-outline {
  background: transparent;
  border-color: var(--primary);
  color: var(--primary);
}

.btn-outline:hover:not(:disabled) {
  background: rgba(38, 166, 154, 0.1);
}

.btn-primary {
  background: var(--primary);
  border-color: var(--primary);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: var(--primary-dark);
  border-color: var(--primary-dark);
  transform: translateY(-1px);
  box-shadow: var(--shadow-medium);
}

.btn-secondary:disabled,
.btn-outline:disabled,
.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.pi-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 768px) {
  .form-content {
    padding: 1rem;
  }
  
  .post-type-tabs {
    flex-direction: column;
  }
  
  .action-buttons {
    flex-direction: column-reverse;
  }
  
  .action-buttons > * {
    flex: 1;
    justify-content: center;
  }
  
  .image-preview-grid {
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
  }
  
  .attachment-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
}

@media (max-width: 480px) {
  .form-header {
    padding: 1rem;
  }
  
  .form-title {
    font-size: 1.25rem;
  }
  
  .markdown-toolbar {
    flex-wrap: wrap;
  }
  
  .link-preview-content {
    flex-direction: column;
  }
  
  .link-preview-image {
    width: 100%;
    height: 120px;
  }
}
</style>
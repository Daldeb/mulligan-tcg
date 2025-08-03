<template>
  <div class="simple-post-form">
    <!-- Header -->
    <div class="form-header">
      <h2 class="form-title">Créer un nouveau post</h2>
      <p class="form-subtitle">Partagez votre contenu avec la communauté</p>
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

      <!-- Contenu -->
      <div class="form-group">
        <label class="form-label">Contenu</label>
        <textarea
          v-model="content"
          class="form-textarea"
          placeholder="Décrivez votre post, ajoutez des détails, posez une question..."
          rows="6"
          @input="validateForm"
        ></textarea>
        <div class="editor-help">
          <span>Supporte le Markdown basique</span>
          <span class="character-count">{{ content.length }} caractères</span>
        </div>
      </div>

      <!-- Images -->
      <div class="form-group">
        <label class="form-label">Images (optionnelles)</label>
        <div 
          class="image-upload-zone"
          :class="{ 'drag-over': isDragOver }"
          @dragover.prevent="isDragOver = true"
          @dragleave.prevent="isDragOver = false"
          @drop.prevent="handleImageDrop"
          @click="$refs.imageInput.click()"
        >
          <div v-if="!imageFiles.length" class="upload-placeholder">
            <i class="pi pi-image upload-icon"></i>
            <p>Ajoutez des images à votre post</p>
            <p class="upload-hint">PNG, JPG, WebP, GIF - Max 5MB par image</p>
          </div>
          
          <!-- Aperçu des images -->
          <div v-else class="image-preview-grid">
            <div v-for="(file, index) in imageFiles" :key="index" class="image-preview">
              <img :src="file.preview" :alt="file.name" />
              <button @click.stop="removeImage(index)" class="image-remove">
                <i class="pi pi-times"></i>
              </button>
              <div class="image-order">{{ index + 1 }}</div>
            </div>
            <div v-if="imageFiles.length < 10" class="add-more-images" @click.stop="$refs.imageInput.click()">
              <i class="pi pi-plus"></i>
              <span>Ajouter</span>
            </div>
          </div>
        </div>
        <input
          ref="imageInput"
          type="file"
          multiple
          accept="image/png,image/jpeg,image/jpg,image/webp,image/gif"
          @change="handleImageSelect"
          style="display: none"
        />
        <div v-if="imageFiles.length" class="image-help">
          Les images seront affichées dans un carrousel sous votre post
        </div>
      </div>

      <!-- Liens -->
      <div class="form-group">
        <label class="form-label">Lien (optionnel)</label>
        <input
          v-model="linkUrl"
          type="url"
          class="form-input"
          placeholder="https://example.com - Ajoutez un lien vers une page web"
          @blur="validateUrl"
        />
        <div v-if="linkUrl && isValidUrl(linkUrl)" class="link-preview">
          <i class="pi pi-link"></i>
          <span>{{ linkUrl }}</span>
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
            @keydown.space.prevent="addTag"
          />
        </div>
        <div class="tags-help">Appuyez sur Entrée, Espace ou Virgule pour ajouter un tag (max 5)</div>
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
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'

const props = defineProps({
  forumSlug: String
})

const router = useRouter()

// État du formulaire
const title = ref('')
const content = ref('')
const linkUrl = ref('')
const tags = ref([])
const newTag = ref('')
const imageFiles = ref([])
const isDragOver = ref(false)
const loading = ref(false)
const errors = ref([])

// Computed
const isFormValid = computed(() => {
  if (!title.value.trim()) return false
  if (errors.value.length > 0) return false
  return true
})

// Méthodes de validation
const validateForm = () => {
  errors.value = []
  
  if (title.value.length > 150) {
    errors.value.push('Le titre ne peut pas dépasser 150 caractères')
  }
}

const validateUrl = () => {
  if (linkUrl.value && !isValidUrl(linkUrl.value)) {
    errors.value.push('URL invalide')
  } else {
    // Supprimer l'erreur d'URL si elle était présente
    errors.value = errors.value.filter(error => error !== 'URL invalide')
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

// Gestion des images
const handleImageDrop = (e) => {
  isDragOver.value = false
  const files = Array.from(e.dataTransfer.files).filter(file => 
    ['image/png', 'image/jpeg', 'image/jpg', 'image/webp', 'image/gif'].includes(file.type)
  )
  processImageFiles(files)
}

const handleImageSelect = (e) => {
  const files = Array.from(e.target.files)
  processImageFiles(files)
  // Reset l'input pour permettre de sélectionner le même fichier
  e.target.value = ''
}

const processImageFiles = (files) => {
  files.forEach(file => {
    // Vérifier la limite de taille (5MB)
    if (file.size > 5 * 1024 * 1024) {
      errors.value.push(`${file.name} est trop volumineux (max 5MB)`)
      return
    }

    // Vérifier la limite de nombre d'images (10 max)
    if (imageFiles.value.length >= 10) {
      errors.value.push('Maximum 10 images par post')
      return
    }

    // Vérifier le type de fichier
    if (!['image/png', 'image/jpeg', 'image/jpg', 'image/webp', 'image/gif'].includes(file.type)) {
      errors.value.push(`${file.name}: Format non supporté (PNG, JPG, WebP, GIF uniquement)`)
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

// Gestion des tags
const addTag = () => {
  const tag = newTag.value.trim().toLowerCase()
  if (tag && !tags.value.includes(tag) && tags.value.length < 5) {
    tags.value.push(tag)
    newTag.value = ''
  } else if (tags.value.length >= 5) {
    errors.value.push('Maximum 5 tags par post')
  }
}

const removeTag = (tag) => {
  const index = tags.value.indexOf(tag)
  if (index > -1) tags.value.splice(index, 1)
}

// Actions du formulaire
const resetForm = () => {
  title.value = ''
  content.value = ''
  linkUrl.value = ''
  tags.value = []
  newTag.value = ''
  imageFiles.value = []
  errors.value = []
}

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i]
}

const submitPost = async () => {
  if (!isFormValid.value) return

  loading.value = true
  errors.value = []

  try {
    const formData = new FormData()
    
    // Données de base
    formData.append('title', title.value.trim())
    formData.append('content', content.value.trim())
    formData.append('forumSlug', props.forumSlug)
    formData.append('postType', imageFiles.value.length > 0 ? 'image' : 'text')
    
    if (linkUrl.value && isValidUrl(linkUrl.value)) {
      formData.append('linkUrl', linkUrl.value.trim())
    }
    
    if (tags.value.length) {
      formData.append('tags', JSON.stringify(tags.value))
    }

    // Images
    imageFiles.value.forEach((imageFile, index) => {
      formData.append(`image_${index}`, imageFile.file)
    })

    console.log('Données envoyées:', {
      title: title.value,
      content: content.value,
      forumSlug: props.forumSlug,
      linkUrl: linkUrl.value,
      tags: tags.value,
      imagesCount: imageFiles.value.length
    })

    const response = await api.post(`/api/forums/${props.forumSlug}/posts`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })

    // Redirection vers le post créé
    router.push(`/forums/${props.forumSlug}/posts/${response.data.postId}`)
    
  } catch (error) {
    console.error('Erreur création post:', error)
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      errors.value = [error.response?.data?.error || 'Erreur lors de la création du post']
    }
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.simple-post-form {
  background: white;
  border-radius: var(--border-radius-large);
  box-shadow: var(--shadow-medium);
  overflow: hidden;
}

.form-header {
  padding: 2rem;
  background: var(--surface-100);
  border-bottom: 1px solid var(--surface-200);
  text-align: center;
}

.form-title {
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0 0 0.5rem 0;
}

.form-subtitle {
  color: var(--text-secondary);
  margin: 0;
  font-size: 1rem;
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
  margin-bottom: 0.75rem;
  font-size: 1rem;
}

.form-input {
  width: 100%;
  padding: 1rem;
  border: 2px solid var(--surface-300);
  border-radius: var(--border-radius);
  font-size: 1rem;
  transition: border-color var(--transition-fast);
  font-family: inherit;
}

.form-input:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(38, 166, 154, 0.1);
}

.form-textarea {
  width: 100%;
  padding: 1rem;
  border: 2px solid var(--surface-300);
  border-radius: var(--border-radius);
  font-size: 1rem;
  font-family: inherit;
  resize: vertical;
  transition: border-color var(--transition-fast);
}

.form-textarea:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(38, 166, 154, 0.1);
}

.character-count {
  font-size: 0.875rem;
  color: var(--text-secondary);
  text-align: right;
  margin-top: 0.5rem;
}

.editor-help {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.875rem;
  color: var(--text-secondary);
  margin-top: 0.5rem;
}

/* Upload d'images */
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
  opacity: 0.8;
}

.image-preview-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
  gap: 1rem;
  max-width: 100%;
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
  background: rgba(0, 0, 0, 0.8);
  color: white;
  border: none;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
}

.image-order {
  position: absolute;
  top: 0.5rem;
  left: 0.5rem;
  width: 20px;
  height: 20px;
  background: var(--primary);
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 600;
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
  aspect-ratio: 1;
}

.add-more-images:hover {
  border-color: var(--primary);
  color: var(--primary);
}

.image-help {
  font-size: 0.875rem;
  color: var(--text-secondary);
  margin-top: 0.5rem;
}

/* Aperçu du lien */
.link-preview {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: 0.5rem;
  padding: 0.75rem;
  background: var(--surface-100);
  border-radius: var(--border-radius);
  font-size: 0.875rem;
  color: var(--primary);
}

/* Tags */
.tags-input-container {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  padding: 0.75rem;
  border: 2px solid var(--surface-300);
  border-radius: var(--border-radius);
  min-height: 50px;
  transition: border-color var(--transition-fast);
}

.tags-input-container:focus-within {
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(38, 166, 154, 0.1);
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
  padding: 0.375rem 0.75rem;
  background: var(--primary);
  color: white;
  border-radius: 20px;
  font-size: 0.875rem;
  font-weight: 500;
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
  border-radius: 50%;
  transition: background var(--transition-fast);
}

.tag-remove:hover {
  background: rgba(255, 255, 255, 0.2);
}

.tag-input {
  flex: 1;
  border: none;
  outline: none;
  min-width: 150px;
  font-size: 1rem;
  font-family: inherit;
}

.tags-help {
  font-size: 0.875rem;
  color: var(--text-secondary);
  margin-top: 0.5rem;
}

/* Actions */
.form-actions {
  margin-top: 2rem;
  padding-top: 2rem;
  border-top: 1px solid var(--surface-200);
}

.error-messages {
  margin-bottom: 1rem;
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
  margin-bottom: 0.5rem;
}

.action-buttons {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
}

.btn-secondary,
.btn-primary {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 1rem 2rem;
  border-radius: var(--border-radius);
  font-weight: 600;
  cursor: pointer;
  transition: all var(--transition-fast);
  border: 2px solid;
  text-decoration: none;
  font-size: 1rem;
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

.btn-primary {
  background: var(--primary);
  border-color: var(--primary);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: var(--primary-dark);
  border-color: var(--primary-dark);
  transform: translateY(-2px);
  box-shadow: var(--shadow-large);
}

.btn-secondary:disabled,
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
    padding: 1.5rem;
  }
  
  .form-header {
    padding: 1.5rem;
  }
  
  .action-buttons {
    flex-direction: column;
  }
  
  .action-buttons > * {
    justify-content: center;
  }
  
  .image-preview-grid {
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
  }
}

@media (max-width: 480px) {
  .form-title {
    font-size: 1.5rem;
  }
  
  .tags-input-container {
    padding: 0.5rem;
  }
  
  .tag-input {
    min-width: 100px;
  }
}
</style>
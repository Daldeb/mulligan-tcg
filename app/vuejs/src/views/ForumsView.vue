<template>
  <div class="forums-page">
    <!-- Header Section avec gradient emerald -->
    <div class="forums-header emerald-gradient">
      <div class="container mx-auto px-4 py-4">
        <div class="text-center text-white">
          <h1 class="text-xl font-bold mb-2 slide-in-down">Forums de Discussion</h1>
          <p class="text-sm opacity-90 max-w-lg mx-auto">
            Rejoignez notre communauté de joueurs TCG
          </p>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8" style="margin-top: 3rem;">
      <!-- Loading State avec skeleton moderne -->
      <div v-if="loading" class="space-y-6">
        <div v-for="i in 3" :key="i" class="forum-card-skeleton loading-skeleton"></div>
      </div>

      <!-- Forums Grid -->
      <div v-else class="forums-grid">
        <div 
          v-for="(forum, index) in forums" 
          :key="forum.slug" 
          :class="['forum-card', 'gaming-card', getForumClass(forum.slug), 'slide-in-up']"
          :style="{ 
            animationDelay: `${index * 0.1}s`,
            backgroundImage: getForumImageUrl(forum.slug) ? 
              `linear-gradient(to bottom, rgba(255,255,255,0.0) 0%, rgba(255,255,255,0.3) 30%, rgba(255,255,255,0.9) 45%, rgba(255,255,255,1) 55%), url(${getForumImageUrl(forum.slug)})` : 
              'none',
            backgroundSize: 'cover',
            backgroundPosition: 'center top'
          }"
        >
          <!-- Forum Header -->
          <div class="forum-header">
            <div class="forum-info">
              <div class="forum-icon">
                <i class="pi pi-comments text-2xl"></i>
              </div>
              <div class="forum-details">
                <h2 class="forum-title">{{ forum.name }}</h2>
                <p class="forum-description">{{ forum.description }}</p>
                <div class="forum-stats">
                  <span class="stat-item">
                    <i class="pi pi-file text-sm"></i>
                    {{ forum.posts?.length || 0 }} posts
                  </span>
                  <span class="stat-item">
                    <i class="pi pi-users text-sm"></i>
                    Communauté active
                  </span>
                </div>
              </div>
            </div>
            <RouterLink 
              :to="`/forums/${forum.slug}`" 
              class="p-button emerald-button primary forum-btn"
            >
              <i class="pi pi-arrow-right mr-2"></i>
              Explorer
            </RouterLink>
          </div>

          <!-- Recent Posts Preview -->
          <div v-if="forum.posts && forum.posts.length" class="recent-posts">
            <div class="section-divider">
              <span class="section-title">Publications récentes</span>
            </div>
            
            <div class="posts-list">
              <div 
                v-for="post in forum.posts.slice(0, 3)" 
                :key="post.id" 
                class="post-preview"
              >
                <div class="post-content">
                  <RouterLink 
                    :to="`/forums/${forum.slug}/posts/${post.id}`" 
                    class="post-title hover-lift"
                  >
                    {{ post.title }}
                  </RouterLink>
                  <div class="post-meta">
                    <div class="author-info">
                      <div class="author-avatar">
                        {{ post.author?.charAt(0).toUpperCase() }}
                      </div>
                      <span class="author-name">{{ post.author }}</span>
                    </div>
                    <span class="post-date">{{ formatDate(post.createdAt) }}</span>
                  </div>
                </div>
                <div class="post-badge">
                  <i class="pi pi-clock text-xs"></i>
                </div>
              </div>
            </div>
          </div>

          <!-- Empty State pour forums sans posts -->
          <div v-else class="empty-forum">
            <div class="empty-icon">
              <i class="pi pi-inbox text-3xl opacity-50"></i>
            </div>
            <p class="empty-text">Aucune publication pour le moment</p>
            <RouterLink 
              :to="`/forums/${forum.slug}`" 
              class="p-button p-button-text emerald-text-btn"
            >
              Être le premier à poster
            </RouterLink>
          </div>
        </div>
      </div>

      <!-- Call to Action Section -->
      <div class="cta-section glass-effect">
        <div class="cta-content">
          <h3 class="cta-title">Rejoignez la conversation</h3>
          <p class="cta-description">
            Partagez vos stratégies, découvrez de nouveaux decks et connectez-vous avec la communauté TCG
          </p>
          <button class="p-button emerald-btn cta-button">
            <i class="pi pi-plus mr-2"></i>
            Créer un nouveau post
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/services/api';

// Imports des images d'arrière-plan
import hearthstoneImg from '@/assets/images/forums/hearthstone-bg.jpg'
import magicImg from '@/assets/images/forums/magic-bg.jpg'
import pokemonImg from '@/assets/images/forums/pokemon-bg.jpg'

const forums = ref([]);
const loading = ref(true);
const router = useRouter();

const getForumClass = (forumSlug) => {
  const slug = forumSlug.toLowerCase();
  if (slug.includes('hearthstone')) return 'hearthstone';
  if (slug.includes('magic')) return 'magic';
  if (slug.includes('pokemon')) return 'pokemon';
  return '';
};

const getForumImageUrl = (forumSlug) => {
  const slug = forumSlug.toLowerCase();
  if (slug.includes('hearthstone')) return hearthstoneImg;
  if (slug.includes('magic')) return magicImg;
  if (slug.includes('pokemon')) return pokemonImg;
  return null;
};

const formatDate = (dateString) => {
  const date = new Date(dateString);
  const now = new Date();
  const diffTime = Math.abs(now - date);
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
  
  if (diffDays === 1) return 'Hier';
  if (diffDays < 7) return `Il y a ${diffDays} jours`;
  if (diffDays < 30) return `Il y a ${Math.ceil(diffDays / 7)} semaines`;
  
  return date.toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: date.getFullYear() !== now.getFullYear() ? 'numeric' : undefined
  });
};

onMounted(async () => {
  try {
    const { data: forumsList } = await api.get('/api/forums');
    const forumsWithPosts = await Promise.all(
      forumsList.map(async (forum) => {
        try {
          const res = await api.get(`/api/forums/${forum.slug}/posts`);
          return { ...forum, posts: res.data.posts || [] };
        } catch (err) {
          console.warn(`Erreur chargement posts pour ${forum.slug}:`, err);
          return { ...forum, posts: [] };
        }
      })
    );
    forums.value = forumsWithPosts;
  } catch (err) {
    console.error('Erreur chargement forums:', err);
  } finally {
    loading.value = false;
  }
});
</script>

<style scoped>
.forums-page {
  min-height: calc(100vh - var(--header-height));
  background: var(--surface);
}

.forums-header {
  margin-top: calc(-1 * var(--header-height));
  padding-top: calc(var(--header-height) - 1rem);
  position: relative;
  overflow: hidden;
}

.forums-header::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
  opacity: 0.3;
}

.forums-grid {
  display: grid;
  gap: 2rem;
  grid-template-columns: repeat(auto-fit, minmax(600px, 1fr));
}

.forum-card {
  background: white;
  border-radius: var(--border-radius-large);
  padding: 2rem;
  transition: all var(--transition-medium);
  border: 1px solid var(--surface-200);
  position: relative;
  overflow: hidden; /* Important pour l'image de fond */
}

.forum-card:hover {
  transform: translateY(-8px) !important;
  box-shadow: var(--shadow-large) !important;
}

.forum-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1.5rem;
  position: relative; /* Pour s'assurer que le contenu est au-dessus de l'image */
  z-index: 2;
}

.forum-info {
  display: flex;
  gap: 1rem;
  flex: 1;
}

.forum-icon {
  width: 48px;
  height: 48px;
  background: linear-gradient(135deg, var(--primary-light), var(--primary));
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  flex-shrink: 0;
  box-shadow: var(--shadow-medium); /* Ajout d'ombre pour faire ressortir l'icône */
}

.forum-details {
  flex: 1;
}

.forum-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: white;
  margin-bottom: 0.5rem;
  line-height: 1.2;
  text-shadow: 2px 2px 4px rgba(0,0,0,0.8), 0 0 8px rgba(0,0,0,0.3);
}

.forum-description {
  color: rgba(255,255,255,0.95);
  margin-bottom: 1rem;
  line-height: 1.5;
  text-shadow: 1px 1px 3px rgba(0,0,0,0.7), 0 0 6px rgba(0,0,0,0.2);
  font-weight: 500;
}

.forum-stats {
  display: flex;
  gap: 1.5rem;
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

.forum-btn {
  white-space: nowrap;
  height: fit-content;
  text-decoration: none !important;
  box-shadow: var(--shadow-medium); /* Ajout d'ombre pour faire ressortir le bouton */
}

.forum-btn:hover {
  text-decoration: none !important;
}

.recent-posts {
  position: relative; /* Pour s'assurer que le contenu est au-dessus de l'image */
  z-index: 2;
}

.section-divider {
  display: flex;
  align-items: center;
  margin-bottom: 1rem;
}

.section-divider::before {
  content: '';
  flex: 1;
  height: 1px;
  background: linear-gradient(90deg, transparent, var(--surface-300), transparent);
}

.section-title {
  padding: 0 1rem;
  color: var(--text-secondary);
  font-size: 0.875rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  background: rgba(255,255,255,0.9); /* Fond semi-transparent pour améliorer la lisibilité */
  border-radius: 12px;
}

.posts-list {
  space-y: 1rem;
}

.post-preview {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background: rgba(var(--surface-100-rgb), 0.95); /* Fond semi-transparent */
  border-radius: var(--border-radius);
  transition: all var(--transition-fast);
  backdrop-filter: blur(5px); /* Effet de flou pour un rendu moderne */
}

.post-preview:hover {
  background: rgba(var(--surface-200-rgb), 0.95);
  transform: translateX(4px);
}

.post-content {
  flex: 1;
}

.post-title {
  color: var(--text-primary);
  font-weight: 600;
  text-decoration: none;
  display: block;
  margin-bottom: 0.5rem;
  transition: color var(--transition-fast);
}

.post-title:hover {
  color: var(--primary);
}

.post-meta {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.author-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.author-avatar {
  width: 24px;
  height: 24px;
  background: var(--primary-light);
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 600;
}

.author-name {
  font-weight: 600;
  color: var(--text-primary);
  font-size: 0.875rem;
}

.post-date {
  color: var(--text-secondary);
  font-size: 0.75rem;
}

.post-badge {
  color: var(--text-secondary);
  opacity: 0.6;
}

.empty-forum {
  text-align: center;
  padding: 2rem;
  color: var(--text-secondary);
  position: relative;
  z-index: 2;
  background: rgba(255,255,255,0.9);
  border-radius: var(--border-radius);
}

.empty-icon {
  margin-bottom: 1rem;
}

.empty-text {
  margin-bottom: 1rem;
  font-style: italic;
}

.cta-section {
  margin-top: 4rem;
  padding: 3rem;
  border-radius: var(--border-radius-large);
  text-align: center;
}

.cta-title {
  font-size: 2rem;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 1rem;
}

.cta-description {
  color: var(--text-secondary);
  font-size: 1.125rem;
  margin-bottom: 2rem;
  max-width: 600px;
  margin-left: auto;
  margin-right: auto;
}

.cta-button {
  font-size: 1.125rem;
  padding: 0.875rem 2rem;
}

/* Loading Skeleton */
.forum-card-skeleton {
  height: 200px;
  border-radius: var(--border-radius-large);
  margin-bottom: 2rem;
}

/* Responsive Design */
@media (max-width: 768px) {
  .forums-grid {
    grid-template-columns: 1fr;
    gap: 1.5rem;
  }
  
  .forum-card {
    padding: 1.5rem;
  }
  
  .forum-header {
    flex-direction: column;
    gap: 1rem;
  }
  
  .forum-info {
    flex-direction: column;
    gap: 1rem;
  }
  
  .forum-stats {
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .post-meta {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
  
  .cta-section {
    padding: 2rem 1rem;
  }
  
  .cta-title {
    font-size: 1.5rem;
  }
}

@media (max-width: 640px) {
  .forums-header h1 {
    font-size: 2.5rem;
  }
  
  .forums-header p {
    font-size: 1rem;
  }
  
  .forum-icon {
    width: 40px;
    height: 40px;
  }
  
  .forum-title {
    font-size: 1.25rem;
  }
}
</style>
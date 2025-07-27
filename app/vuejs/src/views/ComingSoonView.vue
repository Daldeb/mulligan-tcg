<template>
  <div class="coming-soon-view">
    <div class="container">
      <Card class="coming-soon-card fade-in-scale">
        <template #content>
          <div class="coming-soon-content">
            
            <!-- Animation Icon -->
            <div class="icon-section">
              <div class="animated-icon emerald-spinner">
                <i :class="sectionIcon" class="main-icon"></i>
              </div>
            </div>
            
            <!-- Content -->
            <div class="text-section">
              <h1 class="coming-soon-title slide-in-up">
                {{ sectionTitle }}
              </h1>
              <h2 class="coming-soon-subtitle slide-in-up" style="animation-delay: 0.2s">
                Bientôt disponible !
              </h2>
              <p class="coming-soon-description slide-in-up" style="animation-delay: 0.4s">
                {{ sectionDescription }}
              </p>
              
              <!-- Progress bar stylisée -->
              <div class="progress-section slide-in-up" style="animation-delay: 0.6s">
                <div class="progress-label">
                  <span>Progression du développement</span>
                  <span class="progress-percentage">{{ developmentProgress }}%</span>
                </div>
                <div class="progress-bar">
                  <div 
                    class="progress-fill emerald-gradient"
                    :style="{ width: developmentProgress + '%' }"
                  ></div>
                </div>
              </div>
            </div>
            
            <!-- Actions -->
            <div class="actions-section slide-in-up" style="animation-delay: 0.8s">
              <Button 
                label="Retour à l'accueil"
                icon="pi pi-home"
                class="p-button-lg mb-3"
                @click="goHome"
              />
              
              <Button 
                label="Être notifié"
                icon="pi pi-bell"
                class="p-button-outlined p-button-lg"
                @click="subscribeNotifications"
              />
            </div>
            
            <!-- Features preview -->
            <div class="features-preview slide-in-up" style="animation-delay: 1s">
              <h4 class="features-title">Fonctionnalités à venir :</h4>
              <div class="features-list">
                <div 
                  v-for="feature in upcomingFeatures" 
                  :key="feature.id"
                  class="feature-item hover-lift"
                >
                  <i :class="feature.icon" class="feature-icon"></i>
                  <span class="feature-name">{{ feature.name }}</span>
                </div>
              </div>
            </div>
            
          </div>
        </template>
      </Card>
      
      <!-- Suggestions de navigation -->
      <div class="navigation-suggestions">
        <h3 class="suggestions-title">En attendant, explorez :</h3>
        <div class="suggestions-grid">
          <Card 
            v-for="suggestion in navigationSuggestions"
            :key="suggestion.route"
            class="suggestion-card hover-lift"
            @click="navigateTo(suggestion.route)"
          >
            <template #content>
              <div class="suggestion-content">
                <i :class="suggestion.icon" class="suggestion-icon"></i>
                <h4 class="suggestion-title">{{ suggestion.title }}</h4>
                <p class="suggestion-description">{{ suggestion.description }}</p>
              </div>
            </template>
          </Card>
        </div>
      </div>
      
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'

const router = useRouter()
const route = useRoute()
const toast = useToast()

// Configuration par section
const sectionConfig = {
  '/discussions': {
    title: 'Discussions',
    icon: 'pi pi-comments',
    description: 'Un forum communautaire complet pour échanger avec les autres joueurs, partager vos stratégies et discuter des dernières actualités TCG.',
    progress: 25,
    features: [
      { id: 1, name: 'Forum par catégories', icon: 'pi pi-folder' },
      { id: 2, name: 'Système de votes', icon: 'pi pi-thumbs-up' },
      { id: 3, name: 'Messages privés', icon: 'pi pi-envelope' },
      { id: 4, name: 'Modération avancée', icon: 'pi pi-shield' }
    ]
  },
  '/decks': {
    title: 'Decks',
    icon: 'pi pi-clone',
    description: 'Un deck builder complet avec toutes les cartes Hearthstone, statistiques détaillées et possibilité de partager vos créations avec la communauté.',
    progress: 40,
    features: [
      { id: 1, name: 'Deck Builder', icon: 'pi pi-plus' },
      { id: 2, name: 'Statistiques de meta', icon: 'pi pi-chart-bar' },
      { id: 3, name: 'Import/Export', icon: 'pi pi-download' },
      { id: 4, name: 'Recommandations IA', icon: 'pi pi-brain' }
    ]
  },
  '/rankings': {
    title: 'Classements',
    icon: 'pi pi-trophy',
    description: 'Système de classements complet avec suivi des performances, historique des tournois et statistiques détaillées par joueur.',
    progress: 15,
    features: [
      { id: 1, name: 'Classements en temps réel', icon: 'pi pi-refresh' },
      { id: 2, name: 'Historique des matchs', icon: 'pi pi-history' },
      { id: 3, name: 'Tournois organisés', icon: 'pi pi-calendar' },
      { id: 4, name: 'Récompenses', icon: 'pi pi-gift' }
    ]
  },
  '/shops': {
    title: 'Boutiques',
    icon: 'pi pi-shopping-bag',
    description: 'Marketplace intégré pour acheter, vendre et échanger des cartes avec d\'autres joueurs en toute sécurité.',
    progress: 30,
    features: [
      { id: 1, name: 'Système d\'échange', icon: 'pi pi-arrows-h' },
      { id: 2, name: 'Évaluations vendeurs', icon: 'pi pi-star' },
      { id: 3, name: 'Paiement sécurisé', icon: 'pi pi-lock' },
      { id: 4, name: 'Suivi des prix', icon: 'pi pi-chart-line' }
    ]
  }
}

// Computed properties
const currentSection = computed(() => {
  return sectionConfig[route.path] || sectionConfig['/discussions']
})

const sectionTitle = computed(() => currentSection.value.title)
const sectionIcon = computed(() => currentSection.value.icon)
const sectionDescription = computed(() => currentSection.value.description)
const developmentProgress = computed(() => currentSection.value.progress)
const upcomingFeatures = computed(() => currentSection.value.features)

// Navigation suggestions (sections disponibles)
const navigationSuggestions = ref([
  {
    route: '/',
    title: 'Accueil',
    icon: 'pi pi-home',
    description: 'Retour à la page d\'accueil avec les dernières actualités'
  },
  {
    route: '/cards',
    title: 'Collection de cartes',
    icon: 'pi pi-images',
    description: 'Explorez toutes les cartes Hearthstone importées'
  },
  {
    route: '/tournaments',
    title: 'Tournois',
    icon: 'pi pi-calendar',
    description: 'Consultez les tournois à venir et les résultats'
  }
])

// Methods
const goHome = () => {
  router.push('/')
  toast.add({
    severity: 'success',
    summary: 'Navigation',
    detail: 'Retour à l\'accueil',
    life: 2000
  })
}

const subscribeNotifications = () => {
  toast.add({
    severity: 'info',
    summary: 'Notifications',
    detail: `Vous serez notifié lors du lancement de ${sectionTitle.value}`,
    life: 4000
  })
}

const navigateTo = (route) => {
  router.push(route)
}

// Simulate progress update
onMounted(() => {
  // Animation d'entrée du pourcentage
  let currentProgress = 0
  const targetProgress = developmentProgress.value
  const progressInterval = setInterval(() => {
    if (currentProgress < targetProgress) {
      currentProgress += 1
      const progressFill = document.querySelector('.progress-fill')
      if (progressFill) {
        progressFill.style.width = currentProgress + '%'
      }
    } else {
      clearInterval(progressInterval)
    }
  }, 50)
})
</script>

<style scoped>
.coming-soon-view {
  min-height: calc(100vh - 140px);
  display: flex;
  align-items: center;
  padding: 2rem 0;
  background: linear-gradient(135deg, var(--surface) 0%, var(--surface-100) 100%);
}

.coming-soon-card {
  max-width: 800px;
  margin: 0 auto;
  text-align: center;
  background: var(--surface) !important;
  box-shadow: var(--shadow-large) !important;
}

.coming-soon-content {
  padding: 2rem;
}

.icon-section {
  margin-bottom: 2rem;
}

.animated-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 120px;
  height: 120px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--primary-light), var(--primary));
  margin-bottom: 1rem;
  position: relative;
}

.main-icon {
  font-size: 3rem;
  color: var(--text-inverse);
}

.text-section {
  margin-bottom: 3rem;
}

.coming-soon-title {
  font-size: 3rem;
  font-weight: 800;
  color: var(--text-primary);
  margin: 0 0 1rem 0;
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.coming-soon-subtitle {
  font-size: 1.5rem;
  color: var(--text-secondary);
  margin: 0 0 1.5rem 0;
  font-weight: 600;
}

.coming-soon-description {
  font-size: 1.1rem;
  color: var(--text-secondary);
  line-height: 1.6;
  max-width: 600px;
  margin: 0 auto 2rem auto;
}

.progress-section {
  max-width: 400px;
  margin: 0 auto 2rem auto;
}

.progress-label {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
  color: var(--text-secondary);
}

.progress-percentage {
  font-weight: 600;
  color: var(--primary);
}

.progress-bar {
  height: 8px;
  background: var(--surface-200);
  border-radius: 4px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, var(--primary-light), var(--primary));
  border-radius: 4px;
  transition: width 0.3s ease-out;
  width: 0%;
}

.actions-section {
  margin-bottom: 3rem;
}

.actions-section .p-button {
  margin: 0 0.5rem;
  min-width: 200px;
}

.features-preview {
  max-width: 500px;
  margin: 0 auto;
}

.features-title {
  color: var(--text-primary);
  margin-bottom: 1.5rem;
  font-size: 1.2rem;
}

.features-list {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.feature-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem;
  background: var(--surface-100);
  border-radius: var(--border-radius);
  transition: all var(--transition-medium);
  cursor: pointer;
}

.feature-icon {
  color: var(--primary);
  font-size: 1.2rem;
}

.feature-name {
  font-weight: 500;
  color: var(--text-primary);
}

.navigation-suggestions {
  margin-top: 4rem;
  text-align: center;
}

.suggestions-title {
  color: var(--text-primary);
  margin-bottom: 2rem;
  font-size: 1.5rem;
  font-weight: 600;
}

.suggestions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
}

.suggestion-card {
  cursor: pointer;
  transition: all var(--transition-medium);
  background: var(--surface) !important;
}

.suggestion-content {
  text-align: center;
  padding: 1rem;
}

.suggestion-icon {
  font-size: 2.5rem;
  color: var(--primary);
  margin-bottom: 1rem;
}

.suggestion-title {
  color: var(--text-primary);
  margin: 0 0 0.5rem 0;
  font-size: 1.2rem;
  font-weight: 600;
}

.suggestion-description {
  color: var(--text-secondary);
  margin: 0;
  font-size: 0.9rem;
  line-height: 1.5;
}

/* Responsive */
@media (max-width: 768px) {
  .coming-soon-title {
    font-size: 2.5rem;
  }
  
  .coming-soon-subtitle {
    font-size: 1.3rem;
  }
  
  .animated-icon {
    width: 100px;
    height: 100px;
  }
  
  .main-icon {
    font-size: 2.5rem;
  }
  
  .actions-section .p-button {
    width: 100%;
    margin: 0.5rem 0;
  }
  
  .features-list {
    grid-template-columns: 1fr;
  }
  
  .suggestions-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 640px) {
  .coming-soon-content {
    padding: 1.5rem;
  }
  
  .coming-soon-title {
    font-size: 2rem;
  }
  
  .coming-soon-description {
    font-size: 1rem;
  }
}

/* Animations personnalisées */
@keyframes rotate {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.emerald-spinner::before {
  content: '';
  position: absolute;
  inset: -4px;
  border: 3px solid transparent;
  border-top: 3px solid var(--accent);
  border-radius: 50%;
  animation: rotate 2s linear infinite;
}
</style>

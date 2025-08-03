<template>
  <div class="shop-map-page">
    <!-- Breadcrumbs -->
    <nav class="page-breadcrumb">
      <router-link to="/" class="breadcrumb-link">
        <i class="pi pi-home"></i>
        Page d'accueil
      </router-link>
      <i class="pi pi-chevron-right breadcrumb-separator"></i>
      <span class="breadcrumb-current">Carte des boutiques</span>
    </nav>

    <div class="container">
      <div class="page-grid">
        
        <!-- Contenu principal avec la carte -->
        <div class="main-content">
          <div class="map-section slide-in-up">
            <ShopMap />
          </div>
          
          <!-- Widgets sous la carte -->
          <div class="under-map-widgets">
            <!-- Widget Favoris -->
            <Card class="sidebar-card favorites-card slide-in-down">
              <template #content>
                <div class="sidebar-content-inner">
                  <div class="empty-state">
                    <i class="pi pi-heart empty-icon"></i>
                    <h4 class="empty-title">Vos favoris</h4>
                    <p class="empty-description">
                      Aucun favori pour le moment
                    </p>
                    <Button 
                      label="Explorer"
                      icon="pi pi-search"
                      class="emerald-outline-btn small"
                    />
                  </div>
                </div>
              </template>
            </Card>

            <!-- Widget Stats utilisateur -->
            <Card class="sidebar-card stats-card slide-in-down">
              <template #content>
                <div class="sidebar-content-inner">
                  <div class="user-stats">
                    <h4 class="stats-title">Vos statistiques</h4>
                    <div class="stats-grid">
                      <div class="stat-item">
                        <span class="stat-value">0</span>
                        <span class="stat-label">Victoires</span>
                      </div>
                      <div class="stat-item">
                        <span class="stat-value">0</span>
                        <span class="stat-label">Decks</span>
                      </div>
                      <div class="stat-item">
                        <span class="stat-value">0</span>
                        <span class="stat-label">Cartes</span>
                      </div>
                      <div class="stat-item">
                        <span class="stat-value">0</span>
                        <span class="stat-label">Tournois</span>
                      </div>
                    </div>
                  </div>
                </div>
              </template>
            </Card>
          </div>
        </div>

        <!-- Sidebar droite -->
        <aside class="sidebar">
          <div class="sidebar-content">
            
            <!-- Widget Boutiques populaires -->
            <Card class="sidebar-card map-card slide-in-down">
              <template #header>
                <div class="card-header-custom map-header">
                  <i class="pi pi-map-marker header-icon"></i>
                  <h3 class="header-title">Boutiques populaires</h3>
                </div>
              </template>
              <template #content>
                <div class="sidebar-content-inner">
                  <div class="section-subtitle">
                    Les boutiques les plus populaires
                  </div>
                  
                  <!-- Liste des boutiques -->
                  <div class="shops-list">
                    <div 
                      v-for="shop in popularShops" 
                      :key="shop.id"
                      class="shop-item hover-lift"
                      @click="handleShopClick(shop)"
                    >
                      <div class="shop-avatar">
                        <i class="pi pi-shop"></i>
                      </div>
                      <div class="shop-info">
                        <div class="shop-name">{{ shop.name }}</div>
                        <div class="shop-description">{{ shop.description }}</div>
                      </div>
                      <div class="shop-action">
                        <i class="pi pi-chevron-right"></i>
                      </div>
                    </div>
                  </div>

                  <!-- Loading indicator -->
                  <div v-if="loadingShops" class="loading-section">
                    <div class="emerald-spinner"></div>
                    <span class="loading-text">Chargement des données...</span>
                  </div>
                </div>
              </template>
            </Card>
          </div>
        </aside>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import ShopMap from '@/components/ShopMap.vue'
import api from '@/services/api'

// State
const popularShops = ref([])
const loadingShops = ref(false)

// Charger les vraies données des boutiques populaires
const loadPopularShops = async () => {
  try {
    loadingShops.value = true
    const response = await api.shops.getPopular(10)
    
    if (response.data.success) {
      popularShops.value = response.data.data.map(shop => ({
        id: shop.id,
        name: shop.name,
        description: shop.description || `${shop.address?.city || 'Boutique TCG'}`,
        slug: shop.slug
      }))
    }
  } catch (error) {
    console.error('Erreur chargement boutiques populaires:', error)
    // Données par défaut
    popularShops.value = [
      { id: 1, name: 'Magic Store', description: 'Spécialiste cartes premium' },
      { id: 2, name: 'TCG Palace', description: 'Collection rare et vintage' },
      { id: 3, name: 'Card Kingdom', description: 'Nouveautés et exclusivités' },
      { id: 4, name: 'Deck Master', description: 'Accessoires et protection' },
      { id: 5, name: 'Game Zone', description: 'Tournois et événements' },
      { id: 6, name: 'Card Shop Plus', description: 'Cartes anciennes et modernes' },
      { id: 7, name: 'TCG Arena', description: 'Compétitions hebdomadaires' },
      { id: 8, name: 'Magic Corner', description: 'Conseil et stratégie' },
      { id: 9, name: 'Collectible World', description: 'Univers des collectionneurs' },
      { id: 10, name: 'Pro Gaming Store', description: 'Matériel et coaching' }
    ]
  } finally {
    loadingShops.value = false
  }
}

// Gestion du clic sur une boutique
const handleShopClick = (shop) => {
  console.log('Clic sur boutique:', shop.name)
  // TODO: Navigation vers page détail boutique ou action spécifique
}

// Lifecycle
onMounted(() => {
  console.log('🗺️ ShopMapView chargée')
  loadPopularShops()
})
</script>

<style scoped>
.shop-map-page {
  min-height: calc(100vh - 140px);
  background: var(--surface-gradient);
  padding: 2rem 0;
}

/* Breadcrumbs */
.page-breadcrumb {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 0 auto 2rem auto;
  max-width: 1400px;
  padding: 0 2rem;
}

.breadcrumb-link {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--primary);
  text-decoration: none;
  font-weight: 500;
  cursor: pointer;
  transition: color var(--transition-fast);
  padding: 0.5rem 0.75rem;
  border-radius: var(--border-radius);
}

.breadcrumb-link:hover {
  background: var(--surface-200);
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

.container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 2rem;
}

.page-grid {
  display: grid;
  grid-template-columns: 1fr 350px;
  gap: 2rem;
  align-items: start;
}

/* Main content */
.main-content {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

/* Section spéciale pour la carte */
.map-section {
  background: white;
  border-radius: var(--border-radius-large);
  padding: 1.5rem;
  box-shadow: var(--shadow-small);
  min-height: 600px;
  position: relative;
  z-index: 1;
}

/* Widgets sous la carte */
.under-map-widgets {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
  animation: slideInUp 0.6s ease-out;
}

/* Sidebar */
.sidebar {
  position: sticky;
  top: 160px;
}

.sidebar-content {
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
  background: var(--accent);
  color: white;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.map-header {
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

.sidebar-content-inner {
  padding: 1.5rem;
}

.section-subtitle {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--text-secondary);
  margin-bottom: 1rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* Shops list */
.shops-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
}

.shop-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.75rem;
  border-radius: var(--border-radius);
  border: 1px solid var(--surface-200);
  cursor: pointer;
  transition: all var(--transition-fast);
}

.shop-item:hover {
  background: var(--surface-100);
  border-color: var(--primary);
}

.shop-avatar {
  width: 40px;
  height: 40px;
  background: var(--surface-200);
  border-radius: var(--border-radius);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text-secondary);
}

.shop-info {
  flex: 1;
}

.shop-name {
  font-weight: 500;
  color: var(--text-primary);
  font-size: 0.9rem;
}

.shop-description {
  font-size: 0.8rem;
  color: var(--text-secondary);
  margin-top: 0.125rem;
}

.shop-action {
  color: var(--text-secondary);
  transition: color var(--transition-fast);
}

.shop-item:hover .shop-action {
  color: var(--primary);
}

/* Loading section */
.loading-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem 0;
}

.loading-text {
  font-size: 0.875rem;
  color: var(--text-secondary);
}

/* Empty state */
.empty-state {
  text-align: center;
  padding: 1rem 0;
}

.empty-icon {
  font-size: 2.5rem;
  color: var(--text-secondary);
  margin-bottom: 1rem;
}

.empty-title {
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0 0 0.5rem 0;
}

.empty-description {
  font-size: 0.875rem;
  color: var(--text-secondary);
  margin: 0 0 1.5rem 0;
  line-height: 1.4;
}

/* User stats */
.user-stats {
  text-align: center;
}

.stats-title {
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0 0 1.5rem 0;
}

.stats-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
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
}

/* Buttons */
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

/* Animations */
@keyframes slideInUp {
  0% {
    opacity: 0;
    transform: translateY(30px);
  }
  100% {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes slideInDown {
  0% {
    opacity: 0;
    transform: translateY(-20px);
  }
  100% {
    opacity: 1;
    transform: translateY(0);
  }
}

.slide-in-up {
  animation: slideInUp 0.6s ease-out;
}

.slide-in-down {
  animation: slideInDown 0.6s ease-out;
}

.hover-lift:hover {
  transform: translateY(-2px);
}

/* Responsive */
@media (max-width: 1024px) {
  .page-grid {
    grid-template-columns: 1fr;
    gap: 1.5rem;
  }
  
  .sidebar {
    position: static;
  }
  
  .under-map-widgets {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .container {
    padding: 0 1rem;
  }
  
  .page-breadcrumb {
    padding: 0 1rem;
  }
  
  .shop-map-page {
    padding: 1rem 0;
  }
  
  .under-map-widgets {
    grid-template-columns: 1fr;
  }
}
</style>
<template>
  <div class="home-page">
    <div class="container">
      <div class="main-grid">
        
        <!-- Contenu principal (3/4) -->
        <div class="main-content">
          
          <!-- Section Derniers Tournois -->
          <section class="content-section slide-in-up">
            <Card class="gaming-card tournament-card">
              <template #content>
                <div class="section-content tournaments">
                  <div class="section-header">
                    <i class="pi pi-trophy section-icon"></i>
                    <h2 class="section-title">DERNIERS TOURNOIS</h2>
                  </div>
                  <div class="placeholder-content">
                    <div class="placeholder-text">
                      Tournois récemment terminés
                    </div>
                    <Button 
                      label="Voir tous les résultats"
                      icon="pi pi-external-link"
                      class="emerald-outline-btn"
                    />
                  </div>
                </div>
              </template>
            </Card>
          </section>

          <!-- Section Tournois à Venir -->
          <section class="content-section slide-in-up">
            <Card class="gaming-card upcoming-card">
              <template #content>
                <div class="section-content upcoming">
                  <div class="section-header">
                    <i class="pi pi-calendar section-icon"></i>
                    <h2 class="section-title">TOURNOIS À VENIR</h2>
                  </div>
                  <div class="placeholder-content">
                    <div class="upcoming-info">
                      <div class="next-tournament">
                        <span class="tournament-date">Prochain tournoi dans</span>
                        <span class="countdown">3 jours</span>
                      </div>
                    <Button 
                      label="Voir tous les résultats"
                      icon="pi pi-external-link"
                      class="emerald-outline-btn"
                    />
                    </div>
                  </div>
                </div>
              </template>
            </Card>
          </section>

          <!-- Section Marketplace -->
          <section class="content-section slide-in-up">
            <Card class="gaming-card marketplace-card">
              <template #content>
                <div class="section-content marketplace">
                  <div class="section-header">
                    <i class="pi pi-shopping-bag section-icon"></i>
                    <h2 class="section-title">MARKETPLACE</h2>
                  </div>
                  <div class="placeholder-content">
                    <div class="marketplace-stats">
                      <div class="stat-item">
                        <span class="stat-value">1,247</span>
                        <span class="stat-label">Cartes en vente</span>
                      </div>
                      <div class="stat-item">
                        <span class="stat-value">€8,932</span>
                        <span class="stat-label">Valeur totale</span>
                      </div>
                    </div>
                    <Button 
                      label="Explorer le marché"
                      icon="pi pi-search"
                      class="emerald-outline-btn"
                    />
                  </div>
                </div>
              </template>
            </Card>
          </section>

          <!-- Section Meilleurs Decks -->
          <section class="content-section slide-in-up">
            <div class="section-header standalone">
              <i class="pi pi-clone section-icon"></i>
              <h2 class="section-title">MEILLEURS DECKS</h2>
            </div>
            
            <div class="decks-grid">
              <Card 
                v-for="(deck, index) in topDecks" 
                :key="index" 
                class="deck-card gaming-card hover-lift"
              >
                <template #content>
                  <div class="deck-content">
                    <div class="deck-image">
                      <div class="deck-placeholder">
                        <i class="pi pi-clone deck-icon"></i>
                      </div>
                      <div class="deck-rank">#{{ index + 1 }}</div>
                    </div>
                    <div class="deck-info">
                      <h3 class="deck-name">{{ deck.name }}</h3>
                      <div class="deck-stats">
                        <span class="win-rate">{{ deck.winRate }}% WR</span>
                        <span class="games-played">{{ deck.games }} parties</span>
                      </div>
                    </div>
                  </div>
                </template>
              </Card>
            </div>
          </section>
        </div>

        <!-- Sidebar droite (1/4) -->
        <aside class="sidebar">
          <div class="sidebar-content">
            
            <!-- Widget Accéder à la carte -->
            <Card class="sidebar-card map-card slide-in-down">
              <template #header>
                <div class="card-header-custom map-header">
                  <i class="pi pi-map-marker header-icon"></i>
                  <h3 class="header-title">Accéder à la carte</h3>
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
                  <div class="loading-section">
                    <div class="emerald-spinner"></div>
                    <span class="loading-text">Chargement des données...</span>
                  </div>
                </div>
              </template>
            </Card>

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
        </aside>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

// State
const popularShops = ref([
  {
    id: 1,
    name: 'Magic Store',
    description: 'Spécialiste cartes premium'
  },
  {
    id: 2,
    name: 'TCG Palace',
    description: 'Collection rare et vintage'
  },
  {
    id: 3,
    name: 'Card Kingdom',
    description: 'Nouveautés et exclusivités'
  },
  {
    id: 4,
    name: 'Deck Master',
    description: 'Accessoires et protection'
  },
  {
    id: 5,
    name: 'Game Zone',
    description: 'Tournois et événements'
  }
])

const topDecks = ref([
  { name: 'Aggro Rush', winRate: 73, games: 1247 },
  { name: 'Control Master', winRate: 68, games: 892 },
  { name: 'Midrange Power', winRate: 65, games: 1156 },
  { name: 'Combo Strike', winRate: 62, games: 734 },
  { name: 'Tempo Deck', winRate: 59, games: 623 }
])

// Lifecycle
onMounted(() => {
  console.log('🏠 Home Emerald chargée')
  
  // Animation séquentielle des éléments
  const sections = document.querySelectorAll('.content-section')
  sections.forEach((section, index) => {
    setTimeout(() => {
      section.style.animationDelay = `${index * 0.1}s`
    }, 100)
  })
})
</script>

<style scoped>
/* === HOME PAGE EMERALD GAMING === */

.home-page {
  min-height: calc(100vh - 140px);
  background: var(--surface-gradient);
  padding: 2rem 0;
}

.container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 2rem;
}

.main-grid {
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

.content-section {
  animation: slideInUp 0.6s ease-out;
}

/* Gaming cards base */
.gaming-card {
  position: relative;
  background: white;
  border-radius: var(--border-radius-large);
  box-shadow: var(--shadow-medium);
  border: 1px solid var(--surface-200);
  overflow: hidden;
  transition: all var(--transition-medium);
}

.gaming-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: var(--emerald-gradient);
}

.gaming-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-large);
}

/* Section content */
.section-content {
  padding: 2rem;
  min-height: 200px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.section-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.section-header.standalone {
  margin-bottom: 1.5rem;
  padding: 0 0.5rem;
}

.section-icon {
  font-size: 2rem;
  color: var(--primary);
  background: rgba(38, 166, 154, 0.1);
  width: 60px;
  height: 60px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.section-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--text-primary);
  letter-spacing: 1px;
  text-transform: uppercase;
  margin: 0;
}

.placeholder-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 1.5rem;
}

.placeholder-text {
  color: var(--text-secondary);
  font-size: 1.1rem;
  text-align: center;
}

/* Specific card styles */
.tournament-card::before {
  background: linear-gradient(90deg, #f59e0b, #d97706);
}

.upcoming-card::before {
  background: linear-gradient(90deg, var(--primary), var(--primary-dark));
}

.marketplace-card::before {
  background: linear-gradient(90deg, var(--accent), var(--accent-dark));
}

/* Upcoming tournaments */
.upcoming-info {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.next-tournament {
  text-align: center;
}

.tournament-date {
  display: block;
  color: var(--text-secondary);
  font-size: 0.9rem;
  margin-bottom: 0.5rem;
}

.countdown {
  display: block;
  font-size: 2rem;
  font-weight: 700;
  color: var(--primary);
}

/* Marketplace stats */
.marketplace-stats {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

.stat-item {
  text-align: center;
}

.stat-value {
  display: block;
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--primary);
}

.stat-label {
  display: block;
  font-size: 0.875rem;
  color: var(--text-secondary);
  margin-top: 0.25rem;
}

/* Decks grid */
.decks-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
}

.deck-card {
  cursor: pointer;
}

.deck-content {
  padding: 1.5rem;
}

.deck-image {
  position: relative;
  margin-bottom: 1rem;
}

.deck-placeholder {
  aspect-ratio: 16/9;
  background: var(--surface-200);
  border-radius: var(--border-radius);
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
}

.deck-icon {
  font-size: 2rem;
  color: var(--text-secondary);
}

.deck-rank {
  position: absolute;
  top: -8px;
  right: -8px;
  background: var(--primary);
  color: white;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.875rem;
}

.deck-info {
  text-align: center;
}

.deck-name {
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0 0 0.5rem 0;
}

.deck-stats {
  display: flex;
  justify-content: center;
  gap: 1rem;
  font-size: 0.875rem;
}

.win-rate {
  color: var(--primary);
  font-weight: 600;
}

.games-played {
  color: var(--text-secondary);
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

/* Responsive */
@media (max-width: 1024px) {
  .main-grid {
    grid-template-columns: 1fr;
    gap: 1.5rem;
  }
  
  .sidebar {
    position: static;
  }
  
  .sidebar-content {
    grid-column: 1 / -1;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
  }
}

@media (max-width: 768px) {
  .container {
    padding: 0 1rem;
  }
  
  .home-page {
    padding: 1rem 0;
  }
  
  .main-content {
    gap: 1.5rem;
  }
  
  .section-content {
    padding: 1.5rem;
  }
  
  .decks-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
  
  .marketplace-stats {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
}

@media (max-width: 640px) {
  .section-header {
    flex-direction: column;
    text-align: center;
    gap: 0.75rem;
  }
  
  .section-icon {
    width: 50px;
    height: 50px;
    font-size: 1.5rem;
  }
  
  .section-title {
    font-size: 1.25rem;
  }
  
  .sidebar-content {
    grid-template-columns: 1fr;
  }
}
</style>
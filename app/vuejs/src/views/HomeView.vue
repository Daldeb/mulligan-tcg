<template>
  <div class="home-view">
    <!-- Hero Section avec News et Événements -->
    <section class="hero-section">
      <div class="container">
        <Card class="hero-card emerald-gradient text-white">
          <template #content>
            <div class="hero-content">
              <div class="hero-text">
                <h1 class="hero-title slide-in-up">
                  NEWS ET ÉVÉNEMENTS
                </h1>
                <p class="hero-subtitle slide-in-up" style="animation-delay: 0.2s">
                  Restez informé des dernières actualités du monde TCG
                </p>
              </div>
              <div class="hero-actions slide-in-up" style="animation-delay: 0.4s">
                <Button 
                  label="Voir tout" 
                  icon="pi pi-arrow-right"
                  class="p-button-secondary"
                  @click="viewAllNews"
                />
              </div>
            </div>
          </template>
        </Card>
      </div>
    </section>

    <!-- Main Content Grid -->
    <section class="main-content">
      <div class="container">
        <div class="content-grid">
          
          <!-- Left Column - Main Content -->
          <div class="main-column">
            
            <!-- Derniers Tournois -->
            <Card class="section-card slide-in-up" style="animation-delay: 0.1s">
              <template #header>
                <div class="section-header">
                  <h2 class="section-title">DERNIERS TOURNOIS</h2>
                  <Button 
                    icon="pi pi-refresh" 
                    class="p-button-text p-button-rounded"
                    @click="refreshTournaments"
                  />
                </div>
              </template>
              <template #content>
                <div class="tournaments-grid">
                  <div 
                    v-for="tournament in recentTournaments" 
                    :key="tournament.id"
                    class="tournament-item hover-lift"
                    @click="viewTournament(tournament)"
                  >
                    <div class="tournament-info">
                      <h4>{{ tournament.name }}</h4>
                      <p class="text-secondary">{{ tournament.date }}</p>
                    </div>
                    <Badge :value="tournament.participants" severity="info" />
                  </div>
                </div>
              </template>
            </Card>

            <!-- Tournois à Venir -->
            <Card class="section-card slide-in-up" style="animation-delay: 0.2s">
              <template #header>
                <div class="section-header">
                  <h2 class="section-title">TOURNOIS À VENIR</h2>
                  <Button 
                    icon="pi pi-play" 
                    class="p-button-accent p-button-rounded pulse-emerald"
                    @click="joinTournament"
                  />
                </div>
              </template>
              <template #content>
                <div class="upcoming-tournaments">
                  <div 
                    v-for="tournament in upcomingTournaments" 
                    :key="tournament.id"
                    class="upcoming-item hover-lift"
                  >
                    <div class="upcoming-info">
                      <h4>{{ tournament.name }}</h4>
                      <p class="text-secondary">{{ tournament.startDate }}</p>
                      <div class="tournament-meta">
                        <span class="prize">{{ tournament.prize }}</span>
                        <span class="format">{{ tournament.format }}</span>
                      </div>
                    </div>
                    <Button 
                      label="S'inscrire" 
                      class="p-button-sm"
                      @click="registerForTournament(tournament)"
                    />
                  </div>
                </div>
              </template>
            </Card>

            <!-- Marketplace -->
            <Card class="section-card slide-in-up" style="animation-delay: 0.3s">
              <template #header>
                <div class="section-header">
                  <h2 class="section-title">MARKETPLACE</h2>
                  <Button 
                    icon="pi pi-plus" 
                    class="p-button-text p-button-rounded"
                    @click="addToMarketplace"
                  />
                </div>
              </template>
              <template #content>
                <div class="marketplace-preview">
                  <p class="text-secondary mb-4">
                    Découvrez les dernières offres de la communauté
                  </p>
                  <div class="marketplace-stats">
                    <div class="stat-item">
                      <span class="stat-number">{{ marketStats.activeOffers }}</span>
                      <span class="stat-label">Offres actives</span>
                    </div>
                    <div class="stat-item">
                      <span class="stat-number">{{ marketStats.recentSales }}</span>
                      <span class="stat-label">Ventes récentes</span>
                    </div>
                    <div class="stat-item">
                      <span class="stat-number">{{ marketStats.avgPrice }}€</span>
                      <span class="stat-label">Prix moyen</span>
                    </div>
                  </div>
                </div>
              </template>
            </Card>

            <!-- Meilleurs Decks -->
            <Card class="section-card slide-in-up" style="animation-delay: 0.4s">
              <template #header>
                <div class="section-header">
                  <h2 class="section-title">MEILLEURS DECKS</h2>
                  <Button 
                    icon="pi pi-clone" 
                    class="p-button-text p-button-rounded"
                    @click="viewAllDecks"
                  />
                </div>
              </template>
              <template #content>
                <div class="decks-carousel">
                  <div 
                    v-for="deck in topDecks" 
                    :key="deck.id"
                    class="deck-preview hover-lift"
                    @click="viewDeck(deck)"
                  >
                    <div class="deck-image">
                      <i class="pi pi-clone deck-icon"></i>
                    </div>
                    <div class="deck-info">
                      <h5>{{ deck.name }}</h5>
                      <p class="deck-class">{{ deck.class }}</p>
                      <div class="deck-stats">
                        <span class="win-rate">{{ deck.winRate }}%</span>
                        <span class="games">{{ deck.games }} parties</span>
                      </div>
                    </div>
                  </div>
                </div>
              </template>
            </Card>

          </div>

          <!-- Right Column - Sidebar Boutiques -->
          <div class="sidebar-column">
            <Card class="shop-widget slide-in-up" style="animation-delay: 0.5s">
              <template #header>
                <div class="widget-header">
                  <Button 
                    label="Accéder à la carte"
                    class="p-button-accent w-full mb-3"
                    icon="pi pi-map"
                    @click="accessMap"
                  />
                </div>
              </template>
              <template #content>
                <div class="shop-content">
                  <h3 class="shop-title">Les boutiques les plus populaires</h3>
                  
                  <div class="shop-list">
                    <div 
                      v-for="shop in popularShops" 
                      :key="shop.id"
                      class="shop-item hover-lift"
                      @click="visitShop(shop)"
                    >
                      <div class="shop-image">
                        <Avatar 
                          :image="shop.image" 
                          size="large" 
                          shape="square"
                        />
                      </div>
                      <div class="shop-info">
                        <h4>{{ shop.name }}</h4>
                        <p class="shop-description">{{ shop.description }}</p>
                        <div class="shop-rating">
                          <i class="pi pi-star-fill" style="color: #ffd700;"></i>
                          <span>{{ shop.rating }}</span>
                        </div>
                      </div>
                    </div>
                  </div>

                  <Button 
                    icon="pi pi-refresh" 
                    class="p-button-rounded p-button-text mt-4"
                    @click="refreshShops"
                  />
                </div>
              </template>
            </Card>
          </div>

        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'

const toast = useToast()

// Mock data
const recentTournaments = ref([
  { id: 1, name: 'Championship Series #12', date: '15 Juil 2025', participants: '128' },
  { id: 2, name: 'Weekly Standard', date: '20 Juil 2025', participants: '64' },
  { id: 3, name: 'Emerald Cup', date: '22 Juil 2025', participants: '256' }
])

const upcomingTournaments = ref([
  { 
    id: 1, 
    name: 'Grand Prix Paris', 
    startDate: '30 Juil 2025',
    prize: '5000€',
    format: 'Standard'
  },
  { 
    id: 2, 
    name: 'Summer Championship', 
    startDate: '5 Août 2025',
    prize: '2500€',
    format: 'Wild'
  }
])

const marketStats = ref({
  activeOffers: 1247,
  recentSales: 89,
  avgPrice: 23
})

const topDecks = ref([
  { id: 1, name: 'Aggro Warrior', class: 'Guerrier', winRate: 67, games: 1204 },
  { id: 2, name: 'Control Mage', class: 'Mage', winRate: 63, games: 987 },
  { id: 3, name: 'Midrange Hunter', class: 'Chasseur', winRate: 61, games: 856 },
  { id: 4, name: 'Combo Priest', class: 'Prêtre', winRate: 59, games: 743 }
])

const popularShops = ref([
  {
    id: 1,
    name: 'CARTES PREMIUM',
    description: 'Spécialiste cartes rares',
    rating: 4.8,
    image: 'https://i.pravatar.cc/60?img=10'
  },
  {
    id: 2,
    name: 'TCG MASTER',
    description: 'Accessoires et boîtes',
    rating: 4.6,
    image: 'https://i.pravatar.cc/60?img=11'
  },
  {
    id: 3,
    name: 'DECK BUILDER',
    description: 'Cartes singles',
    rating: 4.7,
    image: 'https://i.pravatar.cc/60?img=12'
  },
  {
    id: 4,
    name: 'COLLECTION PRO',
    description: 'Échange et vente',
    rating: 4.5,
    image: 'https://i.pravatar.cc/60?img=13'
  }
])

// Methods
const viewAllNews = () => {
  toast.add({ severity: 'info', summary: 'Navigation', detail: 'Redirection vers les actualités', life: 2000 })
}

const refreshTournaments = () => {
  toast.add({ severity: 'success', summary: 'Actualisation', detail: 'Tournois mis à jour', life: 2000 })
}

const viewTournament = (tournament) => {
  toast.add({ severity: 'info', summary: 'Tournoi', detail: `Consultation de ${tournament.name}`, life: 2000 })
}

const joinTournament = () => {
  toast.add({ severity: 'warn', summary: 'Tournoi', detail: 'Sélectionnez un tournoi pour participer', life: 3000 })
}

const registerForTournament = (tournament) => {
  toast.add({ severity: 'success', summary: 'Inscription', detail: `Inscription au ${tournament.name}`, life: 3000 })
}

const addToMarketplace = () => {
  toast.add({ severity: 'info', summary: 'Marketplace', detail: 'Ajouter une offre', life: 2000 })
}

const viewAllDecks = () => {
  toast.add({ severity: 'info', summary: 'Decks', detail: 'Voir tous les decks', life: 2000 })
}

const viewDeck = (deck) => {
  toast.add({ severity: 'info', summary: 'Deck', detail: `Consultation du deck ${deck.name}`, life: 2000 })
}

const accessMap = () => {
  toast.add({ severity: 'success', summary: 'Carte', detail: 'Accès à la carte des boutiques', life: 2000 })
}

const visitShop = (shop) => {
  toast.add({ severity: 'info', summary: 'Boutique', detail: `Visite de ${shop.name}`, life: 2000 })
}

const refreshShops = () => {
  toast.add({ severity: 'success', summary: 'Actualisation', detail: 'Boutiques mises à jour', life: 2000 })
}

onMounted(() => {
  // Animation d'entrée
  console.log('HomeView mounted - MULLIGAN TCG ready!')
})
</script>

<style scoped>
.home-view {
  min-height: 100vh;
  background: var(--surface);
}

.hero-section {
  padding: 2rem 0;
  background: linear-gradient(135deg, var(--surface-100) 0%, var(--surface) 100%);
}

.hero-card {
  background: linear-gradient(135deg, var(--primary), var(--primary-dark)) !important;
  border: none !important;
  overflow: hidden;
}

.hero-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
}

.hero-title {
  font-size: 2.5rem;
  font-weight: 800;
  margin: 0 0 0.5rem 0;
  letter-spacing: -1px;
}

.hero-subtitle {
  font-size: 1.1rem;
  opacity: 0.9;
  margin: 0;
}

.main-content {
  padding: 2rem 0;
}

.content-grid {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 2rem;
  align-items: start;
}

.section-card {
  margin-bottom: 2rem;
  transition: all var(--transition-medium);
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem;
  background: var(--surface-100);
  margin: -1.5rem -1.5rem 1rem -1.5rem;
}

.section-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0;
  letter-spacing: 1px;
}

.tournaments-grid {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.tournament-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background: var(--surface-100);
  border-radius: var(--border-radius);
  cursor: pointer;
  transition: all var(--transition-medium);
}

.tournament-item h4 {
  margin: 0 0 0.25rem 0;
  color: var(--text-primary);
}

.upcoming-tournaments {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.upcoming-item {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 1.5rem;
  background: var(--surface-100);
  border-radius: var(--border-radius-large);
  cursor: pointer;
  transition: all var(--transition-medium);
}

.upcoming-info h4 {
  margin: 0 0 0.5rem 0;
  color: var(--text-primary);
}

.tournament-meta {
  display: flex;
  gap: 1rem;
  margin-top: 0.5rem;
}

.prize {
  color: var(--accent);
  font-weight: 600;
}

.format {
  color: var(--primary);
  font-weight: 500;
}

.marketplace-preview {
  text-align: center;
}

.marketplace-stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
}

.stat-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 1rem;
  background: var(--surface-100);
  border-radius: var(--border-radius);
}

.stat-number {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--primary);
}

.stat-label {
  font-size: 0.8rem;
  color: var(--text-secondary);
  margin-top: 0.25rem;
}

.decks-carousel {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.deck-preview {
  padding: 1rem;
  background: var(--surface-100);
  border-radius: var(--border-radius-large);
  cursor: pointer;
  transition: all var(--transition-medium);
  text-align: center;
}

.deck-image {
  margin-bottom: 1rem;
}

.deck-icon {
  font-size: 2rem;
  color: var(--primary);
}

.deck-info h5 {
  margin: 0 0 0.5rem 0;
  color: var(--text-primary);
}

.deck-class {
  color: var(--text-secondary);
  margin: 0 0 0.5rem 0;
}

.deck-stats {
  display: flex;
  justify-content: space-between;
  font-size: 0.8rem;
}

.win-rate {
  color: var(--primary);
  font-weight: 600;
}

/* Sidebar Boutiques */
.shop-widget {
  background: linear-gradient(135deg, var(--accent-light), var(--accent)) !important;
  color: var(--text-inverse) !important;
  position: sticky;
  top: 160px;
}

.widget-header {
  margin: -1.5rem -1.5rem 1rem -1.5rem;
  padding: 1.5rem;
}

.shop-title {
  color: var(--text-inverse);
  margin: 0 0 1.5rem 0;
  font-size: 1.1rem;
  font-weight: 600;
}

.shop-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.shop-item {
  display: flex;
  gap: 1rem;
  align-items: center;
  padding: 1rem;
  background: rgba(255, 255, 255, 0.1);
  border-radius: var(--border-radius);
  cursor: pointer;
  transition: all var(--transition-medium);
  backdrop-filter: blur(10px);
}

.shop-item:hover {
  background: rgba(255, 255, 255, 0.2);
  transform: translateX(4px);
}

.shop-info h4 {
  margin: 0 0 0.25rem 0;
  color: var(--text-inverse);
  font-size: 0.9rem;
}

.shop-description {
  margin: 0 0 0.5rem 0;
  font-size: 0.8rem;
  opacity: 0.9;
}

.shop-rating {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.8rem;
}

/* Responsive */
@media (max-width: 1024px) {
  .content-grid {
    grid-template-columns: 1fr;
    gap: 1.5rem;
  }
  
  .hero-content {
    flex-direction: column;
    text-align: center;
    gap: 1rem;
  }
  
  .hero-title {
    font-size: 2rem;
  }
}

@media (max-width: 768px) {
  .hero-title {
    font-size: 1.8rem;
  }
  
  .decks-carousel {
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  }
  
  .marketplace-stats {
    grid-template-columns: 1fr;
  }
  
  .shop-widget {
    position: static;
  }
}

@media (max-width: 640px) {
  .container {
    padding: 0 1rem;
  }
  
  .hero-section {
    padding: 1rem 0;
  }
  
  .main-content {
    padding: 1rem 0;
  }
  
  .upcoming-item {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
}
</style>
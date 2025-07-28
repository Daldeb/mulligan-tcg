<template>
  <div class="home-page">
    <div class="container mx-auto py-8">
      <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        
        <!-- Contenu principal (3/4) -->
        <div class="lg:col-span-3 space-y-8">
          
          <!-- Section Derniers Tournois -->
          <section>
            <Card class="section-card">
              <template #content>
                <div class="section-placeholder">
                  <div class="text-center">
                    <h2 class="text-2xl font-bold text-gray-600 mb-2">DERNIERS TOURNOIS</h2>
                    <i class="pi pi-trophy text-4xl text-gray-400"></i>
                  </div>
                </div>
              </template>
            </Card>
          </section>

          <!-- Section Tournois à Venir -->
          <section>
            <Card class="section-card">
              <template #content>
                <div class="section-placeholder">
                  <div class="text-center">
                    <h2 class="text-2xl font-bold text-gray-600 mb-2">TOURNOIS À VENIR</h2>
                    <i class="pi pi-calendar text-4xl text-gray-400"></i>
                    <div class="mt-4">
                      <Button 
                        icon="pi pi-play" 
                        severity="secondary" 
                        rounded 
                        size="large"
                        class="opacity-60"
                      />
                    </div>
                  </div>
                </div>
              </template>
            </Card>
          </section>

          <!-- Section Marketplace -->
          <section>
            <Card class="section-card">
              <template #content>
                <div class="section-placeholder">
                  <div class="text-center">
                    <h2 class="text-2xl font-bold text-gray-600 mb-2">MARKETPLACE</h2>
                    <i class="pi pi-shopping-bag text-4xl text-gray-400"></i>
                  </div>
                </div>
              </template>
            </Card>
          </section>

          <!-- Section Meilleurs Decks -->
          <section>
            <h2 class="text-2xl font-bold text-gray-700 mb-6">MEILLEURS DECKS</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
              <Card 
                v-for="n in 5" 
                :key="n" 
                class="deck-card cursor-pointer hover:shadow-lg transition-shadow"
              >
                <template #content>
                  <div class="aspect-video bg-gray-300 rounded-lg"></div>
                </template>
              </Card>
            </div>
          </section>
        </div>

        <!-- Sidebar droite (1/4) -->
        <div class="lg:col-span-1">
          <div class="sticky top-8 space-y-6">
            
            <!-- Accéder à la carte -->
            <Card class="sidebar-card">
              <template #header>
                <div class="bg-red-400 text-white p-4 rounded-t-lg">
                  <h3 class="font-bold text-lg">Accéder à la carte</h3>
                </div>
              </template>
              <template #content>
                <div class="space-y-3">
                  <div class="text-sm font-medium text-gray-700 mb-4">
                    Les boutiques les plus populaires
                  </div>
                  
                  <!-- Liste des boutiques -->
                  <div 
                    v-for="shop in popularShops" 
                    :key="shop.id"
                    class="bg-white border border-gray-200 rounded-lg p-3 hover:bg-gray-50 cursor-pointer transition-colors"
                  >
                    <div class="flex items-center space-x-3">
                      <div class="w-12 h-12 bg-gray-200 rounded-lg flex items-center justify-center">
                        <i class="pi pi-shop text-gray-400"></i>
                      </div>
                      <div class="flex-1">
                        <div class="font-medium text-gray-900">{{ shop.name }}</div>
                        <div class="text-sm text-gray-500">{{ shop.description }}</div>
                      </div>
                    </div>
                  </div>

                  <!-- Indicateur de chargement -->
                  <div class="flex justify-center py-4">
                    <div class="w-8 h-8 border-4 border-gray-300 border-t-blue-500 rounded-full animate-spin"></div>
                  </div>
                </div>
              </template>
            </Card>

            <!-- Widget supplémentaire (optionnel) -->
            <Card class="sidebar-card">
              <template #content>
                <div class="text-center py-8">
                  <i class="pi pi-heart text-3xl text-gray-400 mb-3"></i>
                  <h4 class="font-medium text-gray-600">Vos favoris</h4>
                  <p class="text-sm text-gray-500 mt-2">
                    Aucun favori pour le moment
                  </p>
                </div>
              </template>
            </Card>
          </div>
        </div>
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
    name: 'LOREM - IPSUM',
    description: 'Neque porro quisquam est qui dolorem ipsum'
  },
  {
    id: 2,
    name: 'LOREM - IPSUM',
    description: 'Neque porro quisquam est qui dolorem ipsum'
  },
  {
    id: 3,
    name: 'LOREM - IPSUM',
    description: 'Neque porro quisquam est qui dolorem ipsum'
  },
  {
    id: 4,
    name: 'LOREM - IPSUM',
    description: 'Neque porro quisquam est qui dolorem ipsum'
  },
  {
    id: 5,
    name: 'LOREM - IPSUM',
    description: 'Neque porro quisquam est qui dolorem ipsum'
  }
])

// Lifecycle
onMounted(() => {
  console.log('🏠 Page d\'accueil chargée')
  // Ici vous pourrez charger les données réelles depuis l'API
})
</script>

<style scoped>
.home-page {
  min-height: calc(100vh - 200px);
}

.section-card {
  border: none;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.section-card :deep(.p-card-content) {
  padding: 0;
}

.section-placeholder {
  background: #e0e0e0;
  border-radius: 8px;
  min-height: 200px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #666;
}

.deck-card {
  border: none;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.deck-card :deep(.p-card-content) {
  padding: 1rem;
}

.sidebar-card {
  border: none;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.sidebar-card :deep(.p-card-header) {
  padding: 0;
}

.sidebar-card :deep(.p-card-content) {
  padding: 1rem;
}

/* Responsive */
@media (max-width: 1024px) {
  .sticky {
    position: static;
  }
}

@media (max-width: 640px) {
  .container {
    padding: 0 1rem;
  }
  
  .grid.grid-cols-4 {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
<template>
    <div class="test-page">
      <!-- Toast pour les notifications -->
      <Toast />
      
      <!-- Header avec navigation -->
      <Menubar :model="menuItems" class="mb-4">
        <template #start>
          <span class="text-xl font-bold text-primary">🃏 Mulligan TCG</span>
        </template>
        <template #end>
          <Badge value="DEV" severity="info" />
        </template>
      </Menubar>
  
      <!-- Hero Section -->
      <div class="hero-section mb-6 p-6 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg">
        <h1 class="text-4xl font-bold mb-2">Bienvenue sur Mulligan TCG</h1>
        <p class="text-xl opacity-90">Votre plateforme de gestion de cartes à collectionner</p>
      </div>
  
      <!-- Cards Grid -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <!-- Stats Card -->
        <Card class="p-4">
          <template #title>📊 Statistiques</template>
          <template #content>
            <div class="flex justify-between items-center mb-2">
              <span>Decks créés</span>
              <Badge :value="stats.decks" severity="success" />
            </div>
            <div class="flex justify-between items-center mb-2">
              <span>Cartes dans la collection</span>
              <Badge :value="stats.cards" severity="info" />
            </div>
            <div class="flex justify-between items-center">
              <span>Parties jouées</span>
              <Badge :value="stats.games" severity="warning" />
            </div>
          </template>
        </Card>
  
        <!-- Quick Actions Card -->
        <Card class="p-4">
          <template #title>⚡ Actions rapides</template>
          <template #content>
            <div class="flex flex-col gap-2">
              <Button 
                label="Créer un deck" 
                icon="pi pi-plus" 
                @click="showCreateDeck = true"
                class="w-full"
              />
              <Button 
                label="Rechercher cartes" 
                icon="pi pi-search" 
                severity="secondary"
                @click="showCardSearch = true"
                class="w-full"
              />
              <Button 
                label="Voir la map" 
                icon="pi pi-map" 
                severity="help"
                @click="showToast('Carte des boutiques bientôt disponible!')"
                class="w-full"
              />
            </div>
          </template>
        </Card>
  
        <!-- Status Card -->
        <Card class="p-4">
          <template #title>🔧 Status Dev</template>
          <template #content>
            <div class="space-y-2">
              <div class="flex items-center gap-2">
                <i class="pi pi-check-circle text-green-500"></i>
                <span>Backend Symfony</span>
              </div>
              <div class="flex items-center gap-2">
                <i class="pi pi-check-circle text-green-500"></i>
                <span>Frontend Vue.js</span>
              </div>
              <div class="flex items-center gap-2">
                <i class="pi pi-check-circle text-green-500"></i>
                <span>PrimeVue UI</span>
              </div>
              <div class="flex items-center gap-2">
                <i class="pi pi-clock text-orange-500"></i>
                <span>API REST (en cours)</span>
              </div>
            </div>
          </template>
        </Card>
      </div>
  
      <!-- Events Table -->
      <Card class="mb-6">
        <template #title>🎯 Événements à venir</template>
        <template #content>
          <DataTable :value="events" responsiveLayout="scroll">
            <Column field="name" header="Événement" />
            <Column field="date" header="Date" />
            <Column field="location" header="Lieu" />
            <Column field="game" header="Jeu" />
            <Column header="Actions">
              <template #body>
                <Button 
                  icon="pi pi-eye" 
                  size="small" 
                  severity="info"
                  @click="showToast('Détails de l\'événement')"
                />
              </template>
            </Column>
          </DataTable>
        </template>
      </Card>
  
      <!-- Create Deck Dialog -->
      <Dialog 
        v-model:visible="showCreateDeck" 
        modal 
        header="Créer un nouveau deck"
        :style="{ width: '450px' }"
      >
        <div class="flex flex-col gap-4">
          <div>
            <label for="deckName" class="block mb-2">Nom du deck</label>
            <InputText 
              id="deckName" 
              v-model="newDeck.name" 
              placeholder="Mon super deck"
              class="w-full"
            />
          </div>
          <div>
            <label for="deckGame" class="block mb-2">Jeu</label>
            <InputText 
              id="deckGame" 
              v-model="newDeck.game" 
              placeholder="Magic, Pokemon, Yu-Gi-Oh..."
              class="w-full"
            />
          </div>
        </div>
        <template #footer>
          <Button 
            label="Annuler" 
            severity="secondary" 
            @click="showCreateDeck = false"
          />
          <Button 
            label="Créer" 
            @click="createDeck"
          />
        </template>
      </Dialog>
  
      <!-- Card Search Dialog -->
      <Dialog 
        v-model:visible="showCardSearch" 
        modal 
        header="Recherche de cartes"
        :style="{ width: '600px' }"
      >
        <div class="flex flex-col gap-4">
          <div>
            <label for="searchQuery" class="block mb-2">Rechercher</label>
            <InputText 
              id="searchQuery" 
              v-model="searchQuery" 
              placeholder="Nom de la carte..."
              class="w-full"
            />
          </div>
          <div class="text-center text-gray-500">
            <i class="pi pi-search text-4xl mb-2"></i>
            <p>Recherche de cartes en cours de développement</p>
          </div>
        </div>
        <template #footer>
          <Button 
            label="Fermer" 
            @click="showCardSearch = false"
          />
        </template>
      </Dialog>
    </div>
  </template>
  
  <script setup>
  import { ref, onMounted } from 'vue'
  import { useToast } from 'primevue/usetoast'
  
  const toast = useToast()
  
  // Reactive data
  const stats = ref({
    decks: 0,
    cards: 0,
    games: 0
  })
  
  const events = ref([
    {
      name: 'Tournoi Magic Modern',
      date: '2025-07-30',
      location: 'Paris Games Store',
      game: 'Magic: The Gathering'
    },
    {
      name: 'Championship Pokemon',
      date: '2025-08-05',
      location: 'Lyon Cards Shop',
      game: 'Pokemon TCG'
    },
    {
      name: 'Yu-Gi-Oh Regional',
      date: '2025-08-12',
      location: 'Marseille Gaming',
      game: 'Yu-Gi-Oh!'
    }
  ])
  
  const menuItems = ref([
    {
      label: 'Accueil',
      icon: 'pi pi-home'
    },
    {
      label: 'Mes Decks',
      icon: 'pi pi-clone'
    },
    {
      label: 'Collection',
      icon: 'pi pi-th-large'
    },
    {
      label: 'Boutiques',
      icon: 'pi pi-map-marker'
    }
  ])
  
  const showCreateDeck = ref(false)
  const showCardSearch = ref(false)
  const newDeck = ref({ name: '', game: '' })
  const searchQuery = ref('')
  
  // Methods
  const showToast = (message) => {
    toast.add({
      severity: 'info',
      summary: 'Info',
      detail: message,
      life: 3000
    })
  }
  
  const createDeck = () => {
    if (newDeck.value.name && newDeck.value.game) {
      showToast(`Deck "${newDeck.value.name}" créé pour ${newDeck.value.game}!`)
      stats.value.decks++
      newDeck.value = { name: '', game: '' }
      showCreateDeck.value = false
    } else {
      toast.add({
        severity: 'warn',
        summary: 'Attention',
        detail: 'Veuillez remplir tous les champs',
        life: 3000
      })
    }
  }
  
  // Simulate loading stats
  onMounted(() => {
    setTimeout(() => {
      stats.value = {
        decks: 3,
        cards: 157,
        games: 12
      }
    }, 1000)
  })
  </script>
  
  <style scoped>
  .test-page {
    min-height: 100vh;
    padding: 1rem;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  }
  
  .hero-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  }
  
  /* Animation pour les cartes */
  .grid > * {
    animation: fadeInUp 0.6s ease-out;
  }
  
  @keyframes fadeInUp {
    from {
      opacity: 0;
      transform: translateY(20px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }
  
  /* Responsive adjustments */
  @media (max-width: 768px) {
    .test-page {
      padding: 0.5rem;
    }
    
    .hero-section {
      padding: 1rem;
    }
    
    .hero-section h1 {
      font-size: 2rem;
    }
    
    .hero-section p {
      font-size: 1rem;
    }
  }
  </style>
<template>
  <div class="tcg-test-page">
    <!-- Header avec menu -->
    <Menubar :model="menuItems" class="mb-4">
      <template #start>
        <div class="flex items-center gap-2">
          <i class="pi pi-heart-fill text-pink-500 text-2xl"></i>
          <span class="font-bold text-xl">MULLIGAN TCG</span>
        </div>
      </template>
      <template #end>
        <Badge value="DEV" severity="info" />
      </template>
    </Menubar>

    <div class="container mx-auto p-6">
      <!-- Hero Section -->
      <div class="hero-section text-center mb-8">
        <h1 class="text-5xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent mb-4">
          🃏 Bienvenue sur Mulligan TCG
        </h1>
        <p class="text-xl text-gray-600 dark:text-gray-300 mb-6">
          Votre plateforme ultime pour les Trading Card Games
        </p>
        <div class="flex gap-3 justify-center">
          <Button 
            label="Commencer" 
            icon="pi pi-play" 
            class="bg-gradient-to-r from-blue-500 to-purple-600 border-0"
            @click="showWelcomeDialog = true"
          />
          <Button 
            label="Voir les cartes" 
            icon="pi pi-search" 
            severity="secondary"
            outlined
            @click="loadCards"
          />
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <Card class="text-center hover:shadow-lg transition-shadow">
          <template #content>
            <div class="card-content">
              <i class="pi pi-users text-4xl text-blue-500 mb-3"></i>
              <h3 class="text-2xl font-bold mb-2">{{ stats.players }}</h3>
              <p class="text-gray-600">Joueurs actifs</p>
            </div>
          </template>
        </Card>

        <Card class="text-center hover:shadow-lg transition-shadow">
          <template #content>
            <div class="card-content">
              <i class="pi pi-bookmark text-4xl text-green-500 mb-3"></i>
              <h3 class="text-2xl font-bold mb-2">{{ stats.decks }}</h3>
              <p class="text-gray-600">Decks créés</p>
            </div>
          </template>
        </Card>

        <Card class="text-center hover:shadow-lg transition-shadow">
          <template #content>
            <div class="card-content">
              <i class="pi pi-trophy text-4xl text-yellow-500 mb-3"></i>
              <h3 class="text-2xl font-bold mb-2">{{ stats.tournaments }}</h3>
              <p class="text-gray-600">Tournois organisés</p>
            </div>
          </template>
        </Card>
      </div>

      <!-- Features Section -->
      <div class="features-grid grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Deck Builder Preview -->
        <Card>
          <template #title>
            <div class="flex items-center gap-2">
              <i class="pi pi-wrench text-blue-500"></i>
              Deck Builder
            </div>
          </template>
          <template #content>
            <p class="mb-4">Construisez vos decks parfaits avec notre outil intuitif.</p>
            <div class="flex gap-2 mb-4">
              <InputText v-model="searchCard" placeholder="Rechercher une carte..." class="flex-1" />
              <Button icon="pi pi-search" @click="searchCards" />
            </div>
            <div class="grid grid-cols-3 gap-2">
              <div 
                v-for="card in previewCards" 
                :key="card.id"
                class="card-preview bg-gradient-to-br from-blue-100 to-purple-100 dark:from-blue-800 dark:to-purple-800 p-3 rounded-lg text-center cursor-pointer hover:scale-105 transition-transform"
                @click="selectCard(card)"
              >
                <div class="text-2xl mb-1">{{ card.icon }}</div>
                <div class="text-sm font-medium">{{ card.name }}</div>
                <div class="text-xs text-gray-500">{{ card.cost }}</div>
              </div>
            </div>
          </template>
        </Card>

        <!-- Map Preview -->
        <Card>
          <template #title>
            <div class="flex items-center gap-2">
              <i class="pi pi-map text-green-500"></i>
              Boutiques à proximité
            </div>
          </template>
          <template #content>
            <p class="mb-4">Trouvez les boutiques et événements près de chez vous.</p>
            <div class="map-preview bg-gradient-to-br from-green-100 to-blue-100 dark:from-green-800 dark:to-blue-800 rounded-lg p-6 text-center">
              <i class="pi pi-map-marker text-4xl text-green-600 mb-3"></i>
              <p class="font-medium">Carte interactive</p>
              <p class="text-sm text-gray-600 dark:text-gray-300">{{ nearbyShops.length }} boutiques trouvées</p>
              <Button 
                label="Voir la carte" 
                icon="pi pi-external-link" 
                text 
                class="mt-2"
                @click="showMap"
              />
            </div>
          </template>
        </Card>
      </div>

      <!-- Data Table des événements -->
      <Card>
        <template #title>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <i class="pi pi-calendar text-purple-500"></i>
              Événements à venir
            </div>
            <Button 
              icon="pi pi-refresh" 
              text 
              @click="loadEvents"
              :loading="loading"
            />
          </div>
        </template>
        <template #content>
          <DataTable 
            :value="events" 
            :loading="loading"
            stripedRows 
            paginator 
            :rows="5"
            responsiveLayout="scroll"
          >
            <Column field="name" header="Événement" sortable>
              <template #body="{ data }">
                <div class="flex items-center gap-2">
                  <i :class="data.gameIcon"></i>
                  {{ data.name }}
                </div>
              </template>
            </Column>
            <Column field="date" header="Date" sortable />
            <Column field="location" header="Lieu" sortable />
            <Column field="participants" header="Participants" sortable>
              <template #body="{ data }">
                <Badge :value="data.participants" severity="success" />
              </template>
            </Column>
            <Column header="Actions">
              <template #body="{ data }">
                <Button 
                  icon="pi pi-eye" 
                  text 
                  @click="viewEvent(data)"
                  v-tooltip="'Voir détails'"
                />
              </template>
            </Column>
          </DataTable>
        </template>
      </Card>
    </div>

    <!-- Dialogs -->
    <Dialog 
      v-model:visible="showWelcomeDialog" 
      modal 
      header="Bienvenue !" 
      :style="{ width: '50rem' }"
    >
      <p class="mb-4">
        🎉 Félicitations ! Vous êtes connecté à l'environnement de développement de Mulligan TCG.
      </p>
      <p class="mb-4">
        Cette page de test démontre l'intégration de PrimeVue avec Vue.js 3 et Vite.
      </p>
      <ul class="list-disc list-inside mb-4 space-y-1">
        <li>✅ PrimeVue configuré et fonctionnel</li>
        <li>✅ Composants interactifs</li>
        <li>✅ Design moderne avec Tailwind CSS</li>
        <li>✅ Thème adaptatif (clair/sombre)</li>
      </ul>
      <template #footer>
        <Button label="Parfait !" @click="showWelcomeDialog = false" autofocus />
      </template>
    </Dialog>

    <!-- Toast messages -->
    <Toast />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'

const toast = useToast()

// Reactive data
const showWelcomeDialog = ref(false)
const loading = ref(false)
const searchCard = ref('')

const stats = reactive({
  players: 1247,
  decks: 3892,
  tournaments: 156
})

const menuItems = ref([
  {
    label: 'Accueil',
    icon: 'pi pi-home'
  },
  {
    label: 'Deck Builder',
    icon: 'pi pi-wrench'
  },
  {
    label: 'Boutiques',
    icon: 'pi pi-map'
  },
  {
    label: 'Événements',
    icon: 'pi pi-calendar'
  }
])

const previewCards = ref([
  { id: 1, name: 'Dragon Bleu', icon: '🐉', cost: '5 mana' },
  { id: 2, name: 'Éclair', icon: '⚡', cost: '2 mana' },
  { id: 3, name: 'Potion', icon: '🧪', cost: '1 mana' },
  { id: 4, name: 'Épée', icon: '⚔️', cost: '3 mana' },
  { id: 5, name: 'Bouclier', icon: '🛡️', cost: '2 mana' },
  { id: 6, name: 'Mage', icon: '🧙', cost: '4 mana' }
])

const nearbyShops = ref([
  { id: 1, name: 'Cards & Games', distance: '0.5km' },
  { id: 2, name: 'Magic Store', distance: '1.2km' },
  { id: 3, name: 'TCG Paradise', distance: '2.1km' }
])

const events = ref([
  {
    id: 1,
    name: 'Tournoi Magic Modern',
    date: '2025-07-25',
    location: 'Paris 11ème',
    participants: 32,
    gameIcon: 'pi pi-star-fill text-blue-500'
  },
  {
    id: 2,
    name: 'Draft Pokémon',
    date: '2025-07-26',
    location: 'Lyon Centre',
    participants: 16,
    gameIcon: 'pi pi-heart-fill text-red-500'
  },
  {
    id: 3,
    name: 'Yu-Gi-Oh! Local',
    date: '2025-07-27',
    location: 'Marseille',
    participants: 24,
    gameIcon: 'pi pi-bolt text-yellow-500'
  }
])

// Methods
const loadCards = () => {
  loading.value = true
  toast.add({
    severity: 'info',
    summary: 'Chargement',
    detail: 'Recherche de cartes en cours...',
    life: 2000
  })
  
  setTimeout(() => {
    loading.value = false
    toast.add({
      severity: 'success',
      summary: 'Succès',
      detail: `${previewCards.value.length} cartes trouvées !`,
      life: 3000
    })
  }, 1500)
}

const searchCards = () => {
  if (searchCard.value.trim()) {
    toast.add({
      severity: 'info',
      summary: 'Recherche',
      detail: `Recherche de "${searchCard.value}"...`,
      life: 2000
    })
  }
}

const selectCard = (card) => {
  toast.add({
    severity: 'success',
    summary: 'Carte sélectionnée',
    detail: `${card.name} ajoutée au deck !`,
    life: 2000
  })
}

const showMap = () => {
  toast.add({
    severity: 'info',
    summary: 'Navigation',
    detail: 'Ouverture de la carte interactive...',
    life: 2000
  })
}

const loadEvents = () => {
  loading.value = true
  setTimeout(() => {
    loading.value = false
    toast.add({
      severity: 'success',
      summary: 'Actualisé',
      detail: 'Liste des événements mise à jour !',
      life: 2000
    })
  }, 1000)
}

const viewEvent = (event) => {
  toast.add({
    severity: 'info',
    summary: 'Événement',
    detail: `Affichage de "${event.name}"`,
    life: 2000
  })
}

// Lifecycle
onMounted(() => {
  toast.add({
    severity: 'success',
    summary: 'Connexion réussie',
    detail: 'Page de test chargée avec succès !',
    life: 3000
  })
})
</script>

<style scoped>
.tcg-test-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
}

.dark-mode .tcg-test-page {
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
}

.hero-section {
  padding: 3rem 0;
}

.card-content {
  padding: 1.5rem 0;
}

.card-preview {
  min-height: 80px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.map-preview {
  min-height: 150px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.features-grid .p-card {
  height: 100%;
}

/* Animations */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.tcg-test-page > * {
  animation: fadeInUp 0.6s ease-out;
}
</style>
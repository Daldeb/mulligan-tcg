<template>
  <div class="container py-6">
    <h2 class="text-2xl font-bold mb-6 flex items-center gap-2">
      <i class="pi pi-calendar" /> Mes événements
      <Button 
        v-if="canCreateEvent"
        class="emerald-btn ml-auto"
        icon="pi pi-plus"
        label="Créer un événement"
        @click="onCreateEvent"
      />
    </h2>

    <!-- Loading State -->
    <div v-if="loading" class="events-loading-grid">
      <div v-for="i in 2" :key="i" class="event-skeleton"></div>
    </div>

    <!-- Aucune donnée -->
    <div v-else-if="!myEvents.length" class="text-center text-secondary my-12">
      <i class="pi pi-info-circle mr-2"></i>
      Aucun événement créé pour le moment.
    </div>

    <!-- Liste événements -->
    <div v-else class="grid md:grid-cols-2 xl:grid-cols-3 gap-6">
      <EventCard
        v-for="event in myEvents"
        :key="event.id"
        :event="event"
        :show-actions="true"
        @edit="onEditEvent"
        @delete="confirmDelete(event)"
      />
    </div>

    <!-- Dialog d'édition/création d'événement -->
    <Dialog
      v-model:visible="eventDialog"
      modal
      header="Créer/éditer un événement"
      :style="{ width: '650px' }"
      class="fade-in-scale"
      :closable="true"
      @hide="onCloseDialog"
    >
      <CreateEventView 
        v-if="eventDialog"
        :event-id="editingEvent?.id"
        @success="onEventSaved"
        @cancel="onCloseDialog"
      />
    </Dialog>

    <!-- Boîte de confirmation PrimeVue -->
    <ConfirmDialog></ConfirmDialog>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useEventStore } from '@/stores/events'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'

import EventCard from '@/components/events/EventCard.vue'
import CreateEventView from '@/views/CreateEventView.vue'

const eventStore = useEventStore()
const authStore = useAuthStore()
const toast = useToast()
const confirm = useConfirm()

const loading = ref(true)
const eventDialog = ref(false)
const editingEvent = ref(null)

// --- Permission pour créer event ---
const canCreateEvent = computed(() => authStore.canCreateEvent)

// --- ✅ CORRECTION : Liste mes events (RÉACTIF avec store) ---
const myEvents = computed(() => eventStore.myEvents)

// --- Charge mes events seulement si connecté ---
const loadMyEvents = async () => {
  loading.value = true
  try {
    if (authStore.isAuthenticated) {
      console.log('🔄 Chargement des événements utilisateur...')
      await eventStore.loadMyEvents()
      console.log('✅ Événements chargés:', eventStore.myEvents.length)
    }
  } catch (error) {
    console.error('❌ Erreur chargement mes événements:', error)
    toast.add({ 
      severity: 'error', 
      summary: 'Erreur', 
      detail: 'Impossible de charger vos événements', 
      life: 3000 
    })
  } finally {
    loading.value = false
  }
}

// ✅ CORRECTION : Chargement explicite au montage
onMounted(async () => {
  console.log('🚀 MyEventsView montée - User authentifié:', authStore.isAuthenticated)
  if (authStore.isAuthenticated) {
    await loadMyEvents()
  } else {
    loading.value = false
  }
})

// Sécurise le chargement avec watch (auth)
watch(
  () => authStore.isAuthenticated,
  async (isAuth) => {
    console.log('🔄 Auth changed:', isAuth)
    if (isAuth) {
      await loadMyEvents()
    } else {
      eventStore.myEvents = [] // Déconnecté, on clean
      loading.value = false
    }
  },
  { immediate: false } // ✅ CORRECTION : Pas immediate car géré dans onMounted
)

// -- Ouvre dialog création/edition
function onCreateEvent() {
  editingEvent.value = null
  eventDialog.value = true
}

function onEditEvent(event) {
  editingEvent.value = event
  eventDialog.value = true
}

function onCloseDialog() {
  eventDialog.value = false
  editingEvent.value = null
  // ✅ AMÉLIORATION : Rafraîchir seulement si nécessaire
  if (authStore.isAuthenticated) {
    loadMyEvents()
  }
}

// -- Confirmation suppression
function confirmDelete(event) {
  confirm.require({
    message: `Voulez-vous vraiment supprimer "${event.title}" ?`,
    header: 'Supprimer événement',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Oui, supprimer',
    rejectLabel: 'Annuler',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await eventStore.deleteEvent(event.id)
        toast.add({ 
          severity: 'success', 
          summary: 'Supprimé', 
          detail: 'Événement supprimé avec succès', 
          life: 2000 
        })
        // Pas besoin de loadMyEvents(), le store se met à jour automatiquement
      } catch (err) {
        console.error('❌ Erreur suppression:', err)
        toast.add({ 
          severity: 'error', 
          summary: 'Erreur', 
          detail: err.message || 'Erreur lors de la suppression', 
          life: 3000 
        })
      }
    }
  })
}

// -- Callback après save
function onEventSaved() {
  eventDialog.value = false
  toast.add({ 
    severity: 'success', 
    summary: 'Succès', 
    detail: 'Événement sauvegardé avec succès', 
    life: 2000 
  })
  // Pas besoin de loadMyEvents(), le store se met à jour automatiquement
}
</script>
<template>
  <div class="event-detail-page">
    <div class="container">
      <div class="page-header">
        <h1 class="page-title">Détail événement</h1>
        <p class="page-description">Page en cours de développement...</p>
        
        <div class="temp-actions">
          <Button 
            label="Retour aux événements"
            icon="pi pi-arrow-left"
            class="emerald-outline-btn"
            @click="$router.push({ name: 'evenements' })"
          />
        </div>
      </div>
      
      <Card class="temp-card">
        <template #content>
          <div class="temp-content">
            <i class="pi pi-eye temp-icon"></i>
            <h3>Détail événement</h3>
            <p>La page de détail sera bientôt disponible.</p>
            <p v-if="eventId">ID de l'événement : {{ eventId }}</p>
          </div>
        </template>
      </Card>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  eventId: {
    type: Number,
    required: true
  }
})
</script>

<style scoped>
.event-detail-page {
  min-height: calc(100vh - var(--header-height));
  background: var(--surface-gradient);
  padding: 2rem 0;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 2rem;
}

.page-header {
  margin-bottom: 2rem;
  text-align: center;
}

.page-title {
  font-size: 2.5rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0 0 0.5rem 0;
}

.page-description {
  font-size: 1.1rem;
  color: var(--text-secondary);
  margin: 0 0 2rem 0;
}

.temp-actions {
  display: flex;
  justify-content: center;
  gap: 1rem;
}

.temp-card {
  max-width: 500px;
  margin: 0 auto;
  text-align: center;
}

.temp-content {
  padding: 3rem 2rem;
}

.temp-icon {
  font-size: 4rem;
  color: var(--surface-400);
  margin-bottom: 1.5rem;
}

.temp-content h3 {
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0 0 1rem 0;
}

.temp-content p {
  color: var(--text-secondary);
  margin: 0 0 0.5rem 0;
}
</style>
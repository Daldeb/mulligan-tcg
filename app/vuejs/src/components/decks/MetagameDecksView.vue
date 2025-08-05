<template>
  <div class="metagame-placeholder">
    <Card class="gaming-card">
      <template #content>
        <div class="text-center py-6">
          <h2 class="text-primary mb-2">Decks Metagame</h2>
          <p class="text-secondary">
            Cette section affichera les decks metagame scrappés par jeu.
          </p>
          <p class="text-small text-muted mt-3">
            (Fonctionnalité en cours de développement pour Magic et Pokemon)
          </p>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup>
import Card from 'primevue/card'
</script>

<style scoped>
.metagame-placeholder {
  padding-top: 2rem;
}
</style>

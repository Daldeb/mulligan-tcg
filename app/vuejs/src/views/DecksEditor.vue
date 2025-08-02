<template>
  <HearthstoneDeckEditor 
    v-if="gameSlug === 'hearthstone'"
    :gameSlug="gameSlug"
    :formatSlug="formatSlug" 
    :deckSlug="deckSlug"
  />
  
  <div v-else-if="gameSlug === 'magic'" class="coming-soon">
    <div class="container">
      <h2>Magic Deck Editor</h2>
      <p>Bientôt disponible...</p>
      <Button label="Retour" icon="pi pi-arrow-left" @click="$router.push('/decks')" />
    </div>
  </div>
  
  <div v-else-if="gameSlug === 'pokemon'" class="coming-soon">
    <div class="container">
      <h2>Pokemon Deck Editor</h2>
      <p>Bientôt disponible...</p>
      <Button label="Retour" icon="pi pi-arrow-left" @click="$router.push('/decks')" />
    </div>
  </div>
  
  <div v-else class="coming-soon">
    <div class="container">
      <h2>Jeu non supporté</h2>
      <p>{{ gameSlug }} n'est pas encore disponible.</p>
      <Button label="Retour" icon="pi pi-arrow-left" @click="$router.push('/decks')" />
    </div>
  </div>
</template>

<script setup>
import HearthstoneDeckEditor from './HearthstoneDeckEditor.vue'

const props = defineProps({
  gameSlug: String,
  formatSlug: String, 
  deckSlug: String
})
</script>

<style scoped>
.coming-soon {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--surface-gradient);
  padding-top: var(--header-height);
}

.container {
  text-align: center;
  padding: 2rem;
}

.coming-soon h2 {
  color: var(--text-primary);
  margin-bottom: 1rem;
}

.coming-soon p {
  color: var(--text-secondary);
  margin-bottom: 2rem;
}
</style>